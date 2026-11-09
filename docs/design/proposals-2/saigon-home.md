# Proposal: Saigon home (corridor and "Lĩnh vực hoạt động")

Proposal for Maxwell's feedback of 9 Nov 2026: *"the Linh vuc hoat dong section is so bland and all white
and the hallway section is so boring its jst a plain white wall"*. One of two independent proposals; this one
takes its colour from the materials of a Saigon home. Prototype only: nothing here is live, and HCWA approves
colours, objects and any traces before they ship.

**The idea in one line:** the corridor stops being a white architectural render and becomes a lived-in *mái
ấm* corridor: a *gạch bông* (encaustic cement tile) runner on the floor, a mint oil-paint dado under a
Saigon-yellow lime wash, ceiling fans turning, pots of Biên Hòa glaze with gold-edged snake plants by the
breeze-block screen, and tamarind leaflets drifting down through the sun. Then the same tiles come out into
the next section: the four focus areas become four big cement tiles laid edge to edge, each its own pattern
and pigment, with the sun sliding across them as you scroll.

Why this is HCWA's and Saigon's, not a generic "make it colourful":

- **The palette is HCWA's own, as pigment.** The tile pigments are the logo blue (indigo), HCWA's yellow
  (deepened to mustard so it never competes with the Donate button), and two classic cement-tile colours
  (jade, brick). Yellow walls over a green dado is the paint of Saigon schools, ward offices and old
  shelters: the kind of building HCWA's centres are.
- **The tiles are the bông gió blocks turned into a floor.** Each focus-area tile is inlaid with the holes of
  the block that already stands for that area (Hoa thị, Mắt cáo, Vòng tròn, Bậc thang), generated from the
  same numbers the shader uses (`glsl/blocks.js`). Vòng tròn's ring only closes where four blocks meet,
  which suits "family and community".
- **The corridor floor is the house's Hoa thị as a tile**: an ochre quatrefoil in each tile, an indigo disc
  with a cream quatrefoil where four tiles meet. The runner stops short of the door wall, so the sun still
  draws the block's light on plain cement there. The signature (light shaped by the block) is kept, not
  buried under pattern.
- **Tamarind (me) is already the tree outside the screen.** In the dry season Saigon's tamarinds drop their
  tiny yellow-green leaflets like confetti (*lá me bay*). They drift through the corridor's sun.

## Proof

All frames are Playwright captures of the live page in the scratch build, `?tier=1` unless marked, VI.
Files in [`saigon-home/`](saigon-home/).

| | Before | After |
|---|---|---|
| Corridor, bay 1 (Tre Xanh door opening), 1440 | `before-corridor-bay1-1440.jpg` | `corridor-bay1-1440.jpg` |
| Corridor, mid (Little Rose bay), 1440 | `before-corridor-mid-1440.jpg` | `corridor-mid-1440.jpg` |
| Corridor, bay 1, 390 × 844 | (qa/m2/final strip) | `corridor-bay1-390.jpg` |
| Corridor, mid, 390 × 844 | (qa/m2/final strip) | `corridor-mid-390.jpg` |
| Corridor, bay 1, Tier 2 (shafts ½ res, fan shadows on) | | `corridor-bay1-1440-tier2.jpg` |
| Focus areas, 1440 | `before-focus-1440.jpg` | `focus-1440.jpg`, `focus-1440-scrolled.jpg` |
| Focus areas, 390 | | `focus-390.jpg`, `focus-390-scrolled.jpg` |
| Focus areas, EN 1440 | | `focus-1440-en.jpg` |
| Focus areas, reduced motion 390 / no JS 1440 | | `focus-390-reduced-motion.jpg`, `focus-1440-no-js.jpg` |
| Side by side | | `compare-corridor-1440.jpg`, `compare-focus-1440.jpg` |
| The baked floor texture (field + border tile) | | `gach-bong-floor-texture.jpg` |

The code is in the scratch copy (`/tmp/claude-0/-home-user-hcwa-rebuild/8d9e250b-651c-5832-842a-35473e3cdd1b/scratchpad/proposal-saigon-home/prototype`)
and as a patch against `prototype/src`: [`saigon-home/saigon-home.patch`](saigon-home/saigon-home.patch)
(12 files, one new: `world/objects/props.js`). `prototype/` itself is untouched.

## 1. The corridor

### 1.1 Materials and colours

