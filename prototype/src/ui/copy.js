import { $, $$, i18n } from "./dom.js";

// Copy buttons (05-DESIGN D5.7): the label turns to "Đã sao chép" / "Copied" with a check (pop-in, 200 ms)
// and a polite live region says the same in the page language; both revert after 2 s. On Donate every
// copy button stays disabled until HCWA finance confirms the value it would copy.
export function initCopy() {
  const buttons = $$("[data-copy]");
  if (!buttons.length) return;
  let live = $("[data-live]");
  if (!live) { live = Object.assign(document.createElement("p"), { className: "sr-only" }); live.setAttribute("aria-live", "polite"); document.body.append(live); }
  // Clear first so a repeated "Copied" is announced again.
  const announce = (msg) => { live.textContent = ""; setTimeout(() => { live.textContent = msg; }, 50); };
  buttons.forEach((btn) => {
    let timer = 0;
    btn.addEventListener("click", async () => {
      if (btn.disabled || !btn.dataset.copy) return;
      const label = $("span", btn);
      clearTimeout(timer);
      try {
        await navigator.clipboard.writeText(btn.dataset.copy);
        label.textContent = i18n.copied;
        btn.dataset.state = "copied";
        announce(i18n.copied);
      } catch {
        announce(i18n.copyFailed);
      }
      timer = setTimeout(() => { label.textContent = i18n.copy; delete btn.dataset.state; }, 2000);
    });
  });
}
