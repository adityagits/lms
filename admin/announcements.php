<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin.php';
$u = require_login('admin', 'instructor');
if (is_post()) {
    verify_csrf();
    if (input('action') === 'add') {
        $c = own_course(int_in('course'));
        $t = mb_substr((string) input('title'), 0, 200); $b = mb_substr((string) input('body'), 0, 5000);
        if ($t === '' || $b === '') flash('error', 'Title and message are required.');
        else { insert('announcements', ['course_id' => $c['id'], 'author_id' => $u['id'], 'title' => $t, 'body' => $b]); flash('success', 'Announcement posted to enrolled students.'); }
    } elseif (input('action') === 'delete') {
        $a = row('SELECT * FROM announcements WHERE id = ?', [int_in('id')]);
        if ($a) { own_course((int) $a['course_id']); q('DELETE FROM announcements WHERE id = ?', [$a['id']]); flash('success', 'Deleted.'); }
    }
    redirect('admin/announcements.php');
}
admin_header('Announcements', 'announcements.php');
[$scope, $sp] = course_scope('c');
$mine = rows("SELECT c.id, c.title FROM courses c WHERE $scope ORDER BY c.title", $sp);
$list = rows("SELECT a.*, c.title course, us.name author FROM announcements a JOIN courses c ON c.id = a.course_id JOIN users us ON us.id = a.author_id WHERE $scope ORDER BY a.id DESC LIMIT 50", $sp);
?>
<div class="grid2" style="align-items:start">
<form class="f card" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="add"><h2>New announcement</h2>
  <label>Course</label><select name="course"><?php foreach ($mine as $m): ?><option value="<?= (int) $m['id'] ?>"><?= e($m['title']) ?></option><?php endforeach; ?></select>
  <label>Title</label><input type="text" name="title" required maxlength="200"><label>Message</label><textarea name="body" rows="5" required></textarea>
  <p><button class="btn primary" type="submit">Post</button></p></form>
<div class="card"><h2>Recent</h2><?php foreach ($list as $a): ?><div style="border-bottom:1px solid #eee;padding:8px 0"><strong><?= e($a['title']) ?></strong> <span class="muted">· <?= e($a['course']) ?> · <?= e(fdate($a['created_at'])) ?></span><p style="margin:4px 0"><?= nl2br(e($a['body'])) ?></p><?= action_form('delete', ['id' => $a['id']], 'Delete', 'btn sm danger', 'Delete announcement?') ?></div><?php endforeach; if (!$list) echo '<p class="muted">None yet.</p>'; ?></div>
</div>
<?php admin_footer();
