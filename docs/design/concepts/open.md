# Bông gió / Wind flowers

Art direction concept for the HCWA site, one of three competing directions written on
6 Nov 2026 after Maxwell rejected the Phase 1 prototype. Written for the engineer who builds
it next week. Nothing here is approved by HCWA; every new line of copy is marked
`[DRAFT: ...]`, and the hard rules in `CLAUDE.md` win over anything below.

The other two directions are the Mid-Autumn lantern walk (`lantern.md`) and the pop-up
book of bamboo, rose and bridge (`three-places.md`). This one shares none of their motifs:
no lanterns, no paper, no night, no river, no bamboo/rose/bridge objects. It is daylight,
concrete and lime wash.

**Evidence produced while writing this** (folder `open/`, all reproducible):

| File | What it is |
|---|---|
| `open/hero-1440.png`, `open/hero-390.png` | The home hero rendered by a working three.js proof (`open/proof-hero.html`) in headless Chrome: the real procedural screen, the analytic sunlight described in section 3, the HTML header, H1, intro and buttons in the real fonts. |
| `open/sun-path.png` | The same room at three points of the sun clock (3.4): t 0.08 (7:50, hero at rest), t 0.45 (11:45), t 0.90 (16:27). |
| `open/proof-hero.html` | The proof page. Run `python3 -m http.server 8000` from the repo root and open `/docs/design/concepts/open/proof-hero.html?t=0.08`. `t` (0 to 1) moves the sun, `&calm=0` turns the calm zone off, `&bare=1` hides the HTML. It is a proof, not production code, but the GLSL in it is the starting point for `prototype/src/world/glsl/aperture.js`. |
| `open/archivo-w78-600-700.woff2` + `open/Archivo-OFL.txt` | The display face, subset to Latin + Vietnamese and instanced to `wdth 78`, `wght 600–700`: 30,260 bytes. |

The proof rendered in SwiftShader (software WebGL), so it shows the look, not the speed.
Frame-time numbers below are budgets to check on a real phone in week 1, not
measurements.

---

## 0. What we are replacing

`qa/phase1-screens/home-vi-desktop-full.png` is a correct wireframe without a subject:

1. The two most valuable areas on the page, the hero right half and the three center
   cards, are grey boxes that say `A2 · 4:5` and `B1 · 4:3`.
2. Every section has the same rhythm: left H2, "See all" link on the right, a row of equal
   tiles. Ten sections, one shape.
3. Cream paper + Fraunces + one yellow pill button is the default "warm NGO" kit. Swap the
   logo and it is any charity in any country.
4. Dashed draft outlines on nearly every block make the whole page read as unfinished.
5. Nothing moves, nothing has depth, and nothing on the page could only belong to HCWA or
   to Saigon.

Adding effects to that layout would not fix it. It needs a world. This one is built out of
light, concrete and lime wash, so it needs no photos of children to work.

---

## 1. Name, pitch, and why it is HCWA's

**Name:** Bông gió (Wind flowers). *Gạch bông gió* is the Vietnamese name for the
cast-concrete breeze block: *bông* is flower, *gió* is wind.

**Pitch:** The site is the inside of a Saigon *mái ấm* (a children's home, literally a
"warm roof") on a sunny day: a wall of bông gió keeps harm out and lets light and air in,
and the sun coming through it moves across every page as you read, from the first morning
light behind the headline to the late-afternoon gold that opens the shutters on the call
to act.

**Why it fits HCWA, and why it would be wrong for other clients**

- **The name of the organization is the object.** *Bảo trợ* is *bảo* (protect) + *trợ*
  (support, help). A bông gió wall does exactly both at once: it is a wall, and it is
  full of holes that let light and air through. HCWA's name described as architecture.
  (This is a design rationale. It does not go on the site as a pun unless HCWA likes it.)
- **Safeguarding becomes the art direction.** A bông gió screen is privacy with light:
  from inside you see the sun, from outside you cannot see who is inside. The site never
  shows a child; it shows the light in the place where children are safe. For Little Rose
  this is literal: its door in the corridor (signature 2) stays closed, and only daylight
  comes out through the blocks above it.
- **Mái ấm is HCWA's own word.** Little Rose is *Mái ấm* Hoa Hồng Nhỏ. The site is a home,
  not a stage, so the camera stands where a child stands: every 3D shot is taken from
  **1.10 m**, a child's eye height, looking slightly up.
- **It is Saigon, specifically.** Breeze-block screens, lime-washed walls, polished
  terrazzo (*đá mài*) floors and louvered wooden shutters (*cửa lá sách*) are the
  everyday building language of 1960s–70s Saigon homes, schools and apartment stairwells.
  Every Saigonese adult has sat in that patterned shade. International partners read it as
  Saigon tropical modernism, which is sophisticated and not a cliché about poverty.
- **The brand colors become the light.** HCWA's action yellow (`#FFC42E`) is the
  sunlight; the logo blue (`#313188`) lives in the shade and becomes the dusk of the
  footer. The Donate button is the only other thing on screen in sunlight color.
- **The photo gap becomes the style.** HCWA has no approved photos of children yet
  (`10-ASSET-REQUEST.md`). Light on plaster needs none, and when approved photos arrive
  they hang on the lime-washed walls of inner pages like photos in a home.

**Who it would be wrong for.** Breeze blocks are also a trendy retro-Saigon café look.
A café uses them as decoration; it does not need "protect + let the light in" as its
argument, its name does not mean that, and it does not run a shelter whose residents must
never be seen. Section 8.3 lists how we keep it from drifting into café decor.

---

## 2. Signature moments (three, no more)

Everything else on the site stays calm so these three land. All three are skipped under
`prefers-reduced-motion: reduce`, on Tier 0 (no WebGL, save-data, low memory) and without
JS; section 3.8 describes what those visitors see instead.

### S1. Nắng vào (the sun comes in): home hero, on load

**What the visitor sees.** The page paints at once: header, H1, intro and two buttons in
HTML over a still of the room in cool morning shade (no sun patches yet). When the 3D is
ready, the canvas takes over from that still with no visible change (same camera, sun
intensity 0). Then the sun comes in: a field of quatrefoil light shapes appears high on
the back wall near the ceiling, brightens, and slides down and to the left until it rests
across the right half of the H1, while long soft shafts of light appear in the air
between the screen and the wall and dust motes start to glitter inside the shafts only.
The H1 itself does not move: the light moves over it.

**Trigger.** All of these must hold: the world module has compiled its shaders
(`renderer.compileAsync` resolved); the hero is at least 50% in view; the tab is visible;
motion is allowed; it is the first home view of the session (`sessionStorage`
`hcwa:nang`). Otherwise the canvas fades in already at the resting state (opacity 0 → 1,
400 ms, `--ease-out`).

**Timeline (ms from trigger).**

| Track | From → to | Start | Duration | Easing |
|---|---|---|---|---|
| Canvas opacity (poster handoff) | 0 → 1 | 0 | 400 | `--ease-out` `cubic-bezier(0.23,1,0.32,1)` |
| Sun intensity | 0 → 1 | 200 | 1200 | `--ease-sun` `cubic-bezier(0.16,1,0.3,1)` |
| Sun elevation | rest − 9° → rest | 200 | 1800 | `--ease-in-out` `cubic-bezier(0.77,0,0.175,1)` |
| Shaft density | 0 → 1 | 500 | 1500 | `--ease-out` |
| Motes alpha | 0 → 1 | 800 | 1200 | linear |

Total 2000 ms. Raising the sun is what moves the patches down the wall; nothing is faked
in 2D. It plays once; scrolling during it is fine (scroll drives the camera, the sun
track keeps running).

**After the sweep (desktop with a fine pointer).** Pointer position over the hero maps to
sun azimuth ±2.5° and elevation ±1.2° through a critically damped spring (ω = 6 rad/s,
no overshoot). At 1440 px the patches slide about 50 px across the H1 as you move the
mouse: you are moving the sun, and the light follows. Touch devices get no pointer
mapping; instead, every 14–22 s (random) a cloud passes: sun intensity dips to 0.82 over
1600 ms and returns over 2400 ms, `--ease-in-out`. Both stop while the hero is off screen.

**Text.** No text animation in the hero. The H1 is the LCP element and appears with the
first paint.

### S2. Hành lang (the corridor): home "Our centers", sticky scrub

**What the visitor sees.** The camera has passed through the screen (beat "through the wall", 4.4)
and is now in the long open-air corridor of a Saigon building, still at child height:
breeze blocks on the left with the street and trees beyond, a lime-washed wall with three
painted doors on the right, terrazzo underfoot, and the sun throwing the block pattern
across the floor and up the doors. As you scroll, the camera walks down the corridor
(native scroll speed; the scene follows, it never takes over). Each door gets its HTML
card as the camera reaches it:

1. **Tre Xanh / Green Bamboo:** green door (`--unit-green-bamboo`). As the camera passes,
   it swings open 28°, and warm daylight spills out across the threshold. No interior is
   modeled: only light.
2. **Hoa Hồng Nhỏ / Little Rose:** pink door (`--unit-little-rose`). **It never opens.** A
   row of three small bông gió blocks above it (the transom) lets plain daylight out onto
   the corridor floor. No number plate, no shoes, no sign, no window, no colored or dim
   light near it (warm pink or red light behind a closed door has the wrong connotation
   for a shelter for girls who survived sexual abuse; daylight only). Its card says so in
   words (6.4).
