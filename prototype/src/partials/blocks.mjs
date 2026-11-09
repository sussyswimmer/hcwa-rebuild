import { raw } from "../lib/html.mjs";
// The same numbers the light is computed from (05-DESIGN D6.3): an icon can never drift from its shader.
import { BLOCK, HOA_THI, MAT_CAO, VONG_TRON, BAC_THANG, DOT_TRE, VOM_CAU } from "../world/glsl/blocks.js";

// SVG units are millimetres, block centre at 0, y down (the SDFs are y up, so y flips here).
const mm = (m) => Math.round(m * 10000) / 10;
const H = mm(BLOCK / 2);
const n = (v) => +v.toFixed(1);

const circle = (cx, cy, r) => `M${n(cx - r)} ${n(cy)}A${n(r)} ${n(r)} 0 1 0 ${n(cx + r)} ${n(cy)}A${n(r)} ${n(r)} 0 1 0 ${n(cx - r)} ${n(cy)}Z`;
const rect = (x0, y0, x1, y1) => `M${n(x0)} ${n(y0)}H${n(x1)}V${n(y1)}H${n(x0)}Z`;
const poly = (pts) => `M${pts.map(([x, y]) => `${n(x)} ${n(y)}`).join("L")}Z`;

// Sutherland-Hodgman against the square |x|,|y| <= h: the Mắt cáo lattice is cut by the block's frame.
const clipSquare = (pts, h) => {
  const edges = [[(p) => p[0] <= h, (a, b) => [h, a[1] + ((b[1] - a[1]) * (h - a[0])) / (b[0] - a[0])]], [(p) => p[0] >= -h, (a, b) => [-h, a[1] + ((b[1] - a[1]) * (-h - a[0])) / (b[0] - a[0])]], [(p) => p[1] <= h, (a, b) => [a[0] + ((b[0] - a[0]) * (h - a[1])) / (b[1] - a[1]), h]], [(p) => p[1] >= -h, (a, b) => [a[0] + ((b[0] - a[0]) * (-h - a[1])) / (b[1] - a[1]), -h]]];
  for (const [inside, cut] of edges) {
    const out = [];
    pts.forEach((b, i) => {
      const a = pts.at(i - 1);
      if (inside(b)) { if (!inside(a)) out.push(cut(a, b)); out.push(b); } else if (inside(a)) out.push(cut(a, b));
    });
    pts = out;
    if (!pts.length) break;
  }
  return pts;
};

// Hole outlines per block, one entry per SDF in src/world/glsl/blocks.js. Holes never overlap, so the
// face is the block square minus the holes with fill-rule evenodd.
const HOLES = {
  // Four r circles at (±r, 0), (0, ±r): their union is the square ±r with a half circle on each side.
  hoaThi: () => {
    const r = mm(HOA_THI.r), a = `A${r} ${r} 0 0 1 `;
    const q = `M${r} ${-r}${a}${r} ${r}${a}${-r} ${r}${a}${-r} ${-r}${a}${r} ${-r}Z`;
    const p = mm(HOA_THI.pipAt), pr = mm(HOA_THI.pipR);
    return q + [[p, p], [-p, p], [p, -p], [-p, -p]].map(([x, y]) => circle(x, y, pr)).join("");
  },
  matCao: () => {
    const c = mm(MAT_CAO.cell), h = mm(MAT_CAO.half), lim = 130; // the SDF's own 0.13 m frame
    const out = [];
    for (let i = -2; i <= 2; i++) for (let j = -2; j <= 2; j++) {
      const pts = clipSquare([[i * c - h, j * c], [i * c, j * c - h], [i * c + h, j * c], [i * c, j * c + h]], lim);
      if (pts.length > 2) out.push(poly(pts));
    }
    return out.join("");
  },
  // Quarter rings around each corner: the full circle only exists where four blocks meet.
  vongTron: () => {
    const ro = mm(VONG_TRON.R + VONG_TRON.w / 2), ri = mm(VONG_TRON.R - VONG_TRON.w / 2);
    return [[1, 1], [-1, 1], [1, -1], [-1, -1]].map(([sx, sy]) => {
      const cx = sx * H, cy = sy * H, sweep = sx * sy > 0 ? 1 : 0;
      return `M${n(cx - sx * ro)} ${n(cy)}A${ro} ${ro} 0 0 ${sweep} ${n(cx)} ${n(cy - sy * ro)}L${n(cx)} ${n(cy - sy * ri)}A${ri} ${ri} 0 0 ${1 - sweep} ${n(cx - sx * ri)} ${n(cy)}Z`;
    }).join("");
  },
  // One slot per step column, each a step higher than the last (floor(x / s + 0.5) in the SDF).
  bacThang: () => {
    const s = mm(BAC_THANG.step), w = mm(BAC_THANG.w), lim = 125;
    const out = [];
    for (let k = -2; k <= 2; k++) out.push(rect(Math.max(-lim, k * s - s / 2), -k * s - w / 2, Math.min(lim, k * s + s / 2), -k * s + w / 2));
    return out.join("");
  },
  dotTre: () => {
    const x = mm(DOT_TRE.slotX), w = mm(DOT_TRE.slotW) / 2, h = mm(DOT_TRE.slotH) / 2, nh = mm(DOT_TRE.nodeH) / 2;
    const cuts = [-h, ...DOT_TRE.nodes.map(mm).sort((a, b) => a - b).flatMap((y) => [y - nh, y + nh]), h];
    const out = [];
    for (const sx of [-1, 1]) for (let i = 0; i < cuts.length; i += 2) out.push(rect(sx * x - w, cuts[i], sx * x + w, cuts[i + 1]));
    return out.join("");
  },
  vomCau: () => {
    const cy = -mm(VOM_CAU.deckY), ro = mm(VOM_CAU.R + VOM_CAU.w / 2), ri = mm(VOM_CAU.R - VOM_CAU.w / 2);
    const arch = `M${-ro} ${cy}A${ro} ${ro} 0 0 1 ${ro} ${cy}L${ri} ${cy}A${ri} ${ri} 0 0 0 ${-ri} ${cy}Z`;
    const dy = -mm(VOM_CAU.deckY - 0.03), dw = mm(VOM_CAU.deckW) / 2, dh = mm(VOM_CAU.deckH) / 2;
    return arch + rect(-dw, dy - dh, dw, dy + dh);
  },
};

