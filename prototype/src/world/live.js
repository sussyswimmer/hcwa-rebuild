import { Euler, Quaternion, Vector3, Vector4 } from "three";
import { createRenderer } from "./renderer.js";
import { createGate } from "./tiers.js";
import { buildRoom, FRAME_SHAPES } from "./sets/room.js";
import { WIN_Z } from "./sets/places.js";
import { bucketFor } from "./buckets.js";
import { applyFrame, clearAt, clearTurned, edgePads, frameOrigin, readBox, readTextRects, solveFrame, toFrame } from "./frame.js";
import { POSTER_FRAMES } from "./poster-frames.js";
import { setCaps } from "./materials/caps.js";
import { POINTER_RANGE, REST } from "./clock.js";
import { tok } from "./colors.js";
import { bezier } from "./tokens.js";
import { cubicBezier, stepSpring } from "./ease.js";
import { createFlight, journeyAt } from "./flight.js";
import { DOOR_DEG, PASSAGE, SHUTTERS, WALK, WALK_OUT, blendAt, corridorLayout, doorAngle, floodAt, floodStep, leafAt, leafFade, readBeats, s3Exposure, s3Open, s3Pose, s3Sun, setAt, slatLive, walkPitch, washSpan, wash as washAt } from "./journey.js";
import { createWalk } from "./walk.js";
import { worldOff } from "./index.js";

// The live world (05-DESIGN D6, PLAN H2-H4, H7), with three.js. index.js loads it only once the GPU probe has
// passed. It draws into the page's fixed canvas, reads the DOM (frame box, stage, text rects, beats) and
// never writes text. html.world-live is added once the first frame matches the poster's rest pose; the
// page's CSS then fades the stills out. Whatever fails, the stills stay: that is the complete page.
//
// One canvas and one camera for S2 (D5.3): the hero's rest frame, the camera turning to the screen as the
// hero scrolls out, into the hero block, the light wash, the room swapped for the corridor behind it, and
// out of a hole in the corridor screen, then down the corridor door by door while the cards are read
// (PLAN H4). Then, past the sheets, the late-afternoon room of S3 (PLAN H7): the shutters open onto the
// help section. Everything follows the scroll position, so it reverses at the visitor's speed; the one
// time-based moment is S3's flood, which the scroll only arms. Idle motion (canopy, cloud, motes, pointer
// sun) belongs to the two rooms at rest, the hero's and the shutters', and, on Tier 2, the corridor's
// tamarind dapple while a card is read.

const SPRING = 6; // rad/s: the pointer sun and the camera's parallax (D5.1)
const CANOPY_IN = 2.5; // s of world time for the canopy to fade in after the handoff (glsl LEAF)
const PLASTER = tok("lime-100"); // the corridor's way out washes to the plaster under the stage (stages.css)
const CLOUD = { dip: 0.82, down: 1600, up: 2400, min: 14000, max: 22000, first: [3500, 5000] };
// The sun clock along the scroll (D6.4): the hero scrolling out 0.08 → 0.14, the approach to the hole
// 0.14 → 0.20; the corridor stands at 0.25 when the camera comes out into it.
const SUN_T = { rest: REST.room, heroOut: 0.14, hole: 0.2, corridor: WALK.sun[0] };
// Pointer parallax: the camera trucks 5 cm and turns 0.8° / 0.4°, an orbit about a point ~3.6 m in
// front, so the screen's near edge slides against the back wall (a depth cue a still cannot give).
// Scaled down per layout until every pointer extreme keeps the hero text clear.
const PARALLAX = { truck: 0.05, yaw: (0.8 * Math.PI) / 180, pitch: (0.4 * Math.PI) / 180 };
// Nearing the hole the eye adjusts to the light: the halation spreads over the block's face and the
// beams thicken (the room's own uniforms, rest → at the hole).
const NEAR = { haloA: [0.35, 0.95], haloW: [0.012, 0.06], density: [1.6, 2.8] };
// S3's shafts (D5.3: density 1 → 1.6 as the leaves open, → 2.0 at the flood's peak, back to 1.6), scaled to
// this set's beams; the flare is the flood's bloom of sun-100 around the window (renderer.js wash).
const S3_SHAFTS = 1.8, S3_FLARE = { radius: 0.62, held: 0.1, peak: 0.55 };
const inOut = cubicBezier(bezier.inOut);
const nextFrame = () => new Promise((ok) => requestAnimationFrame(ok));
const lerp = (a, b, t) => a + (b - a) * t;
const clamp01 = (x) => Math.min(Math.max(x, 0), 1);
const smoothLeaf = (q) => leafAt(q) / SHUTTERS.leafDeg;

