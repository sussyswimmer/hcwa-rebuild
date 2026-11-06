// Inlined into <head> by src/lib/head-inline.mjs and run before first paint (05-DESIGN D6.9).
// Sets data-tier (0 stills, 1 phone world, 2 full world; data-tier-flag when ?tier set it; hcwa:gpu0 = this
// browser's GPU cannot run the world), data-motion (on|off), data-reduced
// (the OS asks for less motion), sun-pending (the CSS sun-in will play), wf (fonts cached) and,
// on staging, drafts-hidden (the reviewer hid the draft outlines this session).
// Statements end in semicolons: the file is joined onto one line. Budget 1.5 KB.
(function (d, w) {
  var h = d.documentElement, n = navigator, q = new URLSearchParams(location.search);
  var get = function (s, k) { try { return w[s].getItem(k); } catch (e) { return null; } };
  var mm = function (m) { return !!w.matchMedia && w.matchMedia(m).matches; };
  var proto = __PROTO__;
  var reduced = mm("(prefers-reduced-motion: reduce)");
  var off = reduced || get("localStorage", "hcwa:motion") === "off" || (proto && q.get("motion") === "off");
  var c = n.connection || {}, mem = n.deviceMemory;
  var tier = off || c.saveData || /2g/.test(c.effectiveType || "") || (mem && mem < 2) || get("sessionStorage", "hcwa:tier") === "0" || get("localStorage", "hcwa:gpu0") === n.userAgent ? 0 : mm("(pointer: fine)") && (!mem || mem >= 8) && (n.hardwareConcurrency || 0) >= 6 ? 2 : 1;
  if (proto && !off && /^[012]$/.test(q.get("tier") || "")) { tier = +q.get("tier"); h.setAttribute("data-tier-flag", ""); }
  h.setAttribute("data-tier", tier);
  h.setAttribute("data-motion", off ? "off" : "on");
  if (reduced) h.setAttribute("data-reduced", "");
  var cls = ["js"];
  if (!off && h.hasAttribute("data-lit") && !get("sessionStorage", "hcwa:sun:" + location.pathname)) cls.push("sun-pending");
  if (get("sessionStorage", "hcwa:wf")) cls.push("wf");
  if (proto && get("sessionStorage", "hcwa:drafts")) cls.push("drafts-hidden");
  h.className = (h.className + " " + cls.join(" ")).trim();
})(document, window);
