import { raw } from "../lib/html.mjs";

// "Cửa mở" (M6 art direction, Get Involved; rebuilt in M9, Maxwell 13 Nov 2026: "make the animation on the right more
// realistic and fix it up"): a window in a thick shophouse wall, its two louvered wooden leaves swung in off the
// street, onto a late-morning Saigon street. No people, no children, no signage or lettering, no plate or badge on
// the scooter, nothing that names a real street. Every line is HTML or inline SVG (no request, never a photo).
//
// Construction (styles/pages.css, M9): the window box is the room-side outline of a deep, splayed opening. Inside it
// a 3D room (one perspective, its origin on the window) holds a handful of planes, painted with CSS gradients:
//   the street at the outer opening (the far end of the reveal), in a thin painted casement frame;
//   the soffit and the sill (horizontal, as deep as the wall; the sill runs out over a nosing into the room);
//   two jambs, splayed 22.76° so the leaves can fold back toward them;
//   two leaves, each hinged on its jamb's outer edge and exactly the opening's height, swinging in from 62° (scroll 0
//   on the stage) to 102° (the rest pose) without ever leaving the wall's thickness, so they never pass the outline
//   or cross the sill;
//   the sill's nosing, with the sun the sill throws back warming the wall under it.
// The sun is high and to the upper left, beyond the street: the right jamb, the sill and (as it opens) the right leaf
// are in sun; the left jamb, the soffit and the left leaf in shade; the leaves' shadows move with the angle.
//
// The street is the home's S3 street (world/objects/sky.js, M7) in the same inks, drawn in two depths: the far side
// (a row of two-storey shophouses in yellow and mint wash, arched upper windows with jade louvers and iron balconies,
// an ochre floor band, shop grilles half up with the shop dim behind and a lamp's glow at the back, the cream pavement
// with a parked Cub, the road, two sagging wires) and, nearer and in front of the haze, a tamarind on this side, its
// branches spreading into a fine crown that lies across the top with the sky through it. The two depths zoom at
// different rates on the stage (parallax). The sky and the haze between the depths are CSS, not SVG.
//
// Paint cost (M9 fix round): no SVG gradient anywhere (Chromium re-invalidates every gradient-filled SVG shape on each
// scrolled frame, so the street was rastered 120 times in a swing), no turbulence or blur filter. Soft edges are a few
// stacked shapes at low opacity; the crown's leaves are a pattern of leaflet clusters with gaps (a pattern of solid
// shapes is recorded once). About 70 elements in all (M8's drawing had about 340).

const v = (t) => `var(--color-${t})`;
// A mix of two tokens (oklab), as the world's mixc().
const mx = (a, b, p) => `color-mix(in oklab,${v(a)},${v(b)} ${p}%)`;
const mxc = (a, b, p) => `color-mix(in oklab,${a},${b} ${p}%)`;
const fill = (c, o) => `fill:${c}${o != null ? `;fill-opacity:${o}` : ""}`;
const stroke = (c, w, o) => `fill:none;stroke:${c}${w != null ? `;stroke-width:${w}` : ""}${o != null ? `;stroke-opacity:${o}` : ""};stroke-linecap:round;stroke-linejoin:round`;
const rng = (seed) => () => ((seed = (seed * 1664525 + 1013904223) >>> 0) / 4294967296);
const r1 = (n) => Math.round(n * 10) / 10;
// A circle and an ellipse (optionally turned, degrees) as path subpaths: one element carries many.
// Path data kept short (the drawing is inline in the page): whole units for anything bigger than a leaf, no space
// before a minus sign.
const tight = (d) => d.replace(/ -/g, "-");
const circ = (x, y, r) => {
  const q = r >= 4 ? Math.round : r1;
  return tight(`M${q(x - r)} ${q(y)}a${q(r)} ${q(r)} 0 1 0 ${q(2 * r)} 0a${q(r)} ${q(r)} 0 1 0 ${q(-2 * r)} 0`);
};
const ell = (x, y, rx, ry, rot = 0, round) => {
  rot = Math.round(rot) % 360;
  const c = Math.cos((rot * Math.PI) / 180), s = Math.sin((rot * Math.PI) / 180);
  const q = round ?? (rx >= 6 ? Math.round : r1);
  return tight(`M${q(x - rx * c)} ${q(y - rx * s)}a${q(rx)} ${q(ry)} ${rot} 1 0 ${q(2 * rx * c)} ${q(2 * rx * s)}a${q(rx)} ${q(ry)} ${rot} 1 0 ${q(-2 * rx * c)} ${q(-2 * rx * s)}`);
};
// A soft spot without a blur: the same shapes three times, each smaller and a little denser (one path per step).
const STEPS = [1, 0.88, 0.76, 0.64, 0.52, 0.4, 0.28, 0.16];
const soft = (shapes, color, o, steps = [1, 0.72, 0.46]) =>
  steps.map((k) => `<path d="${shapes(k)}" style="${fill(color, o)}"/>`).join("");

