/* HCWA 2026, WordPress only (docs/plan/11-WP-PORT.md 1.2, 2.8). inc/head.php prints this function
   inline right after the EN bar and right after the announcement slot, called with that element, so
   both bars are decided before first paint and the page never jumps. The bundle's ui/en-bar.js binds
   the dismiss buttons as on the prototype; this adds what WordPress has and the prototype does not:
   an announcement dismissed once stays hidden, remembered per announcement text. No dependencies. */
(function (el, which) {
  "use strict";
  if (!el) return;
  // localStorage can be missing or throw (private mode, blocked storage).
  var get = function (k) { try { return window.localStorage.getItem(k); } catch (e) { return null; } };
  var set = function (k) { try { window.localStorage.setItem(k, "1"); } catch (e) { /* this page only */ } };

  // "View this page in English" for English browsers on VI pages; never an automatic redirect (07-BILINGUAL).
  if (which === "en") {
    var lang = (navigator.language || "").toLowerCase();
    if (lang.indexOf("en") === 0 && !get("hcwa-en-bar")) el.hidden = false;
    return;
  }

  // The same key as the earlier bars.js, so a dismissal made before the port still counts.
  var key = function (box) {
    var text = box.getAttribute("data-announce") || box.textContent.replace(/\s+/g, " ").trim();
    var hash = 0;
    for (var i = 0; i < text.length; i++) hash = ((hash << 5) - hash + text.charCodeAt(i)) | 0;
    return "hcwa-announce-" + hash;
  };
  var boxes = el.matches && el.matches("[data-announce]") ? [el] : el.querySelectorAll("[data-announce]");
  for (var i = 0; i < boxes.length; i++) {
    (function (box) {
      if (get(key(box))) { box.hidden = true; return; }
      var close = box.querySelector("[data-dismiss-announce]");
      if (close) close.addEventListener("click", function () { set(key(box)); });
    })(boxes[i]);
  }
});
