<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin.php';
$u = admin_header('Students', 'students.php');
[$scope, $sp] = course_scope('c');
$qs = input('q');
$where = "$scope"; $p = $sp;
if ($qs !== '') { $where .= ' AND (us.name LIKE ? OR us.email LIKE ?)'; $like = '%' . addcslashes($qs, '%_\\') . '%'; $p[] = $like; $p[] = $like; }
$total = (int) val("SELECT COUNT(DISTINCT us.id) FROM users us JOIN enrollments e ON e.user_id = us.id JOIN courses c ON c.id = e.course_id WHERE $where", $p);
$pg = paginate($total, 20, int_in('page', 1));
$list = rows("SELECT us.id, us.name, us.email, us.created_at, COUNT(e.id) courses, SUM(e.status='completed') completed, MAX(e.enrolled_at) last_enrolled FROM users us JOIN enrollments e ON e.user_id = us.id JOIN courses c ON c.id = e.course_id WHERE $where GROUP BY us.id ORDER BY last_enrolled DESC LIMIT {$pg['per']} OFFSET {$pg['offset']}", $p);
?>
<form class="filters" method="get"><input type="search" name="q" value="<?= e($qs) ?>" placeholder="Search name or email"><button class="btn" type="submit">Search</button></form>
<div class="table-wrap"><table class="t"><tr><th>Student</th><th>Email</th><th>Courses</th><th>Completed</th><th>Last enrolled</th></tr>
<?php foreach ($list as $s): ?><tr><td><strong><?= e($s['name']) ?></strong></td><td><?= e($s['email']) ?></td><td><?= (int) $s['courses'] ?></td><td><?= (int) $s['completed'] ?></td><td><?= e(fdate($s['last_enrolled'])) ?></td></tr><?php endforeach; ?>
<?php if (!$list): ?><tr><td colspan="5" class="empty">No students yet.</td></tr><?php endif; ?></table></div>
<?= admin_pager($pg) ?>
<?php admin_footer();
