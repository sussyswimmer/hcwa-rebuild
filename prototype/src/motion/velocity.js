import { gsap } from "gsap";
import { bus } from "../bus.js";
import { hold, release } from "./ticker.js";

// Scroll velocity → critically damped spring → bus "velocity" (05-DESIGN D5.1: motes follow scroll
// velocity through a damped spring, no bounce). Units: viewport heights per second, signed (down +).
// Works for native touch scroll and Lenis alike, because it reads scrollY. Emits only while the
// spring moves, so the world's render-on-demand loop can sleep; nothing is allocated per frame.
const OMEGA = 6;      // rad/s, the same stiffness as the pointer sun
const MAX = 6;        // clamp flings so a fast swipe cannot blow the motes away
const REST = 1e-3;

export function startVelocity() {
  const detail = { value: 0, raw: 0 };
  const event = new CustomEvent("velocity", { detail });
  let lastY = scrollY, x = 0, v = 0, on = false;

  const emit = (value, raw) => {
    detail.value = value;
    detail.raw = raw;
    bus.state.velocity = value;
    bus.dispatchEvent(event);
  };

  // On the ticker only between a scroll and the spring coming to rest: an idle page runs no frames.
  const tick = (_time, deltaMs) => {
    const dt = Math.min(Math.max(deltaMs, 1) / 1000, 1 / 20);
    const y = scrollY;
    const raw = bus.state.paused ? 0 : Math.max(-MAX, Math.min(MAX, (y - lastY) / dt / innerHeight));
    lastY = y;
    // Semi-implicit Euler on x'' = ω²(target − x) − 2ωx' (ζ = 1).
    v += (OMEGA * OMEGA * (raw - x) - 2 * OMEGA * v) * dt;
    x += v * dt;
    if (raw === 0 && Math.abs(x) < REST && Math.abs(v) < REST) {
      x = v = 0;
      emit(0, 0);
      gsap.ticker.remove(tick);
      on = false;
      release();
      return;
    }
    emit(x, raw);
  };
  addEventListener("scroll", () => { if (!on) { on = true; hold(); gsap.ticker.add(tick); } }, { passive: true });
}
