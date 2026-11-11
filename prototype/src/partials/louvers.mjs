import { html, raw } from "../lib/html.mjs";
import { viKeep } from "../lib/vikeep.mjs";
import { button, icon } from "./components.mjs";

// Louver rows (05-DESIGN D7 "Louver row", PLAN H8 and P4): the ways to help as full-width slats. Each
// slat's upper face catches the late sun (a feathered band, never a rule); the title is the link and
// covers the slat. Only the Ủng hộ slat carries the sun button. level: the title's heading level (h3
// under the home H2, h2 on Get Involved where the slats are the page's own sections).
const ROUTE = { donate: "donate", volunteer: "volunteer", partner: "partner", "in-kind": "inKind" };

// buttons: false (Get Involved, M6 fix): the header's ways bar already carries each way's button, so here the Ủng
// hộ slat's title is a link like the others' and no slat repeats a button.
export const louver = (c, w, { level = 3, buttons = true } = {}) => {
  const donate = w.id === "donate" && buttons;
  const h = raw("h" + level);
  const title = html`<span class="louver__label">${viKeep(c.tx(w.title))}</span>`;
  return html`<li class="louver${w.id === "donate" ? " louver--donate" : ""}" id="way-${w.id}" data-way="${w.id}">
    <span class="louver__face" aria-hidden="true"></span>
    <${h} class="louver__title">${donate ? title : html`<a href="${c.url(ROUTE[w.id])}">${title}</a>`}</${h}>
    <div class="louver__body">
      <p class="louver__text"${c.d(w.text)}>${c.tx(w.text)}</p>
      ${donate
        ? button({ href: c.url("donate"), label: c.t("donate"), cls: "louver__btn" })
        : w.id === "in-kind"
          ? html`<a class="text-link louver__extra" href="${c.url("bakery")}"><span>${c.t("bakery")}</span>${icon("arrow")}</a>`
          : ""}
    </div>
    ${donate ? "" : icon("arrow", "icon louver__go")}
  </li>`;
};

export const louvers = (c, ways, { level = 3, cls = "", buttons = true } = {}) =>
  html`<ul class="louvers${cls ? " " + cls : ""}" data-reveal="stagger">${ways.map((w) => louver(c, w, { level, buttons }))}</ul>`;
