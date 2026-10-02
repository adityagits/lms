/* Connects the website-builder to the LMS: loads the page from the server (window.WB_SEED),
   autosaves via window.WB_ON_SAVE, and adapts the editor chrome for single-page editing. */
(function () {
  "use strict";
  const cfg = window.LMS_BUILDER, WB = window.WB, A = WB.api;
  const $ = (s) => document.querySelector(s);
  const status = $("#lms-status");
  let version = 0, savedVersion = 0, saving = false, timer = null;

  const setStatus = (txt, cls) => { status.textContent = txt; status.className = "lms-status " + (cls || ""); };

  async function save(manual) {
    if (saving) { timer = setTimeout(() => save(manual), 600); return; }
    clearTimeout(timer);
    A.flushEdit();
    const sending = version;
    if (!manual && sending === savedVersion) return;
    saving = true; setStatus("Saving…", "busy");
    try {
      const res = await fetch(cfg.saveUrl, {
        method: "POST", credentials: "same-origin",
        headers: { "Content-Type": "application/json", "X-CSRF-Token": cfg.csrf },
        body: JSON.stringify({ id: cfg.pageId, project: A.state.project }),
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || !data.ok) throw new Error(data.error || "HTTP " + res.status);
      savedVersion = sending;
      setStatus(version === savedVersion ? "Saved " + data.saved_at : "Unsaved changes", version === savedVersion ? "ok" : "");
      if (version !== savedVersion) timer = setTimeout(() => save(), 1500);
    } catch (err) {
      setStatus("Save failed: " + err.message, "err");
      timer = setTimeout(() => save(), 8000);   // retry
    } finally { saving = false; }
  }

  window.WB_ON_SAVE = () => { version++; setStatus("Unsaved changes"); clearTimeout(timer); timer = setTimeout(() => save(), 2000); };

  // top bar: Save / Back / View
  const right = document.querySelector(".tb-right");
  const mk = (tag, cls, html) => { const el = document.createElement(tag); el.className = cls; el.innerHTML = html; return el; };
  const saveBtn = mk("button", "btn primary", "💾 Save"); saveBtn.id = "lms-save"; saveBtn.onclick = () => save(true);
  const view = mk("a", "btn", "View page ↗"); view.href = cfg.viewUrl; view.target = "_blank"; view.rel = "noopener";
  right.insertBefore(view, $("#preview")); right.appendChild(saveBtn);
  const back = mk("a", "btn", "← Pages"); back.href = cfg.backUrl; back.onclick = (e) => { if (version !== savedVersion && !confirm("You have unsaved changes. Leave anyway?")) e.preventDefault(); };
  document.querySelector(".tb-left").insertBefore(back, document.querySelector(".tb-left .logo"));

  // single-page mode: hide the multi-page tab and start on Components
  document.querySelector('#left-tabs [data-tab="pages"]').hidden = true;
  A.setTab("components");

  document.addEventListener("keydown", (e) => { if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === "s") { e.preventDefault(); save(true); } });
  window.addEventListener("beforeunload", (e) => { if (version !== savedVersion) { e.preventDefault(); e.returnValue = ""; } });
  setStatus("Saved", "ok");
})();
