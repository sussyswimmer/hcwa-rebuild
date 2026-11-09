# Design system (05-DESIGN.md)

Rewritten 6 Nov 2026 after Maxwell rejected the Phase 1 prototype ("AI slop") and asked for
an experience: strong 3D, moving light, scroll storytelling. Three directions were written
(`docs/design/concepts/`: `lantern.md`, `three-places.md`, `open.md`) and scored by two
judges (craft and fit). **The chosen direction is Bông gió (`open.md`), 16/20 combined**
(lantern 13.5, three places 12), with the best ideas of the other two grafted in and every
judge `must_fix` resolved below. `open.md` stays the long-form rationale; where it and this
file disagree, this file wins. The build plan is `prototype/PLAN.md`.

Supersedes the Phase 1 system (Fraunces, cream paper, dials 4/2/4, "no parallax"). Hard
rules in `CLAUDE.md` still win over anything here.

## 0. Direction

**Direction line (tastegate step 1):**

> Reading this as: an immersive daylight site for HCWA where the visitor stands at a
> child's eye height (1.10 m) inside a Saigon *mái ấm*. A wall of *bông gió* (breeze
> blocks) protects and lets the light in, which is what *bảo trợ* means: protect + support.
> The sun comes in as each page opens; on the home page the scroll flies through one
> quatrefoil hole into a corridor of three painted doors, and late-afternoon shutters open
> onto the call to act. Built on Archivo condensed + Be Vietnam Pro, lime wash in the sun,
> a cool logo-blue shade, and HCWA's yellow as the sunlight. One signature runs through
> everything: the light is always shaped by the block, and it falls where children are
> safe, never on a child.

**Dials (CLAUDE.md):** variance 8, motion 8, density 4. The *world* (3D, light, scroll
sequences) carries the motion 8. UI chrome (buttons, menus, forms) stays calm: Silk
personality, under 300 ms. Density stays 4: one idea per screen.

**Words to design against:** an experience; a place, not a brochure; warm, protective,
specific to Saigon; trustworthy for donors aged 25 to 70; fast on a mid-range Android.

**Signature moments (three, no more):**

| # | Name | Where | What it is |
|---|---|---|---|
| S1 | Nắng vào (the sun comes in) | Every page header; home hero live | At first paint a pre-rendered light layer sweeps down the wall in CSS (every device, every tier). On home the WebGL room then takes over at the same rest state. |
| S2 | Xuyên tường → Hành lang (through the wall, into the corridor) | Home, hero to centers | One uninterrupted scrubbed camera move: from the room, into one quatrefoil hole, through a flood of light that carries the "what we do" statement, out into a corridor where each unit's door swings wide and throws its emblem in light across the floor. The showpiece. |
| S3 | Cửa lá sách (the shutters open) | Home, before "How you can help" | Blue louvered shutters tilt, then swing out; a one-shot 900 ms light flood with a mote burst lands as the help heading arrives. |

**What the grafts and fixes changed from `open.md`** (judge references in brackets):

- The Interest sheet no longer splits the fly-through. The statement rides inside the
  flight, on the light wash; focus areas and impact move after the corridor (craft
  must-fix 1). Logged as a 06-PAGES departure (section 11).
- Bigger 3D energy: the hole fly-through, doors swinging to 72° with unit-emblem light,
  the S3 flood payoff, scroll-velocity motes on touch (craft 2, fit 4; lantern graft).
- The sun comes in on the first paint in CSS for every visitor; the dull shaded state is
  never a resting first impression (craft 3; lantern graft).
- Pause-motion toggle, skip links, index tabs, text-zoom stacking (craft 4/5, fit 1/12).
- Deeper, cooler shade toward `#313188`; the backlit screen reads as a logo-blue
  silhouette; text safety by a deterministic framing solver, not a hazy calm zone
  (craft 6, fit 8; three-places `fitHero()` graft).
- Units become emblems of light: *Đốt tre* (bamboo node) and *Vòm cầu* (bridge arch)
  transom blocks. Little Rose stays plain daylight with no motif (craft 7, fit grafts).
- Sun keyframes are tuned per set so the pattern stays crisp; noon is never shown (craft 8).
- Non-identifying traces of children's lives (chalk *ô ăn quan* board, pencil height
  marks), subject to HCWA safeguarding approval (craft 9).
- Little Rose copy speaks of privacy and safety, not a closed door (craft 10, fit 5).
- In-between sheets carry the light system (bông gió sheet edges, CSS sun layer, lam
  stripes, cool/warm section rhythm) so the page never drops back to the Phase 1 kit
  (craft 11).
- Draft marking stays the CLAUDE.md dashed outline, quieter, with a reviewer toggle
  (craft 12, fit 2).
- View Transition types: `back` reverses the wipe, `lang` is a 180 ms crossfade (fit 10).
- Partners hidden until 4 approved logos; featured story staging-only (fit 9).
- Unit door paint excluded from tone mapping (fit 11). Corridor doors are built from the
  DOM cards, 0 to N (fit 6). Ten-day plan, fallbacks first, device gate in week 1 (craft
  13, fit 3/14).

## 0.5 One continuous flow (Maxwell, 8 Nov 2026: binding, overrides any section below)

> "I want there to be one continuous flow of the entire site. There should be no hard lines;
> one part of the site should flow to the next."

The whole site is **one journey through one day**, and nothing in it has a hard edge.

- **No hard section boundaries, anywhere.** No straight edge, 1 px rule, border-top or
  abrupt background change between sections, sheets, bands, the header, the footer or a page
  header and its body. Every change of background, light or "world" happens over a long
  feathered blend: at least 24svh on desktop and 16svh on phones, eased (never linear), and
  ideally carried by the light itself: sun patches, shafts or the bông gió pattern that fade
  through the seam.
- **One continuous background, not stacked bands.** Section backgrounds come from a single
  page-length "day" gradient that follows the sun clock: morning lime wash at the hero,
  midday at the work sheet, late-afternoon gold around the shutters and help, dusk blue at
  the contact band and footer. A section never has its own flat fill. The existing tokens
  stay the palette; the gradient moves between them.
- **The world never disappears behind a wall.** Where content needs a calm surface for
  reading, use a translucent, feathered surface (soft mask, backdrop blur at most 8 px,
  opacity <= 0.92) through which the world's light still reads, and keep text contrast
  rules (H1 >= 4.5, running text >= 7 over the real background). "Opaque sheets" in PLAN
  H5 become feathered surfaces; the bông gió sheet edge becomes a soft, light-filled mask,
  never a cut-out line.
- **Lists and grids have no hard dividers either.** Rows breathe with space and light
  (hover light, gradients that fade to nothing), not 1 px lines. Cards, if any, have no hard
  outline: shape comes from light and shadow.
- **Pages flow into each other.** Inner pages continue the same day: their lit header blends
  into the body without a bottom edge; the background day position matches the page's place
  in the site. Navigation uses View Transitions so the light and background carry across the
  change (shared light layer, no white flash); off under reduced motion and pause.
- **Motion follows the scroll continuously.** Section reveals overlap the previous
  section's exit, so there is never a dead moment with nothing moving while the visitor
  scrolls, and never a "slide" that starts at a hard line. Native scroll speed still.
- **Fallbacks keep the flow too.** Reduced motion, Tier 0 and no-JS pages show the same
  continuous day gradient and soft seams, just without movement.
- **Review rule:** any visible hard line between two parts of the site is a must-fix in
  every critique, at 390 and 1440, in both languages.

## 0.6 Richness pass (9 Nov 2026: binding, overrides any section below where they conflict)

> Maxwell, on the live site: "i like it but the Linh vuc hoat dong section is so bland and all white
> and the hallway section is so boring its jst a plain white wall"

Two proposals were built and judged (craft and fit): **Saigon home** (`docs/design/proposals-2/saigon-home.md`,
corridor 8 / 7.5, focus 7 / 8) and **Colour and light / kính màu** (`colour-light.md`, corridor 6.5 / 6,
focus 6.5 / 5.5). **Saigon home wins both sections.** Grafts from colour-light and every judge must-fix are
folded in below, together with five lead-designer changes:

1. **No pots, plants or fallen flowers in the corridor.** Section 10 bans plants in the world and `open.md`
   8.3 names them as café drift. Both judges also flagged the near pots and snake plants as low-poly and the
   390 frame as busy. Fans, drifting tamarind leaflets and the colour now carry the life. Hoa sứ goes with
   them: some families link it with pagodas and graves.
2. **The dado is 0.90 m, not 1.12 m** (a graft from colour-light). It is the height of the parapet under the
   screen, so one mint band runs round the whole corridor, and its painted line sits below the 1.10 m eye.
   At 1.12 m the line was a level horizon across the frame, running behind the card's meta line at 1440.
3. **No door surrounds. Instead, the dado stops 10 cm short of every door frame, Little Rose's included.**
   The doors are lost against the mint (on `dado-shade`, green 1.14, blue 1.17, pink 1.02). On the yellow
   wash they read (2.28, 2.32, 2.03). One identical treatment for all three doors also means no bay is
   marked by something missing.
4. **A calmer runner, and no brick in the corridor.** The field disc shrinks, the brick rings go, and the
   solid indigo border becomes a light chain. That answers "the indigo border pulls hard" without a second
   review. Brick appears only on the Vận động tile in the work sheet.
5. **Transom glass (colour-light's optional graft) is declined.** Capped at the 6.5 rule of ≤ 15% unit
   tint it barely shows. Uncapped, it breaks that rule. Either way, coloured pools on cream lower the
   luminance contrast of the Đốt tre and Vòm cầu shapes, and it would leave Little Rose's as the only white
   transom light. The clerestory, the end window and the coloured terrazzo are not taken either.

### 0.6.1 Colour tokens (landed 9 Nov with this section)

The `tile-*` pigments are in the D1 block (section 1) and `prototype/src/styles/tokens.css`. The corridor's
shader targets are in the world-only block of `tokens.css`. `src/world/tokens.js` has been regenerated.
`npm test` passes 91/91 and `npm run build` succeeds. **Engineers do not add these tokens again.**
`tile-mint` from the proposal is dropped because nothing uses it.

| Token | Hex | Where |
|---|---|---|
| `tile-cream` | `#F3E9D6` | cement ground and inlays of every tile |
| `tile-ochre` | `#C4861C` | Giáo dục; the runner's quatrefoils. Not `sun-400`, so Donate stays the only sun fill |
| `tile-indigo` | `#2C3584` | Bảo vệ trẻ em; the runner's discs and border chain |
| `tile-jade` | `#176F5E` | Gia đình và cộng đồng; the runner's leaves; fan bodies |
| `tile-brick` | `#A5472A` | Vận động chính sách only, never in the corridor |
| `tile-ochre-lit` · `-indigo-lit` · `-jade-lit` · `-brick-lit` | `#F0B13F` · `#4265C2` · `#449C80` · `#D7703B` | each pigment in a sun patch on the focus floor: OKLCH L +0.14, chroma ×1.08. The indigo hue moves toward the logo blue (266°), never toward lavender. Cream in the sun is `sun-100` |
| `world-vang-shade` · `-sun` | `#D5BB7E` · `#FFE09A` | Saigon yellow lime wash (*vôi vàng*) above the dado |
| `world-dado-shade` · `-sun` · `-line` | `#5B8F78` · `#A2D4B6` · `#1C5A4C` | mint oil-paint dado to 0.90 m on every wall, the parapet included, with a 3.5 cm line on top |
| `world-gach-shade` | `#BDAF93` | the tiles' cream ground in the corridor's shade (the floor's shade target) |
| `world-ceiling-warm` | `#DDD5C4` | the corridor ceiling: white lime wash with warm bounce |

**Measured** with `design-token-contrast` (`contraste.mjs --tokens prototype/src/styles/tokens.css --pares
qa/contrast-pairs.json`): 77 pairs, all pass. These 10 pairs are new in `qa/contrast-pairs.json`:

| Pair | Ratio | Use |
|---|---|---|
| ink-900 on world-vang-shade · vang-sun · gach-shade · ceiling-warm | 8.55 · 12.47 · 7.39 · 10.95 | shader targets behind the corridor's text panes |
| tile-cream on tile-indigo · jade · brick (3:1) | 8.94 · 5.02 · 4.93 | inlays read on their pigment |
| tile-indigo · jade · brick on lime-100 (3:1) | 8.78 · 4.93 · 4.84 | a tile's shape on the plaster |

**Measured failures that are rules:**

| Pair | Ratio | Rule |
|---|---|---|
| ink-900 / ink-700 on world-dado-shade | 4.30 / 2.44 | Text never sits on the bare dado. Corridor text is always on its pane. |
| tile-cream on tile-ochre; tile-ochre on lime-100 / lime-50 | 2.57; 2.53 / 2.78 | The tiles are decorative and `aria-hidden`. The link is the words. Indigo hearts carry the Giáo dục motif. |
| unit doors on world-dado-shade | green 1.14 · blue 1.17 · pink 1.02 | This is why the dado breaks at every door. On vang-shade the doors read at 2.28 · 2.32 · 2.03. |
| each `*-lit` on its pigment | ochre 1.63 · indigo 1.99 · jade 1.82 · brick 1.77 | This is light, not a graphic. The world's own patches sit in the same range (vang 1.46, the old floor 1.93). |

**Text over the corridor, by token math** (the render gate is the authority). The phone pane is 96%
lime-50 over the darkest new colour (dado line or indigo): ink-900 13.8 and ink-700 7.9. The Tier 2 pane
is 92%: ink-700 7.6. **The pane stays at 96% (Tier 1) and 92% (Tier 2); the proposal's 97.5% is not
taken.** The 13 px rail labels and the stacked cards' meta lines (`.bay__since`, `.bay__where`,
`.bay__note`) move from ink-700 to ink-900, a graft from colour-light, which measured 6.2–6.7 for them
over darker stills. Size and weight keep the hierarchy, and the active rail item keeps its unit underline
and lit transom.

### 0.6.2 The corridor: a Saigon *mái ấm*

**Materials**

| Surface | Spec |
|---|---|
| Walls above the dado | `vang-shade` / `vang-sun` lime wash. *Loang* (metre-wide patches of aged lime, ±6% luminance) come from the lime texture's **alpha channel** (colour-light's `bakeLimeWash` change: A = 0.7·lattice(3) + 0.3·lattice(7)), so there is no extra fetch. The room never reads A. |
| Dado | Every wall below 0.90 m, the parapet under the screen and the bay piers included: `dado-shade` / `dado-sun`, with lime mottle at 30%. A 3.5 cm `dado-line` on top with 3 mm soft edges, never a cut. **On the door wall the dado stops 10 cm before each door frame's outer edge, for every door (Little Rose's too)**, using `uDoors` and `uDoorN`, which are already in the uniforms. The frame stands on yellow wash. |
| Floor | A *gạch bông* runner on the screen side: a border row, 6 field rows and a border row (8 × 20 cm = 1.6 m). From there to the door wall (0.8 m), plain cream tiles with analytic hairline joints, ≤ 11% darker. That is where the sun and the emblem light land. **Field tile:** at the corners where four tiles meet, an indigo disc of r 0.27 tile with a cream Hoa thị quatrefoil (lobe 0.105); an ochre quatrefoil (0.085) in the middle of each tile; jade leaves to the edge midpoints. No brick rings. Pigment covers ≤ 38% of the field texture. **Border tile:** cream ground, a chain of indigo lozenges (half-diagonal 0.22 tile) with ochre cores (0.08), and a 0.03-tile jade line along each edge. Pigment covers ≤ 30%. **Wear:** quintic-interpolated value noise in 3 octaves, plus 1 px speckle at 3%. It lifts pigment toward cream by up to 18%, most along the runner's centre line (foot traffic, Gaussian σ 0.35 m). No axis-aligned or blocky feature may show at 2× zoom; the proof texture failed this. Per-tile tone ±2.5%; grout 1.2 px at 512, ≤ 14% darker. |
| Ceiling | `ceiling-warm`, corner AO as today. |
| Doors | Unit paint and wear unchanged; no surround. Little Rose's leaf and frame keep wear 0.3. |

