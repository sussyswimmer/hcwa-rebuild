import { html } from "../../lib/html.mjs";
import { button, icon } from "../../partials/components.mjs";

// Home H10 contact band. Phase 1 placeholder until rebuilt. Owner: page stream (prototype/PLAN.md section 3).
export function contactBand(c) {
  const { site } = c.content;
  return html`
<section class="cta-band" aria-labelledby="contact-title">
  <div class="container cta-band__inner">
    <h2 id="contact-title">${c.tx(site.contactBand.title)}</h2>
    <div class="cta-band__actions">${button({ href: c.url("contact"), label: c.t("contactUs"), kind: "on-dusk" })}</div>
    <ul class="cta-band__contact" data-draft>
      <li>${icon("phone")}<a href="tel:${site.phone.tel}">${site.phone.display}</a></li>
      <li>${icon("mail")}<a href="mailto:${site.email.general}">${site.email.general}</a></li>
    </ul>
  </div>
</section>`;
}
