// POST /api/cart — buat cart di lynk.id lalu kembalikan cart_url.
// Pembeli memilih metode bayar dan membayar di halaman lynk.id.
// Body: { items: [{ block_id, quantity }], voucher_code? }
import { getCatalog, lynk } from "../lib/lynk.js";

const UTM = { utm_source: "website", utm_medium: "landing", utm_content: "cart" };
const hits = new Map(); // batas sederhana per IP: 6 cart per menit

function tooMany(ip) {
  const now = Date.now();
  const recent = (hits.get(ip) || []).filter((t) => now - t < 60_000);
  recent.push(now);
  hits.set(ip, recent);
  return recent.length > 6;
}

export default async function handler(req, res) {
  if (req.method !== "POST") {
    res.setHeader("Allow", "POST");
    return res.status(405).json({ error: "Gunakan POST." });
  }

  const ip = String(req.headers["x-forwarded-for"] || "").split(",")[0].trim() || req.socket?.remoteAddress || "?";
  if (tooMany(ip)) return res.status(429).json({ error: "Terlalu banyak percobaan. Tunggu satu menit, lalu coba lagi." });

  try {
    let body = req.body;
    if (typeof body === "string") { try { body = JSON.parse(body); } catch { body = null; } }
    const raw = Array.isArray(body?.items) ? body.items : [];
    if (raw.length < 1 || raw.length > 20) {
      return res.status(400).json({ error: "Keranjang harus berisi 1 sampai 20 produk." });
    }

    const { products } = await getCatalog();
    const byId = new Map(products.map((p) => [p.id, p]));
    const items = [];
    for (const it of raw) {
      const p = byId.get(String(it?.block_id ?? ""));
      if (!p || !p.cart_ok) {
        return res.status(400).json({ error: `"${p?.name || "Produk"}" tidak bisa dibeli lewat keranjang. Beli langsung dari kartunya.` });
      }
      const max = p.limit_per_checkout || 99;
      const quantity = Math.min(Math.max(Math.floor(Number(it.quantity) || 1), 1), max);
      items.push({ block_id: p.id, quantity });
    }

    const voucher = typeof body.voucher_code === "string" ? body.voucher_code.trim().toUpperCase() : "";
    if (voucher && !/^[A-Z0-9_-]{1,50}$/.test(voucher)) {
      return res.status(400).json({ error: "Kode voucher hanya boleh huruf, angka, - dan _." });
    }

    const r = await lynk("/v1/carts", { method: "POST", body: { items, ...(voucher ? { voucher_code: voucher } : {}) } });

    if (!r.ok) {
      if (r.code === "CART_ITEMS_INVALID") {
        const lines = (r.data?.lines || []).map((l) => ({ block_id: l.block_id, name: byId.get(l.block_id)?.name, code: l.code, message: l.message }));
        return res.status(422).json({ error: "Ada produk yang tidak bisa dipesan lewat keranjang.", lines });
      }
      if (r.status === 429) return res.status(429).json({ error: "lynk.id sedang sibuk. Coba lagi sebentar lagi." });
      return res.status(502).json({ error: "lynk.id belum bisa membuat keranjang. Coba lagi sebentar lagi." });
    }

    const d = r.data;
    const url = new URL(d.cart_url);
    for (const [k, v] of Object.entries(UTM)) url.searchParams.set(k, v);

    res.status(200).json({
      cart_url: url.toString(),
      subtotal: d.subtotal,
      discount: d.discount,
      total: d.total,
      voucher: d.voucher || null,
      expires_at: d.expires_at,
    });
  } catch (err) {
    res.status(err.status || 502).json({ error: "Gagal menghubungi lynk.id. Coba lagi sebentar lagi." });
  }
}
