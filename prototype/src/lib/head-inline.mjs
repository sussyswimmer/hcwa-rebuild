import { readFileSync } from "node:fs";
import { raw } from "./html.mjs";

// src/head-inline.js joined onto one line; prototype query flags (?tier, ?motion) are compiled out of PROD.
const source = readFileSync(new URL("../head-inline.js", import.meta.url), "utf8");
const BUDGET = 1536;

export function headInline(prod) {
  const js = source
    .split("\n")
    .map((l) => l.trim())
    .filter((l) => l && !l.startsWith("//"))
    .join(" ")
    .replaceAll("__PROTO__", prod ? "false" : "true");
  if (Buffer.byteLength(js) > BUDGET) throw new Error(`head-inline.js is ${Buffer.byteLength(js)} bytes; the budget is ${BUDGET}`);
  return raw(`<script>${js}</script>`);
}
