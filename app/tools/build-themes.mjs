// Salin semua tema dari ../templates (tema pastel dari ../templates/pastel)
// ke folder themes/ milik aplikasi multi-toko, lalu ganti teks khusus pemilik dengan penanda {{...}}
// yang diisi server sesuai data tiap toko.
// Jalankan ulang setiap kali ada tema baru:  node tools/build-themes.mjs
import { readFileSync, writeFileSync, mkdirSync, readdirSync, existsSync, copyFileSync } from "node:fs";
import { join, dirname } from "node:path";
import { fileURLToPath } from "node:url";

const ROOT = join(dirname(fileURLToPath(import.meta.url)), "..");
const SRC = join(ROOT, "..", "templates");
const PASTEL = join(SRC, "pastel", "index.html");
const OUT = join(ROOT, "themes");

const LABELS = {
  pastel: "Pastel", alam: "Alam", sage: "Sage", "kawaii-pink": "Kawaii Pink", "papan-kapur": "Papan Kapur",
  "retro-pixel": "Retro Pixel", "batik-kawung": "Batik Kawung", "pantai-tropis": "Pantai Tropis", "neon-malam": "Neon Malam",
  scrapbook: "Scrapbook", bento: "Bento", majalah: "Majalah", komik: "Komik", galaksi: "Galaksi", "nota-kasir": "Nota Kasir",
  clay: "Clay 3D", "kertas-lipat": "Kertas Lipat", lofi: "Lo-fi", sakura: "Sakura", lebaran: "Lebaran", "toko-kue": "Toko Kue",
  marmer: "Marmer Emas", "art-deco": "Art Deco", amalfi: "Amalfi", velvet: "Velvet", "galeri-seni": "Galeri Seni",
  aurora: "Aurora", japandi: "Japandi", lavender: "Lavender", "senja-gurun": "Senja Gurun",
  "kamar-mainan": "Kamar Mainan", "bawah-laut": "Bawah Laut", "kampung-ceria": "Kampung Ceria", "hutan-dongeng": "Hutan Dongeng", "kejar-kejaran": "Kejar-kejaran",
  champagne: "Champagne", kintsugi: "Kintsugi", atelier: "Atelier", "grand-hotel": "Grand Hotel", mutiara: "Mutiara",
  "piringan-hitam": "Piringan Hitam", terminal: "Terminal", herbarium: "Herbarium", paspor: "Paspor", "chrome-y2k": "Chrome Y2K",
  "susu-stroberi": "Susu Stroberi", piknik: "Piknik", "kafe-kucing": "Kafe Kucing", "awan-kapas": "Awan Kapas", "spa-imut": "Spa Imut",
  tarot: "Tarot", "cetak-biru": "Cetak Biru", gacha: "Gacha", "peta-harta": "Peta Harta Karun", "kaca-patri": "Kaca Patri",
  rpg: "Petualangan RPG", brutalis: "Brutalis", krayon: "Gambar Krayon", bioskop: "Bioskop", "koran-pagi": "Koran Pagi",
  "jeruk-segar": "Jeruk Segar", "es-krim": "Es Krim", "lab-sains": "Lab Sains", "musim-gugur": "Musim Gugur", "stadion": "Stadion", "memphis": "Memphis", "terrazzo": "Terrazzo", "laut-dalam": "Laut Dalam", "rumah-kaca": "Rumah Kaca", "matcha": "Matcha",
  "kereta-senja": "Kereta Senja", "kantor-pos": "Kantor Pos", "perpustakaan": "Perpustakaan", "buku-agenda": "Buku Agenda", "studio-keramik": "Studio Keramik", "kebun-teh": "Kebun Teh", "senja-kota": "Senja Kota", "tenun-ikat": "Tenun Ikat", "swatch": "Swatch", "soft-ui": "Soft UI"
};

