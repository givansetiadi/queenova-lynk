<?php
// Router utama. Semua subdomain diarahkan ke folder ini; subdomain menentukan toko mana yang tampil.
//   admin.<domain>        → panel admin
//   <toko>.<domain>       → halaman toko + /api/catalog, /api/snapshot, /api/cart
//   domain sendiri        → dicocokkan dengan kolom "domain" toko
declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

// Server bawaan PHP (tes lokal): biarkan file statis bersama dilayani langsung.
if (PHP_SAPI === 'cli-server' && in_array($path, ['/app.js', '/base.css'], true)) return false;

require __DIR__ . '/src/bootstrap.php';
require __DIR__ . '/src/lynk.php';
require __DIR__ . '/src/store.php';

$host = request_host();
$label = host_label($host);

if ($label === cfg('admin_subdomain')) {
    require __DIR__ . '/src/admin.php';
    admin_main();
}

$tenant = find_tenant($host);
if (!$tenant) not_found_page($host);

// Mode demo: demo.<domain>/<tema> menampilkan toko demo dengan tema pilihan.
$demo = cfg('demo_subdomain');
if ($demo && $label === $demo && preg_match('~^/([a-z0-9-]+)/?$~', $path, $m) && isset(themes()[$m[1]])) {
    $tenant['theme'] = $m[1];
    render_store($tenant, true);
}

match ($path) {
    '/', '/index.html' => render_store($tenant),
    '/api/catalog' => api_catalog($tenant),
    '/api/snapshot' => api_snapshot($tenant),
    '/api/cart' => api_cart($tenant),
    default => (function () { http_response_code(404); header('Content-Type: text/plain; charset=utf-8'); echo 'Halaman tidak ditemukan.'; exit; })(),
};
