<?php
defined("APP_ROOT") || exit;
/** Domain logic: courses, enrollment, progress, certificates, dynamic page blocks. */

const COURSE_STATS_SQL = "
  c.*, u.name AS instructor_name, u.avatar AS instructor_avatar, cat.name AS category_name, cat.slug AS category_slug,
  (SELECT ROUND(AVG(r.rating),1) FROM reviews r WHERE r.course_id = c.id AND r.status='published') AS rating,
  (SELECT COUNT(*) FROM reviews r WHERE r.course_id = c.id AND r.status='published') AS review_count,
  (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id AND e.status <> 'cancelled') AS student_count,
  (SELECT COUNT(*) FROM lessons l WHERE l.course_id = c.id) AS lesson_count,
  (SELECT COALESCE(SUM(l.duration_min),0) FROM lessons l WHERE l.course_id = c.id) AS total_minutes";
const COURSE_FROM_SQL = " FROM courses c JOIN users u ON u.id = c.instructor_id LEFT JOIN categories cat ON cat.id = c.category_id ";

function find_course(string $where, array $params): ?array
{
    return row('SELECT ' . COURSE_STATS_SQL . COURSE_FROM_SQL . ' WHERE ' . $where, $params);
}
function course_effective_price(array $c): float
{
    $sale = $c['sale_price'] ?? null;
    return ($sale !== null && $sale !== '' && (float) $sale < (float) $c['price']) ? (float) $sale : (float) $c['price'];
}
function course_price_html(array $c): string
{
    $eff = course_effective_price($c);
    if ($eff < (float) $c['price']) {
        return '<span class="lms-price"><del>' . e(money_plain($c['price'])) . '</del> ' . e(money($eff)) . '</span>';
    }
    return '<span class="lms-price">' . e(money($eff)) . '</span>';
}

function can_manage_course(array $course, ?array $u = null): bool
{
    $u ??= current_user();
    return $u && ($u['role'] === 'admin' || ($u['role'] === 'instructor' && (int) $u['id'] === (int) $course['instructor_id']));
}

function is_enrolled(int $userId, int $courseId): bool
{
    return (bool) val("SELECT id FROM enrollments WHERE user_id = ? AND course_id = ? AND status <> 'cancelled'", [$userId, $courseId]);
}

/** Topics with their lessons/quizzes, ordered. */
function course_curriculum(int $courseId): array
{
    $topics = rows('SELECT * FROM topics WHERE course_id = ? ORDER BY position, id', [$courseId]);
    $items = rows('SELECT * FROM lessons WHERE course_id = ? ORDER BY position, id', [$courseId]);
    $by = [];
    foreach ($items as $it) $by[$it['topic_id']][] = $it;
    foreach ($topics as &$t) $t['items'] = $by[$t['id']] ?? [];
    return $topics;
}
function flat_lessons(array $curriculum): array
{
    $out = [];
    foreach ($curriculum as $t) foreach ($t['items'] as $it) $out[] = $it;
    return $out;
}

function course_progress(int $userId, int $courseId): array
{
    $total = (int) val('SELECT COUNT(*) FROM lessons WHERE course_id = ?', [$courseId]);
    $done = (int) val('SELECT COUNT(*) FROM lesson_progress WHERE user_id = ? AND course_id = ?', [$userId, $courseId]);
    return ['done' => $done, 'total' => $total, 'pct' => $total ? (int) floor($done * 100 / $total) : 0];
}

/** Create an enrollment (idempotent). Returns enrollment id. */
function enroll_user(int $userId, int $courseId, ?int $orderId = null): int
{
    $ex = row('SELECT id, status FROM enrollments WHERE user_id = ? AND course_id = ?', [$userId, $courseId]);
    if ($ex) {
        if ($ex['status'] === 'cancelled') update('enrollments', ['status' => 'active', 'order_id' => $orderId], 'id = ?', [$ex['id']]);
        return (int) $ex['id'];
    }
    return insert('enrollments', ['user_id' => $userId, 'course_id' => $courseId, 'order_id' => $orderId]);
}

