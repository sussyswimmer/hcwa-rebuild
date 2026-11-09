# Proposal: colour and light (kính màu)

Status: proposal for Maxwell, 9 Nov 2026. Nothing in `prototype/` is changed. The working copy, with every
change described here, is in the session scratchpad
(`/tmp/claude-0/-home-user-hcwa-rebuild/8d9e250b-651c-5832-842a-35473e3cdd1b/scratchpad/proposal-colour-light/`);
the proof images are in `docs/design/proposals-2/colour-light/`.

Maxwell's feedback on the live site, 9 Nov 2026: "i like it but the Linh vuc hoat dong section is so bland and
all white and the hallway section is so boring its jst a plain white wall".

## In one paragraph

Old Saigon houses colour their light with glass. Transoms (ô thoáng), stair windows and the band above a
breeze-block wall are glazed in small panes of amber, blue, green and teal set in thin steel, and on a sunny
morning those panes throw pools of colour across the plaster and the đá mài floor. This proposal brings that
glass into the corridor and into "Lĩnh vực hoạt động". In the corridor, a band of coloured panes above the
bông gió throws a frieze of colour along the door wall that slides down as the sun clock moves with the scroll.
A coloured window at the far end lays a soft carpet of colour on the floor, and the open units' transoms are
glazed in their own colours. The walls become an ochre lime wash over a teal oil-paint dado, with a terrazzo
floor in coloured chips. The four focus areas become four panes of the same glass, deep and lit from behind,
each casting its colour onto the plaster where its words are. Little Rose gets none of it: its door stays
shut, its transom stays plain, and the glass over its bay is frosted.

## Proof

All frames are from the live page (`?tier=1`, Playwright with SwiftShader WebGL) or, where marked, from the
reduced-motion page (no three.js loaded). They are JPEGs under 400 KB each.

| File | What it shows |
|---|---|
| `colour-light/before-after-corridor-1440.jpg` | The live corridor at its stage start next to the proposal at the same point |
| `colour-light/before-after-corridor-1440-bay1.jpg` | Bay 1 (Tre Xanh), now and proposed |
| `colour-light/corridor-1440-bay1-door-opening.jpg` | 1440 × 900, stage 0.17: the Tre Xanh door swinging open, the frieze across the near wall |
| `colour-light/corridor-1440-mid-little-rose.jpg` | 1440 × 900, stage 0.50 (mid-corridor): Little Rose's bay. No colour, frosted panes above it, the door shut |
| `colour-light/corridor-1440-approach.jpg` | 1440 × 900, stage 0.04: the frieze crossing the closed green door, the end window |
| `colour-light/corridor-1440-end-window.jpg` | 1440 × 900, stage 0.78: Cầu Hàn, and the end window's carpet of colour on the floor |
| `colour-light/corridor-1440-tier2-coloured-shafts.jpg` | Tier 2 only: coloured shafts under the ceiling through the clerestory |
| `colour-light/corridor-390-bay1-door-opening.jpg` | 390 × 844, stage 0.17 |
| `colour-light/corridor-390-mid-little-rose.jpg` | 390 × 844, stage 0.50 |
| `colour-light/corridor-390-end-window.jpg` | 390 × 844, stage 0.78 |
| `colour-light/corridor-1440-reduced-motion-still.jpg`, `corridor-390-reduced-motion-still.jpg` | Reduced motion (and Tier 0, no JS): the re-rendered bay still behind its card |
| `colour-light/before-after-focus-1440.jpg`, `before-after-focus-390.jpg` | "Lĩnh vực hoạt động", now and proposed |
| `colour-light/focus-1440.jpg`, `focus-1440-scrolled.jpg` | 1440: two scroll positions 340 px apart. The sun's hot spot has moved across each pane |
| `colour-light/focus-390-a.jpg`, `focus-390-b.jpg` | 390: the four panes as wide bands, words under each |
| `colour-light/focus-1440-reduced-motion.jpg` | Reduced motion: the same section at rest |

## Why this belongs to HCWA and to Saigon

- **Kính màu is the Saigon house's other light.** Breeze blocks shape the sun. Coloured glass colours it.
  Both sit side by side in the stairwells, transoms and corridors of 1950s to 1970s houses, shophouses and
  chung cư. A wall of bông gió with a band of coloured glass above it is a real Saigon detail, not an
  invented one. It extends the direction we already have ("the light is always shaped by the block").
