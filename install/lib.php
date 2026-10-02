<?php
/** Installer logic shared by the web wizard and the CLI helper. */

function install_default_pages(): array
{
    $home = [
        '<section class="wb-section wb-hero wb-hero--center" data-bg="alt" data-pad="xl"><div class="wb-container"><span class="wb-eyebrow">Online learning platform</span><h1>Learn new skills, at your own pace</h1><p class="wb-lead" style="margin-inline:auto">Expert-led courses, hands-on quizzes and certificates you can share. Start for free today.</p><div class="wb-actions"><a class="wb-btn" href="{{base}}/courses.php">Browse courses</a><a class="wb-btn wb-btn--outline" href="{{base}}/register.php">Create free account</a></div></div></section>',
        '<section class="wb-section" data-lms="stats" data-bg="primary"></section>',
        '<section class="wb-section" data-lms="courses" data-limit="6" data-sort="newest" data-title="Latest courses" data-subtitle="Fresh from our instructors"></section>',
        '<section class="wb-section" data-lms="categories" data-limit="8" data-title="Browse by category" data-bg="alt"></section>',
        '<section class="wb-section"><div class="wb-container"><div class="wb-header"><span class="wb-eyebrow">Why learn here</span><h2>Built for real progress</h2></div><div class="wb-grid" data-cols="3"><div class="wb-card"><span class="wb-icon">🎬</span><h3>Video &amp; text lessons</h3><p>Learn through short, focused lessons you can revisit any time.</p></div><div class="wb-card"><span class="wb-icon">📝</span><h3>Quizzes</h3><p>Check your understanding with auto-graded quizzes after each topic.</p></div><div class="wb-card"><span class="wb-icon">🏆</span><h3>Certificates</h3><p>Finish a course and download a verifiable certificate of completion.</p></div></div></div></section>',
        '<section class="wb-section" data-lms="instructors" data-limit="4" data-title="Learn from the best" data-bg="alt"></section>',
        '<section class="wb-section" data-bg="primary" data-align="center"><div class="wb-container"><h2>Share your knowledge</h2><p class="wb-lead" style="margin-inline:auto">Become an instructor, build your course and reach learners worldwide.</p><div class="wb-actions"><a class="wb-btn" href="{{base}}/register.php?as=instructor">Become an instructor</a></div></div></section>',
    ];
    $about = [
        '<section class="wb-section" data-bg="alt" data-align="center"><div class="wb-container"><span class="wb-eyebrow">About us</span><h1>Learning that fits your life</h1><p class="wb-lead" style="margin-inline:auto">We help people build practical skills with clear, structured courses taught by practitioners.</p></div></section>',
        '<section class="wb-section"><div class="wb-container"><div class="wb-grid" data-cols="3"><div class="wb-card"><h3>Our mission</h3><p>Make high-quality education accessible to anyone, anywhere.</p></div><div class="wb-card"><h3>Our approach</h3><p>Short lessons, practical exercises and quizzes that reinforce what you learn.</p></div><div class="wb-card"><h3>Our community</h3><p>Instructors and learners helping each other grow.</p></div></div></div></section>',
    ];
    $contact = [
        '<section class="wb-section" data-bg="alt" data-align="center"><div class="wb-container"><h1>Contact us</h1><p class="wb-lead" style="margin-inline:auto">Questions about a course or your account? Send us a message.</p></div></section>',
        '<section class="wb-section" data-lms="contact" data-width="narrow"></section>',
    ];
    $wrap = fn(array $htmls) => json_encode(array_map(fn($h) => ['id' => bin2hex(random_bytes(4)), 'type' => 'custom', 'html' => $h], $htmls));
    return [
        ['home', 'Home', 'Learn new skills online', $wrap($home), 0, 0],
        ['about', 'About', 'About our learning platform', $wrap($about), 1, 1],
        ['contact', 'Contact', 'Get in touch', $wrap($contact), 1, 2],
    ];
}

