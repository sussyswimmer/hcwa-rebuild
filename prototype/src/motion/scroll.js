import { gsap } from "gsap";
import { ScrollTrigger } from "gsap/ScrollTrigger";
import { CustomEase } from "gsap/CustomEase";
import Lenis from "lenis";
import "lenis/dist/lenis.css";
import { ease } from "./tokens.js";
import { bus } from "../bus.js";
import { hold, release } from "./ticker.js";

gsap.registerPlugin(ScrollTrigger, CustomEase);
for (const [name, curve] of Object.entries(ease)) CustomEase.create(name, curve);

// 05-DESIGN D5.2. Native speed: Lenis smooths the wheel only (touch stays native, no snap, no
// normalizeScroll), driven from gsap.ticker. ScrollTrigger only reads progress; stages are CSS sticky.
export function startScroll() {
  const root = document.documentElement;
  ScrollTrigger.config({ ignoreMobileResize: true });
  gsap.ticker.lagSmoothing(0);

  // Progress of each world section for the camera. It keeps running while paused: the camera still
  // follows the visitor's scroll (D6.10), only idle motion stops. One reused event, no allocations.
  for (const el of document.querySelectorAll("[data-world]")) {
    const detail = { world: el.dataset.world, progress: 0 };
    const event = new CustomEvent("progress", { detail });
    const emit = (st) => {
      if (st.progress === detail.progress && bus.state.progress[detail.world] !== undefined) return;
      detail.progress = st.progress;
      bus.state.progress[detail.world] = st.progress;
      bus.dispatchEvent(event);
    };
    ScrollTrigger.create({ trigger: el, start: "top top", end: "bottom top", onUpdate: emit, onRefresh: emit });
  }

  // Wheel smoothing exists only while motion is on and the OS allows it; matchMedia cleans it up
  // when either changes. Dialogs and [data-lenis-prevent] scroll natively.
  let mm = null;
  const smooth = () => {
    mm?.revert();
    mm = gsap.matchMedia();
    if (root.dataset.motion === "off") return;
    mm.add("(prefers-reduced-motion: no-preference)", () => {
      const lenis = new Lenis({
        lerp: 0.12, smoothWheel: true, syncTouch: false, autoRaf: false,
        prevent: (node) => Boolean(node.closest?.("dialog, [data-lenis-prevent]")),
      });
      lenis.on("scroll", ScrollTrigger.update);
      // Lenis only needs frames while it is smoothing a wheel scroll: the callback joins the ticker on
      // a wheel event and leaves once Lenis has been at rest for a few frames, so an idle page draws
      // nothing (a rAF loop without a stop condition is the fixing-motion-performance never-pattern).
      let on = false, still = 0;
      const raf = (time) => {
        lenis.raf(time * 1000);
        still = lenis.isScrolling ? 0 : still + 1;
        if (still > 3) { gsap.ticker.remove(raf); on = false; release(); }
      };
      const wake = () => { still = 0; if (!on) { on = true; hold(); gsap.ticker.add(raf); } };
      addEventListener("wheel", wake, { passive: true });
      return () => { removeEventListener("wheel", wake); gsap.ticker.remove(raf); if (on) release(); on = false; lenis.destroy(); };
    });
  };
  smooth();
  bus.addEventListener("pause", smooth);
  // Only the live world reads progress. With no world on this page (Tier 0, the GPU probe said no, the
  // world stopped) ScrollTrigger is switched off, and with it the rAF loop it keeps alive for Firefox.
  const off = () => ScrollTrigger.disable(false);
  if (Number(root.dataset.tier) < 1 || bus.state.world === "off") off();
  else bus.addEventListener("world-off", off, { once: true });
}
