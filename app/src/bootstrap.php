<?php
// Inti aplikasi: konfigurasi, database SQLite, enkripsi token, helper umum.
declare(strict_types=1);

const APP_ROOT = __DIR__ . '/..';
const APP_VERSION = '1.0.0';
const RESERVED_SUBDOMAINS = ['admin', 'www', 'mail', 'ftp', 'api', 'cpanel', 'webmail', 'smtp', 'pop', 'imap', 'ns1', 'ns2', 'autodiscover'];

function cfg(?string $key = null): mixed
{
    static $c = null;
    if ($c === null) {
        $file = APP_ROOT . '/config.php';
        if (!is_file($file)) {
            http_response_code(500);
            exit('config.php belum ada. Salin config.sample.php menjadi config.php lalu isi app_key.');
        }
        $c = require $file;
    }
    return $key === null ? $c : ($c[$key] ?? null);
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo) return $pdo;
    $path = cfg('db_path');
    if (!is_dir(dirname($path))) mkdir(dirname($path), 0775, true);
    $pdo = new PDO('sqlite:' . $path, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA busy_timeout = 5000');
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS tenants (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            subdomain TEXT NOT NULL UNIQUE,
            domain TEXT UNIQUE,
            theme TEXT NOT NULL,
            token_enc TEXT NOT NULL,
            username TEXT NOT NULL DEFAULT '',
            name TEXT NOT NULL DEFAULT '',
            hi TEXT NOT NULL DEFAULT '',
            role TEXT NOT NULL DEFAULT '',
            about TEXT NOT NULL DEFAULT '',
            avatar TEXT NOT NULL DEFAULT '',
            socials TEXT NOT NULL DEFAULT '[]',
            note TEXT NOT NULL DEFAULT '',
            active INTEGER NOT NULL DEFAULT 1,
            created_at INTEGER NOT NULL,
            updated_at INTEGER NOT NULL
        );
        CREATE TABLE IF NOT EXISTS cache (
            tenant_id INTEGER NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
            kind TEXT NOT NULL,
            body TEXT NOT NULL,
            updated_at INTEGER NOT NULL,
            PRIMARY KEY (tenant_id, kind)
        );
        CREATE TABLE IF NOT EXISTS settings (k TEXT PRIMARY KEY, v TEXT NOT NULL);
        CREATE TABLE IF NOT EXISTS hits (bucket TEXT NOT NULL, ts INTEGER NOT NULL);
        CREATE INDEX IF NOT EXISTS hits_bucket_ts ON hits(bucket, ts);
    SQL);
    return $pdo;
}

function setting(string $k, ?string $set = null): ?string
{
    if ($set !== null) {
        db()->prepare('INSERT INTO settings(k, v) VALUES(?, ?) ON CONFLICT(k) DO UPDATE SET v = excluded.v')->execute([$k, $set]);
        return $set;
    }
    $st = db()->prepare('SELECT v FROM settings WHERE k = ?');
    $st->execute([$k]);
    $v = $st->fetchColumn();
    return $v === false ? null : $v;
}

/* ---------- Enkripsi token (AES-256-GCM) ---------- */
function app_key(): string
{
    $k = (string) cfg('app_key');
    if (str_starts_with($k, 'base64:')) $k = base64_decode(substr($k, 7), true) ?: '';
    if (strlen($k) !== 32) {
        http_response_code(500);
        exit('app_key di config.php harus 32 byte (format base64:...).');
    }
    return $k;
}

function encrypt_secret(string $plain): string
{
    $iv = random_bytes(12);
    $tag = '';
    $cipher = openssl_encrypt($plain, 'aes-256-gcm', app_key(), OPENSSL_RAW_DATA, $iv, $tag);
    return base64_encode($iv . $tag . $cipher);
}

function decrypt_secret(string $enc): string
{
    $raw = base64_decode($enc, true);
    if ($raw === false || strlen($raw) < 29) throw new RuntimeException('Token tersimpan rusak.');
    $plain = openssl_decrypt(substr($raw, 28), 'aes-256-gcm', app_key(), OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16));
    if ($plain === false) throw new RuntimeException('Token tidak bisa dibuka. Apakah app_key berubah?');
    return $plain;
}

/* ---------- Host & toko ---------- */
function request_host(): string
{
    return preg_replace('/:\d+$/', '', strtolower($_SERVER['HTTP_HOST'] ?? ''));
}

/** Label subdomain untuk domain dasar yang dikenal ('' = domain utama, null = domain lain). */
function host_label(string $host): ?string
{
    foreach ((array) cfg('base_domains') as $base) {
        if ($host === $base) return '';
        if (str_ends_with($host, '.' . $base)) return substr($host, 0, -strlen($base) - 1);
    }
    return null;
}

function find_tenant(string $host): ?array
{
    $label = host_label($host);
    if ($label !== null && $label !== '') {
        $st = db()->prepare('SELECT * FROM tenants WHERE subdomain = ? AND active = 1');
        $st->execute([$label]);
    } else {
        $st = db()->prepare('SELECT * FROM tenants WHERE domain = ? AND active = 1');
        $st->execute([$host]);
    }
    return $st->fetch() ?: null;
}

function themes(): array
{
    static $t = null;
    $t ??= json_decode((string) file_get_contents(APP_ROOT . '/themes/themes.json'), true) ?: [];
    return $t;
}

/* ---------- Pembatas sederhana ---------- */
function too_many(string $bucket, int $limit, int $window = 60): bool
{
    $now = time();
    $pdo = db();
    $pdo->prepare('DELETE FROM hits WHERE ts < ?')->execute([$now - 3600]);
    $st = $pdo->prepare('SELECT COUNT(*) FROM hits WHERE bucket = ? AND ts >= ?');
    $st->execute([$bucket, $now - $window]);
    if ((int) $st->fetchColumn() >= $limit) return true;
    $pdo->prepare('INSERT INTO hits(bucket, ts) VALUES(?, ?)')->execute([$bucket, $now]);
    return false;
}

function client_ip(): string
{
    return $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0';
}

function json_out(int $status, array $body, int $maxAge = 0): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: ' . ($maxAge > 0 ? "public, max-age=$maxAge" : 'no-store'));
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
