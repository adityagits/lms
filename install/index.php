<?php
declare(strict_types=1);
define('APP_ROOT', dirname(__DIR__));
if (is_file(APP_ROOT . '/config.php')) { http_response_code(403); exit('Already installed. Delete config.php (and use an empty database) to reinstall. For security, also delete the install/ folder.'); }
require __DIR__ . '/lib.php';
session_start();
$err = null; $ok = false;
$v = fn($k, $d = '') => htmlspecialchars((string) ($_POST[$k] ?? $d), ENT_QUOTES);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['t'] ?? '', $_POST['t'] ?? '')) { $err = 'Session expired — reload the page.'; }
    elseif (strlen($_POST['password'] ?? '') < 8) { $err = 'Admin password must be at least 8 characters.'; }
    elseif (!filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL)) { $err = 'Enter a valid admin email.'; }
    else {
        $err = run_install(
            ['host' => trim($_POST['db_host']), 'port' => (int) ($_POST['db_port'] ?: 3306), 'name' => trim($_POST['db_name']), 'user' => trim($_POST['db_user']), 'pass' => (string) $_POST['db_pass']],
            ['name' => trim($_POST['name']), 'email' => trim($_POST['email']), 'password' => $_POST['password']],
            trim($_POST['site_name']) ?: 'My LMS', !empty($_POST['demo'])
        );
        $ok = $err === null;
    }
}
$_SESSION['t'] = $_SESSION['t'] ?? bin2hex(random_bytes(16));
$base = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Install LMS</title>
<style>body{font:15px/1.5 system-ui,sans-serif;background:#f4f6fb;margin:0;padding:30px}main{max-width:640px;margin:auto;background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:28px}h1{margin-top:0}label{display:block;font-weight:600;margin:14px 0 4px}input[type=text],input[type=password],input[type=email],input[type=number]{width:100%;padding:9px 11px;border:1px solid #d1d5db;border-radius:8px;font:inherit;box-sizing:border-box}.row{display:grid;grid-template-columns:1fr 1fr;gap:14px}button{margin-top:22px;background:#4f46e5;color:#fff;border:0;border-radius:8px;padding:11px 20px;font:inherit;font-weight:600;cursor:pointer}.err{background:#fee2e2;color:#991b1b;padding:10px 14px;border-radius:8px}.ok{background:#dcfce7;color:#166534;padding:14px;border-radius:8px}h2{font-size:16px;margin:26px 0 0;border-top:1px solid #eee;padding-top:18px}</style></head><body><main>
<h1>Install LMS</h1>
<?php if ($ok): ?>
<div class="ok"><strong>Installed!</strong> <a href="<?= $base ?>/login.php">Sign in</a> with your admin account. <br><b>Delete the <code>install/</code> folder now.</b></div>
<?php else: ?>
<p>Create an empty MySQL/MariaDB database first, then fill in the form.</p>
<?php if ($err): ?><div class="err"><?= $err ?></div><?php endif; ?>
<form method="post"><input type="hidden" name="t" value="<?= htmlspecialchars($_SESSION['t']) ?>">
<h2>Database</h2>
<div class="row"><div><label>Host</label><input type="text" name="db_host" value="<?= $v('db_host', 'localhost') ?>" required></div><div><label>Port</label><input type="number" name="db_port" value="<?= $v('db_port', '3306') ?>"></div></div>
<label>Database name</label><input type="text" name="db_name" value="<?= $v('db_name', 'lms') ?>" required>
<div class="row"><div><label>Username</label><input type="text" name="db_user" value="<?= $v('db_user') ?>" required></div><div><label>Password</label><input type="password" name="db_pass"></div></div>
<h2>Site</h2>
<label>Site name</label><input type="text" name="site_name" value="<?= $v('site_name', 'My LMS') ?>" required>
<h2>Administrator</h2>
<label>Name</label><input type="text" name="name" value="<?= $v('name', 'Administrator') ?>" required>
<label>Email</label><input type="email" name="email" value="<?= $v('email') ?>" required>
<label>Password (min 8 characters)</label><input type="password" name="password" required>
<label style="font-weight:500;margin-top:18px"><input type="checkbox" name="demo" value="1" <?= !$_POST || !empty($_POST['demo']) ? 'checked' : '' ?>> Add demo content (sample courses, instructor and student)</label>
<button type="submit">Install</button></form>
<?php endif; ?></main></body></html>
