import { ScrollTrigger } from "gsap/ScrollTrigger";
import { write } from "./batch.js";

// The corridor's stage (PLAN H4, 05-DESIGN D5.3 S2): which card the pinned panel shows, the door index,
// keyboard focus, and whether the stage fits at all. Cards are never scrubbed: one is shown whole or not at
// all. The numbers are the world's walk (world/journey.js WALK; test/beats.test.mjs keeps them equal):
// motion/ never imports world/.
export const TIMELINE = { head: [0, 0.12], beats: [0.12, 0.92], cardIn: 0.18, cardOut: 0.98, release: 0.95 };
const width = (n) => (TIMELINE.beats[1] - TIMELINE.beats[0]) / Math.max(n, 1);
const smooth = (a, b, x) => { const t = Math.min(Math.max((x - a) / (b - a), 0), 1); return t * t * (3 - 2 * t); };

// What the panel shows at stage progress q: -1 the stage header, i door i's card, null nothing (the walk
// between two doors, or the stage letting go). A card holds until its door has left the middle of the
// frame; the last one goes at `release`, as the pane fades.
export function cardAt(q, n) {
  if (n <= 0) return q < TIMELINE.release ? -1 : null;
  const w = width(n);
  if (q < TIMELINE.beats[0] + TIMELINE.cardIn * w) return -1;
  const f = (q - TIMELINE.beats[0]) / w, i = Math.min(Math.floor(f), n - 1), u = f - i;
  return u >= TIMELINE.cardIn && (i === n - 1 ? q < TIMELINE.release : u < TIMELINE.cardOut) ? i : null;
}
// The pane (and the door index on it) over the stage's last 0.05: 1 → 0, scrubbed (opacity only), as the last
// card leaves and the camera walks on past its door, so the frame lets go of the corridor with no box on it
// (05-DESIGN D0.5).
export const paneAt = (q) => 1 - smooth(TIMELINE.release, 1, q);
// The door the walk is at (its beat), clamped to the doors there are.
export const doorAt = (q, n) => Math.min(Math.max(Math.floor((q - TIMELINE.beats[0]) / width(n)), 0), Math.max(n - 1, 0));
// The stage progress that reads door i: the middle of its beat, its card in, the camera drifting.
export const beatMiddle = (i, n) => TIMELINE.beats[0] + (i + 0.5) * width(n);

