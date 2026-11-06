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
- **Skills:** `scroll-experience` (sticky stage, steps), `3d-web-experience`,
  `threejs-materials` (paint, `toneMapped: false`), `threejs-animation` (door swings),
  `frontend-ui-engineering` (cards, tabs, focus), `design-token-contrast` (unit bands).
- **Accept:** removing a card from the DOM removes its door (beats test); Little Rose test
  passes; lit door swatches ΔE2000 ≤ 3 against unit tokens; emblem shapes readable on the
  floor at 390 (stills reviewed); tab order: skip link → tabs → card links, focus never on a
  hidden card; 200% text zoom stacks the stage; on G1 failure mobile shows door stills with
  the same cards.

### H5. Our work sheet (`#work`): Desire 2

The first opaque sheet. It slides over the corridor's last frame with a bông gió top edge;
the canvas pauses once the sheet covers it. Background lime-100 (sun) with the CSS sun
layer in the right margin.

**H5a. Focus areas bento**
- **Content:** H2 `work.focusTitle` (new draft: "[DRAFT: Lĩnh vực hoạt động]" / "[DRAFT: Areas
  of work]"); `work.focusAreas[]` `name`, `text` (draft), link `?area=<id>` to Our Work.
- **Layout:** 1440 gapless 4-column dense grid: Giáo dục 2 × 2 (Hoa thị), Bảo vệ trẻ em 2 × 1
  (Mắt cáo), Gia đình và cộng đồng 1 × 1 (Vòng tròn), Vận động chính sách 1 × 1 (Bậc thang) =
  8/8 cells; tablet 2 columns 6/6; 390 one column with 72 px block art left of the title.
- **Motion:** `stagger-cascade` (60 ms, max 4); block-art light drifts with `view()`; hover
  light shift 6 px + lift 2 px.
- **3D:** none; block art SVG from `blocks-svg.mjs` (same numbers as the shader).
- **Skills:** `gpt-taste` (gapless bento), `anti-ui-slop`, `design-spells` (light shift).

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
