import { html, raw } from "../lib/html.mjs";
import { litStill, stillReady } from "../lib/posters.mjs";
import { breadcrumb } from "./components.mjs";

// Inner-page lit header (05-DESIGN D4 + D7 "Lit header", PLAN P1-P4, P6): a still from the world with its
// light coming in (S1, light.css), the H1 and intro on the still's plain wall. From 600 px the box is 3:1
// or taller and the text keeps inside HEADER_TEXT (the left 54%); below 600 px the still sits in its own
// 4:3 box and the text follows it. The still's foot fades into the page over a long seam (D0.5: no edge
// between the header and the body). Pages using it render with layout({ lit: true }).
//
// still: a header-* name from src/world/still-shots.js. meta: a line above the H1 (status, years, unit).
// title may carry a { main, sub } pair: one H1 whose second part is set smaller (long programme names).
export function litHeader(c, { still, trail, title, intro, introDraft = false, meta = "", extra = "", unit = "", size = "" }) {
  const t = typeof title === "object" && title.main ? html`<span class="lit-head__main">${title.main}</span><span class="sr-only">: </span><span class="lit-head__sub">${title.sub}</span>` : title;
  return html`<header class="lit-head${size ? " lit-head--" + size : ""}${unit ? " lit-head--unit" : ""}"${unit ? raw(` style="--unit:var(--color-unit-${unit})"`) : ""}>
  <div class="lit-head__media" data-still="${still}">${stillReady(still) ? litStill(still) : html`<div class="lit lit--still" aria-hidden="true"></div>`}</div>
  <div class="container lit-head__text">
    ${breadcrumb(c, trail)}
    ${meta ? html`<div class="lit-head__meta">${meta}</div>` : ""}
    <h1 class="lit-head__title">${t}</h1>
    ${intro ? html`<p class="lit-head__intro"${introDraft ? raw(" data-draft") : ""}>${intro}</p>` : ""}
    ${extra}
  </div>
</header>`;
}
