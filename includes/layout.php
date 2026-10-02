<?php
defined("APP_ROOT") || exit;
/** Page chrome: public site layout, admin shell, error pages. */

function theme_head(): string
{
    $fonts = setting('theme_fonts', '');
    $h = '';
    if ($fonts && str_starts_with($fonts, 'https://fonts.googleapis.com/')) $h .= '<link rel="stylesheet" href="' . e($fonts) . '">' . "\n";
    $h .= '<link rel="stylesheet" href="' . e(asset('wb/theme/theme.css')) . '">' . "\n";
    $h .= '<link rel="stylesheet" href="' . e(asset('assets/css/lms.css')) . '">' . "\n";
    $vars = setting('theme_css', '');
    $custom = setting('custom_css', '');
    if ($vars !== '' || $custom !== '') {
        // theme_css / custom_css are admin-authored; neutralise any attempt to close the style tag
        $h .= '<style>' . str_ireplace('</style', '<\/style', $vars . "\n" . $custom) . '</style>' . "\n";
    }
    return $h;
}

function public_header(string $title, array $o = []): void
{
    $site = setting('site_name', 'LMS');
    $u = current_user();
    $menu = rows("SELECT slug, title FROM pages WHERE status='published' AND show_in_menu=1 ORDER BY menu_order, id");
    $desc = $o['description'] ?? setting('tagline', '');
    ?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="icon" href="data:,">
<title><?= e($title === $site ? $site : $title . ' · ' . $site) ?></title>
<?php if ($desc): ?><meta name="description" content="<?= e($desc) ?>"><?php endif; ?>

<?= theme_head() ?>
</head>
<body class="lms-public">
<header class="wb-section wb-nav" data-pad="none"><div class="wb-container">
  <a class="wb-nav__brand" href="<?= e(url('')) ?>"><?= e($site) ?></a>
  <button class="wb-nav__toggle" type="button" data-wb-nav-toggle aria-label="Toggle menu" aria-expanded="false">☰</button>
  <ul class="wb-nav__links">
    <li><a href="<?= e(url('courses.php')) ?>">Courses</a></li>
    <?php foreach ($menu as $m): ?><li><a href="<?= e(url('page.php?slug=' . $m['slug'])) ?>"><?= e($m['title']) ?></a></li><?php endforeach; ?>
    <?php if ($u): ?>
      <li><a href="<?= e(url(home_for($u))) ?>"><?= in_array($u['role'], ['admin', 'instructor'], true) ? 'Dashboard' : 'My learning' ?></a></li>
      <li><a href="<?= e(url('account.php')) ?>"><?= e(explode(' ', $u['name'])[0]) ?></a></li>
      <li><a class="wb-btn wb-btn--outline" href="<?= e(url('logout.php')) ?>">Sign out</a></li>
    <?php else: ?>
      <li><a href="<?= e(url('login.php')) ?>">Sign in</a></li>
      <li><a class="wb-btn" href="<?= e(url('register.php')) ?>">Join free</a></li>
    <?php endif; ?>
  </ul>
</div></header>
<main>
<?php foreach (flashes() as [$t, $m]): ?><div class="lms-flash lms-flash--<?= e($t) ?>"><div class="wb-container"><?= e($m) ?></div></div><?php endforeach; ?>
<?php
}

function public_footer(): void
{
    $site = setting('site_name', 'LMS');
    $menu = rows("SELECT slug, title FROM pages WHERE status='published' AND show_in_menu=1 ORDER BY menu_order, id");
    $cats = rows('SELECT name, slug FROM categories ORDER BY name LIMIT 6');
    ?>
</main>
<footer class="wb-section wb-footer" data-bg="dark" data-pad="md"><div class="wb-container">
  <div class="wb-footer-grid">
    <div><strong class="wb-nav__brand"><?= e($site) ?></strong><p class="wb-small"><?= e(setting('tagline', '')) ?></p></div>
    <div><h4>Learn</h4><ul class="lms-list-plain">
      <li><a href="<?= e(url('courses.php')) ?>">All courses</a></li>
      <?php foreach ($cats as $c): ?><li><a href="<?= e(url('courses.php?category=' . $c['slug'])) ?>"><?= e($c['name']) ?></a></li><?php endforeach; ?>
    </ul></div>
    <div><h4>Company</h4><ul class="lms-list-plain">
      <?php foreach ($menu as $m): ?><li><a href="<?= e(url('page.php?slug=' . $m['slug'])) ?>"><?= e($m['title']) ?></a></li><?php endforeach; ?>
      <li><a href="<?= e(url('register.php?as=instructor')) ?>">Become an instructor</a></li>
    </ul></div>
  </div>
  <div class="wb-footer__bottom"><span>© <?= date('Y') ?> <?= e($site) ?>. All rights reserved.</span></div>
</div></footer>
<script src="<?= e(asset('wb/theme/theme.js')) ?>"></script>
</body>
</html>
<?php
}

