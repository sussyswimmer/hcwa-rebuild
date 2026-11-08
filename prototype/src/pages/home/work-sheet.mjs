import { html, raw } from "../../lib/html.mjs";
import { viKeep } from "../../lib/vikeep.mjs";
import { visibleFigures } from "../../lib/content.mjs";
import { icon, pendingChip, programRow } from "../../partials/components.mjs";
import { lightArt, AREA_BLOCK } from "../../partials/blocks.mjs";

// Home H5, the Our work sheet (Desire 2): the first opaque sheet after the corridor. Places were doors;
// here the work is a wall of blocks (focus areas), the plaques on it (figures) and light passing through
// louvers (programs). Owner: page stream (prototype/PLAN.md section 3).

// Gapless 4-column wall, 8 of 8 cells: Education 2 × 2, Protection 2 × 1, Family and Policy 1 × 1
// (tablet 2 + 2 + 1 + 1 = 6 of 6). Each area carries its block from the set drawn in light (lines and lit
// holes, never an ink square), bleeding off the tile's top-right, and the light that block throws on the
// plaster; the words sit in the tile's second row, so they never meet the art.
const SCREEN = { education: { cols: 2, rows: 2 }, protection: { cols: 3, rows: 2 }, family: { cols: 1, rows: 1 }, policy: { cols: 1, rows: 1 } };

const tile = (c, a) => {
  const kind = AREA_BLOCK[a.id];
  return html`<li class="tile tile--${a.id}">
    <span class="tile__light" aria-hidden="true"></span>
    ${lightArt(kind, { ...SCREEN[a.id], cls: "tile__art" })}
    <div class="tile__body">
      <h3 class="tile__name"><a href="${c.url("ourWork")}?area=${a.id}#f-${a.id}">${viKeep(c.tx(a.name))}</a></h3>
      <p class="tile__text"${c.d(a.text)}>${c.tx(a.text)}</p>
    </div>
    ${icon("arrow", "icon tile__go")}
  </li>`;
};

// Organization figures as plaques on the wall. Production renders the row only when at least two are
// verified (06-PAGES); today none are, so it is staging-only and the sheet must read finished without it.
const plaques = (c) => {
  const figs = visibleFigures(c.content.impact.filter((f) => f.scope === "organization"), c.prod);
  if (figs.length < 2) return "";
  return html`<div class="plaques" role="group" aria-label="${c.t("impact")}">
    <ul class="plaques__row">${figs.map((f) => html`<li class="plaque"${c.d(f.label)}>
      <p class="plaque__value num">${f.verified ? c.number(f.value) + (f.suffix ?? "") : f.token}</p>
      <p class="plaque__label">${c.tx(f.label)}</p>
      ${f.verified ? "" : pendingChip(c)}
    </li>`)}</ul>
    <a class="plaques__link text-link" href="${c.url("impact")}"><span>${c.t("seeImpact")}</span>${icon("arrow")}</a>
  </div>`;
};

export function workSheet(c) {
  const { work } = c.content;
  const programs = [...work.programs].sort((a, b) => (a.status === "active" ? 0 : 1) - (b.status === "active" ? 0 : 1)).slice(0, 4);
  const anchor = c.lang === "vi" ? "#du-an" : "#programs";
  return html`
<section class="wall wall--sun wall--edge work" id="work" aria-labelledby="focus-title">
  <span class="sun-layer sun-layer--work" aria-hidden="true"></span>
  <div class="container">
    <h2 class="wall__title" id="focus-title" data-reveal${c.d(work.focusTitle)}>${viKeep(c.tx(work.focusTitle))}</h2>
    <ul class="bento" data-reveal="stagger">${work.focusAreas.map((a) => tile(c, a))}</ul>
    ${plaques(c)}
  </div>
  <section class="programs" aria-labelledby="programs-title">
    <div class="container">
      <div class="programs__head" data-reveal>
        <span class="lam" aria-hidden="true"></span>
        <h2 class="wall__title" id="programs-title">${viKeep(c.t("programs"))}</h2>
      </div>
      <ul class="program-list program-list--home" data-reveal="stagger">
        ${programs.map((p) => programRow(c, p))}
        <li class="program-more"><a class="program-more__link" href="${c.url("ourWork")}${raw(anchor)}"><span>${c.t("allPrograms")}</span>${icon("arrow")}</a></li>
      </ul>
    </div>
  </section>
</section>`;
}