export async function live({ canvas, stage, bus, probed, forced }) {
  const root = document.documentElement;
  const debug = new URLSearchParams(location.search).get("debug");
  let tier = probed.tier;
  const gl = createRenderer(canvas, { context: probed.gl, tier });
  const room = await buildRoom({ gl, tier });
  const section = stage?.closest("[data-world]") ?? document.querySelector('[data-world="room"]');
  const passageEl = document.querySelector('[data-world="passage"]');
  const corridorEl = document.querySelector('[data-world="corridor"]');
  const shuttersEl = document.querySelector('[data-world="shutters"]');
  const cam = room.camera;
  const flight = createFlight();
  const walk = createWalk();
  let corridor = null, shutters = null;

  // ---- framing: exactly the poster's frame, as the browser shows it in the hero box ----
  // The bucket is the one the <picture> media queries pick (viewport aspect); the poster frame covers the
  // box, centred across and anchored to the bottom (frame.js frameOrigin). The solver runs in poster pixels
  // (same rects, same answer as render-posters), and the canvas shows that frame extended. The canvas is
  // fixed: at the rest pose (scroll 0) document px are canvas px; past it the flight table takes over.
  const box = { x: 0, y: 0, w: 1, h: 1 }, view = { w: 1, h: 1 }, frameRect = { x: 0, y: 0, w: 1, h: 1 };
  const right = new Vector3(), turn = new Quaternion(), euler = new Euler(0, 0, 0, "YXZ");
  const span = { hero: { top: 0, len: 1 }, passage: { top: 0, len: 1 }, corridor: { top: 0, len: 1, pre: 1, next: 0 }, shutters: { from: 0, top: 0, len: 1 } };
  let lastFrame = null, dirty = true, parallax = 1, solved = null, walking = false, lastBucket = "landscape";
  const beats = readBeats(document.querySelectorAll("[data-beat]"));
  const doors = corridorLayout(beats);
  // The pointer's parallax on top of the camera's pose, w of it (it fades as the flight starts).
  const nudgePose = (x, y, w) => {
    if (!w || (!x && !y)) return;
    right.set(1, 0, 0).applyQuaternion(cam.quaternion);
    cam.position.addScaledVector(right, x * PARALLAX.truck * parallax * w);
    cam.quaternion.multiply(turn.setFromEuler(euler.set(y * PARALLAX.pitch * parallax * w, x * PARALLAX.yaw * parallax * w, 0)));
    cam.updateMatrixWorld();
  };
  const layout = () => {
    Object.assign(box, readBox(section) ?? { x: 0, y: 0, w: innerWidth, h: innerHeight });
    const w = canvas.clientWidth || innerWidth, h = canvas.clientHeight || innerHeight;
    gl.resize(w, h);
    const { dpr } = gl.size;
    room.shafts.setSize(Math.round(w * dpr), Math.round(h * dpr));
    corridor?.shafts.setSize(Math.round(w * dpr), Math.round(h * dpr));
    shutters?.shafts.setSize(Math.round(w * dpr), Math.round(h * dpr));
    if (shutters) shutters.U.uPx.value = dpr;
    view.w = w; view.h = h;
    const bucket = bucketFor(root.clientWidth || w, root.clientHeight || h), [pw, ph] = bucket.view;
    const k = Math.max(box.w / pw, box.h / ph), o = frameOrigin(box, bucket.view, k);
    Object.assign(frameRect, { x: box.x + o.x, y: box.y + o.y, w: pw * k, h: ph * k });
    room.setShot(bucket, pw, ph);
    const rects = readTextRects();
    const inFrame = edgePads(toFrame(rects, box, bucket.view), bucket.name);
    lastFrame = solveFrame({ camera: cam, width: pw, height: ph, rects: inFrame, ...FRAME_SHAPES, stage: stage ? toFrame(readBox(stage), box, bucket.view) : null });
    // The still's own shift while it keeps this layout's text clear (it was solved over every layout of
    // its bucket): then the handoff lands on the still exactly, its text caps included.
    const still = POSTER_FRAMES[bucket.name];
    const useStill = still && clearAt(lastFrame, still.sx, still.sy);
    if (useStill) Object.assign(lastFrame, { sx: still.sx, sy: still.sy, still: true });
    // Pointer parallax: the largest share of it that keeps every extreme clear.
    const baseQ = cam.quaternion.clone(), basePos = cam.position.clone();
    parallax = 0;
    for (const s of [1, 0.5, 0.25]) {
      parallax = s;
      if ([[-1, -1], [1, -1], [-1, 1], [1, 1]].every(([x, y]) => (cam.position.copy(basePos), cam.quaternion.copy(baseQ), nudgePose(x, y, 1), clearTurned(lastFrame, cam, FRAME_SHAPES, pw, ph)))) break;
      parallax = 0;
    }
    cam.position.copy(basePos); cam.quaternion.copy(baseQ); cam.updateMatrixWorld();
    applyFrame(cam, frameRect, view, { sx: lastFrame.sx * k, sy: lastFrame.sy * k });
    flight.setRest(cam, bucket.name, view);
    const caps = useStill && still.caps ? still.caps.map((r) => ({ ...r, x: frameRect.x + r.x * k, y: frameRect.y + r.y * k, w: r.w * k, h: r.h * k })) : rects;
    setCaps(room.U, caps, { h, dpr });
    room.U.uPx.value = dpr;
    // The journey's spans (document px): the hero scrolls out over its own height; the passage's timeline
    // is its height less the sticky frame's (it starts as the hero leaves and ends before the corridor).
    span.hero.top = box.y; span.hero.len = Math.max(box.h, 1);
    const pb = readBox(passageEl), frameH = passageEl?.querySelector(".passage__frame")?.offsetHeight ?? h;
    span.passage.top = pb ? pb.y : box.y + box.h;
    span.passage.len = pb ? Math.max(pb.h - (getComputedStyle(passageEl.querySelector(".passage__frame") ?? passageEl).position === "sticky" ? frameH : 0), 1) : h * 1.8;
    // Text safety while the hero scrolls out over the fixed canvas (flight.js). The table runs on the page's
    // own scroll from 0, where the rects were measured: anything above the hero (the staging bars) scrolls
    // away first and moves the text with it, so the hero's top is no offset of the table.
    solved = flight.solve(cam, {
      heroH: span.passage.top, approach: PASSAGE.approach[1] * span.passage.len,
      rects: readTextRects(section), shapes: FRAME_SHAPES, view,
      top: parseFloat(getComputedStyle(root).getPropertyValue(innerWidth >= 1024 ? "--header-h-compact" : "--header-h")) || 0,
    });
    if (debug === "frame") import("./frame.js").then((f) => f.drawFrameOverlay(lastFrame, frameRect, view, k));
    lastBucket = bucket.name;
    layoutWalk();
    layoutShutters();
    dirty = true;
  };
  // The corridor's stage (PLAN H4): its span (the approach while the stage rises into place, then the
  // pinned walk), and the part of the frame its card panel leaves free for the door. Only on the stage
  // layout (motion/stages.js adds is-stage once every card fits its panel); stacked, the camera holds at K4.
  const layoutWalk = () => {
    const frameEl = corridorEl?.querySelector(".bays__frame"), paneEl = corridorEl?.querySelector(".bays__panel");
    walking = Boolean(frameEl && paneEl && root.classList.contains("stages") && corridorEl.classList.contains("is-stage"));
    if (!walking) return;
    // The walk's span is the stage track's, exactly as motion/stages.js reads it (top at the top of the
    // screen to bottom at the bottom): the section also holds the link under the stage, and measuring over
    // it would let the cards run ahead of the camera.
    const tb = readBox(corridorEl.querySelector("[data-stage-track]") ?? corridorEl), fr = frameEl.getBoundingClientRect(), pr = paneEl.getBoundingClientRect();
    const fh = fr.height || view.h;
    // The sheet after the corridor: the way out's wash runs until its top nears the top of the frame (journey.js
    // washSpan), wherever the page puts it.
    const nx = corridorEl.nextElementSibling, next = nx ? readBox(nx).y : tb.y + tb.h + view.h;
    Object.assign(span.corridor, { top: tb.y, len: Math.max(tb.h - fh, 1), pre: fh, next });
    const head = parseFloat(getComputedStyle(root).getPropertyValue(innerWidth >= 1024 ? "--header-h-compact" : "--header-h")) || 0;
    const side = pr.left - fr.left > fr.width * 0.3;
    const free = side ? { x0: 0, x1: pr.left - fr.left, y0: head, y1: fh } : { x0: 0, x1: fr.width, y0: head, y1: pr.top - fr.top };
    walk.setLayout({ bucket: lastBucket, view, name: side ? "side" : "below", corridor: doors, free });
  };

  // S3's stage (PLAN H7): only when its frame is sticky (html.stages and a screen at least 640 px tall);
  // otherwise the section shows its stills and the world leaves it alone. from: the scroll where the
  // section starts to rise into view (the approach); top: where the track's pinned span starts; len: its length.
  let staged = false;
  const layoutShutters = () => {
    const track = shuttersEl?.querySelector("[data-stage-track]"), frameEl = shuttersEl?.querySelector(".shutters__frame");
    staged = Boolean(track && frameEl && getComputedStyle(frameEl).position === "sticky");
    if (!staged) return;
    const sb = readBox(shuttersEl), tb = readBox(track), fh = frameEl.offsetHeight || view.h;
    Object.assign(span.shutters, { from: sb.y - view.h, top: tb.y, len: Math.max(tb.h - fh, 1) });
  };
  const inShutters = (y) => staged && y >= span.shutters.from;
  // The flood: a one-shot the scroll arms (journey.js floodStep), stepped in the loop, read in draw().
  const flood = { on: false, v: 0 }, fl = { peak: 0, held: 0 }, flare = new Vector4(), focus = new Vector3();
  // The passage's wash as the inside of the hole: its lobes' vignette and the dust drifting at 0.3× the
  // scroll (renderer.js), in frame-height units.
  const hole = new Vector4(), DUST = 0.3;
  // The corridor's way out: its wash from the top of the frame down (renderer.js sweep), eased --ease-in-out.
  const sweep = new Vector4(), EDGE = 28;
  let leafLast = -1;
  const s3Cam = { pos: [0, 0, 0], look: [0, 0, 0], fov: 0 };
  let s3Last = -1, s3Sunt = -1, floodHold = null;

  // ---- state that moves the light ----
  const fine = matchMedia("(pointer: fine)").matches;
  const px = { x: 0, v: 0 }, py = { x: 0, v: 0 }, target = { x: 0, y: 0 };
  const nudge = { az: 0, el: 0 };
  const cloud = { level: 1, from: 1, to: 1, start: 0, dur: 0, next: 0 };
  let stir = 0, time = 0;
  const wind = room.U.uWind.value;
  const later = (now) => now + CLOUD.min + Math.random() * (CLOUD.max - CLOUD.min);
  const tween = (now, to, dur) => Object.assign(cloud, { from: cloud.level, to, start: now, dur });
  // Touch screens: the first cloud passes 3.5-5 s after the handoff, then one every 14-22 s (D5.3).
  // Fine pointers move the sun instead.
  const stepCloud = (now) => {
    if (fine) return false;
    if (!cloud.next) cloud.next = now + CLOUD.first[0] + Math.random() * (CLOUD.first[1] - CLOUD.first[0]);
    if (cloud.dur) {
      const u = Math.min((now - cloud.start) / cloud.dur, 1);
      cloud.level = cloud.from + (cloud.to - cloud.from) * inOut(u);
      if (u >= 1) {
        cloud.dur = 0;
        if (cloud.to < 1) tween(now, 1, CLOUD.up);
        else cloud.next = later(now);
      }
      return true;
    }
    if (now >= cloud.next) { tween(now, CLOUD.dip, CLOUD.down); return true; }
    return false;
  };

  // ---- the journey: where the scroll is, which set, which pose ----
  const sunState = { t: REST.room, nudge, intensity: 1, rims: 1 }; // reused: no per-frame garbage
  const at = { hero: 0, passage: 0, y: 0, approach: 0, stage: 0, out: 0 }, path = { s: 0, lift: 0 };
  let lastY = -1, inRoom = true, inS3 = false, wasS3 = false, corridorSun = -1, propT = 0, hallWash = 1;
  const draw = (now) => {
    const y = scrollY;
    lastY = y;
    journeyAt(y, span, at);
    const p = at.passage;
    let wash = washAt(p);
    const set = setAt(p);
    inS3 = inShutters(y);
    inRoom = !inS3 && set === "room";
    let scene = null, camera = cam, passes = [], glow = null, washColor = null;
    if (inS3) {
      // S3: the camera comes round to the window as the section rises, then the slats turn, the leaves swing
      // in, the flood, and the frame gives way to sun-100 for the help section. Without the set yet (a very
      // fast scroll), the frame is sun-100 and the section's stills show.
      const sp = span.shutters, a = clamp01((y - sp.from) / Math.max(sp.top - sp.from, 1)), q = clamp01((y - sp.top) / sp.len);
      at.s3 = q;
      // Coming into S3 from elsewhere (a jump, or back from far away): the flood is where this crossing has
      // it, not replayed or unwound in front of the visitor.
      if (!wasS3 && floodHold == null) { flood.on = q >= SHUTTERS.flood; flood.v = flood.on ? 1 : 0; }
      wash = blendAt(q);
      if (shutters && wash < 1) {
        const slat = slatLive(a, q);
        if (a + q !== s3Last) { shutters.pose(leafAt(q), slat); s3Last = a + q; }
        const t = s3Sun(q);
        if (Math.abs(t - s3Sunt) > 1e-4) { shutters.setSun(t); s3Sunt = t; }
        floodAt(flood.v, fl);
        // The gold lines brighten as the slats turn (their angle over 65°), then the open window carries it.
        const e = s3Exposure(q, fl), open = s3Open(q, slat);
        shutters.setExposure(e, open, Math.max(slat / SHUTTERS.slatDeg, smoothLeaf(q)));
        shutters.shafts.uniforms.uDensity.value = S3_SHAFTS * (1 + 0.6 * smoothLeaf(q) + 0.4 * fl.peak);
        shutters.shafts.uniforms.uExposure.value = e;
        shutters.motes.uniforms.uBurst.value = fl.peak;
        shutters.U.uMotesA.value = 0.55 + 0.45 * open;
        shutters.U.uTime.value = time;
        shutters.U.uStir.value = stir;
        shutters.sway(time);
        const c = shutters.camera;
        s3Pose(a, q, lastBucket, WIN_Z, s3Cam);
        c.position.set(...s3Cam.pos);
        c.fov = s3Cam.fov;
        c.aspect = view.w / view.h;
        c.lookAt(s3Cam.look[0], s3Cam.look[1], s3Cam.look[2]);
        c.updateMatrixWorld();
        c.updateProjectionMatrix();
        shutters.project(c, focus);
        flare.set(focus.x * 0.5 + 0.5, focus.y * 0.5 + 0.5, S3_FLARE.radius, S3_FLARE.held * fl.held + S3_FLARE.peak * fl.peak);
        glow = flare;
        scene = shutters.scene;
        camera = c;
        passes = shutters.passes;
      } else if (!shutters) wash = 1;
    } else if (inRoom) {
      flight.at(y, path);
      const rest = flight.room(cam, path.s, path.lift);
      // Pointer parallax and the pointer sun belong to the rest pose; they fade as the camera leaves it.
      nudgePose(px.x, py.x, rest);
      nudge.az = -px.x * POINTER_RANGE.az * rest;
      nudge.el = py.x * POINTER_RANGE.el * rest;
      sunState.t = at.passage > 0 ? lerp(SUN_T.heroOut, SUN_T.hole, clamp01(p / PASSAGE.approach[1])) : lerp(SUN_T.rest, SUN_T.heroOut, at.hero);
      sunState.intensity = sunState.rims = lerp(1, cloud.level, rest);
      room.setSun(sunState);
      const near = clamp01((path.s - 1.3) / 0.7);
      room.U.uHaloA.value = lerp(NEAR.haloA[0], NEAR.haloA[1], near);
      room.U.uHaloW.value = lerp(NEAR.haloW[0], NEAR.haloW[1], near * near);
      room.shafts.uniforms.uDensity.value = lerp(NEAR.density[0], NEAR.density[1], near);
      // The light caps follow the hero text as it scrolls over the fixed canvas.
      room.U.uCapShift.value.set(0, y * gl.size.dpr);
      room.U.uTime.value = time;
      const c = Math.min(time / CANOPY_IN, 1);
      room.U.uLeafAmt.value = c * c * (3 - 2 * c);
      room.U.uStir.value = stir;
      scene = room.scene;
      passes = [room.shafts];
    } else if (corridor) {
      const u = clamp01((p - PASSAGE.exit[0]) / (PASSAGE.exit[1] - PASSAGE.exit[0])), n = corridor.specs.length;
      const c = span.corridor;
      at.approach = walking ? clamp01((y - (c.top - c.pre)) / c.pre) : 0;
      at.stage = walking ? clamp01((y - c.top) / c.len) : 0;
      // The way out (journey.js WALK_OUT): the camera leaves the last door from `release` on, looking down to the
      // runner, and past the stage's end the frame washes to the plaster below from the top down, until the next
      // sheet's top nears the top of the frame: the runner fades last, just above the sheet rising over it.
      const ws = washSpan(c.top + c.len, c.next, view.w, view.h), from = c.top + WALK.release * c.len;
      at.out = walking ? clamp01((y - from) / (ws.y0 + ws.len - from)) : 0;
      const gone = walking ? clamp01((y - ws.y0) / ws.len) : 0;
      // Once the stage lets go the frame scrolls up with the page and the image goes with it (a lens shift), so
      // the floor stays just above the sheet that follows it.
      if (walking && u >= 1) walk.pose(corridor.camera, at.approach, at.stage, n, at.out, walkPitch(at.stage), Math.max(y - ws.y0, 0));
      else flight.exit(corridor.camera, u);
      if (gone > 0) {
        wash = inOut(gone);
        washColor = PLASTER;
        // The foot of what is still seen: the sheet's top (its bông gió row half over the frame).
        sweep.set(1, WALK_OUT.feather, clamp01(1 - (c.next - y - EDGE) / view.h), 0);
      } else sweep.x = 0;
      // The leaflets are gone while Little Rose's door is read (scrubbed with the scroll; journey.js leafFade).
      const lf = walking ? leafFade(at.stage, n, corridor.lr) : 1;
      if (lf !== leafLast) { corridor.setLeafFade(lf); leafLast = lf; }
      // Each open door swings at its beat and stays open; held at K4 (stacked cards) they stand open, as in
      // the stills. Little Rose's stays shut whatever is asked (setDoor).
      for (let i = 0; i < n; i++) corridor.setDoor(i, walking ? doorAngle(i, at.stage, n) : DOOR_DEG);
      // The house's life (D0.6.2): fans, leaflets and the tamarinds outside on one props clock. Tier 2 on the
      // clock and the scroll, Tier 1 on the scroll alone (a phone draws nothing new while the page is still);
      // frozen while motion is paused.
      if (!paused) propT = tier >= 2 ? time + y / 300 : y / 300;
      corridor.animate(propT);
      corridor.sway(propT);
      hallWash = wash;
      // The sun along the corridor's clock (D6.4), set only when it moves (setSun allocates colors).
      const st = lerp(WALK.sun[0], WALK.sun[1], at.stage);
      if (Math.abs(st - corridorSun) > 1e-4) { corridor.setSun(st); corridorSun = st; }
      scene = corridor.scene;
      camera = corridor.camera;
      passes = [corridor.shafts];
    }
    wasS3 = inS3;
    const drift = (DUST * (y - span.passage.top)) / view.h;
    hole.set(!inS3 && p > 0 && p < 1 ? wash : 0, 0.25 * Math.sin(drift * 2.1), -11 * drift, 0);
    gate?.begin();
    // No corridor yet (a fast scroll before it was built): the wash holds until it is.
    if (scene) gl.frame(scene, camera, passes, wash, glow, hole, washColor, !inS3 && !inRoom ? sweep : null);
    else gl.frame(room.scene, cam, [], 1, null, inS3 ? null : hole.setX(1));
    gate?.end(now);
  };

  // ---- render on demand (D6.7): only while a world section is in view and the tab is visible;
  // every frame while something moves, 30 fps for the canopy and motes, nothing when paused ----
  let raf = 0, last = 0, lastDraw = 0, inView = true, visible = !document.hidden, paused = Boolean(bus?.state.paused), lost = false, dead = false, live = false, gate = null, covered = false;
  const running = () => live && inView && visible && !lost && !dead;
  const kick = () => { if (!raf && running()) raf = requestAnimationFrame(tick); };
  // A tap or key waiting for the main thread goes first: this frame is skipped, the next one draws (INP).
  const inputPending = () => navigator.scheduling?.isInputPending?.() ?? false;
  function tick(now) {
    raf = 0;
    if (!running()) return;
    if (inputPending()) { raf = requestAnimationFrame(tick); return; }
    const dt = last ? Math.min((now - last) / 1000, 0.1) : 1 / 60;
    last = now;
    let active = dirty || scrollY !== lastY;
    // Idle motion belongs to the rooms at rest (the hero's, and S3's once built); in the flight nothing moves
    // by itself.
    const s3 = inShutters(scrollY) && shutters && blendAt(at.s3 ?? 0) < 1;
    // The corridor's fans, leaflets and tamarinds move by themselves on Tier 2 only (a phone keeps its battery),
    // at the 30 fps idle cadence, while the corridor is on screen and not washed out.
    const hall = !inRoom && !inS3 && corridor && tier >= 2 && washAt(at.passage) < 1 && hallWash < 0.999 && (sections.get(corridorEl) || sections.get(passageEl));
    // A modal over the page (the menu sheet) covers the canvas: nothing moves by itself under it.
    const idle = !paused && !covered && ((inRoom && at.y <= 1) || s3 || hall);
    // S3's flood runs on time once the scroll has armed it (paused: straight to its end, no flood).
    if (inShutters(scrollY)) {
      const sp = span.shutters;
      if (floodHold == null && floodStep(flood, clamp01((scrollY - sp.top) / sp.len), dt, paused)) active = true;
    }
    if (!paused) {
      if (fine) active = stepSpring(px, target.x, dt, SPRING) | stepSpring(py, target.y, dt, SPRING) || active;
      if (idle && inRoom) active = stepCloud(now) || active;
      const w = s3 ? shutters.U.uWind.value : inRoom ? wind : null;
      if (w && Math.abs(stir) > 1e-3) {
        // Scroll wind: motes lift and drift into the room, the shaft dust shimmers.
        w.y += stir * dt * 0.12;
        w.x -= stir * dt * 0.05;
        (s3 ? shutters : room).motes.wrap(w);
        active = true;
      }
      time += dt;
    }
    const measuring = !paused && gate?.measuring;
    if (active || measuring || (idle && now - lastDraw >= 33 && !gl.busy())) {
      draw(now);
      lastDraw = now;
      dirty = false;
    }
    if ((paused || !idle) && !measuring && !dirty && !active) return;
    raf = requestAnimationFrame(tick);
  }

  // ---- inputs ----
  const onPointer = (e) => {
    if (e.pointerType !== "mouse" || paused) return;
    target.x = Math.max(-1, Math.min(1, (e.clientX / innerWidth) * 2 - 1));
    target.y = Math.max(-1, Math.min(1, 1 - (e.clientY / innerHeight) * 2));
    kick();
  };
  const onLeave = () => { target.x = target.y = 0; kick(); };
  const onVelocity = (e) => {
    const v = e.detail?.value ?? 0;
    stir = paused ? 0 : v;
    if (stir) kick();
  };
  const onScroll = () => kick();
  // The menu is a modal <dialog>: while one is open the idle motion (canopy, cloud, motes) stops, so opening and
  // closing it never waits behind a world frame.
  const onDialog = () => { covered = Boolean(document.querySelector("dialog[open]")); dirty = true; kick(); };
  const dialogs = new MutationObserver(onDialog);
  document.querySelectorAll("dialog").forEach((d) => dialogs.observe(d, { attributes: true, attributeFilter: ["open"] }));
  const onVisibility = () => { visible = !document.hidden; last = 0; gate?.gap(); kick(); };
  let pending = 0;
  const onResize = () => { cancelAnimationFrame(pending); pending = requestAnimationFrame(() => { layout(); kick(); }); };
  if (fine) addEventListener("pointermove", onPointer, { passive: true });
  if (fine) document.documentElement.addEventListener("mouseleave", onLeave);
  bus?.addEventListener("velocity", onVelocity);
  addEventListener("scroll", onScroll, { passive: true });
  document.addEventListener("visibilitychange", onVisibility);
  addEventListener("resize", onResize);
  const ro = new ResizeObserver(onResize);
  ro.observe(section ?? canvas);
  if (passageEl) ro.observe(passageEl);
  if (corridorEl) ro.observe(corridorEl);
  if (shuttersEl) ro.observe(shuttersEl);
  // The stage layout comes and goes with the cards' fit (motion/stages.js): the walk follows it.
  const mo = corridorEl ? new MutationObserver(() => { layoutWalk(); dirty = true; kick(); }) : null;
  mo?.observe(corridorEl, { attributes: true, attributeFilter: ["class"] });
  const sections = new Map();
  const io = new IntersectionObserver((entries) => {
    // An unstaged S3 (stacked: its stills cover the section) never needs the canvas.
    for (const e of entries) sections.set(e.target, e.isIntersecting && (e.target !== shuttersEl || staged));
    const was = inView;
    inView = [...sections.values()].some(Boolean);
    // Shown again: draw now, so the frame it shows is this scroll's, not the last set it drew.
    if (inView && !was && running()) { draw(performance.now()); dirty = false; }
    canvas.style.visibility = inView ? "" : "hidden";
    if (!inView) gate?.gap();
    last = 0;
    kick();
  }, { rootMargin: "10% 0px" });
  document.querySelectorAll("[data-world]").forEach((s) => io.observe(s));

  // ---- context loss (D6.7): stills back at once, one restore, then stills for good ----
  let losses = 0, restoreTimer = 0;
  const onLost = (e) => {
    e.preventDefault();
    lost = true;
    cancelAnimationFrame(raf); raf = 0;
    root.classList.remove("world-live");
    if (++losses > 1) return stop("context lost twice");
    restoreTimer = setTimeout(() => stop("context not restored"), 5000);
  };
  const onRestored = async () => {
    clearTimeout(restoreTimer);
    gate?.dispose(); gate = null; // the restore is not a fair measurement
    lost = false;
    layout();
    draw(performance.now());
    await nextFrame();
    if (!lost && !dead) { root.classList.add("world-live"); kick(); }
  };
  canvas.addEventListener("webglcontextlost", onLost);
  canvas.addEventListener("webglcontextrestored", onRestored);

  // ---- live gates (D6.7), skipped when ?tier forces a tier ----
  const onStep = ({ state, p75, final, source }) => {
    console.info(`[world] gate p75 ${p75.toFixed(1)} ms (${source}) → ${state}${final ? " (final)" : ""}`);
    // A tier change swaps the shafts' programs: compiled off the main thread before they draw (D6.7).
    const recompile = () => Promise.all([room, corridor, shutters].filter(Boolean).map((s) => s.compile())).then(() => { dirty = true; kick(); });
    if (state === "t1") { tier = 1; gl.setTier(1); room.setTier(1); corridor?.setTier(1); shutters?.setTier(1); layout(); recompile(); }
    else if (state === "lite") { for (const s of [room, corridor, shutters]) if (s) s.shafts.enabled = false; gl.setTier(1, { lite: true }); layout(); }
    else if (state === "off") stop("frame time over the Tier 1 limit");
  };

  function stop(reason) {
    if (dead) return;
    console.info(`[world] back to stills: ${reason}`);
    handle.dispose();
  }

  let resume = null;
  const handle = {
    setPaused(p) {
      paused = Boolean(p);
      px.v = py.v = 0;
      stir = 0;
      last = 0;
      gate?.gap();
      dirty = true;
      if (!paused) resume?.();
      kick();
    },
    dispose() {
      if (dead) return;
      dead = true;
      resume?.();
      cancelAnimationFrame(raf); cancelAnimationFrame(pending); clearTimeout(restoreTimer);
      removeEventListener("pointermove", onPointer);
      document.documentElement.removeEventListener("mouseleave", onLeave);
      bus?.removeEventListener("velocity", onVelocity);
      bus?.removeEventListener("pause", onPause);
      removeEventListener("scroll", onScroll);
      document.removeEventListener("visibilitychange", onVisibility);
      removeEventListener("resize", onResize);
      ro.disconnect(); io.disconnect(); mo?.disconnect(); dialogs.disconnect();
      canvas.removeEventListener("webglcontextlost", onLost);
      canvas.removeEventListener("webglcontextrestored", onRestored);
      root.classList.remove("world-live");
      canvas.style.visibility = "";
      document.getElementById("world-frame-debug")?.remove();
      gate?.dispose();
      corridor?.dispose();
      shutters?.dispose();
      shuttersEl?.classList.remove("is-live");
      room.dispose();
      gl.dispose();
      worldOff(bus);
    },
  };
  // Until main.js holds the handle, the bus carries the pause.
  const onPause = (e) => handle.setPaused(e.detail.paused);
  bus?.addEventListener("pause", onPause);

  // ---- handoff: fonts in (text rects), shaders compiled, the CSS sun-in over ----
  await document.fonts?.ready;
  layout();
  await room.compile();
  await gl.compile();
  if (bus && bus.state?.sunIn !== "done") {
    await new Promise((ok) => {
      const t = setTimeout(ok, 4000);
      bus.addEventListener("sun-in-end", () => { clearTimeout(t); ok(); }, { once: true });
    });
  }
  // A visitor who paused keeps the stills: no crossfade, nothing new moving, until motion is back on.
  if (paused && !dead) await new Promise((ok) => (resume = () => { resume = null; ok(); }));
  if (dead) return handle;
  layout(); // the sun-in may have run while the fonts settled
  draw(performance.now());
  const info = gl.renderer.info.render;
  console.info(`[world] room · tier ${tier} (${probed.reason}; ${probed.gpu}) · ${info.calls} draw calls · ${info.triangles} triangles · dpr ${gl.size.dpr} · canvas ${Math.round(view.w * gl.size.dpr)}×${Math.round(view.h * gl.size.dpr)} · ${gl.msaa ? "4x MSAA" : "no MSAA"} · ~${gl.memoryMB} MB · shafts ${room.shafts.size.join("×")} · motes ${room.motes.count} · parallax ${parallax}${lastFrame.ok ? (lastFrame.still ? " · framed as the still" : " · framed live (the still would touch text)") : " · text-safe framing not found"} · flight held ${solved.held}/${solved.rows}, carried ${solved.carried}`);
  await nextFrame();
  if (dead || lost) return handle;
  root.classList.add("world-live");
  live = true;
  if (forced == null) gate = createGate(gl.renderer.getContext(), { tier, onStep });
  kick();

  // QA hooks (?debug): their own chunk, never loaded otherwise.
  if (debug) import("./debug.js").then((d) => d.attach({ gl, room, corridor: () => corridor, tier: () => tier, frame: () => lastFrame, solved: () => solved, at, path, span, inRoom: () => inRoom, walking: () => walking, walk, flight, section, parallax: () => parallax, shutters: () => ({ staged, built: Boolean(shutters), at: at.s3 ?? null, flood: { ...flood }, span: { ...span.shutters } }), hold: (v) => { floodHold = v; if (v != null) Object.assign(flood, { on: true, v }); dirty = true; kick(); }, redraw: () => draw(performance.now()), paused: () => paused, running, pointer: (x, y) => { target.x = px.x = x; target.y = py.x = y; px.v = py.v = 0; dirty = true; kick(); } }));

  // The corridor, in idle time after the handoff, from the page's own cards (D6.5). It is needed first
  // behind the wash halfway through the passage; until it is ready the wash simply holds.
  const idle = () => new Promise((ok) => ("requestIdleCallback" in window ? requestIdleCallback(ok, { timeout: 1500 }) : setTimeout(ok, 50)));
  // No cards still builds the corridor, empty: the camera always has somewhere to come out.
  idle().then(async () => {
    if (dead) return;
    const { buildCorridor } = await import("./sets/corridor.js");
    if (dead) return;
    let slice = 0;
    const t0 = performance.now();
    const c = await buildCorridor({ gl, tier, beats, textures: room.textures, traces: Boolean(corridorEl?.hasAttribute("data-traces")), onSlice: (ms) => { slice = Math.max(slice, ms); } });
    const baked = performance.now() - t0;
    c.setTier(tier);
    c.shafts.setSize(Math.round(view.w * gl.size.dpr), Math.round(view.h * gl.size.dpr));
    await c.compile();
    if (dead) return c.dispose();
    corridor = c;
    dirty = true;
    kick();
    console.info(`[world] corridor · ${beats.length} doors (${beats.map((b) => `${b.unit} ${b.door}`).join(", ")}) · gạch bông baked ${c.bakedInWorker ? "in a worker" : "on the main thread"} in ${Math.round(baked)} ms, longest main-thread slice ${slice.toFixed(1)} ms`);
  }).catch((err) => console.warn("[world] corridor unavailable, the wash holds", err))
    // S3 next, in its own idle slice: it is needed only past the sheets. Until it is built, the section shows
    // its stills (it has no is-live).
    .then(() => (shuttersEl && !dead ? idle() : null))
    .then(async () => {
      if (!shuttersEl || dead) return;
      const { buildShutters } = await import("./sets/shutters.js");
      if (dead) return;
      const s = await buildShutters({ gl, tier, textures: room.textures });
      s.shafts.setSize(Math.round(view.w * gl.size.dpr), Math.round(view.h * gl.size.dpr));
      s.U.uPx.value = gl.size.dpr;
      await s.compile();
      if (dead) return s.dispose();
      shutters = s;
      shuttersEl.classList.add("is-live");
      dirty = true;
      kick();
      console.info("[world] shutters ready");
    }).catch((err) => console.warn("[world] shutters unavailable, the stills stay", err));
  return handle;
}
