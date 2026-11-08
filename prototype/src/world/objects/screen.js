import { ExtrudeGeometry, InstancedMesh, Matrix4, Mesh, Path, PlaneGeometry, ShaderMaterial, Shape } from "three";
import { APERTURE } from "../glsl/aperture.js";
import { BLOCK, BLOCKS, DEPTH, HOA_THI } from "../glsl/blocks.js";
import { DITHER, LEAF, NOISE } from "../glsl/noise.js";
import { STREET } from "./sky.js";

// The breeze-block screen, backlit: a logo-blue silhouette (--color-world-screen-face) with
// holes blazing the street (05-DESIGN D6.3). Face, joints and the halation around each hole
// are shared by both tiers; only how the hole is made differs.
const FACE = /* glsl */ `
uniform vec3 uFace, uGlow, uRim, uWall;
uniform float uRimI, uHaloA, uHaloW; // uTime comes with STREET
// d: hole SDF at this pixel (> 0 on the face), fw: its change per pixel. Cast blocks vary ±6% in
// tone, 8 mm mortar joints sit 20% darker, and the street light halates 1.2 cm into the face
// (world units, so the glow keeps its size at every distance; the passage widens it as the camera nears
// a hole, the eye adjusting to the light).
vec3 faceColor(vec2 c, vec3 P, float d, float fw){
  vec3 col = uFace * (0.94 + 0.12 * h21(floor((P.zy - vec2(uZ0, uY0)) / uB)));
  float jd = 0.5 * uB - max(abs(c.x), abs(c.y));
  col *= 1. - 0.2 * (1. - smoothstep(0.004, 0.004 + fw, jd));
  col = mix(col, uGlow, uHaloA * exp(-max(d, 0.) / uHaloW));
  return col;
}
// A hole wall at H with inward normal n: sunlit where the sun reaches it through the
// street-side opening (dappled by the canopy), otherwise lit by the street.
vec3 wallColor(vec3 H, vec3 n, float depth01){
  float lit = apert(H, uSX + sign(uL.x) * uD) * max(dot(n, uL), 0.) * leaf(H, uTime);
  return mix(uWall * (0.85 + 0.25 * depth01), uRim, clamp(lit * 1.8, 0., 1.) * uRimI);
}
`;

const VERT = /* glsl */ `varying vec3 vP; varying vec3 vN;
void main(){
  mat4 m = modelMatrix;
  #ifdef USE_INSTANCING
  m = m * instanceMatrix;
  #endif
  vec4 w = m * vec4(position, 1.);
  vP = w.xyz; vN = normalize(mat3(m) * normal);
  gl_Position = projectionMatrix * viewMatrix * w;
}`;

const HEAD = /* glsl */ `
varying vec3 vP; varying vec3 vN;
${BLOCKS}
${APERTURE}
${NOISE}
${DITHER}
${LEAF}
${STREET}
${FACE}
`;

// Tier 1: the whole screen is one plane (2 triangles). Inside a hole, a second test at the
// back face decides between the street and the hole wall, so the 10 cm still reads.
function sdfScreen(U, s) {
  const mat = new ShaderMaterial({
    uniforms: U,
    vertexShader: VERT,
    fragmentShader: /* glsl */ `
      ${HEAD}
      void main(){
        vec2 c = blockLocal(vP.zy);
        float d = HOLE(c);
        float fw = max(fwidth(d), 1e-5);
        vec3 col = faceColor(c, vP, d, fw);
        if (d < fw) {
          vec3 rd = normalize(vP - cameraPosition);
          float side = sign(uL.x);
          float tb = uD / max(abs(rd.x), 1e-3);
          vec3 Q = vP + rd * tb;
          float d2 = onScreen(Q) ? HOLE(blockLocal(Q.zy)) : 1.;
          float a = 0., b = tb;
          for (int i = 0; i < 5; i++) { float mid = (a + b) * .5; if (HOLE(blockLocal((vP + rd * mid).zy)) < 0.) a = mid; else b = mid; }
          vec3 H = vP + rd * b;
          vec2 cH = blockLocal(H.zy), e = vec2(1e-3, 0.);
          vec2 g = vec2(HOLE(cH + e.xy) - HOLE(cH - e.xy), HOLE(cH + e.yx) - HOLE(cH - e.yx));
          vec3 n = normalize(vec3(0., -g.y, -g.x));
          vec3 hole = mix(wallColor(H, n, b / tb), street(cameraPosition, rd), smoothstep(fw, -fw, d2));
          col = mix(hole, col, smoothstep(-fw, fw, d));
        }
        gl_FragColor = vec4(col, 1.);
        #include <colorspace_fragment>
        DITHER_OUT;
      }`,
    toneMapped: false,
  });
  const geo = new PlaneGeometry(s.z1 - s.z0, s.y1 - s.y0);
  const mesh = new Mesh(geo, mat);
  mesh.rotation.y = -Math.PI / 2;
  mesh.position.set(s.sx, (s.y0 + s.y1) / 2, (s.z0 + s.z1) / 2);
  return mesh;
}

