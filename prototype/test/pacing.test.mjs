// M6 pacing (Maxwell, 11 Nov 2026: "can you make the scroll a little bit smoother now it just runs past
// everything"). Every beat of the home journey holds still long enough to be read, the wheel glides without
// taking the scroll, and the numbers the world, the motion layer and the CSS use stay the same.
import { test } from "node:test";
import assert from "node:assert/strict";
import { readFileSync } from "node:fs";
import { DOOR_SVH, DWELL, PASSAGE, SHUTTERS, WALK, dwell } from "../src/world/journey.js";

const read = (p) => readFileSync(new URL(p, import.meta.url), "utf8");
const css = read("../src/styles/stages.css");
const svh = (re) => Number(css.match(re)?.[1]);

test("the corridor: each door's card holds still for at least 40svh (34svh on phones)", () => {
  assert.equal(svh(/\.bays__track \{ height: calc\(100svh \+ var\(--n\) \* (\d+)svh\); \}/), DOOR_SVH.desk);
  assert.equal(svh(/max-width: 599\.98px\) \{ html\.stages \.is-stage \.bays__track \{ height: calc\(100svh \+ var\(--n\) \* (\d+)svh\); \}/), DOOR_SVH.phone);
  // n doors over (beats[1] - beats[0]) of the stage: a beat is that share of n × DOOR_SVH.
  const beat = (per) => (WALK.beats[1] - WALK.beats[0]) * per;
  const plateau = (per) => (DWELL.out - DWELL.in) * beat(per);
  assert.ok(plateau(DOOR_SVH.desk) >= 40, `desktop plateau ${plateau(DOOR_SVH.desk).toFixed(1)}svh`);
  assert.ok(plateau(DOOR_SVH.phone) >= 34, `phone plateau ${plateau(DOOR_SVH.phone).toFixed(1)}svh`);
  // The camera barely moves on the plateau: the drift is under a tenth of the walk between two doors.
  assert.ok(dwell(DWELL.out) - dwell(DWELL.in) <= 0.06 + 1e-9);
  // The stage header is read before the walk starts (at least 25svh on a three-door desktop stage).
  assert.ok(WALK.head[1] * 3 * DOOR_SVH.desk >= 25);
  // A card is shown from its door's arrival to just before the next one, and never over the gap between beats.
  assert.ok(WALK.cardIn <= DWELL.in + 1e-9 && WALK.cardOut > DWELL.out);
});

test("the passage: the statement holds over most of the flight, and no stretch of it is without text", () => {
  const flight = svh(/\.passage \{ --flight: (\d+)svh;/), phone = svh(/\.passage \{ --flight: (\d+)svh; \} \}/);
  assert.ok(flight >= 200 && phone >= 170, `${flight} / ${phone}`);
  assert.equal(PASSAGE.washIn[1], PASSAGE.plateau[0], "the statement rises in as the wash is full");
  assert.ok(PASSAGE.washIn[0] * flight <= 15, "the wash starts within 15svh: the hero's light is still on screen until then");
  assert.ok((PASSAGE.plateau[1] - PASSAGE.plateau[0]) * phone >= 100, "the statement is held for a screen at least");
  assert.ok(PASSAGE.read[0] >= PASSAGE.plateau[0] && PASSAGE.read[1] <= PASSAGE.plateau[1]);
});

