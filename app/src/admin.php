<?php
// Panel admin: tambah / ubah / nonaktifkan toko pembeli. Dibuka di admin.<domain>.
declare(strict_types=1);

function admin_main(): never
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    session_name('etalase_admin');
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => $https, 'path' => '/']);
    session_start();
    header('X-Frame-Options: DENY');
    header('Cache-Control: no-store');
    header('X-Robots-Tag: noindex');

    $_SESSION['csrf'] ??= bin2hex(random_bytes(16));
    $action = $_GET['a'] ?? 'list';

    if (setting('admin_hash') === null) admin_setup();
    if (empty($_SESSION['admin'])) admin_login();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!hash_equals($_SESSION['csrf'], (string) ($_POST['csrf'] ?? ''))) admin_flash_redirect('Sesi kedaluwarsa, coba lagi.', 'err');
        match ($action) {
            'save' => admin_save(),
            'toggle' => admin_toggle(),
            'delete' => admin_delete(),
            'refresh' => admin_refresh(),
            'logout' => admin_logout(),
            default => admin_flash_redirect('Aksi tidak dikenal.', 'err'),
        };
    }
    match ($action) {
        'new' => admin_form(null),
        'edit' => admin_form(admin_find((int) ($_GET['id'] ?? 0))),
        default => admin_list(),
    };
}

/* ---------- Auth ---------- */
function admin_setup(): never
{
    $err = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && hash_equals($_SESSION['csrf'], (string) ($_POST['csrf'] ?? ''))) {
        $p1 = (string) ($_POST['p1'] ?? '');
        $p2 = (string) ($_POST['p2'] ?? '');
        if (strlen($p1) < 10) $err = 'Password minimal 10 karakter.';
        elseif ($p1 !== $p2) $err = 'Kedua password tidak sama.';
        else {
            setting('admin_hash', password_hash($p1, PASSWORD_DEFAULT));
            session_regenerate_id(true);
            $_SESSION['admin'] = true;
            admin_flash_redirect('Password admin dibuat. Selamat datang!');
        }
    }
    admin_page('Buat password admin', '
      <form method="post" class="card narrow">
        <h1>Buat password admin</h1>
        <p class="muted">Ini pertama kali panel dibuka. Buat password untuk mengelola toko pembeli.</p>
        ' . ($err ? '<p class="msg err">' . e($err) . '</p>' : '') . '
        <input type="hidden" name="csrf" value="' . e($_SESSION['csrf']) . '">
        <label>Password<input type="password" name="p1" minlength="10" required autocomplete="new-password"></label>
        <label>Ulangi password<input type="password" name="p2" minlength="10" required autocomplete="new-password"></label>
        <button class="btn">Simpan password</button>
      </form>');
}

function admin_login(): never
{
    $err = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (too_many('login:' . client_ip(), 10, 600)) $err = 'Terlalu banyak percobaan. Tunggu 10 menit.';
        elseif (hash_equals($_SESSION['csrf'], (string) ($_POST['csrf'] ?? '')) && password_verify((string) ($_POST['p'] ?? ''), (string) setting('admin_hash'))) {
            session_regenerate_id(true);
            $_SESSION['admin'] = true;
            header('Location: ?');
            exit;
        } else $err = 'Password salah.';
    }
    admin_page('Masuk', '
      <form method="post" class="card narrow">
        <h1>Masuk admin</h1>
        ' . ($err ? '<p class="msg err">' . e($err) . '</p>' : '') . '
        <input type="hidden" name="csrf" value="' . e($_SESSION['csrf']) . '">
        <label>Password<input type="password" name="p" required autofocus autocomplete="current-password"></label>
        <button class="btn">Masuk</button>
      </form>');
}

function admin_logout(): never
{
    $_SESSION = [];
    session_destroy();
    header('Location: ?');
    exit;
}