// Tier 2: real blocks, one InstancedMesh (~230 triangles a block, street-side cap removed).
function blockGeometry() {
  const h = BLOCK / 2, { r, pipAt, pipR } = HOA_THI;
  const s = new Shape().moveTo(-h, -h).lineTo(h, -h).lineTo(h, h).lineTo(-h, h).closePath();
  const N = 40, quat = new Path();
  for (let i = 0; i < N; i++) {
    const th = (-i / N) * Math.PI * 2;
    const R = 2 * r * Math.max(Math.cos(th), Math.cos(th - Math.PI / 2), Math.cos(th - Math.PI), Math.cos(th + Math.PI / 2));
    i ? quat.lineTo(R * Math.cos(th), R * Math.sin(th)) : quat.moveTo(R * Math.cos(th), R * Math.sin(th));
  }
  quat.closePath();
  s.holes.push(quat);
  for (const [cx, cy] of [[1, 1], [-1, 1], [-1, -1], [1, -1]]) s.holes.push(new Path().absarc(cx * pipAt, cy * pipAt, pipR, 0, Math.PI * 2, true));
  const g = new ExtrudeGeometry(s, { depth: DEPTH, bevelEnabled: false, curveSegments: 8 }).rotateY(Math.PI / 2);
  // Drop the street-side cap (normal +x): never seen from the room.
  const n = g.getAttribute("normal"), keep = [];
  for (let i = 0; i < n.count; i += 3) if (n.getX(i) < 0.5) keep.push(i, i + 1, i + 2);
  g.setIndex(keep);
  return g;
}

function instancedScreen(U, s) {
  const mat = new ShaderMaterial({
    uniforms: U,
    vertexShader: VERT,
    fragmentShader: /* glsl */ `
      ${HEAD}
      void main(){
        vec3 N = normalize(vN);
        vec2 c = blockLocal(vP.zy);
        vec3 col;
        if (N.x < -0.5) {
          float d = HOLE(c);
          col = faceColor(c, vP, d, max(fwidth(d), 1e-5));
        } else {
          float depth01 = clamp((vP.x - uSX) / uD * sign(uL.x), 0., 1.);
          col = wallColor(vP, N, depth01);
          // The 4 mm bevel at the street-side lip catches the light, most where it faces up and sunward.
          float lip = smoothstep(0.955, 0.965, depth01) * clamp(0.4 + 0.6 * max(dot(N, uL), 0.) + 0.3 * N.y, 0., 1.);
          col = mix(col, uRim, 0.6 * lip * uRimI);
        }
        gl_FragColor = vec4(col, 1.);
        #include <colorspace_fragment>
        DITHER_OUT;
      }`,
    toneMapped: false,
  });
  const cols = Math.round((s.z1 - s.z0) / BLOCK), rows = Math.round((s.y1 - s.y0) / BLOCK);
  const mesh = new InstancedMesh(blockGeometry(), mat, cols * rows);
  const m4 = new Matrix4();
  let k = 0;
  for (let c = 0; c < cols; c++) for (let r = 0; r < rows; r++) mesh.setMatrixAt(k++, m4.makeTranslation(s.sx, s.y0 + (r + 0.5) * BLOCK, s.z0 + (c + 0.5) * BLOCK));
  mesh.instanceMatrix.needsUpdate = true;
  mesh.computeBoundingSphere();
  return mesh;
}

export const screenMesh = (U, s, tier) => (tier >= 2 ? instancedScreen(U, s) : sdfScreen(U, s));

// One block of the screen as the analytic SDF (the passage's hero block on Tier 2): at 35 cm a 40-sample
// quatrefoil shows its facets; the SDF is exact at any distance. Same grid, same light as the screen.
export function blockMesh(U, s, center) {
  const h = BLOCK / 2;
  return sdfScreen(U, { sx: s.sx, z0: center.z - h, z1: center.z + h, y0: center.y - h, y1: center.y + h });
}
// The instance index of the block centred at `center` (instancedScreen lays columns, then rows).
export function blockIndex(s, center) {
  const rows = Math.round((s.y1 - s.y0) / BLOCK);
  return Math.floor((center.z - s.z0) / BLOCK) * rows + Math.floor((center.y - s.y0) / BLOCK);
}
