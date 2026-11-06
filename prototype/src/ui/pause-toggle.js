import { $$, store } from "./dom.js";
import { bus } from "../bus.js";

// Tạm dừng chuyển động / Pause motion (WCAG 2.2.2, 05-DESIGN D5.6). Stops what moves by itself on this
// page (bus "pause": sun-in, Lenis, motes, cloud, pointer sun); stored so the next page loads as
// reduced motion (head-inline.js reads hcwa:motion before first paint, and three.js never loads).
const KEY = "hcwa:motion";

export function initPauseToggle() {
  const root = document.documentElement;
  const buttons = $$("[data-motion-toggle]");
  const paused = () => root.dataset.motion === "off";
  const sync = () => buttons.forEach((b) => b.setAttribute("aria-pressed", String(paused())));
  sync();
  bus.state.paused = paused();
  buttons.forEach((b) => b.addEventListener("click", () => {
    const next = !paused();
    root.dataset.motion = next ? "off" : "on";
    if (next) store.set(KEY, "off");
    else store.remove(KEY);
    bus.state.paused = next;
    sync();
    bus.dispatchEvent(new CustomEvent("pause", { detail: { paused: next } }));
  }));
}
