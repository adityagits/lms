<?php
require __DIR__ . '/includes/bootstrap.php';
$u = row("SELECT * FROM users WHERE id = ? AND role = 'instructor' AND status = 'active'", [int_in('id')]);
if (!$u) render_error(404, 'Instructor not found.');
$res = published_courses(['instructor' => $u['id'], 'limit' => 24]);
$students = (int) val("SELECT COUNT(*) FROM enrollments e JOIN courses c ON c.id = e.course_id WHERE c.instructor_id = ? AND e.status <> 'cancelled'", [$u['id']]);
public_header($u['name']);
?>
<section class="wb-section" data-bg="alt"><div class="wb-container" style="display:flex;gap:28px;align-items:center;flex-wrap:wrap">
  <img src="<?= e(avatar_url($u)) ?>" alt="" style="width:120px;height:120px;border-radius:50%;object-fit:cover">
  <div><h1 style="margin:0"><?= e($u['name']) ?></h1><p class="wb-lead" style="margin:.2em 0"><?= e($u['headline']) ?></p>
  <p class="wb-muted"><?= count($res['courses']) ?> courses · <?= $students ?> students</p></div>
</div></section>
<section class="wb-section"><div class="wb-container">
  <?php if ($u['bio']): ?><div class="lms-prose" style="margin-bottom:2rem"><h2>About</h2><p><?= nl2br(e($u['bio'])) ?></p></div><?php endif; ?>
  <h2>Courses by <?= e($u['name']) ?></h2>
  <div class="wb-grid lms-grid"><?php foreach ($res['courses'] as $c) echo course_card($c); ?></div>
</div></section>
<?php public_footer();
