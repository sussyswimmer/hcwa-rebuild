import { html } from "../../lib/html.mjs";
import { textLink, centerCard, sectionHead } from "../../partials/components.mjs";

// Home H4 corridor (centers as doors; S2 part 2). Phase 1 placeholder until rebuilt. Owner: 3D story stream (prototype/PLAN.md section 3).
export function corridor(c) {
  const { site, work } = c.content;
  return html`
<section class="section section--sunken" aria-labelledby="centers-title" data-reveal>
  <div class="container">
    ${sectionHead(c, { id: "centers-title", title: c.t("centers"), intro: c.tx(site.centersIntro), draftIntro: true, link: textLink(c.url("ourWork") + (c.lang === "vi" ? "#co-so" : "#centers"), c.t("seeAll")) })}
    <div class="center-grid">${work.centers.map((x) => centerCard(c, x))}</div>
  </div>
</section>`;
}
