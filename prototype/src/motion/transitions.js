// Cross-document View Transitions (05-DESIGN D5.5, D0.5 "pages flow into each other"). motion.css draws
// them; this file only picks the type and switches them off for a visitor who paused motion (reduced
// motion never gets them: @view-transition sits in a no-preference query).
//   back: a traverse to an earlier history entry, the wipe runs the other way.
//   lang: the VI / EN switch, the same page in the other language, a 180 ms crossfade.
// No imports and no top-level window access: layout.mjs reads pageReveal in Node and inlines it.
const KEY = "hcwa:vt";

// pagereveal fires before the new page's first render, so a module script is too late: layout.mjs puts
// this function in a classic script in <head>, after head-inline.js has set data-motion. ES5 on purpose.
export function pageReveal() {
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
}

export function initTransitions() {
  // The old page can still skip the transition as it swaps out (the pseudo-elements live in the new one).
  addEventListener("pageswap", (e) => {
    if (e.viewTransition && document.documentElement.dataset.motion === "off") e.viewTransition.skipTransition();
  });
  // The language links mark the next page so it crossfades instead of wiping.
  document.addEventListener("click", (e) => {
    const link = e.target.closest?.("[data-lang-switch] a[hreflang]");
    if (!link || link.getAttribute("aria-current") === "true" || e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    try { sessionStorage.setItem(KEY, "lang"); } catch { /* the default wipe it is */ }
  });
}
