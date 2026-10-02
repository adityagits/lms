/* Website Builder core: pages, components, canvas editing, theme, inspector, export. */
(function () {
  "use strict";
  const { components: COMPONENTS, componentById: BY_ID, categories: CATEGORIES, presets: PRESETS, templates: TEMPLATES, elements: ELEMENTS, elementById: EL_BY_ID } = window.WB;
  const hooks = { canvasReady: [], afterRender: [], inspectorPicked: [], inspectorBlock: [], panels: [] };
  const STORE = "wb-project-v1";
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];
  const uid = () => Math.random().toString(36).slice(2, 9);
  const esc = (s) => String(s ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
  const debounce = (fn, ms) => { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; };

  /* ---------------------------------------------------------------- data */
  const EDITABLE = "h1,h2,h3,h4,h5,h6,p,li,a,button,summary,blockquote,figcaption,label,td,th,.wb-editable,.wb-logos span,cite,.wb-badge,.wb-eyebrow,.wb-stat strong,[data-month],[data-year],.wb-product__price";
  const NOEDIT = ".wb-nav__toggle,[data-dir],[data-u]";
  const THEME_FIELDS = [
    { group: "Colors", var: "--wb-primary", label: "Primary", type: "color" },
    { var: "--wb-primary-contrast", label: "Text on primary", type: "color" },
    { var: "--wb-secondary", label: "Secondary", type: "color" },
    { var: "--wb-accent", label: "Accent", type: "color" },
    { var: "--wb-bg", label: "Page background", type: "color" },
    { var: "--wb-bg-alt", label: "Alt background", type: "color" },
    { var: "--wb-text", label: "Text", type: "color" },
    { var: "--wb-muted", label: "Muted text", type: "color" },
    { var: "--wb-border", label: "Borders", type: "color" },
    { var: "--wb-dark-bg", label: "Dark section bg", type: "color" },
    { var: "--wb-dark-text", label: "Dark section text", type: "color" },
    { group: "Typography", var: "--wb-font-heading", label: "Heading font", type: "font" },
    { var: "--wb-font-body", label: "Body font", type: "font" },
    { var: "--wb-font-size", label: "Base size", type: "range", min: 13, max: 22, unit: "px" },
    { var: "--wb-heading-weight", label: "Heading weight", type: "select", options: ["400", "500", "600", "700", "800", "900"] },
    { group: "Shape & spacing", var: "--wb-radius", label: "Corner radius", type: "range", min: 0, max: 32, unit: "px" },
    { var: "--wb-container", label: "Content width", type: "range", min: 720, max: 1440, unit: "px", step: 20 },
    { var: "--wb-space", label: "Section spacing", type: "range", min: 24, max: 140, unit: "px", step: 4 },
  ];
  const SYS = "system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif";
  const FONTS = [
    ["System UI", SYS],
    ["Georgia (serif)", "Georgia, 'Times New Roman', serif"],
    ...["Inter", "Poppins", "Roboto", "Montserrat", "Nunito", "Space Grotesk"].map((f) => [f, `'${f}', system-ui, sans-serif`]),
    ...["Playfair Display", "DM Serif Display", "Lora", "Merriweather"].map((f) => [f, `'${f}', Georgia, serif`]),
  ];
  const GOOGLE = FONTS.slice(2).map(([n]) => n);

  const newPage = (name, slug) => ({ id: uid(), name, slug, title: name, description: "", blocks: [] });
  const LIB = "wb-library-v1";
  const getLib = () => { try { return JSON.parse(localStorage.getItem(LIB)) || []; } catch (e) { return []; } };
  const setLib = (l) => { try { localStorage.setItem(LIB, JSON.stringify(l)); } catch (e) {} };
  function makeBlock(type) {
    if (type.startsWith("lib:")) { const it = getLib().find((x) => x.id === type.slice(4)); return { id: uid(), type: "saved", html: it ? it.html : "" }; }
    return { id: uid(), type, html: BY_ID[type].html().trim() };
  }
  function defaultProject() {
    const home = newPage("Home", "index");
    home.blocks = ["navbar", "hero-split", "features-grid", "testimonials", "cta", "footer"].map(makeBlock);
    return { name: "My Website", pages: [home], theme: {}, customCss: "" };
  }
  function load() {
    if (window.WB_SEED) return window.WB_SEED;   // LMS: project is loaded from the server
    try { const raw = localStorage.getItem(STORE); if (raw) { const p = JSON.parse(raw); if (p && p.pages && p.pages.length) return p; } } catch (e) {}
    return defaultProject();
  }

  const state = { project: load(), pageId: null, selectedId: null, pickedEl: null, device: "desktop", preview: false, tab: "pages", filter: "" };
  state.pageId = state.project.pages[0].id;
  const page = () => state.project.pages.find((p) => p.id === state.pageId) || state.project.pages[0];
  const blockById = (id) => page().blocks.find((b) => b.id === id);

  /* -------------------------------------------------------------- history */
  let hist = [JSON.stringify(state.project)], hi = 0;
  const save = () => {
    if (window.WB_SEED) { if (window.WB_ON_SAVE) window.WB_ON_SAVE(state.project); return; }   // LMS: persist to server, not localStorage
    try { localStorage.setItem(STORE, JSON.stringify(state.project)); } catch (e) {}
  };
  function commit() {
    const s = JSON.stringify(state.project);
    if (s === hist[hi]) return;
    hist = hist.slice(0, hi + 1); hist.push(s); hi = hist.length - 1;
    if (hist.length > 100) { hist.shift(); hi--; }
    save(); updateHistoryButtons();
  }
  function restore(i) {
    hi = i; state.project = JSON.parse(hist[hi]);
    if (!state.project.pages.find((p) => p.id === state.pageId)) state.pageId = state.project.pages[0].id;
    if (!blockById(state.selectedId)) state.selectedId = null;
    state.pickedEl = null; save(); applyTheme(); renderAll(); updateHistoryButtons();
  }
  const undo = () => { flushEdit(); if (hi > 0) restore(hi - 1); };
  const redo = () => { if (hi < hist.length - 1) restore(hi + 1); };
  function updateHistoryButtons() { $("#undo").disabled = hi <= 0; $("#redo").disabled = hi >= hist.length - 1; }

  /* --------------------------------------------------------------- canvas */
  const frame = $("#canvas");
  let doc, themeDefaults = {}, dragState = null, indicator;
  const isElDrag = () => dragState && String(dragState.kind).startsWith("el");

  frame.addEventListener("load", () => {
    doc = frame.contentDocument;
    const cs = getComputedStyle(doc.documentElement);
    THEME_FIELDS.forEach((f) => (themeDefaults[f.var] = cs.getPropertyValue(f.var).trim()));
    bindCanvas();
    indicator = doc.createElement("div"); indicator.className = "wb-drop"; doc.body.appendChild(indicator);
    hooks.canvasReady.forEach((fn) => fn(doc));
    applyTheme();
    renderAll();
  });
  frame.srcdoc = `<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="theme/theme.css"><link rel="stylesheet" href="builder/canvas.css">
<link id="gf" rel="stylesheet"><style id="theme-vars"></style><style id="custom-css"></style></head><body><div id="wb-root"></div><script src="theme/theme.js"><\/script></body></html>`;

  const themeVars = () => state.project.theme || (state.project.theme = {});
  const varValue = (v) => themeVars()[v] ?? themeDefaults[v] ?? "";

  function googleFontsUrl() {
    const used = new Set();
    Object.values(themeVars()).forEach((val) => GOOGLE.forEach((g) => { if (String(val).startsWith(`'${g}'`)) used.add(g); }));
    if (!used.size) return "";
    return "https://fonts.googleapis.com/css2?" + [...used].map((g) => "family=" + g.replace(/ /g, "+") + ":wght@400;500;600;700;800").join("&") + "&display=swap";
  }
  const themeCss = () => {
    const entries = Object.entries(themeVars());
    return entries.length ? ":root{\n" + entries.map(([k, v]) => `  ${k}: ${v};`).join("\n") + "\n}" : "";
  };
  function applyTheme() {
    if (!doc) return;
    $("#theme-vars", doc).textContent = themeCss();
    $("#custom-css", doc).textContent = state.project.customCss || "";
    const url = googleFontsUrl();
    const gf = $("#gf", doc);
    if (url) gf.setAttribute("href", url); else gf.removeAttribute("href");
  }

  /** Strip all editor-only markup from a block wrapper and return the clean section HTML. */
  function cleanHTML(wrapper) {
    const el = wrapper.firstElementChild.cloneNode(true);
    el.querySelectorAll("[contenteditable]").forEach((n) => n.removeAttribute("contenteditable"));
    el.querySelectorAll("[spellcheck]").forEach((n) => n.removeAttribute("spellcheck"));
    el.querySelectorAll(".is-picked").forEach((n) => { n.classList.remove("is-picked"); if (!n.className) n.removeAttribute("class"); });
    el.querySelectorAll("details").forEach((d) => { d.removeAttribute("open"); if (d.dataset.open) d.setAttribute("open", ""); });
    return el.outerHTML;
  }

  function makeBlockEl(b) {
    const w = doc.createElement("div");
    w.className = "wb-block"; w.dataset.id = b.id;
    w.innerHTML = b.html;
    if (!state.preview) {
      const comp = BY_ID[b.type] || (b.type === "saved" ? { label: "Saved section" } : null);
      const tb = doc.createElement("div");
      tb.className = "wb-tb"; tb.contentEditable = "false";
      tb.innerHTML = `<span class="tag grab" draggable="true" title="Drag to reorder">⠿ ${esc(comp ? comp.label : b.type)}</span>` +
        `<button data-act="up" title="Move up">↑</button><button data-act="down" title="Move down">↓</button>` +
        `<button data-act="dup" title="Duplicate">⧉</button><button data-act="del" title="Delete">🗑</button>`;
      w.appendChild(tb);
      w.querySelectorAll("details").forEach((d) => (d.open = true));
      w.querySelectorAll(EDITABLE).forEach((n) => {
        if (n.closest(".wb-tb") || n.matches(NOEDIT) || n.closest(".wb-embed") || n.parentElement.closest("[contenteditable=true]")) return;
        n.setAttribute("contenteditable", "true"); n.setAttribute("spellcheck", "false");
      });
    }
    return w;
  }

  function renderCanvas() {
    if (!doc) return;
    const root = $("#wb-root", doc);
    doc.body.className = state.preview ? "" : "wb-edit";
    root.innerHTML = "";
    const blocks = page().blocks;
    if (!blocks.length) root.innerHTML = `<div class="wb-empty"><strong>This page is empty</strong>Drag a component from the sidebar, or click one to add it.</div>`;
    blocks.forEach((b) => root.appendChild(makeBlockEl(b)));
    markSelection();
    doc.title = page().title;
    hooks.afterRender.forEach((fn) => fn());
    if (state.preview && frame.contentWindow.WBTheme) frame.contentWindow.WBTheme.refresh();
  }
  function markSelection() {
    $$(".wb-block", doc).forEach((w) => w.classList.toggle("is-selected", w.dataset.id === state.selectedId));
  }
  const wrapperOf = (id) => $(`.wb-block[data-id="${id}"]`, doc);

  /* sync DOM edits back to block data */
  let editingId = null;
  const commitLater = debounce(() => commit(), 500);
  function syncBlock(id) {
    const w = wrapperOf(id), b = blockById(id);
    if (!w || !b) return;
    b.html = cleanHTML(w);
    commitLater();
  }
  function flushEdit() { if (editingId) { syncBlock(editingId); editingId = null; } commit(); }

  /* --------------------------------------------------- block operations */
  function select(id, scroll) {
    state.selectedId = id; markSelection(); renderInspector();
    if (scroll && id) wrapperOf(id).scrollIntoView({ behavior: "smooth", block: "center" });
  }
  function addBlock(type, index) {
    flushEdit();
    const blocks = page().blocks;
    if (index == null) { const i = blocks.findIndex((b) => b.id === state.selectedId); index = i >= 0 ? i + 1 : blocks.length; }
    const b = makeBlock(type);
    blocks.splice(index, 0, b);
    state.selectedId = b.id; state.pickedEl = null;
    renderCanvas(); renderInspector(); commit();
    wrapperOf(b.id).scrollIntoView({ behavior: "smooth", block: "center" });
  }
  function moveBlock(id, to) {
    flushEdit();
    const blocks = page().blocks, from = blocks.findIndex((b) => b.id === id);
    to = Math.max(0, Math.min(blocks.length - 1, to));
    if (from < 0 || from === to) return;
    blocks.splice(to, 0, blocks.splice(from, 1)[0]);
    renderCanvas(); commit();
  }
  function dupBlock(id) {
    flushEdit();
    const blocks = page().blocks, i = blocks.findIndex((b) => b.id === id);
    const copy = { ...blocks[i], id: uid() };
    blocks.splice(i + 1, 0, copy); state.selectedId = copy.id;
    renderCanvas(); renderInspector(); commit();
  }
  function delBlock(id) {
    flushEdit();
    const blocks = page().blocks, i = blocks.findIndex((b) => b.id === id);
    if (i < 0) return;
    blocks.splice(i, 1);
    if (state.selectedId === id) { state.selectedId = null; state.pickedEl = null; }
    renderCanvas(); renderInspector(); commit();
  }

  /* ------------------------------------------------------- canvas events */
  function dropIndexAt(y) {
    const ws = $$(".wb-block", doc);
    let idx = 0, top = 0;
    ws.forEach((w, i) => { const r = w.getBoundingClientRect(); if (y > r.top + r.height / 2) idx = i + 1; });
    if (ws.length) { const r = (ws[idx] || ws[ws.length - 1]).getBoundingClientRect(); top = (ws[idx] ? r.top : r.bottom) + doc.defaultView.scrollY; }
    return { idx, top };
  }
  function bindCanvas() {
    doc.addEventListener("click", (e) => {
      const t = e.target;
      const a = t.closest("a");
      if (state.preview) {
        if (!a) return;
        const href = a.getAttribute("href") || "";
        e.preventDefault();
        if (href.startsWith("#")) { const el = href.length > 1 && doc.getElementById(href.slice(1)); if (el) el.scrollIntoView({ behavior: "smooth" }); return; }
        const target = state.project.pages.find((p) => href === p.slug + ".html" || href === p.slug);
        if (target) { state.pageId = target.id; renderAll(); }
        return;
      }
      const btn = t.closest(".wb-tb button");
      const w = t.closest(".wb-block");
      if (a) e.preventDefault();
      if (btn && w) {
        const id = w.dataset.id, i = page().blocks.findIndex((b) => b.id === id);
        ({ up: () => moveBlock(id, i - 1), down: () => moveBlock(id, i + 1), dup: () => dupBlock(id), del: () => delBlock(id) })[btn.dataset.act]();
        return;
      }
      if (!w) { state.pickedEl = null; select(null); return; }
      // pick element for attribute editing
      const pick = t.closest("img,a,iframe,video,.wb-btn,button,.wb-embed,.wb-video,.wb-map");
      $$(".is-picked", doc).forEach((n) => n.classList.remove("is-picked"));
      state.pickedEl = pick && !pick.closest(".wb-tb") ? pick : null;
      if (state.pickedEl) state.pickedEl.classList.add("is-picked");
      const changed = state.selectedId !== w.dataset.id;
      state.selectedId = w.dataset.id; markSelection();
      renderInspector();
    });
    doc.addEventListener("focusin", (e) => { const w = e.target.closest(".wb-block"); if (w) editingId = w.dataset.id; });
    doc.addEventListener("focusout", () => { if (editingId) { syncBlock(editingId); } });
    doc.addEventListener("input", (e) => { const w = e.target.closest(".wb-block"); if (w) { editingId = w.dataset.id; syncBlock(editingId); } });
    doc.addEventListener("keydown", (e) => {
      const el = e.target;
      if (el.isContentEditable && e.key === "Enter" && !/^(P|LI|BLOCKQUOTE|DIV|TD|TH)$/.test(el.tagName)) { e.preventDefault(); el.blur(); }
      onKey(e);
    });
    doc.addEventListener("paste", (e) => {
      if (!e.target.isContentEditable) return;
      e.preventDefault();
      doc.execCommand("insertText", false, (e.clipboardData || window.clipboardData).getData("text/plain"));
    });
    // drag & drop (new components from sidebar, reorder from block handle)
    doc.addEventListener("dragstart", (e) => {
      const h = e.target.closest && e.target.closest(".wb-tb [draggable]");
      if (!h) return;
      const w = h.closest(".wb-block");
      dragState = { kind: "move", id: w.dataset.id };
      e.dataTransfer.effectAllowed = "move"; e.dataTransfer.setData("text/plain", w.dataset.id);
      e.dataTransfer.setDragImage(w, 20, 20);
    });
    doc.addEventListener("dragover", (e) => {
      if (!dragState || isElDrag()) return;
      e.preventDefault();
      const { top } = dropIndexAt(e.clientY);
      indicator.style.display = "block"; indicator.style.top = Math.max(0, top - 2) + "px";
    });
    doc.addEventListener("dragleave", (e) => { if (!e.relatedTarget && indicator) indicator.style.display = "none"; });
    doc.addEventListener("drop", (e) => {
      if (!dragState || isElDrag()) return;
      e.preventDefault();
      const { idx } = dropIndexAt(e.clientY), ds = dragState;
      dragState = null; indicator.style.display = "none";
      if (ds.kind === "new") addBlock(ds.type, idx);
      else { const from = page().blocks.findIndex((b) => b.id === ds.id); moveBlock(ds.id, idx > from ? idx - 1 : idx); }
    });
    doc.addEventListener("dragend", () => { dragState = null; if (indicator) indicator.style.display = "none"; });
  }

  function onKey(e) {
    const mod = e.ctrlKey || e.metaKey;
    const editing = e.target.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test(e.target.tagName);
    if (mod && e.key.toLowerCase() === "z") { if (editing && !e.shiftKey && e.target.isContentEditable === false) return; e.preventDefault(); e.shiftKey ? redo() : undo(); }
    else if (mod && e.key.toLowerCase() === "y") { e.preventDefault(); redo(); }
    else if ((e.key === "Delete" || e.key === "Backspace") && !editing && state.selectedId && !state.preview) { e.preventDefault(); delBlock(state.selectedId); }
    else if (e.key === "Escape" && state.preview) togglePreview(false);
  }
  document.addEventListener("keydown", onKey);

  /* ----------------------------------------------------------- left panel */
  function renderPagesPanel() {
    const p = page(), P = state.project.pages;
    $("#panel-pages").innerHTML = `
      <div class="section-label">Pages</div>
      ${P.map((x) => `<div class="page-item ${x.id === p.id ? "active" : ""}" data-page="${x.id}">
        <span class="name">${esc(x.name)}</span><span class="slug">${esc(x.slug)}.html</span>
        <button class="icon-btn" data-pact="dup" title="Duplicate" style="width:26px;height:26px">⧉</button>
        ${P.length > 1 ? `<button class="icon-btn" data-pact="del" title="Delete" style="width:26px;height:26px">🗑</button>` : ""}</div>`).join("")}
      <div class="btn-row"><button class="btn primary" id="add-page">+ New blank page</button></div>
      <div class="section-label">New page from template</div>
      ${TEMPLATES.map((t) => `<button class="tpl" data-tpl="${t.id}"><strong>${esc(t.name)}</strong><span>${esc(t.desc)}</span></button>`).join("")}
      <div class="section-label">Page settings</div>
      <div class="field"><label>Page name</label><input type="text" id="pg-name" value="${esc(p.name)}"></div>
      <div class="field"><label>File name (slug)</label><input type="text" id="pg-slug" value="${esc(p.slug)}"></div>
      <div class="field"><label>SEO title</label><input type="text" id="pg-title" value="${esc(p.title)}"></div>
      <div class="field"><label>Meta description</label><input type="text" id="pg-desc" value="${esc(p.description)}"></div>
      <p class="hint">Link to another page from a button or menu item by setting its link to <code>slug.html</code>.</p>`;
  }
  $("#panel-pages").addEventListener("click", (e) => {
    const item = e.target.closest("[data-page]");
    const act = e.target.closest("[data-pact]");
    const tpl = e.target.closest("[data-tpl]");
    if (tpl) {
      flushEdit();
      const t = TEMPLATES.find((x) => x.id === tpl.dataset.tpl), n = state.project.pages.length + 1;
      const np = newPage(t.name, t.id + (n > 1 ? "-" + n : ""));
      np.blocks = t.blocks.map(makeBlock);
      state.project.pages.push(np); state.pageId = np.id; state.selectedId = null; renderAll(); commit(); toast(`Added "${t.name}" page`);
    } else if (e.target.closest("#add-page")) {
      flushEdit();
      const n = state.project.pages.length + 1, np = newPage("Page " + n, "page-" + n);
      np.blocks = ["navbar", "text", "footer"].map(makeBlock);
      state.project.pages.push(np); state.pageId = np.id; state.selectedId = null; renderAll(); commit();
    } else if (act && item) {
      flushEdit();
      const i = state.project.pages.findIndex((x) => x.id === item.dataset.page);
      if (act.dataset.pact === "dup") {
        const src = state.project.pages[i], cp = JSON.parse(JSON.stringify(src));
        cp.id = uid(); cp.name += " copy"; cp.slug += "-copy"; cp.blocks.forEach((b) => (b.id = uid()));
        state.project.pages.splice(i + 1, 0, cp); state.pageId = cp.id;
      } else if (confirm("Delete this page?")) {
        state.project.pages.splice(i, 1); state.pageId = state.project.pages[Math.max(0, i - 1)].id;
      }
      state.selectedId = null; renderAll(); commit();
    } else if (item) { flushEdit(); state.pageId = item.dataset.page; state.selectedId = null; state.pickedEl = null; renderAll(); }
  });
  $("#panel-pages").addEventListener("input", (e) => {
    const map = { "pg-name": "name", "pg-slug": "slug", "pg-title": "title", "pg-desc": "description" };
    const k = map[e.target.id]; if (!k) return;
    let v = e.target.value;
    if (k === "slug") v = v.toLowerCase().replace(/[^a-z0-9-_]/g, "-");
    page()[k] = v;
    if (k === "name") { const it = $(`.page-item.active .name`); if (it) it.textContent = v; }
    if (k === "slug") { const it = $(`.page-item.active .slug`); if (it) it.textContent = v + ".html"; }
    save(); commitLater();
  });

  function renderComponentsPanel() {
    const q = state.filter.toLowerCase();
    let html = `<input class="search" id="comp-search" placeholder="Search components…" value="${esc(state.filter)}">`;
    CATEGORIES.forEach((cat) => {
      const list = (cat === "Elements" ? ELEMENTS : COMPONENTS).filter((c) => c.category === cat && (!q || c.label.toLowerCase().includes(q) || cat.toLowerCase().includes(q)));
      if (!list.length) return;
      html += `<div class="section-label">${cat}</div><div class="comp-grid">` +
        list.map((c) => `<div class="comp" draggable="true" data-comp="${c.id}" title="Drag onto the page, or click to add"><span class="ic">${c.icon}</span><span class="lb">${esc(c.label)}</span></div>`).join("") + "</div>";
    });
    const lib = getLib().filter((l) => !q || l.name.toLowerCase().includes(q));
    const libHTML = !lib.length ? "" : `<div class="section-label">My sections</div><div>${lib.map((l) => `<div class="lib-item"><div class="comp" draggable="true" data-comp="lib:${l.id}"><span class="lb">★ ${esc(l.name)}</span></div><button class="icon-btn" data-libdel="${l.id}" title="Remove from library" style="width:28px;height:28px">✕</button></div>`).join("")}</div>`;
    if (libHTML) html = html.replace('<div class="section-label">', () => libHTML + '<div class="section-label">');
    $("#panel-components").innerHTML = html + `<p class="hint" style="margin-top:14px">Drag onto the canvas, or click to insert after the selected block.</p>`;
    const s = $("#comp-search"); if (state.tab === "components" && state.filter) { s.focus(); s.setSelectionRange(s.value.length, s.value.length); }
  }
  $("#panel-components").addEventListener("input", (e) => { if (e.target.id === "comp-search") { state.filter = e.target.value; renderComponentsPanel(); } });
  $("#panel-components").addEventListener("click", (e) => {
    const d = e.target.closest("[data-libdel]");
    if (d) { setLib(getLib().filter((l) => l.id !== d.dataset.libdel)); renderComponentsPanel(); return; }
    const c = e.target.closest("[data-comp]");
    if (c) { c.dataset.comp.startsWith("el:") ? WB.nesting.addClick(c.dataset.comp) : addBlock(c.dataset.comp); if (isSmall()) closeDrawers(); }
  });
  $("#panel-components").addEventListener("dragstart", (e) => {
    const c = e.target.closest("[data-comp]"); if (!c) return;
    dragState = c.dataset.comp.startsWith("el:") ? { kind: "el-new", html: EL_BY_ID[c.dataset.comp].html() } : { kind: "new", type: c.dataset.comp };
    e.dataTransfer.effectAllowed = "copy"; e.dataTransfer.setData("text/plain", c.dataset.comp);
  });
  $("#panel-components").addEventListener("dragend", () => { dragState = null; if (indicator) indicator.style.display = "none"; });

  /* ---------------------------------------------------------- theme panel */
  function renderThemePanel() {
    let html = `<div class="section-label">Presets</div><div class="presets">` +
      PRESETS.map((p, i) => `<button class="preset" data-preset="${i}"><div class="dots">${["--wb-primary", "--wb-secondary", "--wb-accent", "--wb-bg-alt"].map((v) => `<i style="background:${p.vars[v] || themeDefaults[v] || "#ddd"}"></i>`).join("")}</div>${esc(p.name)}</button>`).join("") + `</div>`;
    THEME_FIELDS.forEach((f) => {
      if (f.group) html += `<div class="section-label">${f.group}</div>`;
      const v = varValue(f.var);
      if (f.type === "color") html += `<div class="color-field"><label>${f.label}</label><code>${esc(v)}</code><input type="color" data-var="${f.var}" value="${/^#[0-9a-f]{6}$/i.test(v) ? v : "#000000"}"></div>`;
      else if (f.type === "range") { const n = parseFloat(v) || f.min; html += `<div class="range-field"><div class="top"><span>${f.label}</span><span>${n}${f.unit}</span></div><input type="range" data-var="${f.var}" data-unit="${f.unit}" min="${f.min}" max="${f.max}" step="${f.step || 1}" value="${n}"></div>`; }
      else if (f.type === "select") html += `<div class="field"><label>${f.label}</label><select data-var="${f.var}">${f.options.map((o) => `<option ${o === v ? "selected" : ""}>${o}</option>`).join("")}</select></div>`;
      else if (f.type === "font") {
        const known = FONTS.some(([, val]) => val === v);
        html += `<div class="field"><label>${f.label}</label><select data-var="${f.var}">${known ? "" : `<option selected>${esc(v)}</option>`}${FONTS.map(([n, val]) => `<option value="${esc(val)}" ${val === v ? "selected" : ""}>${n}</option>`).join("")}</select></div>`;
      }
    });
    html += `<div class="section-label">Custom CSS</div><div class="field"><textarea id="custom-css-input" placeholder=".wb-card { box-shadow: none; }">${esc(state.project.customCss)}</textarea></div>
      <div class="btn-row"><button class="btn" id="theme-reset">Reset theme</button><button class="btn primary" id="theme-download">Download theme.css</button></div>
      <p class="hint" style="margin-top:10px">All components read these tokens from <code>theme/theme.css</code>. Download the result and drop it into a new project to reuse your design.</p>`;
    $("#panel-theme").innerHTML = html;
  }
  const themeInput = (e) => {
    const t = e.target, v = t.dataset.var; if (!v) return;
    let val = t.value;
    if (t.dataset.unit) val += t.dataset.unit;
    themeVars()[v] = val;
    applyTheme(); commitLater();
    if (t.type === "color") t.previousElementSibling.textContent = val;
    if (t.type === "range") t.previousElementSibling.lastElementChild.textContent = val;
  };
  $("#panel-theme").addEventListener("input", (e) => {
    if (e.target.id === "custom-css-input") { state.project.customCss = e.target.value; applyTheme(); save(); commitLater(); return; }
    if (e.target.tagName !== "SELECT") themeInput(e);
  });
  $("#panel-theme").addEventListener("change", (e) => { if (e.target.tagName === "SELECT" && e.target.dataset.var) { themeInput(e); renderThemePanel(); } });
  $("#panel-theme").addEventListener("click", (e) => {
    const pr = e.target.closest("[data-preset]");
    if (pr) { state.project.theme = { ...PRESETS[+pr.dataset.preset].vars }; applyTheme(); renderThemePanel(); commit(); }
    else if (e.target.id === "theme-reset") { state.project.theme = {}; state.project.customCss = ""; applyTheme(); renderThemePanel(); commit(); }
    else if (e.target.id === "theme-download") exportTheme();
  });

  /* ------------------------------------------------------------ inspector */
  const segHTML = (key, opts, cur) => `<div class="seg" data-key="${key}">${opts.map(([v, l]) => `<button data-val="${v}" class="${cur === v ? "active" : ""}">${l}</button>`).join("")}</div>`;
  function renderInspector() {
    const el = $("#inspector"), b = state.selectedId && blockById(state.selectedId), w = b && wrapperOf(b.id);
    if (!b || !w) {
      el.innerHTML = `<p class="hint">Click a section on the canvas to edit its settings. Click any text to type directly.</p>
        <div class="section-label">Page</div><p><strong>${esc(page().name)}</strong><br><span class="hint">${page().blocks.length} sections</span></p>`;
      return;
    }
    const sec = w.firstElementChild, comp = BY_ID[b.type];
    const bg = sec.dataset.bg || "default";
    const swatch = { default: "var(--ed-panel)", alt: "#eef1f7", primary: "#4f46e5", dark: "#0f172a" };
    let html = `<div class="section-label">${esc(comp ? comp.label : "Section")}</div>
      <div class="field"><label>Background</label><div class="swatches" data-key="bg">${["default", "alt", "primary", "dark"].map((v) => `<button data-val="${v}" title="${v}" class="${bg === v ? "active" : ""}" style="background:${swatch[v]}"></button>`).join("")}</div></div>
      <div class="field"><label>Vertical padding</label>${segHTML("pad", [["none", "0"], ["sm", "S"], ["md", "M"], ["lg", "L"], ["xl", "XL"]], sec.dataset.pad || "md")}</div>
      <div class="field"><label>Text alignment</label>${segHTML("align", [["", "Auto"], ["left", "Left"], ["center", "Center"], ["right", "Right"]], sec.dataset.align || "")}</div>
      <div class="field"><label>Content width</label>${segHTML("width", [["narrow", "Narrow"], ["", "Normal"], ["wide", "Wide"], ["full", "Full"]], sec.dataset.width || "")}</div>
      <div class="field"><label>Anchor ID (for #links)</label><input type="text" id="in-id" value="${esc(sec.id)}" placeholder="e.g. pricing"></div>`;
    const p = state.pickedEl && w.contains(state.pickedEl) ? state.pickedEl : null;
    if (p) {
      const tag = p.tagName.toLowerCase();
      html = `<div class="picked-box"><div class="section-label" style="margin-top:0">Selected ${tag === "a" ? "link" : tag}</div>` +
        (tag === "a" || tag === "button" ? (tag === "a" ? `<div class="field"><label>Link (URL, #anchor or page.html)</label><input type="text" data-attr="href" list="page-links" value="${esc(p.getAttribute("href"))}">
          <datalist id="page-links">${state.project.pages.map((x) => `<option value="${esc(x.slug)}.html">`).join("")}</datalist></div>
          <label class="row"><input type="checkbox" id="in-blank" ${p.target === "_blank" ? "checked" : ""}> Open in new tab</label>` : "") : "") +
        (p.classList.contains("wb-btn") ? `<div class="field" style="margin-top:8px"><label>Button style</label><select id="in-btnstyle">${[["", "Primary"], ["wb-btn--secondary", "Secondary"], ["wb-btn--outline", "Outline"], ["wb-btn--ghost", "Ghost"]].map(([c, l]) => `<option value="${c}" ${(c ? p.classList.contains(c) : !/wb-btn--(secondary|outline|ghost)/.test(p.className)) ? "selected" : ""}>${l}</option>`).join("")}</select></div>` : "") +
        hooks.inspectorPicked.map((fn) => fn({ p, sec, b, w })).join("") + `</div>` + html;
    }
    if (sec.classList.contains("wb-nav")) html += `<label class="row" style="margin-bottom:6px"><input type="checkbox" id="in-sticky" ${sec.hasAttribute("data-sticky") ? "checked" : ""}> Sticky to top</label><label class="row" style="margin-bottom:8px"><input type="checkbox" id="in-transparent" ${sec.hasAttribute("data-transparent") ? "checked" : ""}> Transparent, overlaid on first section</label><p class="hint">Sticky / transparent show on the exported page and in Preview.</p>`;
    html += hooks.inspectorBlock.map((fn) => fn({ p, sec, b, w })).join("");
    html += `<div class="btn-row"><button class="btn sm" data-bact="star" title="Save to My sections">★ Save</button><button class="btn sm" data-bact="html">&lt;/&gt; Edit HTML</button><button class="btn sm" data-bact="up">↑</button><button class="btn sm" data-bact="down">↓</button><button class="btn sm" data-bact="dup">Duplicate</button><button class="btn sm danger" data-bact="del">Delete</button></div>`;
    el.innerHTML = html;
  }
  function setSectionAttr(k, v) {
    const w = wrapperOf(state.selectedId); if (!w) return;
    const sec = w.firstElementChild;
    if (k === "id") v ? sec.setAttribute("id", v) : sec.removeAttribute("id");
    else v && !(k === "bg" && v === "default") ? sec.setAttribute("data-" + k, v) : sec.removeAttribute("data-" + k);
    syncBlock(state.selectedId); flushEdit(); renderInspector();
  }
  $("#inspector").addEventListener("click", (e) => {
    const opt = e.target.closest("[data-key] [data-val]");
    if (opt) return setSectionAttr(opt.parentElement.dataset.key, opt.dataset.val);
    const act = e.target.closest("[data-bact]");
    if (!act || !state.selectedId) return;
    const id = state.selectedId, i = page().blocks.findIndex((b) => b.id === id);
    if (act.dataset.bact === "up") moveBlock(id, i - 1);
    else if (act.dataset.bact === "down") moveBlock(id, i + 1);
    else if (act.dataset.bact === "dup") dupBlock(id);
    else if (act.dataset.bact === "del") delBlock(id);
    else if (act.dataset.bact === "html") openHtmlEditor(id);
    else if (act.dataset.bact === "star") {
      const name = prompt("Name for this saved section:", (BY_ID[blockById(id).type] || { label: "My section" }).label);
      if (!name) return;
      flushEdit(); const l = getLib(); l.push({ id: uid(), name, html: blockById(id).html }); setLib(l);
      renderComponentsPanel(); toast("Saved to Components → My sections");
    }
  });
  $("#inspector").addEventListener("input", (e) => {
    const t = e.target, p = state.pickedEl;
    if (t.id === "in-id") return setSectionAttrQuiet("id", t.value.trim().replace(/[^\w-]/g, ""));
    if (t.dataset.attr && p) { p.setAttribute(t.dataset.attr, t.value); syncBlock(state.selectedId); }
  });
  function setSectionAttrQuiet(k, v) { const sec = wrapperOf(state.selectedId).firstElementChild; v ? sec.setAttribute(k, v) : sec.removeAttribute(k); syncBlock(state.selectedId); }
  $("#inspector").addEventListener("change", (e) => {
    const t = e.target, p = state.pickedEl;
    if (t.id === "in-sticky") setSectionAttr("sticky", t.checked ? "1" : "");
    else if (t.id === "in-transparent") setSectionAttr("transparent", t.checked ? "1" : "");
    else if (t.id === "in-blank" && p) { t.checked ? (p.target = "_blank") && p.setAttribute("rel", "noopener") : (p.removeAttribute("target"), p.removeAttribute("rel")); syncBlock(state.selectedId); flushEdit(); }
    else if (t.id === "in-btnstyle" && p) { p.className = p.className.replace(/\s*wb-btn--(secondary|outline|ghost)/g, ""); if (t.value) p.classList.add(t.value); syncBlock(state.selectedId); flushEdit(); }
  });

  function openHtmlEditor(id) {
    const w = wrapperOf(id);
    $("#modal-title").textContent = "Edit section HTML";
    $("#modal-text").value = cleanHTML(w);
    const dlg = $("#modal");
    dlg.onclose = () => {
      if (dlg.returnValue !== "ok") return;
      const b = blockById(id); if (!b) return;
      b.html = $("#modal-text").value.trim();
      renderCanvas(); renderInspector(); commit();
    };
    dlg.returnValue = ""; dlg.showModal();
  }

  /* --------------------------------------------------------------- export */
  const sources = {};
  async function fetchText(path) {
    if (sources[path] != null) return sources[path];
    try { const r = await fetch(path); if (r.ok) return (sources[path] = await r.text()); } catch (e) {}
    return null; // e.g. opened via file:// — callers fall back to <link>/<script src>
  }
  const fetchThemeSource = () => fetchText("theme/theme.css");
  const download = (name, text, type = "text/html") => {
    const a = document.createElement("a");
    a.href = URL.createObjectURL(new Blob([text], { type })); a.download = name; a.click();
    setTimeout(() => URL.revokeObjectURL(a.href), 2000);
  };
  async function buildPage(p, linked) {
    flushEdit();
    const css = linked ? null : await fetchThemeSource(), js = linked ? null : await fetchText("theme/theme.js"), fonts = googleFontsUrl();
    const body = p.blocks.map((b) => b.html).join("\n\n");
    return `<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>${esc(p.title || p.name)}</title>
${p.description ? `<meta name="description" content="${esc(p.description)}">\n` : ""}${fonts ? `<link rel="stylesheet" href="${fonts}">\n` : ""}${css != null ? `<style>\n${css}\n${themeCss()}\n${state.project.customCss || ""}\n</style>` : `<link rel="stylesheet" href="theme/theme.css">\n<style>\n${themeCss()}\n${state.project.customCss || ""}\n</style>`}
</head>
<body>
${body}
${js != null ? `<script>\n${js}\n</script>` : `<script src="theme/theme.js"></script>`}
</body>
</html>
`;
  }
  async function exportTheme() {
    const css = await fetchThemeSource();
    const out = (css || "/* base theme/theme.css not readable here — keep your original file and append the block below */\n") + "\n/* ---- project overrides ---- */\n" + themeCss() + "\n" + (state.project.customCss || "") + "\n";
    download("theme.css", out, "text/css"); toast("theme.css downloaded");
  }

  /* minimal "stored" (uncompressed) zip writer — no library needed */
  const CRC = (() => { const t = []; for (let n = 0; n < 256; n++) { let c = n; for (let k = 0; k < 8; k++) c = c & 1 ? 0xedb88320 ^ (c >>> 1) : c >>> 1; t[n] = c >>> 0; } return t; })();
  const crc32 = (u8) => { let c = 0xffffffff; for (let i = 0; i < u8.length; i++) c = CRC[(c ^ u8[i]) & 255] ^ (c >>> 8); return (c ^ 0xffffffff) >>> 0; };
  function makeZip(files) {
    const enc = new TextEncoder(), chunks = [], central = []; let offset = 0;
    const u16 = (v) => [v & 255, (v >> 8) & 255], u32 = (v) => [v & 255, (v >> 8) & 255, (v >> 16) & 255, (v >>> 24) & 255];
    files.forEach(({ name, data }) => {
      const nb = enc.encode(name), db = typeof data === "string" ? enc.encode(data) : data, crc = crc32(db);
      const local = new Uint8Array([0x50, 0x4b, 3, 4, 20, 0, 0, 8, 0, 0, 0, 0, 0x21, 0, ...u32(crc), ...u32(db.length), ...u32(db.length), ...u16(nb.length), 0, 0]);
      chunks.push(local, nb, db);
      central.push(new Uint8Array([0x50, 0x4b, 1, 2, 20, 0, 20, 0, 0, 8, 0, 0, 0, 0, 0x21, 0, ...u32(crc), ...u32(db.length), ...u32(db.length), ...u16(nb.length), 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, ...u32(offset)]), nb);
      offset += local.length + nb.length + db.length;
    });
    const cSize = central.reduce((a, c) => a + c.length, 0);
    const end = new Uint8Array([0x50, 0x4b, 5, 6, 0, 0, 0, 0, ...u16(files.length), ...u16(files.length), ...u32(cSize), ...u32(offset), 0, 0]);
    return new Blob([...chunks, ...central, end], { type: "application/zip" });
  }
  async function exportZip() {
    flushEdit();
    const css = await fetchThemeSource(), js = await fetchText("theme/theme.js");
    if (css == null || js == null) { toast("Zip needs the builder served over http(s). Use 'All pages' instead, or serve the folder."); return; }
    const files = [];
    const assets = new Map();   // data URI -> assets/img-N.ext (keeps exported HTML small and cacheable)
    const extract = (html) => html.replace(/data:image\/(png|jpe?g|webp|gif);base64,([A-Za-z0-9+/=]+)/g, (m, ext, b64) => {
      if (!assets.has(m)) {
        const bin = atob(b64), u8 = new Uint8Array(bin.length);
        for (let i = 0; i < bin.length; i++) u8[i] = bin.charCodeAt(i);
        assets.set(m, { name: `assets/img-${assets.size + 1}.${ext === "jpeg" ? "jpg" : ext}`, data: u8 });
      }
      return assets.get(m).name;
    });
    for (const p of state.project.pages) files.push({ name: p.slug + ".html", data: extract(await buildPage(p, true)) });
    assets.forEach((v) => files.push(v));
    files.push({ name: "theme/theme.css", data: css + "\n/* ---- project overrides ---- */\n" + themeCss() + "\n" + (state.project.customCss || "") + "\n" });
    files.push({ name: "theme/theme.js", data: js });
    const a = document.createElement("a"); a.href = URL.createObjectURL(makeZip(files));
    a.download = (state.project.name || "website").replace(/\s+/g, "-").toLowerCase() + ".zip"; a.click();
    setTimeout(() => URL.revokeObjectURL(a.href), 2000); toast("Website zip downloaded");
  }
  const exportActions = {
    zip: exportZip,
    async page() { const p = page(); download(p.slug + ".html", await buildPage(p)); },
    async all() { for (const p of state.project.pages) { download(p.slug + ".html", await buildPage(p)); await new Promise((r) => setTimeout(r, 250)); } },
    theme: exportTheme,
    json() { flushEdit(); download((state.project.name || "project").replace(/\s+/g, "-").toLowerCase() + ".json", JSON.stringify(state.project, null, 2), "application/json"); },
    import() { $("#import-file").click(); },
    reset() {
      if (!confirm("Reset to the starter project? This discards all current pages.")) return;
      state.project = defaultProject(); state.pageId = state.project.pages[0].id; state.selectedId = null;
      applyTheme(); renderAll(); commit();
    },
  };
  $("#export-menu").addEventListener("click", (e) => { const k = e.target.dataset.export; if (k) { $("#export-menu").hidden = true; exportActions[k](); } });
  $("#export-menu-btn").addEventListener("click", (e) => { e.stopPropagation(); $("#export-menu").hidden = !$("#export-menu").hidden; });
  document.addEventListener("click", () => ($("#export-menu").hidden = true));
  $("#import-file").addEventListener("change", (e) => {
    const f = e.target.files[0]; if (!f) return;
    const fr = new FileReader();
    fr.onload = () => {
      try {
        const p = JSON.parse(fr.result);
        if (!p.pages || !p.pages.length) throw new Error("no pages");
        state.project = p; state.pageId = p.pages[0].id; state.selectedId = null;
        applyTheme(); renderAll(); commit(); toast("Project imported");
      } catch (err) { toast("Could not import: invalid project file"); }
    };
    fr.readAsText(f); e.target.value = "";
  });

  /* ------------------------------------------------------------- top bar */
  function setDevice(d) {
    state.device = d; $("#frame-wrap").className = d === "desktop" ? "" : d;
    $$("[data-device]").forEach((b) => b.classList.toggle("active", b.dataset.device === d));
  }
  $$("[data-device]").forEach((b) => b.addEventListener("click", () => setDevice(b.dataset.device)));
  function togglePreview(on = !state.preview) {
    flushEdit(); state.preview = on;
    document.body.classList.toggle("previewing", on);
    $("#preview").textContent = on ? "✎ Back to editor" : "👁 Preview";
    renderCanvas();
  }
  $("#preview").addEventListener("click", () => togglePreview());
  $("#undo").addEventListener("click", undo);
  $("#redo").addEventListener("click", redo);
  $("#project-name").addEventListener("input", (e) => { state.project.name = e.target.value; save(); commitLater(); });
  $$("#left-tabs button").forEach((b) => b.addEventListener("click", () => setTab(b.dataset.tab)));
  function setTab(t) {
    state.tab = t; $$("#left-tabs button").forEach((b) => b.setAttribute("aria-selected", b.dataset.tab === t));
    $$("#left-tabs button").forEach((b) => b.classList.toggle("active", b.dataset.tab === t));
    ["pages", "components", "media", "theme"].forEach((n) => ($("#panel-" + n).hidden = n !== t));
  }
  let toastT;
  function toast(msg) { const t = $("#toast"); t.textContent = msg; t.hidden = false; clearTimeout(toastT); toastT = setTimeout(() => (t.hidden = true), 2400); }

  function renderAll() {
    $("#project-name").value = state.project.name;
    renderPagesPanel(); renderComponentsPanel(); renderThemePanel(); hooks.panels.forEach((fn) => fn()); renderCanvas(); renderInspector(); updateHistoryButtons();
  }
  const isSmall = () => window.matchMedia("(max-width: 960px)").matches;
  function closeDrawers() { document.body.classList.remove("show-left", "show-right"); }
  function toggleDrawer(side) { const on = !document.body.classList.contains("show-" + side); closeDrawers(); document.body.classList.toggle("show-" + side, on); }
  $("#toggle-left").addEventListener("click", () => toggleDrawer("left"));
  $("#toggle-right").addEventListener("click", () => toggleDrawer("right"));
  $("#drawer-backdrop").addEventListener("click", closeDrawers);
  window.addEventListener("resize", () => { if (!isSmall()) closeDrawers(); });
  // on small screens selecting a section opens the inspector shortcut hint via the ⚙ button; keep canvas visible
  $$("button[title]").forEach((b) => !b.getAttribute("aria-label") && b.setAttribute("aria-label", b.title));
  $("#left-tabs").setAttribute("role", "tablist");
  $$("#left-tabs button").forEach((b) => b.setAttribute("role", "tab"));
  /* ------------------------------------------------------------- public API */
  window.WB.hooks = hooks;
  window.WB.api = {
    state, frame, page, blockById, wrapperOf, syncBlock, flushEdit, commit, commitLater, renderInspector, renderCanvas, markSelection,
    toast, uid, esc, select, addBlock, cleanHTML, setTab, isSmall, closeDrawers, themeCss, googleFontsUrl, makeBlock,
    get doc() { return doc; },
    getDrag: () => dragState, setDrag: (d) => { dragState = d; },
    EDITABLE, NOEDIT,
    addBlockHTML(html, index) {
      flushEdit();
      const blocks = page().blocks;
      if (index == null) { const i = blocks.findIndex((b) => b.id === state.selectedId); index = i >= 0 ? i + 1 : blocks.length; }
      const b = { id: uid(), type: "custom", html: html.trim() };
      blocks.splice(index, 0, b); state.selectedId = b.id; state.pickedEl = null;
      renderCanvas(); renderInspector(); commit(); return b;
    },
    /** re-read a block after the DOM was changed programmatically, then rebuild the canvas */
    mutated(id) { syncBlock(id); flushEdit(); const keep = state.selectedId; renderCanvas(); state.selectedId = keep; markSelection(); renderInspector(); },
    dropIndexAt,
  };
  setDevice("desktop"); setTab("pages");
  window.addEventListener("beforeunload", () => { try { flushEdit(); } catch (e) {} });
})();
