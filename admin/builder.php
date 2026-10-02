<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin.php';
require_login('admin');
$p = row('SELECT * FROM pages WHERE id = ?', [int_in('id')]);
if (!$p) render_error(404, 'Page not found.');

$blocks = json_decode($p['blocks_json'] ?: '[]', true) ?: [];
// {{base}} is a portable token in storage; the editor needs real URLs so images/links resolve inside the canvas
foreach ($blocks as &$b) $b['html'] = str_replace('{{base}}', base_path(), (string) ($b['html'] ?? ''));
unset($b);
$theme = json_decode(setting('theme_json', '') ?: '{}', true);
$seed = [
    'name' => $p['title'],
    'pages' => [['id' => 'p' . $p['id'], 'name' => $p['title'], 'slug' => $p['slug'], 'title' => $p['title'], 'description' => (string) $p['description'], 'blocks' => $blocks]],
    'theme' => is_array($theme) && $theme ? $theme : new stdClass(),
    'customCss' => setting('custom_css', ''),
];
$cfg = ['saveUrl' => url('admin/builder-api.php'), 'csrf' => csrf_token(), 'pageId' => (int) $p['id'], 'backUrl' => url('admin/pages.php'),
        'viewUrl' => $p['slug'] === 'home' ? url('') : url('page.php?slug=' . $p['slug']), 'title' => $p['title']];
// JSON inside <script>: escape so it can never terminate the tag
$js = fn($v) => json_encode($v, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="icon" href="data:,">
  <title>Design: <?= e($p['title']) ?> · <?= e(setting('site_name', 'LMS')) ?></title>
  <base href="<?= e(url('wb/')) ?>">
  <link rel="stylesheet" href="builder/builder.css">
  <link rel="stylesheet" href="lms-builder.css">
</head>
<body class="lms-builder">
  <header id="topbar">
    <div class="tb-left">
      <button class="icon-btn only-small" id="toggle-left" title="Components" aria-label="Components">☰</button>
      <strong class="logo">▦ <span class="logo-text"><?= e($p['title']) ?></span></strong>
      <input id="project-name" class="tb-name" aria-label="Page title" hidden>
    </div>
    <div class="tb-center">
      <button class="icon-btn" data-device="desktop" title="Desktop">🖥</button>
      <button class="icon-btn" data-device="tablet" title="Tablet">▭</button>
      <button class="icon-btn" data-device="mobile" title="Mobile">📱</button>
      <span class="sep"></span>
      <button class="icon-btn" id="undo" title="Undo (Ctrl+Z)">↶</button>
      <button class="icon-btn" id="redo" title="Redo (Ctrl+Shift+Z)">↷</button>
    </div>
    <div class="tb-right">
      <button class="icon-btn only-small" id="toggle-right" title="Inspector" aria-label="Inspector">⚙</button>
      <span id="lms-status" class="lms-status">Saved</span>
      <button class="btn" id="preview">👁 Preview</button>
      <!-- export menu elements are required by the builder script but hidden in LMS mode -->
      <div class="menu" hidden><button class="btn" id="export-menu-btn"></button><div class="menu-list" id="export-menu" hidden></div></div>
      <input type="file" id="import-file" hidden>
    </div>
  </header>

  <aside id="left">
    <nav class="tabs" id="left-tabs">
      <button data-tab="pages" class="active">Pages</button>
      <button data-tab="components">Components</button>
      <button data-tab="media">Media</button>
      <button data-tab="theme">Theme</button>
    </nav>
    <div class="panel" id="panel-pages"></div>
    <div class="panel" id="panel-components" hidden></div>
    <div class="panel" id="panel-media" hidden></div>
    <div class="panel" id="panel-theme" hidden></div>
  </aside>

  <main id="stage">
    <div id="textbar" role="toolbar" aria-label="Text formatting"></div>
    <div id="frame-wrap"><iframe id="canvas" title="Page canvas"></iframe></div>
  </main>

  <aside id="right">
    <div class="panel-title">Inspector</div>
    <div class="panel" id="inspector"></div>
  </aside>

  <dialog id="modal"><form method="dialog">
    <h3 id="modal-title"></h3>
    <textarea id="modal-text" spellcheck="false"></textarea>
    <div class="modal-actions"><button class="btn" value="cancel">Cancel</button><button class="btn primary" id="modal-ok" value="ok">Apply</button></div>
  </form></dialog>
  <div id="drawer-backdrop"></div>
  <div id="toast" hidden></div>

  <script>window.WB_SEED = <?= $js($seed) ?>; window.LMS_BUILDER = <?= $js($cfg) ?>;</script>
  <script src="src/components.js"></script>
  <script src="src/templates.js"></script>
  <script src="src/theme-presets.js"></script>
  <script src="src/builder.js"></script>
  <script src="src/text-toolbar.js"></script>
  <script src="src/nesting.js"></script>
  <script src="src/media.js"></script>
  <script src="src/widgets.js"></script>
  <script src="lms-components.js"></script>
  <script src="lms-bridge.js"></script>
</body>
</html>