3. **Cầu Hàn / Cau Han:** blue door (`--unit-cau-han`). Opens 28° like the first.

**Trigger and mechanics.** The section is `260svh` tall (`220svh` below 600 px) with a
`position: sticky; top: 0; height: 100svh` stage inside. No ScrollTrigger pin (sticky is
native and never fights touch scrolling); one ScrollTrigger reads progress
(`start: "top top"`, `end: "bottom bottom"`) and passes it to the world. Camera position
and yaw follow progress through a `scrub: 0.6` tween (0.6 s catch-up smoothing). Door
swings are scrubbed (`ease: "none"` on progress, `--ease-in-out` shaped through the
timeline). No snapping: snap feels like scroll-jacking.

| Progress | Camera (z along the corridor, metres) | What happens |
|---|---|---|
| 0.00–0.08 | +1.5 → −1.0 | Emerging from the block hole ("through the wall", 4.4), exposure settles 1.45 → 1.22 |
| 0.08–0.36 | −1.0 → −6.0 | Door 1 centered at 0.22; swings 0 → 28° over 0.14–0.30 |
| 0.36–0.64 | −6.0 → −13.0 | Door 2 centered at 0.50; stays closed |
| 0.64–0.92 | −13.0 → −20.0 | Door 3 centered at 0.78; swings 0 → 28° over 0.70–0.86 |
| 0.92–1.00 | −20.0 → −22.0 | Camera slows, sun keeps moving, stage releases |

**Cards are never scrubbed.** Each card (HTML, in the stage's right column on desktop,
bottom 44% on mobile) enters when progress crosses its door's start (0.12 / 0.40 / 0.68)
and leaves when it crosses the next one, so text is never half-visible. Enter: `clip-path:
inset(0 0 100% 0)` → `inset(0)` plus `translateY(16px)` → 0, 520 ms, `--ease-out`; the
card's children (unit name, one line, link) stagger 60 ms. Exit: opacity 1 → 0, 200 ms,
`--ease-out` (exits are faster than enters). Scrolling back reverses both.

### S3. Cửa lá sách (the shutters open): home Action, sticky scrub

**What the visitor sees.** A pair of louvered wooden shutters, painted HCWA blue with
paint worn at the edges, fills the frame, closed. It is late afternoon outside; thin gold
lines of light come through the gaps between the slats and lie across the floor. As you
scroll, the slats tilt open (the lines of light widen into bars), then both leaves swing
out on their hinges and gold light floods the frame, until the canvas resolves into the
flat `--sun-100` background of the "How you can help" section. The section heading and
the four ways to help are waiting in that light.

**Trigger and mechanics.** Section `180svh` (`150svh` below 600 px), sticky stage,
progress as in S2.

| Progress | What happens |
|---|---|
| 0.00–0.35 | Slats rotate 0° → 65° (scrubbed). Light-stripe duty cycle in the shader follows `1 − cos(angle) · slatWidth / pitch`. |
| 0.35–0.75 | Leaves swing 0° → 110° outward. Exposure 1.22 → 1.55; shaft density 1 → 1.6. |
| 0.70 | H2 "Bạn có thể giúp như thế nào" and the first row enter (not scrubbed): `reveal-rise` clip-path, 520 ms, `--ease-out`, rows stagger 60 ms. |
| 0.75–1.00 | Canvas opacity 1 → 0 over the flat `--sun-100` section background (no white flash: the brightest frame is `--sun-100`, not white). Render loop stops at 1.00. |

The stage travels 80svh (180svh section minus the 100svh sticky stage). The brightening
happens over 0.35–1.00 of that, about 52svh of the visitor's own scrolling, in one
direction only, and ends on `--sun-100`, not white. A single one-way change is not a flash
in WCAG 2.3.1 terms (a flash is a pair of opposing changes), and scrolling back simply
reverses it at the visitor's speed.

---

## 3. The 3D

### 3.1 Principles

1. **One world, one light.** Every 3D image on the site (live on home, pre-rendered stills
   everywhere else) comes from the same scene code and the same sun.
2. **The sun is analytic, not shadow-mapped.** The only thing that shapes the light is a
   flat screen of holes. So for any point in the room, we can compute exactly whether the
   sun reaches it: trace back toward the sun to the screen plane and ask the block's
   signed-distance function whether that point is inside a hole. Two tests (front and
   back face of the 10 cm block) give the correct thinning of the light shapes at grazing
   angles; a penumbra width that grows with distance (`0.0093 × distance`, the sun's
   angular size) gives the correct softness. No shadow map, no shadow pass, pixel-sharp
   near the screen and soft far from it. This is what the proof renders.
3. **Text is HTML.** The canvas is `aria-hidden="true"`, `pointer-events: none`, and sits
   behind the content. Nothing readable is ever only in the canvas.
4. **The light respects the text.** Any HTML text block over the canvas registers a
   *calm zone* (its padded bounding box, in screen pixels). Inside a calm zone, sun
   patches drop to 16% and the surface is lifted toward shaded lime wash, with a 64 px
   feather (`calm()` in the proof). The H1 is exempt: it is large enough to read across
   the light (measured, 5.1).
5. **3D after text.** The world module is a dynamic import that starts after `load`. The
   page is complete without it.

### 3.2 World layout (metres, Y up)

| Set | Bounds | Notes |
|---|---|---|
| Room (hero) | x −6 → 2.6, y 0 → 3.3, z −4 → 4 | Screen on the right wall: x = 2.6, z −4.0 → 1.4, y 0.9 → 3.3 (18 × 8 = 144 blocks of 0.30 m) above a solid 0.9 m parapet. Solid wall beyond z 1.4. |
| Corridor (S2) | origin at x = 100 (never visible from the room), length 24 m along −z, width 2.2 m, height 3.0 m | Screen on the left (x = 98.9) full length above a 0.9 m parapet: 80 × 7 = 560 blocks. Doors on the right (x = 101.1) centered at z = −3.5, −9.5, −16.5. |
| Shutters (S3) | origin at x = 200 | Window opening 1.6 × 2.0 m, sill at 0.9 m. |

Camera always at **y = 1.10 m**.

| Shot | Camera | Look at | FOV (vertical) |
|---|---|---|---|
| Hero, landscape | (−1.5, 1.10, 3.3) | (0.5, 1.78, −4) | 46° |
| Hero, portrait | (−1.1, 1.10, 3.0) | (2.0, 2.15, −3.2) | 62° |
| Corridor | (99.8, 1.10, z(progress)) | 35° yaw toward the doors, pitch +4° | 52° (64° portrait) |
| Shutters | (200, 1.10, 2.6) | (200, 1.75, 0) | 44° (60° portrait) |

These are the values the proof uses for the hero (verified by `hero-1440.png` and
`hero-390.png`); corridor and shutter shots are first guesses to tune in week 1.

### 3.3 Objects and how each is built

**Bông gió block "Hoa thị"** (`objects/block.js`). 0.30 × 0.30 m outer square minus a
4 mm mortar joint, 0.10 m deep. Holes: one central quatrefoil, the union of four circles
of radius r = 0.056 m centered at (±r, 0) and (0, ±r), whose outline is the star-shaped
radius function `R(θ) = 2r · max(cos θ, cos(θ − π/2), cos(θ − π), cos(θ + π/2))`, plus four
corner pips (circles of radius 0.021 m at (±0.112, ±0.112)). Where four blocks meet, the
pips make a small four-dot rosette, which is what makes the wall read as hand-cast and not
as a perforated sheet.

- *Tier 2 geometry:* `THREE.Shape` square with the five holes as `Path`s (quatrefoil
  sampled at 40 points, pips at 8), `ExtrudeGeometry({ depth: 0.10, bevelEnabled: true,
  bevelSize: 0.004, bevelThickness: 0.004, bevelSegments: 1 })`, then delete the street-
  side cap (never seen from inside) with a filter on face normals. About 300 triangles a
  block. The proof uses a heavier version (72-point quatrefoil, 1,264 triangles); cut it.
- *Tier 1 geometry:* the whole screen is **one plane** with a fragment shader that
  evaluates the same SDF, `discard`s inside holes, and fakes the 10 cm thickness by
  shading the hole wall with the second aperture test (pixels inside the front hole but
  outside the back hole get the side-wall color). 2 triangles.
- *Material:* cast cement, `#D7CDBD` albedo, roughness 0.93, a low-frequency mottling from
  the shared lime-wash texture (below) at 40%, and the HCWA "bóng mát" shade on its
  room-facing side (it is backlit, so it reads as a silhouette with glowing holes).
- *The block set.* Three more patterns share the same code path as SDFs and become the
  site's icon language (6.3): **Mắt cáo** (dense diamond lattice), **Vòng tròn** (quarter
  circles in each corner: the full circle only exists where four blocks meet) and **Bậc
  thang** (stepped zigzag). Each is a 20-line GLSL function and a matching SVG `<path>`
  generated from the same numbers by `scripts/blocks-svg.mjs`.

**Screen** (`objects/screen.js`). Tier 2: `InstancedMesh` (144 in the room, 560 in the
corridor, drawn in 4 bays of 140 so frustum culling drops the bays behind the camera).
Tier 1: one SDF plane per wall.

