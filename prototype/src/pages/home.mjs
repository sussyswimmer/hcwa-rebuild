import { html } from "../lib/html.mjs";
import { hero } from "./home/hero.mjs";
import { passage } from "./home/passage.mjs";
import { corridor } from "./home/corridor.mjs";
import { workSheet } from "./home/work-sheet.mjs";
import { news } from "./home/news.mjs";
import { shutters } from "./home/shutters.mjs";
import { help } from "./home/help.mjs";
import { partners } from "./home/partners.mjs";
import { contactBand } from "./home/contact.mjs";

// The home page in PLAN section 3 order. Each section lives in its own file under home/ and has one
// owner (3D story: hero, passage, corridor, shutters; page: the sheets), so both can be built at once.
export function home(c) {
  const { site } = c.content;
  const body = html`${hero(c)}
${passage(c)}
${corridor(c)}
${workSheet(c)}
${news(c)}
${shutters(c)}
${help(c)}
${partners(c)}
${contactBand(c)}`;

  return {
    title: c.tx(site.orgName),
    description: c.tx(site.hero.intro),
    body,
    bodyClass: "page-home",
    lit: true,
    world: true,
  };
}