function mark_lesson_complete(int $userId, array $lesson): void
{
    q('INSERT IGNORE INTO lesson_progress (user_id, lesson_id, course_id) VALUES (?,?,?)', [$userId, $lesson['id'], $lesson['course_id']]);
    $p = course_progress($userId, (int) $lesson['course_id']);
    if ($p['total'] > 0 && $p['done'] >= $p['total']) {
        $en = row('SELECT * FROM enrollments WHERE user_id = ? AND course_id = ?', [$userId, $lesson['course_id']]);
        if ($en && $en['status'] === 'active') {
            update('enrollments', ['status' => 'completed', 'completed_at' => date('Y-m-d H:i:s'), 'certificate_code' => strtoupper(bin2hex(random_bytes(6)))], 'id = ?', [$en['id']]);
            flash('success', 'Congratulations! You completed the course and earned a certificate.');
        }
    }
}

/** Grade a quiz submission. $answers = [questionId => [optionId,...]] */
function grade_quiz(int $lessonId, array $answers): array
{
    $qs = rows('SELECT * FROM quiz_questions WHERE lesson_id = ?', [$lessonId]);
    if (!$qs) return ['pct' => 0, 'correct' => 0, 'total' => 0];
    $correct = 0;
    foreach ($qs as $qq) {
        $right = array_map('intval', array_column(rows('SELECT id FROM quiz_options WHERE question_id = ? AND is_correct = 1', [$qq['id']]), 'id'));
        $given = array_map('intval', (array) ($answers[$qq['id']] ?? []));
        sort($right); sort($given);
        if ($right && $right === $given) $correct++;
    }
    return ['pct' => (int) round($correct * 100 / count($qs)), 'correct' => $correct, 'total' => count($qs)];
}

function enrolled_courses(int $userId): array
{
    return rows('SELECT ' . COURSE_STATS_SQL . ', e.status AS enroll_status, e.enrolled_at, e.certificate_code ' . COURSE_FROM_SQL .
        " JOIN enrollments e ON e.course_id = c.id AND e.user_id = ? WHERE e.status <> 'cancelled' ORDER BY e.enrolled_at DESC", [$userId]);
}

function coupon_apply(string $code, float $price): ?array
{
    $c = row('SELECT * FROM coupons WHERE code = ? AND active = 1', [strtoupper(trim($code))]);
    if (!$c) return null;
    if ($c['expires_at'] && $c['expires_at'] < date('Y-m-d')) return null;
    if ($c['max_uses'] !== null && (int) $c['used'] >= (int) $c['max_uses']) return null;
    $disc = $c['type'] === 'percent' ? round($price * min(100, (float) $c['value']) / 100, 2) : min($price, (float) $c['value']);
    return ['coupon' => $c, 'discount' => $disc];
}

/* ---- public course card (shared by catalog, home blocks, dashboards) ---- */
function course_card(array $c, ?array $progress = null): string
{
    $h = '<article class="wb-card lms-card">';
    $h .= '<a class="lms-card__img" href="' . e(url('course.php?slug=' . $c['slug'])) . '"><img loading="lazy" src="' . e(img_url($c['thumbnail'])) . '" alt="">';
    if (!empty($c['is_featured'])) $h .= '<span class="wb-badge lms-card__flag">Featured</span>';
    $h .= '</a><div class="lms-card__body">';
    if (!empty($c['category_name'])) $h .= '<span class="lms-card__cat">' . e($c['category_name']) . '</span>';
    $h .= '<h3><a href="' . e(url('course.php?slug=' . $c['slug'])) . '">' . e($c['title']) . '</a></h3>';
    $h .= '<p class="lms-card__by">by ' . e($c['instructor_name']) . '</p>';
    if (isset($c['rating'])) {
        $h .= '<p class="lms-card__rating">' . stars((float) $c['rating']) . ' <span class="wb-muted wb-small">' . number_format((float) $c['rating'], 1) . ' (' . (int) $c['review_count'] . ')</span></p>';
    } elseif (isset($c['review_count'])) {
        $h .= '<p class="lms-card__rating wb-muted wb-small">No reviews yet</p>';
    }
    if ($progress !== null) {
        $h .= '<div class="lms-progress" title="' . (int) $progress['pct'] . '% complete"><span style="width:' . (int) $progress['pct'] . '%"></span></div>';
        $h .= '<p class="wb-small wb-muted">' . (int) $progress['done'] . ' / ' . (int) $progress['total'] . ' items · ' . (int) $progress['pct'] . '%</p>';
        $h .= '<a class="wb-btn wb-btn--block" href="' . e(url('learn.php?course=' . $c['slug'])) . '">' . ($progress['pct'] >= 100 ? 'Review course' : ($progress['done'] ? 'Continue' : 'Start learning')) . '</a>';
    } else {
        $h .= '<div class="lms-card__foot"><span class="wb-muted wb-small">' . (int) $c['lesson_count'] . ' lessons · ' . e(minutes_label((int) $c['total_minutes'])) . '</span>' . course_price_html($c) . '</div>';
    }
    return $h . '</div></article>';
}

