// Klien kecil untuk Lynk.id Open API (https://api.lynk.id/docs).
// Hanya dipakai di server: API key (LYNK_API_KEY) tidak boleh sampai ke browser.

const BASE = "https://api.lynk.id";
const CATALOG_TTL = 5 * 60 * 1000;   // 5 menit
const MAX_DETAIL = 40;               // batas panggilan detail per refresh (limit API: 60 req/menit)
const CART_BLOCKED_TYPES = new Set(["event", "appointment", "support"]);

const cache = new Map();

function apiKey() {
  const k = process.env.LYNK_API_KEY;
  if (!k) throw Object.assign(new Error("LYNK_API_KEY belum diatur."), { status: 500 });
  return k;
}

export async function lynk(path, { method = "GET", body } = {}) {
  const r = await fetch(BASE + path, {
    method,
    headers: {
      Authorization: `Bearer ${apiKey()}`,
      Accept: "application/json",
      ...(body ? { "Content-Type": "application/json" } : {}),
    },
    body: body ? JSON.stringify(body) : undefined,
    signal: AbortSignal.timeout(10_000), // jangan menunggu lynk.id selamanya
  });
  let j = null;
  try { j = await r.json(); } catch { /* bukan JSON */ }
  return { ok: r.ok, status: r.status, code: j?.code, message: j?.message, data: j?.data };
}

async function cached(name, ttl, fn) {
  const hit = cache.get(name);
  if (hit && Date.now() - hit.t < ttl) return hit.v;
  try {
    const v = await fn();
    cache.set(name, { t: Date.now(), v });
    return v;
  } catch (e) {
    if (hit) return hit.v; // pakai data lama kalau lynk.id sedang bermasalah
    throw e;
  }
}

async function mapLimit(list, n, fn) {
  const out = new Array(list.length);
  let next = 0;
  const worker = async () => { while (next < list.length) { const i = next++; out[i] = await fn(list[i], i); } };
  await Promise.all(Array.from({ length: Math.min(n, list.length) }, worker));
  return out;
}

const productPage = (checkoutUrl) => (checkoutUrl ? checkoutUrl.replace(/\/checkout\/?$/, "") : null);

// Dokumentasi: cart menolak event, appointment, support, dan pay-as-you-wish.
// Varian/add-on wajib juga kita arahkan ke checkout lynk.id supaya pembeli memilih di sana.
function cartEligible(b, inStock) {
  return !CART_BLOCKED_TYPES.has(b.type)
    && b.price_type === "fixed"
    && b.price != null
    && !b.has_variants
    && !b.has_required_addon
    && inStock;
}

async function listAllBlocks() {
  const items = [];
  let cursor = null;
  for (let page = 0; page < 20; page++) {
    const qs = new URLSearchParams({ limit: "100" });
    if (cursor) qs.set("cursor", cursor);
    const r = await lynk(`/v1/blocks?${qs}`);
    if (!r.ok) throw Object.assign(new Error(r.code || `HTTP ${r.status}`), { status: 502 });
    items.push(...(r.data?.items ?? []));
    const nextCursor = r.data?.next_cursor;
    if (!nextCursor || nextCursor === cursor) break;
    cursor = nextCursor;
  }
  return items;
}

export function getCatalog() {
  return cached("catalog", CATALOG_TTL, async () => {
    const blocks = await listAllBlocks();

    const details = await mapLimit(blocks.slice(0, MAX_DETAIL), 4, async (b) => {
      const r = await lynk(`/v1/blocks/${encodeURIComponent(b.block_id)}`);
      return r.ok ? r.data : null;
    });

    const products = blocks.map((b, i) => {
      const d = details[i] || null;
      const q = d?.quantity;
      const inStock = q ? (q.is_unlimited || q.in_stock !== false) : true;
      return {
        id: b.block_id,
        name: b.name,
        type: b.type,
        price: b.price,
        original_price: b.original_price,
        price_type: b.price_type,
        min_price: b.min_price,
        image: b.image_url,
        has_variants: b.has_variants,
        requires_shipping: b.requires_shipping,
        url: productPage(b.checkout_url),
        checkout_url: b.checkout_url,
        description: d?.description_text || "",
        in_stock: inStock,
        limit_per_checkout: q?.limit_per_checkout || 0,
        cart_ok: cartEligible(b, inStock),
      };
    });

    let pm = { items: [], fee_paid_by_seller: null };
    const r = await lynk("/v1/payment-methods");
    if (r.ok && r.data) pm = r.data;

    return {
      products,
      payment_methods: (pm.items || []).map((m) => ({ code: m.code, name: m.name, category: m.category, image: m.image_url })),
      fee_paid_by_seller: pm.fee_paid_by_seller ?? null,
      updated_at: new Date().toISOString(),
    };
  });
}