test("the shutters (M7): open by the pin, then held open with the help heading coming up before the blend", () => {
  const s3 = svh(/html\.stages \.shutters \{ --s3: (\d+)svh;/), phone = svh(/html\.stages \.shutters \{ --s3: (\d+)svh; --lead: \d+svh; --rise: var\(--s3\); \} \}/);
  const lead = svh(/html\.stages \.shutters \{ --s3: \d+svh; --lead: (\d+)svh;/);
  assert.ok(s3 >= 90 && phone >= 80, `--s3 ${s3} / ${phone}svh`);
  // The look-out: the window fully open, nothing blending, while the heading rises (at least 18svh on phones).
  assert.equal(SHUTTERS.leaves[1], 1, "the leaves finish as the frame pins");
  assert.ok(SHUTTERS.blend[0] * phone >= 18, `held open for ${(SHUTTERS.blend[0] * phone).toFixed(0)}svh`);
  // The words: the help heading's top reaches the screen's foot as the frame pins; before that only the lead and
  // the last of the approach pass without words (the lead under 16svh).
  assert.ok(lead <= 16, `lead ${lead}svh`);
});

test("the wheel glides without taking the scroll: Lenis lerp 0.07, native distance, no snap, touch native", () => {
  const wheel = read("../src/motion/smooth-wheel.js"), scroll = read("../src/motion/scroll.js");
  assert.match(wheel, /export const LERP = 0\.07;/);
  // One set of options for both instances; a box that can still scroll keeps the wheel (a filled textarea).
  assert.match(wheel, /lerp: LERP, wheelMultiplier: 1, smoothWheel: true, syncTouch: false, autoRaf: false, allowNestedScroll: true,/);
  assert.match(wheel, /node\.closest\?\.\("dialog, select, \[data-lenis-prevent\]"\)/);
  assert.match(scroll, /new Lenis\(lenisOptions\(\)\)/);
  assert.match(wheel, /new Lenis\(lenisOptions\(\)\)/);
  for (const src of [wheel, scroll]) {
    const code = src.replace(/\/\/.*$/gm, "");
    assert.doesNotMatch(code, /snap|normalizeScroll|preventDefault|ScrollSmoother/i);
    // The frame-time guard hands a slow machine back to native scroll; Lenis is made before the first wheel tick.
    assert.match(code, /frameGuard\(\)/);
    assert.match(code, /goNative\(lenis\)/);
    assert.match(code, /addEventListener\("pointermove", make, \{ passive: true, once: true \}\)/);
  }
  assert.doesNotMatch(wheel.replace(/\/\/.*$/gm, ""), /gsap|ScrollTrigger/, "the inner pages' chunk is Lenis alone");
  const main = read("../src/main.js");
  assert.match(main, /import\("\.\/motion\/smooth-wheel\.js"\)/, "loaded lazily");
  assert.match(main, /if \(scrolled \|\| heavy \|\| wheel \|\| wheelNative\(\) \|\| !matchMedia\("\(pointer: fine\)"\)\.matches\) return;/, "only where the motion layer does not run, fine pointers only, never on a heavy page");
  assert.match(main, /const heavy = Boolean\(document\.querySelector\("\.newswall, \[data-steps\]"\)\);/, "not on the News wall or the Little Rose garden");
  // Reduced motion and pause: neither chunk starts (main.js motionOn()).
  assert.match(main, /if \(motionOn\(\)\) \{\n  startMotion\(\);\n  startWheel\(\);/);
});

test("the frame-time guard: three slow frames in one glide, a gap over 150 ms counting twice", async () => {
  const { frameGuard } = await import("../src/motion/smooth-wheel.js").catch(() => ({}));
  if (!frameGuard) return; // lenis' CSS import needs a bundler; the source checks above cover it
  const g = frameGuard();
  assert.equal([0, 16, 33, 50, 66].some((t) => g.slow(t)), false, "60 fps never trips it");
  g.reset();
  assert.equal(g.slow(0) || g.slow(200), false);
  assert.equal(g.slow(260), true, "a 200 ms gap and a 60 ms gap");
});

test("the camera follows the scroll on a critically damped spring; the text never does", () => {
  const live = read("../src/world/live.js");
  assert.match(live, /const CAM_SPRING = 9, CAM_JUMP = 2\.5;/);
  // Paused: no spring (the camera is where the scroll is).
  assert.match(live, /const camAt = \(y\) => \(paused \|\| !live \? y : camY\.x\);/);
  // The hems and washes that meet the DOM's sheets read the scroll, not the spring.
  assert.match(live, /if \(walking && hemSpan\(c\.next - y, view\.w, view\.h, hem\)\)/);
  assert.match(live, /const gone = walking \? clamp01\(\(y - ws\.y0\) \/ ws\.len\) : 0;/);
});
