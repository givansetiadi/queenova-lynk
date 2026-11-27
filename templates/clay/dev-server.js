// Server lokal: halaman + /api/* tanpa Vercel.
// Git Bash:   LYNK_API_KEY=lynk_sk_xxx node dev-server.js
// PowerShell: $env:LYNK_API_KEY="lynk_sk_xxx"; node dev-server.js
// Ganti port: PORT=3001 node dev-server.js   → buka http://localhost:3001
import http from "node:http";
import { readFile } from "node:fs/promises";
import { extname } from "node:path";

const PORT = process.env.PORT || 3000;
const TYPES = {
  ".html": "text/html; charset=utf-8", ".json": "application/json; charset=utf-8", ".js": "text/javascript; charset=utf-8", ".css": "text/css; charset=utf-8",
  ".webp": "image/webp", ".png": "image/png", ".jpg": "image/jpeg", ".jpeg": "image/jpeg",
  ".gif": "image/gif", ".svg": "image/svg+xml",
};
const PUBLIC = /^\/(index\.html|app\.js|base\.css|snapshot\.json|img\/[\w.-]+\.(webp|png|jpe?g|gif|svg))$/;

http.createServer(async (req, res) => {
  const path = new URL(req.url, "http://localhost").pathname;

  if (path.startsWith("/api/")) {
    const name = path.slice(5);
    let mod;
    try {
      if (!/^[a-z-]+$/.test(name)) throw new Error();
      mod = await import(new URL(`./api/${name}.js`, import.meta.url).href);
    } catch {
      res.statusCode = 404;
      return res.end("Not found");
    }
    let raw = "";
    for await (const chunk of req) raw += chunk;
    if (raw) { try { req.body = JSON.parse(raw); } catch { req.body = raw; } }
    const shim = {
      setHeader: (k, v) => res.setHeader(k, v),
      status(code) { res.statusCode = code; return shim; },
      json(body) { res.setHeader("Content-Type", "application/json; charset=utf-8"); res.end(JSON.stringify(body)); },
    };
    return mod.default(req, shim);
  }

  const file = path === "/" ? "/index.html" : path;
  if (!PUBLIC.test(file)) { res.statusCode = 404; return res.end("Not found"); }
  try {
    const data = await readFile(new URL(`.${file}`, import.meta.url));
    res.setHeader("Content-Type", TYPES[extname(file)] || "application/octet-stream");
    res.end(data);
  } catch {
    res.statusCode = 404;
    res.end("Not found");
  }
}).listen(PORT, () => console.log(`Buka http://localhost:${PORT}`));
