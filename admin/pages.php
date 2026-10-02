<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin.php';
require_login('admin');
$reserved = ['admin', 'install', 'wb', 'uploads', 'assets', 'includes', 'database'];
if (is_post()) {
    verify_csrf();
    $p = row('SELECT * FROM pages WHERE id = ?', [int_in('id')]);
    switch (input('action')) {
        case 'create':
            $title = mb_substr((string) input('title'), 0, 200);
            if ($title === '') { flash('error', 'Title is required.'); break; }
            $slug = unique_slug('pages', (string) input('slug') ?: $title);
            if (in_array($slug, $reserved, true)) $slug .= '-page';
            $id = insert('pages', ['slug' => $slug, 'title' => $title, 'blocks_json' => '[]', 'status' => 'draft']);
            flash('success', 'Page created. Design it with the builder.');
            redirect('admin/builder.php?id=' . $id);
        case 'meta':
            if (!$p) break;
            $d = ['title' => mb_substr((string) input('title'), 0, 200) ?: $p['title'], 'description' => mb_substr((string) input('description'), 0, 300),
                  'status' => input('status') === 'published' ? 'published' : 'draft', 'show_in_menu' => !empty($_POST['show_in_menu']) ? 1 : 0, 'menu_order' => int_in('menu_order')];
            if ($p['slug'] !== 'home') {
                $slug = unique_slug('pages', (string) input('slug') ?: $d['title'], (int) $p['id']);
                if (!in_array($slug, $reserved, true)) $d['slug'] = $slug;
            } else { $d['status'] = 'published'; }
            update('pages', $d, 'id = ?', [$p['id']]);
            flash('success', 'Page saved.');
            break;
        case 'delete':
            if ($p && $p['slug'] !== 'home') { q('DELETE FROM pages WHERE id = ?', [$p['id']]); flash('success', 'Page deleted.'); }
            else flash('error', 'The home page cannot be deleted.');
            break;
        case 'duplicate':
            if ($p) { $id = insert('pages', ['slug' => unique_slug('pages', $p['slug'] . '-copy'), 'title' => $p['title'] . ' (copy)', 'description' => $p['description'], 'blocks_json' => $p['blocks_json'], 'status' => 'draft']); flash('success', 'Page duplicated.'); }
            break;
    }
    redirect('admin/pages.php');
}
admin_header('Pages', 'pages.php');
$list = rows('SELECT * FROM pages ORDER BY slug = \'home\' DESC, menu_order, id');
$edit = int_in('edit') ? row('SELECT * FROM pages WHERE id = ?', [int_in('edit')]) : null;
?>
<p class="muted">Pages are designed with the visual builder. The <strong>home</strong> page is shown on the front page; other pages live at <code>page.php?slug=…</code> and can be added to the site menu.</p>
<div class="table-wrap"><table class="t"><tr><th>Title</th><th>Slug</th><th>Menu</th><th>Status</th><th>Updated</th><th></th></tr>
<?php foreach ($list as $p): ?><tr><td><strong><?= e($p['title']) ?></strong></td><td><code><?= e($p['slug']) ?></code></td><td><?= $p['show_in_menu'] ? '✓ #' . (int) $p['menu_order'] : '—' ?></td><td><?= badge($p['status']) ?></td><td><?= e(fdate($p['updated_at'])) ?></td>
<td class="actions"><a class="btn sm primary" href="builder.php?id=<?= (int) $p['id'] ?>">Design</a><a class="btn sm" href="?edit=<?= (int) $p['id'] ?>">Settings</a>
<a class="btn sm" target="_blank" rel="noopener" href="<?= e($p['slug'] === 'home' ? url('') : url('page.php?slug=' . $p['slug'])) ?>">View</a>
<?= action_form('duplicate', ['id' => $p['id']], 'Duplicate') ?><?php if ($p['slug'] !== 'home') echo action_form('delete', ['id' => $p['id']], 'Delete', 'btn sm danger', 'Delete this page?'); ?></td></tr><?php endforeach; ?></table></div>
<div class="grid2" style="margin-top:20px;align-items:start">
<?php if ($edit): ?>
<form class="f card" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="meta"><input type="hidden" name="id" value="<?= (int) $edit['id'] ?>"><h2>Page settings: <?= e($edit['title']) ?></h2>
  <label>Title</label><input type="text" name="title" value="<?= e($edit['title']) ?>" required>
  <label>Slug</label><input type="text" name="slug" value="<?= e($edit['slug']) ?>" <?= $edit['slug'] === 'home' ? 'disabled' : '' ?>>
  <label>SEO description</label><input type="text" name="description" value="<?= e($edit['description']) ?>" maxlength="300">
  <div class="row"><div><label>Status</label><select name="status" <?= $edit['slug'] === 'home' ? 'disabled' : '' ?>><option value="draft" <?= $edit['status'] === 'draft' ? 'selected' : '' ?>>Draft</option><option value="published" <?= $edit['status'] === 'published' ? 'selected' : '' ?>>Published</option></select></div>
  <div><label>Menu order</label><input type="number" name="menu_order" value="<?= (int) $edit['menu_order'] ?>"></div></div>
  <label class="check"><input type="checkbox" name="show_in_menu" value="1" <?= $edit['show_in_menu'] ? 'checked' : '' ?>> Show in site menu & footer</label>
  <p><button class="btn primary" type="submit">Save settings</button> <a class="btn" href="pages.php">Close</a></p></form>
<?php endif; ?>
<form class="f card" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="create"><h2>New page</h2>
  <label>Title</label><input type="text" name="title" required maxlength="200" placeholder="e.g. FAQ"><label>Slug (optional)</label><input type="text" name="slug" placeholder="auto from title">
  <p><button class="btn primary" type="submit">Create & open builder</button></p></form>
</div>
<?php admin_footer();
