# Prototype build plan: Bông gió (PLAN.md)

The plan the build team works from to turn the Phase 1 prototype into the experience Maxwell
asked for on 6 Nov 2026. Design system and every number behind it: `docs/plan/05-DESIGN.md`
(read it first; section numbers below like "D6.5" point into it). Long-form rationale:
`docs/design/concepts/open.md`. Hard rules: `CLAUDE.md`.

Scope: the home page (the showcase), Our Work hub, one center page (Green Bamboo), one
program page (Thrive Together), Get Involved, Donate (placeholder payment panel), Contact,
404 and the components page, each in VI (`/`) and EN (`/en/`). Hosted on GitHub Pages
(`npm run build:pages`, base `/hcwa-rebuild/`). Ported later to `wp/themes/hcwa-2026`, so
every section is HTML-first: content in markup, motion and 3D as a progressive layer.

Not in scope: component code in this file. It says what to build, in what order, and how we
know it is done.

## 0. Definition of done

A section is done when all of these pass. Every section below lists only its extra checks.

| # | Check | How |
|---|---|---|
| A1 | Looks right at 390 × 844 and 1440 × 900, VI and EN; no horizontal scroll at 320 | `python3 skills/tastegate/scripts/gate.py <url> --out qa/tastegate/<date>/<page>` exits 0; screenshots looked at by a person |
| A2 | Complete and readable with `prefers-reduced-motion: reduce`; three.js is never requested | Chrome emulation + network log |
| A3 | Complete without WebGL: stills instead of the live world | `?tier=0` and Chrome `--disable-webgl` |
| A4 | Complete without JS: all text, links and stills present, stages stacked | JS disabled |
| A5 | Mobile LCP ≤ 2.5 s (home target 1.8 s), CLS < 0.1, INP < 200 ms; the 3D loads after the text | Lighthouse mobile (4G, mid-tier), 3 runs, median; `frontend-lighthouse` budgets in CI |
| A6 | Contrast: tokens pass; any text over the world passes the render gate | `contraste.mjs --pares qa/contrast-pairs.json`; `scripts/light-contrast.mjs` |
| A7 | Every visible string from `content/draft/*.json`, both languages; drafts carry `data-draft` + dashed outline | grep templates for literal text; walk the EN and VI pages |
| A8 | `PROD=1 npm run build` still fails on drafts, unverified figures and donation placeholders, and on the new guards (section 6) | `npm test` |
| A9 | 0 console errors; axe 0 serious/critical; keyboard path works; focus never on a hidden element | `webapp-testing` (Playwright) |
| A10 | Nothing animates the H1, figures, amounts or form values; Vietnamese never uppercased | `review-animations`; static check |

Prototype-only query flags (ignored when `PROD=1`): `?tier=0|1|2`, `?motion=off`,
`?debug=frame` (framing solver overlay), `?traces=0`, `?t=0..1` (sun clock, poster entry only).

## 1. Module structure

The existing render step stays: `src/render.mjs` writes static HTML from `src/pages/*.mjs`,
`src/partials/*.mjs` and `content/*.json` into `.site/`, then Vite bundles it. New code is
added beside it.

```
prototype/
  src/
    render.mjs, routes.mjs              (exist) add head-inline injection and new PROD guards
    lib/
      content.mjs, ctx.mjs, html.mjs    (exist)
      vikeep.mjs                        U+00A0 joins for heading compounds (D2)
      split-words.mjs                   build-time word spans + hidden sentence for sunlit reading
      posters.mjs                       <picture> markup for base + light layer, per aspect bucket
    pages/                              (exist) home, work, involve, components, index; add contact, not-found if split
    partials/
      layout.mjs, components.mjs        (exist) header, footer, buttons, cards: restyled
      world.mjs                         hero stage, passage, corridor stage, shutters stage markup
      lit-header.mjs                    inner-page lit header (base + light layer + text)
    head-inline.js                      ≤ 1.5 KB, inlined into <head>: tier, data-motion, sun-pending, font class
    main.js                             (exists) entry: styles, UI, then schedules motion and world
    styles/
      tokens.css                        D1 block verbatim (+ type, space, motion tokens)
      base.css                          reset, type, links, focus, grain
      layout.css                        container, grid, sections (sun / shade), sheet edge
      components.css                    buttons, cards, rows, chips, forms, footer, header
      light.css                         sun-in keyframes, CSS sun layer, lam stripes, louver mask
      motion.css                        reveals, view transitions, data-motion / reduced-motion overrides
      world.css                         fixed canvas, posters, stages, stacked fallbacks
      draft.css                         dashed outlines, reviewer toggle (not shipped in PROD)
    ui/
      header.js  menu-sheet.js  footer.js  en-bar.js  copy.js  forms.js   (split out of main.js)
      pause-toggle.js                   WCAG 2.2.2 toggle, localStorage hcwa:motion
      draft-toggle.js                   staging-only reviewer toggle
    motion/
      tokens.js                         GENERATED from tokens.css (easings, durations)
      scroll.js                         Lenis (wheel only) + ScrollTrigger + gsap.matchMedia contexts
      reveals.js                        reveal-rise, stagger-cascade
      sunlit-reading.js                 one scrubbed custom property on the statement
      stages.js                         sticky stages: progress, beats, cards, index tabs, focusin, ResizeObserver stacking
      velocity.js                       scroll velocity → damped spring (motes, shafts shimmer)
      transitions.js                    View Transition types (back, lang) in pagereveal
      sun-in.js                         starts the CSS sun-in after the light layer decodes
    world/                              the three.js layer, dynamic import only
      index.js                          entry, no three.js: WebGL2 + GPU probe; Tier 0 keeps the stills, else imports live.js
      live.js                           three.js side: build sets lazily, loop, visibility, context loss, handoff
      poster-frames.js                  GENERATED by render-posters.mjs: each still's framing shift (live rest frame reuses it)
      tiers.js                          tier decision, live gates, sessionStorage hcwa:tier
      renderer.js                       WebGLRenderer, DPR caps, resize, compileAsync, render-on-demand, wash pass
      frame.js                          framing solver (setViewOffset to .hero__stage, text-safe rects)
      journey.js                        camera path K0..K4, dwell(), beats read from DOM [data-beat]
      clock.js                          sun keyframes per set, sun direction and color
      tokens.js                         GENERATED world colors from tokens.css
      glsl/aperture.js                  holeSDF, apert(), sunMask() (from open/proof-hero.html)
      glsl/blocks.js                    SDFs: hoaThi, matCao, vongTron, bacThang, dotTre, vomCau
      glsl/noise.js
      textures.js                       bakes lime-wash, terrazzo and traces textures
      sets/room.js passage.js corridor.js shutters.js
      objects/block.js heroBlock.js screen.js door.js shutters.js sky.js traces.js
      materials/surface.js paint.js shafts.js motes.js wash.js
      poster.html                       poster entry (no HTML text), used by render-posters.mjs
  scripts/
    tokens-to-js.mjs                    tokens.css → motion/tokens.js, world/tokens.js (run by predev/prebuild)
    blocks-svg.mjs                      block SDF numbers → SVG icons, sheet-edge mask, footer edge
    render-posters.mjs                  Chrome Headless Shell (SwiftShader) over DevTools protocol → PNG
    encode-posters.py                   Pillow → AVIF / WebP / JPEG, fails over budget
    light-contrast.mjs                  text-over-world contrast gate (D1)
    check-sun-crisp.mjs                 incidence and penumbra per set (D6.4); npm run check:sun
    check-hero.cjs                      hero matrix + short phones, scroll sweep, motion at rest, pointer extremes, GPU memory
    check-sunin.cjs                     S1 frame by frame: no seam along the screen outline, change still rising after 1.75 s
    lcp.cjs                             mobile LCP / CLS, median of 3, world off / on / default (no long task > 200 ms)
    export-glb.mjs                      later: procedural objects → GLB starting files for Blender (D6.11)
  public/
    fonts/                              archivo-w78-600-700.woff2 + Be Vietnam Pro 400/500/600 (fraunces-vf.woff2 removed)
    posters/                            committed stills: <shot>-<bucket>-<layer>-<w>.avif|webp|jpg (layers: base, lightroom, lightscreen)
    light/                              CSS sun-layer overlays (AVIF with alpha)
    svg/                                generated block icons, sheet-edge mask, footer edge
  test/
    prod-guard.test.mjs                 (exists) + traces, Little Rose door, partners, featured story guards
    little-rose.test.mjs                door angle 0, untinted light, no location in any LR markup
    tokens-sync.test.mjs                generated JS matches tokens.css; no hex outside tokens.css
    vi-text.test.mjs                    no text-transform: uppercase; line-height ≥ 1.6 on running text
    beats.test.mjs                      journey builds 0..N doors from DOM fixtures; dwell() is monotonic
```

Rules for the module boundary: `world/` is imported only by `main.js` through a dynamic
`import()` after `load` + idle, and never on Tier 0. `world/` reads the DOM (stage box,
beats, text-safe rects) but never writes text. `motion/` never imports `world/`; they talk
through one small event bus on `window.hcwa` (`progress`, `velocity`, `pause`).

WordPress later: `styles/` becomes theme CSS, `partials/` become PHP header/footer and
block patterns (`hcwa/journey`, `hcwa/lit-header`, `hcwa/louver-rows`), `world/` is enqueued
as an ES module on `is_front_page()` only, `public/posters/` become theme assets.

## 2. Build order (10 days, hero first)

| Day | Work | Milestone / gate |
|---|---|---|
| 1 | P0: tokens.css from D1 + `tokens-to-js.mjs`; Archivo build (`scripts/build-fonts.py`); `head-inline.js`; `vikeep.mjs`; header and **hero** HTML/CSS at 390/1440 with the real type; world core just enough to render the room (renderer, `aperture.js` ported from the proof, Tier 1 SDF screen, `frame.js`); `render-posters.mjs` + `encode-posters.py` for the hero base + light layer; CSS sun-in; pause toggle; deploy | **M1: Maxwell sees the hero** on his phone and desktop: final type, the deep-blue screen silhouette, the sun coming in at first paint |
| 2 | Live room: handoff at rest, pointer sun, motes, cloud, scroll-velocity spring, tiers + live gates, render-on-demand loop, context loss; Tier 2 instanced screen | **M2: live hero link.** **G1 device gate** on a real Galaxy A15-class phone: GPU frame with and without shafts; decides Tier 1 shafts and whether mobile corridor/shutters go live |
| 3 | Whole home as HTML-first Tier 0 (every section, stacked stages, stills placeholders, sun/shade rhythm, sheet edges); render proof stills of K1/K2 (flight), corridor bays (3 doors) and shutters at 390/1440; `check-sun-crisp.mjs` | **G2: Maxwell approves the shots** before the sequences are built; HCWA review pack for the Little Rose door and traces starts |
| 4 | Passage: hero block, K0 → K2 path, wash pass, set swap behind the wash, statement + sunlit reading | Hero → statement works as one move |
| 5–6 | Corridor: doors from DOM beats, emblems (Đốt tre, Vòm cầu), Little Rose rules + test, dwell beats, cards, index tabs, skip link, focusin, ResizeObserver stacking, door stills | **M3: S2 complete** (hero → hole → corridor, uninterrupted) |
| 7 | Sheets: Our work (bento, impact plaques, programs), news, help rows, contact band, footer with dusk still; CSS sun layer, lam stripes, bông gió edges | Home complete except S3 |
| 8 | Shutters (S3) + one-shot flood; help section pull-up | **M4: home complete.** If behind: ship the open-shutters still and move on |
| 9 | Inner pages with lit headers, View Transitions (`back`, `lang`), components page | All pages linkable in both languages |
| 10 | `light-contrast.mjs` in CI, Lighthouse, tastegate on every page, `review-animations`, `fixing-motion-performance`, axe, reduced-motion / no-WebGL / no-JS passes; fix | **M5: review link to HCWA** |

**Cut list, in this order, if time or the device gate says so:** Tier 1 shafts → threshold
tiles → live S3 (still) → live corridor on mobile (door stills) → Get Involved louver-mask
header → traces (if not approved) → View Transition `back` type. The hero, the CSS sun-in,
the passage and the pause toggle are never cut.

## 3. Home, section by section

> **Continuity override (8 Nov 2026, Maxwell):** 05-DESIGN section 0.5 "One continuous
> flow" overrides every layout note in sections 3 and 4. Where a section below says
> "opaque sheet", "sheet edge", "band", a section background colour, "rows separated by
> lines" or a header with an edge, build it as part of the single day gradient with long
> feathered seams and translucent reading surfaces instead. No hard lines anywhere on any
> page; a visible hard line is a must-fix.


Section order: header → hero → passage (what we do) → corridor (centers) → Our work sheet →
news → shutters → help → partners → contact band → footer. The hero, passage and corridor
share one fixed canvas and one camera path with no opaque sheet between them (D5.3).

### H0. Document shell

- **Content:** `site.orgName`, `site.description` (meta), `ui.skip`.
- **Layout:** inline critical CSS ≤ 14 KB; `<link rel="preload">` Archivo and BVP 400; the
  hero `<picture>` as the first image; `<canvas id="world" aria-hidden="true">` fixed behind
  `main`, `visibility: hidden` until handoff.
- **Motion:** `head-inline.js` sets `data-tier`, `data-motion`, `sun-pending` before first
  paint. `main.js` (defer) boots UI, then motion; world after `load` + idle on Tier 1–2.
- **Skills:** `design-system` (loading order, font fallbacks), `web-performance-optimization`.
- **Accept:** A5 with the world disabled and enabled (same LCP); no layout shift at handoff.

### H1. Header

- **Content:** `site.logoLines`, `ui.mainNav` items (`about`, `ourWork`, `impact`, `news`,
  `getInvolved`, `contact`), `ui.language`, `ui.donate`, `ui.menu`, `ui.close`.
- **Layout:** 1440: logo left, menu center-right, VI / EN, Ủng hộ (sun); 80 px, transparent
  over the hero with a 120 px `world-ceiling` scrim, then lime-50 at 94%, 64 px. 390: 60 px,
  mark, VI / EN, Ủng hộ, "Menu" (dialog sheet from the right).
- **Motion:** compaction 240 ms; dropdown 180 ms; sheet 320 ms `--ease-drawer`. Never hides.
- **3D:** none; its text rects are `[data-text-safe]` for the framing solver.
- **Skills:** `frontend-ui-engineering`, `emil-design-eng`.
- **Accept:** nav text ≥ 7:1 over every hero sun state (`light-contrast.mjs`); VI / EN and
  Donate reachable without opening the menu at 390.

### H2. Hero (`#hero`, `data-world="room"`): S1 Nắng vào

- **Content:** `site.hero.title` (H1, draft VI), `site.hero.intro` (draft), buttons
  `ui.exploreWork` → Our Work, `ui.getInvolved` → Get Involved; pause toggle `ui.motionPause`
  / `ui.motionPlay` (new). No eyebrow (`site.hero.eyebrow` holds an unverified year), no
  stats, no badges.
- **Layout:** `100svh`, min 620 px. 1440: text bottom-left; H1 Archivo 640 `--fs-display`,
  3 lines VI / 2 EN, `max-inline-size: 8.6em` (ends near 886 px); intro BVP 400 `--fs-lg`
  ink-900, 36ch; primary + secondary (opaque lime-50) buttons in a row; `.hero__stage` box
  = right 40% full height; pause toggle a text button beside the intro's last line. 390:
  portrait shot from the room's back-right corner (the screen overhead, the H1 on the back
  wall beside it), H1 40.8 px (container query), 3 lines; intro 17 px / 1.65; two full-width
  48 px buttons and the pause toggle under them; all inside the first screen.
- **Motion:** CSS sun-in on the poster at first paint (D5.3, every tier with motion on); then
  WebGL handoff at rest (400 ms); pointer sun (fine pointers), cloud + scroll-velocity motes
  (touch). Hero scrolling out starts S2 (K0 → K1). No text animation.
- **3D:** `room` set: lime-wash room in cool shade, screen silhouette `world-screen-face`
  with holes blazing `world-sky`, sun patches resting across the H1 at t 0.08, shafts,
  motes, traces (if approved). `frame.js` keeps the silhouette and floor edge off every text
  rect.
- **Skills:** `gpt-taste` (wide hero type, no eyebrow/pills), `anti-ui-slop`,
  `3d-web-experience` + `threejs-fundamentals` + `threejs-shaders` +
  `shader-programming-glsl` (aperture, shafts), `premium-3d-website` (handoff, preload),
  `aurora-ui` (bloom as daylight only), `emil-design-eng` (pointer spring).
- **Accept:** H1 + both buttons inside the first screen at 1440 × 900 and 390 × 844; H1 ≥ 4.5
  and intro ≥ 7 over every sun state incl. pointer extremes and the cloud dip; the H1 never
  overlaps the screen silhouette (`?debug=frame`); the dull shaded poster is never visible
  for more than 250 ms after the light layer decodes; posters 480 w ≤ 12 + 10 KB; Tier 1
  GPU ≤ 12 ms on the G1 phone; pause stops motes, cloud and pointer sun immediately.

### H3. Passage (`#passage`, `data-world="passage"`): Interest, S2 part 1

- **Content:** H2 `site.whatWeDo.title` ("Chúng tôi làm gì"); statement
  `site.whatWeDo.summary` (draft), split into word spans at build time (`split-words.mjs`)
  with the plain sentence visually hidden.
- **Layout:** section `180svh` (`150svh` < 600 px) with a sticky `100svh` frame; statement
  left-of-center at 1440 (34ch, `--fs-statement` BVP 500, lh 1.6), full width at 390 (16 px
  gutters, 24 px type). The statement carries a feathered sun-100 halo (`mask-image`), so it
  always sits on sun-100 even if the wash is not complete. Tier 0 / reduced motion / no JS:
  a plain sun-100 band, no sticky.
- **Motion:** sunlit reading scrubbed over passage 0.30–0.72 (ink-700 → ink-900, sun-200
  highlight); the statement block `reveal-rise` at 0.28, 200 ms fade at 0.78, both
  reversible.
- **3D:** K1 → K2 into the hero block; wash 0 → 1 over 0.18–0.28 (exactly `world-wash`);
  plateau draws no scene; room swapped for corridor behind the wash; K3 → K4 out of a
  corridor hole over 0.78–1.00.
- **Skills:** `scroll-experience`, `gsap-framer-scroll-animation` (progress, no pins),
  `threejs-animation` (camera path), `fixing-motion-performance` (wash plateau cost).
- **Accept:** from hero rest to corridor bay 1 there is no frame where an opaque HTML sheet
  covers the canvas; the statement is ≥ 7:1 on every frame (render gate); screen readers
  read one sentence (VoiceOver/TalkBack spot check); yaw ≤ 40° per 60svh; scrolling back
  reverses everything at the visitor's speed.

### H4. Corridor (`#centers`, `data-world="corridor"`): Desire 1, S2 part 2

