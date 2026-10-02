<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin.php';
$me = require_login('admin');
if (is_post()) {
    verify_csrf();
    $t = row('SELECT * FROM users WHERE id = ?', [int_in('id')]);
    $isSelf = $t && (int) $t['id'] === (int) $me['id'];
    switch (input('action')) {
        case 'create':
            $email = strtolower((string) input('email')); $name = mb_substr((string) input('name'), 0, 120); $pw = (string) ($_POST['password'] ?? '');
            $role = in_array(input('role'), ['admin', 'instructor', 'student'], true) ? input('role') : 'student';
            if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pw) < 8) flash('error', 'Name, valid email and a password of 8+ characters are required.');
            elseif (val('SELECT id FROM users WHERE email = ?', [$email])) flash('error', 'That email is already registered.');
            else { insert('users', ['name' => $name, 'email' => $email, 'password_hash' => password_hash($pw, PASSWORD_DEFAULT), 'role' => $role]); flash('success', 'User created.'); }
            break;
        case 'status':
            if ($t && !$isSelf && in_array(input('status'), ['active', 'pending', 'blocked'], true)) { update('users', ['status' => input('status')], 'id = ?', [$t['id']]); flash('success', 'User updated.'); }
            break;
        case 'role':
            if ($t && !$isSelf && in_array(input('role'), ['admin', 'instructor', 'student'], true)) { update('users', ['role' => input('role')], 'id = ?', [$t['id']]); flash('success', 'Role changed.'); }
            break;
        case 'password':
            if ($t && strlen((string) ($_POST['password'] ?? '')) >= 8) { update('users', ['password_hash' => password_hash($_POST['password'], PASSWORD_DEFAULT)], 'id = ?', [$t['id']]); flash('success', 'Password reset.'); }
            else flash('error', 'Password must be at least 8 characters.');
            break;
        case 'delete':
            if ($t && !$isSelf) { q('DELETE FROM users WHERE id = ?', [$t['id']]); flash('success', 'User deleted.'); }
            break;
    }
    redirect('admin/users.php');
}
admin_header('Users', 'users.php');
$role = input('role'); $status = input('status'); $qs = input('q');
$where = ['1=1']; $p = [];
if (in_array($role, ['admin', 'instructor', 'student'], true)) { $where[] = 'role = ?'; $p[] = $role; }
if (in_array($status, ['active', 'pending', 'blocked'], true)) { $where[] = 'status = ?'; $p[] = $status; }
if ($qs !== '') { $where[] = '(name LIKE ? OR email LIKE ?)'; $like = '%' . addcslashes($qs, '%_\\') . '%'; $p[] = $like; $p[] = $like; }
$w = implode(' AND ', $where);
$pg = paginate((int) val("SELECT COUNT(*) FROM users WHERE $w", $p), 20, int_in('page', 1));
$list = rows("SELECT * FROM users WHERE $w ORDER BY id DESC LIMIT {$pg['per']} OFFSET {$pg['offset']}", $p);
?>
<form class="filters" method="get"><input type="search" name="q" value="<?= e($qs) ?>" placeholder="Name or email">
  <select name="role"><option value="">All roles</option><?php foreach (['admin', 'instructor', 'student'] as $r): ?><option <?= $role === $r ? 'selected' : '' ?>><?= $r ?></option><?php endforeach; ?></select>
  <select name="status"><option value="">All statuses</option><?php foreach (['active', 'pending', 'blocked'] as $r): ?><option <?= $status === $r ? 'selected' : '' ?>><?= $r ?></option><?php endforeach; ?></select>
  <button class="btn" type="submit">Filter</button></form>
<div class="table-wrap"><table class="t"><tr><th>User</th><th>Role</th><th>Status</th><th>Joined</th><th></th></tr>
<?php foreach ($list as $u): $self = (int) $u['id'] === (int) $me['id']; ?>
<tr><td><strong><?= e($u['name']) ?></strong><br><span class="muted"><?= e($u['email']) ?></span></td>
<td><?php if ($self): echo e($u['role']); else: ?><form method="post" style="display:flex;gap:4px"><?= csrf_field() ?><input type="hidden" name="action" value="role"><input type="hidden" name="id" value="<?= (int) $u['id'] ?>"><select name="role" onchange="this.form.submit()" style="padding:4px;border-radius:6px"><?php foreach (['admin', 'instructor', 'student'] as $r): ?><option <?= $u['role'] === $r ? 'selected' : '' ?>><?= $r ?></option><?php endforeach; ?></select></form><?php endif; ?></td>
<td><?= badge($u['status']) ?></td><td><?= e(fdate($u['created_at'])) ?></td>
<td class="actions"><?php if (!$self): ?>
  <?= $u['status'] !== 'active' ? action_form('status', ['id' => $u['id'], 'status' => 'active'], $u['status'] === 'pending' ? 'Approve' : 'Unblock', 'btn sm ok') : action_form('status', ['id' => $u['id'], 'status' => 'blocked'], 'Block', 'btn sm danger', 'Block this user?') ?>
  <form method="post" style="display:flex;gap:4px" onsubmit="var p=prompt('New password (min 8 chars)'); if(!p) return false; this.password.value=p;"><?= csrf_field() ?><input type="hidden" name="action" value="password"><input type="hidden" name="id" value="<?= (int) $u['id'] ?>"><input type="hidden" name="password"><button class="btn sm" type="submit">Reset password</button></form>
  <?= action_form('delete', ['id' => $u['id']], 'Delete', 'btn sm danger', 'Delete this user and all their data?') ?>
<?php else: ?><span class="muted">You</span><?php endif; ?></td></tr>
<?php endforeach; if (!$list): ?><tr><td colspan="5" class="empty">No users found.</td></tr><?php endif; ?></table></div>
<?= admin_pager($pg) ?>
<form class="f card" method="post" style="margin-top:20px"><?= csrf_field() ?><input type="hidden" name="action" value="create"><h2>Add user</h2>
<div class="grid3"><div><label>Name</label><input type="text" name="name" required></div><div><label>Email</label><input type="email" name="email" required></div><div><label>Password</label><input type="password" name="password" minlength="8" required autocomplete="new-password"></div></div>
<label>Role</label><select name="role" style="max-width:220px"><option>student</option><option>instructor</option><option>admin</option></select><p><button class="btn primary" type="submit">Create user</button></p></form>
<?php admin_footer();