New tokens, added to `tokens.css` (the D1 block gains the `tile-*` pigments; the world block gains the
corridor's shader targets). No hex value anywhere else, as before.

| Token | Hex | Where | Notes |
|---|---|---|---|
| `--color-tile-cream` | `#F3E9D6` | cement ground of every tile | ink-900 13.27:1, ink-700 7.55:1 on it (never used for text in this proposal) |
| `--color-tile-ochre` | `#C4861C` | mustard pigment: floor quatrefoils, Giáo dục tile | cream inlay 2.57:1 (decorative); deliberately not `sun-400`, so the Donate button stays the only sun-filled thing |
| `--color-tile-indigo` | `#2C3584` | logo blue as pigment: floor discs and border, Bảo vệ trẻ em tile | cream inlay 8.94:1 |
| `--color-tile-jade` | `#176F5E` | *xanh ngọc*: floor leaves, fan bodies, glazed pots, Gia đình tile | cream inlay 5.02:1 |
| `--color-tile-brick` | `#A5472A` | *đỏ gạch*: floor rings and border edge, terracotta pots, Vận động tile | cream inlay 4.93:1. Never in Little Rose's bay as light (it is floor pigment, and the bay gets no props) |
| `--color-tile-mint` | `#8DC2A6` | the dado as a CSS colour (for the WordPress theme's swatches) | |
| `--color-world-vang-shade` / `-sun` | `#D5BB7E` / `#FFE09A` | Saigon yellow lime wash above the dado, in shade / in a sun patch | replaces `wall-shade` `#C7CBD2` in the corridor only; ink-900 8.55 / 12.47 |
| `--color-world-dado-shade` / `-sun` | `#5B8F78` / `#A2D4B6` | mint oil-paint dado, 1.12 m high, on every wall | ink-900 on dado-shade is **4.30**: text never sits on the bare dado (the corridor text is always on its pane; rule like `world-screen-face`) |
| `--color-world-dado-line` | `#1C5A4C` | the 3.5 cm painted line on the dado's top | |
| `--color-world-gach-shade` | `#BDAF93` | tile cream in the corridor's shade (the floor's shade target) | ink-900 7.39 |
| `--color-world-ceiling-warm` | `#DDD5C4` | white lime-wash ceiling, warm bounce off the floor | ink-900 10.95 |

Door surrounds: a painted band 11 cm wide round Tre Xanh's and Cầu Hàn's openings, their unit colour mixed
42% toward `lime-50`. **Little Rose's door gets no surround** (D6.5: nothing is added to its bay).

**Text over the world, measured** (`measure.cjs` in the scratch folder: hide the corridor text, take the
darkest 1% of pixels behind each line, compare with its colour; same gates as `light-contrast.mjs`: display
≥ 4.5, running text ≥ 7):

| Frame | Before (worst line) | After (worst line) |
|---|---|---|
| 1440 bay 1 | 7.25 (`[phường]…`, 14 px) | 7.19 |
| 1440 mid | 7.89 | 7.85 |
| 390 bay 1 | 7.19 (rail label, 13 px) | 7.19 |
| 390 mid | 7.76 | 7.75 |

On the phone the darker yellow and mint behind the pane first measured 7.00 on a 13 px rail label, so the
phone pane goes from 96% to 97.5% `lime-50` (`stages.css`, one value). Every line passes in the four frames.
This is two scroll points per size; the full sun-state sweep (`light-contrast.mjs` over the bay stills) has to
be re-run once the bay stills are re-rendered (section 4).

### 1.2 Objects and motion

| Object | What it is | Motion |
|---|---|---|
| *Gạch bông* runner | 20 cm cement tiles: a border row, six field tiles, a border row (1.6 m from the screen), then plain cream tiles with hairline joints to the door wall, where the sun lands | none (the sun moves on it, as before) |
| Dado + lime wash | mint oil paint below 1.12 m on every wall, the darker line on top, Saigon yellow above; the mottled lime wash shows above the dado only | none |
| *Quạt trần* (ceiling fans) | one per bay (not Little Rose's), hung on a 0.6 m rod at 2.45 m, jade body, cream pitched blades | turn at a lazy 2.1 rad/s (one turn every 3 s: no strobing at 30 fps). Tier 2: on the clock and the scroll; Tier 1: with the scroll only (no idle frames on a phone); frozen when motion is paused |
| Fan shadows | the blades block the sun: they sweep across the top row of sun patches on the wall and through the shafts | Tier 2 only (cost, 1.4) |
| Pots and plants | per bay (not Little Rose's) and in the lead-in: three by the screen, one by the door wall near the far pier. Biên Hòa glazed pots (jade, cobalt) and terracotta; *lưỡi hổ* (snake plant, gold edges) and *trầu bà* (pothos) | leaves sway with height, as if the street's air comes through the screen; same clock rules as the fans |
| *Lá me* (tamarind leaflets) | 16 (Tier 1) or 32 (Tier 2) yellow-green leaflets from the screen's top course | drift down and across over 15 s, tumbling, glinting in the sun, rest on the tiles 3 s, then fade; never spawned within 0.8 m of Little Rose's bay |
| *Hoa sứ* (frangipani) | four fallen blossoms per bay (not Little Rose's), two on the runner, two on the plain tiles in the sun | none |
| Tamarinds outside | unchanged, but now sway on Tier 1 too, driven by the scroll | |

Nothing moves faster than the scroll asks on a phone, nothing hijacks the scroll, and with reduced motion or
Tier 0 the world is not loaded at all (stills, section 4).

### 1.3 How it is built (scratch copy)

- `world/textures.js` `bakeGachBong()`: the floor's field (2 × 2 tiles) and border (2 × 1) baked as
  `DataTexture`s from tile SDFs in tokens' pigments, 2 × 2 supersampled, a grout hairline, per-tile tone
  variation and worn pigment, stored as albedo relative to the cream like the terrazzo. 512 px a pair of tiles
  on Tier 2, 256 on Tier 1; anisotropy 4 / 2. Baked in 16-row slices when the corridor builds (idle, after
  the hero is live).
- `materials/still-surface.js` `house` option: the floor samples field or border by distance from the screen,
  then plain tiles with analytic joints; walls switch to the dado's shade and sun targets below `uDado.x`
  with the line; the door wall draws the surrounds (`uSurround[i]`, loop bounded by the door count).
  The surface model, AO and caps are unchanged.
- `objects/props.js` (new): fans, pots, plants, blossoms and leaflets merged into **one geometry and one
  draw**. Per-vertex `aK` (kind, seed, shape, sway) and `aPivot`; the vertex shader turns the fans
  (`uFanA`), sways the leaves and moves the leaflets along their drift (`uTime`); leaves, blossoms and
  leaflets are cut to shape in the fragment shader. Lit by the same analytic sun (`lightMask0`), so the
  screen's holes shade the plants. Drawn first (`renderOrder −1`) so the shell behind a leaf is depth-rejected.
- `glsl/apertures.js`: a `fans` occluder pattern and a `thin` flag (one face test instead of two);
  `FANS_GLSL` in `props.js` is the blades' SDF from the same `FAN` numbers as the meshes.
- `sets/corridor.js`: `gach` turns the house on (yellow, dado, floor, surrounds, props, fans aperture on Tier
  2); the three door frames become **one draw** (`paint.js` `vertexPaint`), which pays for the props' draw;
  `animate(t)` sets two uniforms; `setSun` keeps the dado's sun tint on the corridor clock. Without `gach`
  (the still renderer today) the corridor is the current one.
- `live.js`: three lines: the props' clock (`time + scrollY/300` on Tier 2, `scrollY/300` on Tier 1,
  frozen when paused) and the tamarind sway on the same clock.

### 1.4 Cost against 05-DESIGN 6.7

| Budget (Tier 1) | Before | After | Limit |
|---|---|---|---|
| Draw calls, worst corridor frame (bay 1, door open) | 14 | **13** (+1 props, −2 merged frames) | ≤ 14 |
| Triangles | 560 | 4,402 | ≤ 8k |
| GPU memory added | | floor textures 0.5 MB (Tier 1) / 2.1 MB (Tier 2) incl. mips, props geometry ~0.3 MB | ≤ 24 MB / 48 MB |
| JS per frame | | two uniform writes, no allocations | ≤ 2 ms |
| Lazy JS (gzip) | corridor chunk 6.7 KB | 11.0 KB; whole world +7.4 KB (corridor, shared surface code) | loads after `load` + idle |
| CSS (gzip, render-blocking) | 27.1 KB | 28.1 KB (+1.0) | |
| HTML (gzip, home) | 12.75 KB | 12.82 KB (inline tile SVGs replace the old block-light SVGs) | |
| One-off bake | | 92 ms (256 px) / 211 ms (512 px) in Node on this machine, sliced; expect ~0.3–0.4 s of idle slices on a mid-range phone | not on the LCP path |

**GPU time.** No real phone here, so the proxy is SwiftShader (CPU WebGL, so its time follows the fragment
work), the same corridor frame redrawn 30 times, minimum taken. The machine was shared with other work (load
4–7), so the spread is wide:

| Frame, Tier 1 | Before | After |
|---|---|---|
| 390 × 844, bay 1 | 92–104 ms | 122–124 ms |
| 390 × 844, mid | 98–123 ms | 121–127 ms |
| 1440 × 900, bay 1 | 273–349 ms | 403–467 ms |

About **+20–30% fragment work on Tier 1**. The first build, with fan shadows on every tier, was +80% at
1440: the blade test runs in every sun lookup, 16 times per shaft pixel. That is why fan shadows are Tier 2
only. On the phone the fans turn, cast nothing, and the shafts never test the blades. Per-feature split at
390 (flag runs, `?house=-fans,-props,-aniso`): fan shadows were most of the added cost; the props draw about
10%; floor, dado and surrounds about 10%.

What this means for the A15 gate (≤ 12 ms at 390 × 844): if today's corridor is under about 9 ms on the
device, it stays inside. **It must be measured on the phone** with the existing timer-query gate (Tier 1 >
14 ms → lite). Cheaper steps if it is close, in order: floor anisotropy 1, leaflets off on Tier 1, surrounds
dropped (the frame paint already carries the unit), props shaded without the sun lookup on Tier 1.

## 2. "Lĩnh vực hoạt động": the tile floor

### 2.1 Layout

- **Desktop (≥ 1024):** H2, then a floor of four 20 cm cement tiles laid edge to edge across the container
  (each about 320 px square at 1440): Giáo dục, Bảo vệ trẻ em, Gia đình và cộng đồng, Vận động chính sách.
  Under each tile, on the plaster, its name (H3), its line and the arrow. Text is never on a pattern.
- **Tablet (600–1023):** two tiles a row, words under each (not rendered in the proofs).
- **Phone (< 600):** the runner turns to run down the page: each tile 128 px on the left, its words beside it.
  Large enough to read as a real tile at arm's length; four areas take about 760 px.
- The whole column (tile and words) is the link, as the old tile was; focus ring round the column; 44 px
  targets kept. No new strings: the names and lines are the existing `work.focusAreas` (VI and EN).
- Departure from PLAN H5a: the 2 × 2 Education tile of the bento is gone; the four areas are equal tiles.
  A floor of equal tiles is the point of the image; Education still leads the row.

### 2.2 Colour and depth

- Each tile is a pigment medallion inlaid with its block's holes in cream, 2 × 2 blocks, plus a cream inner
  border and accent hearts: Giáo dục ochre with indigo (Hoa thị rosettes), Bảo vệ trẻ em indigo with ochre
  (Mắt cáo net), Gia đình jade with ochre (Vòng tròn, the full ring in the middle), Vận động brick with
  indigo (Bậc thang steps, drawn bolder).
- **No line between areas (D0.5):** no ground or grout is drawn round the medallions, so the joints between
  tiles are the page's own plaster (about 22 px at 1440). The section background stays the day gradient's
  lime-100.
- Tactile: one shared `feTurbulence` filter (rasterised once, never animated) wears the pigment with cream
  speckle at 32%; the medallion corners are slightly rounded like a pressed tile.
- Text: ink-900 / ink-700 on lime-100, 13.02 / 7.41, the same pairs as every sheet.

### 2.3 Motion

- **The sun crosses the floor.** A layer of Hoa thị light (sun-200, `mix-blend-mode: screen` so a patch on
  indigo turns a lighter, warmer blue rather than a grey film) inside a soft warm pool slides from left to
  right as the floor passes through the viewport: CSS scroll-driven (`animation-timeline: view()`),
  transform only, native scroll. On phones it slides down the tile column. (05-DESIGN 4 rules out blend
  modes for the grain; this is a light layer, where `screen` is how light adds.)
- Hover: the tile warms (a sun-100 pool fades in, 280 ms), the name underlines, the arrow moves 3 px. The
  existing `stagger-cascade` reveal is kept.
- Reduced motion, `data-motion="off"`, browsers without scroll timelines and no-JS: the sun rests in the middle
  of the floor; everything else is identical (`focus-390-reduced-motion.jpg`, `focus-1440-no-js.jpg`).

### 2.4 Built

`partials/blocks.mjs` `gachTile(kind)` (inline SVG from `holes(kind)`, colours by class, so no hex leaves
`tokens.css`); `pages/home/work-sheet.mjs` (the `floor` list replaces the `bento`); `styles/pages.css` (the
`.floor` / `.gach` block). No JS, no images: the floor costs about 1 KB of CSS and 0.1 KB of HTML (gzip).

## 3. Safeguarding

- **Little Rose's bay is unchanged in kind.** Its door stays shut (the existing `setDoor` guard), its transom
  is the house's Hoa thị with plain untinted daylight, and it still takes no corridor sun (`bayFree`). It gets
  the architecture every bay shares (floor, dado, wash) and **nothing else**: no fan, pot, plant, blossom,
  leaflet or door surround, whatever the DOM says. `buildProps` filters on the unit; a check
  (`lr-props-check.mjs` in the scratch folder) builds the props for three bay orders and asserts no vertex,
  no leaflet's whole drift and no fan's shadow disc reaches the Little Rose bay: PASS in all three. It should
  join `test/little-rose.test.mjs` on adoption.
- No pink or red light anywhere near it: the brick pigment is floor paint and terracotta pots, and there are
  no pots in that bay. The light in the bay stays plain daylight.
- No people, no photos, no invented names or numbers. No new copy at all.
- **Traces of children's lives are not built.** 05-DESIGN 6.1 rule 5 bans shoes ("No child, figure,
  silhouette, shoe or name"), so shoes by a door are out even behind the flag. What could come later, behind
  `traces.enabled` + `approved` and the PROD guard, as 6.8 already lists: a chalk *lò cò* on the plain tiles
  of the Tre Xanh bay (the plain band suits chalk), pencil height marks on a pier (no names, no dates). Never
  in or near Little Rose's bay.
- The floor and dado continue through the corridor and must not make it read as one real building: no
  numbers, no signs, no window. The yellow and green are generic Saigon paint, not any HCWA centre's real
  colours (if a real centre has these colours, HCWA should say whether that makes it recognisable).

## 4. Risks

1. **Phone GPU time** is a proxy, not a measurement: about +20–30% fragment work on Tier 1. Must pass the
   A15 timer-query gate; cheaper steps are listed in 1.4.
2. **Stills and fallbacks lag.** Reduced motion, Tier 0, no-JS and the stacked corridor show the `bay-*`
   stills, which are still the white corridor (the still renderer calls `corridorScene` without `gach`). On
   adoption: bake the tiles in `poster.html` / `still.js` (headless Chrome already runs the world code), pass
   `gach`, re-render `bay-*`, `hub-door-*` and `gallery-wall` within their budgets, then re-run
   `light-contrast.mjs`. Until then a reduced-motion visitor sees the old corridor and the new tile floor.
3. **Pattern versus the block's light.** A busy floor could bury the bông gió patches, the site's signature.
   The runner keeps the sunlit band by the door wall plain for that reason. Watch it in review; if it still
   competes, the field tile can drop its ochre and keep indigo on cream.
4. **D0.5 reading of the tiles.** The tiles have real edges (rounded medallions on plaster), as the doors do.
   No line is drawn between areas, but a strict reading could ask for the medallions' outer edges to feather.
   A one-line mask variant exists if Maxwell wants it.
5. **Hoa sứ.** Frangipani is everywhere in Saigon, but some families link it with pagodas and graves (*cây
   đại*). HCWA should say whether it is welcome; the fallback is more tamarind leaflets, already in the scene.
6. **Fan shadows read only on Tier 2 and only faintly** (the sun at 24–28° rakes under the beam above the
   screen, so only the blades near the screen are lit). On phones the fans turn but cast nothing.
7. **Busy-ness at 390.** Floor, dado, yellow, fan and plants in a 55% frame is a lot. The phone frames still
   read, but the taste review should look at the whole walk, not two frames.
8. **Tests to update on adoption:** `work sheet: gapless wall of four areas` (asserts the old bento markup)
   and `tokens.css carries the 05-DESIGN D1 block verbatim` (05-DESIGN D1 needs the `tile-*` tokens) fail
   in the scratch copy as expected; the other 89 pass, Little Rose's included. `check-tiles.cjs` targets the
   bento and needs pointing at `.gach`.
9. **Bake time on a slow phone:** about 0.3–0.4 s of sliced idle work when the corridor builds. If it shows in
   INP traces, an `OffscreenCanvas` 2D bake is the cheaper route.
