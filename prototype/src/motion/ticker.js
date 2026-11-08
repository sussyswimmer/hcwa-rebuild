import { gsap } from "gsap";

// GSAP's ticker keeps a requestAnimationFrame loop alive for the life of the page once it has run, even
// with nothing to do, and anything that adds a tween, a delayed call or a ticker listener (a ScrollTrigger
// refresh on resize, a scroll) wakes it again. The motion layer holds it only while something moves (Lenis
// smoothing a wheel, the velocity spring settling) and puts it back to sleep whenever nothing does: when the
// last holder lets go, after each scroll ends and after each refresh. An idle page draws no frames (D6.7).
let users = 0, timer = 0;
const active = () => gsap.globalTimeline.getChildren(true, true, false).some((t) => t.isActive());
export const settle = () => { clearTimeout(timer); if (!users && !active()) gsap.ticker.sleep(); };
// After ScrollTrigger's own trailing work (its scrollEnd and refresh calls are followed by a frame or two).
export const settleSoon = (ms = 250) => { clearTimeout(timer); timer = setTimeout(settle, ms); };
export const hold = () => { if (users++ === 0) { clearTimeout(timer); gsap.ticker.wake(); } };
export const release = () => { users = Math.max(0, users - 1); if (!users) settle(); };
