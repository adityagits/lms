<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin.php';
require_login('admin');
if (is_post()) {
    verify_csrf();
    if (input('action') === 'reset_theme') {
        foreach (['theme_json', 'theme_css', 'theme_fonts', 'custom_css'] as $k) save_setting($k, '');
        flash('success', 'Site theme reset to defaults.');
        redirect('admin/settings.php');
    }
    $pct = max(0, min(100, (float) input('commission_pct', 20)));
    foreach ([
        'site_name' => mb_substr((string) input('site_name'), 0, 80) ?: 'LMS',
        'tagline' => mb_substr((string) input('tagline'), 0, 160),
        'currency' => mb_substr((string) input('currency', '$'), 0, 6),
        'contact_email' => filter_var(input('contact_email'), FILTER_VALIDATE_EMAIL) ?: '',
        'commission_pct' => (string) $pct,
        'payment_mode' => input('payment_mode') === 'manual' ? 'manual' : 'demo',
        'allow_registration' => !empty($_POST['allow_registration']) ? '1' : '0',
        'instructor_approval' => !empty($_POST['instructor_approval']) ? '1' : '0',
    ] as $k => $v) save_setting($k, (string) $v);
    flash('success', 'Settings saved.');
    redirect('admin/settings.php');
}
admin_header('Settings', 'settings.php');
$s = fn($k, $d = '') => e(setting($k, $d));
?>
<form class="f card" method="post" style="max-width:720px"><?= csrf_field() ?>
  <h2>General</h2>
  <label>Site name</label><input type="text" name="site_name" value="<?= $s('site_name', 'LMS') ?>" required>
  <label>Tagline</label><input type="text" name="tagline" value="<?= $s('tagline') ?>">
  <div class="row"><div><label>Currency symbol</label><input type="text" name="currency" value="<?= $s('currency', '$') ?>" maxlength="6"></div><div><label>Contact email</label><input type="email" name="contact_email" value="<?= $s('contact_email') ?>"></div></div>
  <h2 style="margin-top:24px">Registration</h2>
  <label class="check"><input type="checkbox" name="allow_registration" value="1" <?= setting('allow_registration', '1') === '1' ? 'checked' : '' ?>> Allow public registration</label>
  <label class="check"><input type="checkbox" name="instructor_approval" value="1" <?= setting('instructor_approval', '1') === '1' ? 'checked' : '' ?>> New instructors must be approved by an admin</label>
  <h2 style="margin-top:24px">Sales</h2>
  <div class="row"><div><label>Payment mode</label><select name="payment_mode"><option value="demo" <?= setting('payment_mode') === 'demo' ? 'selected' : '' ?>>Demo (instant, no real payment)</option><option value="manual" <?= setting('payment_mode') === 'manual' ? 'selected' : '' ?>>Manual (admin confirms payment)</option></select></div>
  <div><label>Platform commission (%)</label><input type="number" name="commission_pct" min="0" max="100" step="0.1" value="<?= $s('commission_pct', '20') ?>"></div></div>
  <p class="hint">Real card payments need a gateway integration (e.g. Stripe/PayPal) in <code>checkout.php</code>.</p>
  <p><button class="btn primary" type="submit">Save settings</button></p>
</form>
<div class="card" style="max-width:720px"><h2>Site design</h2>
  <p>Colours, fonts and custom CSS are edited visually in the page builder (<em>Theme</em> tab) and apply to the whole site.</p>
  <p class="actions"><a class="btn" href="pages.php">Open pages & builder</a><?= action_form('reset_theme', [], 'Reset theme to defaults', 'btn danger', 'Reset colours, fonts and custom CSS?') ?></p></div>
<?php admin_footer();