**Room shell** (`objects/room.js`). Five `PlaneGeometry` quads (back wall, left wall,
floor, ceiling, parapet) and the solid wall segment past the screen, all with one
`ShaderMaterial` that includes the shared aperture chunk. No real lights; the shader
computes:

```
color = albedo · (skyFill + floorBounce) · ao  +  albedo · sunColor · max(N·L, 0) · sunMask(P) · calm
```

- *Lime wash (vôi):* albedo between `#E7DCCB` and `#F1E9DB`, mottled by 2-octave value
  noise, with a faint vertical brush direction. To keep the per-pixel cost to one texture
  fetch, the noise is baked once at startup into a 512 × 512 `DataTexture` (RGBA8, 1 MB,
  mipmapped, `RepeatWrapping`) on the main thread in under 15 ms, sampled with world
  coordinates.
- *Terrazzo (đá mài):* cream cement `#DDD4C5` with chips at 64 cells per metre (3 to 8 mm
  stones in four colors: `#CCBDA8`, `#8C8580`, `#DB9E6B`, `#6B7894`, the last one a quiet
  echo of the HCWA blue), chip contrast 45%. Voronoi baked into a second 512 × 512 texture.
  Polished: the sun term on the floor gets an extra 3% specular lift.
- *Fill:* sky fill `vec3(.86,.84,.90)` × (0.70 + 0.20 · N.x) in the morning (a cool shade
  that leans toward the logo blue: the *bóng mát*), warming to `vec3(.92,.86,.80)` by
  late afternoon; floor bounce `vec3(.70,.56,.42)` × 0.30 fading with height.
- *AO:* analytic darkening near the floor/wall lines (25% at the floor, 18% in corners).
  No SSAO.

**Analytic sunlight** (`glsl/aperture.js`, verbatim from the proof): `holeSDF(p)`,
`apert(P, x)` (project P along the sun direction to the plane x, reject outside the screen
rectangle, wrap into block-local coordinates, smoothstep the SDF with a distance-scaled
penumbra), `sunMask(P) = apert(P, x_inner) · apert(P, x_outer)`. The corridor uses the same
functions with the plane rotated (screen on x = 98.9, light travelling +x). Door transoms
are a second, tiny aperture (3 blocks, light travelling −x from inside the rooms).

**Light shafts** (`materials/shafts.js`). A full-screen pass rendered into a
**quarter-resolution** target (half-resolution on Tier 2), then upsampled with a 9-tap
tent filter (the shafts are soft, so no depth-aware upsampling is needed) and added.
Per pixel: build the view ray from the inverse projection, intersect it with the set's axis-aligned box analytically (the room is a box,
so no depth buffer is needed), march 16 steps (24 on Tier 2) with per-pixel jitter,
accumulate `sunMask(p) · density(p)`, multiply by a Henyey–Greenstein phase (g = 0.45) so
the shafts glow more when you look toward the sun (they flare in S3). Density is the lime-wash
noise texture sampled at (p.x + p.y, p.z), so the dust looks uneven without a 3D texture. Tier 1: 122 × 264 target at 390 px, 16 steps = about 0.5 M aperture tests per
frame.

**Dust motes** (`materials/motes.js`). `Points`, 160 (Tier 1) or 420 (Tier 2), random in
the set's volume, drifting 1–3 cm/s on a curl of two sines. The vertex shader evaluates
`sunMask(position)`, so a mote is visible only while it is inside a beam: they glitter in
and out of the light exactly where the shafts are. Size `2.6 · (3 / depth) + 0.8` px,
additive, `depthWrite: false`.

**Street beyond the screen** (`objects/sky.js`). One large plane outside each screen:
vertical gradient (morning `#FFF7E6` → `#EEF2F8`; afternoon `#FFD9A0` → `#F6E7D2`) with a
soft canopy of tamarind-green (`#BCCBA6`) made from fbm, overexposed so it reads through
the holes as bright street light and trees. Not tone-mapped.

**Doors** (`objects/door.js`, corridor). Frame: 4 `BoxGeometry` pieces (0.06 m).
Leaf: a box 0.95 × 2.2 × 0.04 m plus two inset panels (boxes 0.32 × 0.8 × 0.01 m).
Paint shader: unit color, 85% roughness, worn edges (UV-distance-to-edge × value noise
reveals `#8A6A48` wood at 12% coverage). Transom: 3 Hoa thị blocks (instances of the
screen). Behind open doors: one emissive quad with a warm vertical gradient (`#FFF1DA` →
`#F4D7AE`), so daylight spills out but nothing inside is modeled. Threshold: a 3 × 2 patch
of *gạch bông* (encaustic) tiles, 0.20 m, one 4-fold shader motif recolored per unit with
the unit color at 70% + lime + ink lines (Tier 2 only).

**Shutters** (`objects/shutters.js`, S3). Two leaves 0.8 × 2.0 m: stiles and rails as 6
boxes per leaf, 2 louver panels of 14 slats each (0.72 × 0.055 × 0.012 m, pitch 0.06 m),
all 56 slats in one `InstancedMesh`, rotation set per frame from progress. Paint shader:
`--blue-700` (`#313188`) base, AO in the slat gaps, worn edges showing wood. The light
behind is the same analytic idea with a stripe aperture: light passes where the projected
slat gap is open, duty cycle `1 − cos(angle) · 0.055 / 0.06`, so the stripes widen exactly
as the slats turn.

### 3.4 Lighting and the sun clock

There are no `THREE.Light`s in the room or corridor shaders. Standard materials (blocks,
doors, shutters) get one `DirectionalLight` matching the analytic sun and one
`HemisphereLight` (sky `#E6E4F0`, ground `#D9C19C`, intensity 1.75). Renderer:
`NeutralToneMapping`, exposure 1.22, `outputColorSpace = SRGBColorSpace`.

The sun's position is a function of one number, the **day** `t` from 0 to 1 (7:00 to
17:30), and home scroll drives `t`:

| Home position | t | Clock | Azimuth / elevation | Sun color |
|---|---|---|---|---|
| Hero at rest | 0.08 | 7:50 | 28° / 21° | `#FFD9A0` |
| Corridor start → end | 0.30 → 0.60 | 10:10 → 13:20 | 17° / 31° → 2° / 33° | `#FFE9C8` → `#FFF2DE` |
| Shutters start → end | 0.85 → 0.95 | 15:55 → 17:00 | −11° / 25° → −16° / 20° | `#FFD08A` → `#FFB866` |

Azimuth `lerp(32°, −18°, t)`, elevation `lerp(17°, 34°, sin(πt))`, direction
`(cos el · cos az, sin el, cos el · sin az)`, exactly as in the proof. `open/sun-path.png`
shows t = 0.08, 0.45 and 0.90.

### 3.5 What scroll and pointer drive

| Input | Drives | Smoothing |
|---|---|---|
| Home scroll position | `t` (sun), active set, camera shot | `scrub: 0.6` for camera, direct for `t` |
| Pointer over hero (fine pointer only) | sun azimuth ±2.5°, elevation ±1.2°; camera yaw ±0.8° | critically damped spring, ω = 6 rad/s |
| Time (hero, touch devices) | cloud pass every 14–22 s | `--ease-in-out` |
| Time (all sets) | mote drift; nothing else | linear |

Nothing reacts to scroll velocity, and nothing moves the page.

### 3.6 Budgets (mid-range Android first)

Target device class for testing: Samsung Galaxy A15 / A25 or Redmi Note 13 (Mali-G57/G68
or Adreno 610/619), Chrome, 4G.

| Budget | Tier 1 (phones, tablets, low-memory laptops) | Tier 2 (desktops, laptops with ≥ 8 GB) |
|---|---|---|
| DPR cap | 1.25 (1.0 above 1.6 M canvas pixels) | 1.75 |
| Draw calls per frame | ≤ 12 | ≤ 30 |
| Triangles | ≤ 5 k (SDF screens) | ≤ 60 k |
| Shafts | ¼ res, 16 steps | ½ res, 24 steps |
| Motes | 160 | 420 |
| GPU frame | ≤ 12 ms at 390 × 844 | ≤ 8 ms at 1440 × 900 |
| JS per frame | ≤ 2 ms, zero allocations (reuse vectors) | same |
| GPU memory | ≤ 24 MB (two 512² textures + RTs) | ≤ 48 MB |

**Payload (measured with the installed packages, rolldown `--minify`, gzip -9, since
GitHub Pages serves gzip):**

| Asset | gzip | Loads |
|---|---|---|
| three 0.187.0, only the classes the world imports | 139 KB (115 KB brotli) | dynamic import after `load` |
| gsap 3.15.1 + ScrollTrigger + lenis 1.3.27 | 49 KB | `defer`, after first paint |
| World code (est.) | ≤ 10 KB | with three |
| Fonts, 4 files (Archivo 30.3 + Be Vietnam Pro 29.6 / 31.3 / 31.4) | 120 KB WOFF2 total | preload Archivo + BVP 400 |
| Hero poster, shaded, AVIF 480 / 960 / 1600 w | budget 12 / 28 / 60 KB | `<picture>`, `fetchpriority="high"` |
| Corridor door stills × 3, shutters still, AVIF 800 w | budget ≤ 35 KB each | `loading="lazy"` (Tier 0 only, and inner pages) |

