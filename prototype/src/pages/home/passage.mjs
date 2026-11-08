import { html } from "../../lib/html.mjs";
import { viKeep } from "../../lib/vikeep.mjs";

// Home H3 passage (statement; S2 part 1). Phase 1 placeholder until rebuilt. Owner: 3D story stream (prototype/PLAN.md section 3).
export function passage(c) {
  const { site } = c.content;
  return html`
<section class="section section--sun" aria-labelledby="what-title" data-reveal>
  <div class="container split">
    <div class="split__lead">
      <h2 id="what-title">${viKeep(c.tx(site.whatWeDo.title))}</h2>
      <p${c.d(site.whatWeDo.summary)}>${c.tx(site.whatWeDo.summary)}</p>
    </div>
  </div>
</section>`;
}
