import { raw } from "../lib/html.mjs";

// "Qua cổng vào văn phòng" (M6 art direction, Contact): the house seen from the street through its
// courtyard gate. The office's bông gió screen is the header's own still (L0, rendered from the world);
// in front of it, drawn here as inline SVG in the house's screen-print manner so they are sharp at any
// density and cost no request: the courtyard (L1: its gạch runner, the gate's two piers with the letterbox
// on the right one, two potted plants), the gate's two blue iron leaves with bông gió openwork (L2) and
// tamarind leaves along the top of the frame (L3, light only). Three envelopes (inline SVG, plain paper,
// a stamp outline, no writing and no address) slide into the letterbox as the visitor arrives. No people,
// no lettering, no house number.
//
// All of it is decorative (aria-hidden) and holds no words: the stage line lives in the page's text.
// styles/openings.css moves it from the --s3d-* tracks (scroll3d.mjs TRACKS): the leaves open with
// --s3d-gate, the camera comes through the gate with --s3d-cam, the letters fly with --s3d-mail. At rest
// (no JS, reduced motion, pause, Tier 0) the gate stands half open with one envelope in the slot.

const v = (t) => `var(--color-${t})`;
const fill = (t, o) => `fill:${v(t)}${o != null ? `;fill-opacity:${o}` : ""}`;
const stroke = (t, w, o) => `fill:none;stroke:${v(t)};stroke-width:${w}${o != null ? `;stroke-opacity:${o}` : ""};stroke-linecap:round`;
const rng = (seed) => () => ((seed = (seed * 1664525 + 1013904223) >>> 0) / 4294967296);
const r1 = (n) => Math.round(n * 10) / 10;

// A quatrefoil hole (the house's Hoa thị in small) centred on x, y with radius r: four round lobes.
const hole = (x, y, r) => {
  const a = r * 0.5;
  return `M${r1(x - a)} ${r1(y - a)}a${r1(a)} ${r1(a)} 0 1 1 ${r1(2 * a)} 0a${r1(a)} ${r1(a)} 0 1 1 0 ${r1(2 * a)}a${r1(a)} ${r1(a)} 0 1 1 ${r1(-2 * a)} 0a${r1(a)} ${r1(a)} 0 1 1 0 ${r1(-2 * a)}Z`;
};

// One iron leaf (200 × 465): frame, an openwork field of quatrefoils, a solid kick panel, a top rail.
const leaf = (side) => {
  const holes = [];
  for (let row = 0; row < 7; row++) for (let col = 0; col < 3; col++) holes.push(hole(46 + col * 54, 70 + row * 42, 17));
  const hinge = side === "l" ? 6 : 186;
  return `<svg viewBox="0 0 200 465" preserveAspectRatio="none" aria-hidden="true" focusable="false">
    <path fill-rule="evenodd" d="M0 0H200V465H0ZM16 46H184V360H16Z" style="${fill("blue-700")}"/>
    <path fill-rule="evenodd" d="M16 46H184V360H16Z${holes.join("")}" style="${fill("blue-700")}"/>
    <rect x="16" y="372" width="168" height="80" rx="2" style="${fill("tile-indigo")}"/>
    <rect x="16" y="372" width="168" height="4" style="${fill("tile-indigo-lit", 0.7)}"/>
    <path d="M10 22H190" style="${stroke("tile-indigo-lit", 5, 0.8)}"/>
    <rect x="${hinge}" y="60" width="8" height="26" rx="2" style="${fill("gold-700")}"/>
    <rect x="${hinge}" y="380" width="8" height="26" rx="2" style="${fill("gold-700")}"/>
  </svg>`;
};

// A pier of the gate (60 × 600): lime wash with a column of bông gió holes and an ochre cap.
const pier = () => {
  const holes = Array.from({ length: 6 }, (_, i) => hole(30, 120 + i * 62, 13)).join("");
  return `<svg viewBox="0 0 60 600" preserveAspectRatio="none" aria-hidden="true" focusable="false">
    <rect x="0" y="20" width="60" height="580" style="${fill("lime-50")}"/>
    <path fill-rule="evenodd" d="${holes}" style="${fill("sun-200")}"/>
    <rect x="0" y="20" width="10" height="580" style="${fill("ink-700", 0.12)}"/>
    <rect x="-6" y="0" width="72" height="26" rx="3" style="${fill("tile-ochre")}"/>
  </svg>`;
};

// The letterbox on the office wall (44 × 62): painted blue, a brass slot and a little hood.
const letterbox = () => `<svg viewBox="0 0 44 70" aria-hidden="true" focusable="false">
    <path d="M2 8h40l-4-8H6Z" style="${fill("tile-indigo")}"/>
    <rect x="0" y="8" width="44" height="62" rx="3" style="${fill("blue-700")}"/>
    <rect x="6" y="18" width="32" height="5" rx="2" style="${fill("gold-700")}"/>
    <rect x="6" y="34" width="32" height="2" rx="1" style="${fill("tile-indigo-lit", 0.6)}"/>
  </svg>`;

