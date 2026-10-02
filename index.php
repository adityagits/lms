<?php
require __DIR__ . '/includes/bootstrap.php';
$page = row("SELECT * FROM pages WHERE slug = 'home' AND status = 'published'");
public_header(setting('site_name', 'LMS'), ['description' => $page['description'] ?? '']);
if ($page) {
    echo render_page_blocks(json_decode($page['blocks_json'] ?: '[]', true) ?: []);
} else {
    echo render_dynamic_block('courses', ['title' => 'Latest courses']);
}
public_footer();
