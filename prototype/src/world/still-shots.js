// The stills beyond the hero (PLAN sections 4 and 5, 05-DESIGN D6.10): inner-page lit headers, the
// footer's dusk band, the door cards, the 404 and the story slot. Names, crops, sizes and budgets only;
// no three.js, so lib/posters.mjs and scripts/ read it in Node. The scenes are in sets/stills.js.
//
// Files: public/posters/<name>-<crop>-<layer>-<w>.<ext>. A lit header has three layers that composite
// to the lit frame (scripts/encode-posters.py): base (shaded), lightroom (drifts during the sun-in) and
// lightscreen (the light over the aperture itself: holds still). A plain still has one layer, still.

// Crops. `view` is the CSS box a crop is lit for (the text caps are fractions of it); `media` picks it
// in <picture> (the last crop has none). Lit headers and the dusk band are 3:1 from 600 px up and 4:3
// below (D7; 600 is the design system's first breakpoint, D3).
export const HEADER_MEDIA = "(min-width: 600px)";
export const CROPS = {
  header: [
    { name: "desk", aspect: 3, view: [1440, 480], media: HEADER_MEDIA, widths: [960, 1600] },
    { name: "mobile", aspect: 4 / 3, view: [400, 300], media: null, widths: [480, 960] },
  ],
  band: [
    { name: "desk", aspect: 3, view: [1440, 480], media: HEADER_MEDIA, widths: [800, 1600] },
    { name: "mobile", aspect: 4 / 3, view: [400, 300], media: null, widths: [480, 800] },
  ],
  card: [{ name: "card", aspect: 4 / 3, view: [400, 300], media: null, widths: [480, 800] }],
  // Our Work's wall of doors (critique r1, r2): one 21:9 frame, and each door alone at 4:5 for phones.
  pano: [{ name: "wide", aspect: 21 / 9, view: [1680, 720], media: null, widths: [1600, 2400] }],
  door: [{ name: "door", aspect: 4 / 5, view: [384, 480], media: null, widths: [480, 800] }],
  square: [{ name: "square", aspect: 1, view: [600, 600], media: null, widths: [480, 800] }],
  story: [{ name: "story", aspect: 3 / 2, view: [600, 400], media: null, widths: [480, 800] }],
  // A wall the page draws on (the center page's gallery): 21:9 from 600 px, 16:10 below.
  wall: [
    { name: "desk", aspect: 21 / 9, view: [1320, 566], media: "(min-width: 600px)", widths: [960, 1600] },
    { name: "mobile", aspect: 16 / 10, view: [400, 250], media: null, widths: [480, 800] },
  ],
  // The home passage's own frames (PLAN H3): a whole viewport where the flight would be, art-directed by
  // shape, not width: landscape screens get 16:10, portrait ones 3:4 (cropped by object-fit).
  frame: [
    { name: "desk", aspect: 16 / 10, view: [1440, 900], media: "(min-aspect-ratio: 1/1)", widths: [960, 1600] },
    { name: "mobile", aspect: 3 / 4, view: [390, 520], media: null, widths: [480, 800] },
  ],
};

// Where the H1 and intro sit over a desktop header (fractions of the crop): a quiet wall, no aperture,
// the light capped to 40% so a pattern never sits behind running text (D6.6). The subject keeps right
// of x 0.56; the page anchors the still to its right edge (object-position: 100%) so a box taller than
// 3:1 crops the wall, never the subject. Mobile crops carry no text: it sits below them.
export const HEADER_TEXT = { x: 0, y: 0.14, w: 0.54, h: 0.86 };

