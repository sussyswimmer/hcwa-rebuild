import { $ } from "./dom.js";

// Mobile menu: a modal <dialog> gives focus trapping and Escape for free.
export function initMenuSheet() {
  const sheet = $("#menu-sheet");
  const opener = $("[data-open-sheet]");
  if (!sheet) return;
  opener?.addEventListener("click", () => sheet.showModal());
  $("[data-close-sheet]")?.addEventListener("click", () => sheet.close());
  let followedLink = false;
  sheet.addEventListener("click", (e) => {
    followedLink = Boolean(e.target.closest("a"));
    if (e.target === sheet || followedLink) sheet.close();
  });
  // After a link, focus belongs to where the link went (same-page anchors), not the menu button.
  sheet.addEventListener("close", () => { if (!followedLink) opener?.focus(); followedLink = false; });
}
