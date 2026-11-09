// The bông gió block set as signed distance functions (metres, block centre at 0,
// negative inside a hole, where light passes). The numbers are exported so the SVG
// icons (scripts/blocks-svg.mjs, later) are drawn from the same values as the light.
export const BLOCK = 0.3; // outer size; mortar joints are shading only, never gaps
// 6 cm: a Saigon cast-cement block (19-30 cm square, about 6 cm thick). The light's two aperture tests
// use it too, so a deeper block would thin every patch to a sliver at the room's sun angles.
export const DEPTH = 0.06;
// Doors a corridor can hold (glsl/doors.js; the house's dado breaks read the same list). Here, not in doors.js,
// so the surfaces that only need the count never pull the doors' light into their chunk.
export const MAX_DOORS = 8;

export const HOA_THI = { r: 0.056, pipAt: 0.112, pipR: 0.021 };
export const MAT_CAO = { cell: 0.075, half: 0.026 };
export const VONG_TRON = { R: 0.105, w: 0.022 };
export const BAC_THANG = { step: 0.05, w: 0.018 };
export const DOT_TRE = { slotX: 0.052, slotW: 0.036, slotH: 0.25, nodes: [-0.06, 0.06], nodeH: 0.016 };
export const VOM_CAU = { R: 0.09, w: 0.024, deckY: -0.07, deckW: 0.2, deckH: 0.026 };

const f = (n) => n.toFixed(4);

export const BLOCKS = /* glsl */ `
float sdBox2(vec2 p, vec2 b){ vec2 d = abs(p) - b; return length(max(d, 0.)) + min(max(d.x, d.y), 0.); }

// Hoa thị: quatrefoil (four overlapping circles) + four corner pips that make a rosette
// where four blocks meet. The house block: the screen, the hero block, Little Rose's transom.
float sdHoaThi(vec2 p){
  float r = ${f(HOA_THI.r)};
  float d = min(min(length(p - vec2(r, 0.)), length(p + vec2(r, 0.))), min(length(p - vec2(0., r)), length(p + vec2(0., r)))) - r;
  vec2 a = abs(p) - vec2(${f(HOA_THI.pipAt)});
  return min(d, length(a) - ${f(HOA_THI.pipR)});
}

// Mắt cáo: dense diamond lattice.
float sdMatCao(vec2 p){
  vec2 q = mod(p + 0.5 * ${f(MAT_CAO.cell)}, ${f(MAT_CAO.cell)}) - 0.5 * ${f(MAT_CAO.cell)};
  float d = (abs(q.x) + abs(q.y) - ${f(MAT_CAO.half)}) * 0.7071;
  return max(d, max(abs(p.x), abs(p.y)) - 0.13);
}

// Vòng tròn: quarter rings in the corners; the full circle only exists where four blocks meet.
float sdVongTron(vec2 p){
  return abs(length(abs(p) - vec2(${f(BLOCK / 2)})) - ${f(VONG_TRON.R)}) - ${f(VONG_TRON.w)} * 0.5;
}

// Bậc thang: stepped zigzag slot.
float sdBacThang(vec2 p){
  float s = ${f(BAC_THANG.step)};
  vec2 q = vec2(p.x, p.y - s * (floor(p.x / s + 0.5)));
  float d = sdBox2(q, vec2(0.5 * s + 0.002, ${f(BAC_THANG.w)} * 0.5));
  return max(d, max(abs(p.x), abs(p.y)) - 0.125);
}

// Đốt tre (Tre Xanh's transom): two vertical slots bridged by bamboo nodes.
float sdDotTre(vec2 p){
  vec2 q = vec2(abs(p.x) - ${f(DOT_TRE.slotX)}, p.y);
  float d = sdBox2(q, vec2(${f(DOT_TRE.slotW / 2)}, ${f(DOT_TRE.slotH / 2)}));
  float n = min(abs(p.y - (${f(DOT_TRE.nodes[0])})), abs(p.y - (${f(DOT_TRE.nodes[1])}))) - ${f(DOT_TRE.nodeH / 2)};
  return max(d, -n);
}

// Vòm cầu (Cầu Hàn's transom): an arch over a deck slot.
float sdVomCau(vec2 p){
  float arch = max(abs(length(p - vec2(0., ${f(VOM_CAU.deckY)})) - ${f(VOM_CAU.R)}) - ${f(VOM_CAU.w / 2)}, ${f(VOM_CAU.deckY)} - p.y);
  float deck = sdBox2(p - vec2(0., ${f(VOM_CAU.deckY - 0.03)}), vec2(${f(VOM_CAU.deckW / 2)}, ${f(VOM_CAU.deckH / 2)}));
  return min(arch, deck);
}
`;