// Inks (S3's own mixes, sky.js viewMaterial), with the far side a little hazed toward the sky.
const INK = {
  vang: mx("world-vang-sun", "sun-100", 12),
  vangSh: mx("world-vang-shade", "world-vang-sun", 40),
  mint: mx("world-dado-sun", "lime-50", 50),
  mintSh: mx("world-dado-sun", "world-dado-shade", 30),
  band: mx("tile-ochre-lit", "sun-200", 40),
  cornice: mx("tile-cream", "lime-50", 30),
  shop: mx("ink-700", "world-wood", 45),
  shelf: mx("world-wood", "ink-700", 25),
  glow: v("sun-300"),
  iron: mx("ink-700", "blue-700", 30),
  pave: mx("tile-cream", "world-gach-shade", 25),
  curb: mx("world-gach-shade", "ink-700", 20),
  road: mx("world-gach-shade", "world-vang-shade", 35),
  far: mx("sun-100", "lime-300", 30),
  dapple: mx("sun-100", "sun-200", 50),
  // The Cub: a weathered jade body (lit top, body, underside), the cream leg shield, ink tyres.
  cubLt: mx("tile-jade-lit", "lime-50", 30),
  cub: mx("tile-jade-lit", "tile-jade", 45),
  cubDk: mx("tile-jade", "ink-900", 30),
  shield: mx("lime-50", "lime-100", 50),
  shieldIn: mx("lime-300", "ink-500", 20),
  tyre: mx("ink-700", "ink-900", 40),
  // The tamarind: bark lit, bark, bark in shade; the crown's leaves in shade, mid and sun.
  barkLt: mx("world-wood", "sun-300", 35),
  bark: v("world-wood"),
  barkDk: mx("world-wood", "ink-900", 40),
  leafDk: mx("tile-jade", "ink-900", 30),
  leaf: mx("tile-jade", "unit-green-bamboo", 25),
  leafLt: mx("tile-jade-lit", "sun-300", 40),
};

