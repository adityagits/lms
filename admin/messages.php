<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin.php';
require_login('admin');
if (is_post()) { verify_csrf(); q('DELETE FROM contact_messages WHERE id = ?', [int_in('id')]); flash('success', 'Message deleted.'); redirect('admin/messages.php'); }
admin_header('Messages', 'messages.php');
$list = rows('SELECT * FROM contact_messages ORDER BY id DESC LIMIT 100');
foreach ($list as $m): ?>
<div class="card"><div class="card-h"><span><strong><?= e($m['name']) ?></strong> &lt;<a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a>&gt; <span class="muted">· <?= e(fdate($m['created_at'], 'M j, Y H:i')) ?></span></span><?= action_form('delete', ['id' => $m['id']], 'Delete', 'btn sm danger', 'Delete message?') ?></div><p style="margin:0"><?= nl2br(e($m['message'])) ?></p></div>
<?php endforeach; if (!$list) echo '<div class="card empty">No messages yet.</div>';
admin_footer();
