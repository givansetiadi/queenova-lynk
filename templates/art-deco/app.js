/* =====================================================================
   app.js — logika bersama semua tema (data lynk.id, cari, filter, keranjang).
   Tema cukup menyediakan elemen dengan id yang dipakai di bawah, lalu
   mendefinisikan window.CONFIG sebelum file ini dimuat.
   Jangan edit per tema; salin ulang dari _base/app.js kalau ada perbaikan.
   ===================================================================== */
(() => {
const DEFAULT_TEXT = {
  favorite: "★ Favorit",
  soldOverlay: "Habis",
  soldButton: "Habis",
  buy: "Beli",
  free: "Ambil",
  freePrice: "Gratis!",
  all: "Semua",
  promo: "Lagi promo",
  emptySearchTitle: "Belum ketemu",
  emptyCartTitle: "Keranjang masih kosong",
  emptyCartBody: "Tekan tombol keranjang di produk yang kamu suka.",
  backToProducts: "Lihat produk",
  voucher: "Punya kode voucher?",
  checkout: "Lanjut bayar",
  wait: "Sebentar ya…",
  ready: "Siap dibayar!",
  added: "Masuk keranjang",
  copied: "Link halaman disalin"
};

const CONFIG = window.CONFIG;
const T = { ...DEFAULT_TEXT, ...(CONFIG.text || {}) };

const $ = (s) => document.querySelector(s);
const setHTML = (sel, html) => { const el = $(sel); if (el) el.innerHTML = html; };
const swap = (sel, html) => { const el = $(sel); if (el) el.outerHTML = html; };
const esc = (s) => String(s ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
const fmt = (n) => "Rp" + Number(n).toLocaleString("id-ID");
const norm = (s) => String(s || "").toLowerCase().normalize("NFD").replace(/[̀-ͯ]/g, "");
const hash = (s) => [...String(s)].reduce((h, c) => (h * 31 + c.charCodeAt(0)) >>> 0, 7);
const profileUrl = `https://lynk.id/${CONFIG.username}`;

const S = 'fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"';
const ICON = {
  search: `<svg viewBox="0 0 24 24" ${S}><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>`,
  x: `<svg viewBox="0 0 24 24" ${S}><path d="M6 6l12 12M18 6 6 18"/></svg>`,
  cart: `<svg viewBox="0 0 24 24" ${S}><path d="M3 4h2l2.4 11.2a1.5 1.5 0 0 0 1.5 1.2h8.6a1.5 1.5 0 0 0 1.5-1.1L21 8H6.2"/><circle cx="9.5" cy="20" r="1.3"/><circle cx="17" cy="20" r="1.3"/></svg>`,
  cartPlus: `<svg viewBox="0 0 24 24" ${S}><path d="M3 4h2l2.4 11.2a1.5 1.5 0 0 0 1.5 1.2h8.6a1.5 1.5 0 0 0 1.5-1.1L21 8H6.2"/><circle cx="9.5" cy="20" r="1.3"/><circle cx="17" cy="20" r="1.3"/><path d="M13 9.5v4M11 11.5h4"/></svg>`,
  bag: `<svg viewBox="0 0 24 24" ${S}><path d="M5 8h14l-1 12H6z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/></svg>`,
  share: `<svg viewBox="0 0 24 24" ${S}><circle cx="18" cy="5" r="2.6"/><circle cx="6" cy="12" r="2.6"/><circle cx="18" cy="19" r="2.6"/><path d="m8.3 10.8 7.4-4.4M8.3 13.2l7.4 4.4"/></svg>`,
  check: `<svg viewBox="0 0 24 24" ${S} stroke-width="3"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>`,
  arrow: `<svg viewBox="0 0 24 24" ${S}><path d="M5 12h14M13 6l6 6-6 6"/></svg>`,
  ext: `<svg viewBox="0 0 24 24" ${S}><path d="M7 17 17 7M8 7h9v9"/></svg>`,
  plus: `<svg viewBox="0 0 24 24" ${S} stroke-width="2.6"><path d="M12 5v14M5 12h14"/></svg>`,
  minus: `<svg viewBox="0 0 24 24" ${S} stroke-width="2.6"><path d="M5 12h14"/></svg>`,
  chev: `<svg viewBox="0 0 24 24" ${S}><path d="m6 9 6 6 6-6"/></svg>`,
  heart: `<svg viewBox="0 0 40 36" aria-hidden="true"><path fill="currentColor" d="M20 36S0 23.5 0 10.8C0 4.8 4.6 0 10.3 0 14.4 0 18 2.4 20 6c2-3.6 5.6-6 9.7-6C35.4 0 40 4.8 40 10.8 40 23.5 20 36 20 36z"/></svg>`,
  instagram: `<svg viewBox="0 0 24 24" ${S}><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r=".9" fill="currentColor"/></svg>`,
  tiktok: `<svg viewBox="0 0 24 24" ${S}><path d="M14 3v11.5a3.5 3.5 0 1 1-3.5-3.5"/><path d="M14 3c.4 2.6 2.2 4.4 5 4.6"/></svg>`,
  youtube: `<svg viewBox="0 0 24 24" ${S}><rect x="2.5" y="5.5" width="19" height="13" rx="4"/><path d="M10 9.3v5.4l4.6-2.7z" fill="currentColor"/></svg>`,
  whatsapp: `<svg viewBox="0 0 24 24" ${S}><path d="M4 20l1.3-3.9A8 8 0 1 1 8 18.8z"/><path d="M9.2 9.4c.3 1.9 2.4 4 4.4 4.4"/></svg>`,
  email: `<svg viewBox="0 0 24 24" ${S}><rect x="3" y="5" width="18" height="14" rx="3"/><path d="m4 7 8 6 8-6"/></svg>`,
  link: `<svg viewBox="0 0 24 24" ${S}><path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/></svg>`
};
const MOTIF = CONFIG.motif || ICON.heart;

const TYPE_LABEL = {
  digital: "Produk digital", course: "Kelas online", video: "Video", chat: "Konsultasi",
  content: "Konten", event: "Event", physical: "Produk fisik", support: "Dukungan", appointment: "Jadwal temu"
};
const typeLabel = (t) => TYPE_LABEL[t] || (t ? t[0].toUpperCase() + t.slice(1) : "Produk");
const PRICE_OPTS = [
  ["all", "Semua harga", null],
  ["lt50", "< Rp50rb", (v) => v < 50000],
  ["50to200", "Rp50–200rb", (v) => v >= 50000 && v <= 200000],
  ["gt200", "> Rp200rb", (v) => v > 200000]
];
const SORT_OPTS = [["rec", "Urutan toko"], ["cheap", "Termurah"], ["pricey", "Termahal"], ["disc", "Diskon terbesar"], ["az", "Nama A–Z"]];

const state = {
  products: [], payments: [], feeBySeller: null, live: false, updated: null,
  q: "", type: "all", price: "all", sale: false, sort: "rec", cart: new Map()
};

/* ---------- Helper ---------- */
const discount = (p) => (p.original_price && p.price && p.original_price > p.price ? Math.round((1 - p.price / p.original_price) * 100) : 0);
const baseValue = (p) => p.price ?? p.min_price ?? null;
function priceText(p) {
  if (p.price_type === "pay_as_you_wish") return p.min_price ? `Min. ${fmt(p.min_price)}` : "Bayar sesukamu";
  if (p.price == null) return p.type === "event" ? "Lihat tiket" : "Lihat harga";
  if (p.price === 0) return T.freePrice;
  return (p.has_variants ? "Mulai " : "") + fmt(p.price);
}
function withUtm(url, content) {
  if (!url) return profileUrl;
  try {
    const u = new URL(url);
    for (const [k, v] of Object.entries(CONFIG.utm || {})) u.searchParams.set(k, v);
    if (content) u.searchParams.set("utm_content", content);
    return u.toString();
  } catch { return url; }
}
function imgHTML(p) {
  const gen = `<div class="gen" style="--h:${hash(p.name) % 360}"><span>${esc(typeLabel(p.type))}</span></div>`;
  const src = p.image || p.image_local;
  if (!src) return gen;
  const local = p.image && p.image_local ? p.image_local : "";
  return `${gen}<img src="${esc(src)}" alt="${esc(p.name)}" loading="lazy" decoding="async" data-local="${esc(local)}">`;
}
document.addEventListener("error", (e) => {
  const img = e.target;
  if (!(img instanceof HTMLImageElement)) return;
  if (img.dataset.local) { img.src = img.dataset.local; img.dataset.local = ""; }
  else img.remove();
}, true);
function highlight(text, ws) {
  if (!ws.length) return esc(text);
  const re = new RegExp("(" + ws.map((w) => w.replace(/[.*+?^${}()|[\]\\]/g, "\\$&")).join("|") + ")", "gi");
  return String(text).split(re).map((part, i) => (i % 2 ? `<mark>${esc(part)}</mark>` : esc(part))).join("");
}

/* ---------- Statis ---------- */
function renderStatic() {
  const initials = CONFIG.name.split(/\s+/).map((w) => w[0]).join("").slice(0, 2);
  setHTML("#ava", CONFIG.avatar ? `<img src="${esc(CONFIG.avatar)}" alt="${esc(CONFIG.name)}">` : esc(initials));
  setHTML("#ava-badge", MOTIF);
  for (const [id, key] of [["#hi", "hi"], ["#name", "name"], ["#role", "role"], ["#about", "about"], ["#sign-text", "signoff"]]) {
    const el = $(id); if (el) el.textContent = CONFIG[key] || "";
  }
  if (CONFIG.socials?.length && $("#socials")) {
    $("#socials").hidden = false;
    $("#socials").innerHTML = CONFIG.socials.map((s) =>
      `<a href="${esc(s.url)}" target="_blank" rel="noopener" aria-label="${esc(s.label || s.type)}">${ICON[s.type] || ICON.link}</a>`).join("");
  }
  setHTML("#share", ICON.share);
  swap("#cart-top-icon", ICON.cart);
  swap("#fab-icon", ICON.cart);
  swap("#bag-icon", ICON.bag);
  swap("#search-icon", ICON.search);
  swap("#ext-icon", ICON.ext);
  swap("#sign-motif", MOTIF);
  setHTML("#q-clear", ICON.x);
  setHTML("#sheet-close", ICON.x);
  if ($("#all-link")) $("#all-link").href = withUtm(profileUrl, "profile");
  setHTML("#links", (CONFIG.links || []).map((l) => {
    const href = l.href === "lynk" ? withUtm(profileUrl, "links") : l.href;
    const ext = /^https?:/.test(href);
    return `<a class="lrow" href="${esc(href)}"${ext ? ' target="_blank" rel="noopener"' : ""}>
      <span class="tick">${ICON.check}</span>
      <span class="ltext"><b>${esc(l.title)}</b>${l.note ? `<span>${esc(l.note)}</span>` : ""}</span>${ICON.arrow}</a>`;
  }).join(""));
  setHTML("#grid", Array.from({ length: 4 }, () => `<div class="card" aria-hidden="true"><div class="cimg ph"></div><div class="cbody"><div class="ph" style="height:12px;border-radius:6px;width:50%"></div><div class="ph" style="height:16px;border-radius:6px"></div></div></div>`).join(""));
}

/* ---------- Filter ---------- */
const words = () => norm(state.q).split(/\s+/).filter(Boolean);
function filtered() {
  const ws = words();
  const priceTest = PRICE_OPTS.find((o) => o[0] === state.price)?.[2];
  let list = state.products.filter((p) => {
    if (state.type !== "all" && p.type !== state.type) return false;
    if (state.sale && !discount(p)) return false;
    if (priceTest) { const v = baseValue(p); if (v == null || !priceTest(v)) return false; }
    if (ws.length) {
      const hay = norm(`${p.name} ${p.description || ""} ${typeLabel(p.type)}`);
      if (!ws.every((w) => hay.includes(w))) return false;
    }
    return true;
  });
  const sorters = {
    cheap: (a, b) => (baseValue(a) ?? 1e12) - (baseValue(b) ?? 1e12),
    pricey: (a, b) => (baseValue(b) ?? -1) - (baseValue(a) ?? -1),
    disc: (a, b) => discount(b) - discount(a),
    az: (a, b) => a.name.localeCompare(b.name, "id")
  };
  if (sorters[state.sort]) list = [...list].sort(sorters[state.sort]);
  return list;
}

function renderChips() {
  const types = [...new Set(state.products.map((p) => p.type))];
  const chips = [`<button class="chip" type="button" data-type="all" aria-pressed="${state.type === "all" && !state.sale}">${esc(T.all)}</button>`];
  if (state.products.some((p) => discount(p))) chips.push(`<button class="chip" type="button" data-sale aria-pressed="${state.sale}">${esc(T.promo)}</button>`);
  if (types.length > 1) for (const t of types) chips.push(`<button class="chip" type="button" data-type="${esc(t)}" aria-pressed="${state.type === t}">${esc(typeLabel(t))}</button>`);
  chips.push(`<label class="chip sel"><select id="price" aria-label="Rentang harga">${PRICE_OPTS.map(([v, l]) => `<option value="${v}"${state.price === v ? " selected" : ""}>${l}</option>`).join("")}</select>${ICON.chev}</label>`);
  chips.push(`<label class="chip sel"><select id="sort" aria-label="Urutkan">${SORT_OPTS.map(([v, l]) => `<option value="${v}"${state.sort === v ? " selected" : ""}>${l}</option>`).join("")}</select>${ICON.chev}</label>`);
  $("#chips").innerHTML = chips.join("");
}

function cardHTML(p, i, ws, wide) {
  const off = discount(p), out = p.in_stock === false, inCart = state.cart.has(p.id);
  const detail = esc(withUtm(p.url || p.checkout_url, p.id));
  const add = p.cart_ok && !out
    ? `<button class="add${inCart ? " in" : ""}" type="button" data-add="${esc(p.id)}" aria-label="${inCart ? "Sudah di keranjang" : "Tambah ke keranjang"}: ${esc(p.name)}" title="${inCart ? "Sudah di keranjang" : "Tambah ke keranjang"}">${inCart ? ICON.check : ICON.cartPlus}</button>`
    : "";
  return `
    <article class="card${wide ? " wide" : ""}" style="--i:${i}">
      <a class="cimg" href="${detail}" target="_blank" rel="noopener" tabindex="-1" aria-hidden="true">
        ${imgHTML(p)}
        ${wide ? `<span class="ribbon">${esc(T.favorite)}</span>` : ""}
        ${off ? `<span class="off">-${off}%</span>` : ""}
        ${out ? `<span class="sold">${esc(T.soldOverlay)}</span>` : ""}
      </a>
      <div class="cbody">
        <span class="ctype">${esc(typeLabel(p.type))}</span>
        <h3 class="cname"><a href="${detail}" target="_blank" rel="noopener">${highlight(p.name, ws)}</a></h3>
        ${p.description ? `<p class="cdesc">${highlight(p.description, ws)}</p>` : ""}
        <div class="cprice"><span class="now">${esc(priceText(p))}</span>${off ? `<span class="was">${fmt(p.original_price)}</span>` : ""}</div>
      </div>
      <div class="cact">
        ${out ? `<span class="buy" aria-disabled="true">${esc(T.soldButton)}</span>`
              : `<a class="buy" href="${esc(withUtm(p.checkout_url, p.id))}" target="_blank" rel="noopener">${esc(p.price === 0 ? T.free : T.buy)}</a>`}
        ${add}
      </div>
    </article>`;
}

function renderGrid() {
  const list = filtered(), ws = words(), total = state.products.length;
  const active = state.q || state.type !== "all" || state.price !== "all" || state.sale;
  const wideFirst = !active && state.sort === "rec" && list.length % 2 === 1;
  $("#grid").innerHTML = list.length
    ? list.map((p, i) => cardHTML(p, i, ws, wideFirst && i === 0)).join("")
    : `<div class="empty"><b>${esc(T.emptySearchTitle)}</b>${state.q ? `Tidak ada produk dengan kata "${esc(state.q)}".` : "Tidak ada produk untuk filter ini."}<br><button class="linkbtn" type="button" data-reset>Hapus filter</button></div>`;
  $("#status").hidden = !active;
  $("#status-text").textContent = `${list.length} dari ${total} produk`;
}

function resetFilters() {
  Object.assign(state, { q: "", type: "all", price: "all", sale: false });
  $("#q").value = ""; $("#q-clear").hidden = true;
  renderChips(); renderGrid();
}

/* ---------- Cara bayar ---------- */
function renderPayments() {
  const groups = new Map();
  for (const m of state.payments) {
    const k = m.category || "Lainnya";
    if (!groups.has(k)) groups.set(k, []);
    groups.get(k).push(m);
  }
  const CAT = { "Instant Payment": "QRIS & e-wallet", "Virtual Account": "Transfer bank", "Others": "Lainnya" };
  setHTML("#pay-groups", [...groups].map(([k, ms]) => `
    <h3>${esc(CAT[k] || k)}</h3>
    <div class="pms">${ms.map((m) => `<span class="pm">${m.image ? `<img src="${esc(m.image)}" alt="" loading="lazy">` : ""}${esc(m.name.replace(/ Virtual Account$/, ""))}</span>`).join("")}</div>`).join(""));
  const note = $("#pay-note");
  if (note) note.textContent = state.feeBySeller === true
    ? "Tidak ada biaya tambahan untuk pembayaran."
    : state.feeBySeller === false ? "Ada biaya layanan kecil sesuai metode bayar. Jumlahnya terlihat di checkout sebelum kamu bayar." : "";
}

/* ---------- Keranjang ---------- */
const CART_KEY = `lynk-cart-${CONFIG.username}`;
function loadCart() {
  try {
    const arr = JSON.parse(localStorage.getItem(CART_KEY) || "[]");
    return new Map(arr.filter((x) => x && x.id).map((x) => [String(x.id), Math.max(1, x.q | 0)]));
  } catch { return new Map(); }
}
function saveCart() {
  try { localStorage.setItem(CART_KEY, JSON.stringify([...state.cart].map(([id, q]) => ({ id, q })))); } catch { /* abaikan */ }
}
const byId = (id) => state.products.find((p) => p.id === id);
const maxQty = (p) => (p && p.limit_per_checkout) || 99;
function updateBadge(bump) {
  let n = 0; state.cart.forEach((q) => (n += q));
  for (const id of ["#bubble-fab", "#bubble-top"]) { const el = $(id); if (el) el.textContent = n; }
  const top = $("#bubble-top"); if (top) top.hidden = !n;
  if (bump) { const f = $("#fab"); if (f) { f.classList.remove("bump"); void f.offsetWidth; f.classList.add("bump"); } }
}

let cartErr = "";
function renderCart() {
  const lines = [...state.cart].map(([id, q]) => ({ p: byId(id), q })).filter((l) => l.p);
  if (!lines.length) {
    $("#slist").innerHTML = `<div class="sempty">${ICON.cart}<b>${esc(T.emptyCartTitle)}</b><span>${esc(T.emptyCartBody)}</span></div>`;
    $("#sfoot").innerHTML = `<button class="ghost" type="button" data-close>${esc(T.backToProducts)}</button>`;
    return;
  }
  $("#slist").innerHTML = lines.map(({ p, q }) => `
    <div class="line">
      <div class="thumb">${imgHTML(p)}</div>
      <div>
        <p class="lname">${esc(p.name)}</p>
        <div class="lctl">
          <div class="qty" role="group" aria-label="Jumlah">
            <button type="button" data-dec="${esc(p.id)}" aria-label="Kurangi" ${q <= 1 ? "disabled" : ""}>${ICON.minus}</button>
            <output>${q}</output>
            <button type="button" data-inc="${esc(p.id)}" aria-label="Tambah" ${q >= maxQty(p) ? "disabled" : ""}>${ICON.plus}</button>
          </div>
          <span class="lprice">${fmt(p.price * q)}</span>
        </div>
        <button class="rm" type="button" data-rm="${esc(p.id)}">Hapus</button>
      </div>
    </div>`).join("");
  const subtotal = lines.reduce((s, { p, q }) => s + p.price * q, 0);
  const normal = lines.reduce((s, { p, q }) => s + Math.max(p.original_price || 0, p.price) * q, 0);
  const voucher = $("#voucher")?.value || "";
  $("#sfoot").innerHTML = `
    <input class="voucher" id="voucher" placeholder="${esc(T.voucher)}" aria-label="Kode voucher" maxlength="50" value="${esc(voucher)}">
    <div class="sum">
      ${normal > subtotal ? `<div><span>Harga normal</span><span>${fmt(normal)}</span></div><div class="save"><span>Kamu hemat</span><span>−${fmt(normal - subtotal)}</span></div>` : ""}
      <div class="total"><span>Total</span><b>${fmt(subtotal)}</b></div>
    </div>
    ${cartErr}
    <button class="go" type="button" id="checkout">${esc(T.checkout)} ${ICON.arrow}</button>
    <p class="fine2">Biaya metode bayar dihitung di halaman lynk.id sebelum kamu bayar.</p>`;
}

function addToCart(id) {
  const p = byId(id);
  if (!p || !p.cart_ok) return;
  if (state.cart.has(id)) { openCart(); return; }
  state.cart.set(id, 1);
  saveCart(); updateBadge(true); renderGrid();
  toast(`${T.added}: ${p.name}`);
}

async function checkout() {
  const btn = $("#checkout"), voucher = ($("#voucher")?.value || "").trim();
  btn.disabled = true;
  btn.textContent = T.wait;
  cartErr = "";
  try {
    const r = await fetch(CONFIG.api.cart, {
      method: "POST",
      headers: { "Content-Type": "application/json", Accept: "application/json" },
      body: JSON.stringify({ items: [...state.cart].map(([block_id, quantity]) => ({ block_id, quantity })), ...(voucher ? { voucher_code: voucher } : {}) })
    });
    const j = (r.headers.get("content-type") || "").includes("json") ? await r.json() : null;
    if (!r.ok || !j?.cart_url) {
      const extra = j?.lines?.length ? " " + j.lines.map((l) => `${l.name || l.block_id}: ${l.message || l.code}`).join(" · ") : "";
      throw new Error((j?.error || 'Keranjang belum bisa dibayar di sini. Beli satu per satu lewat tombol "Beli" ya.') + extra);
    }
    const go = `<a class="go" href="${esc(j.cart_url)}" target="_blank" rel="noopener">Bayar di lynk.id ${ICON.ext}</a>`;
    if (voucher && j.voucher && !j.voucher.applied) {
      cartErr = `<p class="err">Kode ${esc(voucher.toUpperCase())} tidak bisa dipakai${j.voucher.message ? ` (${esc(j.voucher.message)})` : ""}. Kamu tetap bisa lanjut tanpa voucher.</p>`;
      renderCart();
      $("#checkout").outerHTML = go;
      return;
    }
    state.cart.clear(); saveCart(); updateBadge(); renderGrid();
    $("#slist").innerHTML = `<div class="sempty"><b>${esc(T.ready)}</b><span>Total ${fmt(j.total)} sebelum biaya pembayaran. Link berlaku 24 jam.</span></div>`;
    $("#sfoot").innerHTML = go;
    window.location.assign(j.cart_url);
  } catch (e) {
    cartErr = `<p class="err">${esc(e.message)}</p>`;
    renderCart();
  }
}

let lastFocus = null;
function openCart() {
  lastFocus = document.activeElement;
  cartErr = "";
  renderCart();
  $("#sheet").classList.add("open");
  $("#scrim").classList.add("open");
  document.body.style.overflow = "hidden";
  setTimeout(() => $("#sheet-close").focus(), 30);
}
function closeCart() {
  $("#sheet").classList.remove("open");
  $("#scrim").classList.remove("open");
  document.body.style.overflow = "";
  lastFocus?.focus?.();
}

let toastTimer;
function toast(text) {
  const t = $("#toast");
  t.innerHTML = `<i>${ICON.check}</i><span>${esc(text)}</span>`;
  t.classList.add("show");
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => t.classList.remove("show"), 2400);
}

async function share() {
  const url = location.href.split("#")[0];
  try {
    if (navigator.share) { await navigator.share({ title: document.title, url }); return; }
  } catch (e) { if (e && e.name === "AbortError") return; }
  try { await navigator.clipboard.writeText(url); toast(T.copied); }
  catch { toast(url); }
}

/* ---------- Event ---------- */
let qTimer;
$("#q").addEventListener("input", (e) => {
  state.q = e.target.value;
  $("#q-clear").hidden = !state.q;
  clearTimeout(qTimer);
  qTimer = setTimeout(renderGrid, 90);
});
$("#q-clear").addEventListener("click", () => { $("#q").value = ""; $("#q").dispatchEvent(new Event("input")); $("#q").focus(); });
$("#chips").addEventListener("click", (e) => {
  const b = e.target.closest("button.chip");
  if (!b) return;
  if (b.dataset.sale !== undefined) { state.sale = !state.sale; if (state.sale) state.type = "all"; }
  else { state.type = b.dataset.type; if (state.type === "all") state.sale = false; }
  renderChips(); renderGrid();
});
$("#chips").addEventListener("change", (e) => {
  if (e.target.id === "price") state.price = e.target.value;
  if (e.target.id === "sort") state.sort = e.target.value;
  renderGrid();
});
$("#reset").addEventListener("click", resetFilters);
$("#grid").addEventListener("click", (e) => {
  if (e.target.closest("[data-reset]")) return resetFilters();
  const add = e.target.closest("[data-add]");
  if (add) addToCart(add.dataset.add);
});
$("#share")?.addEventListener("click", share);
$("#cart-top")?.addEventListener("click", openCart);
$("#fab")?.addEventListener("click", openCart);
$("#sheet-close").addEventListener("click", closeCart);
$("#scrim").addEventListener("click", closeCart);
$("#sheet").addEventListener("click", (e) => {
  const t = e.target.closest("button");
  if (!t) return;
  if (t.id === "checkout") return checkout();
  if (t.dataset.close !== undefined) { closeCart(); $("#produk")?.scrollIntoView({ block: "start" }); return; }
  const id = t.dataset.inc || t.dataset.dec || t.dataset.rm;
  if (!id) return;
  const q = state.cart.get(id) || 0;
  if (t.dataset.inc) state.cart.set(id, Math.min(q + 1, maxQty(byId(id))));
  if (t.dataset.dec) state.cart.set(id, Math.max(q - 1, 1));
  if (t.dataset.rm) state.cart.delete(id);
  cartErr = "";
  saveCart(); updateBadge(); renderCart(); renderGrid();
});
document.addEventListener("keydown", (e) => {
  if (e.key === "Escape" && $("#sheet").classList.contains("open")) closeCart();
  const typing = /^(INPUT|TEXTAREA|SELECT)$/.test(document.activeElement?.tagName);
  if (e.key === "/" && !typing) { e.preventDefault(); $("#q").focus(); }
});

/* ---------- Data ---------- */
async function getJSON(url) {
  const r = await fetch(url, { headers: { Accept: "application/json" } });
  if (!r.ok || !(r.headers.get("content-type") || "").includes("json")) throw new Error(String(r.status));
  return r.json();
}
function apply(data, live) {
  state.products = Array.isArray(data.products) ? data.products : [];
  state.payments = Array.isArray(data.payment_methods) ? data.payment_methods : [];
  state.feeBySeller = data.fee_paid_by_seller ?? null;
  state.live = live;
  state.updated = data.updated_at ? new Date(data.updated_at) : null;
  for (const id of [...state.cart.keys()]) if (!byId(id)?.cart_ok) state.cart.delete(id);
  saveCart(); updateBadge();
  renderChips(); renderGrid(); renderPayments();
  const when = state.updated ? state.updated.toLocaleDateString("id-ID", { day: "numeric", month: "long", year: "numeric" }) : "";
  setHTML("#foot", `© ${new Date().getFullYear()} ${esc(CONFIG.name)} · pembayaran oleh <a href="${esc(withUtm(profileUrl, "footer"))}" target="_blank" rel="noopener">lynk.id</a>${!live && when ? ` · katalog per ${esc(when)}` : ""}`);
}
async function load() {
  try { apply(await getJSON(CONFIG.api.catalog), true); return; } catch { /* coba cadangan */ }
  try { apply(await getJSON(CONFIG.snapshot), false); return; } catch { /* gagal juga */ }
  $("#grid").innerHTML = `<div class="empty"><b>Produk belum bisa dimuat</b>Coba muat ulang, atau buka langsung <a href="${esc(profileUrl)}" target="_blank" rel="noopener">lynk.id/${esc(CONFIG.username)}</a>.</div>`;
}

state.cart = loadCart();
renderStatic();
updateBadge();
load();
})();
