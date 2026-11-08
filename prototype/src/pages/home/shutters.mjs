import { html } from "../../lib/html.mjs";
import { still } from "../../lib/posters.mjs";

// Home H7, the shutters (S3; PLAN H7, 05-DESIGN D5.3, D6.10). Owner: 3D story stream. Decorative: the
// heading it opens onto is the help section's, so the only thing to read here is the skip link.
// One markup, two layouts:
// - Stacked (the default: no JS, Tier 0, reduced motion, a screen under 640 px tall): the shutters open at
//   17:00, one still, its light dissolving into the help section's sun-100.
// - Stage (html.stages, 640 px tall and up): the track is S3's scroll length and the frame is sticky. The
//   live world draws the room behind it (the slats turn, the leaves swing in, the flood) and marks the
//   section is-live; until it does, or if it never can, the three stills of the same beats take turns
//   (motion/shutters.js sets --p). The sill at the track's foot is the light the help section rises on.
// The first two beats wait in a <template>: only the stage uses them, and a browser without JS loads every
// image eagerly, display:none or not.
export function shutters(c) {
  const frame = (name, beat) => html`<div class="shutters__view shutters__view--${beat}">${still(name, { sizes: "100vw" })}</div>`;
  return html`
<section class="shutters" id="shutters" data-world="shutters" data-stage>
  <a class="shutters__skip" href="#help">${c.t("skipShutters")}</a>
  <div class="shutters__track" data-stage-track>
    <div class="shutters__frame" aria-hidden="true">
      <template class="shutters__beats">${frame("shutters-closed", "closed")}${frame("shutters-louvers", "louvers")}</template>
      ${frame("shutters-open", "open")}
      <div class="shutters__flood"></div>
    </div>
    <div class="shutters__sill" aria-hidden="true"></div>
  </div>
</section>`;
}
