import { $, i18n, store } from "./dom.js";

// Staging-only reviewer toggle: hides the dashed draft outlines for this session (05-DESIGN D7).
// The label always says what a press will do (no aria-pressed, which would contradict a changing label).
export function initDraftToggle() {
  const button = $("[data-draft-toggle]");
  if (!button) return;
  const root = document.documentElement;
  const sync = () => {
    button.textContent = root.classList.contains("drafts-hidden") ? i18n.draftShow : i18n.draftHide;
  };
  sync();
  button.addEventListener("click", () => {
    const hidden = root.classList.toggle("drafts-hidden");
    if (hidden) store.set("hcwa:drafts", "hidden", "sessionStorage");
    else store.remove("hcwa:drafts", "sessionStorage");
    sync();
  });
}
