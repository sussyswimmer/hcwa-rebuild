// The wall posters' art (M6; src/world/wall-posters-data.js has the set and the rules). Each design is drawn as a
// procedural SVG from seeded numbers, in flat token inks on cream paper: a halftone screen in its shadows, the key plate
// printed twice a little off register. No lettering at all (M6 fix: words drawn into a texture are neither HTML nor
// bilingual), no <image>, no star shapes, no red or pink (test/wall-posters.test.mjs reads these sources).
//   node scripts/wall-posters.mjs          rasterise every design at the 2048 atlas's print size (headless Chromium)
//                                          into node_modules/.cache/wall-posters/, then run
//   python3 -I scripts/encode-wall-posters.py   to print, age and pack them (public/textures/wall-posters-*.webp)
import { mkdirSync, writeFileSync } from "node:fs";
import { fileURLToPath } from "node:url";
import { createRequire } from "node:module";
import { color as TOK } from "../src/world/tokens.js";
import { PAPER, PRINT_PX, WALL_POSTERS } from "../src/world/wall-posters-data.js";

export const W = 300, H = 420; // design units (the print is 0.5 × 0.72 m)
const ink = (name) => {
  const v = TOK[name];
  if (!v) throw new Error(`wall-posters: no token ${name}`);
  return v;
};
function rng(seed) {
  let a = seed >>> 0;
  return () => { a = (a + 0x6d2b79f5) >>> 0; let t = a; t = Math.imul(t ^ (t >>> 15), t | 1); t ^= t + Math.imul(t ^ (t >>> 7), t | 61); return ((t ^ (t >>> 14)) >>> 0) / 4294967296; };
}
const f = (n) => Math.round(n * 100) / 100;
const at = (attrs) => Object.entries(attrs).filter(([, v]) => v != null).map(([k, v]) => ` ${k}="${typeof v === "number" ? f(v) : v}"`).join("");
const el = (tag, attrs = {}, inner = "") => (inner === "" ? `<${tag}${at(attrs)}/>` : `<${tag}${at(attrs)}>${inner}</${tag}>`);
const circle = (cx, cy, r, fill, extra = {}) => el("circle", { cx, cy, r, fill, ...extra });
const ellipse = (cx, cy, rx, ry, fill, extra = {}) => el("ellipse", { cx, cy, rx, ry, fill, ...extra });
const rect = (x, y, w, h, fill, extra = {}) => el("rect", { x, y, width: w, height: h, fill, ...extra });
const path = (d, fill, extra = {}) => el("path", { d, fill, ...extra });
const line = (x1, y1, x2, y2, stroke, sw, extra = {}) => el("line", { x1, y1, x2, y2, stroke, "stroke-width": sw, "stroke-linecap": "round", ...extra });

// A halftone screen in an ink: 45°, dots 3.6 units apart (the shadows' tone).
const screen = (id, c, r = 1.15) => el("pattern", { id, width: 3.6, height: 3.6, patternUnits: "userSpaceOnUse", patternTransform: "rotate(45)" }, circle(1.8, 1.8, r, c));
// The key plate twice: a second impression in `ghost` 1.4 / 0.9 units off, under the key (misregistration).
const key = (inner, ghost) => el("g", { transform: "translate(1.4 0.9)", opacity: 0.5 }, inner.replaceAll(/fill="#[0-9A-Fa-f]{6}"/g, `fill="${ghost}"`).replaceAll(/stroke="#[0-9A-Fa-f]{6}"/g, `stroke="${ghost}"`)) + inner;
const svg = (defs, body) => `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${W} ${H}" width="${W}" height="${H}">${el("defs", {}, defs)}${rect(0, 0, W, H, ink(PAPER))}${body}</svg>`;

// A five-petal blossom: five discs round a centre (never a star polygon).
const blossom = (x, y, r, petal, heart) => [0, 1, 2, 3, 4].map((k) => { const a = (k / 5) * Math.PI * 2 - Math.PI / 2; return circle(x + Math.cos(a) * r * 0.62, y + Math.sin(a) * r * 0.62, r * 0.52, petal); }).join("") + circle(x, y, r * 0.3, heart);

