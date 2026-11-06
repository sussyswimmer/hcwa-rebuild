import { Euler, Quaternion, Vector3 } from "three";
import { createRenderer } from "./renderer.js";
import { createGate } from "./tiers.js";
import { buildRoom, FRAME_SHAPES } from "./sets/room.js";
import { bucketFor } from "./buckets.js";
import { applyFrame, clearAt, clearTurned, drawFrameOverlay, edgePads, frameOrigin, readBox, readTextRects, solveFrame, toFrame } from "./frame.js";
import { POSTER_FRAMES } from "./poster-frames.js";
import { setCaps } from "./materials/caps.js";
import { POINTER_RANGE, REST } from "./clock.js";
import { bezier } from "./tokens.js";
import { cubicBezier, stepSpring } from "./ease.js";
import { worldOff } from "./index.js";

// The live world (05-DESIGN D6, PLAN H2), with three.js. index.js loads it only once the GPU
// probe has passed. It draws into the page's canvas, reads the DOM (frame box, stage, text rects)
// and never writes text. html.world-live is added once the first frame matches the poster's rest
// pose; the page's CSS then fades the stills out. Whatever fails, the stills stay: that is the
// complete page.

const SPRING = 6; // rad/s: the pointer sun and the camera's parallax (D5.1)
const CANOPY_IN = 2.5; // s of world time for the canopy to fade in after the handoff (glsl LEAF)
const CLOUD = { dip: 0.82, down: 1600, up: 2400, min: 14000, max: 22000, first: [3500, 5000] };
// The hero scrolling out moves the sun clock (D5.3: t 0.08 → 0.14 over 0 → 100svh), so the light
// travels down the wall as the visitor scrolls. Day 4's K0 → K1 camera path replaces this.
const SCROLL_T = [REST.room, 0.14];
// Pointer parallax: the camera trucks 5 cm and turns 0.8° / 0.4°, an orbit about a point ~3.6 m in
// front, so the screen's near edge slides against the back wall (a depth cue a still cannot give).
// Scaled down per layout until every pointer extreme keeps the hero text clear.
const PARALLAX = { truck: 0.05, yaw: (0.8 * Math.PI) / 180, pitch: (0.4 * Math.PI) / 180 };
const inOut = cubicBezier(bezier.inOut);
const nextFrame = () => new Promise((ok) => requestAnimationFrame(ok));

