<?php
require __DIR__ . '/includes/bootstrap.php';
$u = require_login();
$c = find_course("c.id = ? AND c.status = 'published'", [int_in('course')]);
if (!$c) render_error(404, 'Course not found.');
if (is_enrolled((int) $u['id'], (int) $c['id'])) redirect('learn.php?course=' . $c['slug']);
$price = course_effective_price($c);
$mode = setting('payment_mode', 'demo');   // demo = instant "payment"; manual = pending until admin marks paid
$coupon = null; $discount = 0.0;
if (!empty($_SESSION['coupon'][$c['id']])) {
    $applied = coupon_apply($_SESSION['coupon'][$c['id']], $price);
    if ($applied) { $coupon = $applied['coupon']; $discount = $applied['discount']; } else unset($_SESSION['coupon'][$c['id']]);
}
$total = max(0, $price - $discount);

if (is_post()) {
    verify_csrf();
    $action = input('action');
    if ($action === 'coupon') {
        $applied = coupon_apply((string) input('code'), $price);
        if ($applied) { $_SESSION['coupon'][$c['id']] = $applied['coupon']['code']; flash('success', 'Coupon applied.'); }
        else { unset($_SESSION['coupon'][$c['id']]); flash('error', 'That coupon is invalid or expired.'); }
        redirect('checkout.php?course=' . $c['id']);
    }
    if ($action === 'enroll' || $action === 'pay') {
        if ($total <= 0) {
            $oid = insert('orders', ['user_id' => $u['id'], 'course_id' => $c['id'], 'amount' => 0, 'discount' => $discount, 'coupon_id' => $coupon['id'] ?? null, 'method' => 'free', 'status' => 'paid']);
            enroll_user((int) $u['id'], (int) $c['id'], $oid);
        } else {
            $status = $mode === 'manual' ? 'pending' : 'paid';
            $oid = insert('orders', ['user_id' => $u['id'], 'course_id' => $c['id'], 'amount' => $total, 'discount' => $discount, 'coupon_id' => $coupon['id'] ?? null, 'method' => $mode, 'status' => $status]);
            if ($status === 'paid') enroll_user((int) $u['id'], (int) $c['id'], $oid);
        }
        if ($coupon) q('UPDATE coupons SET used = used + 1 WHERE id = ?', [$coupon['id']]);
        unset($_SESSION['coupon'][$c['id']]);
        if ($total > 0 && $mode === 'manual') { flash('info', 'Order received. You will get access as soon as the payment is confirmed.'); redirect('my-courses.php'); }
        flash('success', 'You are enrolled! Happy learning.');
        redirect('learn.php?course=' . $c['slug']);
    }
}
$pending = (bool) val("SELECT id FROM orders WHERE user_id = ? AND course_id = ? AND status = 'pending'", [$u['id'], $c['id']]);
public_header('Checkout');
?>
<section class="wb-section"><div class="wb-container" style="max-width:720px">
  <h1 style="font-size:2rem">Checkout</h1>
  <div class="wb-card">
    <div style="display:flex;gap:16px;align-items:center"><img src="<?= e(img_url($c['thumbnail'])) ?>" alt="" style="width:120px;border-radius:8px;aspect-ratio:16/9;object-fit:cover"><div><h3 style="margin:0"><?= e($c['title']) ?></h3><span class="wb-muted wb-small">by <?= e($c['instructor_name']) ?></span></div></div>
    <hr class="wb-divider">
    <table class="lms-table"><tr><td>Price</td><td style="text-align:right"><?= e(money_plain($price)) ?></td></tr>
      <?php if ($coupon): ?><tr><td>Coupon <?= e($coupon['code']) ?></td><td style="text-align:right">−<?= e(money_plain($discount)) ?></td></tr><?php endif; ?>
      <tr><th>Total</th><th style="text-align:right"><?= e(money_plain($total)) ?></th></tr></table>
    <?php if ($price > 0): ?>
    <form class="lms-form" method="post" style="grid-template-columns:1fr auto;margin:14px 0"><?= csrf_field() ?><input type="hidden" name="action" value="coupon"><input type="text" name="code" placeholder="Coupon code" aria-label="Coupon code"><button class="wb-btn wb-btn--outline" type="submit">Apply</button></form>
    <?php endif; ?>
    <?php if ($pending): ?><div class="lms-error" style="background:#fef3c7;color:#92400e">You already have a pending order for this course.</div><?php endif; ?>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="pay">
      <button class="wb-btn wb-btn--block" type="submit"><?= $total > 0 ? ($mode === 'manual' ? 'Place order' : 'Complete purchase') : 'Enroll now' ?></button></form>
    <?php if ($total > 0): ?><p class="wb-small wb-muted" style="margin-top:12px"><?= $mode === 'manual' ? 'Payment is confirmed manually by the site team (bank transfer / invoice).' : 'Demo checkout: no real payment is taken. Connect a payment gateway for live sales.' ?></p><?php endif; ?>
  </div>
</div></section>
<?php public_footer();
