import { html } from "../../lib/html.mjs";
import { viKeep } from "../../lib/vikeep.mjs";
import { button, duskStill } from "../../partials/components.mjs";

// Home H10 contact band: the day ends. A static dusk sky (the page's one gradient besides the light)
// runs from the help section's sun-100 into blue-900, the room's screen at 18:00 rising out of it; the
// phone and email sit on the night as big links. Owner: page stream (prototype/PLAN.md section 3).
export function contactBand(c) {
  const { site } = c.content;
  return html`
<section class="dusk" id="contact" aria-labelledby="contact-title">
  ${duskStill()}
  <div class="container dusk__inner">
    <h2 class="dusk__title" id="contact-title">${viKeep(c.tx(site.contactBand.title))}</h2>
    <dl class="dusk__lines" data-draft>
      <div><dt>${c.t("phone")}</dt><dd><a href="tel:${site.phone.tel}">${site.phone.display}</a></dd></div>
      <div><dt>${c.t("email")}</dt><dd><a href="mailto:${site.email.general}">${site.email.general}</a></dd></div>
    </dl>
    ${button({ href: c.url("contact"), label: c.t("contactUs"), kind: "on-dusk", cls: "dusk__btn" })}
  </div>
</section>`;
}
