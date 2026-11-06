import { html } from "../lib/html.mjs";
import { poster } from "../lib/posters.mjs";

// The page owns DOM and text; src/world/ only draws into this canvas (PLAN section 1).
// Fixed behind <main>, hidden until the world has drawn the poster's rest frame (html.world-live).
export const worldCanvas = () => html`<canvas class="world-canvas" id="world" aria-hidden="true" role="presentation"></canvas>`;

// Shaded base + the light in two layers, the same rest frame the world hands off from (05-DESIGN D4,
// D5.3): the room's light (it drifts down the wall during the sun-in) and the screen's (it holds still,
// so the lattice never shows twice). Together they are exactly the lit still. Without JS, with reduced
// motion or paused, they simply show at rest.
export const heroMedia = () => html`<div class="hero__media lit" aria-hidden="true">
  ${poster("hero", "base", { cls: "lit__base" })}
  ${poster("hero", "lightroom", { cls: "lit__light" })}
  ${poster("hero", "lightscreen", { cls: "lit__screen" })}
</div>`;

// The box frame.js fits the screen silhouette into; text rects are [data-text-safe].
export const heroStage = () => html`<div class="hero__stage" data-stage aria-hidden="true"></div>`;