/* ---------- Data ---------- */
function admin_find(int $id): array
{
    $st = db()->prepare('SELECT * FROM tenants WHERE id = ?');
    $st->execute([$id]);
    return $st->fetch() ?: admin_flash_redirect('Toko tidak ditemukan.', 'err');
}

function admin_flash_redirect(string $msg, string $type = 'ok'): never
{
    $_SESSION['flash'] = [$type, $msg];
    header('Location: ?');
    exit;
}

/** URL toko berdasarkan host admin saat ini (mis. admin.localhost:8080 → budi.localhost:8080). */
function store_url(array $t): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    $hostPort = strtolower($_SERVER['HTTP_HOST'] ?? '');
    $port = preg_match('/(:\d+)$/', $hostPort, $m) ? $m[1] : '';
    $label = host_label(request_host());
    $base = $label === null ? (cfg('base_domains')[0] ?? '') : substr(request_host(), $label === '' ? 0 : strlen($label) + 1);
    return ($https ? 'https' : 'http') . '://' . $t['subdomain'] . '.' . $base . $port;
}

function parse_socials(string $text): array
{
    $out = [];
    foreach (preg_split('/\R/', $text) as $line) {
        $url = trim($line);
        if ($url === '') continue;
        if (preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $url)) $url = 'mailto:' . $url;
        if (!preg_match('~^(https?://|mailto:)~i', $url)) $url = 'https://' . $url;
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $type = match (true) {
            str_starts_with($url, 'mailto:') => 'email',
            str_contains($host, 'instagram.com') => 'instagram',
            str_contains($host, 'tiktok.com') => 'tiktok',
            str_contains($host, 'youtube.com'), str_contains($host, 'youtu.be') => 'youtube',
            str_contains($host, 'wa.me'), str_contains($host, 'whatsapp.com') => 'whatsapp',
            default => 'link',
        };
        $out[] = ['type' => $type, 'label' => ucfirst($type === 'link' ? 'Link' : $type), 'url' => $url];
        if (count($out) >= 8) break;
    }
    return $out;
}

