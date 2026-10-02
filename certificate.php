<?php
require __DIR__ . '/includes/bootstrap.php';
$code = preg_replace('/[^A-Z0-9]/', '', strtoupper((string) input('code')));
$r = $code === '' ? null : row("SELECT e.*, u.name AS student, c.title, c.id AS cid, i.name AS instructor FROM enrollments e JOIN users u ON u.id = e.user_id JOIN courses c ON c.id = e.course_id JOIN users i ON i.id = c.instructor_id WHERE e.certificate_code = ? AND e.status = 'completed'", [$code]);
if (!$r) render_error(404, 'Certificate not found. Check the code and try again.');
$mins = (int) val('SELECT COALESCE(SUM(duration_min),0) FROM lessons WHERE course_id = ?', [$r['cid']]);
public_header('Certificate · ' . $r['title']);
?>
<div class="wb-container">
  <div class="lms-cert">
    <p class="wb-eyebrow"><?= e(setting('site_name', 'LMS')) ?></p>
    <h1>Certificate of Completion</h1>
    <p>This certifies that</p>
    <div class="name"><?= e($r['student']) ?></div>
    <p>has successfully completed the course</p>
    <h2><?= e($r['title']) ?></h2>
    <?php if ($mins): ?><p class="wb-muted"><?= e(minutes_label($mins)) ?> of learning content</p><?php endif; ?>
    <p style="margin-top:2rem">Completed on <?= e(fdate($r['completed_at'], 'F j, Y')) ?> · Instructor: <?= e($r['instructor']) ?></p>
    <p class="wb-small wb-muted">Certificate ID: <?= e($r['certificate_code']) ?> — verify at <?= e(url('certificate.php?code=' . $r['certificate_code'])) ?></p>
  </div>
  <p class="no-print" style="text-align:center"><button class="wb-btn" onclick="window.print()">Print / save as PDF</button></p>
</div>
<?php public_footer();
