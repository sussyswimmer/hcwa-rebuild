import { ScrollTrigger } from "gsap/ScrollTrigger";
import { write } from "./batch.js";

// S3's stage without the live world (PLAN H7, 05-DESIGN D5.3, D6.10): --p for the stills of its three beats
// and the blend into sun-100, and the flood as a one-shot the scroll arms (is-flooded at 0.78, cleared under
// 0.70, so hovering at the threshold never flickers). The live world reads the same numbers from
// world/journey.js SHUTTERS (test/beats.test.mjs keeps them equal): motion/ never imports world/. The stage
// is CSS (html.stages and a screen 640 px tall): this only arms it while that holds.
export const TIMELINE = { slats: [0, 0.3], leaves: [0.3, 0.66], flood: 0.78, unflood: 0.7, blend: [0.76, 0.92] };

export function initShutters() {
  const root = document.documentElement;
  const el = document.querySelector(".shutters[data-stage]");
  if (!el || !root.classList.contains("stages")) return;
  const track = el.querySelector("[data-stage-track]");
  // The first two beats' stills, for the stage without the world (lazy: never loaded while it draws).
  const beats = el.querySelector("template.shutters__beats");
  if (beats) beats.replaceWith(beats.content.cloneNode(true));
  const tall = matchMedia("(min-height: 640px)");
  let flooded = false, last = -1, p = 0;
  const apply = () => {
    const on = tall.matches && root.classList.contains("stages");
    el.classList.toggle("is-armed", on);
    if (!on) return;
    el.style.setProperty("--p", p.toFixed(4));
    if (!flooded && p >= TIMELINE.flood) flooded = true;
    else if (flooded && p < TIMELINE.unflood) flooded = false;
    el.classList.toggle("is-flooded", flooded);
  };
  // The scroll only reads; the writes wait for the frame's one batch (batch.js).
  const update = (st) => {
    if (st.progress === last) return;
    last = p = st.progress;
    write(apply);
  };
  ScrollTrigger.create({ trigger: track, start: "top top", end: "bottom bottom", onUpdate: update, onRefresh: (st) => { last = p = st.progress; write(apply); } });
}
