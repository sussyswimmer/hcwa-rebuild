// Tiny escaping template tag: interpolations are escaped unless they came from html`` or raw().
const SAFE = Symbol.for("hcwa.safe");
const ESC = { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" };

export const raw = (s) => ({ [SAFE]: String(s) });
export const esc = (v) => String(v).replace(/[&<>"']/g, (c) => ESC[c]);

const val = (v) =>
  v == null || v === false
    ? ""
    : Array.isArray(v)
      ? v.map(val).join("")
      : typeof v === "object" && SAFE in v
        ? v[SAFE]
        : esc(v);

export const html = (strings, ...vals) =>
  raw(strings.reduce((out, s, i) => out + s + (i < vals.length ? val(vals[i]) : ""), ""));

export const str = (v) => val(v);
