# Ba nơi chốn / Three Places

Art direction concept for the HCWA site, one of three competing directions written on
6 Nov 2026 after Maxwell rejected the Phase 1 prototype. Written for the engineer who builds
it next week. HCWA approves every word, number and image before anything goes live; nothing
here changes the hard rules in `CLAUDE.md`.

Files: this document, plus `three-places-hero-specimen.png` (the Vietnamese hero line set
in the chosen display face at 1440px and 390px, rendered from the real font file).

---

## 0. What this replaces

The Phase 1 prototype (`qa/phase1-screens/home-vi-*.png`) is a correct wireframe with no
visual subject. Grey boxes labelled A2, B1 and C5 stand where the art should be. Dashed
draft outlines sit on almost every block. Every section is a row of equal tiles. The
palette and type (cream paper, Fraunces, one yellow button) are the default "warm NGO"
look, and nothing on the page could only belong to HCWA. Adding effects to that layout
won't fix it. It needs a subject, and HCWA already has one: the names of its three units.

---

## 1. Name, pitch, and why it is HCWA's and nobody else's

**Name:** Ba nơi chốn (Three Places).

**Pitch:** Ho Chi Minh City as a hand-cut pop-up book. The page opens, and a bamboo grove,
a rose behind a folding screen and an unfinished bridge stand up out of the paper. Scrolling
carries you along the association's blue river through each one, each cut from its unit's
own colored paper, from afternoon into a lantern-lit night.

**Why it fits HCWA only:**

- **The three objects are the three names.** Tre Xanh is green bamboo, Hoa Hồng Nhỏ is a
  little rose, and *cầu* in Cầu Hàn means bridge. Give this concept to another NGO and the
  world means nothing.
- **The river is the logo blue (`#313188`).** The association is the thing that runs
  between the places. Saigon is a city of rivers, canals and bridges, so the metaphor is
  also a portrait of the city.
- **No photos of children exist, and Little Rose may never have any.** A paper world needs
  none. The thing that produced grey boxes becomes the style.
