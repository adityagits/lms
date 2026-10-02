<?php
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

if (!is_file(APP_ROOT . '/config.php')) {
    $self = $_SERVER['SCRIPT_NAME'] ?? '';
    if (!str_contains($self, '/install/')) {
        header('Location: ' . rtrim(dirname($self), '/\\') . '/install/');
        exit;
    }
    $CONFIG = ['db' => [], 'debug' => true];
} else {
    $CONFIG = require APP_ROOT . '/config.php';
}

error_reporting(E_ALL);
ini_set('display_errors', !empty($CONFIG['debug']) ? '1' : '0');
date_default_timezone_set($CONFIG['timezone'] ?? 'UTC');

require __DIR__ . '/helpers.php';
require __DIR__ . '/db.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/lms.php';
require __DIR__ . '/layout.php';

start_session();
