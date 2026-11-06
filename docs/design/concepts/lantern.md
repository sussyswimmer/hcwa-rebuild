# Rước đèn / The Lantern Walk

Art direction concept for the HCWA rebuild, one of three competing directions.
Status: proposal for Maxwell, 6 Nov 2026. Nothing here is approved by HCWA. Every new
line of copy below is marked `[DRAFT: ...]`.

Evidence produced while writing this (all reproducible, scripts in the session scratchpad,
numbers quoted below):

- `lantern/hero-type-test.png`: the hero line set in the chosen fonts at 390px and 1440px.
- `lantern/lantern-proportion-check.png`: a flat software render of the procedural lantern
  geometry in section 3, to check proportions before anyone opens three.js.

---

## 0. What we are replacing

The Phase 1 prototype (`qa/phase1-screens/home-vi-*`) is correct but anonymous:

1. The first screen is half grey box (`A2`, 4:5 placeholder). The most valuable pixels on
   the site say "Ảnh chính trang chủ · 4:5".
2. It is the default "warm NGO" kit: cream paper, Fraunces, pill buttons, 4px colored top
   bands, left H2 with a "See all" link on the right, repeated eight times. Swap the logo
   and it is any charity anywhere.
3. Dashed draft outlines on almost every text block make it read as unfinished.
4. No point of view and no motion. Nothing on the page could only belong to HCWA.

The fix is not decoration on top of the same layout. It is a world: one object, one
light source, one walk through one city, that only this organization could own.

---

## 1. Name, pitch, and why only HCWA

**Name:** Rước đèn (The Lantern Walk).

**Pitch:** A hand-made star lantern lights up as the page opens and is carried, by the
visitor's own scroll, through a paper-cut night Saigon to the three places HCWA keeps lit
for children, until the sky turns to morning: the page gets *tươi sáng hơn*, brighter,
as you read it.

**Why it fits HCWA and would be wrong for anyone else**

