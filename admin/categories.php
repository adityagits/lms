<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin.php';
require_login('admin');
if (is_post()) {
    verify_csrf();
    $name = mb_substr((string) input('name'), 0, 120);
    switch (input('action')) {
        case 'add':
            if ($name !== '') { insert('categories', ['name' => $name, 'slug' => unique_slug('categories', $name), 'description' => mb_substr((string) input('description'), 0, 255)]); flash('success', 'Category added.'); }
            break;
        case 'edit':
            if ($name !== '') { update('categories', ['name' => $name, 'description' => mb_substr((string) input('description'), 0, 255)], 'id = ?', [int_in('id')]); flash('success', 'Category updated.'); }
            break;
        case 'delete':
            q('DELETE FROM categories WHERE id = ?', [int_in('id')]); flash('success', 'Category deleted.');
            break;
    }
    redirect('admin/categories.php');
}
admin_header('Categories', 'categories.php');
$cats = rows('SELECT cat.*, (SELECT COUNT(*) FROM courses c WHERE c.category_id = cat.id) n FROM categories cat ORDER BY name');
?>
<div class="grid2" style="align-items:start">
<div class="table-wrap"><table class="t"><tr><th>Name</th><th>Courses</th><th></th></tr>
<?php foreach ($cats as $c): ?><tr><td colspan="3"><form method="post" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap"><?= csrf_field() ?><input type="hidden" name="action" value="edit"><input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
  <input type="text" name="name" value="<?= e($c['name']) ?>" style="padding:6px 9px;border:1px solid #d1d5db;border-radius:8px"><input type="text" name="description" value="<?= e($c['description']) ?>" placeholder="Description" style="flex:1;min-width:120px;padding:6px 9px;border:1px solid #d1d5db;border-radius:8px">
  <span class="muted"><?= (int) $c['n'] ?> courses</span><button class="btn sm" type="submit">Save</button></form>
  <div style="margin-top:4px"><?= action_form('delete', ['id' => $c['id']], 'Delete', 'btn sm danger', 'Delete this category? Courses keep existing but become uncategorised.') ?></div></td></tr><?php endforeach; ?>
<?php if (!$cats): ?><tr><td colspan="3" class="empty">No categories yet.</td></tr><?php endif; ?></table></div>
<form class="f card" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="add"><h2>Add category</h2>
  <label>Name</label><input type="text" name="name" required maxlength="120"><label>Description</label><input type="text" name="description" maxlength="255"><p><button class="btn primary" type="submit">Add</button></p></form>
</div>
<?php admin_footer();