No GLB, no image textures, no HDR: every texture is generated at startup.

**LCP.** The H1 text, or the 480 w poster on phones, whichever the browser picks; both
arrive in the first ~90 KB. Target 1.8 s on Lighthouse's mobile 4G profile (limit 2.5 s).
The 3D starts after `load` on `requestIdleCallback({ timeout: 2000 })`, so it cannot move
LCP; shader compile goes through `renderer.compileAsync(scene, camera)` (uses
`KHR_parallel_shader_compile` where available) before the first visible frame.

**Render loop.** `requestAnimationFrame` runs only while a `[data-world]` section
intersects the viewport (`IntersectionObserver`, rootMargin 10%) and the tab is visible.
When the hero is still (no pointer, no scroll, no S1, no cloud for 2.5 s) the loop drops to
every other frame (motes are the only motion). Canvas has `visibility: hidden` while no
world section is in view. `webglcontextlost`: show the poster, try one restore.

**Tiering** (`world/tiers.js`, decided before the import):

- **Tier 0, no world:** no WebGL2, `prefers-reduced-motion: reduce`,
  `navigator.connection.saveData`, `effectiveType` 2g/slow-2g, or `deviceMemory < 2`.
- **Tier 1:** default for coarse pointers and for anything not Tier 2.
- **Tier 2:** fine pointer and `deviceMemory ≥ 8` (or unknown) and a WebGL2 renderer
  string that is not a known software or low-end mobile GPU.
- **Live downgrade:** after S1, measure 90 frames (`EXT_disjoint_timer_query_webgl2` when
  present, else rAF deltas). p75 > 22 ms on Tier 2 → Tier 1. p75 > 33 ms on Tier 1 →
  shafts off, DPR 1.0. Still > 40 ms → render one still frame, stop the loop for good
  this session (`sessionStorage hcwa:tier = 0`).

### 3.7 Loading order

1. HTML with critical CSS inline (≤ 14 KB), font preloads, the hero `<picture>` poster.
2. A 1 KB inline head script picks the tier from cheap signals (reduced motion, save-data,
   `effectiveType`, `deviceMemory`, pointer) and sets `<html data-tier="0|1|2">`.
   Tier 0 keeps the **sunlit** poster (resting state); Tiers 1–2 switch the `<picture>`
   to the **shaded** poster that S1 starts from. Without JS, the sunlit poster is what
   the markup already contains. WebGL2 itself is tested inside the world module (creating
   a context in the head would block parsing); if it fails, the sunlit poster crossfades
   back in over 400 ms and the page carries on as Tier 0.
3. `defer`: motion layer (GSAP, ScrollTrigger, Lenis) and reveals.
4. After `load` + idle: `import('./world/index.js')`, build the room only (the corridor
   and shutters are built when their section is within 1.5 viewports), bake the two noise
   textures, `compileAsync`, render one frame at sun intensity 0, then S1.

### 3.8 Posters, reduced motion, no WebGL, no JS

All stills are rendered by the same world code: `scripts/render-posters.mjs` (Puppeteer,
the cached headless Chrome, WebGL via SwiftShader, exactly how `open/hero-*.png` were made)
loads `world/poster.html?shot=...&t=...&w=...`, screenshots the canvas without HTML, and
encodes AVIF + WebP + JPEG with `sharp` (or `avifenc`). The posters are committed, so
GitHub Pages and WordPress serve static files.

| Visitor | Hero | Corridor (centers) | Shutters (help) |
|---|---|---|---|
| Tier 1–2, motion OK | live (S1) | live (S2) | live (S3) |
| Reduced motion | sunlit still, no sweep, no pointer, no cloud | no sticky stage: three door stills (4:3) stacked with their cards, as a normal section | open-shutters still above the section; heading and list visible |
| No WebGL / Tier 0 | sunlit still | as reduced motion | as reduced motion |
| No JS | sunlit still (default markup) | as reduced motion (default markup, the sticky stage is a JS-added class) | as reduced motion |

Under reduced motion we do not load three.js at all: a still frame does not justify
140 KB. Lenis is not started. Reveals become instant. Hover color changes stay
(they are not motion).

### 3.9 Later: Blender assets

The procedural assets are the shipping version. If HCWA or a volunteer artist later
models replacements in Blender, these are the swap points, each loaded with `GLTFLoader`
+ `DRACOLoader` + `KTX2Loader` and falling back to the procedural object on any error:

- **Shutters** with real moldings, hinges and baked AO, worn paint as a 512² KTX2 texture
  set. Budget ≤ 180 KB.
- **Door** with frame moldings and handle. Budget ≤ 120 KB.
- **A hand-sculpted Hoa thị block** with cast imperfections, used only on Tier 2 (Tier 1
  keeps the SDF plane, because the analytic light needs the SDF anyway). Budget ≤ 40 KB.

The analytic sun stays: a Blender screen must match the SDF's hole positions exactly
(export the SDF as an SVG from `scripts/blocks-svg.mjs` and model on top of it).

### 3.10 Code layout

```
prototype/src/world/
  index.js        bootstrap: tier check, build sets lazily, loop, visibility, context loss
  tiers.js        tier decision + live downgrade
  renderer.js     WebGLRenderer, DPR cap, resize (ResizeObserver), compileAsync
  shots.js        camera + sun keyframes per set, sun clock t → direction/color
  calm.js         collects [data-calm] boxes → uniform array (max 4 rects per frame)
  glsl/aperture.js  holeSDF variants, apert(), sunMask(), calm()
  glsl/noise.js
  textures.js     bakes lime-wash and terrazzo DataTextures
  objects/        block.js, screen.js, room.js, corridor.js, door.js, shutters.js, sky.js
  materials/      surface.js (room shader), paint.js, shafts.js, motes.js
  poster.html     poster render entry for scripts/render-posters.mjs
prototype/src/motion/
  tokens.js       easing + duration tokens mirrored from CSS
  scroll.js       Lenis + ScrollTrigger wiring
  reveals.js      section/card reveals, stagger
  statement.js    the sunlit reading (6.3)
  stages.js       sticky-stage progress for corridor and shutters, focus handling
scripts/render-posters.mjs, scripts/blocks-svg.mjs, scripts/light-contrast.mjs
```

---

## 4. Motion system

### 4.1 Tokens

```css
:root {
  --ease-out:    cubic-bezier(0.23, 1, 0.32, 1);    /* anything entering, UI feedback */
  --ease-in-out: cubic-bezier(0.77, 0, 0.175, 1);   /* things moving on screen */
  --ease-drawer: cubic-bezier(0.32, 0.72, 0, 1);    /* mobile menu sheet */
  --ease-sun:    cubic-bezier(0.16, 1, 0.3, 1);     /* light arriving: long soft tail */
  --ease-hover:  cubic-bezier(0.215, 0.61, 0.355, 1); /* colour and underline changes */

  --d-press: 120ms;  --d-hover: 180ms;  --d-ui: 240ms;  --d-sheet: 320ms;
  --d-reveal: 520ms; --d-sweep: 900ms;  --d-sun: 1800ms;
  --stagger: 60ms;   /* max 5 staggered children: 240 ms total */
}
```

GSAP mirrors: `CustomEase.create("out", "0.23,1,0.32,1")`, `"inOut"`, `"sun"` (CustomEase
ships free in gsap 3.15). One motion personality across the site: *Silk* in ui-motion
terms (smooth, composed). No springs with bounce anywhere except the pointer-to-sun spring,
which is critically damped.

Rules (emil-design-eng): UI feedback stays under 300 ms; nothing animates from
`scale(0)` (minimum 0.96); no `ease-in` on UI; exits are faster than enters; keyboard
actions are never animated; impact numbers and amounts never animate (they appear as they
are: the payload is not decoration).

### 4.2 Scroll infrastructure

- Lenis 1.3.27: `new Lenis({ lerp: 0.12, smoothWheel: true, syncTouch: false, autoRaf:
  false })`. Wheel input is smoothed, distance per notch is unchanged (native speed);
  touch scrolling stays fully native. Driven from `gsap.ticker` (`lenis.raf(time * 1000)`,
  `gsap.ticker.lagSmoothing(0)`), `lenis.on("scroll", ScrollTrigger.update)`. Not started
  under reduced motion.
- ScrollTrigger only reads progress. Pins are CSS `position: sticky` (S2, S3), which never
  fights the mobile address bar. `ScrollTrigger.config({ ignoreMobileResize: true })`.
- Anchors and keyboard: `lenis.scrollTo(target, { immediate: reducedMotion })`; native
  `Tab` scrolling is left alone.
- Everything is created in `gsap.matchMedia()` contexts keyed on
  `(prefers-reduced-motion: no-preference)` and width, so cleanup is automatic.

### 4.3 Reveals

- **Section reveal** (`reveal-rise`): H2 lines are masked (`overflow: clip` wrapper) and
  rise 0.6 em → 0 with opacity 0 → 1, 520 ms, `--ease-out`, when the heading's top crosses
  85% of the viewport, once. Content below fades up 12 px, 520 ms, staggered 60 ms. Content
  is visible by default; JS adds `.will-reveal` only when motion is allowed, so no-JS and
  reduced motion show everything.