function install_seed_demo(PDO $pdo, int $adminId): void
{
    $hash = password_hash('instructor123', PASSWORD_DEFAULT);
    $pdo->prepare("INSERT INTO users (name,email,password_hash,role,headline,bio) VALUES (?,?,?,?,?,?)")
        ->execute(['Dana Whitfield', 'instructor@example.com', $hash, 'instructor', 'Senior web developer & trainer', 'Dana has taught web development to over 10,000 students.']);
    $ins = (int) $pdo->lastInsertId();
    $pdo->prepare("INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,?)")
        ->execute(['Sam Student', 'student@example.com', password_hash('student123', PASSWORD_DEFAULT), 'student']);
    $stu = (int) $pdo->lastInsertId();

    foreach ([['Web Development', 'web-development'], ['Design', 'design'], ['Business', 'business'], ['Data Science', 'data-science']] as [$n, $s]) {
        $pdo->prepare('INSERT INTO categories (name, slug) VALUES (?,?)')->execute([$n, $s]);
    }
    $cat = (int) $pdo->query("SELECT id FROM categories WHERE slug='web-development'")->fetchColumn();

    $mk = function (string $title, string $short, float $price, string $level, int $featured) use ($pdo, $ins, $cat) {
        $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $title), '-'));
        $pdo->prepare("INSERT INTO courses (instructor_id,category_id,title,slug,short_desc,description,requirements,outcomes,level,price,status,is_featured) VALUES (?,?,?,?,?,?,?,?,?,?, 'published', ?)")
            ->execute([$ins, $cat, $title, $slug, $short, "<p>$short</p><p>This course walks you through the fundamentals step by step with practical examples and a final quiz.</p>", "A computer with internet access\nNo prior experience needed", "Understand the core concepts\nBuild a small real-world project\nFeel confident to keep learning", $level, $price, $featured]);
        return (int) $pdo->lastInsertId();
    };
    $c1 = $mk('HTML & CSS for Beginners', 'Build your first responsive web pages from scratch.', 0, 'beginner', 1);
    $mk('JavaScript Essentials', 'Learn the language of the web with hands-on exercises.', 49, 'intermediate', 1);
    $mk('UI Design Fundamentals', 'Principles of layout, colour and typography.', 29, 'beginner', 0);

    $t1 = $pdo->prepare('INSERT INTO topics (course_id,title,position) VALUES (?,?,?)');
    $les = $pdo->prepare('INSERT INTO lessons (topic_id,course_id,kind,title,content,video_url,duration_min,is_preview,position) VALUES (?,?,?,?,?,?,?,?,?)');
    $t1->execute([$c1, 'Getting started', 1]); $tid = (int) $pdo->lastInsertId();
    $les->execute([$tid, $c1, 'lesson', 'What is HTML?', '<p>HTML is the markup language that gives web pages their structure. Elements like <code>&lt;h1&gt;</code> and <code>&lt;p&gt;</code> describe the content.</p>', 'https://www.youtube.com/watch?v=UB1O30fR-EE', 12, 1, 1]);
    $les->execute([$tid, $c1, 'lesson', 'Your first page', '<p>Create an <code>index.html</code> file and add a heading, a paragraph and a link.</p>', '', 8, 0, 2]);
    $t1->execute([$c1, 'Styling with CSS', 2]); $tid2 = (int) $pdo->lastInsertId();
    $les->execute([$tid2, $c1, 'lesson', 'Selectors and colours', '<p>CSS rules select elements and change how they look: colour, spacing, fonts and more.</p>', '', 15, 0, 1]);
    $les->execute([$tid2, $c1, 'quiz', 'Quick quiz: HTML & CSS', '', '', 5, 0, 2]);
    $quizId = (int) $pdo->lastInsertId();
    $qq = $pdo->prepare('INSERT INTO quiz_questions (lesson_id,question,type,position) VALUES (?,?,?,?)');
    $oo = $pdo->prepare('INSERT INTO quiz_options (question_id,label,is_correct) VALUES (?,?,?)');
    $qq->execute([$quizId, 'Which tag creates the largest heading?', 'single', 1]); $q1 = (int) $pdo->lastInsertId();
    foreach ([['<h1>', 1], ['<h6>', 0], ['<head>', 0]] as [$l, $c]) $oo->execute([$q1, $l, $c]);
    $qq->execute([$quizId, 'Which of these are CSS properties?', 'multiple', 2]); $q2 = (int) $pdo->lastInsertId();
    foreach ([['color', 1], ['margin', 1], ['href', 0]] as [$l, $c]) $oo->execute([$q2, $l, $c]);

    $pdo->prepare("INSERT INTO enrollments (user_id,course_id) VALUES (?,?)")->execute([$stu, $c1]);
    $pdo->prepare("INSERT INTO reviews (course_id,user_id,rating,comment) VALUES (?,?,?,?)")->execute([$c1, $stu, 5, 'Clear and beginner friendly. Loved the quiz!']);
    $pdo->prepare("INSERT INTO coupons (code,type,value) VALUES ('WELCOME10','percent',10)")->execute();
}

