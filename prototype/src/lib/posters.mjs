import { existsSync } from "node:fs";
import { html, raw } from "./html.mjs";
// One list of aspect buckets for the stills and the live camera (src/world/buckets.js has no three.js).
import { BUCKETS as WORLD_BUCKETS, POSTER_WIDTHS } from "../world/buckets.js";
import { STILLS as STILL_SHOTS, cropsOf, stillFile } from "../world/still-shots.js";

// Stills rendered from the world (05-DESIGN D6.10, PLAN section 5):
// public/posters/<shot>-<bucket>-<layer>-<w>.<ext>, one framing per viewport aspect bucket.
// The light comes in two layers that composite exactly to the lit frame (scripts/encode-posters.py):
// the room's light, which drifts during the sun-in, and the screen's, which holds still. Both have
// alpha, so they have no JPEG.
export const BUCKETS = WORLD_BUCKETS.map((b) => [b.name, b.media ?? ""]);
export const WIDTHS = POSTER_WIDTHS;
export const FORMATS = { base: ["avif", "webp", "jpg"], lightroom: ["avif", "webp"], lightscreen: ["avif", "webp"] };
// Phones get at most the 960 w still: a 390 px screen at DPR 3 would otherwise pick 1600 w (twice the
// bytes) for an image that is soft light over a lattice. 80vw keeps DPR 1.75-3 on 960 w.
const SIZES = { portrait: "80vw" };
const TYPE = { avif: "image/avif", webp: "image/webp", jpg: "image/jpeg" };
// A bucket without its own light layer must not fall through to another bucket's framing.
const NOTHING = "data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7";

const publicDir = new URL("../../public/", import.meta.url);
export const posterPath = (shot, bucket, layer, w, ext) => `/posters/${shot}-${bucket}-${layer}-${w}.${ext}`;
const exists = (p) => existsSync(new URL("." + p, publicDir));

// Files the world builder has not rendered yet, so the render step can say so (the layout holds without them).
export function missingPosters(shot) {
  const all = BUCKETS.flatMap(([b]) => Object.entries(FORMATS).flatMap(([layer, exts]) => exts.flatMap((ext) => WIDTHS.map((w) => posterPath(shot, b, layer, w, ext)))));
  return all.filter((p) => !exists(p));
}

// <picture> for one layer of a shot. The base is the LCP candidate, so it loads eagerly at high priority;
// the light layers are lazy, so they start after first layout and never share the first bytes with it
// (the sun-in waits for them to decode anyway). Before any still is rendered the planned names are
// referenced as they are; once some exist, only files on disk are listed (an encoder may skip a width
// or format that cannot meet its budget).
export function poster(shot, layer, { cls, sizes = "100vw" } = {}) {
  const exts = FORMATS[layer];
  const rendered = BUCKETS.some(([b]) => exts.some((ext) => WIDTHS.some((w) => exists(posterPath(shot, b, layer, w, ext)))));
  const keep = (p) => !rendered || exists(p);
  const sources = BUCKETS.flatMap(([bucket, media]) => {
    const m = media ? html` media="${media}"` : "";
    const sets = exts
      .map((ext) => [ext, WIDTHS.map((w) => [posterPath(shot, bucket, layer, w, ext), w]).filter(([p]) => keep(p))])
      .filter(([, list]) => list.length);
    if (!sets.length) return html`<source${m} srcset="${NOTHING}">`;
    return sets.map(([ext, list]) => html`<source type="${TYPE[ext]}"${m} srcset="${list.map(([p, w]) => `${p} ${w}w`).join(", ")}" sizes="${SIZES[bucket] ?? sizes}">`);
  });
  const candidates = [...exts].reverse().flatMap((ext) => ["wide", "landscape", ...BUCKETS.map(([b]) => b)].map((b) => posterPath(shot, b, layer, 960, ext)));
  const fallback = candidates.find(keep) ?? NOTHING;
  const priority = layer === "base" ? raw(' fetchpriority="high"') : raw(' loading="lazy" decoding="async"');
  return html`<picture class="${cls}">${sources}<img src="${fallback}" alt="" width="1600" height="900"${priority} data-layer="${layer}"></picture>`;
}

