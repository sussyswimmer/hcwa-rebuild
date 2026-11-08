import { html } from "../../lib/html.mjs";
import { viKeep } from "../../lib/vikeep.mjs";
import { icon } from "../../partials/components.mjs";

// Home H9 partners. Only approved one-tone logos, and only once at least four exist (05-DESIGN D7):
// one logo in a row reads as filler. Until then production has no section at all and staging shows one
// dashed strip where it will go (data-partners-pending, a production blocker in render.mjs). No marquee.
// Owner: page stream (prototype/PLAN.md section 3).
export const MIN_LOGOS = 4;
export const approvedLogos = (c) => (c.content.site.partnerLogos ?? []).filter((l) => l.approved === true);

export function partners(c) {
  const logos = approvedLogos(c);
  const ready = logos.length >= MIN_LOGOS;
  if (c.prod && !ready) return "";
  const links = html`<p class="partners__links">
    <a class="text-link" href="${c.url("partners")}"><span>${c.t("partners")}</span>${icon("arrow")}</a>
    <a class="text-link" href="${c.url("partner")}"><span>${c.t("becomePartner")}</span>${icon("arrow")}</a>
  </p>`;
  return html`
<section class="wall wall--light partners" id="partners" aria-labelledby="partners-title" data-partners="${logos.length}"${ready ? "" : html` data-partners-pending`}>
  <div class="container partners__inner" data-reveal>
    <h2 class="partners__title" id="partners-title">${viKeep(c.tx(c.content.site.partnersTitle))}</h2>
    ${ready
      ? html`<ul class="partner-wall">${logos.map((l) => html`<li><img src="${l.src}" alt="${l.name}" loading="lazy" decoding="async"></li>`)}</ul>`
      : html`<p class="partners__pending" data-draft>${c.t("partnersPending")}</p>`}
    ${links}
  </div>
</section>`;
}