/**
 * @param array{host:string,port:int,name:string,user:string,pass:string} $db
 * @return string|null error message, or null on success
 */
function run_install(array $db, array $admin, string $siteName, bool $demo): ?string
{
    try {
        $pdo = new PDO(sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $db['host'], $db['port'], $db['name']), $db['user'], $db['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    } catch (PDOException $e) {
        return 'Could not connect to the database: ' . $e->getMessage();
    }
    if ($pdo->query("SHOW TABLES LIKE 'users'")->fetchColumn()) return 'This database already contains an LMS installation (a "users" table exists). Use an empty database.';

    $sql = file_get_contents(APP_ROOT . '/database/schema.sql');
    foreach (array_filter(array_map('trim', explode(";\n", $sql))) as $stmt) {
        $stmt = trim(preg_replace('/^--.*$/m', '', $stmt));
        if ($stmt !== '') $pdo->exec($stmt);
    }
    $pdo->prepare("INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,'admin')")
        ->execute([$admin['name'], $admin['email'], password_hash($admin['password'], PASSWORD_DEFAULT)]);
    $adminId = (int) $pdo->lastInsertId();

    $set = $pdo->prepare('INSERT INTO settings (k,v) VALUES (?,?)');
    foreach ([
        'site_name' => $siteName, 'tagline' => 'Learn new skills online', 'currency' => '$', 'allow_registration' => '1',
        'instructor_approval' => '1', 'commission_pct' => '20', 'payment_mode' => 'demo', 'contact_email' => $admin['email'],
        'theme_css' => '', 'theme_fonts' => '', 'custom_css' => '',
    ] as $k => $v) $set->execute([$k, $v]);

    $pg = $pdo->prepare("INSERT INTO pages (slug,title,description,blocks_json,status,show_in_menu,menu_order) VALUES (?,?,?,?, 'published', ?, ?)");
    foreach (install_default_pages() as $p) $pg->execute($p);

    if ($demo) install_seed_demo($pdo, $adminId);

    $cfg = "<?php\ndefined('APP_ROOT') || exit;\nreturn " . var_export([
        'db' => ['host' => $db['host'], 'port' => $db['port'], 'name' => $db['name'], 'user' => $db['user'], 'pass' => $db['pass']],
        'debug' => false,
        'timezone' => 'UTC',
    ], true) . ";\n";
    if (file_put_contents(APP_ROOT . '/config.php', $cfg) === false) {
        return 'Database installed, but config.php could not be written. Create it manually with this content:<pre>' . htmlspecialchars($cfg) . '</pre>';
    }
    @chmod(APP_ROOT . '/config.php', 0640);
    return null;
}
