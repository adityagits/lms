/* Component library. Add your own by pushing to WB.components.
   Each component: { id, label, category, icon, html() } where html() returns the
   section markup. Styling comes ONLY from theme/theme.css (`wb-*` classes). */
(function () {
  const WB = (window.WB = window.WB || {});

  // inline SVG placeholder images (no network needed)
  const ph = (w, h, label = "Image", c1 = "#e0e7ff", c2 = "#c7d2fe") =>
    "data:image/svg+xml;utf8," +
    encodeURIComponent(
      `<svg xmlns="http://www.w3.org/2000/svg" width="${w}" height="${h}" viewBox="0 0 ${w} ${h}">` +
        `<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="${c1}"/><stop offset="1" stop-color="${c2}"/></linearGradient></defs>` +
        `<rect width="100%" height="100%" fill="url(#g)"/>` +
        `<text x="50%" y="50%" font-family="sans-serif" font-size="${Math.round(h / 12)}" fill="#6366f1" text-anchor="middle" dominant-baseline="middle">${label}</text></svg>`
    );
  const avatar = (n, c) => ph(160, 160, n, c, "#e2e8f0");
  WB.placeholder = ph;

  WB.categories = [
    "Elements", "Navigation", "Hero", "Content", "Media", "Features", "Social proof", "Conversion", "Contact", "Blog", "E-commerce", "Footer", "Layout",
  ];

  const C = [];
  const add = (id, label, category, icon, html) => C.push({ id, label, category, icon, html });

  add("navbar", "Navbar", "Navigation", "☰", () => `
<header class="wb-section wb-nav" data-pad="none"><div class="wb-container">
  <a class="wb-nav__brand" href="index.html">Brand</a>
  <button class="wb-nav__toggle" type="button" data-wb-nav-toggle aria-label="Toggle menu" aria-expanded="false">☰</button>
  <ul class="wb-nav__links">
    <li><a href="index.html">Home</a></li>
    <li><a href="#features">Features</a></li>
    <li><a href="#pricing">Pricing</a></li>
    <li><a href="#contact">Contact</a></li>
    <li><a class="wb-btn" href="#contact">Get started</a></li>
  </ul>
</div></header>`);

  add("hero-center", "Hero · Centered", "Hero", "✦", () => `
<section class="wb-section wb-hero wb-hero--center" data-pad="xl"><div class="wb-container">
  <span class="wb-eyebrow">Introducing</span>
  <h1>Build beautiful websites without writing code</h1>
  <p class="wb-lead">Drag, drop and publish. A clean, fast starting point for any idea you want to put online.</p>
  <div class="wb-actions"><a class="wb-btn" href="#">Get started</a><a class="wb-btn wb-btn--outline" href="#">Learn more</a></div>
</div></section>`);

  add("hero-split", "Hero · Split", "Hero", "◧", () => `
<section class="wb-section wb-hero" data-pad="lg"><div class="wb-container wb-split">
  <div>
    <span class="wb-eyebrow">New release</span>
    <h1>Launch your next big idea faster</h1>
    <p class="wb-lead">Everything you need to design, customise and ship a modern landing page.</p>
    <div class="wb-actions"><a class="wb-btn" href="#">Start free</a><a class="wb-btn wb-btn--ghost" href="#">Watch demo</a></div>
  </div>
  <img class="wb-media" src="${ph(720, 540, "Hero image")}" alt="Hero image">
</div></section>`);

  add("hero-image", "Hero · Background", "Hero", "▣", () => `
<section class="wb-section wb-hero wb-hero--image wb-hero--center" data-pad="xl" style="background-image:url('${ph(1600, 800, " ", "#312e81", "#0ea5e9")}')"><div class="wb-container">
  <h1>A bold headline over a full-width image</h1>
  <p class="wb-lead">Change the background by editing the section's HTML or style.</p>
  <div class="wb-actions"><a class="wb-btn" href="#">Primary action</a></div>
</div></section>`);

  add("text", "Text block", "Content", "¶", () => `
<section class="wb-section" data-width="narrow"><div class="wb-container">
  <h2>Section heading</h2>
  <p>Write your story here. Click any text to edit it directly on the page. Use the inspector on the right to change the background, spacing and alignment of this section.</p>
  <p>Add a second paragraph, or drop in more components from the sidebar.</p>
</div></section>`);

  add("image-text", "Image + text", "Content", "◫", () => `
<section class="wb-section"><div class="wb-container wb-split">
  <img class="wb-media" src="${ph(640, 480, "Image")}" alt="Image">
  <div>
    <span class="wb-eyebrow">About us</span>
    <h2>Tell visitors what makes you different</h2>
    <p>A short, friendly paragraph that explains your product, service or story. Keep it clear and focused on the value you deliver.</p>
    <div class="wb-actions"><a class="wb-btn" href="#">Read more</a></div>
  </div>
</div></section>`);

  add("image", "Full image", "Content", "🖼", () => `
<section class="wb-section" data-pad="sm"><div class="wb-container">
  <img class="wb-media" src="${ph(1200, 520, "Wide image")}" alt="Wide image">
</div></section>`);

  add("gallery", "Gallery", "Content", "▦", () => `
<section class="wb-section" data-bg="alt"><div class="wb-container">
  <div class="wb-header"><h2>Gallery</h2><p class="wb-lead">A few moments worth sharing.</p></div>
  <div class="wb-grid wb-gallery" data-cols="3">
    <img src="${ph(480, 360, "1")}" alt="Gallery 1"><img src="${ph(480, 360, "2", "#fce7f3", "#fbcfe8")}" alt="Gallery 2"><img src="${ph(480, 360, "3", "#dcfce7", "#bbf7d0")}" alt="Gallery 3">
    <img src="${ph(480, 360, "4", "#fef3c7", "#fde68a")}" alt="Gallery 4"><img src="${ph(480, 360, "5")}" alt="Gallery 5"><img src="${ph(480, 360, "6", "#e0f2fe", "#bae6fd")}" alt="Gallery 6">
  </div>
</div></section>`);

  add("features-grid", "Features grid", "Features", "▥", () => `
<section class="wb-section" id="features"><div class="wb-container">
  <div class="wb-header"><span class="wb-eyebrow">Features</span><h2>Everything you need</h2><p class="wb-lead" style="margin-inline:auto">Powerful building blocks that work great together.</p></div>
  <div class="wb-grid" data-cols="3">
    <div class="wb-card"><span class="wb-icon">⚡</span><h3>Lightning fast</h3><p>Pages are lightweight, clean and quick to load on any device.</p></div>
    <div class="wb-card"><span class="wb-icon">🎨</span><h3>Fully themeable</h3><p>Change colours, fonts and spacing once and the whole site follows.</p></div>
    <div class="wb-card"><span class="wb-icon">📱</span><h3>Responsive</h3><p>Looks great on desktop, tablet and mobile out of the box.</p></div>
    <div class="wb-card"><span class="wb-icon">🧩</span><h3>Modular</h3><p>Mix and match ready-made sections to build any page.</p></div>
    <div class="wb-card"><span class="wb-icon">🔒</span><h3>Secure</h3><p>Static HTML means a tiny attack surface and easy hosting.</p></div>
    <div class="wb-card"><span class="wb-icon">🚀</span><h3>Export anywhere</h3><p>Download plain HTML and host it wherever you like.</p></div>
  </div>
</div></section>`);

  add("features-list", "Feature rows", "Features", "☷", () => `
<section class="wb-section" data-bg="alt"><div class="wb-container wb-split">
  <div><span class="wb-eyebrow">Why us</span><h2>Built for the way you work</h2><p class="wb-lead">Focus on content while the theme takes care of the details.</p></div>
  <div class="wb-stack">
    <div class="wb-card"><h3>✔ Simple to start</h3><p>Pick a section, edit the text, publish.</p></div>
    <div class="wb-card"><h3>✔ Easy to extend</h3><p>Add your own components in a few lines of code.</p></div>
    <div class="wb-card"><h3>✔ Yours to keep</h3><p>No lock-in. Exported pages are plain HTML and CSS.</p></div>
  </div>
</div></section>`);

  add("stats", "Stats", "Features", "％", () => `
<section class="wb-section" data-bg="primary" data-align="center" data-pad="md"><div class="wb-container">
  <div class="wb-grid" data-cols="4">
    <div class="wb-stat"><strong>10k+</strong><p>Happy customers</p></div>
    <div class="wb-stat"><strong>99.9%</strong><p>Uptime</p></div>
    <div class="wb-stat"><strong>120</strong><p>Countries</p></div>
    <div class="wb-stat"><strong>24/7</strong><p>Support</p></div>
  </div>
</div></section>`);

  add("steps", "Steps", "Features", "①", () => `
<section class="wb-section"><div class="wb-container">
  <div class="wb-header"><h2>How it works</h2></div>
  <div class="wb-grid" data-cols="3">
    <div class="wb-card--flat"><span class="wb-icon">1</span><h3>Choose</h3><p>Pick the sections that fit your page.</p></div>
    <div class="wb-card--flat"><span class="wb-icon">2</span><h3>Customise</h3><p>Edit text, images and the theme.</p></div>
    <div class="wb-card--flat"><span class="wb-icon">3</span><h3>Publish</h3><p>Export clean HTML and go live.</p></div>
  </div>
</div></section>`);

  add("logos", "Logo cloud", "Social proof", "◎", () => `
<section class="wb-section" data-pad="sm"><div class="wb-container">
  <p class="wb-muted wb-small" style="text-align:center">Trusted by teams at</p>
  <div class="wb-logos"><span>Acme</span><span>Globex</span><span>Initech</span><span>Umbrella</span><span>Hooli</span></div>
</div></section>`);

  add("testimonials", "Testimonials", "Social proof", "❝", () => `
<section class="wb-section" data-bg="alt"><div class="wb-container">
  <div class="wb-header"><span class="wb-eyebrow">Testimonials</span><h2>Loved by customers</h2></div>
  <div class="wb-grid" data-cols="3">
    ${[["Priya S.", "Founder"], ["Daniel K.", "Designer"], ["Mei L.", "Marketer"]]
      .map(([n, r], i) => `<figure class="wb-card wb-quote" style="margin:0"><blockquote>“This saved us weeks. The result looks professional and was effortless to set up.”</blockquote><div class="wb-person"><img class="wb-avatar" src="${avatar(n[0], ["#e0e7ff", "#fce7f3", "#dcfce7"][i])}" alt="${n}"><div><p><strong>${n}</strong></p><p class="wb-muted wb-small">${r}</p></div></div></figure>`)
      .join("\n    ")}
  </div>
</div></section>`);

  add("team", "Team", "Social proof", "☺", () => `
<section class="wb-section"><div class="wb-container">
  <div class="wb-header"><h2>Meet the team</h2><p class="wb-lead" style="margin-inline:auto">The people behind the product.</p></div>
  <div class="wb-grid" data-cols="4">
    ${["Alex Doe", "Sam Lee", "Jo Park", "Ravi Rao"]
      .map((n, i) => `<div class="wb-team"><img class="wb-avatar" src="${avatar(n[0], ["#e0e7ff", "#fef3c7", "#dcfce7", "#fce7f3"][i])}" alt="${n}"><h3>${n}</h3><p class="wb-muted">Role title</p></div>`)
      .join("\n    ")}
  </div>
</div></section>`);

  add("pricing", "Pricing", "Conversion", "$", () => `
<section class="wb-section" id="pricing"><div class="wb-container">
  <div class="wb-header"><span class="wb-eyebrow">Pricing</span><h2>Simple, transparent pricing</h2></div>
  <div class="wb-grid" data-cols="3">
    <div class="wb-card wb-price"><h3>Starter</h3><div class="wb-price__amount">$0<small>/mo</small></div><ul><li>1 website</li><li>Community support</li><li>Basic sections</li></ul><a class="wb-btn wb-btn--outline wb-btn--block" href="#">Choose Starter</a></div>
    <div class="wb-card wb-price wb-price--featured"><span class="wb-badge">Most popular</span><h3>Pro</h3><div class="wb-price__amount">$19<small>/mo</small></div><ul><li>10 websites</li><li>Priority support</li><li>All sections</li><li>Custom themes</li></ul><a class="wb-btn wb-btn--block" href="#">Choose Pro</a></div>
    <div class="wb-card wb-price"><h3>Business</h3><div class="wb-price__amount">$49<small>/mo</small></div><ul><li>Unlimited websites</li><li>Dedicated support</li><li>Team access</li></ul><a class="wb-btn wb-btn--outline wb-btn--block" href="#">Choose Business</a></div>
  </div>
</div></section>`);

  add("cta", "Call to action", "Conversion", "➜", () => `
<section class="wb-section" data-bg="primary" data-align="center"><div class="wb-container">
  <h2>Ready to get started?</h2>
  <p class="wb-lead" style="margin-inline:auto">Join thousands of people building with us today.</p>
  <div class="wb-actions"><a class="wb-btn" href="#">Create your site</a></div>
</div></section>`);

  add("newsletter", "Newsletter", "Conversion", "✉", () => `
<section class="wb-section" data-bg="alt" data-align="center"><div class="wb-container">
  <h2>Stay in the loop</h2>
  <p class="wb-lead">Get product updates and tips straight to your inbox.</p>
  <form class="wb-inline-form" onsubmit="return false"><input type="email" placeholder="you@example.com" aria-label="Email"><a class="wb-btn" href="#">Subscribe</a></form>
</div></section>`);

  add("contact", "Contact form", "Conversion", "☎", () => `
<section class="wb-section" id="contact"><div class="wb-container wb-split">
  <div><span class="wb-eyebrow">Contact</span><h2>Let's talk</h2><p class="wb-lead">Questions, ideas or feedback? Send us a message and we'll reply within a day.</p><p class="wb-muted">hello@example.com<br>+1 (555) 000-0000</p></div>
  <form class="wb-card wb-form" action="#" method="post">
    <label>Name<input type="text" name="name" placeholder="Your name"></label>
    <label>Email<input type="email" name="email" placeholder="you@example.com"></label>
    <label>Message<textarea name="message" rows="4" placeholder="How can we help?"></textarea></label>
    <button class="wb-btn" type="submit">Send message</button>
  </form>
</div></section>`);

  add("faq", "FAQ", "Conversion", "?", () => `
<section class="wb-section" data-width="narrow"><div class="wb-container">
  <div class="wb-header"><h2>Frequently asked questions</h2></div>
  <div class="wb-faq">
    <details data-open="1"><summary>Can I use my own domain?</summary><p>Yes. Export your pages and host them on any domain you own.</p></details>
    <details><summary>Do I need to know how to code?</summary><p>No. Everything is edited visually, but you can always tweak the HTML.</p></details>
    <details><summary>Can I change the look of the whole site?</summary><p>Yes. Open the Theme tab to adjust colours, fonts and spacing in one place.</p></details>
  </div>
</div></section>`);

  add("footer", "Footer", "Footer", "▁", () => `
<footer class="wb-section wb-footer" data-bg="dark" data-pad="none"><div class="wb-container">
  <div class="wb-footer-grid">
    <div><a class="wb-nav__brand" href="index.html" style="color:inherit">Brand</a><p class="wb-muted" style="margin-top:.75rem">Short description of your company or project.</p></div>
    <div><h4>Product</h4><ul><li><a href="#">Features</a></li><li><a href="#">Pricing</a></li><li><a href="#">Updates</a></li></ul></div>
    <div><h4>Company</h4><ul><li><a href="#">About</a></li><li><a href="#">Careers</a></li><li><a href="#">Contact</a></li></ul></div>
    <div><h4>Legal</h4><ul><li><a href="#">Privacy</a></li><li><a href="#">Terms</a></li></ul></div>
  </div>
  <div class="wb-footer__bottom"><p>© 2026 Brand. All rights reserved.</p><p>Made with Website Builder</p></div>
</div></footer>`);

  add("footer-simple", "Footer · Simple", "Footer", "—", () => `
<footer class="wb-section" data-pad="sm" data-align="center"><div class="wb-container"><p class="wb-muted wb-small" style="margin:0">© 2026 Brand. All rights reserved.</p></div></footer>`);

  add("spacer", "Spacer", "Layout", "↕", () => `<div class="wb-section wb-spacer" data-pad="none"></div>`);
  add("divider", "Divider", "Layout", "―", () => `<section class="wb-section" data-pad="sm"><div class="wb-container"><hr class="wb-divider"></div></section>`);
  add("html", "Custom HTML", "Layout", "</>", () => `
<section class="wb-section" data-pad="sm"><div class="wb-container"><div class="wb-editable"><p>Custom block — use “Edit HTML” in the inspector to paste your own markup.</p></div></div></section>`);


  /* ---- Navigation extras ---- */
  add("announce", "Announcement bar", "Navigation", "📣", () => `
<div class="wb-section wb-announce" data-pad="none"><div class="wb-container"><p>🎉 Launch offer: get 20% off your first year. <a href="#pricing">Claim it →</a></p></div></div>`);

  add("breadcrumbs", "Breadcrumbs", "Navigation", "›", () => `
<nav class="wb-section" data-pad="none" aria-label="Breadcrumb"><div class="wb-container" style="padding-block:14px"><p class="wb-small wb-muted" style="margin:0"><a href="index.html">Home</a> › <a href="#">Section</a> › Current page</p></div></nav>`);

  /* ---- Hero extras ---- */
  add("hero-form", "Hero · With form", "Hero", "▤", () => `
<section class="wb-section wb-hero" data-bg="alt" data-pad="lg"><div class="wb-container wb-split">
  <div><span class="wb-eyebrow">Free trial</span><h1>Start your 14-day free trial</h1><p class="wb-lead">No credit card required. Cancel any time.</p></div>
  <form class="wb-card wb-form" action="#" method="post"><label>Full name<input type="text" placeholder="Your name"></label><label>Work email<input type="email" placeholder="you@company.com"></label><button class="wb-btn" type="submit">Create account</button></form>
</div></section>`);

  add("hero-video", "Hero · Video", "Hero", "🎬", () => `
<section class="wb-section wb-hero wb-hero--center" data-pad="lg"><div class="wb-container">
  <h1>See it in action</h1><p class="wb-lead">A two-minute walkthrough of everything the product can do.</p>
  <div class="wb-video" style="margin-top:2rem"><iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" title="Product video" allowfullscreen loading="lazy"></iframe></div>
</div></section>`);

  /* ---- Content extras ---- */
  add("columns", "Columns (2–4)", "Layout", "▥", () => `
<section class="wb-section"><div class="wb-container"><div class="wb-cols" data-cols="3">
  <div class="wb-col"><h3>Column one</h3><p>Drag elements from the Elements group into any column.</p></div>
  <div class="wb-col"><h3>Column two</h3><p>Columns stack on mobile automatically.</p></div>
  <div class="wb-col"><h3>Column three</h3><p>Change the column count in the inspector.</p></div>
</div></div></section>`);

  add("container", "Empty container", "Layout", "▢", () => `
<section class="wb-section"><div class="wb-container"></div></section>`);

  add("tabs", "Tabs", "Content", "⊟", () => `
<section class="wb-section"><div class="wb-container"><div class="wb-tabs">
  <div class="wb-tabs__list" role="tablist"><button class="is-active" type="button" role="tab">Overview</button><button type="button" role="tab">Details</button><button type="button" role="tab">Support</button></div>
  <div class="wb-tabs__panel is-active"><h3>Overview</h3><p>A short summary of the topic. Click tab labels to edit them.</p></div>
  <div class="wb-tabs__panel"><h3>Details</h3><p>The specifics, specifications or longer explanation go here.</p></div>
  <div class="wb-tabs__panel"><h3>Support</h3><p>How customers can reach you for help.</p></div>
</div></div></section>`);

  add("timeline", "Timeline", "Content", "⏳", () => `
<section class="wb-section" data-width="narrow"><div class="wb-container">
  <div class="wb-header"><h2>Our journey</h2></div>
  <div class="wb-timeline">
    <div><span class="wb-eyebrow">2022</span><h3>The idea</h3><p>Where it all began.</p></div>
    <div><span class="wb-eyebrow">2024</span><h3>First launch</h3><p>We shipped version one to early customers.</p></div>
    <div><span class="wb-eyebrow">2026</span><h3>Going global</h3><p>Thousands of teams now rely on us.</p></div>
  </div>
</div></section>`);

  add("table", "Table", "Content", "▦", () => `
<section class="wb-section"><div class="wb-container"><div class="wb-table-wrap"><table class="wb-table">
  <thead><tr><th>Feature</th><th>Starter</th><th>Pro</th><th>Business</th></tr></thead>
  <tbody><tr><td>Websites</td><td>1</td><td>10</td><td>Unlimited</td></tr><tr><td>Support</td><td>Community</td><td>Priority</td><td>Dedicated</td></tr><tr><td>Custom themes</td><td>—</td><td>✓</td><td>✓</td></tr></tbody>
</table></div></div></section>`);

  add("quote", "Pull quote", "Content", "❞", () => `
<section class="wb-section" data-width="narrow"><div class="wb-container"><blockquote class="wb-pullquote">Design is not just what it looks like. Design is how it works.<cite>— Someone wise</cite></blockquote></div></section>`);

  add("code", "Code block", "Content", "{ }", () => `
<section class="wb-section" data-pad="sm" data-width="narrow"><div class="wb-container"><pre class="wb-code wb-editable">npm install my-package

import { hello } from "my-package";
hello("world");</pre></div></section>`);

  add("compare-table", "Comparison table", "Features", "⇄", () => `
<section class="wb-section" data-bg="alt"><div class="wb-container">
  <div class="wb-header"><h2>Why choose us</h2></div>
  <div class="wb-table-wrap" style="background:var(--wb-bg)"><table class="wb-table"><thead><tr><th></th><th>Us</th><th>Others</th></tr></thead>
  <tbody><tr><td>Setup time</td><td>Minutes</td><td>Days</td></tr><tr><td>Lock-in</td><td>None</td><td>Yes</td></tr><tr><td>Price</td><td>Fair</td><td>High</td></tr></tbody></table></div>
</div></section>`);

  add("icon-list", "Icon list", "Features", "☑", () => `
<section class="wb-section" data-width="narrow"><div class="wb-container">
  <h2>What's included</h2>
  <ul class="wb-hours" style="padding:0"><li><span>✅ Unlimited pages</span></li><li><span>✅ Free SSL &amp; hosting guide</span></li><li><span>✅ Responsive design</span></li><li><span>✅ Theme editor</span></li></ul>
</div></section>`);

  add("bento", "Bento grid", "Features", "▩", () => `
<section class="wb-section"><div class="wb-container"><div class="wb-grid" data-cols="3">
  <div class="wb-card" style="grid-column:span 2"><span class="wb-icon">✨</span><h3>Big feature</h3><p>Give your most important feature the most space.</p></div>
  <div class="wb-card"><span class="wb-icon">⚙</span><h3>Small one</h3><p>Short supporting point.</p></div>
  <div class="wb-card"><span class="wb-icon">🔔</span><h3>Small two</h3><p>Another supporting point.</p></div>
  <div class="wb-card" style="grid-column:span 2"><span class="wb-icon">🌍</span><h3>Wide feature</h3><p>Another highlight spanning two columns.</p></div>
</div></div></section>`);

  /* ---- Social proof extras ---- */
  add("testimonial-carousel", "Testimonial carousel", "Social proof", "⇆", () => `
<section class="wb-section" data-bg="alt"><div class="wb-container" data-wb-carousel>
  <div class="wb-header"><h2>What people say</h2></div>
  <div class="wb-carousel"><div class="wb-carousel__track">
    ${["Priya S.", "Daniel K.", "Mei L.", "Omar R."].map((n, i) => `<figure class="wb-card wb-quote"><div class="wb-stars">★★★★★</div><blockquote>“Fantastic experience from start to finish. Highly recommended.”</blockquote><div class="wb-person"><img class="wb-avatar" src="${avatar(n[0], ["#e0e7ff", "#fce7f3", "#dcfce7", "#fef3c7"][i])}" alt="${n}"><p><strong>${n}</strong></p></div></figure>`).join("\n    ")}
  </div>
  <div class="wb-carousel__nav"><button type="button" data-dir="-1" aria-label="Previous">←</button><button type="button" data-dir="1" aria-label="Next">→</button></div></div>
</div></section>`);

  add("rating", "Rating summary", "Social proof", "★", () => `
<section class="wb-section" data-pad="sm"><div class="wb-container"><div class="wb-rating"><strong>4.9</strong><div><div class="wb-stars">★★★★★</div><p class="wb-muted" style="margin:0">Based on 2,400+ reviews</p></div></div></div></section>`);

  add("case-studies", "Case studies", "Social proof", "▣", () => `
<section class="wb-section"><div class="wb-container">
  <div class="wb-header"><span class="wb-eyebrow">Case studies</span><h2>Real results</h2></div>
  <div class="wb-grid" data-cols="3">
    ${["Acme Co.", "Globex", "Initech"].map((n, i) => `<article class="wb-card wb-case"><img src="${ph(640, 400, n, ["#e0e7ff", "#dcfce7", "#fce7f3"][i], "#c7d2fe")}" alt="${n}"><div class="wb-case__body"><span class="wb-badge">+${(i + 2) * 40}% growth</span><h3 style="margin-top:.75rem">${n}</h3><p class="wb-muted">How they achieved more with less effort.</p><a href="#">Read story →</a></div></article>`).join("\n    ")}
  </div>
</div></section>`);

  add("awards", "Awards & badges", "Social proof", "🏆", () => `
<section class="wb-section" data-pad="sm" data-align="center"><div class="wb-container"><div class="wb-logos"><span class="wb-badge">🏆 Best Product 2026</span><span class="wb-badge">⭐ Top Rated</span><span class="wb-badge">🔒 SOC 2</span><span class="wb-badge">🌱 Carbon neutral</span></div></div></section>`);

  /* ---- Conversion extras ---- */
  add("countdown", "Countdown", "Conversion", "⏱", () => `
<section class="wb-section" data-bg="dark" data-align="center"><div class="wb-container">
  <h2>Big launch in…</h2>
  <div class="wb-countdown" data-wb-countdown="2027-01-01T00:00:00"><div><strong data-u="d">00</strong><span>Days</span></div><div><strong data-u="h">00</strong><span>Hours</span></div><div><strong data-u="m">00</strong><span>Minutes</span></div><div><strong data-u="s">00</strong><span>Seconds</span></div></div>
  <div class="wb-actions"><a class="wb-btn" href="#">Notify me</a></div>
</div></section>`);

  add("pricing-toggle", "Pricing · Monthly/Yearly", "Conversion", "⇋", () => `
<section class="wb-section" data-align="center"><div class="wb-container wb-pricing" data-period="month">
  <div class="wb-header" style="margin-bottom:1.5rem"><h2>Choose your plan</h2></div>
  <div class="wb-billing"><button type="button" class="is-active" data-period="month">Monthly</button><button type="button" data-period="year">Yearly (save 20%)</button></div>
  <div class="wb-grid" data-cols="3" style="text-align:left">
    ${[["Starter", 9, 86], ["Pro", 19, 182], ["Business", 49, 470]].map(([n, m, y], i) => `<div class="wb-card wb-price${i === 1 ? " wb-price--featured" : ""}"><h3>${n}</h3><div class="wb-price__amount"><span data-month>$${m}<small>/mo</small></span><span data-year>$${y}<small>/yr</small></span></div><ul><li>Feature one</li><li>Feature two</li><li>Feature three</li></ul><a class="wb-btn ${i === 1 ? "" : "wb-btn--outline "}wb-btn--block" href="#">Choose ${n}</a></div>`).join("\n    ")}
  </div>
</div></section>`);

  add("multistep-form", "Multi-step form", "Conversion", "➊", () => `
<section class="wb-section" data-bg="alt" data-width="narrow"><div class="wb-container">
  <div class="wb-header"><h2>Get a quote</h2></div>
  <form class="wb-card wb-multistep" action="#" method="post" onsubmit="return false">
    <div class="wb-steps-dots"><i class="is-done"></i><i></i><i></i></div>
    <div class="wb-step is-active"><h3>Step 1 · About you</h3><label class="wb-form">Name<input type="text" placeholder="Your name"></label><div class="wb-step__nav"><span></span><button class="wb-btn" type="button" data-wb-step="1">Next</button></div></div>
    <div class="wb-step"><h3>Step 2 · Your project</h3><label class="wb-form">Describe it<textarea rows="3" placeholder="Tell us more"></textarea></label><div class="wb-step__nav"><button class="wb-btn wb-btn--ghost" type="button" data-wb-step="-1">Back</button><button class="wb-btn" type="button" data-wb-step="1">Next</button></div></div>
    <div class="wb-step"><h3>Step 3 · Contact</h3><label class="wb-form">Email<input type="email" placeholder="you@example.com"></label><div class="wb-step__nav"><button class="wb-btn wb-btn--ghost" type="button" data-wb-step="-1">Back</button><button class="wb-btn" type="submit">Send</button></div></div>
  </form>
</div></section>`);

  add("lead-magnet", "Lead-magnet banner", "Conversion", "📘", () => `
<section class="wb-section" data-bg="primary"><div class="wb-container wb-split">
  <div><h2>Free guide: 10 tips for a better website</h2><p class="wb-lead">Download the checklist our customers use to launch faster.</p><div class="wb-actions"><a class="wb-btn" href="#">Download free</a></div></div>
  <img class="wb-media" src="${ph(560, 380, "eBook", "#ffffff", "#c7d2fe")}" alt="Guide cover">
</div></section>`);

  /* ---- Contact ---- */
  add("map", "Map embed", "Contact", "📍", () => `
<section class="wb-section"><div class="wb-container"><div class="wb-map"><iframe src="https://www.google.com/maps?q=New+York&output=embed" title="Map" loading="lazy"></iframe></div></div></section>`);

  add("hours", "Opening hours", "Contact", "🕒", () => `
<section class="wb-section" data-width="narrow"><div class="wb-container"><h2>Opening hours</h2>
  <ul class="wb-hours"><li><span>Monday – Friday</span><span>9:00 – 18:00</span></li><li><span>Saturday</span><span>10:00 – 14:00</span></li><li><span>Sunday</span><span>Closed</span></li></ul>
</div></section>`);

  add("social", "Social links", "Contact", "@", () => `
<section class="wb-section" data-pad="sm" data-align="center"><div class="wb-container"><ul class="wb-social"><li><a href="#">Facebook</a></li><li><a href="#">Instagram</a></li><li><a href="#">X / Twitter</a></li><li><a href="#">LinkedIn</a></li><li><a href="#">YouTube</a></li></ul></div></section>`);

  /* ---- Blog ---- */
  add("post-list", "Post list", "Blog", "📰", () => `
<section class="wb-section"><div class="wb-container">
  <div class="wb-header"><span class="wb-eyebrow">Blog</span><h2>Latest articles</h2></div>
  <div class="wb-grid" data-cols="3">
    ${["Getting started", "Design tips", "Product news"].map((t, i) => `<article class="wb-post"><img src="${ph(640, 400, t, ["#e0e7ff", "#fef3c7", "#dcfce7"][i], "#c7d2fe")}" alt="${t}"><p class="wb-small wb-muted">Oct ${i + 1}, 2026 · 4 min read</p><h3>${t}</h3><p class="wb-muted">A short teaser that makes readers want to click through to the full post.</p><a href="#">Read more →</a></article>`).join("\n    ")}
  </div>
</div></section>`);

  add("post-card", "Featured post", "Blog", "📄", () => `
<section class="wb-section"><div class="wb-container wb-split">
  <img class="wb-media" src="${ph(720, 480, "Featured")}" alt="Featured post">
  <article><span class="wb-badge">Featured</span><h2 style="margin-top:1rem">The headline of your featured article</h2><p class="wb-muted wb-small">Oct 2, 2026 · 6 min read</p><p>An intro paragraph that summarises the post and invites the reader to continue.</p><div class="wb-actions"><a class="wb-btn" href="#">Read article</a></div></article>
</div></section>`);

  add("author", "Author box", "Blog", "✍", () => `
<section class="wb-section" data-pad="sm" data-width="narrow"><div class="wb-container"><div class="wb-card wb-author"><img class="wb-avatar" src="${avatar("A", "#e0e7ff")}" alt="Author"><div><p><strong>Written by Alex Doe</strong></p><p class="wb-muted">Writer and designer sharing practical tips for building on the web.</p></div></div></div></section>`);

  /* ---- E-commerce ---- */
  add("product-grid", "Product grid", "E-commerce", "🛍", () => `
<section class="wb-section"><div class="wb-container">
  <div class="wb-header"><h2>Featured products</h2></div>
  <div class="wb-grid" data-cols="4">
    ${[["Classic tee", "$24", "$30"], ["Canvas tote", "$18", ""], ["Ceramic mug", "$14", ""], ["Sneakers", "$79", "$95"]].map(([n, pr, old], i) => `<div class="wb-product"><img src="${ph(480, 480, n, ["#e0e7ff", "#fef3c7", "#dcfce7", "#fce7f3"][i], "#c7d2fe")}" alt="${n}"><h3>${n}</h3><div class="wb-product__price">${pr}${old ? `<s>${old}</s>` : ""}</div><a class="wb-btn wb-btn--outline" href="#" data-wb-add data-name="${n}" data-price="${pr.replace("$", "")}">Add to cart</a></div>`).join("\n    ")}
  </div>
</div></section>`);

  add("product-detail", "Product detail", "E-commerce", "🧾", () => `
<section class="wb-section"><div class="wb-container wb-split">
  <img class="wb-media" src="${ph(640, 640, "Product")}" alt="Product">
  <div><span class="wb-badge">In stock</span><h1 style="font-size:2.2rem;margin-top:1rem">Product name</h1><div class="wb-stars">★★★★★ <span class="wb-muted wb-small">(128 reviews)</span></div><p class="wb-product__price" style="font-size:1.8rem;margin-top:1rem">$49.00</p><p class="wb-muted">A concise description of the product, its materials and what makes it special.</p><div class="wb-actions"><a class="wb-btn" href="#" data-wb-add data-name="Product name" data-price="49">Add to cart</a><a class="wb-btn wb-btn--ghost" href="cart.html">View cart (<span data-wb-cart-count>0</span>)</a></div></div>
</div></section>`);

  add("categories", "Category cards", "E-commerce", "▤", () => `
<section class="wb-section" data-bg="alt"><div class="wb-container"><div class="wb-grid" data-cols="3">
  ${["Women", "Men", "Accessories"].map((n, i) => `<a class="wb-category" href="#" style="text-decoration:none"><img src="${ph(480, 600, " ", ["#6366f1", "#0ea5e9", "#f59e0b"][i], "#1e293b")}" alt="${n}"><h3>${n}</h3></a>`).join("\n  ")}
</div></div></section>`);

  add("cart-summary", "Shopping cart", "E-commerce", "🛒", () => `
<section class="wb-section" data-width="narrow"><div class="wb-container"><div class="wb-card" data-wb-cart data-checkout=""><h3>Your cart</h3>
  <div class="wb-cart__items"><div class="wb-cart__row"><img src="${ph(112, 112, "Item")}" alt=""><div><strong>Sample product</strong></div><span>1 × $24.00</span><span></span></div></div>
  <div class="wb-cart__total"><span>Total</span><span data-wb-cart-total>$24.00</span></div>
  <a class="wb-btn wb-btn--block" href="#" data-wb-checkout>Checkout</a>
  <p class="wb-small wb-muted" style="margin:.75rem 0 0">Set the checkout link (payment link or mailto:) in the inspector.</p></div></div></section>`);

  add("sidebar-layout", "Content + sidebar", "Layout", "◨", () => `
<section class="wb-section"><div class="wb-container wb-sidebar-layout"><div><h2>Main content</h2><p>Your article or page content goes here.</p></div><aside class="wb-card"><h3>Sidebar</h3><p class="wb-muted">Links, widgets or a short bio.</p></aside></div></section>`);


  /* ---- Media / widgets ---- */
  add("slider", "Image slider", "Media", "🎞", () => `
<section class="wb-section"><div class="wb-container"><div class="wb-slider" data-wb-slider data-autoplay="0">
  <div class="wb-slider__track">
    ${["Slide one", "Slide two", "Slide three"].map((n, i) => `<figure class="wb-slide"><img src="${ph(1280, 720, n, ["#e0e7ff", "#fce7f3", "#dcfce7"][i], "#c7d2fe")}" alt="${n}"><figcaption>${n} caption</figcaption></figure>`).join("\n    ")}
  </div>
  <button class="wb-slider__btn wb-slider__prev" type="button" aria-label="Previous slide">‹</button>
  <button class="wb-slider__btn wb-slider__next" type="button" aria-label="Next slide">›</button>
  <div class="wb-slider__dots"></div>
</div></div></section>`);

  add("marquee", "Logo marquee", "Media", "⇄", () => `
<section class="wb-section" data-pad="sm"><div class="wb-container"><div class="wb-marquee"><div class="wb-marquee__track">
  ${["Acme", "Globex", "Initech", "Umbrella", "Hooli", "Stark", "Wayne", "Wonka"].map((n) => `<span>${n}</span>`).join("")}${["Acme", "Globex", "Initech", "Umbrella", "Hooli", "Stark", "Wayne", "Wonka"].map((n) => `<span aria-hidden="true">${n}</span>`).join("")}
</div></div></div></section>`);

  add("compare", "Before / After", "Media", "◐", () => `
<section class="wb-section" data-width="narrow"><div class="wb-container"><div class="wb-compare" data-wb-compare>
  <img class="wb-compare__after" src="${ph(1280, 720, "After", "#dcfce7", "#86efac")}" alt="After">
  <div class="wb-compare__before"><img src="${ph(1280, 720, "Before", "#fee2e2", "#fca5a5")}" alt="Before"></div>
  <span class="wb-compare__tag" style="left:12px">Before</span><span class="wb-compare__tag" style="right:12px">After</span>
  <input class="wb-compare__range" type="range" min="0" max="100" value="50" aria-label="Before and after">
  <span class="wb-compare__handle"></span>
</div></div></section>`);

  add("popup", "Popup / modal", "Conversion", "◫", () => `
<section class="wb-section" data-align="center" data-pad="md"><div class="wb-container">
  <a class="wb-btn" href="#" data-wb-open="promo-popup">Open popup</a>
</div>
<div class="wb-modal" id="promo-popup" role="dialog" aria-modal="true" aria-hidden="true"><div class="wb-modal__box"><button class="wb-modal__close" type="button" data-wb-close aria-label="Close">×</button><h3>Special offer</h3><p>Get 20% off your first order. Use code <strong>WELCOME20</strong>.</p><a class="wb-btn" href="#">Claim offer</a></div></div>
</section>`);

  add("hero-video-bg", "Hero · Video background", "Hero", "🎥", () => `
<section class="wb-section wb-hero wb-hero--video wb-hero--center" data-pad="xl">
  <video class="wb-hero__video" src="https://interactive-examples.mdn.mozilla.net/media/cc0-videos/flower.mp4" autoplay muted loop playsinline></video>
  <div class="wb-container"><h1>Video makes the first impression</h1><p class="wb-lead">Paste any .mp4 / .webm link in the inspector to change the background video.</p><div class="wb-actions"><a class="wb-btn" href="#">Get started</a></div></div>
</section>`);

  add("video-file", "Video (YouTube / Vimeo / MP4)", "Media", "▶", () => `
<section class="wb-section" data-width="narrow"><div class="wb-container"><div class="wb-video"><iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" title="Video" allowfullscreen loading="lazy"></iframe></div></div></section>`);

  add("embed", "HTML / embed code", "Media", "⌘", () => `
<section class="wb-section" data-pad="sm"><div class="wb-container"><div class="wb-embed" data-wb-embed><div style="padding:2rem;border:2px dashed #94a3b8;border-radius:12px;text-align:center;color:#64748b">Paste any embed code (iframe, widget, script, form…) using the inspector.</div></div></div></section>`);

  /* ---- Elements (drop into any column / container / card) ---- */
  const els = (WB.elements = []);
  const addEl = (id, label, icon, html) => els.push({ id: "el:" + id, label, category: "Elements", icon, html });
  addEl("h1", "Heading 1", "H1", () => `<h1>Heading 1</h1>`);
  addEl("h2", "Heading 2", "H2", () => `<h2>Heading 2</h2>`);
  addEl("h3", "Heading 3", "H3", () => `<h3>Heading 3</h3>`);
  addEl("p", "Paragraph", "¶", () => `<p>Write something here. Select text to format it with the toolbar above the canvas.</p>`);
  addEl("lead", "Lead text", "Aa", () => `<p class="wb-lead">A larger introductory paragraph.</p>`);
  addEl("list", "Bullet list", "•", () => `<ul><li>First item</li><li>Second item</li><li>Third item</li></ul>`);
  addEl("button", "Button", "▭", () => `<div class="wb-actions"><a class="wb-btn" href="#">Button</a></div>`);
  addEl("image", "Image", "🖼", () => `<img class="wb-media" src="${ph(800, 500, "Image")}" alt="Image">`);
  addEl("video", "Video", "▶", () => `<div class="wb-video"><iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" title="Video" allowfullscreen loading="lazy"></iframe></div>`);
  addEl("card", "Card", "▢", () => `<div class="wb-card"><h3>Card title</h3><p>Card text goes here.</p></div>`);
  addEl("iconbox", "Icon box", "★", () => `<div class="wb-card"><span class="wb-icon">★</span><h3>Feature</h3><p>Short description.</p></div>`);
  addEl("quote", "Quote", "❝", () => `<blockquote class="wb-pullquote">A memorable quote.<cite>— Author</cite></blockquote>`);
  addEl("badge", "Badge", "◉", () => `<span class="wb-badge">Badge</span>`);
  addEl("code", "Code", "{ }", () => `<pre class="wb-code wb-editable">console.log("hello");</pre>`);
  addEl("divider", "Divider", "―", () => `<hr class="wb-divider">`);
  addEl("spacer", "Spacer", "↕", () => `<div class="wb-spacer"></div>`);
  addEl("cols2", "2 columns", "▥", () => `<div class="wb-cols" data-cols="2"><div class="wb-col"><p>Column one</p></div><div class="wb-col"><p>Column two</p></div></div>`);
  addEl("cols3", "3 columns", "▦", () => `<div class="wb-cols" data-cols="3"><div class="wb-col"><p>One</p></div><div class="wb-col"><p>Two</p></div><div class="wb-col"><p>Three</p></div></div>`);
  addEl("embed", "Embed code", "⌘", () => `<div class="wb-embed" data-wb-embed><div style="padding:1.5rem;border:2px dashed #94a3b8;border-radius:12px;text-align:center;color:#64748b">Embed — edit in the inspector</div></div>`);
  addEl("map", "Map", "📍", () => `<div class="wb-map"><iframe src="https://www.google.com/maps?q=New+York&output=embed" title="Map" loading="lazy"></iframe></div>`);
  addEl("social", "Social links", "@", () => `<ul class="wb-social"><li><a href="#">Facebook</a></li><li><a href="#">Instagram</a></li><li><a href="#">LinkedIn</a></li></ul>`);
  addEl("cart", "Cart button", "🛒", () => `<a class="wb-btn wb-btn--outline" href="cart.html">🛒 Cart (<span data-wb-cart-count>0</span>)</a>`);
  addEl("compare", "Before / After", "◐", () => `<div class="wb-compare" data-wb-compare><img class="wb-compare__after" src="${ph(1280, 720, "After", "#dcfce7", "#86efac")}" alt="After"><div class="wb-compare__before"><img src="${ph(1280, 720, "Before", "#fee2e2", "#fca5a5")}" alt="Before"></div><span class="wb-compare__tag" style="left:12px">Before</span><span class="wb-compare__tag" style="right:12px">After</span><input class="wb-compare__range" type="range" min="0" max="100" value="50" aria-label="Before and after"><span class="wb-compare__handle"></span></div>`);
  WB.elementById = Object.fromEntries(els.map((e) => [e.id, e]));

  WB.components = C;
  WB.componentById = Object.fromEntries(C.map((c) => [c.id, c]));
})();