// ---- Named stills (inner-page lit headers, the footer's dusk, door cards, the 404, the story slot) ----
// src/world/still-shots.js holds the names, crops and budgets; scripts/render-posters.mjs --still renders
// them from the world. A crop is art direction: each gets its own <source media> with its own size, so
// the 3:1 desk header and the 4:3 phone crop never stretch into each other.
export { STILLS, HEADER_MEDIA, HEADER_TEXT } from "../world/still-shots.js";
const STILL_FORMATS = { base: ["avif", "webp", "jpg"], lightroom: ["avif", "webp"], lightscreen: ["avif", "webp"], still: ["avif", "webp", "jpg"] };
const cropSize = (c) => [Math.max(...c.widths), Math.round(Math.max(...c.widths) / c.aspect)];

function requireStill(name, layer) {
  if (!STILL_SHOTS[name]) throw new Error(`posters: no still named "${name}" (src/world/still-shots.js)`);
  if (layer && !STILL_SHOTS[name].layers.includes(layer)) throw new Error(`posters: still "${name}" has no ${layer} layer`);
}

// Files a named still is missing on disk (the render step lists them; the layout holds without them).
export function missingStills(name) {
  requireStill(name);
  return STILL_SHOTS[name].layers.flatMap((layer) => cropsOf(name).flatMap((c) => STILL_FORMATS[layer].flatMap((ext) => c.widths.map((w) => stillFile(name, c.name, layer, w, ext))))).filter((p) => !exists(p));
}

// Whether every file of a named still is on disk (a page may fall back to its CSS light until then).
export const stillReady = (name) => missingStills(name).length === 0;

// <picture> for one layer of a named still. eager: the LCP candidate (a lit header's base); everything
// else loads lazily. sizes: per crop name or one string for all.
export function stillPicture(name, layer, { cls = "", sizes = "100vw", eager = false, alt = "" } = {}) {
  requireStill(name, layer);
  const crops = cropsOf(name);
  const sourceSizes = (c) => (typeof sizes === "string" ? sizes : sizes[c.name] ?? "100vw");
  const sources = crops.flatMap((c) => {
    const m = c.media ? html` media="${c.media}"` : "";
    const [w, h] = cropSize(c);
    const sets = STILL_FORMATS[layer]
      .map((ext) => [ext, c.widths.map((cw) => [stillFile(name, c.name, layer, cw, ext), cw]).filter(([p]) => exists(p))])
      .filter(([, list]) => list.length);
    if (!sets.length) return html`<source${m} srcset="${NOTHING}" width="${w}" height="${h}">`;
    return sets.map(([ext, list]) => html`<source type="${TYPE[ext]}"${m} srcset="${list.map(([p, cw]) => `${p} ${cw}w`).join(", ")}" sizes="${sourceSizes(c)}" width="${w}" height="${h}">`);
  });
  // The <img> carries the last crop (the one without a media query) as its fallback and size.
  const last = crops.at(-1), [w, h] = cropSize(last);
  const fallback = [...STILL_FORMATS[layer]].reverse().flatMap((ext) => [...last.widths].reverse().map((cw) => stillFile(name, last.name, layer, cw, ext))).find(exists) ?? NOTHING;
  const load = eager ? raw(' fetchpriority="high"') : raw(' loading="lazy" decoding="async"');
  return html`<picture class="${cls}">${sources}<img src="${fallback}" alt="${alt}" width="${w}" height="${h}"${load} data-layer="${layer}"></picture>`;
}

// A lit header's still (05-DESIGN D4, D5.3 S1, D7 "Lit header"): the shaded base, the room's light (it
// drifts during the sun-in) and, when the opening is in frame, its own light (holds still). The block
// fills its positioned, isolated parent (light.css .lit); the page puts the H1 and intro over HEADER_TEXT
// on desktop and below the still on phones, and renders the page with layout({ lit: true }) so the head
// script can schedule the sun-in. Decorative: aria-hidden, no alt text.
export function litStill(name, { cls = "" } = {}) {
  requireStill(name, "base");
  const has = (l) => STILL_SHOTS[name].layers.includes(l);
  const motion = STILL_SHOTS[name].motion ? " lit--" + STILL_SHOTS[name].motion : "";
  return html`<div class="lit lit--still${motion}${cls ? " " + cls : ""}" aria-hidden="true" data-still="${name}">
  ${stillPicture(name, "base", { cls: "lit__base", eager: true })}
  ${stillPicture(name, "lightroom", { cls: "lit__light" })}
  ${has("lightscreen") ? stillPicture(name, "lightscreen", { cls: "lit__screen" }) : ""}
</div>`;
}

// A plain still (dusk band, door card, 404, story slot): one decorative <picture>, lazy unless eager.
export const still = (name, opts = {}) => stillPicture(name, "still", opts);
