import Lenis from "lenis";
import "lenis/dist/lenis.css";

// Wheel smoothing for the pages without the motion layer (Contact and most inner pages): the same Lenis
// settings as motion/scroll.js (M6 pacing, 05-DESIGN D5.2), but no GSAP and no ScrollTrigger. Native distance per
// wheel tick (wheelMultiplier 1), touch stays native (syncTouch false), no snap, never a scroll-jack: Lenis only
// spreads each tick over about half a second, and the page's own scroll listeners (motion/scroll3d.js) keep reading
// scrollY. main.js loads it only with motion on, a fine pointer and no reduced-motion request, after load + idle, and
// never on a page with heavy scroll-linked paint (the News wall, the Little Rose garden). Lenis is made in idle time
// (or at the first pointer move, whichever is first), so the first wheel tick is already smoothed; its frames run
// only while it is moving, so an idle page draws nothing.
export const LERP = 0.07;

// What both Lenis instances share. A form field or any box that can still scroll the way the wheel goes keeps the
// wheel (allowNestedScroll: a long message in the textarea scrolls, the page stays put); dialogs, selects and
// [data-lenis-prevent] always scroll natively.
export const lenisOptions = () => ({
  lerp: LERP, wheelMultiplier: 1, smoothWheel: true, syncTouch: false, autoRaf: false, allowNestedScroll: true,
  prevent: (node) => Boolean(node.closest?.("dialog, select, [data-lenis-prevent]")),
});

// The frame-time guard: Lenis makes the visible scroll wait for the main thread, so on a machine that cannot keep
// up (frames more than 50 ms apart while it glides) it would stutter where native scroll keeps moving. Three slow
// frames in one glide (a gap over 150 ms counts twice) and the glide is handed back to native scroll for the rest of
// the visit (sessionStorage hcwa:wheel = native, read by main.js on the next page).
export const NATIVE_KEY = "hcwa:wheel";
export function frameGuard() {
  let last = 0, slow = 0;
  return {
    reset() { last = 0; slow = 0; },
    // t in ms; true once this glide has been too slow.
    slow(t) {
      if (last) { const dt = t - last; if (dt > 150) slow += 2; else if (dt > 50) slow += 1; }
      last = t;
      return slow >= 3;
    },
  };
}
export function goNative(lenis) {
  const target = lenis?.targetScroll;
  lenis?.destroy();
  // The rest of the glide lands at once, so no distance the visitor asked for is lost.
  if (Number.isFinite(target)) scrollTo({ top: target, behavior: "instant" });
  try { sessionStorage.setItem(NATIVE_KEY, "native"); } catch { /* storage blocked: this page only */ }
}

export function startSmoothWheel() {
  const fine = matchMedia("(pointer: fine)"), reduced = matchMedia("(prefers-reduced-motion: reduce)");
  const root = document.documentElement;
  const guard = frameGuard();
  let lenis = null, raf = 0, still = 0, dead = false;
  const on = () => !dead && fine.matches && !reduced.matches && root.dataset.motion !== "off";
  const frame = (t) => {
    raf = 0;
    if (!lenis) return;
    lenis.raf(t);
    if (lenis.isScrolling === "smooth" && guard.slow(t)) { dead = true; goNative(lenis); lenis = null; return; }
    still = lenis.isScrolling ? 0 : still + 1;
    if (still <= 3) raf = requestAnimationFrame(frame);
    else guard.reset();
  };
  const stop = () => { cancelAnimationFrame(raf); raf = 0; lenis?.destroy(); lenis = null; };
  const make = () => {
    if (!on()) return stop();
    lenis ??= new Lenis(lenisOptions());
  };
  const wake = () => {
    if (!on()) return stop();
    make();
    still = 0;
    if (!raf) raf = requestAnimationFrame(frame);
  };
  addEventListener("wheel", wake, { passive: true });
  addEventListener("pointermove", make, { passive: true, once: true });
  ("requestIdleCallback" in window ? requestIdleCallback : setTimeout)(make);
  // Pause or the OS asking for less motion: back to native scroll at once.
  window.hcwa?.addEventListener?.("pause", () => { if (!on()) stop(); });
  reduced.addEventListener?.("change", () => { if (!on()) stop(); });
}
