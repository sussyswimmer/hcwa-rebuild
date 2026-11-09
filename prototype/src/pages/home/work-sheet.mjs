import { html, raw } from "../../lib/html.mjs";
import { viKeep } from "../../lib/vikeep.mjs";
import { visibleFigures } from "../../lib/content.mjs";
import { icon, pendingChip, programRow } from "../../partials/components.mjs";
import { gachArt, gachSpriteOnce, AREA_BLOCK, AREA_PATCH } from "../../partials/blocks.mjs";

// Home H5, the Our work sheet (Desire 2): the first sheet after the corridor. Places were doors; here the
// work is the floor they open onto (focus areas as gạch bông), the plaques (figures) and light passing
// through louvers (programs). Owner: page stream (prototype/PLAN.md section 3).

// H5a, "Lĩnh vực hoạt động" as a floor of gạch bông (05-DESIGN 0.6.3): each area is a patch of 20 cm
// cement tiles in its own pigment, the corridor's floor seen from above. The hierarchy is the number of
// tiles (4 / 2 / 1 / 1), never their size. The words stand on the plaster beside or under the patch, never
// on a tile. A band of sunlight through the house's Hoa thị screen crosses each patch as it crosses the
// screen: the same tiles drawn in their sunlit pigments, so the light lands only on tiles (pages.css).
// The whole area is the link; every element but the words is aria-hidden decoration.
const gach = (c, a) => {
  const kind = AREA_BLOCK[a.id], patch = AREA_PATCH[a.id];
  return html`<li class="gach gach--${a.id}">
    <div class="gach__floor" aria-hidden="true">${gachArt(kind, patch, "gach__art")}<span class="gach__light">${gachArt(kind, patch, "gach__lit")}</span></div>
    <div class="gach__body">
      <h3 class="gach__name"><a href="${c.url("ourWork")}?area=${a.id}#f-${a.id}"><span>${viKeep(c.tx(a.name))}</span>&#8288;${icon("arrow", "icon gach__go")}</a></h3>
      <p class="gach__text"${c.d(a.text)}>${c.tx(a.text)}</p>
    </div>
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
  <div class="container">
    <div class="floor">
      <h2 class="wall__title floor__title" id="focus-title" data-reveal${c.d(work.focusTitle)}>${viKeep(c.tx(work.focusTitle))}</h2>
      <ul class="floor__areas" data-reveal="stagger">${work.focusAreas.map((a) => gach(c, a))}</ul>
    </div>
    ${gachSpriteOnce(c)}
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
