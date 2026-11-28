# Template lynk.id

Kumpulan template halaman toko yang tersambung ke lynk.id lewat Open API.
Tiap folder tema berdiri sendiri dan bisa di-deploy terpisah (Vercel).

| Folder | Tema |
|---|---|
| `alam/` | Pemandangan gunung & pinus, papan kayu, hijau hutan |
| `sage/` | Kalem, hijau sage, bingkai lengkung (arch), serif miring |
| `kawaii-pink/` | Pink polkadot, pita merah, label bintang, font bulat |
| `papan-kapur/` | Papan tulis menu kafe, tulisan kapur, bingkai kayu (kuliner) |
| `retro-pixel/` | Komputer jadul 90-an, jendela timbul, font pixel, stiker Y2K |
| `batik-kawung/` | Elegan Nusantara: motif kawung, indigo, soga, emas |
| `pantai-tropis/` | Laut toska, ombak bergerak, matahari, aksen coral |
| `neon-malam/` | Gelap, cahaya neon, grid synthwave |
| `scrapbook/` | Buku catatan, polaroid berselotip washi, tulisan tangan |
| `bento/` | Modern minimalis, ubin warna-warni |
| `majalah/` | Sampul majalah: masthead raksasa, judul sampul, barcode, daftar isi |
| `komik/` | Pop art: halftone, panel tebal, balon kata, ledakan POW! |
| `galaksi/` | Luar angkasa: planet bercincin, bintang jatuh, bulan mengorbit |
| `nota-kasir/` | Struk belanja: tepi bergerigi, font kasir, produk sebagai baris item |
| `clay/` | Claymorphism: elemen empuk 3D, warna permen, bentuk melayang |
| `kertas-lipat/` | Kerajinan kertas: pelangi berlapis, awan, origami |
| `lofi/` | Kamar malam hujan, lampu hangat, stiker "now playing" |
| `sakura/` | Kelopak berguguran, pola seigaiha, cap merah |
| `lebaran/` | Ramadan/Idulfitri: lentera, bulan sabit, ketupat, bingkai masjid |
| `toko-kue/` | Lelehan krim, sprinkles, tombol macaron |
| `marmer/` | Marmer putih & hijau gelap, emas foil, huruf Didone |
| `art-deco/` | Glamor 1920-an: hitam-emas, pola kipas, bingkai bersudut |
| `amalfi/` | Pesisir Italia: ubin biru kobalt, lemon, dinding putih |
| `velvet/` | Beludru merah anggur, tirai panggung, cahaya lilin |
| `galeri-seni/` | Produk = lukisan berbingkai + label museum |
| `aurora/` | Cahaya utara bergerak, panel kaca buram |
| `japandi/` | Kayu & linen, ensō, minimalis hangat |
| `lavender/` | Ladang lavender, huruf sambung, segel lilin |
| `senja-gurun/` | Bukit pasir berlapis, matahari terbenam |
| `kamar-mainan/` | Kartun: rak kayu penuh mainan, wallpaper awan, kotak mainan berjendela |
| `bawah-laut/` | Kartun: laut toska, gelembung, rumput laut, garis tepi bergelombang |
| `kampung-ceria/` | Kartun: kampung Nusantara, rumah panggung, rambutan, layang-layang |
| `hutan-dongeng/` | Kartun: hutan pinus bersalju, pondok berasap, madu, beruang |
| `kejar-kejaran/` | Kartun klasik: lubang tikus, keju, debu kecepatan |
| `champagne/` | Premium: satin rose-gold, gelembung emas, gelas coupe garis tipis |
| `kintsugi/` | Premium gelap: keramik arang dengan retakan emas beranimasi |
| `atelier/` | Premium: rumah mode, huruf Didone, pita ukur, jahitan |
| `grand-hotel/` | Premium: lobi hotel dongker & kuningan, produk = kunci kamar bernomor |
| `mutiara/` | Premium: putih mutiara iridescent, untaian mutiara, cangkang |
| `piringan-hitam/` | Toko vinyl: piringan berputar, lengan jarum, equalizer, tracklist |
| `terminal/` | Layar terminal hijau: perintah diketik, kursor berkedip, garis CRT |
| `herbarium/` | Spesimen botani antik: pakis kering, selotip, label tinta |
| `paspor/` | Travel: pesawat di jalur, cap imigrasi, produk = boarding pass |
| `chrome-y2k/` | Logam cair krom, bintang 4 sudut, langit holografik |
| `susu-stroberi/` | Imut: susu pink, stroberi, kotak susu berwajah, avatar telinga kelinci |
| `piknik/` | Imut: taplak kotak-kotak, keranjang rotan, daisy, kupu-kupu |
| `kafe-kucing/` | Imut: kafe hangat, kucing tidur, latte, kartu bertelinga kucing |
| `awan-kapas/` | Imut: langit pastel, awan empuk, pelangi, bulan sabit tidur |
| `spa-imut/` | Imut: kamar mandi pastel, busa, bebek karet, botol skincare berwajah |
| `tarot/` | Unik: malam berbintang, bola kristal, produk = kartu tarot bernomor Romawi |
| `cetak-biru/` | Unik: gambar arsitek, grid biru, garis ukuran, kotak judul |
| `gacha/` | Unik: mesin kapsul, tuas berputar, produk = kapsul dua warna |
| `peta-harta/` | Unik: perkamen, pulau, jalur ke tanda X, kompas berputar |
| `kaca-patri/` | Unik: jendela mawar katedral, kaca permata berbingkai timah |
| `rpg/` | Antarmuka game 8-bit: dialog, slime pixel, bar HP/MP, item inventaris |
| `brutalis/` | Poster tipografi raksasa rata kiri, garis tebal, nomor besar |
| `krayon/` | Coretan krayon anak: garis bergoyang (filter SVG), arsiran warna |
| `bioskop/` | Papan lampu bohlam berjalan, tirai, produk = poster film |
| `koran-pagi/` | Surat kabar: kop gotik, kolom, foto halftone hitam-putih |
| `_base/` | Sumber bersama: `app.js`, `base.css`, `dev-server.js`, `lib/lynk.js` |

