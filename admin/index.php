<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin.php';
$u = admin_header('Dashboard', '');
$admin = $u['role'] === 'admin';
[$scope, $sp] = course_scope('c');
$commission = (float) setting('commission_pct', 20) / 100;

$stat = [
    ['Published courses', (int) val("SELECT COUNT(*) FROM courses c WHERE c.status='published' AND $scope", $sp)],
    ['Enrollments', (int) val("SELECT COUNT(*) FROM enrollments e JOIN courses c ON c.id=e.course_id WHERE e.status<>'cancelled' AND $scope", $sp)],
    [$admin ? 'Students' : 'Students (mine)', $admin ? (int) val("SELECT COUNT(*) FROM users WHERE role='student'") : (int) val("SELECT COUNT(DISTINCT e.user_id) FROM enrollments e JOIN courses c ON c.id=e.course_id WHERE $scope", $sp)],
    ['Completions', (int) val("SELECT COUNT(*) FROM enrollments e JOIN courses c ON c.id=e.course_id WHERE e.status='completed' AND $scope", $sp)],
];
$gross = (float) val("SELECT COALESCE(SUM(o.amount),0) FROM orders o JOIN courses c ON c.id=o.course_id WHERE o.status='paid' AND $scope", $sp);
$stat[] = [$admin ? 'Revenue' : 'Your earnings', money_plain($admin ? $gross : $gross * (1 - $commission))];
if ($admin) {
    $stat[] = ['Instructors', (int) val("SELECT COUNT(*) FROM users WHERE role='instructor' AND status='active'")];
}
$months = [];
for ($i = 5; $i >= 0; $i--) $months[date('Y-m', strtotime("first day of -$i month"))] = 0;
foreach (rows("SELECT DATE_FORMAT(e.enrolled_at,'%Y-%m') m, COUNT(*) n FROM enrollments e JOIN courses c ON c.id=e.course_id WHERE e.enrolled_at >= (CURDATE() - INTERVAL 6 MONTH) AND $scope GROUP BY m", $sp) as $r) if (isset($months[$r['m']])) $months[$r['m']] = (int) $r['n'];
$max = max(1, max($months));
$recent = rows("SELECT e.enrolled_at, us.name student, c.title, c.id cid FROM enrollments e JOIN users us ON us.id=e.user_id JOIN courses c ON c.id=e.course_id WHERE $scope ORDER BY e.id DESC LIMIT 8", $sp);
$top = rows("SELECT c.id, c.title, COUNT(e.id) n FROM courses c LEFT JOIN enrollments e ON e.course_id=c.id WHERE $scope GROUP BY c.id ORDER BY n DESC LIMIT 5", $sp);
$pendingCourses = $admin ? rows("SELECT c.id, c.title, us.name FROM courses c JOIN users us ON us.id=c.instructor_id WHERE c.status='pending' ORDER BY c.id DESC LIMIT 5") : [];
$pendingUsers = $admin ? rows("SELECT id, name, email FROM users WHERE status='pending' ORDER BY id DESC LIMIT 5") : [];
$unread = $admin ? (int) val('SELECT COUNT(*) FROM contact_messages') : 0;
?>
<div class="stats"><?php foreach ($stat as [$l, $v]): ?><div class="stat"><b><?= is_int($v) ? number_format($v) : e($v) ?></b><span><?= e($l) ?></span></div><?php endforeach; ?></div>
<?php if ($pendingCourses || $pendingUsers): ?>
<div class="grid2">
  <div class="card"><h2>Courses awaiting review</h2><?php foreach ($pendingCourses as $p): ?><p><a href="course-edit.php?id=<?= (int) $p['id'] ?>"><?= e($p['title']) ?></a> <span class="muted">by <?= e($p['name']) ?></span></p><?php endforeach; if (!$pendingCourses) echo '<p class="muted">Nothing pending.</p>'; ?></div>
  <div class="card"><h2>Instructor applications</h2><?php foreach ($pendingUsers as $p): ?><p><a href="users.php?status=pending"><?= e($p['name']) ?></a> <span class="muted"><?= e($p['email']) ?></span></p><?php endforeach; if (!$pendingUsers) echo '<p class="muted">Nothing pending.</p>'; ?></div>
</div>
<?php endif; ?>
<div class="grid2">
  <div class="card"><h2>Enrollments · last 6 months</h2><div class="chart-wrap"><div class="chart"><?php foreach ($months as $m => $n): ?><div style="height:<?= max(2, (int) round($n / $max * 100)) ?>%" title="<?= $n ?> enrollments"><small><?= e(date('M', strtotime($m . '-01'))) ?></small></div><?php endforeach; ?></div></div></div>
  <div class="card"><h2>Top courses</h2><?php foreach ($top as $t): ?><p style="display:flex;justify-content:space-between;margin:6px 0"><a href="course-edit.php?id=<?= (int) $t['id'] ?>"><?= e($t['title']) ?></a><span class="muted"><?= (int) $t['n'] ?> enrolled</span></p><?php endforeach; if (!$top) echo '<p class="muted">No courses yet.</p>'; ?></div>
</div>
<div class="card"><h2>Recent enrollments <a class="btn sm" href="enrollments.php">View all</a></h2>
<table class="t"><tr><th>Student</th><th>Course</th><th>Date</th></tr><?php foreach ($recent as $r): ?><tr><td><?= e($r['student']) ?></td><td><?= e($r['title']) ?></td><td><?= e(fdate($r['enrolled_at'])) ?></td></tr><?php endforeach; ?>
<?php if (!$recent): ?><tr><td colspan="3" class="empty">No enrollments yet.</td></tr><?php endif; ?></table></div>
<?php if ($admin && $unread): ?><p><a href="messages.php"><?= $unread ?> contact message(s)</a></p><?php endif; ?>
<?php admin_footer();
