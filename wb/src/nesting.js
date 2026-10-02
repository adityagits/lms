/* Nested layout: drop elements into any column / container / card, and move, duplicate or delete
   any nested element with its hover handle. */
(function () {
  "use strict";
  const WB = window.WB, A = () => WB.api;
  const SLOT = ".wb-col,.wb-container,.wb-card,.wb-stack,.wb-tabs__panel,.wb-step,.wb-modal__box";
  let doc, box, chip, line, unit = null, hideT;

  const inBlock = (el) => el && el.closest && el.closest(".wb-block");
  const kids = (slot) => [...slot.children].filter((c) => !c.classList.contains("wb-tb"));
  const isRow = (list) => list.length > 1 && Math.abs(list[0].getBoundingClientRect().top - list[1].getBoundingClientRect().top) < 10;

  function findSlot(target) {
    let t = target && target.nodeType === 1 ? target : target && target.parentElement;
    while (t && t !== doc.body) { if (t.matches && t.matches(SLOT) && inBlock(t)) return t; t = t.parentElement; }
    return null;
  }
  function locate(slot, x, y) {
    const list = kids(slot).filter((c) => c !== (A().getDrag() || {}).node);
    if (!list.length) return { ref: null, rect: slot.getBoundingClientRect(), row: false, before: true, list };
    const row = isRow(list);
    let best = null, bd = Infinity;
    list.forEach((c) => {
      const r = c.getBoundingClientRect(), cx = r.left + r.width / 2, cy = r.top + r.height / 2, d = (cx - x) ** 2 + (cy - y) ** 2;
      if (d < bd) { bd = d; best = { c, r }; }
    });
    const before = row ? x < best.r.left + best.r.width / 2 : y < best.r.top + best.r.height / 2;
    return { ref: before ? best.c : best.c.nextElementSibling, rect: best.r, row, before, list };
  }
  function drawLine(loc) {
    const sy = doc.defaultView.scrollY, sx = doc.defaultView.scrollX, r = loc.rect;
    line.style.display = "block";
    if (loc.row) Object.assign(line.style, { left: (loc.before ? r.left : r.right) + sx - 2 + "px", top: r.top + sy + "px", width: "4px", height: r.height + "px" });
    else if (loc.list.length) Object.assign(line.style, { left: r.left + sx + "px", top: (loc.before ? r.top : r.bottom) + sy - 2 + "px", width: r.width + "px", height: "4px" });
    else Object.assign(line.style, { left: r.left + sx + 8 + "px", top: r.top + sy + 8 + "px", width: Math.max(0, r.width - 16) + "px", height: "4px" });
  }

  function htmlToNode(html) { const t = doc.createElement("template"); t.innerHTML = html.trim(); return t.content.firstElementChild; }

  function onDragOver(e) {
    const d = A().getDrag(); if (!d || !String(d.kind).startsWith("el")) return;
    e.preventDefault();
    const slot = findSlot(e.target);
    if (!slot) { // between sections: show a block-level drop line
      const { top } = A().dropIndexAt(e.clientY);
      Object.assign(line.style, { display: "block", left: "0", width: "100%", height: "4px", top: Math.max(0, top - 2) + "px" });
      return;
    }
    if (d.node && (d.node === slot || d.node.contains(slot))) { line.style.display = "none"; return; }
    drawLine(locate(slot, e.clientX, e.clientY));
  }
  function onDrop(e) {
    const d = A().getDrag(); if (!d || !String(d.kind).startsWith("el")) return;
    e.preventDefault(); line.style.display = "none"; A().setDrag(null);
    const slot = findSlot(e.target);
    if (!slot) {
      const { idx } = A().dropIndexAt(e.clientY);
      const inner = d.kind === "el-new" ? d.html : d.node.outerHTML;
      if (d.kind === "el-move") { const sid = inBlock(d.node).dataset.id; d.node.remove(); A().mutated(sid); }
      A().addBlockHTML(`<section class="wb-section"><div class="wb-container">${inner}</div></section>`, idx);
      return;
    }
    if (d.node && (d.node === slot || d.node.contains(slot))) return;
    const loc = locate(slot, e.clientX, e.clientY);
    const node = d.kind === "el-new" ? htmlToNode(d.html) : d.node;
    const srcId = d.node ? inBlock(d.node).dataset.id : null, dstId = inBlock(slot).dataset.id;
    slot.insertBefore(node, loc.ref);
    if (srcId && srcId !== dstId) A().syncBlock(srcId);
    A().mutated(dstId);
  }

  /* ---- hover handle for any nested element ---- */
  function findUnit(t) {
    let el = t && t.nodeType === 1 ? t : t && t.parentElement;
    while (el && el !== doc.body) {
      if (el.classList.contains("wb-block") || el.classList.contains("wb-eltb")) return null;
      if (el.parentElement && el.parentElement.matches(SLOT) && inBlock(el) && !el.classList.contains("wb-tb")) return el;
      el = el.parentElement;
    }
    return null;
  }
  function place() {
    if (!unit || !unit.isConnected) return hide();
    const r = unit.getBoundingClientRect(), sy = doc.defaultView.scrollY, sx = doc.defaultView.scrollX;
    box.style.cssText = `display:block;left:${r.left + sx}px;top:${r.top + sy}px;width:${r.width}px;height:${r.height}px`;
    chip.style.display = "flex";
    chip.style.left = Math.max(0, r.left + sx) + "px"; chip.style.top = Math.max(0, r.top + sy - 24) + "px";
    chip.querySelector(".nm").textContent = "<" + unit.tagName.toLowerCase() + ">";
  }
  function hide() { unit = null; if (box) { box.style.display = "none"; chip.style.display = "none"; } }

  function build() {
    box = doc.createElement("div"); box.className = "wb-elbox";
    chip = doc.createElement("div"); chip.className = "wb-eltb"; chip.contentEditable = "false";
    chip.innerHTML = `<span class="grab" draggable="true" title="Drag to move">⠿ <span class="nm"></span></span><button data-e="parent" title="Select parent element">↰</button><button data-e="up" title="Move up">↑</button><button data-e="down" title="Move down">↓</button><button data-e="dup" title="Duplicate">⧉</button><button data-e="del" title="Delete">🗑</button>`;
    doc.body.append(box, chip);
    line = doc.createElement("div"); line.className = "wb-drop"; line.style.display = "none"; doc.body.appendChild(line);
  }

  WB.hooks.canvasReady.push((d) => {
    doc = d; build();
    d.addEventListener("dragover", onDragOver); d.addEventListener("drop", onDrop);
    d.addEventListener("dragend", () => { line.style.display = "none"; });
    d.addEventListener("mouseover", (e) => {
      if (A().state.preview) return;
      if (e.target.closest && e.target.closest(".wb-eltb")) { clearTimeout(hideT); return; }
      const u = findUnit(e.target);
      clearTimeout(hideT);
      if (u) { unit = u; place(); } else hideT = setTimeout(hide, 250);
    });
    d.addEventListener("scroll", hide, true);
    chip.addEventListener("dragstart", (e) => {
      if (!unit) return;
      A().setDrag({ kind: "el-move", node: unit });
      e.dataTransfer.effectAllowed = "move"; e.dataTransfer.setData("text/plain", "el"); e.dataTransfer.setDragImage(unit, 10, 10);
    });
    chip.addEventListener("click", (e) => {
      const b = e.target.closest("button"); if (!b || !unit) return;
      const id = inBlock(unit).dataset.id, u = unit;
      if (b.dataset.e === "parent") { const pu = findUnit(u.parentElement); if (pu) { unit = pu; place(); } return; }
      if (b.dataset.e === "del") u.remove();
      else if (b.dataset.e === "dup") u.after(u.cloneNode(true));
      else if (b.dataset.e === "up" && u.previousElementSibling && !u.previousElementSibling.classList.contains("wb-tb")) u.parentElement.insertBefore(u, u.previousElementSibling);
      else if (b.dataset.e === "down" && u.nextElementSibling) u.parentElement.insertBefore(u.nextElementSibling, u);
      hide(); A().mutated(id);
    });
  });
  WB.hooks.afterRender.push(hide);

  /* click-to-add: append the element to the selected section's container, or create a new section */
  WB.nesting = {
    addHTML(html) {
      const st = A().state, id = st.selectedId, w = id && A().wrapperOf(id);
      if (w) {
        const sec = w.firstElementChild, slot = sec.querySelector(".wb-container") || sec;
        slot.appendChild(htmlToNode(html)); A().mutated(id);
      } else A().addBlockHTML(`<section class="wb-section"><div class="wb-container">${html}</div></section>`);
    },
    addClick(elId) { const el = WB.elementById[elId]; this.addHTML(el.html()); A().toast(`Added ${el.label}`); },
  };
})();