function admin_save(): never
{
    $id = (int) ($_POST['id'] ?? 0);
    $old = $id ? admin_find($id) : null;
    $f = fn(string $k, int $max = 300) => mb_substr(trim((string) ($_POST[$k] ?? '')), 0, $max);

    $sub = strtolower($f('subdomain', 40));
    $theme = $f('theme', 40);
    $token = $f('token', 200);
    $domain = strtolower($f('domain', 120));
    $avatar = $f('avatar', 500);
    $errors = [];

    if (!preg_match('/^[a-z0-9](?:[a-z0-9-]{0,38}[a-z0-9])?$/', $sub)) $errors[] = 'Subdomain hanya huruf kecil, angka, dan tanda minus (mis. tokobudi).';
    if (in_array($sub, RESERVED_SUBDOMAINS, true)) $errors[] = "Subdomain \"$sub\" dipakai sistem, pilih nama lain.";
    if (!isset(themes()[$theme])) $errors[] = 'Pilih tema.';
    if ($domain !== '' && !preg_match('/^(?=.{4,120}$)([a-z0-9-]+\.)+[a-z]{2,}$/', $domain)) $errors[] = 'Domain sendiri tidak valid (mis. tokobudi.com).';
    if ($avatar !== '' && !preg_match('~^https?://~i', $avatar)) $errors[] = 'Link foto harus diawali https://';
    if (!$old && $token === '') $errors[] = 'Token lynk.id wajib diisi untuk toko baru.';

    $username = $f('username', 60);
    if (!$errors && $token !== '') {
        if (!str_starts_with($token, 'lynk_sk_')) $errors[] = 'Token lynk.id selalu diawali lynk_sk_';
        else {
            $v = verify_token($token);
            if (!$v['ok']) $errors[] = $v['error'];
            elseif ($v['username'] !== '') $username = $v['username'];
        }
    }
    if ($username === '' && $old) $username = $old['username'];

    if ($errors) {
        $_SESSION['form'] = $_POST;
        $_SESSION['form_errors'] = $errors;
        header('Location: ?a=' . ($id ? 'edit&id=' . $id : 'new'));
        exit;
    }

    $row = [
        'subdomain' => $sub, 'domain' => $domain ?: null, 'theme' => $theme, 'username' => $username,
        'name' => $f('name', 80), 'hi' => $f('hi', 60), 'role' => $f('role', 120), 'about' => $f('about', 400),
        'avatar' => $avatar, 'socials' => json_encode(parse_socials((string) ($_POST['socials'] ?? '')), JSON_UNESCAPED_SLASHES),
        'note' => $f('note', 500), 'updated_at' => time(),
    ];
    if ($token !== '') $row['token_enc'] = encrypt_secret($token);

    try {
        if ($old) {
            $sets = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($row)));
            db()->prepare("UPDATE tenants SET $sets WHERE id = :id")->execute($row + ['id' => $id]);
            if ($token !== '' || $old['theme'] !== $theme) db()->prepare('DELETE FROM cache WHERE tenant_id = ?')->execute([$id]);
        } else {
            $row['created_at'] = time();
            $cols = implode(', ', array_keys($row));
            $vals = implode(', ', array_map(fn($k) => ":$k", array_keys($row)));
            db()->prepare("INSERT INTO tenants ($cols) VALUES ($vals)")->execute($row);
        }
    } catch (PDOException $e) {
        $_SESSION['form'] = $_POST;
        $_SESSION['form_errors'] = [str_contains($e->getMessage(), 'domain') && !str_contains($e->getMessage(), 'subdomain')
            ? 'Domain sendiri itu sudah dipakai toko lain.' : 'Subdomain itu sudah dipakai toko lain.'];
        header('Location: ?a=' . ($id ? 'edit&id=' . $id : 'new'));
        exit;
    }
    admin_flash_redirect($old
        ? "Toko $sub diperbarui."
        : "Toko $sub dibuat. Pastikan subdomain $sub sudah dibuat di hPanel dan diarahkan ke folder aplikasi ini.");
}

function admin_toggle(): never
{
    $t = admin_find((int) ($_POST['id'] ?? 0));
    db()->prepare('UPDATE tenants SET active = ?, updated_at = ? WHERE id = ?')->execute([$t['active'] ? 0 : 1, time(), $t['id']]);
    admin_flash_redirect('Toko ' . $t['subdomain'] . ($t['active'] ? ' dinonaktifkan.' : ' diaktifkan lagi.'));
}

function admin_delete(): never
{
    $t = admin_find((int) ($_POST['id'] ?? 0));
    if (($_POST['confirm'] ?? '') !== $t['subdomain']) admin_flash_redirect('Ketik nama subdomain dengan benar untuk menghapus.', 'err');
    db()->prepare('DELETE FROM tenants WHERE id = ?')->execute([$t['id']]);
    admin_flash_redirect('Toko ' . $t['subdomain'] . ' dihapus beserta tokennya.');
}

function admin_refresh(): never
{
    $t = admin_find((int) ($_POST['id'] ?? 0));
    db()->prepare('DELETE FROM cache WHERE tenant_id = ?')->execute([$t['id']]);
    try {
        $c = tenant_catalog($t);
        admin_flash_redirect('Katalog ' . $t['subdomain'] . ' diperbarui: ' . count($c['products']) . ' produk.');
    } catch (Throwable $e) {
        admin_flash_redirect('Gagal mengambil katalog ' . $t['subdomain'] . ': ' . $e->getMessage(), 'err');
    }
}

