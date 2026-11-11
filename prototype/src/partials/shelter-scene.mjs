import { html, raw } from "../lib/html.mjs";

// "Khu vườn có tường bao" (M6 art direction, Little Rose): a walled garden in paper-cut / screen-print
// illustration, drawn here as inline SVG so it is sharp at any density, costs no request and is never a
// photo, a still or a place. Safeguarding (CLAUDE.md; 05-DESIGN 0.6.4; test/little-rose-page.test.mjs):
// no figure, no face, no hand, no silhouette of a person; no way in of any kind (nothing that opens in
// the wall), no house number, no street, no skyline, no landmark, no plant or building that locates it;
// clothes on the line are plain sheets only; the high window shows lamp glow and nothing behind it; no
// pink, magenta or red anywhere, as light or as paint (the blooms are ivory, cream and apricot). Every
// colour is a house token from a short allow-list; no text in the picture.
//
// Layers, back to front, each its own <svg> so the page moves it as one composited picture (transform
// only, styles/pages.css): L0 sky (still), L1 the far wall with its high window and the garden floor,
// L2 the trellis with its climbing blooms and the bench, L3 the line of sheets, L4 the desk under its
// awning, L5 pots, paper-cut grass and fallen petals. The paper grain (one small tile of noise in CSS)
// sits on top and never moves. Each layer
// is drawn 15% past the frame at the top and the foot, so its parallax never bares an edge.
//
// The five story steps (what the shelter provides, content/draft/work.json) each light one part of the
// garden through --step-1 … --step-5 (0 → 1): the wall's colour and the lamp in the window; the cup's
// steam and a cushion on the bench; the books open, the loaf and the desk lamp; a notebook and the
// blooms opening; the afternoon sun crossing the garden and the sheets glowing. At rest every step is 1.

const VB = "0 -60 800 1300";
const ALLOWED = ["lime-50", "lime-100", "sun-100", "sun-200", "sun-300", "tile-cream", "tile-ochre", "tile-jade", "tile-indigo", "ink-700", "ink-900", "gold-700"];
export const SCENE_COLOURS = ALLOWED;

// A token as paint (fill / stroke) in a style attribute: tokens only, never a literal colour.
const v = (t) => {
  if (!ALLOWED.includes(t)) throw new Error(`shelter scene: ${t} is not in the scene palette`);
  return `var(--color-${t})`;
};
const fill = (t, o) => `fill:${v(t)}${o != null ? `;fill-opacity:${o}` : ""}`;
const stroke = (t, w, o) => `fill:none;stroke:${v(t)};stroke-width:${w}${o != null ? `;stroke-opacity:${o}` : ""};stroke-linecap:round;stroke-linejoin:round`;

// A seeded random, so the picture is the same on every build and in both languages.
const rng = (seed) => () => ((seed = (seed * 1664525 + 1013904223) >>> 0) / 4294967296);
const r1 = (n) => Math.round(n * 10) / 10;

// Torn paper: a ragged edge from x0 to x1 around y, never a straight rule.
const torn = (x0, x1, y, amp, step, rand) => {
  const pts = [];
  for (let x = x0; x <= x1; x += step * (0.6 + rand() * 0.8)) pts.push(`${r1(x)} ${r1(y + (rand() - 0.5) * 2 * amp)}`);
  pts.push(`${x1} ${y}`);
  return pts.join(" L");
};

// One bloom: five round petals about a centre, in two inks (screen-print: the second plate 1.5 px off).
const bloom = (x, y, s, petal, centre) => {
  const petals = Array.from({ length: 5 }, (_, i) => {
    const a = (i / 5) * Math.PI * 2 - Math.PI / 2;
    return `<circle cx="${r1(x + Math.cos(a) * s * 0.55)}" cy="${r1(y + Math.sin(a) * s * 0.55)}" r="${r1(s * 0.5)}"/>`;
  }).join("");
  return `<g class="lr-bloom" style="transform-origin:${x}px ${r1(y + s)}px">`
    + `<g style="${fill("tile-ochre", 0.45)}" transform="translate(1.5 1)">${petals}</g>`
    + `<g style="${fill(petal)}">${petals}</g>`
    + `<circle cx="${x}" cy="${y}" r="${r1(s * 0.32)}" style="${fill(centre)}"/></g>`;
};

