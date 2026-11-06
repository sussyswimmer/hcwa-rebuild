import { store } from "../ui/dom.js";
import { bus } from "../bus.js";

// S1 Nắng vào in CSS (05-DESIGN D5.3, light.css). head-inline.js set sun-pending; this starts the
// sweep once the light layer has decoded (and the display font is in, so nothing swaps under the
// light). Plays once per page per session. Not decoded in 2.5 s, already out of view, failed or
// paused: the layer appears at rest. The world hands off only after this ends (state.sunIn "done").
const DEADLINE = 2500;

export function initSunIn() {
  const root = document.documentElement;
  const finish = () => {
    if (bus.state.sunIn === "done") return;
    root.classList.remove("sun-pending", "sun-in");
    bus.state.sunIn = "done";
    bus.dispatchEvent(new Event("sun-in-end"));
  };
  if (!root.classList.contains("sun-pending")) return finish();
  const lit = document.querySelector(".lit");
  const img = lit?.querySelector(".lit__light img");
  if (!img) return finish();
  store.set("hcwa:sun:" + location.pathname, "1", "sessionStorage");
  const timer = setTimeout(finish, DEADLINE);
  bus.addEventListener("pause", (e) => { if (e.detail.paused) finish(); });
  const font = root.classList.contains("wf") || !document.fonts ? null : document.fonts.load('640 1em "Archivo HCWA"').catch(() => null);
  // The screen's light layer must be decoded too, or it would join the sweep late. Both are lazy
  // images: decode() waits for their fetch as well.
  const screen = lit.querySelector(".lit__screen img")?.decode().catch(() => null);
  Promise.all([img.decode(), font, screen]).then(() => {
    if (bus.state.sunIn === "done") return;
    clearTimeout(timer);
    const r = lit.getBoundingClientRect();
    if (r.bottom <= 0 || r.top >= innerHeight) return finish();
    bus.state.sunIn = "running";
    root.classList.add("sun-in");
    root.classList.remove("sun-pending");
    // The drift is the longest track (80 + 2200 ms).
    img.closest(".lit__light").addEventListener("animationend", (e) => { if (e.animationName === "sun-drift") finish(); });
  }, finish);
}
