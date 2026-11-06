// Aspect buckets shared by the posters and the live camera (05-DESIGN D6.10). No three.js
// here, so the page's Node build (lib/posters.mjs) can import it. `view` is the CSS
// viewport a bucket's poster is framed for; `media` is the <source> query that picks it.
export const BUCKETS = [
  { name: "portrait", aspect: 9 / 19.5, max: 10 / 17, view: [390, 844], media: "(max-aspect-ratio: 10/17)" },
  { name: "tablet", aspect: 3 / 4, max: 12 / 11, view: [768, 1024], media: "(max-aspect-ratio: 12/11)" },
  { name: "landscape", aspect: 16 / 10, max: 27 / 16, view: [1440, 900], media: "(max-aspect-ratio: 27/16)" },
  { name: "wide", aspect: 16 / 9, max: Infinity, view: [1920, 1080], media: null },
];

export const POSTER_WIDTHS = [480, 960, 1600];

export const bucketFor = (w, h) => BUCKETS.find((b) => w / h <= b.max);
