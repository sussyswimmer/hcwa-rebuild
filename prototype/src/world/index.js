import { forcedTier, probe, store } from "./tiers.js";

// The world's entry (05-DESIGN D6.9, PLAN H2). main.js imports it after load + idle on Tier 1-2
// with motion on. It holds no three.js: the GPU gets its say first, and three.js (live.js) is
// requested only when a live world can run. No WebGL2, a failed caveat context or a software
// GPU is Tier 0 (D6.7): the stills stay, and this browser skips the probe from now on.
// The probe's context is simply left alone on the stills path: releasing it (WEBGL_lose_context) is a
// synchronous GPU-process round trip that blocked the main thread for over a second on a throttled phone.
const NOOP = { setPaused() {}, dispose() {} };
// Nothing will read scroll progress on this page any more: the motion layer can stop listening.
export const worldOff = (bus) => { if (!bus || bus.state.world === "off") return; bus.state.world = "off"; bus.dispatchEvent(new Event("world-off")); };

export async function startWorld({ canvas, stage, tier: pageTier, bus }) {
  const forced = forcedTier();
  const probed = probe(canvas, Number(pageTier ?? document.documentElement.dataset.tier ?? 1), { forced });
  if (!probed.gl || probed.tier < 1) {
    console.info(`[world] stills only: ${probed.reason}`);
    if (forced == null) { store.set(0); store.noGpu(); }
    worldOff(bus);
    return NOOP;
  }
  const go = () => import("./live.js").then(({ live }) => live({ canvas, stage, bus, probed, forced }));
  if (!bus?.state.paused) return go();
  // Paused before three.js was requested: nothing loads until the visitor turns motion back on.
  let started = null, dead = false;
  return {
    setPaused(p) {
      if (p || dead || started) return started?.then((w) => w.setPaused(p));
      started = go().then((w) => (dead ? (w.dispose(), NOOP) : w));
    },
    dispose() { dead = true; started?.then((w) => w.dispose()); },
  };
}
