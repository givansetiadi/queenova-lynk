// Simpan salinan katalog ke snapshot.json + gambar ke img/.
// Dipakai halaman saat /api/catalog tidak bisa dihubungi.
// Jalankan:  LYNK_API_KEY=lynk_sk_xxx node scripts/snapshot.js
import { mkdir, writeFile } from "node:fs/promises";
import { getCatalog } from "../lib/lynk.js";

const root = new URL("../", import.meta.url);
const catalog = await getCatalog();

await mkdir(new URL("img/", root), { recursive: true });
for (const p of catalog.products) {
  if (!p.image) continue;
  try {
    const r = await fetch(p.image);
    if (!r.ok) continue;
    const ext = (p.image.split("?")[0].match(/\.(webp|png|jpe?g|gif|svg)$/i)?.[1] || "webp").toLowerCase();
    const file = `img/${p.id.replace(/[^\w-]/g, "_")}.${ext}`;
    await writeFile(new URL(file, root), Buffer.from(await r.arrayBuffer()));
    p.image_local = file;
  } catch { /* lewati gambar yang gagal */ }
}

catalog.snapshot = true;
await writeFile(new URL("snapshot.json", root), JSON.stringify(catalog, null, 2) + "\n");
console.log(`snapshot.json: ${catalog.products.length} produk, ${catalog.payment_methods.length} metode pembayaran`);
