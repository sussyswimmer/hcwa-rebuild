import "./styles/tokens.css";
import "./styles/base.css";
import "./styles/layout.css";
import "./styles/components.css";
import "./styles/light.css";
import "./styles/motion.css";
import "./styles/world.css";
import { bus } from "./bus.js";
import { store } from "./ui/dom.js";
import { initHeader } from "./ui/header.js";
import { initMenuSheet } from "./ui/menu-sheet.js";
import { initFooter } from "./ui/footer.js";
import { initEnBar } from "./ui/en-bar.js";
import { initFilters } from "./ui/filters.js";
import { initLightbox } from "./ui/lightbox.js";
import { initCopy } from "./ui/copy.js";
import { initForms } from "./ui/forms.js";
import { initPauseToggle } from "./ui/pause-toggle.js";
import { initStills } from "./ui/stills.js";
import { initDraftToggle } from "./ui/draft-toggle.js";
import { initSunIn } from "./motion/sun-in.js";
import { initReveals } from "./motion/reveals.js";

// Loading order (05-DESIGN D6.9): head-inline.js has set data-tier / data-motion / sun-pending;
// this module (deferred) boots the sun-in and the UI, then the motion layer, then the world.
const root = document.documentElement;
const tier = Number(root.dataset.tier ?? 0);
const reduced = matchMedia("(prefers-reduced-motion: reduce)");
const motionOn = () => root.dataset.motion !== "off" && !reduced.matches;

// What the first screen needs runs now; the rest waits for the first frame, so it never sits between
// the stylesheet and first paint.
initStills();
initSunIn();
initPauseToggle();
initHeader();
initEnBar(); // before paint: the pointer bar changes the hero's height
const initRest = () => {
  initMenuSheet();
  initFooter();
  initFilters();
  initLightbox();
  initCopy();
  initForms();
  initReveals();
  initDraftToggle();
};
requestAnimationFrame(() => setTimeout(initRest));

// Fonts are in the HTTP cache from here on: later pages skip waiting for them before the sun-in.
document.fonts?.ready.then(() => { root.classList.add("wf"); store.set("hcwa:wf", "1", "sessionStorage"); });

// GSAP, ScrollTrigger and Lenis: a separate chunk, only when something may move.
let motion = null;
const startMotion = () => {
  motion ??= import("./motion/index.js").then((m) => m.startMotion({ tier }));
};

// The world (three.js) after load + idle, on Tier 1-2 with motion on, never otherwise (PLAN section 1).
// world/index.js exports startWorld({ canvas, stage, tier, bus }) → { setPaused, dispose }; it probes the
// GPU before three.js loads, adds html.world-live once its first frame matches the poster, and leaves
// the poster alone if it fails.
let world = null;
let worldRequested = false;
const startWorld = () => {
  const canvas = document.querySelector(".world-canvas");
  if (worldRequested || !canvas || tier < 1 || !motionOn()) return;
  worldRequested = true;
  const go = () => {
    // Paused (or the OS asked for less motion) while waiting for load + idle: nothing loads now;
    // resuming calls startWorld again.
    if (!motionOn()) return void (worldRequested = false);
    return import("./world/index.js")
      .then(({ startWorld: start }) => start({ canvas, stage: document.querySelector(".hero__stage"), tier, bus }))
      .then((w) => {
        world = w;
        if (bus.state.paused) world?.setPaused(true);
      })
      .catch((err) => console.warn("world unavailable, keeping the stills", err));
  };
  const idle = () => ("requestIdleCallback" in window ? requestIdleCallback(go, { timeout: 2000 }) : setTimeout(go, 200));
  if (document.readyState === "complete") idle();
  else addEventListener("load", idle, { once: true });
};

if (motionOn()) {
  startMotion();
  startWorld();
}
bus.addEventListener("pause", (e) => {
  world?.setPaused(e.detail.paused);
  // Turning motion back on during this visit brings the layers in that were never loaded.
  if (!e.detail.paused && motionOn()) { startMotion(); startWorld(); }
});
// The OS asking for less motion mid-visit (05-DESIGN D5.6): everything that moves stops and the stills
// come back, as on a reduced-motion load. Not stored as the visitor's own choice (hcwa:motion).
reduced.addEventListener?.("change", (e) => {
  if (!e.matches) return;
  root.dataset.motion = "off";
  root.setAttribute("data-reduced", "");
  bus.state.paused = true;
  bus.dispatchEvent(new CustomEvent("pause", { detail: { paused: true, system: true } }));
  world?.dispose();
  world = null;
});