const defs = () => `<defs>
  <linearGradient id="lr-sky" x1="0" y1="0" x2="0" y2="1"><stop offset="0" style="stop-color:${v("sun-100")}"/><stop offset="0.55" style="stop-color:${v("sun-100")}"/><stop offset="1" style="stop-color:${v("lime-50")}"/></linearGradient>
  <radialGradient id="lr-glow"><stop offset="0" style="stop-color:${v("sun-300")}"/><stop offset="0.55" style="stop-color:${v("sun-200")};stop-opacity:0.6"/><stop offset="1" style="stop-color:${v("sun-200")};stop-opacity:0"/></radialGradient>
  <radialGradient id="lr-patch"><stop offset="0" style="stop-color:${v("sun-200")}"/><stop offset="0.6" style="stop-color:${v("sun-200")};stop-opacity:0.7"/><stop offset="1" style="stop-color:${v("sun-200")};stop-opacity:0"/></radialGradient>
  <filter id="lr-cut" x="-5%" y="-5%" width="110%" height="115%"><feDropShadow dx="0" dy="2" stdDeviation="1.5" style="flood-color:${v("ink-900")};flood-opacity:0.14"/></filter>
</defs>`;

// L0: the sky wash, and the sun's soft disc high on the left (light, not a place).
const sky = () => `<rect x="0" y="-150" width="800" height="1500" fill="url(#lr-sky)"/>
  <circle cx="150" cy="70" r="150" fill="url(#lr-patch)" opacity="0.8"/>
  <g filter="url(#lr-cut)" style="${fill("lime-50")}">
    <path d="M430 150c0-26 30-38 50-24 10-30 60-34 74-4 22-8 46 6 44 28Z"/>
    <path d="M110 210c0-18 22-26 36-16 8-22 44-24 54-2 16-6 34 4 32 18Z"/>
    <path d="M620 60c0-20 24-30 40-18 8-24 48-26 58-4 18-6 36 4 34 22Z"/>
  </g>`;

// L1: the far wall in lime wash with its torn-paper top and a coping of round tiles, the one high
// window (shutters ajar, only lamp glow behind them), the garden floor, and the afternoon sun patch.
const wall = (rand) => {
  const top = 262;
  const coping = Array.from({ length: 23 }, (_, i) => `<circle cx="${r1(-10 + i * 37 + rand() * 4)}" cy="${r1(top - 4 + rand() * 4)}" r="15"/>`).join("");
  const slats = (x, w) => Array.from({ length: 7 }, (_, i) => `<path d="M${x} ${348 + i * 15}h${w}" style="${stroke("tile-cream", 3)}"/>`).join("");
  const edge = `M-20 1300V${top}L${torn(-20, 820, top, 5, 26, rand)}V1300Z`;
  return `<g filter="url(#lr-cut)">
    <g style="${fill("tile-ochre", 0.85)}">${coping}</g>
    <path d="${edge}" style="${fill("lime-100")}"/>
    <path class="lr-warm" d="${edge}" style="${fill("sun-200")}"/>
    <path d="M-20 ${top + 40}L${torn(-20, 820, top + 40, 3, 40, rand)}" style="${stroke("ink-700", 1, 0.12)}"/>
  </g>
  <g class="lr-window">
    <rect x="296" y="324" width="148" height="132" rx="3" style="${fill("tile-cream")}"/>
    <rect class="lr-glow" x="300" y="328" width="140" height="124" style="${fill("sun-300")}"/>
    <circle class="lr-glow" cx="370" cy="400" r="150" fill="url(#lr-glow)" opacity="0.7"/>
    <g filter="url(#lr-cut)">
      <path d="M300 330L262 322V468L300 452Z" style="${fill("tile-jade")}"/>${slats(266, 30)}
      <path d="M440 330L478 322V468L440 452Z" style="${fill("tile-jade")}"/>${slats(444, 30)}
      <rect x="288" y="452" width="164" height="12" rx="2" style="${fill("tile-cream")}"/>
    </g>
  </g>
  <path d="M-20 880L${torn(-20, 820, 880, 2, 50, rand)}V1300H-20Z" style="${fill("sun-100")}"/>
  <path d="M-20 900H820" style="${stroke("ink-700", 1, 0.1)}"/>
  <ellipse class="lr-sun" cx="400" cy="930" rx="300" ry="70" fill="url(#lr-patch)"/>`;
};

