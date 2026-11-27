<?php
// Halaman toko & API publik (/api/catalog, /api/snapshot, /api/cart) untuk satu toko.
declare(strict_types=1);

function render_store(array $t, bool $demo = false): never
{
    $theme = $t['theme'];
    $file = APP_ROOT . "/themes/$theme/index.html";
    if (!preg_match('/^[a-z0-9-]+$/', $theme) || !is_file($file)) $file = APP_ROOT . '/themes/' . cfg('default_theme') . '/index.html';
    $html = (string) file_get_contents($file);

    $username = $t['username'] ?: $t['subdomain'];
    $name = trim($t['name']) ?: $username;
    $socials = json_decode($t['socials'] ?: '[]', true) ?: [];

    // Data toko menimpa nilai bawaan tema. Token TIDAK pernah dikirim ke browser.
    $over = [
        'username' => $username,
        'name' => $name,
        'role' => $t['role'],
        'about' => $t['about'],
        'avatar' => $t['avatar'],
        'socials' => $socials,
        'api' => ['catalog' => '/api/catalog', 'cart' => '/api/cart'],
        'snapshot' => '/api/snapshot',
    ];
    if (trim($t['hi']) !== '') $over['hi'] = $t['hi'];
    $json = json_encode($over, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    $configScript = "<script>Object.assign(window.CONFIG, $json);</script>";

    $first = preg_split('/\s+/', $name)[0] ?? $name;
    $repl = [
        '{{config}}' => $configScript,
        '{{v}}' => APP_VERSION,
        '{{title}}' => e($name),
        '{{description}}' => e("Toko online $name. Lihat produk dan bayar aman lewat lynk.id."),
        '{{name}}' => e($name),
        '{{username}}' => e($username),
        '{{slug}}' => e(strtolower(preg_replace('/[^a-z0-9]+/i', '', $username) ?: 'toko')),
        '{{first_upper}}' => e(mb_strtoupper($first)),
        '{{year}}' => date('Y'),
    ];
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-cache');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    echo strtr($html, $repl);
    if ($demo) echo demo_badge($t['theme']);
    exit;
}

/** Tombol kecil di halaman demo: nama tema + kembali ke galeri landing page. */
function demo_badge(string $theme): string
{
    $label = e(themes()[$theme] ?? $theme);
    $url = (string) cfg('landing_url');
    $style = 'position:fixed;z-index:35;left:max(14px,env(safe-area-inset-left,0px));bottom:calc(env(safe-area-inset-bottom,0px) + 22px);'
        . 'display:inline-flex;align-items:center;gap:8px;padding:10px 14px;border-radius:999px;background:#1D1B22;color:#fff;'
        . "font:600 13px/1 system-ui,-apple-system,'Segoe UI',sans-serif;letter-spacing:0;text-transform:none;text-decoration:none;box-shadow:0 10px 24px -10px rgba(0,0,0,.6)";
    $inner = '<span style="opacity:.7">Demo</span> <b>' . $label . '</b>';
    return $url !== ''
        ? "\n<a href=\"" . e($url) . "\" style=\"$style\" aria-label=\"Kembali ke galeri template\">← Galeri · $inner</a>"
        : "\n<span style=\"$style\">$inner</span>";
}

function api_catalog(array $t): never
{
    try {
        json_out(200, tenant_catalog($t), 60);
    } catch (Throwable $e) {
        json_out(502, ['error' => 'Gagal mengambil data dari lynk.id.']);
    }
}

function api_snapshot(array $t): never
{
    $hit = cache_get((int) $t['id'], 'catalog');
    $hit ? json_out(200, $hit['data'] + ['snapshot' => true], 60) : json_out(404, ['error' => 'Belum ada data tersimpan.']);
}

function api_cart(array $t): never
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Allow: POST');
        json_out(405, ['error' => 'Gunakan POST.']);
    }
    if (too_many('cart:' . $t['id'] . ':' . client_ip(), 6)) {
        json_out(429, ['error' => 'Terlalu banyak percobaan. Tunggu satu menit, lalu coba lagi.']);
    }
    $body = json_decode((string) file_get_contents('php://input'), true);
    $raw = is_array($body['items'] ?? null) ? $body['items'] : [];
    if (count($raw) < 1 || count($raw) > 20) json_out(400, ['error' => 'Keranjang harus berisi 1 sampai 20 produk.']);

    try {
        $catalog = tenant_catalog($t);
    } catch (Throwable $e) {
        json_out(502, ['error' => 'Gagal menghubungi lynk.id. Coba lagi sebentar lagi.']);
    }
    $byId = [];
    foreach ($catalog['products'] as $p) $byId[(string) $p['id']] = $p;

    $items = [];
    foreach ($raw as $it) {
        $p = $byId[(string) ($it['block_id'] ?? '')] ?? null;
        if (!$p || empty($p['cart_ok'])) {
            json_out(400, ['error' => '"' . ($p['name'] ?? 'Produk') . '" tidak bisa dibeli lewat keranjang. Beli langsung dari kartunya.']);
        }
        $max = $p['limit_per_checkout'] ?: 99;
        $items[] = ['block_id' => $p['id'], 'quantity' => max(1, min($max, (int) ($it['quantity'] ?? 1)))];
    }

    $voucher = strtoupper(trim((string) ($body['voucher_code'] ?? '')));
    if ($voucher !== '' && !preg_match('/^[A-Z0-9_-]{1,50}$/', $voucher)) {
        json_out(400, ['error' => 'Kode voucher hanya boleh huruf, angka, - dan _.']);
    }

    $payload = ['items' => $items] + ($voucher !== '' ? ['voucher_code' => $voucher] : []);
    $r = lynk_request(tenant_token($t), 'POST', '/v1/carts', $payload);
    if (!$r['ok']) {
        if ($r['code'] === 'CART_ITEMS_INVALID') {
            $lines = array_map(fn($l) => [
                'block_id' => $l['block_id'] ?? null,
                'name' => $byId[(string) ($l['block_id'] ?? '')]['name'] ?? null,
                'code' => $l['code'] ?? null,
                'message' => $l['message'] ?? null,
            ], $r['data']['lines'] ?? []);
            json_out(422, ['error' => 'Ada produk yang tidak bisa dipesan lewat keranjang.', 'lines' => $lines]);
        }
        if ($r['status'] === 429) json_out(429, ['error' => 'lynk.id sedang sibuk. Coba lagi sebentar lagi.']);
        json_out(502, ['error' => 'lynk.id belum bisa membuat keranjang. Coba lagi sebentar lagi.']);
    }

    $d = $r['data'];
    $url = (string) $d['cart_url'];
    $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query(['utm_source' => 'website', 'utm_medium' => 'landing', 'utm_content' => 'cart']);
    json_out(200, [
        'cart_url' => $url,
        'subtotal' => $d['subtotal'] ?? null,
        'discount' => $d['discount'] ?? null,
        'total' => $d['total'] ?? null,
        'voucher' => $d['voucher'] ?? null,
        'expires_at' => $d['expires_at'] ?? null,
    ]);
}

function not_found_page(string $host): never
{
    http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="id"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<title>Toko tidak ditemukan</title><body style="margin:0;min-height:100vh;display:grid;place-items:center;font:16px/1.6 system-ui,sans-serif;background:#F6F4EF;color:#222;text-align:center;padding:24px">'
        . '<div><h1 style="margin:0 0 8px;font-size:28px">Toko tidak ditemukan</h1><p style="margin:0;color:#666">Alamat <b>' . e($host) . '</b> belum terdaftar atau sedang tidak aktif.</p></div></body></html>';
    exit;
}
