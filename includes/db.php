<?php
defined("APP_ROOT") || exit;
/** Thin PDO wrapper. All queries use prepared statements. */

function db(): PDO
{
    static $pdo = null;
    if ($pdo) return $pdo;
    global $CONFIG;
    $c = $CONFIG['db'];
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $c['host'] ?? 'localhost', $c['port'] ?? 3306, $c['name'] ?? '');
    try {
        $pdo = new PDO($dsn, $c['user'] ?? '', $c['pass'] ?? '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        exit(!empty($CONFIG['debug']) ? 'Database connection failed: ' . e($e->getMessage()) : 'Database connection failed.');
    }
    return $pdo;
}

function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}
function rows(string $sql, array $params = []): array { return q($sql, $params)->fetchAll(); }
function row(string $sql, array $params = []): ?array { $r = q($sql, $params)->fetch(); return $r === false ? null : $r; }
function val(string $sql, array $params = []) { $r = q($sql, $params)->fetchColumn(); return $r === false ? null : $r; }
function insert(string $table, array $data): int
{
    $cols = array_keys($data);
    q('INSERT INTO `' . $table . '` (`' . implode('`,`', $cols) . '`) VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')', array_values($data));
    return (int) db()->lastInsertId();
}
function update(string $table, array $data, string $where, array $whereParams = []): int
{
    $set = implode(',', array_map(fn($c) => "`$c`=?", array_keys($data)));
    return q("UPDATE `$table` SET $set WHERE $where", [...array_values($data), ...$whereParams])->rowCount();
}

function setting(string $key, $default = '')
{
    static $cache = null;
    if ($cache === null) {
        try { $cache = array_column(rows('SELECT k, v FROM settings'), 'v', 'k'); } catch (Throwable $e) { $cache = []; }
    }
    return $cache[$key] ?? $default;
}
function save_setting(string $key, string $value): void
{
    q('INSERT INTO settings (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)', [$key, $value]);
}
