<?php
require __DIR__ . '/includes/bootstrap.php';
$slug = (string) input('slug');
$page = row('SELECT * FROM pages WHERE slug = ?', [$slug]);
// admins can preview drafts
if (!$page || ($page['status'] !== 'published' && !is_role('admin'))) render_error(404, 'Page not found.');
public_header($page['title'], ['description' => $page['description']]);
if ($page['status'] !== 'published') echo '<div class="lms-flash"><div class="wb-container">Draft preview — this page is not public yet.</div></div>';
echo render_page_blocks(json_decode($page['blocks_json'] ?: '[]', true) ?: []);
public_footer();
