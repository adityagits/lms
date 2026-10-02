<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin.php';
$u = require_login('admin', 'instructor');
$admin = $u['role'] === 'admin';

if (is_post()) {
    verify_csrf();
    $c = own_course(int_in('id'));
    switch (input('action')) {
        case 'publish': case 'reject': case 'feature':
            if (!$admin) render_error(403, 'Admins only.');
            if (input('action') === 'feature') update('courses', ['is_featured' => $c['is_featured'] ? 0 : 1], 'id = ?', [$c['id']]);
            else update('courses', ['status' => input('action') === 'publish' ? 'published' : 'rejected'], 'id = ?', [$c['id']]);
            flash('success', 'Course updated.');
            break;
        case 'submit':
            update('courses', ['status' => $admin ? 'published' : 'pending'], 'id = ?', [$c['id']]);
            flash('success', $admin ? 'Course published.' : 'Submitted for review.');
            break;
        case 'unpublish':
            update('courses', ['status' => 'draft'], 'id = ?', [$c['id']]);
            flash('success', 'Course moved to draft.');
            break;
        case 'delete':
            q('DELETE FROM courses WHERE id = ?', [$c['id']]);
            flash('success', 'Course deleted.');
            break;
    }
    redirect('admin/courses.php');
}

$u = admin_header('Courses', 'courses.php');
[$scope, $sp] = course_scope('c');
$status = input('status'); $qs = input('q');
$where = [$scope]; $p = $sp;
if (in_array($status, ['draft', 'pending', 'published', 'rejected'], true)) { $where[] = 'c.status = ?'; $p[] = $status; }
if ($qs !== '') { $where[] = 'c.title LIKE ?'; $p[] = '%' . addcslashes($qs, '%_\\') . '%'; }
$w = implode(' AND ', $where);
$pg = paginate((int) val("SELECT COUNT(*) FROM courses c WHERE $w", $p), 15, int_in('page', 1));
$list = rows('SELECT ' . COURSE_STATS_SQL . COURSE_FROM_SQL . " WHERE $w ORDER BY c.id DESC LIMIT {$pg['per']} OFFSET {$pg['offset']}", $p);
?>
<form class="filters" method="get">
  <input type="search" name="q" value="<?= e($qs) ?>" placeholder="Search title">
  <select name="status"><option value="">All statuses</option><?php foreach (['published', 'pending', 'draft', 'rejected'] as $s): ?><option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select>
  <button class="btn" type="submit">Filter</button>
  <a class="btn primary" href="course-edit.php">+ New course</a>
</form>
<div class="table-wrap"><table class="t">
<tr><th>Course</th><?php if ($admin): ?><th>Instructor</th><?php endif; ?><th>Price</th><th>Content</th><th>Students</th><th>Status</th><th></th></tr>
<?php foreach ($list as $c): ?>
<tr>
  <td><img class="thumb" src="<?= e(img_url($c['thumbnail'])) ?>" alt=""><a href="course-edit.php?id=<?= (int) $c['id'] ?>"><strong><?= e($c['title']) ?></strong></a><?= $c['is_featured'] ? ' ★' : '' ?><br><span class="muted"><?= e($c['category_name'] ?: 'Uncategorised') ?></span></td>
  <?php if ($admin): ?><td><?= e($c['instructor_name']) ?></td><?php endif; ?>
  <td><?= e(money(course_effective_price($c))) ?></td>
  <td><?= (int) $c['lesson_count'] ?> items</td>
  <td><?= (int) $c['student_count'] ?></td>
  <td><?= badge($c['status']) ?></td>
  <td class="actions">
    <a class="btn sm" href="course-edit.php?id=<?= (int) $c['id'] ?>">Edit</a>
    <a class="btn sm" href="curriculum.php?course=<?= (int) $c['id'] ?>">Curriculum</a>
    <a class="btn sm" href="<?= e(url('course.php?slug=' . $c['slug'])) ?>" target="_blank" rel="noopener">View</a>
    <?php if ($admin && $c['status'] !== 'published') echo action_form('publish', ['id' => $c['id']], 'Publish', 'btn sm ok'); ?>
    <?php if ($admin && $c['status'] === 'pending') echo action_form('reject', ['id' => $c['id']], 'Reject', 'btn sm danger'); ?>
    <?php if ($admin) echo action_form('feature', ['id' => $c['id']], $c['is_featured'] ? 'Unfeature' : 'Feature'); ?>
    <?php if ($c['status'] === 'draft' || $c['status'] === 'rejected') { if (!$admin) echo action_form('submit', ['id' => $c['id']], 'Submit for review', 'btn sm primary'); }
          elseif ($c['status'] === 'published') echo action_form('unpublish', ['id' => $c['id']], 'Unpublish'); ?>
    <?= action_form('delete', ['id' => $c['id']], 'Delete', 'btn sm danger', 'Delete this course and all its lessons, enrollments and reviews?') ?>
  </td>
</tr>
<?php endforeach; if (!$list): ?><tr><td colspan="7" class="empty">No courses found.</td></tr><?php endif; ?>
</table></div>
<?= admin_pager($pg) ?>
<?php admin_footer();
