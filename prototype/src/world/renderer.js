import { BufferGeometry, Float32BufferAttribute, Mesh, NeutralToneMapping, OrthographicCamera, Scene, ShaderMaterial, SRGBColorSpace, WebGLRenderer } from "three";
import { EXPOSURE, tok } from "./colors.js";

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

  // The light wash (D5.3 S2): the frame becomes exactly --color-world-wash. At 1 the scene
  // is not drawn at all, only the clear.
  const wash = new ShaderMaterial({
    uniforms: { uWash: { value: 0 }, uColor: { value: tok("wash") } },
    vertexShader: SCREEN_VERT,
    fragmentShader: /* glsl */ `uniform float uWash; uniform vec3 uColor; varying vec2 vU;
      void main(){ gl_FragColor = vec4(uColor, uWash);
      #include <colorspace_fragment>
      }`,
    transparent: true, depthTest: false, depthWrite: false, toneMapped: false,
  });
  const washScene = new Scene().add(new Mesh(fullscreen(), wash));
  washScene.children[0].frustumCulled = false;
  const ortho = new OrthographicCamera(-1, 1, 1, -1, 0, 1);

  let size = { w: 0, h: 0, dpr: 1 }, lite = false;
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
    // scene + passes (shafts) unless the wash covers everything.
    frame(scene, camera, passes = [], washAmount = 0) {
      renderer.info.reset();
      renderer.setRenderTarget(null);
      renderer.clear();
      if (washAmount < 1) {
        renderer.render(scene, camera);
        for (const p of passes) p.render(camera);
      }
      if (washAmount > 0 && washAmount < 1) {
        wash.uniforms.uWash.value = washAmount;
        renderer.render(washScene, ortho);
      }
    },
    dispose() {
      wash.dispose();
      washScene.children[0].geometry.dispose();
      renderer.dispose();
    },
  };
}