export const KINDS = Object.keys(HOLES);
export const holes = (kind) => HOLES[kind]();
// The block face with its holes cut out (fill-rule evenodd).
export const face = (kind) => rect(-H, -H, H, H) + holes(kind);
export const SIZE = 2 * H;

// Which block stands for which focus area (PLAN H5a) and which unit wears which emblem (D6.5).
export const AREA_BLOCK = { education: "hoaThi", protection: "matCao", family: "vongTron", policy: "bacThang" };

// slice: a box with another aspect (the 72 px phone icon) shows the top-right block whole, never a shrunk strip.
const svg = (vb, body, cls) => raw(`<svg class="${cls}" viewBox="${vb}" preserveAspectRatio="xMaxYMin slice" aria-hidden="true" focusable="false">${body}</svg>`);

// A piece of the screen, backlit: logo-blue faces with the street light in the holes, cols × rows blocks.
export function screenArt(kind, { cols = 1, rows = 1, cls = "block-art" } = {}) {
  const S = SIZE, f = face(kind);
  const blocks = [];
  for (let r = 0; r < rows; r++) for (let c = 0; c < cols; c++) blocks.push(`<path class="block-art__face" fill-rule="evenodd" transform="translate(${c * S + S / 2} ${r * S + S / 2})" d="${f}"/>`);
  const joints = [];
  for (let c = 1; c < cols; c++) joints.push(`M${c * S} 0V${rows * S}`);
  for (let r = 1; r < rows; r++) joints.push(`M0 ${r * S}H${cols * S}`);
  return svg(`0 0 ${cols * S} ${rows * S}`, `<rect class="block-art__sky" width="${cols * S}" height="${rows * S}"/>${blocks.join("")}${joints.length ? `<path class="block-art__joint" d="${joints.join("")}"/>` : ""}`, cls);
}

// Mortar-free single block in line work (filter chips, icons): edge and holes stroked, holes lit.
export function blockIcon(kind, cls = "block-icon") {
  return svg(`${-H - 6} ${-H - 6} ${SIZE + 12} ${SIZE + 12}`, `<path class="block-icon__light" d="${holes(kind)}"/><path class="block-icon__line" fill="none" d="${face(kind)}"/>`, cls);
}

