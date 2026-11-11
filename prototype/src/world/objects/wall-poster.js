import { LinearFilter, LinearMipmapLinearFilter, Mesh, PlaneGeometry, SRGBColorSpace, ShaderMaterial, Texture } from "three";
import { BLOCKS } from "../glsl/blocks.js";
import { NOISE } from "../glsl/noise.js";
import { aperturesGLSL } from "../glsl/apertures.js";
import { wallUrl } from "../walls.js";
import { ATLAS, WALL_POSTERS, cellUv } from "../wall-posters-data.js";

// A wall poster (M6; wall-posters-data.js has the set, the rules and where they hang). One print from the atlas on a
// sheet 5 mm off the wall: its corner curls up to 18 mm off it in the vertex shader, it hangs 1.2° or less off true,
// two strips of tape hold its top (painted in the atlas, translucent) and its soft shadow lies on the wall around it.
// It is lit exactly as the wall behind it (the set's own lights and apertures: the screen's sun patches, the transoms'
// light, the shade), so it never glows; the curl turns a little away from the light. Until the atlas has loaded the
// sheet is not drawn (uPosterK 0); live.js and the still renderer load it after the first frame (never on the LCP path).

// One atlas texture and one fade for every poster of every set (the room's, the corridor's, S3's).
const shared = { uPosterTex: { value: null }, uPosterK: { value: 0 } };
export const posterUniforms = () => shared;

// The atlas for a tier / width (wall-posters-data.js atlasSizeFor): fetched, decoded off the main thread, uploaded once.
let loaded = null;
export async function loadPosterAtlas({ size = 1024, signal } = {}) {
  if (loaded?.size === size) return loaded;
  const url = wallUrl(`wall-posters`, size);
  const r = await fetch(url, { signal, priority: "low" });
  if (!r.ok) throw new Error(`wall posters: ${r.status} ${url}`);
  const blob = await r.blob();
  const bmp = await createImageBitmap(blob, { imageOrientation: "flipY", premultiplyAlpha: "none", colorSpaceConversion: "none" });
  const t = new Texture(bmp);
  t.flipY = false; // flipped at decode
  t.colorSpace = SRGBColorSpace;
  t.minFilter = LinearMipmapLinearFilter;
  t.magFilter = LinearFilter;
  t.generateMipmaps = true;
  t.anisotropy = 4;
  t.needsUpdate = true;
  shared.uPosterTex.value?.dispose?.();
  shared.uPosterTex.value = t;
  loaded = { size, texture: t, kb: Math.round(blob.size / 1024) };
  return loaded;
}
export function disposePosters() { shared.uPosterTex.value?.dispose?.(); shared.uPosterTex.value = null; shared.uPosterK.value = 0; loaded = null; }

const VERT = /* glsl */ `
uniform vec4 uCurl; // corner (uv), lift in metres, reach (fraction of the shorter side)
uniform vec2 uSize;
varying vec3 vP; varying vec3 vN; varying vec2 vUv; varying float vCurl;
void main(){
  vec2 d = (uv - uCurl.xy) * uSize;
  float k = 1. - smoothstep(0., uCurl.w * min(uSize.x, uSize.y), length(d));
  vCurl = k * k;
  vec3 p = position + vec3(0., 0., uCurl.z * vCurl);
  vec4 w = modelMatrix * vec4(p, 1.);
  vP = w.xyz; vN = normalize(mat3(modelMatrix) * normal); vUv = uv;
  gl_Position = projectionMatrix * viewMatrix * w;
}`;

// U: the set's uniforms (lights, apertures); own: uSunTint, uSpillTint, uShadeK (as the set's paint).
export function posterMaterial(U, lights, own, index) {
  const c = cellUv(index, 2048); // the cells are at the same fractions in every atlas size
  return new ShaderMaterial({
    uniforms: { ...U, ...shared, ...own, uCell: { value: c }, uCurl: { value: [1, 0, 0, 0.3] }, uSize: { value: [1, 1] } },
    vertexShader: VERT,
    fragmentShader: /* glsl */ `
      varying vec3 vP; varying vec3 vN; varying vec2 vUv; varying float vCurl;
      uniform sampler2D uPosterTex;
      uniform float uPosterK, uShadeK, uRef[2], uI[2];
      uniform vec4 uCell;
      uniform vec3 uSunTint, uSpillTint;
      ${BLOCKS}
      ${NOISE}
      ${aperturesGLSL(lights)}
      void main(){
        vec4 t = texture2D(uPosterTex, mix(uCell.xy, uCell.zw, vUv));
        float a = t.a * uPosterK;
        if (a < .004) discard;
        vec3 albedo = t.rgb, N = normalize(vN);
        float sky = 0.82 + 0.18 * N.y;
        vec3 shade = albedo * uShadeK * sky;
        float n0 = max(dot(N, uLt[0]), 0.), n1 = max(dot(N, uLt[1]), 0.);
        float f0 = n0 > 0. && uI[0] > 0. ? n0 / uRef[0] * lightMask0(vP) * uI[0] : 0.;
        ${lights.length > 1 ? "float f1 = n1 > 0. && uI[1] > 0. ? n1 / uRef[1] * lightMask1(vP) * uI[1] : 0.;" : "float f1 = 0.;"}
        vec3 col = mix(shade, albedo * uSunTint, min(f0, 1.));
        col = mix(col, albedo * uSpillTint, min(f1, 1.) * .85);
        col *= 1. - .1 * vCurl;
        gl_FragColor = vec4(col, a);
        #include <colorspace_fragment>
      }`,
    transparent: true, depthWrite: false, toneMapped: false,
  });
}

// A hung poster: id (wall-posters-data.js), w (the print's width, m), at [x, y, z] (the print's centre), the wall's
// normal axis (rotation about y: −π/2 faces −x), and a seed for its tilt and which corner curls.
const r01 = (s) => { const x = Math.sin(s * 127.1 + 311.7) * 43758.5453; return x - Math.floor(x); };
export function wallPoster(U, lights, own, { id, w, at, rotY = -Math.PI / 2, seed = 1 }) {
  const p = WALL_POSTERS.find((x) => x.id === id);
  if (!p || p.atlas == null) throw new Error(`wall poster ${id}: not in the atlas`);
  const [mx, my] = ATLAS.margin, cw = w / (1 - 2 * mx), [pw, ph] = ATLAS.sizes[2048].cell, ch = (cw * ph) / pw;
  const mat = posterMaterial(U, lights, own, p.atlas);
  // A bottom corner curls (the tape holds the top), 10-18 mm off the wall.
  mat.uniforms.uCurl.value = [r01(seed) < 0.5 ? mx : 1 - mx, my, 0.01 + 0.008 * r01(seed + 1), 0.32];
  mat.uniforms.uSize.value = [cw, ch];
  const m = new Mesh(new PlaneGeometry(cw, ch, 8, 10), mat);
  m.position.set(...at);
  m.rotation.set(0, rotY, ((r01(seed + 2) - 0.5) * 2.4 * Math.PI) / 180, "YXZ");
  m.renderOrder = 2;
  m.name = `poster:${id}`;
  return m;
}
