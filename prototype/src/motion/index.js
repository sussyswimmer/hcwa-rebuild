import { startScroll } from "./scroll.js";
import { startVelocity } from "./velocity.js";
import { settle } from "./ticker.js";
import { initPassage } from "./passage.js";
import { initStages } from "./stages.js";
import { initShutters } from "./shutters.js";

// The motion layer (GSAP, ScrollTrigger, CustomEase, Lenis): its own chunk, loaded by main.js after
// the UI, and never under reduced motion or pause. Velocity only matters when a world can listen.
// M8 long tasks: each part starts in its own task (the chunk's evaluation, then one task per part), so no one of
// them adds its style and layout reads to the others' (one 140-340 ms task on a Moto G at 4x CPU before).
const next = () => (globalThis.scheduler?.yield ? scheduler.yield() : new Promise((go) => setTimeout(go, 0)));
export async function startMotion({ tier }) {
  for (const part of [startScroll, initPassage, initStages, initShutters, ...(tier > 0 ? [startVelocity] : [])]) {
    await next();
    part();
  }
  // ScrollTrigger's first refresh runs on the ticker; once that is done nothing needs frames until a scroll.
  setTimeout(settle, 1000);
}