// L2: the trellis (indigo lines), the climbing leaves, nine blooms and the bench with its cushion.
const trellis = (rand) => {
  const x0 = 40, x1 = 300, y0 = 380, y1 = 840;
  const lines = [];
  for (let k = -6; k <= 9; k++) {
    const a = x0 + k * 46;
    lines.push(`<path d="M${a} ${y1}L${a + (y1 - y0) * 0.55} ${y0}" />`, `<path d="M${a} ${y0}L${a + (y1 - y0) * 0.55} ${y1}" />`);
  }
  const leaves = Array.from({ length: 46 }, () => {
    const x = x0 + rand() * (x1 - x0), y = y0 + 20 + rand() * (y1 - y0 - 30);
    return `<ellipse cx="${r1(x)}" cy="${r1(y)}" rx="${r1(9 + rand() * 7)}" ry="${r1(5 + rand() * 3)}" transform="rotate(${r1(rand() * 180)} ${r1(x)} ${r1(y)})"/>`;
  }).join("");
  const spots = [[96, 436, 27], [206, 476, 24], [268, 404, 20], [70, 572, 25], [170, 612, 29], [262, 566, 22], [112, 706, 24], [224, 728, 27], [66, 792, 21]];
  const inks = [["tile-cream", "sun-300"], ["sun-200", "tile-ochre"], ["sun-300", "tile-ochre"]];
  return `<g filter="url(#lr-cut)">
    <rect x="${x0 - 10}" y="${y0 - 10}" width="${x1 - x0 + 20}" height="${y1 - y0 + 20}" rx="4" style="${fill("lime-50", 0.35)}"/>
    <clipPath id="lr-lattice-clip"><rect x="${x0}" y="${y0}" width="${x1 - x0}" height="${y1 - y0}"/></clipPath>
    <g clip-path="url(#lr-lattice-clip)" style="${stroke("tile-indigo", 2, 0.55)}">${lines.join("")}</g>
    <rect x="${x0 - 6}" y="${y0 - 6}" width="${x1 - x0 + 12}" height="${y1 - y0 + 12}" rx="4" style="${stroke("tile-indigo", 5)}"/>
    <path d="M60 860C70 760 40 660 90 560S70 420 150 400M210 860C190 760 250 680 200 560S260 430 270 400" style="${stroke("tile-jade", 5)}"/>
    <g style="${fill("tile-jade")}">${leaves}</g>
    ${spots.map(([x, y, s], i) => bloom(x, y, s, inks[i % 3][0], inks[i % 3][1])).join("")}
    <rect x="60" y="822" width="232" height="16" rx="3" style="${fill("gold-700")}"/>
    <path d="M76 838V888M276 838V888" style="${stroke("gold-700", 8)}"/>
    <rect class="lr-cushion" x="110" y="802" width="110" height="22" rx="10" style="${fill("tile-ochre")}"/>
  </g>`;
};

