// Tier decision and live gates (05-DESIGN D6.7). The head script picks 0/1/2 from cheap
// signals before first paint; here the GPU gets its say, and the live gates step the world
// down while it runs. sessionStorage hcwa:tier carries a downgrade to the next page.
const KEY = "hcwa:tier";
export const store = {
  get() { try { const v = sessionStorage.getItem(KEY); return v == null ? null : Number(v); } catch { return null; } },
  set(v) { try { sessionStorage.setItem(KEY, String(v)); } catch { /* private mode: this page only */ } },
  // A GPU that cannot run the world (no WebGL2, a caveat context, a software renderer) stays that way
  // for this browser: the head script reads hcwa:gpu0 and returning visitors skip the probe. Keyed by
  // the user agent, so a browser or driver update asks again. Frame-time verdicts stay per session.
  noGpu() { try { localStorage.setItem("hcwa:gpu0", navigator.userAgent); } catch { /* this visit only */ } },
};

// ?tier=0|1|2 is a prototype flag. It counts only when the head script honoured it (it marks
// data-tier-flag), so a PROD build, which ignores the flag, never lets a URL skip the GPU checks
// and live gates, even for a visitor whose own tier happens to equal the flag.
export function forcedTier() {
  const root = document.documentElement;
  return root.hasAttribute("data-tier-flag") ? Number(root.dataset.tier) : null;
}

const SOFTWARE = /swiftshader|llvmpipe|softpipe|software|basic render/i;
const LOW_GPU = /mali-(4|t6|t7|g31|g51|g52)|adreno[^0-9]*(3|4|50|51)\d\d|powervr|sgx|videocore|intel.*hd graphics [2-5]\d\d\b/i;

export const CONTEXT_ATTRS = { alpha: false, depth: true, stencil: false, premultipliedAlpha: true, powerPreference: "high-performance" };

// GPU memory (05-DESIGN D6.7: Tier 1 24 MB, Tier 2 48 MB). Every canvas pays about 12 bytes a pixel
// (depth, resolve, display); 4x MSAA adds about 32 more, so only a small drawing buffer can afford it.
// Tier 1 renders the SDF screen (its holes are smooth without MSAA) at DPR <= 1.25 and never takes it.
export const BYTES = { base: 12, msaa: 32 };
export const MSAA_MAX_PIXELS = 1e6;
export const canvasPixels = (dpr = globalThis.devicePixelRatio || 1) => Math.round((globalThis.innerWidth || 1) * (globalThis.innerHeight || 1) * dpr * dpr);
export const wantsMsaa = (tier, poster) => poster || (tier >= 2 && canvasPixels(1) <= MSAA_MAX_PIXELS);

// Opens the WebGL2 context the renderer will use (attributes, MSAA included, are fixed at creation).
export function probe(canvas, pageTier, { forced = forcedTier(), poster = false } = {}) {
  const likely = forced ?? Math.min(pageTier, store.get() ?? 2);
  const attrs = { ...CONTEXT_ATTRS, antialias: wantsMsaa(likely, poster), preserveDrawingBuffer: poster, failIfMajorPerformanceCaveat: forced == null && !poster };
  let gl = null;
  try { gl = canvas.getContext("webgl2", attrs); } catch { gl = null; }
  if (!gl && (forced != null || poster)) try { gl = canvas.getContext("webgl2", { ...attrs, failIfMajorPerformanceCaveat: false }); } catch { gl = null; }
  if (!gl) return { tier: 0, gl: null, gpu: "", reason: "no WebGL2 context" };
  const info = gl.getExtension("WEBGL_debug_renderer_info");
  const gpu = String(gl.getParameter(info ? info.UNMASKED_RENDERER_WEBGL : gl.RENDERER) || "");
  if (forced != null) return { tier: forced, gl, gpu, reason: "?tier flag" };
  if (poster) return { tier: pageTier, gl, gpu, reason: "poster" };
  let tier = Math.min(pageTier, store.get() ?? 2);
  let reason = "head script";
  if (SOFTWARE.test(gpu)) (tier = 0), (reason = "software GPU");
  else if (tier > 1 && LOW_GPU.test(gpu)) (tier = 1), (reason = "low-end GPU");
  return { tier, gl, gpu, reason };
}

const p75 = (a) => [...a].sort((x, y) => x - y)[Math.floor(a.length * 0.75)];

// Live gates: 90 frames after the handoff, GPU time from EXT_disjoint_timer_query_webgl2 when
// present, else rAF deltas (the loop renders every frame while a gate measures).
// rAF deltas cannot see GPU time under the 16.7 ms vsync, so they gate on missed frames:
// Tier 2 p75 > 22 ms → Tier 1. Tier 1 p75 > 22 ms → lite (no shafts, DPR 1). Lite > 33 ms → off.
// A timer query measures the GPU itself and gates on the D6.7 budgets with a little headroom:
// Tier 2 > 10 ms → Tier 1, Tier 1 > 14 ms → lite, lite > 22 ms → off.
export const LIMITS = { raf: { t2: 22, t1: 22, lite: 33 }, timer: { t2: 10, t1: 14, lite: 22 } };
export function createGate(gl, { tier, onStep }) {
  const ext = gl.getExtension("EXT_disjoint_timer_query_webgl2");
  const limit = LIMITS[ext ? "timer" : "raf"], source = ext ? "timer" : "rAF";
  const queries = [], samples = [];
  let state = tier >= 2 ? "t2" : "t1", last = 0, open = null, done = false;
  const decide = () => {
    const v = p75(samples);
    samples.length = 0;
    const next = v > limit[state] ? { t2: "t1", t1: "lite", lite: "off" }[state] : null;
    if (!next) { done = true; return onStep({ state, p75: v, final: true, source }); }
    state = next;
    store.set(next === "t1" || next === "lite" ? 1 : 0);
    if (next === "off") done = true;
    onStep({ state, p75: v, final: done, source });
  };
  const push = (ms) => { if (done) return; samples.push(ms); if (samples.length >= 90) decide(); };
  return {
    get measuring() { return !done; },
    begin() {
      if (done || !ext || open) return;
      open = gl.createQuery();
      gl.beginQuery(ext.TIME_ELAPSED_EXT, open);
    },
    end(now) {
      if (done) return;
      if (ext && open) { gl.endQuery(ext.TIME_ELAPSED_EXT); queries.push(open); open = null; }
      if (ext) {
        while (queries.length && gl.getQueryParameter(queries[0], gl.QUERY_RESULT_AVAILABLE)) {
          const q = queries.shift();
          if (!gl.getParameter(ext.GPU_DISJOINT_EXT)) push(gl.getQueryParameter(q, gl.QUERY_RESULT) / 1e6);
          gl.deleteQuery(q);
        }
      } else {
        if (last) push(now - last);
        last = now;
      }
    },
    // A hidden tab or a pause breaks the rAF chain; the next delta must not count.
    gap() { last = 0; },
    dispose() { for (const q of queries) gl.deleteQuery(q); queries.length = 0; done = true; },
  };
}
