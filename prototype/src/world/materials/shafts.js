import { AddEquation, CustomBlending, Matrix4, Mesh, OneFactor, OneMinusDstColorFactor, Scene, ShaderMaterial, Vector2, Vector3 } from "three";
import { APERTURE } from "../glsl/aperture.js";
import { BLOCKS } from "../glsl/blocks.js";
import { DISPLAY, LEAF, NOISE } from "../glsl/noise.js";
import { CAPS } from "./caps.js";
import { fullscreen, releaseShaftTarget, SCREEN_VERT, shaftTarget } from "../renderer.js";
import { shaftScale } from "../memory.js";
import { EXPOSURE } from "../colors.js";

// Light shafts (05-DESIGN D6.3): a full-screen raymarch into a reduced target (¼ res and
// 16 steps on Tier 1, ½ and 24 on Tier 2). The view ray is clipped to the set's box
// analytically, so no depth buffer is needed; density comes from the baked lime-wash noise
// (shifted by the scroll wind, which is the shimmer). Then a 9-tap tent upsample, screen-blended
// (1 − (1 − dst)(1 − src)): light over light never clips one channel ahead of the others.
// mask: { glsl, call } for sets lit through glsl/apertures.js (the stills): the chunk that defines the
// light's mask and the expression that reads it at p; the room uses sunMask() and the canopy.
// The target is the renderer's one shared target (renderer.js shaftTarget, M7 memory): every set sizes it the same.
export function createShafts(gl, U, { box, tier, ortho, steps = null, mask = null }) {
  const rt = shaftTarget(gl);
  const local = {
    uInvP: { value: new Matrix4() },
    uInvV: { value: new Matrix4() },
    uCam: { value: new Vector3() },
    uMin: { value: new Vector3(...box.min) },
    uMax: { value: new Vector3(...box.max) },
    uDensity: { value: 1.6 }, // screen blending gives back less than adding did on the light wall
    uExposure: { value: EXPOSURE },
    uCapScale: { value: 4 },
  };
  const march = new ShaderMaterial({
    defines: { STEPS: steps ?? (tier >= 2 ? 24 : 16) },
    uniforms: { ...U, ...local },
    vertexShader: SCREEN_VERT,
    fragmentShader: /* glsl */ `
      varying vec2 vU;
      uniform mat4 uInvP, uInvV; uniform vec3 uCam, uMin, uMax, uSunCol, uWind;
      uniform float uSunI, uDensity, uExposure, uCapScale, uTime;
      uniform sampler2D uLime;
      ${BLOCKS}
      ${NOISE}
      ${mask ? mask.glsl + "\n#define SHAFT_L uLt[0]" : APERTURE + LEAF + "\n#define SHAFT_L uL"}
      ${DISPLAY}
      ${CAPS}
      void main(){
        vec4 vp = uInvP * vec4(vU * 2. - 1., 1., 1.); vp /= vp.w;
        vec3 rd = normalize((uInvV * vec4(vp.xyz, 0.)).xyz), ro = uCam;
        vec3 tf = max((uMin - ro) / rd, (uMax - ro) / rd);
        float tExit = min(min(min(tf.x, tf.y), tf.z), 14.);
        float dt = tExit / float(STEPS), j = h21(gl_FragCoord.xy) * dt, acc = 0.;
        for (int i = 0; i < STEPS; i++) {
          vec3 p = ro + rd * (j + dt * float(i));
          float m = ${mask ? mask.call : "sunMask(p)"};
          if (m > 0.) acc += m ${mask ? "" : "* leaf(p, uTime)"} * (0.5 + 0.5 * texture2D(uLime, (vec2(p.x + p.y, p.z) + uWind.xz) * 0.3).r) * dt;
        }
        float g = .45, mu = dot(rd, SHAFT_L), hg = (1. - g * g) / pow(1. + g * g - 2. * g * mu, 1.5) / 12.566;
        vec3 col = uSunCol * uSunI * uDensity * acc * (0.02 + 0.3 * hg) * capAt(gl_FragCoord.xy * uCapScale);
        gl_FragColor = vec4(toSRGB(neutralTM(col, uExposure)), 1.);
      }`,
    depthTest: false, depthWrite: false,
  });
  const texel = new Vector2();
  const composite = new ShaderMaterial({
    uniforms: { uTex: { value: rt.texture }, uTexel: { value: texel } },
    vertexShader: SCREEN_VERT,
    fragmentShader: /* glsl */ `
      uniform sampler2D uTex; uniform vec2 uTexel; varying vec2 vU;
      vec3 s(vec2 o){ return texture2D(uTex, vU + o * uTexel).rgb; }
      void main(){
        vec3 c = s(vec2(0)) * 4. + (s(vec2(1, 0)) + s(vec2(-1, 0)) + s(vec2(0, 1)) + s(vec2(0, -1))) * 2.
               + s(vec2(1, 1)) + s(vec2(-1, 1)) + s(vec2(1, -1)) + s(vec2(-1, -1));
        gl_FragColor = vec4(c / 16., 1.);
      }`,
    blending: CustomBlending, blendEquation: AddEquation, blendSrc: OneMinusDstColorFactor, blendDst: OneFactor,
    transparent: true, depthTest: false, depthWrite: false, toneMapped: false,
  });
  const tri = fullscreen();
  const marchScene = new Scene().add(new Mesh(tri, march));
  const compScene = new Scene().add(new Mesh(tri, composite));
  for (const s of [marchScene, compScene]) s.children[0].frustumCulled = false;

  let scale = shaftScale(tier), enabled = true;
  const pass = {
    scenes: [marchScene, compScene],
    get enabled() { return enabled; },
    set enabled(v) { enabled = v; },
    setTier(t) {
      scale = shaftScale(t);
      march.defines.STEPS = steps ?? (t >= 2 ? 24 : 16);
      march.needsUpdate = true;
    },
    setSize(w, h) {
      rt.setSize(Math.max(1, Math.ceil(w / scale)), Math.max(1, Math.ceil(h / scale)));
      texel.set(1 / rt.width, 1 / rt.height);
      local.uCapScale.value = w / rt.width;
    },
    get size() { return [rt.width, rt.height]; },
    render(camera) {
      if (!enabled) return;
      local.uInvP.value.copy(camera.projectionMatrixInverse);
      local.uInvV.value.copy(camera.matrixWorld);
      local.uCam.value.copy(camera.position);
      const prev = gl.getRenderTarget();
      gl.setRenderTarget(rt); // every pixel is written, no clear needed
      gl.render(marchScene, ortho);
      gl.setRenderTarget(prev);
      gl.render(compScene, ortho);
    },
    uniforms: local,
    dispose() { releaseShaftTarget(gl); march.dispose(); composite.dispose(); tri.dispose(); },
  };
  return pass;
}