- **Tết Trung Thu is Vietnam's children's festival**, and the đèn ông sao (five-pointed
  star lantern of bamboo and cellophane) is the object almost every Vietnamese adult
  remembers carrying as a child. Local donors recognize it in half a second; international
  partners get an image that no other NGO in their portfolio uses. For an adult-focused
  charity it would be wrong (it is a children's object), for a Western charity it would be
  appropriation, and for a Hanoi organization the city would be wrong: this one is built
  from Saigon tube houses, tangled power lines, after-rain streets and a river with
  bridges.
- **Safeguarding becomes the art direction, not a constraint on it.** We never show the
  child, only the light the child carries. The lantern's stick always enters from the
  edge of the frame, held by someone just out of view. That is an honest way to show
  children with zero photos, and it makes Little Rose residents unidentifiable by
  construction. When approved photos arrive (`10-ASSET-REQUEST.md`), they go into inner
  pages and news; the hero stays the lantern.
- **The lantern belongs to the child.** HCWA walks beside them. This matches the draft H1,
  which already starts with *Cùng trẻ em* ("together with children"), and keeps the light
  metaphor away from "rescue" or poverty framing.
- **The words already speak light.** HCWA's own line is "Helping children build safer,
  brighter futures" (*tương lai an toàn và tươi sáng hơn*). The scroll makes the page
  literally brighter, night to morning.
- **The units map onto the walk:** the lantern frame is bamboo (Tre Xanh / Green Bamboo,
  green lanterns in a bamboo grove); a single warm window with a cut-paper rose (Hoa Hồng
  Nhỏ / Little Rose, rose lanterns, no building, no door, no address); a bridge of lights
  (Cầu Hàn / Cau Han: *cầu* means bridge).
- **The brand colors map onto the light:** the HCWA logo blue becomes the night; the HCWA
  sun-yellow becomes the candle flame. The Donate button is the only other thing on screen
  in flame color.

---

## 2. Signature moments (three, no more)

### 2.1 Thắp đèn: the lantern lights (hero, on load)

What the visitor sees: a dark indigo night. The headline, intro and buttons are already
there (they are the LCP and never animate in). Upper right on desktop, top band on mobile,
an unlit paper star hangs from a bamboo stick that enters from the bottom-left edge of the
frame. A spark appears inside it, the cellophane catches and flickers, holds, and settles
into a steady ember-red glow with a gold core; a warm halo opens around it; the headline
warms from cool moonlit lavender to candle-lit cream. Then the lantern starts to turn on
its string, its tassels swaying.

Trigger and timeline (first page load; skipped on back/forward cache restore,
`pageshow.persisted === true`, where the lit state shows at once):

| t (ms) | What | Property | Duration | Easing |
|---|---|---|---|---|
| 0 | First paint: H1, intro, buttons in final position; inline SVG lantern, unlit layer | none | 0 | none |
| 250 | Spark: flame glyph inside the SVG | `opacity 0→1`, `scale .6→1` (origin flame base) | 180 | `--ease-out` |
| 250 | Ignition: lit SVG layer over unlit | `opacity` keyframes 0 / .55 @18% / .40 @24% / .80 @40% / .70 @52% / 1 @100% | 1200 | `linear` between stops (the stops are the flicker) |
| 250 | Halo | `opacity 0→.9`, `scale .6→1` | 1600 | `--ease-glow` |
| 400 | Headline warms | `color: var(--color-lit-300) → var(--color-lit-50)` (11.35:1 → 16.52:1, readable throughout) | 1200 | `--ease-out` |
| 1600 | Ambient sway (CSS, SVG poster) | `rotate ±2.2deg` around the string point | 5600 loop | `ease-in-out`, alternate |
| when ready | WebGL takes over (section 3.6) | canvas `opacity 0→1`, SVG `1→0` | 600 | `--ease-out` |

The ignition is done in SVG + CSS on purpose: it runs at first paint on every device,
costs no JavaScript, and is identical with or without WebGL. The 3D takes over silently
afterwards with the same pose and the same lit state, and only then does the lantern
begin to turn in real 3D (section 3.2). Reduced motion: the lit SVG shows immediately,
no flicker, no sway, headline in `--color-lit-50` from the start.

### 2.2 Rước đèn: the procession (Desire, pinned)

What the visitor sees: as the hero scrolls away, the lantern drifts down and to the left
and becomes the head of a line of lanterns walking at child height through a paper-cut
city at night, wet street reflecting every light. The camera follows the walk to three
stops, while a text panel for each center slides in:

1. **Green Bamboo:** the walk enters a bamboo grove; lanterns near it shift to bamboo green;
   culms sway.
2. **Little Rose:** a single warm window in a paper wall, a cut-paper rose inside, pink
   light. Rose-colored lanterns pass in front of it and keep walking. No figures, no door,
   no house number, no street.
3. **Cau Han:** the procession climbs onto an arched bridge strung with blue lights over a
   dark river; the reflections stretch in the water.

Trigger: ScrollTrigger on `#procession`, `pin: true`, `start: "top top"`,
`end: "+=320%"` (desktop) / `"+=260%"` (mobile), `scrub: 0.8` (0.8s catch-up smoothing; the
page still scrolls at native speed). No `snap` (snapping moves the page after the user
stops, which is scroll hijacking).

Timeline (progress p of the pin):

| p | Beat | Panel (DOM) | Camera |
|---|---|---|---|
| 0 → .08 | Intro: H2 "Cơ sở trực thuộc" + one-line intro | intro panel in | K1 street |
| .08 → .36 | Green Bamboo | panel 1 in at .08, out at .32 | K1 → K2 |
| .36 → .66 | Little Rose | panel 2 in at .36, out at .62 | K2 → K3 |
| .66 → .94 | Cau Han | panel 3 in at .66, out at .92 | K3 → K4 |
| .94 → 1 | Release; dawn begins underneath (2.3) | none | K4 → K5 starts |

Each panel tween lasts .04 of progress: in `opacity 0→1, y 24px→0`, `power2.out`; out
`opacity →0, y →-16px`, `power2.in`. Within each beat the camera uses **dwell easing** so
the scene holds still while the visitor reads:

```js
// u: 0..1 inside one beat → camera travel 0..1 inside that beat
const sOut = gsap.parseEase("sine.out"), sIn = gsap.parseEase("sine.in");
export const dwell = (u) =>
  u < 0.22 ? 0.45 * sOut(u / 0.22) :
  u < 0.78 ? 0.45 + 0.10 * ((u - 0.22) / 0.56) :   // slow drift while text is up
             0.55 + 0.45 * sIn((u - 0.78) / 0.22);
```

At a normal reading scroll the sequence takes 12 to 20 seconds. A progress rail (three
center names, each a real link that scrolls to its beat) sits at the right edge on desktop
and the top of the panel on mobile. A "skip" link precedes the pin.

### 2.3 Bình minh: dawn (Action)

What the visitor sees: right after the bridge, a sheet of warm paper rises over the night
with a cut, deckled top edge. Behind that edge the sky scrubs from indigo through ember and
gold to morning; the lanterns dim to 40% as the light comes up; the moon slides down behind
the skyline. Everything practical (programs, news, ways to help, donate, contact) lives on
that paper, in the clear day palette.

Trigger: ScrollTrigger on `#dawn`, `start: "top bottom"`, `end: "top 15%"`,
`scrub: true` (direct). One viewport of scroll. Sky uniform `uDawn 0→1` with
`power1.inOut`; lantern emissive `×(1 - .6·uDawn)` with `power2.in` (they hold their light
until late). The same progress writes a CSS custom property `--dawn` on `<html>` so the
DOM-only fallback (a gradient band above the paper edge) follows the same curve. When the
paper sheet covers the viewport the render loop stops.

Text is never placed on the mid-tones of the transition: text sits on night (light text)
or on paper (dark text), and the gradient band between them carries no text. That keeps
every text pair at its measured contrast during the scrub.

---

## 3. The 3D

### 3.1 Principles

- One `WebGLRenderer`, one canvas, `position: fixed; inset: 0; z-index: 0;
  pointer-events: none; aria-hidden="true"`, behind night sections only. Day sections
  have opaque paper backgrounds that cover it.
- No three.js lights, no shadow maps, no post-processing. Every surface is a small custom
  `ShaderMaterial` that computes its own candle light. Bloom is faked with camera-facing
  additive halo quads. That is the main reason this runs on a mid-range Android.
- No GLB, no image textures in v1. Everything is procedural, generated from one seeded RNG
  (`mulberry32(20261106)`) so the city is identical on every load and matches the SVG
  posters that the same code generates at build time.
- `renderer.outputColorSpace = SRGBColorSpace`, `toneMapping = NoToneMapping`; token hex
  values are converted to linear once (`new Color(hex)` handles this); every
  `ShaderMaterial` ends with `#include <colorspace_fragment>`.
- Units: 1 = 1 meter. Lantern held at 1.0 to 1.3 m (child height).

### 3.2 Objects and how each is built

**A. The star lantern** (`scene/lantern.js`, geometry in `lib/lantern-geometry.mjs`,
shared with the SVG poster generator). Local radius R = 1, world scale 0.20 for the hero,
0.16 to 0.22 in the procession.

| Part | Geometry | Triangles | Material |
|---|---|---|---|
| Skin (cellophane) | Non-indexed `BufferGeometry`, flat normals. 5 tips at radius 1, z 0, angles 90° + 72°·i. 5 inner notch vertices at radius 0.382 (pentagram ratio), angles 126° + 72°·i, z ±0.10. Apex at z ±0.30. Per side: 10 facets apex→tip→notch. Plus 10 side walls tip→notch(front)→notch(back). | 30 | `cellophane` shader (below) |
| Bamboo frame | 20 outline sticks (tip↔notch, both faces) + 5 spacers (notch front↔back): `CylinderGeometry(0.012, 0.012, len, 5, 1, true)` merged with `mergeGeometries` | 250 | `bamboo`: base `#2B1A0F`, rim term from the candle (lit side warm) |
| Tassels | 2 bunches × 9 strips under the two lower tips, `PlaneGeometry(0.018, 0.34, 1, 6)`, `InstancedMesh` (18), attributes `aPhase`, `aColor` (sun / ember alternating) | 216 | `tassel`: vertex sway `x += sin(t·2.1 + y·3 + phase)·0.02·(1-y)²` + lantern swing velocity |
| String + stick | 1 line segment; stick `CylinderGeometry(0.014, 0.018, 3.2, 6)` entering from off-frame lower-left | 24 | flat `#3A2618` |
| Halo | 2 additive quads: core (1.2R, `--color-flame-100`) and outer (3.6R, ember at .35), `depthWrite: false` | 4 | `halo`: `a = exp(-r²·k) · uFlame · uFlicker · (1 - .6·uDawn)` |

The candle itself is not modeled: the skin is opaque, so the candle is shown the way the
eye sees it through cellophane, as a hotspot. The trick that makes it read as a lantern
and not a red plastic star is the **ray-to-candle hotspot**: how close the view ray
through each fragment passes to the candle.

```glsl
// cellophane.frag (sketch; vColor is the instance glow color)
uniform vec3 uCandle; uniform float uFlame, uFlicker, uDawn;
uniform float uScale, uScale2;       // lantern world scale and its square
uniform vec3 uPaperDim, uCore, uMoon, uMoonDir;
varying vec3 vWorld, vNormal, vColor; varying vec2 vFacetUv;
void main() {
  vec3 V = normalize(cameraPosition - vWorld), dir = -V;
  vec3 toC = uCandle - cameraPosition;
  float dRay = length(toC - dir * max(dot(toC, dir), 0.0));
  float hot = exp(-dRay * dRay / (0.018 * uScale2));            // gold core behind the paper
  float dC = distance(vWorld, uCandle) / uScale;
  float trans = 0.35 + 0.65 / (1.0 + 2.5 * dC * dC);            // paper transmission falloff
  float crinkle = vnoise(vFacetUv * 9.0) * 0.6 + vnoise(vFacetUv * 23.0) * 0.4;
  trans *= mix(0.82, 1.18, crinkle);                            // cellophane wrinkles
  float lit = uFlame * uFlicker * (1.0 - 0.55 * uDawn);
  vec3 col = mix(uPaperDim, vColor, clamp(trans * lit, 0.0, 1.0));
  col += uCore * hot * lit * 1.4;
  vec3 N = normalize(vNormal + vec3(dFdx(crinkle), dFdy(crinkle), 0.0) * 2.0);
  col += uMoon * pow(max(dot(reflect(-V, N), uMoonDir), 0.0), 28.0) * 0.25; // moonlit sheen
  gl_FragColor = vec4(col, 1.0);
  #include <colorspace_fragment>
}
```

`uFlicker` is computed on the CPU once per frame (two octaves of 1D noise at 7 Hz and
13 Hz, range .85 to 1.05) and shared by every lantern with a per-instance phase, so the
GPU never evaluates time noise per fragment. `uPaperDim` is `#3B1A26` (unlit cellophane).
The proportion check (`lantern/lantern-proportion-check.png`) shows the geometry reads as a
đèn ông sao at the hero angle (rotY -0.55, rotX 0.14, rotZ -0.10).

Hero lantern motion: a damped pendulum hanging from the string point.
`θ'' = -14·θ - 2.2·θ' + torque` per axis (period about 1.7 s, underdamped). Torque sources:
idle wind `0.25·sin(0.7t) + 0.15·sin(1.9t + 1)`; pointer on fine pointers
(`0.6 × pointer NDC`, toward the cursor); scroll velocity on every device
(`clamp(v · 0.002, ±1.5)`, so a flick on a phone makes it swing). Spin about the string
axis 0.12 rad/s, slowing to 0.04 while the pointer is over the hero. Tassels add the swing
velocity to their sway, so they trail.

The hero lantern's screen position is decided by the DOM, not by camera numbers: the
`.hero__stage` box (the same box that holds the SVG poster) is measured, and
`camera.setViewOffset()` shifts the projection so the lantern's world point lands on that
box's center. Poster and 3D therefore line up at every viewport, and the layout stays
editable in HTML and later in WordPress.

**B. The procession** (`scene/procession.js`). One `InstancedMesh` for skins, one for frames
(the merged frame geometry), one for halos, one for street reflections; handles as one
`InstancedMesh` of thin cylinders. Lanterns live in a moving window of ±30 m around the
camera's position along the street path and wrap around, so 48 instances (mobile) or 140
(desktop) are enough for a procession that seems endless. Each instance: path parameter,
file offset ±0.8 m, bob `0.035·sin(2π·1.7t + φ)` (walking cadence), roll
`0.10·sin(2π·0.85t + φ)`, slow yaw. Colors by weight: ember 55%, sun 20%, bridge blue 9%,
bamboo green 8%, rose 8%. Near a center, colors blend toward that unit:
`mix(instanceColor, uUnitColor, smoothstep(r1, r0, distance(instancePos, uBeatCenter)))`.
The walk advances by `0.25 m/s` ambient time plus scroll progress, so it always feels alive
but the scroll still decides where you are. Matrices are composed on the CPU for visible
instances only (about 0.15 ms per frame for 140).

Street reflections: a vertical additive quad under each lantern, 0.9 m tall, alpha .18,
UV ripple `sin(uv.y·40 + t·3)·0.04`: Saigon after rain.

**C. The paper-cut city** (`scene/city.js`). Five silhouette layers along the street at
depths of about 60, 36, 22, 10 and 3 m from the path, each a single `ShapeGeometry` built
from a step polyline of seeded houses (no alpha textures, so no overdraw from transparent
quads):

| Layer | Content | Color | Window density |
|---|---|---|---|
| L0 far | generic high-rise blocks, stepped tops; no real landmarks | `#1A1A4A` | .15, tiny |
| L1 | tube houses 4 to 6 floors, 4 m frontage, rooftop water tanks | `#15153F` | .12 |
| L2 | tube houses with balconies, awnings, blank signboards (never text), tree canopies; Little Rose window lives here | `#10103A` | .08 |
| L3 near | power poles, catenary cables (`LineSegments`, 1 px), one tree | `#0B0B2A` | none |
| L4 frame | bamboo culm edges near the Green Bamboo beat | `#070718` | none |

Windows are procedural in the fragment shader (`floor(local / winSize)` cell, hashed,
lit cells `#FFB45C` at 40% to 70%), so the geometry stays a few hundred triangles per
layer. Each layer adds lantern light: the CPU picks the 8 lanterns nearest the camera
target (4 on mobile) and passes them as `uLightPos[8]`, `uLightCol[8]`; the shader sums
`col · I / (1 + 0.35·d²)`, which paints warm pools on the walls as the procession passes.

**D. Unit set pieces**

- *Green Bamboo grove:* `InstancedMesh` of `CylinderGeometry(r, r, h, 6, 6)` culms (60
  desktop / 24 mobile), height 6 to 10 m, radius 0.04 to 0.07, node rings as shader
  stripes, vertex sway `x += sin(0.6t + φ)·0.08·h²`. Leaves: instanced quads with a
  lanceolate SDF (600 / 200), color `#0A1F1A`, green rim light from nearby lanterns.
- *Little Rose window:* a wall `Shape` with an arched rectangular hole; behind it a quad
  with a pink-to-flame gradient and a cut-paper flower: polar shape
  `r(θ) = 0.55 + 0.45·|cos(2.5θ)|^0.6` (five petals). This is decoration, not a building:
  no door, no number, no sign, no map. HCWA's safeguarding lead approves it before it ships.
- *Cau Han bridge:* deck `BoxGeometry(24, 0.4, 4)`; arch `TubeGeometry` along a quadratic
  curve (span 24 m, rise 4 m, radius 0.12, 32 × 5 segments); hangers as `LineSegments`;
  40 string lights as halo sprites (`--color-glow-bridge` and flame). River: a plane with a
  slow ripple shader and stretched reflection sprites.

**E. Sky, moon, embers**

- Sky: one full-screen triangle drawn first. Gradient `#0A0A22` (top) → `#24245E`
  (horizon) plus two slow color fields (ember city glow and logo blue) drifting over 40 s:
  the aurora-ui idea done as low cloud lit by lanterns, in the shader, never as CSS
  `filter: blur()` blobs (those jank on Android). Dawn mixes toward `#FFE9CF` / `#FFC27A`.
- Moon: a disc quad, soft edge, faint fbm mottling, `#E9E4F5` at 85%, low contrast so it
  never competes with the lantern; sets during dawn.
- Embers: `Points`, 60 mobile / 160 desktop, size 2 to 5 px × DPR, rise 0.12 to 0.35 m/s,
  life 4 to 7 s, additive, `--color-flame-100`.

### 3.3 Camera

One camera for the whole home page. FOV 35° when landscape, 50° when portrait. Keyframes
(world meters, tuned with a dev-only lil-gui panel that never ships):

| Key | Position | Look at | Used by |
|---|---|---|---|
| K0 hero | hero lantern at (0, 1.7, 0), camera 1.15 m in front, framed by `setViewOffset` | lantern | 2.1 |
| K1 street | (1.5, 1.4, 6) | (-4, 1.1, 0) | hero exit, procession intro |
| K2 bamboo | (-9, 1.6, 4) | (-16, 2.2, -3) | beat 1 |
| K3 window | (-10, 1.8, -10) | (-19, 2.6, -14.5) | beat 2 |
| K4 bridge | (4, 3.2, -16) | (22, 3.0, -31) | beat 3 |
| K5 dawn | (6, 5, -14) | (22, 9, -60) | 2.3 (camera tilts up to the sky) |

Position follows a `CatmullRomCurve3` through K1 to K5; look-at targets are lerped. On
fine pointers the camera adds a look-around offset of ±0.25 m x, ±0.12 m y, eased with a
per-frame lerp of 0.06. No pointer camera motion on touch.

### 3.4 Budgets (mid-range Android first)

Reference device: a Samsung Galaxy A-series phone of 2023 to 2024 (Mali-G68 class), Chrome,
4G. Measured numbers are marked (m); the rest are budgets to enforce.

| | Mobile (tier 1) | Desktop (tier 2) |
|---|---|---|
| DPR cap | 1.25 | 1.75 |
| Antialias | on (MSAA is cheap on tile GPUs) | on |
| Triangles | ≤ 40k | ≤ 120k |
| Draw calls | ≤ 22 | ≤ 26 |
| Light uniforms per city layer | 4 | 8 |
| Lanterns / embers / culms / leaves | 48 / 60 / 24 / 200 | 140 / 160 / 60 / 600 |
| Frame budget | 16.6 ms; GPU ≤ 10 ms, JS ≤ 3 ms | 16.6 ms; GPU ≤ 8 ms |
| GPU memory | < 30 MB (no textures except a 128×128 R8 noise) | < 40 MB |

Payload, all loaded after the H1 has painted:

| Asset | gzip |
|---|---|
| three r187, only the classes this scene imports, bundled with rolldown | 139 KB (m) |
| gsap 3.15.1 + ScrollTrigger + lenis 1.3.27 | 49 KB (m) |
| scene code + shaders | 18 to 24 KB (estimate) |
| GLB / textures | 0 |
| Total after LCP | about 210 KB |

The homepage total stays far under the 1 MB limit in `09-QA-CHECKLIST.md` (HTML ~30 KB,
CSS ~20 KB, fonts 138 KB, inline posters ~15 KB, JS above).

Load order:

1. HTML with critical CSS inline; preload `bricolage-display.woff2` and
   `be-vietnam-pro-400.woff2`. H1 paints: LCP. The hero poster is inline SVG, which is not
   an LCP candidate, so the H1 stays the LCP element and nothing waits for an image.
2. `main.js` (~8 KB): header, menu, accordions, pause toggle.
3. `motion/index.js` (GSAP, ScrollTrigger, Lenis) as a module after `DOMContentLoaded`.
4. After `load` + `requestIdleCallback({ timeout: 2000 })`: if tier ≥ 1,
   `import("./scene/index.js")`. Geometry is built in three idle chunks (lantern, city,
   set pieces; ≤ 60 ms main thread total), shaders compile with
   `renderer.compileAsync(scene, camera)`, then the first frame renders and the canvas
   crossfades in.

Tier selection (`scene/capability.js`):

- **Tier 0, posters only:** no WebGL2, `prefers-reduced-motion: reduce`, motion paused by
  the visitor, `navigator.connection.saveData`, or `navigator.deviceMemory < 2`.
- **Tier 1:** `(pointer: coarse)` or viewport width < 768.
- **Tier 2:** everything else.
- **Downgrade at runtime:** rolling median of frame time over 90 frames. Above 22 ms twice
  in a row: drop one step (DPR 1.25 → 1.0 → 0.85, then halve embers and leaves, then 4
  lights → 2). Still above 30 ms at the lowest step: render one last frame, stop the loop,
  crossfade back to the posters. Never upgrade during a session.

Render loop rules:

- Drive rendering from `gsap.ticker` (the same ticker Lenis uses), one rAF for everything.
- Render only while a `.night-zone` element intersects the viewport (IntersectionObserver),
  the tab is visible (`visibilitychange`), and motion is not paused.
- After 6 s with no scroll or pointer input, render every other tick (30 fps ambient).
- `webglcontextlost`: stop, add `.is-3d-off` (posters return); on restore, rebuild once.

### 3.5 Posters: the no-WebGL, no-JS and reduced-motion version

The same `lib/lantern-geometry.mjs` runs in Node inside `src/render.mjs` and writes SVG:
it projects the 30 facets with the hero camera, sorts them back to front, and fills each
with the same lighting formula the shader uses (falloff, facing term, gold core). Output:

- `partials/lantern-poster.mjs` → inline SVG in the hero with two layers (`.unlit`,
  `.lit`), a flame glyph and a radial-gradient halo. About 4 to 6 KB. Variants by color
  for inner-page headers (ember, bamboo, rose, bridge) and a 404 version.
- `partials/city-posters.mjs` → three SVG posters for the procession beats (grove, rose
  window, bridge) built from the same seeded silhouettes; external `.svg` files loaded
  with `<img loading="lazy">`, 6 to 10 KB each.

| Situation | What renders |
|---|---|
| No JS | Inline lit poster (CSS shows `.lit` without the ignition), all sections as plain HTML, the procession as three stacked center panels each with its poster, the dawn as a static gradient band above the paper edge. Header is solid night. |
| JS, no WebGL / tier 0 with motion allowed | The above plus the CSS ignition (2.1), CSS sway, GSAP reveals, and the dawn band driven by `--dawn`. Procession panels are not pinned: they reveal one after another with their posters. |
| `prefers-reduced-motion: reduce` | Lit poster at once, no ignition, no sway, no Lenis, no pins, no scrubs, no WebGL (a still 3D frame adds nothing over the poster and saves 210 KB). Reveals off. Transitions only on color and opacity, ≤ 150 ms. View transitions off. |
| Visitor pressed "Tạm dừng chuyển động" | Same as reduced motion from that moment, stored in `localStorage` (`hcwa-motion=paused`, wrapped in try/catch); the canvas freezes on its current frame. |

### 3.6 Later: a Blender-authored lantern

Optional, never required. A Blender lantern (real bamboo lashings, folded paper, baked AO
in vertex colors) can replace the procedural one if it meets this contract: GLB with Draco
+ meshopt, ≤ 150 KB, nodes `skin`, `frame`, `tassels`, `stick`, origin at the string
point, R = 1. It loads only on tier 2 after the procedural lantern is already on screen,
and swaps in only if it arrives within 4 s. Our shaders stay; Blender supplies geometry.
Until then, `lib/lantern-geometry.mjs` is the lantern.

---

## 4. Motion system

### 4.1 Tokens (CSS custom properties, mirrored in `motion/tokens.js`)

| Token | Value | GSAP | Use |
|---|---|---|---|
| `--ease-out` | `cubic-bezier(.23, 1, .32, 1)` | `CustomEase "hcwaOut"` | every entrance, hover, press release |
| `--ease-in-out` | `cubic-bezier(.77, 0, .175, 1)` | `"hcwaInOut"` | things moving on screen (rail marker, tabs indicator) |
| `--ease-drawer` | `cubic-bezier(.32, .72, 0, 1)` | `"hcwaDrawer"` | mobile menu sheet |
| `--ease-glow` | `cubic-bezier(.16, 1, .3, 1)` | `"expo.out"` | light blooms, halos |
| `--t-press` | 100 ms | | button press |
| `--t-hover` | 160 ms | | hover, color, underline |
| `--t-ui` | 220 ms | | menus, accordions, header theme |
| `--t-sheet-in` / `--t-sheet-out` | 300 / 200 ms | | mobile menu |
| `--t-reveal` | 560 ms | | section reveals |
| `--t-ignite` | 1200 ms | | lantern ignition, headline warm-up |
| `--t-halo` | 1600 ms | | halo bloom |
| `--t-view-out` / `--t-view-in` | 180 / 280 ms | | page transitions |
| `--stagger` | 50 ms (lists), 80 ms (hero intro + buttons, motion-ok only) | | max 6 staggered items, the rest appear with the 6th |
| `--rise` | 20 px desktop, 14 px mobile | | reveal distance |

Rules (emil-design-eng, ui-motion): no `ease-in` on UI; exits faster than entrances; never
`transition: all`; never scale from below .96; hover effects only inside
`@media (hover: hover) and (pointer: fine)`; nothing animates on keyboard-initiated actions
except focus rings; **the payload never moves**: H1, impact numbers, prices, payment
fields and form errors appear in place. Only `transform` and `opacity` animate, plus
`color` on the one-shot headline warm-up.

### 4.2 Scroll beats (home)

| Beat | Trigger | Start → end | Pin | Scrub | Drives |
|---|---|---|---|---|---|
| B1 hero exit | `#hero` | `top top` → `bottom top` | no | 0.6 | camera K0 → K1; lantern scale .20 → .16 and drift to the head of the walk; city fog `1 → .35` |
| B2 light reading | `.statement` | `top 80%` → `bottom 45%` | no | true | each word's `--lit` 0 → 1 in reading order: color from `--color-lit-500` (7.50:1) to `--color-lit-50` (16.52:1), so the effect never drops below AAA |
| B3 bento | `.bento` | `top 85%`, once | no | none | cells fade-up `--rise`, `--t-reveal`, stagger 60 ms |
| B4 procession | `#procession` | `top top` → `+=320%` / `+=260%` | yes | 0.8 | 2.2 timeline |
| B5 dawn | `#dawn` | `top bottom` → `top 15%` | no | true | 2.3: `uDawn`, `--dawn`, lantern dimming, moon set |
| B6 day reveals | `[data-reveal]` | `top 85%`, once | no | none | fade-up, stagger 50 ms in lists |
| B7 CTA lantern | `.cta-band` | `top 75%`, once | no | none | the small SVG lantern in the CTA lights (CSS ignition, 900 ms) |

Light reading (B2) splits words with a build-time helper: the paragraph holds the plain
sentence in a visually hidden span, followed by an `aria-hidden="true"` copy in which each
word is a `<span class="w" style="--i:n">`. Screen readers hear one sentence, not 30
fragments (an `aria-label` on a `<p>` is not reliably announced, so it is not used). Words
break only at spaces, never inside a syllable, so diacritics are never split. It is used
once on the site.

Lenis (desktop wheel only):

```js
const lenis = new Lenis({ lerp: 0.12, smoothWheel: true, syncTouch: false,
  wheelMultiplier: 1, touchMultiplier: 1, autoRaf: false,
  anchors: { offset: -88 }, stopInertiaOnNavigate: true });
lenis.on("scroll", ScrollTrigger.update);
gsap.ticker.add((t) => lenis.raf(t * 1000));
gsap.ticker.lagSmoothing(0);
ScrollTrigger.config({ ignoreMobileResize: true });
```

Touch keeps native scrolling (`syncTouch: false`). No `ScrollTrigger.normalizeScroll`.
Lenis is destroyed under reduced motion and when the visitor pauses motion.

### 4.3 Page transitions

Cross-document View Transitions (Chrome and Android Chrome 126+, Safari 18.2+; others
simply navigate):

```css
@view-transition { navigation: auto; }
::view-transition-old(root) { animation: var(--t-view-out) var(--ease-out) both vt-fade-out; }
::view-transition-new(root) { animation: var(--t-view-in) var(--ease-out) both vt-fade-in-up; } /* 8px */
.site-logo { view-transition-name: hcwa-logo; }
.night-band { view-transition-name: night-band; } /* home hero ↔ inner-page header band morph */
@media (prefers-reduced-motion: reduce) { @view-transition { navigation: none; } }
```

### 4.4 Micro-interactions

| Element | Behavior |
|---|---|
| Primary button (sun) | Radius 10 px (not a pill), 48 px min height, 1 px inner top highlight like a paper edge. Hover: `--color-sun-500`, `--t-hover`. Donate only: a 12 px star-lantern glyph left of the label; on hover its flame does `scaleY 1 → 1.2 → 1` over 320 ms. Press: `scale(.97)`, `--t-press`, release `--t-hover`. Loading: label stays, 16 px spinner, 700 ms linear. Disabled: 50% opacity, `cursor: not-allowed`. |
| Secondary button | 1.5 px border (`--color-lit-300` on night, `--color-blue-700` on paper); hover fills `--color-night-800` / `--color-blue-100`. |
| Focus ring | 3 px outline, 3 px offset: `--color-sun-400` on night (11.32:1 against night-900), `--color-blue-700` on paper (10.30:1). Never animated. |
| Arrow link | arrow `translateX(4px)`, underline 1 → 2 px, `--t-hover`. |
| Bento cell "candle" | A 220 px radial gradient of sun at 14% follows the pointer inside the cell (`--mx`, `--my` set in one rAF); fades in 220 ms, out 300 ms. The cut-paper motif in the cell brightens from behind. Fine pointers only. |
| Program row | its lantern dot scales 1 → 1.35 with a 12 px sun glow, arrow moves 4 px, 220 ms. |
| News / story card | `translateY(-2px)` + shadow step, 200 ms; title underline. |
| Header | transparent over the hero; night-900 at 92% after 80 px (no `backdrop-filter`: it re-composites the WebGL canvas every frame on Android); switches to paper/ink when a `data-theme="day"` section reaches the top, colors over `--t-ui`. |
| Mobile menu | `<dialog>` sheet from the right, `translateX(100%) → 0`, `--t-sheet-in` `--ease-drawer`; scrim 0 → .6; items stagger 40 ms (max 7); closes in `--t-sheet-out` with no stagger. |
| Accordions (FAQ, footer) | `grid-template-rows: 0fr → 1fr`, `--t-ui` `--ease-in-out`; chevron rotates 180°. |
| Copy button (Donate) | icon cross-fades copy → check with `scale .9 → 1`, 160 ms; label "Đã sao chép" / "Copied" for 2 s; `aria-live="polite"`. |
| Pause motion toggle | 44 × 44 px, bottom-right of the hero and in the footer; icon swaps instantly. |
| Form errors | message appears with a 160 ms opacity fade; focus moves to the error summary. No shake. |

---

## 5. Palette, type, texture

### 5.1 Tokens

Night tokens are new. Day tokens are the current ones in
`prototype/src/styles/tokens.css` (already measured in `qa/contrast-pairs.json`) and stay
unchanged, so inner pages, forms and the Donate panel keep their tested contrast.

| Token | Hex | Role |
|---|---|---|
| `--color-night-950` | `#0A0A22` | hero sky, deepest background |
| `--color-night-900` | `#11113A` | night section background |
| `--color-night-800` | `#1B1B4D` | raised panels on night (bento cells, procession panels) |
| `--color-night-700` | `#2E2E6B` | dividers on night (decorative, 1.48:1, never a control border) |
| `--color-night-control` | `#7A75B4` | input and control borders on night (4.32:1 on 900, 3.84:1 on 800) |
| `--color-lit-50` | `#FFF4DE` | candle-lit paper: primary text on night |
| `--color-lit-300` | `#CFC9EC` | secondary text on night; moonlit headline before ignition |
| `--color-lit-500` | `#A7A2D2` | meta text on night; unlit words in light reading |
| `--color-ember-500` | `#E8432A` | lantern cellophane; decorative only |
| `--color-ember-700` | `#B3321C` | ember when it must be text on paper (5.85:1) |
| `--color-flame-100` | `#FFE7AE` | lantern core, halo, embers |
| `--color-sun-400` / `-500` | `#FFC42E` / `#F2B312` | unchanged: the candle flame and the only action color |
| `--color-glow-bamboo` | `#5FD69B` | Green Bamboo light on night |
| `--color-glow-rose` | `#FF8CC3` | Little Rose light on night |
| `--color-glow-bridge` | `#8DBBFF` | Cau Han light on night |
| `--color-moon` | `#E9E4F5` | moon disc (3D and posters) |
| `--color-dawn-100` | `#FFF0DA` | first paper after dawn |
| `--color-dawn-300` / `-500` | `#FFC98A` / `#FF9E6B` | dawn gradient band only, never under text |
| `--color-blue-700` | `#313188` | unchanged logo blue: CTA band, links on paper |
| `--color-ink` | `#1C2333` | unchanged: text on paper; footer background |

Main text/background pairs (WCAG 2 ratios, computed with the relative-luminance formula;
run `design-token-contrast` and add them to `qa/contrast-pairs.json` in the first build
task):

| Text | Background | Ratio | Use |
|---|---|---|---|
| lit-50 | night-950 / 900 / 800 | 17.83 / 16.52 / 14.69 | headings and body on night |
| lit-300 | night-900 / 800 | 11.35 / 10.09 | secondary text |
| lit-500 | night-900 / 800 | 7.50 / 6.67 | meta, unlit words |
| lit-50 | night-900 + sun halo at 35% (`#645036`) | 7.02 | worst case allowed under text; halo alpha under any text block is capped at .35 |
| ink | sun-400 / sun-500 | 9.86 / 8.40 | primary button rest / hover |
| sun-400 | night-900 | 11.32 | focus ring and button fill against night |
| glow-bamboo / rose / bridge | night-900 | 9.94 / 8.40 / 9.17 | unit labels and dots on night |
| glow-rose | night-800 | 7.47 | lowest unit label pair |
| ember-500 | night-900 | 4.52 | decorative only, never text |
| lit-50 / sun-400 | blue-700 | 10.00 / 6.85 | CTA band text / focus ring |
| ink / ink-2 / blue-700 | dawn-100 | 14.00 / 6.97 / 9.73 | first day section |
| ink / ink-2 / ink-3 / blue-700 | paper | 14.82 / 7.38 / 5.41 / 10.30 | day pages (existing) |
| lit-50 / lit-300 / lit-500 / sun-400 | ink (footer) | 14.39 / 9.88 / 6.54 / 9.86 | footer |

Color rules: sun appears at most twice per screen (header Donate + one primary button).
Unit glows never carry body text. A red five-pointed star with a yellow shape on it can
evoke the national flag; the lantern is always shown as a faceted 3D object with stick
and tassels, never as a flat yellow star on red. Draft outlines stay visible (CLAUDE.md)
but get quieter: 1 px dashed, 3 px offset, `--color-sun-400` at 55% on night and
`--color-warn` at 50% on paper.

### 5.2 Type

**Display: Bricolage Grotesque** (Mathieu Triay, OFL), pinned to `wdth 80`, `wght 700`,
optical size 32 to 96 left variable (`font-optical-sizing: auto`). The condensed width is
not a style tic: it is what lets the Vietnamese H1 fit in three lines on a phone at a
real display size, and its exaggerated ink traps read like notches cut into paper.

**Body and UI: Be Vietnam Pro** 400 / 500 / 600 (the files already in
`prototype/public/fonts/`), designed for Vietnamese.

Verification, not assumption (fonts downloaded from `github.com/google/fonts`, checked
with fontTools, rendered with Pillow + raqm):

| Font | Google subset list | 147 Vietnamese letter forms (12 bases × 6 tones + Đ đ ₫, upper and lower) | GPOS `mark`/`mkmk` | Verdict |
|---|---|---|---|---|
| Bricolage Grotesque | vietnamese, latin-ext, latin | 147 / 147 | yes | display |
| Be Vietnam Pro | vietnamese, latin-ext, latin | 147 / 147 | yes | body |
| Phudu (Dương Trần, revival of Saigon hand-painted signboard lettering, the perfect story) | vietnamese, latin-ext, latin | 147 / 147 | yes | rejected: its lowercase code points draw cap forms, so every Vietnamese heading would be in ALL CAPS |
| Satoshi, Cabinet Grotesk (gpt-taste shortlist, Fontshare) | n/a | 54 / 147 (no Ơ Ư, no tone-mark precomposed letters) | n/a | rejected |
| Outfit (shortlist) | latin, latin-ext | 50 / 147 | n/a | rejected |
| Geist (shortlist) | | 146 / 147 (no ₫) | yes | rejected: no dong sign for amounts, and it is the face of every AI demo |
| Fraunces (current) | vietnamese | 147 / 147 | no `mark`/`mkmk` (precomposed only) | retired: it is the "slop" look |

Self-hosted build (extend `scripts/build-fonts.py`): subset Bricolage to the same Latin +
Vietnamese ranges as the other files, then instance to `{"opsz": (32, 96), "wdth": 80,
"wght": 700}`. Load the font with `TTFont(src, lazy=False)` and subset *before* instancing
(instancing first raised `KeyError: 'uni2003'` from the lazily loaded `gvar`). Measured
output: **47 KB** WOFF2 (a fully static instance is 27 KB; keeping wght 600 to 800 costs
78 KB, not worth it). Fonts total: Bricolage 47 + Be Vietnam Pro 29 + 31 + 31 = **138 KB,
4 files**, under the current 155 KB.

Scale:

```css
--fs-display: clamp(2.5rem, 1.15rem + 5.6vw, 5.75rem); /* home H1: 40px @390 → 92px from 1314px */
--fs-h1:      clamp(2.25rem, 1.4rem + 3.4vw, 4rem);    /* inner pages: 36 → 64 */
--fs-h2:      clamp(1.875rem, 1.3rem + 2.4vw, 3.25rem);/* 30 → 52 */
--fs-h3:      clamp(1.375rem, 1.15rem + 0.9vw, 1.75rem);
--fs-cta:     clamp(2.25rem, 1.2rem + 4.4vw, 5rem);    /* final CTA band */
--fs-statement: clamp(1.5rem, 1.1rem + 1.6vw, 2.25rem);/* Be Vietnam Pro 500, line-height 1.6 */
--fs-base: 1.0625rem; /* 17px body, line-height 1.65 */
```

- Display line-height **1.2** everywhere. Measured on Bricolage at opsz 96 / wdth 80 /
  wght 700: stacked lowercase marks (ẫ ỗ ẩ) reach 0.89 em above the baseline, the deepest
  descender or dot below (ỵ, ợ, g) reaches 0.31 em below; 1.20 em is the smallest
  line-height at which two lines can never touch. (Stacked capitals reach 1.01 em; in
  sentence case they only occur on the first line or in proper nouns.)
- Letter-spacing 0 for display and body. Sentence case only. `text-wrap: balance` on
  headings.
- Impact numbers: Bricolage with `font-variant-numeric: tabular-nums lining-nums` (`tnum`
  and `lnum` features present). Static, never counted.

**The hero line at display size** (draft VI translation of HCWA's EN line; measured with
the real fonts; H1 `max-width: 11em`, so the wrap is the same wherever 11 em fits):

390 px, 40 px, measure 358 px, three lines:

```
Cùng trẻ em xây dựng
tương lai an toàn và
tươi sáng hơn.
```

1440 px, 92 px, measure 1000 px, two lines:

```
Cùng trẻ em xây dựng tương lai
an toàn và tươi sáng hơn.
```

EN ("Helping children build safer, brighter futures.") sets in two lines at both widths.
Intro paragraph (Be Vietnam Pro 17/1.65): three lines at 390 px, two at 1440 px. See
`lantern/hero-type-test.png`. Tastegate must confirm at 390, 768, 1024, 1440 and at 200%
text zoom.

### 5.3 Texture, grain, light

- **Paper grain:** one 160 × 160 grayscale noise PNG (~6 KB), as a section background
  layer at 4% on night and 5% on paper. Plain opacity, no `mix-blend-mode` (blend modes
  over a WebGL canvas force extra compositing on Android). Never `position: fixed`.
- **Cut-paper edges:** section boundaries between night and day are SVG deckle edges (three
  seeded variants), with a 1 px `--color-lit-50` at 8% top highlight on night: the edge of
  a paper sheet catching lantern light.
- **Light in the DOM:** halos are radial gradients (pre-computed in posters), never
  `filter: blur()` on large elements. The only light that moves text color is the
  headline warm-up (2.1) and light reading (B2), both above AAA at every frame.
- **Photos (when HCWA approves them):** natural color, never tinted or blue-washed, never
  under text, framed as paper prints (4 px `--color-lit-50` border on night, 1 px
  `--color-line` on paper).

---

## 6. Home page, section by section

Content comes from `content/draft/*.json`. Anything marked draft there keeps `data-draft`
and its dashed outline in the prototype. No meta labels ("SECTION 01") anywhere.

### 6.1 Nav (night variant on home)

- Left: the logo on a 44 px `--color-lit-50` paper roundel until the white logo (A1)
  arrives, then the white logo. Text lockup from `site.logoLines.vi`: "Hội Bảo trợ Trẻ em /
  TP. Hồ Chí Minh".
- Center (≥ 1024 px): Về chúng tôi · Hoạt động · Tác động & Báo cáo · Tin tức · Chung tay ·
  Liên hệ (`ui.json`).
- Right: VI / EN, [Ủng hộ] sun button with the lantern glyph.
- Mobile: logo, VI / EN, Ủng hộ, Menu (64 px tall).
- Behavior as in 4.4. Prototype bar (staging only): `ui.prototypeNoteShort`.

### 6.2 Attention: hero

Night, `min-height: 100svh` (min 640 px), `data-theme="night"`, class `night-zone`.

- **H1:** `site.hero.title` (draft): "Cùng trẻ em xây dựng tương lai an toàn và tươi sáng
  hơn." `--fs-display`, lit-300 at first paint, warms to lit-50.
- **Intro:** `site.hero.intro` (draft), Be Vietnam Pro 17/1.65, lit-300, max 38 em.
- **Buttons:** [Khám phá hoạt động] (sun, `ui.exploreWork`), [Chung tay] (secondary,
  `ui.getInvolved`).
- **Stage:** `.hero__stage` with the inline SVG poster; the canvas aligns to it.
- **Pause toggle:** bottom-right, "Tạm dừng chuyển động" / "Pause motion".
- Nothing else: no eyebrow with "từ năm [năm]" (an unverified year has no place in the
  hero), no stats, no badges, no pill tags.

Desktop 1440 × 900: H1 bottom-left, 2 lines, measure 11 em (about 1000 px), starting at
about 52% of the viewport height; intro and buttons below, all inside 900 px. The stage
is the upper-right quadrant (center at 68% x, 34% y), so the lantern halo never sits under
the H1 (halo alpha under text capped at .35, see 5.1).
Mobile: section 6.9.

### 6.3 Interest: what we do

Night continues (`night-zone`), camera on beat B1 descending to the street.

- **H2:** "Chúng tôi làm gì" (`site.whatWeDo.title`).
- **Statement (light reading, B2):** first sentence of `site.whatWeDo.summary` (draft):
  "Hội chăm sóc, bảo vệ và giáo dục trẻ em có nguy cơ phải lao động sớm, bị xâm hại, bỏ học
  hoặc bị ảnh hưởng bởi HIV/AIDS." `--fs-statement`, max 28 em.
- **Body:** the second sentence, 17 px, lit-300.
- **Bento (gapless, `grid-auto-flow: dense`):** the four `focusAreas`, each a night-800
  cell with a cut-paper motif (interim, drawn in code: an open book, a sheltering roof,
  three lanterns of different sizes, a bridge; replaced by the agency's A6 icons when they
  arrive), name, one line of text (draft), and a link to `/hoat-dong/?area=<id>`.
  Desktop 12 columns, 3 rows: Giáo dục cols 1–7 rows 1–2; Bảo vệ trẻ em cols 8–12 row 1;
  Hỗ trợ gia đình và cộng đồng cols 8–12 row 2; Vận động chính sách cols 1–12 row 3.
  Check: row 1 = 7 + 5, row 2 = 7 (spanned) + 5, row 3 = 12. No empty cell. Mobile: one
  column, four cells.
- **Impact tags:** the four organization figures from `impact.json`, set as paper tags on
  thin strings under the bento: Bricolage numbers, Be Vietnam Pro labels. In the prototype
  they show `[N]` with the "Đang xác minh" chip; with `PROD=1` the strip is hidden unless
  at least two figures are verified (06-PAGES). Numbers never animate; only the strings
  get the 6 s CSS sway, and only with motion allowed. Link: "Xem tác động và báo cáo →".

### 6.4 Desire: the procession (signature 2.2)

`#procession`, night, pinned on JS + WebGL tiers.

- **Before the pin:** a visually hidden, focusable link "Bỏ qua phần giới thiệu các cơ sở"
  / "Skip the centers tour" that jumps to `#dawn`.
- **Intro panel:** H2 "Cơ sở trực thuộc" (`ui.centers`) + `site.centersIntro` (draft):
  "Nơi trẻ em được học, được chữa lành và lớn lên, mỗi ngày."
- **Three panels** (night-800 at 92%, 1 px night-700 border, radius 16 px), one per center
  from `work.centers`: a 10 px dot in the glow color, name, "Từ năm [năm]" (draft token),
  `serves` (draft), `location` only where the JSON has one (Green Bamboo has a draft one;
  Little Rose never shows a location), link "Xem cơ sở →" (`ui.visitCenter`). The Little
  Rose panel adds the small caption "Hình minh họa" / "Illustration".
- **Rail:** `<nav aria-label="Các cơ sở">` with the three names as anchor links.
- Desktop: panels in the left 5 columns, scene full-bleed. Mobile: 6.9.

### 6.5 Dawn (signature 2.3)

`#dawn`: a 100 vh band with the deckle edge at its bottom; text-free. The paper sheet
that follows starts the day sections (`data-theme="day"`). On the paper side the first
section background is `--color-dawn-100`, then `--color-paper`.

### 6.6 Desire, continued, in daylight

- **Dự án & chương trình** (`ui.programs`): the three `work.programs` as rows, not cards:
  status pill (Đang hoạt động / Đã hoàn thành), years token (draft), name, summary (draft),
  partner ("CapitaLand Vietnam" for Thrive Together, from the current site), arrow. A thin
  line runs through the rows like a string of lights, with a lantern dot per row that lights
  on hover. "Tất cả dự án →".
- **Tin mới từ Hội** (`ui.latest`): the featured story placeholder (`news.featuredStory`,
  with "Tên nhân vật đã được thay đổi để bảo vệ trẻ.") as a large card whose image slot
  shows a lantern-light pattern until C2 arrives; the three real posts with category and
  real date (25/05/2026, 25/05/2026, 07/03/2026). "Tất cả tin tức →".

### 6.7 Action

- **Bạn có thể giúp như thế nào** (`site.helpTitle`): the four `involve.ways` as a gapless
  2 × 2 bento (desktop: Ủng hộ cols 1–7, Tình nguyện cols 8–12, Hợp tác cols 1–5, Hiện vật
  và Tiệm bánh Hoa Hồng Nhỏ cols 6–12; row 1 = 7 + 5, row 2 = 5 + 7, no gap). Each has an
  interim cut-paper illustration drawn in code until A7 arrives: one lantern lighting
  another (donate), a string of lanterns (volunteer), the bridge (partner), a bakery box
  with a paper rose (in-kind). Title, text (draft), link.
- **CTA band** (blue-700, full bleed): `--fs-cta` headline `[DRAFT: Đi cùng các em một
  đoạn đường.]` / `[DRAFT: Walk part of the way with them.]`, a small SVG lantern that lights
  on enter (B7), [Ủng hộ] (sun) + [Tình nguyện] (secondary on blue). HCWA approves the line.
- **Cảm ơn các đối tác đã đồng hành** (`site.partnersTitle`): one-tone logo wall, C5
  placeholders until partners give permission; "Trở thành đối tác →".
- **Contact band:** "Bạn có câu hỏi hoặc muốn đến thăm?" + [Liên hệ với chúng tôi], phone
  `+84 28 3840 1406`, email `hcwa.vn@gmail.com` (both draft in `site.json`).

### 6.8 Footer

`--color-ink` background (the ground under the morning). Four columns from 06-PAGES:
org description and registration token; contact; Explore; Reports & policies. Bottom row:
© year, Facebook, VI / EN, the pause-motion toggle. Accordions on mobile.

### 6.9 Mobile hero and procession (390 × 844, and 390 × 740 with browser bars)

- Stage: top 36svh. Lantern centered horizontally, at 18% of the viewport height.
- H1 starts at 39svh: 3 lines × 48 px = 144 px; intro 3 lines ≈ 84 px; buttons stacked
  full-width, 2 × 48 px + 12 px gap. At 844 px: 329 + 144 + 16 + 84 + 24 + 108 = 705 px.
  At 740 px: 289 + 376 = 665 px. Both buttons are visible on the first screen at both
  heights (06-PAGES requirement).
- Procession: the panel is a bottom card (max 46svh, night-900 at 88%), the scene frames
  the top 54%. Pin length 260%.

---

## 7. How the other pages inherit the system

Only the home page loads WebGL. Inner pages are the same world at rest: one night header
band, then daylight, and at most one quiet CSS animation per screen.

| Page | Night header band | Body (day) | Motion |
|---|---|---|---|
| Shared rules | `clamp(280px, 44vh, 420px)` desktop, 240 to 300 px mobile; breadcrumb, H1 (`--fs-h1`, max 3 lines), one-line intro, a small static SVG lantern poster in the page color; deckle edge into the paper body; `view-transition-name: night-band` | Existing day tokens and components | Lantern CSS sway (motion-ok only); reveals; no pins, no scrubs |
| Our Work hub | three small lanterns side by side (bamboo, rose, bridge): the unit family at a glance | filter chips (links to `?area=`), center cards with their procession posters as images until B1/B2 arrive, program rows | none extra |
| Green Bamboo (center) | bamboo-green lantern + two layers of bamboo silhouettes | at-a-glance box, story, scoped figures (pending), gallery (placeholders, later natural-color photos), programs here, how to help, related news, contact + CTA band | silhouettes move ≤ 24 px with `animation-timeline: view()` inside `@supports`; static otherwise |
| Little Rose (rules for later) | rose lantern + the rose-window illustration | never photos of residents, never a location, bakery link | same as Green Bamboo |
| Thrive Together (program) | status pill, years, H1, CapitaLand Vietnam logo one-tone | Goals and Activities as a vertical string of lights: each item's lantern dot turns sun-colored as it crosses the viewport center (`animation-timeline: view()`, fallback: all lit); "where it runs" as three unit lanterns linking to the centers | only that |
| Get Involved | ember lantern | the four ways (same illustrations as home), FAQ accordion | none extra |
| Donate | short band, no lantern (nothing decorative near money) | why it matters, gift examples with `[số tiền]` tokens, methods in tabs (Trong nước / Từ nước ngoài), every field shows "Chờ HCWA xác nhận" and `data-donation-placeholder`; copy buttons; "Tell us you donated" form | micro-interactions only |
| Contact | ember lantern | form (labels above, errors below, consent), office cards; optional paper-cut HCMC outline (C7) with the office pin and no Little Rose pin | none extra |
| 404 | full night page: a lantern drifting away on its string (SVG, 9 s loop), `ui.nf_title`, `ui.nf_text`, search, links | none | the lantern re-lights when the "Trang chủ" link is hovered or focused (`:has()`); static under reduced motion |
| Components | every component in night and day variants, motion tokens with live demos | | |

WordPress port (`wp/themes/hcwa-2026`): night and day tokens go into `theme.json`; the hero,
night band, procession panels, dawn edge and bento ship as VI/EN block pattern pairs that
hold plain HTML with `data-beat` anchors; the scene is a script module enqueued only on
the front page (`wp_enqueue_script_module`) and reads those anchors, so it tolerates an
editor deleting or reordering panels (0 to 3 beats). Posters are generated at theme build
time from the same geometry module.

---

## 8. Mobile, accessibility, risks

### 8.1 Mobile plan (390 px)

- Hero as in 6.9; lantern swings with scroll velocity instead of following a pointer.
- Tier 1 budgets (3.4): DPR 1.25, 48 lanterns, 4 lights, 60 embers.
- Native touch scroll (Lenis `syncTouch: false`), `ScrollTrigger.config({ ignoreMobileResize:
  true })`, `svh` units for every full-height block so the address bar does not shift pins.
- Bento, programs, ways to help: single column. Body 17 px. Tap targets ≥ 44 px, buttons
  48 px.
- Procession panel as a bottom card; rail as three dots plus names at the top of the card.
- Decision gate in week 1: on a real Galaxy A-series phone, if the pinned procession holds
  under 45 fps after downgrades, tier 1 ships it unpinned (stacked panels with posters) and
  keeps the hero 3D.

### 8.2 Accessibility

- All text is HTML in reading order; the canvas is `aria-hidden`; posters are decorative
  (`aria-hidden`) because the panels carry the content.
- WCAG 2.2.2 Pause, Stop, Hide: the hero lantern, embers and procession move for more than
  5 s next to other content, so the pause toggle is mandatory (hero and footer).
- 2.3.3 Animation from Interactions: reduced motion removes everything listed in 3.5.
- 2.4.7 / 2.4.11: focus rings per 4.4; `scroll-padding-top: 88px` so the sticky header
  never covers a focused element; when focus enters a procession panel while pinned, the
  page scrolls to that panel's beat (instant under reduced motion), so keyboard users see
  what they tab to. Panels stay focusable when not visible.
- 1.4.10 Reflow and text zoom: if any panel is taller than 80% of the viewport (200% zoom,
  large text settings), the pin is not created and panels stack.
- 1.4.3 Contrast: every pair in 5.1, including the worst-case halo tint and every frame of
  light reading and the headline warm-up.
- Light reading keeps one accessible sentence (visually hidden text + `aria-hidden` word
  spans), not word fragments.
- Language: every new string exists in both languages (below). No mixed-language controls.

New UI strings (draft, add to `content/draft/ui.json`):

| Key | vi | en |
|---|---|---|
| `motionPause` | Tạm dừng chuyển động | Pause motion |
| `motionPlay` | Bật lại chuyển động | Play motion |
| `skipCenters` | Bỏ qua phần giới thiệu các cơ sở | Skip the centers tour |
| `centersRail` | Các cơ sở | Centers |
| `illustration` | Hình minh họa | Illustration |
| `ctaWalk` | [DRAFT: Đi cùng các em một đoạn đường.] | [DRAFT: Walk part of the way with them.] |

### 8.3 Honest risks

1. **Seasonal reading.** Trung Thu is one night a year; in March the site could read like an
   event page. Mitigation: no moon cakes, no rabbits, no festival dates, no "happy
   Mid-Autumn" copy; the lantern is a year-round symbol. HCWA may still feel it is too
   festive for child protection work: ask them early with the hero alone.
2. **Flag association.** A red five-pointed star with yellow can recall the national flag.
   Rule in 5.1 (always a 3D lantern with stick and tassels, never a flat star on red), and
   HCWA confirms they are comfortable.
3. **A dark home page for a trust-first NGO and older donors.** Mitigated by the dawn: all
   practical content and all forms are on paper, every night pair is ≥ 6.67:1. If HCWA wants
   it lighter, the fallback is a dusk hero on night-800 with the same tokens.
4. **Low-end Android.** Tier detection can misjudge; the runtime downgrade and posters are
   the safety net, but the pinned procession is the heaviest part and may need the decision
   gate in 8.1. LCP is protected because nothing 3D loads before the H1 paints.
5. **Pins on real phones** (address bar, iOS rubber-banding, text zoom) need device testing;
   tastegate screenshots will not catch pin jank.
6. **Little Rose window.** Even an abstract lit window could be read as a real house. No
   location, door, number or map pin, and HCWA's safeguarding lead approves the
   illustration before it ships.
7. **Tone.** "Light in the dark" can drift toward rescue imagery. The lantern belongs to the
   child and HCWA walks beside them; copy must keep that (the H1's *Cùng trẻ em* does).
8. **Typeface.** Bricolage Grotesque is popular in 2024 to 2026 product sites and its
   condensed bold is informal; formal partners may find it playful. It is used for display
   only, one width, one weight.
9. **Build time.** One engineer-week is tight. Order: (1) tokens, type, HTML sections, posters
   and all fallbacks; (2) hero lantern 3D + ignition; (3) dawn; (4) procession with two city
   layers; (5) QA: tastegate at 390/1440, Lighthouse mobile, reduced-motion and no-WebGL
   runs, a real Android device. If behind, the procession ships unpinned with posters and
   the pin follows.
10. **Draft copy everywhere.** The VI hero line is a draft translation, the CTA line is new,
   impact figures are tokens and will probably be hidden at launch. The design must look
   finished with the impact strip absent; it does, because nothing in the composition
   depends on it.
11. **Blender upgrade** is unproven; the procedural lantern is the product, not a stand-in.

---

## Appendix: files the engineer will create

```
prototype/src/lib/lantern-geometry.mjs   pure math: facets, frame segments, tassel anchors, poster projection
prototype/src/lib/city-seed.mjs          seeded skyline polylines shared by posters and scene
prototype/src/partials/lantern-poster.mjs  inline SVG (unlit + lit + flame + halo), color variants
prototype/src/partials/city-posters.mjs    three beat posters → public/img/posters/*.svg
prototype/src/styles/tokens.css          + night tokens, motion tokens, type scale
prototype/src/styles/night.css           themes, hero, bento, procession panels, dawn edge
prototype/src/motion/index.js            Lenis, ScrollTrigger beats B1–B7, reduced-motion and pause gates
prototype/src/motion/tokens.js           CustomEase definitions mirroring the CSS tokens
prototype/src/scene/index.js             dynamic-import entry: capability, tiers, renderer, loop, context loss
prototype/src/scene/{lantern,procession,city,setpieces,sky,embers,camera}.js
prototype/src/scene/shaders/*.glsl.js    cellophane, bamboo, tassel, halo, layer, sky, water
scripts/build-fonts.py                   + Bricolage subset-then-instance step
qa/contrast-pairs.json                   + the pairs in 5.1
```

Acceptance for the concept build: tastegate passes at 390 × 844 and 1440 × 900 for home in
VI and EN; Lighthouse mobile LCP < 2.5 s with the H1 as LCP element; CLS < 0.1; the home page
is complete with JS disabled, with WebGL disabled (`--disable-webgl`), and with reduced
motion; the render loop is idle (no rAF work) when the dawn paper covers the screen and
when the tab is hidden; no console errors.
