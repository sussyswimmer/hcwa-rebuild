import { html, raw } from "../lib/html.mjs";
import { litStill, stillReady, STILLS } from "../lib/posters.mjs";
import { breadcrumb } from "./components.mjs";

// Inner-page lit header (05-DESIGN D4 + D7 "Lit header", PLAN P1-P4, P6): a still from the world with its
// light coming in (S1, light.css), the H1 and intro on the still's plain wall. From 600 px the box is 3:1
// or taller and the text keeps inside HEADER_TEXT (the left 54%); below 600 px the still sits in its own
// 4:3 box and the text follows it. The still's foot fades into the page over a long seam (D0.5: no edge
// between the header and the body). Pages using it render with layout({ lit: true }).
//
// still: a header-* name from src/world/still-shots.js. A name the world stream has not declared or
// rendered yet (header-about, header-impact, header-cau-han in M4) shows the shade fill, never an error. meta: a line above the H1 (status, years, unit).
// title may carry a { main, sub } pair: one H1 whose second part is set smaller (long programme names).
// layers: decorative layers drawn over the still inside the media box (Contact's gate), never words.
export function litHeader(c, { still, trail, title, intro, introDraft = false, meta = "", extra = "", unit = "", size = "", layers = "" }) {
  const t = typeof title === "object" && title.main ? html`<span class="lit-head__main">${title.main}</span><span class="sr-only">: </span><span class="lit-head__sub">${title.sub}</span>` : title;
  return html`<header class="lit-head${size ? " lit-head--" + size : ""}${unit ? " lit-head--unit" : ""}"${unit ? raw(` style="--unit:var(--color-unit-${unit})"`) : ""}>
  <div class="lit-head__media" data-still="${still}">${still in STILLS && stillReady(still) ? litStill(still) : html`<div class="lit lit--still" aria-hidden="true"></div>`}${layers}</div>
  <div class="container lit-head__text">
    ${breadcrumb(c, trail)}
    ${meta ? html`<div class="lit-head__meta">${meta}</div>` : ""}
    <h1 class="lit-head__title">${t}</h1>
    ${intro ? html`<p class="lit-head__intro"${introDraft ? raw(" data-draft") : ""}>${intro}</p>` : ""}
    ${extra}
  </div>
</header>`;
}

// Whether a page's header still is declared and rendered. Until it is, the page opens on the sun band
// instead of the lit header's flat shade fill (fix round 1: a cool lilac first view clashed with the warm
// day on every other page); the page swaps back to its lit header by itself once the walls stream ships
// the still. Used by Who we are (header-about), Impact (header-impact) and Cầu Hàn (header-cau-han).
export const headerReady = (still) => still in STILLS && stillReady(still);

// The image-free header (Donate's sun band, PLAN-M4 decision 8): the H1 is the picture, a patch of the
// house's Hoa thị light falls across it from the left, and the band feathers into the page's day with no
// edge. time: where the page sits in the day (morning lime for About, late sun for Get Involved, calm
// for Policies). unit: a center's paint as a 6 px band (Little Rose: paint, never light). side: the
// intro and any first-screen actions. art: optional block art beside the title (decoration only). meta: a
// line above the H1 (a center's facts while its still is pending).
export function sunHead(c, { trail, title, intro, introDraft = false, side = "", time = "sun", unit = "", art = "", size = "", meta = "" }) {
  return html`<header class="sun-head sun-head--page sun-head--${time}${size ? " sun-head--" + size : ""}${unit ? " sun-head--unit" : ""}${art ? " sun-head--art" : ""}"${unit ? raw(` style="--unit:var(--color-unit-${unit})"`) : ""}>
  <span class="sun-layer sun-layer--page" aria-hidden="true"></span>
  <div class="container sun-head__inner">
    ${breadcrumb(c, trail)}
    ${meta ? html`<div class="sun-head__meta">${meta}</div>` : ""}
    <h1 class="sun-head__title">${title}</h1>
    <div class="sun-head__side">
      ${intro ? html`<p class="sun-head__intro"${introDraft ? raw(" data-draft") : ""}>${intro}</p>` : ""}
      ${side}
      ${art ? html`<div class="sun-head__art" aria-hidden="true">${art}</div>` : ""}
    </div>
  </div>
</header>`;
}
