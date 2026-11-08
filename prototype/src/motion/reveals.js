// Fade-ups for content still below the fold (05-DESIGN D5.4: 12 px, 520 ms). data-reveal="stagger" reveals
// its children one after another (stagger-cascade: 60 ms, at most 5 steps). Content is visible by default:
// .reveal-pending is added only when motion is allowed, so no JS, reduced motion and pause show everything.
const MAX_STEPS = 5;

export function initReveals() {
  if (document.documentElement.dataset.motion === "off" || !("IntersectionObserver" in window)) return;
  const targets = (el) => (el.dataset.reveal === "stagger" ? [...el.children] : [el]);
  const show = (el) => { targets(el).forEach((t) => t.classList.remove("reveal-pending")); io.unobserve(el); entry.unobserve(el); };
  const seen = (entries) => entries.forEach((e) => { if (e.isIntersecting) show(e.target); });
  // The root reaches far above the viewport: a section the visitor flung past (or an anchor jumped over)
  // counts as seen the moment it is above the fold, so it never waits hidden behind them.
  const io = new IntersectionObserver(seen, { rootMargin: "100000px 0px -15% 0px" });
  // data-reveal-on="entry": as soon as it crosses the foot (the help heading rising into the shutters' flood).
  const entry = new IntersectionObserver(seen, { rootMargin: "100000px 0px 0px 0px" });
  // Every rect is read before any class is written: one style and layout pass, not one per section.
  const below = [...document.querySelectorAll("[data-reveal]")].filter((el) => el.getBoundingClientRect().top > innerHeight);
  below.forEach((el) => {
    targets(el).forEach((t, i) => {
      if (el.dataset.reveal === "stagger") t.style.setProperty("--i", Math.min(i, MAX_STEPS - 1));
      t.classList.add("reveal-pending");
    });
    (el.dataset.revealOn === "entry" ? entry : io).observe(el);
  });
  // Focus never lands on something still invisible: a keyboard visitor tabbing into a waiting group (or
  // a section focus scrolled into view) sees the whole group at once, without the fade, as the corridor
  // does with is-instant. The group is then seen; nothing replays.
  document.addEventListener("focusin", (e) => {
    for (let group = e.target.closest?.("[data-reveal]"); group; group = group.parentElement?.closest("[data-reveal]")) {
      if (!targets(group).some((t) => t.classList.contains("reveal-pending"))) continue;
      group.classList.add("reveal-instant");
      show(group);
      requestAnimationFrame(() => requestAnimationFrame(() => group.classList.remove("reveal-instant")));
    }
  });
}
