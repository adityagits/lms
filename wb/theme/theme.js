/* Website Builder — theme behaviour (tiny, dependency-free).
   Powers: mobile nav toggle, tabs, pricing toggle, carousel arrows, multi-step form, countdown.
   Included in every exported page. Does nothing while editing in the builder. */
(function () {
  "use strict";
  var editing = function () { return document.body.classList.contains("wb-edit"); };

  document.addEventListener("click", function (e) {
    if (editing()) return;
    var t = e.target.closest("button, a");
    if (!t) return;

    if (t.hasAttribute("data-wb-nav-toggle")) {
      var nav = t.closest(".wb-nav"); nav.classList.toggle("is-open");
      t.setAttribute("aria-expanded", nav.classList.contains("is-open"));
      return;
    }
    var openNav = t.closest(".wb-nav.is-open");
    if (openNav && t.tagName === "A") openNav.classList.remove("is-open");

    var tab = t.closest(".wb-tabs__list button");
    if (tab) {
      var box = tab.closest(".wb-tabs"), i = Array.prototype.indexOf.call(tab.parentNode.children, tab);
      Array.prototype.forEach.call(tab.parentNode.children, function (b, k) { b.classList.toggle("is-active", k === i); b.setAttribute("aria-selected", k === i); });
      Array.prototype.forEach.call(box.querySelectorAll(".wb-tabs__panel"), function (p, k) { p.classList.toggle("is-active", k === i); });
      return;
    }
    var bill = t.closest(".wb-billing button");
    if (bill) {
      var wrap = bill.closest(".wb-pricing"); wrap.setAttribute("data-period", bill.getAttribute("data-period"));
      Array.prototype.forEach.call(bill.parentNode.children, function (b) { b.classList.toggle("is-active", b === bill); });
      return;
    }
    var car = t.closest("[data-wb-carousel]");
    if (car && t.hasAttribute("data-dir")) {
      var track = car.querySelector(".wb-carousel__track");
      track.scrollBy({ left: track.clientWidth * 0.8 * Number(t.getAttribute("data-dir")), behavior: "smooth" });
      return;
    }
    var step = t.closest("[data-wb-step]");
    if (step) {
      var form = t.closest(".wb-multistep"), steps = form.querySelectorAll(".wb-step"), dots = form.querySelectorAll(".wb-steps-dots i");
      var cur = Array.prototype.findIndex.call(steps, function (s) { return s.classList.contains("is-active"); });
      var next = Math.max(0, Math.min(steps.length - 1, cur + Number(t.getAttribute("data-wb-step"))));
      Array.prototype.forEach.call(steps, function (s, k) { s.classList.toggle("is-active", k === next); });
      Array.prototype.forEach.call(dots, function (d, k) { d.classList.toggle("is-done", k <= next); });
    }
  });

  /* --- sliders, compare, modals, cart (run on pages, skipped while editing) --- */
  function slides(sl) { return sl.querySelectorAll(".wb-slide"); }
  function goSlide(sl, dir, abs) {
    var tr = sl.querySelector(".wb-slider__track"), n = slides(sl).length, w = tr.clientWidth;
    var cur = Math.round(tr.scrollLeft / w), next = abs != null ? abs : cur + dir;
    if (next >= n) next = 0; if (next < 0) next = n - 1;
    tr.scrollTo({ left: next * w, behavior: "smooth" });
  }
  function initSlider(sl) {
    var dots = sl.querySelector(".wb-slider__dots"), tr = sl.querySelector(".wb-slider__track");
    if (dots && !dots.children.length) {
      slides(sl).forEach(function (_, i) { var b = document.createElement("button"); b.type = "button"; b.setAttribute("aria-label", "Slide " + (i + 1)); b.setAttribute("data-slide", i); dots.appendChild(b); });
    }
    function mark() { var i = Math.round(tr.scrollLeft / tr.clientWidth); Array.prototype.forEach.call(dots ? dots.children : [], function (d, k) { d.classList.toggle("is-active", k === i); }); }
    tr.addEventListener("scroll", mark); mark();
    sl.addEventListener("mouseenter", function () { sl._hover = true; }); sl.addEventListener("mouseleave", function () { sl._hover = false; });
  }
  var cartKey = "wb-cart";
  function getCart() { try { return JSON.parse(localStorage.getItem(cartKey)) || []; } catch (e) { return []; } }
  function setCart(c) { try { localStorage.setItem(cartKey, JSON.stringify(c)); } catch (e) {} refreshCart(); }
  function money(n, cur) { return (cur || "$") + n.toFixed(2); }
  function refreshCart() {
    if (editing()) return;
    var c = getCart(), count = c.reduce(function (a, i) { return a + i.qty; }, 0);
    Array.prototype.forEach.call(document.querySelectorAll("[data-wb-cart-count]"), function (n) { n.textContent = count; });
    Array.prototype.forEach.call(document.querySelectorAll("[data-wb-cart]"), function (box) {
      var items = box.querySelector(".wb-cart__items"), total = box.querySelector("[data-wb-cart-total]"), sum = 0;
      if (!items) return;
      items.innerHTML = c.length ? c.map(function (i, k) {
        sum += i.price * i.qty;
        return '<div class="wb-cart__row"><img src="' + (i.img || "") + '" alt=""><div><strong></strong></div><span>' + i.qty + " × " + money(i.price) + '</span><button type="button" data-wb-remove="' + k + '" aria-label="Remove">✕</button></div>';
      }).join("") : '<p class="wb-cart__empty">Your cart is empty.</p>';
      Array.prototype.forEach.call(items.querySelectorAll("strong"), function (s, k) { s.textContent = c[k].name; });
      if (total) total.textContent = money(sum);
    });
  }
  document.addEventListener("click", function (e) {
    if (editing()) return;
    var t = e.target.closest("button, a, div, span");
    if (!t) return;
    var sb = e.target.closest(".wb-slider__btn"), dot = e.target.closest(".wb-slider__dots button");
    if (sb) { e.preventDefault(); goSlide(sb.closest(".wb-slider"), sb.classList.contains("wb-slider__next") ? 1 : -1); return; }
    if (dot) { goSlide(dot.closest(".wb-slider"), 0, Number(dot.getAttribute("data-slide"))); return; }
    var op = e.target.closest("[data-wb-open]");
    if (op) { e.preventDefault(); var m = document.getElementById(op.getAttribute("data-wb-open")); if (m) { m.classList.add("is-open"); m.setAttribute("aria-hidden", "false"); } return; }
    var cl = e.target.closest("[data-wb-close]"), bd = e.target.classList && e.target.classList.contains("wb-modal") ? e.target : null;
    if (cl || bd) { var mm = (cl || bd).closest(".wb-modal"); mm.classList.remove("is-open"); mm.setAttribute("aria-hidden", "true"); return; }
    var add = e.target.closest("[data-wb-add]");
    if (add) {
      e.preventDefault();
      var c = getCart(), name = add.getAttribute("data-name") || "Item", ex = c.filter(function (i) { return i.name === name; })[0];
      if (ex) ex.qty++; else c.push({ name: name, price: parseFloat(add.getAttribute("data-price")) || 0, img: add.getAttribute("data-img") || "", qty: 1 });
      setCart(c); return;
    }
    var rm = e.target.closest("[data-wb-remove]");
    if (rm) { var c2 = getCart(); c2.splice(Number(rm.getAttribute("data-wb-remove")), 1); setCart(c2); return; }
    var co = e.target.closest("[data-wb-checkout]");
    if (co) {
      var url = co.closest("[data-wb-cart]").getAttribute("data-checkout") || "";
      var cc = getCart();
      if (!cc.length) { e.preventDefault(); return; }
      if (url.indexOf("mailto:") === 0) {
        e.preventDefault();
        var body = cc.map(function (i) { return i.qty + " x " + i.name + " (" + money(i.price) + ")"; }).join("\n");
        location.href = url + (url.indexOf("?") > -1 ? "&" : "?") + "subject=" + encodeURIComponent("New order") + "&body=" + encodeURIComponent(body);
      } else if (url) { e.preventDefault(); location.href = url; }
    }
  });
  document.addEventListener("keydown", function (e) {
    if (e.key !== "Escape" || editing()) return;
    Array.prototype.forEach.call(document.querySelectorAll(".wb-modal.is-open"), function (m) { m.classList.remove("is-open"); });
  });
  document.addEventListener("input", function (e) {
    var r = e.target.closest && e.target.closest(".wb-compare__range");
    if (r && !editing()) r.closest(".wb-compare").style.setProperty("--pos", r.value + "%");
  });
  function scan() {
    if (editing()) return;
    Array.prototype.forEach.call(document.querySelectorAll("[data-wb-slider]"), function (sl) {
      if (!sl._wb) { sl._wb = 1; initSlider(sl); }
      var ms = Number(sl.getAttribute("data-autoplay")) || 0;
      if (ms && !sl._hover && Date.now() - (sl._t || 0) >= ms) { sl._t = Date.now(); goSlide(sl, 1); }
    });
    Array.prototype.forEach.call(document.querySelectorAll("[data-wb-auto]"), function (m) {
      if (m._wb) return; m._wb = 1;
      setTimeout(function () { if (!editing()) { m.classList.add("is-open"); } }, Number(m.getAttribute("data-wb-auto")) * 1000);
    });
    if (!window.__wbCartDone || window.__wbCartDirty) { window.__wbCartDone = 1; refreshCart(); }
  }
  window.WBTheme = { refresh: function () { window.__wbCartDirty = 1; scan(); window.__wbCartDirty = 0; } };

  function tick() {
    scan();
    if (editing()) return;
    Array.prototype.forEach.call(document.querySelectorAll("[data-wb-countdown]"), function (el) {
      var ms = Math.max(0, new Date(el.getAttribute("data-wb-countdown")) - Date.now()), s = Math.floor(ms / 1000);
      var v = { d: Math.floor(s / 86400), h: Math.floor(s / 3600) % 24, m: Math.floor(s / 60) % 60, s: s % 60 };
      Object.keys(v).forEach(function (k) { var n = el.querySelector("[data-u=" + k + "]"); if (n) n.textContent = String(v[k]).padStart(2, "0"); });
    });
  }
  tick(); setInterval(tick, 1000);
})();
