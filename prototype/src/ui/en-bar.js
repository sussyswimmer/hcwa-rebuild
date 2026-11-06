import { $, store } from "./dom.js";

// "View this page in English" for English browsers on VI pages; no automatic redirect (07-BILINGUAL).
export function initEnBar() {
  const bar = $("[data-en-bar]");
  if (bar && navigator.language?.toLowerCase().startsWith("en") && !store.get("hcwa-en-bar")) bar.hidden = false;
  $("[data-dismiss-en-bar]")?.addEventListener("click", () => { bar.hidden = true; store.set("hcwa-en-bar", "1"); });
  $("[data-dismiss-announce]")?.addEventListener("click", (e) => { e.target.closest("[data-announce]").hidden = true; });
}
