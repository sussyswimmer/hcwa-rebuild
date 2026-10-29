import "./styles/tokens.css";
import "./styles/site.css";

const $ = (sel, root = document) => root.querySelector(sel);
const $$ = (sel, root = document) => [...root.querySelectorAll(sel)];
const i18n = JSON.parse($("#i18n")?.textContent ?? "{}");
const reduced = matchMedia("(prefers-reduced-motion: reduce)").matches;
const store = {
  get: (k) => { try { return localStorage.getItem(k); } catch { return null; } },
  set: (k, v) => { try { localStorage.setItem(k, v); } catch { /* private mode: dismissal lasts this page only */ } },
};

// Header shrinks to 64px after 120px of scroll (06-PAGES), driven by a sentinel, not a scroll listener.
{
  const header = $(".site-header");
  const sentinel = Object.assign(document.createElement("div"), { ariaHidden: "true" });
  sentinel.style.cssText = "position:absolute;top:120px;height:1px;width:1px";
  document.body.prepend(sentinel);
  new IntersectionObserver(([e]) => header.classList.toggle("is-compact", !e.isIntersecting)).observe(sentinel);
}

// Desktop submenus: toggle buttons, Escape and outside click close them.
{
  const toggles = $$(".nav__toggle");
  const close = (except) => toggles.forEach((t) => { if (t !== except) { t.setAttribute("aria-expanded", "false"); $("#" + t.getAttribute("aria-controls")).hidden = true; } });
  toggles.forEach((t) => t.addEventListener("click", () => {
    const open = t.getAttribute("aria-expanded") !== "true";
    close(t);
    t.setAttribute("aria-expanded", String(open));
    $("#" + t.getAttribute("aria-controls")).hidden = !open;
  }));
  document.addEventListener("keydown", (e) => {
    if (e.key !== "Escape") return;
    const open = toggles.find((t) => t.getAttribute("aria-expanded") === "true");
    if (open) { close(); open.focus(); }
  });
  document.addEventListener("click", (e) => { if (!e.target.closest(".nav__item")) close(); });
  $(".nav")?.addEventListener("focusout", (e) => { if (!e.relatedTarget?.closest(".nav")) close(); });
}

// Mobile menu: a modal <dialog> gives focus trapping and Escape for free.
{
  const sheet = $("#menu-sheet");
  const opener = $("[data-open-sheet]");
  opener?.addEventListener("click", () => sheet.showModal());
  $("[data-close-sheet]")?.addEventListener("click", () => sheet.close());
  let followedLink = false;
  sheet?.addEventListener("click", (e) => {
    followedLink = Boolean(e.target.closest("a"));
    if (e.target === sheet || followedLink) sheet.close();
  });
  // After a link, focus belongs to where the link went (same-page anchors), not the menu button.
  sheet?.addEventListener("close", () => { if (!followedLink) opener?.focus(); followedLink = false; });
}

// Footer columns are accordions on mobile and plain columns on desktop.
{
  const desktop = matchMedia("(min-width: 1024px)");
  const sync = () => $$(".foot-col").forEach((d) => { d.open = desktop.matches; });
  sync();
  desktop.addEventListener("change", sync);
  $$(".foot-col summary").forEach((s) => s.addEventListener("click", (e) => { if (desktop.matches) e.preventDefault(); }));
}

// "View this page in English" for English browsers on VI pages; no automatic redirect (07-BILINGUAL).
{
  const bar = $("[data-en-bar]");
  if (bar && navigator.language?.toLowerCase().startsWith("en") && !store.get("hcwa-en-bar")) bar.hidden = false;
  $("[data-dismiss-en-bar]")?.addEventListener("click", () => { bar.hidden = true; store.set("hcwa-en-bar", "1"); });
  $("[data-dismiss-announce]")?.addEventListener("click", (e) => { e.target.closest("[data-announce]").hidden = true; });
}

// One gentle fade-up per section, only for sections still below the fold.
if (!reduced && "IntersectionObserver" in window) {
  const io = new IntersectionObserver((entries) => entries.forEach((e) => {
    if (e.isIntersecting) { e.target.classList.remove("reveal-pending"); io.unobserve(e.target); }
  }), { rootMargin: "0px 0px -10% 0px" });
  $$("[data-reveal]").forEach((el) => {
    if (el.getBoundingClientRect().top > innerHeight) { el.classList.add("reveal-pending"); io.observe(el); }
  });
}

// Our Work filter: chips are plain ?area= links; this hides what doesn't match.
{
  const area = new URLSearchParams(location.search).get("area");
  const groups = $$("[data-filter-group]");
  if (area && groups.length) {
    $$(".filters .chip").forEach((chip) => (chip.dataset.area === area ? chip.setAttribute("aria-current", "true") : chip.removeAttribute("aria-current")));
    let shown = 0;
    groups.forEach((g) => {
      const items = $$("[data-areas]", g);
      items.forEach((it) => { const hit = it.dataset.areas.split(" ").includes(area); it.hidden = !hit; shown += hit; });
      g.hidden = !items.some((it) => !it.hidden);
    });
    $("[data-filter-empty]").hidden = shown > 0;
  }
}

