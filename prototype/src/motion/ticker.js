import { gsap } from "gsap";

// GSAP's ticker keeps a requestAnimationFrame loop alive for the life of the page once it has run, even
// with nothing to do. The motion layer holds it only while something scrolls (Lenis smoothing a wheel,
// the velocity spring settling) and lets it sleep otherwise, so an idle page draws no frames. Adding a
// tween or a ticker listener wakes it on its own.
let users = 0;
export const hold = () => { if (users++ === 0) gsap.ticker.wake(); };
export const release = () => { users = Math.max(0, users - 1); if (!users) gsap.ticker.sleep(); };
export const settle = () => { if (!users) gsap.ticker.sleep(); };
