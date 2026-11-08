import { Matrix4, Mesh, PlaneGeometry, ShaderMaterial, Vector3 } from "three";
import { BLOCK, BLOCKS, DEPTH } from "../glsl/blocks.js";
import { NOISE } from "../glsl/noise.js";
import { aperturesGLSL } from "../glsl/apertures.js";

// A panel of breeze blocks for the stills (05-DESIGN D6.3, the Tier 1 screen of screen.js generalized):
// one plane, the block pattern as an SDF, the 6 cm depth faked by a second test at the back face.
//   backlit:  seen from the shaded side (a screen from inside, a transom from the corridor): the face is
//             a silhouette, the holes show what is behind (the street, a lit room), the hole walls catch
//             the light that comes through.
//   frontlit: seen from the sun's side (the screen from the street): the face takes the sun through the
//             shell's light 0 (canopy dapple included), the holes show the room in shade.
// Patterns are the light's own (glsl/apertures.js): the same holes cast the patches on the floor.
const PATTERN = {
  hoaThi: "return sdHoaThi(c);",
  hoaThiMissing: "return all(equal(id, uOne)) ? min(sdHoaThi(c), sdBox2(c, vec2(0.5 * B - 0.004))) : sdHoaThi(c);",
  dotTre: "return sdDotTre(c);",
  vomCau: "return sdVomCau(c);",
};

