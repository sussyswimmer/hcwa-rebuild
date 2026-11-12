const __vite__mapDeps=(i,m=__vite__mapDeps,d=(m.f||(m.f=["assets/frame-CQsXUThy.js","assets/rolldown-runtime-DK3Fl9T5.js","assets/debug-D2c3PMPp.js","assets/corridor-CnlRBB1a.js","assets/main-EuQKaEKu.js","assets/main-Dub9OtyF.css","assets/three-Dgmw-8ZC.js","assets/wall-poster-C-wKsBqz.js","assets/memory-Pyl4VpNY.js","assets/common-CYgvHfiY.js","assets/shutters-WEeBIf_X.js"])))=>i.map(i=>d[i]);
import{n as e,r as t,t as n}from"./main-EuQKaEKu.js";import{A as r,C as i,F as a,O as o,P as s,S as c,_ as l,b as u,c as d,f,g as p,h as m,i as h,k as g,l as _,u as v,w as y,x as b}from"./three-Dgmw-8ZC.js";import{a as x,c as S,d as C,f as w,i as ee,l as T,n as te,r as ne,s as re,t as ie,u as ae}from"./frame-CQsXUThy.js";import{a as oe,o as se,s as ce,t as le}from"./memory-Pyl4VpNY.js";import{t as ue,worldOff as de}from"./world-But5Jb9e.js";import{$ as fe,A as pe,B as me,C as he,Ct as E,D as ge,Dt as D,E as O,F as _e,G as k,H as ve,I as ye,J as be,K as xe,L as Se,M as Ce,Mt as A,N as we,Nt as Te,O as Ee,Ot as De,P as Oe,Pt as j,R as ke,S as Ae,St as M,T as je,Tt as Me,U as Ne,V as N,W as Pe,X as Fe,Y as Ie,Z as Le,_ as Re,_t as ze,at as Be,b as P,bt as F,c as Ve,ct as He,dt as Ue,et as We,ft as Ge,g as Ke,gt as qe,ht as Je,it as Ye,j as Xe,jt as I,k as Ze,kt as L,lt as Qe,mt as $e,n as et,nt as tt,o as nt,ot as rt,q as R,r as it,rt as at,st as ot,t as st,v as ct,vt as lt,w as ut,x as dt,xt as ft,y as pt,yt as mt,z as ht}from"./wall-poster-C-wKsBqz.js";function gt(e){return new g({uniforms:e,vertexShader:`
      attribute float aKind;
      varying vec3 vP; varying vec3 vN; varying float vKind;
      void main(){
        vec4 w = modelMatrix * vec4(position, 1.);
        vP = w.xyz; vN = normalize(mat3(modelMatrix) * normal); vKind = aKind;
        gl_Position = projectionMatrix * viewMatrix * w;
      }`,fragmentShader:`
      #include <common>
      varying vec3 vP; varying vec3 vN; varying float vKind;
      uniform vec3 uShade[3], uSunT[3], uGlare, uBoxMin, uBoxMax;
      uniform float uSunI, uNdlRef, uTime;
      uniform sampler2D uLime, uTerr;
      ${ft}
      ${lt}
      ${L}
      ${De}
      ${Je}
      ${He([`Pl`])}
      float occ(float d, float r, float s){ return d < 0.003 ? 1. : 1. - s * (1. - smoothstep(0., r, d)); }
      void main(){
        int k = int(vKind + 0.5);           // 0 lime wall, 1 terrazzo floor, 2 ceiling
        vec3 N = normalize(vN);
        vec2 uv = abs(N.z) > .5 ? vP.xy : abs(N.x) > .5 ? vP.zy : vP.xz;
        vec4 lw = texture2D(uLime, uv * 0.25);
        float vertical = 1. - step(.5, abs(N.y));
        float mott = (lw.r - .5) * .09 + (lw.g - .5) * .05 + (lw.b - .5) * .04 * vertical;
        vec3 rel = k == 1 ? texture2D(uTerr, vP.xz * 0.5).rgb / ${Qe.toFixed(3)} * (1. + mott * .5) : vec3(1. + mott);
        vec3 Nb = N;
        if (k != 1 && uWallK > 0.) {
          vec2 wuv = uv / ${at.wall.toFixed(1)};
          float mw = smoothstep(.35, .65, lw.r);
          vec4 w = wallTap(uWPl, wuv, mw);
          // The ceiling stays calmer; behind body text (a capped rect, caps.js) the plaster keeps a fifth of its relief,
          // so the intro's darkest pixels stay where its contrast bar was measured.
          float age = wallAge(lw.a) * (k == 2 ? .3 : 1.) * mix(.2, 1., smoothstep(.4, 1., capAt(gl_FragCoord.xy)));
          float cg = wallCrackGate(wuv, mw, age);
          rel = mix(rel, vec3(wallRelM(w, age, cg)), uWallK);
          Nb = wallN(N, wallCrackN(w, cg), uWallK * .6 * mix(.3, 1., age)); // the plaster's grit, softened: a phone stands 0.5 m from it
        }

        // Analytic contact shadow at floor, ceiling and corners: no SSAO.
        float ao = occ(vP.y - uBoxMin.y, .35, .12) * occ(uBoxMax.y - vP.y, .4, .1)
                 * occ(vP.z - uBoxMin.z, .9, .2) * occ(uBoxMax.z - vP.z, .8, .12)
                 * occ(vP.x - uBoxMin.x, .9, .2) * occ(uBoxMax.x - vP.x, .6, .12);
        // Skylight comes through the screen (+x): surfaces near it and facing it are brighter,
        // the far side and the ceiling away from it fall off. Text sits on the back wall
        // left and low, where this stays within a few percent of the target.
        float near = smoothstep(uBoxMin.x, uBoxMax.x, vP.x);
        float fill = (1. + 0.06 * N.x) * (0.92 + 0.14 * near) * (k == 2 ? 0.86 + 0.16 * near : 1.);
        // The back wall meets the screen in the back-right corner. The fill above peaks right there, so without
        // a real corner shade the last half metre of plaster read as a hard white seam against the dark screen
        // (Maxwell, 11 Nov 2026). Walls only: a deeper, wider contact shade that eases the plaster into the screen.
        float toScreen = 1. - smoothstep(0., .55, uBoxMax.x - vP.x);
        ao *= 1. - .26 * vertical * step(.5, abs(N.z)) * toScreen * toScreen;

        float ndl = max(dot(N, uL), 0.);
        float m = ndl > 0. ? sunMask(vP) : 0.;
        float f = m > 0. ? ndl / uNdlRef * m * leaf(vP, uTime) * capAt(gl_FragCoord.xy) * uSunI : 0.;
        vec3 shade = uShade[k] * rel * ao * fill * wallFill(Nb, N, vec3(.8, .6, 0.), .4);
        vec3 sun = uSunT[k] * 0.94 * rel * mix(1., ao, .5) * wallSun(Nb, N, uL);

        vec3 col = mix(mix(shade, sun, min(f, 1.)), uGlare, min(max(f - 1., 0.) * .35, .6));
        gl_FragColor = vec4(col, 1.);
        #include <colorspace_fragment>
        gl_FragColor.rgb += (h21(gl_FragCoord.xy) - .5) / 255.;
      }`,toneMapped:!1})}var _t={1:160,2:420};function vt(e,{min:t,max:n},{mask:r=null}={}){let a=_t[2],o=new Float32Array(a*4),c=7,l=()=>(c=c*16807%2147483647)/2147483647;for(let e=0;e<o.length;e++)o[e]=l();let u=new h;u.setAttribute(`position`,new v(new Float32Array(a*3),3)),u.setAttribute(`aSeed`,new v(o,4));let d=new s(...t),f=new s(...n).sub(d),p=new g({uniforms:{...e,uBoxMin:{value:d},uBoxSize:{value:f},uBurst:{value:0}},vertexShader:`
      attribute vec4 aSeed;
      uniform vec3 uBoxMin, uBoxSize, uWind;
      uniform float uTime, uPx, uMotesA, uStir, uSunI, uBurst;
      varying float vA;
      ${ft}
      ${L}
      ${r?r.glsl:lt+De}
      void main(){
        float ph = aSeed.w * 6.2831;
        vec3 p = uBoxMin + aSeed.xyz * uBoxSize + uWind
          + vec3(sin(uTime * .21 + ph) * .06, sin(uTime * .17 + ph * 1.7) * .05 + uTime * .006, cos(uTime * .19 + ph * .7) * .06);
        p = uBoxMin + mod(p - uBoxMin, uBoxSize);
        ${r?`p -= normalize(uLt[0] * vec3(1., .25, 1.)) * uBurst * (.25 + .55 * aSeed.y);`:``}
        vec4 mv = modelViewMatrix * vec4(p, 1.);
        float m = ${r?r.call:`sunMask(p) * leaf(p, uTime)`};
        vA = m > 0. ? m * uMotesA * uSunI * (1. + .6 * uBurst) * (0.725 + 0.175 * sin(uTime * 2.3 + ph * 3.)) * (1. + 0.4 * min(abs(uStir), 1.)) : 0.;
        gl_PointSize = (4.2 * (3. / -mv.z) + 1.2) * uPx;
        gl_Position = vA < 0.004 ? vec4(2., 2., 2., 1.) : projectionMatrix * mv;
      }`,fragmentShader:`
      uniform vec3 uMoteCol; varying float vA;
      void main(){ vec2 d = gl_PointCoord - .5; gl_FragColor = vec4(uMoteCol * vA * exp(-dot(d, d) * 18.), 1.); }`,blending:2,transparent:!0,depthWrite:!1,toneMapped:!1}),m=new i(u,p);return m.frustumCulled=!1,m.renderOrder=5,{points:m,uniforms:p.uniforms,setTier(e){u.setDrawRange(0,_t[e>=2?2:1])},get count(){return u.drawRange.count},wrap(e){e.set((e.x%f.x+f.x)%f.x,(e.y%f.y+f.y)%f.y,(e.z%f.z+f.z)%f.z)},dispose(){u.dispose(),p.dispose()}}}var yt=`
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
`,bt=()=>({uSkyLow:{value:A(`sky`)},uSkyTop:{value:I(A(`sky`),A(`shade-100`),.35)},uCanopy:{value:I(A(`sky`),A(`unit-green-bamboo`),.2)},uSunGlow:{value:A(`on-dusk`)},uStreetX:{value:9}});function z(e,{x:t,z:n,size:r=[40,20]}){let i=new g({uniforms:e,vertexShader:`varying vec3 vP; void main(){ vec4 w = modelMatrix * vec4(position, 1.); vP = w.xyz; gl_Position = projectionMatrix * viewMatrix * w; }`,fragmentShader:`
      uniform vec3 uL;
      varying vec3 vP;
      ${L}
      ${D}
      ${yt}
      void main(){
        gl_FragColor = vec4(street(cameraPosition, normalize(vP - cameraPosition)), 1.);
        #include <colorspace_fragment>
        DITHER_OUT;
      }`,toneMapped:!1}),a=new l(new c(...r),i);return a.rotation.y=-Math.PI/2,a.position.set(t,r[1]/2-2,n),a}var xt={street:-3.4,road:[2.6,9.4],facade:12,kerb:.15,bay:4.2,ground:3.4,band:.35,upper:3.2,cornice:.55,tree:2.3,cub:10.7};function B({street:e=xt.street,zc:t=0,trunk:n=-1.4,lite:r=!1}={}){let i=xt,a=e=>A(e),o=e=>Te(e),s=I(a(`sun-200`),a(`sun-100`),.35),c=[a(`world-vang-sun`),I(a(`world-dado-sun`),a(`lime-50`),.45),I(a(`tile-cream`),a(`lime-50`),.3),I(a(`world-vang-sun`),a(`tile-ochre-lit`),.25)],l={uPale:{value:0},uSway:{value:0},uTime:{value:0},uSky:{value:o(s)},uSkyHi:{value:o(a(`sun-100`))},uHaze:{value:o(I(a(`sun-100`),a(`sun-200`),.4))},uFront:{value:c.map(o)},uBand:{value:o(I(a(`tile-ochre-lit`),a(`sun-200`),.35))},uCream:{value:o(a(`tile-cream`))},uRib:{value:o(I(a(`lime-300`),a(`world-gach-shade`),.4))},uShop:{value:o(I(a(`ink-700`),a(`world-wood`),.45))},uJade:{value:o(a(`tile-jade`))},uJadeLit:{value:o(a(`tile-jade-lit`))},uIron:{value:o(I(a(`ink-700`),a(`blue-700`),.3))},uRoad:{value:o(I(a(`world-gach-shade`),a(`world-vang-shade`),.35))},uPave:{value:o(I(a(`tile-cream`),a(`world-gach-shade`),.25))},uSunPatch:{value:o(I(a(`sun-200`),a(`sun-400`),.35))},uWood:{value:o(a(`world-wood`))},uLeaf:{value:o(I(a(`tile-jade`),a(`unit-green-bamboo`),.35))},uLeafLit:{value:o(I(a(`tile-jade-lit`),a(`sun-400`),.45))},uCub:{value:o(I(a(`tile-jade-lit`),a(`tile-jade`),.35))},uShield:{value:o(a(`lime-50`))},uTyre:{value:o(a(`ink-700`))}},u=e=>e.toFixed(3);return new g({uniforms:l,defines:r?{LITE:``}:{},vertexShader:`varying vec3 vP; void main(){ vec4 w = modelMatrix * vec4(position, 1.); vP = w.xyz; gl_Position = projectionMatrix * viewMatrix * w; }`,fragmentShader:`
      varying vec3 vP;
      uniform float uPale, uSway, uTime;
      uniform vec3 uSky, uSkyHi, uHaze, uFront[4], uBand, uCream, uRib, uShop, uJade, uJadeLit, uIron, uRoad, uPave, uSunPatch, uWood, uLeaf, uLeafLit, uCub, uShield, uTyre;
      ${L}
      ${ft}
      ${D}
      #define G ${u(e)}
      #define ZC ${u(t)}
      #define FX ${u(i.facade)}
      #define BAY ${u(i.bay)}
      #define B ${F.toFixed(4)}
      // Coverage of a signed distance (negative inside) with a pixel-wide soft edge.
      float cov(float d, float w){ return 1. - smoothstep(-w, w, d); }
      // A ray to the plane x = X: the hit point (y, z) and its distance; t < 0 never hits.
      vec3 atX(vec3 ro, vec3 rd, float X){ float t = (X - ro.x) / rd.x; return vec3(ro.yz + rd.yz * t, t); }
      // The canopy's leaf mass at (z, y) on its plane (fine pinnate leaves: small cells over a broad mass), swayed.
      float canopy(vec2 q, float top){
        vec2 o = vec2(${u(n)} + ZC, G + 5.);
        float s = sin(uSway), k = cos(uSway);
        q = mat2(k, s, -s, k) * (q - o) + o;
        vec2 m = q * vec2(.55, .7) + 4.3;
      #ifdef LITE
        float mass = vn(m) * .62 + vn(m * 2.03) * .31 + .9 * smoothstep(top - 1.6, top + 1.4, q.y) - .78;
        return smoothstep(-.03, .05, mass);
      #else
        float mass = fbm(m) + .9 * smoothstep(top - 1.6, top + 1.4, q.y) - .78;
        float fine = vn(q * vec2(9.5, 13.) + 1.7) * .6 + vn(q * vec2(23., 31.)) * .4;
        return smoothstep(-.03, .05, mass + (fine - .5) * .16);
      #endif
      }
      // One shophouse front at (z, h): h above the street; the bay index picks its wash and how far its shutter is up.
      vec3 front(vec2 p, float w, out float shade){
        float h = p.y - G, bi = floor((p.x - ZC) / BAY + .5), zb = p.x - ZC - bi * BAY; // zb: across the bay, 0 at its centre
        float r = h21(vec2(bi, 7.)), r2 = h21(vec2(bi, 3.));
        int k = int(mod(bi, 4.) + 4.) - 4;
        vec3 wash = k == 0 ? uFront[0] : k == 1 ? uFront[1] : k == 2 ? uFront[2] : uFront[3];
        vec3 col = wash * (.94 + .06 * vn(p * 3.1));
        shade = 0.;
        float gh = ${u(i.ground)}, bh = ${u(i.band)}, uh = ${u(i.upper)};
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
        float cornice = step(0., ch) * cov(ch - ${u(i.cornice)}, w);
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
        float roof = G + ${u(i.ground+i.band+i.upper+i.cornice)};
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
            float far = step(${u(i.road[1])}, g.x), near = 1. - step(${u(i.road[0])}, g.x);
            float tiles = .5 + .5 * sin(g.x * 6.2832 / .4) * sin(g.z * 6.2832 / .4);
            vec3 pave = uPave * (.95 + .05 * step(.5, tiles));
            vec3 gc = mix(uRoad * (.95 + .08 * vn(g.xz * 2.)), pave, max(far, near));
            // The kerbs: a lit edge on each pavement.
            gc = mix(gc, uCream, cov(abs(g.x - ${u(i.road[1])}) - .06, w * tg) + cov(abs(g.x - ${u(i.road[0])}) - .06, w * tg));
            // The late sun on the pavement and the road, broken by the tamarinds' shade.
          #ifdef LITE
            float lit = smoothstep(.42, .58, vn(g.xz * vec2(.35, .5) + vec2(uTime * .01, 0.)) * .94 + .12 * far);
          #else
            float lit = smoothstep(.42, .58, fbm(g.xz * vec2(.35, .5) + vec2(uTime * .01, 0.)) + .12 * far);
          #endif
            gc = mix(gc * .82, mix(gc, uSunPatch, .55), lit);
            col = gc;
            dist = tg;
            // The Cub's shadow on the far pavement.
            col = mix(col, col * .8, cov(length((g.xz - vec2(${u(i.cub)}, ZC + 1.6)) * vec2(2.4, 1.)) - .9, .25) * far);
          }
        }
        // The Cub, parked on the far pavement against a shop front: in front of whatever the ray met behind it.
        vec3 sC = atX(ro, rd, ${u(i.cub)});
        if (rd.x > 0. && sC.z > 0. && sC.z < dist + .5) {
          vec3 cc;
          float a = cub(vec2(sC.y - (ZC + 1.6), sC.x - G - ${u(i.kerb)}), w * sC.z, cc);
          col = mix(col, cc, a);
        }
        // Haze: the far planes take the late light.
        col = mix(col, uHaze, smoothstep(6., 22., dist) * .2);
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
        vec3 sT = atX(ro, rd, ${u(i.tree)});
        if (sT.z > 0.) {
          float tz = ${u(n)} + ZC, bend = .25 * smoothstep(G, G + 6., sT.x);
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
      #undef B`,toneMapped:!1})}var St=`
uniform vec3 uFace, uGlow, uRim, uWall;
uniform float uRimI, uHaloA, uHaloW; // uTime comes with STREET
// d: hole SDF at this pixel (> 0 on the face), fw: its change per pixel. Cast blocks vary ±6% in
// tone, 8 mm mortar joints sit 20% darker, and the street light halates 1.2 cm into the face
// (world units, so the glow keeps its size at every distance; the passage widens it as the camera nears
// a hole, the eye adjusting to the light).
// The Blender concrete (walls.js; live only, faded in by uWallK): each block takes its own corner of the map (cast one
// by one), its tone relief and its relief under the room's light from above; the silhouette's token holds.
vec3 faceColor(vec2 c, vec3 P, float d, float fw){
  vec2 id = floor((P.zy - vec2(uZ0, uY0)) / uB);
  vec3 col = uFace * (0.94 + 0.12 * h21(id));
  if (uWallK > 0.) {
    vec4 w = wallTap(uWCo, P.zy / ${at.block.toFixed(1)} + h22(id), .5);
    col *= mix(1., wallRel(w.b, .8) * wallFill(wallN(vec3(-1., 0., 0.), w.rg, 1.), vec3(-1., 0., 0.), vec3(-.406, .914, 0.), .8), uWallK);
  }
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
`,Ct=`varying vec3 vP; varying vec3 vN;
void main(){
  mat4 m = modelMatrix;
  #ifdef USE_INSTANCING
  m = m * instanceMatrix;
  #endif
  vec4 w = m * vec4(position, 1.);
  vP = w.xyz; vN = normalize(mat3(m) * normal);
  gl_Position = projectionMatrix * viewMatrix * w;
}`,wt=`
varying vec3 vP; varying vec3 vN;
${ft}
${lt}
${L}
${D}
${De}
${yt}
${He([`Co`])}
${St}
`;function Tt(e,t){let n=new g({uniforms:e,vertexShader:Ct,fragmentShader:`
      ${wt}
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
      }`,toneMapped:!1}),r=new c(t.z1-t.z0,t.y1-t.y0),i=new l(r,n);return i.rotation.y=-Math.PI/2,i.position.set(t.sx,(t.y0+t.y1)/2,(t.z0+t.z1)/2),i}function Et(){let e=F/2,{r:t,pipAt:n,pipR:i}=E,a=new r().moveTo(-e,-e).lineTo(e,-e).lineTo(e,e).lineTo(-e,e).closePath(),o=new u;for(let e=0;e<40;e++){let n=-e/40*Math.PI*2,r=2*t*Math.max(Math.cos(n),Math.cos(n-Math.PI/2),Math.cos(n-Math.PI),Math.cos(n+Math.PI/2));e?o.lineTo(r*Math.cos(n),r*Math.sin(n)):o.moveTo(r*Math.cos(n),r*Math.sin(n))}o.closePath(),a.holes.push(o);for(let[e,t]of[[1,1],[-1,1],[-1,-1],[1,-1]])a.holes.push(new u().absarc(e*n,t*n,i,0,Math.PI*2,!0));let s=new _(a,{depth:M,bevelEnabled:!1,curveSegments:8}).rotateY(Math.PI/2),c=s.getAttribute(`normal`),l=[];for(let e=0;e<c.count;e+=3)c.getX(e)<.5&&l.push(e,e+1,e+2);return s.setIndex(l),s}function Dt(e,t){let n=new g({uniforms:e,vertexShader:Ct,fragmentShader:`
      ${wt}
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
      }`,toneMapped:!1}),r=Math.round((t.z1-t.z0)/F),i=Math.round((t.y1-t.y0)/F),a=new f(Et(),n,r*i),o=new p,s=0;for(let e=0;e<r;e++)for(let n=0;n<i;n++)a.setMatrixAt(s++,o.makeTranslation(t.sx,t.y0+(n+.5)*F,t.z0+(e+.5)*F));return a.instanceMatrix.needsUpdate=!0,a.computeBoundingSphere(),a}var Ot=(e,t,n)=>n>=2?Dt(e,t):Tt(e,t);function V(e,t,n){let r=F/2;return Tt(e,{sx:t.sx,z0:n.z-r,z1:n.z+r,y0:n.y-r,y1:n.y+r})}function kt(e,t){let n=Math.round((e.y1-e.y0)/F);return Math.floor((t.z-e.z0)/F)*n+Math.floor((t.y-e.y0)/F)}var H={min:[-6,0,-4],max:[2.6,3.3,4]},U={sx:2.6,z0:-4,z1:1.4,y0:.9,y1:3.3},At=new s(2.6,1.05,-1.15),W={portrait:new s(2.6,1.05,-3.25)},G=e=>W[e]??At,jt={min:[-3.5,.05,-3.9],max:[2.5,3.2,2]},Mt={portrait:{pos:[.83,1.1,-2.93],look:[1.68,.91,-3.43],fov:49.6},tablet:{pos:[-1.3,1.1,3.15],look:[1.2,1.95,-3.6],fov:54},landscape:{pos:[-1.5,1.1,3.3],look:[.5,1.78,-4],fov:46},wide:{pos:[-1.5,1.1,3.3],look:[.5,1.78,-4],fov:44}},Nt={silhouette:(e=>[new s(e.sx,e.y0,e.z0),new s(e.sx,e.y1,e.z0),new s(e.sx,e.y1,e.z1),new s(e.sx,e.y0,e.z1)])(U),floor:[new s(H.min[0],0,H.min[2]),new s(H.max[0],0,H.min[2]),new s(H.max[0],0,H.max[2]),new s(H.min[0],0,H.max[2])],heroBlock:At};function Pt(){let[e,t,n]=H.min,[r,i,a]=H.max,o=(e+r)/2,s=r-e,l=a-n,u=i-t,f=[[s,u,[0,0,0],[o,u/2,n],0],[l,u,[0,Math.PI/2,0],[e,u/2,(n+a)/2],0],[s,l,[-Math.PI/2,0,0],[o,t,(n+a)/2],1],[s,l,[Math.PI/2,0,0],[o,i,(n+a)/2],2],[U.z1-U.z0,U.y0,[0,-Math.PI/2,0],[r,U.y0/2,(U.z0+U.z1)/2],0],[a-U.z1,u,[0,-Math.PI/2,0],[r,u/2,(U.z1+a)/2],0],[s,u,[0,Math.PI,0],[o,u/2,a],0]],m=[],g=[],_=[],y=[],b=new p;for(let[e,t,n,r,i]of f){let a=new c(e,t);b.makeRotationFromEuler(new d(...n)).setPosition(...r),a.applyMatrix4(b);let o=m.length/3;m.push(...a.getAttribute(`position`).array),g.push(...a.getAttribute(`normal`).array);for(let e=0;e<a.getAttribute(`position`).count;e++)_.push(i);y.push(...Array.from(a.index.array,e=>e+o)),a.dispose()}let x=new h;return x.setAttribute(`position`,new v(m,3)),x.setAttribute(`normal`,new v(g,3)),x.setAttribute(`aKind`,new v(_,1)),x.setIndex(y),x}async function Ft({gl:e,tier:t,slice:n=!0,shaftSteps:r=null}){let[i,a]=await Promise.all([Ue({slice:n}),Ge({slice:n})]),c=fe();We(`room`,Le.room,c);let u=[A(`wallShade`),A(`floorShade`),A(`ceiling`)].map(Te),d=[A(`wallSun`),A(`wallSun`).multiplyScalar(.8),A(`wallSun`)].map(Te),f=A(`sunRoom`),m=Math.max(c.dir.z,.05),h=c.dir,g={...mt(U,h),...qe(),...bt(),...ot(),uShade:{value:u},uSunT:{value:d.map(e=>e.clone())},uNdlRef:{value:m},uGlare:{value:Te(A(`sky`))},uSunCol:{value:new s},uSunI:{value:1},uRimI:{value:1},uHaloA:{value:.35},uHaloW:{value:.012},uBoxMin:{value:new s(...H.min)},uBoxMax:{value:new s(...H.max)},uLime:{value:i},uTerr:{value:a},uFace:{value:A(`screenFace`)},uGlow:{value:A(`sky`)},uRim:{value:I(A(`wallSun`),A(`sky`),.3)},uWall:{value:I(A(`screenFace`),A(`sky`),.38)},uTime:{value:0},uLeafAmt:{value:0},uWind:{value:new s},uStir:{value:0},uMotesA:{value:1},uPx:{value:1},uMoteCol:{value:Te(I(A(`sky`),A(`sun-200`),.6).convertLinearToSRGB())}},_=new o,v=new l(Pt(),gt(g)),y=Ot(g,U,1),x=z(g,{x:9,z:-1}),S=vt(g,jt);_.add(v,y,x,S.points);let C=tt(e.renderer,g,{box:H,tier:t,ortho:e.ortho,steps:r}),w=new b(46,1,.05,60),ee=[],T=e=>{if(e>=2!=!!y.isInstancedMesh){_.remove(y),y.geometry.dispose(),y.material.dispose(),y=Ot(g,U,e),_.add(y);for(let e of ee)_.remove(e),e.geometry.dispose(),e.material.dispose();if(ee=[],y.isInstancedMesh){for(let e of[At,...Object.values(W)])y.setMatrixAt(kt(U,e),new p().makeScale(0,0,0)),ee.push(V(g,U,e));y.instanceMatrix.needsUpdate=!0,_.add(...ee)}}x.visible=e>=2,S.setTier(e),C.setTier(e)};T(t);let te=({t:e=Le.room,nudge:t=null,intensity:n=1,rims:r=n}={})=>{We(`room`,e,c,t);let i=c.color.r/f.r,a=c.color.g/f.g,o=c.color.b/f.b;for(let e=0;e<3;e++)g.uSunT.value[e].set(d[e].x*i,d[e].y*a,d[e].z*o);g.uSunCol.value.set(c.color.r,c.color.g,c.color.b),g.uSunI.value=n,g.uRimI.value=r};return te(),{scene:_,camera:w,U:g,shafts:C,motes:S,setTier:T,setSun:te,setShot:(e,t,n,r=Mt[e.name])=>{w.position.set(...r.pos),w.fov=r.fov,w.aspect=t/n,w.lookAt(...r.look),w.clearViewOffset(),w.updateProjectionMatrix(),w.updateMatrixWorld()},textures:{lime:i,terr:a},compile:async()=>{await e.renderer.compileAsync(_,w);for(let t of C.scenes)await e.renderer.compileAsync(t,e.ortho)},dispose(){_.traverse(e=>{(e.isMesh||e.isPoints)&&(e.geometry.dispose(),e.material.dispose())}),C.dispose(),i.dispose(),a.dispose()}}}var It={portrait:{pos:[1.52,1.1,-3.05],fov:56},tablet:{pos:[.3,1.1,.4],fov:50},landscape:{pos:[.2,1.1,.6],fov:42},wide:{pos:[.2,1.1,.6],fov:40}},Lt={portrait:{dist:.5,fov:56},tablet:{dist:.42,fov:50},landscape:{dist:.35,fov:42},wide:{dist:.35,fov:40}},Rt={portrait:[20,8],tablet:[28,14]},K=e=>Rt[e]??[32,R.yaw],zt={portrait:2.2},Bt=e=>zt[e]??.8,q=6,Vt=(e,t,n)=>{let r=m.clamp((n-e)/(t-e),0,1);return r*r*(3-2*r)},J=e=>.5-.5*Math.cos(Math.PI*m.clamp(e,0,1)),Ht=e=>Math.sin(m.clamp(e,0,1)*Math.PI/2);function Y(e){let t=Math.tan(m.degToRad(e.fov/2))/e.zoom,n=2*t,r=e.aspect*n,i=-r/2,a=t,o=e.view;return o?.enabled&&(i+=o.offsetX*r/o.fullWidth,a-=o.offsetY*n/o.fullHeight,r*=o.width/o.fullWidth,n*=o.height/o.fullHeight),{l:i,r:i+r,t:a,b:a-n}}var Ut=(e,t,n)=>{let r=Math.tan(m.degToRad(e/2));return Object.assign(n,{l:-r*t,r:r*t,t:r,b:-r})};function X(e,t,n,r){let i=e.near,a=n*(t.t-t.b)/r;e.projectionMatrix.makePerspective(t.l*i,t.r*i,(t.t-a)*i,(t.b-a)*i,i,e.far),e.projectionMatrixInverse.copy(e.projectionMatrix).invert()}var Wt=(e,t)=>{let n=t.clone().sub(e).normalize();return[Math.atan2(-n.x,-n.z),Math.asin(m.clamp(n.y,-1,1))]},Gt=(e,t,n)=>{let r=t-e;for(;r>Math.PI;)r-=2*Math.PI;for(;r<-Math.PI;)r+=2*Math.PI;return e+r*n};function Kt(e,t,n,r,i,a){let o=i*i,s=o*i;for(let c of[`x`,`y`,`z`])a[c]=.5*(2*t[c]+(n[c]-e[c])*i+(2*e[c]-5*t[c]+4*n[c]-r[c])*o+(3*t[c]-e[c]-3*n[c]+r[c])*s);return a}function qt(){let e={pos:new s,yaw:0,pitch:0,fov:46,bounds:{l:-1,r:1,t:1,b:-1}},t=new s,n=new s,r=`landscape`,i=1,a=1,o=G(`landscape`),c=0,l=0,u=new s,f=new d(0,0,0,`YXZ`),p=new y,h={l:0,r:0,t:0,b:0},g={l:0,r:0,t:0,b:0},_=new s,v=(u,d,f)=>{r=d,i=f.w/f.h,a=f.h,e.pos.copy(u.position);let p=new s(0,0,-1).applyQuaternion(u.quaternion);e.yaw=Math.atan2(-p.x,-p.z),e.pitch=Math.asin(m.clamp(p.y,-1,1)),e.fov=u.fov,Object.assign(e.bounds,Y(u)),o=G(r),t.set(...It[r].pos),_.copy(t).sub(o).setY(0).normalize().lerp(new s(-1,0,0),.35).normalize(),n.copy(o).addScaledVector(_,Lt[r].dist).setY(1.1),[c,l]=Wt(t,o)},b=(s,d,_=0)=>{d<=1?Kt(e.pos,e.pos,t,n,d,u):Kt(e.pos,t,n,n,d-1,u),s.position.copy(u);let v,y,b;if(d<=1){let t=J(d),[n,i]=Wt(u,o),a=Vt(.6,1,d);v=Gt(e.yaw,Gt(c,n,a),t),y=m.lerp(e.pitch,m.lerp(l,i,a),Vt(.45,1,d)),b=m.lerp(e.fov,It[r].fov,t)}else[v,y]=Wt(u,o),b=m.lerp(It[r].fov,Lt[r].fov,d-1);s.quaternion.copy(p.setFromEuler(f.set(y,v,0))),s.fov=b,s.updateMatrixWorld();let x=Vt(0,.7,d);Ut(b,i,g);for(let t of[`l`,`r`,`t`,`b`])h[t]=m.lerp(e.bounds[t],g[t],x);return X(s,h,_,a),1-x},x=(e,t)=>{let n=Ht(t);e.position.lerpVectors(R.from,R.to,n),e.fov=R.fov[r],e.aspect=i;let[o,s]=K(r);e.lookAt(Ie(e.position,m.lerp(o,s,n))),e.updateMatrixWorld(),X(e,Ut(e.fov,i,g),0,a)},C=null;return{setRest:v,room:b,exit:x,solve:(e,{heroH:t,approach:n,rects:i,shapes:a,view:o,top:s=0})=>{let c=t+n,l=Math.ceil(c/q)+1,u=t+.6*n,d=e=>e<=t?m.clamp(e/t,0,1)**1.3:1+J((e-t)/n),f=i.filter(e=>e.kind!==`opaque`&&!e.pinned),p=(t,n,r)=>{b(e,t,n);let i=S(a.silhouette,e,o.w,o.h),c=S(a.floor,e,o.w,o.h);for(let e of f){let t=e.y-r,n=r>80?s:0;if(t+e.h<=n||t>=o.h)continue;let a={x:e.x,y:Math.max(t,n),w:e.w,h:Math.min(t+e.h,o.h)-Math.max(t,n)};if(re(i,0,0,a)||e.kind===`body`&&re(c,0,0,a))return!1}return!0},h=(e,t,n)=>{for(let r=0;r<10;r++){let r=(t+n)/2;e(r)?n=r:t=r}return n},g=(e,t,n)=>p(e,t,n)?e:p(0,t,n)?e-h(r=>p(e-r,t,n),0,e):0,v=[];for(let e=0;e<l;e++){let n=Math.min(e*q,c),r=d(n),i=n<u?n:0,a=r,o=0;n<t&&!p(r,0,n)&&(p(0,0,n)?a=g(r,0,n):(o=i>0&&p(0,i,n)?h(e=>p(0,e,n),0,i):i,a=g(r,o,n))),v.push({y:n,s:a,lift:o,need:o})}for(let e=1;e<l;e++)v[e].lift=Math.max(v[e].need,v[e-1].lift-Bt(r)*q);for(let e=l-2;e>=0;e--)v[e].lift=Math.min(v[e].y,Math.max(v[e].lift,v[e+1].lift-q));for(let e of v)e.y>=u&&(e.lift=0);for(let e of v)e.y>=t||e.lift===e.need||p(e.s,e.lift,e.y)||(e.s=p(0,e.lift,e.y)?e.s-h(t=>p(e.s-t,e.lift,e.y),0,e.s):0);for(let e=l-2;e>=0;e--)v[e].s=Math.min(v[e].s,v[e+1].s);let y=m.degToRad(40)*q/(.6*o.h),x=t=>(b(e,t,0),_.set(0,0,-1).applyQuaternion(e.quaternion),Math.atan2(-_.x,-_.z)),w=x(v[0].s);for(let e=1;e<l;e++){let t=v[e-1].s,n=e=>Math.abs(Gt(0,x(e)-w,1));v[e].s>t&&n(v[e].s)>y&&(v[e].s=t+(v[e].s-t)*(1-h(r=>n(v[e].s-(v[e].s-t)*r)<=y,0,1))),w=x(v[e].s)}return C={rows:v,end:c},{rows:l,held:v.filter(e=>e.s<d(e.y)-.001).length,carried:v.filter(e=>e.lift>0).length}},at:(e,t)=>{if(!C)return Object.assign(t,{s:0,lift:0});let{rows:n}=C,r=m.clamp(e/q,0,n.length-1),i=Math.floor(r),a=Math.min(i+1,n.length-1),o=r-i;return t.s=m.lerp(n[i].s,n[a].s,o),t.lift=m.lerp(n[i].lift,n[a].lift,o),t},get bucket(){return r},get table(){return C}}}function Jt(e,{hero:t,passage:n},r){return r.hero=m.clamp((e-t.top)/t.len,0,1),r.passage=m.clamp((e-n.top)/n.len,0,1),r.y=e-t.top,r}var Z=e=>{let t=m.clamp(e,0,1);return t*t*(3-2*t)},Yt=Math.tan(m.degToRad(3));function Xt(){let e=new s,t=new s,n={pos:[0,0,0],yaw:0,fov:0},r={l:0,r:0,t:0,b:0},i={l:0,r:0,t:0,b:0},a=`side`,o=null,c=1,l=1,u=`landscape`,d=null;return{setLayout:({bucket:e,view:t,free:n,name:r,corridor:i})=>{u=e,a=r,d=i,c=t.w/t.h,l=t.h;let s=N(1,a,d),f=d.doors[0]?.zc??s.pos[2]-P[a].d,[p,m]=P[a].at,h=(n.x0+p*(n.x1-n.x0))/t.w,g=(n.y0+m*(n.y1-n.y0))/t.h;o=Xe(s.pos,s.yaw,[Ke.wallX,Re,f],s.fov,c,h,g)},pose:(s,f,p,h,g=0,_=0,v=0)=>{let y=N(ve(p,h)+ht(g),a,d,n),b=Z(f),x=m.degToRad(m.lerp(K(u)[1],y.yaw,b)),S=m.lerp(R.fov[u],y.fov,b);e.set(m.lerp(R.to.x,y.pos[0],b),1.1,m.lerp(R.to.z,y.pos[2],b)),s.position.copy(e),s.fov=S,s.aspect=c;let C=m.degToRad(_),w=Math.cos(C)*10;s.lookAt(t.set(e.x+Math.sin(x)*w,1.1-Math.sin(C)*10,e.z-Math.cos(x)*w)),s.updateMatrixWorld(),Ut(S,c,r);let ee=Math.tan(m.degToRad(S/2))/Math.tan(m.degToRad(y.fov/2));for(let e of[`l`,`r`,`t`,`b`])i[e]=m.lerp(r[e],o[e]*ee,b);if(a===`side`){let e=Yt*O(p,h)*b;i.t-=e,i.b-=e}X(s,i,v,l)},get layout(){return a},get lens(){return o}}}var Zt=6,Qt=9,$t=2.5,en=2.5,tn=A(`lime-100`),nn=A(`wash`),rn={dip:.82,down:1600,up:2400,min:14e3,max:22e3,first:[3500,5e3]},an={rest:Le.room,heroOut:.14,hole:.2,corridor:dt.sun[0]},on={truck:.05,yaw:.8*Math.PI/180,pitch:.4*Math.PI/180},sn={haloA:[.35,.95],haloW:[.012,.06],density:[1.6,2.8]},cn=1.8,ln={radius:.62,held:.1,peak:.55},un=k(j.inOut),dn=()=>new Promise(e=>requestAnimationFrame(e)),fn=(e,t,n)=>e+(t-e)*n,Q=e=>Math.min(Math.max(e,0),1);async function pn({canvas:r,stage:i,bus:o,probed:c,forced:l}){let u=document.documentElement,f=new URLSearchParams(location.search).get(`debug`),p=c.tier,m=Me(r,{context:c.gl,tier:p}),h=await Ft({gl:m,tier:p});m.track(`baked`,le);let g=i?.closest(`[data-world]`)??document.querySelector(`[data-world="room"]`),_=document.querySelector(`[data-world="passage"]`),v=document.querySelector(`[data-world="corridor"]`),b=document.querySelector(`[data-world="shutters"]`),S=h.camera,re=qt(),fe=Xt(),E=null,D=null,O={x:0,y:0,w:1,h:1},k={w:1,h:1},ve={x:0,y:0,w:1,h:1},A=new s,Te=new y,De=new d(0,0,0,`YXZ`),j={hero:{top:0,len:1},passage:{top:0,len:1},corridor:{top:0,len:1,pre:1,next:0},shutters:{from:0,top:0,len:1}},M=null,N=!0,Ie=1,Re=null,P=!1,F=`landscape`,He=Ce(document.querySelectorAll(`[data-beat]`)),Ue=ut(He),We=(e,t,n)=>{n&&(e||t)&&(A.set(1,0,0).applyQuaternion(S.quaternion),S.position.addScaledVector(A,e*on.truck*Ie*n),S.quaternion.multiply(Te.setFromEuler(De.set(t*on.pitch*Ie*n,e*on.yaw*Ie*n,0))),S.updateMatrixWorld())},Ge=()=>{Object.assign(O,T(g)??{x:0,y:0,w:innerWidth,h:innerHeight});let a=r.clientWidth||innerWidth,o=r.clientHeight||innerHeight;m.resize(a,o);let{dpr:s}=m.size;h.shafts.setSize(Math.round(a*s),Math.round(o*s)),E?.shafts.setSize(Math.round(a*s),Math.round(o*s)),D?.shafts.setSize(Math.round(a*s),Math.round(o*s)),D&&(D.U.uPx.value=s),k.w=a,k.h=o;let c=t(u.clientWidth||a,u.clientHeight||o),[l,d]=c.view,p=Math.max(O.w/l,O.h/d),v=x(O,c.view,p);Object.assign(ve,{x:O.x+v.x,y:O.y+v.y,w:l*p,h:d*p}),h.setShot(c,l,d);let y=ae(),b=ee(w(y,O,c.view),c.name);M=C({camera:S,width:l,height:d,rects:b,...Nt,stage:i?w(T(i),O,c.view):null});let oe=e[c.name],se=oe&&te(M,oe.sx,oe.sy);se&&Object.assign(M,{sx:oe.sx,sy:oe.sy,still:!0});let ce=S.quaternion.clone(),le=S.position.clone();Ie=0;for(let e of[1,.5,.25]){if(Ie=e,[[-1,-1],[1,-1],[-1,1],[1,1]].every(([e,t])=>(S.position.copy(le),S.quaternion.copy(ce),We(e,t,1),ne(M,S,Nt,l,d))))break;Ie=0}S.position.copy(le),S.quaternion.copy(ce),S.updateMatrixWorld(),ie(S,ve,k,{sx:M.sx*p,sy:M.sy*p}),re.setRest(S,c.name,k);let ue=se&&oe.caps?oe.caps.map(e=>({...e,x:ve.x+e.x*p,y:ve.y+e.y*p,w:e.w*p,h:e.h*p})):y;ze(h.U,ue,{h:o,dpr:s}),h.U.uPx.value=s,j.hero.top=O.y,j.hero.len=Math.max(O.h,1);let de=T(_),fe=_?.querySelector(`.passage__frame`)?.offsetHeight??o;j.passage.top=de?de.y:O.y+O.h,j.passage.len=de?Math.max(de.h-(getComputedStyle(_.querySelector(`.passage__frame`)??_).position===`sticky`?fe:0),1):o*1.8,Re=re.solve(S,{heroH:j.passage.top,approach:ct.approach[1]*j.passage.len,rects:ae(g),shapes:Nt,view:k,top:parseFloat(getComputedStyle(u).getPropertyValue(innerWidth>=1024?`--header-h-compact`:`--header-h`))||0}),f===`frame`&&n(()=>import(`./frame-CQsXUThy.js`).then(e=>e.o).then(e=>e.drawFrameOverlay(M,ve,k,p)),__vite__mapDeps([0,1])),F=c.name,Ke(),Je(),N=!0},Ke=()=>{let e=v?.querySelector(`.bays__frame`),t=v?.querySelector(`.bays__panel`);if(P=!!(e&&t&&u.classList.contains(`stages`)&&v.classList.contains(`is-stage`)),!P)return;let n=T(v.querySelector(`[data-stage-track]`)??v),r=e.getBoundingClientRect(),i=t.getBoundingClientRect(),a=r.height||k.h,o=v.nextElementSibling,s=o?T(o).y:n.y+n.h+k.h;Object.assign(j.corridor,{top:n.y,len:Math.max(n.h-a,1),pre:a,next:s});let c=parseFloat(getComputedStyle(u).getPropertyValue(innerWidth>=1024?`--header-h-compact`:`--header-h`))||0,l=i.left-r.left>r.width*.3,d=l?{x0:0,x1:i.left-r.left,y0:c,y1:a}:{x0:0,x1:r.width,y0:c,y1:i.top-r.top};fe.setLayout({bucket:F,view:k,name:l?`side`:`below`,corridor:Ue,free:d})},qe=!1,Je=()=>{let e=b?.querySelector(`[data-stage-track]`),t=b?.querySelector(`.shutters__frame`);if(qe=!!(e&&t&&getComputedStyle(t).position===`sticky`),!qe)return;let n=T(b),r=T(e),i=t.offsetHeight||k.h;Object.assign(j.shutters,{from:n.y-k.h,top:r.y,len:Math.max(r.h-i,1)})},Xe=e=>qe&&e>=j.shutters.from,I={on:!1,v:0},L={peak:0,held:0},Qe=new a,tt=new s,R=new a,at=new a,lt=new a,ft=nn.clone(),mt={pos:[0,0,0],look:[0,0,0],fov:0},ht=-1,gt=-1,_t=null,vt=matchMedia(`(pointer: fine)`).matches,yt={x:0,v:0},bt={x:0,v:0},z={x:0,y:0},xt={az:0,el:0},B={level:1,from:1,to:1,start:0,dur:0,next:0},St=0,Ct=0,wt=h.U.uWind.value,Tt=e=>e+rn.min+Math.random()*(rn.max-rn.min),Et=(e,t,n)=>Object.assign(B,{from:B.level,to:t,start:e,dur:n}),Dt=e=>{if(vt)return!1;if(B.next||=e+rn.first[0]+Math.random()*(rn.first[1]-rn.first[0]),B.dur){let t=Math.min((e-B.start)/B.dur,1);return B.level=B.from+(B.to-B.from)*un(t),t>=1&&(B.dur=0,B.to<1?Et(e,1,rn.up):B.next=Tt(e)),!0}return e>=B.next&&(Et(e,rn.dip,rn.down),!0)},Ot={t:Le.room,nudge:xt,intensity:1,rims:1},V={hero:0,passage:0,y:0,approach:0,stage:0,out:0},kt={s:0,lift:0},H={x:scrollY,v:0},U=0,At=-1,W=!0,G=!1,jt=!1,Mt=-1,Pt=0,It=1,Lt=e=>J||!Ut?e:H.x,Rt=e=>{let t=scrollY;At=t,Jt(t,j,V);let n=V.passage,r=Ne(n),i=Se(n);G=Xe(t),W=!G&&i===`room`;let a=null,o=S,s=[],c=null,l=null;if(G){let e=j.shutters,n=Lt(t),i=Q((n-e.from)/Math.max(e.top-e.from,1)),l=Q((t-e.top)/e.len),u=Q((n-e.top)/e.len);if(V.s3=l,!jt&&_t==null&&(I.on=l>=pt.flood,I.v=+!!I.on),r=he(l),D&&r<1){let e=ke(i),t=pe(i)/pt.leafDeg;i+u!==ht&&(D.pose(t*pt.leafDeg,e),ht=i+u);let n=ye(i+l);Math.abs(n-gt)>1e-4&&(D.setSun(n),gt=n),ge(I.v,L);let r=we(i,L),d=Oe(i,e);D.setExposure(r,d,Math.max(e/pt.slatDeg,t)),D.shafts.uniforms.uDensity.value=cn*((1+.6*t)*(1-.75*t)+.6*L.peak),D.shafts.uniforms.uExposure.value=r,D.motes.uniforms.uBurst.value=L.peak,D.U.uMotesA.value=.55+.45*d,D.U.uTime.value=Ct,D.U.uStir.value=St,D.sway(Ct);let f=D.camera;_e(i,u,F,be,mt),f.position.set(...mt.pos),f.fov=mt.fov,f.aspect=k.w/k.h,f.lookAt(mt.look[0],mt.look[1],mt.look[2]),f.updateMatrixWorld(),f.updateProjectionMatrix(),D.project(f,tt),Qe.set(tt.x*.5+.5,tt.y*.5+.5,ln.radius,ln.held*L.held+ln.peak*L.peak),c=Qe,a=D.scene,o=f,s=D.passes}else D||(r=1)}else if(W){re.at(t,kt);let e=re.room(S,kt.s,kt.lift);We(yt.x,bt.x,e),xt.az=-yt.x*Fe.az*e,xt.el=bt.x*Fe.el*e,Ot.t=V.passage>0?fn(an.heroOut,an.hole,Q(n/ct.approach[1])):fn(an.rest,an.heroOut,V.hero),Ot.intensity=Ot.rims=fn(1,B.level,e),h.setSun(Ot);let r=Q((kt.s-1.3)/.7);h.U.uHaloA.value=fn(sn.haloA[0],sn.haloA[1],r),h.U.uHaloW.value=fn(sn.haloW[0],sn.haloW[1],r*r),h.shafts.uniforms.uDensity.value=fn(sn.density[0],sn.density[1],r),h.U.uCapShift.value.set(0,t*m.size.dpr),h.U.uTime.value=Ct;let i=Math.min(Ct/en,1);h.U.uLeafAmt.value=i*i*(3-2*i),h.U.uStir.value=St,a=h.scene,s=[h.shafts]}else if(E){let e=Q((n-ct.exit[0])/(ct.exit[1]-ct.exit[0])),i=E.specs.length,c=j.corridor,u=Lt(t);V.approach=P?Q((u-(c.top-c.pre))/c.pre):0,V.stage=P?Q((u-c.top)/c.len):0;let d=Pe(c.top+c.len,c.next,k.w,k.h),f=c.top+dt.release*c.len;V.out=P?Q((u-f)/(d.y0+d.len-f)):0;let m=P?Q((t-d.y0)/d.len):0;P&&e>=1?fe.pose(E.camera,V.approach,V.stage,i,V.out,me(V.stage),Math.max(t-d.y0,0)):re.exit(E.camera,e),m>0?(r=un(m),l=tn,at.set(1,Ae.feather,Q(1-(c.next-t-28)/k.h),0)):at.x=0,P&&Ze(c.next-t,k.w,k.h,lt)&&(l=tn);for(let e=0;e<i;e++)E.setDoor(e,P?je(e,V.stage,i):96);J||(Pt=p>=2?Ct+t/300:t/300),E.animate(Pt),E.sway(Pt),It=r;let h=fn(dt.sun[0],dt.sun[1],V.stage);Math.abs(h-Mt)>1e-4&&(E.setSun(h),Mt=h),a=E.scene,o=E.camera,s=[E.shafts]}jt=G;let u=.3*(t-j.passage.top)/k.h;if(R.set(!G&&n>0&&n<1?r:0,.25*Math.sin(u*2.1),-11*u,0),X?.begin(),a)m.frame(a,o,s,r,c,R,l,!G&&!W?at:null,!G&&!W?lt:null);else if(G)m.frame(h.scene,S,[],1,null,null);else{let e=0,n=!1;if(P&&!W&&j.corridor.next>0){let r=j.corridor,i=Pe(r.top+r.len,r.next,k.w,k.h);e=un(Q((t-i.y0)/i.len)),n=Ze(r.next-t,k.w,k.h,lt)}ft.copy(nn).lerp(tn,e),m.frame(h.scene,S,[],1,null,R.setX(1-e),ft,null,n?lt:null,tn)}X?.end(e)},K=0,zt=0,Bt=0,q=!0,Vt=!document.hidden,J=!!o?.state.paused,Ht=!1,Y=!1,Ut=!1,X=null,Wt=!1,Gt=ot(),Kt=new AbortController,Z=null,Yt=null,pn=it(),mn=()=>m.memoryMB,hn=null,gn=()=>hn||(hn=ce({tier:p,width:innerWidth,cw:Math.round(k.w*m.size.dpr),ch:Math.round(k.h*m.size.dpr),msaa:m.msaa}),m.limit(oe({tier:p,msaa:m.msaa,textures:hn.textures})),hn),_n=null,vn=()=>Ut&&q&&Vt&&!Ht&&!Y,$=()=>{!K&&vn()&&(K=requestAnimationFrame(bn))},yn=()=>navigator.scheduling?.isInputPending?.()??!1;function bn(e){if(K=0,!vn())return;if(yn()){K=requestAnimationFrame(bn);return}let t=zt?Math.min((e-zt)/1e3,.1):1/60;zt=e;let n=N||scrollY!==At,r=U;if(U=e,J||Math.abs(scrollY-H.x)>$t*k.h)H.x=scrollY,H.v=0;else if(H.x!==scrollY){let i=Math.min((e-(r||e))/1e3,1)||t,a=H.x-scrollY,o=H.v+Qt*a,s=Math.exp(-9*i);H.x=scrollY+(a+o*i)*s,H.v=(o-Qt*(a+o*i))*s,Math.abs(scrollY-H.x)<.5&&Math.abs(H.v)<8&&(H.x=scrollY,H.v=0),n=!0}let i=Xe(scrollY)&&D&&he(V.s3??0)<1,a=!W&&!G&&E&&p>=2&&Ne(V.passage)<1&&It<.999&&(jn.get(v)||jn.get(_)),o=!J&&!Wt&&(W&&V.y<=1||i||a);if(Xe(scrollY)){let e=j.shutters;_t==null&&Ee(I,Q((scrollY-e.top)/e.len),t,J)&&(n=!0)}if(!J){Z&&Z.k<1&&(Z.k=Math.min(Z.k+t*1e3/700,1),Gt.uWallK.value=un(Z.k),n=!0,Z.k>=1&&Rn()),_n&&_n.k<1&&(_n.k=Math.min(_n.k+t*1e3/700,1),pn.uPosterK.value=un(_n.k),n=!0),vt&&(n=xe(yt,z.x,t,Zt)|xe(bt,z.y,t,Zt)||n),o&&W&&(n=Dt(e)||n);let r=i?D.U.uWind.value:W?wt:null;r&&Math.abs(St)>.001&&(r.y+=St*t*.12,r.x-=St*t*.05,(i?D:h).motes.wrap(r),n=!0),Ct+=t}let s=!J&&X?.measuring;(n||s||o&&e-Bt>=33&&!m.busy())&&(Rt(e),Bt=e,N=!1),(!J&&o||s||N||n)&&(K=requestAnimationFrame(bn))}let xn=e=>{e.pointerType!==`mouse`||J||(z.x=Math.max(-1,Math.min(1,e.clientX/innerWidth*2-1)),z.y=Math.max(-1,Math.min(1,1-e.clientY/innerHeight*2)),$())},Sn=()=>{z.x=z.y=0,$()},Cn=e=>{let t=e.detail?.value??0;St=J?0:t,St&&$()},wn=()=>$(),Tn=new MutationObserver(()=>{Wt=!!document.querySelector(`dialog[open]`),N=!0,$()});document.querySelectorAll(`dialog`).forEach(e=>Tn.observe(e,{attributes:!0,attributeFilter:[`open`]}));let En=()=>{Vt=!document.hidden,zt=0,X?.gap(),$()},Dn=0,On=()=>{cancelAnimationFrame(Dn),Dn=requestAnimationFrame(()=>{Ge(),$()})};vt&&addEventListener(`pointermove`,xn,{passive:!0}),vt&&document.documentElement.addEventListener(`mouseleave`,Sn),o?.addEventListener(`velocity`,Cn),addEventListener(`scroll`,wn,{passive:!0}),document.addEventListener(`visibilitychange`,En),addEventListener(`resize`,On);let kn=new ResizeObserver(On);kn.observe(g??r),_&&kn.observe(_),v&&kn.observe(v),b&&kn.observe(b);let An=v?new MutationObserver(()=>{Ke(),N=!0,$()}):null;An?.observe(v,{attributes:!0,attributeFilter:[`class`]});let jn=new Map,Mn=new IntersectionObserver(e=>{for(let t of e)jn.set(t.target,t.isIntersecting&&(t.target!==b||qe));let t=q;q=[...jn.values()].some(Boolean),q&&!t&&vn()&&(Rt(performance.now()),N=!1),r.style.visibility=q?``:`hidden`,q||X?.gap(),zt=0,$()},{rootMargin:`10% 0px`});document.querySelectorAll(`[data-world]`).forEach(e=>Mn.observe(e));let Nn=0,Pn=0,Fn=e=>{if(e.preventDefault(),Ht=!0,cancelAnimationFrame(K),K=0,Kt.abort(),Ye(),Z=null,m.track(`walls`,0),u.classList.remove(`world-live`),++Nn>1)return zn(`context lost twice`);Pn=setTimeout(()=>zn(`context not restored`),5e3)},In=async()=>{clearTimeout(Pn),X?.dispose(),X=null,Ht=!1,Ge(),Rt(performance.now()),await dn(),!Ht&&!Y&&(u.classList.add(`world-live`),$())};r.addEventListener(`webglcontextlost`,Fn),r.addEventListener(`webglcontextrestored`,In);let Ln=({state:e,p75:t,final:n,source:r})=>{console.info(`[world] gate p75 ${t.toFixed(1)} ms (${r}) → ${e}${n?` (final)`:``}`),Yt=e;let i=()=>Promise.all([h,E,D].filter(Boolean).map(e=>e.compile())).then(()=>{N=!0,$()});if(e===`t1`)p=1,m.setTier(1),rt(1),h.setTier(1),E?.setTier(1),D?.setTier(1),Ge(),i();else if(e===`lite`){for(let e of[h,E,D])e&&(e.shafts.enabled=!1);m.setTier(1,{lite:!0}),Ge()}else e===`off`&&zn(`frame time over the Tier 1 limit`)};function Rn(){l!=null||Y||!Ut||Yt&&Yt!==`t2`&&Yt!==`t1`||X?.measuring||(X?.dispose(),X=ue(m.renderer.getContext(),{tier:p,onStep:Ln}))}function zn(e){Y||(console.info(`[world] back to stills: ${e}`),Vn.dispose())}let Bn=null,Vn={setPaused(e){J=!!e,yt.v=bt.v=0,St=0,zt=0,X?.gap(),N=!0,J||Bn?.(),$()},dispose(){Y||(Y=!0,Bn?.(),cancelAnimationFrame(K),cancelAnimationFrame(Dn),clearTimeout(Pn),Kt.abort(),Ye(),st(),removeEventListener(`pointermove`,xn),document.documentElement.removeEventListener(`mouseleave`,Sn),o?.removeEventListener(`velocity`,Cn),o?.removeEventListener(`pause`,Hn),removeEventListener(`scroll`,wn),document.removeEventListener(`visibilitychange`,En),removeEventListener(`resize`,On),kn.disconnect(),Mn.disconnect(),An?.disconnect(),Tn.disconnect(),r.removeEventListener(`webglcontextlost`,Fn),r.removeEventListener(`webglcontextrestored`,In),u.classList.remove(`world-live`),r.style.visibility=``,document.getElementById(`world-frame-debug`)?.remove(),X?.dispose(),E?.dispose(),D?.dispose(),b?.classList.remove(`is-live`),h.dispose(),m.dispose(),de(o))}},Hn=e=>Vn.setPaused(e.detail.paused);if(o?.addEventListener(`pause`,Hn),await document.fonts?.ready,Ge(),await h.compile(),await m.compile(),o&&o.state?.sunIn!==`done`&&await new Promise(e=>{let t=setTimeout(e,4e3);o.addEventListener(`sun-in-end`,()=>{clearTimeout(t),e()},{once:!0})}),J&&!Y&&await new Promise(e=>Bn=()=>{Bn=null,e()}),Y)return Vn;Ge(),Rt(performance.now());let Un=m.renderer.info.render;if(console.info(`[world] room · tier ${p} (${c.reason}; ${c.gpu}) · ${Un.calls} draw calls · ${Un.triangles} triangles · dpr ${m.size.dpr} · canvas ${Math.round(k.w*m.size.dpr)}×${Math.round(k.h*m.size.dpr)} · ${m.msaa?`4x MSAA`:`no MSAA`} · ~${m.memoryMB} MB · shafts ${h.shafts.size.join(`×`)} · motes ${h.motes.count} · parallax ${Ie}${M.ok?M.still?` · framed as the still`:` · framed live (the still would touch text)`:` · text-safe framing not found`} · flight held ${Re.held}/${Re.rows}, carried ${Re.carried}`),await dn(),Y||Ht)return Vn;u.classList.add(`world-live`),Ut=!0,l??(X=ue(m.renderer.getContext(),{tier:p,onStep:Ln})),$(),f&&n(()=>import(`./debug-D2c3PMPp.js`).then(e=>e.attach({gl:m,memoryMB:mn,plan:()=>hn,room:h,corridor:()=>E,tier:()=>p,frame:()=>M,solved:()=>Re,at:V,path:kt,span:j,inRoom:()=>W,walking:()=>P,walk:fe,flight:re,section:g,parallax:()=>Ie,s3Set:()=>D,shutters:()=>({staged:qe,built:!!D,at:V.s3??null,flood:{...I},span:{...j.shutters}}),hold:e=>{_t=e,e!=null&&Object.assign(I,{on:!0,v:e}),N=!0,$()},redraw:()=>Rt(performance.now()),paused:()=>J,running:vn,pointer:(e,t)=>{z.x=yt.x=e,z.y=bt.x=t,yt.v=bt.v=0,N=!0,$()},wallK:e=>(e!=null&&(Gt.uWallK.value=e,Z&&(Z.k=1)),Gt.uWallK.value)})),__vite__mapDeps([2,0,1]));let Wn=()=>new Promise(e=>`requestIdleCallback`in window?requestIdleCallback(e,{timeout:1500}):setTimeout(e,50));return Wn().then(async()=>{if(Y)return;let e=performance.now(),t=await Be({renderer:m.renderer,tier:p,yieldNow:$e,signal:Kt.signal,size:gn().walls});if(Y||Ht)return Ye();rt(p),Z={k:0},N=!0,$(),m.track(`walls`,t.gpuMB),console.info(`[world] walls · ${t.size} px maps · ${t.kb} KB · GPU ~${t.gpuMB} MB · in ${Math.round(performance.now()-e)} ms · world ~${mn()} MB (plan ${hn.walls}/${hn.atlas}/${hn.gach}, ~${hn.mb} MB)`)}).catch(e=>{!Y&&!Kt.signal.aborted&&console.warn(`[world] walls unavailable, the procedural walls stay`,e)}),Wn().then(async()=>{if(Y)return;let e=await et({size:Math.min(nt({tier:p,width:innerWidth}),gn().atlas),signal:Kt.signal});if(Y||Ht)return st();_n={k:0},N=!0,$(),m.track(`posters`,Ve(e.size)),console.info(`[world] posters · ${e.size} px atlas · ${e.kb} KB · GPU ~${Ve(e.size)} MB · world ~${mn()} MB`)}).catch(e=>{!Y&&!Kt.signal.aborted&&console.warn(`[world] posters unavailable, the walls stay bare`,e)}),Wn().then(async()=>{if(Y)return;let{buildCorridor:e}=await n(async()=>{let{buildCorridor:e}=await import(`./corridor-CnlRBB1a.js`);return{buildCorridor:e}},__vite__mapDeps([3,4,5,6,7,8,9]));if(Y)return;let t=0,r=performance.now(),i=gn().gach,a=await e({gl:m,tier:p,beats:He,textures:h.textures,traces:!!v?.hasAttribute(`data-traces`),gachSize:i,onSlice:e=>{t=Math.max(t,e)}}),o=performance.now()-r;if(a.setTier(p),a.shafts.setSize(Math.round(k.w*m.size.dpr),Math.round(k.h*m.size.dpr)),await a.compile(),Y)return a.dispose();E=a,m.track(`gach`,se(i)),N=!0,$(),console.info(`[world] corridor · ${He.length} doors (${He.map(e=>`${e.unit} ${e.door}`).join(`, `)}) · gạch bông baked ${a.bakedInWorker?`in a worker`:`on the main thread`} in ${Math.round(o)} ms, longest main-thread slice ${t.toFixed(1)} ms`)}).catch(e=>console.warn(`[world] corridor unavailable, the wash holds`,e)).then(()=>b&&!Y?Wn():null).then(async()=>{if(!b||Y)return;let{buildShutters:e}=await n(async()=>{let{buildShutters:e}=await import(`./shutters-WEeBIf_X.js`);return{buildShutters:e}},__vite__mapDeps([10,6,7,8,9]));if(Y)return;let t=await e({gl:m,tier:p,textures:h.textures});if(t.shafts.setSize(Math.round(k.w*m.size.dpr),Math.round(k.h*m.size.dpr)),t.U.uPx.value=m.size.dpr,await t.compile(),Y)return t.dispose();D=t,b.classList.add(`is-live`),N=!0,$(),console.info(`[world] shutters ready`)}).catch(e=>console.warn(`[world] shutters unavailable, the stills stay`,e)),Vn}export{pn as live,vt as n,B as t};