const DESIGNS = {
  // Xuân: a hoa mai branch across a large pale sun, the flower market's baskets along the foot.
  "tet-hoa-mai"() {
    const r = rng(11), ochre = ink("tile-ochre"), sun = ink("sun-300"), jade = ink("tile-jade"), k = ink("ink-900");
    const defs = screen("htj", jade) + screen("hto", ochre, 1.0);
    let body = circle(178, 150, 112, sun);
    // The branch: a tapering line from the lower left up across the sun, with two side shoots.
    const twig = (pts, w) => pts.slice(1).map((p, i) => line(pts[i][0], pts[i][1], p[0], p[1], k, w * (1 - i / pts.length) + 1)).join("");
    const main = [[18, 300], [70, 250], [118, 214], [168, 168], [214, 132], [262, 96], [292, 84]];
    let br = twig(main, 7) + twig([[118, 214], [140, 252], [170, 270]], 4) + twig([[214, 132], [226, 96], [236, 60]], 3.5) + twig([[168, 168], [196, 196], [232, 204]], 3);
    let flowers = "";
    const spots = [...main.slice(1), [140, 252], [170, 270], [226, 96], [236, 60], [196, 196], [232, 204], [92, 232], [244, 116], [150, 190]];
    for (const [x, y] of spots) {
      const n = 1 + Math.floor(r() * 2);
      for (let i = 0; i < n; i++) flowers += blossom(x + (r() - 0.5) * 22, y + (r() - 0.5) * 18, 6 + r() * 5, i % 2 ? ink("tile-ochre-lit") : ochre, k);
    }
    body += key(br, jade) + flowers;
    // The market: baskets of flowers in a row, flat shapes with a halftone shade.
    let foot = rect(0, 336, W, 84, jade, { opacity: 0.18 });
    for (let i = 0; i < 6; i++) {
      const x = 8 + i * 49 + (r() - 0.5) * 6, w = 40, top = 346 + (r() - 0.5) * 6;
      foot += path(`M${f(x)} ${f(top + 20)} L${f(x + w)} ${f(top + 20)} L${f(x + w - 6)} 412 L${f(x + 6)} 412 Z`, i % 2 ? ochre : jade);
      foot += path(`M${f(x + 6)} ${f(top + 30)} L${f(x + w - 6)} ${f(top + 30)} L${f(x + w - 8)} 412 L${f(x + 8)} 412 Z`, "url(#hto)", { opacity: 0.9 });
      for (let j = 0; j < 4; j++) foot += circle(x + 6 + j * 9.5, top + 14 - (j % 2) * 5, 6.5, j % 2 ? sun : ink("tile-ochre-lit"));
    }
    body += foot;
    return svg(defs, body);
  },
  // Cà phê: a phin dripping into a glass, a low stool in halftone, steam.
  "ca-phe-phin"() {
    const wood = ink("world-wood"), cream = ink("tile-cream"), indigo = ink("tile-indigo");
    const defs = screen("hti", indigo, 1.1) + screen("htw", wood, 1.2);
    let body = rect(0, 0, W, 250, wood) + rect(0, 250, W, 170, "url(#htw)", { opacity: 0.55 });
    // The stool (low plastic, seen from the side): seat and four legs, in the halftone.
    body += path("M60 300 L240 300 L232 312 L68 312 Z", indigo) + path("M74 312 L92 312 L80 404 L64 404 Z M208 312 L226 312 L236 404 L220 404 Z", "url(#hti)") + path("M74 312 L92 312 L80 404 L64 404 Z M208 312 L226 312 L236 404 L220 404 Z", "none", { stroke: indigo, "stroke-width": 2 });
    // The glass, the coffee in it, the condensed milk at its foot.
    body += path("M112 196 L188 196 L182 298 L118 298 Z", cream) + path("M116 240 L184 240 L182 298 L118 298 Z", wood) + path("M118 282 L182 282 L182 298 L118 298 Z", ink("tile-cream"), { opacity: 0.85 });
    const phin = rect(120, 150, 60, 40, indigo, { rx: 3 }) + rect(110, 186, 80, 8, indigo, { rx: 2 }) + rect(132, 140, 36, 12, indigo, { rx: 3 }) + circle(150, 136, 5, indigo);
    body += key(phin, cream);
    // Drops.
    for (const [y, r] of [[204, 2.6], [216, 2.2], [228, 1.8]]) body += ellipse(150, y, r * 0.8, r * 1.2, indigo);
    // Steam: three curls over the phin.
    for (const [x, s] of [[136, 1], [152, -1], [168, 1]]) body += path(`M${x} 128 C${x + 10 * s} 112 ${x - 10 * s} 100 ${x} 84 C${x + 8 * s} 72 ${x - 6 * s} 62 ${x} 50`, "none", { stroke: cream, "stroke-width": 3, "stroke-linecap": "round", opacity: 0.85 });
    return svg(defs, body);
  },
  // A Honda-Cub-type step-through (no badge, no plate, no rider) parked under a tamarind, the light through its fine
  // leaves in dots: the step-through backbone, the front leg shield, small wheels, the rounded rear body and seat, the
  // headlight in the handlebar cover. Tamarind leaves are pinnate: a thin rachis with many tiny paired leaflets.
  "cub-duoi-hang-me"() {
    const r = rng(23), jade = ink("tile-jade"), sun = ink("sun-200"), indigo = ink("tile-indigo"), cream = ink("tile-cream");
    const defs = screen("htj", jade, 1.25);
    let body = rect(0, 0, W, 196, "url(#htj)", { opacity: 0.55 });
    // The tamarind: two branches in from the top corners, each carrying sprays of pinnate leaves that droop a little.
    const leaf = (x, y, ang, len) => {
      const pts = [];
      for (let t = 0; t <= 1.0001; t += 1 / 16) pts.push([x + Math.cos(ang) * len * t, y + Math.sin(ang) * len * t + 10 * t * t]);
      let out = path("M" + pts.map(([px, py]) => `${f(px)} ${f(py)}`).join(" L"), "none", { stroke: jade, "stroke-width": 0.9 });
      for (let i = 1; i < pts.length; i++) {
        const [px, py] = pts[i], deg = (ang * 180) / Math.PI;
        for (const s of [-1, 1]) out += ellipse(px + Math.cos(ang + s * 1.35) * 3.2, py + Math.sin(ang + s * 1.35) * 3.2, 3.4, 1.25, jade, { transform: `rotate(${f(deg + s * 70)} ${f(px + Math.cos(ang + s * 1.35) * 3.2)} ${f(py + Math.sin(ang + s * 1.35) * 3.2)})` });
      }
      return out;
    };
    const branch = (pts) => pts.slice(1).map((q, i) => line(pts[i][0], pts[i][1], q[0], q[1], indigo, 5 - i * 1.1)).join("");
    const left = [[-6, 18], [60, 44], [120, 58], [168, 92]], right = [[306, 6], [250, 34], [208, 40]];
    body += branch(left) + branch(right);
    for (const [pts] of [[left], [right]]) for (let i = 1; i < pts.length; i++) {
      const [x0, y0] = pts[i - 1], [x1, y1] = pts[i];
      for (let k = 0; k < 6; k++) {
        const t = r(), x = x0 + (x1 - x0) * t, y = y0 + (y1 - y0) * t;
        body += leaf(x, y, (r() < 0.5 ? 0.4 : 2.5) + (r() - 0.5) * 0.9, 34 + r() * 30);
      }
    }
    // Dappled light on the ground and across the scooter.
    for (let i = 0; i < 40; i++) body += ellipse(r() * W, 258 + r() * 150, 4 + r() * 7, 2 + r() * 3, sun, { opacity: 0.9 });
    // The scooter, facing left: small wheels far apart, the low step-through between them.
    const wheel = (x) => circle(x, 376, 25, indigo) + circle(x, 376, 15, cream) + circle(x, 376, 5, indigo);
    const bike = wheel(76) + wheel(230)
      // the rear body: a rounded shell over the engine and the rear wheel
      + path("M168 352 C164 322 176 300 204 296 L246 296 C268 298 280 318 278 344 L268 352 Z", jade)
      // the rear fender skirt over the wheel's top
      + path("M200 352 C204 334 218 326 234 328 C250 330 262 340 264 352 Z", indigo, { opacity: 0.35 })
      // the seat and the rack behind it
      + rect(184, 284, 66, 13, indigo, { rx: 6 }) + line(250, 290, 276, 290, indigo, 3) + line(276, 290, 274, 300, indigo, 3)
      // the backbone: from the steering head down in a low swoop to the rear body (the step-through)
      + path("M104 262 C112 312 132 346 172 346 L172 336 C140 336 122 306 116 262 Z", jade)
      // the floorboard and the engine under it
      + rect(118, 346, 62, 8, indigo, { rx: 3 }) + path("M140 354 L178 354 L182 370 L146 372 Z", indigo)
      // the exhaust along the right side to the back
      + path("M150 368 C190 376 236 372 268 362", "none", { stroke: indigo, "stroke-width": 4, "stroke-linecap": "round" })
      // the front leg shield: a broad curved panel from the head down to the floor, cream with its rim
      + path("M98 248 C74 280 78 328 108 352 L138 352 C114 326 110 288 124 254 Z", cream, { stroke: indigo, "stroke-width": 2.2 })
      // the fork and the front fender
      + line(100, 262, 78, 376, indigo, 4) + path("M50 372 C52 346 70 334 90 338 C100 340 106 348 106 356 L96 354 C94 346 86 344 78 346 C66 348 60 358 60 372 Z", jade)
      // the handlebar cover with the headlight in it, the grips
      + path("M84 248 C84 236 100 230 118 232 C128 234 130 246 124 254 L92 258 C86 258 84 254 84 248 Z", jade)
      + ellipse(86, 248, 7, 8, cream, { stroke: indigo, "stroke-width": 1.6 }) + line(106, 236, 130, 228, indigo, 3.2);
    body += key(bike, sun);
    return svg(defs, body);
  },
  // Sen: a lotus pond. Lotus leaves are round and stand above the water on their stems (unlike water lilies, whose
  // notched pads float), cupped a little; one open flower with pointed petals, cream with apricot (never pink); a bud;
  // a seed pod (gương sen) with its seed holes; ripples and a dragonfly.
  "ao-sen"() {
    const r = rng(37), jade = ink("tile-jade"), lit = ink("tile-jade-lit"), cream = ink("tile-cream"), blue = ink("tile-indigo-lit"), deep = ink("tile-indigo"), apricot = ink("sun-300"), sun = ink("sun-200");
    const defs = screen("htb", blue, 1.0) + screen("htj", jade, 1.1);
    let body = rect(0, 250, W, 170, "url(#htb)", { opacity: 0.45 });
    for (let i = 0; i < 6; i++) body += ellipse(150 + (r() - 0.5) * 50, 330 + i * 16, 128 - i * 8, 5 + i, "none", { stroke: blue, "stroke-width": 1.3, opacity: 0.7 });
    // A raised lotus leaf: round, seen from a little above (an ellipse), its rim slightly wavy, cupped (a darker inner
    // bowl toward the far side), veins from the centre where the stem meets it. No notch.
    const lotusLeaf = (x, y, rr, tilt) => {
      const n = 28, rim = [];
      for (let k = 0; k < n; k++) { const a = (k / n) * Math.PI * 2, w = 1 + 0.05 * Math.sin(a * 7 + x); rim.push([x + Math.cos(a) * rr * w, y + Math.sin(a) * rr * 0.5 * w]); }
      const d = "M" + rim.map(([px, py]) => `${f(px)} ${f(py)}`).join(" L") + " Z";
      let g = path(d, jade) + ellipse(x, y - rr * 0.08, rr * 0.7, rr * 0.3, "url(#htj)", { opacity: 0.9 });
      for (let k = 0; k < 12; k++) { const a = (k / 12) * Math.PI * 2; g += line(x, y, x + Math.cos(a) * rr * 0.9, y + Math.sin(a) * rr * 0.45, lit, 1); }
      g += circle(x, y, 3, lit);
      return el("g", { transform: `rotate(${tilt} ${f(x)} ${f(y)})` }, g);
    };
    // Stems first (the leaves and flowers stand on them), from below the water line.
    for (const [x0, x1, y1, w] of [[66, 62, 236, 3.4], [238, 232, 206, 3.2], [168, 172, 292, 3], [128, 124, 150, 3.4], [258, 250, 112, 2.8], [34, 40, 116, 2.6]]) body += path(`M${x0} 400 C${x0 + 6} 340 ${x1 - 6} ${y1 + 40} ${x1} ${y1}`, "none", { stroke: jade, "stroke-width": w, "stroke-linecap": "round" });
    body += lotusLeaf(62, 236, 56, -6) + lotusLeaf(232, 206, 48, 8) + lotusLeaf(172, 292, 42, -3);
    // The open flower: pointed petals in two rings, cream outside, apricot toward the heart, round the seed head.
    const petal = (x, y, a, len, wd, c) => el("path", { d: `M0 0 Q${f(wd)} ${f(-len * 0.45)} 0 ${f(-len)} Q${f(-wd)} ${f(-len * 0.45)} 0 0 Z`, fill: c, stroke: jade, "stroke-width": 1, transform: `translate(${f(x)} ${f(y)}) rotate(${f(a)})` });
    let flower = circle(124, 128, 70, "url(#htj)", { opacity: 0.55 });
    for (const a of [-78, -50, 50, 78, -22, 22]) flower += petal(124, 156, a, 52, 15, cream);
    for (const a of [-34, 34, -10, 10, 0]) flower += petal(124, 156, a, 44, 12, a === 0 ? apricot : cream);
    flower += ellipse(124, 132, 13, 5, sun) + ellipse(124, 133, 9, 3, lit);
    body += flower;
    // A bud on its own stem.
    body += path("M40 116 C26 98 30 74 40 58 C50 74 54 98 40 116 Z", cream, { stroke: jade, "stroke-width": 1.2 }) + path("M40 116 C34 102 35 86 40 74 C45 86 46 102 40 116 Z", apricot);
    // The seed pod: a flat top full of seed holes over a cone that narrows to its stem, bending a little.
    const pod = path("M234 92 C236 104 244 110 250 112 C256 110 264 104 266 92 Z", lit) + ellipse(250, 92, 16, 5.5, ink("tile-jade-lit"), { stroke: jade, "stroke-width": 1.2 })
      + [[-9, -1], [-3, -2], [3, -2], [9, -1], [-6, 1.6], [0, 1.4], [6, 1.6]].map(([dx, dy]) => circle(250 + dx, 92 + dy, 1.5, deep)).join("");
    body += el("g", { transform: "rotate(-14 250 112)" }, pod);
    // A dragonfly over the water.
    const fly = line(178, 60, 216, 80, deep, 2.4) + ellipse(190, 60, 14, 4, blue, { transform: "rotate(-60 190 60)", opacity: 0.8 }) + ellipse(198, 74, 14, 4, blue, { transform: "rotate(40 198 74)", opacity: 0.8 }) + ellipse(186, 66, 12, 3.6, blue, { transform: "rotate(-20 186 66)", opacity: 0.8 }) + ellipse(194, 56, 12, 3.6, blue, { transform: "rotate(70 194 56)", opacity: 0.8 });
    body += key(fly, jade);
    return svg(defs, body);
  },
  // Mưa: rain over shophouse shutters, the street wet with their reflections, one bicycle.
  "pho-mua"() {
    const r = rng(41), indigo = ink("tile-indigo"), lime = ink("lime-300"), sun = ink("sun-200");
    const defs = screen("hti", indigo, 1.2);
    let body = rect(0, 0, W, 280, lime);
    // Three shopfronts with folding shutters (horizontal ribs), one half open to a lit room.
    for (let i = 0; i < 3; i++) {
      const x = 12 + i * 96;
      body += rect(x, 40, 84, 232, indigo, { opacity: 0.92 });
      const open = i === 1 ? 150 : 232;
      body += rect(x + 6, 46, 72, open - 6, lime, { opacity: 0.25 });
      for (let y = 50; y < 40 + open; y += 7) body += line(x + 6, y, x + 78, y, indigo, 2.4);
      if (i === 1) body += rect(x + 6, 40 + open, 72, 232 - open, sun);
    }
    body += rect(0, 272, W, 148, "url(#hti)", { opacity: 0.55 });
    // Reflections: broken strokes under each front.
    for (let i = 0; i < 26; i++) { const x = 18 + r() * 264, y = 290 + r() * 110; body += line(x, y, x + 10 + r() * 22, y, i % 3 ? lime : sun, 2.2, { opacity: 0.85 }); }
    const bike = circle(196, 360, 22, "none", { stroke: indigo, "stroke-width": 3.4 }) + circle(256, 360, 22, "none", { stroke: indigo, "stroke-width": 3.4 }) + path("M196 360 L220 324 L248 324 L256 360 M220 324 L230 360 L196 360 M248 324 L252 314 M214 318 L230 318", "none", { stroke: indigo, "stroke-width": 3.4, "stroke-linejoin": "round" });
    body += key(bike, sun);
    // Rain: long slanted lines over everything.
    for (let i = 0; i < 90; i++) { const x = r() * (W + 60) - 30, y = r() * H, l = 18 + r() * 26; body += line(x, y, x - l * 0.28, y + l, ink("tile-cream"), 1.1, { opacity: 0.55 }); }
    return svg(defs, body);
  },
  // One kite high over terraced rice; its string runs off the lower edge, no flyer.
  "dieu-dong-lua"() {
    const jade = ink("tile-jade"), sun = ink("sun-300"), indigo = ink("tile-indigo"), lit = ink("tile-jade-lit");
    const defs = screen("htj", jade, 1.2);
    let body = rect(0, 0, W, 220, sun);
    // Terraces: curved bands, alternating jade and its lit tint, the lower ones in halftone.
    for (let i = 0; i < 9; i++) {
      const y = 196 + i * 26, c = i % 2 ? jade : lit, sag = 18 + i * 3;
      body += path(`M0 ${y} C90 ${y - sag} 210 ${y + sag} 300 ${y - 8} L300 420 L0 420 Z`, i > 5 ? "url(#htj)" : c);
      body += path(`M0 ${y} C90 ${y - sag} 210 ${y + sag} 300 ${y - 8}`, "none", { stroke: ink("tile-cream"), "stroke-width": 1.2, opacity: 0.6 });
    }
    // The kite: a diamond with its spars, a long tail of bows. Its string runs off the lower edge.
    const kite = path("M196 54 L232 92 L196 146 L160 92 Z", indigo) + line(196, 54, 196, 146, ink("tile-cream"), 1.4) + line(160, 92, 232, 92, ink("tile-cream"), 1.4)
      + path("M196 146 C188 170 206 190 194 214 C184 234 202 252 192 272", "none", { stroke: indigo, "stroke-width": 1.6 })
      + [168, 196, 226, 254].map((y, i) => path(`M${190 + (i % 2) * 8} ${y} l-8 -5 l0 10 Z M${190 + (i % 2) * 8} ${y} l8 -5 l0 10 Z`, indigo)).join("");
    body += key(kite, jade) + path("M196 118 C150 210 110 300 40 420", "none", { stroke: ink("ink-900"), "stroke-width": 0.9, opacity: 0.7 });
    return svg(defs, body);
  },
  // No lettering: a bánh mì cart, its glass case and a parasol.
  "xe-banh-mi"() {
    const ochre = ink("tile-ochre"), cream = ink("tile-cream"), k = ink("ink-900");
    const defs = screen("hto", ochre, 1.2);
    let body = rect(0, 0, W, H, "url(#hto)", { opacity: 0.4 });
    for (let i = 0; i < 6; i++) { const a0 = Math.PI + (i / 6) * Math.PI, a1 = Math.PI + ((i + 1) / 6) * Math.PI; body += path(`M150 120 L${f(150 + 130 * Math.cos(a0))} ${f(120 + 70 * Math.sin(a0))} L${f(150 + 130 * Math.cos(a1))} ${f(120 + 70 * Math.sin(a1))} Z`, i % 2 ? cream : ochre); }
    body += line(150, 120, 150, 230, k, 4);
    const cart = rect(54, 230, 192, 80, ochre) + rect(64, 186, 172, 50, cream, { stroke: k, "stroke-width": 3 }) + line(64, 211, 236, 211, k, 1.5)
      + [84, 112, 140, 168, 196].map((x) => ellipse(x + 8, 200, 12, 5, ochre)).join("") + circle(86, 338, 24, k) + circle(214, 338, 24, k) + circle(86, 338, 9, cream) + circle(214, 338, 9, cream) + line(246, 256, 286, 240, k, 4);
    body += key(cart, ochre);
    return svg(defs, body);
  },
  // No lettering: a folding shophouse shutter half up, a potted plant and a stool.
  "cua-hieu"() {
    const indigo = ink("tile-indigo"), lime = ink("lime-300"), sun = ink("sun-200");
    const defs = screen("hti", indigo, 1.2);
    let body = rect(0, 0, W, H, lime) + rect(30, 30, 240, 330, sun);
    for (let y = 34; y < 200; y += 8) body += line(30, y, 270, y, indigo, 4);
    body += rect(30, 200, 240, 160, "url(#hti)", { opacity: 0.5 }) + rect(0, 360, W, 60, indigo);
    const pot = path("M200 360 L250 360 L242 316 L208 316 Z", ink("tile-ochre")) + [0, 1, 2, 3, 4, 5].map((i) => ellipse(225 + (i - 2.5) * 10, 290 - (i % 2) * 14, 7, 20, ink("tile-jade"), { transform: `rotate(${(i - 2.5) * 14} ${225 + (i - 2.5) * 10} ${290 - (i % 2) * 14})` })).join("");
    const stool = rect(70, 320, 70, 8, indigo) + path("M76 328 L84 328 L80 360 L72 360 Z M126 328 L134 328 L138 360 L130 360 Z", indigo);
    body += key(pot + stool, indigo);
    return svg(defs, body);
  },
};

