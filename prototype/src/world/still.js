import { createRenderer } from "./renderer.js";
import { probe } from "./tiers.js";
import { buildStill } from "./sets/stills.js";
import { HEADER_TEXT, STILLS, cropsOf } from "./still-shots.js";
import { applyFrame, PAD, projectPoly } from "./frame.js";
import { setCaps } from "./materials/caps.js";
import { applyBounds } from "./flight.js";
import { stillWalls } from "./walls.js";

// Still entry (05-DESIGN D6.10): one named still at one crop, rendered once at an exact pixel width by
// scripts/render-posters.mjs. layer=base is the set in shade, layer=lit every light at rest; the encoder
// turns the pair into a lit header's layers, or keeps the lit frame for a plain still.
// A desktop header keeps its subject right of the text zone (still-shots.js HEADER_TEXT): if the shot
// does not, the lens shifts (setViewOffset, as frame.js does for the hero) and the report says by how
// much. Light inside the zone is capped as body text (40%, D6.6).
const q = new URLSearchParams(location.search);
const name = q.get("still");
const crop = cropsOf(name).find((c) => c.name === q.get("crop"));
const [W, H] = crop.view;
const width = Number(q.get("w") ?? crop.widths.at(-1));
const lit = q.get("layer") !== "base";

const canvas = document.getElementById("world");
canvas.style.width = W + "px";
canvas.style.height = H + "px";
const { gl: context, gpu } = probe(canvas, 2, { poster: true });
const gl = createRenderer(canvas, { context, tier: 2, poster: true });
gl.setPosterSize(W, H, width);
// Tuning only (render-posters never passes them): &cam=x,y,z,lookX,lookY,lookZ,fov &sun=az,el.
const num = (k) => q.get(k)?.split(",").map(Number);
const still = await buildStill(name, crop.name, { gl, aspect: W / H, cam: num("cam"), sun: num("sun") });
// The Blender walls at Tier 2 (walls.js), as the live world has them once they have faded in; &walls=0 renders the
// procedural walls alone (before/after comparisons).
const walls = q.get("walls") === "0" ? null : await stillWalls({ renderer: gl.renderer, tier: 2 });
const { dpr } = gl.size;
for (const p of still.passes) p.setSize(Math.round(W * dpr), Math.round(H * dpr));

const view = { x: 0, y: 0, w: W, h: H };
const project = (list) => list.map((c) => projectPoly(c, still.camera, W, H));
const text = STILLS[name].kind === "header" && crop.media ? { x: HEADER_TEXT.x * W, y: HEADER_TEXT.y * H, w: HEADER_TEXT.w * W, h: HEADER_TEXT.h * H, kind: "body" } : null;
// A shot with a lens of its own (verticals kept vertical) is checked through that lens.
if (still.lens) applyBounds(still.camera, still.lens, 0, H);
let sx = 0;
if (text) {
  // Only what overlaps the zone's height can collide with it.
  const xs = project(still.subject).flat().filter((p) => p.y > text.y - PAD && p.y < text.y + text.h + PAD).map((p) => p.x);
  sx = Math.max(0, text.x + text.w + PAD - Math.min(Infinity, ...xs));
  if (!Number.isFinite(sx)) sx = 0;
  if (still.capText) setCaps(still.U, [text], { h: H, dpr });
}
if (still.lens) {
  // The text-zone shift as a slide of the lens window (sx px of W).
  const k = (sx / W) * (still.lens.r - still.lens.l);
  applyBounds(still.camera, { ...still.lens, l: still.lens.l - k, r: still.lens.r - k }, 0, H);
} else applyFrame(still.camera, view, view, { sx, sy: 0 });
still.setLayer(lit);
await gl.renderer.compileAsync(still.scene, still.camera);
gl.frame(still.scene, still.camera, still.passes);
const info = gl.renderer.info.render;
// The light that holds still during the sun-in: the apertures themselves (poster px of this frame).
const silhouettes = project(still.glow).map((poly) => poly.map((p) => [Math.round((p.x + sx) * 10) / 10, Math.round(p.y * 10) / 10]));
window.__posterResult = {
  still: name, crop: crop.name, view: [W, H], width, layer: lit ? "lit" : "base", gpu,
  frame: { sx: Math.round(sx * 10) / 10, clear: true },
  silhouettes, walls: walls && { size: walls.size, kb: walls.kb }, calls: info.calls, triangles: info.triangles, shafts: still.passes.length > 0,
  png: canvas.toDataURL("image/png"),
};
if (q.get("debug") === "frame") {
  const c = document.createElement("canvas");
  c.width = W; c.height = H;
  c.style.cssText = `position:fixed;inset:0;width:${W}px;height:${H}px;pointer-events:none`;
  document.body.append(c);
  const g = c.getContext("2d");
  if (text) { g.strokeStyle = "rgba(0,160,80,.95)"; g.setLineDash([6, 4]); g.strokeRect(text.x, text.y, text.w, text.h); g.setLineDash([]); }
  g.strokeStyle = "rgba(60,60,200,.95)"; g.lineWidth = 2;
  for (const poly of project(still.subject)) { g.beginPath(); poly.forEach((p, i) => g[i ? "lineTo" : "moveTo"](p.x + sx, p.y)); g.closePath(); g.stroke(); }
  g.strokeStyle = "rgba(230,120,0,.95)";
  for (const poly of silhouettes) { g.beginPath(); poly.forEach(([x, y], i) => g[i ? "lineTo" : "moveTo"](x, y)); g.closePath(); g.stroke(); }
}
document.title = "done";
