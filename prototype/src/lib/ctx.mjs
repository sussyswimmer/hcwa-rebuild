import { raw } from "./html.mjs";
import { content } from "./content.mjs";
import { routes } from "../routes.mjs";

const LOCALE = { vi: "vi-VN", en: "en-GB" };

// One context per page render: language, translation helpers, URLs, production flag.
export function ctx(lang, routeKey, prod) {
  const other = lang === "vi" ? "en" : "vi";
  const missing = (what) => {
    throw new Error(`Missing ${lang.toUpperCase()} text: ${what}`);
  };
  const t = (key, vars) => {
    const s = content.ui[key]?.[lang] ?? missing(`ui.${key}`);
    return vars ? s.replace(/\{(\w+)\}/g, (_, k) => vars[k]) : s;
  };
  // Localized field: { vi, en, draft? }. Plain strings are language-neutral (names, URLs).
  const tx = (field) => (field == null || typeof field === "string" ? field ?? "" : field[lang] ?? missing(JSON.stringify(field)));
  const d = (field) => (field?.draft ? raw(" data-draft") : "");
  const url = (key, l = lang) => routes[key]?.[l] ?? missing(`route ${key}`);
  const date = (iso) =>
    new Intl.DateTimeFormat(LOCALE[lang], lang === "vi" ? { day: "2-digit", month: "2-digit", year: "numeric" } : { day: "numeric", month: "short", year: "numeric" }).format(new Date(iso + "T00:00:00"));
  const number = (n) => new Intl.NumberFormat(LOCALE[lang]).format(n);
  return { lang, other, routeKey, prod, content, t, tx, d, url, date, number };
}