// L3: the line of sheets between two posts (plain sheets and towels only: nothing that suggests who).
const line = () => {
  const sheet = (x, w, h, t, i) => `<g class="lr-sheet lr-sheet--${i}" style="transform-origin:${x + w / 2}px 446px">
      <path d="M${x} 446H${x + w}L${x + w + 4} ${446 + h}Q${x + w * 0.75} ${446 + h - 10} ${x + w / 2} ${446 + h}T${x - 3} ${446 + h}Z" style="${fill(t)}"/>
      <path d="M${x + w * 0.3} 452V${446 + h - 8}M${x + w * 0.66} 452V${446 + h - 12}" style="${stroke("ink-700", 1, 0.14)}"/>
    </g>`;
  return `<g filter="url(#lr-cut)">
    <path d="M468 410V880M786 410V880" style="${stroke("gold-700", 7)}"/>
    <ellipse class="lr-line-glow" cx="627" cy="540" rx="210" ry="150" fill="url(#lr-glow)"/>
    <path d="M468 432Q627 462 786 432" style="${stroke("ink-700", 1.6, 0.7)}"/>
    ${sheet(492, 74, 168, "tile-cream", 1)}${sheet(584, 66, 136, "lime-50", 2)}${sheet(668, 92, 182, "sun-100", 3)}
  </g>`;
};

// L4: the desk under a small striped awning: books (closed, then open), the loaf on its board, a teapot
// and cup with steam, the desk lamp and its light, a notebook and pencil.
const desk = () => {
  const scallops = Array.from({ length: 9 }, (_, i) => `<path d="M${436 + i * 40} 690a20 14 0 0 0 40 0Z" style="${fill(i % 2 ? "tile-cream" : "tile-jade")}"/>`).join("");
  const stripes = Array.from({ length: 9 }, (_, i) => `<path d="M${436 + i * 40} 650h40v40h-40Z" style="${fill(i % 2 ? "tile-cream" : "tile-jade")}"/>`).join("");
  // Pulled 36 units in and drawn at 0.92 about the middle, so the awning's right end clears the side feather.
  return `<g filter="url(#lr-cut)" transform="translate(400 0) scale(0.92) translate(-436 0)">
    <path d="M430 650L456 618H778L800 650Z" style="${fill("tile-jade")}"/>
    ${stripes}${scallops}
    <circle class="lr-lamp-glow" cx="712" cy="780" r="120" fill="url(#lr-glow)"/>
    <rect x="436" y="800" width="352" height="18" rx="3" style="${fill("gold-700")}"/>
    <path d="M452 818V910M772 818V910" style="${stroke("gold-700", 9)}"/>
    <g class="lr-books-shut">
      <rect x="470" y="776" width="86" height="12" rx="2" style="${fill("tile-indigo")}"/>
      <rect x="474" y="764" width="80" height="12" rx="2" style="${fill("tile-ochre")}"/>
      <rect x="468" y="788" width="90" height="12" rx="2" style="${fill("tile-jade")}"/>
    </g>
    <g class="lr-books-open">
      <path d="M461.5 787L506.5 779L551.5 787V800L506.5 793L461.5 800Z" style="${fill("tile-ochre", 0.5)}"/>
      <path d="M460 786L505 778L550 786V799L505 792L460 799Z" style="${fill("tile-cream")}"/>
      <path d="M505 778V792M470 786l26-4M470 791l26-4M514 782l26 4M514 787l26 4" style="${stroke("ink-700", 1, 0.4)}"/>
    </g>
    <g class="lr-loaf">
      <rect x="566" y="790" width="68" height="10" rx="2" style="${fill("tile-cream")}"/>
      <path d="M574 790C574 770 626 770 626 790Z" style="${fill("tile-ochre")}"/>
      <path d="M588 778l6 8M600 776l6 8M612 778l6 8" style="${stroke("sun-200", 2)}"/>
    </g>
    <path d="M640 800C640 772 684 772 684 800Z" style="${fill("tile-indigo")}"/>
    <path d="M684 786q12-4 14 6" style="${stroke("tile-indigo", 4)}"/>
    <rect x="656" y="768" width="12" height="6" rx="2" style="${fill("tile-indigo")}"/>
    <rect x="694" y="786" width="16" height="14" rx="3" style="${fill("tile-cream")}"/>
    <g class="lr-steam" style="${stroke("ink-700", 1.6, 0.35)}">
      <path d="M698 780c-6-10 6-14 0-24" pathLength="1"/><path d="M704 778c6-10-6-16 2-26" pathLength="1"/><path d="M710 781c-5-8 5-12 0-20" pathLength="1"/>
    </g>
    <path d="M748 800V738l-16-30" style="${stroke("ink-700", 4)}"/>
    <path d="M712 716l40-22 14 26-40 18Z" style="${fill("tile-jade")}"/>
    <rect x="736" y="794" width="26" height="8" rx="2" style="${fill("ink-700")}"/>
    <g class="lr-notebook">
      <path d="M560 806L620 802L626 812L566 816Z" style="${fill("lime-50")}"/>
      <path d="M576 803l38-3" style="${stroke("tile-indigo", 1, 0.5)}"/>
      <path d="M630 798l34-6" style="${stroke("tile-ochre", 4)}"/>
    </g>
  </g>`;
};