// Standalone files for CSS masks (scripts/blocks-svg.mjs writes them to public/svg/). A mask reads alpha,
// so plain black is enough: the colour comes from the element being masked.
export const maskFile = (kind, part) => {
  const d = part === "face" ? face(kind) : holes(kind);
  return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="${-H} ${-H} ${SIZE} ${SIZE}"><path fill-rule="evenodd" d="${d}"/></svg>\n`;
};

// ---------- Gạch bông: the focus areas as cement tiles (05-DESIGN 0.6.3, PLAN H5a) ----------
// The house's blocks turned into a floor: one 20 cm encaustic tile per <symbol>, in millimetres (200 = one
// tile), inlaid with its block from the same SDF numbers as the light. One hidden sprite per page holds the
// four symbols, the one wear filter and the corner clip; each area is one <svg> of n <use>s at a 200 mm
// pitch. Colours are custom properties from pages.css (--cream, --pig, --acc, --wear, --grout) that inherit
// into <use>, so the sunlit copy (.gach__lit) is the same symbols in the *-lit pigments.
// Fix r1 (9 Nov): a patch is one piece of laid floor, not a set of stickers. Each tile is a square of cement
// (--cream) with its pigment medallion 2 mm in (was 7, which left a 10 px plaster gutter at 1440); the
// corner motifs run to the tile's own edge, and the tiles meet at a 1.5 px grout joint (gachArt), so four
// quarter-discs close into one rosette where four tiles meet.
export const TILE = 200;
const MED = 2, MED_R = 1.5; // pigment medallion: inset and corner radius (a square tile)
const FRAME = 15, FRAME_W = 2.5, FRAME_R = 1; // the cream frame line
const DOT_R = 2.2, DOT_AT = (MED + FRAME - FRAME_W / 2) / 2; // accent dots centred in the border band
const CORNER_R = 38, LOBE = 13; // corner quarter-motif: an accent disc carrying a cream quarter Hoa thị
const INLAY = 0.82; // the 2 × 2 blocks, scaled inside the frame so they stay clear of the corner motifs
export const GROUT_PX = 1.5; // the joint between two tiles of a patch, in screen pixels at any tile size

// Tiles per area, cols × rows. The hierarchy is the number of tiles, never their size.
export const AREA_PATCH = { education: [2, 2], protection: [2, 1], family: [1, 1], policy: [1, 1] };

const style = (prop, v) => ` style="${prop}:var(--${v})"`;
const med = (inset, r) => `x="${inset}" y="${inset}" width="${TILE - 2 * inset}" height="${TILE - 2 * inset}" rx="${r}"`;
const CORNERS = [[0, 0], [TILE, 0], [0, TILE], [TILE, TILE]];

// Bậc thang, redrawn as one rising stair: a cream band (tread and riser) from the bottom-left corner motif
// to the top-right one in 6 equal steps, as thick as the block's slot is to its step, an accent dot at every
// nosing. The stubs at each end tuck under the corner discs. It rises left to right in both languages.
const STEP = 24;
const stair = () => {
  const c = TILE / 2, s = STEP, d = [`M${c - 3.5 * s} ${c + 3 * s}`], nosings = [];
  for (let k = 1; k <= 6; k++) {
    const x = c - 3.5 * s + k * s, y = c + 3 * s - k * s;
    d.push(`H${x}V${y}`);
    nosings.push(circle(x, y, 3.4));
  }
  d.push(`H${c + 3.5 * s}`);
  const w = n((s * BAC_THANG.w) / BAC_THANG.step);
  return `<path fill="none" stroke-width="${w}" stroke-linejoin="miter"${style("stroke", "cream")} d="${d.join("")}"/><path${style("fill", "acc")} d="${nosings.join("")}"/>`;
};

// At a tile's scale the Mắt cáo lattice keeps only its whole diamonds: the slivers its frame cuts at the
// block's edge would read as grit at 88 px.
const INLAY_HOLES = {
  matCao: () => {
    const c = mm(MAT_CAO.cell), h = mm(MAT_CAO.half), out = [];
    for (let i = -1; i <= 1; i++) for (let j = -1; j <= 1; j++) out.push(poly([[i * c - h, j * c], [i * c, j * c - h], [i * c + h, j * c], [i * c, j * c + h]]));
    return out.join("");
  },
};

// The area's block inlaid in cream, 2 × 2, with an accent heart at each block centre.
const inlay = (kind) => {
  if (kind === "bacThang") return stair();
  const cell = ((TILE - 2 * (FRAME + FRAME_W / 2)) * INLAY) / 2, s = (cell / SIZE).toFixed(4);
  const at = [TILE / 2 - cell / 2, TILE / 2 + cell / 2].map(n);
  const blocks = [], hearts = [];
  for (const y of at) for (const x of at) {
    blocks.push(`<use href="#gach-b-${kind}" transform="translate(${x} ${y}) scale(${s})"/>`);
    hearts.push(circle(x, y, kind === "matCao" ? 3.4 : 5.6));
  }
  return `<g stroke-width="3.5" style="fill:var(--cream);stroke:var(--cream)">${blocks.join("")}</g><path${style("fill", "acc")} d="${hearts.join("")}"/>`;
};

const symbol = (kind) => {
  // the band's dots, every 20 mm between the corner motifs: a dotted stroke (round caps on zero dashes)
  const a = n(DOT_AT), b = n(TILE - DOT_AT), dots = `M50 ${a}H150M50 ${b}H150M${a} 50V150M${b} 50V150`;
  const discs = CORNERS.map(([x, y]) => circle(x, y, CORNER_R)).join("");
  const lobes = CORNERS.map(([x, y]) => circle(x + (x ? -LOBE : LOBE), y, LOBE) + circle(x, y + (y ? -LOBE : LOBE), LOBE)).join("");
  return `<symbol id="gach-${kind}">`
    + `<rect width="${TILE}" height="${TILE}"${style("fill", "cream")}/>`
    + `<rect ${med(MED, MED_R)}${style("fill", "pig")}/>`
    + `<rect ${med(FRAME, FRAME_R)} fill="none" stroke-width="${FRAME_W}"${style("stroke", "cream")}/>`
    + `<path fill="none" stroke-width="${2 * DOT_R}" stroke-linecap="round" stroke-dasharray="0 20"${style("stroke", "acc")} d="${dots}"/>`
    + inlay(kind)
    + `<g clip-path="url(#gach-clip)"><path${style("fill", "acc")} d="${discs}"/><path${style("fill", "cream")} d="${lobes}"/></g>`
    + `<rect ${med(MED, MED_R)} filter="url(#gach-wear)" style="fill:var(--cream);opacity:var(--wear)"/>`
    + `</symbol>`;
};

// The page's one sprite. The wear (worn pigment and cement speckle) is one feTurbulence, rasterised once
// with the tile and never animated (--wear 0.22 on the floor; fix r2, was 0.32). The lit copy keeps a trace
// of it (0.08), so the grain runs on through the light without blurring the light's crisp edges.
export function gachSprite() {
  const wear = `<filter id="gach-wear" x="0" y="0" width="1" height="1"><feTurbulence type="fractalNoise" baseFrequency=".62" numOctaves="2" seed="7" result="n"/><feColorMatrix in="n" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 8 -4.1" result="a"/><feComposite in="SourceGraphic" in2="a" operator="in"/></filter>`;
  // The corner motifs are clipped to the tile's own square, so they run to the joint and close into one
  // rosette with the three tiles that meet them.
  const clip = `<clipPath id="gach-clip"><rect width="${TILE}" height="${TILE}"/></clipPath>`;
  const kinds = Object.values(AREA_BLOCK), blocks = kinds.filter((k) => k !== "bacThang").map((k) => `<path id="gach-b-${k}" d="${(INLAY_HOLES[k] ?? holes)(k)}"/>`);
  return raw(`<svg class="gach-sprite" width="0" height="0" aria-hidden="true" focusable="false"><defs>${wear}${clip}${blocks.join("")}${kinds.map(symbol).join("")}</defs></svg>`);
}

