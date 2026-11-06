import { $, $$ } from "./dom.js";

// Gallery lightbox: <dialog>, arrow keys, Escape, focus returns to the thumbnail.
export function initLightbox() {
  const box = $("[data-lightbox]");
  const items = $$("[data-gallery] .gallery__item");
  if (!box || !items.length) return;
  let i = 0, from = null;
  const show = (n) => {
    i = (n + items.length) % items.length;
    $("[data-lightbox-stage]", box).replaceChildren(items[i].firstElementChild.cloneNode(true));
    $("[data-lightbox-count]", box).textContent = `${i + 1} / ${items.length}`;
  };
  items.forEach((b, n) => b.addEventListener("click", () => { from = b; show(n); box.showModal(); }));
  $("[data-lightbox-prev]", box).addEventListener("click", () => show(i - 1));
  $("[data-lightbox-next]", box).addEventListener("click", () => show(i + 1));
  $("[data-lightbox-close]", box).addEventListener("click", () => box.close());
  box.addEventListener("keydown", (e) => { if (e.key === "ArrowLeft") show(i - 1); if (e.key === "ArrowRight") show(i + 1); });
  box.addEventListener("close", () => from?.focus());
}