export const posterSvg = (id) => {
  const d = DESIGNS[id];
  if (!d) throw new Error(`wall-posters: no design for ${id}`);
  return d();
};
export const designIds = () => Object.keys(DESIGNS);

export const CACHE = fileURLToPath(new URL("../node_modules/.cache/wall-posters/", import.meta.url));

if (process.argv[1] && fileURLToPath(import.meta.url) === process.argv[1]) {
  const require = createRequire(import.meta.url);
  let chromium;
  try { ({ chromium } = require("playwright")); } catch { ({ chromium } = require("/opt/node-tools/node_modules/playwright")); }
  mkdirSync(CACHE, { recursive: true });
  const browser = await chromium.launch();
  const [pw, ph] = PRINT_PX;
  const page = await browser.newPage({ viewport: { width: pw, height: ph }, deviceScaleFactor: 1 });
  for (const p of WALL_POSTERS) {
    const s = posterSvg(p.id).replace(`width="${W}" height="${H}"`, `width="${pw}" height="${ph}"`);
    writeFileSync(CACHE + p.id + ".svg", posterSvg(p.id));
    await page.setContent(`<!doctype html><style>html,body{margin:0;background:#000}svg{display:block}</style>${s}`);
    await page.screenshot({ path: CACHE + p.id + ".png", clip: { x: 0, y: 0, width: pw, height: ph } });
    console.log(`wall-posters: ${p.id} → ${CACHE}${p.id}.png`);
  }
  await browser.close();
}
