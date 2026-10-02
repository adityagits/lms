<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin.php';
$u = require_login('admin', 'instructor');
$l = row('SELECT * FROM lessons WHERE id = ?', [int_in('id')]);
if (!$l) render_error(404, 'Item not found.');
$c = own_course((int) $l['course_id']);
$self = 'admin/lesson-edit.php?id=' . $l['id'];

if (is_post()) {
    verify_csrf();
    switch (input('action')) {
        case 'save':
            $vid = (string) input('video_url');
            if ($vid !== '' && !preg_match('~^(https?://|/)~i', $vid)) { flash('error', 'Video must be an http(s) link.'); redirect($self); }
            update('lessons', [
                'title' => mb_substr((string) input('title'), 0, 200) ?: $l['title'],
                'content' => clean_html((string) ($_POST['content'] ?? '')),
                'video_url' => mb_substr($vid, 0, 500),
                'duration_min' => max(0, int_in('duration_min')),
                'is_preview' => !empty($_POST['is_preview']) ? 1 : 0,
                'pass_mark' => max(1, min(100, int_in('pass_mark', 70))),
            ], 'id = ?', [$l['id']]);
            flash('success', 'Saved.');
            break;
        case 'add_question':
            $text = (string) input('question');
            $opts = array_values(array_filter(array_map('trim', (array) ($_POST['opt'] ?? [])), fn($o) => $o !== ''));
            $rawOpts = (array) ($_POST['opt'] ?? []); $correct = array_map('intval', (array) ($_POST['correct'] ?? []));
            if ($text === '' || count($opts) < 2) { flash('error', 'A question needs text and at least two answer options.'); break; }
            $isCorrect = [];
            foreach ($rawOpts as $i => $o) if (trim($o) !== '') $isCorrect[] = in_array((int) $i, $correct, true) ? 1 : 0;
            if (!array_sum($isCorrect)) { flash('error', 'Mark at least one correct answer.'); break; }
            $type = input('type') === 'multiple' || array_sum($isCorrect) > 1 ? 'multiple' : 'single';
            $qid = insert('quiz_questions', ['lesson_id' => $l['id'], 'question' => $text, 'type' => $type, 'position' => next_position('quiz_questions', 'lesson_id', $l['id'])]);
            foreach ($opts as $i => $o) insert('quiz_options', ['question_id' => $qid, 'label' => mb_substr($o, 0, 500), 'is_correct' => $isCorrect[$i]]);
            flash('success', 'Question added.');
            break;
        case 'delete_question':
            q('DELETE FROM quiz_questions WHERE id = ? AND lesson_id = ?', [int_in('qid'), $l['id']]);
            break;
    }
    redirect($self);
}

admin_header(($l['kind'] === 'quiz' ? 'Edit quiz' : 'Edit lesson'), 'courses.php');
$questions = $l['kind'] === 'quiz' ? rows('SELECT * FROM quiz_questions WHERE lesson_id = ? ORDER BY position, id', [$l['id']]) : [];
?>
<p><a href="curriculum.php?course=<?= (int) $c['id'] ?>">← Back to curriculum of <strong><?= e($c['title']) ?></strong></a></p>
<form class="f card" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="save">
  <label>Title</label><input type="text" name="title" value="<?= e($l['title']) ?>" required maxlength="200">
  <?php if ($l['kind'] === 'lesson'): ?>
    <label>Video link (YouTube, Vimeo or .mp4) — optional</label><input type="text" name="video_url" value="<?= e($l['video_url']) ?>" placeholder="https://www.youtube.com/watch?v=…">
    <label>Lesson content</label><textarea data-editor name="content" rows="12"><?= e($l['content']) ?></textarea>
    <div class="row"><div><label>Duration (minutes)</label><input type="number" name="duration_min" min="0" value="<?= (int) $l['duration_min'] ?>"></div>
    <div><label>&nbsp;</label><label class="check"><input type="checkbox" name="is_preview" value="1" <?= $l['is_preview'] ? 'checked' : '' ?>> Free preview (visible without enrolling)</label></div></div>
  <?php else: ?>
    <div class="row"><div><label>Pass mark (%)</label><input type="number" name="pass_mark" min="1" max="100" value="<?= (int) $l['pass_mark'] ?>"></div>
    <div><label>Estimated time (minutes)</label><input type="number" name="duration_min" min="0" value="<?= (int) $l['duration_min'] ?>"></div></div>
    <label>Instructions (optional)</label><textarea name="content" rows="3"><?= e($l['content']) ?></textarea>
  <?php endif; ?>
  <p><button class="btn primary" type="submit">Save</button></p>
</form>
<?php if ($l['kind'] === 'quiz'): ?>
<div class="card"><h2>Questions (<?= count($questions) ?>)</h2>
<?php foreach ($questions as $n => $qq): $opts = rows('SELECT * FROM quiz_options WHERE question_id = ? ORDER BY id', [$qq['id']]); ?>
  <div style="border-bottom:1px solid #eee;padding:10px 0"><div style="display:flex;justify-content:space-between;gap:10px"><strong><?= $n + 1 ?>. <?= e($qq['question']) ?></strong>
    <?= action_form('delete_question', ['qid' => $qq['id']], 'Delete', 'btn sm danger', 'Delete this question?') ?></div>
    <ul style="margin:6px 0"><?php foreach ($opts as $o): ?><li><?= $o['is_correct'] ? '<strong style="color:#16a34a">✓ ' . e($o['label']) . '</strong>' : e($o['label']) ?></li><?php endforeach; ?></ul></div>
<?php endforeach; if (!$questions) echo '<p class="muted">No questions yet.</p>'; ?>
</div>
<form class="f card" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="add_question"><h2>Add a question</h2>
  <label>Question</label><input type="text" name="question" required maxlength="500">
  <label>Answer options <span class="hint">— tick the correct answer(s). Leave unused rows empty.</span></label>
  <?php for ($i = 0; $i < 6; $i++): ?><div class="q-opt"><input type="checkbox" name="correct[]" value="<?= $i ?>" title="Correct answer"><input type="text" name="opt[<?= $i ?>]" placeholder="Option <?= $i + 1 ?>" maxlength="500"></div><?php endfor; ?>
  <p class="hint">One ticked answer = single choice; several ticked = "select all that apply".</p>
  <button class="btn primary" type="submit">Add question</button></form>
<?php endif; ?>
<?php admin_footer();
