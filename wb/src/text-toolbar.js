/* Text designer: heading level, bold/italic/underline/strike/code/sub/sup/highlight, link, lists,
   alignment, colour, font, size and line-height for the text element being edited on the canvas. */
(function () {
  "use strict";
  const WB = window.WB, A = () => WB.api;
  const bar = document.getElementById("textbar");
  let doc, active = null, lastRange = null;
  const FONT_OPTS = [["", "Font"], ["inherit", "Theme"], ["Georgia, serif", "Georgia"], ["'Courier New', monospace", "Mono"], ["Arial, sans-serif", "Arial"], ["'Trebuchet MS', sans-serif", "Trebuchet"], ["'Times New Roman', serif", "Times"]]
    .concat(["Inter", "Poppins", "Roboto", "Montserrat", "Nunito", "Lora", "Playfair Display"].map((f) => [`'${f}', sans-serif`, f]));

  bar.innerHTML = `
    <select data-t="tag" title="Text style" aria-label="Text style"><option value="">Style</option><option value="p">Paragraph</option>${[1, 2, 3, 4, 5, 6].map((n) => `<option value="h${n}">Heading ${n}</option>`).join("")}<option value="blockquote">Quote</option></select>
    <span class="tb-sep"></span>
    <button data-c="bold" title="Bold (Ctrl+B)"><b>B</b></button>
    <button data-c="italic" title="Italic (Ctrl+I)"><i>I</i></button>
    <button data-c="underline" title="Underline (Ctrl+U)"><u>U</u></button>
    <button data-c="strikeThrough" title="Strikethrough"><s>S</s></button>
    <button data-x="code" title="Inline code">&lt;/&gt;</button>
    <button data-c="subscript" title="Subscript">x<sub>2</sub></button>
    <button data-c="superscript" title="Superscript">x<sup>2</sup></button>
    <span class="tb-sep"></span>
    <button data-x="link" title="Link">🔗</button>
    <button data-c="unlink" title="Remove link">⛓</button>
    <button data-x="ul" title="Bullet list">• ≡</button>
    <button data-x="ol" title="Numbered list">1 ≡</button>
    <span class="tb-sep"></span>
    <button data-a="left" title="Align left">⇤</button>
    <button data-a="center" title="Align center">≡</button>
    <button data-a="right" title="Align right">⇥</button>
    <span class="tb-sep"></span>
    <label class="tb-color" title="Text colour">A<input type="color" data-col="foreColor" value="#1e293b"></label>
    <label class="tb-color tb-hl" title="Highlight">▮<input type="color" data-col="hiliteColor" value="#fde68a"></label>
    <select data-t="font" title="Font family" aria-label="Font family">${FONT_OPTS.map(([v, l]) => `<option value="${v.replace(/"/g, "&quot;")}">${l}</option>`).join("")}</select>
    <select data-t="size" title="Font size" aria-label="Font size"><option value="">Size</option>${[12, 14, 16, 18, 20, 24, 28, 32, 40, 48, 64, 80].map((n) => `<option value="${n}px">${n}</option>`).join("")}</select>
    <select data-t="lh" title="Line height" aria-label="Line height"><option value="">Line</option>${["1", "1.2", "1.4", "1.6", "1.8", "2"].map((n) => `<option>${n}</option>`).join("")}</select>
    <span class="tb-sep"></span>
    <button data-x="clear" title="Clear formatting">⌫ Clear</button>`;

  const q = (sel) => [...bar.querySelectorAll(sel)];
  const blockId = () => active && active.closest(".wb-block") && active.closest(".wb-block").dataset.id;
  function setDisabled(on) { bar.classList.toggle("is-off", on); q("button,select,input").forEach((n) => (n.disabled = on)); }
  setDisabled(true);

  function restore() {
    if (!active || !active.isConnected) return false;
    const win = doc.defaultView;
    win.focus(); active.focus();
    if (lastRange) { const sel = win.getSelection(); sel.removeAllRanges(); sel.addRange(lastRange); }
    return true;
  }
  const changed = () => { const id = blockId(); if (id) A().syncBlock(id); refresh(); };

  function refresh() {
    if (!active) return;
    q("[data-c]").forEach((b) => { try { b.classList.toggle("on", !!doc.queryCommandState(b.dataset.c)); } catch (e) {} });
    const tag = active.tagName.toLowerCase();
    bar.querySelector('[data-t="tag"]').value = /^(h[1-6]|p|blockquote)$/.test(tag) ? tag : "";
    q("[data-a]").forEach((b) => b.classList.toggle("on", active.style.textAlign === b.dataset.a));
    bar.querySelector('[data-t="tag"]').disabled = !/^(h[1-6]|p|blockquote)$/.test(tag) || !!active.closest("a,li");
    bar.querySelector('[data-t="size"]').value = active.style.fontSize || "";
    bar.querySelector('[data-t="lh"]').value = active.style.lineHeight || "";
    bar.querySelector('[data-t="font"]').value = active.style.fontFamily ? active.style.fontFamily.replace(/"/g, "'") : "";
  }

  function replaceTag(tag) {
    if (!restore()) return;
    const old = active, n = doc.createElement(tag);
    [...old.attributes].forEach((a) => n.setAttribute(a.name, a.value));
    n.innerHTML = old.innerHTML;
    old.replaceWith(n); active = n; n.focus();
    const r = doc.createRange(); r.selectNodeContents(n); r.collapse(false);
    const sel = doc.defaultView.getSelection(); sel.removeAllRanges(); sel.addRange(r); lastRange = r;
    changed();
  }
  function wrapSelection(tag) {
    const sel = doc.defaultView.getSelection();
    if (!sel.rangeCount || sel.isCollapsed) return A().toast("Select some text first");
    const r = sel.getRangeAt(0), el = r.commonAncestorContainer.nodeType === 1 ? r.commonAncestorContainer : r.commonAncestorContainer.parentElement;
    const existing = el.closest(tag);
    if (existing && active.contains(existing)) { const f = doc.createDocumentFragment(); while (existing.firstChild) f.appendChild(existing.firstChild); existing.replaceWith(f); }
    else { const w = doc.createElement(tag); w.appendChild(r.extractContents()); r.insertNode(w); }
    changed();
  }
  function toList(type) {
    if (!restore()) return;
    if (active.tagName === "LI") { // toggle back to paragraph
      const list = active.parentElement, p = doc.createElement("p"); p.innerHTML = active.innerHTML;
      list.replaceWith(p); active = p; return changedRender();
    }
    if (!/^(P|H[1-6]|BLOCKQUOTE)$/.test(active.tagName) || active.closest("a")) return A().toast("Lists work on paragraphs and headings");
    const list = doc.createElement(type), li = doc.createElement("li");
    li.innerHTML = active.innerHTML; list.appendChild(li); active.replaceWith(list); active = li; changedRender();
  }
  function changedRender() { const id = blockId() || (active && active.closest && active.closest(".wb-block").dataset.id); A().mutated(id || A().state.selectedId); }

  bar.addEventListener("mousedown", (e) => { if (!/^(SELECT|INPUT|OPTION)$/.test(e.target.tagName)) e.preventDefault(); });
  bar.addEventListener("click", (e) => {
    const b = e.target.closest("button"); if (!b || !restore()) return;
    if (b.dataset.c) { doc.execCommand("styleWithCSS", false, false); doc.execCommand(b.dataset.c, false, null); return changed(); }
    if (b.dataset.a) { active.style.textAlign = active.style.textAlign === b.dataset.a ? "" : b.dataset.a; return changed(); }
    const x = b.dataset.x;
    if (x === "code") wrapSelection("code");
    else if (x === "link") {
      const url = prompt("Link URL (https://…, #anchor, page.html):", "https://");
      if (url) { doc.execCommand("createLink", false, url); changed(); }
    } else if (x === "ul" || x === "ol") toList(x);
    else if (x === "clear") {
      doc.execCommand("removeFormat"); doc.execCommand("unlink");
      active.removeAttribute("style"); active.querySelectorAll("[style]").forEach((n) => n.removeAttribute("style"));
      active.querySelectorAll("b,i,u,s,strike,code,sub,sup,mark,font").forEach((n) => n.replaceWith(...n.childNodes));
      changed();
    }
  });
  bar.addEventListener("change", (e) => {
    const t = e.target, k = t.dataset.t;
    if (t.dataset.col) { if (!restore()) return; doc.execCommand("styleWithCSS", false, true); doc.execCommand(t.dataset.col, false, t.value); return changed(); }
    if (!k) return;
    if (k === "tag") return t.value && replaceTag(t.value);
    if (!restore()) return;
    const css = { size: "fontSize", font: "fontFamily", lh: "lineHeight" }[k];
    active.style[css] = t.value; if (!active.getAttribute("style")) active.removeAttribute("style");
    changed();
  });
  bar.addEventListener("input", (e) => { if (e.target.dataset.col && restore()) { doc.execCommand("styleWithCSS", false, true); doc.execCommand(e.target.dataset.col, false, e.target.value); changed(); } });

  WB.hooks.canvasReady.push((d) => {
    doc = d;
    d.addEventListener("focusin", (e) => { const el = e.target.closest && e.target.closest("[contenteditable=true]"); if (el) { active = el; setDisabled(false); refresh(); } });
    d.addEventListener("selectionchange", () => {
      const sel = d.defaultView.getSelection();
      if (sel.rangeCount && active && active.contains(sel.anchorNode)) { lastRange = sel.getRangeAt(0).cloneRange(); refresh(); }
    });
    d.addEventListener("mousedown", (e) => { if (!e.target.closest("[contenteditable=true]") && !e.target.closest("#wb-root")) return; });
    d.addEventListener("click", (e) => { if (!e.target.closest("[contenteditable=true]")) { /* keep toolbar enabled until another block is clicked */ } });
  });
  WB.hooks.afterRender.push(() => { if (active && !active.isConnected) { active = null; setDisabled(true); } });
})();
