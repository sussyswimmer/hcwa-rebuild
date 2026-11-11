import { html, raw } from "../lib/html.mjs";

// "Cửa mở" (M6 art direction, Get Involved): a window in the house's wall with its two blue louvered
// shutters thrown open onto a late-morning Saigon street, drawn as inline SVG in the screen-print manner
// of the house's posters (flat inks, a second plate a little off, no outlines). Sharp at any density, no
// request, never a photo. No people, no children, no signage or lettering, no plate or badge on the
// scooter, nothing that names a real street.
//
// Layers (styles/pages.css): the street (L0, scales toward the window as the camera comes in), a wash of
// sun over it (opacity only), the window's reveal and sill (still), the two leaves (L2/L3, each a 3D
// hinge: rotateY about its outer edge in a 1200 px perspective) and the louver light on the wall under
// the sill (L4). At rest the leaves stand open at 112°; on the stage they are already open at 62° at
// scroll 0 (an open window, never closed slabs) and swing on to 112° with --s3d-cam.

const v = (t) => `var(--color-${t})`;
const fill = (t, o) => `fill:${v(t)}${o != null ? `;fill-opacity:${o}` : ""}`;
const stroke = (t, w, o) => `fill:none;stroke:${v(t)};stroke-width:${w}${o != null ? `;stroke-opacity:${o}` : ""};stroke-linecap:round`;
const rng = (seed) => () => ((seed = (seed * 1664525 + 1013904223) >>> 0) / 4294967296);
const r1 = (n) => Math.round(n * 10) / 10;

