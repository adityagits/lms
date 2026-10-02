<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin.php';
require_login('admin');
if (is_post()) {
    verify_csrf();
    switch (input('action')) {
        case 'add':
            $code = strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', (string) input('code')));
            $type = input('type') === 'fixed' ? 'fixed' : 'percent';
            $value = (float) input('value');
            if ($code === '' || $value <= 0 || ($type === 'percent' && $value > 100)) { flash('error', 'Enter a code and a valid value (percent: 1–100).'); break; }
            if (val('SELECT id FROM coupons WHERE code = ?', [$code])) { flash('error', 'That code already exists.'); break; }
            insert('coupons', ['code' => $code, 'type' => $type, 'value' => $value, 'max_uses' => input('max_uses') === '' ? null : max(1, int_in('max_uses')), 'expires_at' => input('expires_at') ?: null]);
            flash('success', 'Coupon created.');
            break;
        case 'toggle': q('UPDATE coupons SET active = 1 - active WHERE id = ?', [int_in('id')]); break;
        case 'delete': q('DELETE FROM coupons WHERE id = ?', [int_in('id')]); flash('success', 'Coupon deleted.'); break;
    }
    redirect('admin/coupons.php');
}
admin_header('Coupons', 'coupons.php');
$list = rows('SELECT * FROM coupons ORDER BY id DESC');
?>
<div class="grid2" style="align-items:start">
<div class="table-wrap"><table class="t"><tr><th>Code</th><th>Discount</th><th>Used</th><th>Expires</th><th>Status</th><th></th></tr>
<?php foreach ($list as $c): ?><tr><td><strong><?= e($c['code']) ?></strong></td><td><?= $c['type'] === 'percent' ? e(rtrim(rtrim(number_format((float) $c['value'], 2), '0'), '.')) . '%' : e(money_plain($c['value'])) ?></td><td><?= (int) $c['used'] ?><?= $c['max_uses'] !== null ? ' / ' . (int) $c['max_uses'] : '' ?></td><td><?= e(fdate($c['expires_at'])) ?></td><td><?= badge($c['active'] ? 'active' : 'hidden') ?></td>
<td class="actions"><?= action_form('toggle', ['id' => $c['id']], $c['active'] ? 'Disable' : 'Enable') ?><?= action_form('delete', ['id' => $c['id']], 'Delete', 'btn sm danger', 'Delete coupon?') ?></td></tr><?php endforeach; if (!$list): ?><tr><td colspan="6" class="empty">No coupons.</td></tr><?php endif; ?></table></div>
<form class="f card" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="add"><h2>New coupon</h2>
  <label>Code</label><input type="text" name="code" required maxlength="40" placeholder="SUMMER20">
  <div class="row"><div><label>Type</label><select name="type"><option value="percent">Percent (%)</option><option value="fixed">Fixed amount</option></select></div><div><label>Value</label><input type="number" name="value" step="0.01" min="0.01" required></div></div>
  <div class="row"><div><label>Max uses</label><input type="number" name="max_uses" min="1" placeholder="Unlimited"></div><div><label>Expires</label><input type="date" name="expires_at"></div></div>
  <p><button class="btn primary" type="submit">Create coupon</button></p></form>
</div>
<?php admin_footer();