// The sprite is one per page, wherever the tiles first appear (the work sheet's floor, or the first program
// row on a page without one). It must never sit inside a display: none subtree (a filtered-out program
// row): Chromium then drops its filter and clip, so the floor emits it before its rows.
const spriteOn = new WeakSet();
export const gachSpriteOnce = (c) => (spriteOn.has(c) ? "" : (spriteOn.add(c), gachSprite()));

// One area's patch: cols × rows tiles at a 200 mm pitch. Symmetric tiles are laid at quarter turns, as a
// real floor is, so the wear never repeats; the stair always rises left to right. The joints are one
// non-scaling 1.5 px grout stroke (--grout), drawn only between tiles: the patch's outer edge is the
// tiles' own cement, square.
export function gachArt(kind, [cols, rows], cls = "gach__art") {
  const uses = [], joints = [];
  for (let y = 0; y < rows; y++) for (let x = 0; x < cols; x++) {
    const tx = x * TILE, ty = y * TILE, turn = kind === "bacThang" ? 0 : ((x * 3 + y * 5) % 4) * 90;
    uses.push(`<use href="#gach-${kind}"${tx || ty ? ` x="${tx}" y="${ty}"` : ""}${turn ? ` transform="rotate(${turn} ${tx + TILE / 2} ${ty + TILE / 2})"` : ""}/>`);
  }
  for (let x = 1; x < cols; x++) joints.push(`M${x * TILE} 0V${rows * TILE}`);
  for (let y = 1; y < rows; y++) joints.push(`M0 ${y * TILE}H${cols * TILE}`);
  const grout = joints.length ? `<path fill="none" stroke-width="${GROUT_PX}" vector-effect="non-scaling-stroke" style="stroke:var(--grout)" d="${joints.join("")}"/>` : "";
  return raw(`<svg class="${cls}" viewBox="0 0 ${cols * TILE} ${rows * TILE}" aria-hidden="true" focusable="false">${uses.join("")}${grout}</svg>`);
}

