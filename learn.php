<?php
require __DIR__ . '/includes/bootstrap.php';
$c = find_course('c.slug = ?', [(string) input('course')]);
$u = current_user();
if (!$c || ($c['status'] !== 'published' && !can_manage_course($c, $u))) render_error(404, 'Course not found.');
$cid = (int) $c['id'];
$enrolled = $u ? is_enrolled((int) $u['id'], $cid) : false;
$manager = $u && can_manage_course($c, $u);
$cur = course_curriculum($cid);
$flat = flat_lessons($cur);
$lessonId = int_in('lesson');
$lesson = null;
foreach ($flat as $l) if ((int) $l['id'] === $lessonId) $lesson = $l;
if ($lessonId && !$lesson) render_error(404, 'Lesson not found.');
$self = 'learn.php?course=' . $c['slug'];

// access control: enrolled students, course managers, or free-preview lessons
$canView = $enrolled || $manager || ($lesson && $lesson['is_preview']);
if (!$canView) {
    if (!$u) { flash('info', 'Sign in to access this course.'); redirect('login.php?next=' . rawurlencode(url($self))); }
    flash('info', 'Enroll in this course to access the lessons.');
    redirect('course.php?slug=' . $c['slug']);
}
$track = $enrolled && $u;   // progress is tracked for enrolled students only

if (is_post()) {
    verify_csrf();
    if (!$track) redirect($self);
    $action = input('action');
    if ($action === 'complete' && $lesson && $lesson['kind'] === 'lesson') {
        mark_lesson_complete((int) $u['id'], $lesson);
        $idx = array_search($lesson['id'], array_column($flat, 'id'));
        $next = $flat[$idx + 1] ?? null;
        redirect($next ? $self . '&lesson=' . $next['id'] : $self);
    }
    if ($action === 'quiz' && $lesson && $lesson['kind'] === 'quiz') {
        $res = grade_quiz((int) $lesson['id'], (array) ($_POST['a'] ?? []));
        $passed = $res['total'] > 0 && $res['pct'] >= (int) $lesson['pass_mark'];
        insert('quiz_attempts', ['user_id' => $u['id'], 'lesson_id' => $lesson['id'], 'score_pct' => $res['pct'], 'passed' => (int) $passed]);
        if ($passed) mark_lesson_complete((int) $u['id'], $lesson);
        redirect($self . '&lesson=' . $lesson['id']);
    }
    if ($action === 'review') {
        $rating = max(1, min(5, int_in('rating', 5)));
        q('INSERT INTO reviews (course_id, user_id, rating, comment) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment)', [$cid, $u['id'], $rating, mb_substr((string) input('comment'), 0, 2000)]);
        flash('success', 'Thanks for your review!');
        redirect($self);
    }
    redirect($self);
}

