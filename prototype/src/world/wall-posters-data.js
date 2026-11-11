// Wall posters (M6, Maxwell 11 Nov 2026: "the walls are so bland can we add some vietnamese posters (NOT
// PROPAGANDA)"). Original prints of everyday Saigon life in the manner of 1960s-70s screen printing: flat inks, a
// halftone in the shadows, a second plate a little off register, cream paper aged by the sun. Never propaganda: no
// stars, flags, emblems, uniforms, leaders, slogans, crowds or rays; no brands, logos, plates or signage; no people's
// faces and no children; at most one decorative word, no dates, no information. HCWA approves the set before
// production (PLAN.md section 9). No three.js here: the tests, the art script and the world all read it.
//   scripts/wall-posters.mjs   draws each design as SVG and rasterises it (headless Chromium)
//   scripts/encode-wall-posters.py  prints and ages it, and packs the atlases (public/textures/wall-posters-*.webp)
//   objects/wall-poster.js     hangs them: a curled sheet with its tape and its shadow, lit like the wall

// The set. word: the one decorative word (display face) or null. inks: the token names it is printed in (on the
// paper, tile-cream). atlas: its cell in the atlases (null: a single print for the stills, not hung live).
export const WALL_POSTERS = [
  { id: "tet-hoa-mai", word: "Xuân", inks: ["tile-ochre", "tile-ochre-lit", "sun-300", "tile-jade", "ink-900"], atlas: 0, what: "A hoa mai branch across a pale sun disc, flower-market baskets at its foot" },
  { id: "ca-phe-phin", word: "Cà phê", inks: ["world-wood", "tile-cream", "tile-indigo"], atlas: 1, what: "A phin dripping into a glass, a low stool, steam" },
  { id: "cub-duoi-hang-me", word: null, inks: ["tile-jade", "sun-200", "tile-indigo"], atlas: 2, what: "A step-through scooter (no badge, no plate) parked under tamarind leaves, dappled light" },
  { id: "ao-sen", word: "Sen", inks: ["tile-jade", "tile-jade-lit", "tile-cream", "tile-indigo-lit", "tile-indigo", "sun-300", "sun-200"], atlas: 3, what: "Lotus pads, a bud and an apricot flower, ripples, a dragonfly" },
  { id: "pho-mua", word: "Mưa", inks: ["tile-indigo", "lime-300", "sun-200", "tile-cream"], atlas: 4, what: "Rain over shophouse shutters, reflections, one bicycle" },
  { id: "dieu-dong-lua", word: null, inks: ["tile-jade", "tile-jade-lit", "sun-300", "tile-indigo", "tile-cream", "ink-900"], atlas: 5, what: "One kite high over terraced rice; its string runs off the edge, no flyer" },
  { id: "xe-banh-mi", word: null, inks: ["tile-ochre", "tile-cream", "ink-900"], atlas: null, what: "A bánh mì cart with a glass case and a parasol, no lettering" },
  { id: "cua-hieu", word: null, inks: ["tile-indigo", "lime-300", "sun-200", "tile-ochre", "tile-jade"], atlas: null, what: "A folding shophouse shutter half up, a potted plant and a stool" },
];
export const PAPER = "tile-cream";
// Inks no poster may use, as paint or light (Little Rose's unit colour, brick, error, any red or pink).
export const FORBIDDEN_INKS = ["unit-little-rose", "tile-brick", "tile-brick-lit", "error"];