**Objects and motion.** The objects are fans and leaflets, nothing else (pots, plants and blossoms are
cut, decision 1). **As built, the leaflets are cut too (0.6.7): fans are the only props.**

| Object | Spec | Motion |
|---|---|---|
| *Quạt trần* (ceiling fan) | One per bay **except Little Rose's**, at bay centre −1.6 m, x 99.75 (`FAN` in the proposal). A 0.6 m rod from the 3.1 m ceiling, hub at 2.45 m. Body `tile-jade`; three pitched blades in `tile-cream` with rounded tips; a 16-segment motor housing. | 2.1 rad/s, one turn every 3 s, so no strobing at 30 fps. Tier 2 turns on the clock and with the scroll; Tier 1 only with the scroll. Frozen while paused. |
| Fan shadows | The blades block the sun in the surfaces and shafts (the `fans` pattern in `glsl/apertures.js`). | **Tier 2 only.** On every tier they took the proxy to +80% at 1440 (the blade test runs in every sun lookup). Phones turn the fans but cast nothing. |
| *Lá me* (tamarind leaflets) | **Cut on every tier and from the stills (0.6.7).** Kept here only as the record of what was tried. 16 on Tier 1, 32 on Tier 2. Each is 34 × 13 mm, `mixc(sun-400, tile-jade, 0.3)`, released from the screen's top course. | Drift down and across over 15 s, tumble and glint, rest on the tiles 3 s, fade. Same clock rules as the fans. **No spawn run and no drift path within 0.8 m of Little Rose's bay.** |
| Tamarinds outside | unchanged | sway on Tier 1 too, driven by the scroll (`sway(propT)`) |

`live.js` runs one props clock: `propT = tier >= 2 ? time + scrollY/300 : scrollY/300`, frozen while
paused. Tier 2 idle frames use the existing 30 fps idle cadence, and only while the corridor intersects
the viewport, the tab is visible, motion is on and the menu dialog is closed. Tier 1 draws nothing new
while the page is still. Everything moves in the vertex shader from two uniforms, with no per-frame
allocation. *Optional, judge in the strip:* during the end wash (stage 0.95–1.0) the camera pitches down
4°, scrubbed, so the runner is the last thing seen before the plaster and the focus floor. Drop it if it
reads as a nod.

**Build** (port list at the end of 0.6). `bakeGachBong()` bakes the field (2 × 2 tiles) and the border
(2 × 1) as `DataTexture`s: 256 px per pair on Tier 1 (anisotropy 2), 512 on Tier 2 (anisotropy 4), with
mips. The bake starts when the corridor starts building (1.5 viewports ahead) and yields every 8 rows on
Tier 1 and every 16 on Tier 2. If any slice is over 16 ms on the 4× CPU throttle trace, or INP passes 200
ms while it runs, the bake moves to a module worker; it is pure maths into a transferable `Uint8Array`.
The `house` option in `still-surface.js` draws the floor, the dado with its door breaks and the loang.
`objects/props.js` draws fans and leaflets as one merged geometry and one draw, lit by the same analytic
sun. **The door frames of every unit, plus Little Rose's static leaf (a graft from colour-light), are one
draw**: `vertexPaint` in `paint.js`, with `aPaint` as a vec4 holding rgb and wear.

**Budgets (05-DESIGN 6.7, Tier 1, worst corridor frame, bay 1 with the door open)**

| | Before | Target |
|---|---|---|
| Draw calls | 14 | ≤ 12 (+1 props, −2 frames merged, −1 Little Rose leaf merged) |
| Triangles | 560 | ≤ 2.5k (the proposal had 4.4k with the pots and plants) |
| GPU memory | | +0.5 MB on Tier 1, +2.1 MB on Tier 2, geometry < 0.1 MB |
| Lazy JS, gzip | corridor 6.7 KB | ≤ 10.5 KB; world total ≤ +6 KB |
| JS per frame | | two uniform writes, no allocations |
| GPU frame | | ≤ 12 ms at 390 × 844 on a real A15-class phone (timer query) |

The SwiftShader proxy showed +20–30% with the props. Without pots and plants, expect about +15–25%. **This
is not a measurement.** The live gate (Tier 1 > 14 ms → lite) protects visitors at runtime. If the phone
misses, apply in order: floor anisotropy 1; no leaflets on Tier 1; props shaded without the sun lookup on
Tier 1; then the existing G1 fallback (mobile corridor as stills), which now carries the colour. Until a
phone is found (question 13.3), staging records "not measured on device" in PLAN section 9 and ships with
the gate.

