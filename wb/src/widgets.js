/* Widget settings in the inspector: video (YouTube / Vimeo / MP4), HTML embed, map,
   column count, slider (autoplay, add/remove slides), countdown date, hero video, cart checkout, popup auto-open. */
(function () {
  "use strict";
  const WB = window.WB, A = () => WB.api, $ = (s, r = document) => r.querySelector(s);
  const esc = (s) => String(s ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
  const selSec = () => { const w = A().wrapperOf(A().state.selectedId); return w && w.firstElementChild; };
  const done = (rebuild) => { const id = A().state.selectedId; rebuild ? A().mutated(id) : (A().syncBlock(id), A().flushEdit(), A().renderInspector()); };

  /* ---------- video URL helpers ---------- */
  function videoHTML(url, o) {
    url = url.trim();
    const yt = url.match(/(?:youtube\.com\/(?:watch\?(?:.*&)?v=|embed\/|shorts\/)|youtu\.be\/)([\w-]{11})/);
    const vm = url.match(/vimeo\.com\/(?:video\/)?(\d+)/);
    if (/\.(mp4|webm|ogg)(\?.*)?$/i.test(url)) return `<video src="${esc(url)}" ${o.controls ? "controls" : ""} ${o.autoplay ? "autoplay muted playsinline" : ""} ${o.loop ? "loop" : ""} preload="metadata"></video>`;
    let src = url;
    if (yt) { const q = []; if (o.autoplay) q.push("autoplay=1", "mute=1"); if (o.loop) q.push("loop=1", "playlist=" + yt[1]); src = `https://www.youtube.com/embed/${yt[1]}${q.length ? "?" + q.join("&") : ""}`; }
    else if (vm) { const q = []; if (o.autoplay) q.push("autoplay=1", "muted=1"); if (o.loop) q.push("loop=1"); src = `https://player.vimeo.com/video/${vm[1]}${q.length ? "?" + q.join("&") : ""}`; }
    return `<iframe src="${esc(src)}" title="Video" allowfullscreen loading="lazy" allow="autoplay; fullscreen; picture-in-picture"></iframe>`;
  }
  const flag = (box, k) => box.dataset[k] === "1";

  WB.hooks.inspectorPicked.push(({ p }) => {
    const vid = p.closest(".wb-video") || (p.tagName === "IFRAME" || p.tagName === "VIDEO" ? p.parentElement : null);
    if (vid && vid.classList.contains("wb-video")) {
      const m = vid.querySelector("iframe,video"), cur = m ? m.getAttribute("src") : "";
      return `<div class="field"><label>Video link (YouTube, Vimeo, or .mp4/.webm)</label><input type="text" id="w-vurl" value="${esc(cur)}" placeholder="https://www.youtube.com/watch?v=…"></div>
        <label class="row"><input type="checkbox" id="w-vauto" ${flag(vid, "autoplay") ? "checked" : ""}> Autoplay (muted)</label>
        <label class="row"><input type="checkbox" id="w-vloop" ${flag(vid, "loop") ? "checked" : ""}> Loop</label>
        <label class="row"><input type="checkbox" id="w-vctl" ${vid.dataset.controls === "0" ? "" : "checked"}> Show controls (MP4)</label>
        <div class="btn-row"><button class="btn sm primary" data-wact="video">Apply video</button></div>`;
    }
    const emb = p.closest(".wb-embed");
    if (emb) return `<div class="field"><label>Embed / HTML code</label><textarea id="w-embed" spellcheck="false">${esc(emb.innerHTML.trim())}</textarea></div>
      <div class="btn-row" style="margin-top:0"><button class="btn sm primary" data-wact="embed">Apply code</button></div>
      <p class="hint">Iframes show live here. &lt;script&gt; tags run on the exported page / Preview only.</p>`;
    const map = p.closest(".wb-map");
    if (map) {
      const q = (map.querySelector("iframe").getAttribute("src").match(/[?&]q=([^&]*)/) || [])[1] || "";
      return `<div class="field"><label>Address or place</label><input type="text" id="w-addr" value="${esc(decodeURIComponent(q.replace(/\+/g, " ")))}"></div><div class="btn-row"><button class="btn sm primary" data-wact="map">Update map</button></div>`;
    }
    return "";
  });

  /* ---------- block-level widget settings ---------- */
  WB.hooks.inspectorBlock.push(({ sec }) => {
    let h = "";
    const grids = [...sec.querySelectorAll("[data-cols]")].slice(0, 3);
    grids.forEach((g, i) => {
      h += `<div class="field"><label>Columns${grids.length > 1 ? " #" + (i + 1) : ""}</label><div class="seg">${[1, 2, 3, 4].map((n) => `<button data-wcols="${i}" data-n="${n}" class="${g.dataset.cols == n ? "active" : ""}">${n}</button>`).join("")}</div></div>`;
    });
    const sl = sec.querySelector("[data-wb-slider]");
    if (sl) h += `<div class="field"><label>Slider autoplay (seconds, 0 = off)</label><input type="text" id="w-auto" value="${(+sl.dataset.autoplay || 0) / 1000}"></div>
      <div class="btn-row" style="margin-top:0"><button class="btn sm" data-wact="slide+">+ Slide</button><button class="btn sm" data-wact="slide-">− Last slide</button></div><p class="hint">Click a slide image to replace, crop or resize it.</p>`;
    const cd = sec.querySelector("[data-wb-countdown]");
    if (cd) h += `<div class="field"><label>Countdown target</label><input type="datetime-local" id="w-cd" value="${esc((cd.dataset.wbCountdown || "").slice(0, 16))}" style="width:100%;padding:6px;border:1px solid var(--ed-border);border-radius:6px"></div>`;
    const hv = sec.querySelector(".wb-hero__video");
    if (hv) h += `<div class="field"><label>Background video (.mp4 / .webm link)</label><input type="text" id="w-hv" value="${esc(hv.getAttribute("src"))}"></div><div class="btn-row" style="margin-top:0"><button class="btn sm primary" data-wact="herovideo">Apply</button></div>`;
    const cart = sec.querySelector("[data-wb-cart]");
    if (cart) h += `<div class="field"><label>Checkout link (payment link URL or mailto:you@site.com)</label><input type="text" id="w-co" value="${esc(cart.dataset.checkout || "")}" placeholder="https://buy.stripe.com/… or mailto:…"></div><p class="hint">Cart is kept in the visitor's browser. Real payments need a payment provider link.</p>`;
    const md = sec.querySelector(".wb-modal");
    if (md) h += `<div class="field"><label>Auto-open popup after (seconds, blank = button only)</label><input type="text" id="w-modal" value="${esc(md.dataset.wbAuto || "")}"></div>`;
    const mq = sec.querySelector(".wb-marquee__track");
    if (mq) h += `<div class="btn-row"><button class="btn sm" data-wact="marquee">Rebuild loop after editing names</button></div>`;
    return h ? `<div class="section-label">Widget settings</div>${h}` : "";
  });

  document.addEventListener("click", (e) => {
    const cols = e.target.closest("#inspector [data-wcols]");
    if (cols) {
      const sec = selSec(), g = [...sec.querySelectorAll("[data-cols]")][+cols.dataset.wcols], n = +cols.dataset.n;
      g.dataset.cols = n;
      if (g.classList.contains("wb-cols")) {
        let c = g.querySelectorAll(":scope > .wb-col");
        for (let i = c.length; i < n; i++) { const d = document.createElement("div"); d.className = "wb-col"; d.innerHTML = "<p>New column</p>"; g.appendChild(d); }
        c = g.querySelectorAll(":scope > .wb-col");
        for (let i = c.length - 1; i >= n; i--) { if (c[i].textContent.trim() && !confirm("Remove a column that contains content?")) return; c[i].remove(); }
      }
      return done(true);
    }
    const b = e.target.closest("#inspector [data-wact]"); if (!b) return;
    const act = b.dataset.wact, p = A().state.pickedEl, sec = selSec();
    if (act === "video") {
      const vid = p.closest(".wb-video") || p.parentElement, o = { autoplay: $("#w-vauto").checked, loop: $("#w-vloop").checked, controls: $("#w-vctl").checked };
      vid.dataset.autoplay = o.autoplay ? "1" : "0"; vid.dataset.loop = o.loop ? "1" : "0"; vid.dataset.controls = o.controls ? "1" : "0";
      vid.innerHTML = videoHTML($("#w-vurl").value, o); return done(true);
    }
    if (act === "embed") { p.closest(".wb-embed").innerHTML = $("#w-embed").value; return done(true); }
    if (act === "map") { p.closest(".wb-map").querySelector("iframe").src = `https://www.google.com/maps?q=${encodeURIComponent($("#w-addr").value)}&output=embed`; return done(true); }
    if (act === "slide+") { const t = sec.querySelector(".wb-slider__track"), l = t.lastElementChild; t.appendChild(l.cloneNode(true)); return done(true); }
    if (act === "slide-") { const t = sec.querySelector(".wb-slider__track"); if (t.children.length > 1) t.lastElementChild.remove(); return done(true); }
    if (act === "herovideo") { sec.querySelector(".wb-hero__video").src = $("#w-hv").value.trim(); return done(true); }
    if (act === "marquee") {
      const t = sec.querySelector(".wb-marquee__track"), items = [...t.children], half = Math.ceil(items.length / 2);
      t.innerHTML = ""; const first = items.slice(0, half).map((n) => n.textContent);
      first.forEach((x) => t.insertAdjacentHTML("beforeend", `<span>${esc(x)}</span>`)); first.forEach((x) => t.insertAdjacentHTML("beforeend", `<span aria-hidden="true">${esc(x)}</span>`));
      return done(true);
    }
  });
  document.addEventListener("input", (e) => {
    const t = e.target, sec = selSec(); if (!sec || !t.closest || !t.closest("#inspector")) return;
    if (t.id === "w-auto") { const sl = sec.querySelector("[data-wb-slider]"); sl.dataset.autoplay = Math.round((parseFloat(t.value) || 0) * 1000); A().syncBlock(A().state.selectedId); }
    else if (t.id === "w-cd") { const cd = sec.querySelector("[data-wb-countdown]"); cd.setAttribute("data-wb-countdown", t.value); A().syncBlock(A().state.selectedId); }
    else if (t.id === "w-co") { sec.querySelector("[data-wb-cart]").setAttribute("data-checkout", t.value.trim()); A().syncBlock(A().state.selectedId); }
    else if (t.id === "w-modal") { const m = sec.querySelector(".wb-modal"); t.value.trim() ? m.setAttribute("data-wb-auto", t.value.trim()) : m.removeAttribute("data-wb-auto"); A().syncBlock(A().state.selectedId); }
  });
})();
