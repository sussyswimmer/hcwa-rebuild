import { bus } from "../bus.js";

// M8 long tasks: on the home and the kit page the content boxes far below the fold skip their style and layout until
// they come near the screen (layout.css, content-visibility: auto; it says why no inner page has any), so the first
// paint lays out much less. This lays each box out
// once, one per frame in idle time after the first paint (html stays as it is: the box alone gets .cv-warm for one
// frame), so each keeps its real size (contain-intrinsic-size: auto) and the page has its real height long before
// anyone scrolls to a stage or a footer link. "boxes" on the bus says the sizes are in (motion/scroll.js refreshes
// ScrollTrigger then). Again after a width change, which changes every box's height.
// The same list as layout.css (test/cv-boxes.test.mjs keeps them equal).
export const CV_BOXES = ".page-home .bays-section > .bays__track, .page-home .wall > .container, .page-home .wall > section, .page-home .wall ~ .dusk > .container, .page-home .site-footer__cols, .kit__body > .kit-sec ~ .kit-sec ~ .kit-sec, .kit-sec > .pairs-box, .page-kit .site-footer__cols";
const idle = (fn) => ("requestIdleCallback" in window ? requestIdleCallback(fn, { timeout: 1000 }) : setTimeout(fn, 50));

export function initWarm() {
  if (!CSS.supports?.("content-visibility", "auto")) return void (bus.state.boxes = true);
  let run = 0;
  const warm = () => {
    const id = ++run, boxes = [...document.querySelectorAll(CV_BOXES)];
    bus.state.boxes = false;
    let i = 0, prev = null;
    const step = () => {
      if (id !== run) return void prev?.classList.remove("cv-warm");
      prev?.classList.remove("cv-warm");
      if (i >= boxes.length) {
        bus.state.boxes = true;
        bus.dispatchEvent(new Event("boxes"));
        return;
      }
      const box = boxes[i++], r = box.getBoundingClientRect();
      // A box within a screen of the view is the browser's own to lay out (content-visibility: auto does it as it
      // comes near); switching it here would only report its words as moved (Chrome scores that as a layout shift).
      if (r.bottom > -innerHeight && r.top < 2 * innerHeight) { prev = null; return void idle(step); }
      prev = box;
      prev.classList.add("cv-warm");
      // Its layout is the next frame's; the next box waits for idle time after that frame.
      requestAnimationFrame(() => setTimeout(() => idle(step)));
    };
    idle(step);
  };
  // innerWidth is read from the load on, never in main.js's first task: in Chrome it lays the page out.
  let w = 0, timer = 0;
  const start = () => requestAnimationFrame(() => setTimeout(() => {
    w = innerWidth;
    warm();
    addEventListener("resize", () => {
      if (innerWidth === w) return;
      w = innerWidth;
      clearTimeout(timer);
      timer = setTimeout(warm, 400);
    }, { passive: true });
  }));
  if (document.readyState === "complete") start();
  else addEventListener("load", start, { once: true });
}
