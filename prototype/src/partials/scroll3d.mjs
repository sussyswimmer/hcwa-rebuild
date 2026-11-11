import { html, raw } from "../lib/html.mjs";
import { HEADER_TEXT } from "../lib/posters.mjs";
import { cropsOf } from "../world/still-shots.js";
import { icon } from "./components.mjs";

// The two M4 openings (PLAN-M4 section 5): Contact's "through the screen to the street" and News's
// "notices on the wall". A stage is plain HTML that reads as a finished page at rest. It only becomes
// a 3D stage when head-inline.js has set html.stages with motion on (before first paint, so nothing
// jumps): then styles/openings.css pins it and moves its layers from a few custom properties that
// follow the native scroll. CSS scroll-driven animations write them where supported; motion/scroll3d.js
// writes the same values everywhere else. No Lenis, no GSAP, no snap. No JS, reduced motion, pause and
// ?tier=0 never get html.stages with motion on, so they see the rest frame.
//
// Progress runs from the top of the page (scroll 0) to the scroll at which the stage lets go
// (--s3d-end, measured by motion/scroll3d.js from the pinned layer, data-s3d-pin). The tracks every stage reads:
//   --s3d-p     linear 0 → 1
//   --s3d-cam   the camera, a soft in-out that is already moving at the first pixel of scroll and never
//               runs faster than 1.5x its mean rate (so no stretch of scroll does most of the move), done at
//               0.86 (the last 14% holds still before reading starts)
//   --s3d-sun   light arriving, a soft ease-out (peak 1.8x its mean rate), done at 0.86
//   --s3d-open  the street's light opening out, in-out over 0.55 → 0.92 (it grows with the camera's last
//               move and is still widening as the page rises into it)
// The easings and ranges live here once; openings.css and motion/scroll3d.js both read TRACKS.
export const TRACKS = {
  cam: { from: 0, to: 0.86, ease: [0.3, 0.1, 0.6, 1] },
  sun: { from: 0, to: 0.86, ease: [0.3, 0.45, 0.45, 1] },
  open: { from: 0.55, to: 0.92, ease: [0.37, 0, 0.63, 1] },
};

// A stage wrapper. name: "contact" | "news"; vars: CSS custom properties taken from the still's own data.
// min: the shortest range, as a fraction of the viewport height (a stage that is not pinned lets go
// almost at once; its camera still takes this much scroll to come to rest).
// pin: a selector for the layer the stage pins (position: sticky). The stage lets go when the bottom of
// that layer meets the bottom of the wrapper, which is earlier than "wrapper bottom = screen bottom"
// when the pinned layer is shorter than the screen (Contact's stage is about two thirds of it).
export const stage = (name, body, vars = {}, { min = 0, pin = "" } = {}) => {
  const style = Object.entries(vars).map(([k, v]) => `--${k}:${v}`).join(";");
  return html`<div class="s3d s3d--${name}" data-scroll3d="${name}"${min ? raw(` data-s3d-min="${min}"`) : ""}${pin ? html` data-s3d-pin="${pin}"` : ""}${style ? raw(` style="${style}"`) : ""}>${body}</div>`;
};

// Where a lit header's subject sits, from the still's own crop data (src/world/still-shots.js), never
// from pixels: the desk crop's aspect, and the text column (HEADER_TEXT) the subject keeps clear of.
// openings.css places the full-height still so the subject starts just right of the text column and
// dollies toward the subject's middle.
export const litStageVars = (still) => {
  const desk = cropsOf(still).find((c) => c.name === "desk") ?? cropsOf(still)[0];
  const subject = HEADER_TEXT.x + HEADER_TEXT.w + 0.02;
  return {
    "s3d-aspect": desk.aspect.toFixed(4),
    "s3d-subject": subject.toFixed(3),
    // The camera aims at the middle of the subject's side of the frame.
    "s3d-ox": ((1 + subject) / 2).toFixed(3),
  };
};

// "Skip the opening" link in the first screen (PLAN-M4 5: budget). A plain text link to the first
// thing a visitor came for (the form, the first news card).
export const skipOpening = (href, label) =>
  html`<a class="s3d-skip text-link" href="${href}"><span>${label}</span>${icon("arrow")}</a>`;
