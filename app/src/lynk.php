<?php
// Klien Lynk.id Open API (https://api.lynk.id/docs) + katalog ber-cache per toko.
declare(strict_types=1);

const LYNK_BASE = 'https://api.lynk.id';
const CART_BLOCKED_TYPES = ['event', 'appointment', 'support'];
const MAX_DETAIL = 40; // batas panggilan detail per refresh (limit API 60 req/menit per token)

function lynk_request(string $token, string $method, string $path, ?array $body = null): array
{
    $ch = curl_init(LYNK_BASE . $path);
    $headers = ['Authorization: Bearer ' . $token, 'Accept: application/json'];
    if ($body !== null) $headers[] = 'Content-Type: application/json';
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_USERAGENT => 'etalase-lynk/' . APP_VERSION,
    ]);
    if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    if ($raw === false) {
        return ['ok' => false, 'status' => 0, 'code' => 'NETWORK', 'message' => curl_error($ch), 'data' => null];
    }
    $j = json_decode((string) $raw, true);
    return [
        'ok' => $status >= 200 && $status < 300,
        'status' => $status,
        'code' => $j['code'] ?? null,
        'message' => $j['message'] ?? null,
        'data' => $j['data'] ?? null,
    ];
}

function tenant_token(array $tenant): string
{
    return decrypt_secret($tenant['token_enc']);
}

/** Cek token & ambil username lynk.id dari link checkout produk pertama. */
function verify_token(string $token): array
{
    $r = lynk_request($token, 'GET', '/v1/blocks?limit=5');
    if (!$r['ok']) {
        $msg = match ($r['code']) {
            'INVALID_API_KEY' => 'Token tidak valid (salah, dicabut, atau tidak dikenal).',
            'API_KEY_EXPIRED' => 'Token sudah kedaluwarsa. Minta pembeli membuat token baru.',
            'API_ACCESS_DISABLED' => 'Akun lynk.id ini belum punya akses API.',
            'NETWORK' => 'Tidak bisa menghubungi lynk.id: ' . $r['message'],
            default => 'lynk.id menolak token (' . ($r['code'] ?? 'HTTP ' . $r['status']) . ').',
        };
        return ['ok' => false, 'error' => $msg];
    }
    $username = '';
    foreach ($r['data']['items'] ?? [] as $b) {
        if (preg_match('~lynk\.id/([A-Za-z0-9._-]+)/~', (string) ($b['checkout_url'] ?? ''), $m) && $m[1] !== 's') {
            $username = $m[1];
            break;
        }
    }
    return ['ok' => true, 'username' => $username, 'count' => count($r['data']['items'] ?? [])];
}

function cart_eligible(array $b, bool $inStock): bool
{
    return !in_array($b['type'] ?? '', CART_BLOCKED_TYPES, true)
        && ($b['price_type'] ?? '') === 'fixed'
        && ($b['price'] ?? null) !== null
        && empty($b['has_variants'])
        && empty($b['has_required_addon'])
        && $inStock;
}

function build_catalog(string $token): array
{
    $blocks = [];
    $cursor = null;
    for ($page = 0; $page < 20; $page++) {
        $qs = ['limit' => 100];
        if ($cursor) $qs['cursor'] = $cursor;
        $r = lynk_request($token, 'GET', '/v1/blocks?' . http_build_query($qs));
        if (!$r['ok']) throw new RuntimeException($r['code'] ?? 'HTTP ' . $r['status']);
        array_push($blocks, ...($r['data']['items'] ?? []));
        $next = $r['data']['next_cursor'] ?? null;
        if (!$next || $next === $cursor) break;
        $cursor = $next;
    }

    $products = [];
    foreach ($blocks as $i => $b) {
        $d = null;
        if ($i < MAX_DETAIL) {
            $r = lynk_request($token, 'GET', '/v1/blocks/' . rawurlencode((string) $b['block_id']));
            $d = $r['ok'] ? $r['data'] : null;
        }
        $q = $d['quantity'] ?? null;
        $inStock = $q ? (!empty($q['is_unlimited']) || ($q['in_stock'] ?? true) !== false) : true;
        $checkout = $b['checkout_url'] ?? null;
        $products[] = [
            'id' => $b['block_id'],
            'name' => $b['name'],
            'type' => $b['type'],
            'price' => $b['price'],
            'original_price' => $b['original_price'],
            'price_type' => $b['price_type'],
            'min_price' => $b['min_price'],
            'image' => $b['image_url'],
            'has_variants' => (bool) $b['has_variants'],
            'requires_shipping' => (bool) $b['requires_shipping'],
            'url' => $checkout ? preg_replace('~/checkout/?$~', '', $checkout) : null,
            'checkout_url' => $checkout,
            'description' => $d['description_text'] ?? '',
            'in_stock' => $inStock,
            'limit_per_checkout' => (int) ($q['limit_per_checkout'] ?? 0),
            'cart_ok' => cart_eligible($b, $inStock),
        ];
    }

    $pm = ['items' => [], 'fee_paid_by_seller' => null];
    $r = lynk_request($token, 'GET', '/v1/payment-methods');
    if ($r['ok'] && $r['data']) $pm = $r['data'];

    return [
        'products' => $products,
        'payment_methods' => array_map(fn($m) => [
            'code' => $m['code'], 'name' => $m['name'], 'category' => $m['category'], 'image' => $m['image_url'],
        ], $pm['items'] ?? []),
        'fee_paid_by_seller' => $pm['fee_paid_by_seller'] ?? null,
        'updated_at' => gmdate('c'),
    ];
}

function cache_get(int $tenantId, string $kind): ?array
{
    $st = db()->prepare('SELECT body, updated_at FROM cache WHERE tenant_id = ? AND kind = ?');
    $st->execute([$tenantId, $kind]);
    $row = $st->fetch();
    return $row ? ['data' => json_decode($row['body'], true), 'age' => time() - (int) $row['updated_at']] : null;
}

function cache_put(int $tenantId, string $kind, array $data): void
{
    db()->prepare('INSERT INTO cache(tenant_id, kind, body, updated_at) VALUES(?, ?, ?, ?)
        ON CONFLICT(tenant_id, kind) DO UPDATE SET body = excluded.body, updated_at = excluded.updated_at')
        ->execute([$tenantId, $kind, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), time()]);
}

/** Katalog toko: dari cache kalau masih segar, ambil ulang kalau kedaluwarsa, pakai data lama kalau lynk.id gagal. */
function tenant_catalog(array $tenant): array
{
    $id = (int) $tenant['id'];
    $hit = cache_get($id, 'catalog');
    $ttl = (int) cfg('catalog_ttl');
    if ($hit && $hit['age'] < $ttl) return $hit['data'];
    if ($hit) {
        // Tandai sedang diperbarui (30 detik) supaya pengunjung lain memakai data lama, tidak ikut memanggil API.
        db()->prepare('UPDATE cache SET updated_at = ? WHERE tenant_id = ? AND kind = ?')->execute([time() - $ttl + 30, $id, 'catalog']);
    }
    try {
        $fresh = build_catalog(tenant_token($tenant));
        cache_put($id, 'catalog', $fresh);
        return $fresh;
    } catch (Throwable $e) {
        if ($hit) return $hit['data'];
        throw $e;
    }
}
