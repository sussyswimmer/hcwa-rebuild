import { html } from "../../lib/html.mjs";
import { viKeep } from "../../lib/vikeep.mjs";
import { splitWords } from "../../lib/split-words.mjs";
import { still } from "../../lib/posters.mjs";

// Home H3 passage (PLAN H3, 05-DESIGN D5.3 S2): the flight through the hero block into the light, where
// "what we do" is read, then out into the corridor. The section is the scroll length of the flight; the
// frame inside it is sticky and holds the statement over the wash, on its own sun-100 halo. Without the
// live world (Tier 0, reduced motion, no JS) it is a plain band of light: the statement, then the corridor
// still it opens onto. The statement is split into word spans for the sunlit reading; screen readers get
// the plain sentence once. Owner: 3D story stream.
export function passage(c) {
  const what = c.content.site.whatWeDo;
  const text = c.tx(what.summary);
  const { count, words } = splitWords(text);
  return html`
<section class="passage" id="passage" data-world="passage" data-stage aria-labelledby="what-title">
  <div class="passage__frame">
    <div class="passage__halo" aria-hidden="true"></div>
    <div class="container passage__text">
      <h2 class="passage__title" id="what-title">${viKeep(c.tx(what.title))}</h2>
      <p class="statement"${c.d(what.summary)} style="--n:${count}"><span class="sr-only">${text}</span><span class="statement__words" aria-hidden="true">${words}</span></p>
    </div>
  </div>
  <div class="passage__exit" aria-hidden="true">${still("passage-corridor", { cls: "passage__still", sizes: "100vw" })}</div>
</section>`;
}
