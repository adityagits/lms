<?php
defined("APP_ROOT") || exit;

function e($v): string { return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

/** URL path of the app root (works in a sub-folder or at the domain root). */
function base_path(): string
{
    static $b = null;
    if ($b !== null) return $b;
    global $CONFIG;
    if (isset($CONFIG['base_path'])) return $b = rtrim($CONFIG['base_path'], '/');
    $doc = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '';
    $app = realpath(APP_ROOT) ?: APP_ROOT;
    if ($doc !== '' && str_starts_with($app, $doc)) {
        return $b = rtrim(str_replace('\\', '/', substr($app, strlen($doc))), '/');
    }
    return $b = '';
}
function url(string $path = ''): string { return base_path() . '/' . ltrim($path, '/'); }
function asset(string $path): string
{
    $f = APP_ROOT . '/' . ltrim($path, '/');
    return url($path) . (is_file($f) ? '?v=' . filemtime($f) : '');
}
function redirect(string $path): never
{
    header('Location: ' . (preg_match('#^https?://#', $path) ? $path : url($path)));
    exit;
}
/** Only allow redirects back into this site. */
function safe_next(?string $next, string $fallback = ''): string
{
    if ($next && str_starts_with($next, '/') && !str_starts_with($next, '//') && !str_contains($next, '\\')) {
        return base_path() !== '' && !str_starts_with($next, base_path() . '/') ? $fallback : $next;
    }
    return $fallback;
}

function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_name('lmssid');
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $https, 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

/* ---- flash messages ---- */
function flash(string $type, string $msg): void { $_SESSION['flash'][] = [$type, $msg]; }
function flashes(): array { $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }

/* ---- CSRF ---- */
function csrf_token(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(24));
}
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'; }
function verify_csrf(): void
{
    $t = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($t) || !hash_equals(csrf_token(), $t)) {
        http_response_code(419);
        exit('Security token expired. Go back, refresh the page and try again.');
    }
}
function is_post(): bool { return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'; }

/* ---- input ---- */
function input(string $k, $default = '') { $v = $_POST[$k] ?? $_GET[$k] ?? $default; return is_string($v) ? trim($v) : $v; }
function int_in(string $k, int $default = 0): int { return (int) ($_POST[$k] ?? $_GET[$k] ?? $default); }

/* ---- formatting ---- */
function currency(): string { return (string) setting('currency', '$'); }
function money($n): string
{
    $n = (float) $n;
    return $n <= 0 ? 'Free' : currency() . number_format($n, 2);
}
function money_plain($n): string { return currency() . number_format((float) $n, 2); }
function slugify(string $s): string
{
    $s = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s), '-'));
    return $s !== '' ? $s : 'item';
}
function unique_slug(string $table, string $base, ?int $ignoreId = null): string
{
    $slug = $base = slugify($base);
    $i = 2;
    while (val("SELECT id FROM `$table` WHERE slug = ?" . ($ignoreId ? ' AND id <> ' . (int) $ignoreId : ''), [$slug])) {
        $slug = $base . '-' . $i++;
    }
    return $slug;
}
function fdate(?string $d, string $fmt = 'M j, Y'): string { return $d ? date($fmt, strtotime($d)) : '—'; }
function minutes_label(int $m): string
{
    if ($m < 60) return $m . ' min';
    return floor($m / 60) . 'h ' . ($m % 60 ? ($m % 60) . 'm' : '');
}
function stars(float $r): string
{
    $full = (int) round($r);
    return '<span class="lms-stars" aria-label="' . e(number_format($r, 1)) . ' out of 5">' . str_repeat('★', $full) . str_repeat('☆', 5 - $full) . '</span>';
}
function paginate(int $total, int $perPage, int $page): array
{
    $pages = max(1, (int) ceil($total / $perPage));
    $page = max(1, min($page, $pages));
    return ['page' => $page, 'pages' => $pages, 'offset' => ($page - 1) * $perPage, 'per' => $perPage, 'total' => $total];
}
function pager(array $p): string
{
    if ($p['pages'] <= 1) return '';
    $qs = $_GET; $out = '<nav class="lms-pager" aria-label="Pagination">';
    for ($i = 1; $i <= $p['pages']; $i++) {
        $qs['page'] = $i;
        $out .= '<a class="' . ($i === $p['page'] ? 'is-current' : '') . '" href="?' . e(http_build_query($qs)) . '">' . $i . '</a>';
    }
    return $out . '</nav>';
}
function text_lines(?string $t): array { return array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $t)))); }

