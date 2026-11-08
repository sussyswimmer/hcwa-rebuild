import { $, $$ } from "./dom.js";

// Components page demos (PLAN P8): replay the sun-in on a lit still, run the motion-token tracks. With
// reduced motion or motion paused the CSS has no transitions, so a demo lands on its end state at once.
const motionOn = () => document.documentElement.dataset.motion !== "off" && !matchMedia("(prefers-reduced-motion: reduce)").matches;

export function initKit() {
  $$("[data-kit-play]").forEach((btn) => btn.addEventListener("click", () => {
    btn.closest("[data-kit-demo]").classList.toggle("is-on");
  }));
  const lit = $("[data-kit-light]");
  $("[data-kit-sun]")?.addEventListener("click", () => {
    if (!lit) return;
    lit.classList.remove("is-sun");
    if (!motionOn()) return;
    void lit.offsetWidth; // restart the keyframes
    lit.classList.add("is-sun");
  });
}
