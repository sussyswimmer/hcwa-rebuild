import { html } from "../lib/html.mjs";
import { viKeep } from "../lib/vikeep.mjs";
import { textLink, button, quatrefoil, reachLines } from "../partials/components.mjs";
import { sunHead } from "../partials/lit-header.mjs";
import { blockIcon } from "../partials/blocks.mjs";

// Little Rose Warm Shelter (PLAN-M4 4.11; 05-DESIGN D6.5, 0.6.4, section 8; CLAUDE.md safeguarding).
// Safeguarding first: words and an abstract illustration only. The illustration is one Hoa thị block of
// the house letting plain, untinted daylight onto a lime wall: no door (open or shut), no building, no
// street or window, no figure. No still of any kind, no location of any kind, no contact person and no
// phone of its own (everything goes through the office), no gallery, no figures, no news, no share links,
// and never the word "visit". The header is the plain sun band with the unit's pink as paint, not light.
// It ends without a dusk band (the band carries the room's still): the office lines close the page on the
// day's light and the footer's evening takes it from there.

const CENTERS = { vi: "co-so", en: "centers" };

// The block and the light it lets in, drawn from the house's own SDF numbers (blocks.mjs): the block's
// line and lit holes, and below it the same holes cast long on the wall in plain daylight.
const art = () => html`<span class="lr-art">${blockIcon("hoaThi", "lr-art__block")}<span class="lr-art__light"></span></span>`;

export function littleRose(c) {
  const { work } = c.content;
  const x = work.centers.find((u) => u.id === "little-rose");
  const L = work.littleRose.page;
  const note = work.littleRose.privacyNote;
  const body = html`
${sunHead(c, {
  trail: [[c.t("ourWork"), c.url("ourWork")], [c.t("centers"), c.url("ourWork") + "#" + CENTERS[c.lang]], [c.tx(x.shortName)]],
  title: viKeep(c.tx(x.name)),
  intro: c.tx(L.intro),
  introDraft: L.intro.draft,
  unit: x.accent,
  time: "shelter",
  art: art(),
  size: "long",
  side: c.lang === "en" ? html`<p class="sun-head__vi" lang="vi">${x.name.vi}</p>` : "",
})}
<div class="day day--shelter">
  <section class="shelter container" aria-labelledby="lr-serves">
    <h2 class="plate__label" id="lr-serves">${c.t("whoWeServe")}</h2>
    <p class="plate__statement"${c.d(x.serves)}>${c.tx(x.serves)}</p>
    <p class="shelter__note"${c.d(note)}>${quatrefoil("shelter__mark")}<span>${c.tx(note)}</span></p>
  </section>

  <section class="offers container" aria-labelledby="lr-offers">
    <h2 class="offers__title" id="lr-offers" data-reveal="rise"><span>${c.t("whatShelterOffers")}</span></h2>
    <ul class="does offers__list" data-reveal="stagger">${L.offers.map((o) => html`<li${c.d(o)}>${c.tx(o)}</li>`)}</ul>
  </section>

  <section class="give container" aria-labelledby="lr-help">
    <h2 class="give__title give__title--sub" id="lr-help" data-reveal="rise"><span>${c.t("helpShelter")}</span></h2>
    <p class="give__lead"${c.d(L.helpLead)}>${c.tx(L.helpLead)}</p>
    <div class="give__act">
      ${button({ href: c.url("donate"), label: c.t("donateGeneral"), cls: "give__btn" })}
      ${textLink(c.url("bakery"), c.t("bakery"))}
      ${textLink(c.url("inKind"), c.t("inKind"))}
    </div>
  </section>

  <section class="reach-office container" aria-labelledby="lr-contact">
    <h2 class="reach-office__title" id="lr-contact"${c.d(L.contactLine)}>${c.tx(L.contactLine)}</h2>
    ${reachLines(c)}
    ${button({ href: c.url("contact"), label: c.t("contactUs"), kind: "secondary" })}
  </section>
</div>
`;
  return { title: c.tx(x.name), description: c.tx(L.intro), body, bodyClass: "page-shelter" };
}
