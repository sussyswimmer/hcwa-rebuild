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

// What the open shutters give onto at 17:00 (M7, Maxwell 12 Nov 2026: "the shutters open onto a blank cream view"):
// a Saigon street seen from the first floor, drawn by ray: from the camera through the window to whichever of these it
// meets first, so the view has real depth and parallax as the camera comes round. The same street as Get Involved's
// header (partials/involve-window.mjs): a row of two-storey shophouses across the road in yellow, mint and cream
// wash, their ground floors behind rolling shutters (some half up, the shop in shade behind), ochre floor bands,
// arched upper windows with jade louvers and potted plants, a cornice with a course of Hoa thị blocks; the cream tiled
// pavement in front of them with a parked Cub (step-through, no plate, no lettering); the road; a tamarind on this
// side whose fine canopy lies across the top of the window and whose shade dapples the road; two sagging overhead
// wires. No people, no signage, no text. In the screen-print manner of the house's posters: flat token inks, soft
// edges (screen-space AA), the far planes hazed toward the late light. Nothing passes sun-100 (the set's MIN cap
// holds anyway). The plane is only a canvas: put it anywhere beyond the window.
//   street: the street level (y, metres; the room's floor is 0), zc: the window's centre in z, trunk: the near
//   tamarind's z. Uniforms the set drives: uPale (0..1, the late light washing the whole view toward sun-100 as the
//   exposure rises), uSway (rad, the canopy about its trunk, Tier 2 only), uTime (the dapple's slow drift).
export const STREET_VIEW = { street: -3.4, road: [2.6, 9.4], facade: 12, kerb: 0.15, bay: 4.2, ground: 3.4, band: 0.35, upper: 3.2, cornice: 0.55, tree: 2.3, cub: 10.7 };
export function viewMaterial({ street = STREET_VIEW.street, zc = 0, trunk = -1.4 } = {}) {
  const V = STREET_VIEW;
  const c = (name) => tok(name), v3 = (col) => vec(col);
  const sky = mixc(c("sun-200"), c("sun-100"), 0.35);
  const fronts = [c("world-vang-sun"), mixc(c("world-dado-sun"), c("lime-50"), 0.45), mixc(c("tile-cream"), c("lime-50"), 0.3), mixc(c("world-vang-sun"), c("tile-ochre-lit"), 0.25)];
  const U = {
    uPale: { value: 0 }, uSway: { value: 0 }, uTime: { value: 0 },
    uSky: { value: v3(sky) }, uSkyHi: { value: v3(c("sun-100")) }, uHaze: { value: v3(mixc(c("sun-100"), c("sun-200"), 0.4)) },
    uFront: { value: fronts.map(v3) },
    uBand: { value: v3(mixc(c("tile-ochre-lit"), c("sun-200"), 0.35)) }, uCream: { value: v3(c("tile-cream")) },
    uRib: { value: v3(mixc(c("lime-300"), c("world-gach-shade"), 0.4)) }, uShop: { value: v3(mixc(c("ink-700"), c("world-wood"), 0.45)) },
    uJade: { value: v3(c("tile-jade")) }, uJadeLit: { value: v3(c("tile-jade-lit")) }, uIron: { value: v3(mixc(c("ink-700"), c("blue-700"), 0.3)) },
    uRoad: { value: v3(mixc(c("world-gach-shade"), c("world-vang-shade"), 0.35)) }, uPave: { value: v3(mixc(c("tile-cream"), c("world-gach-shade"), 0.25)) },
    uSunPatch: { value: v3(mixc(c("sun-200"), c("sun-400"), 0.35)) }, uWood: { value: v3(c("world-wood")) },
    uLeaf: { value: v3(mixc(c("tile-jade"), c("unit-green-bamboo"), 0.35)) }, uLeafLit: { value: v3(mixc(c("tile-jade-lit"), c("sun-400"), 0.45)) },
    uCub: { value: v3(mixc(c("tile-jade-lit"), c("tile-jade"), 0.35)) }, uShield: { value: v3(c("lime-50")) }, uTyre: { value: v3(c("ink-700")) },
  };
  const f = (n) => n.toFixed(3);
  return new ShaderMaterial({
    uniforms: U,
    vertexShader: /* glsl */ `varying vec3 vP; void main(){ vec4 w = modelMatrix * vec4(position, 1.); vP = w.xyz; gl_Position = projectionMatrix * viewMatrix * w; }`,
    fragmentShader: /* glsl */ `
      varying vec3 vP;
      uniform float uPale, uSway, uTime;
      uniform vec3 uSky, uSkyHi, uHaze, uFront[4], uBand, uCream, uRib, uShop, uJade, uJadeLit, uIron, uRoad, uPave, uSunPatch, uWood, uLeaf, uLeafLit, uCub, uShield, uTyre;
      ${NOISE}
      ${BLOCKS}
      ${DITHER}
      #define G ${f(street)}
      #define ZC ${f(zc)}
      #define FX ${f(V.facade)}
      #define BAY ${f(V.bay)}
      #define B ${BLOCK.toFixed(4)}
      // Coverage of a signed distance (negative inside) with a pixel-wide soft edge.
      float cov(float d, float w){ return 1. - smoothstep(-w, w, d); }
      // A ray to the plane x = X: the hit point (y, z) and its distance; t < 0 never hits.
      vec3 atX(vec3 ro, vec3 rd, float X){ float t = (X - ro.x) / rd.x; return vec3(ro.yz + rd.yz * t, t); }
      // The canopy's leaf mass at (z, y) on its plane (fine pinnate leaves: small cells over a broad mass), swayed.
      float canopy(vec2 q, float top){
        vec2 o = vec2(${f(trunk)} + ZC, G + 5.);
        float s = sin(uSway), k = cos(uSway);
        q = mat2(k, s, -s, k) * (q - o) + o;
        float mass = fbm(q * vec2(.55, .7) + 4.3) + .9 * smoothstep(top - 1.6, top + 1.4, q.y) - .78;
        float fine = vn(q * vec2(9.5, 13.) + 1.7) * .6 + vn(q * vec2(23., 31.)) * .4;
        return smoothstep(-.03, .05, mass + (fine - .5) * .16);
      }
      // One shophouse front at (z, h): h above the street; the bay index picks its wash and how far its shutter is up.
      vec3 front(vec2 p, float w, out float shade){
        float h = p.y - G, bi = floor((p.x - ZC) / BAY + .5), zb = p.x - ZC - bi * BAY; // zb: across the bay, 0 at its centre
        float r = h21(vec2(bi, 7.)), r2 = h21(vec2(bi, 3.));
        int k = int(mod(bi, 4.) + 4.) - 4;
        vec3 wash = k == 0 ? uFront[0] : k == 1 ? uFront[1] : k == 2 ? uFront[2] : uFront[3];
        vec3 col = wash * (.94 + .06 * vn(p * 3.1));
        shade = 0.;
        float gh = ${f(V.ground)}, bh = ${f(V.band)}, uh = ${f(V.upper)};
        // Ground floor: piers at the bay's edges, the opening between them behind a rolling shutter.
        float open = cov(abs(zb) - (BAY * .5 - .32), w) * cov(h - (gh - .25), w);
        float up = mix(.55, 2.6, r);                         // how far the shutter is raised, from the lintel down
        float shutter = open * cov((gh - .25 - up) - h, w);  // the rolled part above, ribbed
        float rib = .5 + .5 * sin(h * 6.2832 / .085);
        vec3 shutterC = mix(uRib, uCream, .25 + .35 * rib);
        vec3 shop = uShop * (.8 + .25 * smoothstep(0., 1.6, h)) * (1. + .15 * vn(p * vec2(1.3, 2.)));
        col = mix(col, shop, open);
        col = mix(col, shutterC, shutter);
        // The step in front of the shop and a darker plinth.
        col = mix(col, col * .86, cov(h - .3, w));
        // The floor band (the balcony slab) and its shadow.
        float band = cov(abs(h - gh - bh * .5) - bh * .5, w);
        col = mix(col, col * .82, cov(abs(h - gh + .08) - .08, w) * (1. - band));
        col = mix(col, uBand, band);
        // Upper floor: an arched window with jade louvers, a sill, a small iron railing, plants on alternate bays.
        float u = h - gh - bh;
        vec2 wq = vec2(zb, u - 1.2);
        float arch = max(abs(wq.x) - .62, max(wq.y - .7, -1.0 - wq.y));
        arch = min(arch, max(length(vec2(wq.x, max(wq.y - .7, 0.))) - .62, -wq.y + .7));
        float win = cov(arch, w);
        float slats = .5 + .5 * sin((u) * 6.2832 / .11);
        vec3 louver = mix(uJade, uJadeLit, .25 + .45 * slats);
        louver = mix(louver, uJade * .8, cov(abs(zb) - .03, w));   // the meeting stiles
        col = mix(col, louver, win);
        col = mix(col, uBand, cov(max(abs(zb) - .78, abs(u - .14) - .06), w));      // the sill
        float rail = cov(abs(u - .62) - .018, w) + cov(abs(fract(zb / .14) - .5) * .14 - .008, w) * step(.14, u) * step(u, .62);
        col = mix(col, uIron, clamp(rail, 0., 1.) * cov(abs(zb) - .74, w) * .8);
        if (r2 > .45) {
          vec2 pq = vec2(zb - .95 * sign(r2 - .7), u - .38);
          float pot = cov(max(abs(pq.x) - .12 + pq.y * .12, abs(pq.y + .1) - .12), w);
          float leaves = cov(length((pq - vec2(0., .22)) * vec2(1., 1.25)) - .24 - .05 * vn(pq * 22.), w);
          col = mix(col, uBand * .9, pot);
          col = mix(col, mix(uJade, uJadeLit, .4 + .4 * vn(pq * 30.)), leaves);
        }
        // The cornice: a cream ledge, a course of Hoa thị blocks with the sky through them, the coping.
        float ch = h - gh - bh - uh;
        float cornice = step(0., ch) * cov(ch - ${f(V.cornice)}, w);
        vec2 bq = vec2(p.x, ch - .12);
        float course = step(0., bq.y) * step(bq.y, B);
        float hole = course * cov(sdHoaThi(mod(bq, B) - .5 * B), w * .6);
        col = mix(col, uCream * .97, cornice);
        col = mix(col, col * .9, cov(abs(ch - .08) - .03, w) * cornice);
        shade = hole;
        return col;
      }
      // The Cub on the far pavement, side on (front to −z): a flat silhouette in z (q.x) and height (q.y).
      float cub(vec2 q, float w, out vec3 col){
        float wheels = min(length(q - vec2(-.62, .27)), length(q - vec2(.58, .27))) - .27;
        float hubs = min(length(q - vec2(-.62, .27)), length(q - vec2(.58, .27))) - .1;
        float body = sdBox2(q - vec2(.38, .55), vec2(.36, .16)) - .06;            // the rear body under the seat
        float seat = sdBox2(q - vec2(.36, .78), vec2(.34, .05)) - .03;
        float back = sdBox2(q - vec2(-.05, .38), vec2(.32, .05)) - .03;           // the step-through backbone
        float shield = sdBox2(q - vec2(-.42, .62), vec2(.08, .3)) - .05;           // the front leg shield
        float fork = sdBox2(q - vec2(-.58, .62), vec2(.03, .34));
        float head = length(q - vec2(-.56, 1.02)) - .09;                           // the headlight in the bar cover
        float bar = sdBox2(q - vec2(-.5, 1.04), vec2(.14, .025));
        float paint = min(min(body, back), head);
        col = uCub;
        col = mix(col, uShield, cov(shield, w));
        col = mix(col, uTyre * 1.2, cov(min(min(seat, fork), bar), w));
        col = mix(col, uTyre, cov(wheels, w) * (1. - cov(hubs, w)));
        col = mix(col, uShield * .85, cov(hubs, w));
        return cov(min(min(min(paint, shield), min(seat, fork)), min(min(wheels, bar), hubs)), w);
      }
      void main(){
        vec3 ro = cameraPosition, rd = normalize(vP - ro);
        float w = 1.5 * length(fwidth(vP)) / max(distance(vP, ro), .01); // a pixel's angle, for the soft edges below
        vec3 col = uSky;
        float dist = 60.;
        // The sky over the roofs, paler toward the top.
        vec3 sF = atX(ro, rd, FX);
        col = mix(uSky, uSkyHi, smoothstep(G + 7., G + 13., sF.x));
        // The facade across the road.
        float roof = G + ${f(V.ground + V.band + V.upper + V.cornice)};
        if (rd.x > 0. && sF.x < roof && sF.x > G) {
          float sh;
          vec3 fc = front(sF.yx, w * sF.z, sh);
          col = mix(fc, mix(uSky, uSkyHi, .3), sh);
          dist = sF.z;
        }
        // The ground: the road and the pavements, where the ray meets it before the facade.
        if (rd.y < 0.) {
          float tg = (G - ro.y) / rd.y;
          vec3 g = ro + rd * tg;
          if (g.x < FX && tg < dist) {
            float far = step(${f(V.road[1])}, g.x), near = 1. - step(${f(V.road[0])}, g.x);
            float tiles = .5 + .5 * sin(g.x * 6.2832 / .4) * sin(g.z * 6.2832 / .4);
            vec3 pave = uPave * (.95 + .05 * step(.5, tiles));
            vec3 gc = mix(uRoad * (.95 + .08 * vn(g.xz * 2.)), pave, max(far, near));
            // The kerbs: a lit edge on each pavement.
            gc = mix(gc, uCream, cov(abs(g.x - ${f(V.road[1])}) - .06, w * tg) + cov(abs(g.x - ${f(V.road[0])}) - .06, w * tg));
            // The late sun on the pavement and the road, broken by the tamarinds' shade.
            float lit = smoothstep(.42, .58, fbm(g.xz * vec2(.35, .5) + vec2(uTime * .01, 0.)) + .12 * far);
            gc = mix(gc * .82, mix(gc, uSunPatch, .55), lit);
            col = gc;
            dist = tg;
            // The Cub, parked on the far pavement against a shop front.
            vec3 sC = atX(ro, rd, ${f(V.cub)});
            if (sC.z > 0. && sC.z < tg + .5) {
              vec3 cc;
              float a = cub(vec2(sC.y - (ZC + 1.6), sC.x - G - ${f(V.kerb)}), w * sC.z, cc);
              col = mix(col, cc, a);
              // Its shadow on the pavement.
              col = mix(col, col * .8, cov(length((g.xz - vec2(${f(V.cub)}, ZC + 1.6)) * vec2(2.4, 1.)) - .9, .25) * far);
            }
          }
        }
        // Haze: the far planes take the late light.
        col = mix(col, uHaze, smoothstep(6., 22., dist) * .35);
        // Two overhead wires sagging across, in front of the facade.
        for (int i = 0; i < 2; i++) {
          float X = 6.5 + float(i) * 1.4;
          vec3 sW = atX(ro, rd, X);
          if (sW.z > 0. && sW.z < dist) {
            float zz = (sW.y - ZC) / 9.;
            float y = G + 6.1 + float(i) * .35 - 1.1 * (1. - zz * zz);
            col = mix(col, uIron, .55 * cov(abs(sW.x - y) - .012, w * sW.z * .8));
          }
        }
        // The near tamarind: its trunk on this side's pavement, its canopy across the top of the window.
        vec3 sT = atX(ro, rd, ${f(V.tree)});
        if (sT.z > 0.) {
          float tz = ${f(trunk)} + ZC, bend = .25 * smoothstep(G, G + 6., sT.x);
          float trunkD = abs(sT.y - tz - bend) - .17 + .03 * smoothstep(G + 4., G, sT.x);
          col = mix(col, uWood * (.85 + .15 * vn(sT.yx * vec2(9., 2.))), cov(trunkD, w * sT.z) * step(sT.x, G + 6.));
          float leaves = canopy(sT.yx, G + 6.2);
          float glow = vn(sT.yx * 4.1 + 2.) * .6 + .4 * (1. - smoothstep(G + 5.5, G + 9., sT.x));
          col = mix(col, mix(uLeaf, uLeafLit, glow), leaves);
        }
        // The late light washes the whole view toward sun-100 as the exposure rises (the set's setExposure).
        col = mix(col, uSkyHi, uPale);
        gl_FragColor = vec4(col, 1.);
        #include <colorspace_fragment>
        DITHER_OUT;
      }
      #undef G
      #undef ZC
      #undef FX
      #undef BAY
      #undef B`,
    toneMapped: false,
  });
}