- **The palette is HCWA's own.** HCWA's yellow is the sun, so amber glass sits in the middle block of every
  glazed transom and in the heart of the end window. Logo blue is the dark frame of every pane and one of the
  glass colours. The unit colours become glass in their own transoms: green for Tre Xanh, blue for Cầu Hàn.
- **The walls are a Saigon school or ward building.** Ochre lime wash (vôi vàng) with a teal oil-paint dado
  at parapet height (90 cm), so children's hands mark paint and not lime. The floor is đá mài with coloured
  chips and a darker border along the walls, laid the way Saigon floors are.
- **Frosted glass (kính mờ) is the house's own privacy glass.** Over Little Rose's bay, the clerestory is
  frosted and untinted. This is the same idea as its closed door and its privacy note, expressed in the
  building's own material.
- **No pink, no red, anywhere.** The glass palette is amber, teal, logo blue, green and sky. A test checks
  that no glass token leans red.

## The corridor

### What changes

| Element | Now | Proposed | Built in (scratch copy) |
|---|---|---|---|
| Door wall, piers, end walls | Cool grey lime wash (`world-wall-shade` #C7CBD2) | Ochre lime wash with metre-wide *loang* patches, over a teal oil-paint dado to 90 cm (matching the parapet height on the screen side), with a faint sheen of sky on the paint | `materials/still-surface.js` (`glass` option: dado, mottle, sheen); loang baked into the lime texture's unused alpha channel (`textures.js`), read in the same fetch |
| Floor | Grey terrazzo | Đá mài in colour: white marble, black, amber, teal, green and a little logo blue in a warm grey cement, crushed-stone chips 4 to 14 mm, and a 24 cm darker border along both long walls | `textures.js` `bakeTerrazzoColour()` (one more 512² texture), border in the surface shader |
| Ceiling | Grey #D5D9DF | Warm and lower in key (`world-ceiling-warm`), deeper corner shade (AO strength 1.7) | corridor surface targets |
| Screen (left) | 7 rows of Hoa thị to 3.0 m | 5 rows of Hoa thị (0.9 to 2.4 m), then a **clerestory**: two rows of 30 cm coloured panes (2.44 to 3.04 m), one pane per block column, in dark steel | `CORRIDOR.rows` 5 (`journey.js`); `objects/glass.js` (the glass you see); `glsl/glass.js` `clereLight()` (the light it throws) |
| Light on the door wall | Hoa thị patches only | Hoa thị patches below and a **frieze of coloured rectangles** above, from 1.36 to 1.96 m at the stage start. It slides 21 cm down the wall and 67 cm along it, about two panes, as the sun clock moves from t 0.25 to 0.45 with the scroll. It crosses open leaves and closed doors too | `clereLight()` in the surface and paint shaders: one ray-plane test per pixel against the clerestory, pane id, muntin, penumbra growing with distance (the same sun size as the screen) |
| End wall | Plain | A **7 × 11 coloured window** (1.5 × 2.2 m), glazed the way Saigon stair windows are: a teal field, a logo-blue border, an amber diamond with a sky heart, green where the diamond's points meet the border. It glows at the vanishing point of every frame, and its daylight lays a soft carpet of its pattern on the floor of the last bay | same glass mesh; `endLight()` (one ray-plane test, soft penumbra 1 cm + 3 cm per metre, falloff 1/(1 + 0.12 t²), cut off at 7 m) |
| Transoms | Emblem light, untinted | Tre Xanh's Đốt tre glazed green with an amber middle block. Cầu Hàn's Vòm cầu glazed logo blue with an amber middle block. Their floor pools take those colours. **Little Rose's stays plain, untinted daylight.** | `glsl/doors.js` (`gDoorTint`, per-door glass); `objects/lattice.js` (`uTintSide`, `uTintMid`) |
| Air | Shafts through the screen | Tier 2 only: the shafts also gather the sun through the clerestory in each pane's colour | `materials/shafts.js` (`GLASS_SHAFTS` define, off on Tier 1) |

### Colours (proposed tokens, all in `tokens.css` after the D1 block; no hex anywhere else)

| Token | Hex | Use | Contrast notes |
|---|---|---|---|
| `--color-glass-amber` | #F2A31B | HCWA's sun through glass | Never text. 8.22:1 against the blue-950 frame |
| `--color-glass-teal` | #16A39A | xanh ngọc | Never text. 5.52:1 against blue-950 |
| `--color-glass-blue` | #3E5BD6 | logo blue, lit from behind | Never text. 3.01:1 against blue-950, which is decorative only |
| `--color-glass-green` | #5AAE3C | xanh lá mạ (lighter and yellower than Tre Xanh's door green, so it never reads as the unit) | Never text. 6.19:1 against blue-950 |
| `--color-glass-sky` | #5FB6E4 | xanh da trời | Never text. 7.62:1 against blue-950 |
| `--color-world-ochre-sun` | #F3C77C | lime wash in ochre, in the sun | Shader target. ink-900 on it is 10.10, but no text sits on it |
| `--color-world-ochre-shade` | #B58B57 | the same wall in shade | ink-900 on it is 5.17. **Rule: text never sits on the bare wall** (it never did; the framing solver and the panes keep it so) |
| `--color-world-dado-sun` | #3F958B | teal oil-paint dado in the sun | ink-900 on it is 4.48. Never behind text |
| `--color-world-dado-shade` | #1F5D58 | the dado in shade | ink-900 on it is 2.10. **Measured failure that is a rule**: never behind text |
| `--color-world-ceiling-warm` | #B9A585 | corridor ceiling | ink-900 on it is 6.68. Never behind text |
| `--color-world-terrazzo` | #9E9282 | đá mài cement in shade | ink-900 on it is 5.24. Never behind text |

Light on surfaces is a display target, as in the rest of the world (`toneMapped: false`). A coloured pool is
the glass colour carried at the brightness of the lit surface, with 22% of the surface's own colour and a 10%
leak of white light. A blue pool on ochre plaster stays blue, as the eye sees it, instead of turning into the
muddy navy that a straight multiply gives (`pool()` in `glsl/glass.js`).

**Text over the corridor, measured** (text hidden, darkest 2% of real pixels behind each line, as
`scripts/light-contrast.mjs` does). Live corridor at 1440 × 900 and 390 × 844, stage 0.04, 0.17, 0.22, 0.50
and 0.78: 0 lines below the gate. The worst line is 7.66:1 (the ink-700 door index at the stage start), where
the live page today measures 7.76:1. The plaster pane behind the text does the work, so the darker world costs
almost nothing. On the reduced-motion and Tier 0 page, `light-contrast.mjs` (home, VI and EN) first found 7
lines below 7:1: the ink-700 meta lines of the stacked cards (Từ năm, phường, the privacy note) over the new
ochre and frieze, at 6.2 to 6.7:1. Those three lines move to ink-900 (`stages.css`); size and weight still rank
them. Rerun after the change: 82 lines, 0 below the bar.

### Motion

Everything moves with the scroll, never on a timer. The frieze slides because the corridor's existing sun
clock (t 0.25 to 0.45 over the stage) moves the sun. The transom pools appear as each door swings open, which
is the existing door timeline. The end window's carpet comes into view as the camera walks toward it. No new
idle motion. On Tier 2, the existing tamarind sway still moves the Hoa thị patches. The clerestory light skips
the tamarind dapple on purpose, because it would cost a second canopy lookup per pixel. Pause, reduced
motion and Tier 0 show the stills. `bay-*` and `passage-corridor` were re-rendered from the same scene code,
so the fallback carries the same colour, within their existing size budgets. AVIF files are 1600 w desktop
58.8, 59.5 and 60.5 KB and 800 w mobile 34 to 35 KB. `encode-posters.py` passed every file.

### Little Rose (safeguarding, enforced in code)

- Its door stays shut and its transom stays the plain, motif-free opening (pattern 0). The shader never tints
  pattern 0, whatever the per-door glass uniform says, and `transomGlass("little-rose")` returns `null`.
- The clerestory panes over its bay, plus 0.6 m either side, are frosted and untinted (palette id 0). The
  existing `bayFree()` also removes all corridor sun and all glass light from its bay.
- The end window's light stops at 7 m, which is 1.5 m short of its bay. A test checks this distance.
- No traces anywhere (none are added by this proposal). No location, no number, no sign.
- A new test, `test/little-rose.test.mjs` "kính màu: …", checks all of the above. It also checks that no glass
  token leans red or pink. `npm test`: 92 pass, 0 fail.
- One more change keeps the draw budget: Little Rose's leaf never moves, so it is merged into its frame. That
  saves one draw call.

### Cost against 05-DESIGN 6.7

| Budget (Tier 1) | Live today | Proposal | Note |
|---|---|---|---|
| Draw calls | 14 at bay 1, 10 at Little Rose | **14 at bay 1, 10 at Little Rose** | The glass (clerestory + end window) is one merged mesh (+1). Merging Little Rose's static leaf into its frame gives that draw back (−1) |
| Triangles | 560 | 584 | ≤ 8k |
| GPU memory | — | +1.3 MB | One 512² RGBA texture with mips (coloured đá mài). The lime texture's alpha now holds the loang, which costs nothing extra |
| Texture fetches per wall pixel | 1 | 1 | The second mottle fetch was folded into the alpha channel |
| Fragment work | — | +1 ray-plane test against the clerestory per lit pixel, +1 against the end window, early rejects on height | Shafts on Tier 1 are unchanged (the glass define is off) |
| GPU frame, proxy | 156 to 178 ms per synchronous redraw | 216 to 244 ms | **SwiftShader on a loaded CPU, 390 × 844, Tier 1, three interleaved runs at stage 0.17 and 0.50.** About +35%. This is a CPU rasterizer, not a Mali, and the per-part toggles were too noisy to blame one term. It has to be measured on the G1 phone (05-DESIGN 13, question 3) |
| JS per frame | — | 0 new per-frame work | `setSun` copies one more colour when the clock moves, as it already does for the walls |
| Bake time | — | +38 ms (Node, desktop CPU), sliced with `scheduler.yield()` every 128 rows and 2,500 chips | Built in idle time with the corridor, after the hero is live |
| Payload (gzip -9) | corridor 6.66 KB, common 4.62, three + world 148.3, CSS 27.14, home HTML 12.76 | corridor 8.91, common 5.48, three + world 150.9, CSS 27.57, home HTML 14.97 | World JS +5.7 KB. HTML +2.2 KB is the inline SVG of the four panes |
| Mobile LCP (`scripts/lcp.cjs`, median of 3) | 2176 ms default, 2148 ms world on | 2172 ms default, 2176 ms world on | Same within noise. The 3D still loads after the text |

**If the G1 phone misses 12 ms:** (1) Tier 1 compiles the surface without the end window and the transom
tint, keeping the clerestory frieze, the dado and the floor. The scratch copy has these switches as
`stillSurface(..., { parts })`. (2) Next, the end window's carpet is baked into a 256² projective light
texture instead of being computed. (3) Last, mobile shows the corridor as stills, which is the existing G1
fallback; they now carry all the colour. A prototype-only `?gl=` probe flag in `sets/corridor.js` builds the
glass with or without each part, for measuring on the phone. It must not ship.

## Lĩnh vực hoạt động (the focus areas)

### What it becomes

Four panes of kính màu, each the area's block pattern glazed in its own glass:

| Area | Block | Glass | The pane |
|---|---|---|---|
| Giáo dục (education) | Hoa thị | amber | the big window: 3 × 3 blocks, golden quatrefoils glowing out of a deep amber field, rosette pips in teal and sky |
| Bảo vệ trẻ em (child protection) | Mắt cáo | logo blue | a wide band of 3 blocks: sky and blue diamonds on a deep logo-blue field, an amber heart in every block |
| Hỗ trợ gia đình và cộng đồng | Vòng tròn | teal | rings closing into circles where four blocks meet, teal with amber and green, on deep teal |
| Vận động chính sách | Bậc thang | green | green steps climbing to sky and an amber top step, on deep green |

- **Depth.** The holes are bright glass. The field round them is the same colour, deep (the token mixed
  toward gold-700, blue-700 or blue-950), and darkens further toward the frame, so each pane glows from a hot
  spot. The cames are dark steel (blue-950) along every hole and joint. The pane sits in a 14 px reveal of
  blue-950 that feathers to nothing, so it reads as set into the wall without drawing a line round it
  (05-DESIGN 0.5).
- **The cast.** Below each pane, on the plaster above its heading, falls the colour it throws: a soft pool and
  the block's own holes, sheared away from the sun, gone before the heading's cap height. It never sits under
  running text. Under the words, a 12% wash of the same colour is feathered on every side, so there is no panel
  edge.
- **Layout.** 1440 keeps the 8-cell wall, but every cell is now full. Giáo dục's window fills its two rows, and
  its heading sits on the pool below it. Bảo vệ is a wide band over its words. Hỗ trợ and Vận động are
  transom-like panes over theirs. Tablet keeps 2 columns. At 390, one column: each pane is a wide band (12:5)
  whose blocks crop and never shrink, with the pool and the words under it. 320 has no horizontal scroll (VI,
  EN, no JS).
- **Motion, all scroll-linked, CSS only** (`animation-timeline: view()`, transform and opacity only): the
  sun's hot spot crosses each pane while the pane crosses the screen (added as light with `plus-lighter`).
  Each pane is brightest mid-screen (opacity 0.8 to 1 to 0.8). The pool slides 28 px against the hot spot.
  Hover strengthens the pool and underlines the name. Reduced motion, pause, Firefox (no `view()`) and no JS
  show the panes at rest, complete. No JS and no WebGL are involved.
- **Text and contrast.** The words stay HTML. Running text moves from ink-700 to ink-900, because ink-700
  would fall to 6.3 to 6.9:1 on the colour washes. Measured on the live page at 1440 and 390: headings
  11.0 to 11.8:1, running text ≥ 11.0:1, 0 lines below the gate. Token pairs on the wash (12% over lime-100):
  ink-900 12.18 (amber), 11.64 (teal), 11.13 (blue), 11.86 (green); the blue-700 arrow 7.59 to 8.31.
- **Built in:** `partials/blocks.mjs` `glassArt()` (from the same SDF numbers as the shader, as `lightArt()`
  was); `pages/home/work-sheet.mjs` (markup: niche, glass, sheen, cast, body); `styles/pages.css` (the tile
  rules). There are no new strings. Every new element is decorative and `aria-hidden`, so VI and EN are
  unchanged and no Vietnamese is uppercased.

## Risks

1. **GPU cost on a real phone.** The SwiftShader proxy says about +35% fragment cost in the corridor. The live
   corridor has never been measured on a Galaxy A15-class phone. The mitigation ladder is above; the stills
   fallback already exists.
2. **Taste: "a bit much"?** Five glass colours in one corridor, plus the ochre and teal walls, is a big step
   from the white wall Maxwell rejected. If it reads as busy on his phone, the first cut is the end window's
   carpet (keep the window), then the clerestory's second row of panes.
3. **The end window could read as pixel art at a distance.** Its panes are about 15 to 20 px wide at 1440. A
   stronger design (a Hoa thị leaded into the middle) is possible if it does.
4. **Continuity rule (0.5).** The panes are crisp-edged objects (windows) inside feathered reveals, and the
   dado has a painted edge in the 3D world. Neither is a boundary between sections, but the review rule should
   look at them.
5. **Little Rose sits between two coloured bays.** Its own bay has no colour, and the frosted panes over it
   make that deliberate. HCWA's safeguarding lead should still see `corridor-1440-mid-little-rose.jpg` and
   confirm that a plain bay between coloured ones does not single the shelter out. If it does, the frosted run
   can extend over the whole corridor's middle, or the clerestory can be clear glass everywhere except over the
   two open units.
6. **Glass colour and unit colour.** Tre Xanh's transom is green and Cầu Hàn's is blue, so a visitor may link
   glass colour to a unit. The focus panes avoid unit colours on purpose: glass green is lighter and yellower
   than the door green.
7. **`plus-lighter` blending.** It is used only for the pane's hot spot. A browser without it shows plain alpha,
   which is a slightly flatter glint.
8. **Stills re-rendered.** `bay-*` and `passage-corridor` must be regenerated with the change. Our Work's
   `door-*`, `hub-*` and `gallery-wall` stills use other scenes and are unchanged. Should Our Work's wall get
   the same glass, for one world?

## Files changed in the scratch copy (for porting)

`src/styles/tokens.css` (new tokens), `src/world/tokens.js` (generated), `src/world/glsl/glass.js` (new),
`src/world/objects/glass.js` (new), `src/world/glsl/doors.js`, `src/world/objects/lattice.js`,
`src/world/materials/still-surface.js`, `src/world/materials/paint.js`, `src/world/materials/shafts.js`,
`src/world/sets/corridor.js`, `src/world/sets/stills.js`, `src/world/journey.js` (`rows: 5`),
`src/world/textures.js`, `src/partials/blocks.mjs`, `src/pages/home/work-sheet.mjs`, `src/styles/pages.css`,
`src/styles/stages.css` (meta lines ink-900), `test/little-rose.test.mjs` (new test), `public/posters/bay-*`
and `passage-corridor-*` (re-rendered). `scripts/light-contrast-home.mjs` is a measuring helper and should
not be ported.

Checks run on the scratch copy: `npm test` (92 pass), `npm run build`, `PROD=1 npm run build`, which still
fails on the existing guards for unapproved Little Rose and traces content, as designed. Also
`npm run check:sun`, `scripts/light-contrast.mjs` on home (0 below the bar), the live contrast probe (0 below
the gate), `scripts/lcp.cjs` (median of 3) and the 320 px overflow check (none).
