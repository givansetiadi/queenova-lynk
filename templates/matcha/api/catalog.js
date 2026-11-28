// GET /api/catalog — produk + metode pembayaran dari lynk.id (field publik saja).
import { getCatalog } from "../lib/lynk.js";

export default async function handler(req, res) {
  try {
    const catalog = await getCatalog();
    res.setHeader("Cache-Control", "public, s-maxage=300, stale-while-revalidate=3600");
    res.status(200).json(catalog);
  } catch (err) {
    res.status(err.status || 502).json({ error: "Gagal mengambil data dari lynk.id." });
  }
}