/* ---------- Tampilan ---------- */
function admin_list(): never
{
    $st = db()->prepare('SELECT t.*, c.updated_at AS cached_at FROM tenants t LEFT JOIN cache c ON c.tenant_id = t.id AND c.kind = ? ORDER BY t.created_at DESC');
    $st->execute(['catalog']);
    $rows = $st->fetchAll();
    $csrf = e($_SESSION['csrf']);
    $items = '';
    foreach ($rows as $t) {
        $url = store_url($t);
        $age = $t['cached_at'] ? max(0, intdiv(time() - (int) $t['cached_at'], 60)) . ' mnt lalu' : 'belum pernah';
        $items .= '
        <div class="row' . ($t['active'] ? '' : ' off') . '">
          <div class="who">
            <a class="sub" href="' . e($url) . '" target="_blank" rel="noopener">' . e($t['subdomain']) . '</a>
            <span class="muted">' . e($t['name'] ?: $t['username']) . ' · lynk.id/' . e($t['username'] ?: '?') . '</span>
            ' . ($t['note'] ? '<span class="note">' . e($t['note']) . '</span>' : '') . '
          </div>
          <div class="meta">
            <span class="pill">' . e(themes()[$t['theme']] ?? $t['theme']) . '</span>
            <span class="pill ' . ($t['active'] ? 'on' : 'offp') . '">' . ($t['active'] ? 'Aktif' : 'Nonaktif') . '</span>
            <span class="muted small">Katalog: ' . $age . '</span>
          </div>
          <div class="acts">
            <a class="btn ghost" href="?a=edit&id=' . $t['id'] . '">Ubah</a>
            <form method="post" action="?a=refresh"><input type="hidden" name="csrf" value="' . $csrf . '"><input type="hidden" name="id" value="' . $t['id'] . '"><button class="btn ghost">Muat ulang</button></form>
            <form method="post" action="?a=toggle"><input type="hidden" name="csrf" value="' . $csrf . '"><input type="hidden" name="id" value="' . $t['id'] . '"><button class="btn ghost">' . ($t['active'] ? 'Nonaktifkan' : 'Aktifkan') . '</button></form>
          </div>
        </div>';
    }
    if (!$rows) $items = '<div class="empty"><b>Belum ada toko.</b><p class="muted">Tambahkan toko pertama untuk pembeli template kamu.</p></div>';

    admin_page('Toko', '
      <header class="top"><h1>Toko pembeli <span class="count">' . count($rows) . '</span></h1>
        <div class="tacts"><a class="btn" href="?a=new">+ Tambah toko</a>
        <form method="post" action="?a=logout"><input type="hidden" name="csrf" value="' . $csrf . '"><button class="btn ghost">Keluar</button></form></div>
      </header>
      <div class="list">' . $items . '</div>');
}

function admin_form(?array $t): never
{
    $f = $_SESSION['form'] ?? null;
    $errors = $_SESSION['form_errors'] ?? [];
    unset($_SESSION['form'], $_SESSION['form_errors']);
    $v = fn(string $k) => e($f[$k] ?? ($t[$k] ?? ''));
    $socialsText = $f['socials'] ?? implode("\n", array_column(json_decode($t['socials'] ?? '[]', true) ?: [], 'url'));
    $themeNow = $f['theme'] ?? ($t['theme'] ?? cfg('default_theme'));
    $opts = '';
    foreach (themes() as $id => $label) $opts .= '<option value="' . e($id) . '"' . ($id === $themeNow ? ' selected' : '') . '>' . e($label) . '</option>';
    $tokenHint = '';
    if ($t) {
        try { $tokenHint = 'Tersimpan: lynk_sk_…' . e(substr(tenant_token($t), -4)) . '. Kosongkan kalau tidak diganti.'; }
        catch (Throwable $e) { $tokenHint = 'Token tersimpan tidak bisa dibuka. Isi token baru.'; }
    }
    $csrf = e($_SESSION['csrf']);
    $danger = $t ? '
      <form method="post" action="?a=delete" class="card danger">
        <h2>Hapus toko</h2>
        <p class="muted">Token dan data toko ini dihapus permanen. Ketik <b>' . e($t['subdomain']) . '</b> untuk konfirmasi.</p>
        <input type="hidden" name="csrf" value="' . $csrf . '"><input type="hidden" name="id" value="' . $t['id'] . '">
        <div class="inline"><input name="confirm" placeholder="' . e($t['subdomain']) . '" autocomplete="off"><button class="btn red">Hapus permanen</button></div>
      </form>' : '';

    admin_page($t ? 'Ubah ' . $t['subdomain'] : 'Tambah toko', '
      <header class="top"><h1>' . ($t ? 'Ubah toko' : 'Tambah toko') . '</h1><a class="btn ghost" href="?">← Kembali</a></header>
      ' . ($errors ? '<div class="msg err">' . implode('<br>', array_map('e', $errors)) . '</div>' : '') . '
      <form method="post" action="?a=save" class="card form">
        <input type="hidden" name="csrf" value="' . $csrf . '"><input type="hidden" name="id" value="' . ($t['id'] ?? 0) . '">
        <h2>Wajib</h2>
        <div class="grid2">
          <label>Subdomain<input name="subdomain" value="' . $v('subdomain') . '" required pattern="[a-z0-9-]+" placeholder="tokobudi"><small>Alamat: <b>subdomain</b>.' . e(cfg('base_domains')[0] ?? '') . '</small></label>
          <label>Tema<select name="theme">' . $opts . '</select></label>
        </div>
        <label>Token lynk.id<input name="token" value="" placeholder="lynk_sk_…" autocomplete="off" ' . ($t ? '' : 'required') . '><small>' . ($tokenHint ?: 'Dari lynk.id → Settings → Integrations → API Keys. Dicek otomatis lalu disimpan terenkripsi.') . '</small></label>
        <h2>Profil <span class="muted small">(opsional)</span></h2>
        <div class="grid2">
          <label>Nama toko<input name="name" value="' . $v('name') . '" placeholder="Kosong = username lynk.id"></label>
          <label>Sapaan<input name="hi" value="' . $v('hi') . '" placeholder="Kosong = sapaan bawaan tema"></label>
        </div>
        <label>Keterangan singkat<input name="role" value="' . $v('role') . '" placeholder="mis. Skincare · Make up · Bodycare"></label>
        <label>Bio<textarea name="about" rows="2" placeholder="1–2 kalimat tentang toko">' . $v('about') . '</textarea></label>
        <label>Link foto profil<input name="avatar" value="' . $v('avatar') . '" placeholder="https://…jpg"></label>
        <label>Sosmed (satu link per baris)<textarea name="socials" rows="3" placeholder="https://instagram.com/tokobudi&#10;https://wa.me/628123456789">' . e($socialsText) . '</textarea><small>Instagram, TikTok, YouTube, WhatsApp, dan email dikenali otomatis.</small></label>
        <h2>Lainnya <span class="muted small">(opsional)</span></h2>
        <div class="grid2">
          <label>Username lynk.id<input name="username" value="' . $v('username') . '" placeholder="Terisi otomatis dari token"></label>
          <label>Domain sendiri<input name="domain" value="' . $v('domain') . '" placeholder="tokobudi.com"></label>
        </div>
        <label>Catatan internal<input name="note" value="' . $v('note') . '" placeholder="mis. Budi · WA 0812… · paket 1 tahun"></label>
        <button class="btn big">' . ($t ? 'Simpan perubahan' : 'Buat toko') . '</button>
      </form>' . $danger);
}

function admin_page(string $title, string $body): never
{
    $flash = '';
    if (!empty($_SESSION['flash'])) {
        [$type, $msg] = $_SESSION['flash'];
        unset($_SESSION['flash']);
        $flash = '<div class="msg ' . e($type) . '">' . e($msg) . '</div>';
    }
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex"><title>' . e($title) . ' · Admin Etalase</title>
<style>
:root{--bg:#F4F2EE;--card:#fff;--ink:#1D1B22;--muted:#6E6A75;--line:#E4E0DA;--acc:#FF5B37;--ok:#1F8F5F;--err:#C8283A}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--ink);font:15px/1.5 system-ui,-apple-system,"Segoe UI",sans-serif}
.wrap{max-width:920px;margin:0 auto;padding:28px max(16px,env(safe-area-inset-left,0px)) 60px}
h1{margin:0;font-size:26px;letter-spacing:-.02em}h2{margin:22px 0 10px;font-size:15px;text-transform:uppercase;letter-spacing:.08em;color:var(--muted)}
.top{display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:12px;margin-bottom:18px}.tacts{display:flex;gap:8px}.tacts form{margin:0}
.count{display:inline-grid;place-items:center;min-width:28px;height:28px;padding:0 8px;border-radius:14px;background:var(--ink);color:#fff;font-size:14px;vertical-align:middle}
.card{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:22px;margin-bottom:16px}.narrow{max-width:420px;margin:10vh auto 0}
label{display:grid;gap:6px;margin-bottom:14px;font-weight:600;font-size:14px}small{font-weight:400;color:var(--muted)}
input,select,textarea{width:100%;font:inherit;padding:11px 12px;border:1px solid var(--line);border-radius:10px;background:#fff;color:var(--ink)}
input:focus,select:focus,textarea:focus{outline:2px solid var(--acc);outline-offset:1px;border-color:transparent}
.grid2{display:grid;gap:0 14px;grid-template-columns:repeat(auto-fit,minmax(min(100%,260px),1fr))}
.btn{display:inline-flex;align-items:center;justify-content:center;min-height:40px;padding:0 16px;border-radius:10px;border:1px solid var(--ink);background:var(--ink);color:#fff;font:600 14px system-ui,sans-serif;text-decoration:none;cursor:pointer;white-space:nowrap}
.btn.ghost{background:transparent;color:var(--ink);border-color:var(--line)}.btn.ghost:hover{border-color:var(--ink)}.btn.red{background:var(--err);border-color:var(--err)}.btn.big{width:100%;min-height:48px;margin-top:6px;background:var(--acc);border-color:var(--acc)}
.msg{padding:12px 14px;border-radius:12px;margin-bottom:16px;font-weight:500}.msg.ok{background:#E3F5EC;color:#14613F}.msg.err{background:#FCE4E7;color:#9A1C2D}
.muted{color:var(--muted)}.small{font-size:12.5px;font-weight:500}
.list{display:grid;gap:10px}.row{display:grid;grid-template-columns:minmax(0,1.3fr) minmax(0,1fr) auto;gap:10px 16px;align-items:center;background:var(--card);border:1px solid var(--line);border-radius:14px;padding:14px 16px}
.row.off{opacity:.6}.who{display:grid;min-width:0}.sub{font-weight:700;font-size:16px;color:var(--ink)}.who .muted{font-size:13px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.note{font-size:12.5px;color:#7A5A00;background:#FFF5D6;border-radius:6px;padding:2px 6px;width:fit-content;margin-top:4px}
.meta{display:flex;flex-wrap:wrap;gap:6px;align-items:center}.pill{padding:3px 10px;border-radius:999px;background:#EFEDE8;font-size:12.5px;font-weight:600}.pill.on{background:#E3F5EC;color:var(--ok)}.pill.offp{background:#FCE4E7;color:var(--err)}
.acts{display:flex;gap:6px;flex-wrap:wrap}.acts form{margin:0}.acts .btn{min-height:34px;padding:0 12px;font-size:13px}
@media(max-width:720px){.row{grid-template-columns:1fr}}
.empty{text-align:center;padding:40px;background:var(--card);border:1px dashed var(--line);border-radius:14px}
.danger{border-color:#F3C3CA}.inline{display:flex;gap:8px}.inline input{flex:1}
</style></head><body><div class="wrap">' . $flash . $body . '</div></body></html>';
    exit;
}