export function initStages() {
  const root = document.documentElement;
  const el = document.querySelector(".bays-section[data-stage]");
  if (!el || !root.classList.contains("stages")) return;
  const track = el.querySelector("[data-stage-track]"), frame = el.querySelector(".bays__frame");
  const panel = el.querySelector(".bays__panel"), rail = el.querySelector(".bays__rail"), head = el.querySelector(".bays__head");
  const glass = el.querySelector(".bays__glass"), all = el.querySelector(".bays__all"), list = el.querySelector(".bays");
  const views = [...el.querySelectorAll(".bay__view")], next = el.nextElementSibling;
  const cards = [...el.querySelectorAll("[data-beat]")], n = cards.length;
  const texts = cards.map((c) => c.querySelector(".bay__text"));
  const links = [...el.querySelectorAll(".bays__rail a[data-to]")];
  const staged = () => root.classList.contains("stages") && el.classList.contains("is-stage");
  let shown, at, pane = -1, q = 0;

  // Where the panel's content is, read once per layout (decide): the pane is plaster only from the door index's
  // top to the shown text's foot (stages.css, critique r1: the yellow wash, the mint dado and the floor show above
  // and below the card), and the link to every center sits just under the last card (never alone on plaster).
  // Between two cards the pane eases back to the index alone (critique r2: on a phone the runner and the dado fill
  // the screen between beats, never an empty band of plaster). In the glass's own box (phones: it rises from the
  // frame's foot; desktop: it starts at the frame's top).
  const GAP = 12;
  let ext = null;
  // The link goes beside the last card's own link when the row has room for it (one row of links, the card no
  // taller, clear of the sheet that rises under the stage's end on phones), else on its own row under the card.
  const measure = () => {
    const f = frame.getBoundingClientRect(), y = (e) => e.getBoundingClientRect();
    const lb = n ? y(texts[n - 1]).bottom : y(head).bottom, cell = y(list), a = all.firstElementChild;
    const own = n ? texts[n - 1].querySelector(".bay__links")?.lastElementChild : null, o = own ? y(own) : null;
    const beside = o && o.right + 2 * GAP + a.offsetWidth <= cell.right;
    const ax = beside ? o.right + 2 * GAP - cell.left : 0, ay = beside ? o.top - cell.top : lb - cell.top + GAP;
    const bottom = beside ? lb : lb + GAP + all.offsetHeight;
    const g = y(glass).top;
    ext = { top: y(rail).top - g, rail: y(rail).bottom - g, head: y(head).bottom - g, cards: texts.map((t) => y(t).bottom - g), all: bottom - g };
    el.style.setProperty("--all-x", `${Math.round(ax)}px`);
    el.style.setProperty("--all-y", `${Math.round(ay)}px`);
    // The link must fit the panel (M6: on desktop the list is only as tall as its tallest card, centred with the index).
    const foot = y(panel).bottom - parseFloat(getComputedStyle(panel).paddingBottom);
    return bottom <= Math.max(cell.bottom, foot) + 1;
  };
  let lastK = -1;
  const extent = (k) => {
    lastK = k;
    if (!ext) return;
    const b = k == null ? ext.rail : k === -1 ? ext.head : k === n - 1 ? ext.all : ext.cards[k];
    glass.style.setProperty("--pane-t", `${Math.round(ext.top)}px`);
    glass.style.setProperty("--pane-b", `${Math.round(b)}px`);
  };
  const show = (k) => {
    if (k === shown) return;
    shown = k;
    head.classList.toggle("is-current", k === -1);
    cards.forEach((c, i) => c.classList.toggle("is-current", i === k));
    el.classList.toggle("is-last", n > 0 && k === n - 1);
    extent(k);
  };
  // The door index and the light in the panel follow the walk, between cards too.
  const mark = (i, lit) => {
    const key = lit ? i : -1;
    if (key === at) return;
    at = key;
    cards.forEach((c, j) => c.classList.toggle("is-at", j === i));
    links.forEach((a, j) => (j === key ? a.setAttribute("aria-current", "true") : a.removeAttribute("aria-current")));
    el.style.setProperty("--now", key >= 0 ? cards[key].style.getPropertyValue("--spill") : "");
  };
  // Opacity only, and only when it changes: the pane and the index fade together.
  const fade = (v) => {
    if (v === pane) return;
    pane = v;
    const o = v >= 1 ? "" : v.toFixed(3);
    glass.style.opacity = o;
    rail.style.opacity = o;
    rail.inert = v <= 0;
  };
  const apply = () => {
    writeHem();
    if (!staged()) return fade(1);
    show(cardAt(q, n));
    mark(doorAt(q, n), q >= TIMELINE.beats[0]);
    fade(paneAt(q));
  };
  // Without the live world the bay's view is the picture (stages.css): it fades to the plaster over the hem above
  // the next sheet's top, as the canvas does (world/journey.js hemSpan; critique r2, D0.5). --sheet-y is that top in
  // the frame's box, read with the progress (the frame is pinned at the top until the stage ends, and the sheet
  // scrolls with it after that) and written only when it changes, only while the views show.
  let sheetY = null, sheetW = null;
  const hem = () => {
    if (!next || root.classList.contains("world-live")) return null;
    const v = next.getBoundingClientRect().top - frame.getBoundingClientRect().top;
    return v < frame.offsetHeight * 1.4 ? Math.round(v) : null;
  };
  const writeHem = () => {
    if (sheetY === sheetW) return;
    sheetW = sheetY;
    for (const v of views) sheetY == null ? v.style.removeProperty("--sheet-y") : v.style.setProperty("--sheet-y", `${sheetY}px`);
  };
  // The scroll only reads; the writes wait for the frame's one batch (batch.js).
  const update = (st) => { q = st.progress; sheetY = staged() ? hem() : null; write(apply); };

  // The stage only when the stage header and every card fit the panel; otherwise (a long card, 200% text,
  // a short screen) the stacked layout, with the same cards and the bays' views (D6.10).
  const room = () => {
    const cs = getComputedStyle(panel);
    return panel.clientHeight - parseFloat(cs.paddingTop) - parseFloat(cs.paddingBottom) - rail.offsetHeight - parseFloat(cs.rowGap || 0);
  };
  const fits = () => { const r = room(); return n > 0 && [head, ...texts].every((t) => t.offsetHeight <= r + 1) && measure(); };
  const decide = () => {
    const was = el.classList.contains("is-stage");
    el.classList.add("is-stage");
    const ok = root.classList.contains("stages") && fits();
    if (!ok) { el.classList.remove("is-stage"); ext = null; }
    else extent(lastK);
    if (ok !== was) ScrollTrigger.refresh();
  };
  let pending = 0;
  const later = () => { cancelAnimationFrame(pending); pending = requestAnimationFrame(decide); };
  new ResizeObserver(later).observe(frame);
  const ro = new ResizeObserver(later);
  [head, ...texts].forEach((t) => ro.observe(t));
  document.fonts?.ready.then(later);
  decide();
  show(-1);
  mark(0, false);

  ScrollTrigger.create({ trigger: track, start: "top top", end: "bottom bottom", onUpdate: update, onRefresh: update });

  // Scroll to door i's beat: the index smoothly (instantly when motion is off), focus at once.
  const go = (i, instant) => {
    const top = track.getBoundingClientRect().top + scrollY, len = track.offsetHeight - frame.offsetHeight;
    scrollTo({ top: Math.round(top + beatMiddle(i, n) * len), behavior: instant || root.dataset.motion === "off" ? "instant" : "smooth" });
  };
  rail?.addEventListener("click", (e) => {
    const a = e.target.closest("a[data-to]");
    if (!a || !staged()) return;
    e.preventDefault();
    go(Number(a.dataset.to), false);
  });
  // Focus on a card that is not shown walks to its door first, so focus never lands on an empty pane: the
  // card is shown at once (no fade) and the page jumps to its beat.
  el.addEventListener("focusin", (e) => {
    // The link to every center belongs to the last card: focusing it walks to the last door.
    const card = e.target.closest("[data-beat]") ?? (all.contains(e.target) ? cards[n - 1] : null);
    if (!card || !staged()) return;
    const i = cards.indexOf(card);
    if (i === shown) return;
    frame.classList.add("is-instant");
    show(i);
    mark(i, true);
    go(i, true);
    requestAnimationFrame(() => { go(i, true); requestAnimationFrame(() => frame.classList.remove("is-instant")); });
  });
}
