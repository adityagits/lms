<?php
require __DIR__ . '/includes/bootstrap.php';
$c = find_course('c.slug = ?', [(string) input('slug')]);
$u = current_user();
if (!$c || ($c['status'] !== 'published' && !can_manage_course($c, $u))) render_error(404, 'Course not found.');
$cur = course_curriculum((int) $c['id']);
$enrolled = $u ? is_enrolled((int) $u['id'], (int) $c['id']) : false;
$reviews = rows("SELECT r.*, u.name FROM reviews r JOIN users u ON u.id = r.user_id WHERE r.course_id = ? AND r.status = 'published' ORDER BY r.id DESC LIMIT 20", [$c['id']]);
$eff = course_effective_price($c);
$instructor = row('SELECT id, name, headline, bio, avatar FROM users WHERE id = ?', [$c['instructor_id']]);
public_header($c['title'], ['description' => $c['short_desc']]);
if ($c['status'] !== 'published') echo '<div class="lms-flash"><div class="wb-container">Preview — this course is "' . e($c['status']) . '" and not visible to students.</div></div>';
?>
<section class="wb-section lms-hero" data-bg="dark"><div class="wb-container">
  <?php if ($c['category_name']): ?><a class="wb-eyebrow" href="<?= e(url('courses.php?category=' . $c['category_slug'])) ?>"><?= e($c['category_name']) ?></a><?php endif; ?>
  <h1><?= e($c['title']) ?></h1>
  <p class="wb-lead"><?= e($c['short_desc']) ?></p>
  <p><?= stars((float) $c['rating']) ?> <?= $c['review_count'] ? number_format((float) $c['rating'], 1) . ' (' . (int) $c['review_count'] . ' reviews)' : 'No reviews yet' ?> · <?= (int) $c['student_count'] ?> student<?= (int) $c['student_count'] === 1 ? '' : 's' ?> · <?= e(ucfirst($c['level'])) ?> · <?= e($c['language']) ?></p>
  <p class="wb-small">Created by <a href="<?= e(url('instructor.php?id=' . $c['instructor_id'])) ?>" style="color:inherit"><?= e($c['instructor_name']) ?></a></p>
</div></section>
<section class="wb-section"><div class="wb-container"><div class="lms-detail">
  <div>
    <?php if ($outs = text_lines($c['outcomes'])): ?>
      <div class="wb-card" style="margin-bottom:2rem"><h3>What you'll learn</h3><ul class="lms-outcomes"><?php foreach ($outs as $o): ?><li><?= e($o) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <h2>Course content</h2>
    <p class="wb-muted wb-small"><?= count($cur) ?> topics · <?= (int) $c['lesson_count'] ?> items · <?= e(minutes_label((int) $c['total_minutes'])) ?></p>
    <?php foreach ($cur as $i => $t): ?>
      <details class="lms-topic" <?= $i === 0 ? 'open' : '' ?>><summary><?= e($t['title']) ?></summary><ul>
        <?php foreach ($t['items'] as $it): ?>
          <li><span><?= $it['kind'] === 'quiz' ? '📝' : ($it['video_url'] ? '▶' : '📄') ?> 
            <?php if ($it['is_preview'] && !$enrolled): ?><a href="<?= e(url('learn.php?course=' . $c['slug'] . '&lesson=' . $it['id'])) ?>"><?= e($it['title']) ?></a> <span class="wb-badge">Preview</span><?php else: ?><?= e($it['title']) ?><?php endif; ?></span>
            <span class="wb-muted wb-small"><?= $it['duration_min'] ? e(minutes_label((int) $it['duration_min'])) : '' ?></span></li>
        <?php endforeach; ?>
        <?php if (!$t['items']): ?><li class="wb-muted">No items yet.</li><?php endif; ?>
      </ul></details>
    <?php endforeach; ?>
    <?php if (!$cur): ?><p class="wb-muted">The curriculum is being prepared.</p><?php endif; ?>

    <h2 style="margin-top:2.5rem">Description</h2>
    <div class="lms-prose"><?= clean_html($c['description']) ?></div>
    <?php if ($reqs = text_lines($c['requirements'])): ?><h3>Requirements</h3><ul><?php foreach ($reqs as $r): ?><li><?= e($r) ?></li><?php endforeach; ?></ul><?php endif; ?>

    <h2 style="margin-top:2.5rem">Instructor</h2>
    <div style="display:flex;gap:16px;align-items:flex-start"><img src="<?= e(avatar_url($instructor)) ?>" alt="" style="width:72px;height:72px;border-radius:50%;object-fit:cover">
      <div><h3 style="margin:0"><a href="<?= e(url('instructor.php?id=' . $instructor['id'])) ?>"><?= e($instructor['name']) ?></a></h3><p class="wb-muted" style="margin:0"><?= e($instructor['headline']) ?></p><p><?= nl2br(e($instructor['bio'])) ?></p></div></div>

    <h2 style="margin-top:2.5rem">Reviews</h2>
    <?php foreach ($reviews as $r): ?><div class="lms-review"><strong><?= e($r['name']) ?></strong> <?= stars((float) $r['rating']) ?> <span class="wb-muted wb-small"><?= e(fdate($r['created_at'])) ?></span><p><?= nl2br(e($r['comment'])) ?></p></div><?php endforeach; ?>
    <?php if (!$reviews): ?><p class="wb-muted">No reviews yet.</p><?php endif; ?>
  </div>
  <aside class="wb-card lms-buybox">
    <?php if ($c['preview_video'] && ($emb = video_embed($c['preview_video']))): ?><div class="lms-media"><?= $emb ?></div>
    <?php else: ?><img src="<?= e(img_url($c['thumbnail'])) ?>" alt="" style="width:100%;aspect-ratio:16/9;object-fit:cover"><?php endif; ?>
    <div class="lms-buybox__body">
      <?= course_price_html($c) ?>
      <?php if ($enrolled): ?>
        <a class="wb-btn wb-btn--block" href="<?= e(url('learn.php?course=' . $c['slug'])) ?>">Go to course</a>
      <?php elseif ($u && can_manage_course($c, $u)): ?>
        <a class="wb-btn wb-btn--block" href="<?= e(url('admin/course-edit.php?id=' . $c['id'])) ?>">Edit this course</a>
      <?php elseif ($c['status'] === 'published'): ?>
        <?php if ($eff <= 0): ?>
          <form method="post" action="<?= e(url('checkout.php')) ?>"><?= csrf_field() ?><input type="hidden" name="course" value="<?= (int) $c['id'] ?>"><input type="hidden" name="action" value="enroll"><button class="wb-btn wb-btn--block" type="submit">Enroll for free</button></form>
        <?php else: ?>
          <a class="wb-btn wb-btn--block" href="<?= e(url('checkout.php?course=' . $c['id'])) ?>">Buy now</a>
        <?php endif; ?>
      <?php endif; ?>
      <ul class="lms-list-plain wb-small wb-muted"><li>📚 <?= (int) $c['lesson_count'] ?> lessons & quizzes</li><li>⏱ <?= e(minutes_label((int) $c['total_minutes'])) ?> of content</li><li>🏆 Certificate of completion</li><li>♾ Lifetime access</li></ul>
    </div>
  </aside>
</div></div></section>
<?php public_footer();
