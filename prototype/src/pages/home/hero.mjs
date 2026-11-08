import { html } from "../../lib/html.mjs";
import { button, pauseToggle } from "../../partials/components.mjs";
import { heroMedia, heroStage } from "../../partials/world.mjs";
import { keepHyphens, viKeep } from "../../lib/vikeep.mjs";

// Home H2 hero (S1 Nắng vào). Owner: 3D story stream (prototype/PLAN.md section 3).
export function hero(c) {
  const hero = c.content.site.hero;
  return html`
<section class="hero" id="hero" data-world="room" aria-labelledby="hero-title">
  ${heroMedia()}
  ${heroStage()}
  <div class="container hero__inner">
    <h1 class="hero__title" id="hero-title" data-text-safe><span${c.d(hero.title)}>${viKeep(c.tx(hero.title))}</span></h1>
    <p class="hero__intro" data-text-safe><span${c.d(hero.intro)}>${keepHyphens(c.tx(hero.intro))}</span></p>
    <div class="hero__actions" data-text-safe>
      ${button({ href: c.url("ourWork"), label: c.t("exploreWork") })}
      ${button({ href: c.url("getInvolved"), label: c.t("getInvolved"), kind: "secondary" })}
    </div>
    ${pauseToggle(c, "hero__pause", { textSafe: "display" })}
  </div>
</section>`;
}
