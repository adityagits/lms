<?php
defined("APP_ROOT") || exit;
/** Helpers for admin/instructor pages. */

function badge(string $status): string { return '<span class="badge ' . e($status) . '">' . e(ucfirst($status)) . '</span>'; }

/** Load a course the current user may manage (admins: any; instructors: own) or stop with 404/403. */
function own_course(int $id): array
{
    $c = row('SELECT * FROM courses WHERE id = ?', [$id]);
    if (!$c) render_error(404, 'Course not found.');
    if (!can_manage_course($c)) render_error(403, 'You cannot manage this course.');
    return $c;
}
/** SQL fragment restricting course rows to the current instructor (admins see all). */
function course_scope(string $alias = 'c'): array
{
    $u = current_user();
    return $u['role'] === 'admin' ? ['1=1', []] : ["$alias.instructor_id = ?", [$u['id']]];
}

function admin_pager(array $p): string
{
    if ($p['pages'] <= 1) return '';
    $qs = $_GET; $o = '<div class="pager">';
    for ($i = 1; $i <= $p['pages']; $i++) { $qs['page'] = $i; $o .= '<a class="' . ($i === $p['page'] ? 'is-current' : '') . '" href="?' . e(http_build_query($qs)) . '">' . $i . '</a>'; }
    return $o . '</div>';
}

/** Small POST form with a single button (delete, approve, ...). */
function action_form(string $action, array $fields, string $label, string $class = 'btn sm', ?string $confirm = null, string $to = ''): string
{
    $h = '<form method="post" action="' . e($to) . '" ' . ($confirm ? 'onsubmit="return confirm(\'' . e(addslashes($confirm)) . '\')"' : '') . '>' . csrf_field() . '<input type="hidden" name="action" value="' . e($action) . '">';
    foreach ($fields as $k => $v) $h .= '<input type="hidden" name="' . e($k) . '" value="' . e($v) . '">';
    return $h . '<button class="' . e($class) . '" type="submit">' . e($label) . '</button></form>';
}

/** Move a row up/down among siblings that share $scopeCol = $scopeVal, by `position`. */
function move_position(string $table, int $id, string $dir, string $scopeCol, $scopeVal): void
{
    $items = rows("SELECT id FROM `$table` WHERE `$scopeCol` = ? ORDER BY position, id", [$scopeVal]);
    $ids = array_column($items, 'id');
    $i = array_search($id, array_map('intval', $ids), true);
    if ($i === false) return;
    $j = $dir === 'up' ? $i - 1 : $i + 1;
    if (!isset($ids[$j])) return;
    [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
    foreach ($ids as $pos => $rid) q("UPDATE `$table` SET position = ? WHERE id = ?", [$pos + 1, $rid]);
}
function next_position(string $table, string $scopeCol, $scopeVal): int
{
    return (int) val("SELECT COALESCE(MAX(position),0) + 1 FROM `$table` WHERE `$scopeCol` = ?", [$scopeVal]);
}
