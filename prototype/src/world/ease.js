// cubic-bezier() from the motion tokens, for the few time-driven world tweens (the cloud).
export function cubicBezier([x1, y1, x2, y2]) {
  const cx = 3 * x1, bx = 3 * (x2 - x1) - cx, ax = 1 - cx - bx;
  const cy = 3 * y1, by = 3 * (y2 - y1) - cy, ay = 1 - cy - by;
  const x = (u) => ((ax * u + bx) * u + cx) * u;
  const dx = (u) => (3 * ax * u + 2 * bx) * u + cx;
  return (t) => {
    if (t <= 0) return 0;
    if (t >= 1) return 1;
    let u = t;
    for (let i = 0; i < 6; i++) { const d = dx(u); if (Math.abs(d) < 1e-6) break; u -= (x(u) - t) / d; }
    if (u < 0 || u > 1 || Math.abs(x(u) - t) > 1e-4) {
      let lo = 0, hi = 1;
      for (let i = 0; i < 24; i++) { u = (lo + hi) / 2; x(u) < t ? (lo = u) : (hi = u); }
    }
    return ((ay * u + by) * u + cy) * u;
  };
}

// Critically damped spring (ζ = 1, no overshoot), semi-implicit Euler. Returns true while moving.
export function stepSpring(s, target, dt, omega) {
  s.v += (omega * omega * (target - s.x) - 2 * omega * s.v) * dt;
  s.x += s.v * dt;
  if (Math.abs(target - s.x) < 1e-4 && Math.abs(s.v) < 1e-4) { s.x = target; s.v = 0; return false; }
  return true;
}
