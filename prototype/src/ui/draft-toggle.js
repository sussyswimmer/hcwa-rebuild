import { $, i18n, store } from "./dom.js";

// Staging-only reviewer toggle: shows the dashed draft outlines for this session (05-DESIGN D7).
// Off by default (Maxwell, 9 Nov 2026); the data-draft attributes and the PROD guard are unchanged.
// The label always says what a press will do (no aria-pressed, which would contradict a changing label).
export function initDraftToggle() {
  const button = $("[data-draft-toggle]");
  if (!button) return;
  const root = document.documentElement;
  const sync = () => {
    button.textContent = root.classList.contains("drafts-shown") ? i18n.draftHide : i18n.draftShow;
  };
  sync();
  button.addEventListener("click", () => {
    const shown = root.classList.toggle("drafts-shown");
    if (shown) store.set("hcwa:drafts", "shown", "sessionStorage");
    else store.remove("hcwa:drafts", "sessionStorage");
    sync();
  });
}
