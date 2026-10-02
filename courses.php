<?php
require __DIR__ . '/includes/bootstrap.php';
$f = ['q' => input('q'), 'category' => input('category'), 'level' => input('level'), 'price' => input('price'), 'sort' => input('sort', 'newest')];
$per = 12;
$count = published_courses($f + ['limit' => 1])['total'];
$pg = paginate($count, $per, int_in('page', 1));
$res = published_courses($f + ['limit' => $per, 'offset' => $pg['offset']]);
$cats = rows('SELECT name, slug FROM categories ORDER BY name');
public_header('Courses');
?>
<section class="wb-section" data-bg="alt" data-pad="sm"><div class="wb-container"><h1 style="margin:0">All courses</h1><p class="wb-muted" style="margin:.3em 0 0"><?= (int) $count ?> course<?= $count === 1 ? '' : 's' ?> found</p></div></section>
<section class="wb-section" data-pad="sm"><div class="wb-container">
<form class="lms-filters" method="get">
  <label>Search<input type="search" name="q" value="<?= e($f['q']) ?>" placeholder="Search courses"></label>
  <label>Category<select name="category"><option value="">All</option><?php foreach ($cats as $c): ?><option value="<?= e($c['slug']) ?>" <?= $f['category'] === $c['slug'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></label>
  <label>Level<select name="level"><option value="">Any</option><?php foreach (['beginner', 'intermediate', 'advanced'] as $l): ?><option value="<?= $l ?>" <?= $f['level'] === $l ? 'selected' : '' ?>><?= ucfirst($l) ?></option><?php endforeach; ?></select></label>
  <label>Price<select name="price"><option value="">Any</option><option value="free" <?= $f['price'] === 'free' ? 'selected' : '' ?>>Free</option><option value="paid" <?= $f['price'] === 'paid' ? 'selected' : '' ?>>Paid</option></select></label>
  <label>Sort<select name="sort"><?php foreach (['newest' => 'Newest', 'popular' => 'Most popular', 'rating' => 'Top rated', 'price_asc' => 'Price: low to high', 'price_desc' => 'Price: high to low'] as $k => $l): ?><option value="<?= $k ?>" <?= $f['sort'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></label>
  <button class="wb-btn" type="submit">Filter</button>
</form>
<?php if (!$res['courses']): ?><p class="wb-muted" style="text-align:center;padding:40px 0">No courses match your filters.</p>
<?php else: ?><div class="wb-grid lms-grid"><?php foreach ($res['courses'] as $c) echo course_card($c); ?></div><?php endif; ?>
<?= pager($pg) ?>
</div></section>
<?php public_footer();
