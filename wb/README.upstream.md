# Website Builder

## Purpose

A lightweight, **dependency-free visual page builder** for creating HTML websites without writing code, and a
**reusable starter kit** for building your own page-builder products later.

The project has two goals:

1. **A working builder** – drag components from a sidebar onto a page, edit text and images inline, manage multiple
   pages, preview on desktop/tablet/mobile, and export clean static HTML.
2. **A reusable theme** – `theme/theme.css` holds every design decision (colours, fonts, radius, spacing) as CSS
   variables and styles every component. Copy it into a new project, change the tokens, and the whole site is re-skinned.

It is inspired by popular WordPress page builders (Elementor, Beaver Builder, Divi, Gutenberg, Brizy, Bricks, etc.),
reduced to the essentials: a **Pages / Components / Theme** left sidebar, a live canvas, and an inspector on the right.

## How to run

**Requirements:** any modern browser (Chrome, Edge, Firefox, Safari). No Node, PHP, Python or database needed.

**Option 1 — just open it (simplest)**
1. Download or clone this repository.
2. Double-click `index.html` (or drag it into a browser window).
3. Start building: add components from the left sidebar, click text to edit, use the inspector on the right.

**Option 2 — local server (recommended for the zip export)**
Use any static server. Pick one you already have:
```bash
# VS Code: install "Live Server", right-click index.html -> Open with Live Server
npx serve .                    # Node
python3 -m http.server 8080    # Python, then open http://localhost:8080
```

**Option 3 — host it online**
Upload the whole folder to any static host (GitHub Pages, Netlify, Vercel, any web hosting). It is plain HTML/CSS/JS.

**Using it**
1. **Pages** tab: add pages or start from a template. **Components** tab: drag sections and elements onto the canvas. **Media** tab: upload images. **Theme** tab: change colours, fonts and spacing.
2. Click **Preview** to test menus, sliders and the cart. Use **Undo/Redo** at the top.
3. **Export ▾** → Website (.zip) for a ready-to-host site, or a single page `.html`.
4. Upload the exported files to your host. Keep the `theme/` and `assets/` folders next to the HTML files.

**Notes**
- Your work autosaves in the browser (localStorage). Use **Export → Project (.json)** to back it up or move it to another browser/computer.
- The zip export and inlined CSS need the builder served over `http://` (Option 2/3). When opened as a plain file, use "All pages (.html files)" instead, and keep the `theme/` folder beside them.
- Google Fonts in some theme presets need an internet connection.

## Project structure

| Path | Purpose |
|---|---|
| `theme/theme.css` | **The reusable theme.** Design tokens (`--wb-*`) + styles for every component. |
| `theme/theme.js` | Tiny behaviour script (nav toggle, tabs, pricing toggle, carousel, multi-step form, countdown). Included in exports. |
| `src/components.js` | Component library. Push to `WB.components` to add your own. |
| `src/theme-presets.js` | Theme presets (token overrides). |
| `src/templates.js` | Full-page templates. |
| `src/builder.js` | Editor core: pages, blocks, canvas, inspector, theme editor, history, export. |
| `src/text-toolbar.js` | Text designer. |
| `src/nesting.js` | Nested drop zones and element handles. |
| `src/media.js` | Image library, image tools, resize handle, cropper. |
| `src/widgets.js` | Video, embed, map, slider, countdown, cart settings. |
| `builder/` | Editor UI styles (`builder.css`) and canvas-only styles (`canvas.css`); not shipped in exported pages. |

## Builder features

- **Pages sidebar** – add, duplicate, delete pages; file name (slug), SEO title, meta description; link pages with `slug.html`.
- **Components sidebar** – searchable, grouped by category; drag onto the canvas or click to insert; reorder via the block handle.
- **Theme sidebar** – colours, fonts (Google Fonts), corner radius, content width, section spacing, presets, custom CSS; download `theme.css`.
- **Inspector** – section background, padding, alignment, width, anchor ID, raw HTML editing; link / image / button settings for the selected element.
- **Canvas** – inline text editing, responsive device preview, preview mode, undo/redo, autosave (localStorage).
- **Export** – current page, all pages, **a ready-to-host .zip** (pages + theme; needs http serving), theme CSS, or project JSON (import supported).

## Components

**65 sections** (Components tab) and **23 elements** (Elements group, droppable into any column, container or card).

