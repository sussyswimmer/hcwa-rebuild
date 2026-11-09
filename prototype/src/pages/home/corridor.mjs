import { html } from "../../lib/html.mjs";
import { viKeep } from "../../lib/vikeep.mjs";
import { still } from "../../lib/posters.mjs";
import { textLink } from "../../partials/components.mjs";
import { screenArt } from "../../partials/blocks.mjs";
import { EMBLEMS } from "../../world/objects/door.js";

// Home H4, the corridor (S2 part 2; PLAN H4, 05-DESIGN D5.3, D6.5, D6.10). Owner: 3D story stream.
// One markup, two layouts:
// - Stacked (the default: no JS, Tier 0, reduced motion, or a card that does not fit its panel): the walk
//   as three frames, each bay's view of its door (the live camera's own pose, rendered) with its card set
//   into it.
// - Stage (html.stages + is-stage, added by motion/stages.js once every card fits): the track is the walk's
//   scroll length, the frame is sticky, the live corridor (or, without it, the bay's view) fills it, and the
//   panel shows the stage header, then one card per beat, with an index of the doors. The link to every center
//   comes in under the last card and goes with it (critique r1: never a link alone on plaster).
// Each [data-beat] card is one door of the live corridor (unit, open or closed, its transom's emblem), in
// DOM order: adding or removing a center here adds or removes its door. Little Rose: closed door, no
// location, never "visit", its privacy note in words, its light plain daylight (--spill none).
const spillOf = (center) => (center.id === "little-rose" ? "var(--color-sun-100)" : `var(--color-unit-${center.accent})`);

const bay = (c, center, i) => {
  const lr = center.id === "little-rose";
  const path = c.tx(center.path);
  const links = lr
    ? [textLink(path, c.t("aboutShelter")), textLink(c.url("bakery"), c.t("bakery"))]
    : [textLink(path, c.t("visitCenter"))];
  return html`<li class="bay" id="center-${center.id}" data-beat data-unit="${center.id}" data-door="${lr ? "closed" : "open"}" data-emblem="${EMBLEMS[center.id] ?? "hoaThi"}" style="--unit:var(--color-unit-${center.accent});--spill:${spillOf(center)};--i:${i}">
  <div class="bay__view" aria-hidden="true">${still("bay-" + center.id, { sizes: "(min-width: 1024px) 76vw, 100vw" })}</div>
  <div class="bay__text">
    <h3 class="bay__name">${viKeep(c.tx(center.name))}</h3>
    ${c.prod ? "" : html`<p class="bay__since" data-draft>${c.t("since")} ${c.tx(center.sinceToken)}</p>`}
    <p class="bay__serves"${c.d(center.serves)}>${c.tx(center.serves)}</p>
    ${!lr && center.location ? html`<p class="bay__where"${c.d(center.location)}>${c.tx(center.location)}</p>` : ""}
    ${lr ? html`<p class="bay__note"${c.d(c.content.work.littleRose.privacyNote)}>${c.tx(c.content.work.littleRose.privacyNote)}</p>` : ""}
    <p class="bay__links">${links}</p>
  </div>
</li>`;
};

// The index of the doors: each one's transom (three blocks of its emblem), lit while its card is read.
// Links to the bays, so it works as a table of contents without JS too; motion/stages.js turns a click into
// a scroll to that door's beat.
const rail = (c, centers) => html`<nav class="bays__rail" aria-label="${c.t("centersRail")}">
  <ol>${centers.map((x, i) => html`<li><a href="#center-${x.id}" data-to="${i}" style="--unit:var(--color-unit-${x.accent})">${screenArt(EMBLEMS[x.id] ?? "hoaThi", { cols: 3, rows: 1, cls: "rail__transom" })}<span>${c.tx(x.shortName)}</span></a></li>`)}</ol>
</nav>`;

// Traces of children's lives (05-DESIGN D6.8): only the chalk lò cò on the Tre Xanh bay's plain band, drawn by the
// live corridor when content/draft/world.json switches traces on. Staging shows it for HCWA's safeguarding lead to
// judge in context; PROD renders it only once HCWA has approved (and the PROD guard fails the build before then).
// Never in a still, never in or near Little Rose's bay (sets/corridor.js).
const tracesOn = (c) => c.content.world?.traces?.enabled === true && (!c.prod || c.content.world.traces.approved === true);

export function corridor(c) {
  const { site, work } = c.content;
  return html`
<section class="bays-section" id="centers" data-world="corridor" data-stage${tracesOn(c) ? " data-traces" : ""} style="--n:${work.centers.length}" aria-labelledby="centers-title">
  <a class="bays__skip" href="#work">${c.t("skipCenters")}</a>
  <div class="bays__track" data-stage-track>
    <div class="bays__frame">
      <div class="bays__glass" aria-hidden="true"></div>
      <div class="container bays__panel">
        <div class="bays__head">
          <h2 id="centers-title">${viKeep(c.t("centers"))}</h2>
          <p class="bays__intro"${c.d(site.centersIntro)}>${c.tx(site.centersIntro)}</p>
        </div>
        ${rail(c, work.centers)}
        <ol class="bays">${work.centers.map((x, i) => bay(c, x, i))}</ol>
        <p class="bays__all">${textLink(c.url("ourWork") + (c.lang === "vi" ? "#co-so" : "#centers"), c.t("allWork"))}</p>
      </div>
    </div>
  </div>
</section>`;
}
