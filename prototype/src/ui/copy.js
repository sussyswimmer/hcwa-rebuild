import { $, $$, i18n } from "./dom.js";

// Copy buttons announce the result in a live region (disabled until donation details are verified).
export function initCopy() {
  const buttons = $$("[data-copy]");
  if (!buttons.length) return;
  let live = $("[data-live]");
  if (!live) { live = Object.assign(document.createElement("p"), { className: "sr-only" }); live.setAttribute("aria-live", "polite"); document.body.append(live); }
  // Clear first so a repeated "Copied" is announced again.
  const announce = (msg) => { live.textContent = ""; setTimeout(() => { live.textContent = msg; }, 50); };
  buttons.forEach((btn) => btn.addEventListener("click", async () => {
    const label = $("span", btn);
    try {
      await navigator.clipboard.writeText(btn.dataset.copy);
      label.textContent = i18n.copied;
      announce(i18n.copied);
    } catch {
      announce(i18n.copyFailed);
    }
    setTimeout(() => { label.textContent = i18n.copy; }, 2000);
  }));
}