function generic(html) {
  return html
    .replace(/<title>[^<]*<\/title>/, "<title>{{title}}</title>")
    .replace(/<meta name="description" content="[^"]*">/, '<meta name="description" content="{{description}}">')
    .replace(/href="base\.css"/g, 'href="/base.css"')
    .replace('<script src="app.js"></script>', '{{config}}\n<script src="/app.js?v={{v}}"></script>')
    // teks khusus pemilik di beberapa tema
    .replace("toko_ivan.exe", "toko_{{slug}}.exe")
    .replace("<span>lynk.id/ivansetiadi</span>", "<span>lynk.id/{{username}}</span>")
    .replace("<span>KASIR</span><span>IVAN</span>", "<span>KASIR</span><span>{{first_upper}}</span>")
    .replace("0001 2026 IVAN", "0001 {{year}} {{first_upper}}")
    .replace(/<span><b>Template website<\/b>[^<]*<\/span>/, "<span><b>Produk pilihan</b>langsung dari {{name}}</span>")
    .replace(/<span><b>Ebook &amp; panduan<\/b>[^<]*<\/span>/, "<span><b>Bayar aman</b>checkout lewat lynk.id</span>");
}

// Pastel memakai script sendiri; ubah ke model app.js bersama.
function convertPastel(html) {
  const start = html.indexOf("<script>\n/*");
  const end = html.lastIndexOf("</script>") + "</script>".length;
  const cfg = `<script>
window.CONFIG = {
  username: "ivansetiadi",
  hi: "Hi, aku",
  name: "Ivan Setiadi",
  role: "",
  about: "",
  avatar: "",
  socials: [],
  links: [
    { title: "Semua produk di lynk.id", note: "Etalase lengkap + checkout", href: "lynk" },
    { title: "Cara bayar", note: "QRIS, e-wallet, transfer bank", href: "#cara-bayar" },
    { title: "Tanya jawab", note: "Jawaban cepat sebelum beli", href: "#tanya-jawab" }
  ],
  signoff: "Yuk, tumbuh bareng!",
  text: {
    favorite: "★ Favorit", soldOverlay: "yah, habis :(", promo: "Lagi promo", free: "Ambil", freePrice: "Gratis!",
    emptySearchTitle: "Yah, belum ketemu", emptyCartTitle: "Masih kosong nih", wait: "Sebentar ya…",
    ready: "Yeay, siap dibayar!", added: "Masuk keranjang", voucher: "Punya kode voucher? 🎟️"
  },
  api: { catalog: "/api/catalog", cart: "/api/cart" },
  snapshot: "snapshot.json",
  utm: { utm_source: "website", utm_medium: "landing", utm_campaign: "tema-pastel" }
};
</script>
<script src="app.js"></script>`;
  return (html.slice(0, start) + cfg + html.slice(end))
    .replace(/ava-heart/g, "ava-badge")
    .replace('<span id="sign-heart"></span>', '<span id="sign-motif"></span>');
}

mkdirSync(OUT, { recursive: true });
const list = [];
const write = (id, html) => {
  mkdirSync(join(OUT, id), { recursive: true });
  writeFileSync(join(OUT, id, "index.html"), generic(html));
  list.push(id);
};

write("pastel", convertPastel(readFileSync(PASTEL, "utf8")));
for (const id of readdirSync(SRC, { withFileTypes: true }).filter((d) => d.isDirectory() && !["_base", "pastel"].includes(d.name)).map((d) => d.name).sort()) {
  const f = join(SRC, id, "index.html");
  if (existsSync(f)) write(id, readFileSync(f, "utf8"));
}

copyFileSync(join(SRC, "_base", "app.js"), join(ROOT, "app.js"));
copyFileSync(join(SRC, "_base", "base.css"), join(ROOT, "base.css"));

const meta = Object.fromEntries(list.map((id) => [id, LABELS[id] || id]));
writeFileSync(join(OUT, "themes.json"), JSON.stringify(meta, null, 2) + "\n");

// Laporan: sisa teks pemilik di luar blok CONFIG (seharusnya kosong)
for (const id of list) {
  const html = readFileSync(join(OUT, id, "index.html"), "utf8");
  const outside = html.replace(/<script>\s*(?:\/\*[\s\S]*?\*\/\s*)?window\.CONFIG[\s\S]*?<\/script>/, "");
  const left = outside.match(/[^\n]{0,30}(ivan|Ivan|IVAN)[^\n]{0,30}/g);
  if (left) console.log(`! ${id}:`, left.join(" | "));
  if (!html.includes("{{config}}")) console.log(`! ${id}: penanda {{config}} tidak ditemukan`);
}
console.log(`${list.length} tema disalin ke themes/`);
