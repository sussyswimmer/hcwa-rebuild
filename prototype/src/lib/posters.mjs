import { existsSync } from "node:fs";
import { html, raw } from "./html.mjs";
// One list of aspect buckets for the stills and the live camera (src/world/buckets.js has no three.js).
import { BUCKETS as WORLD_BUCKETS, POSTER_WIDTHS } from "../world/buckets.js";

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
