import Lenis from "lenis";
import "lenis/dist/lenis.css";

// Wheel smoothing for the pages without the motion layer (Contact, News, every inner page): the same Lenis
// settings as motion/scroll.js (M6 pacing, 05-DESIGN D5.2), but no GSAP and no ScrollTrigger. Native distance per
// wheel tick (wheelMultiplier 1), touch stays native (syncTouch false), no snap, never a scroll-jack: Lenis only
// spreads each tick over about half a second, and the page's own scroll listeners (motion/scroll3d.js) keep reading
// scrollY. main.js loads it only with motion on, a fine pointer and no reduced-motion request. Lenis is made at the
// first wheel event and its frames run only while it is moving, so an idle page draws nothing.
export const LERP = 0.07;

export function startSmoothWheel() {
  const fine = matchMedia("(pointer: fine)"), reduced = matchMedia("(prefers-reduced-motion: reduce)");
  const root = document.documentElement;
  let lenis = null, raf = 0, still = 0;
  const on = () => fine.matches && !reduced.matches && root.dataset.motion !== "off";
  const frame = (t) => {
    raf = 0;
    if (!lenis) return;
    lenis.raf(t);
    still = lenis.isScrolling ? 0 : still + 1;
    if (still <= 3) raf = requestAnimationFrame(frame);
  };
  const stop = () => { cancelAnimationFrame(raf); raf = 0; lenis?.destroy(); lenis = null; };
  const wake = () => {
    if (!on()) return stop();
    lenis ??= new Lenis({
      lerp: LERP, wheelMultiplier: 1, smoothWheel: true, syncTouch: false, autoRaf: false,
      prevent: (node) => Boolean(node.closest?.("dialog, [data-lenis-prevent]")),
    });
    still = 0;
    if (!raf) raf = requestAnimationFrame(frame);
  };
  addEventListener("wheel", wake, { passive: true });
  // Pause or the OS asking for less motion: back to native scroll at once.
  window.hcwa?.addEventListener?.("pause", () => { if (!on()) stop(); });
  reduced.addEventListener?.("change", () => { if (!on()) stop(); });
}
