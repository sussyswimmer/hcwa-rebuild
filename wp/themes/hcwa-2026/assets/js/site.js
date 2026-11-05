/* HCWA 2026 front-end script: a port of prototype/src/main.js (no dependencies, no build).
   Strings come from window.hcwaI18n (inc/assets.php) in the page language.
   Everything here is progressive: without JS the menus, accordions and links still work. */
(function () {
  "use strict";

  var $ = function (sel, root) { return (root || document).querySelector(sel); };
  var $$ = function (sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); };
  var i18n = window.hcwaI18n || {};
  var reduced = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  // localStorage can be missing or throw (private mode, blocked storage): never let that break the page.
  var store = {
    get: function (k) { try { return window.localStorage.getItem(k); } catch (e) { return null; } },
    set: function (k, v) { try { window.localStorage.setItem(k, v); } catch (e) { /* dismissal lasts this page only */ } }
  };

  // Header gets a shadow after 120px of scroll, driven by a sentinel, not a scroll listener.
  (function () {
    var header = $(".site-header");
    if (!header || !("IntersectionObserver" in window)) return;
    var sentinel = document.createElement("div");
    sentinel.setAttribute("aria-hidden", "true");
    sentinel.style.cssText = "position:absolute;top:120px;left:0;height:1px;width:1px;pointer-events:none";
    document.body.prepend(sentinel);
    new IntersectionObserver(function (entries) {
      header.classList.toggle("is-compact", !entries[0].isIntersecting);
    }).observe(sentinel);
  })();

  // Desktop submenus: toggle buttons; Escape, outside click and focus leaving the nav close them.
  (function () {
    var toggles = $$(".nav__toggle");
    if (!toggles.length) return;
    var panel = function (t) { return document.getElementById(t.getAttribute("aria-controls")); };
    var close = function (except) {
      toggles.forEach(function (t) {
        if (t === except) return;
        t.setAttribute("aria-expanded", "false");
        var p = panel(t);
        if (p) p.hidden = true;
      });
    };
    toggles.forEach(function (t) {
      t.addEventListener("click", function () {
        var open = t.getAttribute("aria-expanded") !== "true";
        close(t);
        t.setAttribute("aria-expanded", String(open));
        var p = panel(t);
        if (p) p.hidden = !open;
      });
    });
    document.addEventListener("keydown", function (e) {
      if (e.key !== "Escape") return;
      var open = toggles.filter(function (t) { return t.getAttribute("aria-expanded") === "true"; })[0];
      if (open) { close(); open.focus(); }
    });
    document.addEventListener("click", function (e) { if (!e.target.closest(".nav__item")) close(); });
    var nav = $(".nav");
    if (nav) nav.addEventListener("focusout", function (e) { if (!e.relatedTarget || !e.relatedTarget.closest(".nav")) close(); });
  })();

  // Mobile menu: a modal <dialog> traps focus and closes on Escape natively.
  (function () {
    var sheet = $("#menu-sheet");
    var opener = $("[data-open-sheet]");
    if (!sheet || !opener) return;
    var native = typeof sheet.showModal === "function";
    var followedLink = false;

    // Fallback for browsers without <dialog>: same behaviour, done by hand.
    var trap = function (e) {
      if (e.key === "Escape") { e.preventDefault(); closeSheet(); return; }
      if (e.key !== "Tab") return;
      var items = $$("a[href], button:not([disabled]), summary", sheet).filter(function (el) { return el.offsetParent !== null; });
      if (!items.length) return;
      var first = items[0], last = items[items.length - 1];
      if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
      else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    };
    var openSheet = function () {
      if (native) { sheet.showModal(); return; }
      sheet.setAttribute("open", "");
      document.addEventListener("keydown", trap);
      var first = $("button, a[href]", sheet);
      if (first) first.focus();
    };
    var closeSheet = function () {
      if (native) { sheet.close(); return; }
      sheet.removeAttribute("open");
      document.removeEventListener("keydown", trap);
      sheet.dispatchEvent(new Event("close"));
    };

    opener.addEventListener("click", openSheet);
    var closer = $("[data-close-sheet]", sheet);
    if (closer) closer.addEventListener("click", closeSheet);
    sheet.addEventListener("click", function (e) {
      followedLink = Boolean(e.target.closest("a"));
      if (e.target === sheet || followedLink) closeSheet();
    });
    // After a link, focus belongs where the link went (same-page anchors), not on the menu button.
    sheet.addEventListener("close", function () {
      if (!followedLink) opener.focus();
      followedLink = false;
    });
  })();

  // Footer columns are accordions on mobile and plain columns on desktop.
  (function () {
    var cols = $$(".foot-col");
    if (!cols.length || !window.matchMedia) return;
    var desktop = window.matchMedia("(min-width: 1024px)");
    // On desktop the summaries do nothing, so they leave the tab order too.
    var sync = function () {
      cols.forEach(function (d) {
        d.open = desktop.matches;
        var s = d.querySelector("summary");
        if (s) s.tabIndex = desktop.matches ? -1 : 0;
      });
    };
    sync();
    if (desktop.addEventListener) desktop.addEventListener("change", sync);
    $$(".foot-col summary").forEach(function (s) {
      s.addEventListener("click", function (e) { if (desktop.matches) e.preventDefault(); });
    });
  })();

  // "View this page in English": bars.js shows it before first paint (header.php); this remembers a dismissal.
  (function () {
    var bar = $("[data-en-bar]");
    var dismiss = $("[data-dismiss-en-bar]");
    if (bar && dismiss) {
      dismiss.addEventListener("click", function () { bar.hidden = true; store.set("hcwa-en-bar", "1"); });
    }
  })();

  // Announcement bar: bars.js hides a dismissed one before first paint (header.php). The
  // dismissal is remembered per text (bars.js announceKey), so a new announcement shows again.
  (function () {
    var bars = window.hcwaBars;
    $$("[data-announce]").forEach(function (box) {
      var btn = $("[data-dismiss-announce]", box);
      if (btn) btn.addEventListener("click", function () { box.hidden = true; if (bars) store.set(bars.announceKey(box), "1"); });
    });
  })();

  // One gentle fade-up per section, only for sections still below the fold.
  if (!reduced && "IntersectionObserver" in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) { e.target.classList.remove("reveal-pending"); io.unobserve(e.target); }
      });
    }, { rootMargin: "0px 0px -10% 0px" });
    $$("[data-reveal]").forEach(function (el) {
      if (el.getBoundingClientRect().top > window.innerHeight) { el.classList.add("reveal-pending"); io.observe(el); }
    });
  }

  // Our Work filter: chips are plain ?area= links (the page pattern's list, or .chip[data-area]);
  // the hcwa/center-grid and hcwa/program-list blocks mark [data-filter-group] and [data-areas].
  (function () {
    var groups = $$("[data-filter-group]");
    if (!groups.length) return;
    var area = new URLSearchParams(window.location.search).get("area") || "";
    var chipArea = function (a) {
      if (a.hasAttribute("data-area")) return a.getAttribute("data-area");
      try { return new URL(a.href, window.location.href).searchParams.get("area") || ""; } catch (e) { return null; }
    };
    $$(".filters").forEach(function (nav) {
      var chips = $$("a", nav);
      // Only filter lists that have area links (the news archive also uses .filters).
      if (!chips.some(function (a) { return chipArea(a); })) return;
      chips.forEach(function (a) {
        if (chipArea(a) === area) a.setAttribute("aria-current", "true");
        else a.removeAttribute("aria-current");
      });
    });
    if (!area) return;
    var shown = 0;
    groups.forEach(function (g) {
      var items = $$("[data-areas]", g);
      items.forEach(function (it) {
        var hit = it.getAttribute("data-areas").split(" ").indexOf(area) !== -1;
        it.hidden = !hit;
        if (hit) shown++;
      });
      g.hidden = !items.some(function (it) { return !it.hidden; });
    });
    var empty = $("[data-filter-empty]");
    if (!empty && shown === 0 && i18n.noResults) {
      // Pattern markup cannot carry a hidden message, so it is added here.
      empty = document.createElement("p");
      empty.className = "filters__empty";
      empty.setAttribute("data-filter-empty", "");
      empty.setAttribute("role", "status");
      empty.textContent = i18n.noResults + " ";
      var all = document.createElement("a");
      all.href = window.location.pathname;
      all.textContent = i18n.showAll || "";
      empty.appendChild(all);
      var anchor = $(".filters .container") || $(".filters");
      if (anchor) anchor.appendChild(empty);
    }
    if (empty) empty.hidden = shown > 0;
  })();

  // Gallery lightbox: <dialog>, arrow keys, Escape, focus returns to the thumbnail.
  (function () {
    var box = $("[data-lightbox]");
    var items = $$("[data-gallery] .gallery__item");
    if (!box || !items.length || typeof box.showModal !== "function") return;
    var stage = $("[data-lightbox-stage]", box);
    var count = $("[data-lightbox-count]", box);
    var index = 0, from = null;
    var show = function (n) {
      index = (n + items.length) % items.length;
      var item = items[index];
      var full = item.getAttribute("data-full");
      var view;
      if (full) {
        view = document.createElement("img");
        view.src = full;
        view.alt = item.getAttribute("data-alt") || "";
      } else {
        view = item.firstElementChild.cloneNode(true);
      }
      stage.replaceChildren(view);
      count.textContent = (index + 1) + " / " + items.length;
    };
    items.forEach(function (b, n) {
      b.addEventListener("click", function () { from = b; show(n); box.showModal(); });
    });
    $("[data-lightbox-prev]", box).addEventListener("click", function () { show(index - 1); });
    $("[data-lightbox-next]", box).addEventListener("click", function () { show(index + 1); });
    $("[data-lightbox-close]", box).addEventListener("click", function () { box.close(); });
    box.addEventListener("keydown", function (e) {
      if (e.key === "ArrowLeft") show(index - 1);
      if (e.key === "ArrowRight") show(index + 1);
    });
    box.addEventListener("close", function () { if (from) from.focus(); });
  })();

  // Copy buttons announce the result in a polite live region, in the page language.
  (function () {
    var buttons = $$("[data-copy]");
    if (!buttons.length) return;
    var live = $("[data-live]");
    if (!live) {
      live = document.createElement("p");
      live.className = "sr-only";
      live.setAttribute("aria-live", "polite");
      document.body.appendChild(live);
    }
    // Clear first so a repeated "Copied" is announced again.
    var announce = function (msg) { live.textContent = ""; setTimeout(function () { live.textContent = msg; }, 50); };
    var copyText = function (text) {
      if (navigator.clipboard && window.isSecureContext) return navigator.clipboard.writeText(text);
      return new Promise(function (resolve, reject) {
        var area = document.createElement("textarea");
        area.value = text;
        area.setAttribute("readonly", "");
        area.style.cssText = "position:fixed;top:0;left:0;opacity:0";
        document.body.appendChild(area);
        area.select();
        var ok = false;
        try { ok = document.execCommand("copy"); } catch (e) { ok = false; }
        document.body.removeChild(area);
        if (ok) resolve(); else reject(new Error("copy failed"));
      });
    };
    buttons.forEach(function (btn) {
      var label = $("span", btn);
      var original = label ? label.textContent : "";
      var timer = null;
      btn.addEventListener("click", function () {
        copyText(btn.getAttribute("data-copy")).then(function () {
          if (label) label.textContent = i18n.copied || original;
          announce(i18n.copied || "");
        }, function () {
          announce(i18n.copyFailed || "");
        });
        clearTimeout(timer);
        timer = setTimeout(function () { if (label) label.textContent = original; }, 2000);
      });
    });
  })();

  // Phone share sheet (Zalo, Messenger, …): only where the browser has one.
  (function () {
    if (typeof navigator.share !== "function") return;
    $$("[data-share-native]").forEach(function (wrap) {
      var btn = $("button", wrap);
      if (!btn) return;
      wrap.hidden = false;
      btn.addEventListener("click", function () {
        navigator.share({ title: btn.getAttribute("data-share-title") || document.title, url: btn.getAttribute("data-share-url") || window.location.href })
          .catch(function () { /* the visitor closed the share sheet */ });
      });
    });
  })();

  // Donate links from a center page carry ?center=<slug>; preselect it in "Tell us you donated".
  (function () {
    var pick = $("#d-center");
    var center = new URLSearchParams(window.location.search).get("center");
    if (!pick || !center) return;
    if (Array.prototype.some.call(pick.options, function (o) { return o.value === center; })) pick.value = center;
  })();
})();
