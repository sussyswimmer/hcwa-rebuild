import { BufferGeometry, Float32BufferAttribute, Mesh, NeutralToneMapping, OrthographicCamera, Scene, ShaderMaterial, SRGBColorSpace, Vector4, WebGLRenderer } from "three";
import { EXPOSURE, tok } from "./colors.js";
import { DITHER } from "./glsl/noise.js";

// One full-screen triangle, shared by the wash, the shaft passes and the debug views.
export const fullscreen = () => {
  const g = new BufferGeometry();
  g.setAttribute("position", new Float32BufferAttribute([-1, -1, 0, 3, -1, 0, -1, 3, 0], 3));
  g.setAttribute("uv", new Float32BufferAttribute([0, 0, 2, 0, 0, 2], 2));
  return g;
};
export const SCREEN_VERT = /* glsl */ `varying vec2 vU; void main(){ vU = uv; gl_Position = vec4(position.xy, 0., 1.); }`;

// DPR caps (05-DESIGN D6.7): Tier 1 1.25, or 1.0 when that would pass 1.6 M canvas pixels;
// Tier 2 1.75, at most 3 M pixels (the memory budget), and without MSAA at least 1.5 within that cap
// (supersampled, so the instanced block edges stay clean); the lite gate drops to 1.0.
export function dprFor(w, h, tier, lite, msaa = true) {
  const d = globalThis.devicePixelRatio || 1;
  if (lite) return Math.min(d, 1);
  if (tier >= 2) {
    const cap = Math.sqrt(3e6 / Math.max(w * h, 1));
    return Math.round(Math.min(msaa ? Math.min(d, 1.75) : Math.max(Math.min(d, 1.75), 1.5), cap) * 100) / 100;
  }
  const c = Math.min(d, 1.25);
  return w * h * c * c > 1.6e6 ? Math.min(d, 1) : c;
}

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
  const flareU = { value: new Vector4() }, aspectU = { value: 1 }, holeU = { value: new Vector4() }, light = tok("wash");
  const wash = new ShaderMaterial({
    uniforms: { uWash: { value: 0 }, uColor: { value: light.clone() }, uRim: { value: tok("sun-200") }, uFlare: flareU, uAspect: aspectU, uHole: holeU },
    vertexShader: SCREEN_VERT,
    fragmentShader: /* glsl */ `uniform float uWash, uAspect; uniform vec3 uColor, uRim; uniform vec4 uFlare, uHole; varying vec2 vU;
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
        gl_FragColor = vec4(col, max(uWash, f));
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
  const ctx = renderer.getContext();
  return {
    renderer,
    ortho,
    get size() { return size; },
    // Drawing-buffer memory estimate (D6.7), for the [world] line and check-hero.
    get memoryMB() { const px = Math.round(size.w * size.dpr) * Math.round(size.h * size.dpr); return +((px * (12 + (msaa ? 32 : 0))) / 2 ** 20).toFixed(1); },
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
    // Vector4 (amount, drift x, drift y), the passage's inside of the hole; color: the wash's (default the light).
    frame(scene, camera, passes = [], washAmount = 0, flare = null, hole = null, color = null) {
      renderer.info.reset();
      renderer.setRenderTarget(null);
      const c = color ?? light;
      if (!wash.uniforms.uColor.value.equals(c)) { wash.uniforms.uColor.value.copy(c); renderer.setClearColor(c, 1); }
      renderer.clear();
      if (washAmount < 1) {
        renderer.render(scene, camera);
        for (const p of passes) p.render(camera);
      }
      const flaring = flare && flare.w > 0, holing = hole && hole.x > 0 && washAmount > 0;
      if ((washAmount < 1 && (washAmount > 0 || flaring)) || holing) {
        wash.uniforms.uWash.value = washAmount;
        if (flaring) flareU.value.copy(flare); else flareU.value.w = 0;
        if (holing) holeU.value.copy(hole); else holeU.value.x = 0;
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
