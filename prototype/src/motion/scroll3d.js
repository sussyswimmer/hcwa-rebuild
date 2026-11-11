// The Contact and News openings (PLAN-M4 5.3; partials/scroll3d.mjs, styles/openings.css). Native scroll
// only: no Lenis, no GSAP, never imports motion/index.js. Two jobs:
// 1. Measure where each stage lets go (--s3d-end, px of scroll from the top), which CSS scroll-driven
//    animations use as their range end; again on resize and when the layout changes (pause, fonts,
//    a stage changing height).
// 2. Where CSS scroll-driven animations are not supported (Firefox today), write the same tracks the
//    CSS keyframes write: one passive scroll listener, one rAF per frame, asleep while no stage is on
//    screen (IntersectionObserver). TRACKS mirrors scroll3d.mjs.
const TRACKS = [
  ["cam", 0, 0.86, [0.77, 0, 0.175, 1]],
  ["sun", 0, 0.86, [0.16, 1, 0.3, 1]],
  ["open", 0.55, 0.92, [0.37, 0, 0.63, 1]],
];

// cubic-bezier(x1, y1, x2, y2) at x (Newton steps, then bisection where the slope is flat).
const bezier = ([x1, y1, x2, y2]) => {
  const at = (a, b, t) => ((1 - 3 * b + 3 * a) * t + (3 * b - 6 * a)) * t * t + 3 * a * t;
  return (x) => {
    let t = x;
    for (let i = 0; i < 6; i++) {
      const d = 3 * (1 - 3 * x2 + 3 * x1) * t * t + 2 * (3 * x2 - 6 * x1) * t + 3 * x1;
      if (Math.abs(d) < 1e-6) break;
      t -= (at(x1, x2, t) - x) / d;
    }
    if (t < 0 || t > 1 || Math.abs(at(x1, x2, t) - x) > 1e-4) {
      let lo = 0, hi = 1;
      for (let i = 0; i < 24; i++) { t = (lo + hi) / 2; if (at(x1, x2, t) < x) lo = t; else hi = t; }
    }
    return at(y1, y2, t);
  };
};
const clamp = (v) => Math.min(1, Math.max(0, v));
const EASES = TRACKS.map(([name, from, to, e]) => [name, from, to, bezier(e)]);

export function initScroll3d() {
  const stages = [...document.querySelectorAll("[data-scroll3d]")];
  if (!stages.length) return;
  const ends = new Map();
  const measure = () => {
    for (const s of stages) {
      const min = Number(s.dataset.s3dMin || 0) * innerHeight;
      const end = Math.max(1, min, Math.round(s.getBoundingClientRect().bottom + scrollY - innerHeight));
      ends.set(s, end);
      s.style.setProperty("--s3d-end", end + "px");
    }
  };
  measure();
  let pending = 0;
  const remeasure = () => { cancelAnimationFrame(pending); pending = requestAnimationFrame(() => { measure(); write(); }); };
  addEventListener("resize", remeasure, { passive: true });
  addEventListener("load", remeasure, { once: true });
  document.fonts?.ready.then(remeasure);
  // Pause and the OS asking for less motion switch the stage to its rest layout and back; a filter or a
  // late image changes a stage's height.
  window.hcwa?.addEventListener?.("pause", remeasure);
  if ("ResizeObserver" in window) { const ro = new ResizeObserver(remeasure); stages.forEach((s) => ro.observe(s)); }

  const native = window.CSS?.supports?.("animation-timeline: scroll()");
  let write = () => {};
  if (native) return;

  const on = new Set();
  let frame = 0;
  write = () => {
    frame = 0;
    for (const s of on) {
      const p = clamp(scrollY / (ends.get(s) || 1));
      s.style.setProperty("--s3d-p", p.toFixed(4));
      for (const [name, from, to, ease] of EASES) s.style.setProperty("--s3d-" + name, ease(clamp((p - from) / (to - from))).toFixed(4));
    }
  };
  const tick = () => { if (!frame && on.size) frame = requestAnimationFrame(write); };
  if ("IntersectionObserver" in window) {
    const io = new IntersectionObserver((entries) => {
      for (const e of entries) (e.isIntersecting ? on.add(e.target) : on.delete(e.target));
      tick();
    });
    stages.forEach((s) => io.observe(s));
  } else stages.forEach((s) => on.add(s));
  addEventListener("scroll", tick, { passive: true });
  tick();
}
