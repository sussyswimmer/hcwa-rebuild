import { html } from "../lib/html.mjs";
import { viKeep } from "../lib/vikeep.mjs";
import { textLink, button, quatrefoil, reachLines, breadcrumb } from "../partials/components.mjs";
import { shelterScene } from "../partials/shelter-scene.mjs";

// Little Rose Warm Shelter (PLAN-M4 4.11; 05-DESIGN D6.5, 0.6.4, section 8; CLAUDE.md safeguarding; M6
// "Khu vườn có tường bao"). Safeguarding first: words and an illustration only. The picture is a walled
// garden in paper-cut (partials/shelter-scene.mjs): no figure, no way in, no place, no pink or red. No still
// of any kind, no location of any kind, no contact person and no phone of its own (everything goes through
// the office), no gallery, no figures, no news, no share links, and never the word "visit".
//
// The page is one surface from the title to the office lines (no band, no rule: 05-DESIGN 0.5). The
// garden sits on the right from the first screen; on the stage (html.stages, motion on) it stays pinned
// while the five things the shelter provides scroll past at native speed, each lighting one part of it
// (--step-1 … --step-5, styles/pages.css, CSS scroll-driven timelines; motion/scroll3d.js where those are
// missing). At rest (no JS, reduced motion, pause, Tier 0) the garden shows once, fully lit, beside the
// same five lines as a plain list. It ends without a dusk band: the office lines close the page on the
// day's light and the footer's evening takes it from there.

const CENTERS = { vi: "co-so", en: "centers" };

export function littleRose(c) {
  const { work } = c.content;
  const x = work.centers.find((u) => u.id === "little-rose");
  const L = work.littleRose.page;
  const note = work.littleRose.privacyNote;
  const trail = [[c.t("ourWork"), c.url("ourWork")], [c.t("centers"), c.url("ourWork") + "#" + CENTERS[c.lang]], [c.tx(x.shortName)]];
  const body = html`
<div class="lr-top" data-steps>
  <div class="container lr-grid">
    <header class="lr-head">
      ${breadcrumb(c, trail)}
      <h1 class="lr-head__title">${viKeep(c.tx(x.name))}</h1>
      <p class="lr-head__intro"${c.d(L.intro)}>${c.tx(L.intro)}</p>
      ${c.lang === "en" ? html`<p class="lr-head__vi" lang="vi">${x.name.vi}</p>` : ""}
    </header>
    <figure class="lr-garden">
      ${shelterScene()}
      <figcaption class="lr-garden__note"${c.d(L.artNote)}>${c.tx(L.artNote)}</figcaption>
    </figure>
    <section class="lr-story offers" aria-labelledby="lr-offers">
      <h2 class="offers__title" id="lr-offers">${c.t("whatShelterOffers")}</h2>
      <ul class="does offers__list lr-steps">${L.offers.map((o, i) => html`<li class="lr-step" data-step="${i + 1}"><span class="lr-step__text"${c.d(o)}>${c.tx(o)}</span></li>`)}</ul>
    </section>
  </div>
</div>
<div class="day day--shelter">
  <section class="shelter container" aria-labelledby="lr-serves">
    <h2 class="plate__label" id="lr-serves">${c.t("whoWeServe")}</h2>
    <p class="plate__statement"${c.d(x.serves)}>${c.tx(x.serves)}</p>
    <p class="shelter__note"${c.d(note)}>${quatrefoil("shelter__mark")}<span>${c.tx(note)}</span></p>
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
