import { ScrollTrigger } from "gsap/ScrollTrigger";
import { write } from "./batch.js";

// S3's stage without the live world (PLAN H7, 05-DESIGN D5.3, D6.10): --a (the approach: the section rising into
// place, M7) for the stills of its three beats, --p (the pinned stage) for the blend into sun-100, and the flood as a
// one-shot the scroll arms (is-flooded at 0.30, cleared under 0.22, so hovering at the threshold never flickers).
// The live world reads the same numbers from world/journey.js SHUTTERS (test/shutters.test.mjs keeps them equal):
// motion/ never imports world/. The stage is CSS (html.stages and a screen 640 px tall): this only arms it while
// that holds.
export const TIMELINE = { slats: [0.12, 0.5], leaves: [0.5, 1], flood: 0.3, unflood: 0.22, blend: [0.28, 0.62] };

export function initShutters() {
  const root = document.documentElement;
  const el = document.querySelector(".shutters[data-stage]");
  if (!el || !root.classList.contains("stages")) return;
  const track = el.querySelector("[data-stage-track]");
  // The first two beats' stills, for the stage without the world (lazy: never loaded while it draws).
  const beats = el.querySelector("template.shutters__beats");
  if (beats) beats.replaceWith(beats.content.cloneNode(true));
  const tall = matchMedia("(min-height: 640px)");
  let flooded = false, last = -1, lastA = -1, p = 0, a = 0;
  const apply = () => {
    const on = tall.matches && root.classList.contains("stages");
    el.classList.toggle("is-armed", on);
    if (!on) return;
    el.style.setProperty("--a", a.toFixed(4));
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
  const approach = (st) => {
    if (st.progress === lastA) return;
    lastA = a = st.progress;
    write(apply);
  };
  ScrollTrigger.create({ trigger: track, start: "top top", end: "bottom bottom", onUpdate: update, onRefresh: (st) => { last = p = st.progress; write(apply); } });
  // The approach: from the section's top at the screen's foot to the frame pinning (the track's top at the top).
  // The distance is the lead above the track plus a screen, read again on every refresh.
  ScrollTrigger.create({ trigger: el, start: "top bottom", end: () => `+=${track.offsetTop + innerHeight}`, invalidateOnRefresh: true, onUpdate: approach, onRefresh: (st) => { lastA = a = st.progress; write(apply); } });
}