// Gallery lightbox: <dialog>, arrow keys, Escape, focus returns to the thumbnail.
{
  const box = $("[data-lightbox]");
  const items = $$("[data-gallery] .gallery__item");
  if (box && items.length) {
    let i = 0, from = null;
    const show = (n) => {
      i = (n + items.length) % items.length;
      const ph = items[i].firstElementChild.cloneNode(true);
      $("[data-lightbox-stage]", box).replaceChildren(ph);
      $("[data-lightbox-count]", box).textContent = `${i + 1} / ${items.length}`;
    };
    items.forEach((b, n) => b.addEventListener("click", () => { from = b; show(n); box.showModal(); }));
    $("[data-lightbox-prev]", box).addEventListener("click", () => show(i - 1));
    $("[data-lightbox-next]", box).addEventListener("click", () => show(i + 1));
    $("[data-lightbox-close]", box).addEventListener("click", () => box.close());
    box.addEventListener("keydown", (e) => { if (e.key === "ArrowLeft") show(i - 1); if (e.key === "ArrowRight") show(i + 1); });
    box.addEventListener("close", () => from?.focus());
  }
}

// Copy buttons announce the result in a live region.
{
  let live = $("[data-live]");
  if (!live) { live = Object.assign(document.createElement("p"), { className: "sr-only" }); live.setAttribute("aria-live", "polite"); document.body.append(live); }
  // Clear first so a repeated "Copied" is announced again.
  const announce = (msg) => { live.textContent = ""; setTimeout(() => { live.textContent = msg; }, 50); };
  $$("[data-copy]").forEach((btn) => btn.addEventListener("click", async () => {
    const label = $("span", btn);
    try {
      await navigator.clipboard.writeText(btn.dataset.copy);
      label.textContent = i18n.copied;
      announce(i18n.copied);
    } catch {
      announce(i18n.copyFailed);
    }
    setTimeout(() => { label.textContent = i18n.copy; }, 2000);
  }));
}

// Donate links from a center page carry ?center=<id>; preselect it in "Tell us you donated".
{
  const pick = $("#d-center");
  const center = new URLSearchParams(location.search).get("center");
  if (pick && center && [...pick.options].some((o) => o.value === center)) pick.value = center;
}

// Forms: inline errors in the page language, error summary on submit, then success.
$$("[data-form]").forEach((form) => {
  const summary = $("[data-summary]", form);
  const success = $("[data-success]", form);
  const submit = $('button[type="submit"]', form);
  const check = (input) => {
    const box = input.closest("[data-field]");
    const err = $(".field__error", box);
    const bad = !input.checkValidity();
    box.classList.toggle("is-invalid", bad);
    if (bad) input.setAttribute("aria-invalid", "true");
    else input.removeAttribute("aria-invalid");
    err.hidden = !bad;
    $("span", err).textContent = bad ? err.dataset.error : "";
    return !bad;
  };
  const inputs = $$("input, select, textarea", form).filter((el) => el.closest("[data-field]"));
  // Re-check while typing, not on blur: clearing an error on blur shifts the layout under the
  // pointer mid-click, so the next control (e.g. the consent box) misses the click.
  inputs.forEach((el) => ["input", "change"].forEach((ev) => el.addEventListener(ev, () => { if (el.closest(".is-invalid")) check(el); })));
  form.addEventListener("submit", (e) => {
    e.preventDefault();
    success.hidden = true;
    const bad = inputs.filter((el) => !check(el));
    if (bad.length) {
      summary.innerHTML = "";
      const p = Object.assign(document.createElement("p"), { textContent: i18n.e_summary });
      const ul = document.createElement("ul");
      bad.forEach((el) => {
        const a = Object.assign(document.createElement("a"), { href: "#" + el.id, textContent: $(`#${el.id}-error span`, form)?.textContent ?? "" });
        a.addEventListener("click", (ev) => { ev.preventDefault(); el.focus(); });
        const li = document.createElement("li");
        li.append(a);
        ul.append(li);
      });
      summary.append(p, ul);
      summary.hidden = false;
      summary.focus();
      return;
    }
    summary.hidden = true;
    // Prototype: there is no backend. Show the sending state, then the success message.
    submit.disabled = true;
    submit.setAttribute("aria-busy", "true");
    submit.textContent = i18n.f_sending;
    setTimeout(() => {
      form.reset();
      submit.disabled = false;
      submit.removeAttribute("aria-busy");
      submit.textContent = i18n.f_send;
      success.hidden = false;
      success.focus();
    }, 600);
  });
});
