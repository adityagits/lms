<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin.php';
$u = require_login('admin', 'instructor');
$c = own_course(int_in('course'));
$cid = (int) $c['id'];
$back = 'admin/curriculum.php?course=' . $cid;

if (is_post()) {
    verify_csrf();
    $topic = fn() => row('SELECT * FROM topics WHERE id = ? AND course_id = ?', [int_in('topic'), $cid]);
    $item = fn() => row('SELECT * FROM lessons WHERE id = ? AND course_id = ?', [int_in('item'), $cid]);
    switch (input('action')) {
        case 'add_topic':
            $t = mb_substr((string) input('title'), 0, 200);
            if ($t !== '') insert('topics', ['course_id' => $cid, 'title' => $t, 'position' => next_position('topics', 'course_id', $cid)]);
            break;
        case 'rename_topic':
            if ($t = $topic()) update('topics', ['title' => mb_substr((string) input('title'), 0, 200) ?: $t['title']], 'id = ?', [$t['id']]);
            break;
        case 'delete_topic':
            if ($t = $topic()) q('DELETE FROM topics WHERE id = ?', [$t['id']]);
            break;
        case 'move_topic':
            if ($t = $topic()) move_position('topics', (int) $t['id'], input('dir') === 'up' ? 'up' : 'down', 'course_id', $cid);
            break;
        case 'add_item':
            if ($t = $topic()) {
                $kind = input('kind') === 'quiz' ? 'quiz' : 'lesson';
                $title = mb_substr((string) input('title'), 0, 200) ?: ($kind === 'quiz' ? 'New quiz' : 'New lesson');
                $newId = insert('lessons', ['topic_id' => $t['id'], 'course_id' => $cid, 'kind' => $kind, 'title' => $title, 'position' => next_position('lessons', 'topic_id', $t['id'])]);
                redirect('admin/lesson-edit.php?id=' . $newId);
            }
            break;
        case 'delete_item':
            if ($i = $item()) q('DELETE FROM lessons WHERE id = ?', [$i['id']]);
            break;
        case 'move_item':
            if ($i = $item()) move_position('lessons', (int) $i['id'], input('dir') === 'up' ? 'up' : 'down', 'topic_id', $i['topic_id']);
            break;
    }
    redirect($back);
}

admin_header('Curriculum · ' . $c['title'], 'courses.php');
$cur = course_curriculum($cid);
?>
<div class="tabs"><a href="course-edit.php?id=<?= $cid ?>">Course info</a><a class="active" href="curriculum.php?course=<?= $cid ?>">Curriculum</a><a href="<?= e(url('course.php?slug=' . $c['slug'])) ?>" target="_blank" rel="noopener">Preview ↗</a></div>
<?php foreach ($cur as $t): ?>
<div class="topic">
  <div class="topic-h">
    <form method="post" style="display:flex;gap:6px;flex:1"><?= csrf_field() ?><input type="hidden" name="action" value="rename_topic"><input type="hidden" name="topic" value="<?= (int) $t['id'] ?>">
      <input type="text" name="title" value="<?= e($t['title']) ?>" style="flex:1;padding:6px 10px;border:1px solid #d1d5db;border-radius:8px;font-weight:600"><button class="btn sm" type="submit">Rename</button></form>
    <?= action_form('move_topic', ['topic' => $t['id'], 'dir' => 'up'], '↑') ?><?= action_form('move_topic', ['topic' => $t['id'], 'dir' => 'down'], '↓') ?>
    <?= action_form('delete_topic', ['topic' => $t['id']], 'Delete', 'btn sm danger', 'Delete this topic and everything in it?') ?>
  </div>
  <?php foreach ($t['items'] as $it): ?>
    <div class="item"><span class="ico"><?= $it['kind'] === 'quiz' ? '📝' : ($it['video_url'] ? '▶' : '📄') ?></span>
      <span class="t"><a href="lesson-edit.php?id=<?= (int) $it['id'] ?>"><?= e($it['title']) ?></a> <?= $it['is_preview'] ? '<span class="badge">Preview</span>' : '' ?> <span class="muted"><?= $it['kind'] === 'quiz' ? 'Quiz' : 'Lesson' ?><?= $it['duration_min'] ? ' · ' . (int) $it['duration_min'] . ' min' : '' ?></span></span>
      <div class="actions"><a class="btn sm" href="lesson-edit.php?id=<?= (int) $it['id'] ?>">Edit</a>
      <?= action_form('move_item', ['item' => $it['id'], 'dir' => 'up'], '↑') ?><?= action_form('move_item', ['item' => $it['id'], 'dir' => 'down'], '↓') ?>
      <?= action_form('delete_item', ['item' => $it['id']], 'Delete', 'btn sm danger', 'Delete this item?') ?></div></div>
  <?php endforeach; ?>
  <form class="inline-add" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="add_item"><input type="hidden" name="topic" value="<?= (int) $t['id'] ?>">
    <input type="text" name="title" placeholder="New item title" maxlength="200">
    <select name="kind" style="padding:7px;border:1px solid #d1d5db;border-radius:8px"><option value="lesson">Lesson</option><option value="quiz">Quiz</option></select>
    <button class="btn sm primary" type="submit">+ Add</button></form>
</div>
<?php endforeach; ?>
<?php if (!$cur): ?><div class="card empty">No topics yet. Add your first topic below.</div><?php endif; ?>
<form class="card" method="post" style="display:flex;gap:8px"><?= csrf_field() ?><input type="hidden" name="action" value="add_topic">
  <input type="text" name="title" placeholder="New topic title (e.g. Getting started)" required maxlength="200" style="flex:1;padding:9px 11px;border:1px solid #d1d5db;border-radius:8px"><button class="btn primary" type="submit">+ Add topic</button></form>
<?php admin_footer();
