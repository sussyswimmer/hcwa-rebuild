import { ScrollTrigger } from "gsap/ScrollTrigger";
import { write } from "./batch.js";

// The passage's text (PLAN H3, 05-DESIGN D5.3 S2, D5.4): on the stage layout the statement waits in the
// light, rises in at 0.16 and fades at 0.78 (time-based, never scrubbed, reversed on the way back), and the
// sunlit reading runs with the scroll over 0.18-0.74 through one custom property. The halo under it comes
// in with the wash. The numbers are the world's timeline (world/journey.js PASSAGE; test/beats.test.mjs
// keeps them equal): motion/ never imports world/.
export const TIMELINE = { approach: [0, 0.16], plateau: [0.16, 0.78], read: [0.18, 0.74] };
// The halo goes with the statement (0.78), before the wash has cleared off the corridor.
const HALO = [0.1, 0.16, 0.78, 0.83];
const smooth = (a, b, x) => { const t = Math.min(Math.max((x - a) / (b - a), 0), 1); return t * t * (3 - 2 * t); };

export function initPassage() {
  const root = document.documentElement;
  const el = document.querySelector(".passage[data-stage]");
  if (!el || !root.classList.contains("stages")) return;
  const statement = el.querySelector(".statement"), text = el.querySelector(".passage__text"), frame = el.querySelector(".passage__frame");
  const [r0, r1] = TIMELINE.read, [in0, out0] = TIMELINE.plateau;
  let last = -1, p = 0;
  const unfit = () => el.classList.contains("is-unfit");
  // The scroll only reads progress; the writes wait for the frame's one batch, after every read (batch.js).
  const apply = () => {
    if (!root.classList.contains("stages") || unfit()) return void el.classList.remove("is-armed", "is-reading");
    el.classList.add("is-armed");
    el.classList.toggle("is-reading", p >= in0 && p < out0);
    statement.style.setProperty("--reading", Math.min(Math.max((p - r0) / (r1 - r0), 0), 1).toFixed(4));
    el.style.setProperty("--halo", (smooth(HALO[0], HALO[1], p) * (1 - smooth(HALO[2], HALO[3], p))).toFixed(3));
  };
  const update = (st) => {
    if (st.progress === last) return;
    last = p = st.progress;
    write(apply);
  };

  // The statement must fit the pinned frame below the header (WCAG 1.4.4: at 200% text a line pinned
  // under the header or below the fold is never read). Otherwise the passage goes back to the band of
  // light, as the corridor stacks when a card does not fit its panel.
  const decide = () => {
    const was = unfit();
    el.classList.remove("is-unfit");
    const cs = getComputedStyle(frame);
    const room = frame.clientHeight - parseFloat(cs.paddingTop) - parseFloat(cs.paddingBottom);
    const now = cs.position === "sticky" && text.offsetHeight > room + 1;
    el.classList.toggle("is-unfit", now);
    apply();
    if (now !== was) ScrollTrigger.refresh();
  };
  let pending = 0;
  const later = () => { cancelAnimationFrame(pending); pending = requestAnimationFrame(decide); };
  const ro = new ResizeObserver(later);
  ro.observe(text);
  ro.observe(frame);
  document.fonts?.ready.then(later);
  decide();
  ScrollTrigger.create({ trigger: el, start: "top top", end: "bottom bottom", onUpdate: update, onRefresh: (st) => { last = p = st.progress; write(apply); } });
}
