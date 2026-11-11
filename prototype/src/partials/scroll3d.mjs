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
// (--s3d-end, measured by motion/scroll3d.js). The tracks every stage reads:
//   --s3d-p     linear 0 → 1
//   --s3d-cam   the camera, --ease-in-out, done at 0.86 (the last 14% holds still before reading starts)
//   --s3d-sun   light arriving, --ease-sun, done at 0.86
//   --s3d-open  the late light flooding in, --ease-sun over 0.50 → 0.86
// The easings and ranges live here once; openings.css and motion/scroll3d.js both read TRACKS.
export const TRACKS = {
  cam: { from: 0, to: 0.86, ease: [0.77, 0, 0.175, 1] },
  sun: { from: 0, to: 0.86, ease: [0.16, 1, 0.3, 1] },
  open: { from: 0.5, to: 0.86, ease: [0.16, 1, 0.3, 1] },
};

// A stage wrapper. name: "contact" | "news"; vars: CSS custom properties taken from the still's own data.
export const stage = (name, body, vars = {}) => {
  const style = Object.entries(vars).map(([k, v]) => `--${k}:${v}`).join(";");
  return html`<div class="s3d s3d--${name}" data-scroll3d="${name}"${style ? raw(` style="${style}"`) : ""}>${body}</div>`;
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
    "s3d-ox": `${(((1 + subject) / 2) * 100).toFixed(1)}%`,
  };
};

// "Skip the opening" link in the first screen (PLAN-M4 5: budget). A plain text link to the first
// thing a visitor came for (the form, the first news card).
export const skipOpening = (href, label) =>
  html`<a class="s3d-skip text-link" href="${href}"><span>${label}</span>${icon("arrow")}</a>`;
