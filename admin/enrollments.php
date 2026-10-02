<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin.php';
$u = require_login('admin', 'instructor');
$admin = $u['role'] === 'admin';
if (is_post()) {
    verify_csrf();
    switch (input('action')) {
        case 'add':
            $c = own_course(int_in('course'));
            $stu = row("SELECT id FROM users WHERE email = ? AND role = 'student'", [strtolower((string) input('email'))]);
            if (!$stu) flash('error', 'No student account with that email.');
            else { enroll_user((int) $stu['id'], (int) $c['id']); flash('success', 'Student enrolled.'); }
            break;
        case 'cancel': case 'restore':
            $e = row('SELECT * FROM enrollments WHERE id = ?', [int_in('id')]);
            if ($e) { own_course((int) $e['course_id']); update('enrollments', ['status' => input('action') === 'cancel' ? 'cancelled' : 'active'], 'id = ?', [$e['id']]); flash('success', 'Enrollment updated.'); }
            break;
    }
    redirect('admin/enrollments.php');
}
admin_header('Enrollments', 'enrollments.php');
[$scope, $sp] = course_scope('c');
$pg = paginate((int) val("SELECT COUNT(*) FROM enrollments e JOIN courses c ON c.id = e.course_id WHERE $scope", $sp), 20, int_in('page', 1));
$list = rows("SELECT e.*, us.name student, us.email, c.title, c.id cid FROM enrollments e JOIN users us ON us.id = e.user_id JOIN courses c ON c.id = e.course_id WHERE $scope ORDER BY e.id DESC LIMIT {$pg['per']} OFFSET {$pg['offset']}", $sp);
$mine = rows("SELECT c.id, c.title FROM courses c WHERE $scope ORDER BY c.title", $sp);
?>
<form class="f card" method="post" style="display:flex;gap:10px;flex-wrap:wrap;align-items:end"><?= csrf_field() ?><input type="hidden" name="action" value="add">
  <div style="flex:1;min-width:180px"><label>Enroll a student by email</label><input type="email" name="email" required placeholder="student@example.com"></div>
  <div style="flex:1;min-width:180px"><label>Course</label><select name="course"><?php foreach ($mine as $m): ?><option value="<?= (int) $m['id'] ?>"><?= e($m['title']) ?></option><?php endforeach; ?></select></div>
  <button class="btn primary" type="submit">Enroll</button></form>
<div class="table-wrap"><table class="t"><tr><th>Student</th><th>Course</th><th>Progress</th><th>Enrolled</th><th>Status</th><th></th></tr>
<?php foreach ($list as $e): $p = course_progress((int) $e['user_id'], (int) $e['cid']); ?>
<tr><td><strong><?= e($e['student']) ?></strong><br><span class="muted"><?= e($e['email']) ?></span></td><td><?= e($e['title']) ?></td>
<td><div class="bar"><i style="width:<?= (int) $p['pct'] ?>%"></i></div><span class="muted"><?= (int) $p['pct'] ?>%</span></td><td><?= e(fdate($e['enrolled_at'])) ?></td><td><?= badge($e['status']) ?></td>
<td class="actions"><?= $e['status'] === 'cancelled' ? action_form('restore', ['id' => $e['id']], 'Restore') : action_form('cancel', ['id' => $e['id']], 'Cancel', 'btn sm danger', 'Cancel this enrollment?') ?></td></tr>
<?php endforeach; if (!$list): ?><tr><td colspan="6" class="empty">No enrollments yet.</td></tr><?php endif; ?></table></div>
<?= admin_pager($pg) ?>
<?php admin_footer();
