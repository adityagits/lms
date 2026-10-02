<?php
require __DIR__ . '/includes/bootstrap.php';
$next = safe_next((string) input('next'));
if ($u = current_user()) redirect($next ?: home_for($u));
$err = '';
if (is_post()) {
    verify_csrf();
    $email = strtolower((string) input('email'));
    if (login_throttled()) {
        $err = 'Too many failed attempts. Please wait a few minutes and try again.';
    } else {
        $user = row('SELECT * FROM users WHERE email = ?', [$email]);
        if ($user && password_verify((string) ($_POST['password'] ?? ''), $user['password_hash'])) {
            if ($user['status'] === 'blocked') {
                $err = 'This account has been disabled.';
            } else {
                if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) update('users', ['password_hash' => password_hash($_POST['password'], PASSWORD_DEFAULT)], 'id = ?', [$user['id']]);
                login_user($user);
                redirect($next ?: home_for($user));
            }
        } else {
            login_failed($email);
            $err = 'Incorrect email or password.';
        }
    }
}
public_header('Sign in');
?>
<section class="wb-section"><div class="wb-container lms-auth">
  <h1 style="font-size:2rem;text-align:center">Welcome back</h1>
  <div class="wb-card">
    <?php if ($err): ?><div class="lms-error"><?= e($err) ?></div><?php endif; ?>
    <form class="lms-form" method="post"><?= csrf_field() ?><input type="hidden" name="next" value="<?= e($next) ?>">
      <label>Email<input type="email" name="email" value="<?= e(input('email')) ?>" required autofocus autocomplete="username"></label>
      <label>Password<input type="password" name="password" required autocomplete="current-password"></label>
      <button class="wb-btn wb-btn--block" type="submit">Sign in</button>
    </form>
  </div>
  <p style="text-align:center" class="wb-muted">New here? <a href="<?= e(url('register.php')) ?>">Create an account</a></p>
</div></section>
<?php public_footer();
