import { packFrom } from "./walls-pack.js";

// The wall maps off the main thread (walls.js): fetch, decode and pack one material, hand the texels back as a
// transferable buffer. The page makes the texture.
self.onmessage = async ({ data: { name, size, urls } }) => {
  try {
    const p = await packFrom(name, size, urls);
    self.postMessage(p, [p.data.buffer]);
  } catch (e) {
    self.postMessage({ name, error: String(e?.message ?? e) });
  }
};
