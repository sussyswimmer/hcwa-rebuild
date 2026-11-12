import { BufferGeometry, Float32BufferAttribute, LinearFilter, Mesh, NeutralToneMapping, OrthographicCamera, Scene, ShaderMaterial, SRGBColorSpace, Vector4, WebGLRenderer, WebGLRenderTarget } from "three";
import { EXPOSURE, tok } from "./colors.js";
import { DITHER } from "./glsl/noise.js";
import { MAX_PIXELS, MSAA_PIXELS, buffersMB } from "./memory.js";

// One full-screen triangle, shared by the wash, the shaft passes and the debug views.
export const fullscreen = () => {
  const g = new BufferGeometry();
  g.setAttribute("position", new Float32BufferAttribute([-1, -1, 0, 3, -1, 0, -1, 3, 0], 3));
  g.setAttribute("uv", new Float32BufferAttribute([0, 0, 2, 0, 0, 2], 2));
  return g;
};
export const SCREEN_VERT = /* glsl */ `varying vec2 vU; void main(){ vU = uv; gl_Position = vec4(position.xy, 0., 1.); }`;

// DPR caps (05-DESIGN D6.7): Tier 1 1.25, or 1.0 when that would pass 1.6 M canvas pixels;
// Tier 2 1.75, and without MSAA at least 1.5 (supersampled, so the instanced block edges stay clean); the lite gate
// drops to 1.0. Every tier stays inside the memory budget (memory.js, M7: textures count too): at most 2.9 M pixels
// without MSAA (so a very large window renders a little under DPR 1), 0.85 M with it (never under DPR 1 there: a
// window grown past the MSAA size after load stays sharp, and the memory line says what it costs).
export function dprFor(w, h, tier, lite, msaa = true) {
  const d = globalThis.devicePixelRatio || 1, px = Math.max(w * h, 1), fit = Math.sqrt(MAX_PIXELS / px);
  const r = (x) => Math.round(x * 100) / 100;
  if (lite) return r(Math.min(d, 1, fit));
  if (tier >= 2) {
    if (msaa) return r(Math.min(d, 1.75, Math.max(Math.sqrt(MSAA_PIXELS / px), Math.min(d, 1))));
    return r(Math.min(Math.max(Math.min(d, 1.75), 1.5), fit));
  }
  const c = Math.min(d, 1.25);
  return r(Math.min(px * c * c > 1.6e6 ? Math.min(d, 1) : c, fit));
}

// The shafts' raymarch target (materials/shafts.js), one per renderer whatever the number of sets: a frame draws one
// set's passes, never two (M7: three targets were 8.3 MB at 1440 on Tier 2). Counted: released by the last set.
const targets = new WeakMap();
export function shaftTarget(renderer) {
  let t = targets.get(renderer);
  if (!t) targets.set(renderer, (t = { rt: new WebGLRenderTarget(1, 1, { depthBuffer: false, minFilter: LinearFilter, magFilter: LinearFilter }), users: 0 }));
  t.users++;
  return t.rt;
}
export function releaseShaftTarget(renderer) {
  const t = targets.get(renderer);
  if (!t || --t.users > 0) return;
  t.rt.dispose();
  targets.delete(renderer);
}
const shaftTargetMB = (renderer) => { const rt = targets.get(renderer)?.rt; return rt && rt.width > 1 ? (rt.width * rt.height * 4) / 2 ** 20 : 0; };

