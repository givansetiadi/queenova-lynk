# Etalase lynk.id — multi-toko (PHP, untuk Hostinger)

Satu aplikasi untuk semua pembeli template. Tiap pembeli dapat subdomain sendiri
(`tokobudi.queenovatech.com`), memakai token lynk.id miliknya, dan tema pilihannya.

```
tokobudi.queenovatech.com ─┐
tokosari.queenovatech.com ─┼─► folder aplikasi yang SAMA ─► baca subdomain ─► token & tema toko itu
admin.queenovatech.com    ─┘                                 (token disimpan terenkripsi AES-256-GCM)
```

## Isi folder

| Path | Fungsi |
|---|---|
| `index.php` | Router: admin / halaman toko / API |
| `src/` | Logika: database, enkripsi, lynk.id API, admin |
| `themes/` | 55 tema (hasil `tools/build-themes.mjs`) |
| `app.js`, `base.css` | Logika & gaya bersama semua tema |
| `config.php` | Pengaturan + **app_key** (kunci enkripsi) |
| `data/` | Database SQLite (dibuat otomatis, diblokir dari web) |

## Pasang di Hostinger (sekali saja)

1. **Cek PHP**: hPanel → Website → Advanced → *PHP Configuration* → pilih **PHP 8.2 atau lebih baru**.
   Pastikan ekstensi `curl`, `openssl`, `pdo_sqlite` aktif (biasanya sudah).
2. **Upload**: hPanel → *File Manager* → buat folder, misalnya `public_html/etalase`,
   lalu upload seluruh isi folder ini **kecuali `data/app.sqlite`** (itu database tes lokal).
3. **Subdomain admin**: hPanel → *Domains → Subdomains* → buat `admin`,
   centang *Custom folder* → arahkan ke `public_html/etalase`.
4. **SSL**: hPanel → *Security → SSL* → pasang SSL gratis untuk `admin.queenovatech.com`.
5. Buka `https://admin.queenovatech.com` → buat password admin (minimal 10 karakter).

> `config.php` berisi `app_key`. **Simpan cadangannya.** Kalau app_key hilang/berubah,
> semua token tersimpan tidak bisa dibuka dan harus diinput ulang.

## Setiap ada pembeli baru

1. **hPanel → Subdomains** → buat subdomain (mis. `tokobudi`) dengan *Custom folder* → `public_html/etalase`.
   Pasang SSL gratis untuk subdomain itu.
2. **Admin** (`admin.queenovatech.com`) → **+ Tambah toko** → isi subdomain, tema, token. Simpan.
   Token langsung dicek ke lynk.id; username terisi otomatis.
3. Selesai: `https://tokobudi.queenovatech.com` langsung tampil dengan produk pembeli.

> Ingin lewati langkah 1? Buat **wildcard subdomain** `*` di hPanel yang mengarah ke folder yang sama.
> Cek dulu apakah paket Hostinger kamu mendukung wildcard **dan** SSL wildcard; kalau tidak, tetap pakai langkah 1.

**Domain sendiri pembeli** (mis. `tokobudi.com`): tambahkan domain itu di hPanel sebagai *addon/parked domain*
yang mengarah ke folder yang sama, lalu isi kolom *Domain sendiri* di admin.

## Yang perlu dikirim pembeli

- **Token API lynk.id**: lynk.id → *Settings → Integrations → API Keys* → buat key baru.
  Sarankan membuat key **khusus untukmu** (lynk.id mengizinkan 2 key aktif) supaya bisa dicabut kapan saja.
- (Opsional) nama toko, bio, link foto profil, link sosmed.

## Admin

- **Ubah**: ganti tema, profil, atau token (kosongkan kolom token kalau tidak diganti).
- **Muat ulang**: ambil ulang katalog dari lynk.id sekarang juga (normalnya otomatis tiap 5 menit).
- **Nonaktifkan**: toko menampilkan "Toko tidak ditemukan" (mis. langganan habis). Data tetap tersimpan.
- **Hapus**: hapus toko + tokennya permanen.

## Tes di komputer

```
cd D:\project\lynk-saas
C:\php84\php.exe -S 127.0.0.1:8080 index.php
```
Buka `http://admin.localhost:8080` (admin) dan `http://<subdomain>.localhost:8080` (toko).

## Menambah / memperbarui tema

Buat atau ubah tema di `../templates`, lalu jalankan (dari folder `app/`):
```
node tools/build-themes.mjs
```
Tema baru otomatis muncul di pilihan admin. Tambahkan nama tampilannya di `LABELS` dalam skrip itu.

## Keamanan

- Token tidak pernah dikirim ke browser; hanya dipakai server untuk memanggil api.lynk.id.
- Token disimpan terenkripsi AES-256-GCM dengan `app_key`; admin hanya melihat 4 karakter terakhir.
- `data/`, `src/`, `themes/`, `config.php` diblokir dari akses web (`.htaccess`).
- Login admin dibatasi 10 percobaan / 10 menit; keranjang dibatasi 6 permintaan / menit per pengunjung.
