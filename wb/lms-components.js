/* LMS components for the page builder.
   "Dynamic" blocks carry data-lms="<type>" plus settings as data-attributes; the PHP site replaces the whole
   block with live data (courses, categories, instructors, stats) when the page is rendered. The preview that is
   visible inside the editor is only a placeholder and is marked data-u so it cannot be text-edited. */
(function () {
  "use strict";
  const WB = window.WB;
  WB.categories.splice(WB.categories.indexOf("Footer"), 0, "LMS");
  const add = (id, label, icon, html) => { const c = { id, label, category: "LMS", icon, html }; WB.components.push(c); WB.componentById[id] = c; };
  const esc = (s) => String(s ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));

  const mockCard = (n) => `<div class="wb-card" data-u style="padding:0;overflow:hidden"><img data-u src="${WB.placeholder(640, 360, "Course " + n)}" alt="" style="aspect-ratio:16/9;width:100%;object-fit:cover"><div style="padding:14px 16px"><h3 data-u style="font-size:1rem;margin:0 0 4px">Course title ${n}</h3><p data-u class="wb-muted wb-small" style="margin:0">Instructor name · ★★★★★</p></div></div>`;
  const note = (txt) => `<p data-u class="wb-muted wb-small" style="text-align:center;margin-top:1.5rem">⚙ ${esc(txt)} Edit the settings in the inspector →</p>`;

  add("lms-courses", "Course grid (live)", "📚", () => `
<section class="wb-section" data-lms="courses" data-limit="6" data-sort="newest" data-title="Popular courses" data-subtitle="Learn from the best instructors"><div class="wb-container">
  <div class="wb-header"><h2 data-u data-lms-title>Popular courses</h2><p data-u class="wb-lead" data-lms-sub style="margin-inline:auto">Learn from the best instructors</p></div>
  <div class="wb-grid" data-cols="3" data-u>${mockCard(1)}${mockCard(2)}${mockCard(3)}</div>
  ${note("Live courses from your catalog appear here on the public site.")}
</div></section>`);

  add("lms-categories", "Categories (live)", "🏷", () => `
<section class="wb-section" data-lms="categories" data-limit="8" data-title="Browse by category" data-bg="alt"><div class="wb-container">
  <div class="wb-header"><h2 data-u data-lms-title>Browse by category</h2></div>
  <div class="wb-grid" data-cols="4" data-u>${["Web Development", "Design", "Business", "Data Science"].map((n) => `<div class="wb-card" data-u><h3 data-u>${n}</h3><p data-u class="wb-muted wb-small">12 courses</p></div>`).join("")}</div>
  ${note("Your categories appear here.")}
</div></section>`);

  add("lms-instructors", "Instructors (live)", "🧑‍🏫", () => `
<section class="wb-section" data-lms="instructors" data-limit="4" data-title="Meet our instructors"><div class="wb-container">
  <div class="wb-header"><h2 data-u data-lms-title>Meet our instructors</h2></div>
  <div class="wb-grid" data-cols="4" data-u>${[1, 2, 3, 4].map((n) => `<div class="wb-card" data-u style="text-align:center"><img data-u src="${WB.placeholder(160, 160, "Instructor " + n, "#e0e7ff", "#e2e8f0")}" alt="" style="width:88px;height:88px;border-radius:50%;margin:0 auto 10px"><h3 data-u>Instructor ${n}</h3></div>`).join("")}</div>
  ${note("Active instructors with published courses appear here.")}
</div></section>`);

  add("lms-stats", "Platform stats (live)", "📈", () => `
<section class="wb-section" data-lms="stats" data-bg="primary" data-pad="md"><div class="wb-container"><div class="wb-grid" data-cols="4" data-u style="text-align:center">${["Courses", "Students", "Instructors", "Enrollments"].map((l) => `<div class="wb-stat" data-u><strong data-u>1,234</strong><span data-u>${l}</span></div>`).join("")}</div>${note("Real totals are shown on the public site.")}</div></section>`);

  add("lms-search", "Course search", "🔎", () => `
<section class="wb-section" data-lms="search" data-bg="alt" data-title="Find your next course"><div class="wb-container" style="text-align:center">
  <h2 data-u data-lms-title>Find your next course</h2>
  <div data-u style="display:flex;gap:10px;max-width:560px;margin:1rem auto 0"><input data-u disabled placeholder="Search courses…" style="flex:1;padding:10px 12px;border:1px solid var(--wb-border);border-radius:8px"><span data-u class="wb-btn">Search</span></div>
</div></section>`);

  add("lms-contact", "Contact form (saves to admin)", "✉", () => `
<section class="wb-section" data-lms="contact" data-width="narrow"><div class="wb-container">
  <div data-u class="wb-card" style="display:grid;gap:12px"><input data-u disabled placeholder="Your name"><input data-u disabled placeholder="Email"><textarea data-u disabled rows="4" placeholder="Message"></textarea><span data-u class="wb-btn">Send message</span></div>
  ${note("A working contact form appears here. Messages are listed under Admin → Messages.")}
</div></section>`);

  /* ---- inspector fields for live blocks ---- */
  const FIELDS = {
    courses: [["title", "Heading"], ["subtitle", "Subheading"], ["limit", "How many courses (1–24)", "number"], ["sort", "Show", "select", [["newest", "Newest"], ["popular", "Most popular"], ["rating", "Top rated"], ["featured", "Featured only"]]], ["category", "Category slug (optional)"]],
    categories: [["title", "Heading"], ["limit", "How many (1–24)", "number"]],
    instructors: [["title", "Heading"], ["limit", "How many (1–24)", "number"]],
    search: [["title", "Heading"]],
    stats: [], contact: [],
  };
  WB.hooks.inspectorBlock.push(({ sec }) => {
    const type = sec.dataset && sec.dataset.lms; if (!type) return "";
    const f = FIELDS[type] || [];
    return `<div class="section-label">Live content · ${esc(type)}</div>` + f.map(([k, label, kind, opts]) => {
      const v = sec.dataset[k] || "";
      if (kind === "select") return `<div class="field"><label>${label}</label><select data-lmsattr="${k}">${opts.map(([val, l]) => `<option value="${val}" ${v === val ? "selected" : ""}>${l}</option>`).join("")}</select></div>`;
      return `<div class="field"><label>${label}</label><input type="${kind || "text"}" data-lmsattr="${k}" value="${esc(v)}"></div>`;
    }).join("") + `<p class="hint">This block is filled with real data on the public site.</p>`;
  });
  document.addEventListener("input", (e) => {
    const t = e.target; if (!t.dataset || !t.dataset.lmsattr) return;
    const A = WB.api, w = A.wrapperOf(A.state.selectedId), sec = w && w.firstElementChild; if (!sec) return;
    const k = t.dataset.lmsattr, v = t.value.replace(/[<>"]/g, "");
    sec.setAttribute("data-" + k, v);
    if (k === "title") { const h = sec.querySelector("[data-lms-title]"); if (h) h.textContent = v; }
    if (k === "subtitle") { const h = sec.querySelector("[data-lms-sub]"); if (h) h.textContent = v; }
    A.syncBlock(A.state.selectedId);
  });
})();
