import { html } from "../../lib/html.mjs";
import { placeholder, sectionHead } from "../../partials/components.mjs";

// Home H8 how you can help. Phase 1 placeholder until rebuilt. Owner: page stream (prototype/PLAN.md section 3).
export function help(c) {
  const { site, involve } = c.content;
  return html`
<section class="section section--help" aria-labelledby="help-title" data-reveal>
  <div class="container">
    ${sectionHead(c, { id: "help-title", title: c.tx(site.helpTitle) })}
    <ul class="ways">
      ${involve.ways.map(
        (w) => html`<li class="way">
          ${placeholder(c, { code: w.art, label: w.title, ratio: "1:1", illustration: true }, "way__art")}
          <h3><a href="${c.url(w.page === "partner" ? "partner" : w.page === "in-kind" ? "inKind" : w.page)}">${c.tx(w.title)}</a></h3>
          <p${c.d(w.text)}>${c.tx(w.text)}</p>
        </li>`,
      )}
    </ul>
  </div>
</section>`;
}
