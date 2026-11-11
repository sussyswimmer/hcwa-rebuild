import { $, $$ } from "./dom.js";

// Our Work filters (PLAN P1). Without JS the chips are links that land on #f-<area> / #f--<status> and CSS
// :target hides what does not match (pages.css). Here they become one state, area and status together,
// kept in the URL (?area=&status=, so a shared link and WordPress agree) and applied in place: the items
// that stay glide to their new places (FLIP, 240 ms) and the ones that come back fade in. Paused or
// reduced motion: instantly.
const D = 240;
const EASE = "cubic-bezier(0.23, 1, 0.32, 1)";

export function initFilters() {
  const hub = $("[data-hub]");
  if (!hub) return;
  const areas = new Set($$(".chip[data-area]", hub).map((a) => a.dataset.area));
  const statuses = new Set($$(".seg__opt[data-status]", hub).map((a) => a.dataset.status));
  const q = new URLSearchParams(location.search);
  const hash = location.hash.match(/^#f-([a-z-]*?)(?:--([a-z]+))?$/);
  const pick = (v, set) => (set.has(v) ? v : "");
  const state = { area: pick(q.get("area") ?? hash?.[1] ?? "", areas), status: pick(q.get("status") ?? hash?.[2] ?? "", statuses) };
  hub.classList.add("js-filters");
  apply(hub, state, false);

  hub.addEventListener("click", (e) => {
    const a = e.target.closest("a[data-area], a[data-status]");
    if (!a || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    e.preventDefault();
    if ("area" in a.dataset) state.area = a.dataset.area;
    else state.status = a.dataset.status;
    const url = new URL(location.href);
    url.hash = "";
    for (const k of ["area", "status"]) (state[k] ? url.searchParams.set(k, state[k]) : url.searchParams.delete(k));
    history.replaceState(history.state, "", url);
    apply(hub, state, true);
    // The empty note's "show all" link sits where the list was: send focus back to the chips.
    if (a.closest("[data-filter-empty]")) $(".chip--all", hub)?.focus();
  });
}

function apply(hub, { area, status }, animate) {
  const current = (el, on) => (on ? el.setAttribute("aria-current", "true") : el.removeAttribute("aria-current"));
  for (const chip of $$(".chip[data-area]", hub)) current(chip, chip.dataset.area === area);
  for (const opt of $$(".seg__opt[data-status]", hub)) current(opt, opt.dataset.status === status);

  const items = $$("[data-areas]", hub);
  const motion = animate && document.documentElement.dataset.motion !== "off" && !matchMedia("(prefers-reduced-motion: reduce)").matches;
  // First: where every visible item is now (one read pass before any write).
  const before = motion ? new Map(items.filter((el) => !el.hidden).map((el) => [el, el.getBoundingClientRect()])) : null;
  for (const el of items) {
    const okArea = !area || el.dataset.areas.split(" ").includes(area);
    const okStatus = !status || !el.dataset.status || el.dataset.status === status;
    el.hidden = !(okArea && okStatus);
  }
  for (const group of $$("[data-filter-group]", hub)) {
    const empty = $("[data-filter-empty]", group);
    if (empty) empty.hidden = $$("[data-areas]", group).some((el) => !el.hidden);
  }
  if (animate) say(hub);
  if (!motion) return;
  // Last, invert, play: transform and opacity only.
  for (const el of items) {
    if (el.hidden) continue;
    const from = before.get(el);
    if (!from) { el.animate({ opacity: [0, 1], translate: ["0 8px", "0 0"] }, { duration: D, easing: EASE }); continue; }
    const to = el.getBoundingClientRect();
    const dx = from.left - to.left, dy = from.top - to.top;
    if (Math.abs(dx) > 0.5 || Math.abs(dy) > 0.5) el.animate({ translate: [`${dx}px ${dy}px`, "0 0"] }, { duration: D, easing: EASE });
  }
}

// What a choice left on the page, said once in the polite live region (WCAG 4.1.3): "Showing 1 center,
// 0 projects" on Our Work, "Showing 3 posts" on News. Only after a visitor's choice, never on load. A
// kind is counted only when the region carries its words (data-<kind>-one / data-<kind>-many).
const KINDS = { centers: ".door[data-areas]", programs: ".program-row[data-areas]", posts: ".notice[data-areas]" };
function say(hub) {
  const out = $("[data-filter-status]", hub);
  if (!out) return;
  const count = (sel) => $$(sel, hub).filter((el) => !el.hidden).length;
  const word = (n, kind) => out.dataset[kind + (n === 1 ? "One" : "Many")].replace("{n}", n);
  let text = out.dataset.template;
  for (const [kind, sel] of Object.entries(KINDS)) if (out.dataset[kind + "One"]) text = text.replace("{" + kind + "}", word(count(sel), kind));
  out.textContent = text;
}