function render_error(int $code, string $msg): never
{
    http_response_code($code);
    public_header($code === 404 ? 'Not found' : 'Error');
    echo '<section class="wb-section"><div class="wb-container" style="text-align:center"><h1>' . $code . '</h1><p class="wb-lead" style="margin-inline:auto">' . e($msg) . '</p><p><a class="wb-btn" href="' . e(url('')) . '">Back to home</a></p></div></section>';
    public_footer();
    exit;
}

/* ------------------------------------------------------------------ admin shell */

function admin_menu(array $u): array
{
    $all = [
        ['', 'Dashboard', '▦', ['admin', 'instructor']],
        ['courses.php', 'Courses', '📚', ['admin', 'instructor']],
        ['categories.php', 'Categories', '🏷', ['admin']],
        ['students.php', 'Students', '🎓', ['admin', 'instructor']],
        ['enrollments.php', 'Enrollments', '✅', ['admin', 'instructor']],
        ['reviews.php', 'Reviews', '★', ['admin', 'instructor']],
        ['announcements.php', 'Announcements', '📣', ['admin', 'instructor']],
        ['orders.php', 'Orders & earnings', '💳', ['admin', 'instructor']],
        ['coupons.php', 'Coupons', '🎟', ['admin']],
        ['users.php', 'Users', '👥', ['admin']],
        ['pages.php', 'Pages (builder)', '🧱', ['admin']],
        ['messages.php', 'Messages', '✉', ['admin']],
        ['settings.php', 'Settings', '⚙', ['admin']],
        ['profile.php', 'My profile', '🙍', ['admin', 'instructor']],
    ];
    return array_values(array_filter($all, fn($m) => in_array($u['role'], $m[3], true)));
}

function admin_header(string $title, string $active = ''): array
{
    $u = require_login('admin', 'instructor');
    if ($u['role'] === 'instructor' && $u['status'] !== 'active') {
        render_error(403, 'Your instructor account is awaiting approval by an administrator.');
    }
    $site = setting('site_name', 'LMS');
    ?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="icon" href="data:,">
<title><?= e($title) ?> · <?= e($site) ?> Admin</title>
<link rel="stylesheet" href="<?= e(asset('assets/css/admin.css')) ?>">
</head>
<body class="adm">
<aside class="adm-side" id="adm-side">
  <a class="adm-brand" href="<?= e(url('admin/')) ?>"><?= e($site) ?><small><?= $u['role'] === 'admin' ? 'Admin' : 'Instructor' ?></small></a>
  <nav>
    <?php foreach (admin_menu($u) as [$href, $label, $icon]): ?>
      <a href="<?= e(url('admin/' . $href)) ?>" class="<?= $active === $href ? 'active' : '' ?>"><span><?= $icon ?></span><?= e($label) ?></a>
    <?php endforeach; ?>
  </nav>
  <a class="adm-view" href="<?= e(url('')) ?>" target="_blank" rel="noopener">View site ↗</a>
</aside>
<div class="adm-main">
  <header class="adm-top">
    <button class="adm-burger" type="button" onclick="document.body.classList.toggle('adm-open')" aria-label="Menu">☰</button>
    <h1><?= e($title) ?></h1>
    <div class="adm-user"><span><?= e($u['name']) ?></span> <a href="<?= e(url('logout.php')) ?>">Sign out</a></div>
  </header>
  <div class="adm-body">
  <?php foreach (flashes() as [$t, $m]): ?><div class="adm-flash adm-flash--<?= e($t) ?>"><?= e($m) ?></div><?php endforeach; ?>
<?php
    return $u;
}

function admin_footer(): void
{
    ?>
  </div>
</div>
<script src="<?= e(asset('assets/js/admin.js')) ?>"></script>
</body>
</html>
<?php
}
