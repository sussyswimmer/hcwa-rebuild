import { html } from "../../lib/html.mjs";
import { textLink, sectionHead } from "../../partials/components.mjs";

// Home H9 partners. Phase 1 placeholder until rebuilt. Owner: page stream (prototype/PLAN.md section 3).
export function partners(c) {
  const { site } = c.content;
  return html`
<section class="section" aria-labelledby="partners-title" data-reveal>
  <div class="container">
    ${sectionHead(c, { id: "partners-title", title: c.tx(site.partnersTitle) })}
    <ul class="logo-wall" aria-label="${c.t("partnerLogo")}">
      ${Array.from({ length: 6 }, () => html`<li><span class="ph ph--logo" role="img" aria-label="C5 · ${c.t("partnerLogo")}"><span class="ph__code">C5</span></span></li>`)}
    </ul>
    <p class="logo-wall__links">${textLink(c.url("partners"), c.t("partners"))} ${textLink(c.url("partner"), c.t("becomePartner"))}</p>
  </div>
</section>`;
}