// A program's areas as a strip of the same floor (program rows, fix r2): one tile per area, side by side at
// the 200 mm pitch, each in its own pigment (the class on its <use> sets --pig and --acc, pages.css), never
// turned, never scaled per row, with a patch's 1.5 px grout between them. Needs the page's sprite.
export function gachStrip(areas, cls = "gach-strip") {
  const known = areas.filter((a) => a in AREA_BLOCK);
  if (!known.length) return "";
  const uses = known.map((a, i) => `<use href="#gach-${AREA_BLOCK[a]}" class="pig--${a}"${i ? ` x="${i * TILE}"` : ""}/>`);
  const joints = known.slice(1).map((_, i) => `M${(i + 1) * TILE} 0V${TILE}`).join("");
  const grout = joints ? `<path fill="none" stroke-width="${GROUT_PX}" vector-effect="non-scaling-stroke" style="stroke:var(--grout)" d="${joints}"/>` : "";
  return raw(`<svg class="${cls}" viewBox="0 0 ${known.length * TILE} ${TILE}" aria-hidden="true" focusable="false">${uses.join("")}${grout}</svg>`);
}

// The sun through the house's Hoa thị screen as it lands on the floor (a CSS mask, public/svg/sun-hoa-thi.svg),
// crisp, no blur. Fix r2 (9 Nov): sunlight through a screen, not a stain. Each hole (the quatrefoil with its
// 4 pips, 266 units across) is drawn at 0.6 of its 300-unit cell, so at least 0.4 of a cell of tile lies
// between two lit shapes in every direction and each quatrefoil stands alone. A hole keeps the floor's
// 1.1 stretch but leans only 8° (was 14°, which turned the lobes into one lump); the lattice steps a third
// of a cell per row, so three rows come back to the first and the 300 × 990 file tiles seamlessly in x and
// in y. Row j's centres sit at x = 150 - 100 j (mod 300), y = 165 + 330 j.
export const SUN = { hole: 0.6, lean: 8, stretch: 1.1, rows: 3, step: 1 / 3 };
export const SUN_REACH = mm(HOA_THI.pipAt + HOA_THI.pipR); // a hole's half extent, pips included (133)
export function sunMaskFile() {
  const W = SIZE, rowH = W * SUN.stretch, s = (SUN.hole * W) / (2 * SUN_REACH), lean = Math.tan((SUN.lean * Math.PI) / 180);
  const m = [s, 0, -s * SUN.stretch * lean, s * SUN.stretch, 0, 0].map((v) => +v.toFixed(4));
  const uses = [];
  for (let j = 0; j < SUN.rows; j++) for (let i = -1; i <= 2; i++) {
    const x = W / 2 - j * W * SUN.step + i * W;
    if (x > -W / 2 && x < 1.5 * W) uses.push(`<use href="#h" x="${n(x)}" y="${n(rowH / 2 + j * rowH)}"/>`);
  }
  const h = n(rowH * SUN.rows);
  return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${W} ${h}" width="${W}" height="${h}"><defs><path id="h" transform="matrix(${m.join(" ")})" d="${holes("hoaThi")}"/></defs>${uses.join("")}</svg>\n`;
}

// The cement ground the patches are laid in (pages.css .floor::after): the tiles' own wear speckle (the same
// fractal noise, frequency and threshold as #gach-wear, here one per CSS pixel) as a seamless 240 px alpha
// mask. A mask reads alpha only, so it carries no colour; it is rasterised once and never animated.
export function cementMaskFile() {
  return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 240 240" width="240" height="240"><filter id="w" x="0" y="0" width="1" height="1"><feTurbulence type="fractalNoise" baseFrequency=".62" numOctaves="2" seed="7" stitchTiles="stitch"/><feColorMatrix values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 8 -4.1"/></filter><rect width="240" height="240" filter="url(#w)"/></svg>\n`;
}