const street = () => {
  const rand = rng(1030);
  const leaves = Array.from({ length: 80 }, () => {
    const x = rand() * 840 - 20, y = rand() * 150 - 30;
    return `<ellipse cx="${r1(x)}" cy="${r1(y)}" rx="${r1(16 + rand() * 22)}" ry="${r1(10 + rand() * 12)}"/>`;
  });
  const dapple = Array.from({ length: 30 }, () => `<circle cx="${r1(40 + rand() * 740)}" cy="${r1(130 + rand() * 300)}" r="${r1(3 + rand() * 7)}"/>`).join("");
  // An upper-floor window: arched, shutters of jade slats, a sill and a small iron railing.
  const win = (x, y) => `<path d="M${x} ${y + 110}V${y + 30}a37 30 0 0 1 74 0V${y + 110}Z" style="${fill("tile-jade")}"/>
    <path d="M${x + 37} ${y + 4}V${y + 110}" style="${stroke("tile-cream", 3)}"/>
    ${Array.from({ length: 6 }, (_, i) => `<path d="M${x + 6} ${y + 36 + i * 12}h62" style="${stroke("tile-jade-lit", 3)}"/>`).join("")}
    <rect x="${x - 10}" y="${y + 108}" width="94" height="8" style="${fill("tile-ochre")}"/>
    <path d="M${x - 8} ${y + 116}v30M${x + 82} ${y + 116}v30M${x - 8} ${y + 130}h90M${x - 8} ${y + 144}h90" style="${stroke("ink-700", 2, 0.55)}"/>`;
  const pot = (x, y) => `<path d="M${x} ${y}h26l-4 20h-18Z" style="${fill("tile-ochre")}"/><circle cx="${x + 6}" cy="${y - 8}" r="11" style="${fill("tile-jade")}"/><circle cx="${x + 20}" cy="${y - 12}" r="13" style="${fill("tile-jade")}"/>`;
  // A shop's folding grille half up, the cool room behind it in shade.
  const grille = (x, w, up) => `<rect x="${x}" y="330" width="${w}" height="230" style="${fill("ink-700", 0.5)}"/>
    <rect x="${x}" y="330" width="${w}" height="${up}" style="${fill("tile-cream")}"/>
    ${Array.from({ length: Math.floor(up / 11) }, (_, i) => `<path d="M${x} ${336 + i * 11}H${x + w}" style="${stroke("lime-300", 3.5)}"/>`).join("")}`;
  return `<svg class="gi-street__art" viewBox="0 0 800 600" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
  <defs><linearGradient id="gi-sky" x1="0" y1="0" x2="0" y2="1"><stop offset="0" style="stop-color:${v("sun-100")}"/><stop offset="1" style="stop-color:${v("sun-200")}"/></linearGradient></defs>
  <rect width="800" height="600" fill="url(#gi-sky)"/>
  <path d="M0 600V100H800V600Z" style="${fill("world-vang-sun")}"/>
  <path d="M0 100H800V114H0Z" style="${fill("tile-ochre")}"/>
  <path d="M0 300H800V312H0Z" style="${fill("tile-ochre", 0.8)}"/>
  ${win(100, 150)}${win(300, 150)}${win(500, 150)}${win(700, 150)}
  ${pot(130, 292)}${pot(530, 292)}
  <g style="${fill("sun-300", 0.9)}">${dapple}</g>
  ${grille(60, 300, 120)}${grille(440, 300, 200)}
  <path d="M0 560H800V600H0Z" style="${fill("tile-cream")}"/>
  <path d="M0 560H800" style="${stroke("ink-700", 2, 0.25)}"/>
  <g class="gi-scooter" transform="translate(-10 -110)">
    <path d="M190 668c0-34 18-50 46-52l40-2c8 0 12 6 8 14l-16 26h44c12-26 34-34 54-30l-6-30h16l10 40c10 6 14 18 12 34Z" style="${fill("tile-jade")}"/>
    <path d="M220 616h52l-4-10h-44Z" style="${fill("ink-700")}"/>
    <path d="M330 598l14-22h16" style="${stroke("ink-700", 5)}"/>
    <circle cx="222" cy="676" r="22" style="${fill("ink-700")}"/><circle cx="222" cy="676" r="9" style="${fill("lime-300")}"/>
    <circle cx="362" cy="676" r="22" style="${fill("ink-700")}"/><circle cx="362" cy="676" r="9" style="${fill("lime-300")}"/>
  </g>
  <path d="M-10 60C200 110 520 100 810 50M-10 84C240 130 540 120 810 80" style="${stroke("ink-700", 1.6, 0.6)}"/>
  <path d="M30 600C34 470 24 260 70 90" style="${stroke("world-wood", 18)}"/>
  <g style="${fill("tile-jade")}">${leaves.join("")}</g>
  <g style="${fill("tile-jade-lit", 0.8)}" transform="translate(2 1.5)">${leaves.filter((_, i) => i % 4 === 0).join("")}</g>
</svg>`;
};

// One louvered leaf: a blue-700 frame, two panels of slats with the street's light between them.
const leaf = () => {
  const slats = (y0, n) => Array.from({ length: n }, (_, i) => `<rect x="12" y="${y0 + i * 13}" width="76" height="9" style="${fill("blue-700")}"/><rect x="12" y="${y0 + i * 13}" width="76" height="2" style="${fill("tile-indigo-lit")}"/>`).join("");
  return `<svg viewBox="0 0 100 320" preserveAspectRatio="none" aria-hidden="true" focusable="false">
    <rect width="100" height="320" style="${fill("blue-700")}"/>
    <rect x="12" y="12" width="76" height="140" style="${fill("sun-200")}"/>
    <rect x="12" y="168" width="76" height="140" style="${fill("sun-200")}"/>
    ${slats(14, 11)}${slats(170, 11)}
    <rect x="2" y="2" width="4" height="316" style="${fill("tile-indigo-lit", 0.7)}"/>
  </svg>`;
};

export const involveWindow = () => raw(`<div class="gi-stage" aria-hidden="true">
  <div class="gi-window" data-art>
    <div class="gi-street">${street()}</div>
    <span class="gi-sunwash"></span>
    <span class="gi-reveal"></span>
    <div class="gi-leaf gi-leaf--l" data-art>${leaf()}</div>
    <div class="gi-leaf gi-leaf--r" data-art>${leaf()}</div>
  </div>
  <span class="gi-sill" data-art></span>
  <span class="gi-stripes"></span>
</div>`);