- **Content:** skip link `ui.skipCenters` (new) → `#work`; H2 `ui.centers` ("Cơ sở trực
  thuộc"), `site.centersIntro` (draft); one card per `work.centers[]`: `name`, `serves`
  (draft), `sinceToken` (staging only), `location` (Tre Xanh only, draft; never Little
  Rose), link `ui.visitCenter` (Tre Xanh, Cầu Hàn) or `ui.aboutShelter` (new) +
  `ui.bakery` (Little Rose); Little Rose note `work.littleRose.privacyNote` (new draft, both
  languages); index tabs `ui.centersRail` (new); `ui.allWork` link under the stage.
- **Layout:** section `260svh` (`220svh` < 600 px), canvas fixed, sticky card panel: 1440
  right column 420 px lime-50 panel with 6 px unit band, index tabs as a vertical rail on
  the right edge; 390 bottom 44% panel, tabs as a row at its top, the door framed in the top
  56%. All cards in DOM order. Stacked layout (door still 4:3 + card) when a card is taller
  than 44svh (< 600 px) or 70svh, on Tier 0, reduced motion and no JS.
- **Motion:** stage header in during 0–0.08; per beat: card in at u 0.18 (`reveal-rise`
  520 ms, children stagger 60 ms), out at u 0.86 (200 ms); `focusin` on a card link scrolls
  to its beat (`lenis.scrollTo`, immediate under reduced motion); tabs scroll to beats.
- **3D:** `corridor` set built from `[data-beat]`: N bays, `dwell()` camera, open doors to 72°
  with doorway spill (≤ 15% unit tint) and transom emblem light (Đốt tre, Vòm cầu); Little
  Rose closed, Hoa thị transom, untinted daylight, no trace in its bay; sun t 0.25 → 0.45
  nearly head-on to the door wall; lò cò chalk in bay 1 (if approved).
- **Richness pass (9 Nov 2026, D0.6.2; Maxwell: "a plain white wall").** The corridor becomes a
  Saigon *mái ấm*:
  - Walls: Saigon-yellow lime wash with *loang* (from the lime texture's alpha) over a mint
    oil-paint dado to **0.90 m** on every wall. The dado stops 10 cm short of **every** door frame,
    Little Rose's included. No door surrounds.
  - Floor: a calmed *gạch bông* runner on the screen side (border, 6 field rows, border; no brick)
    and plain cream tiles in the 0.8 m sunlit band by the door wall, where the emblem light lands.
  - Ceiling: warm (`ceiling-warm`).
  - Life: one ceiling fan per bay except Little Rose's (shadows Tier 2 only) and *lá me* leaflets
    (16 / 32). Tier 2 moves on the clock and the scroll, at the existing 30 fps idle cadence and only
    while the corridor is on screen. Tier 1 moves only with the scroll. Everything freezes when paused.
  - Not built: pots, plants, *hoa sứ*, transom glass, the clerestory and the end window.
  - Draws: frames, plus Little Rose's static leaf, become one draw; props are one draw; ≤ 12 draws at
    bay 1 on Tier 1.
  - Stills: `bay-*` and `passage-corridor` are re-rendered with the house (fans still, leaflets
    resting). So are Our Work's `hub-doors-wide`, `hub-door-*`, `door-*` and Green Bamboo's
    `gallery-wall` (wash, dado and plain tiles only).
  - Pane: rail labels and the stacked meta lines go to ink-900. The pane stays at 96% / 92%.
  - Port list and file ownership: D0.6.6.
- **Skills:** `scroll-experience` (sticky stage, steps), `3d-web-experience`,
  `threejs-materials` (paint, `toneMapped: false`), `threejs-shaders` (house surface, props vertex
  motion), `threejs-animation` (door swings, fans, leaflets), `frontend-ui-engineering` (cards,
  tabs, focus), `design-token-contrast` (unit bands), `fixing-motion-performance` (bake slices).
- **Accept:** removing a card from the DOM removes its door (beats test); Little Rose test
  passes; lit door swatches ΔE2000 ≤ 3 against unit tokens; emblem shapes readable on the
  floor at 390 (stills reviewed); tab order: skip link → tabs → card links, focus never on a
  hidden card; 200% text zoom stacks the stage; on G1 failure mobile shows door stills with
  the same cards. **Richness pass, also:**
  - `test/little-rose.test.mjs` gains:
    - the props check over three bay orders: no prop vertex within the bay ±0.3 m, no leaflet path
      within ±0.8 m, no fan disc touching it;
    - no `tile-brick` in the corridor set or props;
    - every colour used as light in the corridor is untinted or has g ≥ r;
    - the door stays at 0 and the transom is plain whatever the DOM says.
  - Text contrast:
    - `light-contrast.mjs` passes on the re-rendered stills (all stage points, VI/EN, 390/1440);
    - the live probe passes on Tier 1 **and** Tier 2 at stage 0.04 / 0.17 / 0.22 / 0.50 / 0.78
      (H ≥ 4.5, running text ≥ 7, darkest 1%, 0 lines below).
  - Light:
    - `check-sun-crisp` passes;
    - the emblem pools at 390 reach a lit-to-shade luminance ratio ≥ 1.6;
    - the runner never reaches under the bông gió patches.
  - Card sync: the 1440 Tier 1 bay-1 frame is re-captured and no dwell frame shows two headings each
    above 10% opacity (beats test plus strip).
  - Performance:
    - no bake slice over 16 ms on the 4× CPU trace and INP < 200 ms, otherwise the bake moves to a
      worker;
    - `lcp.cjs` median of 3, world on and off, ≤ 2.5 s;
    - GPU ≤ 12 ms at 390 × 844 on a real A15-class phone, or "not measured on device" logged in
      section 9 with the runtime gate in place;
    - the D0.6.2 ladder is applied in order if it misses.
  - Stills: every still is within the `encode-posters.py` budgets.
  - Review: the 16-frame strip (390/1440, Tier 1/2) is reviewed for busyness and the "could this be a
    café?" question. If it is busy, simplify the field tile to indigo on cream first.
  - `?house=` flags removed; 97.5% pane not ported.

### H5. Our work sheet (`#work`): Desire 2

The first opaque sheet. It slides over the corridor's last frame with a bông gió top edge;
the canvas pauses once the sheet covers it. Background lime-100 (sun) with the CSS sun
layer in the left margin behind the H2 (moved from the right margin on 9 Nov: Bảo vệ's tiles fill the top
right now, D0.6.3).

**H5a. Focus areas: a floor of *gạch bông* (richness pass, 9 Nov 2026, D0.6.3)**

Maxwell: "so bland and all white". The bento's pale tiles are replaced by cement tiles in pigment: the
corridor's floor seen from above. This replaces the 8 Nov bento. The sentence on the bento's shade pools
in section 9 "Continuity" no longer applies.
- **Content:** H2 `work.focusTitle` (new draft: "[DRAFT: Lĩnh vực hoạt động]" / "[DRAFT: Areas
  of work]"); `work.focusAreas[]` `name`, `text` (draft), link `?area=<id>` to Our Work. No new
  strings; every new element is `aria-hidden` decoration.
- **Layout (tile count is the hierarchy, a tile is never scaled):**
  - Patches: Giáo dục 2 × 2 tiles (Hoa thị, ochre), Bảo vệ trẻ em 2 × 1 (Mắt cáo, indigo), Gia đình
    và cộng đồng 1 (Vòng tròn, jade), Vận động chính sách 1 (Bậc thang redrawn as one rising stair,
    brick).
  - ≥ 1024 (`--T` = `clamp(136px, 11.7vw, 176px)`): a 4-column grid. The H2 is at columns 1–2, row 1.
    The `ul` uses subgrid: Bảo vệ at columns 3–4, rows 1–2 (fills the top right); Giáo dục at columns
    1–2, rows 2–3; Gia đình and Vận động at columns 3 and 4, row 3. Two-tile-wide patches have their
    words under them, at the cell's foot for Giáo dục; single tiles have their words under them.
  - 600–1023: 2 columns. Giáo dục and Bảo vệ are full rows with their words beside; Gia đình and Vận
    động sit side by side.
  - < 600: one column, one tile per area (`clamp(88px, 30vw, 128px)`, a patch cropped to its first
    tile) beside its words.
  - Text: ink-900 / ink-700 on lime-100, never on a tile. The whole area is the link, with the focus
    ring on the `li`.
  - `.sun-layer--work` moves to the left margin behind the H2.
- **Motion (CSS only, transform and opacity only, no blend modes):**
  - A crisp band of Hoa thị sunlight (the mask `public/svg/sun-hoa-thi.svg` from `blocks-svg.mjs`)
    crosses each area as it crosses the viewport (a named view timeline per area, `cover 12%–88%`).
  - The light is the same tile drawn in its `*-lit` pigments, counter-translated so it stays
    registered, so it lands only on pigment and inlay, never on the plaster.
  - Band opacity 0.55 → 1 → 0.55.
  - Hover: lit 0.9 → 1, the name underlines, the arrow moves 3 px.
  - `stagger-cascade` (60 ms, max 4) is kept.
  - Rest (reduced motion, pause, no JS, Tier 0, no scroll timelines): the band at 55% of its travel,
    opacity 0.9.
- **3D:** none. Tile art is an SVG sprite of 4 symbols from `blocks.mjs` (the shader's numbers), with a
  border band, corner quarter-motifs that close into rosettes where tiles meet, the block inlaid in cream,
  and one `feTurbulence` wear filter on the base only.
- **Skills:** `anti-ui-slop`, `design-taste-frontend`, `high-end-visual-design` (tile craft),
  `emil-design-eng` and `ui-motion` (Silk light band), `scroll-experience` (view timelines),
  `design-token-contrast`.
- **Accept (H5a):**
  - The work-sheet test in `test/sheets.test.mjs` asserts 4 `li.gach`, patch sizes 4 / 2 / 1 / 1
    `<use>`s, one sprite, and no `.tile` or `.bento` markup.
  - `check-tiles.cjs` is pointed at `.gach`: no heading or text rect overlaps any tile art or light
    band, at 390 / 600 / 768 / 1024 / 1440, VI and EN.
  - All four names are inside the first 1440 × 900 view of the section. No quadrant of the section's
    box at 1440 is empty plaster (the old top right).
  - Rest frames at 390 and 1440 (reduced motion, no JS) are screenshotted and look deliberate, not
    stained. No light on the plaster at any scroll position (screenshot the strip at 390, where the
    proposal's blobs leaked between tiles).
  - Vận động reads as rising steps at 88 px.
  - 320 px has no overflow (VI, EN, no JS). Tastegate passes at 390 / 1440, VI / EN.
  - CSS ≤ +0.6 KB gzip **net**, with the dead `.bento`, `.tile*` and `lightArt` deleted. Home HTML ≤ +1.5
    KB gzip. No JS.
  - Log the departure from the 8 Nov bento in section 9.

**H5b. Impact plaques**
- **Content:** `impact.figures[]` with `scope: organization` (4 figures, all unverified):
  `token`, `label` (draft), `ui.pending`; link `ui.seeImpact`.
- **Layout:** one row of 4 at 1440, 2 × 2 at 390; lime-50 plaques, 1 px lime-300 edge.
- **Motion:** none on the numbers (they appear with the section).
- **Accept:** PROD renders nothing here while fewer than 2 are verified (existing guard);
  the composition still looks finished without the row (screenshot both states).

**H5c. Projects and programs**
- **Content:** H2 `ui.programs` ("Dự án & chương trình"); `work.programs[]` sorted active
  first, max 4: `status` (`ui.status_active` / `status_completed`), `years` token, `name`,
  `summary` (draft), `partners` as text ("CapitaLand Vietnam"), `centers` as small door
  chips; link `ui.allPrograms`.
- **Layout:** full-width rows separated by lime-300 lines; name Archivo 600 `--fs-h3`;
  1440 two-column row (name + meta / summary + arrow); 390 stacked.
- **Motion:** rows `stagger-cascade`; lam stripes on hover/focus (520 ms).
- **Skills:** `frontend-ui-engineering`, `emil-design-eng`.
- **Accept (H5):** sheet edge shows world light through its holes at 1440 and 390; no grey
  placeholder boxes; reads as a different shape from the corridor cards.

### H6. News (`#news`): Desire 3

- **Content:** H2 `ui.latest`; `news.featuredStory` (staging only; `ui.namesChanged` note;
  image slot = lit wall still 3:2, never a photo until approved); `news.posts[]` (3 real
  posts, real dates 25/05/2026, 25/05/2026, 07/03/2026, `ui.cat_*`, external links);
  `ui.allNews`.
- **Layout:** shade-100 (cool) section. 1440: featured story left (7 columns), typographic
  list right (5 columns) with big Archivo dates; PROD (no featured story): the list spans
  12 columns as three wide rows. 390: one column.
- **Motion:** reveals; date numerals never animate.
- **Skills:** `typography-first` (date list), `frontend-ui-engineering`.
- **Accept:** PROD build has no featured-story markup (new guard); dates per locale
  (VI 25/05/2026, EN 25 May 2026).

### H7. Shutters (`#shutters`, `data-world="shutters"`): S3

- **Content:** skip link `ui.skipShutters` (new) → `#help`; no other text (the stage is
  decorative; the heading lives in H8).
- **Layout:** `180svh` (`150svh` < 600 px); canvas fixed; still on screens < 640 px tall,
  Tier 0, reduced motion, no JS (open-shutters still 16:9 / 4:3 above the help section).
- **Motion / 3D:** D5.3 S3 table: slats 0 → 65°, leaves 0 → 110°, one-shot flood at 0.78
  (900 ms, mote burst, reverses at 1.6×), blend to sun-100 over 0.75–0.90, loop stops at 1.
  Sun t 0.85 → 0.95.
- **Skills:** `3d-web-experience`, `threejs-animation`, `shader-programming-glsl` (stripe
  aperture), `review-animations` (one-shot inside a scrub).
- **Accept:** brightest frame equals sun-100 (pixel check), no white; flood fires once per
  crossing and reverses cleanly; GPU budget holds during the burst.

### H8. How you can help (`#help`): Action

- **Content:** H2 `site.helpTitle`; `involve.ways[]` `title`, `text` (draft); Ủng hộ row gets
  the only sun button (`ui.donate` → Donate); others text links (`ui.volunteer`,
  `ui.partnerWithUs`, `ui.inKind` + `ui.bakery`).
- **Layout:** sun-100 section pulled up 30svh into the S3 stage; four full-width louver
  rows: Archivo title at `--fs-h2` left, one line + link right (1440); stacked (390). CSS
  sun layer in the margins.
- **Motion:** H2 `reveal-rise` as it arrives on the flood; rows stagger 60 ms; louver hover.
- **Skills:** `gpt-taste` (AIDA action), `emil-design-eng`, `copywriting` / `anti-slop` for
  any draft line.
- **Accept:** H2 ≥ 4.5:1 on every S3 frame it overlaps (render gate); exactly one sun
  button in view.

### H9. Partners (`#partners`)

- **Content:** H2 `site.partnersTitle`; approved one-tone logos (C5) only; links
  `ui.partners`, `ui.becomePartner`.
- **Layout / rule:** PROD renders the section only with ≥ 4 approved logos (today: none, so
  absent). Staging shows one dashed placeholder strip `ui.partnersPending` (new) so HCWA sees
  where it goes. No marquee.
- **Accept:** PROD guard test with 1, 3 and 4 approved logos.

### H10. Contact band (`#contact`)

- **Content:** `site.contactBand.title`, `ui.contactUs` → Contact, `site.phone.display`,
  `site.email.general` (both draft).
- **Layout:** blue-900, Archivo 620 white title, on-dusk button, phone/email inline (1440),
  stacked (390). A static 240 px dusk gradient above it ends the day.
- **Accept:** focus ring sun-400 on dusk; `tel:` and `mailto:` links work.

### H11. Footer (dusk, every page)

- **Content:** `site.description`, `site.registration` (draft), address/phone/email (draft),
  `ui.explore` links, `ui.trust` links (`annualReports`, `safeguarding`, `privacy`),
  `ui.followUs` + Facebook link, VI / EN, pause toggle, © current year, `ui.prototypeNote`
  (staging only).
- **Layout:** dusk still band on top (3:1 desktop, 4:3 mobile, lazy, ≤ 35 KB), block-row
  silhouette edge, one sun-400 quatrefoil by the address; 4 columns ≥ 1024, accordions
  below; bottom bar blue-950.
- **3D:** still only (`dusk` shot).
- **Accept:** no embedded feeds; accordions keyboard-operable; still is decorative.

## 4. Inner pages

> **Continuity override (8 Nov 2026, Maxwell):** 05-DESIGN section 0.5 "One continuous
> flow" overrides every layout note in this section and section 3. Where a section below says
> "opaque sheet", "sheet edge", "band", a section background colour, "rows separated by
> lines" or a header with an edge, build it as part of the single day gradient with long
> feathered seams and translucent reading surfaces instead. No hard lines anywhere on any
> page; a visible hard line is a must-fix.


No live WebGL on inner pages. Each page gets one lit header (base still + light layer + CSS
sun-in), reveals, micro-interactions and View Transitions. One idea per page.

### P1. Our Work hub (`/hoat-dong/`, `/en/our-work/`)

1. **Lit header:** H1 `ui.ourWork`, intro (new draft); still = the screen seen frontally at
   10:00. Skills: `premium-3d-website` (still pipeline).
2. **Filter chips:** `ui.filterArea`, `ui.all`, `work.focusAreas[].name` with block icons;
   links to `?area=` (no JS needed); selected icon lights.
3. **Centers:** door cards (door stills 4:3; Little Rose = closed door still, no location,
   `ui.aboutShelter`).
4. **Projects & programs:** `ui.filterStatus` Active / Completed, program rows.
5. **Note:** centers vs programs (draft, from 06-PAGES).
- **Accept:** filters work without JS; list re-orders with a 240 ms FLIP only with motion on;
  `noResults` state exists.

### P2. Center: Green Bamboo (`/hoat-dong/co-so/tre-xanh/`)

1. **Lit header:** 6 px green band, breadcrumb, H1 `work.centers[green-bamboo].name`, since
   token (staging), location (draft); still = its green door open, Đốt tre light on the floor.
2. **At a glance:** who we serve, what we do (draft bullets), since, contact (draft).
3. **Our story:** placeholder copy `[DRAFT]`, ≤ 250 words, expandable full history.
4. **Figures:** `impact.figures` scope `green-bamboo` (pending chips; hidden in PROD).
5. **Gallery:** B3 slots as lime-50 wall panels with asset codes, no stock.
6. **Programs here:** rows filtered by `centers` containing `green-bamboo`.
7. **How to help this center:** draft needs list + Donate link with the center preselected.
8. **Related news, contact, contact band.**
- **Skills:** `frontend-ui-engineering`, `scroll-experience` (header parallax 0.12×).
- **Accept:** header text over the still passes the render gate at 1440; on 390 the text
  sits below the still.

### P3. Program: Thrive Together (`/hoat-dong/du-an/cung-vuon-len/`)

1. **Lit header:** status pill, years token, H1 `fullName`, funder "CapitaLand Việt Nam" as
   text; still = lam stripes of late-morning light on a wall, stripes drift 0.12×.
2. **Summary** (`summary`, draft). 3. **Goals / activities** (`[DRAFT]` lists). 4. **Results:**
   `impact.figures` scope `thrive-together` (pending). 5. **Where it runs:** door chips for its
   three centers (Little Rose chip: name only, no location, no link to a visit). 6.
   **Documents:** report rows or the empty state. 7. **Related news.** 8. **CTA band.**
- **Accept:** reads as "light passing through" (rows, stripes), visibly not a center page.

### P4. Get Involved (`/chung-tay/`)

1. **Lit header:** the open shutters at 17:00 (S3's end state); light layer revealed through
   a CSS louver mask (stripe duty 0 → 1, 1200 ms) instead of the diagonal sweep (cut-list
   item: falls back to the standard sun-in).
2. **Four louver rows** as on home (`involve.ways`).
3. **FAQ:** `involve.faq[].q`, answers `involve.faqAnswer` (placeholder), accordion.
- **Skills:** `design-spells` (louver mask), `emil-design-eng` (accordion).

### P5. Donate (`/chung-tay/ung-ho/`)

1. **Header without imagery:** 6 px sun band, H1 `ui.donate`, one line (draft).
2. **Gift examples:** `involve.giftExamples` with `[số tiền]` tokens (staging only).
3. **Payment panel:** tabs In Vietnam / From abroad from `involve.bankFields` /
   `abroadFields`; every value shows `ui.pendingFinance`; copy buttons disabled;
   `data-donation-placeholder` so PROD fails until `content/verified/donation.json` exists.
4. **"Tell us you donated" form** (UI only), consent, errors.
- **Motion:** micro-interactions only; nothing in the panel animates.
- **Accept:** no imagery near payment details; existing PROD guard still fires.

### P6. Contact (`/lien-he/`)

1. **Lit header:** the street side of the screen at 16:30. 2. **Form:** `ui.f_*`, `ui.e_*`,
topics, consent, error summary, `ui.f_prototypeSent`. 3. **Office card:** address, phone,
email (draft), `ui.openMap` link (no embed). 4. **Zalo / Messenger links** (if HCWA confirms).
- **Accept:** VI walk and EN walk with no mixed-language error message.

### P7. 404 (`/404.html`, `/en/404.html`)

Still "Một viên gạch bị thiếu": the screen with one block missing and light pouring
through; `ui.nf_title`, `ui.nf_text`, search (`ui.nf_search`, `ui.nf_searchBtn`), links
home / Our Work / Contact. Static, 1:1.

### P8. Components (`/components/`, prototype only)

Every component in every state (D7), the token table with measured ratios, motion tokens
with live demos (respecting reduced motion), a light-layer demo, the block-icon set, a tier
switcher, and the reviewer notes for HCWA: Little Rose door stills, traces on/off.
Not built in PROD (existing guard).

## 5. Asset list

Nothing below is a photo of a child, a stock image, or text inside an image.

| Asset | Now (how it is made) | Files | Budget | Blender GLB later? |
|---|---|---|---|---|
| Hoa thị block, screen | procedural: Shape + ExtrudeGeometry / SDF plane | `world/objects/block.js`, `screen.js` | in world code | optional, Tier 2 (≤ 40 KB), holes locked to SDF |
| Hero block (passage close-up) | procedural, high detail | `heroBlock.js` | ~1.3k tris | optional (same GLB as above) |
| Block set: Mắt cáo, Vòng tròn, Bậc thang, Đốt tre, Vòm cầu | GLSL SDFs + generated SVG | `glsl/blocks.js`, `public/svg/` | ≤ 6 KB SVG total | no |
| Room and corridor shells, partitions, parapets | procedural planes + shader | `sets/room.js`, `corridor.js` | | optional: columns, beams, caps (≤ 150 KB) |
| Doors (frame, leaf, panels, transom, threshold tiles) | procedural boxes + paint shader | `objects/door.js` | | optional (≤ 120 KB) |
| Shutters (frame, 56 slats) | procedural, one InstancedMesh | `objects/shutters.js` | | optional (≤ 180 KB) |
| Lime wash, terrazzo | baked DataTextures at startup | `textures.js` | 2 × 1 MB GPU | no |
| Traces (ô ăn quan, lò cò, height marks) | Canvas 2D decals, baked | `objects/traces.js` | 256² alpha | no |
| Street + canopy, shafts, motes, wash | shaders | `sky.js`, `materials/*` | | no |
| Hero posters: base + light layer × 4 buckets × 3 widths | rendered from the world | `public/posters/hero-*` | 480 w ≤ 12 + 10 KB | no |
| Door stills ×3, shutters still, dusk still, story still, 404 still | rendered | `public/posters/*` | ≤ 35 KB each (800 w) | no |
| Gallery wall (Green Bamboo's B3 slots), 21:9 + 16:10 | rendered | `public/posters/gallery-wall-*` | ≤ 60 KB at 1600 w, ≤ 35 KB at 800 w | no |
| Inner header stills (Our Work, Green Bamboo, Thrive Together, Get Involved, Contact) base + light | rendered | `public/posters/header-*` | ≤ 70 KB at 1600 w, ≤ 22 KB at 480 w | no |
| CSS sun-layer overlays (Hoa thị morning, afternoon; lam stripes) | rendered, AVIF with alpha | `public/light/` | ≤ 12 KB each | no |
| Sheet-edge mask, footer edge, block icons | generated SVG | `public/svg/` | ≤ 6 KB total | no |
| Grain tile | generated PNG | `public/light/grain.png` | ~3 KB | no |
| Fonts | Archivo instanced + BVP ×3 | `public/fonts/` | 122.6 KB, 4 files | n/a |
| Real photos (B1, B3, B4, B5, C2, C4) | from HCWA's agency, later | `10-ASSET-REQUEST.md` | per 05-DESIGN D8 | n/a |
| Little Rose illustration (B2) | commissioned, later | | | n/a |

`10-ASSET-REQUEST.md` changes to send HCWA's agency: A2 (home hero photo) is no longer
needed; C1 (page headers) is filled by world stills unless HCWA prefers photos; A7 ("ways to
help" illustrations) is replaced by the louver rows; A5/A6 icons are replaced by the block
set unless the agency's icons are preferred.

## 6. Scripts, guards and tests

- `scripts/tokens-to-js.mjs`: runs before dev and build; `tokens-sync.test.mjs` fails on
  drift or on any hex outside `tokens.css`.
- `scripts/render-posters.mjs` + `encode-posters.py`: `npm run posters` regenerates every
  still; CI (a new GitHub Actions workflow) re-renders and fails if a committed poster is
  stale or over budget. Editors never run it.
- `scripts/light-contrast.mjs`: D1 gate, in CI on every push touching `world/`, `styles/`,
  `pages/` or posters.
- `scripts/check-sun-crisp.mjs`: D6.4 gate.
- New PROD guards in `render.mjs` (tests in `prod-guard.test.mjs`):
  - `content/draft/world.json` `traces.enabled && !traces.approved` → fail.
  - `world.json` `littleRoseDoor.approved !== true` → fail (HCWA safeguarding sign-off).
  - Partners section markup present while fewer than 4 approved logos exist → fail (PROD
    omits the section; the guard catches a leak).
  - Featured-story placeholder markup present → fail (PROD omits it until an approved story
    exists in `content/verified/`).
  - Any `data-draft`, `[DRAFT`, bracket token, pending figure or donation placeholder →
    fail (existing).
- `little-rose.test.mjs`: door angle 0 and untinted motif-free light for `data-unit=
  "little-rose"` whatever the attributes; no `location` field rendered for Little Rose; its
  card links never use `ui.visitCenter`.
- `vi-text.test.mjs`: no `text-transform: uppercase` in `src/styles/`; running-text
  line-height ≥ 1.6.
- `beats.test.mjs`: 0, 1, 3 and 5 cards build 0, 1, 3 and 5 doors; `dwell()` monotonic,
  `dwell(0) = 0`, `dwell(1) = 1`.
- Playwright (`webapp-testing`): keyboard path through the corridor stage, pause toggle
  persistence, VI / EN switch stays on the same page, no console errors.

## 7. Content to add (all `[DRAFT]` until HCWA approves)

`content/draft/ui.json`:

| Key | vi | en |
|---|---|---|
| `motionPause` | Tạm dừng chuyển động | Pause motion |
| `motionPlay` | Bật lại chuyển động | Play motion |
| `skipCenters` | Bỏ qua phần giới thiệu các cơ sở | Skip the centers tour |
| `skipShutters` | Bỏ qua, đến phần Bạn có thể giúp như thế nào | Skip to how you can help |
| `centersRail` | Các cơ sở | Centers |
| `aboutShelter` | Tìm hiểu về mái ấm | About the shelter |
| `draftHide` / `draftShow` | Ẩn khung nháp / Hiện khung nháp | Hide draft outlines / Show draft outlines |
| `partnersPending` (staging) | [Chờ ít nhất 4 logo đối tác đã được duyệt] | [Waiting for at least 4 approved partner logos] |

`content/draft/work.json`: `focusTitle` ("[DRAFT: Lĩnh vực hoạt động]" / "[DRAFT: Areas of
work]"); `littleRose.privacyNote`: "[DRAFT: Để bảo vệ sự riêng tư và an toàn của các em,
chúng tôi không đăng hình ảnh, tên hay địa chỉ của mái ấm.]" / "[DRAFT: To protect the
girls' privacy and safety, we never publish their photos, their names or the shelter's
address.]"; Our Work hub intro (draft, both languages).

`content/draft/world.json` (new): `{ "traces": { "enabled": true, "approved": false },
"littleRoseDoor": { "approved": false } }`.

English drafts go through `anti-slop`; HCWA approves all final wording.

## 8. Risks to watch while building

| Risk | Watch for | Fallback |
|---|---|---|
| Per-pixel cost on Mali/Adreno (shafts, aperture tests) | G1 numbers on day 2 | shafts off; mobile corridor and shutters as stills |
| The flight feels like a trick, or makes people queasy | M3 review on a phone; yaw/FOV limits | slower yaw, shorter passage (150svh), never remove the pause or reduced-motion path |
| Café or architecture-magazine drift | tastegate questions "could this be a café?", "does it read as a place for children?", "does anything move?" | cut decoration, keep light and traces |
| Little Rose door read as locked-in or secretive | HCWA safeguarding review | the privacy note carries it; if HCWA prefers, Little Rose gets no door at all, only its card |
| Corridor reads as one building | M3 review | stronger bay partitions, separate threshold tiles, location lines on cards |
| iOS / Android address-bar jumps with sticky stages | real devices | `svh` units, `ignoreMobileResize`, stacked fallback |
| Statement unreadable during the wash edge | render gate on passage frames | the halo is always sun-100, so it holds even if the wash lags |
| Ten days is tight for one engineer | daily milestones | the cut list in section 2 |

## 9. Departures logged at integration (8 Nov 2026)

What the build does differently from sections 3 to 6, and why. HCWA or Maxwell can ask for any of
these back.

**H2 hero.** Below 1024 px the pause toggle sits under the intro (it scrolls away with the text, and the
camera is freed about 120 px sooner at 390). When enlarged text would cross the still's screen
(`src/hero-plate.js` tests each line against the poster's screen outline), the hero gets `.hero--plate`: a
feathered lime-50 plate behind the H1, intro and toggle.

**H3 passage.** The section is the flight's scroll length (180svh, 150svh below 600 px) plus its 100svh
sticky frame, so the statement stays pinned through 0.28-0.78; home is 100svh longer than planned. Its
ScrollTrigger runs top-top to bottom-bottom (`[data-stage]`). `html.stages` is set on every page with
tier ≥ 1 and motion on; only the passage, hero media, corridor and shutters rules read it. When the
statement is taller than the pinned frame (200% text on a phone), the passage gets `.is-unfit` and is a
band of light in the flow instead of a pinned stage.

**H4 corridor.**
1. The track scales with the number of centers: 100svh + n × 50svh (n × 40svh below 600 px), 250svh and
   220svh for three; no tail after the last door.
   Phones: the card panel is the frame's bottom 45svh (one row more than 44%, for Little Rose's two link
   rows), the door framed in the top 55%.
2. The stacked fallback shows each bay as the live camera's own rendered view (`bay-*` frame stills,
   16:10 or 3:4) with its card set into it, not a 4:3 door still beside a card. The `door-*` stills stay
   for the Our Work hub.
3. On desktop the door index is a column at the top of the right-hand panel, not a rail on the far edge.
4. The stage header (H2 + intro) stays in the panel until the first card arrives; on desktop the panel's
   content is centred, so the header is in view while the frame rises.
5. Each card follows the door nearest the camera's look target (in at walk u 0.18, out at 0.97); the
   last card leaves at stage 0.95 while the glass pane and the door index fade to nothing (0.95-1.0,
   scrubbed), and from 0.95 the camera walks on past the blue door.
6. `ui.allWork` sits under the stage on a plaster fade (at least 92% from 15svh above the section's foot);
   past the stage's end the canvas washes to lime-100 over 20svh while the camera keeps walking, so the
   link stands on plain plaster.
7. The door at its reading spot is 4.4 m away; camera x 99.5, yaw 15° on desktop, x 100.05, yaw 2° on
   phones, with a lens shift framing the door.

**H7 shutters.**
1. A 30svh lead (24svh on phones), then a 100svh sticky frame plus 180svh (150svh); help rises --rise
   (24% of the track, 30% on phones) into it through the shutters section's own `margin-bottom` (no
   `data-staged` flag; the old pages.css pull-up is gone). Help's top reaches the screen's foot at p 0.76
   (0.70 on phones). On the stage, help's top padding is 24-32 px instead of a section's, and its H2
   reveals as it crosses the foot (`data-reveal-on="entry"`), so the heading rises into the flood.
2. The leaves swing into the room, matching `shutterSpec` and the Get Involved header still.
3. A MIN-blend cap pass holds every S3 pixel at or below sun-100.
4. The fallback still is a 16:10 / 3:4 frame crop, shown 16:9 on desktop and 3:4 on phones.
5. The camera already moves during the approach. On phones (portrait) the dolly stops at 3.8 m with a
   72° vertical field, so both leaves folded back to 110° stay in the 390 × 844 frame through the blend.
   Through the opening: a tamarind canopy across its top (swaying on Tier 2 only) and a low parapet with
   a course of lit Hoa thị holes.
6. Idle tamarind sway in the corridor, Tier 2 only.
7. Corridor doors swing on `--ease-out` instead of smoothstep.

**Continuity (D0.5) on the sheets.** The work sheet's focus areas are a floor of gạch bông (H5a, see
"H5a focus floor" below): no line is drawn between areas, the joints are the page's own plaster.
Plaques have no frames. Work (lime-100) runs into news (shade-100) over 28svh (18svh on phones) with
eased stops. Louver faces feather in over 36 px. The footer parapet is a row of lit Hoa thị holes with
no blue-950 band, and the dusk still's foot sinks into the night. Lit headers end their fade above the
still's wall/floor line, measured per still on the re-rendered (warm plaster) stills: Our Work 58-75%
(left fade 34-54%, so the screen wall comes out of the light), Green Bamboo 58-81%, Thrive Together
66-94%, Get Involved 62-86% on desktop.

**P1 Our Work hub.** From 600 px the centers are one render of the hub wall (`hub-doors-wide`, 21:9, doors
at 1/6, 1/2 and 5/6 so each door stands over its own column; one level camera 5 m from the wall, the
gallery screen behind it laying Hoa thị patches between the doors, none in Little Rose's bay). The wall
comes out of the plaster over its top 5% and its floor sinks into the page from the threshold line (81%);
each plate starts at 89%, on the floor in front of its door and clear of the open leaves' feet. Phones
show each door's own 4:5 render from the same spot (`hub-door-<unit>`), shown whole. The CSS door leaves
are gone (they matched no re-rendered still): the scroll-linked idea is now the open doorways' sun-200
light carried on across the floor between the threshold and the name as the wall comes in. Little Rose's
door is shut and throws no light.

**P2 Green Bamboo.** The header still now shows the door already open from the door wall in perspective,
so the CSS header leaf is dropped; the header's scroll-linked idea is the light layer's 0.12× drift. The
gallery (B3 slots) hangs its six frames of light on its own render, `gallery-wall` (21:9 from 600 px,
16:10 on phones): a stretch of the corridor's far wall, no door, a screen in the side wall out of frame
raking a slanted band of Hoa thị across it. The picture fades out on every side. The `story` still is
left to the home news feature.

**P3 Thrive Together.** The header's lam stripes carry their gold in the render (sun-200 toward sun-400 on
warm lime), so the page applies no filter to the light layer.

**Guards (section 6).** `content/draft/world.json` holds `traces` (enabled, not approved) and
`littleRoseDoor` (not approved). PROD fails once for the traces flag and on every page that shows the
Little Rose door in any form (the corridor bay, door, hub-door and bay stills, the hub panorama
`hub-doors-wide`, the contact page's painted door): home, Our Work, Thrive Together and Contact in both
languages. No traces module exists yet, so the flag
gates nothing drawn today.

**Removed.** The Phase 1 kit exports (`placeholder`, `figure`, `figureStrip`, `centerCard`, `storyCard`,
`newsItem`, `reportRow`, `ctaBand`, `sectionHead`, `unitDot`, `langNotice`) and their CSS. `centerCard`
was the last place that could label Little Rose with `ui.visitCenter`.

**Stills, corner seams (recheck r2).** The still surface's analytic AO skipped every box face within 3 mm of
a point, which also skipped the neighbouring wall's last 3 mm: every corner of every still set (and of the
live corridor and shutters, which use the same surface) drew a 1 px unshaded bright seam. A surface now
skips only the face it lies in (by its normal). Every still was re-rendered and re-encoded within budget;
the hero room (`materials/surface.js`) is unchanged.

**Scripts.** `npm run svg` regenerates `public/svg/` from the block SDF numbers (now also
`sun-hoa-thi.svg`, the floor's sunlight). `npm run check:tiles` checks the gạch floor: no heading or line
of text touches any patch or its light band, the sun layer touches neither, all four names sit in the
section's first 1440 × 900 view, and nothing scrolls sideways (320 / 390 / 600 / 768 / 1024 / 1440, VI and
EN). The pause
toggle carries `data-paused` (no `aria-pressed`); `check-page.cjs` and `check-hero.cjs` read that.

**H5a focus floor (richness pass, 9 Nov 2026; focus engineer).** Built to D0.6.3. The 8 Nov bento, its
`.bento` / `.tile*` / `.block-light*` CSS and `lightArt` are deleted. Where the build differs:
1. From 1280 px, Giáo dục and Bảo vệ set their words beside the patch, at its foot, not under it. At 1440
   a two-tile patch (337 px) left 311 px of bare plaster beside it in its two-column cell, and words under
   Bảo vệ's patch left the top right empty again. 1024-1279 keeps D0.6.3 exactly (words under; Giáo dục's
   at the cell's foot), because words beside would be under 20ch there.
2. The corner motifs are clipped to the medallion's square with a 4 mm corner, not its 14 mm one. With
   rx 14 the cream quarter Hoa thị (lobe 13) sat almost wholly in the joint and no rosette closed. The
   medallion keeps rx 14 underneath, so a tile reads as square with quarter-flowers in its corners.
3. Mắt cáo inlays keep only the block's 9 whole diamonds: the slivers its frame cuts read as grit at 88 px.
4. Symmetric tiles are laid at quarter turns (as a real floor is), so the wear never repeats within a
   patch; the stair is never turned. Wear is one feTurbulence (fractal, 0.62, 2 octaves, alpha 8a - 4.1)
   at 32%: a fine cement speckle. Lower frequencies read as water stains.
5. The 2 × 2 inlay blocks carry a 0.8 mm cream stroke, which closes the anti-aliased hairlines where the
   Vòng tròn quarter rings meet in the middle.
6. The sun layer is an absolutely placed item in the H2's own grid row (cols 1-2 from 1024), so it can
   reach neither a tile nor an area's words; `check:tiles` asserts it.
7. The arrow keeps to the name's last word with a word joiner (U+2060), never alone on a line.
8. The light band's rest (55% of travel, opacity 0.9) also applies on Tier 0, as D0.6.3 lists.
9. Our Work's filter chips keep the `screenArt` block icon; D0.6 does not extend the floor to them.
Budgets: home HTML +0.6 KB gzip (VI and EN), main CSS +0.2 KB gzip net, `public/svg` 4.8 KB of 6. No JS.
Measured text over the floor: H2 and names 13.02, lines 7.41 (lime-100 under every word at 390 and 1440,
VI and EN, Tier 0 and 1). HCWA approves the tile palette and the brick tile (D0.6.4).

**H5a focus floor, fix round 1 (9 Nov 2026; focus engineer).** Maxwell: "so bland and all white". Built to the
critique's items; where the build differs, and what it supersedes above:
1. **The sheet rises under the corridor's exit (D0.5).** On the stage (`#centers.is-stage + #work`) it rises
   24svh (16svh below 600 px) with a `clamp(24px, 3vw, 48px)` lead, so the H2 and the first patch arrive in
   the lower half of the screen while the runner is still fading above. The sheet keeps its bông gió row and
   is plaster from its top, not see-through: the corridor's wash now runs from the frame's top down
   (`WALK_OUT`), so a see-through lead would have set the H2 on the runner. Stacked (Tier 0, reduced motion,
   no JS) there is no picture and the corridor's list ends on the link to every center, so the sheet rises
   only into the corridor's own foot, `--section-y − --s-8`, and keeps 64 px clear of that link. Where the
   sheet follows the corridor its bông gió row tops out at 62% opacity and the sheet carries the same fade
   on to solid over its first 24 px (above the H2): at full opacity the row read as a lace trim ending on a
   line under its last pips, now that it passes over the live runner.
2. **Light band** as the critique set it: Hoa thị blocks 0.42 of a tile, a band 2 tiles wide with 10% side
   fades, the lit copy at wear 0.16 (supersedes D0.6.3's "`--wear: 0` in the lit copy"). `sun-hoa-thi.svg`
   now holds five rows (1650 units, 2.3 tiles): the shear does not repeat in y, and three rows would have
   tiled with a seam inside Giáo dục's patch. At rest `check:tiles` counts, from the mask's own lattice, the
   whole quatrefoils in the band's unfaded part: 4 on a single tile, 7 on Bảo vệ, 14 on Giáo dục, at every
   width (the geometry scales with `--T`). Opacity at rest stays 0.9.
3. **A patch is one laid floor.** Each tile is a square of cement (`--cream`) with its medallion 2 mm in (was
   7) and rx 1.5; the corner motifs clip to the tile's square (supersedes item 2 above), so four quarter-discs
   close into one rosette across the joint. Tiles meet at a 1.5 px `vector-effect: non-scaling-stroke` grout
   joint (`color-mix(tile-cream, ink-700 14%)`; in the light `sun-100` + 12%), drawn only between tiles: no
   outline goes round a patch. `--T` is unchanged.
4. **A cement ground.** `.floor::before` is tile-cream and `.floor::after` its speckle (`public/svg/cement.svg`,
   the tiles' own wear noise, alpha only, seamless at 240 px) in `color-mix(tile-cream, ink-700 44%)` at 0.28,
   both under one ellipse feathered from 46% to 100% of its radius (at least 100 px at 320, 194 px at 1440).
   **Departure:** the areas' lines are ink-900, not ink-700 (D0.6.3): ink-700 fell under 7:1 on the darkest
   grains. Measured with the text hidden, darkest 1% under each line, VI and EN, 390 and 1440, Tier 0 and
   Tier 1 (sheet rising, and floor in view): every line ≥ 10.86, 0 below. Size and weight keep the hierarchy.
5. **No plaster hole.** From 1024 px Giáo dục's words follow its patch straight away (supersedes item 1's
   "at the cell's foot" for 1024-1279): beside the patch's first row from 1280, under the patch below that.
6. **Phones show the tile count.** Below 600 px Giáo dục shows its whole 2 × 2 patch and Bảo vệ its 2 × 1,
   above their words; Gia đình and Vận động keep one tile beside theirs (supersedes D0.6.3's < 600 row).
   `check:tiles` asserts every patch shows whole tiles at its count, one `--T` for all (320-1440).
7. **`.sun-layer--work` is deleted** (the critique's second option; supersedes item 6 above). Crisp patches
   behind the H2 would sit in the overlap, over the corridor's picture; the tile light carries the sheet.
8. Not done: the Our Work filter chips' tile swatches (could). `src/pages/work.mjs` has no owner in this round.
9. `check:tiles` also walks the stage overlap at 390 and 1440 (Tier 1) in 40 px steps and fails when the
   sheet hides more than a third of a corridor line that can still be read, or when any sheet word or tile
   touches one. Open (corridor side): in EN at 390 the last card and its "All work" link sit in the frame's
   bottom ~100 px, so for one 40 px step before the card leaves (stage 0.95) the sheet's bông gió row passes
   over the link (VI and 1440 pass). The last card leaving a little earlier on phones, or sitting higher,
   clears it; the 16svh rise is the critique's number and is kept.
Budgets: home HTML +54 B gzip (VI and EN), main CSS +0.33 KB gzip (with the corridor's concurrent stages.css
changes), `public/svg` 5.3 KB of 6. No JS. HCWA still approves the tile palette and the brick tile.

**H5a focus floor, fix round 2 (9 Nov 2026; focus engineer).** Maxwell: "so bland and all white". Built to the
round-2 critique; where the build differs, and what it supersedes above:
1. **The sheet lays no lace over the live corridor.** On the stage (`html.stages #centers.is-stage + #work`) the
   bông gió row is gone and the sheet is solid `--wall` from its top; the corridor's canvas fades itself out
   above the sheet (its hem, the corridor engineer's). The 24svh / 16svh rise and the H2 lead are unchanged.
   Stacked (Tier 0, reduced motion, no JS) keeps its 62% row and 24 px fade (supersedes fix r1 item 1 on the
   stage only). Seam scan at 390 and 1440, Tier 1: no step over 1 level across the sheet's top in a continuous
   scroll (`qa/m3/fix-r2-focus/logs/seam.log`).
2. **Light: isolated quatrefoils.** `sun-hoa-thi.svg` draws each hole (quatrefoil and 4 pips) at 0.6 of its
   300-unit cell, so 0.4 of a cell of tile lies between two lit shapes in x and in y; three rows, each a third
   of a cell further along, so the 300 × 990 file repeats seamlessly in x and y. **Departures:** (a) the cell
   is `0.54 × --T`, not 0.62: at 0.62 a single tile holds one row of light, so Gia đình and Vận động could
   never show the critique's "at least 2 whole quatrefoils"; at 0.54 two rows land whole on any tile (the
   mask's rows are centred on it, 0.5 T ± 0.55 cell) and a quatrefoil cell is 89 px at 1440 and 63 px at
   390, the critique's own sizes. (b) A hole leans 8°, not 14°: the 14° shear turned the four lobes into one
   lump, which is half of what read as camouflage. (c) The lit copy's cream is `sun-200`, not `sun-100` (1.08:1
   on tile-cream), so the light shows on the inlays too and each quatrefoil reads whole instead of breaking
   into the scraps that fell on pigment. Wear: 0.22 on the floor, 0.08 in the light. `check:tiles` counts,
   from the mask's own lattice, whole quatrefoils at rest and at mid-sweep: 2 / 2 on a single tile, 5 / 6 on
   Bảo vệ, 8 / 9 on Giáo dục, at 390 to 1440, VI and EN.
3. **A floor of plain cement tiles.** The patches are laid into a lattice of plain tiles at exactly `--T`
   (1.5 px joints, `color-mix(tile-cream, ink-700 10%)`, half on each side of a tile's edge), and `--T` is now
   a whole number of pixels (`round(down, …, 1px)`). From a 56rem container the floor is one lattice: four
   columns of two tiles with no gaps (`--T` at most an eighth of the container), rows of at least one tile, so
   every patch corner is a joint (Bảo vệ at 4 T, Giáo dục at 1 T down, Gia đình and Vận động at 2 T, a plain
   row under Bảo vệ); the plain tiles there also vary 0-4.5% in tone (`plain-tiles.svg`, black alpha, a 5 × 4
   run). Below, each area's joints are registered to its own patch and fade over half the gap to the next
   (tablets: Vận động starts 3 T in, so both share one lattice); tones would read as bands there, so none.
   **Departures:** (a) D0.6.3's "text never sits on a tile" now means a pigment tile: the words cross plain
   joints (lead sign-off). Measured with the text hidden, darkest 1% under each line, VI and EN, 390 / 768 /
   1024 / 1440, Tier 0 and Tier 1: H2 ≥ 11.28, names and lines ≥ 9.43, 0 below. (b) From 1024 to about 1180 px
   `--T` is under D0.6.3's 136 px floor (118 at 1024), so that eight tiles fit the container. (c) Both
   two-tile patches set their words beside the patch from 1024 (supersedes fix r1 item 5 for 1024-1279), in
   the second column of two tiles; Giáo dục's words take the patch's full height with the link's arrow at its
   foot, level with the patch's last joint. (d) The floor's layouts follow its container in rem
   (`@container`, 33rem / 56rem: a 600 / 1024 px window at the default text size), so with text at 200% it
   steps down a layout instead of pushing words onto the tiles.
4. **Program rows carry the floor's pigments.** Each row's head has an `aria-hidden` strip of its areas'
   gạch tiles (blocks.mjs `gachStrip`, the same symbols, one 44 px tile per area, 48 px from 1024, never turned,
   never scaled per row) above the status line, on the home sheet and on Our Work. The page's one sprite comes
   with the floor on home and with the first row elsewhere (`gachSpriteOnce`); it never sits inside a row a
   filter may hide on home. On Our Work, when a filter hides the first row, the other rows' tiles lose their
   wear speckle (Chromium drops a filter inside `display: none`); the shapes are unchanged. The row's light
   stays sunlight: the critique's optional pigment-tinted hover is not taken (tinted light is the declined
   *kính màu*, 0.6 item 5).
5. Not done (could): the Our Work filter chips' gạch tiles. `src/pages/work.mjs` still has no owner.
Budgets: home HTML +0.10 KB gzip (VI and EN); Our Work +1.5 KB gzip (the sprite now ships there); pages.css +0.12
KB gzip, components.css ±0; `public/svg` 5.7 KB of 6. No JS. Captures: `qa/m3/fix-r2-focus/`.

**H4 corridor richness pass (9 Nov 2026; corridor engineer).** Built to D0.6.2 and D0.6.4 from the port list
(saigon-home's bake, house surface, fans and leaflets, vertex paint, props clock; colour-light's loang in the
lime texture's alpha, Little Rose leaf merge, still baking, ink-900 meta lines, red/pink test). Not ported:
pots, plants, hoa sứ, door surrounds, the 97.5% pane, the `?house=` flags, anything glass. Where the build
differs:
1. **The bake runs in a module worker** (`world/gach-worker.js`, pure maths in `world/gach-bake.js`). On the
   4× CPU trace the main-thread bake's first slice took 32 ms (rule: 16 ms), so D0.6.2's fallback applies:
   the worker bakes both textures and hands back transferable buffers (138 ms Tier 1, 319 ms Tier 2 at 4×,
   0 ms on the main thread). Where no module worker starts, the sliced main-thread bake (8 / 16 rows) loads
   as its own chunk. The stills call the maths directly.
2. **Fan shadows land on the surfaces, not in the shafts.** In the shafts' march the blade test ran in every
   step: the SwiftShader proxy at 1440 Tier 2 went from +6% (no fans) to +52%; with the blades on the
   surfaces only it is +11%. On a gate step from Tier 2 to Tier 1 the fans stop casting (a uniform), their
   test stays compiled.
3. **The house's sun targets shift relative to `sun-corridor-a`**, not the room's warmer rest sun, so at
   t 0.25 a patch on the wash is `world-vang-sun` and on the dado `world-dado-sun` (measured in D0.6.1).
   The dado's painted line in the sun is `dado-line × dado-sun / dado-shade`.
4. **Wear** is tileable gradient noise with the quintic fade (3 octaves) in the texture's alpha, plus the 3%
   speckle; quintic value noise still showed axis-aligned blocks at 2×. The shader lifts pigment toward cream
   by up to 18%, × a Gaussian (σ 0.35 m) about the runner's centre × the lime texture's broad mottle, so the
   0.4 m bake never repeats as a pattern. Per-tile tone (±2.5%) is by tile id in the shader.
5. **Emblem pools: not on the plain band.** The rooms' light leaves the transom at 57°, so the Đốt tre and
   Vòm cầu pools land 1.49-1.69 m from the door wall, on the runner's field (the 0.8 m plain band takes the
   screen's sun, which lands within 0.72 m of the wall, and the first 0.8 m of the doorway's spill). On the
   runner's cream ground the pool is 1.72:1 lit to shade (token maths; the old terrazzo 1.86), but the
   pattern breaks the slots and the arch: the emblems read in the transoms, not on the floor. D0.6.2's
   signature check therefore fails as written and needs a lead decision:
   (a) leave it; (b) steepen the rooms' light to ~76° so the pools reach the plain band (then 7 cm deep and
   merged with the doorway's spill); (c) centre a 0.8 m runner between a 0.6 m sunlit band and a 1.0 m
   plain band on the screen side where the pools land.
6. **Green Bamboo's gallery is framed higher** (wall y 0.95-3.25 m, was 0.02-2.59): with the house's dado
   in frame its painted line ran under the two lower frames and through the page's bottom fade. The frames
   now hang on the wash; the dado is just below the crop. `header-green-bamboo` is not in D0.6.2's list and
   keeps the 8 Nov plaster, so that page shows two plasters (header warm-grey, gallery yellow): flagged.
7. The rail labels are ink-900 at every size (`.bays__rail a`), not only the 13 px stage ones.
8. The stills' resting leaflets: two on the tiles in each open bay and one in the lead-in (five in the
   home corridor). Fans stand at a fixed angle (0.6 rad).
9. The door frames and Little Rose's leaf merge with a small merge in `objects/door.js`, and `MAX_DOORS`
   moved to `glsl/blocks.js`: three's BufferGeometryUtils and the doors' light no longer ride in the shared
   chunk.
10. Not built: D0.6.2's optional 4° pitch-down during the end wash.
11. Supersedes H7 item 6 above: the corridor's tamarinds sway on the props clock on every tier (Tier 1 with
   the scroll only), and Tier 2's idle frames run only while the passage or the corridor intersects the
   viewport and the frame is not washed out.

Measured (Tier 1 worst frame, bay 1, door open): 12 draw calls (14), 1,306 triangles (560); textures +0.50 MB
Tier 1, +2.00 MB Tier 2; props 0.05 MB and frames 0.02 MB of geometry. Lazy JS gzip: corridor chunk 10.05 KB
(6.69), the worker 1.3 KB, world total +6.3 KB on the worker path (target +6: 0.3 KB over; the main-thread
bake's 1.2 KB chunk loads only when no worker starts). Per frame: two uniform writes
(`uFanA`, `uTime`), no allocation. SwiftShader proxy (not a GPU measurement; median of 24 frames with a
pixel read-back): 390 × 844 Tier 1 +13% (89.9 → 101.4 ms), 1440 Tier 1 +14%, 1440 Tier 2 +11%.
**GPU on a real A15-class phone: not measured on device** (no phone here; 05-DESIGN 13.3). The runtime gate
(Tier 1 > 14 ms → lite) stays, and the D0.6.2 ladder applies in order if the phone misses.
Text: `light-contrast.mjs` 212 lines over the re-rendered stills, 0 below; the live probe at stage 0.04 /
0.17 / 0.22 / 0.50 / 0.78, Tier 1 and 2, VI and EN, 390 and 1440, darkest 1%: 296 lines, 0 below (worst
running text 7.60 Tier 1, 7.59 Tier 2; headings ≥ 12.5). `check-sun-crisp` passes (its corridor set is
still listed as skipped). LCP (`lcp.cjs`, median of 3, Moto G Power / Slow 4G / 4× CPU): world off 2,180 ms,
world on 2,208 ms, default path 2,208 ms (limit 2,500, goal 1,800); the script still exits 1 on its
first-10 s long-task rule (255 ms; 267 ms before this pass). The 16-frame strips (390 and 1440, Tier 1 and 2,
VI and EN): live, 0 console errors, no frame with two headings above 10%. `check-bays` 24/24; the matrix
(home corridor, Our Work, Green Bamboo; VI/EN; 390/1440; Tier 1/2, reduced, Tier 0, no JS; 320): 56 views,
0 console errors, 0 overflow. Tastegate passes on home, VI and EN. Stills: every `bay-*`, `passage-corridor`, `hub-*`, `door-*` and `gallery-wall`
file re-encoded within `encode-posters.py`'s budgets (bay and passage AVIF at q49-62).
HCWA approves before production: the yellow and mint paint, the fans, the leaflets and the tile palette
(D0.6.4); the safeguarding lead sees `bay-little-rose` re-rendered (no fan, no leaflet, door shut, plain
transom, the same dado break as every door).

**H4 corridor, fix round 1 (9 Nov 2026; corridor engineer).** Maxwell: "the hallway section is so boring its
jst a plain white wall". The critique's items, each at its source; where the build differs, and what it
supersedes above (item 6 of "H4 corridor" and items 5 and 10 of the richness pass):
1. **The way out.** The link to every center is in the panel: it comes in with the last card, beside that
   card's own link when the row has room (under it otherwise), and goes with it (`--all-x` / `--all-y` from
   `motion/stages.js`; focusing it walks to the last door). The section ends where the walk does: no plaster
   tail, no bottom fade. Over stage 0.92-1.0 the camera looks 14° down to the runner (scrubbed, sine in-out; the
   4° option was invisible). From the stage's end the image scrolls up with the frame (a lens shift equal to the
   scroll since the pin let go) and washes to lime-100 **from the top of the frame down** (`renderer.js`
   `uSweep`: a front feathered over 0.6 of the part still seen above the next sheet), `--ease-in-out`, until
   the sheet's top is 0.12 of a screen from the frame's top and never in under 24svh (16svh below 600 px;
   `journey.js` `washSpan`). The runner is the last thing seen, fading just above the work sheet's bông gió
   row as its tiles rise (the sheet's 24svh / 16svh rise is the focus engineer's). Seam scan, the critique's
   method (a frame every 40 px from stage 0.86 to the H2 at 0.3 of the screen; background = within 10 levels of
   lime-100, the header left out), VI and EN, 390 and 1440, Tier 1 and 2: the
   emptiest frame holds 27.3% non-background, 0 frames under 15%, no frame whose only content is a link.
2. **Leaflets.** Tier 1 draws none (D0.6.2 ladder step 2 taken now; `LEAFLETS`). Tier 2: two a beat, 8 at
   most (was 32), released where their drift passes 1.7-2.6 m ahead of each door's reading spot and drawn only
   1.3-3.6 m from the camera (faded and shrunk outside it), 68 × 26 mm with a tapered tip and a midrib,
   `mixc(tile-jade, sun-400, 0.25)`, glinting only when the face shown is to the sun. `uLeafFade`
   (`journey.js` `leafFade`, from the stage's progress, never time) takes their alpha and size to 0 over the
   first 0.04 of Little Rose's beat and back over its last 0.04; a gate step down to Tier 1 grounds them.
   `little-rose.test.mjs` projects every point of every leaflet's drift with the dwell camera at 390 × 844
   and 1440 × 900 for u 0.05-0.95 of Little Rose's beat, three bay orders.
3. **Thresholds.** Before every door, Little Rose's identical, the runner stops for 6 plain cream tiles (1.2 m,
   either side of the tile joint nearest the door's centre) across its whole width, its border chain turning
   the corner (mitred corner tiles): each bay has its own rug (`uGachP.y`, `still-surface.js`). So the pools
   read on them: the transom's emblem is drawn crisper than the doorway's spill (penumbra 0.0015 + 0.0035·d,
   was 0.0025 + 0.0056·d), the rooms' daylight on the floor is the day colour itself (was 86% of it) at full
   strength (`uI[1]` 1, was 0.9), and on phones the door's centre sits at 0.44 of the free frame (was 0.5),
   which puts the threshold above the card panel's feather. Signature check (rooms' light on and off, the
   pixels it brightens inside each pool's floor quad, `pool.cjs`): at 390 Tier 1 Đốt tre 1.77 at the
   slots' core (90th percentile) and 1.51 median, Vòm cầu 1.72 / 1.54; at 1440 Tier 2 1.73 / 1.49 and
   1.66 / 1.34. The medians carry the
   antialiased edges of 2-3 px slots; the slots themselves pass 1.6. **Little Rose's threshold has no pool**:
   its plain transom glows on the wall and casts nothing on the floor (`glsl/doors.js`), so its floor has only
   the corridor's untinted shade.
4. **The pane.** On desktop the plaster runs only from the door index's top − 24 px to the shown text's foot +
   72 px, feathered over 96 px with eased stops (`--pane-t` / `--pane-b`, registered and eased between
   cards): the wash and the ceiling show above it, the dado and the floor below. It leans to the wall it stands
   on: lime-50 with 20% of `world-vang-sun`, at 96% (Tier 1) and 92% (Tier 2). Live probe (stage 0.04 / 0.17 /
   0.22 / 0.50 / 0.78, Tier 1 and 2, VI and EN, 390 and 1440, darkest 1%): 296 lines, 0 below; worst running
   text 7.42 (the phone stage intro), headings ≥ 12.21. The 20% tint stays.
5. **Door wear.** A leaf (its `aLeafP`, hinge space, `objects/door.js`) is worn only within 4 cm of its edges,
   in a kick zone fading up its foot's 25 cm and in a 10 cm disc at the handle; its face takes at most 0.1, and
   the chips stay on the leaf as it swings. Frames keep their chipped edge band. One rule for every door,
   Little Rose's at its 0.3. Lit swatches by the paint's own maths (token × the sun tint in full light) are
   unchanged by this: green 1.65 and blue 3.0 ΔE2000 at t 0.25; the most a face can now take moves them by at
   most 0.6. Pink reads 5.0 by the same maths (it was before this round), but Little Rose's door never takes
   the corridor's sun.
6. **Ceiling (could).** Warmed toward `world-vang-shade` by up to 30% within 0.5 m of the long walls, and aged
   with the wash's loang (the lime texture's A, no new fetch).
7. **Doorways (could).** Each open door's lit room: sun-100 under the head to sun-200 at the threshold over the
   door's height, the reveal's shade along the jambs and the floor line, and a 7% Hoa thị dapple of the room's
   own screen on its back wall. Departure: the dapple is on the room seen through the doorway, not on the jamb
   reveal (the frames are one merged draw and take no light from the room on their reveal faces).
8. **Traces (could, staging only).** The chalk lò cò (six rows of 0.34 m squares and a half-round home, blue
   chalk, no numbers, names or letters) on the Tre Xanh bay's plain band, on the side of its door away from
   Little Rose and its whole length at least 1.5 m from Little Rose's bay (else none). It is drawn only when
   the corridor section carries `data-traces`: in staging while `content/draft/world.json` has traces on and
   unapproved (the PROD guard fails the build then), in PROD only once HCWA approves. Never in a still.
Stills re-rendered and re-encoded within `encode-posters.py`'s budgets: `bay-*` and `passage-corridor`
(thresholds, pools, Little Rose's bare threshold, ceiling, wear), `hub-doors-wide`, `hub-door-*`, `door-*`,
`gallery-wall` and `header-green-bamboo` (wear, ceiling). `light-contrast.mjs`: 212 lines, 0 below.
Budgets: corridor chunk 10.40 KB gzip (10.05; limit 10.5), live chunk 16.19 KB (15.98); fewer leaflet
triangles; per frame still no allocation (`uLeafFade` is written only when it changes). GPU on a real phone:
still not measured on device. `npm test` 101/101 (beats: the way out's span and pitch, the link in the panel;
little-rose: leaflets out of frame during its beat, the same threshold for every door, no pool at its door, the
lò cò). The 20-frame strips (passage → corridor → work sheet; VI and EN, 390 and 1440, Tier 1 and 2): 0 console
errors, no frame with two headings above 10%. The matrix (home corridor, Our Work, Green Bamboo; VI/EN; 390/1440;
Tier 1/2, reduced, Tier 0, no JS; 320): 56 views, 0 console errors, 0 overflow. `check:tiles` passes, its stage
overlap included (the EN 390 open item of the focus round is closed: the link now shares the last card's links
row). Tastegate passes on home, VI and EN. Captures: `qa/m3/fix-r1-corridor/`.

**H4 corridor, fix round 2 (9 Nov 2026; corridor engineer).** Maxwell: "the hallway section is so boring its jst a
plain white wall". The round-2 critique's items, each at its source; where the build differs, and what it supersedes
above (fix round 1 items 1, 2, 4 and 5; the richness pass's leaflets and door swing):
1. **The hem (must).** The picture fades to lime-100 over a hem above the work sheet's top, whatever the top-down wash
   is doing, so the sheet never cuts the corridor at full strength. **Departure:** it is drawn in the wash program
   (`renderer.js` `uHem`, `journey.js` `hemSpan`), not as a CSS mask on the canvas fed by a per-frame `--sheet-top`:
   one full-screen pass in a program that already runs there, no style write or mask repaint per frame, and the hem
   and the sweep multiply in one place. The hem is 0.38 of a screen (0.36 under 600 px; D0.5's 24svh / 16svh are the
   floor), on eased stops (0.11 at an eighth, 0.51 at half): at the critique's 24svh the stops moved 40 px band means by
   16-19 levels over the corridor's darkest floor. While the scene is drawn, the clear colour (what shows past an open
   doorway) stays the light, so the hem never switches it at once. Without the world on the stage (the GPU lost mid-walk,
   `world-off`) the bay's still takes the same hem (`stages.css` `--hem-mask`; `motion/stages.js` writes `--sheet-y`, the
   sheet's top in the frame's box, only while the stills show). Seam scan (a frame every 40 px from 1.4 screens before
   the corridor's end to the H2 at 0.3 of the screen; band means of row luma; VI and EN, 390 and 1440, Tier 1 and 2,
   `qa/m3/fix-r2-corridor/evidence/seam.py`): over the lower hem, where the sheet used to cut, no 40 px band moves the
   mean by more than 11.9 levels (it was 42 in 18 px). Over the whole hem the picture's own edges as they fade (the
   dado's line, the yellow pane's foot over the floor under a phone card) still reach 18.1 at 1440 and 25.5 at 390.
   Least non-background share 24.8% (≥ 15%). 0 console errors.
2. **Running text ≥ 7 (must).** `.bays__intro` is ink-900 in both layouts (one rule). Live probe (stage 0.04 / 0.17 /
   0.22 / 0.50 / 0.78; Tier 1 and 2; VI and EN; 390 and 1440; darkest 1%): 304 lines, 0 below; worst running text 7.58
   (the blue-700 links at 1440 Tier 2), `.bays__intro` 12.23, headings 11.55, rail labels 11.37. The probe takes a
   throwaway capture after hiding the text: SwiftShader at Tier 2 sometimes handed the first capture a stale text layer.
3. **Doors (should).** An open door opens into its room, hinged on the jamb nearer the camera's approach, to 96°
   (`sets/corridor.js` `DOOR_OPEN`, `journey.js` `DOOR_DEG`): it turns away from the visitor, the lit room opening from
   the far jamb first, and comes to rest behind the near wall. **Departure:** folded back outward along the wall (166°)
   it was still a green slab beside the lens at 1440, so the critique's alternative is taken; the units' colour stays
   in the frames, the transoms and the doors still shut ahead. `glsl/doors.js` knows the swing (`uDoors.w` ±2), the lit
   room's glow plane is 1.05 m back (the leaf stands in front of it). Backstop: corridor paint and walls nearer the lens
   than 1.4 m along its axis dither away (gone by 0.7 m; discarded, no sort; walls only, as a phone frame's foot is that
   near). Little Rose's door is untouched (its hinge, shut). `little-rose.test.mjs`: over the whole walk, 390 and 1440,
   three orders, no point of any leaf in frame is nearer than 1.5 m. `bay-*` and `passage-corridor` re-rendered.
4. **The pane (should).** It is the wall it stands on: lime-50 with 60% `world-vang-sun` (96% / 92%), token maths ink-900
   ≥ 12.3 and blue-700 links ≥ 8.4 over the dado line or indigo. Between cards it eases back to the door index alone, on
   every width; under 1024 px its foot also follows the shown text (+24 px, then a 96 px eased feather), so the runner and
   the dado fill the screen under the card. The panel's layout box never moves (the camera frames the door above it).
   8 strips (34 frames each): no corridor frame with two headings above 10%.
5. **Coulds.** (a) The dwell beside the card (1440 layout) looks 3° down, eased in over 0.12-0.42 of each beat and out
   over 0.62-0.92, scrubbed: **departure**, a lens shift, not a pitch, so verticals stay vertical (D5.3); the desk bay
   stills take it too. (b) The transom's emblem lands 1.5× its size about its centre (`EMBLEM`), its penumbra kept.
   (c) Lò cò (staging only): 3.5 cm lines, whiter chalk (60% lime-50), dry-brushed, 0.97, 10 cm nearer its door; still
   no numbers, letters or names, never near Little Rose's bay. (d) Leaflets cut on every tier and from the stills
   (`objects/props.js`, `leafFade` and its tests removed; the fans, the shafts and the tamarinds' dapple carry the life).
   (e) Door wear shows an older, lighter coat (`mix(paint, tile-cream, 0.45)`), not wood; the kick zone is 12.5 cm and a
   face takes at most 0.06. ΔE2000 of the most a face spot can take: green 2.48, blue 2.13, pink 1.84 (were 2.11, 1.96,
   1.47 with wood at 0.1).
Budgets: corridor chunk 9.18 KB gzip (10.40), live chunk 16.21 KB (16.19); per frame no allocation (the hem is a reused
`Vector4`). GPU on a real phone: still not measured on device; the hem adds the wash pass over the stage's last
0.38 screen of scroll. `npm test` 103/103; build, `PROD=1` (blocked only on drafts and guards), `build:pages`. Strips,
matrix (72 views, 0 console errors, 0 overflow, plus the stage after a lost GPU context), live probe, seam scan and
tastegate (home, VI and EN, pass): `qa/m3/fix-r2-corridor/`.

**Final recheck, m3 (9 Nov 2026; integration).** Maxwell: "the Linh vuc hoat dong section is so bland and all white
and the hallway section is so boring its jst a plain white wall". The two streams' work is in; this pass took the
cross-stream needs and re-ran every gate on one final build.
1. **A jump straight to the floor (focus need).** If the corridor was not built yet (a jump within a second of load,
   or a slow phone), the canvas held the passage's light at full strength and the sheet's solid top cut it in a
   straight line (`qa/m3/final/evidence/jump-slow-before-after-390.png`, the corridor chunk held back 4 s). The wash
   pass now fades its hem to its own colour (`renderer.js` `uHemC`, `frame(..., hemColor)`; blended as C·A + dst·(1−A),
   A = 1 − v(1 − a), so the walk's own hem, where the hem colour is the wash's, is unchanged). The fallback in
   `live.js` holds the light, but on the way out it turns to the plaster as the walk's wash does (from the stage's
   end, eased, the passage's hole fading with it) and fades to the plaster over the hem above the next sheet's top.
   Same frame after: no step at the sheet's top (row means 229.2 → 229.0 at 390, 227.8 → 227.4 at 1440 Tier 2).
   No allocation per frame (`held` is a reused colour). A corridor that is built but one frame behind a jump still
   shows that frame for about 100 ms (soft, no hard line); not changed.
2. **Our Work area chips (critique r1/r2 could; `src/pages/work.mjs` had no owner).** Each area chip carries one
   26 px gạch tile of its area (`gachStrip([area], "gach-strip chip__tile")`, the home floor's pigments, decoration
   only, never scaled per chip), from the page's one sprite, which the filters now bring (before every program row,
   never inside one a filter may hide). Chosen (JS or `:target`), the chip turns logo blue and its tile keeps its
   pigment inside a 1.5 px lime-50 rim. The components page shows the same chip. The navy screen-block icons are
   gone from the filter (`.chip__icon`, `.block-art__sky` rules removed). Test: `work-pages.test.mjs`.
3. **200% text (focus need, pre-existing).** At 1024-1100 px with the root at 200%, the footer's four columns ran
   146 px (EN) off the screen and the hero's pause toggle 56 px (VI). Both now step down on their container's width in
   rem, so nothing changes at normal text size from 1024 px up (measured, 100%: same positions): the footer columns go
   two by two under 58rem of `.site-footer__cols` (a new wrapper, `container-type: inline-size`); the toggle sits under
   the buttons under 56rem of `.hero__inner`. 200%, 1024/1100/1280/1440, VI and EN, home, Our Work, Contact: no
   sideways scroll (`qa/m3/final/evidence/footer-1024-en-text100-text200.png`).
4. **Gates on the final build** (`qa/m3/final/logs/`): `npm test` 104/104; `npm run build`, `build:pages` (svg masks
   rebased) pass; `PROD=1` blocked only on drafts and guards (59: data-draft, [DRAFT, bracket tokens, the donation
   placeholder, the Little Rose door guard, the traces guard). `light-contrast.mjs` 212 lines, 0 below; live probe
   (Tier 1 and 2, VI and EN, 390 and 1440, stage 0.04/0.17/0.22/0.50/0.78) 304 lines, worst running text 7.58, 0 real
   lines below (3 rail labels read 1.00 on one Tier 2 shot where SwiftShader kept the hidden text layer; the same
   labels measure 11.55 or more in every other shot); seam scan: 40 px bands at the seam 11.9 levels at most, least
   non-background share 24.8%. `check-tiles`, `check-bays`, `check-sun-crisp` (corridor still skipped) pass;
   `check-page` 24/24 run alone (its velocity check misses its 6 s settle under four parallel SwiftShader browsers);
   `check-hero` 2 FAIL, both "the world moves with no input" at 390 Tier 1 (0.37% / 0.16% against 0.6%: the first
   touch cloud falls outside the 1 s window; m2's final run failed the same two at 0%, the run before this one passed
   at 0.97%). Every page VI/EN at 390/1440: 96 views, 0 console errors, 0 4xx, one H1 each, no overflow at 320 (JS on
   and off), no hidden text in the reduced, Tier 0 and no-JS home and Our Work. Strips (24 + 6 back, both sizes, both
   tiers, VI and EN): 0 errors. Mobile LCP median of 3: 2240 / 2160 / 2184 ms (world off / on / default), under 2.5 s,
   over the 1.8 s goal; lcp.cjs's first-10 s long-task rule still fails (285 ms; 210-332 across runs). Tastegate on
   all 18 pages: 0 fail (warnings as in m2: no button in the first desktop screen on inner pages, example.com on the
   components page). Budgets: live chunk 16.31 KB gzip (+0.09), main CSS 28.97 KB (+0.07), corridor 9.18 KB.

**M4 plan (11 Nov 2026; planning).** Maxwell: "finish the rest of the pages; at the top of the contact and news
pages there should be clean 3D scroll animations; make sure there are no more 'page can't be found'", plus his
walls request ("too perfect … not play-doh"). The crawl of the built prototype found 36 dead targets (18 VI, 18
EN, 638 links), all missing pages. `PLAN-M4.md` lists the 18 page pairs to build, who owns which file (pages,
openings, walls) and the acceptance gates. Departures it decides: news cards keep linking to the live posts (no
article bodies exist) and categories filter in-page; the Cầu Hàn EN URL becomes
`/en/our-work/centers/cau-han-project/` (04-SITEMAP, wp/CONTRACT.md); Little Rose's page has no still and no
door, only words and an abstract Hoa thị illustration; the two openings are CSS 3D on prerendered layers driven
by native scroll, not live WebGL; the 404 page loses its "not in the prototype" note once the crawl is clean.

**M4 pages (11 Nov 2026; pages stream, PLAN-M4 sections 3-4).** Every page the menus, footer and pages link to is
built, VI and EN: Who we are, Our story, Team, Partners & sponsors, Policies (`#bao-ve-tre-em`/`#safeguarding`,
`#quyen-rieng-tu`/`#privacy`, `#dieu-khoan`/`#terms`), Impact & Reports (`#bao-cao`/`#reports`), Volunteer, Partner
with us, In-kind gifts (`#tiem-banh`/`#bakery`), Little Rose, Cầu Hàn, the Charity Class and the disability pilot;
the news route is registered on the openings stream's `news.mjs`. `test/links.test.mjs` and `npm run check:links`
(`scripts/check-links.mjs`, base-aware, `check:links:pages` for build:pages) find 0 dead links in VI and EN, staging
and production. Departures and decisions:
1. **Content loading.** `lib/content.mjs` merges `ui.json` and every `ui-*.json` (a key in two files names both files
   and fails the build); `about.json` and `involve-pages.json` load when present; `impact.json` keeps `figures` as
   `content.impact` and its new `page` strings and empty `reports` list as `content.impactPage`.
2. **Routes carry what a template renders** (`unit` for `center()`, `program` for `program()`), and `PARENT` marks the
   hub in the header (`isCurrent`). `center()` and `program()` are generalised; Green Bamboo and Thrive Together keep
   their ids and output. Sections with nothing to show leave themselves out (no scoped figures, no centers, no posts:
   `programPages[*].news` / `centerPages[*].news` list the post URLs a page shows; Green Bamboo and Thrive Together
   keep showing all posts). A completed program's closing band offers Donate and Contact, not "Partner on a program".
3. **Headers.** Who we are, Impact and Cầu Hàn use the lit header with `header-about`, `header-impact` and
   `header-cau-han`; `litHeader()` now shows the shade fill for a still the walls stream has not declared yet instead
   of throwing. The other pages use `sunHead()` (lit-header.mjs), Donate's image-free band with a `time` (morning lime
   for About, calm lime-50 for Policies, sun for Get Involved, `shelter` for Little Rose) whose colour is the first
   stop of the page's day, so header and body are one surface. The M4 CSS is appended to the end of `pages.css` (an
   `@import` at the top lost every tie to the rules it builds on).
4. **Little Rose** (`little-rose.mjs`, its own template): words, the privacy note, what the shelter offers (condensed
   from the current page, draft), how to help (general Donate, the bakery, in-kind) and the office lines. The
   illustration is one Hoa thị block in line with white holes and its holes cast long in white on a lime patch,
   `aria-hidden`. No still, no door, no place, no person, no phone but the office's, never "visit"; it ends without a
   dusk band because the band carries the room's still. `work.json` gives Little Rose no location.
5. **No numbers, no years.** Who we are condenses the aim, vision, mission and values from the current About page
   (draft, with `source`); the 300,000 and "30 years" claims are left out. Our story prints `[năm]`/`[year]` on staging
   and nothing in production. Team shows three `[Tên] · [Vai trò]` places with `data-team-pending` (new
   `render.mjs` blocker) on staging and only the intro and ways in on production. Partners shows the pending strip and,
   on staging only, the funder already named in `work.json` as text linked to its program.
6. **Cầu Hàn** serves families affected by HIV/AIDS: only its `serves` line names that; its activities are general
   (`test/work-pages.test.mjs` checks the rest of the page). Its gallery is the existing `gallery-wall` placeholder.
7. **PROD=1** still fails only on its guards: 143 problems against the baseline 59. The +84 are 28 new production
   pages (14 route pairs, the news stub among them) × the 3 text categories every page already carries in its footer
   (data-draft, [DRAFT, bracket tokens). No new Little Rose door (still 8), no donation, traces or team lines.
8. **QA** (`qa/m4/pages/`): 26 new pages (13 VI, 13 EN) at 320, 390 and 1440 with JS, reduced motion, scripts
   blocked and `?tier=0`: 182 views, one H1 each, no sideways scroll, no text left at opacity 0, 0 console errors
   (besides the refused scripts in the no-JS run), 0 4xx (`matrix.log`). Tastegate on all 26: 0 fail, 1 warn each
   (no button in the first desktop screen, as on the m3 inner pages); the first run's small tap targets (inline phone
   and email links) are fixed (`tastegate-r2/`). Contrast pairs added: warn and gold-700 on lime-100 (4.8:1,
   staging placeholders and the large gold numerals only).
- **needs (lead):** `npm run export:wp`: besides "the committed generated files" assertion, the two guard-export
  assertions in `test/export-wp.test.mjs` are red until it runs, because `render.mjs` gained the `data-team-pending`
  blocker that PLAN-M4 4.3 asks for (the generated `guards.json` and PHP patterns are stale, `wp/` is not ours).
- **needs (walls):** stills `header-about`, `header-impact`, `header-cau-han` (lit headers; pages.css has no per-still
  mask values for them yet, so the default seam applies). **needs (openings):** none.

**M4 pages, fix round 1 (11 Nov 2026; pages stream).** From the critique:
1. **export:wp (must).** About's focus-area tiles print `gachStrip([...], "gach-strip area__tile")`; the golden-case
   list in `scripts/export-wp.mjs` now builds that class too, so `npm run export:wp` no longer throws. Checked in a
   scratch copy of the tree: export:wp succeeds and `npm test` then gives 154 pass, 0 fail. In the live tree the only
   red tests stay the three export-wp ones the lead clears by running export:wp at integration. **WordPress port:**
   `golden.json` will now hold `gach_strip` cases with `gach-strip area__tile`; `HCWA_Theme_Art::gach_strip()` already
   takes any class string, so no PHP change is needed, only the About pattern printing that class.
2. **Headers while their stills are pending (should).** `headerReady(still)` (lit-header.mjs) decides per page: Who we
   are, Impact and Cầu Hàn render `sunHead()` (morning lime, midday sun, morning lime with the unit's paint) until the
   walls stream declares and renders `header-about`, `header-impact`, `header-cau-han`, then swap back to the lit
   header by themselves (and set `lit` for the head script only then). No page opens on the cool shade fill any more.
   Cầu Hàn's band keeps its facts above the title (`sun-head__meta`) and shows its own Vòm cầu block casting warm sun
   as the picture (`block-art`, aria-hidden); its day starts on lime (`day--center-sun`). Supersedes item 3 above for
   these three pages.
3. **Little Rose illustration (should).** Any sun band with `art` gets `sun-head--art`: on desktop the title and intro
   take the left column and the illustration the whole right column (320-460 px); on phones it is the full gutter
   width (square). Still aria-hidden, untinted white daylight, no door, no place.
4. **Coulds.** Team's three pending places and the pending partner strips (Partners, Partner with us; the home strip
   is untouched) are paper sheets with a shadow like the News notices, no dashed outline; the draft marking stays in
   `data-team-pending` / `data-partners-pending` and the reviewer's draft outlines. The unit band (sun band and lit
   header) is full for 3 px then feathers down over about 15 px and fades in over 12 px at its left end. Our story
   carries a staging-only `[Order of the steps: HCWA to confirm along with the years]` note (`about.json`
   `story.orderNote` with a `source`), and the EN title wraps "Cầu Muối" in `lang="vi"` (`steps[].viName`). The Charity
   Class's two activities with no source are now bracket tokens ("[Subjects taught, HCWA to confirm]", "[Who teaches
   and tutors, HCWA to confirm]") with a `source` note saying why.
5. **Checks.** npm test 151 pass, 3 fail (the export-wp three, as planned); build, build:pages, check:links and
   check:links:pages 0 dead links (46 pages). PROD=1 still fails only on its guards, 143 problems, unchanged (the order
   note is staging only). Captures in `qa/m4/pages/fix1/` (390 and 1440, JS, reduced motion, no JS; `?tier=0` probe: one
   H1, no sideways scroll, no console errors). Tastegate on 14 pages (About, Impact, Cầu Hàn, Little Rose VI and EN,
   Team, Partners, Partner with us, Our story EN, Charity Class, Green Bamboo): 0 fail, 1 warn each
   (`qa/m4/pages/tastegate-fix1b/`).
- **needs (lead):** run `npm run export:wp` at integration (clears the three export-wp tests, now with the area__tile
  golden cases). **needs (walls):** unchanged: `header-about`, `header-impact`, `header-cau-han`; the pages pick them up
  with no code change once they are in `STILLS` and rendered.

**M4 openings (11 Nov 2026; openings stream, PLAN-M4 sections 4.13, 4.14 and 5).** Contact and News each open with a
3D scroll stage that follows the native scroll, and the News hub exists (`/tin-tuc/`, `/en/news/`). Shared parts:
`partials/scroll3d.mjs` (stage wrapper, `TRACKS`, still geometry), `motion/scroll3d.js` (1.1 KB gzip, imports
nothing), `styles/openings.css` (imported after `pages.css`). A stage becomes 3D only under `html.stages` with motion
on, which `head-inline.js` sets before first paint, so nothing moves after paint and no JS, reduced motion, pause and
`?tier=0` keep the rest frame. Progress runs from scroll 0 to where the stage lets go (`--s3d-end`, measured by the
driver, kept fresh by a ResizeObserver); four tracks (`--s3d-p` linear, `--s3d-cam` in-out to 0.86, `--s3d-sun`
ease-sun to 0.86, `--s3d-open` 0.55-0.92) come from CSS scroll-driven keyframes, or from the driver where
`animation-timeline` is unsupported (one passive listener, one rAF, asleep off screen). `openings.test.mjs` pins CSS,
JS and `TRACKS` together. Departures and findings:
- **`animation-duration: auto` is set explicitly** on the stage: with four animations in one shorthand the built CSS
  came out with `0s` durations, so every track finished at once (progress stuck at 1). The existing single-animation
  `view()` rules (`lit-drift`) compute `auto` and are fine.
- **Contact.** The lit header is kept as the rest frame (no layout change at rest except a "Skip to the contact form"
  link). On the stage the still fills the first screen and stays pinned for 76svh (32svh on phones, where the words
  come first and the still pins under them): a dolly toward the screen (scale up to 1.55), a turn of up to 10° in a
  1400 px perspective that evens the blocks out as if the camera came round, and a truck toward the words' side once
  they have scrolled off (Tier 2 also slides the canopy's flecks with the sun). Placement comes from the still's own
  data (`HEADER_TEXT`, the crop's aspect), never pixels. The page rises over the stage as a long sun-100 fog (the day's
  first colour, eased stops) so the office and the form arrive while the camera still moves and no edge ever shows.
  The plan's "street side opens out" flood was tried as a sun-100 bloom and dropped: it read as a washed-out photo,
  not light, and left a dead stretch; the rising day carries the hand-off instead.
- **News.** Departure from 5.2: the chips sit in the first screen above the notices (not after the opening), because
  the notices are the archive itself (one list, no copy) and a filter belongs above what it filters. The wall plane is
  pinned and recedes (scale 1.28 → 1), the board starts 80 px nearer and tilted 7° and settles flat (56 px, 5° on
  phones), each notice turns from at most 9° to flat on its own `view()` timeline and a soft quatrefoil of sun crosses
  it. Notices are not pinned, so any number of posts reads at native speed. Cards link to the live posts with
  `ui.externalPost`; no images, embeds, article or category URLs. Chips reuse `ui/filters.js` (`data-hub`,
  `data-area`) and CSS `:target` without JS; Stories shows its empty note. The featured-story placeholder is staging
  only (`data-story-placeholder`). The plaster is an inline SVG noise (≤ 6% stains) and the Hoa thị light is an
  inline data-URI mask: a fetched mask made the first notice the page's late LCP (2.47 s); inline it is 2.0 s.
- **404:** the "not part of the prototype yet" note is gone (the crawl of `build:pages` finds 0 dead targets in 46
  pages).
- **Results.** `check-openings.cjs` (16-frame strips at 390 and 1440, VI and EN, contrast sampled from the real pixels
  behind every visible word at every frame, one H1, H1 moves only with the scroll; rest frames under reduced motion,
  `?tier=0` and no JS): 0 FAIL; strips in `qa/m4/openings/final/`. Driver fallback checked with `CSS.supports` forced
  false (same track values). Mobile LCP (lcp.cjs, median of 3): Contact 2.08 s (default 2.08, Tier 1 2.08), News 2.02 s
  (Tier 0 1.95, Tier 1 1.99), CLS 0.000; the first-10-s long task is 139-294 ms on News as on every page (open issue
  since m3). Tastegate on all four pages: 0 fail, 1 warn each (no button in the first desktop screen, as on every inner
  page). `openings.css` adds about 3.7 KB gzip to the main CSS. PROD: News adds 6 blocker lines (2 pages × data-draft,
  [DRAFT, bracket token from the shared parts), nothing else; Contact and the 404 unchanged.
- **needs (walls):** a sharper Contact stage: `header-contact` desk layers at 2400 w (the full-height stage shows the
  1600 w still at 1.5× and the dolly to 1.55× on top, so the screen goes soft); and, if the real plaster map lands, it
  can replace News's `--wall-tex` (opacity ≤ 6%). No `header-news` still is needed.
- **needs (pages):** `.gap__proto` in `pages.css` is now unused; `ui/filters.js` `say()` could announce a news count
  (it only knows centers and programs, so News has no live region yet).
- **needs (lead):** `wp/themes/hcwa-2026/404.php` still prints the `notInPrototype` note (wp/ is not ours).
- **Fix round (11 Nov, critic issues for openings).** What changed and why:
  - *Camera easing.* `--s3d-cam` was `cubic-bezier(0.77, 0, 0.175, 1)`: almost still for the first quarter, then
    about 95 px of phone scroll did half the move (peak rate 4.8x the mean). It is now `(0.3, 0.1, 0.6, 1)`: moving
    from the first pixel, peak 1.49x the mean, still done at 0.86 (the last 14% holds). `--s3d-sun` went from
    `(0.16, 1, 0.3, 1)` (peak 6.2x) to a soft ease-out `(0.3, 0.45, 0.45, 1)` (peak 1.8x); the notices' turn, shadow
    and sun keyframes use the same two curves. Departure from PLAN-M4 5 ("camera on `--ease-in-out`"): the house
    in-out is too steep once it is mapped onto scroll. `openings.test.mjs` now fails any track or notice curve
    whose peak rate passes 1.6x its mean.
  - *Where a stage lets go.* The driver measured "wrapper bottom = screen bottom", which is only right for a pinned
    layer as tall as the screen. Each stage now names its pinned layer (`data-s3d-pin`) and `--s3d-end` is the
    wrapper's bottom minus that layer's sticky top and height. This also found that News's wall plane had never
    pinned: `inset: auto` came after `top` and reset it, so the wall scrolled with the page. Fixed.
  - *Contact: no more blown-up still.* The stage is no longer the full screen: 62svh on desktop (about the still's
    own 1x at 1440) and 48svh on phones, so the form's title is always on screen below it. The dolly is 1.16x on
    desktop and 1.12x on phones (was 1.55 and 1.42); the turn is 6° (4° on phones), the truck 4cqw. The run is 36svh
    on desktop and 12svh on phones (was 76 and 32). `check-openings.cjs` now reports the still's css px per source
    px at the middle of the picture: at most 1.22 at 1440 (was 3.0 at 0.8 of the run) and 0.63 at 390 (1.9 device
    px per source px at 3x, was 4.8). The depth is the turn and the truck (near and far columns separate) and the
    canopy's flecks softening with the sun; the flecks no longer slide (any shift doubles the quatrefoils, which
    are light through the screen's own holes) and the room's light stays registered (moving it bared a grey strip
    of unlit wall at the screen's shadow line).
  - *Contact: hand-off.* The page rises over the stage from the first pixel of scroll (`--pull` = the run), its fog
    is nearly whole a third of the way down, and the form sits 0.36 of the pull into it (was 0.8): every strip
    frame at 390 and 1440 has text on screen (new check). The desktop stage now feathers on three sides
    (`mask-composite: intersect`): into the header (48 px), the plain wall and the day. Measured with text hidden,
    the brightness step across the header's edge over the stage's columns is 2.7-4.2 (mean) while pinned; after
    the stage lets go the still's remaining top passes under the header's own 24 px foot like any picture does.
  - *News: notices stay in the gutter and read flat.* The board no longer moves toward the camera (its translateZ
    pushed the outer cards 18-111 px past the gutter) and nothing turns about the vertical axis (the shear on the
    titles). Each notice now hangs from its pin: it arrives with its foot lifted at most 9° in its own perspective
    (scaled 0.94 so its widest edge stays in the column), settles flat as it comes fully into view
    (`entry 0% → contain 10%`), and the shadow it casts on the wall shortens and melts into the paper (a blurred
    pseudo-element moving on transform and opacity only). Notices already in the first screen are flat at load.
    `check-openings.cjs` fails a notice outside the board's box at any frame, or turned more than 3° in the first
    screen.
  - *News: the wall is no longer play-doh.* Until the walls stream's Higgsfield plaster map lands, the wall is three
    inline SVG layers: grit and pores on a 320 px tile, cloudy lime wash on a 487 px tile (no common period on any
    screen; the old single 320 px tile showed seams every 410 px at 1.28x), and one untiled drawing of the wall's
    history (rain streaks from the top, two patched repairs, a damp stain, hairline cracks, scratches through the
    wash, pits). Static, light (grain and wash 6% or less) and checked at 7:1 under running text in every strip
    frame. The pinned plane recedes 1.22x to 1x; its cracks and patches are what make that read. `--wall-tex` and
    `--wall-tex-size` take the real map. Mobile LCP may now land on the marks drawing (inline, painted with the
    first frame): News 2.06 s median, Contact 2.08 s, CLS 0.
  - *Featured story placeholder (News only).* Its edges feather into the day (two-axis mask) and its cool render
    takes the day's hue (a `color` blend at 0.5), so it no longer sits as a blue-grey box on the warm wall.
  - *Not done:* a dado band (05-DESIGN keeps text off the bare dado, and on a pinned plane it would cross every line
    of text); the Contact still's own wear and the 2400 w desk layers (stills are the walls stream's).
- **needs (walls), fix round:** (1) `header-contact` re-rendered with the breeze-block map (pores, flaking lime wash,
  chipped edges): the screen is still uniform and clean; (2) its desk layers at 2400 w (the stage now shows the
  1600 w layers at 1.05x at rest and 1.22x at the end, which is soft on a 2x screen); (3) the generated lime
  plaster map as a WebP at 1024 px or more under `public/textures/`, which News takes through `--wall-tex` /
  `--wall-tex-size` at an opacity of 6% or less (openings swaps it in, one line).
- **needs (pages), fix round:** the Impact page's featured-story placeholder could take the same feather and warm
  blend as News's (`.news-feature .feature__wall` in `openings.css`).

**M4 final recheck (11 Nov 2026).** Cross-stream needs closed, then every gate rerun on the final tree.
- **Closed.** (1) Impact's featured-story placeholder now feathers into the day on all four edges and takes the warm
  hue (`.change__story .feature__wall` in `pages.css`, the same rule as News's). (2) News announces a filter
  choice in a polite live region, as Our Work does: "Đang hiện 1 tin" / "Showing 1 post" (new strings
  `filterShowingPosts`, `countPost`, `countPosts` in `ui-openings.json`, VI and EN). `ui/filters.js` `say()` now
  counts each kind (centers, programs, posts) only when the region carries its words, so Our Work's message is
  unchanged.
- **Left as is, on purpose.** `.gap__proto` stays in `pages.css`: the WordPress 404 template still prints the old
  note with that class, and the theme's CSS is generated from these files.
- **Checks.** `npm test` 155 pass, 3 fail (the three `export-wp.test` assertions; in a scratch copy of this tree
  `npm run export:wp` succeeds and then all 158 pass). `build`, `build:pages`, `check:links`, `check:links:pages`:
  46 pages, 0 dead. `PROD=1`: only its guards, 143 problems (44 × 3 draft lines, 8 Little Rose door, 2 donation, 1
  traces), the same as before; the new strings are UI words, not drafts. `qa/m4/crawl.mjs` on both builds: 0 dead
  targets, 0 "not in the prototype" notices, 8 external links. Browser matrix (`qa/m4/final/matrix.cjs`): all 46
  pages at 390 and 1440, walked top to bottom: 0 console or page errors, 0 failed requests, 0 sideways scroll, one H1
  each, and 0 dead links or anchors among the 6,802 links in the live DOM. `check-openings.cjs` (16 frames, VI and
  EN, 390 and 1440, plus the rest frames): all pass; strips in `qa/m4/final/strips/`. The new live region was
  probed: VI and EN say the right count after a chip and after "All". Tastegate on the 30 new pages and both 404s: 0
  fail (1 warn each on the 30, no button in the first desktop screen). The Python Playwright package on this box
  was upgraded today and looks for a browser build that is not installed, so tastegate ran through a small wrapper
  that points it at the installed headless shell (the gate itself is unchanged). `light-contrast`: the same 3 home
  lines at 390 fail (6.7 < 7); everything else passes. Mobile LCP was not re-measured (the openings did not change
  in this step; the fix round measured Contact 2.08 s and News 2.06 s).
- **Still open.** Maxwell's walls request: no Higgsfield texture exists yet (`public/textures/` is missing; the walls
  stream has not run and needs the budget OK in PLAN-M4 6.1), so no still was re-rendered and News still uses its
  interim procedural plaster. `header-about`, `header-impact`, `header-cau-han` and the sharper `header-contact`
  are waiting on the same stream. **needs (lead):** `npm run export:wp` at integration; `wp/themes/hcwa-2026/404.php`
  still prints the `notInPrototype` note; the 3 home contrast lines.

**M5 walls: the Blender materials in the live world (11 Nov 2026).** Maxwell: "the walls look too perfect, like
play-doh; they need character, more realistic: scratches, a real wall". `blender/walls.py` (Cycles, Blender 4.2) bakes
five seamless 1024 px materials; `scripts/encode-walls.py` writes them to `public/textures/` and `src/world/walls-data.js`.
- **What ships.** Per material an albedo and a tangent-space normal WebP (roughness dropped: the analytic light has no
  specular term), 512 px for Tier 1 and 1024 px for Tier 2. The normal is low-passed 0.8 px at 1024 before encoding
  (the 2 mm grit it loses is sub-pixel at every camera distance and was half of every file). Tier 1 251 KB, Tier 2
  689 KB (budgets 300 / 800 KB, held by `test/walls.test.mjs`, which reads the files).
- **Loading.** `live.js` asks for them in an idle slice after the handoff (after `world-live`), never before the
  first frame, so neither LCP nor the handoff moves; until they arrive, or if any fails, the procedural walls stay. A
  module worker (`walls-worker.js`, main-thread fallback) fetches, decodes and packs each material into one RGBA
  texture (`walls-pack.js`: normal xy, luminance relief, chip mask), one upload a slice; the maps then fade in over
  700 ms of unpaused time (`uWallK`; a pause holds the fade). Context loss drops them for good (procedural after a
  restore). Once they are in, the live gate measures again from where it stood (t2 or t1, never from lite).
- **Departure: tone relief is luminance, not per channel.** The task asked for albedo / mean × token. The shaders
  use the albedo's luminance over its mean × the token (darker freely, lighter by half and never past +5%, so a sun
  patch keeps its headroom), and a chip mask where the paint is gone: the dado's chips take the ceiling's warm
  plaster targets, a door's chips the wood / older coat its wear rule already had. Reason: the palette holds exactly
  (no map hue drifts the yellow wash or the unit paints), and one RGBA texture per material instead of two.
- **Departure: GPU memory on Tier 2.** 1024 px packed is ~26.7 MB (5 × 4 MB + mips); with the Tier 2 drawing buffer
  (~33 MB at 1440 × 900, dpr 1.5) that passes D6.7's 48 MB. Tier 1 is ~6.7 MB (inside 24 MB with its ~6 MB buffer).
  One line (`WALL_SIZE` in `walls.js`) puts Tier 2 on the 512 px maps (~6.7 MB) if the lead holds the budget.
- **Shading** (`glsl/walls.js`). Walls are axis-aligned: the tangent basis is the surface's own uv (x walls: z, y; z
  walls: x, y; floors and ceilings: x, z). The normal shades relief on the light's own term (`wallSun`: how much more
  or less a texel faces the light than its wall, 0.55 to 1.05, so cracks and chips catch a grazing sun) and, softer,
  against the open side's fill in shade (`wallFill`, 0.7 to 1.04). Masks, caps and patch targets are untouched, so the
  light's geometry is exactly as before (`check-sun-crisp` is pure geometry and passes unchanged). Scale: 2 m a map on
  walls, 1 m on paint and on breeze blocks (each block its own random corner of the map). Repetition: Tier 2 blends a
  second sample turned 34° at 0.73 the scale where the lime texture's broad mottle says; on both tiers the cracks,
  stains and chips read fully only where the wash's metre-wide loang has aged the wall (a third elsewhere), the
  ceilings take 30%, and behind body text (a capped rect, caps.js) the relief drops to a fifth, so the hero intro's
  darkest pixels stay where its bar was measured.
  - Hero room (`surface.js`): plaster on walls and ceiling; the terrazzo floor unchanged.
  - Corridor (`still-surface.js` house): limewash above the dado (at 55% tone: its crack network is the strongest map;
    the house's loang stays at half strength over it), mint dado below with its chips, plaster on the ceiling; the gạch
    bông, thresholds and cream tiles unchanged. The shutters room (non-house): plaster on walls and ceiling.
  - Doors, frames, shutters (`paint.js`): the neutral paint map tinted by the unit's paint, on a leaf in hinge space
    (its brush streaks swing with it); its normal through a derivative frame (leaves turn). Wood or the older coat
    shows through a chip only where the existing wear masks allow (edges, kick zone, handle), so faces stay clean
    (critique r1). Little Rose's door: same rules at its 0.3 wear, still shut; no colour or light added near its bay.
  - Breeze blocks: the room's screen face (Tier 1 SDF and Tier 2 instanced, `screen.js`) and every lattice panel
    (`lattice.js`: the corridor's screen, the transoms) take the concrete's relief, the face tokens holding.
  - Tier 1 keeps the normal map: the SwiftShader proxy (`qa/m5/integrate/perf.cjs`, median of 12-16 redraws with a
    pixel read-back, walls off/on alternated; not a GPU measurement) gave 390 × 844 Tier 1 hero +8.8%, corridor bay 1
    +1.5%, shutters +14.5%; 1440 Tier 1 +11.5% / +4.8% / +8.5%; 1440 Tier 2 +16.7% / +7.4% / +2.7%. The richness pass
    took +13-14% on the same proxy; the runtime gate re-measures with the maps in.
- **Code.** Lazy JS gzip +4.3 KB (live +0.86, common +0.82, corridor +0.27, the three chunk +2.33) and the 1.6 KB
  worker; nothing on the first-paint path (`main.js`, `world/index.js`, posters) imports the walls (the test checks).
  `?debug` gets `__hcwaWorld.wallK(v)` for before/after captures.
- **Stills and posters are unchanged.** They never load the maps; with `uWallK` 0 every new term is exactly 1 or the
  old value, so a re-render gives the same pixels. Re-rendering stills with the maps is a separate step (needs below).
- **Not done:** the News page's `--wall-tex` (the openings stream can point it at `textures/plaster-1024.webp`).
- **Checks** (captures and logs in `qa/m5/integrate/`; before = this tree without the walls, built and served side by
  side). `npm test` 164 pass (6 new in `walls.test.mjs`). `build`, `build:pages` (textures resolve under
  `/hcwa-rebuild/`), `check-links` on both: 46 pages, 0 dead. `PROD=1`: only its guards, 143 problems, as before.
  `check-sun-crisp` all pass. `check-bays` and `check-tiles` pass. `check-hero`: its matrix FAIL lines are identical to
  the before build's (27; the 390 intro over the stills, 6.6-6.98 < 7, comes from this morning's re-rendered hero
  posters, which this pass does not touch; the live Tier 1 intro is 6.98 before and after), plus the known flaky "world
  moves with no input" at 390 Tier 1 and the six short-phone extras, which also read the stills (tier 0).
  `light-contrast` (over the stills): the same 3 home lines at 390 (6.7 < 7) as the M4 recheck. Live probe over the
  world with the maps faded in (`qa/m5/integrate/livecontrast.cjs`, the m3 method plus the hero at rest; VI/EN, 390/1440,
  Tier 1/2): 0 lines newly below their bar; hero intro at 390 6.97 (before 6.95) and 7.03 (7.01), hero H1 ≥ 6.97, worst
  corridor running text 7.64 (7.63). Mobile LCP (`lcp.cjs`, median of 3, before / after on the same machine): world
  off 2,312 / 2,316 ms, world on 2,304 / 2,284 ms, default 2,324 / 2,292 ms; the world chunk is requested after LCP and
  the maps after the handoff, so they cannot move it (the first-10 s long-task rule fails on both builds). Before/after
  crops: `cmp-<lang>-<w>-t<tier>-<place>.png` and `sheet-{before,after}-*.png`. No new hard line: the maps are seamless,
  the age mask is metres wide, a block's own corner of the concrete changes only inside its mortar joint.
- **needs (lead):** (1) the Tier 2 GPU memory call above (keep 1024 px and raise D6.7's 48 MB, or one line to 512 px);
  (2) 05-DESIGN 6.11 still says Blender is not available here: it is (4.2), and the walls now use it; (3) the hero,
  bay and header stills could be re-rendered with the maps for a matching handoff (the world fades the maps in over
  700 ms after the handoff, so nothing pops today); that needs `still.html`/`poster.html` to load them and an encode
  budget check; (4) the hero intro at 390 over the stills (6.6-6.98) predates this pass.

**M6 walls finisher (11 Nov 2026).** Maxwell (11 Nov): "the walls are so bland". The M5 walls pass stopped midway (7
tests red); this pass makes it coherent, adds the three M4 header stills and fixes the News and Contact openings' walls.
- **Stills and posters.** The named stills (`still.js`: inner headers, doors, bays) render with the Blender maps at
  full strength (Tier 2 maps, PLAN-M4 6.4). The hero posters (`poster.js`) do **not**: they are the LCP and hand off to
  the live room, which starts on its procedural walls and fades the maps in after the handoff; a poster with the maps
  would pop back at the handoff. `test/walls.test.mjs` now walks the static import graph from `main.js` and
  `world/index.js` (any module, not two named files), keeps `poster.js` out of the maps, and checks that nothing the
  site runs imports the still entry. **Departure:** the stopped pass's test also listed `still.js`; that contradicted
  6.4 (and the M5 task #203 it had done), so the clause is replaced by the stronger graph check, not dropped.
- **Cracks were stamps.** A Blender map has one crack network a tile; at 2 m a tile it repeated on every header and
  read as stickers (most visible under a raking sun, through the normal map's lips, not the tone). Now: `walls-pack.js`
  packs a crack mask in A for plaster, lime wash and concrete (the crack's core plus the 8 texels of lips its normal
  gives it, measured on the bake; wraps); `glsl/walls.js` `wallCrackGate()` lets a repeat keep its cracks only where
  the wall is well aged (`wallCrack(age)`, age 0.82 → 1) **and** on a random draw per 2 m tile (a value noise whose
  lattice sits on tile centres, so a crack takes its own tile's draw and is never cut; Tier 2's rotated sample draws on
  its own tiles): about one repeat in three. `wallRelM()`/`wallCrackN()` gate tone and relief under the mask together.
  The dado (A = chips) finds its cracks by tone. Paint, blocks: unchanged (`wallRel`). Live check (SwiftShader, both
  tiers): maps load after the handoff (`[world] walls · 1024 px · 689 KB` / `512 px · 251 KB`), no shader errors.
- **Header stills** (`still-shots.js`, `sets/stills.js`; PLAN-M4 4.1, 4.6, 4.10), rendered with the maps:
  `header-about` (the house front at 06:30, the low sun raking the plaster, the screen right; **two layers**: its
  screen is lit from the street side, so the face's light is the room light; the lightscreen layer could not meet its
  8 KB at any size with the concrete's relief, and nothing comes through toward the camera anyway; canopy moved to the
  screen's far end only; phone crop pulled back so the screen sits in the upper two thirds and the breadcrumb stays on
  plaster), `header-impact` (noon down the light well, no numbers), `header-cau-han` (`centerDoor` with the unit's
  rules: blue door open, Vòm cầu transom light; no Little Rose door, `stills.json` says so). The pages switch to them
  by themselves (`headerReady`). Budgets: all inside (test/stills.test.mjs).
- **News wall** (`openings.css`, `scripts/encode-wall-css.py`). The SVG feTurbulence grain and wash are gone: the
  Blender plaster (grit, pores, trowel ridges, mottle, relief lit from the upper right) at 640 px and the Blender lime
  wash's broad loang (low-passed, no grit) at 1037 px, each one hard-light map over the lime, **with their cracks masked
  and filled** (a tiled crack is a stamp); the cracks, patches and streaks stay drawn once (`--wall-marks`). Found and
  fixed: the hard-light maps had been blending with nothing (the backdrop's mask makes its own group) and showed as flat
  grey; the plane now carries the lime. Bounds checked by the encoder on both layers together: at most 14% darker
  (ink-900 on the darkest 11.3:1), 7% lighter. Bytes: 103 + 6 KB desktop, 22 + 3 KB phones. `check-openings` (news):
  pass. LCP 2,040 ms (the inline marks, = FCP; the maps are never the LCP).
- **Contact sharp** (`still-shots.js` per-still `widths`; `cropsOf()` merges them). The desk crop now has 2400 and
  3840 w, the phone crop 1440 w, each at the 1600 w (960 w) file's bytes: at 1440 × 900 on a 2x screen the browser takes
  3840 w and draws about 1.0 device px a texel at the end of the dolly (was 2.4); 1920 at 1x takes 2400 w; a 390 phone
  at 3x takes 1440 w (1.3, was 1.9) for the same 22 KB. `encode-posters.py`: past their kind's usual largest width the
  light layers may step down to about the usual file's pixels (tried only after every usual step, so no other still
  changes). The opening's CSS and module are untouched (the pages stream reworks the Contact header next).
- **Checks.** `npm test` 164 pass; `build`, `build:pages` (textures resolve under `/hcwa-rebuild/`), `check-links` 46
  pages 0 dead; `PROD=1` blocked by its existing guards only (143, as before); `export:wp`, `php wp/tests/run.php` 8228
  pass (`runtime-scripts-test.php`: Cầu Hàn now seeds its still; the placeholder case moved to Charity Class),
  `wp-static-check` 0 errors. `light-contrast` (now also About, Impact, Cầu Hàn VI/EN): only the 3 known home lines at
  390. Mobile LCP (median of 3): home 2,336, Contact 2,104, News 2,040, Cầu Hàn 2,056, About 2,084, Impact 2,072 ms.
  `check-openings` contact and news: pass. Captures in `qa/m6/walls/`.
- **needs (lead / later phase):** (1) the re-render-all phase: every other still still has procedural walls (only the
  three new headers and Contact have the maps); when they are re-rendered, the lit layers of block-heavy frames may need
  the same treatment as `header-about` (the concrete relief costs bytes in a light layer); (2) Contact at 1440 × 900 on a
  1x screen still takes 1600 w (1.2x at the end of the dolly; a `sizes` per still would fix it, but the PHP builder has no
  per-still sizes yet); (3) the M5 needs (1), (2) and (4) stand.

### M6 world and flow (11 Nov 2026, Maxwell: "it just runs past everything", "the gradient into the wall", "the walls are so bland", "the white space between green bamboo little rose and centers is too huge")
- **Pacing** (`motion/scroll.js`, new `motion/smooth-wheel.js`, `world/live.js`, `world/journey.js`, `motion/stages.js`,
  `motion/passage.js`, `motion/shutters.js`, `stages.css`; `test/pacing.test.mjs`). Lenis lerp 0.12 → 0.07 (about 0.5 s to
  settle), `wheelMultiplier` 1, `syncTouch` false, no snap: the same native distance per wheel tick, a longer glide. Every
  page without the motion layer (Contact, News, inner pages) now gets the same glide from a Lenis-only chunk, fine pointers
  and motion on only (departure: openings.test's "no Lenis on these pages" now means no Lenis in their driver; the wheel
  chunk is separate). The world camera follows the scroll on an exact critically damped spring (ω 9/s), so a trackpad
  fling glides through a door; the text, cards, washes and hems keep reading the scroll itself. Measured scroll per beat
  (before → after): 1440 passage flight 1620 → 1800 px, text-less run 454 → under 290 px (the wash rises 0.06-0.16 and
  the statement holds 0.16-0.78, 1116 px); corridor 378 → 612 px a door, the card's still plateau 212 → 392 px, the
  stage header 108 → 275 px; shutters stage 1620 → 1800 px with a new fully open "look out" hold of 180 px (leaves
  0.30-0.66, blend 0.76-0.92). 390: passage 1266 → 1435 px, corridor 284 → 472 px a door (plateau 159 → 302 px),
  shutters 1266 → 1435 px. `cardOut` 0.97 → 0.98 (with the shorter leave, Little Rose's card had gone while its door
  was still at 0.79 of the frame). Departures from the art director's numbers: passage `read` is [0.18, 0.74] (not
  [0.12, 0.74]: the words would be lit before they rise in); `approach` is [0, 0.16] so the camera reaches the block as
  the wash fills; `WALK_OUT.pitch` stays 14° (0.6.7 is binding; the plan asked 6°).
- **Corridor rail to Centers** (`stages.css`, `motion/stages.js`). On desktop the door index and the stage header (or the
  card) are one block centred in the panel, the index 24 px above the H2 (it was about 260 px of empty pane). The pane's
  mask feathers 24svh top and bottom and 200 px toward the door, each on 8 smoothstep stops (it was 96 px and a linear 22%,
  which read as a horizontal and a vertical line). The link-fit check measures against the panel's foot.
- **Seams** (`materials/still-surface.js`, `materials/paint.js`, `renderer.js`, `stages.css`, `components.css`).
  The near-lens backstop no longer discards on an interleaved-noise threshold (the stipple where the screen meets the
  dado): a wall or door nearer than 1.4 m settles smoothly into its own flat shade. The hem's three-piece ramp is a
  smoothstep in the shader and on 8 stops in the stills' `--hem-mask`. The passage's band is an oklab ramp on 8 stops,
  its exit still's masks eased; the bay views, the phone glass and S3's frame feather on eased stops. The site header's
  skirt is 48 px with its own 8 px blur, eased (the blur used to end in a line at the bar's foot: rows 79/84 flagged in
  14 home frames before, none after). The dusk band and the footer sky are eased per stretch in oklab; the footer's hole
  row varies 0.35-1 along an irregular ramp. New gate `scripts/seam-scan.py` (row/column luminance steps > 6 levels over
  > 40% of the frame, and a dither block finder): home strips at 25svh, Tier 1, 1440 rows 61 → 49 lines, 390 rows 242 →
  230 (most of what remains is 3D geometry, the news image's edge, the draft partner box and the footer); captures in
  `qa/m6/world/{before,after}/`.
- **Wall posters** (`world/wall-posters-data.js`, `scripts/wall-posters.mjs`, `scripts/encode-wall-posters.py`,
  `world/objects/wall-poster.js`; `test/wall-posters.test.mjs`). Eight original screen prints of everyday Saigon life
  (Xuân hoa mai, Cà phê phin, a scooter under tamarind, Sen, Mưa on shophouse shutters, a kite over rice terraces, a
  bánh mì cart, a shophouse shutter): flat token inks, halftone, a second plate off register, aged cream paper (sun
  fade, stains, edge grime, creases), tape and a soft wall shadow. No stars, flags, people's faces, children, brands,
  dates or red/pink (0% pink, 0% red measured). Atlases 512 / 1024 / 2048 px at 33.5 / 98.4 / 238 KB (budgets 34 / 100 /
  240), loaded in idle time after the world is live, faded in like the wall maps. Hung: the corridor's end wall
  (the scooter: every frame of the walk looks toward it), Green Bamboo's bay (the kite) and Cầu Hàn's far side (Sen), and
  S3's window wall (Xuân, Cà phê); lit by the set's own lights. Little Rose rule tested over every bay order: no print
  within 4.75 m of its door. **Departure:** no poster in the hero room: its H1 covers the back wall in every bucket and
  the other walls are out of frame, so any print would sit under text (the hero stills are unchanged). The pho-mua and
  the two Contact prints (xe-banh-mi, cua-hieu) exist as single prints (`public/textures/prints/`) for the pages'
  stills. **HCWA approves the set before production.**
- **Stills re-rendered:** `bay-green-bamboo`, `bay-cau-han`, `passage-corridor`, `shutters-closed/-louvers/-open`
  (posters, smooth near fade). `bay-little-rose`, the hub doors and the hero posters are unchanged.
- **Checks:** `npm test` 195 pass; `build`, `build:pages` pass; `PROD=1` blocked by its existing guards only (143);
  `export:wp`, `php wp/tests/run.php` 8288 pass, `wp-static-check` 0 errors. `check-bays` pass, `check-tiles` pass,
  `check-sun-crisp` pass, `light-contrast` only the 3 known home lines at 390 (6.7 < 7). `check-hero`: the known 390
  intro contrast (6.6-7.0 < 7, predates M6) and the known Tier 1 ambient-motion timing; nothing new.
- **needs:** (1) pages: the work floor's tiles end in a straight line at the floor's foot (pages.css `.floor`, gradient
  audit B5); (2) the header skirt over the dusk band reads as a pale band (a dark-mode skirt over `.dusk` would fix it);
  (3) HCWA approval of the poster set; (4) the Lenis glide and the camera spring should be tried by Maxwell on a real
  trackpad.

### M6 pages: Contact, Get Involved, Little Rose (11 Nov 2026, Maxwell: "that contact page has an ugly line and also no animation when scrolling down", "the get involved is the worst out of the bunch", "the little rose warm shelter page looks so bland and boring")
- **Contact, "Qua cổng vào văn phòng".** The hard vertical line was the bông gió screen's silhouette in the
  `header-contact` still standing on plain plaster (x ≈ 862 at 1440 on the stage, ≈ 757 at rest), plus the shade-100
  fill of `.lit`. Fixed at its source: the still's left mask ramp now sits on the subject's edge (16% of the frame,
  `--s3d-subject - 10cqw → + 6cqw`, on the still so it moves with it; at rest `--x0: 44%; --x1: 60%`), and the still's
  box is lime-100. The stage is now a layered 2.5D scene on every stage tier (Tier 1 included): the still (L0, 1 → 1.12,
  4° round on desktop), a courtyard of gạch runner and two plants (L1), the gate's two blue iron leaves with bông gió
  openwork between lime-washed piers (L2, opening 20° → 80° on `--s3d-gate`, then the camera passes through it:
  1 → 1.9, fading over cam 0.55-0.75), tamarind along the top (L3), and three plain envelopes (no writing, no address)
  dropping into a letterbox on the office wall (`--s3d-mail`). New tracks `gate` (0.05-0.45) and `mail` (0.55-0.85);
  `cam` now ends at 0.80. The stage line ("Cổng luôn mở. Mời bạn vào." / "The gate is open. Come in.", draft) is HTML,
  pinned at the screen's foot for the whole run, so every frame has words. Measured: Tier 1 frames 0 → 8 differ by 30
  levels at 1440 and 55-70 at 390 (gate ≥ 18); no vertical hard line in any strip frame.
  - **Departures.** (1) The plan's four rendered alpha layers (`contact-gate`, 2400/1600/960 w, new encode budgets) are
    drawn as inline SVG in the house's screen-print manner instead (partials/contact-gate.mjs): sharp at 2x, no new
    request, no budget, and the world stream's sets stay untouched. L0 is the existing `header-contact` still. (2) The
    run is long again (36 → 110svh desktop, 12 → 80svh phones), reversing the M4 fix round, but the stage is
    `min(100svh - hdr, 66svh)` tall rather than the full screen: posters.mjs offers the desk crop with `sizes="100vw"`, so
    on a 1x screen at 1440 a full-height stage would draw the 1600 w file at 1.5 css px per source px (blurred). At
    66svh it is 0.52 at rest of the move and about 1.1 at its end; a per-still `sizes` (WordPress builder too) would
    let the stage grow. (3) The fog from the first pixel is gone: the page rises in only over the last 28svh (eased,
    8 stops, none solid before 70%). (4) The rest frame is the same still with the gate half open and one envelope
    in the slot, drawn over it, not a re-rendered still.
- **Get Involved, "Cửa mở".** The header is rebuilt: a room in late-morning light whose window's blue louvered
  shutters stand open onto a sunny street (partials/involve-window.mjs, inline SVG: shophouse front, tamarind, a
  generic scooter, no people, no lettering), the H1 "Mời bạn cùng chung tay" / "Come and lend a hand" (draft; the
  `<title>` stays "Chung tay"), and the four ways as tiles, each with a gạch tile in its pigment and its own button
  (Ủng hộ the only sun fill among them; new `cta` labels, draft), all above the fold: ways buttons' bottoms 599-779 at
  390x844 and 574-768 at 1440x900 with the staging bars (check-page.cjs 8). The header is lime-50 → sun-100 → sun-200
  toward the window and sinks into sun-100 at its foot: no shade-100, no grey fog. On the stage the leaves, already open
  62° at scroll 0, swing to 112° over a 60svh run while the street comes in (1 → 1.14) and its sun strengthens; at rest
  they stand at 112°. `.louver__face` is now a feathered elliptical patch (it was a full-width band that read as a
  rule; this also changes the home help slats). New token `--color-sun-300: #FFD58A` (light only, never text or a
  button; tokens.css and the D1 block in 05-DESIGN.md; ink-900 on it 11.5:1 in qa/contrast-pairs.json).
  - **Departures.** The new `header-get-involved` layered still is drawn as inline SVG, not rendered (as Contact). The
    run is one value (`data-s3d-min="0.6"`, 60svh) on phones too, not 45svh. The ways bar's Ủng hộ repeats the
    slat's, so the page has two sun buttons (one per group; involve-pages.test.mjs says so). On phones the tile shows
    the button only; the way's name stays in the tile for screen readers.
- **Little Rose, "Khu vườn có tường bao".** A paper-cut walled garden in six inline SVG layers
  (partials/shelter-scene.mjs): sky, the far wall with a torn-paper top, a coping of round tiles and one high window
  showing only lamp glow, a trellis with nine ivory and apricot blooms and a bench, a line of plain sheets, a desk under
  a striped awning (books, a loaf, a teapot and cup, a lamp, a notebook), pots and paper-cut grass. On the stage it is
  pinned while the five things the shelter provides (content/draft/work.json, unchanged) scroll past, and each lights
  one part of it (`--step-1…5` on CSS view timelines, motion/scroll3d.js where unsupported); the layers drift at
  depths 0.08-0.28 of the scene's height. At rest it is one picture, fully lit, beside the five lines as a plain list
  (no reveal, so no long empty beige stretch). Caption "Tranh minh họa, không phải nơi ở thật của các em." / "An
  illustration, not the girls' real home." (draft). The pink unit band and the sun band's 6 px top rule are gone from
  the page. Safeguarding, all in test/little-rose-page.test.mjs: no image, file, foreignObject or text in the scene;
  no class or id naming a door, gate, person, girl, face, hand, child, rose, pink or unit; every paint a token from the
  scene's allow-list (no hex, no unit-little-rose, no tile-brick, no error red). Tier 2: the sheets sway (6 s, only
  near the screen, never with motion off).
  - **Departures.** "Đối tượng" and the privacy note now follow the story (the plan's order). The steps are the
    existing `ul.does.offers__list`, styled as the story on the stage. The grain is one 128 px CSS noise tile, not a
    full-size feTurbulence (that cost up to 265 ms of main thread on a 4x-throttled phone).
- **Shared.** motion/scroll3d.js now also runs Little Rose's steps and only writes tracks while the stage is on (pause
  and reduced motion fall back to the CSS rest values); its gzip budget in openings.test.mjs is 2.7 KB (was 2 KB).
  check-openings.cjs covers Get Involved and adds a vertical hard-line scan inside the stage on every frame (words and
  `[data-art]` objects masked) and a Tier 1 motion-visibility check. New test/involve-first-screen.test.mjs.
- **Checks.** npm test 195/195 (before export: all but the export test); build, build:pages and check:links (46 pages,
  0 dead); PROD=1 blocked only by its guards (143, as before); export:wp, php wp/tests/run.php 8288/0,
  wp-static-check 0 errors (10 known warnings); check-openings all pass (Contact, News, Get Involved); light-contrast:
  only the 3 known home lines; tastegate 0 fail on all six (one heuristic "no CTA" warning each); mobile LCP median
  2.0-2.2 s on the three pages, CLS 0-0.001 (0.001 on EN pages predates M6), H1/intro stays the LCP on Get Involved and
  Little Rose. Captures: qa/m6/pages/before, after, after-2x, openings, tastegate.

### M6 stills and gates (11 Nov 2026, after both M6 streams)
- **Stills.** Every hero still (`npm run posters`) and every named still (`npm run posters -- --still all`) re-rendered
  and re-encoded from the M6 world; all inside the encode-posters budgets, hero framing shifts unchanged (poster-frames.js
  identical). The stills the world stream had not re-rendered (all lit headers except Contact, About, Impact and Cầu Hàn;
  door cards, hub doors, gallery wall, story, 404, dusk, bay-little-rose) now carry the Blender wall maps and, where the
  camera sees them, the corridor's end-wall print; the hero stills came out identical. bay-little-rose and the Little
  Rose door crops: door shut, no print near it, no pink or red light. Before/after contact sheets: qa/m6/stills/sheet-*.jpg
  (HEAD before M6 | tree at the start of this pass | after), with a per-still difference table in diff.json.
- **Gate fixes (integration, not design).** check-page.cjs section 6 waited 6 s for the scroll-velocity spring to reach
  0; with the M6 glide (Lenis lerp 0.07) under a busy software GPU it needs up to ~10 s, so the wait is 12 s (it failed
  1 run in 2 under load, last value 0.0016). check-hero.cjs read `window.__hcwaWorld` as soon as the world went live;
  the debug hooks chunk now arrives later (the poster atlas and wall maps decode in the same idle time), so the extras
  crashed half way. It now waits for the hooks, and the live-world extras run to the end again (all pass but the known
  Tier 1 ambient-motion timing at 390).
- **Not fixed here.** Get Involved's first render is one 196-246 ms task on a 4x-throttled phone (pre-M6 on the same
  machine: 132-157 ms): layout and style of the new header's inline SVG. LCP is fine (2.09 s), lcp.cjs's 200 ms
  long-task line fails. The home has failed that line since M3 (338 ms pre-M6, 333 ms now on this machine).

### M6 fix round (11 Nov 2026, critics of the three M6 streams)
- **Contact stage line (must).** "Cổng luôn mở. Mời bạn vào." stayed pinned and landed on the office address (390) and
  the form label (1440). It now fades out just before the page's first words reach it: over p 0.66 → 0.70 at the
  screen's foot (phones, tablets), over p 0.80 → 0.88 where it stands higher (>= 1024 px). check-openings.cjs checks the
  line's box against every other text box on every strip frame and on a 40 px sweep: no touch at either width, VI and EN.
  On a wide screen the line now stands in the left half at H2 size (bottom 30svh) while the gate opens, so the left of
  the run is not empty cream.
- **Wheel and form fields (must).** Both Lenis instances share one set of options (motion/smooth-wheel.js
  lenisOptions): `allowNestedScroll: true` and `prevent` for dialogs, selects and `[data-lenis-prevent]`. A filled
  textarea scrolls under the wheel and the page stays put (check-page.cjs section 9, six pages: scrollTop 300, page 0 px).
- **Poster draw calls (must).** Each set's prints are one merged mesh and one draw (objects/wall-poster.js
  `wallPosters`: placement baked, atlas cell / curl / size as vertex attributes). Measured `?tier=1&debug=1` sweep of the
  home, every 1% of the page: 390×844 max 13 calls, 1440×900 max 13 (budget 14; critics measured 15). test/world.test.mjs:
  the corridor has one `posters:` mesh holding four prints.
- **Wheel glide on weak machines (should).** Not loaded on pages with heavy scroll-linked paint (News wall, Little Rose
  garden: native wheel, check-page section 9). A frame-time guard (three frames > 50 ms apart in one glide, a > 150 ms
  gap counting twice) hands the wheel back to native scroll for the visit (sessionStorage hcwa:wheel). Lenis is made in
  idle time or at the first pointer move, so the first wheel tick is smoothed too (Contact: 0,0,0,0,7,24,30… px by frame,
  was a 100 px jump). The inner-page chunk loads after load + idle. 05-DESIGN 5.2 updated (lerp 0.07, inner-page chunk,
  guard). Maxwell should still try it on his own trackpad.
- **Poster GPU memory (should).** Live atlas: 512 px on phones and Tier 1, 1024 on Tier 2; 2048 for the stills only.
  The world's memory figure (and check-hero's GPU line) now counts drawing buffers + wall maps + poster atlas: 1440 Tier 1
  = 14.8 + 6.7 + 1.3 = 22.8 MB (budget 24); 390 Tier 1 = 3.8 + 6.7 + 1.3 = 11.8 MB.