export async function live({ canvas, stage, bus, probed, forced }) {
  const root = document.documentElement;
  const debug = new URLSearchParams(location.search).get("debug");
  let tier = probed.tier;
  const gl = createRenderer(canvas, { context: probed.gl, tier });
  const room = await buildRoom({ gl, tier });
  const section = stage?.closest("[data-world]") ?? document.querySelector('[data-world="room"]');
  const passes = [room.shafts];
  const cam = room.camera;

  // ---- framing: exactly the poster's frame, as the browser shows it in the hero box ----
  // The bucket is the one the <picture> media queries pick (viewport aspect); the poster frame
  // covers the box, centred across and anchored to the bottom (frame.js frameOrigin). The solver
  // runs in poster pixels (same rects, same answer as render-posters), and the canvas shows that
  // frame extended. The canvas scrolls with the page (world.css), so document px are canvas px.
  const box = { x: 0, y: 0, w: 1, h: 1 }, view = { w: 1, h: 1 }, frameRect = { x: 0, y: 0, w: 1, h: 1 };
  const basePos = new Vector3(), baseQ = new Quaternion(), right = new Vector3(), turn = new Quaternion(), euler = new Euler(0, 0, 0, "YXZ");
  let lastFrame = null, dirty = true, parallax = 1;
  // pose(x, y): the camera at pointer (x, y) in −1..1, scaled by the layout's parallax.
  const pose = (x, y) => {
    cam.position.copy(basePos).addScaledVector(right, x * PARALLAX.truck * parallax);
    cam.quaternion.copy(baseQ).multiply(turn.setFromEuler(euler.set(y * PARALLAX.pitch * parallax, x * PARALLAX.yaw * parallax, 0)));
    cam.updateMatrixWorld();
  };
  const layout = () => {
    Object.assign(box, readBox(section) ?? { x: 0, y: 0, w: innerWidth, h: innerHeight });
    // The canvas must reach the hero's bottom edge, which sits below the first screen when the hero
    // keeps its 620 px minimum (phones held sideways, short laptop windows).
    const reach = Math.ceil(box.y + box.h);
    canvas.style.minHeight = reach > innerHeight ? reach + "px" : "";
    const w = canvas.clientWidth || innerWidth, h = canvas.clientHeight || innerHeight;
    gl.resize(w, h);
    const { dpr } = gl.size;
    room.shafts.setSize(Math.round(w * dpr), Math.round(h * dpr));
    view.w = w; view.h = h;
    const bucket = bucketFor(root.clientWidth || w, root.clientHeight || h), [pw, ph] = bucket.view;
    const k = Math.max(box.w / pw, box.h / ph), o = frameOrigin(box, bucket.view, k);
    Object.assign(frameRect, { x: box.x + o.x, y: box.y + o.y, w: pw * k, h: ph * k });
    room.setShot(bucket, pw, ph);
    basePos.copy(cam.position); baseQ.copy(cam.quaternion); right.set(1, 0, 0).applyQuaternion(baseQ);
    const rects = readTextRects();
    const inFrame = edgePads(toFrame(rects, box, bucket.view), bucket.name);
    lastFrame = solveFrame({ camera: cam, width: pw, height: ph, rects: inFrame, ...FRAME_SHAPES, stage: stage ? toFrame(readBox(stage), box, bucket.view) : null });
    // The still's own shift while it keeps this layout's text clear (it was solved over every layout of
    // its bucket): then the handoff lands on the still exactly, its text caps included.
    const still = POSTER_FRAMES[bucket.name];
    const useStill = still && clearAt(lastFrame, still.sx, still.sy);
    if (useStill) Object.assign(lastFrame, { sx: still.sx, sy: still.sy, still: true });
    // Pointer parallax: the largest share of it that keeps every extreme clear.
    parallax = 0;
    for (const s of [1, 0.5, 0.25]) {
      parallax = s;
      if ([[-1, -1], [1, -1], [-1, 1], [1, 1]].every(([x, y]) => (pose(x, y), clearTurned(lastFrame, cam, FRAME_SHAPES, pw, ph)))) break;
      parallax = 0;
    }
    pose(0, 0);
    applyFrame(cam, frameRect, view, { sx: lastFrame.sx * k, sy: lastFrame.sy * k });
    const caps = useStill && still.caps ? still.caps.map((r) => ({ ...r, x: frameRect.x + r.x * k, y: frameRect.y + r.y * k, w: r.w * k, h: r.h * k })) : rects;
    setCaps(room.U, caps, { h, dpr });
    room.U.uPx.value = dpr;
    if (debug === "frame") drawFrameOverlay(lastFrame, frameRect, view, k);
    dirty = true;
  };

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

  const sunState = { t: REST.room, nudge, intensity: 1, rims: 1 }; // reused: no per-frame garbage
  const onProgress = (e) => {
    if (e.detail?.world !== "room") return;
    const p = Math.min(Math.max(e.detail.progress, 0), 1);
    const t = SCROLL_T[0] + (SCROLL_T[1] - SCROLL_T[0]) * p;
    if (t !== sunState.t) { sunState.t = t; dirty = true; kick(); }
  };
  const draw = (now) => {
    nudge.az = -px.x * POINTER_RANGE.az;
    nudge.el = py.x * POINTER_RANGE.el;
    sunState.intensity = sunState.rims = cloud.level;
    room.setSun(sunState);
    pose(px.x, py.x);
    room.U.uTime.value = time;
    const c = Math.min(time / CANOPY_IN, 1);
    room.U.uLeafAmt.value = c * c * (3 - 2 * c);
    room.U.uStir.value = stir;
    gate?.begin();
    gl.frame(room.scene, cam, passes);
    gate?.end(now);
  };

  // ---- render on demand (D6.7): only while a world section is in view and the tab is visible;
  // every frame while something moves, 30 fps for the canopy and motes, nothing when paused ----
  let raf = 0, last = 0, lastDraw = 0, inView = true, visible = !document.hidden, paused = Boolean(bus?.state.paused), lost = false, dead = false, live = false, gate = null;
  const running = () => live && inView && visible && !lost && !dead;
  const kick = () => { if (!raf && running()) raf = requestAnimationFrame(tick); };
  function tick(now) {
    raf = 0;
    if (!running()) return;
    const dt = last ? Math.min((now - last) / 1000, 0.1) : 1 / 60;
    last = now;
    let active = dirty;
    if (!paused) {
      if (fine) active = stepSpring(px, target.x, dt, SPRING) | stepSpring(py, target.y, dt, SPRING) || active;
      active = stepCloud(now) || active;
      if (Math.abs(stir) > 1e-3) {
        // Scroll wind: motes lift and drift into the room, the shaft dust shimmers.
        wind.y += stir * dt * 0.12;
        wind.x -= stir * dt * 0.05;
        room.motes.wrap(wind);
        active = true;
      }
      time += dt;
    }
    const measuring = !paused && gate?.measuring;
    if (active || measuring || (!paused && now - lastDraw >= 33)) {
      draw(now);
      lastDraw = now;
      dirty = false;
    }
    if (paused && !measuring && !dirty) return;
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
  const onVisibility = () => { visible = !document.hidden; last = 0; gate?.gap(); kick(); };
  let pending = 0;
  const onResize = () => { cancelAnimationFrame(pending); pending = requestAnimationFrame(() => { layout(); kick(); }); };
  if (fine) addEventListener("pointermove", onPointer, { passive: true });
  if (fine) document.documentElement.addEventListener("mouseleave", onLeave);
  bus?.addEventListener("velocity", onVelocity);
  bus?.addEventListener("progress", onProgress);
  document.addEventListener("visibilitychange", onVisibility);
  addEventListener("resize", onResize);
  const ro = new ResizeObserver(onResize);
  ro.observe(section ?? canvas);
  const sections = new Map();
  const io = new IntersectionObserver((entries) => {
    for (const e of entries) sections.set(e.target, e.isIntersecting);
    inView = [...sections.values()].some(Boolean);
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
    if (state === "t1") { tier = 1; gl.setTier(1); room.setTier(1); layout(); }
    else if (state === "lite") { room.shafts.enabled = false; gl.setTier(1, { lite: true }); layout(); }
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
      bus?.removeEventListener("progress", onProgress);
      bus?.removeEventListener("pause", onPause);
      document.removeEventListener("visibilitychange", onVisibility);
      removeEventListener("resize", onResize);
      ro.disconnect(); io.disconnect();
      canvas.removeEventListener("webglcontextlost", onLost);
      canvas.removeEventListener("webglcontextrestored", onRestored);
      root.classList.remove("world-live");
      canvas.style.visibility = canvas.style.minHeight = "";
      document.getElementById("world-frame-debug")?.remove();
      gate?.dispose();
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
  if (bus && bus.state?.sunIn !== "done") {
    await new Promise((ok) => {
      const t = setTimeout(ok, 4000);
      bus.addEventListener("sun-in-end", () => { clearTimeout(t); ok(); }, { once: true });
    });
  }
  // A visitor who paused keeps the stills: no crossfade, nothing new moving, until motion is back on.
  if (paused && !dead) await new Promise((ok) => (resume = () => { resume = null; ok(); }));
  if (dead) return handle;
  if (bus?.state.progress.room != null) onProgress({ detail: { world: "room", progress: bus.state.progress.room } });
  layout(); // the sun-in may have run while the fonts settled
  draw(performance.now());
  const info = gl.renderer.info.render;
  console.info(`[world] room · tier ${tier} (${probed.reason}; ${probed.gpu}) · ${info.calls} draw calls · ${info.triangles} triangles · dpr ${gl.size.dpr} · canvas ${Math.round(view.w * gl.size.dpr)}×${Math.round(view.h * gl.size.dpr)} · ${gl.msaa ? "4x MSAA" : "no MSAA"} · ~${gl.memoryMB} MB · shafts ${room.shafts.size.join("×")} · motes ${room.motes.count} · parallax ${parallax}${lastFrame.ok ? (lastFrame.still ? " · framed as the still" : " · framed live (the still would touch text)") : " · text-safe framing not found"}`);
  await nextFrame();
  if (dead || lost) return handle;
  root.classList.add("world-live");
  live = true;
  if (forced == null) gate = createGate(gl.renderer.getContext(), { tier, onStep });
  if (debug) {
    const ext = gl.renderer.getContext().getExtension("WEBGL_lose_context");
    window.__hcwaWorld = { room, tier: () => tier, frame: () => lastFrame, parallax: () => parallax, memoryMB: () => gl.memoryMB, msaa: gl.msaa, info: () => ({ ...gl.renderer.info.render }), redraw: () => draw(performance.now()), lose: () => ext?.loseContext(), restore: () => ext?.restoreContext(), paused: () => paused, running, pointer: (x, y) => { target.x = px.x = x; target.y = py.x = y; px.v = py.v = 0; dirty = true; kick(); } };
  }
  kick();
  return handle;
}
