import { esc, raw } from "./html.mjs";

// Sunlit reading (05-DESIGN D5.4): the statement split into word spans at build time, each carrying its
// index as --w, so one scrubbed custom property on the block lights the words in order. The spans are
// aria-hidden and the plain sentence sits beside them visually hidden, so screen readers hear the
// sentence once, never word by word. Each word keeps its following space inside its span, so the light
// runs on across the gap, and the line still breaks where the text does.
export function splitWords(text) {
  const words = String(text).normalize("NFC").trim().split(/\s+/).filter(Boolean);
  const spans = words.map((w, i) => `<span class="w" style="--w:${i}">${esc(w)}${i < words.length - 1 ? " " : ""}</span>`).join("");
  return { count: words.length, words: raw(spans) };
}