| Category | Components |
|---|---|
| Navigation | Navbar (hamburger, sticky, transparent), Announcement bar, Breadcrumbs |
| Hero | Centered, Split, Background image, **Video background**, With form, Video |
| Content | Text block, Image + text, Full image, Gallery, Tabs, Timeline, Table, Pull quote, Code block |
| Media | **Image slider/carousel** (arrows, dots, autoplay), **Logo marquee**, **Before/After slider**, **Video (YouTube / Vimeo / MP4)**, **HTML / embed code** |
| Features | Features grid, Feature rows, Stats, Steps, Comparison table, Icon list, Bento grid |
| Social proof | Logo cloud, Testimonials, Testimonial carousel, Rating summary, Case studies, Team, Awards & badges |
| Conversion | Pricing, Pricing monthly/yearly toggle, CTA, Newsletter, Contact form, Multi-step form, Countdown, Lead-magnet banner, FAQ, **Popup / modal** |
| Contact | Map embed (address), Opening hours, Social links |
| Blog | Post list, Featured post, Author box |
| E-commerce | Product grid & detail with working **add-to-cart**, Category cards, **Shopping cart** (browser-stored, checkout link) |
| Footer | Footer, Footer · Simple |
| Layout | Columns (1–4, nestable), Empty container, Content + sidebar, Spacer, Divider, Custom HTML |

**Elements** (drag into columns/containers): Heading 1–3, Paragraph, Lead, Bullet list, Button, Image, Video, Card, Icon box, Quote, Badge, Code, Divider, Spacer, 2/3 columns, Embed code, Map, Social links, Cart button, Before/After.

**Page templates:** Landing, Business/About, Portfolio, Blog home, Online shop, Pricing (`src/templates.js`).
**My sections:** save any section with ★ Save and reuse it from the Components tab.

## Editing tools

- **Text designer** (bar above the canvas): paragraph / Heading 1–6 / quote, **bold, italic, underline, strikethrough, inline code, subscript, superscript**, link / unlink, bullet & numbered list, alignment, text colour, highlight, font family, size, line height, clear formatting.
- **Nested drag & drop:** drop elements into any column / container / card; hover any nested element for a handle to **move, duplicate, delete or select its parent**.
- **Images:** upload (auto-resized), **Media library**, **resize handle on the canvas**, width / align / aspect ratio / fit / corners in the inspector, and a **cropper** (free or fixed ratio, rotate, output width and format).
- **Video:** paste a YouTube, Vimeo or .mp4/.webm link (autoplay / loop / controls). **HTML embed:** paste any iframe or widget code (scripts run on the exported page).
- **Widgets:** slider autoplay and slides, countdown date, popup auto-open, cart checkout link, column count.
- **Export:** page HTML, all pages, **zip** (pages + `theme/` + extracted `assets/` images), theme.css, project JSON.

## Phase status
Phases 1–5 of the roadmap are implemented. Not possible without a server: real payment processing (use a payment-link URL as the cart checkout link) and form submission handling (point the form `action` at your form service).

## Responsive
- **Exported pages** are mobile-first responsive (grids collapse, navbar becomes a hamburger menu).
- **The editor itself** adapts to tablets and phones: the Pages/Components/Theme sidebar and the Inspector become slide-in drawers (☰ and ⚙ buttons).
  Touch devices use tap-to-add and the ↑ ↓ buttons, since HTML5 drag & drop is not available on touch screens.

## Add a component

```js
WB.components.push({
  id: "my-block", label: "My block", category: "Content", icon: "★",
  html: () => `<section class="wb-section"><div class="wb-container"><h2>Hello</h2></div></section>`,
});
WB.componentById["my-block"] = WB.components.at(-1);
```

Section options are plain data-attributes handled by the theme:
`data-bg="alt|dark|primary"`, `data-pad="none|sm|md|lg|xl"`, `data-align="left|center|right"`, `data-width="narrow|wide|full"`.

## Reuse the theme in a new project

1. Copy `theme/theme.css` into your project and link it: `<link rel="stylesheet" href="theme.css">`.
2. Edit the variables in the `:root` block (or use the Theme tab and **Download theme.css**).
3. Add `theme.js` if you use interactive components (tabs, hamburger, etc.).
4. Use the `wb-*` classes from `src/components.js` as markup examples.
