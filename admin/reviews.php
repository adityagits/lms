<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin.php';
$u = require_login('admin', 'instructor');
$admin = $u['role'] === 'admin';
if (is_post()) {
    verify_csrf();
    $r = row('SELECT * FROM reviews WHERE id = ?', [int_in('id')]);
    if ($r) {
        own_course((int) $r['course_id']);
        match (input('action')) {
            'hide' => update('reviews', ['status' => 'hidden'], 'id = ?', [$r['id']]),
            'show' => update('reviews', ['status' => 'published'], 'id = ?', [$r['id']]),
            'delete' => q('DELETE FROM reviews WHERE id = ?', [$r['id']]),
            default => null,
        };
        flash('success', 'Review updated.');
    }
    redirect('admin/reviews.php');
}
admin_header('Reviews', 'reviews.php');
[$scope, $sp] = course_scope('c');
$pg = paginate((int) val("SELECT COUNT(*) FROM reviews r JOIN courses c ON c.id = r.course_id WHERE $scope", $sp), 20, int_in('page', 1));
$list = rows("SELECT r.*, us.name, c.title FROM reviews r JOIN users us ON us.id = r.user_id JOIN courses c ON c.id = r.course_id WHERE $scope ORDER BY r.id DESC LIMIT {$pg['per']} OFFSET {$pg['offset']}", $sp);
?>
<div class="table-wrap"><table class="t"><tr><th>Student</th><th>Course</th><th>Rating</th><th>Review</th><th>Date</th><th>Status</th><th></th></tr>
<?php foreach ($list as $r): ?><tr><td><?= e($r['name']) ?></td><td><?= e($r['title']) ?></td><td><?= stars((float) $r['rating']) ?></td><td style="max-width:320px"><?= e(mb_strimwidth((string) $r['comment'], 0, 160, '…')) ?></td><td><?= e(fdate($r['created_at'])) ?></td><td><?= badge($r['status']) ?></td>
<td class="actions"><?= $r['status'] === 'published' ? action_form('hide', ['id' => $r['id']], 'Hide') : action_form('show', ['id' => $r['id']], 'Show') ?><?= action_form('delete', ['id' => $r['id']], 'Delete', 'btn sm danger', 'Delete this review?') ?></td></tr>
<?php endforeach; if (!$list): ?><tr><td colspan="7" class="empty">No reviews yet.</td></tr><?php endif; ?></table></div>
<?= admin_pager($pg) ?>
<?php admin_footer();