// L5: pots, paper-cut grass and fallen petals in the foreground.
const front = (rand) => {
  const blades = (x0, x1, base, h) => {
    const pts = [`M${x0} ${base}`];
    for (let x = x0; x < x1; x += 14 + rand() * 10) pts.push(`L${r1(x + 6)} ${r1(base - h * (0.5 + rand() * 0.6))}L${r1(x + 14)} ${base}`);
    // M6 fix: the grass ends in its own torn-paper foot a little under its blades (never a dark mass the scene's
    // mask has to blur away): a jagged line back along the bottom.
    let foot = `L${x1} ${base + 34}`;
    for (let x = x1; x > x0; x -= 16 + rand() * 12) foot += `L${r1(x)} ${r1(base + 26 + rand() * 18)}`;
    return pts.join("") + foot + `L${x0} ${base + 34}Z`;
  };
  const petals = Array.from({ length: 16 }, () => {
    const x = 140 + rand() * 520, y = 958 + rand() * 50;
    return `<ellipse cx="${r1(x)}" cy="${r1(y)}" rx="5" ry="3" transform="rotate(${r1(rand() * 180)} ${r1(x)} ${r1(y)})" style="${fill(rand() > 0.5 ? "sun-300" : "tile-ochre", 0.85)}"/>`;
  }).join("");
  const pot = (x, w) => `<path d="M${x} 900H${x + w}L${x + w - 14} 1010H${x + 14}Z" style="${fill("tile-ochre")}"/><rect x="${x - 6}" y="892" width="${w + 12}" height="16" rx="3" style="${fill("gold-700")}"/>
    <path d="M${x + w / 2} 892C${x + 4} 840 ${x - 20} 830 ${x - 10} 800M${x + w / 2} 892C${x + w / 2} 830 ${x + w / 2 + 10} 810 ${x + w / 2 + 6} 780M${x + w / 2} 892C${x + w} 850 ${x + w + 26} 840 ${x + w + 30} 812" style="${stroke("tile-jade", 7)}"/>`;
  // Lifted 90 units so the pots and the grass sit inside the frame's safe area, clear of the foot's feather.
  return `<g filter="url(#lr-cut)" transform="translate(0 -90)">
    ${pot(76, 104)}${pot(620, 100)}
    <path d="${blades(-20, 820, 1010, 60)}" style="${fill("tile-jade")}"/>
    <path d="${blades(-20, 820, 1060, 50)}" style="${fill("tile-jade", 0.75)}"/>
    ${petals}
  </g>`;
};

const layer = (n, body, cls = "") => `<svg class="lr-layer lr-layer--${n}${cls}" viewBox="${VB}" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">${body}</svg>`;

// The whole picture. Decorative: aria-hidden, with the caption under it in the page (artNote).
export const shelterScene = () => {
  const rand = rng(20261111);
  const svg = `<svg class="lr-scene lr-layer lr-layer--0" viewBox="${VB}" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">${defs()}${sky()}</svg>`
    + layer(1, wall(rand))
    + layer(2, trellis(rand))
    + layer(3, line())
    + layer(4, desk())
    + layer(5, front(rand))
    + `<span class="lr-layer lr-grain"></span>`;
  return html`<div class="lr-garden__scene" aria-hidden="true">${raw(svg)}</div>`;
};
