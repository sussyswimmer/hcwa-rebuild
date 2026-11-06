import { startScroll } from "./scroll.js";
import { startVelocity } from "./velocity.js";
import { settle } from "./ticker.js";

// The motion layer (GSAP, ScrollTrigger, CustomEase, Lenis): its own chunk, loaded by main.js after
// the UI, and never under reduced motion or pause. Velocity only matters when a world can listen.
export function startMotion({ tier }) {
  startScroll();
  if (tier > 0) startVelocity();
  // ScrollTrigger's first refresh runs on the ticker; once that is done nothing needs frames until a scroll.
  setTimeout(settle, 1000);
}
