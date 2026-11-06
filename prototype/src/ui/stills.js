// A still that has not been rendered yet (or failed) hides instead of showing a broken-image icon;
// the lit header's shade fill holds the layout without it.
export function initStills() {
  for (const img of document.querySelectorAll(".lit img")) {
    const hide = () => { img.style.visibility = "hidden"; };
    if (img.complete && img.naturalWidth === 0) hide();
    else img.addEventListener("error", hide, { once: true });
  }
}
