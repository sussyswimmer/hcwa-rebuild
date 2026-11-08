import { Mesh, PlaneGeometry, ShaderMaterial } from "three";
import { BLOCK, BLOCKS } from "../glsl/blocks.js";
import { DITHER, NOISE } from "../glsl/noise.js";
import { mixc, tok, vec } from "../colors.js";

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
      ${DITHER}
      ${STREET}
      void main(){
        gl_FragColor = vec4(street(cameraPosition, normalize(vP - cameraPosition)), 1.);
        #include <colorspace_fragment>
        DITHER_OUT;
      }`,
    toneMapped: false,
  });
  const m = new Mesh(new PlaneGeometry(...size), mat);
  m.rotation.y = -Math.PI / 2;
  m.position.set(x, size[1] / 2 - 2, z);
  return m;
}

// What the open shutters give onto at 17:00 (critique r2: the reveal must find something outside): the
// late sky (sun-200 low to sun-100 high), a tamarind canopy across its top as a low-contrast silhouette (its
// leaves sun-200 toward sun-400 at about 30%, the sky through its gaps), and a low breeze-block parapet with a
// row of Hoa thị holes lit from behind, as the footer's dusk has. A plane in x (facing −x) at the window's
// distance; world y: the parapet's top at `parapet`, the canopy from `canopy` up. Nothing on it passes
// sun-100 (the set's MIN cap holds anyway). uSway: the canopy's angle (rad) about its trunk, Tier 2 only.
export function viewMaterial({ parapet = 1.8, canopy = 2.6, trunk = [-1.2, -1] } = {}) {
  const sky = mixc(tok("sun-200"), tok("sun-100"), 0.15), high = tok("sun-100");
  const leaf = mixc(tok("sun-200"), tok("sun-400"), 0.45), wall = mixc(mixc(tok("lime-300"), tok("sun-200"), 0.35), tok("gold-700"), 0.1);
  return new ShaderMaterial({
    uniforms: {
      uLow: { value: sky }, uHigh: { value: high }, uLeaf: { value: leaf }, uWall: { value: wall }, uHole: { value: high.clone() },
      uY: { value: [parapet, canopy] }, uTrunk: { value: trunk }, uSway: { value: 0 },
    },
    vertexShader: /* glsl */ `varying vec3 vP; void main(){ vec4 w = modelMatrix * vec4(position, 1.); vP = w.xyz; gl_Position = projectionMatrix * viewMatrix * w; }`,
    fragmentShader: /* glsl */ `
      varying vec3 vP;
      uniform vec3 uLow, uHigh, uLeaf, uWall, uHole; uniform float uY[2], uTrunk[2], uSway;
      ${NOISE}
      ${BLOCKS}
      ${DITHER}
      #define B ${BLOCK.toFixed(4)}
      void main(){
        vec2 p = vP.zy;
        vec3 c = mix(uLow, uHigh, smoothstep(uY[0], uY[1] + 1.5, p.y));
        // The canopy: leaf masses whose lower edge is lace, turned about the trunk by the sway.
        vec2 q = p - vec2(uTrunk[0], uTrunk[1]);
        float s = sin(uSway), k = cos(uSway);
        q = mat2(k, s, -s, k) * q + vec2(uTrunk[0], uTrunk[1]);
        float mass = fbm(q * vec2(.8, 1.) + 4.3) + 0.7 * smoothstep(uY[1] - .2, uY[1] + 1.2, q.y) - .7;
        float leaves = smoothstep(-.02, .03, mass) * (0.7 + 0.3 * smoothstep(.45, .6, vn(q * 7.3 + 1.7)));
        c = mix(c, uLeaf, leaves * smoothstep(uY[1] - .5, uY[1] + .1, q.y));
        // The parapet: plastered top, then a course of Hoa thị blocks lit from the street behind.
        if (p.y < uY[0]) {
          vec2 b = vec2(p.x, p.y - (uY[0] - 0.12 - B));
          float course = step(0., b.y) * step(b.y, B);
          vec2 cc = mod(b, B) - 0.5 * B;
          float hole = course * (1. - smoothstep(-.004, .004, sdHoaThi(cc)));
          float joint = course * (1. - smoothstep(.003, .007, .5 * B - max(abs(cc.x), abs(cc.y))));
          // In its own shade, a touch deeper toward the ground; the coping catches the light.
          vec3 w = uWall * (0.97 + 0.06 * vn(p * 9.)) * (1. - 0.06 * joint) * mix(.9, 1., smoothstep(uY[0] - 1.6, uY[0], p.y)) * mix(1., 1.06, step(uY[0] - .12, p.y));
          c = mix(w, uHole, hole);
        }
        gl_FragColor = vec4(c, 1.);
        #include <colorspace_fragment>
        DITHER_OUT;
      }
      #undef B`,
    toneMapped: false,
  });
}