**Stills: every fallback carries the colour** (a graft from colour-light; the must-fix of both judges).
`buildStill` bakes the gạch textures (`slice: false`, 512) and passes them to every corridor-scene shot:
`bay-green-bamboo`, `bay-little-rose`, `bay-cau-han` and `passage-corridor`. In stills the fans stop at a
fixed angle. Leaflets are built resting on the tiles only (a `leaflets: "rest"` build option, ≤ 6 per frame,
none mid-air within 1.5 m of the camera, none in Little Rose's bay). Our Work's wall of doors and Green
Bamboo's gallery (`hub-doors-wide`, `hub-door-*`, `door-*`, `gallery-wall`) get the same house: wash, dado
with door breaks, warm ceiling, plain cream tiles with no runner, no fans. That keeps 6.1's "one world, one
light". Every still is re-encoded within the existing `encode-posters.py` budgets. If the patterned floor
pushes a file over, raise the encoder effort before touching the art. The lit-header fades on those pages
are re-measured.

**Verification.** `light-contrast.mjs` sweeps the stills (every stage point, VI and EN, 390 and 1440), and
the live probe covers Tier 1 **and Tier 2** at stage 0.04, 0.17, 0.22, 0.50 and 0.78. Gate: H ≥ 4.5 and
running text ≥ 7 over the darkest 1%; 0 lines below. `check-sun-crisp` passes. **Signature check:** at 390
the Đốt tre and Vòm cầu pools on the plain band reach a lit-to-shade luminance ratio ≥ 1.6 and the bông
gió patches on the door wall stay crisp; the runner never reaches under them. Re-capture 1440 bay 1 on
Tier 1: the proof caught "Cơ sở trực thuộc" ghosting under the Tre Xanh title. Then the beats test plus a
strip check: no dwell frame may show two headings each above 10% opacity. LCP with `lcp.cjs`, median of 3,
world on and off: ≤ 2.5 s, target 1.8. Walk the whole 16-frame strip at 390 and 1440, Tier 1 and 2, and
answer the tastegate questions "could this be a café?", "does it read as a place for children?" and "does
anything move?". If it is busy, simplify the field tile to indigo on cream first.

### 0.6.3 "Lĩnh vực hoạt động": a floor of gạch bông

The four areas become cement tiles: the corridor's floor seen from above. The hierarchy of H5a stays, but
it is set by **the number of tiles, not their size**. A 20 cm tile is always a 20 cm tile; larger areas
crop more floor and never scale a tile. Giáo dục is a 2 × 2 patch, Bảo vệ trẻ em 2 × 1, Gia đình và cộng
đồng 1 tile, Vận động chính sách 1 tile: 8 tiles, the bento's 8 cells. Text never sits on a tile. Names and
lines stay ink-900 / ink-700 on lime-100 (13.02 / 7.41). There are no new strings.

**Layout**

| Width | `--T` (one tile) | Arrangement |
|---|---|---|
| ≥ 1024 | `clamp(136px, 11.7vw, 176px)`: 168 at 1440 | `.floor` is a 4-column grid with 3 auto rows. The H2 sits at columns 1–2, row 1. The `ul` spans everything with `grid-template: subgrid / subgrid`, so: **Bảo vệ** at columns 3–4, rows 1–2 (fills the top right beside the H2: patch, words under); **Giáo dục** at columns 1–2, rows 2–3 (patch at the top, words at the cell's foot); **Gia đình** at column 3, row 3 and **Vận động** at column 4, row 3 (tile, words under). Column gap `--s-5`, row gap `--s-6`. All four names fit in one 1440 × 900 viewport. Without subgrid, the H2 goes above and the same placement shifts down one row. |
| 600–1023 | `clamp(112px, 13vw, 136px)` | Two columns. Giáo dục is a full row (2 × 2 patch, words beside), then Bảo vệ (2 × 1, words beside), then Gia đình and Vận động side by side (tile, words under). |
| < 600 | `clamp(88px, 30vw, 128px)` | One column. Each area is one tile beside its words. A patch's box is one tile square and crops the patch's first tile at full scale. Order: Giáo dục, Bảo vệ, Gia đình, Vận động. |

Giáo dục's name is `clamp(2rem, 1.2rem + 2vw, 3.25rem)` Archivo 620 and its line is `--fs-lg`. The others
use `--fs-h3` and `--fs-base`. Line-height ≥ 1.6, and words measure ≤ 34ch. **The whole area (tile and
words) is the link**: the `a` sits in the H3 with an `::after` overlay, the arrow sits beside the name, and
the focus ring goes round the `li`. The work sheet's CSS sun layer (`.sun-layer--work`) moves to the left
margin behind the H2 and never overlaps a tile or body text. **No line is drawn between areas** (D0.5):
the joints are the page's own plaster. A medallion has edges, like a door, and is an object rather than a
seam. Keep a one-line feather-mask variant ready in case Maxwell reads them as hard lines.

**Tile art** (`partials/blocks.mjs`, from the shader's SDF numbers; viewBox in mm, a tile is 200)

- One hidden sprite per page holds 4 `<symbol>`s (`gach-hoaThi`, `-matCao`, `-vongTron`, `-bacThang`) and
  the one shared `feTurbulence` wear filter. Each area is one `<svg>` with n `<use>`s at a 200 mm pitch.
  Fills are custom properties (`--cream`, `--pig`, `--acc`, `--wear`), which inherit into `<use>`, so the
  lit copy reuses the symbols.
- Every tile has: a pigment medallion (inset 7, rx 14); a border band between the medallion edge and a
  2.5 mm cream frame line at inset 15, with accent dots of r 2.2 every 20 mm; **corner quarter-motifs** (an
  accent disc of r 38 centred on the tile corner and clipped by the medallion, carrying a cream quarter
  Hoa thị of lobe 13), so where tiles meet in a patch the quarters close into one rosette broken only by
  the plaster joint; the area's block inlaid in cream, 2 × 2 blocks scaled 0.82 inside the frame and clear
  of the corner motifs; accent hearts at block centres.
- Pigment and accent: Giáo dục ochre and indigo; Bảo vệ indigo and ochre; Gia đình jade and ochre (its ring
  closes in the middle); Vận động brick and indigo.
- **Bậc thang, redrawn:** one continuous stepped band of cream (tread and riser), climbing from the
  bottom-left corner motif to the top-right one in 6 equal steps, with the band as thick as `BAC_THANG.w`
  relative to `BAC_THANG.step`. An indigo dot sits at every nosing. It rises left to right in both
  languages and must read as steps at 88 px.
- Wear: the filter at 32% on the base art only (`--wear: 0` in the lit copy). It is rasterised once and
  never animated.

**Light (CSS only, transform and opacity only, no blend modes, nothing on the plaster)**

- A new mask, `public/svg/sun-hoa-thi.svg`, is written by `scripts/blocks-svg.mjs`: the Hoa thị holes as the
  floor's projection (`skewX(-14°) scale(1, 1.1)`), repeatable, crisp, with no blur.
- Each area holds `.gach__light`, a band `calc(var(--T) * 1.25)` wide, masked by that pattern (tile size
  `0.75 × --T`) intersected with a horizontal fade over 22% at each side. Inside it sits `.gach__lit`, the
  same `<svg>` in the lit palette (`--cream: sun-100`, `--pig` / `--acc`: the `-lit` tokens). **The light
  is the tile itself in its sunlit colours, so a patch on indigo is a brighter logo blue and a patch on
  jade is a brighter jade, never a whitish film. Light exists only where pigment and inlay exist, so
  nothing lands on the plaster.**
- On a named view timeline per area (`view-timeline-name: --gach`, range `cover 12% cover 88%`), the band
  translates from `-band` to `+w`, where w = n × `--T`. The lit art counter-translates by the same amount, so
  it stays registered with the base while the quatrefoils slide across the tile. Band opacity goes 0.55 → 1
  (mid-screen) → 0.55. This is the brightness graft. Areas in the same row sweep together.
- Hover: lit art opacity 0.9 → 1 (180 ms `--ease-hover`), the name underlines (2 px), the arrow moves 3 px.
  The `stagger-cascade` reveal is kept.
- **Rest** (reduced motion, `data-motion="off"`, no JS, no `animation-timeline` support, Tier 0): no
  animation. The band rests at 55% of its travel at opacity 0.9: 2–3 crisp quatrefoils across each area's
  right half. Screenshot it; it must look deliberate.

**Budgets:** no JS, no images. HTML ≤ +1.5 KB gzip on home. CSS ≤ +0.6 KB gzip **net**: the dead `.bento`,
`.tile*` and `lightArt` code is deleted (nothing else uses them). The light layers are 8 small composited
layers.

### 0.6.4 Safeguarding and HCWA approvals

- **Little Rose's bay is unchanged in kind.** The door stays shut (`setDoor`). Its transom is the house's
  Hoa thị with plain, untinted light (`transomPattern: "open"`). No corridor sun reaches it (`bayFree`). It
  shares exactly the floor, dado (with the same door break), wash and ceiling every bay has. It has **no
  fan, no fan-shadow disc, no leaflet spawn or drift, and no surround**, whatever the DOM says. No brick
  is in the corridor, and no light anywhere near the bay leans red or pink.
- Tests added to `test/little-rose.test.mjs`. The proposal's `lr-props-check`, kept at
  `docs/design/proposals-2/saigon-home/lr-props-check.mjs`, becomes a test over three bay orders: no prop
  vertex within the bay ±0.3 m, no leaflet path within ±0.8 m, no fan disc (r + 0.1 m) touching it. Plus
  colour-light's pattern: every colour the corridor uses as light (sun, spill, transom, leaflet glint) is
  untinted or has g ≥ r, `tile-brick` is never referenced by the corridor's set or props, and the door
  angle is 0 for `little-rose`.
- **Traces stay unbuilt.** Rule 6.1.5 bans shoes even behind the flag. Only 6.8's chalk *lò cò* on the plain
  band of the Tre Xanh bay may come later, behind `traces.enabled` + `approved` and the PROD guard, never in
  or near Little Rose's bay.
- **HCWA approves before production:** the yellow and mint paint (does it make any real centre
  recognisable?), the fans, the leaflets, the tile palette and the brick tile on the work sheet. The
  safeguarding lead sees `bay-little-rose` re-rendered. No photos, names, numbers or partners are added.

### 0.6.5 What this supersedes

- 6.2 / 6.5: the corridor's "lime wall" is the yellow wash over the mint dado above.
- 6.3, Door row: there is no separate Tier 2 threshold *gạch bông* motif, because the floor carries the
  tiles on every tier. `open.md` 8.3's "gạch bông only at door thresholds" is superseded for this one
  runner. The rest of its café guard (no rattan, plants, neon, coffee) stands.
- 4 "Ornament discipline": the block pattern also appears as tile pigment (focus floor, corridor runner).
- 5.7 "Bento tile" and 7 "Bento tile": replaced by the gạch floor in 0.6.3.
- 11.5: the focus areas are a floor of gạch bông tiles in the bento's hierarchy (by tile count).
- PLAN section 9, "Continuity (D0.5) on the sheets": the sentence on the bento's shade pools is replaced.

### 0.6.6 Port list and who owns what

Two engineers work in parallel. Neither touches the other's files. Tokens are already landed. Each one
logs departures in PLAN section 9 under their own heading.

**Corridor engineer:** `src/world/**`, the corridor stills, `src/styles/stages.css`,
`test/little-rose.test.mjs`, `test/beats.test.mjs`, `public/posters/*`.

| From | File | Port |
|---|---|---|
| saigon-home (`docs/design/proposals-2/saigon-home/saigon-home.patch`; scratch copy `…/scratchpad/proposal-saigon-home/prototype`) | `world/textures.js` `bakeGachBong` | **with changes:** new field and border per 0.6.2, smooth wear, slices of 8 rows on Tier 1 |
| | `materials/still-surface.js` `house` and `HOUSE_GLSL` | **with changes:** dado 0.90; the surround loop becomes the door-break loop over `uDoors` / `uDoorN`; loang from `lw.a`; drop `uSurround*` |
| | `objects/props.js` | **fans and leaflets only:** delete `pot`, `plant`, the blossoms, glaze and terracotta, and their shader branches; leaflet colour per 0.6.2; add `leaflets: "rest"` for stills |
| | `glsl/apertures.js` (`fans` pattern, `thin` flag) | as is |
| | `materials/paint.js` `vertexPaint` | **with changes:** `aPaint` becomes a vec4 (rgb, wear); Little Rose's leaf merges into it |
| | `sets/corridor.js` | **with changes:** gach and house wiring, merged frames, `animate()`, dado in `setSun`. No surrounds or pots. **Delete the `?house=` measurement flags** |
| | `live.js` (props clock) | as is, inside the existing idle-cadence gating |
| | `styles/stages.css` (97.5% pane) | **do not port** |
| colour-light (`docs/design/proposals-2/colour-light/colour-light.patch`; scratch copy `…/scratchpad/proposal-colour-light/prototype`) | `world/textures.js` `bakeLimeWash` alpha | as is (the loang) |
| | `sets/corridor.js` Little Rose leaf merge | the idea, into the merged frames draw |
| | `sets/stills.js` `buildStill` baking and passing textures | the pattern, for `gach` |
| | `styles/stages.css` meta lines to ink-900 | as is, plus the rail labels |
| | `test/little-rose.test.mjs` "kính màu" test | the pattern (red/pink and distance checks), not the glass |
| | `glsl/glass.js`, `objects/glass.js`, clerestory, end window, `rows: 5`, `bakeTerrazzoColour`, transom glass (`doors.js`, `lattice.js`), `GLASS_SHAFTS`, `?gl=`, `light-contrast-home.mjs` | **do not port** |

**Focus engineer:** `src/pages/home/work-sheet.mjs`, `src/partials/blocks.mjs`, `src/styles/pages.css`,
`scripts/blocks-svg.mjs` (the new mask), `scripts/check-tiles.cjs`, `test/sheets.test.mjs`.

| From | File | Port |
|---|---|---|
| saigon-home | `partials/blocks.mjs` `gachTile` | **rewrite** to 0.6.3 (sprite, symbols, corner motifs, border band, the Bậc thang stair, lit copy). The proposal is only the starting point |
| | `pages/home/work-sheet.mjs` `gach()` | **with changes:** whole-area link and words beside or under kept; patches of 4 / 2 / 1 / 1; the H2 inside the subgrid |
| | `styles/pages.css` `.gach__name`, `__text`, `__go`, hover and focus rules | as is. **Delete** `.floor__sun`: the screen blend and the radial pool are what the judges read as smudges and blobs |
| colour-light | the view-timeline brightness ramp | the idea only (0.6.3). `glassArt` and the glass CSS are **not** ported |

### 0.6.7 As built after two critic rounds (9 Nov 2026: binding, overrides 0.6.1 to 0.6.5 where they differ)

Two taste critics judged the build against Maxwell's words ("so bland and all white", "just a plain white
wall") and both streams were changed to answer them. The lead signed off every change below. Measurements
and reasons are in PLAN section 9 ("H4 corridor, fix round 1/2", "H5a focus floor, fix round 1/2",
"Final recheck, m3"); review captures are in `qa/m3-review/`.

**Corridor**
- **Leaflets are cut** on every tier and from the stills. On phones they were specks or frozen blobs, and
  one hung beside Little Rose's door. The fans are the only props.
- **Open doors swing into their rooms** to 96°, hinged on the jamb nearer the camera, not out into the
  corridor (6.3, 6.4's 72° outward swing is superseded). Corridor paint and walls nearer than 1.4 m to the
  lens fade out with a dither. Little Rose's door is unchanged: shut.
- **Thresholds:** in front of every door the runner stops for 6 plain cream tiles (1.2 m), its border
  turning the corner, so each bay has its own rug and the transom emblem lands on plain cream (pool core
  ≥ 1.6:1 lit to unlit, drawn 1.5× its first size). Little Rose's rug is identical and gets plain daylight
  with no pool.
- **Pane:** masked to its content and tinted `lime-50` + 60% `world-vang-sun`; between cards it eases back
  to the door index. `.bays__intro` is ink-900.
- **Exit and seam (D0.5):** the "all work" link lives in the last card's link row, never alone on plaster.
  Over stage 0.92–1.0 the camera tilts 14° down to the runner, then the picture fades to lime-100 from the
  top down (at least 24svh, 16svh on phones) and over a hem 0.38 of a screen (0.36 on phones) above the
  work sheet's top, inside the wash shader. Gate: no 40 px band at the seam moves more than 12 levels and
  no frame between the last door and the H2 is under 15% non-background.
- At 1440 the dwell takes a 3° downward lens shift (verticals stay vertical).
- Door wear sits only at the edges, the kick zone and the handle, as an older coat of the same paint.

**"Lĩnh vực hoạt động"**
- Over the live corridor the sheet rises 24svh (16svh on phones), is solid plaster from its top and has no
  bông gió row; the stacked fallback keeps the row.
- **Sun on the tiles:** the Hoa thị mask cell is 0.54 of a tile (not 0.62), each hole 0.6 of its cell and
  leaning 8° (not 14°), three rows to a seamless repeat; the lit cream is `sun-200`. Each quatrefoil reads
  on its own instead of merging into blots.
- **The floor is laid:** the pigment patches sit in a lattice of plain cement tiles (1.5 px joints, 0–4.5%
  tone variation). Area words may cross plain joints; they never sit on a pigment tile (this is what 0.6.3's
  "text never sits on a tile" now means).
- Layouts follow the floor's container in rem (`@container` 33rem / 56rem), so 200% text steps down a
  layout instead of pushing words onto tiles.
- Each program row carries a strip of its areas' gạch tiles (44 px, 48 px from 1024) on home and Our Work.
  Row hover light stays sunlight, never the area pigment (kính màu stays declined).
- Our Work's area chips carry a 26 px gạch tile in their area's pigment instead of the navy block icon.

**Large text (WCAG 1.4.4)**
- The footer's columns go two by two under 58rem of `.site-footer__cols`; the hero pause toggle moves under
  the buttons under 56rem of `.hero__inner`. Nothing changes at the default text size.

**Still open** (not blocking the review build): the Đốt tre pool still reads as short dashes at 1440; the
programs list after the floor is mostly cream apart from its tile strips; the passage room (S2's start)
is still pale; `check-sun-crisp` skips the corridor set; the first-10-second long task (210–332 ms) and
the 1.8 s LCP goal; `check-hero`'s ambient-motion check at 390 Tier 1 depends on when the first touch
cloud arrives. HCWA still approves everything in 0.6.4 plus the doors opening into their rooms, the
plain-tile floor, the program-row strips and the chips.

## 1. Color tokens

Source of truth: the block below, copied verbatim into `prototype/src/styles/tokens.css`
(build task P0.1) and mirrored to JS by `prototype/scripts/tokens-to-js.mjs` for the
shaders. `theme.json` slugs in `wp/themes/hcwa-2026` use the same names without the
`--color-` prefix. No hex value appears anywhere else in CSS, JS or GLSL.

```css
:root {
  /* tokens:start */
  /* The house in sun: lime wash (vôi) */
  --color-lime-50:  #F7F2E9;  /* sunlit plaster: cards, panels, solid header, fields, secondary button fill */
  --color-lime-100: #EFE7DA;  /* page background, sunlit sections */
  --color-lime-300: #CDBFAB;  /* mortar line: dividers only (decorative, 1.47:1) */

  /* The house in shade: bóng mát, cool, leaning to the logo blue */
  --color-shade-100: #E3E1EC; /* shaded sections (news, alternate inner sections) */
  --color-shade-300: #C3C0D8; /* slat gaps and shade bands in SVG art: decorative, never behind text */
  --color-border:    #7A6F5E; /* field and control borders */

  /* Ink */
  --color-ink-900: #1D2131;   /* text */
  --color-ink-700: #434859;   /* secondary text */
  --color-ink-500: #5A5F70;   /* meta, dates: 14 px and up */

  /* Sun: HCWA yellow is the light */
  --color-sun-400: #FFC42E;   /* primary button fill and Donate only */
  --color-sun-500: #F2B312;   /* primary hover */
  --color-sun-200: #FFE6AE;   /* light patches in CSS and SVG, reading highlight */
  --color-sun-100: #FFF3D3;   /* help section, the flight wash, statement halo */
  --color-gold-700: #8A5A12;  /* small accents on sun-100 / lime-50; draft outline */

  /* Shade and dusk: the logo blue */
  --color-blue-700: #313188;  /* links, focus, secondary border, shutter paint, logo */
  --color-blue-900: #20205A;  /* dusk: contact band, footer */
  --color-blue-950: #16163F;  /* footer bottom bar */
  --color-on-dusk:   #FFFFFF;
  --color-on-dusk-2: #C8CAEB; /* secondary text on dusk */

  /* Units: doors, bands, dots, emblem art. Never text. Only on lime-50, lime-100, sun-100 */
  --color-unit-green-bamboo: #008E3A;
  --color-unit-little-rose:  #DC4C98;
  --color-unit-cau-han:      #417CBD;

  /* Status */
  --color-ok: #256B43;  --color-ok-100: #E2EFE6;
  --color-warn: #8A5A00;      /* "pending" chip text on sun-100 */
  --color-error: #B42318;
  --color-focus: #313188;  --color-focus-on-dark: #FFC42E;

  /* World: shader targets, exported to JS. Never used as CSS backgrounds. */
  --color-world-sky:         #FFF7E6; /* street light seen through the holes */
  --color-world-wall-shade:  #C7CBD2; /* lime wash in the cool shade: the logo's blue-grey, almost no chroma */
  --color-world-wall-sun:    #FFE6AE; /* a sun patch on the wall */
  --color-world-ceiling:     #D5D9DF;
  --color-world-floor-shade: #A5A9B1; /* terrazzo in shade */
  --color-world-screen-face: #3D4370; /* backlit block face: the logo-blue silhouette (holes 8.8:1 against it). Never behind text */
  --color-world-wash:        #FFF3D3; /* the light flood: equals sun-100 */
  /* Gạch bông pigments (05-DESIGN 0.6): the focus-area tiles and the corridor floor. Decorative only:
     never text, never a button, never behind running text. Brick never in the corridor. */
  --color-tile-cream:  #F3E9D6; /* cement ground of every tile; the inlays */
  --color-tile-ochre:  #C4861C; /* mustard: Giáo dục, the floor's quatrefoils (not sun-400: Donate stays the only sun fill) */
  --color-tile-indigo: #2C3584; /* the logo blue as pigment: Bảo vệ trẻ em, the floor's discs */
  --color-tile-jade:   #176F5E; /* xanh ngọc: Gia đình và cộng đồng, the floor's leaves, fan bodies */
  --color-tile-brick:  #A5472A; /* đỏ gạch: Vận động chính sách only */
  /* The same pigments in a sun patch (the focus floor's light); cream in the sun is sun-100 */
  --color-tile-ochre-lit:  #F0B13F;
  --color-tile-indigo-lit: #4265C2;
  --color-tile-jade-lit:   #449C80;
  --color-tile-brick-lit:  #D7703B;
  /* tokens:end */
}
```

Units were sampled from the PNG logos (task 1.1) and stay provisional until the agency
delivers SVGs (B6); re-run the pairs when they change.

### Measured pairs

Measured 6 Nov 2026 with `design-token-contrast` (WCAG 2.x formula): 67 pairs, all pass (77 since
the 0.6 tokens, 9 Nov; their pairs are in 0.6.1).
The pair list is `qa/contrast-pairs.json`. Re-run after P0.1:
`node contraste.mjs --tokens prototype/src/styles/tokens.css --pares qa/contrast-pairs.json`
(the checker source is in the skill's `references/contraste-checker.md`).

| Text / graphic | On | Ratio | Use |
|---|---|---|---|
| ink-900 | lime-50 · lime-100 · shade-100 · sun-100 · sun-200 | 14.33 · 13.02 · 12.36 · 14.47 · 13.07 | all text |
| ink-900 | sun-400 · sun-500 | 10.04 · 8.55 | primary button label, hover |
| ink-900 | ok-100 | 13.49 | active pill |
| ink-700 | lime-50 · lime-100 · shade-100 · sun-100 · sun-200 | 8.15 · 7.41 · 7.03 · 8.23 · 7.44 | secondary text, reading start state |
| ink-500 | lime-50 · lime-100 · shade-100 · sun-100 | 5.70 · 5.18 · 4.92 · 5.75 | meta (14 px+) |
| blue-700 | lime-50 · lime-100 · shade-100 · sun-100 · sun-200 | 9.78 · 8.89 · 8.44 · 9.87 · 8.92 | links |
| gold-700 · warn | sun-100 | 5.35 · 5.37 | accents, pending chip |
| gold-700 | lime-50 | 5.30 | accents |
| ok | ok-100 · lime-50 · lime-100 | 5.43 · 5.77 · 5.24 | status |
| error | lime-50 · lime-100 · shade-100 | 5.90 · 5.36 · 5.09 | form errors |
| white | blue-700 · blue-900 · blue-950 | 10.91 · 14.83 · 17.20 | dusk text |
| on-dusk-2 | blue-900 · blue-950 | 9.26 · 10.73 | footer secondary |
| sun-400 | blue-900 · blue-950 | 9.32 · 10.80 | footer link hover |
| ink-900 | world-wall-shade · world-wall-sun · world-ceiling · world-sky · world-wash | 9.82 · 13.07 · 11.28 · 14.99 · 14.47 | shader targets behind hero text |
| ink-700 | world-wash | 8.23 | statement reading start state |
| ink-900 (large) | world-floor-shade | 6.78 | H1 only, if the solver lets it touch the floor |
| border (3:1) | lime-50 · lime-100 · shade-100 | 4.42 · 4.01 · 3.81 | field borders |
| focus (3:1) | lime-50 · lime-100 · shade-100 · sun-100 | 9.78 · 8.89 · 8.44 · 9.87 | focus ring |
| focus-on-dark (3:1) | blue-900 · blue-950 · blue-700 | 9.32 · 10.80 · 6.85 | focus ring on dusk |
| units (3:1) | lime-50 · lime-100 · sun-100 | green 3.82 · 3.47 · 3.86; rose 3.41 · 3.09 · 3.44; blue 3.89 · 3.53 · 3.93 | bands, dots, door art |
| ink-500 (3:1 icon) | lime-50 · lime-100 · shade-100 | 5.70 · 5.18 · 4.92 | utility icons |

**Measured failures that are rules, not bugs:**

| Pair | Ratio | Rule |
|---|---|---|
| sun-400 fill on lime-50 / lime-100 / shade-100 | 1.43 / 1.30 / 1.23 | Accepted exception (as in Phase 1): the button is identified by its 10.04:1 label. |
| unit-little-rose on shade-100 | 2.94 | Unit colors never sit on shade-100 or darker: center cards are lime-50 panels. |
| ink-900 on world-screen-face | 1.70 | Text never overlaps the screen silhouette; the framing solver (6.6) enforces it. |
| lime-300, shade-300 on their sections | 1.47, 1.37 | Decorative only: dividers, slat gaps. |

**Text over the live world** cannot be proven on tokens. `prototype/scripts/light-contrast.mjs`
renders the world with the HTML hidden at 390×844 and 1440×900, for every sun state a
visitor can produce (each set's sun keyframes sampled every 0.05, pointer extremes ±6°
azimuth / ±3° elevation with the camera's parallax, the cloud dip at 0.82, the canopy's 0.42
low, the S3 flood peak), reads the DOM rects of
every `[data-text-safe]` element and compares its text color with the darkest 1% of
pixels behind it. Gates (internal targets above WCAG): display text (H1, H2 over the world)
≥ 4.5:1, body text (intro, nav, statement, inner-header intro) ≥ 7:1. CI fails below. If the
intro ever fails, the intro moves onto a `--color-lime-50` panel; the light is never dimmed
into haze.

### Color rules

- One action color. `--color-sun-400` fills the primary button and Donate only; at most two
  sun-filled elements per screen. Sunlight itself (patches, glints) is not a button.
- Sections alternate **sun** (`lime-100`, warm, with the CSS light layer) and **shade**
  (`shade-100`, cool). The rhythm is the page's temperature; it replaces Phase 1's flat cream.
- The logo blue is present on the first screen: logo, secondary button border, focus, the
  screen silhouette, the cool shade. It owns the dusk at the bottom of every page.
- Unit colors appear as door paint, 6 px top bands, dots and emblem art. Never as text,
  never on shade-100 or darker.
- No glassmorphism, no `filter: blur()` layers, no neon, no gradients except the light
  itself (shader, light layers) and the dusk band above the footer.
- Dark mode: not in scope. The dusk footer is the only dark surface.

## 2. Typography

| Role | Family (Google Fonts name) | Weights / axes | Where |
|---|---|---|---|
| Display | **Archivo** (Omnibus-Type, OFL) | one file instanced to `wdth 78`, `wght 600–700`; H1 640, H2 620, H3 600 | H1–H3, impact figures, louver-row titles, big dates |
| Body and UI | **Be Vietnam Pro** (OFL) | 400, 500, 600 (static files already in `prototype/public/fonts/`) | everything else |

**Vietnamese coverage, verified 6 Nov 2026 (not assumed):**

| Check | Archivo | Be Vietnam Pro |
|---|---|---|
| Google Fonts CSS2 API subsets (queried today) | vietnamese, latin-ext, latin | vietnamese, latin-ext, latin (all three weights) |
| 147 Vietnamese forms (12 vowels × 6 tones, upper + lower, Đ đ ₫), fontTools on the built WOFF2 | 147/147 (`docs/design/concepts/open/archivo-w78-600-700.woff2`) | 147/147 (400, 500, 600 in `prototype/public/fonts/`) |
| GPOS `mark` / `mkmk` (stacked diacritics) | yes / yes | yes / yes |
| Rendered test line "Hội Bảo trợ Trẻ em Thành phố Hồ Chí Minh" | headless Chrome (`open/hero-*.png`) | headless Chrome (Phase 1) |

Rejected, with evidence (open.md 5.2): Phudu (lowercase draws capitals: would read as ALL
CAPS), Outfit (50/147, no vietnamese subset), Satoshi and Cabinet Grotesk (Fontshare only,
fail Vietnamese), Bodoni Moda (54/147), DM Serif Display (48/147). gpt-taste's shortlist
does not override the coverage rule.

**Build:** extend `scripts/build-fonts.py`: `TTFont(src, lazy=False)`, subset to the existing
`UNICODES` first, then `instancer.instantiateVariableFont(font, {"wdth": 78, "wght": (600, 700)})`.
Output 30,260 bytes. Fonts total 4 files, 122,600 bytes (fraunces-vf.woff2 is deleted).
Self-hosted only. Preload Archivo and Be Vietnam Pro 400. `font-display: swap` with
metric-matched fallbacks (`size-adjust`, `ascent-override`, `descent-override` on a
`local()` fallback face) so the swap moves nothing. Archivo has `tnum` and `lnum`.

**Metrics** (Archivo wdth 78 / wght 640, em units): stacked capital Ổ 1.048, stacked lowercase
ẩ 0.919, deepest descender (ỵ, ợ, g) −0.192. Display line-height 1.15 (H1), 1.18 (H2),
1.25 (H3). All running text in Be Vietnam Pro: line-height 1.65 (body) and 1.6 (statement,
lead), never below 1.6.

**Scale** (rem; fluid where it matters):

```css
--fs-display: clamp(2.5rem, 0.6rem + 6.5vw, 7rem);    /* home H1 ≥ 600 px: 49 @600 · 76 @1024 · 103 @1440 · 112 cap */
/* < 600 px the hero text column is an inline-size container: */
/* .hero h1 { font-size: clamp(1.875rem, 11.4cqi, 2.75rem); }  → 32.8 @320 · 40.8 @390 */
--fs-h1:   clamp(2.25rem, 1.3rem + 3.6vw, 4.5rem);    /* inner page H1: 36 → 72 */
--fs-h2:   clamp(2rem, 1.35rem + 2.6vw, 3.75rem);     /* 32 → 60 */
--fs-h3:   clamp(1.375rem, 1.2rem + 0.7vw, 1.75rem);  /* 22 → 28 */
--fs-statement: clamp(1.5rem, 1.1rem + 1.6vw, 2.5rem);/* Be Vietnam Pro 500, lh 1.6 */
--fs-stat: clamp(2.75rem, 2rem + 3vw, 4.5rem);        /* Archivo 640, tabular-nums lining-nums */
--fs-lg: 1.25rem; --fs-base: 1.0625rem; --fs-sm: 0.9375rem; --fs-meta: 0.875rem;
```

**The hero line, measured** (Pillow + raqm shaping on the built Archivo file at wght 640;
widths in em): "Cùng trẻ em xây dựng" 8.13, "tương lai an toàn" 6.25, "và tươi sáng hơn."
6.38; the two-line split needs 11.55. "Helping children build" 8.28, "safer, brighter
futures." 8.37. So the H1 gets `max-inline-size: 8.6em` and `text-wrap: balance`:

| Viewport | Size | VI | EN |
|---|---|---|---|
| 320 | 32.8 px | 3 lines | 2 lines |
| 390 | 40.8 px | 3 lines (Cùng trẻ em xây dựng / tương lai an toàn / và tươi sáng hơn.) | 2 lines |
| 1024 | 76 px | 3 lines, text column ends at about 660 px | 2 lines |
| 1440 | 103 px | 3 lines, text column ends at 886 px: the right 38% belongs to the screen | 2 lines |

A three-line VI poster at 1440 (it was two lines in `open.md`) keeps the H1 off the screen
silhouette while staying above 100 px. Confirm in Chrome on day 1 (tastegate).

**Rules:**
- Sentence case everywhere, both languages. No `text-transform: uppercase` anywhere;
  `scripts/wp-static-check.py`'s uppercase check is extended to `prototype/src/styles/`.
- Heading compounds are joined with U+00A0 by `viKeep()` (glossary: trẻ em, xây dựng, tương
  lai, an toàn, tươi sáng, unit names), plus `text-wrap: balance` on headings, `pretty` on body.
- Letter-spacing −0.005em on display, 0 elsewhere. Never positive tracking on Vietnamese.
- Measure 60 to 72 characters for body; 34ch for the statement; 36ch for the hero intro.
- Numbers in figures and dates: Archivo `tabular-nums lining-nums`. Mono is not used.

## 3. Space, radius, elevation, layout

```css
--s-1: 4px; --s-2: 8px; --s-3: 12px; --s-4: 16px; --s-5: 24px;
--s-6: 32px; --s-7: 48px; --s-8: 64px; --s-9: 96px; --s-10: 128px; --s-11: 176px;
--section-y: clamp(96px, 10vw, 176px);
--container: 1320px;  --gutter: clamp(16px, 4vw, 48px);
--r-sm: 4px;  --r-md: 8px;  --r-pill: 999px;     /* architectural: small radii, pill buttons only */
--shadow-1: 0 1px 2px rgb(32 32 90 / .06), 0 8px 24px rgb(32 32 90 / .08); /* cool, from the shade */
--edge-block: 56px;   /* height of the bông gió edge on sheets (40px < 600 px) */
```

- Grid: 12 columns ≥ 1024 px, 6 columns 600–1023 px, single column < 600 px. Breakpoints
  600 and 1024; a short-screen rule at `max-height: 640px` (stills replace sticky stages).
- Spacing inside a group is always tighter than between groups. Sheets are full-bleed; their
  content sits in the container.
- Cards only where the whole thing is one link (door cards, story cards, bento tiles,
  report rows); lists and rows elsewhere.
- Nested radii are concentric: child = max(0, parent − gap). Buttons stay pills (the round
  lobes of the quatrefoil, and the current site's button shape).
- Shadows only on things that lift (hover cards, the menu sheet).

## 4. Light and texture (the HTML side of the world)

The world does not stop where the canvas stops. Every page carries the same light in CSS.

- **Sun-in (S1, CSS).** Each lit header (home hero poster, inner-page stills) is two
  pre-rendered images from the same world code: a *shaded* base and a transparent *light
  layer* (patches + shafts). At first paint the light layer is hidden; when it has decoded
  it sweeps in (timeline in 5.4). Without JS, under reduced motion or pause, the light layer
  is simply shown at rest. Plays once per page per session.
- **CSS sun layer.** Sheets without a canvas carry a decorative light-patch layer (AVIF with
  alpha, Hoa thị pattern projected at that section's sun angle, about 12 KB) in margins and
  behind display headings, never behind body text. It drifts at 0.12× scroll through CSS
  scroll-driven animation (`animation-timeline: view()`, transform only) where supported,
  static elsewhere.
- **Bông gió sheet edge.** Every opaque sheet that slides over the canvas has a top edge
  cut as one row of blocks (`mask-image` from a generated SVG, `--edge-block` tall), so the
  world's light still shows through the holes as the sheet arrives.
- **Lam (louver) stripes.** Programs are light passing through: rows and the program header
  use horizontal sun-200 stripes, the same duty-cycle maths as S3.
- **Grain.** A 128 × 128 grey noise PNG with 5% alpha (about 3 KB), tiled as a second
  `background-image` on lime sections. Not an overlay layer, not animated, no blend modes.
  The shaders dither with the same noise to stop banding in the shafts.
- **Aurora, done as daylight.** aurora-ui's glow survives only as sunlight: bloom around
  patches and in S3, computed in the shader. No blob gradients, no blur layers.
- **Ornament discipline.** The block pattern appears as light, as the block-icon set
  (focus areas, filter chips), as sheet edges and the footer's top edge. Nowhere else.

## 5. Motion

### 5.1 Tokens

```css
--ease-out:    cubic-bezier(0.23, 1, 0.32, 1);     /* entering, UI feedback */
--ease-in-out: cubic-bezier(0.77, 0, 0.175, 1);    /* things moving on screen */
--ease-drawer: cubic-bezier(0.32, 0.72, 0, 1);     /* menu sheet */
--ease-sun:    cubic-bezier(0.16, 1, 0.3, 1);      /* light arriving: long soft tail */
--ease-hover:  cubic-bezier(0.215, 0.61, 0.355, 1);/* color and underline changes */

--d-press: 120ms; --d-hover: 180ms; --d-ui: 240ms; --d-sheet: 320ms;
--d-reveal: 520ms; --d-flood: 900ms; --d-sun-in: 1200ms; --d-sun: 1800ms;
--stagger: 60ms;   /* at most 5 children: 240 ms total */
```

GSAP mirrors in `motion/tokens.js` (generated): `CustomEase.create("out", "0.23,1,0.32,1")`,
`"inOut"`, `"sun"`, `"drawer"`. Scroll smoothing: camera `scrub: 0.6`, doors and slats
`scrub: 0.4`, CSS-variable scrubs direct. In-beat camera easing is `dwell()` (5.3).

**Personality.** UI chrome is *Silk* (ui-motion vocabulary: smooth, composed; named moves
`reveal-rise`, `stagger-cascade`, `pop-in`, `toggle-curtain`, implemented in CSS/GSAP, not
framer-motion). The world is physical: light arrives with `--ease-sun`, the camera follows
the scroll 1:1 with smoothing, the pointer sun is a critically damped spring (ω 6 rad/s),
motes follow scroll velocity through a damped spring. No bounce anywhere.

**Rules (emil-design-eng):** UI feedback under 300 ms; nothing scales from 0 (minimum
0.96); no `ease-in` on UI; exits faster than enters; keyboard actions never animate; only
`transform`, `opacity`, `clip-path`, `mask-position` and CSS custom properties animate
(the one color animation is the scrubbed reading state). **Never animated:** the H1,
impact numbers, amounts, the donation panel, form values, error messages. Numbers appear
as they are and never count up.

### 5.2 Scroll infrastructure

- **Native speed.** Lenis 1.3.27 smooths the wheel only: `{ lerp: 0.12, smoothWheel: true,
  syncTouch: false, autoRaf: false }`, driven from `gsap.ticker` with `lagSmoothing(0)`,
  `lenis.on("scroll", ScrollTrigger.update)`. Touch scrolling is fully native. No `snap`
  anywhere, no `normalizeScroll`. Not started under reduced motion or pause.
- **Stages are CSS sticky.** The canvas is `position: fixed` behind the page while a world
  section is in view; text panels inside world sections use `position: sticky`. (Until the
  passage exists, day 4, the canvas is `position: absolute` and scrolls with the hero like the
  stills, so the room never lags the text by a frame.) ScrollTrigger
  only reads progress (`ignoreMobileResize: true`). No ScrollTrigger pins.
- Everything is created inside `gsap.matchMedia()` contexts keyed on reduced motion, the
  `data-motion` attribute and width, so cleanup is automatic.
- Anchors and keyboard: `lenis.scrollTo(target, { immediate: reduced })`; native Tab
  scrolling is left alone; `scroll-padding-top` equals the header height.

### 5.3 The signature timelines

**S1 Nắng vào, CSS (every lit header; ms from the light layer's `decode()` and first paint):**

| Track | From → to | Start | Duration | Easing |
|---|---|---|---|---|
| Light layers opacity | 0 → 1 | 80 | 1000 | `cubic-bezier(0.25, 0.1, 0.25, 1)` |
| Room light position | `translate(3%, -14%) scale(1.16)` → 0 from the top-right corner (the sun lifting moves patches down the wall, about 150 px at 1440; the screen's light holds still) | 80 | 2200 | `cubic-bezier(0.33, 1, 0.68, 1)` (still travelling at full strength) |
| Soft diagonal mask sweep | `mask-position` 100% → 0% | 80 | 1500 | `cubic-bezier(0.33, 1, 0.68, 1)` |

Total 2.28 s. The light comes as two layers baked to composite exactly to the lit still
(`encode-posters.py`): the room's, which drifts, and the screen's, which does not, so the
lattice never shows twice and no runtime mask cuts them. If the light layer has not decoded within 2.5 s it appears at rest without
animation; if the hero is already out of view, no animation.

**S1 on home, WebGL handoff.** When the world module has compiled (`compileAsync`) and the
CSS sun-in has ended, the canvas renders the same rest state (same framing function as the
posters) and fades in over 400 ms `--ease-out`; the posters are then hidden. After that:
every visitor sees the tamarind canopy beyond the screen move the light (patches and beams
dim to 42% and recover as leaves cross them, about 2.5 s a patch; the leaves sway in the holes
too) and dust motes in the beams; fine pointers move the sun ±6° azimuth and ±3° elevation and
turn the camera 0.8° / 0.4° with a 5 cm truck (parallax: the screen's edge slides against the
back wall), all on one spring (ω 6 rad/s), scaled down per layout until every extreme keeps the
text clear; touch screens get scroll-velocity motes and a passing cloud 3.5–5 s after the
handoff, then every 14–22 s (intensity 1 → 0.82 over 1600 ms, back over 2400 ms,
`--ease-in-out`). Scrolling the hero out moves the sun clock 0.08 → 0.14.

**S2 Xuyên tường → Hành lang (home).** One fixed canvas, one camera path, three sections
in a row with no opaque sheet between them: hero (`100svh`), passage (`180svh`, `150svh`
below 600 px) and corridor (`260svh`, `220svh` below 600 px).

| Section · progress | Camera | World | DOM |
|---|---|---|---|
| Hero scrolling out (0 → 100svh) | K0 → K1: yaw +38° toward the screen, dolly 2.4 m, FOV 46° → 42° | sun t 0.08 → 0.14; motes stir with scroll velocity | hero text scrolls away natively |
| Passage 0.00 → 0.28 | K1 → K2: approach the hero block at eye height; the quatrefoil grows to 60% of frame height | exposure 1.22 → 1.6; wash 0 → 1 over 0.18–0.28 (frame becomes exactly `--color-world-wash`) | statement container waiting, halo visible |
| Passage 0.28 → 0.78 | passes through the hole | wash = 1; the scene is not drawn (clear color only); room swapped for corridor behind the wash | H2 + statement visible; sunlit reading scrubbed over 0.30–0.72 |
| Passage 0.78 → 1.00 | K3 → K4: pulls back out of a hole in the corridor screen | wash 1 → 0; exposure 1.6 → 1.22 | statement fades 200 ms at 0.78 (reverses on scroll back) |
| Corridor 0.00 → 0.08 | into bay 1 | sun t 0.25 | stage header (H2 + intro) in |
| Corridor beats (N doors, each 0.84/N) | `dwell(u)` per beat | open doors swing 0° → 72° over u 0.10–0.45; doorway spill and transom emblem grow with the angle | card in at u 0.18 (`reveal-rise` 520 ms, children stagger 60 ms), out at u 0.86 (200 ms) |
| Corridor 0.92 → 1.00 | slows to rest | sun t 0.45 | the next sheet arrives with its bông gió edge |

```js
// u: 0..1 inside one beat → camera travel 0..1 inside that beat (from lantern.md)
const sOut = gsap.parseEase("sine.out"), sIn = gsap.parseEase("sine.in");
export const dwell = (u) =>
  u < 0.22 ? 0.45 * sOut(u / 0.22) :
  u < 0.78 ? 0.45 + 0.10 * ((u - 0.22) / 0.56) :   // slow drift while the card is read
             0.55 + 0.45 * sIn((u - 0.78) / 0.22);
```

Vestibular limits: camera moves follow scroll (never time), no roll, yaw ≤ 40° per
60svh of scroll, FOV change ≤ 15%, no sudden zoom. Cards are never scrubbed: text is either
fully shown or gone.

**S3 Cửa lá sách (home; section `180svh`, `150svh` below 600 px; still on screens < 640 px tall):**

| Progress | What happens |
|---|---|
| 0.00 → 0.35 | Slats rotate 0° → 65° (scrubbed). Gold stripes widen: duty cycle `1 − cos(angle) · 0.055 / 0.06`. |
| 0.35 → 0.75 | Leaves swing 0° → 110° outward; exposure 1.22 → 1.55; shaft density 1 → 1.6. |
| 0.78 (one-shot, not scrubbed) | **Flood:** 900 ms. Exposure 1.55 → 1.75 → 1.6 (`--ease-sun`), shaft density → 2.0 → 1.6, mote burst (outward impulse, brightness × 1.6, decays over 900 ms). Reverses at 1.6× speed if the visitor scrolls back below 0.70. |
| 0.75 → 0.90 | Canvas blends to flat `--color-sun-100`; the loop stops at 1.00. |
| ≈ 0.83 | The help section (pulled up by 30svh) brings "Bạn có thể giúp như thế nào" into view on light that is already ≥ 90% sun-100; it rises with `reveal-rise`. |

The brightening is one-directional, driven by the visitor's own scroll, and its brightest
frame is sun-100, never white: not a flash in WCAG 2.3.1 terms.

### 5.4 Reveals

- `reveal-rise` on H2s: lines masked (`overflow: clip`), rise 0.6em → 0 with opacity,
  520 ms `--ease-out`, when the heading crosses 85% of the viewport, once.
- Content below fades up 12 px, 520 ms, stagger 60 ms, at most 5 per group.
- **Sunlit reading** (the statement): words go `ink-700` → `ink-900` under a moving
  `sun-200` highlight, scrubbed by one custom property. Every frame is ≥ 7.41:1 (worst case
  ink-700 on sun-100 is 8.23). The plain sentence is in a visually hidden span; the split
  word spans are `aria-hidden`, so screen readers hear one sentence.
- Content is visible by default: JS adds `.will-reveal` only when motion is allowed. No-JS,
  reduced motion and pause show everything at once.

### 5.5 Page transitions

Cross-document View Transitions, CSS only (the WordPress theme gets them for free):
the old page is wiped left → right by a soft mask over a sun-200 band (440 ms
`--ease-in-out`), the new page rises 8 px and fades in (360 ms `--ease-out`, 80 ms delay);
the header has `view-transition-name: site-header` and stays still. Types set in a
`pagereveal` listener: `back` (traverse to an earlier entry) reverses the wipe direction;
`lang` (the VI / EN links write `sessionStorage["hcwa:vt"] = "lang"`) is a 180 ms crossfade,
because it is the same page. Off under reduced motion and when motion is paused. Chromium
and Safari 18.2+ animate; Firefox swaps pages instantly.

### 5.6 Pause, reduced motion

- **Tạm dừng chuyển động / Pause motion** (WCAG 2.2.2): a 44 px tall toggle in the hero (a
  text button beside the intro's last line on desktop, under the buttons below 1024 px, in
  the text flow and in Tab order) and the footer, `aria-pressed`, labelled in the page
  language; its name and pause icon never change, pressed shows as a sun-100 fill and a 2 px
  ink-900 border. It stops
  everything that moves by itself (motes, cloud, pointer sun, S1 if running, light-layer
  drift, the render loop when nothing changes) and turns off View Transitions and Lenis. The
  choice is stored (`localStorage` key `hcwa:motion`, inside try/catch) and read by the
  inline head script before first paint: on the next page load `data-motion="off"` behaves
  exactly like reduced motion (stills, stacked stages, three.js never loaded).
- **Reduced motion** (`prefers-reduced-motion: reduce`) never loads three.js, GSAP smoothing
  or Lenis; reveals are instant; stages stack; lit headers show at rest. Hover color changes
  stay (they are not motion).

### 5.7 Micro-interactions

| Element | Behavior |
|---|---|
| Primary button (sun) | Hover: sun-400 → sun-500, 180 ms; one quatrefoil glint (`::after`, sun-100 at 60%) crosses once, 600 ms `--ease-out`. Press `scale(0.97)` 120 ms. Loading: label stays, 2 px bar runs linear 1200 ms. Disabled 45% opacity, no glint. |
| Secondary button | Opaque lime-50 fill (never transparent over the world), 1.5 px blue-700 border, ink label. Hover: border ink-900. Press `scale(0.97)`. |
| On-dusk button | 1.5 px white border, white label; hover fill blue-700. |
| Text link + arrow | Underline 1 → 2 px, offset 4 → 3 px, 180 ms; arrow `translateX(3px)`. |
| Door card / center card | 6 px unit band; hover: unit light spill (0 → 12% gradient from the band) grows to 40% of the card, 280 ms; `translateY(-2px)`. Whole card is the link. |
| Bento tile | Block art's light layer shifts 6 px as if the sun moved, 280 ms; drifts with `view()` while scrolling; tile lifts 2 px. |
| Program row | Lam stripes slide in behind the row 0 → 100%, 520 ms `--ease-out`, then stay; arrow 3 px. |
| Louver row (ways to help) | Top line 1 → 3 px sun-400; a light bar runs under the title 0 → 100%, 520 ms. Text always visible. |
| Header | Transparent over the hero with a 120 px scrim (`world-ceiling` 85% → 0). After 80 px: opaque lime-50 + 1 px lime-300 line, 80 → 64 px, 240 ms. Never hides. |
| Dropdown | opacity + `translateY(4px)` + `scale(0.98 → 1)`, 180 ms; 0 ms on re-hover within 400 ms. |
| Menu sheet (`<dialog>`) | From the right, 320 ms `--ease-drawer`; scrim ink-900 0 → 40%; close 200 ms. Focus trapped, Esc closes. |
| Accordion | `grid-template-rows: 0fr → 1fr` + opacity, 240 ms; chevron 180°. |
| Copy button (disabled until verified) | "Sao chép" → check + "Đã sao chép" (`pop-in`, scale 0.96 → 1, 200 ms), `aria-live="polite"`, reverts after 2 s. |
| Form errors | Opacity + height 160 ms; no shake; the error summary takes focus. |
| Focus ring | 3 px `--color-focus`, offset 2 px; `--color-focus-on-dark` on dusk. Never animated. |

## 6. The 3D system

### 6.1 Principles

1. **One world, one light.** Every 3D image on the site (live on home, stills everywhere
   else) comes from the same scene code and the same sun.
2. **The sun is analytic.** For any point, trace toward the sun to the screen plane and ask
   the block's signed-distance function whether it is in a hole. Two tests (front and back
   face of the 10 cm block) give the correct thinning at grazing angles; the penumbra grows
   at `0.0093 × distance` (the sun's angular size). No shadow maps. The GLSL starts from
   `docs/design/concepts/open/proof-hero.html`.
3. **Text is HTML.** The canvas is `aria-hidden="true"`, `role="presentation"`,
   `pointer-events: none`, behind the content. Nothing readable is ever only in the canvas.
4. **3D after text.** The world is a dynamic import after `load` and idle. The page is
   complete without it.
5. **No people, ever.** No child, figure, silhouette, shoe or name appears in the world or
   any still, by construction.

### 6.2 Sets (scenes)

| Set | Where | Live / still | Contents |
|---|---|---|---|
| `room` | Home hero | live (Tier 1–2); stills for posters | Lime-wash room 8.6 × 3.3 × 8 m, breeze-block screen on the right wall (18 × 8 blocks of 0.30 m over a 0.9 m parapet), terrazzo floor, street light and tamarind canopy beyond, traces (6.8). |
| `passage` | Home, the flight | live | The *hero block*: one high-detail Hoa thị block swapped in for the target hole at eye height (row 1, centre 1.05 m), then the wash. |
| `corridor` | Home centers | live; door stills | Open-air corridor: screen on the left (80 × 7 blocks), lime wall on the right with 0..N doors in their own bays, separated by full-height block partitions. Built from the DOM cards. |
| `shutters` | Home, before help | live; one still | Blue louvered shutters (two leaves, 56 slats) in a window opening, late-afternoon light. |
| `dusk` | Footer of every page | still | The room at 18:00: the light gone blue, one last sun-lit quatrefoil. |
| `headers` | Inner pages, 404 | stills | Framings of the same sets (6.10). |

World layout (metres, Y up, camera always at y = 1.10): room origin 0; corridor origin
x = 100 (24+ m along −z, length `3 + 6.5 × N + 2`); shutters origin x = 200. Shots
(starting values; final shots are approved as stills on day 3):

| Shot | Camera | Look at | FOV (vertical) |
|---|---|---|---|
| K0 hero, landscape | (−1.5, 1.10, 3.3) | (0.5, 1.78, −4) | 46° |
| K0 hero, portrait | (0.83, 1.10, −2.93) | (1.68, 0.91, −3.43) | 49.6° (the back-right corner: below eye height the screen cannot share a 390 px width with the H1 anywhere else in the room; searched over every position) |
| K1 hero out | (0.2, 1.10, 0.6) | hero block centre | 42° |
| K2 at the hole | 0.35 m from the hero block | its centre | 42° |
| K3/K4 corridor entry | (98.9 → 99.8, 1.10, +1.5) | down the corridor, 35° yaw toward the doors | 52° (64° portrait) |
| Shutters | (200, 1.10, 2.6) | (200, 1.75, 0) | 44° (60° portrait) |

### 6.3 Objects and how each is built in code

| Object | Build | Tier 1 | Tier 2 |
|---|---|---|---|
| Hoa thị block | `THREE.Shape` 0.30 m square, holes = quatrefoil (union of four r = 0.056 m circles, 40 samples) + four corner pips (r = 0.021 m), `ExtrudeGeometry` depth 0.06 (a Saigon cast-cement block; 10 cm thinned every patch to a sliver at the room's sun), 4 mm bevel; street-side cap removed | whole screen = one plane, fragment SDF + `discard`, thickness faked by the second aperture test (2 triangles) | `InstancedMesh`, about 300 tris per block, corridor in 4 bays of 140 for culling |
| Hero block (passage) | same SDF, 72-sample quatrefoil, bevel 2 segments, cast-texture noise | one mesh, about 1.3k tris (both tiers) | same |
| Block set (SDF + SVG) | `holeSDF` variants in `glsl/blocks.js`; `scripts/blocks-svg.mjs` writes matching SVG paths from the same numbers | Hoa thị, Mắt cáo, Vòng tròn, Bậc thang (focus-area icons) + Đốt tre, Vòm cầu (unit emblems) | same |
| Room / corridor shell | `PlaneGeometry` quads, one `ShaderMaterial` with the aperture chunk; analytic AO at floor and corners | same | same |
| Lime wash, terrazzo | 2-octave value noise and Voronoi chips baked once at startup into two 512² `DataTexture`s (under 15 ms) | one texture fetch per surface, no per-pixel fbm | same |
| Door | frame 4 boxes, leaf box 0.95 × 2.2 × 0.04 + 2 inset panels; paint shader (unit color, worn edges showing `#8A6A48` wood); transom = 3 blocks of the unit's emblem; doorway = emissive warm plane, nothing inside modeled; threshold *gạch bông* tile motif (Tier 2) | no threshold tiles | all |
| Shutters | 2 leaves × 6 frame boxes, 56 slats in one `InstancedMesh` (0.72 × 0.055 × 0.012 m, pitch 0.06 m); paint `--color-blue-700`, worn edges; stripe aperture for the light | same | same |
| Street beyond | one plane per set: vertical gradient + fbm tamarind canopy, overexposed, not tone-mapped | same | same |
| Light shafts | full-screen pass into a reduced target, view ray vs set box analytically, `sunMask × density`, Henyey–Greenstein g = 0.45, density from the baked noise texture | ¼ res, 16 steps, gated by the week-1 device test | ½ res, 24 steps |
| Dust motes | `Points`, visible only where `sunMask(position) > 0`; drift on two sines plus scroll velocity | 160 | 420 |
| Traces (6.8) | flat decals baked with Canvas 2D into a 256² alpha texture, sampled by the surface shader | yes | yes |

Renderer: `NeutralToneMapping`, exposure 1.22, `SRGBColorSpace` output. **Door paint, the
wash and the street are `toneMapped: false`** with exposure applied by hand, so unit colors
land on their tokens (a test renders a lit door swatch and asserts ΔE2000 ≤ 3 against the
token).

### 6.4 The sun clock, kept crisp

A narrative clock `t` (0 = 7:00, 1 = 17:30) runs down the home page: morning hero, late
morning corridor, late-afternoon shutters, dusk footer. Noon is never shown. Sun direction
is keyframed per set (no single lerp), from the formula
`(cos el · cos az, sin el, cos el · sin az)`:

| Set | t | Azimuth / elevation | Sun color | Signature surface |
|---|---|---|---|---|
| Room at rest | 0.08 | 40° / 24° | `#FFD9A0` | back wall (54°); floor stretch accepted (66°): a sun high enough to crisp the floor drops every patch below the text |
| Room → passage (the hero scrolling out reaches 0.14) | 0.08 → 0.20 | 40° → 36° / 24° → 26° | `#FFD9A0` → `#FFE4B8` | screen holes |
| Corridor | 0.25 → 0.45 | 8° → −8° / 24° → 28° | `#FFE9C8` → `#FFF2DE` | door wall and leaves (light nearly head-on) |
| Shutters | 0.85 → 0.95 | −11° → −16° / 25° → 20° | `#FFD08A` → `#FFB866` | floor stripes |

`prototype/scripts/check-sun-crisp.mjs` asserts, for each set's signature surface while its
content is on screen: incidence angle ≤ 60° (pattern stretch ≤ 2×) and penumbra ≤ 0.5 ×
the quatrefoil lobe radius (corridor) or ≤ 1.0 × (hero back wall, soft by design). The
`open/sun-path.png` t = 0.45 smear (azimuth near 0° grazing the back wall) cannot recur:
the room is never shown after t 0.20.

### 6.5 Units as emblems of light (corridor)

- Each door is its unit's color and its transom is its emblem. Light from the room behind
  passes through the transom and lands on the corridor floor as the unit's shape:
  **Đốt tre** (bamboo culm: two vertical slots bridged by nodes) for Tre Xanh, **Vòm cầu**
  (an arch over a deck slot; *cầu* = bridge) for Cầu Hàn. Same aperture code path, one
  more SDF each.
- Open doors (Tre Xanh, Cầu Hàn) swing to 72°; their doorway spill may carry up to 15% of
  the unit color.
- **Little Rose: the door never opens; its transom is the house's own Hoa thị block; plain
  untinted daylight only; no motif, number plate, sign, shoes, window or trace anywhere in
  its bay.** A unit test asserts, for any card whose `data-unit` is `little-rose`, door angle
  0 and an untinted, motif-free light whatever the DOM attributes say.
- Doors come from the DOM: `world/journey.js` reads `[data-beat]` cards (`data-unit`,
  `data-door="open|closed"`, `data-emblem`) and builds 0 to N doors; the WordPress pattern
  can add, remove or reorder centers without touching the 3D.
- The corridor is a symbolic walk, not a floor plan: each door sits in its own bay with a
  full-height block partition and its own threshold, no numbers or signage, and each card
  carries only that unit's own approved location (never Little Rose's). It must not read as
  one building.

### 6.6 Text safety: the framing solver

`world/frame.js` (from three-places' `fitHero()` and lantern's `setViewOffset` to a DOM
box): on load and resize it reads the `.hero__stage` box and every `[data-text-safe]` rect
(H1, intro, buttons, nav, plus 24 px), then binary-searches the camera's `setViewOffset`
(x and y, 12 iterations) so that no projected bounding box of the screen silhouette or the
floor's front edge touches a text rect, while the hero block stays inside the stage box.
The poster renderer calls the same function per aspect bucket, so stills and live frames
match. A dev overlay (`?debug=frame`) draws the safe zones. Patch intensity inside text rects
is capped per kind of text, with a 24 px feather (a cap, not a lift: no haze): display text
(the H1) is not capped, because light raises ink-900's contrast (13.1:1 on a patch against
9.8:1 on the shade) and the concept puts the patches across it; body text (intro, nav) is
capped at 40%, because a pattern behind 17-20 px text hurts reading; header text is not
capped, its bar carries the 85% scrim. Buttons are opaque, so they never depend on the
world behind them.

### 6.7 Budgets, tiers, gates

Target device: Samsung Galaxy A15 / A25 or Redmi Note 13 class (Mali-G57/G68, Adreno
610/619), Chrome, 4G.

| Budget | Tier 1 (phones, tablets, low-memory laptops) | Tier 2 (fine pointer, ≥ 8 GB or unknown, ≥ 6 cores, no software GPU) |
|---|---|---|
| DPR cap | 1.25 (1.0 above 1.6 M canvas pixels), no MSAA (the SDF screen is smooth without it) | 1.75, at most 3 M pixels; 4x MSAA only under 1 M pixels, otherwise supersampled at DPR ≥ 1.5 |
| Draw calls / frame | ≤ 14 | ≤ 32 |
| Triangles | ≤ 8k | ≤ 60k |
| Shafts | ¼ res, 16 steps, baked density (only if the device gate passes) | ½ res, 24 steps |
| Motes | 160 | 420 |
| GPU frame | ≤ 12 ms at 390 × 844 | ≤ 8 ms at 1440 × 900 |
| JS / frame | ≤ 2 ms, zero allocations | same |
| GPU memory | ≤ 24 MB | ≤ 48 MB |

**Tier 0 (no live world):** no WebGL2, `prefers-reduced-motion`, `data-motion="off"`,
`saveData`, `effectiveType` 2g/slow-2g, `deviceMemory < 2`, or a failed
`failIfMajorPerformanceCaveat` context.

**Live gates** (90 frames after S1, `EXT_disjoint_timer_query_webgl2` when present, else
rAF deltas). rAF deltas cannot see GPU time under vsync, so they gate on missed frames: Tier
2 p75 > 22 ms → Tier 1. Tier 1 p75 > 22 ms → shafts off, DPR 1.0. Still > 33 ms → stop. A
timer query gates on the budgets: Tier 2 > 10 ms, Tier 1 > 14 ms, lite > 22 ms. Stopping
the live world shows the stills + CSS sun layer and sets `sessionStorage hcwa:tier = 0`; a GPU
that fails the probe (no WebGL2, caveat context, software renderer) is remembered for this
browser (`localStorage hcwa:gpu0`, keyed by user agent) and its probe context is never
released by `WEBGL_lose_context` (a synchronous round trip that blocked a phone for 1.3 s).
**Week-1 device gate:** if Tier 1 misses 12 ms GPU on the real phone after cutting shafts,
mobile ships the corridor and shutters as stills with cards; the hero and the passage keep
their live 3D.

**Render loop:** runs only while a `[data-world]` section intersects the viewport (rootMargin
10%) and the tab is visible; renders only when something changed (scroll, pointer, running
tween, motes); 30 fps when only motes move; the wash plateau draws no scene. Canvas is
`visibility: hidden` when no world section is in view. `webglcontextlost`: show the posters,
try one restore.

**Payload (measured 6 Nov 2026, rolldown `--minify`, gzip -9; GitHub Pages serves gzip):**

| Asset | gzip | Loads |
|---|---|---|
| three 0.187.0, the classes the world imports | 139.8 KB | dynamic import after `load` + idle |
| gsap 3.15.1 + ScrollTrigger + CustomEase + lenis 1.3.27 | 52.6 KB | `defer`, after first paint |
| World code | budget ≤ 18 KB | with three |
| Fonts, 4 WOFF2 | 122.6 KB | preload Archivo + BVP 400 |
| Hero shaded base AVIF 480 / 960 / 1600 w | ≤ 12 / 28 / 60 KB | `<picture>`, `fetchpriority="high"` |
| Hero light layer AVIF (alpha) 480 / 960 / 1600 w | ≤ 10 / 22 / 45 KB | after the base |
| Inner header still (base + light) 1600 w / 480 w | ≤ 70 / 22 KB together | eager, sized |
| Door, shutter, dusk, story stills, AVIF 800 w | ≤ 35 KB each | `loading="lazy"` |

**LCP:** the H1 or the 480 w poster, both in the first ~90 KB. Target 1.8 s on Lighthouse
mobile (limit 2.5 s). Shader compile goes through `renderer.compileAsync` before the first
visible frame; the world cannot move LCP.

### 6.8 Traces of children's lives (needs HCWA approval)

An empty adult room says "architecture", not "children". The world carries a few flat,
non-identifying traces at child height, as decals the analytic light handles: a chalk
*ô ăn quan* board on the room's terrazzo, a chalk *lò cò* (hopscotch) grid in the Tre Xanh
bay, short pencil height marks (no names, no dates, no letters) on the room's left pier.
Never in or near the Little Rose bay. Controlled by `content/draft/world.json`
(`traces.enabled`, `traces.approved`); a PROD build fails if traces are enabled and not
approved by HCWA's safeguarding lead. Posters render with and without them.

### 6.9 Loading order

1. HTML with inline critical CSS (≤ 14 KB), font preloads, the hero `<picture>` (shaded base
   + light layer).
2. Inline head script (≤ 1.5 KB): reads reduced motion, `hcwa:motion`, saveData,
   `effectiveType`, `deviceMemory`, pointer; sets `<html data-tier="0|1|2"
   data-motion="on|off">` and `sun-pending` (only when the CSS sun-in will play).
3. `defer`: UI scripts, motion layer (GSAP, ScrollTrigger, Lenis), reveals.
4. After `load` + `requestIdleCallback({ timeout: 2000 })`: `import('./world/index.js')`,
   WebGL2 probe, build `room` and `passage` only (corridor and shutters build when within
   1.5 viewports), bake textures in `scheduler.yield()` slices, `compileAsync`, render one
   rest frame, hand off.
5. A CSS safety net: if the world is not live 6 s after `load`, nothing changes (the posters
   are already the complete page).

### 6.10 Stills, posters, and what each visitor sees

All stills come from the world code: `prototype/scripts/render-posters.mjs` drives the cached
Chrome Headless Shell (SwiftShader WebGL, as `open/hero-*.png` were made) over the DevTools
protocol, loads `world/poster.html?shot=…&t=…&w=…&layer=base|light`, captures the canvas
with no HTML, and `prototype/scripts/encode-posters.py` (Pillow: AVIF, WebP, JPEG) writes
them and fails if any file is over budget. Posters are committed (GitHub Pages and WordPress
serve static files) and regenerable in CI. Aspect buckets: portrait 9:19.5, tablet 3:4,
landscape 16:10 and 16:9, each framed by `frame.js`.

| Visitor | Hero | Passage (statement) | Corridor | Sheets | Shutters | Footer |
|---|---|---|---|---|---|---|
| Tier 1–2, motion on | CSS sun-in, then live | live flight + wash | live, sticky cards | CSS sun layer, reveals | live + flood | dusk still |
| Paused on this page | frozen frame; no idle motion; scroll still moves the camera | same | same | static | same, no flood | same |
| Reduced motion, or paused on an earlier page | sunlit still at rest | statement on a sun-100 band, no sticky | stacked: door stills 4:3 + cards | static | open-shutters still | dusk still |
| Tier 0 with motion on | CSS sun-in on the still | sun-100 band | stacked stills | CSS sun layer | still | still |
| No JS | sunlit still (default markup) | band | stacked stills (default markup; sticky is a JS-added class) | static | still | still |

### 6.11 Later: Blender assets (optional, Tier 2 only)

Blender is not available in this environment, so the procedural assets are the product. A
volunteer artist can refine them later: `prototype/scripts/export-glb.mjs` exports the
procedural objects with three.js `GLTFExporter` (metres, Y up, fixed node names) plus the
hole layout as SVG from `blocks-svg.mjs`, as Blender starting files. Replacements load with
`GLTFLoader` + meshopt (KTX2 only if a texture is needed), on Tier 2 only, and fall back to
procedural on any error:

| Asset | Node names kept | Budget |
|---|---|---|
| Shutters with moldings, hinges, baked AO, worn paint | `leaf_L`, `leaf_R`, `slat_00`–`slat_55` | ≤ 180 KB |
| Door with frame moldings and handle | `frame`, `leaf`, `transom` | ≤ 120 KB |
| Hand-cast Hoa thị block (hero block and Tier 2 screen) | `block`; holes must match the SDF exactly | ≤ 40 KB |
| Corridor columns, beams, parapet caps | `bay_*` | ≤ 150 KB |

The analytic sun stays: any Blender screen must keep the SDF hole positions.

## 7. Components (build once, reuse everywhere)

| Component | Rules |
|---|---|
| Header | Logo (mark + `logoLines`) left; menu (Về chúng tôi · Hoạt động · Tác động & Báo cáo · Tin tức · Chung tay · Liên hệ) in BVP 500 15 px; VI / EN; Ủng hộ (sun). 80 → 64 px desktop, 60 px mobile (mark, VI / EN, Ủng hộ, "Menu" text button ≥ 44 × 44). Transparent over the hero (scrim), solid lime-50 after 80 px. Never hides. |
| Language switch | Text "VI / EN", current bold + underlined, `aria-current="true"`, `hreflang` and `lang` on each link, `data-lang-switch` (View Transition `lang` type). |
| Pause toggle | 44 × 44, `aria-pressed`, visible label on desktop, icon + accessible name on mobile; hero and footer. |
| Draft toggle (staging only) | "Hiện khung nháp / Show draft outlines": outlines **off by default** (Maxwell, 9 Nov 2026), on for the session after a press or with `?drafts=1`, `sessionStorage`. The `data-draft` attributes and the PROD guard are unchanged. Never in PROD. |
| Draft marking | `data-draft` + 1 px dashed `--color-gold-700` outline at 55% opacity, 2 px offset. PROD build fails on any `data-draft`. |
| Button | Primary (sun, ink label), secondary (opaque lime-50, blue-700 border), on-dusk (white outline), text link with arrow. Min height 48 px, pill. States: hover, focus-visible, active, disabled, loading. |
| Lit header (inner pages) | Shaded still + light layer (`<picture>`, 3:1 desktop, 4:3 mobile) with CSS sun-in; H1 + intro over the still's text-safe wall on desktop, below the still on mobile; 6 px unit band when the page belongs to a unit. WordPress: `hcwa/lit-header` pattern. |
| Statement | H2 + one paragraph at `--fs-statement`, sunlit reading, sun-100 halo (feathered `mask-image`) so it always sits on sun-100. |
| Stage (sticky) | Skip link before it; index tabs (unit names, real anchor links, `aria-current` on the active beat); cards in DOM order, all present; `focusin` scrolls to the card's beat; a `ResizeObserver` stacks the stage when a card is taller than 44svh (< 600 px) or 70svh. |
| Door card | lime-50 panel, 6 px unit band, unit name (Archivo 600 `--fs-h3`), who it serves, location line only where approved (never Little Rose), one link ("Xem cơ sở"; Little Rose: "Tìm hiểu về mái ấm" + "Tiệm bánh Hoa Hồng Nhỏ"). |
| Gạch floor (focus areas; was "bento tile") | Section 0.6.3: each area is a patch of 20 cm cement tiles (4 / 2 / 1 / 1) in its pigment with its block inlaid, words on the plaster beside or under it, a sunlit Hoa thị band crossing it with the scroll. Name, one line, link to `?area=`; the whole area is the link. |
| Impact plaque | Archivo 640 `--fs-stat` tabular, label in BVP; staging shows `[N]` + "Đang xác minh" chip; PROD renders the row only when ≥ 2 figures are verified. Never counts up. |
| Program row | Status pill (ok on ok-100 / ink-700 on shade-100), years token, name (Archivo 600), summary, partner names as text, arrow, lam stripes on hover. Rows, never cards. |
| News list item | Big date (Archivo tnum), category label, title, external link to the live post. Featured story card: staging only until an approved story exists, with the "names changed" note. |
| Louver row | Full-width slat: Archivo title at `--fs-h2` left, one line + link right. Only the Ủng hộ row has a sun button. |
| Partner wall | One-tone logos (ink-900 at 70%), same box height. Rendered only when ≥ 4 approved logos exist. No marquee. |
| Contact band | blue-900, Archivo 620 white title, on-dusk button, phone and email inline. |
| Footer | Dusk still band on top, block-row silhouette edge, one sun-400 quatrefoil by the address; 4 columns ≥ 1024 px, `<details>` accordions below; bottom bar blue-950: © year, Facebook link (never an embed), VI / EN, pause toggle. |
| Sheet edge | `mask-image` row of blocks, `--edge-block` tall, on every sheet that slides over the canvas. |
| Form field | Label above, helper, error below with icon, `aria-describedby`; consent checkbox linking the privacy notice; error summary focused on submit. |
| Payment panel (Donate) | lime-50 card; every bank field shows "Chờ HCWA xác nhận" chip; copy disabled; values only ever from `content/verified/donation.json`. |
| Pending chip | warn on sun-100, 1 px dashed gold-700. Staging only. |
| Filter chip | Block icon + label; links to `?area=` (works without JS); selected: icon lights (sun-200). |
| Report row, breadcrumb, language notice, announcement bar, EN bar | As Phase 1 (`06-PAGES.md`, `07-BILINGUAL.md`), restyled with these tokens. |
| Gallery panel | Approved photos hang on lime-50 "wall" panels with a 1 px edge, 3:2, lightbox with keyboard support. Never under the light layer, never with text over them. |

Icons: the block set is the illustration language; utility icons are one outline set (Phosphor
Regular or Lucide, 24 px grid, 1.5 px stroke). No emoji anywhere (strip leading emoji from
news titles in templates).

## 8. Photo, illustration and world imagery (safeguarding unchanged)

- No child appears in the 3D world or any still, ever. Stills and posters are decorative
  (`alt=""`, `aria-hidden`), because the HTML carries the content.
- Real photos come only from HCWA's agency (`10-ASSET-REQUEST.md`) with HCWA approval per
  photo. They hang on inner pages and in stories as wall panels; the home hero stays
  architectural. No stock photos of children; no invented photos.
- Natural light, eye level, activity over posing; hands, backs, groups at a distance and
  over-the-shoulder shots are good defaults. Dignity first: no staged sadness, no poverty
  framing.
- Little Rose: illustrations only (B2), never a location, never an open door, never a
  figure. Its world presence is a closed door in daylight, approved by HCWA's safeguarding
  lead before anyone outside the review team sees it.
- Story subjects get changed names plus the visible note "Tên nhân vật đã được thay đổi để
  bảo vệ trẻ." / "Names have been changed to protect the children's privacy."
- Every real image has alt text in both languages describing the activity, never naming a
  child. Formats AVIF + WebP + JPEG, `srcset` 480/768/1200/1600/2400.
- No live social feeds.

## 9. Accessibility and bilingual rules

- WCAG 2.2 AA. All text in HTML, in reading order. One H1 per page, landmarks, skip link
  "Bỏ qua, đến nội dung chính" first in the tab order.
- Contrast: section 1, including text over the live world (CI gate).
- 2.2.2: the pause toggle. 2.3.1/2.3.3: S3 is one-directional and scroll-driven; reduced
  motion removes every world motion.
- 1.4.10 / text zoom: stages stack (ResizeObserver rule); H1 widths are in `em`.
- Keyboard: index tabs and skip links before stages; focus never lands on a hidden card;
  Escape closes overlays; touch targets ≥ 44 × 44 (buttons 48).
- Every UI string exists in VI and EN (`content/draft/ui.json`); no mixed-language controls.
  `lang` on `<html>` and on any foreign phrase. Dates and numbers per locale.
- Vietnamese never in ALL CAPS; running text line-height ≥ 1.6.

## 10. Do / don't

| Do | Don't |
|---|---|
| Let light do the work: patches, shafts, the flood | Blob gradients, neon glows, glassmorphism |
| Keep the camera at 1.10 m, following the scroll | Scroll-jacking, snapping, time-driven camera moves |
| Three signatures; everything else calm and fast | A fourth showcase, a 3D scene on inner pages |
| Alternate sun and shade sections | One flat cream for the whole page |
| Units as door paint, bands and emblems of light | Unit colors as text, pink light near Little Rose |
| Archivo condensed, sentence case, big | ALL CAPS, wide tracking, eyebrow pills, "SECTION 01" labels |
| Rows for programs, doors for places, louvers for actions | The same card row in every section |
| Stills from the world while photos are missing | Stock photos, grey boxes on the home page |
| Specific labels: "children in classes and clubs in [year]" | "Lives touched", counters that count up |
| One tone partner logos, once 4 are approved | A one-logo partner row, a marquee |
| Ask "could this be a café?" and "does it read as a place for children?" | Rattan, plants, coffee, furniture, signage in the world |
| Saigon materials as paint and tile: yellow wash, mint dado, one *gạch bông* runner, ceiling fans, tamarind leaflets (0.6) | Pots, potted plants, fallen flowers, any prop a café would also buy |

## 11. Departures from 06-PAGES and 10-ASSET-REQUEST (HCWA decides)

1. Home order: the "what we do" statement moves into the flight, right after the hero;
   centers (corridor) come next; focus areas, impact figures and programs follow as one
   "Our work" sheet. 06 order was hero → impact → what we do → centers → programs.
2. The impact strip leaves the hero and becomes plaques in the Our work sheet (the
   "≥ 2 verified or hidden" rule is unchanged).
3. The home hero is a 3D room, not a split layout with photo A2. A2 is retired from the home
   hero; approved photos go to inner pages and stories.
4. Home centers appear one at a time in the corridor (all three in the DOM).
5. Focus areas are a floor of *gạch bông* tiles in a bento's hierarchy, by tile count (0.6.3; 06 said a
   2×2 list, not cards).
6. Partners render only with ≥ 4 approved one-tone logos (06: up to 12 on home).
7. The featured story is staging-only until an approved story exists.
8. Ways to help are four full-width louver rows (06: a 4-column row).
9. The Little Rose card links "Tìm hiểu về mái ấm / About the shelter", not "Xem cơ sở".
10. Every inner page header is a lit still from the world (fills C1 until photos exist).
11. No eyebrow line with the founding year in the hero (the year is unverified).
12. `09-QA-CHECKLIST.md` items "signature detail (unit color bands) visible" and
    "reduced motion removes the fade-ups" are replaced by the checks in `prototype/PLAN.md`.

## 12. Agent prompt guide

Read this file and `prototype/PLAN.md` before any UI work. Use only the tokens in section 1
(no stray hex, also not in GLSL: use `world/tokens.js`). Every visible string comes from
`content/draft/*.json` in both languages; new copy is `[DRAFT: …]` with `data-draft`. Text is
HTML; the canvas is decoration. Build the no-JS / Tier 0 version of a section first, then
the motion, then the world. Never put a child, a name, a location for Little Rose, a number
HCWA has not verified, or a payment detail outside `content/verified/` on any page or in any
still. Check every section at 390 and 1440, with reduced motion, with `?tier=0`, and with JS
off before calling it done.

## 13. Open questions

1. HCWA safeguarding lead: approve the Little Rose closed door in daylight, its privacy
   note, and the traces (6.8) before anyone outside the review team sees them.
2. Cầu Hàn: is "Hàn" a place name, and is HCWA comfortable with a bridge-arch emblem for it?
3. A real Galaxy A15-class phone (or Chrome remote debugging on one) for the week-1 device
   gate: who has one?
4. Archivo condensed is a strong voice. If HCWA finds it severe, widen to `wdth 88` and
   re-measure the H1 (8.6em rule).
5. The 06-PAGES departures in section 11.
6. Unit colors and every unit pair re-measured when the SVG logos (B6) arrive.
7. Hosting: GitHub Pages now (gzip only, no cache headers); Netlify was planned and would
   add brotli and headers.
8. A volunteer Blender artist for 6.11, if Maxwell wants hand-modeled assets later.
9. promptkura.me/library, named by Maxwell, sits behind a sign-in and could not be consulted.
10. HCWA (0.6.4): approve the corridor's yellow wash and mint dado (does either make a real centre
    recognisable?), the ceiling fans, the tamarind leaflets and the tile palette. The safeguarding lead
    sees the re-rendered `bay-little-rose` before anyone outside the review team does.
11. Measure the corridor on a real A15-class phone (question 3) before the richness pass leaves staging.
