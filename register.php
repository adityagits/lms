<?php
require __DIR__ . '/includes/bootstrap.php';
if (current_user()) redirect('');
if (setting('allow_registration', '1') !== '1') render_error(403, 'Registration is currently closed.');
$asInstructor = input('as') === 'instructor';
$err = '';
if (is_post()) {
    verify_csrf();
    $name = mb_substr((string) input('name'), 0, 120);
    $email = strtolower((string) input('email'));
    $pass = (string) ($_POST['password'] ?? '');
    $role = input('role') === 'instructor' ? 'instructor' : 'student';
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $err = 'Please enter your name and a valid email.';
    elseif (strlen($pass) < 8) $err = 'Password must be at least 8 characters.';
    elseif (val('SELECT id FROM users WHERE email = ?', [$email])) $err = 'An account with this email already exists.';
    else {
        $status = ($role === 'instructor' && setting('instructor_approval', '1') === '1') ? 'pending' : 'active';
        $id = insert('users', ['name' => $name, 'email' => $email, 'password_hash' => password_hash($pass, PASSWORD_DEFAULT), 'role' => $role, 'status' => $status]);
        login_user(['id' => $id]);
        if ($status === 'pending') flash('info', 'Account created. An administrator must approve your instructor account before you can publish courses.');
        else flash('success', 'Welcome aboard, ' . explode(' ', $name)[0] . '!');
        redirect($role === 'instructor' ? 'admin/' : 'courses.php');
    }
}
public_header('Create account');
?>
<section class="wb-section"><div class="wb-container lms-auth">
  <h1 style="font-size:2rem;text-align:center"><?= $asInstructor ? 'Become an instructor' : 'Create your account' ?></h1>
  <div class="wb-card">
    <?php if ($err): ?><div class="lms-error"><?= e($err) ?></div><?php endif; ?>
    <form class="lms-form" method="post"><?= csrf_field() ?>
      <label>Full name<input type="text" name="name" value="<?= e(input('name')) ?>" required autocomplete="name"></label>
      <label>Email<input type="email" name="email" value="<?= e(input('email')) ?>" required autocomplete="email"></label>
      <label>Password<input type="password" name="password" minlength="8" required autocomplete="new-password"><span class="hint">At least 8 characters.</span></label>
      <label>I want to<select name="role"><option value="student">Learn (student)</option><option value="instructor" <?= $asInstructor || input('role') === 'instructor' ? 'selected' : '' ?>>Teach (instructor)</option></select></label>
      <button class="wb-btn wb-btn--block" type="submit">Create account</button>
    </form>
  </div>
  <p style="text-align:center" class="wb-muted">Already registered? <a href="<?= e(url('login.php')) ?>">Sign in</a></p>
</div></section>
<?php public_footer();