$done = $track ? array_column(rows('SELECT lesson_id FROM lesson_progress WHERE user_id = ? AND course_id = ?', [$u['id'], $cid]), 'lesson_id') : [];
$done = array_flip($done);
$prog = $track ? course_progress((int) $u['id'], $cid) : ['done' => 0, 'total' => count($flat), 'pct' => 0];
$idx = $lesson ? array_search($lesson['id'], array_column($flat, 'id')) : -1;
$prev = $idx > 0 ? $flat[$idx - 1] : null;
$next = $lesson && isset($flat[$idx + 1]) ? $flat[$idx + 1] : null;
public_header($lesson ? $lesson['title'] . ' · ' . $c['title'] : $c['title']);
?>
<div class="lms-learn">
  <aside class="lms-learn__side">
    <h2><a href="<?= e(url($self)) ?>" style="color:inherit;text-decoration:none"><?= e($c['title']) ?></a></h2>
    <?php if ($track): ?><div class="lms-progress"><span style="width:<?= (int) $prog['pct'] ?>%"></span></div><p class="wb-small wb-muted" style="margin:0 18px 8px"><?= (int) $prog['pct'] ?>% complete</p><?php endif; ?>
    <?php foreach ($cur as $t): ?>
      <div class="lms-learn__topic"><?= e($t['title']) ?></div>
      <?php foreach ($t['items'] as $it): $accessible = $enrolled || $manager || $it['is_preview']; ?>
        <?php if ($accessible): ?><a class="lms-learn__item <?= $lesson && $lesson['id'] == $it['id'] ? 'is-current' : '' ?>" href="<?= e(url($self . '&lesson=' . $it['id'])) ?>">
        <?php else: ?><span class="lms-learn__item" style="opacity:.55"><?php endif; ?>
          <span class="lms-check <?= isset($done[$it['id']]) ? 'is-done' : '' ?>"><?= isset($done[$it['id']]) ? '✓' : '' ?></span>
          <span><?= $it['kind'] === 'quiz' ? '📝 ' : '' ?><?= e($it['title']) ?></span>
        <?= $accessible ? '</a>' : '</span>' ?>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </aside>
  <section class="lms-learn__main">
  <?php if (!$lesson): /* ------------- overview ------------- */
      $en = $track ? row('SELECT * FROM enrollments WHERE user_id = ? AND course_id = ?', [$u['id'], $cid]) : null;
      $anns = rows('SELECT * FROM announcements WHERE course_id = ? ORDER BY id DESC LIMIT 10', [$cid]);
      $myReview = $track ? row('SELECT * FROM reviews WHERE user_id = ? AND course_id = ?', [$u['id'], $cid]) : null;
      $first = null; foreach ($flat as $l) { if (!isset($done[$l['id']])) { $first = $l; break; } } $first ??= $flat[0] ?? null; ?>
    <h1 style="font-size:2rem"><?= e($c['title']) ?></h1>
    <?php if ($en && $en['status'] === 'completed'): ?>
      <div class="lms-result lms-result--pass">🎉 You completed this course. <a href="<?= e(url('certificate.php?code=' . $en['certificate_code'])) ?>">View your certificate →</a></div>
    <?php endif; ?>
    <?php if ($first): ?><p><a class="wb-btn" href="<?= e(url($self . '&lesson=' . $first['id'])) ?>"><?= $prog['done'] ? 'Continue learning' : 'Start course' ?></a></p><?php else: ?><p class="wb-muted">This course has no lessons yet.</p><?php endif; ?>
    <div class="lms-prose"><?= clean_html($c['description']) ?></div>
    <h3 style="margin-top:2rem">Announcements</h3>
    <?php foreach ($anns as $a): ?><div class="lms-ann"><strong><?= e($a['title']) ?></strong> <span class="wb-muted wb-small">· <?= e(fdate($a['created_at'])) ?></span><br><?= nl2br(e($a['body'])) ?></div><?php endforeach; ?>
    <?php if (!$anns): ?><p class="wb-muted">No announcements yet.</p><?php endif; ?>
    <?php if ($track): ?>
    <h3 style="margin-top:2rem">Rate this course</h3>
    <form class="lms-form" method="post" style="max-width:520px"><?= csrf_field() ?><input type="hidden" name="action" value="review">
      <label>Rating<select name="rating"><?php for ($i = 5; $i >= 1; $i--): ?><option value="<?= $i ?>" <?= ($myReview['rating'] ?? 5) == $i ? 'selected' : '' ?>><?= str_repeat('★', $i) ?></option><?php endfor; ?></select></label>
      <label>Your review<textarea name="comment" rows="4" maxlength="2000"><?= e($myReview['comment'] ?? '') ?></textarea></label>
      <button class="wb-btn" type="submit"><?= $myReview ? 'Update review' : 'Submit review' ?></button></form>
    <?php endif; ?>
  <?php elseif ($lesson['kind'] === 'quiz'): /* ------------- quiz ------------- */
      $qs = rows('SELECT * FROM quiz_questions WHERE lesson_id = ? ORDER BY position, id', [$lesson['id']]);
      $att = $track ? rows('SELECT * FROM quiz_attempts WHERE user_id = ? AND lesson_id = ? ORDER BY id DESC LIMIT 5', [$u['id'], $lesson['id']]) : []; ?>
    <h1 style="font-size:1.8rem">📝 <?= e($lesson['title']) ?></h1>
    <p class="wb-muted">Pass mark: <?= (int) $lesson['pass_mark'] ?>% · <?= count($qs) ?> questions</p>
    <?php if ($att): $a = $att[0]; ?><div class="lms-result lms-result--<?= $a['passed'] ? 'pass' : 'fail' ?>"><?= $a['passed'] ? '✅ Passed' : '❌ Not passed' ?> — last score <?= (int) $a['score_pct'] ?>%<?= $a['passed'] ? '' : '. Review the material and try again.' ?></div><?php endif; ?>
    <?php if (!$qs): ?><p class="wb-muted">This quiz has no questions yet.</p>
    <?php else: ?>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="quiz">
      <?php foreach ($qs as $n => $qq): $opts = rows('SELECT id, label FROM quiz_options WHERE question_id = ? ORDER BY id', [$qq['id']]); ?>
        <fieldset class="lms-quiz-q" style="margin-inline:0"><legend><strong><?= $n + 1 ?>. <?= e($qq['question']) ?></strong> <span class="wb-muted wb-small"><?= $qq['type'] === 'multiple' ? '(select all that apply)' : '' ?></span></legend>
          <?php foreach ($opts as $o): ?><label><input type="<?= $qq['type'] === 'multiple' ? 'checkbox' : 'radio' ?>" name="a[<?= (int) $qq['id'] ?>][]" value="<?= (int) $o['id'] ?>"> <?= e($o['label']) ?></label><?php endforeach; ?>
        </fieldset>
      <?php endforeach; ?>
      <?php if ($track): ?><button class="wb-btn" type="submit">Submit answers</button><?php else: ?><p class="wb-muted">Enroll to take this quiz.</p><?php endif; ?>
    </form>
    <?php endif; ?>
  <?php else: /* ------------- lesson ------------- */ ?>
    <h1 style="font-size:1.8rem"><?= e($lesson['title']) ?></h1>
    <?php if ($lesson['video_url'] && ($emb = video_embed($lesson['video_url']))): ?><div class="lms-media" style="margin-bottom:1.5rem"><?= $emb ?></div><?php endif; ?>
    <div class="lms-prose"><?= clean_html($lesson['content']) ?></div>
    <?php if ($track): ?>
      <?php if (isset($done[$lesson['id']])): ?><p class="lms-result lms-result--pass" style="margin-top:1.5rem">✓ Completed</p>
      <?php else: ?><form method="post" style="margin-top:1.5rem"><?= csrf_field() ?><input type="hidden" name="action" value="complete"><button class="wb-btn" type="submit"><?= $next ? 'Mark complete & continue' : 'Mark complete' ?></button></form><?php endif; ?>
    <?php endif; ?>
  <?php endif; ?>
  <?php if ($lesson): ?>
    <div class="lms-learn__nav">
      <?= $prev && ($enrolled || $manager || $prev['is_preview']) ? '<a class="wb-btn wb-btn--outline" href="' . e(url($self . '&lesson=' . $prev['id'])) . '">← ' . e($prev['title']) . '</a>' : '<span></span>' ?>
      <?= $next && ($enrolled || $manager || $next['is_preview']) ? '<a class="wb-btn wb-btn--outline" href="' . e(url($self . '&lesson=' . $next['id'])) . '">' . e($next['title']) . ' →</a>' : '' ?>
    </div>
  <?php endif; ?>
  </section>
</div>
<?php public_footer();