// The far side of the street (viewBox 300 x 400): sky above y 40 is the frame's CSS sky.
const far = () => {
  const rand = rng(913);
  // Arched French windows, one per bay (both in one path, filled with the louver pattern) and their frames.
  const arch = (x, w, y, h) => `M${x} ${y + h}V${y + w / 2}a${w / 2} ${w / 2} 0 0 1 ${w} 0V${y + h}Z`;
  const arches = arch(44, 52, 70, 104) + arch(204, 52, 70, 104);
  const mullion = (x) => `M${x + 26} 72V174`;
  // Iron balconies in front of the arches: rail, bottom bar and balusters.
  const rail = (x) => `M${x - 6} 156H${x + 58}M${x - 6} 176H${x + 58}` + Array.from({ length: 11 }, (_, i) => `M${x - 6 + i * 6.4} 156V176`).join("");
  // The tree's shade on this end of the street (it stands to the left, on the near side): a soft-edged dapple of
  // shade over the left shopfront, the pavement and the road, and sun spots in it.
  const shade = (k) => ell(26, 262, 56 * k + 14, 66 * k + 12);
  const spots = Array.from({ length: 14 }, (_, i) => {
    const ground = i > 3, x = 10 + rand() * 250, y = ground ? 314 + rand() * 66 : 96 + rand() * 150;
    const r = ground ? 4 + rand() * 7 : 2.4 + rand() * 3.4;
    return [x, y, r, ground ? r * 0.28 : r * 0.86];
  });
  const dapple = (k) => spots.map(([x, y, rx, ry]) => ell(x, y, rx * k + 0.6, ry * k + 0.3)).join("");
  // Under the cornice and the floor band, and under each balcony: their soft shadows on the wall.
  const ledges = (k) => `M-10 52H310V${r1(52 + 9 * k)}H-10ZM-10 198H310V${r1(198 + 8 * k)}H-10ZM38 176H102V${r1(176 + 6 * k)}H38ZM198 176H262V${r1(176 + 6 * k)}H198Z`;
  // The shops: the dim room behind the half-raised grilles, a back shelf, and a lamp's warm pool at the back.
  const glow = (k) => ell(84, 262, 34 * k + 4, 16 * k + 3) + ell(236, 250, 30 * k + 4, 14 * k + 3);
  // The Cub, facing right, parked on the pavement (y 333): step-through, leg shield, round headlamp; no plate, no badge.
  const cx = 96;
  const tyres = circ(cx - 26, 322, 9.6) + circ(cx + 26, 322, 9.6);
  const rims = circ(cx - 26, 322, 6.4) + circ(cx + 26, 322, 6.4);
  const body = `M${cx - 44} 305C${cx - 44} 296 ${cx - 36} 291 ${cx - 26} 291L${cx - 6} 292C${cx} 293 ${cx + 2} 298 ${cx + 2} 303L${cx + 3} 311H${cx + 8}L${cx + 8} 315H${cx - 12}C${cx - 14} 307 ${cx - 20} 303 ${cx - 28} 303C${cx - 36} 303 ${cx - 41} 307 ${cx - 42} 313Z` +
    `M${cx + 16} 279C${cx + 18} 274 ${cx + 26} 273 ${cx + 32} 275C${cx + 34} 278 ${cx + 32} 282 ${cx + 28} 282H${cx + 17}Z` +
    `M${cx + 14} 324C${cx + 14} 315 ${cx + 19} 309 ${cx + 26} 309C${cx + 33} 309 ${cx + 38} 314 ${cx + 39} 320L${cx + 36} 321C${cx + 34} 316 ${cx + 30} 313 ${cx + 26} 313C${cx + 21} 313 ${cx + 17} 317 ${cx + 17} 324Z`;
  const bodyLt = `M${cx - 42} 300C${cx - 40} 294 ${cx - 34} 292 ${cx - 26} 292L${cx - 7} 293C${cx - 3} 294 ${cx} 296 ${cx + 1} 299C${cx - 8} 297 ${cx - 30} 296 ${cx - 42} 300Z` +
    `M${cx + 17} 278C${cx + 20} 275 ${cx + 27} 274 ${cx + 31} 276C${cx + 26} 276 ${cx + 21} 277 ${cx + 17} 278Z`;
  const bodyDk = `M${cx - 42} 312C${cx - 41} 307 ${cx - 36} 303 ${cx - 28} 303C${cx - 20} 303 ${cx - 14} 307 ${cx - 12} 315H${cx - 16}C${cx - 18} 309 ${cx - 22} 306 ${cx - 28} 306C${cx - 34} 306 ${cx - 39} 309 ${cx - 40} 313Z` +
    `M${cx - 12} 315H${cx + 8}V312H${cx - 12}Z`;
  const shield = `M${cx + 2} 314C${cx + 1} 302 ${cx + 5} 290 ${cx + 13} 281L${cx + 18} 282C${cx + 13} 291 ${cx + 12} 302 ${cx + 15} 314Z`;
  const shieldIn = `M${cx + 2} 314C${cx + 1} 302 ${cx + 5} 290 ${cx + 13} 281L${cx + 14} 282C${cx + 8} 291 ${cx + 6} 302 ${cx + 7} 314Z`;
  const iron = `M${cx + 18} 281L${cx + 26} 322M${cx + 14} 276L${cx + 22} 272M${cx - 44} 290H${cx - 28}M${cx - 2} 325H${cx - 30}`;
  const seat = `M${cx - 40} 291C${cx - 38} 286 ${cx - 33} 285 ${cx - 26} 285L${cx - 8} 286C${cx - 5} 286 ${cx - 4} 289 ${cx - 5} 291Z`;
  const cubShadow = (k) => ell(cx, 333, 50 * k + 4, 3.4 * k + 1);
  return `<svg class="gi-street__art" viewBox="0 0 300 400" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
  <defs>
    <pattern id="gi-louver" width="6" height="4" patternUnits="userSpaceOnUse"><path d="M0 0H6V4H0Z" style="${fill(v("tile-jade"))}"/><path d="M0 0.6H6" style="${stroke(v("tile-jade-lit"), 1.1)}"/></pattern>
    <pattern id="gi-grille" width="8" height="3.4" patternUnits="userSpaceOnUse"><path d="M0 0H8V3.4H0Z" style="${fill(v("tile-cream"))}"/><path d="M0 2.6H8" style="${stroke(mx("lime-300", "world-gach-shade", 40), 1)}"/></pattern>
  </defs>
  <path d="M150 46V20C170 15 200 13 236 17V8H310V46Z" style="${fill(INK.far, 0.7)}"/>
  <path d="M-10 40H152V308H-10Z" style="${fill(INK.vang)}"/>
  <path d="M152 40H310V308H152Z" style="${fill(INK.mint)}"/>
  <path d="M-10 206H152V308H-10Z" style="${fill(INK.vangSh, 0.55)}"/>
  <path d="M152 206H310V308H152Z" style="${fill(INK.mintSh, 0.5)}"/>
  <path d="M-10 40H310V52H-10ZM-10 186H310V198H-10ZM146 52H158V308H146Z" style="${fill(INK.band)}"/>
  <path d="M-10 40H310" style="${stroke(INK.cornice, 2.4)}"/>
  ${soft(ledges, v("ink-900"), 0.06)}
  <path d="${arches}" fill="url(#gi-louver)"/>
  <path d="${arches}${mullion(44)}${mullion(204)}" style="${stroke(INK.cornice, 2.6)}"/>
  <path d="${rail(44)}${rail(204)}" style="${stroke(INK.iron, 1.1, 0.85)}"/>
  <path d="M12 254H140V302H12ZM162 232H290V302H162Z" style="${fill(INK.shop)}"/>
  <path d="M18 270H134M168 262H284M20 284H130" style="${stroke(INK.shelf, 2.4, 0.7)}"/>
  ${soft(glow, INK.glow, 0.035, STEPS)}
  <path d="M12 206H140V254H12ZM162 206H290V232H162Z" fill="url(#gi-grille)"/>
  <path d="M12 252H140V256H12ZM162 230H290V234H162Z" style="${fill(INK.curb, 0.6)}"/>
  <path d="M-10 308H310V338H-10Z" style="${fill(INK.pave)}"/>
  <path d="M-10 336H310V340H-10Z" style="${fill(INK.curb)}"/>
  <path d="M-10 340H310V410H-10Z" style="${fill(INK.road)}"/>
  ${soft(shade, v("ink-900"), 0.02, STEPS)}
  ${soft(dapple, INK.dapple, 0.15)}
  ${soft(cubShadow, v("ink-900"), 0.12)}
  <g class="gi-cub">
    <path d="${tyres}" style="${stroke(INK.tyre, 2.2)}"/>
    <path d="${rims}" style="${stroke(mx("lime-300", "ink-500", 30), 0.8, 0.8)}"/>
    <path d="${iron}" style="${stroke(v("ink-700"), 1.6)}"/>
    <path d="${body}" style="${fill(INK.cub)}"/>
    <path d="${bodyDk}" style="${fill(INK.cubDk)}"/>
    <path d="${bodyLt}" style="${fill(INK.cubLt)}"/>
    <path d="${seat}" style="${fill(INK.shop)}"/>
    <path d="${shield}" style="${fill(INK.shield)}"/>
    <path d="${shieldIn}" style="${fill(INK.shieldIn)}"/>
    <path d="${circ(cx + 30, 278.5, 1.9)}" style="${fill(v("sun-100"))}"/>
  </g>
  <path d="M-10 98C80 124 190 128 310 92M-10 110C100 138 200 138 310 106" style="${stroke(v("ink-700"), 0.8, 0.4)}"/>
</svg>`;
};

