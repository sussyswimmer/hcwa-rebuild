import { Mesh, PlaneGeometry, ShaderMaterial } from "three";
import { NOISE } from "../glsl/noise.js";
import { mixc, tok } from "../colors.js";

// The street beyond the screen (05-DESIGN D6.3): morning light and a soft tamarind canopy,
// overexposed and not tone-mapped, so the holes blaze --color-world-sky. Tier 1 evaluates
// street() inside the screen shader; Tier 2 sees it on a plane through real holes.
export const STREET = /* glsl */ `
uniform vec3 uSkyLow, uSkyTop, uCanopy, uSunGlow;
uniform float uStreetX, uTime;
vec3 street(vec3 ro, vec3 rd){
  float t = (uStreetX - ro.x) / max(abs(rd.x), 1e-3);
  vec3 S = ro + rd * t;
  float h = clamp(S.y / 8., 0., 1.);
  vec3 c = mix(uSkyLow, uSkyTop, smoothstep(0.05, 1., h));
  // The canopy sways with the same clock as the dapple it throws into the room (glsl LEAF).
  float tree = smoothstep(.56, .64, fbm(S.zy * vec2(.42, .5) + 3.1 + vec2(uTime * .22, sin(uTime * .45) * .3)) + (h - .5) * .8);
  c = mix(c, uCanopy, tree * .55);
  return mix(c, uSunGlow, pow(max(dot(rd, uL), 0.), 18.) * .5);
}
`;

export const streetUniforms = () => ({
  uSkyLow: { value: tok("sky") },
  uSkyTop: { value: mixc(tok("sky"), tok("shade-100"), 0.35) },
  uCanopy: { value: mixc(tok("sky"), tok("unit-green-bamboo"), 0.2) },
  uSunGlow: { value: tok("on-dusk") },
  uStreetX: { value: 9 },
});

export function streetPlane(U, { x, z, size = [40, 20] }) {
  const mat = new ShaderMaterial({
    uniforms: U,
    vertexShader: /* glsl */ `varying vec3 vP; void main(){ vec4 w = modelMatrix * vec4(position, 1.); vP = w.xyz; gl_Position = projectionMatrix * viewMatrix * w; }`,
    fragmentShader: /* glsl */ `
      uniform vec3 uL;
      varying vec3 vP;
      ${NOISE}
      ${STREET}
      void main(){
        gl_FragColor = vec4(street(cameraPosition, normalize(vP - cameraPosition)), 1.);
        #include <colorspace_fragment>
      }`,
    toneMapped: false,
  });
  const m = new Mesh(new PlaneGeometry(...size), mat);
  m.rotation.y = -Math.PI / 2;
  m.position.set(x, size[1] / 2 - 2, z);
  return m;
}
