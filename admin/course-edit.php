<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin.php';
$u = require_login('admin', 'instructor');
$admin = $u['role'] === 'admin';
$id = int_in('id');
$c = $id ? own_course($id) : null;
$cats = rows('SELECT id, name FROM categories ORDER BY name');

if (is_post()) {
    verify_csrf();
    $err = null;
    $data = [
        'title' => mb_substr((string) input('title'), 0, 200),
        'short_desc' => mb_substr((string) input('short_desc'), 0, 300),
        'description' => clean_html((string) ($_POST['description'] ?? '')),
        'requirements' => (string) input('requirements'),
        'outcomes' => (string) input('outcomes'),
        'category_id' => int_in('category_id') ?: null,
        'level' => in_array(input('level'), ['beginner', 'intermediate', 'advanced', 'all'], true) ? input('level') : 'all',
        'language' => mb_substr((string) input('language', 'English'), 0, 40),
        'price' => max(0, (float) input('price', 0)),
        'sale_price' => input('sale_price') === '' ? null : max(0, (float) input('sale_price')),
        'preview_video' => mb_substr((string) input('preview_video'), 0, 255),
    ];
    if ($data['title'] === '') $err = 'Title is required.';
    if ($data['preview_video'] !== '' && !preg_match('~^https?://~i', $data['preview_video'])) $err = 'Preview video must be an http(s) link.';
    if ($data['sale_price'] !== null && $data['sale_price'] >= $data['price'] && $data['price'] > 0) $err = 'Sale price must be lower than the regular price.';
    $img = save_image_upload('thumbnail', 'courses', $err);
    if ($img) $data['thumbnail'] = $img;
    if ($err) {
        flash('error', $err);
        redirect('admin/course-edit.php' . ($c ? '?id=' . $c['id'] : ''));
    }
    if ($c) {
        update('courses', $data, 'id = ?', [$c['id']]);
        flash('success', 'Course saved.');
        redirect('admin/course-edit.php?id=' . $c['id']);
    }
    $data['slug'] = unique_slug('courses', $data['title']);
    $data['instructor_id'] = $admin && int_in('instructor_id') ? int_in('instructor_id') : $u['id'];
    $newId = insert('courses', $data);
    flash('success', 'Course created. Now add topics and lessons.');
    redirect('admin/curriculum.php?course=' . $newId);
}

$u = admin_header($c ? 'Edit course' : 'New course', 'courses.php');
$instructors = $admin ? rows("SELECT id, name FROM users WHERE role IN ('instructor','admin') AND status='active' ORDER BY name") : [];
$v = fn($k, $d = '') => e($c[$k] ?? $d);
if ($c): ?><div class="tabs"><a class="active" href="course-edit.php?id=<?= (int) $c['id'] ?>">Course info</a><a href="curriculum.php?course=<?= (int) $c['id'] ?>">Curriculum</a><a href="<?= e(url('course.php?slug=' . $c['slug'])) ?>" target="_blank" rel="noopener">Preview ↗</a></div><?php endif; ?>
<form class="f" method="post" enctype="multipart/form-data"><?= csrf_field() ?>
<div class="grid2" style="align-items:start">
  <div class="card">
    <label>Title</label><input type="text" name="title" value="<?= $v('title') ?>" required maxlength="200">
    <label>Short description</label><input type="text" name="short_desc" value="<?= $v('short_desc') ?>" maxlength="300">
    <label>Description</label><textarea data-editor name="description" rows="10"><?= $v('description') ?></textarea><span class="hint">Basic HTML is allowed (headings, lists, links, images). Scripts are stripped.</span>
    <label>What students will learn</label><textarea name="outcomes" rows="4"><?= $v('outcomes') ?></textarea><span class="hint">One item per line.</span>
    <label>Requirements</label><textarea name="requirements" rows="3"><?= $v('requirements') ?></textarea><span class="hint">One item per line.</span>
  </div>
  <div class="card">
    <div class="row"><div><label>Category</label><select name="category_id"><option value="">— None —</option><?php foreach ($cats as $k): ?><option value="<?= (int) $k['id'] ?>" <?= ($c['category_id'] ?? 0) == $k['id'] ? 'selected' : '' ?>><?= e($k['name']) ?></option><?php endforeach; ?></select></div>
    <div><label>Level</label><select name="level"><?php foreach (['all' => 'All levels', 'beginner' => 'Beginner', 'intermediate' => 'Intermediate', 'advanced' => 'Advanced'] as $k => $l): ?><option value="<?= $k ?>" <?= ($c['level'] ?? 'all') === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div></div>
    <div class="row"><div><label>Price (<?= e(currency()) ?>)</label><input type="number" name="price" step="0.01" min="0" value="<?= $v('price', '0') ?>"><span class="hint">0 = free</span></div>
    <div><label>Sale price</label><input type="number" name="sale_price" step="0.01" min="0" value="<?= $v('sale_price') ?>"></div></div>
    <label>Language</label><input type="text" name="language" value="<?= $v('language', 'English') ?>">
    <label>Intro video (YouTube / Vimeo / .mp4 link)</label><input type="url" name="preview_video" value="<?= $v('preview_video') ?>" placeholder="https://…">
    <label>Thumbnail</label><?php if (!empty($c['thumbnail'])): ?><img src="<?= e(img_url($c['thumbnail'])) ?>" alt="" style="width:200px;border-radius:8px;display:block;margin-bottom:8px"><?php endif; ?>
    <input type="file" name="thumbnail" accept="image/png,image/jpeg,image/gif,image/webp"><span class="hint">JPG/PNG/WebP, max 5 MB. 16:9 looks best.</span>
    <?php if ($admin && !$c): ?><label>Instructor</label><select name="instructor_id"><?php foreach ($instructors as $i): ?><option value="<?= (int) $i['id'] ?>" <?= $i['id'] == $u['id'] ? 'selected' : '' ?>><?= e($i['name']) ?></option><?php endforeach; ?></select><?php endif; ?>
    <?php if ($c): ?><p style="margin-top:16px">Status: <?= badge($c['status']) ?></p><?php endif; ?>
  </div>
</div>
<button class="btn primary" type="submit"><?= $c ? 'Save changes' : 'Create & add curriculum' ?></button>
</form>
<?php admin_footer();
