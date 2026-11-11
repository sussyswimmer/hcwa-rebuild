import { createRenderer } from "./renderer.js";
import { probe } from "./tiers.js";
import { buildRoom, FRAME_SHAPES } from "./sets/room.js";
import { BUCKETS } from "./buckets.js";
import { applyFrame, drawFrameOverlay, edgePads, solveFrame, toFrame } from "./frame.js";
import { setCaps } from "./materials/caps.js";
import { REST } from "./clock.js";
import { mixc, tok } from "./colors.js";
import { stillWalls } from "./walls.js";

// Poster entry: the same scene code, camera and framing solver as the live world, rendered
// once at an exact pixel width. layer=base is the shaded room (sun intensity 0), layer=lit
// the rest frame; encode-posters.py turns the pair into the base still and the light layer.
// Text rects come from the real page (window.__posterInput), so the stills keep the same
// text-safe framing as the live frame at that viewport.
const q = new URLSearchParams(location.search);
const input = window.__posterInput ?? {};
const bucket = BUCKETS.find((b) => b.name === (q.get("bucket") ?? "landscape"));
const [W, H] = input.view ?? bucket.view;
const width = Number(q.get("w") ?? 1600);
const lit = q.get("layer") !== "base";
const tier = Number(q.get("tier") ?? (bucket.aspect < 1 ? 1 : 2));

const canvas = document.getElementById("world");
canvas.style.width = W + "px";
canvas.style.height = H + "px";

const { gl: context, gpu } = probe(canvas, tier, { poster: true });
const gl = createRenderer(canvas, { context, tier, poster: true });
gl.setPosterSize(W, H, width);
const room = await buildRoom({ gl, tier, slice: false, shaftSteps: 64 }); // a still can afford clean shafts
// The Blender walls of this bucket's tier (walls.js: 512 px on Tier 1, 1024 px with the second sample on Tier 2), at
// full strength: the live room at rest once its maps are in. &walls=0 renders the procedural walls alone.
const walls = q.get("walls") === "0" ? null : await stillWalls({ renderer: gl.renderer, tier });
const { dpr } = gl.size;
room.shafts.setSize(Math.round(W * dpr), Math.round(H * dpr));
// &shot=x,z,lookX,lookY,lookZ,fov tries a K0 for this bucket (shot approvals, day 3).
const tryShot = q.get("shot")?.split(",").map(Number);
room.setShot(bucket, W, H, tryShot && { pos: [tryShot[0], 1.1, tryShot[1]], look: tryShot.slice(2, 5), fov: tryShot[5] });
// Page rects arrive in document px, one set per measured page: VI and EN, the bucket's own view and
// its two extreme aspects, with and without the staging bars. The poster frame is the hero box seen
// through cover (bottom-anchored), and one still must keep every one of those layouts clear. If no
// frame clears them all, the bucket's own VI view decides (the report says so).
const view = { x: 0, y: 0, w: W, h: H };
const sets = input.sets ?? (input.box ? [input] : []);
// Header text is pinned and carries its own scrim: only the bucket's own VI and EN views ask the frame to
// keep it clear. The edge aspects and the EN pointer bar (an English browser on a VI page) move the
// header over another part of the still, and holding the silhouette off it there would cost every
// visitor the framing (the H1 pushed onto the ceiling).
const relaxed = (m) => m.extreme || (m.route === "/" && m.locale && !m.locale.startsWith("vi"));
const allRects = (m) => edgePads(toFrame(m.rects, m.box, [W, H]), bucket.name);
const inFrame = (m) => allRects(m).filter((r) => !(r.pinned && relaxed(m)));
const stage = sets[0]?.stage ? toFrame(sets[0].stage, sets[0].box, [W, H]) : null;
const solve = (list) => solveFrame({ camera: room.camera, width: W, height: H, rects: list.flatMap(inFrame), ...FRAME_SHAPES, stage });
let frame = solve(sets), clears = sets.length;
const own = sets.filter((m) => !m.extreme);
if (!frame.ok && own.length < sets.length) (frame = solve(own)), (clears = own.length);
if (!frame.ok && sets.length > 1) (frame = solve(sets.slice(0, 1))), (clears = 1);
applyFrame(room.camera, view, view, frame);
// Caps: each text element's box over every layout with the same elements in the same order.
const capRects = sets.length ? sets.map(allRects).reduce((acc, list) => list.length !== acc.length ? acc : acc.map((r, i) => {
  const o = list[i];
  if (o.kind !== r.kind || o.pinned !== r.pinned) return r;
  const x = Math.min(r.x, o.x), y = Math.min(r.y, o.y);
  return { ...r, x, y, w: Math.max(r.x + r.w, o.x + o.w) - x, h: Math.max(r.y + r.h, o.y + o.h) - y };
})) : [];
setCaps(room.U, capRects, { h: H, dpr });
// &nudge=dAz,dEl turns the sun by degrees from its keyframe, &depth=m sets the aperture depth: shot
// and sun approvals only (render-posters never passes them).
const tryNudge = q.get("nudge")?.split(",").map(Number);
if (q.get("depth")) room.U.uD.value = Number(q.get("depth"));
if (q.get("density")) room.shafts.uniforms.uDensity.value = Number(q.get("density"));
// &face=sand: the proof's sand-cement face, for an A/B against the blue-grey silhouette.
if (q.get("face") === "sand") { room.U.uFace.value = tok("faceSand"); room.U.uWall.value = mixc(tok("faceSand"), tok("sky"), 0.38); }
room.setSun({ t: Number(q.get("t") ?? REST.room), intensity: lit ? 1 : 0, rims: 1, nudge: tryNudge ? { az: tryNudge[0], el: tryNudge[1] } : null });
room.shafts.enabled = lit;
room.motes.points.visible = false; // dust frozen in a still reads as noise
await room.compile();
gl.frame(room.scene, room.camera, [room.shafts]);
const info = gl.renderer.info.render;
window.__posterResult = {
  bucket: bucket.name, view: [W, H], width, layer: lit ? "lit" : "base", tier, gpu,
  frame: { sx: frame.sx, sy: frame.sy, ok: frame.ok, headerClear: frame.headerClear, heroInStage: frame.heroInStage, clears, of: sets.length },
  // The screen outline in this still (poster px): the page holds the screen's light still while the room's drifts.
  silhouette: frame.debug.scr.map((p) => [Math.round((p.x + frame.sx) * 10) / 10, Math.round((p.y + frame.sy) * 10) / 10]),
  // The text caps this still was lit with: the live rest frame uses the same ones, so the handoff is exact.
  caps: capRects.filter((r) => r.kind !== "opaque" && !r.pinned).map(({ x, y, w, h, kind }) => ({ x: Math.round(x), y: Math.round(y), w: Math.round(w), h: Math.round(h), kind })),
  walls: walls && { size: walls.size, kb: walls.kb },
  calls: info.calls, triangles: info.triangles,
  png: canvas.toDataURL("image/png"),
};
if (q.get("debug") === "frame") drawFrameOverlay(frame, view, view);
document.title = "done";
