<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin.php';
$u = require_login('admin', 'instructor');
$admin = $u['role'] === 'admin';
if (is_post()) {
    verify_csrf();
    if (!$admin) render_error(403, 'Admins only.');
    $o = row('SELECT * FROM orders WHERE id = ?', [int_in('id')]);
    if ($o) {
        switch (input('action')) {
            case 'paid':
                update('orders', ['status' => 'paid'], 'id = ?', [$o['id']]);
                enroll_user((int) $o['user_id'], (int) $o['course_id'], (int) $o['id']);
                flash('success', 'Order marked as paid and student enrolled.');
                break;
            case 'refund':
                update('orders', ['status' => 'refunded'], 'id = ?', [$o['id']]);
                update('enrollments', ['status' => 'cancelled'], 'order_id = ?', [$o['id']]);
                flash('success', 'Order refunded and enrollment cancelled.');
                break;
        }
    }
    redirect('admin/orders.php');
}
admin_header('Orders & earnings', 'orders.php');
[$scope, $sp] = course_scope('c');
$commission = (float) setting('commission_pct', 20) / 100;
$gross = (float) val("SELECT COALESCE(SUM(o.amount),0) FROM orders o JOIN courses c ON c.id = o.course_id WHERE o.status='paid' AND $scope", $sp);
$pend = (float) val("SELECT COALESCE(SUM(o.amount),0) FROM orders o JOIN courses c ON c.id = o.course_id WHERE o.status='pending' AND $scope", $sp);
$pg = paginate((int) val("SELECT COUNT(*) FROM orders o JOIN courses c ON c.id = o.course_id WHERE $scope", $sp), 20, int_in('page', 1));
$list = rows("SELECT o.*, us.name, c.title FROM orders o JOIN users us ON us.id = o.user_id JOIN courses c ON c.id = o.course_id WHERE $scope ORDER BY o.id DESC LIMIT {$pg['per']} OFFSET {$pg['offset']}", $sp);
?>
<div class="stats">
  <div class="stat"><b><?= e(money_plain($gross)) ?></b><span>Gross sales</span></div>
  <?php if ($admin): ?><div class="stat"><b><?= e(money_plain($gross * $commission)) ?></b><span>Platform fee (<?= e(rtrim(rtrim(number_format($commission * 100, 2), '0'), '.')) ?>%)</span></div>
  <div class="stat"><b><?= e(money_plain($gross * (1 - $commission))) ?></b><span>Instructor payouts</span></div>
  <?php else: ?><div class="stat"><b><?= e(money_plain($gross * (1 - $commission))) ?></b><span>Your earnings (after <?= e(rtrim(rtrim(number_format($commission * 100, 2), '0'), '.')) ?>% fee)</span></div><?php endif; ?>
  <div class="stat"><b><?= e(money_plain($pend)) ?></b><span>Pending</span></div>
</div>
<div class="table-wrap"><table class="t"><tr><th>#</th><th>Student</th><th>Course</th><th>Amount</th><th>Method</th><th>Date</th><th>Status</th><?php if ($admin): ?><th></th><?php endif; ?></tr>
<?php foreach ($list as $o): ?><tr><td><?= (int) $o['id'] ?></td><td><?= e($o['name']) ?></td><td><?= e($o['title']) ?></td><td><?= e(money_plain($o['amount'])) ?><?= $o['discount'] > 0 ? '<br><span class="muted">−' . e(money_plain($o['discount'])) . ' coupon</span>' : '' ?></td><td><?= e($o['method']) ?></td><td><?= e(fdate($o['created_at'])) ?></td><td><?= badge($o['status']) ?></td>
<?php if ($admin): ?><td class="actions"><?= $o['status'] === 'pending' ? action_form('paid', ['id' => $o['id']], 'Mark paid', 'btn sm ok') : '' ?><?= $o['status'] === 'paid' && $o['amount'] > 0 ? action_form('refund', ['id' => $o['id']], 'Refund', 'btn sm danger', 'Refund this order and cancel the enrollment?') : '' ?></td><?php endif; ?></tr>
<?php endforeach; if (!$list): ?><tr><td colspan="8" class="empty">No orders yet.</td></tr><?php endif; ?></table></div>
<?= admin_pager($pg) ?>
<?php admin_footer();