- **Poster lettering (should; departure closed).** No print carries a word any more (Xuân, Cà phê, Sen, Mưa removed);
  the test now fails on any `<text>`. The prints are image-only decorative art; HCWA still approves the set.
- **Poster art (should).** Sen redrawn as a lotus pond: round leaves standing above the water on stems (no notched
  water-lily pads), pointed petals, a bud and a seed pod. The Cub redrawn with the step-through backbone, a broad front
  leg shield, the headlight in the handlebar cover and smaller wheels; the tamarind as fine pinnate leaves. The end wall
  now hangs a pair (the Cub, 0.76 m, and Tết hoa mai, 0.5 m) and the bay prints are a size up (0.6, 0.62 m); the Little
  Rose rule holds in all six bay orders. The single prints moved out of public/ to prototype/art/prints/ (they shipped
  with the deploy and nothing read them).
- **Header over the night (should).** ui/header.js marks the dark half of every evening sky (a closing .dusk band, the
  footer's sky, the footer); while the bar's foot is over it the bar and skirt take blue-900 at 92% and the words
  on-dusk (`.is-dusk`). No pale band over the navy any more; without JS the bar stays cream.
- **Dusk ramp (should).** Footer sky max(560px, 62svh), the .dusk band's sky max(600px, 66svh); the sun-200 → blue-700
  crossing spans about 52% of the sky (over 30svh, was ~40 px at 1440) on eased oklab stops. The footer's org name
  rises into the sky's dark last quarter (flex order: sky, name, room at 18:00, parapet, columns), and the .dusk band's
  words rise into its last quarter. The dusk still fades in by opacity in the crossing instead of being pulled up into
  the pale sky.
- **Home shutters beat (should).** Slats 0 → 0.28, leaves 0.28 → 0.60, look-out hold 0.60 → 0.70 (20svh), flood 0.72
  (cleared under 0.64), blend 0.70 → 0.84 (was 0.76 → 0.92); the help section rises 42% of S3 into the stage (44% on
  phones), so "Bạn có thể giúp như thế nào" enters as the leaves finish and stands mid-screen during the blend. The
  view outside pales less while the leaves open (only the flood pales it). Not done: a new outside layer (the Get
  Involved street) behind the window: the outside is the world's own view shader; logged as a need.
- **Get Involved (should).** The header's four tiles are one line each, the tile and its one action (no label that
  repeats the button). The slats below hold the names and the detail and repeat no button (every slat title a link,
  `louvers(..., { buttons: false })`; home's help slats keep theirs). One sun button on the page. The header is shorter
  (min-height min(86svh - header, 720px)), so the gap under the window is gone at 1440.
- **Little Rose (should).** The grass ends in its own torn-paper foot inside the frame (no dark mass for the mask to
  blur); the front layer is lifted 90 units and its parallax halved (0.28 → 0.14); the pots moved in and the desk/awning
  drawn at 0.92 about the middle, clear of the side feathers. 1440: the five lines at H2 size, 36svh apart, the one in
  the middle of the screen ink-900 and the others ink-700, the section heading pinned at the column's top. 390: the
  garden and its caption pinned under the header on the page's lime with a soft lime skirt; the lines rise in the space
  beneath (no plate over the art) and pass behind it.
- **Contact polish (should).** The courtyard runner is a perspective trapezoid as wide as the gate at its far end; the
  phone dolly on the still is 1 → 1.06 (was 1.12).
- **Coulds taken.** three.js has a stable chunk name (vite.config.js advancedChunks `three`), lcp.cjs matches
  /assets/three-/. Blue paint keeps its hue under warm light (paint.js: red never above 1.3x the paint's own red/blue
  ratio), so the indigo shutter leaf no longer reads mauve.
- **Stills.** Re-rendered (`npm run posters -- --still all`, `npm run posters`) for the new prints, the paint clamp and
  the S3 view; see qa/m6/fix/logs.
- **Also.** The folded footer columns lose their 1 px underline (a rule, D0.5). check-openings.cjs counts a slat's
  words under its own whole-row link layer (the Get Involved slats' titles are all links now).
- **Checks on the final tree.** npm test 197/197; build, build:pages, check:links (46 pages, 0 dead) pass; PROD=1
  blocked only by its guards (143, as before). export:wp, php wp/tests/run.php 8288/0, wp-static-check 0 errors (10
  known warnings). check-openings: Contact, News, Get Involved all pass (Contact's line overlap sweep included).
  check-page: all pass, with section 9 (textareas, native wheel on heavy pages). check-bays, check-tiles, check-sun-crisp
  pass; light-contrast only the 3 known home lines at 390. Seam scan of the last screen of all 42 pages at 390 and 1440:
  no row line under the header (y < 120) anywhere; the 390 rows left are the pause toggle's border. Mobile LCP (median
  of 3, measured while check-hero ran): home 2356 ms (longest task 345), Contact 2180 (213), Get Involved 2004 (169),
  Little Rose 2060 (228); three.js after LCP everywhere.
- **check-hero, now counting textures.** Tier 2 at 1440 is 65.4 MB (drawing buffers 33.4 + Tier 2 wall maps 26.7 +
  poster atlas 5.3) against 48 MB: it failed silently before, because only the buffers were counted. The poster share
  is down from ~21 MB to 5.3 MB; the rest is the Tier 2 wall maps (1024 px). Lead's call: count textures in the 48 MB
  (then the Tier 2 wall maps go to 512 px, 6.7 MB, total 45.4) or keep 48 MB for buffers only. Tier 1: 22.8 MB (1440),
  13.9 MB (390). The other check-hero failures are the known 390 intro contrast (6.6-7.0) and Tier 1 ambient timing.

### M6 final recheck (11 Nov 2026, on the final tree; captures and logs in qa/m6/final/)
- **Gates.** npm test 197/197. build, build:pages, check:links and check:links:pages pass (46 pages, 0 dead).
  PROD=1 build blocked only by its guards: 143 = 132 draft (44 data-draft, 44 [DRAFT, 44 bracket tokens) + 8 Little
  Rose door + 2 donation + 1 traces. export:wp re-run, no change to the tree; php wp/tests/run.php 8288/0;
  wp-static-check 0 errors, the 10 known warnings.
- **Browser checks** (own preview on :4461, at most two browsers at once). check-page 50 PASS / 0 FAIL (section 9
  included). check-openings: all pass (Contact, News, Get Involved; Tier 1 motion 55/32 Contact, 38/44 Get Involved).
  check-bays 24 pass, check-tiles pass, check-sun-crisp pass (its shutters line still prints a stale "not built yet
  (day 8)" skip: the shutters set is not checked). light-contrast: the 3 known home lines at 390 (6.7 < 7) of 254.
  check-hero 345 pass / 37 fail, all in the known groups: 390 intro contrast 6.62-6.98 (and the six short phones),
  Tier 1 ambient motion at 390, Tier 2 GPU memory 65.4 MB > 48 (buffers 33.4 + walls 26.7 + posters 5.3).
  Draw calls, `?tier=1&debug=1` every 2% of the home: max 13 at 390x844 and at 1440x900 (budget 14); Tier 1 memory
  11.8 MB (390) and 22.8 MB (1440).
- **Every page** (46: VI, EN, 404s, components) at 390x844 and 1440x900, walked to the end: 0 console errors, 0 failed
  requests or 4xx, one H1, no sideways scroll, no uppercase Vietnamese (qa/m6/final/pages/matrix.json). Reduced
  motion, no JS and no WebGL on Little Rose, Get Involved, Contact and home (VI, EN, both widths): no content text
  hidden; what the probe flags is navigation chrome by design (skip links, the corridor rail in the stacked layout,
  breadcrumb steps folded on phones, Contact's stage line after it has faded at the page end).
- **Mobile LCP** (median of 3, machine otherwise idle): home 2268 ms (longest task 293 ms, over 200 as since M3),
  Contact 2112 ms (163; LCP is the header still), Get Involved 1988 ms (129), Little Rose 1988 ms (160). CLS <= 0.004;
  three.js after LCP everywhere.
- **Home strips**, 24 frames each, world live, 0 errors: VI 390 and 1440 at Tier 1 and Tier 2, EN 390 Tier 1, EN 1440
  Tier 2 (qa/m6/final/strip/sheet-*.jpg). Screens: Contact, Get Involved, Little Rose (10 frames each) and the
  corridor-to-Centers handoff (20 frames) at 390 Tier 1 and 1440 Tier 2 (qa/m6/final/sheet-*.jpg).
- **Seam scan** (scripts/seam-scan.py) of the first and last screen of every page and every strip frame. Last
  screens: no row in the header band; at 390 the only rows are the pause toggle's border. Still flagged: the
  prototype review bar and the "View this page in English" bar end in straight edges on first screens (y 35/43 and
  77/83; review chrome, but the language bar ships); the cream header skirt over the dusk crossing for one strip frame
  (1440 EN f22, 390 EN f22) before the bar turns night, and always without JS; 3D geometry, tiles and art (columns) in
  the strips.
- **Still open (not green).** The shutters beat at 1440: two frames of closed shutters and one of the open window onto
  cream with no words before "Bạn có thể giúp như thế nào" rises (f16-f18). At 390, f01 (the hero room's corner under
  the buttons) and f20-f21 (partners to dusk) carry little. Little Rose at 390: one small line at a time under the
  pinned garden, with empty space below. Contact and every inner page end on about one screen of dusk with no words
  before the footer. Home long task 293 ms; Tier 2 GPU memory 65.4 MB with textures counted (lead's call, logged above).

### M7 chrome and pages (12 Nov 2026, Maxwell: Get Involved lists the ways twice; straight edges on the bars and the header skirt; captures in qa/m7/chrome/)
- **Get Involved shows the four ways once.** The header keeps its tiles with one button each (above the fold at 390 and
  1440). The slats under it are gone; in their place "Mỗi cách bắt đầu thế nào / How each way begins" (h2, data-draft)
  gives each way its line, its own list where its page has one (volunteer areas, partner forms) and text links into
  the right part of its page (the donate page's two ways to give and "tell us", what to expect and the child-safety
  rules, the partner form, the needs list and the bakery). Every word comes from content/draft; no numbers (the test
  checks for digits); no buttons (the tiles carry them). The header's foot no longer pads out an empty band at 1024+:
  tiles to the next heading 64 px (390) and 126-138 px (1440), the widest empty run 68-138 px, was about 300.
  WordPress's Get Involved pattern still prints the slats until the port takes this.
- **Bars end in light.** The review note and the "View this page in English" pointer are the header's own plaster
  (lime-50 at 92%, ink words; the toggle's on state gold-700, 5.3:1), so note, pointer and bar are one surface that
  ends in the header's feathered skirt. Over the home hero (no bar fill) the scrim starts in that plaster and eases into
  the ceiling over 20 px. The kit page's 6 px blue-700 top rule (read through the skirt as a line) is sun-200 like the
  other morning heads.
- **The header follows the evening (CSS scroll-driven, with or without JS).** Each sky (the closing .dusk band, the
  footer) is a view timeline seen through a 1 px line under the bar. Over the sky's light half the bar and skirt take
  the sky's own stops at 92% and hold the last one where ink-900 keeps 7:1 (#C5B295, 7.9:1); from the night mark (0.5 /
  0.45 of the sky, unchanged) they take blue-900 and the night's words, in the same frame as the scroll. The home's
  hero scrim steps aside over both, and the skirt shows there even before the bar compacts. Without JS the line uses the
  uncompacted 80 px bar. ui/header.js's IntersectionObserver .is-dusk is now only the fallback for browsers without
  scroll-driven animations (and its skirt now eases with the bar). No markup changed (WordPress header.php unchanged).
- **Proof.** Luminance-step scan (qa/m7/chrome/tools/lum-scan.py: a row where 90% of columns step more than 6 levels)
  on the first screen and five dusk-crossing frames (one frame after the jump and settled) of all 46 pages, 390x844 and
  1440x900, with and without JS, an English browser so VI pages show the pointer: before 308 of 1574 shots flagged
  (178 bar rows on 176 first screens, 211 header rows on 113 dusk frames, the kit rule), after 8 of 1564, none in the
  chrome. night-probe.cjs: bar, skirt and logo switch between 3 px before and 3 px after each night mark on every mode.
  Sheets: qa/m7/chrome/sheet-before-after-{390,1440}.jpg. Test: test/chrome-edges.test.mjs.
- **Still open.** The 8 flagged shots are content at 390: the News list's and Team placeholders' card edges (tops +11-14,
  bottoms about -52). In a browser without scroll-driven animations and without JS the bar stays cream over the dusk.

### M7 world (12 Nov 2026, Maxwell: the shutters "open onto a blank cream view, then about two screens with no words"; Tier 2 GPU memory)
- **S3 outside view.** `objects/sky.js viewMaterial` draws a Saigon street by ray from the camera through the window
  (real depth and parallax): two-storey shophouses across the road in yellow, mint and cream wash, ground floors
  behind rolling shutters (some half up, the shop in shade), ochre floor bands, arched upper windows with jade louvers,
  sills, iron railings and potted plants, a cream cornice with a course of Hoa thị blocks; the cream tiled pavement in
  late sun broken by the tamarind's shade; a parked Cub (no plate, no lettering), drawn over the facade as well as the
  pavement; two sagging wires; the near tamarind's trunk and canopy across the top of the window (sways on Tier 2).
  No people, no signage, no text, no children. Same tokens and street as Get Involved's header (`involve-window.mjs`).
  `sets/shutters.js` uses it for the live set and the S3 stills (`uPale` ≤ 0.3 replaces the old low/high lerp, so the
  street keeps its shape when open). The beams thin as the leaves open (density × (1 + 0.6·leaf)(1 − 0.75·leaf), the
  flood brings them back; the stills' shafts 1.2 → 0.48 open): at full density they were a veil over the street.
- **Wordless stretch.** The opening moved onto the approach (the section rising under the news): slats over
  a 0.12–0.50, leaves over 0.50–1.00, open by the pin (`journey.js SHUTTERS`, `slatAt/leafAt/s3Open/s3Exposure` take
  a; camera keys 0, 0.45, 0.58, 0.78, 1, 2). Stage 100svh (90svh phones), lead 14svh (12svh), `--rise: var(--s3)`: the
  help heading comes up from the screen's foot as the frame pins, under the open window; flood 0.30 (unflood 0.22),
  blend 0.28–0.62. The stills stage (world gone after the passage) reads `--a` from a second ScrollTrigger (section top
  at the foot → track top at the top) and `--p` as before. Measured with qa/m7/world/tools/wordgap.cjs (30 px steps,
  a word counts when its box is under the header, on top and at opacity > 0.35): longest run without words
  **1440 1650 → 450 px (1.83 → 0.50 screens), 390 1350 → 450 px (1.60 → 0.53)**; 1440 Tier 2 0.50, EN 1440 0.47;
  reduced motion (stacked) 1440 0.53, 390 0.14. Total home scroll at 1440: 14129 → 12941 px (S3 is 100svh shorter
  and its words overlap it; no other section's dwell changed).
- **check-sun-crisp** now gates the shutters set: the 17:00 sun through the window onto the terrazzo, penumbra under
  the 65° slat gap (0.45–0.53 × gap, limit 0.6), incidence 65–70° accepted (raking bars by design); the shut slats'
  5 mm gap reported as info (soft by design). The corridor skip names its real reason.
- **GPU memory** (the lead's call; `world/memory.js`, `renderer.memory`, counted by check-hero with textures, one
  48 MB budget on every tier). Measured live (qa/m7/world/memory-final.json, memory-1440-t2.json), MB total
  (buffers / shafts / baked / walls / atlas / runner): 390x844@2 Tier 1 17.2 (5.9/0.1/2.7/6.7/1.3/0.5), Tier 2 47.8
  (35.8 MSAA/0.8/…); 1440x900 Tier 1 26.3, Tier 2 47.3 (33.4/2.8/2.7/6.7/1.3/0.5; was 65.4); 1920x1080 Tier 1 35.4,
  Tier 2 47.0. Every case takes the 512 maps / 512 atlas / 256 runner: at these sizes the 1024 maps (+20 MB) never fit
  beside a supersampled canvas; the planner takes them where they do (a smaller Tier 2 canvas, test/world.test.mjs).
- **Gates.** npm test 203/203; build, build:pages pass; PROD=1 build blocked only by its 143 guards; export:wp re-run;
  php wp/tests/run.php 8300/0; wp-static-check 0 errors (10 known warnings). check-hero 349 pass / 33 fail, all in the
  known groups (390 intro contrast 6.6–7.0, the six short phones, Tier 1 ambient motion at 390); its GPU memory lines
  now pass (17.2 and 47.3 MB). Stills re-rendered: shutters-closed/-louvers/-open, inside the encode budgets.
- **Captures** (qa/m7/world/): before/ and after/ 24-frame strips (VI 1440 Tier 1 and 2, 390 Tier 1, sheets
  sheet-*.jpg), probe1/probe2 (S3 positions 1440/390 Tier 2), fallback/ (stills stage, reduced, no JS), stills-after.jpg
  (new stills over the old). In the 1440 Tier 2 strip f18 the help heading had not finished its reveal at capture
  time (SwiftShader frame time); the stepped measurement at Tier 2 has words there.

### M7 fix round (12 Nov 2026, critique of the M7 streams; captures and logs in qa/m7/fix/)
- **Get Involved: each way once, as one object** (the critique: the header tiles were followed at once by "Mỗi cách bắt
  đầu thế nào", the same four icons and names in the same order, about three screens of repeat at 390; Maxwell's item 1
  in substance). The header keeps the window, the H1, the intro that names the four ways, and one text link down to
  them ("Xem các cách chung tay / See the ways to help", new `involve.seeWays`, draft). Under it "Các cách chung tay /
  Ways to help" (the existing `waysLabel`, h2) lists each way once: its gạch tile and name (h3), its line, its page's
  own list (volunteer areas, partner forms), text links into the right part of its page, and its one button at the foot
  (Ủng hộ the only sun fill, labelled with the donate page's own "Cách ủng hộ / How to give" so the name is not said
  twice in a row; the others their `cta`). `startTitle` is gone. Every name shows once on the page (involve-pages.test,
  check-page 8: four ways, one button each, no name twice, the link above the fold at 390 and 1440). The ways buttons
  are no longer above the fold (departure from M6 "Cửa mở": the header's link is, and the site header's Ủng hộ is always
  there). At 1024+ the header is shorter (min(60svh − bar, 520 px)) and the window takes more of it, so the link to the
  ways heading is 71–102 px at 1024–1440 (was 141–210) and 64 px at 390. WordPress's Get Involved pattern already lists
  each way once (lit header, then hcwa/louvers slats); it has not taken the new objects.
- **The header's foot meets no picture in one row** (Cầu Hàn and Green Bamboo first screens, VI and EN, 390 and 1440, JS
  and no JS: a 9–11 level step over 65–92% of the width at the bar's foot). The cause was the center's unit mark
  (`.lit-head--unit::before`: 3 px of solid unit paint right under the skirt, which shows 8% of what is under it). It is
  now a soft wash with no solid core that rises out of the skirt (peak 17% at 54 px, gone by 104 px, its left end eased
  over 72 px), and every lit header's still eases in from the plaster over its first 64 px (`--top`, a third
  intersected mask). Header-foot scan (qa/m7/fix/tools/lum-scan.py --foot: 50% of columns over 4 levels in the top
  260 px, thin lines dropped): both pages clean at 390 and 1440, JS and no JS.
- **No backdrop blur on the bar or its skirt.** The bar is a backdrop root (its own blur, its view-transition-name), so
  the skirt's blur never saw the page and the bar's ended square at its foot over whatever scrolled under it (white
  words under the navy bar over the dusk, the tiles under the cream bar). Without the blur, words under the nav showed
  through the 92% fill as legible ghosts (15 levels), so every chrome fill (the review note, the EN pointer, the bar,
  the skirt, the evening and night keyframes, the hero scrim's first stop) is now 97% (a 6 level trace). Contrast is
  unchanged or better (the fills are nearer their tokens). The blur also cost about 30 ms of the home's first long task
  (below). The same soft wash replaces the 3 px solid unit band on `.sun-head--unit` (Little Rose's pink).
- **GPU memory after a resize.** `memory.js canvasCap`: once the textures are planned (`live.js planNow`), the renderer
  holds the canvas to what the budget leaves them (`renderer.limit`, `dprFor(..., cap)`, rounded down under a cap), MSAA
  included and under DPR 1 if need be (`dprFor` no longer keeps an MSAA canvas at DPR ≥ 1). Load-then-grow, critic's
  resize-mem.cjs: 900×900 → 1440×900 at DPR 1 **68.3 → 47.4 MB** (canvas 1137×711), at DPR 2 **66.8 → 46.8 MB**.
  test/world.test.mjs adds a resize case (four DPRs × both tiers × five load/grow pairs); check-hero's extras add a
  load-then-grow probe (four cases, every texture in before the grow).
- **Home long task** (6–7 runs per build, lt-ab.cjs: Moto G, Slow 4G, 4x CPU, cold, gzip, stills path): **M6 279 ms
  median (234–331), this round 252 ms (246–302)**, LCP medians 2304 / 2292 ms. The first task is the page's first style
  and layout (trace: Layout 121 ms, UpdateLayoutTree 97 of 260). Switched off one at a time in flight: the scroll-driven
  header block 290 ms, the `body:has()` rules 286, both 262, against 255 with them: no measurable cost, so they stay
  (the night's word colours need the descendants). The header blur put back: 281 ms. Still over the 200 ms gate, as at
  M6 (293 logged then).
- **S3 street on Tier 1** (could): the live Tier 1 set draws the street with lighter noise (`viewMaterial lite`: the
  canopy's mass from two octaves and no fine-leaf term, the road's dapple from one; about a third of the per-pixel noise);
  Tier 2 and the stills keep the full street. SwiftShader S3 frames 390 Tier 1, mean of 0.70–0.80: 180 → 175 ms (the
  window is a third of the frame there); not checked on a real mid-range Android.
- **Proof.** All 46 pages, 390 and 1440, JS and no JS, English browser (qa/m7/chrome/tools/chrome-shots.cjs → qa/m7/fix/edges,
  1564 shots): the 90%/6 scan flags 8, the same News and Team card edges as before, none in the chrome; the new header-foot
  scan (lum-scan.py --foot, rows inside a feathered ramp dropped) flags 36, none at the bars or the header's foot (the GI
  window's frame and the 404 lattice on first screens, dusk-band headings and text at 390). night-probe: bar, skirt and
  logo switch within 3 px of each night mark, every mode, at 97%. check-page 56/0 (its Get Involved block rewritten).
  check-hero 352 pass / 34 fail, all in the known groups (390 intro contrast 6.6–7.0, the six short phones); every GPU
  memory line passes, the four load-then-grow cases 44.5–47.6 MB. light-contrast: the same 3 known 390 intro lines.
  lcp.cjs home medians 2320 / 2260 / 2288 ms (tier 0 / 1 / default), Get Involved 2008 / 2000 / 2012 ms.
- **Gates.** npm test 206/206; build, build:pages pass; PROD=1 build blocked only by its 143 guards; export:wp re-run; php
  wp/tests/run.php 8298/0 (two golden cases fewer: the detail section's classes are gone); wp-static-check 0 errors, the
  10 known warnings.

### M7 final recheck (12 Nov 2026, on the final tree over HEAD 05c4d9e; captures and logs in qa/m7/final/)
- **Gates.** npm test 206/206. build, build:pages, check:links and check:links:pages pass (46 pages, 0 dead). PROD=1
  build blocked only by its 143 guards. export:wp re-run: no change to wp/ (md5 before and after identical); php
  wp/tests/run.php 8298/0; wp-static-check 0 errors, the 10 known warnings. check-sun-crisp all pass (the shutters set
  is gated: incidence 65–70°, penumbra 0.45–0.53 × gap).
- **Browser checks** (own preview on :4173, at most two browsers at once, stopped by PID after). check-page 56/0
  (Get Involved: four ways, one button each, no name twice, the link above the fold at 390, 1440 and 320, VI and EN).
  check-hero 354 pass / 32 fail, all in the known groups: 390 intro contrast over the shaded base 6.62–6.98 (26 lines)
  and the six short phones' silhouette under the H1; this run had no Tier 1 ambient-motion or pause-flake fail. Every
  GPU memory line passes, the four load-then-grow cases 44.5–47.6 MB. check-openings all pass, check-bays 24/24,
  check-tiles 16 ok, light-contrast the same 3 known 390 intro lines (6.7) of 254. night-probe: bar, skirt and logo
  switch between 3 px before and 3 px after each night mark, at 97%. Draw calls, Tier 1, every 2% of the home: max 13
  at 390, 1440, 1920 and 2560 (budget 14). tier-errors (3D stage pages, Tier 1 and 2, 390 and 1440): 0 bad. 0 page
  errors over the 184 page loads of the edge sweep (46 pages × 4 modes).
- **GPU memory**, MB, measured at the WebGL calls (qa/m7/final/tools/gpu-truth.cjs, whole home scrolled; textures +
  drawing buffers) and, in brackets where it differs, the world's own count that check-hero reads:

  | Viewport | Tier 1 | Tier 2 |
  |---|---|---|
  | 390x844 @2 | 17.2 | 44.5 (47.8, MSAA) |
  | 1024x768 | – | 43.4 (46.4, MSAA) |
  | 1440x900 | 26.3 | 47.3 |
  | 1440x900 @2 | – | 47.3 |
  | 1920x1080 | 35.4 (world count) | 46.9 (47.0) |
  | 2560x1440 | 45.3 (world count) | 47.3 (47.4) |
  | grown after load (resize-mem) | – | 900² → 1440x900 47.4 (@1), 46.8 (@2); 1024x640 → 1920x1080 47.6; 390x844@2 → 1440x900 46.8 |

  Every case is within 48 MB. Every plan takes 512 px wall maps and a 512 px atlas (the 1024 px atlas only on a small
  Tier 2 window such as 1024x640); no live viewport takes the 1024 px wall maps. Desktop Tier 2 headroom about 0.7 MB.
- **Mobile LCP** (lcp.cjs, median of 3, machine otherwise idle): home 2272 / 2332 / 2256 ms (tier 0 / 1 / default),
  CLS 0.004, three.js after LCP; longest task in the first 10 s 260 ms on the default path (runs 247–260; over the 200
  ms gate as since M3, M6 293 logged). Get Involved 2104 / 2056 / 2092 ms, CLS 0, longest task 212 ms (runs 161–212:
  now at or just over the gate; M6 129 logged, the M7 critic 190).
- **Home strips** (qa/m7/final/strip/, 24 frames over the page plus 28 over the shutters beat, 0 errors, world live):
  longest run without words over the beat VI 1440 Tier 1 and Tier 2 420 px (0.47 screen), EN 1440 Tier 2 0.43, VI 390
  Tier 1 and Tier 2 390 px (0.46), EN 390 Tier 1 0.50; no JS and reduced motion 1440 0.50, 390 0.11. The window opens
  onto the street (shophouses, louvred arches, wires, tamarind, a parked Cub; no people, no lettering) and the help
  heading rises into it. Other wordless whole-page frames are the known ones (1440 f02 passage wall, f05 corridor
  entry; 390 f04 corridor). Sheets: strip/sheet-whole-*.jpg, strip/sheet-beat-*.jpg; shutters/ (the beat, 1440
  Tier 2 and 390 Tier 1).
- **Get Involved** (qa/m7/final/gi/, VI and EN at 390 and 1440, plus 390 without JS): each way's name appears once;
  header (window, H1, intro, "Xem các cách chung tay" link), then "Các cách chung tay" with each way's tile, line,
  list, links and one button; the FAQ and the contact band follow. At 1440 the fourth way's two-line name ("Hiện vật và
  Tiệm bánh Hoa Hồng Nhỏ") pushes its column's body about 30 px below the others (the critique's could, not taken).
- **Luminance-step scans** of the whole of every page (edges.cjs: every 0.6 viewport, plus the top 260 px one frame
  after each jump where the bar's foot is over an evening sky; English browser so VI pages show the EN bar): 390 and
  1440, Tier 1 and no JS, 2170 shots in qa/m7/final/edges, reports in qa/m7/final/scans/. First screens: the
  header-foot scan (50% of columns over 4 levels, top 260 px) flags none at the bars or the header's foot on any page
  in any mode; the critique's three chrome edges are gone (Cầu Hàn and Green Bamboo header foot at y 103/151 and
  115/163, the 390 EN review bar at y 43, the blur's square foot at y 59 over the dusk). Its only first-screen hit is
  Get Involved's window lintel at 390 without JS (illustration). The 90%/6-level row scan: 1440 0 shots (JS and no
  JS); 390 55 (JS) / 53 (no JS), all content: /components/ tables 35, home work-sheet floor joints (2 px grout lines)
  8, News cards 6, Team placeholders 4, Our Work hub door stills' floor sill 2. Over scrolled frames at 390 the
  header-foot scan also catches content under the bar: field and button outlines show through the 97% fill as 4–6
  level traces (by design since the blur went; no edge at the bar's foot).
- **Still open (not green).** Home long task 260 ms and Get Involved 212 ms against the 200 ms gate. check-hero's
  known 390 intro-contrast and short-phone fails. News and Team card edges at 390. The 97% bar's faint trace of what
  scrolls under it. A browser without scroll-driven animations and without JS (bar stays cream over the dusk): not
  testable here (Chromium only). WordPress's Get Involved pattern has not taken the one-object ways (it lists each way
  once as hcwa/louvers slats). No real mid-range Android check of the S3 street's frame time.
