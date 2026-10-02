<?php
require __DIR__ . '/includes/bootstrap.php';
$u = require_login();
$list = enrolled_courses((int) $u['id']);
$pending = rows("SELECT o.*, c.title, c.slug FROM orders o JOIN courses c ON c.id = o.course_id WHERE o.user_id = ? AND o.status = 'pending' ORDER BY o.id DESC", [$u['id']]);
$orders = rows("SELECT o.*, c.title FROM orders o JOIN courses c ON c.id = o.course_id WHERE o.user_id = ? ORDER BY o.id DESC LIMIT 20", [$u['id']]);
$tab = input('tab', 'courses');
$ann = rows("SELECT a.*, c.title AS course_title FROM announcements a JOIN courses c ON c.id = a.course_id JOIN enrollments e ON e.course_id = c.id AND e.user_id = ? ORDER BY a.id DESC LIMIT 5", [$u['id']]);
public_header('My learning');
?>
<section class="wb-section" data-bg="alt" data-pad="sm"><div class="wb-container"><h1 style="margin:0">My learning</h1><p class="wb-muted" style="margin:.3em 0 0">Welcome back, <?= e($u['name']) ?>.</p></div></section>
<section class="wb-section" data-pad="sm"><div class="wb-container">
  <nav class="lms-tabs"><a class="<?= $tab === 'courses' ? 'active' : '' ?>" href="?tab=courses">My courses</a><a class="<?= $tab === 'orders' ? 'active' : '' ?>" href="?tab=orders">Orders</a></nav>
  <?php if ($tab === 'orders'): ?>
    <table class="lms-table"><tr><th>#</th><th>Course</th><th>Date</th><th>Amount</th><th>Status</th></tr>
    <?php foreach ($orders as $o): ?><tr><td><?= (int) $o['id'] ?></td><td><?= e($o['title']) ?></td><td><?= e(fdate($o['created_at'])) ?></td><td><?= e(money_plain($o['amount'])) ?></td><td><?= e(ucfirst($o['status'])) ?></td></tr><?php endforeach; ?>
    <?php if (!$orders): ?><tr><td colspan="5" class="wb-muted">No orders yet.</td></tr><?php endif; ?></table>
  <?php else: ?>
    <?php foreach ($pending as $p): ?><div class="lms-ann">Order #<?= (int) $p['id'] ?> for <strong><?= e($p['title']) ?></strong> is awaiting payment confirmation.</div><?php endforeach; ?>
    <?php if ($ann): ?><h3>Latest announcements</h3><?php foreach ($ann as $a): ?><div class="lms-ann"><strong><?= e($a['title']) ?></strong> <span class="wb-muted wb-small">· <?= e($a['course_title']) ?> · <?= e(fdate($a['created_at'])) ?></span><br><?= nl2br(e($a['body'])) ?></div><?php endforeach; endif; ?>
    <h3>Courses</h3>
    <?php if (!$list): ?><p class="wb-muted">You haven't enrolled in any course yet. <a href="<?= e(url('courses.php')) ?>">Browse courses →</a></p>
    <?php else: ?><div class="wb-grid lms-grid"><?php foreach ($list as $c): echo course_card($c, course_progress((int) $u['id'], (int) $c['id'])); endforeach; ?></div>
      <?php $certs = array_filter($list, fn($c) => $c['certificate_code']); if ($certs): ?><h3 style="margin-top:2rem">Certificates</h3><ul><?php foreach ($certs as $c): ?><li><a href="<?= e(url('certificate.php?code=' . $c['certificate_code'])) ?>"><?= e($c['title']) ?></a></li><?php endforeach; ?></ul><?php endif; ?>
    <?php endif; ?>
  <?php endif; ?>
</div></section>
<?php public_footer();
