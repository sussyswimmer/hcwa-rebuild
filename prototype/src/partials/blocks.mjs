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

// The same piece of screen drawn in light instead of ink (D7, the work sheet's tiles): every edge and hole
// as a 1.5 px lime-300 line, the sun coming through each block's holes (sun-200 fading out from the
// centre). Colours from pages.css; one gradient per kind on a page.
export function lightArt(kind, { cols = 1, rows = 1, cls = "block-light" } = {}) {
  const S = SIZE, f = face(kind), h = holes(kind), id = `bl-${kind}`;
  const blocks = [];
  for (let r = 0; r < rows; r++) for (let c = 0; c < cols; c++) {
    const t = `translate(${c * S + S / 2} ${r * S + S / 2})`;
    blocks.push(`<path class="block-light__sun" fill="url(#${id})" transform="${t}" d="${h}"/><path class="block-light__line" fill-rule="evenodd" transform="${t}" d="${f}"/>`);
  }
  const defs = `<defs><radialGradient id="${id}" r="0.62"><stop offset="0" class="block-light__core"/><stop offset="1" class="block-light__edge"/></radialGradient></defs>`;
  return svg(`0 0 ${cols * S} ${rows * S}`, defs + blocks.join(""), cls);
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
