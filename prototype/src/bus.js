// The one channel between motion/ and world/ (PLAN section 1): events "progress", "velocity", "pause".
// `state` keeps the latest values, so a world that starts late can read where the page already is.
export const bus = (window.hcwa ??= Object.assign(new EventTarget(), { state: { progress: {}, velocity: 0, paused: false, sunIn: "pending" } }));
