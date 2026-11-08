import { ScrollTrigger } from "gsap/ScrollTrigger";
import { write } from "./batch.js";

// The corridor's stage (PLAN H4, 05-DESIGN D5.3 S2): which card the pinned panel shows, the door index,
// keyboard focus, and whether the stage fits at all. Cards are never scrubbed: one is shown whole or not at
// all. The numbers are the world's walk (world/journey.js WALK; test/beats.test.mjs keeps them equal):
// motion/ never imports world/.
export const TIMELINE = { head: [0, 0.08], beats: [0.08, 0.92], cardIn: 0.18, cardOut: 0.97, release: 0.95 };
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
  const glass = el.querySelector(".bays__glass");
  const cards = [...el.querySelectorAll("[data-beat]")], n = cards.length;
  const texts = cards.map((c) => c.querySelector(".bay__text"));
  const links = [...el.querySelectorAll(".bays__rail a[data-to]")];
  const staged = () => root.classList.contains("stages") && el.classList.contains("is-stage");
  let shown, at, pane = -1, q = 0;

  const show = (k) => {
    if (k === shown) return;
    shown = k;
    head.classList.toggle("is-current", k === -1);
    cards.forEach((c, i) => c.classList.toggle("is-current", i === k));
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
    if (!staged()) return fade(1);
    show(cardAt(q, n));
    mark(doorAt(q, n), q >= TIMELINE.beats[0]);
    fade(paneAt(q));
  };
  // The scroll only reads; the writes wait for the frame's one batch (batch.js).
  const update = (st) => { q = st.progress; write(apply); };

  // The stage only when the stage header and every card fit the panel; otherwise (a long card, 200% text,
  // a short screen) the stacked layout, with the same cards and the bays' views (D6.10).
  const room = () => {
    const cs = getComputedStyle(panel);
    return panel.clientHeight - parseFloat(cs.paddingTop) - parseFloat(cs.paddingBottom) - rail.offsetHeight - parseFloat(cs.rowGap || 0);
  };
  const fits = () => { const r = room(); return n > 0 && [head, ...texts].every((t) => t.offsetHeight <= r + 1); };
  const decide = () => {
    const was = el.classList.contains("is-stage");
    el.classList.add("is-stage");
    const ok = root.classList.contains("stages") && fits();
    if (!ok) el.classList.remove("is-stage");
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
    const card = e.target.closest("[data-beat]");
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
