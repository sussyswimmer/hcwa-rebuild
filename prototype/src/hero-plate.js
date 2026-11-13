import { bucketFor } from "./world/buckets.js";
import { POSTER_FRAMES } from "./world/poster-frames.js";

// The hero's text never sits on the screen's silhouette (PLAN H2, WCAG 1.4.3 / 1.4.4). The stills and the live
// rest frame are framed around the text at its normal size; zoomed text (200%) or an unusual window can grow
// past that safe zone. When any text line meets the poster's screen outline, the hero's text gets a feathered
// lime-50 plate (world.css .hero--plate), so it reads on plaster whatever the world draws behind it.
// Data only (no three.js): the outline is the one render-posters.mjs wrote for the poster this window shows.
const PAD = 8;

// Separating axis test: convex polygon against a padded rect (both in viewport px).
function meets(poly, r) {
  const x0 = r.left - PAD, x1 = r.right + PAD, y0 = r.top - PAD, y1 = r.bottom + PAD;
  if (poly.every(([x]) => x < x0) || poly.every(([x]) => x > x1) || poly.every(([, y]) => y < y0) || poly.every(([, y]) => y > y1)) return false;
  const corners = [[x0, y0], [x1, y0], [x1, y1], [x0, y1]];
  for (let i = 0; i < poly.length; i++) {
    const [ax, ay] = poly[i], [bx, by] = poly[(i + 1) % poly.length], nx = ay - by, ny = bx - ax;
    const p = poly.map(([x, y]) => x * nx + y * ny), c = corners.map(([x, y]) => x * nx + y * ny);
    if (Math.max(...p) < Math.min(...c) || Math.max(...c) < Math.min(...p)) return false;
  }
  return true;
}

export function initHeroPlate() {
  const hero = document.querySelector(".hero[data-world]"), inner = hero?.querySelector(".hero__inner");
  if (!inner || !("ResizeObserver" in window)) return;
  const check = () => {
    const frame = POSTER_FRAMES[bucketFor(innerWidth, innerHeight).name];
    if (!frame?.silhouette) return;
    // The poster covers the hero box, centred across and anchored to its foot (world/frame.js frameOrigin).
    const box = hero.getBoundingClientRect(), [pw, ph] = frame.view, k = Math.max(box.width / pw, box.height / ph);
    const ox = box.left + (box.width - pw * k) / 2, oy = box.top + box.height - ph * k;
    const poly = frame.silhouette.map(([x, y]) => [ox + x * k, oy + y * k]);
    const lines = [...inner.querySelectorAll("[data-text-safe]:not(.hero__actions)")].flatMap((el) => [...el.getClientRects()]);
    hero.classList.toggle("hero--plate", lines.some((r) => meets(poly, r)));
  };
  let pending = 0;
  const later = () => { cancelAnimationFrame(pending); pending = requestAnimationFrame(check); };
  // The observer's first call is the first check, after the first layout and before the first paint (M8: not a
  // layout forced in main.js's own task); later ones wait for the next frame.
  let first = true;
  new ResizeObserver(() => (first ? ((first = false), check()) : later())).observe(inner);
  addEventListener("resize", later, { passive: true });
  document.fonts?.ready.then(later);
}
