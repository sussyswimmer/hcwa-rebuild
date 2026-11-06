import { esc, raw } from "./html.mjs";

// Heading compounds that must not break across lines, joined with U+00A0 (05-DESIGN D2).
// Used on H1 and H2 only; `text-wrap: balance` then shares the line length between them.
const GLOSSARY = [
  "trẻ em", "xây dựng", "tương lai", "an toàn", "tươi sáng", "thanh thiếu niên", "gia đình", "cộng đồng",
  "bảo vệ", "giáo dục", "chung tay", "đối tác", "hoạt động", "dự án", "chương trình", "cơ sở", "mái ấm",
  "Hồ Chí Minh", "TP. Hồ Chí Minh", "Tre Xanh", "Cầu Hàn", "Hoa Hồng Nhỏ", "Cùng vươn lên",
  "Green Bamboo", "Little Rose", "Thrive Together",
];
const escape = (s) => s.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
// Longest first, so "TP. Hồ Chí Minh" wins over "Hồ Chí Minh". Letters on either side mean a longer word.
const RE = new RegExp(
  `(?<![\\p{L}\\p{M}])(?:${[...GLOSSARY].sort((a, b) => b.length - a.length).map(escape).join("|")})(?![\\p{L}\\p{M}])`,
  "giu",
);

export const viKeep = (text) => String(text).normalize("NFC").replace(RE, (m) => m.replaceAll(" ", " "));

// Hyphenated compounds kept on one line ("community-based"): the body font subset has no U+2011, so
// a no-wrap span does it. Returns escaped HTML.
export const keepHyphens = (text) => raw(esc(String(text)).replace(/[\p{L}\p{M}]+(?:-[\p{L}\p{M}]+)+/gu, (m) => `<span class="nowrap">${m}</span>`));