- **Cards and rows:** fade up 12 px, 520 ms, stagger 60 ms, at most 5 per group.
- **Never revealed:** the H1, impact numbers, prices or amounts, form fields, error
  messages, the donation panel.

### 4.4 Home scroll beats

| Beat | Section | Height | What moves | Mechanism |
|---|---|---|---|---|
| Hero out | Hero | 100svh | S1 on load; while the hero scrolls out (0 → 100svh) the camera dollies 2.6 m toward one hole of the screen and the sun advances t 0.08 → 0.18 | scrubbed (`scrub: 0.6`) |
| Sheet | Interest | auto | opaque lime sheet slides over the canvas (canvas stops) | normal scroll |
| Sunlit reading | Statement | auto | sunlit reading (6.3) | scrubbed CSS variable |
| Through the wall | Entering the corridor | first 8% of S2 | camera starts *inside* a block hole and pulls back into the corridor; exposure 1.45 → 1.22. Read with "Hero out", it is one move through the wall. | part of S2 |
| S2 | Corridor | 260svh | signature 2 | sticky + progress |
| Sheets | Programs, news | auto | opaque sheets; row/card reveals | normal scroll |
| S3 | Shutters | 180svh | signature 3 | sticky + progress |
| Louvers | How you can help | auto | on `--sun-100`; louver rows (4.6) | normal scroll |
| Dusk | Footer | auto | the day ends: background `--sun-100` → `--blue-900` across a 240 px gradient band between the contact band and the footer, a static gradient, not animated | none |

### 4.5 Page transitions

Cross-document View Transitions, CSS only, so the WordPress theme gets them for free:

```css
@view-transition { navigation: auto; }
.site-header { view-transition-name: site-header; }        /* header stays put */
::view-transition-old(root) {               /* the old page is wiped left → right */
  mask-image: linear-gradient(90deg, transparent 0 40%, #000 60%);
  mask-size: 250% 100%;
  animation: 440ms var(--ease-in-out) both light-wipe;   /* mask-position 100% → 0% */
}
::view-transition-new(root) { animation: 360ms var(--ease-out) 80ms both rise-in; } /* 8px up + fade */
::view-transition-image-pair(root) { background: var(--sun-200); } /* the wipe's soft edge reads as a band of light */
/* keyframes light-wipe and rise-in live in motion/transitions.css. Check in Chrome that
   the image-pair background paints; if not, drop the tint and keep the wipe. */
@media (prefers-reduced-motion: reduce) { @view-transition { navigation: none; } }
```

Chromium supports this today; other browsers swap pages instantly, which is fine. VI ↔ EN
switches use the same transition (it is a navigation).

### 4.6 Micro-interactions

| Element | Behavior |
|---|---|
| Primary button (sun) | Hover: fill `--sun-400` → `--sun-500`, 180 ms `--ease-hover`; a soft quatrefoil glint (`::after`, sun-100 at 60%) crosses the button once, `translateX(-120% → 120%)` 600 ms `--ease-out`. Press: `scale(0.97)` 120 ms. Loading: label stays, a 2 px underline bar runs linear 1200 ms loop. Disabled: 45% opacity, no glint. |
| Secondary button | 1.5 px ink border. Hover: background `--lime-50`, border `--ink-900`. Press `scale(0.97)`. |
| Text link with arrow | Underline 1 px → 2 px, offset 4 px → 3 px, 180 ms; arrow `translateX(3px)`. |
| Center card (inner pages) | 6 px unit-color "transom" band on top; hover: a light spill (unit color 0 → 12% gradient from the band) grows down 0 → 40% of the card, 280 ms `--ease-out`; card `translateY(-2px)`. Whole card is the link. |
| Program row | Hover: a sun-200 stripe pattern (the *lam*, louver light) slides 0 → 100% behind the row, 520 ms `--ease-out`, then stays; arrow moves 3 px. |
| Ways-to-help louver row | Each row is a slat: on hover/focus the 1 px top line thickens to 3 px sun and a light bar 0 → 100% width runs under the title, 520 ms. Description is always visible (no hover-only content). |
| Header | Transparent over the hero with a 120 px lime gradient scrim behind it. After 80 px of scroll: background `--lime-50` at 94% + 1 px `--lime-300` line, height 80 → 64 px, 240 ms `--ease-out`. Never hides on scroll (Donate stays reachable). |
| Desktop dropdown | Opacity + `translateY(4px → 0)` + `scale(0.98 → 1)` from the trigger's edge, 180 ms; 0 ms on subsequent hovers within 400 ms. |
| Mobile menu | Sheet from the right, 320 ms `--ease-drawer`; scrim `--ink-900` 0 → 40%; close 200 ms. Focus trapped, `Esc` closes. |
| Accordion (FAQ) | `grid-template-rows: 0fr → 1fr` + opacity, 240 ms `--ease-out`; chevron rotates 180°. |
| Copy button (donate, disabled until verified) | "Sao chép" → check icon + "Đã sao chép" (`pop-in`: scale 0.96 → 1 + opacity, 200 ms); `aria-live="polite"`; reverts after 2 s. |
| Form errors | Message appears with opacity + height 160 ms; no shake (motion is not an error signal). Error summary receives focus. |
| Focus ring | 3 px `--blue-700`, offset 2 px; on blue/dusk backgrounds `--sun-400`. Never animated. |

---

## 5. Palette, type, texture

### 5.1 Color tokens

Named for what they are in the world. Source of truth will be
`prototype/src/styles/tokens.css` and `theme.json`.

```css
:root {
  /* lime wash and concrete: the house */
  --lime-50:  #F7F2E9;  /* sunlit plaster: cards, header after scroll, form fields */
  --lime-100: #EFE7DA;  /* page background */
  --lime-200: #E3D9C9;  /* plaster in shade: alternate sections */
  --lime-300: #CDBFAB;  /* mortar line: dividers only (1.47:1, decorative) */
  --border:   #7A6F5E;  /* field and control borders: 4.01 on lime-100, 3.53 on lime-200 */
  --terrazzo: #DDD4C5;  /* floor tone in stills and posters */

  /* ink */
  --ink-900: #1D2131;   /* text: 14.33 on lime-50, 13.02 on lime-100, 11.44 on lime-200 */
  --ink-700: #434859;   /* secondary text: 7.41 on lime-100, 6.51 on lime-200 */
  --ink-500: #5A5F70;   /* meta, dates: 5.18 on lime-100, 4.55 on lime-200 */

  /* sun: the action color is the light */
  --sun-400: #FFC42E;   /* HCWA yellow. Primary button fill, ink text 10.04 */
  --sun-500: #F2B312;   /* hover, ink text 8.55 */
  --sun-200: #FFE6AE;   /* light patches in CSS, highlight, ink text 13.07 */
  --sun-100: #FFF3D3;   /* "How you can help" background, ink text 14.47 */
  --gold-700: #8A5A12;  /* small accents on sun-100 only: 5.35 */

  /* shade and dusk: the logo blue */
  --blue-700: #313188;  /* HCWA logo blue: links, focus, shutters. 8.89 on lime-100 */
  --blue-900: #20205A;  /* dusk: footer and contact band. White 14.83, sun-400 9.32 */
  --blue-950: #16163F;  /* footer bottom bar. White 17.20 */
  --on-dusk-2: #C8CAEB; /* secondary text on dusk: 9.26 on blue-900, 10.73 on blue-950 */
  --shade-tint: #C9C6DA;/* the bóng mát: shadow tint in stills and shadows, never text */

  /* unit accents: doors, bands, dots. Never text. */
  --unit-green-bamboo: #008E3A;  /* 3.47 on lime-100 */
  --unit-little-rose:  #DC4C98;  /* 3.09 on lime-100 */
  --unit-cau-han:      #417CBD;  /* 3.53 on lime-100 */

  /* status */
  --ok:    #256B43;     /* 5.24 on lime-100 */
  --error: #B42318;     /* 5.36 on lime-100, 5.90 on lime-50, 4.71 on lime-200 */
  --focus: #313188;  --focus-on-dark: #FFC42E;
}
```

Unit accents stay as `05-DESIGN.md` sampled them from the logos (provisional until the
agency sends SVGs). They pass 3:1 as graphics (WCAG 1.4.11) and never carry text.

**Main text/background pairs (WCAG 2.x ratios, computed, not estimated):**

| Text | Background | Ratio | Use |
|---|---|---|---|
| `--ink-900` | `--lime-100` | 13.02 | body, headings |
| `--ink-900` | `--lime-200` | 11.44 | body on shade sections |
| `--ink-700` | `--lime-100` | 7.41 | secondary text |
| `--ink-500` | `--lime-200` | 4.55 | meta only (≥ 14 px) |
| `--ink-900` | `--sun-400` | 10.04 | primary button label |
| `--ink-900` | `--sun-100` | 14.47 | help section |
| `--blue-700` | `--lime-100` | 8.89 | links |
| white | `--blue-900` | 14.83 | footer, contact band |
| `--sun-400` | `--blue-900` | 9.32 | footer links on hover, focus ring on dusk |
| `--on-dusk-2` | `--blue-900` | 9.26 | footer secondary text |
| `--ink-900` over the live hero, H1 area | measured canvas, 1st-percentile pixel | 5.30 (1440) / 5.26 (390) | H1 (≥ 33 px bold: needs 3:1) |
| `--ink-900` over the live hero, intro and buttons (calm zone) | measured canvas, 1st-percentile pixel | 10.39 / 10.29 | body text (needs 4.5:1) |

