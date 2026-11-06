// One fade-up per section, only for sections still below the fold (05-DESIGN D5.4: 12 px, 520 ms).
// Content is visible by default; .reveal-pending is added only when motion is allowed.
export function initReveals() {
  if (document.documentElement.dataset.motion === "off" || !("IntersectionObserver" in window)) return;
  const io = new IntersectionObserver((entries) => entries.forEach((e) => {
    if (e.isIntersecting) { e.target.classList.remove("reveal-pending"); io.unobserve(e.target); }
  }), { rootMargin: "0px 0px -15% 0px" });
  // Every rect is read before any class is written: one style and layout pass, not one per section.
  const below = [...document.querySelectorAll("[data-reveal]")].filter((el) => el.getBoundingClientRect().top > innerHeight);
  below.forEach((el) => { el.classList.add("reveal-pending"); io.observe(el); });
}