export function createRenderer(canvas, { context, tier, poster = false }) {
  const msaa = Boolean(context?.getContextAttributes?.().antialias);
  const renderer = new WebGLRenderer({ canvas, context, antialias: msaa, alpha: false, powerPreference: "high-performance", preserveDrawingBuffer: poster });
  renderer.toneMapping = NeutralToneMapping;
  renderer.toneMappingExposure = EXPOSURE;
  renderer.outputColorSpace = SRGBColorSpace;
  renderer.autoClear = false;
  renderer.info.autoReset = false;
  renderer.setClearColor(tok("wash"), 1);
  // Shader logs are a synchronous GPU round trip per program: read only when debugging (?debug).
  renderer.debug.checkShaderErrors = poster || new URLSearchParams(globalThis.location?.search ?? "").has("debug");

  // The light wash (D5.3 S2): the frame becomes exactly --color-world-wash. At 1 the scene
  // is not drawn at all. The flare (S3's flood) is the same light, radial: a bloom of
  // wash around a point of the frame (uv x, y, radius in frame heights, amount). Mixing toward the wash,
  // never adding, so the brightest a frame can be is the wash itself: sun-100, never white (D5.3).
  // In the passage the wash is the inside of the hole (uHole: amount, mote drift x, y): the quatrefoil's
  // lobes reach past the frame and its block shows only as a soft sun-200 vignette between them (18%
  // feather), and dust drifts through the light with the scroll, so the plateau is a place, not a fill.
  // The corridor goes out through a wash of the plaster it hands over to (lime-100), not the light: color.
  // And it goes from the top of the frame down (uSweep: on, the front's feather, the foot of what is still seen,
  // in uv from the bottom): the ceiling and the walls go first, the floor last, so the runner is still fading
  // just above the sheet that rises over it (critique r1, fix round 1).
  // The hem (uHem: on, the next sheet's top in uv from the bottom, the hem's height in uv; critique r2, D0.5): the
  // picture fades to the wash's color over the hem above that top, whatever the sweep is doing, on a smoothstep (M6:
  // no corner anywhere in the ramp; its steepest 40 px band still moves the frame's mean by about 11 levels at most over
  // the corridor's darkest floor), so the sheet never cuts the corridor at full strength. Hem and
  // sweep multiply (their masks intersect). The canvas fades; the page's text never does. The hem fades to its own color
  // (uHemC, the plaster), which is the wash's on the walk; while no corridor is built yet (a jump straight to the sheet)
  // the frame is the light and the hem still takes it to the plaster, so the sheet's solid top never cuts a frame of
  // light (final m3 recheck). Blended over the frame: C·A + dst·(1 − A), A = 1 − v(1 − a), C = (hemC(1 − v) + col·a·v) / A.
  const flareU = { value: new Vector4() }, aspectU = { value: 1 }, holeU = { value: new Vector4() }, sweepU = { value: new Vector4() }, hemU = { value: new Vector4() }, light = tok("wash"), hemC = { value: light.clone() };
  const wash = new ShaderMaterial({
    uniforms: { uWash: { value: 0 }, uColor: { value: light.clone() }, uRim: { value: tok("sun-200") }, uFlare: flareU, uAspect: aspectU, uHole: holeU, uSweep: sweepU, uHem: hemU, uHemC: hemC },
    vertexShader: SCREEN_VERT,
    fragmentShader: /* glsl */ `uniform float uWash, uAspect; uniform vec3 uColor, uRim, uHemC; uniform vec4 uFlare, uHole, uSweep, uHem; varying vec2 vU;
      ${DITHER}
      float h12(vec2 p){ p = fract(p * vec2(123.34, 456.21)); p += dot(p, p + 45.32); return fract(p.x * p.y); }
      float lobes(vec2 p, float r){ return min(min(length(p - vec2(r, 0.)), length(p + vec2(r, 0.))), min(length(p - vec2(0., r)), length(p + vec2(0., r)))) - r; }
      void main(){
        vec2 d = (vU - uFlare.xy) * vec2(uAspect, 1.);
        float f = uFlare.w > 0. ? uFlare.w * exp(-dot(d, d) / (uFlare.z * uFlare.z)) : 0.;
        vec3 col = uColor;
        if (uHole.x > 0.) {
          vec2 p = (vU - 0.5) * vec2(uAspect, 1.);
          float inside = 1. - smoothstep(-0.09, 0.09, lobes(p, 0.3 * clamp(uAspect, 0.55, 1.2)));
          vec3 light = mix(uRim, uColor, inside);
          // Dust: one speck in some cells of a grid that slides with the scroll, the opposite tone of the
          // light it is in (a fleck of gold on the plateau, a glint in the vignette).
          vec2 g = p * 11. + uHole.yz, c = floor(g), q = fract(g) - 0.5;
          float k = h12(c), m = step(0.58, k);
          vec2 at = vec2(h12(c + 7.1), h12(c + 3.3)) - 0.5;
          float speck = m * smoothstep(0.075, 0.02, length(q - at * 0.7)) * (0.35 + 0.4 * h12(c + 1.9));
          light = mix(light, mix(uRim, uColor, 1. - inside), speck);
          col = mix(uColor, light, uHole.x);
        }
        float a = uWash;
        if (uSweep.x > 0.) {
          // r: 0 at the frame's top, 1 at the foot of what is still seen; the front runs down it, feathered.
          float r = clamp((1. - vU.y) / max(1. - uSweep.z, .05), 0., 1.), F = uSweep.y, g = uWash * (1. + F) - F * .5;
          a = 1. - smoothstep(g - F * .5, g + F * .5, r);
        }
        if (uHem.x > 0.) {
          float h = clamp((vU.y - uHem.y) / uHem.z, 0., 1.);
          // M6: a smoothstep, not three straight pieces: a piecewise ramp's corners read as faint lines (Mach bands).
          float v = h * h * (3. - 2. * h);
          float A = 1. - (1. - a) * v;
          col = (uHemC * (1. - v) + col * a * v) / max(A, 1e-4);
          a = A;
        }
        gl_FragColor = vec4(col, max(a, f));
      #include <colorspace_fragment>
        DITHER_OUT;
        gl_FragColor.a += (ign(gl_FragCoord.yx) - .5) / 255.;
      }`,
    transparent: true, depthTest: false, depthWrite: false, toneMapped: false,
  });
  const washScene = new Scene().add(new Mesh(fullscreen(), wash));
  washScene.children[0].frustumCulled = false;
  const ortho = new OrthographicCamera(-1, 1, 1, -1, 0, 1);

  let size = { w: 0, h: 0, dpr: 1 }, lite = false, fence = null;
  // Textures the world has uploaded, MB by name (live.js: baked, gach, walls, posters), counted in memoryMB.
  const textures = {};
  const clearC = light.clone();
  const ctx = renderer.getContext();
  return {
    renderer,
    ortho,
    get size() { return size; },
    // GPU memory (D6.7, memory.js), for the [world] line and check-hero: the drawing buffers, the shafts' target and
    // every texture the world has said it holds (track). memory: the same, by part.
    get buffersMB() { return +buffersMB(Math.round(size.w * size.dpr), Math.round(size.h * size.dpr), msaa).toFixed(1); },
    track(name, mbs) { if (mbs > 0) textures[name] = mbs; else delete textures[name]; },
    get memory() {
      const parts = { buffers: this.buffersMB, shafts: +shaftTargetMB(renderer).toFixed(1) };
      for (const [k, v] of Object.entries(textures)) parts[k] = +v.toFixed(1);
      const total = buffersMB(Math.round(size.w * size.dpr), Math.round(size.h * size.dpr), msaa) + shaftTargetMB(renderer) + Object.values(textures).reduce((a, b) => a + b, 0);
      return { ...parts, total: +total.toFixed(1) };
    },
    get memoryMB() { return this.memory.total; },
    msaa,
    get tier() { return tier; },
    setTier(t, { lite: l = false } = {}) { tier = t; lite = l; },
    resize(w, h) {
      const dpr = poster ? size.dpr : dprFor(w, h, tier, lite, msaa);
      if (w === size.w && h === size.h && dpr === size.dpr) return false;
      size = { w, h, dpr };
      renderer.setPixelRatio(dpr);
      renderer.setSize(w, h, false);
      return true;
    },
    // Posters render at an exact pixel width, whatever the device.
    setPosterSize(w, h, pixelWidth) {
      size = { w, h, dpr: pixelWidth / w };
      renderer.setPixelRatio(size.dpr);
      renderer.setSize(w, h, false);
    },
    // scene + passes (shafts) unless the wash covers everything. flare: a Vector4 (see the wash); hole: a
    // Vector4 (amount, drift x, drift y), the passage's inside of the hole; color: the wash's (default the light);
    // sweep: a Vector4 (on, feather, foot) for a wash from the top of the frame down; hem: a Vector4 (on, the next
    // sheet's top, the hem's height) for the fade above that sheet (see the wash); hemColor: what the hem fades to
    // (default the wash's color).
    frame(scene, camera, passes = [], washAmount = 0, flare = null, hole = null, color = null, sweep = null, hem = null, hemColor = null) {
      renderer.info.reset();
      renderer.setRenderTarget(null);
      const c = color ?? light;
      if (!wash.uniforms.uColor.value.equals(c)) wash.uniforms.uColor.value.copy(c);
      // What shows where nothing is drawn (past an open doorway) stays the light while the scene is drawn, so the hem
      // and the sweep never change it at once; the wash pass fades it with everything else. A full wash is its color.
      const clear = washAmount >= 1 ? c : light;
      if (!clearC.equals(clear)) { clearC.copy(clear); renderer.setClearColor(clear, 1); }
      renderer.clear();
      if (washAmount < 1) {
        renderer.render(scene, camera);
        for (const p of passes) p.render(camera);
      }
      const flaring = flare && flare.w > 0, holing = hole && hole.x > 0 && washAmount > 0, hemming = hem && hem.x > 0;
      const hc = hemColor ?? c;
      if (hemming && !hemC.value.equals(hc)) hemC.value.copy(hc);
      if ((washAmount < 1 && (washAmount > 0 || flaring || hemming)) || holing || (hemming && !hc.equals(c))) {
        wash.uniforms.uWash.value = washAmount;
        if (flaring) flareU.value.copy(flare); else flareU.value.w = 0;
        if (holing) holeU.value.copy(hole); else holeU.value.x = 0;
        if (sweep && sweep.x > 0) sweepU.value.copy(sweep); else sweepU.value.x = 0;
        if (hemming) hemU.value.copy(hem); else hemU.value.x = 0;
        aspectU.value = size.w / Math.max(size.h, 1);
        renderer.render(washScene, ortho);
      }
      if (!fence && !poster) fence = ctx.fenceSync(ctx.SYNC_GPU_COMMANDS_COMPLETE, 0);
    },
    // The wash's program, compiled before the flight can reach it (D6.7: compileAsync before first use).
    compile: () => renderer.compileAsync(washScene, ortho),
    // Whether the GPU is still on the last frame (a fence after it has not signalled). Idle motion waits for
    // it, so a slow GPU drops idle frames instead of queueing them behind each other (fixing-motion-performance).
    busy() {
      if (!fence) return false;
      if (ctx.isContextLost()) { fence = null; return false; }
      // UNSIGNALED only: a fence from before a context restore reads null and is dropped.
      if (ctx.getSyncParameter(fence, ctx.SYNC_STATUS) === ctx.UNSIGNALED) return true;
      ctx.deleteSync(fence);
      fence = null;
      return false;
    },
    dispose() {
      if (fence && !ctx.isContextLost()) ctx.deleteSync(fence);
      fence = null;
      wash.dispose();
      washScene.children[0].geometry.dispose();
      renderer.dispose();
    },
  };
}