The last two rows were measured on `open/hero-*.png` renders with the HTML hidden: the
darkest 1% of pixels behind each text box. Without the calm zone the intro measured 3.93:1
(terrazzo chips behind text), which is why the calm zone exists and why intro text over
the canvas is `--ink-900`, never `--ink-700`. `scripts/light-contrast.mjs` repeats this
check in CI for every `[data-calm]` box at 390 and 1440 and for every sun position the
page can show (sampled at t steps of 0.05).

Accepted exception (unchanged from 05-DESIGN): the sun button fill against lime is
1.30:1; the label carries the contrast.

### 5.2 Type

**Display: Archivo** (Omnibus-Type, OFL), condensed to **`wdth 78`**, weight 640 for
the H1, 620 for H2, 600 for H3 (one variable file, `wght 600–700`). It is the
condensed, upright, slightly engineered grotesque of Saigon's hand-painted shop signs and
building lettering, without being a costume. The condensed width is functional: it is what
puts the Vietnamese H1 in three lines on a 320 px phone at a real display size. Both
other directions chose Bricolage Grotesque; this one does not.

**Body and UI: Be Vietnam Pro** 400 / 500 / 600 (the files already in
`prototype/public/fonts/`), designed for Vietnamese. Kept.

**Checked, not assumed** (6 Nov 2026; fonts from `github.com/google/fonts`, checked with
fontTools, rendered in headless Chrome):

| Font | Google CSS2 API subsets | 147 Vietnamese forms (12 vowels × 6 tones, upper + lower, Đ đ ₫) | GPOS `mark` / `mkmk` | Verdict |
|---|---|---|---|---|
| Archivo | vietnamese, latin-ext, latin | 147 / 147 | yes / yes | **display** |
| Be Vietnam Pro | vietnamese, latin-ext, latin | 147 / 147 | yes / yes | **body** |
| Phudu (Dương Trần, a revival of Vietnamese signboard lettering) | vietnamese | 147 / 147 | yes / yes | rejected: rendered, its lowercase code points draw capital forms, so every heading would read as ALL CAPS |
| Outfit (gpt-taste list; Satoshi and Cabinet Grotesk are Fontshare-only) | latin, latin-ext | 50 / 147 | | rejected |
| Bodoni Moda / DM Serif Display | no vietnamese | 54 / 147, 48 / 147 | | rejected |
| Geologica, Epilogue, Familjen Grotesk, Big Shoulders, Noto Serif Display | vietnamese | 147 / 147 | yes | rendered and compared; Archivo condensed fit the concept and the 3-line rule best |

**Metrics** (Archivo instanced at `wdth 78`, `wght 640`, units of the em): tallest stacked
capital Ổ / Ẩ 1.048, tallest stacked lowercase ẩ 0.919, deepest descender or dot below
(ỵ, ợ, g) −0.192. Lowercase stacks over descenders need 0.919 + 0.192 = 1.111, so display
line-height is **1.15** (H1) and 1.18 (H2); stacked capitals only occur at the start of a
sentence-case line or in proper nouns, where the extra 0.13 em is taken from the space
above line 1. Body line-height 1.65.

**Build:** extend `scripts/build-fonts.py`: load with `TTFont(src, lazy=False)`, subset to
the existing `UNICODES` first, then `instancer.instantiateVariableFont(font, {"wdth": 78,
"wght": (600, 700)})`. Output 30,260 bytes (`open/archivo-w78-600-700.woff2` is that file).
A static 640 instance is 17,988 bytes if we ever want one weight. Fonts total 4 files,
120 KB (was 162 KB with Fraunces). Archivo has `tnum` and `lnum` for impact figures.

**Scale:**

```css
--fs-display: clamp(2.75rem, 1.2rem + 5.9vw, 6.75rem);   /* home H1 ≥ 600px: 54.6px @600 → 104px @1440 → 108px cap */
/* below 600px the hero text column is a size container: */
@media (max-width: 599px) { .hero h1 { font-size: clamp(1.875rem, 11.4cqi, 2.75rem); } }
--fs-h1:   clamp(2.25rem, 1.3rem + 3.6vw, 4.5rem);   /* inner page H1: 36 → 72 */
--fs-h2:   clamp(2rem, 1.35rem + 2.6vw, 3.75rem);    /* 32 → 60 */
--fs-h3:   clamp(1.375rem, 1.2rem + 0.7vw, 1.75rem); /* 22 → 28, Archivo 600 */
--fs-statement: clamp(1.375rem, 1.05rem + 1.4vw, 2.25rem); /* Be Vietnam Pro 500, lh 1.6 */
--fs-stat: clamp(2.75rem, 2rem + 3vw, 4.5rem);       /* Archivo 640, tabular-nums lining-nums */
--fs-lg: 1.25rem; --fs-base: 1.0625rem; --fs-sm: 0.9375rem; --fs-meta: 0.875rem;
```

Letter-spacing −0.005 em on display, 0 elsewhere. Sentence case everywhere in both
languages; Vietnamese is never uppercased (no `text-transform: uppercase` anywhere;
`scripts/wp-static-check.py` already fails on it in the theme CSS, extend the same check
to `prototype/src/styles/`). Heading compounds are joined with U+00A0 (`xây dựng`, `tương lai`, `an toàn`, `tươi sáng`, unit
names) by a small `viKeep()` helper, `text-wrap: balance` on headings, `pretty` on body.

**The hero line at display size, measured in Chrome with the real font**
(`max-inline-size: 12.6em`, `text-wrap: balance`, compounds joined):

| Viewport | Size | VI lines | EN lines |
|---|---|---|---|
| 320 | 32.8 px | 3 | 2 |
| 360 | 37.4 px | 3 | 2 |
| 390 | 40.8 px | 3 | 2 |
| 412 | 43.3 px | 3 | 2 |
| 600 | 54.6 px | 3 | 2 |
| 768 | 64.5 px | 3 | 2 |
| 1024 | 79.6 px | 2 | 2 |
| 1440 | 104.2 px | 2 | 2 |
| 1920 | 108 px | 2 | 2 |

At 1440: **Cùng trẻ em xây dựng tương lai / an toàn và tươi sáng hơn.**
At 390: **Cùng trẻ em xây dựng / tương lai an toàn / và tươi sáng hơn.**
Both are in `open/hero-1440.png` and `open/hero-390.png`. (The first version of the clamp,
with a 42 px floor, broke into 4 lines at 320 and 360 px; the container-query rule above
is the fix.)

### 5.3 Texture, grain and light

- **Grain:** a 128 × 128 grey noise PNG with 5% alpha baked in (about 3 KB), tiled as a
  second `background-image` on lime sections. Not an overlay layer, not animated, no blend
  modes (they cost compositing on phones). In WebGL, the shader dithers with the same
  noise to kill banding in the shafts.
- **Light in HTML (the CSS sun layer).** Sections without a live canvas still get
  sunlight: a pre-rendered transparent WebP of light patches (the Hoa thị pattern
  projected at the current page's sun angle, soft edges, `--sun-200`, about 25 KB) placed
  as a decorative `::before` on section backgrounds, moving at 0.12× scroll speed
  (`transform` only; the "parallax" CLAUDE.md now allows). Never behind body text; it sits
  in margins and behind display headings.
- **Shadows:** cool, from the shade: `--shadow-1: 0 1px 2px rgba(32,32,90,.06), 0 8px 24px
  rgba(32,32,90,.08)`. Only on things that lift (cards on hover, the menu sheet).
- **Radius:** architectural and small: `--r-sm 4px`, `--r-md 8px` for cards and panels.
  Buttons stay pill-shaped: the round lobes of the quatrefoil, and the shape the current
  site's yellow buttons already have.
- **Aurora, done as daylight.** aurora-ui's soft glows survive as sunlight, not as neon
  blobs: the only "glow" on the site is warm light bloom around the sun patches and in S3,
  computed in the shader (no `filter: blur()` layers, which are expensive on phones). No
  glassmorphism: plaster is opaque.
- **No ornament for its own sake.** The block pattern appears as light, as the four focus
  area icons and as the footer's top edge (a single row of block silhouettes against
  dusk, inline SVG, 28 px tall). Nowhere else.

---

## 6. Home page, section by section

Content comes from `content/draft/*.json` (`site.json`, `work.json`, `involve.json`,
`news.json`, `impact.json`, `ui.json`). Draft fields keep `data-draft` and the dashed
outline in the prototype, but the outline moves from every text block to a single
2 px dashed `--gold-700` underline on the draft text itself, plus the existing prototype
note in the footer, so the page no longer looks unfinished while still flagging drafts.
Section padding: 160 px top/bottom on desktop, 96 px on mobile (`--section-y:
clamp(96px, 10vw, 176px)`). No meta labels ("SECTION 01"), no eyebrow pills.

### 6.1 Nav

