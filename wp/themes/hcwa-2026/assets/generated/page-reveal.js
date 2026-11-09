(function pageReveal() {
  addEventListener("pagereveal", function (e) {
    var type = null;
    try { type = sessionStorage.getItem("hcwa:vt"); sessionStorage.removeItem("hcwa:vt"); } catch (x) { /* storage blocked: no type */ }
    var vt = e.viewTransition;
    if (!vt) return;
    if (document.documentElement.getAttribute("data-motion") === "off") { vt.skipTransition(); return; }
    var a = window.navigation && navigation.activation;
    if (type === "lang") vt.types.add("lang");
    else if (a && a.navigationType === "traverse" && a.from && a.entry && a.entry.index < a.from.index) vt.types.add("back");
  });
})();