// The atlases: 3 × 2 cells, each the print with a margin for its tape and the shadow it casts on the wall.
// 512 px for phones (< 600 px wide), 1024 px for Tier 1, 2048 px for Tier 2 and the stills. Budgets in KB.
export const ATLAS = {
  cols: 3, rows: 2,
  sizes: { 512: { cell: [168, 235], gutter: 2 }, 1024: { cell: [336, 470], gutter: 4 }, 2048: { cell: [672, 940], gutter: 8 } },
  budgetKB: { 512: 34, 1024: 100, 2048: 240 },
  // The print inside its cell (fractions of the cell): the rest is tape and shadow.
  margin: [36 / 672, 50 / 940],
};
// The print's own pixels at the 2048 atlas's scale (scripts render the art at this size).
export const PRINT_PX = [Math.round(672 * (1 - 2 * ATLAS.margin[0])), Math.round(940 * (1 - 2 * ATLAS.margin[1]))];
export const atlasSizeFor = ({ tier, width }) => (width < 600 ? 512 : tier >= 2 ? 2048 : 1024);

// A cell's uv rect in an atlas of `size` px: [u0, v0, u1, v1], v from the bottom (WebGL, flipY).
export function cellUv(index, size = 1024) {
  const s = ATLAS.sizes[size], col = index % ATLAS.cols, row = Math.floor(index / ATLAS.cols);
  const x0 = col * (s.cell[0] + s.gutter), y0 = row * (s.cell[1] + s.gutter);
  return [x0 / size, 1 - (y0 + s.cell[1]) / size, (x0 + s.cell[0]) / size, 1 - y0 / size];
}

// ---- where they hang ----
// The corridor, the print's centre at 1.6 m. "end": the corridor's far end wall, facing the whole walk (the one wall
// every frame of it looks toward); a unit's: on the door wall in its bay, on the side of its door away from Little Rose.
// (Live captures, M6: the door wall between the camera and the door it reads lies under the card's pane at 1440, so the
// end wall and the far side of the last door are where a print is seen.) Little Rose rule: no poster whose nearest edge is
// within LR_CLEAR (half a bay + 1.5 m) of Little Rose's door centre, in any order of the bays; nothing on its door,
// transom or pier. A poster that would break it is not hung (corridorPosters drops it).
export const LR_CLEAR = 6.5 / 2 + 1.5;
export const CORRIDOR_POSTERS = [
  { id: "cub-duoi-hang-me", at: "end", w: 0.68, x: 100.35 },
  { id: "dieu-dong-lua", unit: "green-bamboo", w: 0.5, off: 1.8 },
  { id: "ao-sen", unit: "cau-han", w: 0.56, off: 1.9 },
];
export const POSTER_Y = 1.6;
// Height of a print w metres wide (the prints are 5:7.2).
export const printH = (w) => (w * PRINT_PX[1]) / PRINT_PX[0];

// lay: journey.js corridorLayout(beats). Returns [{ id, wall, x, z, y, w, h }]: wall "door" (x is the door wall's) or
// "end" (z is the end wall's); z is the print's centre along the corridor.
export function corridorPosters(lay) {
  const lr = lay.doors.find((d) => d.unit === "little-rose");
  const lri = lay.doors.indexOf(lr);
  const out = [];
  for (const p of CORRIDOR_POSTERS) {
    let z, wall = "door";
    if (p.at === "end") { z = lay.end; wall = "end"; }
    else {
      const d = lay.doors.find((x) => x.unit === p.unit);
      if (!d) continue;
      // Away from Little Rose: toward the corridor's start when Little Rose is further down, else past the door.
      const s = lr && lri < d.index ? -1 : 1;
      z = d.zc + s * p.off;
    }
    const w = p.w, h = printH(w), reach = wall === "end" ? 0 : w / 2;
    if (lr && Math.abs(z - lr.zc) - reach <= LR_CLEAR) continue;
    out.push({ id: p.id, wall, x: p.x ?? null, z, y: POSTER_Y, w, h });
  }
  return out;
}

// S3's room (sets/shutters.js, the live set and its stills only): on the window wall either side of the window, so the
// slat light and the late sun cross them. z from the window's centre.
export const S3_POSTERS = [
  { id: "tet-hoa-mai", dz: -1.75, y: 1.55, w: 0.5 },
  { id: "ca-phe-phin", dz: 1.6, y: 1.35, w: 0.36 },
];
