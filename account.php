<?php
require __DIR__ . '/includes/bootstrap.php';
$u = require_login();
if (is_post()) {
    verify_csrf();
    if (input('action') === 'password') {
        if (!password_verify((string) ($_POST['current'] ?? ''), $u['password_hash'])) flash('error', 'Current password is incorrect.');
        elseif (strlen((string) ($_POST['new'] ?? '')) < 8) flash('error', 'New password must be at least 8 characters.');
        else { update('users', ['password_hash' => password_hash($_POST['new'], PASSWORD_DEFAULT)], 'id = ?', [$u['id']]); flash('success', 'Password changed.'); }
    } else {
        $err = null; $data = ['name' => mb_substr((string) input('name'), 0, 120), 'headline' => mb_substr((string) input('headline'), 0, 190), 'bio' => mb_substr((string) input('bio'), 0, 3000)];
        if ($data['name'] === '') { flash('error', 'Name is required.'); redirect('account.php'); }
        if ($img = save_image_upload('avatar', 'avatars', $err)) $data['avatar'] = $img;
        if ($err) flash('error', $err);
        else { update('users', $data, 'id = ?', [$u['id']]); flash('success', 'Profile updated.'); }
    }
    redirect('account.php');
}
public_header('My account');
?>
<section class="wb-section" data-pad="md"><div class="wb-container" style="max-width:720px">
  <h1 style="font-size:2rem">My account</h1>
  <div class="wb-card" style="margin-bottom:24px"><form class="lms-form" method="post" enctype="multipart/form-data"><?= csrf_field() ?>
    <div style="display:flex;gap:16px;align-items:center"><img src="<?= e(avatar_url($u)) ?>" alt="" style="width:72px;height:72px;border-radius:50%;object-fit:cover"><label style="flex:1">Profile photo<input type="file" name="avatar" accept="image/png,image/jpeg,image/gif,image/webp"></label></div>
    <label>Name<input type="text" name="name" value="<?= e($u['name']) ?>" required></label>
    <label>Email<input type="email" value="<?= e($u['email']) ?>" disabled></label>
    <label>Headline<input type="text" name="headline" value="<?= e($u['headline']) ?>" maxlength="190"></label>
    <label>Bio<textarea name="bio" rows="4"><?= e($u['bio']) ?></textarea></label>
    <button class="wb-btn" type="submit">Save profile</button></form></div>
  <div class="wb-card"><h3>Change password</h3><form class="lms-form" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="password">
    <label>Current password<input type="password" name="current" required autocomplete="current-password"></label>
    <label>New password<input type="password" name="new" minlength="8" required autocomplete="new-password"></label>
    <button class="wb-btn" type="submit">Change password</button></form></div>
</div></section>
<?php public_footer();
