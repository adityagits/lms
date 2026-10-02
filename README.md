# LMS — PHP + MySQL learning platform with a visual page builder

A self-hosted LMS in the spirit of **Tutor LMS**: a role-based back office (admin / instructor), a public course
site, a student learning area, and a drag-and-drop **page builder** (the
[website-builder](https://github.com/adityagits/website-builder) project, vendored in `wb/`) for designing the home
page and any other public page.

No framework, no Composer, no build step: PHP 8.1+ and MySQL 5.7+/MariaDB 10.3+.

## Features

**Public site** — builder-designed home/about/contact pages · course catalog with search, category, level, price and
sort filters · course detail (curriculum, outcomes, reviews, instructor, free-preview lessons) · instructor profiles ·
registration (student or instructor) · sign in.

**Student area** — My learning dashboard with progress · lesson player (video/YouTube/Vimeo/text) · auto-graded quizzes
(single & multiple choice, pass mark) · course completion → **verifiable certificate** · reviews & ratings ·
announcements · order history · profile & password.

**Instructor / admin back office** (`/admin/`, one shell, role-filtered menu)
- Dashboard: stats, 6-month enrollment chart, top courses, pending approvals
- Courses: create/edit, thumbnail upload, pricing + sale price, **topic → lesson/quiz curriculum builder**, reorder,
  review workflow (draft → pending → published/rejected), featured flag
- Quiz builder with questions & correct answers
- Students, enrollments (enroll/cancel by email), reviews moderation, course announcements
- Orders & earnings (with platform commission), coupons (percent/fixed, max uses, expiry)
- Admin only: categories, users (create, role, approve/block, reset password, delete), pages & builder, contact
  messages, settings (site name, currency, registration, instructor approval, payment mode, commission)

**Page builder** (admin → *Pages* → *Design*) — all 65 sections/23 elements of website-builder plus LMS blocks that
render **live data** on the public site: *Course grid, Categories, Instructors, Platform stats, Course search,
Contact form*. Autosave to MySQL, Ctrl+S, theme (colours/fonts/spacing/custom CSS) applied site-wide, uploaded
images stored as files in `uploads/pages/`.

## Install

1. Create an empty MySQL/MariaDB database and user.
2. Upload this folder to your web root (or a sub-folder — URLs are auto-detected).
3. Open `/install/` in the browser, enter DB details and the admin account (tick *demo content* to try it out).
4. **Delete the `install/` folder** afterwards.

CLI alternative: `php install/cli.php HOST PORT DB USER PASS "Site name" admin@email "Admin Name" password [demo]`

Demo content creates `instructor@example.com` / `instructor123` and `student@example.com` / `student123` —
**delete or change these before going live.**

Quick local run: `php -S 127.0.0.1:8080 -t .` then open <http://127.0.0.1:8080/>.

### Web server notes
- **Apache**: the shipped `.htaccess` files block `includes/`, `database/`, `config.php`, and PHP execution in `uploads/`.
- **Nginx**: add equivalents, e.g.
  ```nginx
  location ~ ^/(includes|database|install)/ { deny all; }   # remove install after setup
  location ~ ^/uploads/.*\.php$ { deny all; }
  location = /config.php { deny all; }
  ```
- `uploads/` must be writable by PHP. Set `post_max_size`/`upload_max_filesize` ≥ 8M if builder pages contain large images.

## Layout

| Path | Purpose |
|---|---|
| `index.php`, `courses.php`, `course.php`, `page.php`, `instructor.php` | Public pages |
| `login.php`, `register.php`, `logout.php`, `account.php` | Auth & profile |
| `checkout.php`, `my-courses.php`, `learn.php`, `certificate.php` | Purchase & learning |
| `admin/` | Admin + instructor back office; `admin/builder.php` hosts the page builder, `admin/builder-api.php` saves it |
| `includes/` | Bootstrap, PDO helpers, auth, domain logic (`lms.php`), layouts |
| `wb/` | Vendored website-builder (`theme/` is the shared public theme) + `lms-bridge.js`, `lms-components.js` |
| `database/schema.sql` | Schema (also run by the installer) |
| `assets/` | `lms.css` (public), `admin.css`, `admin.js` |

## How the builder is wired in

`admin/builder.php` seeds `window.WB_SEED` with one CMS page (blocks from the `pages` table) and the site theme.
The builder (two small patches in `wb/src/builder.js`) calls `window.WB_ON_SAVE`, which `wb/lms-bridge.js` debounces and
POSTs to `admin/builder-api.php` (admin + CSRF token required). The API moves base64 images to files, rebuilds the theme
CSS from whitelisted `--wb-*` tokens, and stores blocks as JSON. Pages render through the same `wb/theme/theme.css`, so
the editor is WYSIWYG. Blocks with `data-lms="…"` are swapped for live HTML in `render_dynamic_block()`
(`includes/lms.php`). Links use a portable `{{base}}` token so the site works in a sub-folder.

Add your own live block: push a component in `wb/lms-components.js` with a `data-lms="mytype"` root, then add a `case`
in `render_dynamic_block()`.

## Security notes
Prepared statements everywhere · CSRF tokens on every POST (and the builder API) · `password_hash` · session
regeneration on login, `HttpOnly`/`SameSite` cookies · login throttling (8 failures / 10 min / IP) · output escaping ·
instructor-written HTML is sanitised (`clean_html`) · image uploads validated by content and stored outside any
executable path · ownership checks on every course/lesson action. Builder pages are **admin-authored raw HTML**
(embeds may contain scripts) — only give admin to trusted people.

## Not included (by design / next steps)
Real payment gateway (checkout is *demo* or *manual-confirmation*), email sending (password reset, receipts),
assignments/Q&A, drip content. These slot into `checkout.php` and `includes/lms.php`.