function published_courses(array $opts = []): array
{
    $where = ["c.status = 'published'"]; $p = [];
    if (!empty($opts['category'])) { $where[] = 'cat.slug = ?'; $p[] = $opts['category']; }
    if (!empty($opts['instructor'])) { $where[] = 'c.instructor_id = ?'; $p[] = (int) $opts['instructor']; }
    if (!empty($opts['q'])) { $where[] = '(c.title LIKE ? OR c.short_desc LIKE ?)'; $p[] = $p[] = '%' . addcslashes($opts['q'], '%_\\') . '%'; }
    if (!empty($opts['level'])) { $where[] = 'c.level = ?'; $p[] = $opts['level']; }
    if (($opts['price'] ?? '') === 'free') $where[] = 'c.price = 0';
    if (($opts['price'] ?? '') === 'paid') $where[] = 'c.price > 0';
    if (!empty($opts['featured'])) $where[] = 'c.is_featured = 1';
    $order = match ($opts['sort'] ?? 'newest') {
        'popular' => 'student_count DESC, c.id DESC',
        'rating' => 'rating DESC, review_count DESC',
        'price_asc' => 'c.price ASC',
        'price_desc' => 'c.price DESC',
        default => 'c.id DESC',
    };
    $sql = ' FROM courses c JOIN users u ON u.id = c.instructor_id LEFT JOIN categories cat ON cat.id = c.category_id WHERE ' . implode(' AND ', $where);
    $total = (int) val('SELECT COUNT(*)' . $sql, $p);
    $limit = (int) ($opts['limit'] ?? 12); $offset = (int) ($opts['offset'] ?? 0);
    $list = rows('SELECT ' . COURSE_STATS_SQL . ' ' . $sql . " ORDER BY $order LIMIT $limit OFFSET $offset", $p);
    return ['total' => $total, 'courses' => $list];
}

/* ---- CMS page rendering with dynamic LMS blocks ---- */

/** Replace {{base}} tokens used by builder blocks so links work in any sub-folder. */
function resolve_tokens(string $html): string
{
    return str_replace(['{{base}}', '{{site_name}}'], [base_path(), e(setting('site_name', 'LMS'))], $html);
}

function render_page_blocks(array $blocks): string
{
    $out = '';
    foreach ($blocks as $b) {
        $html = (string) ($b['html'] ?? '');
        if (preg_match('/^\s*<(?:section|div)\b[^>]*\sdata-lms="([a-z-]+)"([^>]*)>/i', $html, $m)) {
            $attrs = [];
            if (preg_match_all('/\sdata-([a-z-]+)="([^"]*)"/i', $m[0], $am, PREG_SET_ORDER)) {
                foreach ($am as $a) $attrs[$a[1]] = html_entity_decode($a[2], ENT_QUOTES);
            }
            $out .= render_dynamic_block($m[1], $attrs) . "\n";
        } else {
            $out .= resolve_tokens($html) . "\n";
        }
    }
    return $out;
}

function dyn_section_open(array $a, string $title, string $sub = ''): string
{
    $bg = in_array($a['bg'] ?? '', ['alt', 'dark', 'primary'], true) ? ' data-bg="' . e($a['bg']) . '"' : '';
    return '<section class="wb-section"' . $bg . '><div class="wb-container"><div class="wb-header"><h2>' . e($a['title'] ?? $title) . '</h2>' .
        (($a['subtitle'] ?? $sub) !== '' ? '<p class="wb-lead">' . e($a['subtitle'] ?? $sub) . '</p>' : '') . '</div>';
}

