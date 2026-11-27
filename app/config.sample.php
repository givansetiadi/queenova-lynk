<?php
// Salin file ini menjadi config.php, lalu isi app_key.
// Buat app_key baru (sekali saja, JANGAN diganti setelah ada toko karena token tersimpan tidak bisa dibuka lagi):
//   php -r "echo 'base64:' . base64_encode(random_bytes(32)), PHP_EOL;"
return [
    // Domain dasar. Toko = <subdomain>.<domain>. "localhost" untuk tes lokal.
    'base_domains' => ['queenovatech.com', 'localhost'],
    // Subdomain panel admin → admin.queenovatech.com
    'admin_subdomain' => 'admin',
    // Kunci enkripsi token (32 byte, base64). WAJIB diisi & disimpan baik-baik.
    'app_key' => 'base64:ISI_DENGAN_KUNCI_ACAK',
    // Lokasi database SQLite (folder data/ sudah diblokir dari web).
    'db_path' => __DIR__ . '/data/app.sqlite',
    // Lama cache katalog per toko (detik).
    'catalog_ttl' => 300,
    // Tema bawaan kalau tema toko tidak ditemukan.
    'default_theme' => 'pastel',
    // Mode demo: toko dengan subdomain ini bisa menampilkan tema apa saja lewat alamat
    // demo.<domain>/<tema>, mis. demo.queenovatech.com/sakura. Buat tokonya di admin (subdomain "demo").
    'demo_subdomain' => 'demo',
    // Tujuan tombol "← Galeri" di halaman demo (landing page). Kosongkan untuk menyembunyikan tombol.
    'landing_url' => 'https://lynk.queenovatech.com/#template',
];
