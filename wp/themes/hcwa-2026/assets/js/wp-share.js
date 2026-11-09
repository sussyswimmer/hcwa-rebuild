/* HCWA 2026, WordPress only (docs/plan/11-WP-PORT.md 1.2, 2.8): the phone's share sheet on single
   posts (where Zalo and Messenger live on most phones). The list item stays hidden unless the browser
   has navigator.share. Enqueued deferred on single posts only. No dependencies. */
(function () {
  "use strict";
  if (typeof navigator.share !== "function") return;
  var items = document.querySelectorAll("[data-share-native]");
  for (var i = 0; i < items.length; i++) {
    (function (item) {
      var btn = item.querySelector("button");
      if (!btn) return;
      item.hidden = false;
      btn.addEventListener("click", function () {
        navigator.share({ title: btn.getAttribute("data-share-title") || document.title, url: btn.getAttribute("data-share-url") || window.location.href })
          .catch(function () { /* the visitor closed the share sheet */ });
      });
    })(items[i]);
  }
})();
