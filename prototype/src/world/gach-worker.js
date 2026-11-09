import { gachBaker } from "./gach-bake.js";

// The corridor's tile bake off the main thread (05-DESIGN D0.6.2): both textures' pixels, handed back as
// transferable buffers. Pure maths; the pigments come from the page (tokens), the textures are made there.
self.onmessage = ({ data: { size, P } }) => {
  const out = [["field", size, size, 2, 2], ["border", size, size / 2, 2, 1]].map(([kind, w, h, tx, ty]) => {
    const fill = gachBaker(kind, w, h, tx, ty, P);
    fill(0, h);
    return fill.data;
  });
  self.postMessage(out, out.map((d) => d.buffer));
};
