export const $ = (sel, root = document) => root.querySelector(sel);
export const $$ = (sel, root = document) => [...root.querySelectorAll(sel)];
export const i18n = JSON.parse($("#i18n")?.textContent ?? "{}");
// Storage can throw (private mode, blocked site data): every read and write is best effort.
export const store = {
  get: (k, area = "localStorage") => { try { return window[area].getItem(k); } catch { return null; } },
  set: (k, v, area = "localStorage") => { try { window[area].setItem(k, v); } catch { /* lasts this page only */ } },
  remove: (k, area = "localStorage") => { try { window[area].removeItem(k); } catch { /* nothing stored */ } },
};
