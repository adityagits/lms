/* Media: image library (upload / reuse / drag), image inspector (width, align, ratio, fit, corners),
   on-canvas resize handle, and a crop / rotate / resize editor. */
(function () {
  "use strict";
  const WB = window.WB, A = () => WB.api, $ = (s, r = document) => r.querySelector(s);
  const KEY = "wb-media-v1";
  const getMedia = () => { try { return JSON.parse(localStorage.getItem(KEY)) || []; } catch (e) { return []; } };
  const setMedia = (m) => { try { localStorage.setItem(KEY, JSON.stringify(m)); return true; } catch (e) { A().toast("Browser storage is full — delete some library images"); return false; } };
  const esc = (s) => String(s ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));

  /* ---------- load + downscale an uploaded file to a data URL ---------- */
  function fileToImage(file, maxW = 1600) {
    return new Promise((resolve, reject) => {
      const fr = new FileReader();
      fr.onerror = reject;
      fr.onload = () => {
        if (/svg|gif/.test(file.type)) return resolve({ data: fr.result, name: file.name, w: 0, h: 0 });
        const im = new Image();
        im.onerror = reject;
        im.onload = () => {
          const k = Math.min(1, maxW / im.naturalWidth), w = Math.round(im.naturalWidth * k), h = Math.round(im.naturalHeight * k);
          const c = document.createElement("canvas"); c.width = w; c.height = h;
          const ctx = c.getContext("2d");
          const png = file.type === "image/png";
          if (!png) { ctx.fillStyle = "#fff"; ctx.fillRect(0, 0, w, h); }
          ctx.drawImage(im, 0, 0, w, h);
          resolve({ data: c.toDataURL(png ? "image/png" : "image/jpeg", 0.85), name: file.name, w, h });
        };
        im.src = fr.result;
      };
      fr.readAsDataURL(file);
    });
  }
  async function addFiles(files) {
    const list = getMedia();
    for (const f of files) {
      if (!f.type.startsWith("image/")) continue;
      try { const r = await fileToImage(f); list.push({ id: A().uid(), ...r }); } catch (e) { A().toast("Could not read " + f.name); }
    }
    setMedia(list); renderPanel();
    return list;
  }

  /* ---------- Media panel ---------- */
  function renderPanel() {
    const el = $("#panel-media"); if (!el) return;
    const m = getMedia();
    el.innerHTML = `<div class="section-label">Image library</div>
      <label class="btn primary" style="display:block;text-align:center;cursor:pointer">+ Upload images<input type="file" id="media-up" accept="image/*" multiple hidden></label>
      <p class="hint" style="margin:8px 0">Images are resized to max 1600px and stored in your browser. Click to insert or replace the selected image; drag onto the page. Exported zips save them as files in <code>assets/</code>.</p>
      ${m.length ? `<div class="media-grid">${m.map((i) => `<div class="media-item" draggable="true" data-mid="${i.id}" title="${esc(i.name)}"><img src="${i.data}" alt=""><button data-mdel="${i.id}" title="Delete">✕</button></div>`).join("")}</div>` : `<p class="hint">No images yet.</p>`}`;
  }
  const panel = () => $("#panel-media");
  document.addEventListener("change", async (e) => { if (e.target.id === "media-up") { await addFiles(e.target.files); e.target.value = ""; } });
  document.addEventListener("click", (e) => {
    const del = e.target.closest("[data-mdel]");
    if (del && panel().contains(del)) { setMedia(getMedia().filter((i) => i.id !== del.dataset.mdel)); return renderPanel(); }
    const it = e.target.closest(".media-item");
    if (it && panel().contains(it)) useImage(getMedia().find((i) => i.id === it.dataset.mid));
  });
  document.addEventListener("dragstart", (e) => {
    const it = e.target.closest && e.target.closest(".media-item"); if (!it) return;
    const m = getMedia().find((i) => i.id === it.dataset.mid);
    A().setDrag({ kind: "el-new", html: `<img class="wb-media" src="${m.data}" alt="${esc(m.name.replace(/\.[^.]+$/, ""))}">` });
    e.dataTransfer.effectAllowed = "copy"; e.dataTransfer.setData("text/plain", "img");
  });
  function pickedImg() { const p = A().state.pickedEl; return p && p.isConnected && p.tagName === "IMG" ? p : null; }
  function useImage(item) {
    if (!item) return;
    const p = pickedImg();
    if (p) { p.setAttribute("src", item.data); if (!p.alt) p.alt = item.name.replace(/\.[^.]+$/, ""); commitImg(); }
    else { WB.nesting.addHTML(`<img class="wb-media" src="${item.data}" alt="${esc(item.name.replace(/\.[^.]+$/, ""))}">`); }
    if (A().isSmall()) A().closeDrawers();
  }
  function commitImg() { const id = A().state.selectedId; A().syncBlock(id); A().flushEdit(); A().renderInspector(); positionOverlay(); }

  /* ---------- Inspector for a picked image ---------- */
  const seg = (k, opts, cur) => `<div class="seg">${opts.map(([v, l]) => `<button data-mk="${k}" data-mv="${v}" class="${cur === v ? "active" : ""}">${l}</button>`).join("")}</div>`;
  WB.hooks.inspectorPicked.push(({ p }) => {
    if (p.tagName !== "IMG") return "";
    const st = p.style, src = p.getAttribute("src") || "";
    const width = parseInt(st.width) || 100, ml = st.marginLeft, mr = st.marginRight;
    const align = ml === "auto" && mr === "auto" ? "center" : ml === "auto" ? "right" : st.display === "block" ? "left" : "";
    const ratio = (st.aspectRatio || "").replace(/\s/g, "").replace("/", ":");
    const radius = st.borderRadius === "50%" ? "circle" : st.borderRadius === "0px" || st.borderRadius === "0" ? "square" : st.borderRadius ? "round" : "";
    return `<div class="field"><label>Image URL</label><input type="text" data-attr="src" value="${esc(src.startsWith("data:") ? "" : src)}" placeholder="${src.startsWith("data:") ? "(embedded image)" : "https://…"}"></div>
      <div class="btn-row" style="margin:0 0 10px"><label class="btn sm" style="cursor:pointer">⬆ Upload<input type="file" id="img-up" accept="image/*" hidden></label>
        <button class="btn sm" data-mact="library">🖼 Library</button><button class="btn sm" data-mact="crop">✂ Crop / resize</button></div>
      <div class="field"><label>Alt text</label><input type="text" data-attr="alt" value="${esc(p.getAttribute("alt"))}"></div>
      <div class="range-field"><div class="top"><span>Width</span><span id="img-w-val">${width}%</span></div><input type="range" data-mrange="width" min="10" max="100" value="${width}"></div>
      <div class="field"><label>Align</label>${seg("align", [["", "Auto"], ["left", "Left"], ["center", "Center"], ["right", "Right"]], align)}</div>
      <div class="field"><label>Aspect ratio</label><select data-msel="ratio">${[["", "Original"], ["1:1", "Square 1:1"], ["4:3", "4:3"], ["3:2", "3:2"], ["16:9", "16:9"], ["3:4", "Portrait 3:4"], ["9:16", "Story 9:16"]].map(([v, l]) => `<option value="${v}" ${v === ratio ? "selected" : ""}>${l}</option>`).join("")}</select></div>
      <div class="field"><label>Fit (when ratio set)</label>${seg("fit", [["cover", "Cover"], ["contain", "Contain"]], st.objectFit || "cover")}</div>
      <div class="field"><label>Corners</label>${seg("radius", [["", "Theme"], ["square", "Square"], ["round", "Round"], ["circle", "Circle"]], radius)}</div>
      <div class="btn-row" style="margin-top:0"><button class="btn sm" data-mact="reset">Reset image style</button></div>`;
  });
  function applyStyle(k, v) {
    const p = pickedImg(); if (!p) return;
    const st = p.style;
    if (k === "width") { st.width = v >= 100 ? "" : v + "%"; st.height = ""; }
    else if (k === "align") {
      st.marginLeft = st.marginRight = ""; st.display = "";
      if (v) { st.display = "block"; if (v === "center") st.marginLeft = st.marginRight = "auto"; if (v === "right") st.marginLeft = "auto"; }
    } else if (k === "ratio") { st.aspectRatio = v ? v.replace(":", " / ") : ""; st.objectFit = v ? st.objectFit || "cover" : ""; if (v) st.width = st.width || "100%"; }
    else if (k === "fit") st.objectFit = v;
    else if (k === "radius") st.borderRadius = { "": "", square: "0", round: "24px", circle: "50%" }[v];
    if (!p.getAttribute("style")) p.removeAttribute("style");
    commitImg();
  }
  document.addEventListener("click", (e) => {
    const b = e.target.closest("#inspector [data-mk]");
    if (b) return applyStyle(b.dataset.mk, b.dataset.mv);
    const a = e.target.closest("#inspector [data-mact]"); if (!a) return;
    if (a.dataset.mact === "library") { A().setTab("media"); if (A().isSmall()) document.body.classList.add("show-left"); }
    else if (a.dataset.mact === "crop") openCropper(pickedImg());
    else if (a.dataset.mact === "reset") { const p = pickedImg(); if (p) { p.removeAttribute("style"); commitImg(); } }
  });
  document.addEventListener("input", (e) => {
    const r = e.target.closest && e.target.closest("[data-mrange]"); if (!r) return;
    const p = pickedImg(); if (!p) return;
    p.style.width = +r.value >= 100 ? "" : r.value + "%"; p.style.height = "";
    if (!p.getAttribute("style")) p.removeAttribute("style");
    const o = $("#img-w-val"); if (o) o.textContent = r.value + "%";
    A().syncBlock(A().state.selectedId); positionOverlay();
  });
  document.addEventListener("change", async (e) => {
    if (e.target.dataset && e.target.dataset.msel) return applyStyle(e.target.dataset.msel, e.target.value);
    if (e.target.id === "img-up" && e.target.files[0]) {
      const list = await addFiles([e.target.files[0]]), it = list[list.length - 1], p = pickedImg();
      if (p && it) { p.setAttribute("src", it.data); commitImg(); A().toast("Image uploaded & added to library"); }
    }
  });

  /* ---------- on-canvas resize handle ---------- */
  let doc, rs, label;
  function buildOverlay() {
    rs = doc.createElement("div"); rs.className = "wb-rs"; rs.innerHTML = `<i data-r="se"></i><i data-r="e"></i><i data-r="s"></i><span></span>`;
    label = rs.querySelector("span"); doc.body.appendChild(rs);
    let drag = null;
    rs.addEventListener("pointerdown", (e) => {
      const h = e.target.dataset.r, p = pickedImg(); if (!h || !p) return;
      e.preventDefault(); rs.setPointerCapture(e.pointerId);
      const pr = p.parentElement.getBoundingClientRect(), r = p.getBoundingClientRect();
      drag = { h, sx: e.clientX, sw: r.width, pw: pr.width };
    });
    rs.addEventListener("pointermove", (e) => {
      if (!drag) return; const p = pickedImg(); if (!p) return;
      const w = Math.max(40, drag.sw + (e.clientX - drag.sx)), pct = Math.max(10, Math.min(100, Math.round((w / drag.pw) * 100)));
      p.style.width = pct >= 100 ? "" : pct + "%"; p.style.height = "";
      positionOverlay(); label.textContent = pct + "%";
    });
    rs.addEventListener("pointerup", () => { if (!drag) return; drag = null; const p = pickedImg(); if (p && !p.getAttribute("style")) p.removeAttribute("style"); commitImg(); });
  }
  function positionOverlay() {
    if (!rs) return;
    const p = pickedImg();
    if (!p || A().state.preview) { rs.style.display = "none"; return; }
    const r = p.getBoundingClientRect(), win = doc.defaultView;
    Object.assign(rs.style, { display: "block", left: r.left + win.scrollX + "px", top: r.top + win.scrollY + "px", width: r.width + "px", height: r.height + "px" });
    label.textContent = Math.round(r.width) + " × " + Math.round(r.height);
  }
  WB.hooks.canvasReady.push((d) => {
    doc = d; buildOverlay();
    d.addEventListener("click", () => setTimeout(positionOverlay, 0));
    d.defaultView.addEventListener("resize", positionOverlay);
  });
  WB.hooks.afterRender.push(() => { if (rs) rs.style.display = "none"; });
  window.addEventListener("resize", () => setTimeout(positionOverlay, 50));
  WB.hooks.panels.push(renderPanel);

  /* ---------- crop / rotate / resize editor ---------- */
  const dlg = document.createElement("dialog"); dlg.id = "cropper";
  dlg.innerHTML = `<h3>Crop &amp; resize image</h3>
    <div class="crop-tools">
      <label>Ratio <select id="cr-ratio"><option value="">Free</option><option value="orig">Original</option><option value="1">1:1</option><option value="1.3333">4:3</option><option value="1.5">3:2</option><option value="1.7778">16:9</option><option value="0.75">3:4</option><option value="0.5625">9:16</option></select></label>
      <label>Output width <select id="cr-out"><option value="0">Crop size</option><option value="1600">1600 px</option><option value="1200">1200 px</option><option value="800">800 px</option><option value="400">400 px</option><option value="200">200 px</option></select></label>
      <label>Format <select id="cr-fmt"><option value="image/jpeg">JPEG</option><option value="image/png">PNG</option><option value="image/webp">WebP</option></select></label>
      <button type="button" class="btn sm" id="cr-rl" title="Rotate left">↺</button><button type="button" class="btn sm" id="cr-rr" title="Rotate right">↻</button>
    </div>
    <div class="crop-stage" id="cr-stage"><canvas id="cr-canvas"></canvas><div id="cr-rect"><i data-h="nw"></i><i data-h="n"></i><i data-h="ne"></i><i data-h="e"></i><i data-h="se"></i><i data-h="s"></i><i data-h="sw"></i><i data-h="w"></i></div></div>
    <p class="hint" id="cr-info" style="margin:8px 0 0"></p>
    <div class="modal-actions"><button type="button" class="btn" id="cr-cancel">Cancel</button><button type="button" class="btn" id="cr-reset">Reset</button><button type="button" class="btn primary" id="cr-apply">Apply</button></div>`;
  document.body.appendChild(dlg);
  const cv = $("#cr-canvas", dlg), rectEl = $("#cr-rect", dlg), stage = $("#cr-stage", dlg), info = $("#cr-info", dlg);
  let target = null, src = null, orig = null, scale = 1, rect = { x: 0, y: 0, w: 1, h: 1 }, ratio = null, drag = null;

  function openCropper(img) {
    if (!img) return;
    const im = new Image(); im.crossOrigin = "anonymous";
    im.onerror = () => A().toast("This image can't be edited (external host blocks it). Upload it first, then crop.");
    im.onload = () => {
      target = img; const k = Math.min(1, 4096 / Math.max(im.naturalWidth, im.naturalHeight));
      src = document.createElement("canvas"); src.width = Math.round(im.naturalWidth * k); src.height = Math.round(im.naturalHeight * k);
      src.getContext("2d").drawImage(im, 0, 0, src.width, src.height);
      orig = document.createElement("canvas"); orig.width = src.width; orig.height = src.height; orig.getContext("2d").drawImage(src, 0, 0);
      $("#cr-ratio", dlg).value = ""; ratio = null; $("#cr-out", dlg).value = "0";
      $("#cr-fmt", dlg).value = /^data:image\/png/.test(img.src) ? "image/png" : "image/jpeg";
      dlg.showModal(); setup();
    };
    im.src = img.getAttribute("src");
  }
  function setup() {
    const maxW = Math.min(640, window.innerWidth - 90), maxH = Math.min(420, window.innerHeight - 320);
    scale = Math.min(maxW / src.width, maxH / src.height, 1);
    cv.width = src.width; cv.height = src.height; cv.style.width = src.width * scale + "px"; cv.style.height = src.height * scale + "px";
    cv.getContext("2d").drawImage(src, 0, 0);
    stage.style.width = src.width * scale + "px"; stage.style.height = src.height * scale + "px";
    rect = { x: 0, y: 0, w: src.width, h: src.height }; if (ratio) fitRatio(); draw();
  }
  function fitRatio() {
    const cx = rect.x + rect.w / 2, cy = rect.y + rect.h / 2;
    let w = Math.min(rect.w, rect.h * ratio), h = w / ratio;
    rect = { x: Math.max(0, Math.min(src.width - w, cx - w / 2)), y: Math.max(0, Math.min(src.height - h, cy - h / 2)), w, h };
  }
  function draw() {
    Object.assign(rectEl.style, { left: rect.x * scale + "px", top: rect.y * scale + "px", width: rect.w * scale + "px", height: rect.h * scale + "px" });
    const out = +$("#cr-out", dlg).value, ow = out ? Math.min(out, Math.round(rect.w)) : Math.round(rect.w);
    info.textContent = `Selection ${Math.round(rect.w)} × ${Math.round(rect.h)} px  →  output ${ow} × ${Math.round(ow * rect.h / rect.w)} px`;
  }
  $("#cr-ratio", dlg).addEventListener("change", (e) => { const v = e.target.value; ratio = v === "orig" ? src.width / src.height : v ? +v : null; if (ratio) fitRatio(); draw(); });
  $("#cr-out", dlg).addEventListener("change", draw);
  function rotate(dir) {
    const c = document.createElement("canvas"); c.width = src.height; c.height = src.width;
    const x = c.getContext("2d"); x.translate(c.width / 2, c.height / 2); x.rotate((dir * Math.PI) / 2); x.drawImage(src, -src.width / 2, -src.height / 2);
    src = c; setup();
  }
  $("#cr-rl", dlg).onclick = () => rotate(-1); $("#cr-rr", dlg).onclick = () => rotate(1);
  $("#cr-reset", dlg).onclick = () => { src.width = orig.width; src.height = orig.height; src.getContext("2d").drawImage(orig, 0, 0); ratio = null; $("#cr-ratio", dlg).value = ""; setup(); };
  $("#cr-cancel", dlg).onclick = () => dlg.close();
  $("#cr-apply", dlg).onclick = () => {
    const out = +$("#cr-out", dlg).value, ow = Math.round(out ? Math.min(out, rect.w) : rect.w), oh = Math.round((ow * rect.h) / rect.w);
    const c = document.createElement("canvas"); c.width = ow; c.height = oh;
    const x = c.getContext("2d"), fmt = $("#cr-fmt", dlg).value;
    if (fmt === "image/jpeg") { x.fillStyle = "#fff"; x.fillRect(0, 0, ow, oh); }
    x.drawImage(src, rect.x, rect.y, rect.w, rect.h, 0, 0, ow, oh);
    target.setAttribute("src", c.toDataURL(fmt, 0.88)); target.removeAttribute("width"); target.removeAttribute("height");
    dlg.close(); commitImg(); A().toast(`Image updated (${ow} × ${oh})`);
  };
  stage.addEventListener("pointerdown", (e) => {
    const h = e.target.dataset.h || (e.target === rectEl ? "move" : null); if (!h) return;
    e.preventDefault(); stage.setPointerCapture(e.pointerId);
    drag = { h, sx: e.clientX, sy: e.clientY, s: { ...rect } };
  });
  stage.addEventListener("pointermove", (e) => {
    if (!drag) return;
    const dx = (e.clientX - drag.sx) / scale, dy = (e.clientY - drag.sy) / scale, s = drag.s, W = src.width, H = src.height, h = drag.h;
    if (h === "move") { rect.x = Math.max(0, Math.min(W - s.w, s.x + dx)); rect.y = Math.max(0, Math.min(H - s.h, s.y + dy)); return draw(); }
    let x = s.x, y = s.y, w = s.w, hh = s.h;
    if (h.includes("e")) w = s.w + dx; if (h.includes("s")) hh = s.h + dy;
    if (h.includes("w")) { x = s.x + dx; w = s.w - dx; } if (h.includes("n")) { y = s.y + dy; hh = s.h - dy; }
    if (x < 0) { w += x; x = 0; } if (y < 0) { hh += y; y = 0; }
    if (x + w > W) w = W - x; if (y + hh > H) hh = H - y;
    w = Math.max(16, w); hh = Math.max(16, hh);
    if (ratio) {
      if (h === "n" || h === "s") w = hh * ratio;
      else if (h === "e" || h === "w") hh = w / ratio;
      else if (Math.abs(w - s.w) / s.w > Math.abs(hh - s.h) / s.h) hh = w / ratio; else w = hh * ratio;
      const aR = h.includes("w"), aB = h.includes("n");
      const f = Math.min(1, (aR ? s.x + s.w : W - s.x) / w, (aB ? s.y + s.h : H - s.y) / hh);
      w *= f; hh *= f; x = aR ? s.x + s.w - w : s.x; y = aB ? s.y + s.h - hh : s.y;
    }
    rect = { x, y, w, h: hh }; draw();
  });
  stage.addEventListener("pointerup", () => (drag = null));
})();