/** Convert a YouTube/Vimeo/mp4 link to embeddable markup. */
function video_embed(string $url): string
{
    $url = trim($url);
    if ($url === '') return '';
    if (preg_match('~(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/)|youtu\.be/)([\w-]{11})~', $url, $m)) {
        return '<iframe src="https://www.youtube.com/embed/' . e($m[1]) . '" title="Video" allowfullscreen loading="lazy"></iframe>';
    }
    if (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $url, $m)) {
        return '<iframe src="https://player.vimeo.com/video/' . e($m[1]) . '" title="Video" allowfullscreen loading="lazy"></iframe>';
    }
    if (preg_match('~^(https?://|/)[^\s]+\.(mp4|webm|ogg)(\?.*)?$~i', $url)) {
        return '<video src="' . e($url) . '" controls preload="metadata"></video>';
    }
    return '';
}

/**
 * Minimal safe HTML for lesson/course descriptions written by instructors.
 * Allows basic formatting only; strips scripts, handlers and unsafe URLs.
 */
function clean_html(?string $html): string
{
    $html = (string) $html;
    if ($html === '') return '';
    $allowed = ['p','br','strong','b','em','i','u','s','ul','ol','li','h2','h3','h4','blockquote','code','pre','a','img','hr','table','thead','tbody','tr','th','td','span'];
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="utf-8"?><div id="root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    $root = $dom->getElementById('root');
    if (!$root) return e($html);
    $walk = function (DOMNode $n) use (&$walk, $allowed) {
        foreach (iterator_to_array($n->childNodes) as $c) {
            if ($c instanceof DOMElement) {
                $tag = strtolower($c->tagName);
                if (!in_array($tag, $allowed, true)) {
                    if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'form'], true)) { $n->removeChild($c); continue; }
                    $walk($c);
                    while ($c->firstChild) $n->insertBefore($c->firstChild, $c);
                    $n->removeChild($c);
                    continue;
                }
                foreach (iterator_to_array($c->attributes) as $a) {
                    $name = strtolower($a->name); $v = trim($a->value);
                    $ok = ($tag === 'a' && $name === 'href' && preg_match('~^(https?:|mailto:|/|#)~i', $v))
                        || ($tag === 'img' && in_array($name, ['src', 'alt'], true) && ($name === 'alt' || preg_match('~^(https?:|/)~i', $v)))
                        || in_array($name, ['colspan', 'rowspan'], true);
                    if (!$ok) $c->removeAttributeNode($a);
                }
                if ($tag === 'a') { $c->setAttribute('rel', 'noopener noreferrer'); }
                $walk($c);
            }
        }
    };
    $walk($root);
    $out = '';
    foreach ($root->childNodes as $c) $out .= $dom->saveHTML($c);
    return $out;
}

/* ---- uploads ---- */
function upload_dir(string $sub = ''): string
{
    $d = APP_ROOT . '/uploads' . ($sub !== '' ? '/' . trim($sub, '/') : '');
    if (!is_dir($d)) mkdir($d, 0755, true);
    return $d;
}
/** Save an uploaded image; returns public path like "uploads/courses/ab12.jpg" or null. */
function save_image_upload(string $field, string $sub, ?string &$error = null): ?string
{
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return null;
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) { $error = 'Upload failed (code ' . $f['error'] . ').'; return null; }
    if ($f['size'] > 5 * 1024 * 1024) { $error = 'Image must be 5 MB or smaller.'; return null; }
    $info = @getimagesize($f['tmp_name']);
    $map = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif', IMAGETYPE_WEBP => 'webp'];
    if (!$info || !isset($map[$info[2]])) { $error = 'Only JPG, PNG, GIF or WebP images are allowed.'; return null; }
    $name = bin2hex(random_bytes(8)) . '.' . $map[$info[2]];
    if (!move_uploaded_file($f['tmp_name'], upload_dir($sub) . '/' . $name)) { $error = 'Could not save the image.'; return null; }
    return 'uploads/' . trim($sub, '/') . '/' . $name;
}
function img_url(?string $path, string $fallback = ''): string
{
    if ($path) return preg_match('#^https?://#', $path) ? $path : url($path);
    return $fallback ?: placeholder_img();
}
function placeholder_img(): string
{
    return 'data:image/svg+xml;utf8,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="640" height="360"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#e0e7ff"/><stop offset="1" stop-color="#c7d2fe"/></linearGradient></defs><rect width="100%" height="100%" fill="url(#g)"/><text x="50%" y="52%" font-family="sans-serif" font-size="34" fill="#6366f1" text-anchor="middle">Course</text></svg>');
}
function avatar_url(array $u): string
{
    if (!empty($u['avatar'])) return img_url($u['avatar']);
    $ini = strtoupper(mb_substr($u['name'] ?? '?', 0, 1));
    return 'data:image/svg+xml;utf8,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="96" height="96"><rect width="100%" height="100%" fill="#c7d2fe"/><text x="50%" y="54%" font-family="sans-serif" font-size="44" fill="#4338ca" text-anchor="middle" dominant-baseline="middle">' . htmlspecialchars($ini) . '</text></svg>');
}
