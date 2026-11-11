import "./styles/tokens.css";
import "./styles/base.css";
import "./styles/layout.css";
import "./styles/components.css";
import "./styles/light.css";
import "./styles/motion.css";
import "./styles/world.css";
import "./styles/stages.css";
import "./styles/pages.css";
import "./styles/openings.css";
import { bus } from "./bus.js";
import { store } from "./ui/dom.js";
import { initHeader } from "./ui/header.js";
import { initEnBar } from "./ui/en-bar.js";
import { initPauseToggle } from "./ui/pause-toggle.js";
import { initStills } from "./ui/stills.js";
import { initSunIn } from "./motion/sun-in.js";
import { initUiRest } from "./ui/index.js";
import { initHeroPlate } from "./hero-plate.js";
import { initScroll3d } from "./motion/scroll3d.js";

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
initHeroPlate();
initScroll3d(); // the Contact and News openings (PLAN-M4 5): native scroll only, no GSAP or Lenis
requestAnimationFrame(() => setTimeout(initUiRest));

// Fonts are in the HTTP cache from here on: later pages skip waiting for them before the sun-in.
document.fonts?.ready.then(() => { root.classList.add("wf"); store.set("hcwa:wf", "1", "sessionStorage"); });

// GSAP, ScrollTrigger and Lenis: a separate chunk, only on a page whose world or stages read the scroll
// (the home). Inner pages move with CSS and the UI layer alone, and never pay for it.
let motion = null;
const scrolled = Boolean(document.querySelector("[data-world], [data-stage]"));
const startMotion = () => {
  if (scrolled) motion ??= import("./motion/index.js").then((m) => m.startMotion({ tier }));
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
// The stage layout (head-inline.js) is for a page whose world may fly. If the world gives up (no WebGL2,
// a software GPU, the live gates) before the visitor has reached the passage, the page goes back to the
// stacked band (nothing above it moves); once they are in it or past it the stage stays, with its stills.
// The passage changes height below the fold as it unstages, and Chrome's layout-shift tracker scores that
// move as if the statement had been on screen (CLS 0.42 on a phone). Hidden for the frame it moves in, it is
// not a shift: it simply appears in its new place.
const unstage = () => {
  const passage = document.querySelector(".passage[data-stage]");
  if (passage && passage.getBoundingClientRect().top <= 0) return;
  root.classList.add("unstaging");
  root.classList.remove("stages");
  requestAnimationFrame(() => requestAnimationFrame(() => root.classList.remove("unstaging")));
};
// The stage's fallback stills (the bays' views, the flight's end) are display:none while the world may still
// draw them, so they never download with the page (stages.css). They come in when the world gives up, or when
// the visitor nears the corridor before the world is live.
const worldOff = () => { root.classList.add("world-off"); unstage(); };
if (bus.state.world === "off") worldOff();
else bus.addEventListener("world-off", worldOff, { once: true });
const corridor = root.classList.contains("stages") && document.querySelector("#centers");
if (corridor && "IntersectionObserver" in window) {
  const near = new IntersectionObserver((entries) => {
    if (!entries.some((e) => e.isIntersecting)) return;
    if (!root.classList.contains("world-live")) root.classList.add("stills-near");
    near.disconnect();
  }, { rootMargin: "100% 0px" });
  near.observe(corridor);
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
  root.classList.remove("stages");
  bus.state.paused = true;
  bus.dispatchEvent(new CustomEvent("pause", { detail: { paused: true, system: true } }));
  world?.dispose();
  world = null;
});