Logo (the HCWA mark + `logoLines`, "Hội Bảo trợ Trẻ em / TP. Hồ Chí Minh") left; main
menu (Về chúng tôi, Hoạt động, Tác động & Báo cáo, Tin tức, Chung tay, Liên hệ) center
right in Be Vietnam Pro 500 15 px; `VI / EN` with the current one bold and underlined,
`aria-current`, `hreflang`; **Ủng hộ** as the only sun-colored button. Over the hero: no
background, a 120 px top scrim (`--lime-100` 85% → 0); target ≥ 7:1 for nav text at every
sun position, checked by `light-contrast.mjs` (an early proof render without the scrim
measured 4.79:1 at the darkest pixel behind the nav at 1440, which is why the scrim
exists). Then the solid state in 4.6. Mobile (≤ 1023 px): logo mark, `VI / EN`,
Ủng hộ, "Menu" text button (≥ 44 × 44 px), as in `open/hero-390.png`.

### 6.2 Attention: hero (`100svh`, min 620 px)

Layout (Editorial bottom-left, gpt-taste's "Artistic Asymmetry"): the room fills the
viewport; text sits bottom-left on the back wall, the screen glows on the right.

- **H1:** "Cùng trẻ em xây dựng tương lai an toàn và tươi sáng hơn." / "Helping children
  build safer, brighter futures." (`site.json` hero.title; VI is a draft translation).
  Archivo 640, `--fs-display`, 2 lines at 1440, 3 at 390 (5.2).
- **Intro:** "Hội hỗ trợ trẻ em, thanh thiếu niên và gia đình qua giáo dục, bảo vệ trẻ em
  và các chương trình dựa vào cộng đồng." Be Vietnam Pro 400, `--fs-lg`, `--ink-900`,
  max 36ch, registered as a calm zone.
- **Buttons:** [Khám phá hoạt động] primary sun → Our Work; [Chung tay] secondary →
  Get Involved. Calm zone. Full width and stacked below 600 px.
- **No eyebrow line.** `site.json` hero.eyebrow contains the unverified founding year
  (`từ năm [năm]`); the header already carries the name, and a year-less eyebrow adds
  nothing. If HCWA verifies the year, it appears as the first plaque of 6.3, not here.
- **No stats, no badges, no pills** in the hero (gpt-taste; impact numbers belong to 6.3).
- Fits the first screen at 1440 × 900 and 390 × 844 with buttons visible (both renders).

### 6.3 Interest: what we do (opaque `--lime-100` sheet over the canvas)

1. **H2** "Chúng tôi làm gì" (`whatWeDo.title`), then **the statement** in
   `--fs-statement`: "Hội chăm sóc, bảo vệ và giáo dục trẻ em có nguy cơ phải lao động
   sớm, bị xâm hại, bỏ học hoặc bị ảnh hưởng bởi HIV/AIDS. Chúng tôi làm việc cùng gia
   đình, cộng đồng, chính quyền và các tổ chức đối tác." (`whatWeDo.summary`, draft)

   **Sunlit reading (kinetic type, 4.4):** the words are split into `<span>`s at build
   time (server-side, so no-JS gets plain text). As the paragraph crosses the viewport,
   a soft band of light moves through it word by word: each word's color goes
   `--ink-700` → `--ink-900` and a `--sun-200` highlight (`background-size` 0 → 100% on
   the word's `linear-gradient`) lights behind the current line. Driven by one
   `--p` custom property scrubbed by ScrollTrigger (start "top 75%", end "bottom 40%").
   Every word is readable at every moment: the darkest state is 7.41:1. Reduced motion /
   no JS: all words `--ink-900`, no highlight.

2. **Impact plaques** (`impact.json`, organization scope, 4 figures). Archivo 640
   `--fs-stat` with `tabular-nums lining-nums`, label below in Be Vietnam Pro, on
   `--lime-50` plaques with a 1 px `--lime-300` edge, in one row (2 × 2 on mobile). All
   four are unverified today, so on staging they show `[N]` with the "Đang xác minh"
   chip, and on production (`PROD=1`) the whole row is not rendered (fewer than 2
   verified). Never count up, never animate: they appear with the section. Link
   "Xem tác động và báo cáo →".

3. **Focus areas as a gapless bento** (`work.json` focusAreas). Each tile carries one
   block of the set (3.3), drawn as a lit SVG: the block in `--lime-300` line work with
   `--sun-200` light through its holes.

   | Tile | Block | Desktop span, 4-col grid (cols × rows) | Tablet span, 2-col grid |
   |---|---|---|---|
   | Giáo dục / Education | Hoa thị | 2 × 2 | 2 × 1 |
   | Bảo vệ trẻ em / Child protection | Mắt cáo | 2 × 1 | 2 × 1 |
   | Hỗ trợ gia đình và cộng đồng / Family & community | Vòng tròn | 1 × 1 | 1 × 1 |
   | Vận động chính sách / Policy & advocacy | Bậc thang | 1 × 1 | 1 × 1 |

   Desktop: rows `E E P P / E E F Po` = 8 cells of 8, `grid-auto-flow: dense`. Tablet:
   `E E / P P / F Po`, 6 of 6. Mobile: one column. Each tile: block art, name (Archivo 600
   `--fs-h3`), the one-line text, link to Our Work filtered by area. Hover: the light in
   the block art shifts as if the sun moved (the SVG's light layer translates 6 px,
   280 ms) and the tile lifts 2 px.

### 6.4 Desire 1: our centers (S2, transparent over the canvas)

- Stage header, top-left of the sticky stage: **H2** "Cơ sở trực thuộc" + `centersIntro`
  "Nơi trẻ em được học, được chữa lành và lớn lên, mỗi ngày." (draft). Registered as a calm
  zone.
- Three cards from `work.json` centers, one per door (S2 timing), in a `--lime-50` panel
  (opaque, so no calm zone is needed), 6 px top band in the unit color:
  - **Cơ sở Bảo trợ Xã hội Tre Xanh**: "Trẻ em và gia đình có hoàn cảnh khó khăn, hỗ trợ
    ngay tại cộng đồng" (draft), "Từ năm [năm]" (staging only), location "[phường], TP. Hồ
    Chí Minh" (draft), [Xem cơ sở →].
  - **Mái ấm Hoa Hồng Nhỏ**: "Trẻ em gái [độ tuổi] đã trải qua hoặc có nguy cơ bị xâm hại
    tình dục" (draft, age range hidden until verified). **No location line.** Then:
    `[DRAFT: Chúng tôi không đăng hình ảnh, tên hay nơi ở của các em. Cánh cửa này luôn
    đóng.]` / `[DRAFT: We never show the girls, their names or where they live. This door
    stays closed.]` [Xem cơ sở →].
  - **Dự án Cầu Hàn**: "Trẻ em và gia đình bị ảnh hưởng bởi HIV/AIDS, gia đình di cư và trẻ
    đã nghỉ học" (draft), [Xem cơ sở →].
- DOM order is the card order, all three are always in the DOM; the stage only changes
  which one is shown. When a card's link receives keyboard focus, `stages.js` scrolls to
  the middle of that card's progress window first (`lenis.scrollTo`, immediate under
  reduced motion), so focus never lands on a hidden card.
- "Xem tất cả" → Our Work hub, under the stage.

### 6.5 Desire 2: projects & programs, and news (opaque sheets)

- **H2** "Dự án & chương trình". Program **rows**, not cards (`work.json` programs):
  status pill (Đang hoạt động / Đã hoàn thành), years tokens, name in Archivo 600
  `--fs-h3`, summary, partner names as text ("CapitaLand Việt Nam" for Cùng Vươn Lên),
  arrow. Hover: the *lam* light stripe (4.6). Rows look nothing like the doors on
  purpose: places are doors, programs are light passing through.
- **H2** "Tin mới từ Hội". Featured story (`news.json` featuredStory: placeholder
  `[Tên câu chuyện]`, C2 photo slot, the "Tên nhân vật đã được thay đổi" note) large on
  the left; three real posts on the right with category and real dates (25/05/2026,
  25/05/2026, 07/03/2026), linking to the live posts. Featured-story image slot: until
  HCWA approves a photo, a lit wall still from the world at 3:2, never a stock photo.

### 6.6 Action: how you can help (S3, then `--sun-100`)

- S3 opens the shutters; the section below is flat `--sun-100` with the CSS sun layer in
  the margins.
- **H2** "Bạn có thể giúp như thế nào" (`helpTitle`).
- **Four louver rows** (`involve.json` ways), full width, each a slat: Archivo 600 title
  at `--fs-h2` on the left (Ủng hộ, Tình nguyện, Hợp tác, Hiện vật và Tiệm bánh Hoa Hồng
  Nhỏ), the one-line text and an arrow link on the right. Ủng hộ first, and it is the only
  row with a sun-filled button ("Ủng hộ"); the other three are text links. This is the
  biggest, highest-contrast moment on the page, and it is type, not cards.
- **Partners:** **H2** "Cảm ơn các đối tác đã đồng hành". One-tone logo row on
  `--sun-100` (logos at `--ink-900` 70%). Only CapitaLand Vietnam is known today (as the
  Thrive Together funder); the other slots stay C5 placeholders on staging and are not
  rendered on production. [Đối tác & nhà tài trợ →] [Trở thành đối tác →]. No marquee
  (a moving logo wall reads as filler with one logo).
- **Contact band** on `--blue-900` (the day ends): "Bạn có câu hỏi hoặc muốn đến thăm?"
  in Archivo 620 white, [Liên hệ với chúng tôi] (white outline button), phone
  `+84 28 3840 1406` and `hcwa.vn@gmail.com` (both draft, from `site.json`).

### 6.7 Footer (dusk)

`--blue-900` with the row of block silhouettes along its top edge and one warm lit
quatrefoil (a 12 px `--sun-400` shape) next to the address: the only light left at dusk.
Four columns on desktop, accordions on mobile, as `06-PAGES.md`: about blurb (draft),
address/phone/email (draft), Khám phá links, Báo cáo & chính sách. Bottom bar on
`--blue-950`: © year, Facebook link (a link, never an embedded feed), `VI / EN`. All text
white or `--on-dusk-2`.

---

## 7. How the other pages inherit the system

The rule: **live WebGL runs on the home page only.** Every other page uses stills rendered
from the same world (3.8), the CSS sun layer, reveals and micro-interactions. Each page
gets one "lit" header and is otherwise a calm reading page. One idea per page, never a
second showcase.

| Page | Header still (from the world) | Page-specific motion | Notes |
|---|---|---|---|
| Our Work hub | The room's screen seen frontally at 10:00, the four block patterns as filter chips | Filter chips: the chip's block icon lights (sun-200 fill) on select; list re-orders with a 240 ms FLIP | Centers as door cards (door stills at 4:3), programs as rows, exactly as on home, so the two kinds stay visibly different. |
| Center: Tre Xanh | Its green door, open 28°, daylight spilling out | Header light layer parallax 0.12× | 6 px green transom band at the top of the header. Sections: about, what happens here, impact (pending, hidden on prod), programs here (rows), visiting and contact (address token, draft). When approved photos come, they hang on lime-50 "wall" panels with a 1 px edge, never under the light layer. |
| Program: Cùng Vươn Lên / Thrive Together | *Lam* (louver) stripes of noon light on a wall: programs are light passing through | Header stripes drift 0.12× | Status pill, years tokens, funder "CapitaLand Việt Nam" as text (logo when supplied), the three centers it works in as small door chips, impact pending. |
| Get Involved | The open shutters at 17:00 (S3's end state) | Louver rows as on home | Continuity with the home Action section. FAQ accordion (`involve.json` faq, answers "[Answer supplied by HCWA]"). |
| Donate | None. A 6 px sun band only | Micro-interactions only | Trust page: no imagery near payment details. The payment panel is a `--lime-50` card with every bank field (`bankFields`, `abroadFields`) showing the "Chờ HCWA xác nhận" chip; copy buttons disabled; gift examples with `[số tiền]` tokens. Nothing here comes from anywhere but `content/verified/` once it exists. |
| Contact | The street side of the screen at 16:30, warm | Form states (4.6) | Map is a link ("Mở bản đồ"), not an embed. |
| 404 | "Một viên gạch bị thiếu": the screen wall with one block missing, light pouring through the gap | None | `nf_title`, `nf_text`, search. Static still, 1:1. |
| Little Rose (later) | Its closed pink door with daylight from the transom blocks | None | Never a figure, never a location, never an open door. The commissioned B2 illustration goes in the body, not the header. |
| Components | n/a | Every state of every component | Includes a light-layer demo and the contrast table. |

Header still sizes: 3:1 at 1600 / 1200 / 800 w on desktop, cropped to 4:3 on phones
(`<picture>` art direction; both crops rendered by `render-posters.mjs`), AVIF budget
≤ 70 KB at 1600 w. These stills also fill the C1 "page header images" slot in
`10-ASSET-REQUEST.md` until (or instead of) photos.

**WordPress port.** The stills are theme assets; the header is a block pattern
(`hcwa/lit-header`) with the still chosen by page template; the CSS sun layer, reveals,
View Transitions and micro-interactions are theme CSS/JS. The world module is enqueued on
the front page only (`is_front_page()`), as an ES module, after the content.

---

## 8. Mobile, accessibility, risks

### 8.1 Mobile plan (390 × 844)

- **Hero:** portrait shot (3.2), screen on the right, H1 3 lines at 40.8 px, intro 17 px
  at 1.65, two full-width 48 px buttons, all inside the first screen
  (`open/hero-390.png`). Canvas DPR 1.25, Tier 1 (SDF screen, ¼-res shafts, 160 motes),
  no pointer mapping, cloud pass on.
- **Statement:** 22 px, sunlit reading unchanged (it is cheap).
- **Bento:** one column; block art 72 px left of the title.
- **Corridor:** `220svh`, sticky stage 100svh: the 3D fills the stage, the camera frames
  the door in the top 56%, the card sits in the bottom 44% on `--lime-50`. Cards switch
  at the same thresholds.
- **Shutters:** `150svh`; on very short screens (< 640 px tall) the sticky stage is
  skipped and the open-shutters still is used.
- **Everything else:** single column, 16 px gutters, `--section-y` 96 px, touch targets
  ≥ 48 px, no hover-only content, no horizontal scroll (`overflow-x: clip` on `main`).
- **Data:** a first-time visitor on 4G loads about 90 KB (HTML with inline critical CSS,
  the two preloaded fonts, the 480 w poster) before the hero is complete, then about
  200 KB gzip more for the motion layer and the 3D after `load`. Save-data gets none of
  the 3D.

### 8.2 Accessibility

- All content is HTML in a logical order; the canvas is `aria-hidden="true"`,
  `role="presentation"`, not focusable.
- Contrast: measured pairs in 5.1, including over the live canvas. Calm zones are required
  for any non-display text over the canvas, enforced by `scripts/light-contrast.mjs` at
  390 and 1440 across sun positions.
- Reduced motion, no WebGL and no JS each give a complete page (3.8). Lenis off under
  reduced motion; View Transitions off.
- Sticky stages: focus management as in 6.4; every card is a real link in the DOM; skip
  link "Bỏ qua, đến nội dung chính" first in the tab order.
- No flashing: the largest luminance change (S3) is one-directional, scrubbed by the
  visitor's own scroll over about 52svh, and its brightest frame is `--sun-100`, not white.
- Vietnamese: `lang="vi"` on the page and `lang="en"` on English fragments (and the
  reverse on `/en/`), no uppercase, line-height ≥ 1.6 on body, U+00A0 compound joins in
  headings.
- Every UI string from `ui.json` in both languages; the new draft strings in this document
  are given in both.
- Safeguarding: no child appears anywhere in the 3D or the stills, by construction. Little
  Rose: door closed, no location, daylight only, explicit note in its card.

### 8.3 Honest risks

1. **Café drift.** Breeze blocks, terrazzo and painted doors are also a fashionable
   retro-Saigon café look. Guardrails: no rattan, no hanging plants, no neon, no coffee,
   no patterned-tile overload (gạch bông only at door thresholds); the camera stays at a
   child's height; the copy and the doors keep it about children's places. Tastegate
   reviewer question: "could this be a café?" If yes, cut decoration, not light.
2. **No people on the home page.** The site is about children and shows none. That is
   the safeguarding position, but donors may find it distant. When HCWA approves photos
   (`10-ASSET-REQUEST.md`), they belong on inner pages and in stories, hung on the walls;
   the hero stays architectural.
3. **The analytic light only works with flat screens.** Anything we add inside the room
   (a chair, a plant) casts no shadow unless we add a shadow map for that object alone.
   Keep the rooms empty; that is also the look.
4. **Performance is a budget, not a measurement.** The proof ran in software WebGL. Week
   1 needs a real Galaxy A15-class phone over 4G (or Chrome DevTools remote debugging) to
   confirm the ≤ 12 ms GPU frame for Tier 1. If the ¼-res shafts are too expensive, they
   are the first thing to go; the room still works without them.
5. **Calm zones can read as haze.** In the proof they look like soft mist around the
   intro. Tune the feather (48–96 px) and the lift; if it still reads as a patch, move the
   intro onto a `--lime-50` panel instead.
6. **Sticky stages are long on phones.** 220svh of corridor is three thumb flicks. If
   testing shows people skip it, cut to 180svh or drop the camera walk on mobile and keep
   the three door stills with cards.
7. **Archivo condensed is a strong voice.** It is louder than the current site and than
   Be Vietnam Pro. If HCWA finds it severe, widen to `wdth 88` (the H1 then needs the
   container-query sizes re-measured).
8. **The "bảo trợ" rationale is ours, not HCWA's.** It explains the choice to Maxwell
   and HCWA; it is not site copy unless they want it.
9. **The Little Rose door is a symbol for a real shelter.** HCWA should approve the
   closed-door treatment and its note before it is shown to anyone outside the team.
10. **Cross-document View Transitions are Chromium-only today.** Others get instant page
    swaps; nothing breaks.
11. **GitHub Pages serves gzip, not brotli, and cannot set cache headers.** Budgets above
    use gzip numbers. Netlify (planned) fixes both.
12. **Scope.** The world (room, corridor, shutters), three signatures, posters, tiers and
    the contrast audit are about two weeks of focused work, not one. Suggested order:
    days 1–3 world core + room + S1 + poster pipeline; 4 interest + statement + bento; 5–6
    corridor (S2); 7 shutters (S3); 8 inner-page headers + View Transitions; 9 device
    testing + tier tuning; 10 tastegate at 390/1440, contrast audit, reduced-motion pass.
13. **Not used:** the promptkura.me library Maxwell mentioned sits behind a sign-in and
    could not be read from this environment.
