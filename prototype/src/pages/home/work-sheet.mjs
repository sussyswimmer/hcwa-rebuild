import { html } from "../../lib/html.mjs";
import { textLink, figureStrip, programRow, sectionHead } from "../../partials/components.mjs";

// Home H5 Our work sheet (focus areas, impact plaques, programs). Phase 1 placeholder until rebuilt. Owner: page stream (prototype/PLAN.md section 3).
export function workSheet(c) {
  const { work } = c.content;
  const programs = [...work.programs].sort((a, b) => (a.status === "active" ? -1 : 1) - (b.status === "active" ? -1 : 1)).slice(0, 4);
  return html`
<section class="section section--sun" data-reveal aria-label="${c.t("ourWork")}">
  <div class="container">
    <ul class="focus-list">
        ${work.focusAreas.map(
          (a) => html`<li class="focus-item">
            <span class="ph ph--icon" role="img" aria-label="${a.icon} · ${c.tx(a.name)}"><span class="ph__code">${a.icon}</span></span>
            <div>
              <h3><a href="${c.url("ourWork")}?area=${a.id}">${c.tx(a.name)}</a></h3>
              <p${c.d(a.text)}>${c.tx(a.text)}</p>
            </div>
          </li>`,
        )}
      </ul>
  </div>
</section>

${(() => {
  // Impact leaves the hero (05-DESIGN D11.2); it joins the Our work sheet in the home rebuild.
  const strip = figureStrip(c, "organization", { min: 2, cls: "figures--strip" });
  return strip ? html`<section class="section section--tight" aria-label="${c.t("impact")}" data-reveal><div class="container impact-strip">${strip}${textLink(c.url("impact"), c.t("seeImpact"))}</div></section>` : "";
})()}

<section class="section" aria-labelledby="programs-title" data-reveal>
  <div class="container">
    ${sectionHead(c, { id: "programs-title", title: c.t("programs"), link: textLink(c.url("ourWork") + (c.lang === "vi" ? "#du-an" : "#programs"), c.t("allPrograms")) })}
    <ul class="program-list">${programs.map((p) => programRow(c, p))}</ul>
  </div>
</section>`;
}