function render_dynamic_block(string $type, array $a): string
{
    $limit = max(1, min(24, (int) ($a['limit'] ?? 6)));
    switch ($type) {
        case 'courses':
            $res = published_courses(['limit' => $limit, 'category' => $a['category'] ?? '', 'sort' => $a['sort'] ?? 'newest', 'featured' => ($a['sort'] ?? '') === 'featured']);
            $h = dyn_section_open($a, 'Popular courses', 'Learn from the best instructors');
            if (!$res['courses']) return $h . '<p class="wb-muted" style="text-align:center">No courses published yet.</p></div></section>';
            $h .= '<div class="wb-grid lms-grid" data-cols="3">';
            foreach ($res['courses'] as $c) $h .= course_card($c);
            return $h . '</div><p style="text-align:center;margin-top:2rem"><a class="wb-btn wb-btn--outline" href="' . e(url('courses.php')) . '">Browse all courses</a></p></div></section>';
        case 'categories':
            $cats = rows("SELECT cat.*, (SELECT COUNT(*) FROM courses c WHERE c.category_id = cat.id AND c.status='published') AS n FROM categories cat ORDER BY name LIMIT $limit");
            $h = dyn_section_open($a, 'Browse by category');
            $h .= '<div class="wb-grid" data-cols="4">';
            foreach ($cats as $c) $h .= '<a class="wb-card wb-category" href="' . e(url('courses.php?category=' . $c['slug'])) . '"><h3>' . e($c['name']) . '</h3><p class="wb-muted wb-small">' . (int) $c['n'] . ' courses</p></a>';
            return $h . '</div></div></section>';
        case 'instructors':
            $ins = rows("SELECT u.*, (SELECT COUNT(*) FROM courses c WHERE c.instructor_id = u.id AND c.status='published') AS n FROM users u WHERE u.role='instructor' AND u.status='active' HAVING n > 0 ORDER BY n DESC LIMIT $limit");
            $h = dyn_section_open($a, 'Meet our instructors');
            $h .= '<div class="wb-grid" data-cols="4">';
            foreach ($ins as $u) $h .= '<a class="wb-card wb-person" href="' . e(url('instructor.php?id=' . $u['id'])) . '" style="text-align:center;text-decoration:none;color:inherit"><img class="wb-avatar" style="margin:0 auto 12px;width:88px;height:88px;border-radius:50%" src="' . e(avatar_url($u)) . '" alt=""><h3>' . e($u['name']) . '</h3><p class="wb-muted wb-small">' . e($u['headline'] ?: ((int) $u['n'] . ' courses')) . '</p></a>';
            return $h . '</div></div></section>';
        case 'stats':
            $s = [
                ['Courses', (int) val("SELECT COUNT(*) FROM courses WHERE status='published'")],
                ['Students', (int) val("SELECT COUNT(*) FROM users WHERE role='student'")],
                ['Instructors', (int) val("SELECT COUNT(*) FROM users WHERE role='instructor' AND status='active'")],
                ['Enrollments', (int) val("SELECT COUNT(*) FROM enrollments")],
            ];
            $h = '<section class="wb-section" data-bg="' . e(in_array($a['bg'] ?? '', ['alt', 'dark', 'primary'], true) ? $a['bg'] : 'primary') . '" data-pad="md"><div class="wb-container"><div class="wb-grid" data-cols="4" style="text-align:center">';
            foreach ($s as [$l, $n]) $h .= '<div class="wb-stat"><strong>' . number_format($n) . '</strong><span>' . e($l) . '</span></div>';
            return $h . '</div></div></section>';
        case 'contact':
            return '<section class="wb-section" data-width="narrow"><div class="wb-container"><form class="lms-form" action="' . e(url('contact.php')) . '" method="post">' . csrf_field() .
                '<label>Your name<input type="text" name="name" required maxlength="120"></label><label>Email<input type="email" name="email" required></label>' .
                '<label>Message<textarea name="message" rows="6" required maxlength="5000"></textarea></label>' .
                '<input type="text" name="website" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px" aria-hidden="true">' .
                '<button class="wb-btn" type="submit">Send message</button></form></div></section>';
        case 'search':
            return '<section class="wb-section" data-bg="' . e(in_array($a['bg'] ?? '', ['alt', 'dark', 'primary'], true) ? $a['bg'] : 'alt') . '"><div class="wb-container" style="text-align:center"><h2>' . e($a['title'] ?? 'Find your next course') . '</h2>' .
                '<form class="lms-search" action="' . e(url('courses.php')) . '" method="get"><input type="search" name="q" placeholder="Search courses…" aria-label="Search courses"><button class="wb-btn" type="submit">Search</button></form></div></section>';
    }
    return '';
}