// AVIF budgets in KB per file (PLAN section 5, D6.7): a header's three layers together stay within
// 70 KB at 1600 w and 22 KB at 480 w; a plain still within 35 KB. WebP and JPEG may use twice that.
// 2400 and 3840 (desk), 1440 (mobile): a still that is shown larger than its box at 1x (Contact's opening, below) keeps
// the 1600 w (960 w) header's bytes, spent on more pixels.
const HEADER_BUDGET = {
  desk: {
    960: { base: 18, lightroom: 12, lightscreen: 6 }, 1600: { base: 36, lightroom: 22, lightscreen: 12 },
    2400: { base: 36, lightroom: 22, lightscreen: 12 }, 3840: { base: 36, lightroom: 22, lightscreen: 12 },
  },
  mobile: { 480: { base: 10, lightroom: 8, lightscreen: 4 }, 960: { base: 22, lightroom: 14, lightscreen: 8 }, 1440: { base: 22, lightroom: 14, lightscreen: 8 } },
};
const STILL_KB = 35;
const PANO_KB = { 1600: 70, 2400: 130 };
// A frame still fills a screen, so it gets a header's room (D6.7): still lazy, never on the first screen.
const FRAME_KB = { desk: { 960: 30, 1600: 60 }, mobile: { 480: 22, 800: 35 } };

const LIT = ["base", "lightroom", "lightscreen"];
const PLAIN = ["still"];

