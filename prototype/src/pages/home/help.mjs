import { html } from "../../lib/html.mjs";
import { viKeep } from "../../lib/vikeep.mjs";
import { louvers } from "../../partials/louvers.mjs";

// Home H8 how you can help (Action). The shutters have just opened onto it: four full-width louver
// slats in big type on sun-100, and the page's only sun button, on the Ủng hộ row. It rises into the
// shutters stage when that stage is live (pages.css); stacked, it simply follows the still.
// Owner: page stream (prototype/PLAN.md section 3).
export function help(c) {
  const { site, involve } = c.content;
  return html`
<section class="wall wall--light help" id="help" aria-labelledby="help-title">
  <span class="sun-layer sun-layer--help" aria-hidden="true"></span>
  <div class="container">
    <h2 class="help__title" id="help-title" data-reveal data-reveal-on="entry">${viKeep(c.tx(site.helpTitle))}</h2>
  </div>
  ${louvers(c, involve.ways)}
</section>`;
}
