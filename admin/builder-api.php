<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin.php';

header('Content-Type: application/json');
$fail = function (int $code, string $msg) { http_response_code($code); echo json_encode(['ok' => false, 'error' => $msg]); exit; };

$u = current_user();
if (!$u || $u['role'] !== 'admin') $fail(403, 'Not allowed.');
if (!is_post()) $fail(405, 'POST only.');
if (!hash_equals(csrf_token(), $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) $fail(419, 'Security token expired — reload the builder.');

$body = json_decode(file_get_contents('php://input'), true);
$p = is_array($body) ? row('SELECT * FROM pages WHERE id = ?', [(int) ($body['id'] ?? 0)]) : null;
$project = is_array($body) ? ($body['project'] ?? null) : null;
if (!$p || !is_array($project) || empty($project['pages'][0])) $fail(400, 'Invalid request.');
$page = $project['pages'][0];

/** Move inline base64 images out of the HTML into /uploads/pages (deduplicated by content hash). */
$extract = function (string $html): string {
    return preg_replace_callback('~data:image/(png|jpe?g|webp|gif);base64,([A-Za-z0-9+/=]+)~', function ($m) {
        $bin = base64_decode($m[2], true);
        if ($bin === false || strlen($bin) > 8 * 1024 * 1024 || !@getimagesizefromstring($bin)) return $m[0];
        $ext = $m[1] === 'jpeg' ? 'jpg' : $m[1];
        $name = sha1($bin) . '.' . $ext;
        $dir = upload_dir('pages');
        if (!is_file("$dir/$name")) file_put_contents("$dir/$name", $bin);
        return '{{base}}/uploads/pages/' . $name;
    }, $html);
};

/** Turn app-absolute URLs (/lms/courses.php) back into the portable {{base}} token. */
$portable = fn(string $html): string => preg_replace('~\b(href|src|action)="' . preg_quote(base_path(), '~') . '/(?!/)~', '$1="{{base}}/', $html);

$blocks = [];
foreach ((array) ($page['blocks'] ?? []) as $b) {
    if (!is_array($b) || !is_string($b['html'] ?? null)) continue;
    $blocks[] = ['id' => substr(preg_replace('/\W/', '', (string) ($b['id'] ?? '')), 0, 16) ?: bin2hex(random_bytes(4)), 'type' => substr(preg_replace('/[^\w:-]/', '', (string) ($b['type'] ?? 'custom')), 0, 40), 'html' => $portable($extract($b['html']))];
}

// Theme: only accept known token names / safe values, rebuild the CSS on the server.
$theme = [];
foreach ((array) ($project['theme'] ?? []) as $k => $v) {
    if (is_string($k) && preg_match('/^--wb-[a-z-]{1,40}$/', $k) && is_string($v) && strlen($v) <= 200 && preg_match('/^[\w\s#%.,\'"()\/-]*$/', $v)) $theme[$k] = trim($v);
}
$css = '';
if ($theme) {
    $css = ":root{\n";
    foreach ($theme as $k => $v) $css .= "  $k: $v;\n";
    $css .= '}';
}
$google = ['Inter', 'Poppins', 'Roboto', 'Montserrat', 'Nunito', 'Space Grotesk', 'Playfair Display', 'DM Serif Display', 'Lora', 'Merriweather'];
$used = [];
foreach ($theme as $v) foreach ($google as $g) if (str_starts_with($v, "'$g'")) $used[$g] = true;
$fonts = $used ? 'https://fonts.googleapis.com/css2?' . implode('&', array_map(fn($g) => 'family=' . str_replace(' ', '+', $g) . ':wght@400;500;600;700;800', array_keys($used))) . '&display=swap' : '';

$json = json_encode($blocks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
update('pages', [
    'blocks_json' => $json,
    'title' => mb_substr(trim((string) ($page['title'] ?? '')), 0, 200) ?: $p['title'],
    'description' => mb_substr(trim((string) ($page['description'] ?? '')), 0, 300),
], 'id = ?', [$p['id']]);
save_setting('theme_json', json_encode($theme ?: new stdClass()));
save_setting('theme_css', $css);
save_setting('theme_fonts', $fonts);
save_setting('custom_css', str_ireplace('</style', '<\/style', (string) ($project['customCss'] ?? '')));

echo json_encode(['ok' => true, 'saved_at' => date('H:i:s'), 'blocks' => count($blocks)]);