Semua tema selain `alam`, `sage`, dan `kawaii-pink` memakai `base.css` (struktur bersama) + CSS tema di `index.html`.
Tema `alam`, `sage`, `kawaii-pink` menyimpan seluruh CSS-nya di `index.html`.

Versi pertama (tema pastel) ada di `pastel/` (memakai script sendiri, dikonversi oleh `app/tools/build-themes.mjs`).

## Isi tiap folder tema

```
index.html      tampilan + blok CONFIG (nama, bio, foto, sosmed, link)
app.js          logika bersama (jangan diedit per tema)
api/            perantara server ke lynk.id (catalog + cart)
lib/lynk.js     koneksi API lynk.id, pakai LYNK_API_KEY
scripts/        snapshot.js untuk memperbarui snapshot.json + img/
snapshot.json   katalog cadangan kalau API tidak bisa dihubungi
dev-server.js   server lokal
```

## Menjalankan di komputer

```
cd templates/alam
$env:LYNK_API_KEY="lynk_sk_xxx"; $env:PORT="3001"; node dev-server.js
```

Buka http://localhost:3001. Ganti folder dan port untuk tema lain.

## Deploy ke Vercel

1. Upload satu folder tema (misalnya `sage/`) sebagai proyek Vercel.
2. Settings → Environment Variables → tambah `LYNK_API_KEY`.
3. Redeploy, lalu pasang link-nya di bio.

## Mengubah logika untuk semua tema

Edit `_base/app.js`, lalu salin ke tiap folder tema:

```
Get-ChildItem -Directory | Where-Object Name -ne "_base" | ForEach-Object { Copy-Item _base\app.js $_.FullName }
# base.css hanya untuk tema yang memakainya:
Get-ChildItem -Directory -Exclude _base,alam,sage,kawaii-pink | ForEach-Object { Copy-Item _base\base.css $_.FullName }
```

## Membuat tema baru

Salin salah satu folder tema, ganti CSS dan hiasan di `index.html`.
Pertahankan `id` elemen yang dipakai `app.js` (lihat elemen dengan `id=` di tema yang ada).