// The tamarind on this side of the street, nearer than the haze: trunk, branches into the crown, and the crown as
// leaflet clusters (patterns of small solid shapes with gaps, in shade, mid and sun) over lobes heavier to the left.
const tree = () => {
  const rand = rng(4217);
  // Each lobe is dense inside (two layers of sprigs) and thin at its rim (one sparse layer), so the crown's outline is
  // ragged leaves rather than a circle; the sunlit lobes on its upper left take a third, light layer.
  const lobes = [], rims = [], lit = [], core = [];
  for (let i = 0; i < 24; i++) {
    const x = -34 + rand() * 350, left = 1 - Math.min(1, Math.max(0, x / 310));
    const y = -4 + rand() * (24 + 60 * left), r = 13 + rand() * 15 + 10 * left;
    lobes.push(circ(x, y, r * 0.76));
    rims.push(circ(x, y, r * 0.98));
    core.push(circ(x + (rand() - 0.5) * r * 0.3, y - r * 0.1, r * 0.42));
    if (y < 36 && x < 250) lit.push(circ(x - r * 0.25, y - r * 0.3, r * 0.6));
  }
  // Drooping fringes under the crown: small lobes, more to the left.
  for (let i = 0; i < 14; i++) {
    const x = -6 + rand() * 280, left = 1 - Math.min(1, Math.max(0, x / 280));
    rims.push(circ(x, 42 + 50 * left + rand() * 14, 5 + rand() * 8));
  }
  // A sprig: five to seven small leaflets radiating at random from one point (tamarind leaves are fine and feathery);
  // a tile holds a dozen or so, and the three layers use tiles of different sizes, turned, so no repeat lines up.
  // Drawn at twice the size in whole units (short path data) and scaled down by the pattern.
  const tile = (w, h, n, seed) => {
    const t = rng(seed);
    return Array.from({ length: n }, () => {
      const x = t() * w, y = t() * h, a0 = t() * 360;
      return Array.from({ length: 4 + Math.floor(t() * 3) }, (_, j) => {
        const a = a0 + j * (48 + t() * 30), d = 1.4 + t() * 1.2, rad = (a * Math.PI) / 180;
        return ell(2 * (x + d * Math.cos(rad)), 2 * (y + d * Math.sin(rad)), 2 * (2.3 + t() * 1.1), 2 * (1.1 + t() * 0.35), a, Math.round);
      }).join("");
    }).join("");
  };
  // One pattern may carry two layers (shade and mid), so the dense lobes are filled once.
  const pat = (id, w, h, turn, ...layers) => `<pattern id="${id}" width="${2 * w}" height="${2 * h}" patternUnits="userSpaceOnUse" patternTransform="rotate(${turn}) scale(0.5)">${layers.map(([n, seed, color]) => `<path d="${tile(w, h, n, seed)}" style="${fill(color)}"/>`).join("")}</pattern>`;
  // A limb that tapers from w0 to w1 along a quadratic curve through (cx, cy).
  const limb = (x0, y0, cx, cy, x1, y1, w0, w1) => {
    const dx = x1 - x0, dy = y1 - y0, l = Math.hypot(dx, dy), nx = -dy / l, ny = dx / l, wm = (w0 + w1) / 2;
    const P = (x, y, w, k) => `${r1(x + nx * w * k)} ${r1(y + ny * w * k)}`;
    return `M${P(x0, y0, w0, 1)}Q${P(cx, cy, wm, 1)} ${P(x1, y1, w1, 1)}L${P(x1, y1, w1, -1)}Q${P(cx, cy, wm, -1)} ${P(x0, y0, w0, -1)}Z`;
  };
  const trunk = "M-8 400C0 352 6 292 4 220C3 170 10 128 28 92L46 98C32 132 28 176 30 228C32 296 32 352 28 400Z";
  const trunkLt = "M2 400C9 352 13 292 11 222C10 172 16 132 32 95L37 97C23 132 19 174 20 224C22 292 18 352 11 400Z";
  const trunkDk = "M19 400C25 352 25 292 23 226C22 176 27 134 41 97L46 98C32 132 28 176 30 228C32 296 32 352 28 400Z";
  const limbs = limb(34, 100, 54, 66, 108, 50, 5, 1.4) + limb(30, 96, 38, 60, 20, 22, 4.4, 1.2) + limb(40, 104, 80, 92, 146, 70, 4, 1) +
    limb(24, 124, 10, 100, -10, 90, 3.6, 1.4) + limb(70, 62, 86, 40, 82, 18, 2, 0.8) + limb(104, 88, 128, 70, 150, 44, 1.8, 0.7);
  return `<svg class="gi-street__tree" viewBox="0 0 300 400" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
  <defs>${pat("gi-lv", 25, 21, 17, [15, 11, INK.leafDk], [11, 23, INK.leaf])}${pat("gi-lv-lt", 19, 17, 41, [8, 37, INK.leafLt])}${pat("gi-lv-fr", 27, 23, -61, [6, 53, INK.leaf])}</defs>
  <path d="${core.join("")}" style="${fill(INK.leafDk, 0.3)}"/>
  <path d="${trunk}" style="${fill(INK.bark)}"/>
  <path d="${trunkLt}" style="${fill(INK.barkLt)}"/>
  <path d="${trunkDk}" style="${fill(INK.barkDk, 0.7)}"/>
  <path d="${limbs}" style="${fill(INK.barkDk)}"/>
  <path d="${lobes.join("")}" fill="url(#gi-lv)"/>
  <path d="${rims.join("")}" fill="url(#gi-lv-fr)"/>
  <path d="${lit.join("")}" fill="url(#gi-lv-lt)"/>
</svg>`;
};

export const involveWindow = () => raw(`<div class="gi-stage" aria-hidden="true">
  <div class="gi-window" data-art>
    <div class="gi-room">
      <div class="gi-street">${far()}<span class="gi-haze"></span>${tree()}<span class="gi-sunwash"></span></div>
      <span class="gi-soffit"></span>
      <span class="gi-sill"></span>
      <span class="gi-jamb gi-jamb--l"></span>
      <span class="gi-jamb gi-jamb--r"></span>
      <span class="gi-leaf gi-leaf--l"></span>
      <span class="gi-leaf gi-leaf--r"></span>
      <span class="gi-ledge"></span>
    </div>
  </div>
</div>`);