// light: which light catches the hole walls of a backlit panel (default: the last one, a room behind a
// transom; the corridor's screen takes the sun, light 0).
export function latticePanel(U, lights, { corner, u, v, size, pattern = "hoaThi", mode = "backlit", depth = DEPTH, own = {}, light = lights.length - 1 }) {
  const U3 = new Vector3(...u).normalize(), V3 = new Vector3(...v).normalize(), N3 = new Vector3().crossVectors(U3, V3);
  const uniforms = {
    ...U,
    uO: { value: new Vector3(...corner) }, uU: { value: U3 }, uV: { value: V3 }, uNf: { value: N3 },
    uSize: { value: [size[0], size[1]] }, uDepth: { value: depth },
    uOne: { value: [-1, -1] },
    uFace: { value: new Vector3() }, uFaceSun: { value: new Vector3() }, uWallC: { value: new Vector3() }, uRimC: { value: new Vector3() },
    uBehindLow: { value: new Vector3() }, uBehindHigh: { value: new Vector3() }, uBehindY: { value: [0, 3] },
    uHalo: { value: 0.35 },
    // The tamarind canopy past the holes (sky.js STREET), at uBehindDist behind the panel.
    uCanopyC: { value: new Vector3() }, uCanopyAmt: { value: 0 }, uBehindDist: { value: 6 },
    // Along the panel (U, metres from its corner): the street's light fading from the near end (x) to the
    // far one (y) by z; 0 leaves the street as it is (the dusk: the last gold dies toward the far end).
    uFall: { value: new Vector3(0, 1, 0) },
    ...own,
  };
  const mat = new ShaderMaterial({
    uniforms,
    vertexShader: /* glsl */ `varying vec3 vP; void main(){ vec4 w = modelMatrix * vec4(position, 1.); vP = w.xyz; gl_Position = projectionMatrix * viewMatrix * w; }`,
    fragmentShader: /* glsl */ `
      varying vec3 vP;
      uniform vec3 uO, uU, uV, uNf, uFace, uFaceSun, uWallC, uRimC, uBehindLow, uBehindHigh, uCanopyC, uFall;
      uniform vec2 uSize, uOne, uBehindY;
      uniform float uDepth, uHalo, uRef[2], uI[2], uCanopyAmt, uBehindDist;
      ${BLOCKS}
      ${NOISE}
      ${aperturesGLSL(lights)}
      #define B ${BLOCK.toFixed(4)}
      vec2 local(vec3 P){ return vec2(dot(P - uO, uU), dot(P - uO, uV)); }
      float pat(vec2 uv){ vec2 c = mod(uv, B) - 0.5 * B, id = floor(uv / B); ${PATTERN[pattern]} }
      // What is seen through a hole: a lit room or the street, a gradient in world y.
      vec3 behind(vec3 P, float along){
        vec3 c = mix(uBehindLow, uBehindHigh, smoothstep(uBehindY.x, uBehindY.y, P.y));
        c = mix(c, uBehindHigh, uFall.z * smoothstep(uFall.x, uFall.y, along));
        float tree = smoothstep(.56, .64, fbm(vec2(dot(P, uU), P.y) * vec2(.42, .5) + 3.1) + (clamp(P.y / 8., 0., 1.) - .5) * .8);
        return mix(c, uCanopyC, tree * uCanopyAmt);
      }
      void main(){
        vec2 uv = local(vP);
        float d = pat(uv), fw = max(fwidth(d), 1e-5);
        vec2 id = floor(uv / B), c = mod(uv, B) - 0.5 * B;
        // Cast blocks vary a little in tone; 8 mm joints sit darker.
        float tone = 0.94 + 0.12 * h21(id + 3.7);
        float joint = 1. - 0.2 * (1. - smoothstep(0.004, 0.004 + fw, 0.5 * B - max(abs(c.x), abs(c.y))));
        vec3 rd = normalize(vP - cameraPosition);
        float toward = dot(rd, -uNf);          // > 0: the ray goes into the panel
        vec3 inward = -uNf * sign(toward);
        float tb = uDepth / max(abs(toward), 1e-3);
        ${mode === "frontlit" ? `
        vec3 facing = uNf * sign(toward); // the face's normal on the viewer's side
        float n0 = max(dot(facing, uLt[0]), 0.);
        float f0 = n0 > 0. ? n0 / uRef[0] * lightMask0(vP + facing * 0.002) * uI[0] : 0.;
        vec3 col = mix(uFace, uFaceSun, min(f0, 1.)) * tone * joint;` : `
        vec3 col = mix(uFace * tone * joint, uRimC, uHalo * exp(-max(d, 0.) / 0.012));`}
        if (d < fw) {
          float a = 0., b = tb;
          for (int i = 0; i < 5; i++) { float m = (a + b) * .5; if (pat(local(vP + rd * m)) < 0.) a = m; else b = m; }
          vec3 H = vP + rd * b;
          float d2 = pat(local(vP + rd * tb));
          vec2 cH = local(H), e = vec2(1e-3, 0.);
          vec2 g = vec2(pat(cH + e.xy) - pat(cH - e.xy), pat(cH + e.yx) - pat(cH - e.yx));
          vec3 n = normalize(-(g.x * uU + g.y * uV));
          // Hole walls: lit where they face the light that comes through.
          ${mode === "frontlit" ? `
          float lit = max(dot(n, uLt[0]), 0.) * uI[0] * lightMask0(H);
          vec3 wall = mix(uWallC, uRimC, clamp(lit * 1.6, 0., 1.));` : `
          float lit = max(dot(n, uLt[${light}]), 0.) * uI[${light}];
          vec3 wall = mix(uWallC * (0.85 + 0.25 * b / tb), uRimC, clamp(lit * 1.8, 0., 1.));`}
          vec3 hole = mix(wall, behind(vP + rd * (uBehindDist / max(abs(toward), 1e-3)), uv.x), smoothstep(fw, -fw, d2));
          col = mix(hole, col, smoothstep(-fw, fw, d));
        }
        gl_FragColor = vec4(col, 1.);
        #include <colorspace_fragment>
      }
      #undef B`,
    toneMapped: false,
  });
  const mesh = new Mesh(new PlaneGeometry(size[0], size[1]), mat);
  // PlaneGeometry spans x, y with normal +z: map onto U, V, N at the panel's centre.
  const m = new Matrix4().makeBasis(U3, V3, N3);
  mesh.quaternion.setFromRotationMatrix(m);
  mesh.position.copy(new Vector3(...corner).addScaledVector(U3, size[0] / 2).addScaledVector(V3, size[1] / 2));
  return mesh;
}