- **The safeguarding rule becomes a design rule.** Little Rose has no address on this site
  (`09-QA-CHECKLIST`: "the Little Rose page shows no location and no invitation to
  visit"). So in the book it has **no place on the map**. It rises out of the *spine*,
  the fold between the two pages, inside a ring-shaped *bình phong*, the folding screen
  Vietnamese homes and temples set up to shield the household. The camera looks over the
  screen from above and never goes inside. There are no figures, no lit windows with
  people, no path and no gate.
- **The bridge is built from both banks while you scroll**, and its two halves meet just
  before the page reaches "Chung tay" (join hands), the Get Involved label HCWA already
  uses.
- **Cut paper and lanterns belong to Tết Trung Thu**, the Vietnamese children's festival.
  The look reads as home to Vietnamese families and as crafted, not cartoonish, to
  international donors.

---

## 2. Signature moments (three, no more)

Everything else on the site stays calm so these three land.

### S1. The book opens (home hero)

**What the visitor sees.** The hero text is there at once, on a paper-colored sky. Under
it lies the open book with every piece folded flat. This is an inline SVG poster of the
flat state, so it paints with the HTML. When the 3D is ready, the pieces stand up the way
a pop-up book does. The folding screen unfolds around the rose in the spine. The bamboo
rows stand up from back to front. The narrow tube houses (*nhà ống*) on the right page
stand up. Two planks reach out from each bank where the bridge will be, then the lanterns
come on.

**Trigger.** All four must hold: the world module is ready (§3.8); the hero is at least 50%
in view; this is the first view this session (`sessionStorage` key `hcwa:popped`); and
motion is on. On later page views in the same session the book starts open. If the
visitor has already scrolled more than 40% of the hero before the world is ready, the
whole sequence is time-scaled to 600ms.

**Timeline** (one GSAP timeline, played once, not scrubbed; times in ms):

| Start | What | Duration | Ease |
|---|---|---|---|
| 0 | Canvas fades in over the flat poster (same frame, so nothing jumps) | 200 | `none` |
| 0 | Camera dolly from 106% of its distance to 100% | 1500 | `power3.out` |
| 80 | Screen panels (10) unfold around the rose, front panel first, then both ways round | 520 each, stagger 32 | `back.out(1.3)` |
| 180 | Bamboo rows stand up, back row first; stalks inside a row stagger 18 | 620 each, row stagger 90 | `back.out(1.5)` |
| 300 | Pavilion beside the grove | 600 | `back.out(1.4)` |
| 300 | Tube houses (12), left to right | 560 each, stagger 35 | `back.out(1.4)` |
| 520 | Rose stem rises; the bud stays closed (`uBloom` 0.15) | 700 | `power2.out` |
| 820 | Bridge abutments plus two planks from each bank. The bridge stays unfinished | 400 | `back.out(1.6)` |
| 900 | Lanterns on: emissive 0 to 1, glow sprites 0 to 1 | 300 each, stagger 60 | `power1.out` |
| 1100 | Idle loops start: bamboo sway, river drift, paper boat | ongoing | see §3.6 |

Every standing piece is a `Group` whose origin sits on its hinge line on the page. It
rotates on `rotation.x` from `-π/2` (lying flat toward the viewer) to `0`. The overshoot of
`back.out` is 6 to 9 degrees, which reads as paper springing, not rubber.

**Never animated:** the H1, intro and buttons. They paint immediately and are the LCP
element (see §3.8). The paper moves; the message doesn't.

### S2. Along the river (home, pinned scroll through the three places)

**What the visitor sees.** After the "what we do" sheet scrolls past, its hand-cut bottom
edge reveals the city again. The camera drops toward the river and visits the three places
in order, and the scroll builds each one. At Tre Xanh the bamboo grows. At Hoa Hồng Nhỏ
the screen unfolds and the rose opens. At Cầu Hàn the bridge is laid plank by plank from
both banks until the halves meet. A paper card with the unit's name, who it serves and a
link sits beside the scene on desktop and under it on mobile. The light moves from
afternoon to golden hour to dusk.

**Trigger and length.** A ScrollTrigger pin on `#ba-noi-chon .places__stage`, `start: "top
top"`, `end: "+=300%"` on desktop and `"+=270%"` on mobile, `scrub: 0.5` on desktop and
`scrub: true` on mobile. A separate approach tween (camera from the establishing shot to the
Tre Xanh shot) scrubs from `"top bottom"` to `"top top"` before the pin starts. Each place
holds still for about 35% of its beat so its card can be read with nothing moving. The full
beat table is in §4.5.

**The join** (the one non-scrubbed event inside S2). When pin progress crosses `p = 2.72`
going forward, a 900ms timeline plays:

| Start | What | Duration | Ease |
|---|---|---|---|
| 0 | The two railing halves slide the last 0.18 units together | 360 | `cubic-bezier(.23,1,.32,1)` |
| 200 | Eight lanterns along the railing light in pairs from both ends toward the middle | 240 each, stagger 70 | `power1.out` |
| 300 | Camera eases back 0.12 units | 600 | `power2.out` |
| 300 | One paper ripple ring spreads on the canal under the bridge (scale 0.2 to 1.4, opacity 0.5 to 0) | 600 | `power2.out` |

Scrolling back across 2.72 reverses it at 1.6x speed. Under "pause motion" the join snaps
to its end state with no tween.

### S3. The page turn (every page, cross-document View Transitions)

**What the visitor sees.** Following a link turns the page. The new page wipes in from the
right edge, the old page slides 6% left and dims slightly, and the header stays perfectly
still. Going back turns the page the other way. Switching VI/EN does not turn the page,
because it is the same page; it does a 180ms crossfade instead.

**Trigger.** Any same-origin navigation in a browser that supports cross-document view
transitions (Chrome and Edge 126+, Safari 18.2+). Firefox navigates normally. That is the
fallback, and it is fine.

```css
@view-transition { navigation: auto; }
.site-header { view-transition-name: site-header; }

::view-transition-old(root) { animation: 360ms var(--ease-in-out) both vt-turn-out; }
::view-transition-new(root) { animation: 480ms var(--ease-in-out) both vt-turn-in; }
@keyframes vt-turn-out { to { transform: translateX(-6%); filter: brightness(.92); } }
@keyframes vt-turn-in  { from { clip-path: inset(0 0 0 100%); } to { clip-path: inset(0); } }

html:active-view-transition-type(back) ::view-transition-old(root) { animation-name: vt-turn-out-back; } /* +6% */
html:active-view-transition-type(back) ::view-transition-new(root) { animation-name: vt-turn-in-back; } /* inset(0 100% 0 0) -> 0 */
html:active-view-transition-type(lang) ::view-transition-old(root),
html:active-view-transition-type(lang) ::view-transition-new(root) { animation: 180ms ease both vt-fade; }

@media (prefers-reduced-motion: reduce) {
  ::view-transition-group(*), ::view-transition-old(*), ::view-transition-new(*) { animation: none !important; }
}
html[data-motion="off"] ::view-transition-old(*), html[data-motion="off"] ::view-transition-new(*) { animation: none !important; }
```

The types are set in a `pagereveal` listener. For `back`: `navigation.activation.navigationType
=== "traverse"` and the entry index is below the `from` index. For `lang`: the language
links carry `data-lang-switch`; a click writes `sessionStorage["hcwa:vt"] = "lang"`, and
`pagereveal` reads and clears it before calling `e.viewTransition.types.add(...)`.

---

## 3. The 3D

All of it is procedural three.js (0.187.0, already installed). No Blender is needed and no
GLB is downloaded. There are no image textures: paper fibre is generated in the shader.
Everything is built from **one shared description of 2D cut-out shapes**. That same data
drives the runtime scene and the build-time SVG posters (§3.10), so the poster and the
canvas always show the same thing.

### 3.1 World layout (world units, Y up, page surface at y = 0)

- **Book**: two pages, each 10 (x) by 7 (z), meeting at the spine x = 0. Board thickness
  0.12. Near the spine the page surface dips into the gutter: `y = -0.35 * (1 - min(|x|/1.2, 1))^2`.
  Each page is a `PlaneGeometry(10, 7, 48, 1)` laid flat and displaced on the CPU once.
  The board edges are separate thin boxes, merged.
- **River** (HCWA blue): a `CatmullRomCurve3` through (x, z) points (-10, -2.6),
  (-7.5, -0.4), (-4.2, 0.9), (-1.6, 2.4), (1.8, 2.6), (4.2, 1.2), (6.0, 0.4), (8.2, 1.6),
  (10, 2.9) at y = 0.004. It is a ribbon 0.9 wide on the left page, widening to 1.6 between
  x = 4 and x = 7 where it becomes the canal. The river passes **in front of** the spine
  garden (z ≈ 2.5) and never touches it.
- **Tre Xanh** (left page): the grove is centered at (-6.4, 0, -1.6) with four rows at
  z = -2.9, -2.2, -1.5 and -0.8, and the pavilion sits at (-4.4, 0, -0.2).
- **Hoa Hồng Nhỏ** (spine): the screen ring is centered at (0, 0, -0.6) with radius 1.1;
  the rose is in the middle.
- **Cầu Hàn** (right page): the bridge spans the canal along z from (5.4, 0, -0.5) to
  (5.4, 0, 1.9), with the arch rising 0.55. Tube houses stand on the north bank
  (z = -2.8 to -0.8, x = 2.5 to 9.5), and three low houses sit on the south bank front-right.

### 3.2 Paper material

One material factory, `makePaper({ color, core })`. It returns a `MeshLambertMaterial`
patched with `onBeforeCompile`, so three's own lighting and shadow chunks still work. It
adds three things:

1. **Fibre.** Two-octave value noise on world position
   (`fbm(vWorld.xz * 38.0 + vWorld.y * 12.0)`) multiplies `diffuseColor.rgb` by
   `1.0 + (n - 0.5) * 0.06`. That is a ±3% luminance tooth, invisible as a pattern and
   obvious as material.
2. **Cut edges show the paper core.** A per-vertex attribute `aEdge` is 1 on extruded side
   walls and 0 on caps. Set it from the `ExtrudeGeometry` groups: group 0 is caps and
   group 1 is sides. The fragment mixes toward `uCore`, a lighter version of the face
   color (`mix(color, #FFF8EC, 0.55)`), by `aEdge * 0.55`. That light rim is what makes
   it read as card stock and not plastic.
3. **Lit windows and lanterns.** Optional `aGlow` attribute times `uLights` (0 to 1) mixes
   toward `--den-200`. This drives house windows and the pavilion lantern without any
   point lights.

Colors are set with `color.setHex(hex, SRGBColorSpace)` from a generated `tokens.mjs`
(§3.13). Renderer: `outputColorSpace = SRGBColorSpace`, `toneMapping = NoToneMapping`,
so a token hex on a face facing the light lands within a few percent of the CSS color.
`alpha: false`: the canvas is opaque, and the sky is drawn in WebGL (§3.4).

### 3.3 Objects

Every piece of geometry is extruded from a 2D `Shape` defined in `world/shapes.mjs`. Extrude
depth is 0.012 (one sheet of card) with no bevel, unless stated.

| Object | Built from | Instancing / draw calls | Triangles (desktop / mobile) | Animation hooks |
|---|---|---|---|---|
| Book pages + boards | Displaced planes + thin boxes, merged | 1 | 0.4k | none |
| River ribbon | `ShapeGeometry` from the curve offset ±width/2, 400 samples | 1 | 0.8k | `uDrift` moves a stripe pattern along the curve's u |
| River wave strips | One cut-paper crest shape (0.3 long), placed along the curve | `InstancedMesh` ×30, 1 call | 1k | Instances drift 0.05 u/s, wrap around |
| Bamboo stalks | Procedural silhouette: width 0.12, height 2.6 to 3.6, a node every 0.45 with a 0.015 notch and a 0.01 ridge | `InstancedMesh` ×48 (mobile ×28), `instanceColor` by row: back `--tre-300`, then two rows `--tre-600`, front `--tre-800` | 18k / 10k | `uGrow` (per-instance `aDelay`) scales y; vertex sway (§3.6) |
| Bamboo leaves | Lance-shaped leaf, 0.34 long | `InstancedMesh` ×220 (mobile ×120), attached at stalk heights 55 to 100% | 3.5k / 1.9k | Scale 0 to 1 when its stalk's growth passes its attach height |
| Pavilion | Open-sided classroom: curved roof shape, four posts, low rail; `--giay-0` with `--tre-600` roof | Merged, 1 | 1k | Hinge; lantern via `aGlow` |
| Bình phong (screen ring) | 10 panels, each 0.66 wide by 0.9 tall, with a scalloped top edge and three cut-out lattice holes (`Shape.holes`) in a four-petal flower pattern; outside `--hong-100`, inside `--hong-300` | `InstancedMesh` ×10, `DoubleSide`, the inside color chosen with `gl_FrontFacing` | 3k | Per-panel hinge angle from `uScreen` plus index |
| Rose | 26 petals in three rings (5, 8, 13) at a 137.5° phyllotaxis step. Each petal is a `PlaneGeometry(1, 1.2, 6, 8)` cupped and edge-curled in the vertex shader. Outer face `--hong-500`, inner `--hong-300`. Stem plus two leaves in `--tre-600` | Petals `InstancedMesh` ×26 (1 call); stem and leaves merged (1 call) | 2.5k + 0.3k | `uBloom` 0 to 1: per-ring tilt from 10° to 55/65/75°, curl grows with bloom |
| Tube houses (*nhà ống*) | 12 tall narrow facades, 0.45 wide, 1.0 to 1.9 tall, with balcony cutouts and window holes; vertex colors from `--cau-100`, `--giay-0`, `--cau-300`, one `--den-200`, one `--tre-100` | Merged, 1 | 4.8k | Hinge per house through a vertex attribute `aHouse` plus a `uHinge[12]` uniform array; windows via `aGlow` |
| Bridge planks | 18 boxes 0.18 × 0.03 × 0.5 placed along the arch (quadratic Bézier) | `InstancedMesh` ×18, 1 call | 0.2k | Per-plank drop from +0.5 y with `rotation.z` 0.4 to 0; order alternates banks |
| Bridge railings | Two half-span side panels (left, right) with lattice cut-outs, `--cau-500`, core edges `--cau-100` | 2 calls | 1.2k | Halves slide together at the join |
| Abutments and banks | Extruded bank edges, merged | 1 | 0.6k | Hinge |
| Lanterns | Short paper cylinders, 8 on the bridge, 1 in the pavilion, 3 at house doors | `InstancedMesh` ×12, `MeshBasicMaterial`, `instanceColor` lerps `--cau-100` to `--den-400` | 0.6k | `lit` per instance |
| Glow sprites | Camera-facing quads; the fragment is a radial falloff `a = pow(1 - length(uv*2-1), 2)` in `--den-200` | `InstancedMesh` ×24, additive, `depthWrite: false` | 0.05k | Intensity per instance |
| Paper boat | Folded-boat shape (two extrusions) in `--giay-0` | 1 | 0.2k | Ping-pongs along river u 0.10 to 0.45, 22 s, `sine.inOut` |
| Contact shadows (low tier only) | One soft disc per piece cluster (radial alpha quad) | `InstancedMesh` ×20 | 0.04k | none |

**Totals:** about 38k triangles and 17 to 18 draw calls on desktop (plus a shadow pass of
about 14 calls on the mid and high tiers). About 26k triangles and 17 calls on mobile.

There are no people anywhere in the world, in any unit. That is a safeguarding rule here,
not a style choice: no silhouettes, no figures in windows, no children on the boat.

### 3.4 Lighting, sky and time of day

- `HemisphereLight(sky, ground = --giay-200, 1.1)` and one warm `DirectionalLight` from
  (-0.6, 1.0, 0.5). No point lights; glows are sprites and emissive faces.
- **Shadows** on the mid and high tiers only: `shadowMap.type = PCFShadowMap`, one
  orthographic shadow camera fitted tightly to the book (14 × 10), map size 1024 on desktop
  and 512 on mobile, `bias -0.0004`, `normalBias 0.02`. On the low tier shadows are off and
  the contact-shadow discs switch on.
- **Sky** is a full-screen quad drawn first, with `depthTest: false`, as a vertical gradient
  with two uniforms. The renderer's clear color equals the top stop. In the hero the sky is
  exactly `--giay-50` top to bottom, so the H1 sits on the token color and its contrast is
  known (14.74:1).
- **One uniform `uDusk` (0 to 1)** drives everything time-of-day, by lerping between four
  keyframes:

| uDusk | Key light | Hemisphere sky | Sky top / horizon | `uLights` |
|---|---|---|---|---|
| 0 (afternoon, hero) | `#FFF1D6` × 2.2 | `#FFF4E0` | `#F7F1E6` / `#F7F1E6` | 0.25 |
| 0.45 (golden, rose) | `#FFC27A` × 2.0 | `#F8DDB8` | `#F3D8B4` / `#F7E7C9` | 0.5 |
| 0.75 (dusk, bridge) | `#FF9E6B` × 1.3 | `#6C5FA8` | `#4B3F86` / `#E9A97A` | 0.85 |
| 1 (night, Action section) | `#8A90E0` × 0.6 | `#1E1E5C` | `#121238` / `#24245E` | 1 |

Lantern flicker: intensity 1 ± 0.06 driven by smooth noise at about 0.7 Hz. That is far
below any flash threshold (WCAG 2.3.1), and it stops under pause.

### 3.5 Camera shots

Shots live in `world/shots.mjs`. Values are tuned for a 16:9 desktop. `fitShot(shot,
aspect)` keeps each shot's target sphere in frame: when `aspect < 1` it uses the horizontal
FOV, `2·atan(tan(vfov/2)·aspect)`, and pushes the camera back along its view direction.

| Shot | Position | Target | FOV | Notes |
|---|---|---|---|---|
| `heroWide` | (0.4, 7.2, 17.5) | (0.4, 0.6, 0.2) | 28 | Panorama strip. `setViewOffset` pushes the book down so it fills the bottom ~45% of the hero (see the safe zone below) |
| `heroPortrait` (aspect < 0.8) | (7.5, 6.0, 13.5) | (-1.2, 0.8, -0.4) | 40 | Three-quarter view along the river, so the three places line up in depth on a phone: bamboo far left, screen in the middle, bridge near right |
| `tre` | (-3.6, 2.4, 4.8) | (-6.4, 1.6, -1.6) | 32 | Low, looking up into the grove |
| `hong` | (0.0, 4.6, 4.4) | (0.0, 0.9, -0.6) | 30 | Looks down at about 37°, always above the top of the screen. Never at eye level, never inside the ring |
| `cau` | (8.6, 1.3, 5.2) | (5.4, 0.7, 0.7) | 32 | Low by the water, the bridge against the dusk sky |
| `night` | (0.4, 8.4, 18.5) | (0.4, 0.6, 0.2) | 28 | The whole spread at night, for the Action section |

Moves between shots tween position and target separately with `power2.inOut`. The path
lifts +0.6 to +0.8 in y at its midpoint so the camera never passes through bamboo or
houses. The camera never rolls. Rotation is at most 30° across a beat. It moves 1:1 with
scroll and is never amplified (vestibular safety).

**Text safe zone (hero).** On load and on resize, `fitHero()` reads the DOM rects of the H1,
the intro and the buttons (plus 16px of padding). It then binary-searches the
`setViewOffset` y so that no projected bounding box of a standing piece overlaps those
rects. That keeps the text on flat `--giay-50` sky at every aspect ratio, deterministically
and without a scrim. In dev builds an overlay draws the safe zone in red.

### 3.6 What scroll and pointer drive

| Driver | Effect |
|---|---|
| Hero scroll (hero top to hero bottom) | Camera dollies forward 6% and its target drops 0.2 units, so the city comes closer as the "what we do" sheet slides over it |
| Approach to `#ba-noi-chon` | `heroWide` to `tre`, scrubbed; `uDusk` 0 to 0.1 |
| Pinned journey | §4.5 beat table |
| Action section in view | Camera rises 0.6 y across the section's scroll, scrubbed (parallax with the night content) |
| Lenis velocity | Bamboo sway impulse (clamped 0 to 1, critically damped spring, k = 60) and river drift speed × (1 + 0.5·v) |
| Pointer (desktop, `pointer: fine` only, hero and holds only) | Target offset ±0.35 x / ±0.2 y from the normalized pointer, through a critically damped spring (stiffness 60, damping 2√60, about 300ms of settle). Pointer over the screen ring's projected bounds in the hero opens the bud by +0.08. The bridge ignores the pointer |
| Idle (stops under pause) | Bamboo sway: vertex x offset `sin(t·1.5 + phase)·0.015·(y/h)²`. River strips drift. Boat ping-pong. Lantern flicker |

Touch devices get no pointer effects and no device-orientation input, because iOS asks for
permission and that prompt is not worth it.

### 3.7 Budgets

| Item | Budget | Measured or estimated |
|---|---|---|
| three.js chunk (the classes we use, tree-shaken) | ≤ 150 KB gzip | **142 KB gzip / 567 KB min, measured** 6 Nov 2026 with Vite 8 on three 0.187.0 |
| GSAP + ScrollTrigger + Lenis chunk | ≤ 55 KB gzip | **49.5 KB gzip, measured** |
| World code (shapes, builders, choreography) | ≤ 20 KB gzip | estimate |
| Downloaded textures / models | 0 | — |
| Inline hero poster SVG (flat + popped groups) | ≤ 14 KB gzip | estimate, measure after `world-posters.mjs` exists |
| Draw calls | ≤ 45 mobile, ≤ 70 desktop including the shadow pass | ~18 + ~14 |
| Triangles | ≤ 60k mobile, ≤ 120k desktop | ~26k / ~38k |
| GPU memory | ≤ 24 MB | 1024² shadow map 4 MB, geometry < 3 MB |
| Frame time, mid-range Android (Galaxy A15 / Redmi Note 13 class, Mali-G57 or Adreno 610) | p75 ≤ 16.7 ms target, hard floor 33 ms | Measure on a real device over USB remote debugging before HCWA review |
| Frame time, desktop | p75 ≤ 8 ms on integrated graphics | measure |
| JS work per frame | ≤ 4 ms main thread (uniforms, a handful of instance matrices during growth only) | measure |
| Homepage transfer | < 1 MB (09-QA) | About 0.5 MB: fonts 140 KB, motion 50 KB, world ~160 KB, HTML/CSS ~65 KB, logo and posters ~60 KB |

**DPR caps:** `pointer: coarse` gets 1.25. Desktop gets `min(devicePixelRatio, 1.75)`, and
the high tier gets 2. `antialias: true` throughout: tile-based mobile GPUs resolve MSAA
cheaply, and paper edges need it.

### 3.8 Loading order (text first, 3D last)

1. HTML paints the hero text, and the inline SVG poster paints with it. **The H1 is the LCP
   element** (inline `<svg>` shapes are not LCP candidates), so LCP is just HTML plus the
   two preloaded fonts.
2. A tiny inline `<head>` script (under 600 bytes) decides whether to set `html.will-pop`.
   It requires no reduced motion, no `data-motion="off"`, no `navigator.connection?.saveData`,
   `"WebGL2RenderingContext" in window`, and no `hcwa:popped` this session. With
   `will-pop`, CSS shows the poster's **flat** group. Otherwise it shows the **popped**
   group.
3. `main.js` (core UI, ≤ 12 KB gzip) runs as usual.
4. After the first LCP entry (`PerformanceObserver`, with a fallback of `load` + 300ms),
   `import("./motion/index.mjs")` loads Lenis, GSAP, reveals and the ink fill.
5. Then, in `requestIdleCallback` (timeout 2000), if `will-pop` or the motion tier allows
   it: a real WebGL2 context probe (`canvas.getContext("webgl2", { failIfMajorPerformanceCaveat:
   true })`), then `import("./world/index.mjs")`. The world is built in four idle slices
   (book and river, bamboo, rose, bridge and houses), each awaiting `scheduler.yield()`
   where available (else `setTimeout 0`), then `await renderer.compileAsync(scene, camera)`
   so shader compiling uses `KHR_parallel_shader_compile` and doesn't block input. Target:
   no long task over 120ms from world init.
6. The canvas fades in over the poster, which shows the identical frame. S1 plays and
   `html.world-live` is set.
7. **Safety net:** `.will-pop .poster .pop { animation: pop-show 0s 6s forwards }`. If the
   world hasn't gone live within 6 s (failed chunk, blocked GPU, offline), CSS shows the
   popped poster by itself. The world module cancels the timer by setting `world-live`.

### 3.9 Tiers and live downgrade

The world starts at the **mid** tier: DPR cap as above and shadows on (512 on mobile, 1024
on desktop). After S1 it measures 90 frames.

- p75 frame interval > 22 ms: drop to **low** (DPR 1.0, shadows off with contact discs on,
  leaves halved, wave strips ×12).
- Still > 30 ms on low: **static mode**. Stop the loop, show the per-shot posters, and
  journey cards keep working with posters crossfading per beat (the reduced-motion layout
  in §3.11, but with the pin kept).
- p75 < 9 ms on a desktop: **high** (DPR up to 2, PCFSoft shadows, 1024 maps).

No user-agent sniffing. `deviceMemory` and `hardwareConcurrency` are logged for the
device-test report but don't decide anything.

**Render loop:** `renderer.setAnimationLoop(tick)` runs only while the hero, the journey or
the Action section intersects the viewport (one `IntersectionObserver` over the three) and
`document.visibilityState === "visible"`. Inside `tick`, a frame is rendered only if
`needsRender` (a scroll, pointer or timeline change) or an idle loop is active. With motion
paused and the page still, the GPU does nothing.

### 3.10 Posters: no WebGL, no JS, reduced motion

`scripts/world-posters.mjs` runs in Node at build time, using three.js for maths only (no
WebGL, so it works in this GPU-less environment). It imports `world/shapes.mjs`,
`world/layout.mjs` and `world/shots.mjs`. For each requested state it:

1. builds the same pieces at their final transforms for that state;
2. projects each piece's front-face outline through the shot's camera, including its
   `setViewOffset`;
3. sorts pieces back to front by projected centroid depth (painter's order works because
   every piece is a flat standing card);
4. shades each face flat on the CPU with the same Lambert terms and `uDusk` keyframe;
5. projects each piece's silhouette onto the page along the light direction as a shadow
   path at 12% `--muc-900`;
6. writes SVG with 1-decimal coordinates and merged same-fill paths.

Outputs go to `prototype/public/art/` (and later to the theme's `assets/art/`):

| File | Used for |
|---|---|
| `poster-hero.svg` | Inlined in the home hero. Groups: `.flat` (pieces folded flat) and `.pop` (pieces standing, bridge unfinished), plus a `heroPortrait` variant swapped in with `<picture>`-style CSS at `(max-aspect-ratio: 4/5)` |
| `poster-tre.svg`, `poster-hong.svg`, `poster-cau.svg` | Journey beats when there is no WebGL, reduced motion or static tier; Our Work cards until photos arrive (Hoa Hồng Nhỏ keeps its poster forever) |
| `poster-night.svg` | Action section background without WebGL |
| `poster-map.svg` | Top-down spread for the Our Work hub header (the rose stays in the spine; no geography) |
| `layers-tre-{back,mid,front}.svg` | The Green Bamboo page header, split into three parallax layers |

Posters are decorative (`alt=""`, `aria-hidden="true"` on inline SVG). Everything they
show is also said in HTML text.

### 3.11 Reduced motion

`prefers-reduced-motion: reduce` means the three.js chunk is **never downloaded**. The hero
shows the popped poster. There is no pin: `#ba-noi-chon` renders as three stacked
`<li class="place">` items, each with its own poster beside its card (two columns on
desktop, stacked on mobile). No Lenis, no reveals, no ink fill (text is full ink), no idle
CSS loops, no page-turn transitions. Nothing is hidden waiting for an animation that will
never come.

`html[data-motion="off"]` (the on-page pause toggle, §8.2) keeps WebGL and scroll-scrubbed
changes, which are user-driven. It stops every automatic loop, disables Lenis smoothing,
snaps the bridge join, and skips page turns.

### 3.12 Later: Blender assets

Every object module exports `build(params) → Object3D` and `setProgress(name, value)`. A
Blender-authored GLB can replace a procedural object later if it keeps those hooks:

- Rose: shape key `bloom`, or 26 bones `petal_00`–`petal_25`.
- Bridge: nodes `plank_00`–`plank_17` and `rail_L` / `rail_R`.
- Bamboo: one stalk mesh, instanced at runtime.

Ceiling: 150 KB per GLB with meshopt compression, no textures over 512². The poster script
keeps using `shapes.mjs` either way, so posters stay in sync.

### 3.13 Code layout

```
prototype/src/
  world/
    tokens.mjs        generated from styles/tokens.css by scripts/tokens-to-js.mjs (never hand-edited)
    shapes.mjs        pure 2D outlines (no three import): bambooStalk(h, seed), leaf(), screenPanel(i),
                      petal(), houseFacade(kind), railingHalf(side), pavilionRoof(), boat()
    layout.mjs        placement data: position, hinge axis, color token, beat owner, poster group
    shots.mjs         camera shots + fitShot()
    paper.mjs         makePaper(), glow material, sky quad
    build/*.mjs       book, river, bamboo, rose, bridge, houses, lanterns, boat
    stage.mjs         renderer, tiers, DPR, resize, visibility, pause, needsRender loop, fitHero()
    choreo.mjs        popUp(), heroScrub(), journey(), join(), night()
    index.mjs         gates, chunked build, compileAsync, poster crossfade
  motion/
    index.mjs         Lenis, ScrollTrigger config, matchMedia, reveals, ink fill, header state,
                      pause toggle, view-transition types
scripts/
  world-posters.mjs   Node: shapes + layout + shots -> SVG posters
  tokens-to-js.mjs    CSS custom properties -> world/tokens.mjs
```

All asset URLs go through `import.meta.env.BASE_URL` (the prototype is served from
`/hcwa-rebuild/` on GitHub Pages).

---

## 4. Motion system

Seed: **Silk** (smooth, composed) for the UI. The paper hinges are the only springy thing
on the site, so bounce means paper and nothing else.

### 4.1 Easing tokens

```css
:root {
  --ease-out:    cubic-bezier(.23, 1, .32, 1);     /* UI enters, hovers that move, reveals */
  --ease-in-out: cubic-bezier(.77, 0, .175, 1);    /* things moving on screen: page turn, camera (CSS side) */
  --ease-drawer: cubic-bezier(.32, .72, 0, 1);     /* mobile menu sheet */
  --ease-paper:  cubic-bezier(.34, 1.36, .64, 1);  /* CSS twin of back.out(1.4): card glyph hinges only */
  --ease-color:  ease;                             /* color and background changes */
}
```

GSAP equivalents: `--ease-out` ≈ `expo.out`, camera moves `power2.inOut`, hinges
`back.out(1.3–1.6)`, and scrubbed tweens `none` (scroll is the easing). No `ease-in` on
anything the user waits for.

### 4.2 Duration scale

| Token | ms | Used for |
|---|---|---|
| `--dur-1` | 120 | Press scale, focus ring |
| `--dur-2` | 180 | Hover color, link underline, VI/EN crossfade |
| `--dur-3` | 240 | Header compact, chevrons, card lift |
| `--dur-4` | 320 | Menu sheet close, accordion |
| `--dur-5` | 420 | Menu sheet open; old page in the page turn (360) |
| `--dur-6` | 560 | Section reveals; new page in the page turn (480) |
| `--dur-7` | 900 | The bridge join |
| `--dur-8` | 1500 | S1 pop-up, total |

UI never exceeds 320ms. Only the three signature moments and reveals run longer.

### 4.3 Stagger

- Reveal children: 60ms, at most 6 children (the 7th and later appear with the 6th).
- Menu links: 30ms.
- Hinges: 18 to 35ms inside a group, 90ms between bamboo rows.
- Planks: one per 2% of the bridge beat, alternating banks.
- Lanterns: 60ms (S1), 70ms (join).

### 4.4 Scroll infrastructure

```js
const lenis = new Lenis({ lerp: 0.1, wheelMultiplier: 1, smoothWheel: true, syncTouch: false, autoRaf: false, anchors: true });
gsap.ticker.add((t) => lenis.raf(t * 1000));
gsap.ticker.lagSmoothing(0);
lenis.on("scroll", ScrollTrigger.update);
ScrollTrigger.config({ ignoreMobileResize: true });
```

- `wheelMultiplier: 1` and `syncTouch: false` keep native scroll distance and native touch
  scrolling. Lenis only smooths the wheel on desktop. No snapping anywhere and no wheel
  hijacking: the CLAUDE.md "native scroll speed" rule.
- Lenis is not created under reduced motion or `data-motion="off"`.
- `gsap.matchMedia()` contexts: `desktop: (min-width: 1024px) and (prefers-reduced-motion:
  no-preference)`, `mobile: (max-width: 1023px) and (prefers-reduced-motion:
  no-preference)`, `reduced: (prefers-reduced-motion: reduce)`. Every trigger is created
  inside a context, so a resize across 1024px reverts cleanly.
- Pins use `anticipatePin: 1` and `invalidateOnRefresh: true`. Section heights use `svh`.

### 4.5 Home scroll beats

Pin progress `p` runs from 0 to 3 across `+=300%` (desktop) or `+=270%` (mobile).

| p | Camera | Scene | Card (HTML) | `uDusk` |
|---|---|---|---|---|
| before pin | `heroWide` to `tre` | Grove still short (`uGrow` 0.15) | Section head scrolls away | 0 to 0.1 |
| 0.00–0.55 | Hold at `tre`, pointer allowed | Stalks grow 0.15 to 1, back row first (row offset 0.05) | Tre Xanh card in at 0.00–0.08 (opacity 0 to 1, y 24 to 0px) | 0.1 |
| 0.25–0.65 | Hold | Leaves unfurl as each stalk passes their height | Readable | 0.1 |
| 0.65–1.00 | Hold | Idle sway only | Readable (35% of the beat) | 0.1 |
| 1.00–1.30 | `tre` to `hong`, lift +0.8 at the midpoint | — | Tre out 1.00–1.10 (to y -16px), Hồng in 1.15–1.27 | 0.1 to 0.45 |
| 1.30–1.60 | Hold | Screen unfolds: each panel's hinge over 0.06, stagger 0.024, front panel first then both ways round | Readable | 0.45 |
| 1.45–1.80 | Crane up +0.3 | `uBloom` 0.15 to 1 | Readable | 0.45 |
| 1.80–2.00 | Hold | Idle | Readable | 0.45 |
| 2.00–2.30 | `hong` to `cau`, swinging out over the river, lift +0.6 | — | Hồng out 2.00–2.10, Cầu in 2.15–2.27 | 0.45 to 0.75 |
| 2.30–2.72 | Slow push-in 0.25 | 14 remaining planks drop in, alternating banks | Readable | 0.75 |
| 2.72 | — | **Join** (§2, S2), one-shot 900ms | — | — |
| 2.72–3.00 | Hold | Lanterns lit | Readable | 0.75 to 0.8 |
| after pin | Programs sheet covers the canvas; while hidden the stage jumps to `night` | — | — | set to 1 |

**Index tabs.** On desktop three book-index tabs sit on the right edge of the pinned stage,
labelled "Tre Xanh", "Hoa Hồng Nhỏ" and "Cầu Hàn" in each unit's `-800` color on paper.
They are anchor links: a click or Enter calls `lenis.scrollTo(beatStart, { duration: 1.0,
easing: easeInOutCubic })`. The active tab sticks out 8px more (`--dur-3`, `--ease-out`).
These are navigation, not decoration and not "01 / 02 / 03" labels. On mobile the same
three tabs sit at the top of the bottom sheet.

**Keyboard.** A `focusin` on any link inside a beat card scrolls to that beat's hold
position, so a keyboard user never focuses a link on a card that isn't shown. In DOM order
the three cards are a normal `<ol>`, and screen readers read them as a list.

### 4.6 Reveals and the ink fill

- `[data-reveal]` children: `opacity 0 → 1` and `translateY 16px → 0`, `--dur-6`,
  `--ease-out`, 60ms stagger, once, at `IntersectionObserver` threshold 0.2 with
  `rootMargin "0px 0px -10% 0px"`. The hidden state applies only under `html.motion-ok`,
  which JS adds before first paint, so no JS means no hidden content. `@media print` shows
  everything.
- **Ink fill** (the "what we do" statement): the paragraph is split into words with GSAP
  SplitText (`type: "words"`, `aria: "auto"`, so the parent keeps one accessible label). Word
  color is scrubbed from `--muc-500` (5.42:1 on paper, already AA) to `--muc-900`
  (14.74:1) between `"top 80%"` and `"bottom 55%"`. The text is never unreadable at any
  scroll position. Words wrapped in `<mark>` (the editor's highlight format in Gutenberg)
  get a `--den-200` strip behind them that scrubs `scaleX 0 → 1` from the left over the
  same range.

### 4.7 Header

The header is transparent over the hero. After 120px (an `IntersectionObserver` sentinel,
already in `main.js`) it becomes a floating paper strip: inset 12px, height 72 to 60px,
solid `--giay-0` and `--shadow-1`, over `--dur-3` / `--ease-out`. Its corner radii are
hand-cut and slightly uneven: `border-radius: 14px 18px 16px 12px / 16px 12px 18px 14px`.
Over sections marked `data-header="night"` it switches to `--dem-900` with paper text in
`--dur-2`. There is no `backdrop-filter` (too expensive on mid Android).

### 4.8 Micro-interactions

| Element | Hover (`pointer: fine` only) | Press | Focus-visible | Other |
|---|---|---|---|---|
| Primary button (`--den-400` pill, `--muc-900` label, Be Vietnam Pro 600 17px, height 52/48, padding 0 28px) | Background to `--den-500` over `--dur-2`; arrow moves 3px right over `--dur-2` `--ease-out` | `scale(.97)` over `--dur-1` `--ease-out` | 3px ring `--hcwa-700`, offset 3px (on night: `--den-400`) | Loading: label stays, a 16px spinner replaces the arrow, `aria-busy` |
| Secondary button (1.5px `--muc-900` outline) | Fill `--giay-100` | `scale(.97)` | Same ring | On night: paper outline, fill `--dem-800` |
| Text link | Underline 1px to 2px, `text-decoration-color` from 40% to 100% over `--dur-2` | — | Ring | Underline offset 4px |
| Bento card (whole card is the link) | Lift -3px plus a shadow step over `--dur-3` `--ease-out`; the cut-paper glyph hinges `rotateX(-10deg)` with `perspective(600px)` over 280ms `--ease-paper` | `scale(.985)` | Ring on the card | Glyph at rest is flat; the hinge echoes S1 |
| Program row | Background `--giay-100`; arrow +4px | — | Ring | Rows, not cards (unchanged from 06-PAGES) |
| Index tabs | Tab sticks out +4px | — | Ring | Active tab +8px |
| Mobile menu | — | — | Focus moves to the first link | Opens: sheet `translateY(-100%) → 0` over 420ms `--ease-drawer`, backdrop 0 to 0.4 over 240ms, links stagger 30ms. Closes: 260ms `--ease-in-out`. A `<dialog>` keeps focus trapping and Escape |
| Accordion (FAQ, mobile footer) | — | — | Ring on `<summary>` | `::details-content` with `interpolate-size: allow-keywords`, block-size over `--dur-4` `--ease-out` (instant where unsupported); chevron rotates 180° over `--dur-2` |
| Copy button (Donate, only once verified) | — | `scale(.97)` | Ring | Copy icon to check icon: 200ms crossfade with `blur(2px)`; label "Đã chép" / "Copied" for 1600ms; `aria-live="polite"` |
| Form errors | — | — | — | Message slides down 8px over 180ms. No shake: anxiety is wrong for a donation form |
| Pause-motion toggle | Icon crossfade 150ms | `scale(.97)` | Ring | Label flips between "Tạm dừng chuyển động" and "Bật lại chuyển động" [DRAFT] |

Never animated: impact numbers (they appear as they are, per CLAUDE.md), prices and gift
amounts, form values, the H1, error text content.

---

## 5. Palette, type, texture

### 5.1 Color tokens

Token names are Vietnamese, after what each color is in the paper world: *giấy* paper,
*mực* ink, *đêm* night, *đèn* lantern, plus the three unit words. The brand blue and the
three unit `500/600` colors are carried over unchanged from the logo sampling in
`05-DESIGN` (still provisional until the agency sends SVG logos).

| Token | Hex | Role |
|---|---|---|
| `--giay-0` | `#FFFCF6` | Card paper (brightest) |
| `--giay-50` | `#F7F1E6` | Page paper, hero sky |
| `--giay-100` | `#EFE6D4` | Sunken paper, far pop-up layers, row hover |
| `--giay-200` | `#E2D4BA` | Dividers, paper shadow tone (non-text) |
| `--muc-900` | `#1A1D33` | Body and heading ink |
| `--muc-700` | `#3F4359` | Secondary text |
| `--muc-500` | `#5E6176` | Meta text, ink-fill start |
| `--dem-950` | `#121238` | Night page (Action, footer), night sky top |
| `--dem-900` | `#1A1A4A` | Header over night |
| `--dem-800` | `#24245E` | Night cards, night sky horizon |
| `--dem-line` | `#3A3A78` | Night dividers (non-text) |
| `--hcwa-700` | `#313188` | Logo blue: the river, links on paper, focus ring |
| `--hcwa-900` | `#23236A` | Deep brand |
| `--den-400` | `#FFC42E` | Primary button and lantern light (the old sun-400) |
| `--den-500` | `#F2B312` | Primary hover |
| `--den-200` | `#FFE29A` | Highlight strip, glow core, lit windows |
| `--tre-800` / `600` / `300` / `100` | `#0B5A2E` / `#008E3A` / `#7CC47F` / `#DDEFD6` | Tre Xanh: text-safe, logo green, light paper, tint |
| `--hong-800` / `500` / `300` / `100` | `#9C1D60` / `#DC4C98` / `#F29CC5` / `#FBE2EE` | Hoa Hồng Nhỏ: text-safe, logo pink, light paper, tint |
| `--cau-800` / `500` / `300` / `100` | `#1E4E86` / `#417CBD` / `#9DC0E6` / `#E1ECF8` | Cầu Hàn: text-safe, logo blue, light paper, tint |
| `--ok` / `--ok-100` | `#2C7A4C` / `#E6F2EA` | Active pill (unchanged) |
| `--warn` / `--warn-100` | `#8A5A00` / `#FFF4D6` | Pending chip, staging only (unchanged) |
| `--error` / `--error-night` | `#B42318` / `#FF9C8F` | Errors on paper / on night |

### 5.2 Text and background pairs (WCAG 2.x contrast, computed)

| Text | Background | Ratio | Use |
|---|---|---|---|
| `--muc-900` | `--giay-50` | 14.74 | Body, headings |
| `--muc-900` | `--giay-0` | 16.18 | Card text |
| `--muc-900` | `--giay-100` | 13.36 | Sunken sections |
| `--muc-700` | `--giay-50` / `--giay-100` | 8.65 / 7.85 | Secondary text |
| `--muc-500` | `--giay-50` / `--giay-100` | 5.42 / 4.91 | Meta, ink-fill start |
| `--muc-900` | `--den-400` / `--den-500` | 10.40 / 8.86 | Primary button / hover |
| `--giay-50` | `--dem-950` | 15.97 | Night text |
| `--giay-200` | `--dem-950` | 12.28 | Night secondary |
| `--den-400` | `--dem-950` / `--dem-800` | 11.28 / 8.86 | Links and labels on night |
| `--giay-50` | `--dem-800` | 12.55 | Night card text |
| `--giay-200` | `--dem-800` | 9.64 | Night card secondary |
| `#FFFFFF` | `--hcwa-700` | 10.91 | Text on brand blue |
| `--hcwa-700` | `--giay-50` | 9.70 | Links and focus ring on paper |
| `--tre-800` / `--hong-800` / `--cau-800` | `--giay-50` | 7.42 / 6.77 / 7.50 | Unit names and labels on paper |
| the same | their own `-100` tint | 6.91 / 6.24 / 7.05 | Text on unit tints |
| `--giay-0` | `--tre-800` / `--hong-800` / `--cau-800` | 8.15 / 7.43 / 8.24 | Index-tab labels |
| `--error` | `--giay-50` | 5.85 | Errors on paper |
| `--error-night` | `--dem-800` | 6.99 | Errors on night |

Non-text, 3:1 needed where it identifies something: unit logo colors on paper are 3.79
(`--tre-600`), 3.38 (`--hong-500`) and 3.86 (`--cau-500`), which pass, and they never carry
text. The `--den-400` button fill on paper is 1.42, the same accepted exception as before:
the button is identified by its 10.40:1 label. Run `design-token-contrast` on
`tokens.css` before building components, and add the new pairs to `qa/contrast-pairs.json`.

**Rules.**
- One action color (lantern yellow) for Donate and primary buttons. In 3D it appears only
  as lantern light, never as large areas.
- Unit colors never carry body text. Text uses the `-800` steps.
- In the 3D, a unit's world uses only its own four steps plus paper, ink and the river
  blue. Bamboo green appears in the rose's stem as the single cross-unit echo.
- Gradients appear in exactly two places: the WebGL sky and the lantern glow in the Action
  section (§5.5).

### 5.3 Type

**Display: Bricolage Grotesque** (OFL, Google Fonts), self-hosted, used only at 28px and up
(H1, H2, statement, unit names on cards, large dates).

Checked 6 Nov 2026, not assumed:
1. The Google Fonts CSS2 API for Bricolage Grotesque serves `vietnamese`, `latin-ext` and
   `latin` subsets.
2. A cmap check on the variable source (`google/fonts` `ofl/bricolagegrotesque`, v1.001,
   axes opsz 12–96, wght 200–800, wdth 75–100) finds **0 missing of 135 Vietnamese code
   points**: U+1EA0–1EF9, Ă ă Đ đ Ĩ ĩ Ũ ũ Ơ ơ Ư ư, ₫, and the Latin-1 accented vowels.
3. Metrics at opsz 96, wght 700: the tallest stacked capital (Ổ) reaches 1043/1000, the
   tallest stacked lowercase (ổ) 908, and the lowest descender (ỵ) -329. In sentence-case
   headings a stacked capital almost never sits over a ỵ or ỹ, so line-height 1.1 is clean.
   The specimen confirms it at 85px and 36px.
4. Built subset: `wdth 100`, `opsz 96`, `wght 600–800` variable, with Latin and Vietnamese
   merged (the unicode list in `scripts/build-fonts.py`): **48 KB WOFF2** (48,088 bytes). Add it to
   `build-fonts.py` in place of Fraunces.

**Body: Be Vietnam Pro** 400 / 500 / 600 (already self-hosted: 29.6 / 31.3 / 31.4 KB;
re-checked: 0 missing Vietnamese code points). Total: **4 font files, 140 KB**, down from
162 KB today (with Fraunces), meeting the "fonts ≤ 4 files" rule. Preload the display file and body 400.

**Why not keep Fraunces:** it is the prototype's face and today's most common "warm NGO"
default. A wonky serif on cream paper is exactly what read as a template. Bricolage
("bricolage" is French for handmade DIY) has chunky counters and cut-in ink traps at opsz
96 that look scissor-cut, which suits a paper world, and it stays crisp at 34px on a phone.

**Why not gpt-taste's shortlist:** Outfit has no Vietnamese subset on Google Fonts. Satoshi
(Fontshare) is missing 92 of 102 Vietnamese letters (cmap checked). Geist covers Vietnamese
but is a neutral UI grotesk with nothing to say here. Inter is banned by the skill itself.
CLAUDE.md wins anyway.

**The hero line, in Vietnamese, at display size** (see
`three-places-hero-specimen.png`):

```css
.hero h1 {
  font-family: "Bricolage Grotesque", var(--display-fallback);
  font-weight: 700;
  font-size: clamp(1.75rem, 0.55rem + 6.6vw, 5.25rem); /* 320: 30px · 390: 34.5px · 768: 59.5px · 1024: 76px · 1440+: 84px */
  line-height: 1.1;
  letter-spacing: -0.02em;
  max-inline-size: 13.5em;   /* em, so the breaks stay the same as the size scales */
  text-wrap: balance;
}
```

The wrap was simulated with the font's own advance widths (Pillow, NFC text, compound
words joined with no-break spaces):

| Width | Size | Lines (VI) |
|---|---|---|
| 1440 | 84px in 1134px | 2: "Cùng trẻ em xây dựng tương lai / an toàn và tươi sáng hơn." |
| 1024 | 76px in 960px | 3 (balance evens out the last line) |
| 768 | 59.5px in 704px | 3: "Cùng trẻ em xây dựng / tương lai an toàn và / tươi sáng hơn." |
| 390 | 34.5px in 358px | 3: same breaks as 768 |
| 320 | 30px in 288px | 3: same breaks |

English ("Helping children build safer, brighter futures.") is 2 lines at every width.
The prototype must still confirm this in a real browser through tastegate.

**Vietnamese line breaking.** Browsers break between syllables, so "tương lai" (future) can
split across two lines. Headings (H1, H2, card names) join compound words with U+00A0. In
the prototype a `viKeep()` helper does this from a short glossary: trẻ em, tương lai, an
toàn, tươi sáng, xây dựng, gia đình, cộng đồng, giáo dục, bảo vệ, chính sách, chữa lành,
lớn lên, Hồ Chí Minh, Tre Xanh, Hoa Hồng Nhỏ, Cầu Hàn. In WordPress it becomes a
`the_title` / heading-block render filter in `hcwa-core`. Body text uses `text-wrap:
pretty` and no joins.

**Scale.**

| Role | Font | Size | Line-height | Weight / tracking |
|---|---|---|---|---|
| H1 | Bricolage | as above | 1.1 | 700 / -0.02em |
| Statement | Bricolage | `clamp(1.75rem, 1.2rem + 2vw, 3rem)` | 1.3 | 600 / -0.01em |
| H2 | Bricolage | `clamp(1.75rem, 1.2rem + 2.4vw, 3.25rem)` | 1.15 | 680 / -0.015em |
| Card unit name | Bricolage | `clamp(1.75rem, 1.4rem + 1vw, 2.25rem)` | 1.15 | 680 |
| H3 | Be Vietnam Pro | `clamp(1.25rem, 1.1rem + 0.6vw, 1.5rem)` | 1.35 | 600 |
| Lead | Be Vietnam Pro | 1.25rem | 1.6 | 400 |
| Body | Be Vietnam Pro | 1.0625rem (17px) | **1.7** | 400, max 64ch |
| Meta / labels | Be Vietnam Pro | 0.875rem | 1.6 | 500 / +0.01em |
| Button | Be Vietnam Pro | 1.0625rem | 1.2 | 600 |

Sentence case everywhere, in both languages. There is no uppercase utility in the system
at all, so nobody can apply one to Vietnamese by accident. The fallback stack uses
`@font-face` overrides (`size-adjust`, `ascent-override`) on local Arial for the display
face, to keep CLS under 0.02 when the font swaps in.

### 5.4 Texture, grain and light

- **Paper grain:** one 192×192 PNG fibre tile (noise plus a few long fibres, about 3 KB,
  made by script) used as `background-image` on paper surfaces at 5% opacity baked into
  the PNG. Night surfaces use a lighter tile at 6%. No `background-attachment: fixed`, no
  blend modes, no animated grain.
- **Hand-cut edges:** paper-to-paper and paper-to-night section boundaries are an SVG strip
  24px tall with an irregular cut path (amplitude 4 to 6px) and its own drop shadow drawn
  into the SVG, placed as a `::before` on the next section. No CSS `filter`. Scrolling one
  sheet over the fixed city is the "sheet slides over the scene" effect, at no cost.
- **Light:** in 3D, the `uDusk` keyframes. In CSS, light appears once: the lantern glow
  behind the Donate card in the Action section (the aurora-ui idea, done cheaply). Two
  absolutely positioned radial gradients (`--den-400` at 22% to transparent, and `--hong-500`
  at 12% to transparent, each 70vmax) drift with `transform: translate()` over a 40 s
  `ease-in-out` alternate loop. No `filter: blur()`, no `backdrop-filter`, paused under
  pause and reduced motion.
- **No glassmorphism.** Every text panel over the canvas is solid paper or solid night.

---

## 6. Home page, section by section (AIDA)

All copy below is from `content/draft/*.json` unless marked `[DRAFT: new]`. New strings go
into `content/draft/ui.json` with VI and EN before use.

### Nav

- **Desktop:** logo (`hcwa-logo.png`, then SVG A1 when it arrives) on the left. Menu in the
  center: Về chúng tôi · Hoạt động · Tác động & Báo cáo · Tin tức · Chung tay · Liên hệ.
  On the right: VI / EN, then "Ủng hộ" (primary, lantern yellow). Transparent over the
  hero; it becomes the floating paper strip (§4.7).
- **Mobile 390:** 60px tall: logo, VI / EN, Ủng hộ, and the menu button. The menu is a
  full-height paper sheet with links in Bricolage 28px and accordions for sub-items.
- No meta labels, no pills under the nav.

### A. Attention: hero (`100svh`, min 640px, transparent over the fixed canvas)

- **Layout:** text on the paper sky, top left, in the 1312px container. H1 (§5.3), then
  the intro at 20px with a 34em measure, then two buttons. The pop-up city fills the bottom
  ~45% at full bleed; on a portrait phone it uses the three-quarter river shot. No eyebrow
  line: the logo already carries the name, and "since [year]" isn't verified. No stats
  (gpt-taste: none in the hero) and no badges.
- **H1:** "Cùng trẻ em xây dựng tương lai an toàn và tươi sáng hơn." / "Helping children
  build safer, brighter futures." (`site.hero.title`, draft)
- **Intro:** "Hội hỗ trợ trẻ em, thanh thiếu niên và gia đình qua giáo dục, bảo vệ trẻ em
  và các chương trình dựa vào cộng đồng." (`site.hero.intro`, draft)
- **Buttons:** "Khám phá hoạt động" (primary, to Our Work) and "Chung tay" (secondary, to
  Get Involved). At 390 they stack full width (48px + 12px + 48px). From 400px up they sit
  side by side. Both are inside the first screen at 390×844 and 1440×900. Measured stack at
  390: header 60 + 24 + H1 3 × 38 + 16 + intro 4 × 32 + 24 + buttons 108 ≈ 470px, which
  leaves about 370px for the book.
- **Bottom right:** the pause-motion toggle (icon button, 44px, with a visible label on
  hover and focus) and a text link "Đi qua ba nơi chốn ↓" / "Visit the three places ↓"
  [DRAFT: new] to `#ba-noi-chon`. It works without JS.
- **Motion:** S1, plus the hero-scroll dolly (§3.6).

### I. Interest: what we do (opaque paper sheet that slides over the city)

- The sheet arrives with a hand-cut top edge. Section padding is `clamp(96px, 12vw, 192px)`
  top and bottom.
- **H2:** "Chúng tôi làm gì" / "What we do". It sits above the statement at H2 size, so
  there is no meta label.
- **Statement:** `site.whatWeDo.summary` set at the statement size with the ink fill
  (§4.6). Max width 24em. "chăm sóc, bảo vệ và giáo dục" is wrapped in `<mark>` for the
  lantern strip.
- **Focus-area bento:** four cards from `work.focusAreas`. A 12-column grid with
  `grid-auto-flow: dense`, 16px gap, `--r-lg` 20px:
  - Giáo dục: columns 1–7, row 1
  - Bảo vệ trẻ em: columns 8–12, rows 1–2 (tall)
  - Hỗ trợ gia đình và cộng đồng: columns 1–4, row 2
  - Vận động chính sách: columns 5–7, row 2
  - Check: row 1 = 7 + 5 = 12; row 2 = 4 + 3 + 5 (the tall card) = 12. No empty cell.
  - Each card has a cut-paper glyph (open book, roof, two-window house, scroll), drawn from
    `shapes.mjs` as inline SVG and marked as draft art until the agency's A6 icons arrive.
    It has the area name in Bricolage 28px, its one sentence (`text`, draft), and the whole
    card links to Our Work `?area=`.
  - 1024–1279px: two by two. Under 768px: one column, the tall card back to normal height.
  - This deliberately departs from 06-PAGES ("a 2×2 list, not cards"): the cards are where
    the paper hinge micro-interaction lives. HCWA decides.
- The sheet's bottom edge is cut too, and the section head of `#ba-noi-chon` sits on the
  same sheet, so the city reveals itself under the H2.

### D. Desire, part 1: the three places (`#ba-noi-chon`, S2)

- **Section head** (on the paper sheet): H2 = `site.centersIntro`: "Nơi trẻ em được học,
  được chữa lành và lớn lên, mỗi ngày." / "Places where children learn, heal and grow,
  every day." (draft). Plus "Xem tất cả" / "See all" to the Our Work centers anchor.
- **Stage:** pinned, transparent over the canvas. The cards are solid `--giay-0` with a
  6px top tab in the unit's `-800` color (a book divider tab carrying the short unit
  name). Desktop: 420px wide, left column, vertically centered. Mobile: bottom sheet (§8.1).
- **Card contents** (from `work.centers`):
  - **Tre Xanh:** name "Cơ sở Bảo trợ Xã hội Tre Xanh"; "Từ năm [năm]" (pending chip,
    staging only); `serves`; focus areas as plain text separated by middots (not pills);
    "Xem cơ sở →".
  - **Hoa Hồng Nhỏ:** name "Mái ấm Hoa Hồng Nhỏ"; `serves` (the age range stays a token
    until HCWA confirms it). **No location, no year-and-ward line, no "visit"**: the link
    reads "Tìm hiểu về mái ấm" / "About the shelter" [DRAFT: new], with a second link to
    Tiệm bánh Hoa Hồng Nhỏ (support through the bakery).
  - **Cầu Hàn:** name "Dự án Cầu Hàn"; `serves`; "Xem cơ sở →".
- **Index tabs** and keyboard behavior: §4.5.
- **Without WebGL or with reduced motion:** the same `<ol>` with a poster per item, in
  normal flow.

### D. Desire, part 2: projects and programs (opaque paper sheet over the bridge)

- **H2:** "Dự án & chương trình" (`ui.programs`). "Tất cả dự án →".
- **Rows, not cards** (06-PAGES kept): status pill (`--ok-100` / `--ok`, "Đang hoạt
  động"), years token, name in Bricolage 28px, `summary`, partner text ("CapitaLand
  Vietnam" for Cùng Vươn Lên, already in the draft), then "Diễn ra tại" with the unit short
  names in their `-800` colors, each prefixed by an 8px dot. Thrive Together lists all
  three; Lớp học tình thương lists Cầu Hàn and Tre Xanh.
- Active first, at most 4. Hover per §4.8. `data-reveal` on the list.

### D. Desire, part 3: latest from HCWA

- **H2:** "Tin mới từ Hội" (`ui.latest`). "Tất cả tin tức →".
- **Featured story:** placeholder only (`news.featuredStory` is draft). On staging it shows
  with the draft outline and a paper-frame placeholder (photo corners, the C2 code on a small
  label, no grey box). **On production it is hidden** until an approved story exists.
- **Three real posts** (`news.posts`) as a typographic list: date "25/05/2026" in Bricolage
  600 32px with tabular figures, category label, title (Be Vietnam Pro 600 20px), and an
  external-link icon (the posts live on the current site). Hover: title underline plus an
  arrow nudge.

### A. Action: how you can help (night; the canvas returns as the night city)

- `data-header="night"`. The section background is transparent, so the night city shows
  through: the whole spread, every window lit, the finished bridge with its lantern string.
  Without WebGL, `poster-night.svg`.
- **H2:** "Bạn có thể giúp như thế nào" (`site.helpTitle`) in `--giay-50` on the night sky
  (12.55 to 15.97:1 across the sky gradient).
- **Ways-to-help bento** (from `involve.ways`), solid `--dem-800` cards, 12 columns,
  `grid-auto-flow: dense`:
  - **Ủng hộ:** columns 1–7, rows 1–2. Text, the three `giftExamples` with `[số tiền]`
    tokens (staging only; on production the list is hidden until amounts are verified),
    and the primary button "Ủng hộ". The lantern glow (§5.4) sits behind this card only.
  - **Tình nguyện:** columns 8–12, row 1.
  - **Hợp tác:** columns 8–12, row 2, with "Trở thành đối tác →".
  - **Hiện vật và Tiệm bánh Hoa Hồng Nhỏ:** columns 1–12, row 3 (a wide strip).
  - Check: row 1 = 7 + 5, row 2 = 7 + 5, row 3 = 12. No empty cell. Mobile: one column,
    Ủng hộ first.
- **Partners:** "Cảm ơn các đối tác đã đồng hành" renders only once at least 4 approved
  one-tone logos exist (C5). Until then the section is absent; a wall or marquee with one
  name would be fake fullness.
- **Contact band** (inside the night section, on `--dem-800`): "Bạn có câu hỏi hoặc muốn
  đến thăm?" (`site.contactBand.title`), "Liên hệ với chúng tôi" (secondary, night
  variant), +84 28 3840 1406, hcwa.vn@gmail.com (both draft).

### Footer (`--dem-950`)

- A static SVG river strip in `--hcwa-700` runs across the top edge with the paper boat
  resting on it (static, no loop).
- **Four columns** (06-PAGES): logo plus `site.description` plus the registration token;
  Contact (address, phone, emails, map link); Explore; Reports & policies. Accordions on
  mobile.
- **Bottom row:** © 2026 HCWA · Facebook · VI / EN · the pause-motion toggle.
- No giant wordmark: the Donate card is the big ask, and the footer stays a footer.

### gpt-taste pre-flight

- **Hero:** H1 at most 3 lines in VI at 390 and at most 2 at 1440 (§5.3, measured with the
  real font). Container `max-inline-size: 13.5em` inside 1312px. No badges, pills, stamps or
  stats.
- **Bento:** both grids verified above, `grid-auto-flow: dense`, no empty cells.
- **Labels:** no "SECTION 01"-style labels. The index tabs carry real unit names.
- **Buttons:** ink on lantern 10.40:1. Paper outline on night ≥ 12:1.
- **AIDA:** nav, hero, interest (statement and bento), desire (pinned journey, programs,
  news), action (night bento, contact), footer.

---

## 7. Other pages: one inherited system, no second showcase

**The rule:** WebGL runs on the home page only. Inner pages get the system (tokens, type,
paper and cut edges, header, page turn, reveals, micro-interactions) plus **at most one**
small paper element in their header, made from posters or SVG layers with CSS or scrubbed
transforms of 56px or less. No pins on inner pages.

| Page | Header element | Motion beyond the system |
|---|---|---|
| Our Work hub | `poster-map.svg`: the top-down spread with three HTML labels positioned in % that link to each unit. The rose label sits on the spine with no address | Filter chips are paper tabs. With JS, the list reorders with FLIP over 240ms `--ease-out`; without JS, the chips are links to `?area=` |
| Green Bamboo center | `layers-tre-{back,mid,front}.svg`, scrubbed `translateY` 0 / -12 / -28 / -56px over the header height. Front leaves sway with CSS `rotate(±1.2deg)`, 7 s `ease-in-out` alternate, `transform-origin: bottom` (paused by the toggle and by reduced motion) | The "At a glance" box is a paper card with a `--tre-800` tab. Figures show a pending chip (staging only) and never count. Gallery (B3) hidden until approved photos exist; photos go into paper frames with corner mounts |
| Thrive Together program | A thread: an SVG path draws (`stroke-dashoffset` 1 to 0, 1100ms `--ease-in-out`, once on enter) between three small unit glyphs, because the program runs at all three. Partner name as text until C5 logos arrive | Rows for goals and activities; documents as report rows |
| Get Involved hub | A night strip with the lantern glow, plus the same ways-to-help bento component as the home page | FAQ accordions (§4.8) |
| Donate | A small lantern glyph only. **The calmest page on the site** | The payment panel is a plain paper envelope: tabs "Trong nước / Từ nước ngoài" with `involve.bankFields` labels and values shown as "[Chờ HCWA xác nhận]". Copy buttons are `aria-disabled` with the helper "Thông tin sẽ hiện khi Hội xác nhận" [DRAFT: new]. `PROD=1` fails the build unless `content/verified/donation.json` exists. No QR, account number, holder name or PayPal address appears anywhere |
| Contact | A paper map illustration (C7 style, from `poster-map.svg`) with the HQ pin only. Tre Xanh and Cầu Hàn pins only if HCWA approves. **Never Little Rose** | Form with paper fields; errors per §4.8 |
| 404 | A paper boat on a short river strip: CSS `translateX` loop over 16 s `linear` plus a 3 s bob (stopped by pause and reduced motion). Line: "Trang này đã trôi đi mất." / "This page has drifted away." [DRAFT: new] | Search box plus links to Home, Our Work, Contact |
| Components | Tokens, type, every button and card state, a motion lab with a replay button per easing token | — |

When photos arrive (B1, B3, C2), they always sit in paper frames on paper, never under
text and never in the 3D. Hoa Hồng Nhỏ stays illustration only, permanently.

---

## 8. Mobile, accessibility and the honest risks

### 8.1 Mobile (390 × 844)

- **Hero:** §6. `heroPortrait` shot, DPR 1.25, `fitHero()` keeps the text on clean sky.
- **Journey:** the canvas fills 100svh. The card is a bottom sheet of at most 42svh
  (`--giay-0`, 20px top radius, the three index tabs on its top edge), and the scene is
  framed in the top 58% with `setViewOffset`. The pin is 270svh (90svh per place) with
  `scrub: true` (no lag on touch). Each beat's portrait framing comes from
  `fitShot(shot, aspect)` with the camera pushed back by 1.35× and FOV 40.
- **Touch:** native scrolling (Lenis does not smooth touch), no pointer effects, no
  device-orientation input.
- **Bento:** one column. The statement sits at 28px (the display floor) with line-height 1.3.
- **Low-tier phones** drop to posters on their own (§3.9). Save-Data never downloads the
  3D.
- **Weight on a first mobile visit:** HTML+CSS ~65 KB, fonts 140 KB (two preloaded), core
  JS 12 KB, then motion 50 KB, then the world ~160 KB after LCP, all gzip.

### 8.2 Accessibility

- **All text is HTML.** The canvas is `aria-hidden="true"` with `role="presentation"`, and
  posters are decorative. Nothing is said only by the 3D.
- **Pause control (WCAG 2.2.2).** The idle loops (sway, river, boat, flicker, lantern glow)
  run longer than 5 s next to other content, so a visible "Tạm dừng chuyển động" / "Pause
  motion" toggle sits in the hero and the footer. It sets `html[data-motion="off"]` and
  stores the choice in `localStorage` (wrapped in try/catch). The inline head script reads
  it before first paint.
- `prefers-reduced-motion` means the fully static site (§3.11), with the 3D chunk never
  loaded.
- **Focus:** a 3px ring with 3px offset, `--hcwa-700` on paper (9.70:1) and `--den-400` on
  night (11.28:1). The journey's `focusin` handler scrolls to the right beat. The index tabs
  are real links.
- **Targets** are at least 44×44 (buttons 48 to 52 tall).
- **Zoom 200% and text-only zoom:** the H1 width is in `em`, so it reflows. The pinned
  stage switches to the stacked layout when the card's content height exceeds 42svh on
  mobile or 70svh on desktop (checked with a `ResizeObserver`), so zoomed text never gets
  clipped inside a pin.
- **Language:** `lang` on `<html>` and on every VI/EN link. No uppercase anywhere.
  Body line-height 1.7.
- **Vestibular:** camera moves follow scroll 1:1, with no roll, no sudden zoom and no
  auto-moving camera except the 1.5 s S1 dolly, which is skipped when paused.
- **Safeguarding as accessibility of trust:** no figures anywhere in the world. The rose is
  never placed on a map, never given a location, never visited by the camera. The posters
  are generated from the same data, so a figure can't sneak in through the art.

### 8.3 Risks (honest)

1. **Mid-range Android performance is unproven until measured.** The budgets are
   conservative, but shadows plus MSAA on a Mali-G57 at 1.25 DPR has to be measured on a
   real phone before HCWA sees it. The tier system degrades to posters, so the worst case
   is a beautiful static site, not a broken one.
2. **JS weight.** About 210 KB gzip of motion plus 3D (measured 142 + 49.5, plus our code)
   loads after LCP. Parsing 567 KB of minified three.js costs roughly 250 to 450 ms of main
   thread on a mid phone, in idle slices. Save-Data and reduced motion skip all of it.
3. **"A children's book" could read as childish** to government partners and international
   funders. The mitigation is execution: adult paper-engineering quality (think Sabuda, not
   clip art), a deep night palette, confident type, no faces, no mascots. Maxwell and HCWA
   should judge this on a working prototype, not on this text.
4. **Little Rose symbolism must be reviewed by Little Rose staff and counsellors.** A rose
   opening inside a screen is meant as healing and protection. The screen must never read
   as walls or a cage: it is low, scalloped, soft pink, with open sky above. A fallback is
   ready: a closed bud with the lantern glow and no bloom animation.
5. **Cầu Hàn's name.** We read *cầu* as bridge. *Hàn* may be a place name, and the
   unfinished bridge in the hero must not suggest the project itself is unfinished. Ask
   HCWA what the name refers to and whether the build-the-bridge story feels right.
6. **Unit colors are provisional** (sampled from PNG logos). The `-800` text steps are
   derived; recompute contrast when the SVG logos (B6) arrive.
7. **Pinned scroll on iOS Safari** can jitter with the address bar. Mitigated with `svh`,
   `ignoreMobileResize` and `anticipatePin`; test on a real iPhone.
8. **View transitions** don't run in Firefox. Navigation there is plain, which is
   acceptable.
9. **The WordPress port** works because everything is HTML-first: sections are block
   patterns, posters are theme files, motion hooks are `data-*` attributes or block style
   variations, and the world is one ES module enqueued on the front page only with
   `wp_enqueue_script_module`. Theme tokens move to `theme.json` with the same slugs.
   Risk: editors changing card text lengths. Cards are fluid, and the overflow fallback
   (stacked layout) covers extremes.
10. **Effort.** About 8 to 10 developer days for the world and choreography, 3 for the
    motion system and components, 2 for the poster pipeline, and 2 for device testing and
    tastegate fixes.
11. **Bricolage Grotesque is fashionable** (widely used since 2024), so it may date. It is
    one token to swap. Any replacement must pass the same Vietnamese checks.
12. **Draft outlines.** CLAUDE.md requires visible dashed outlines on drafts in the
    prototype, and on a page this crafted they will look noisy in screenshots. Keep them
    (1px dashed `--muc-500` at 50%, offset 4px) and add a reviewer-only toggle "Ẩn khung
    nháp / Hide draft outlines" on staging that defaults to visible.

### 8.4 Questions for HCWA (add to the reply email)

1. Is the pop-up book world (with no photos on the home page) the right voice for HCWA?
2. Do Little Rose's staff accept the rose and folding-screen motif?
3. What does "Hàn" in Cầu Hàn refer to, and is an unfinished bridge in the hero acceptable?
4. Can Tre Xanh and Cầu Hàn appear as pins on the Contact page's illustrated map?

---

## 9. Build order for next week, and how we know it's done

1. **Day 1:** tokens (§5.1) into `tokens.css` plus `tokens-to-js.mjs`; Bricolage subset
   added to `build-fonts.py`; `design-token-contrast` run; `viKeep()` written.
2. **Day 2:** HTML for the home sections (§6) with no motion; reduced-motion layout as the
   default render; tastegate at 390 and 1440.
3. **Days 3–4:** `shapes.mjs`, `layout.mjs`, `shots.mjs`, `world-posters.mjs`. Posters
   inline, the flat/popped switch, the 6 s safety net.
4. **Days 5–8:** the three.js world, paper material, the S1 pop-up, the journey timeline,
   the join, tiers, loop pausing, `fitHero()`.
5. **Day 9:** motion module (Lenis, reveals, ink fill, header, pause toggle, page turn).
   Inner page headers.
6. **Day 10:** device test (a Galaxy A15-class phone, an iPhone, a desktop with integrated
   graphics), Lighthouse mobile, fixes.

**Acceptance:**

- [ ] LCP < 2.5 s (Lighthouse mobile and a real mid Android over 4G); the H1 is the LCP
      element; CLS < 0.1; INP < 200 ms.
- [ ] No long task over 120 ms caused by world init (Performance panel).
- [ ] With reduced motion, the network panel shows no three.js chunk, and the page reads
      top to bottom with all posters.
- [ ] With `--disable-webgl` and with JS off, all content is visible, the popped posters
      show, every link works, and the console shows no errors.
- [ ] The render loop does no work while the hero, journey and Action section are all off
      screen, or the tab is hidden.
- [ ] p75 frame time ≤ 16.7 ms on desktop integrated graphics; the tier downgrade proven on
      the test phone.
- [ ] H1 at most 3 lines in VI at 390 and at most 2 at 1440 in a real browser; tastegate
      passes on every page at 390 and 1440.
- [ ] Hoa Hồng Nhỏ: no location, no visit link, no figures, not on any map; the camera
      never enters the screen ring (assert in dev: camera height above the screen top at
      every `p`).
- [ ] `PROD=1` build: no `[DRAFT`, no unverified figure, no payment placeholder, no
      featured-story placeholder, no partner section with fewer than 4 approved logos.
- [ ] Homepage transfer < 1 MB; 4 font files.

---

## Appendix: how the numbers above were measured (6 Nov 2026)

- **Font subsets:** Google Fonts CSS2 API queried for Bricolage Grotesque, Fraunces, Be
  Vietnam Pro, Outfit, Geist and others. Bricolage, Be Vietnam Pro and Geist list
  `vietnamese`; Outfit doesn't.
- **Glyph coverage and metrics:** fontTools on the downloaded TTFs (Bricolage variable
  source from `google/fonts`, Be Vietnam Pro 400/600 from the Google Fonts API, Satoshi
  from the Fontshare CSS API).
- **Subset size:** fontTools instancer plus subsetter with the same unicode list as
  `scripts/build-fonts.py`: 48 KB for opsz 96, wght 600–800; 91 KB if opsz stays variable
  12–96, which isn't needed because the display face is only used from 28px up.
- **Line wrapping:** Pillow `ImageFont.getlength` on Bricolage instanced at opsz 96 /
  wght 700, with -0.02em tracking, a greedy break on normal spaces, and no-break spaces
  inside compound words.
- **Bundle sizes:** Vite 8 production build in a scratch folder against the prototype's
  installed three 0.187.0, gsap 3.15.1 and lenis 1.3.27, importing the classes listed in
  §3.3.
- **Contrast:** WCAG 2.x relative luminance formula over every pair in §5.2.