// The courtyard floor: a gạch bông runner seen in perspective, coming out of the gate toward the camera.
const court = () => {
  const tiles = [];
  for (let row = 0; row < 7; row++) {
    const t0 = row / 7, t1 = (row + 1) / 7, y0 = 40 + t0 * t0 * 200, y1 = 40 + t1 * t1 * 200;
    // M6 fix: a perspective trapezoid whose far end is as wide as the gate between its piers (the floor box is 1.9
    // gate widths wide, so the gate is 421 of its 800 units), widening toward the camera: it meets the gate.
    const w0 = 440 + t0 * 340, w1 = 440 + t1 * 340;
    for (let col = 0; col < 4; col++) {
      const xa0 = 400 - w0 / 2 + (col * w0) / 4, xb0 = xa0 + w0 / 4, xa1 = 400 - w1 / 2 + (col * w1) / 4, xb1 = xa1 + w1 / 4;
      const pig = (row + col) % 2 ? "tile-indigo" : "tile-ochre";
      const cx = (xa0 + xb0 + xa1 + xb1) / 4, cy = (y0 + y1) / 2, rx = (xb1 - xa1) * 0.32, ry = (y1 - y0) * 0.32;
      tiles.push(`<path d="M${r1(xa0)} ${r1(y0)}H${r1(xb0)}L${r1(xb1)} ${r1(y1)}H${r1(xa1)}Z" style="${fill("tile-cream")}"/>`
        + `<path d="M${r1(cx)} ${r1(cy - ry)}L${r1(cx + rx)} ${r1(cy)}L${r1(cx)} ${r1(cy + ry)}L${r1(cx - rx)} ${r1(cy)}Z" style="${fill(pig, 0.85)}"/>`);
    }
  }
  return `<svg class="cg-floor" viewBox="0 0 800 240" preserveAspectRatio="none" aria-hidden="true" focusable="false">
    ${tiles.join("")}
  </svg>`;
};

const plant = (flip) => {
  const rand = rng(flip ? 77 : 41);
  const fronds = Array.from({ length: 9 }, (_, i) => {
    const a = -150 + i * 15 + rand() * 6, len = 70 + rand() * 50, x = 60 + Math.cos((a * Math.PI) / 180) * len, y = 120 + Math.sin((a * Math.PI) / 180) * len;
    return `<path d="M60 120Q${r1((60 + x) / 2 + (flip ? -10 : 10))} ${r1((120 + y) / 2 - 20)} ${r1(x)} ${r1(y)}" style="${stroke(i % 3 ? "tile-jade" : "tile-jade-lit", 9)}"/>`;
  }).join("");
  return `<svg viewBox="0 0 120 200" aria-hidden="true" focusable="false">${fronds}<path d="M28 120H92L84 196H36Z" style="${fill("tile-ochre")}"/><rect x="22" y="114" width="76" height="14" rx="3" style="${fill("gold-700")}"/></svg>`;
};

const canopy = () => {
  const rand = rng(1630);
  const leaves = Array.from({ length: 64 }, () => {
    const x = rand() * 1000, y = rand() * 90 - 20 + (x > 820 ? rand() * 220 : 0);
    return `<ellipse cx="${r1(x)}" cy="${r1(y)}" rx="${r1(14 + rand() * 16)}" ry="${r1(6 + rand() * 6)}" transform="rotate(${r1(rand() * 60 - 30)} ${r1(x)} ${r1(y)})"/>`;
  }).join("");
  return `<svg viewBox="0 0 1000 300" preserveAspectRatio="xMaxYMin slice" aria-hidden="true" focusable="false"><g style="${fill("tile-jade", 0.88)}">${leaves}</g><path d="M-10 18C300 60 700 30 1010 4" style="${stroke("world-wood", 9)}"/></svg>`;
};

const envelope = () => `<svg viewBox="0 0 60 40" aria-hidden="true" focusable="false">
  <rect x="1" y="1" width="58" height="38" rx="2" style="${fill("lime-50")}"/>
  <path d="M2 3L30 22L58 3" style="${stroke("lime-300", 1.6)}"/>
  <rect x="44" y="6" width="10" height="12" rx="1" style="${stroke("gold-700", 1.4)}"/>
</svg>`;

export const contactGate = () => raw(`<div class="cg" aria-hidden="true">
  <div class="cg-box" data-art>
    <span class="cg-letterbox">${letterbox()}<span class="cg-slot"><span class="cg-mail cg-mail--1">${envelope()}</span><span class="cg-mail cg-mail--2">${envelope()}</span><span class="cg-mail cg-mail--3">${envelope()}</span></span></span>
  </div>
  <div class="cg-court" data-art>
    ${court()}
    <span class="cg-plant cg-plant--l">${plant(false)}</span>
    <span class="cg-plant cg-plant--r">${plant(true)}</span>
  </div>
  <div class="cg-gate" data-art>
    <span class="cg-pier cg-pier--l">${pier()}</span>
    <span class="cg-pier cg-pier--r">${pier()}</span>
    <span class="cg-leaves">
      <span class="cg-leaf cg-leaf--l">${leaf("l")}</span>
      <span class="cg-leaf cg-leaf--r">${leaf("r")}</span>
    </span>
  </div>
  <div class="cg-canopy">${canopy()}</div>
</div>`);
