<?php
// php install/cli.php host port dbname user pass "Site name" admin@email "Admin Name" adminpass [demo]
if (PHP_SAPI !== 'cli') exit;
define('APP_ROOT', dirname(__DIR__));
require __DIR__ . '/lib.php';
[, $h, $p, $n, $u, $pw, $site, $mail, $an, $ap] = $argv;
$err = run_install(['host' => $h, 'port' => (int) $p, 'name' => $n, 'user' => $u, 'pass' => $pw], ['name' => $an, 'email' => $mail, 'password' => $ap], $site, ($argv[10] ?? '') === 'demo');
echo $err ?? "Installed.\n", "\n";
exit($err ? 1 : 0);