// kind: the crop set; layers: what the page draws (a header whose aperture is out of frame has no
// lightscreen); motion (light.css): "drift", the light layer drifts 0.12× with the scroll (P2, P3);
// "louver", the sun-in opens through louver stripes instead of the diagonal sweep (P4).
export const STILLS = {
  "header-our-work": { kind: "header", layers: LIT, page: "our-work", what: "The room's screen seen frontally at 10:00" },
  "header-green-bamboo": { kind: "header", layers: LIT, page: "center", unit: "green-bamboo", motion: "drift", what: "Its green door open, Đốt tre light on the floor" },
  "header-thrive-together": { kind: "header", layers: ["base", "lightroom"], page: "program", motion: "drift", what: "Lam stripes of late-morning light on a wall" },
  "header-get-involved": { kind: "header", layers: LIT, page: "get-involved", motion: "louver", what: "The open shutters at 17:00" },
  // Contact's opening (styles/openings.css) shows the desk crop as tall as its stage (62svh) and dollies in 1.16x: at
  // 1440 × 900 that is 1942 CSS px wide at the end of the move, 3884 device px on a 2x screen. The browser takes the
  // 3840 w file there (sizes 100vw: 2880 → the next width up) and 2400 w at 1x on wide screens. Phones (48svh, 1.12x:
  // about 600 CSS px) at 3x take the 1440 w file, at the 960 w file's bytes, so the phone's LCP does not grow.
  "header-contact": { kind: "header", layers: LIT, page: "contact", widths: { desk: [960, 1600, 2400, 3840], mobile: [480, 960, 1440] }, what: "The street side of the screen at 16:30" },
  // M4 pages (PLAN-M4 4.1, 4.6, 4.10), rendered with the Blender walls (M5).
  // Its screen is lit from the street side (no light comes through it toward the camera), so the morning sun on the
  // face and the screen is one layer; the light holds still (no drift).
  "header-about": { kind: "header", layers: ["base", "lightroom"], page: "about", what: "The house's front screen in early-morning light, raking across the plaster" },
  "header-impact": { kind: "header", layers: ["base", "lightroom"], page: "impact", motion: "drift", what: "Noon down the light well: Hoa thị patches drawn long on the back wall, no numbers" },
  "header-cau-han": { kind: "header", layers: LIT, page: "center", unit: "cau-han", motion: "drift", what: "Its blue door open, Vòm cầu light on the wall" },
  dusk: { kind: "band", layers: PLAIN, what: "The room at 18:00, one last lit quatrefoil" },
  // Our Work's door wall (critique r1): one wall, one camera, one sun, the three doors at 1/6, 1/2, 5/6.
  "hub-doors-wide": { kind: "pano", layers: PLAIN, page: "our-work", what: "One lime wall, three doors: green open, pink shut in plain daylight, blue open" },
  "hub-door-green-bamboo": { kind: "door", layers: PLAIN, unit: "green-bamboo", page: "our-work", what: "The hub wall's green door, open (phones)" },
  "hub-door-little-rose": { kind: "door", layers: PLAIN, unit: "little-rose", page: "our-work", what: "The hub wall's pink door, shut, plain daylight (phones)" },
  "hub-door-cau-han": { kind: "door", layers: PLAIN, unit: "cau-han", page: "our-work", what: "The hub wall's blue door, open (phones)" },
  "door-green-bamboo": { kind: "card", layers: PLAIN, unit: "green-bamboo", what: "Green door open, Đốt tre transom light" },
  "door-cau-han": { kind: "card", layers: PLAIN, unit: "cau-han", what: "Blue door open, Vòm cầu transom light" },
  "door-little-rose": { kind: "card", layers: PLAIN, unit: "little-rose", what: "Pink door closed, Hoa thị transom, plain daylight" },
  "not-found": { kind: "square", layers: PLAIN, page: "not-found", what: "Một viên gạch bị thiếu: one block missing, light through the gap" },
  story: { kind: "story", layers: PLAIN, what: "A lit wall: Hoa thị patches on lime wash" },
  "gallery-wall": { kind: "wall", layers: PLAIN, page: "center", what: "A stretch of the corridor's far wall, no door, crisp Hoa thị patches at hanging height" },
  "passage-corridor": { kind: "frame", layers: PLAIN, page: "home", what: "The corridor seen from the hole the passage comes out of (K4): doors in their bays, emblem light on the floor" },
  // The home corridor's walk (PLAN H4) for every visitor without the live corridor: each door from where
  // the live camera reads it, the doors before it open, the ones after shut, its card's side of the frame
  // (desk: the right third; mobile: below) left to plain wall.
  "bay-green-bamboo": { kind: "frame", layers: PLAIN, page: "home", unit: "green-bamboo", beat: 0, what: "The green door, open, Đốt tre light from its transom and the screen's sun on the floor" },
  "bay-little-rose": { kind: "frame", layers: PLAIN, page: "home", unit: "little-rose", beat: 1, what: "The pink door, shut, plain daylight from its Hoa thị transom; no corridor sun in its bay" },
  "bay-cau-han": { kind: "frame", layers: PLAIN, page: "home", unit: "cau-han", beat: 2, what: "The blue door, open, Vòm cầu light from its transom" },
  // S3 (PLAN H7) at its three beats, from the live camera's pose: the stage shows them in turn when the world
  // does not draw it; the stacked page (no JS, Tier 0, reduced motion, short screens) shows the open one.
  "shutters-closed": { kind: "frame", layers: PLAIN, page: "home", what: "The blue louvered shutters shut at 15:55: thin gold lines through the slats across the floor" },
  "shutters-louvers": { kind: "frame", layers: PLAIN, page: "home", what: "The slats turned 65°: the lines widened into bars of late sun" },
  "shutters-open": { kind: "frame", layers: PLAIN, page: "home", what: "Both leaves swung into the room at 17:00 onto a street of shophouses under a tamarind (no people, no lettering), the window's whole patch of gold on the floor" },
};

// A still may carry its own widths per crop (`widths: { desk: [...] }`); everything else is its kind's.
export const cropsOf = (name) => CROPS[STILLS[name].kind].map((c) => (STILLS[name].widths?.[c.name] ? { ...c, widths: STILLS[name].widths[c.name] } : c));
export const stillFile = (name, crop, layer, w, ext) => `/posters/${name}-${crop}-${layer}-${w}.${ext}`;

// Budget in bytes for one AVIF file.
export function budgetKB(name, crop, layer, w) {
  const s = STILLS[name];
  if (s.kind === "frame" || s.kind === "wall") return FRAME_KB[crop][w];
  if (s.kind === "pano") return PANO_KB[w];
  if (s.kind !== "header") return STILL_KB;
  const b = HEADER_BUDGET[crop][w];
  // Without a screen layer, the room's light may use its share.
  return layer === "lightroom" && !s.layers.includes("lightscreen") ? b.lightroom + b.lightscreen : b[layer];
}
