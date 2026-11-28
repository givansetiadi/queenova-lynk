# Queenova Lynk

Jasa template halaman toko untuk penjual lynk.id — landing page, aplikasi multi-toko, dan sumber tema dalam satu repo.

```
queenova-lynk/
├── landing/     Landing page jualan (HTML statis)      → lynk.queenovatech.com
├── app/         Aplikasi multi-toko PHP (Hostinger)    → admin.*, demo.*, <toko>.queenovatech.com
└── templates/   Sumber 60 tema (+ _base, pastel)       → tidak di-deploy, di-build ke app/themes
```

## Alur kerja tema

1. Ubah/buat tema di `templates/<tema>/`.
2. Build ke aplikasi:
   ```
   cd app
   node tools/build-themes.mjs
   ```
3. Kalau tema baru: tambahkan nama tampilannya di `LABELS` (`app/tools/build-themes.mjs`)
   dan kartu + harganya di `landing/index.html` (`CONFIG.templates`, `TEMPLATE_TIER`).
4. Commit & push.

## Deploy ke production (Hostinger)

Lihat panduan lengkap di `app/README.md`. Ringkasnya:

| Subdomain | Folder di server | Isi |
|---|---|---|
| `lynk.queenovatech.com` | `public_html/queenova-lynk/landing` | Landing page |
| `admin.queenovatech.com` | `public_html/queenova-lynk/app` | Panel admin |
| `demo.queenovatech.com` | `public_html/queenova-lynk/app` | Demo semua tema (`/sakura`, `/bento`, …) |
| `<toko>.queenovatech.com` | `public_html/queenova-lynk/app` | Toko pembeli |

Sekali saja di server:
- Salin `app/config.sample.php` → `app/config.php`, isi `app_key` (simpan cadangannya!).
- Di admin, buat toko dengan subdomain **`demo`** supaya tombol "Coba langsung" di landing berfungsi.

## Rahasia (tidak ada di repo)

- `app/config.php` — berisi `app_key` enkripsi token.
- `app/data/` — database SQLite.
- `.env` / `.env.local` — `LYNK_API_KEY` untuk tes tema lokal.
