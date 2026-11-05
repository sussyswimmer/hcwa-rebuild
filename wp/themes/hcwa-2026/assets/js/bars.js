/* HCWA 2026: the two top bars are decided before first paint, not by the deferred site.js,
   which ran after paint and pushed the whole page down or pulled it up (CLS, 09-QA).
   inc/assets.php prints this file inline in <head>; header.php calls hcwaBars right after
   each bar; site.js reuses announceKey() for the dismiss button. No dependencies. */
(function () {
  "use strict";

  // localStorage can be missing or throw (private mode, blocked storage).
  var get = function (k) { try { return window.localStorage.getItem(k); } catch (e) { return null; } };

  var bars = {
    // Dismissal is remembered per announcement text, so a new announcement shows again.
    announceKey: function (box) {
      var text = box.getAttribute("data-announce") || box.textContent.replace(/\s+/g, " ").trim();
      var hash = 0;
      for (var i = 0; i < text.length; i++) { hash = ((hash << 5) - hash + text.charCodeAt(i)) | 0; }
      return "hcwa-announce-" + hash;
    },

    // "View this page in English" for English browsers on VI pages; no automatic redirect (07-BILINGUAL).
    enBar: function (bar) {
      var lang = (navigator.language || "").toLowerCase();
      if (bar && lang.indexOf("en") === 0 && !get("hcwa-en-bar")) bar.hidden = false;
    },

    // Announcements the visitor already dismissed stay hidden.
    announce: function (root) {
      if (!root) return;
      var boxes = root.querySelectorAll("[data-announce]");
      for (var i = 0; i < boxes.length; i++) {
        if (get(bars.announceKey(boxes[i]))) boxes[i].hidden = true;
      }
    }
  };

  window.hcwaBars = bars;
})();
