import { html } from "../lib/html.mjs";
import { still as stillPicture, stillReady, STILLS } from "../lib/posters.mjs";
import { cropsOf } from "../world/still-shots.js";

// Named stills rendered by the world stream (src/world/still-shots.js: dusk, story, door cards). A still
// shows once its files are on disk; until then the page carries its CSS light instead, so nothing 404s
// and the layout holds. Always lazy: none of them is in the first screen. A name the world stream has
// not declared yet (a still still being planned) reads as missing, never as an error.
export const hasStill = (name) => name in STILLS && stillReady(name);
export const still = (name, cls, sizes = "100vw") => (hasStill(name) ? stillPicture(name, { cls, sizes }) : "");
// Width over height of a still's widest crop (the page sizes its box before the image arrives).
export const stillAspect = (name) => Math.max(...cropsOf(name).map((c) => c.aspect));

// A wall in the cool shade with the Hoa thị light falling across it: the CSS stand-in for a lit still.
export const litWall = (cls = "") => html`<span class="lit-wall${cls ? " " + cls : ""}" aria-hidden="true"><span class="lit-wall__light"></span></span>`;
