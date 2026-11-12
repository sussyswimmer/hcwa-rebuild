import{F as e,P as t,S as n,c as r,g as i,i as a,k as o,u as s}from"./three-Dgmw-8ZC.js";import{Mt as c,Nt as l,bt as u,ct as d,d as f,gt as p,ht as m,jt as h,kt as g,lt as _,rt as v,st as y,u as b,ut as x,xt as S}from"./wall-poster-C-wKsBqz.js";var C=e=>`
uniform sampler2D uGach, uGachB;
uniform vec4 uGachP, uHouse, uWearP, uTrace;
uniform vec3 uDadoShade, uDadoSun, uDadoLine, uDadoLineSun, uChalk;
${e?``:`uniform vec4 uDoors[8];
uniform int uDoorN;`}
// The lò cò (hopscotch) in chalk: rows of 0.34 m squares, single, single, single, double, single, double, and a
// half-round home; p: across from the band's centre, along the hop from its first square. No numbers, no names.
float loCo(vec2 p){
  const float S = .34;
  float d = 1e3;
  for (int r = 0; r < 6; r++) {
    float c = (float(r) + .5) * S;
    if (r == 3 || r == 5) { d = min(d, abs(sdBox2(vec2(p.x - S * .5, p.y - c), vec2(S * .5)))); d = min(d, abs(sdBox2(vec2(p.x + S * .5, p.y - c), vec2(S * .5)))); }
    else d = min(d, abs(sdBox2(vec2(p.x, p.y - c), vec2(S * .5))));
  }
  if (p.y > 6. * S) d = min(d, abs(length(vec2(p.x, p.y - 6. * S)) - S * .5));
  float aa = max(fwidth(p.y), fwidth(p.x));
  // Critique r2: a child's chalk line, about 3.5 cm (it was 2.6 and read as faded tape at 1440).
  float stroke = 1. - smoothstep(.0175 - aa, .0175 + aa, d);
  // Dry-brushed chalk: streaks along the line (the noise stretched along both axes, so either direction of a stroke
  // breaks the same way), skipping where the tile's grain is low; barely worn elsewhere.
  float streak = max(vn(vec2(p.x * 160., p.y * 9.)), vn(vec2(p.x * 9., p.y * 160.)));
  return stroke * smoothstep(.18, .42, streak) * smoothstep(.12, .4, vn(p * 46.)) * (.82 + .18 * vn(p * 9. + 3.1));
}
`;function w(e,t,{contact:n=null,house:r=!1,nearFade:i=!1}={}){let a=t.some(e=>e.glsl?.includes(`uDoors[`));return new o({uniforms:e,defines:i?{NEAR_FADE:``}:{},vertexShader:`
      attribute float aKind;
      varying vec3 vP; varying vec3 vN; varying float vKind, vDepth;
      void main(){
        vec4 w = modelMatrix * vec4(position, 1.), v = viewMatrix * w;
        vP = w.xyz; vN = normalize(mat3(modelMatrix) * normal); vKind = aKind; vDepth = -v.z;
        gl_Position = projectionMatrix * v;
      }`,fragmentShader:`
      varying vec3 vP; varying vec3 vN; varying float vKind, vDepth;
      uniform vec3 uShade[3], uSunT[3], uSpillT[3], uGlare, uBoxMin, uBoxMax, uFillDir;
      uniform float uRef[2], uI[2], uAoK;
      uniform sampler2D uLime, uTerr;
      ${S}
      ${g}
      ${b(t)}
      ${m}
      ${n??`float contact(vec3 P){ return 1.; }`}
      ${r?C(a):``}
      ${d(r?[`Pl`,`Lw`,`Da`]:[`Pl`])}
      // A surface skips the term of the plane it lies in (facing it, within 3 mm). Distance alone also
      // skipped the last 3 mm of the neighbouring wall, and every corner drew an unshaded bright seam.
      float occ(float d, float r, float s, float self){ return self > .5 && d < 0.003 ? 1. : 1. - s * (1. - smoothstep(0., r, d)); }
      void main(){
        int k = int(vKind + 0.5);
        vec3 N = normalize(vN);
        // M6: a wall passing the lens no longer dithers away (a screen-door discard read as stipple where the screen
        // meets the dado, Maxwell 11 Nov): it settles smoothly into its own shade, no noise, no transparency.
        #ifdef NEAR_FADE
        float nearK = k == 0 ? 1. - smoothstep(.7, 1.4, vDepth) : 0.;
        #else
        float nearK = 0.;
        #endif
        vec2 uv = abs(N.z) > .5 ? vP.xy : abs(N.x) > .5 ? vP.zy : vP.xz;
        vec4 lw = texture2D(uLime, uv * 0.25);
        float vertical = 1. - step(.5, abs(N.y));
        float mott = (lw.r - .5) * .09 + (lw.g - .5) * .05 + (lw.b - .5) * .04 * vertical;
        vec3 rel = k == 1 ? texture2D(uTerr, vP.xz * 0.5).rgb / ${_.toFixed(3)} * (1. + mott * .5) : vec3(1. + mott);
        vec3 shT = uShade[k], suT = uSunT[k], spT = uSpillT[k];
        // The maps: wA the wall's own (plaster, or the house's wash), wB the house's dado.
        vec3 Nb = N;
        vec4 wA = vec4(0., 0., 1., 0.), wB = wA;
        float age = 1., cg = 0.;
        if (k != 1 && uWallK > 0.) {
          vec2 wuv = uv / ${v.wall.toFixed(1)};
          float mw = smoothstep(.35, .65, lw.r);
          // The ceiling stays calmer; behind body text (a capped rect, caps.js) a fifth of the relief.
          age = wallAge(lw.a) * (k == 2 ? .3 : 1.) * mix(.2, 1., smoothstep(.4, 1., capAt(gl_FragCoord.xy)));
          ${r?`wA = k == 0 ? wallTap(uWLw, wuv, mw) : wallTap(uWPl, wuv, mw);
          if (k == 0) wB = wallTap(uWDa, wuv + vec2(.43, .19), mw);`:`wA = wallTap(uWPl, wuv, mw);`}
          cg = wallCrackGate(wuv, mw, age);
          // The dado's A is its chips: its cracks are found by their tone instead.
          wA.rg = wallCrackN(wA, cg); wB.rg *= mix(1., cg, smoothstep(.08, .25, 1. - wB.b) * (1. - wB.a));
          ${r?``:`rel = mix(rel, vec3(wallRelM(wA, age, cg)), uWallK);`}
          Nb = wallN(N, wA.rg, uWallK * .6 * mix(.3, 1., age));
        }
        ${r?`
        if (k == 1) {
          float s = vP.x - uGachP.x, T = uGachP.z, R = uGachP.w;
          float tone = 1. + (h21(floor(vec2(s, vP.z) / T) + 17.31) - .5) * .05;
          // ez: how far into its bay's rug (< 0: on a door's threshold); a long way when there are no thresholds.
          float ez = 1e3;
          if (uGachP.y > 0. && R > 0.) for (int i = 0; i < 8; i++) { if (i >= uDoorN) break; ez = min(ez, abs(vP.z - floor(uDoors[i].x / T + .5) * T) - uGachP.y); }
          if (s >= 0. && s < R && ez >= 0.) {
            float e = min(s, R - s);
            // The border chain along each long edge, and across the rug's ends where it meets a threshold: mitred
            // in the corner tiles, so the chain turns the corner.
            vec4 g = min(e, ez) < T ? (ez < e ? texture2D(uGachB, vec2(s / (2. * T), 1. - ez / T)) : texture2D(uGachB, vec2(vP.z / (2. * T), 1. - e / T))) : texture2D(uGach, vec2(s - T, vP.z) / (2. * T));
            float d = (s - .5 * R) / uWearP.y;
            float lift = min(uWearP.z * g.a * (.3 + .7 * exp(-.5 * d * d)) * (.55 + .9 * lw.r), uWearP.z);
            rel = mix(g.rgb / ${x.toFixed(3)}, vec3(1.), lift);
          } else {
            vec2 q = abs(fract(vec2(s, vP.z) / T + .5) - .5) * T;
            float fw = max(fwidth(s), fwidth(vP.z));
            rel = vec3(1. - .11 * (1. - smoothstep(.0007, .0007 + 1.5 * fw, min(q.x, q.y))));
            if (uTrace.w > .5) rel = mix(rel, uChalk, .97 * loCo(vec2(vP.x - uTrace.z, (vP.z - uTrace.x) * uTrace.y)));
          }
          rel *= tone * (1. + mott * .3);
        } else if (k == 0) {
          // 3 mm soft edges, never thinner than a pixel (a far line must not crawl).
          float ay = max(.0015, fwidth(vP.y)), az = max(.0015, fwidth(vP.z));
          float dado = smoothstep(uHouse.x + ay, uHouse.x - ay, vP.y);
          if (N.x < -.5 && abs(vP.x - uHouse.w) < .02) {
            float dz = 1e3;
            for (int i = 0; i < 8; i++) { if (i >= uDoorN) break; dz = min(dz, abs(vP.z - uDoors[i].x)); }
            dado *= smoothstep(uHouse.z - az, uHouse.z + az, dz);
          }
          float line = smoothstep(uHouse.x - uHouse.y - ay, uHouse.x - uHouse.y + ay, vP.y);
          vec3 dS = mix(uDadoShade, uDadoLine, line), dU = mix(uDadoSun, uDadoLineSun, line);
          spT = mix(spT, spT * dU / max(suT, vec3(.001)), dado);
          shT = mix(shT, dS, dado);
          suT = mix(suT, dU, dado);
          vec3 relW = mix(rel, vec3(wallRelM(wA, age * .55, cg)), uWallK) * (1. + (lw.a - .5) * uWearP.w * (1. - .5 * uWallK));
          vec3 relD = vec3(mix(1. + mott * .3, mix(wallRelC(wB.b, age, age * cg), 1., wB.a), uWallK));
          rel = mix(relW, relD, dado);
          // The dado's chips show the plaster under the paint: the ceiling's warm targets.
          float chip = dado * wB.a * uWallK * smoothstep(.25, .6, age);
          shT = mix(shT, uShade[2], chip); suT = mix(suT, uSunT[2], chip); spT = mix(spT, uSpillT[2], chip);
          Nb = wallN(N, mix(wA.rg * .7, wB.rg, dado), uWallK * mix(.3, 1., age));
        } else {
          // The ceiling: the yellow walls' bounce within 0.5 m of them (up to 30%), and the wash's loang.
          float dw = min(vP.x - uBoxMin.x, uBoxMax.x - vP.x);
          shT = mix(shT, uShade[0], .3 * (1. - smoothstep(0., .5, dw)));
          rel = mix(rel, vec3(wallRelM(wA, age, cg)), uWallK) * (1. + (lw.a - .5) * uWearP.w);
        }`:``}
        vec3 an = abs(N);
        float ao = occ(vP.y - uBoxMin.y, .35, .12 * uAoK, an.y) * occ(uBoxMax.y - vP.y, .4, .1 * uAoK, an.y)
                 * occ(vP.z - uBoxMin.z, .9, .2 * uAoK, an.z) * occ(uBoxMax.z - vP.z, .9, .2 * uAoK, an.z)
                 * occ(vP.x - uBoxMin.x, .9, .2 * uAoK, an.x) * occ(uBoxMax.x - vP.x, .9, .2 * uAoK, an.x) * contact(vP);
        // Skylight from the open side: surfaces facing it and near it a little brighter.
        vec3 c = 0.5 * (uBoxMin + uBoxMax), half_ = max(0.5 * (uBoxMax - uBoxMin), vec3(0.01));
        float near = clamp(dot((vP - c) / half_, uFillDir) * 0.5 + 0.5, 0., 1.);
        float fill = (1. + 0.06 * dot(N, uFillDir)) * (0.92 + 0.14 * near) * (k == 2 ? 0.86 + 0.16 * near : 1.);
        float cap = capAt(gl_FragCoord.xy);
        float n0 = max(dot(N, uLt[0]), 0.), n1 = max(dot(N, uLt[1]), 0.);
        float f0 = n0 > 0. && uI[0] > 0. ? n0 / uRef[0] * lightMask0(vP) * cap * uI[0] : 0.;
        ${t.length>1?`float f1 = n1 > 0. && uI[1] > 0. ? n1 / uRef[1] * lightMask1(vP) * cap * uI[1] : 0.;`:`float f1 = 0.;`}
        vec3 shade = shT * rel * ao * fill * wallFill(Nb, N, normalize(uFillDir + vec3(0., .7, 0.)), .4);
        vec3 sun = suT * 0.94 * rel * mix(1., ao, .5) * wallSun(Nb, N, uLt[0]);
        vec3 col = mix(mix(shade, sun, min(f0, 1.)), uGlare, min(max(f0 - 1., 0.) * .35, .6));
        col = mix(col, spT * 0.94 * rel * mix(1., ao, .5) * wallSun(Nb, N, uLt[1]), min(f1, 1.));
        col = mix(col, shT * fill, nearK * nearK * (3. - 2. * nearK));
        gl_FragColor = vec4(col, 1.);
        #include <colorspace_fragment>
        gl_FragColor.rgb += (h21(gl_FragCoord.xy) - .5) / 255.;
      }`,toneMapped:!1})}var T=`
varying vec3 vP; varying vec3 vN; varying vec2 vUv; varying float vDepth;
#ifdef VERTEX_PAINT
attribute vec4 aPaint; varying vec4 vPaint;
#endif
#ifdef LEAF_WEAR
attribute vec3 aLeafP; varying vec3 vL;
#endif
void main(){
  #ifdef VERTEX_PAINT
  vPaint = aPaint;
  #endif
  #ifdef LEAF_WEAR
  vL = aLeafP;
  #endif
  mat4 m = modelMatrix;
  #ifdef USE_INSTANCING
  m = m * instanceMatrix;
  #endif
  vec4 w = m * vec4(position, 1.);
  vP = w.xyz; vN = normalize(mat3(m) * normal); vUv = uv;
  vec4 v = viewMatrix * w;
  vDepth = -v.z;
  gl_Position = projectionMatrix * v;
}`;function E(e,t,n,{ao:r=null,vertexPaint:i=!1,leafWear:a=!1,nearFade:s=!1,oldCoat:c=!1}={}){return new o({defines:{...i?{VERTEX_PAINT:``}:{},...a?{LEAF_WEAR:``}:{},...s?{NEAR_FADE:``}:{},...c?{OLD_COAT:``}:{}},uniforms:{...e,uGrad:{value:0},...n},vertexShader:T,fragmentShader:`
      varying vec3 vP; varying vec3 vN; varying vec2 vUv; varying float vDepth;
      #ifdef LEAF_WEAR
      varying vec3 vL;
      #endif
      #ifdef VERTEX_PAINT
      varying vec4 vPaint;
      #define uPaint vPaint.rgb
      #define uWear vPaint.a
      #else
      uniform vec3 uPaint;
      uniform float uWear;
      #endif
      uniform vec3 uWood, uCool, uSpillTint, uSunTint;
      uniform float uShadeK, uGrad, uRef[2], uI[2];
      ${S}
      ${g}
      ${b(t)}
      ${r??`float paintAo(vec3 P){ return 1.; }`}
      ${d([`Pt`])}
      void main(){
        #ifdef NEAR_FADE
        float nearK = 1. - smoothstep(.7, 1.4, vDepth);
        #else
        float nearK = 0.;
        #endif
        vec3 N = normalize(vN);
        // Edge band in face UV, broken up by noise: chipped paint at corners and edges only.
        vec2 e = min(vUv, 1. - vUv);
        float wear = (1. - smoothstep(0.015, 0.05, min(e.x, e.y))) * uWear;
        vec2 grain = vP.xy * 38. + vP.zz * 21.;
        vec2 puv = abs(N.x) > .5 ? vP.zy : abs(N.z) > .5 ? vP.xy : vP.xz;
        #ifdef LEAF_WEAR
        if (vL.y > -.5) {
          float az = abs(vL.z), d = min(min(vL.y, ${f.h.toFixed(3)} - vL.y), min(az, ${f.w.toFixed(3)} - az));
          #ifdef OLD_COAT
          float m = max(1. - smoothstep(.03, .045, d), 1. - smoothstep(.01, .125, vL.y)); // edges; scuffs fading up the kick zone
          #else
          float m = max(1. - smoothstep(.03, .045, d), 1. - smoothstep(.02, .25, vL.y)); // edges; scuffs fading up the kick zone
          #endif
          m = max(m, 1. - smoothstep(.085, .105, length(vec2(vL.y - 1.02, az - ${(f.w-.08).toFixed(3)}))));
          #ifdef OLD_COAT
          wear = mix(min(uWear, .06), uWear, m); // the lighter coat shows more than wood did: its face takes less
          #else
          wear = mix(min(uWear, .1), uWear, m);
          #endif
          grain = vL.zy * 38. + vL.xx * 21.; // the chips stay on the leaf as it swings
          puv = vL.zy;
        }
        #endif
        float chip = smoothstep(0.62, 0.7, vn(grain)) * wear;
        vec4 wp = vec4(0., 0., 1., 0.);
        vec3 Nb = N;
        if (uWallK > 0.) {
          vec2 tuv = puv / ${v.paint.toFixed(1)};
          wp = wallTap(uWPt, tuv, .5);
          vec3 dp1 = dFdx(vP), dp2 = dFdy(vP), p2 = cross(dp2, N), p1 = cross(N, dp1);
          vec2 du1 = dFdx(tuv), du2 = dFdy(tuv);
          vec3 T = p2 * du1.x + p1 * du2.x, B = p2 * du1.y + p1 * du2.y;
          float im = inversesqrt(max(max(dot(T, T), dot(B, B)), 1e-12));
          Nb = wallNB(N, T * im, B * im, wp.rg, uWallK);
          chip = max(chip, smoothstep(.3, .7, wp.a) * wear * uWallK);
        }
        #ifdef OLD_COAT
        vec3 albedo = mix(uPaint * mix(1., wallRel(wp.b, 1.), uWallK * (1. - wp.a)), mix(uPaint, uWood, .45), chip);
        #else
        vec3 albedo = mix(uPaint * mix(1., wallRel(wp.b, 1.), uWallK * (1. - wp.a)), uWood, chip);
        #endif
        float sky = 0.82 + 0.18 * N.y;
        vec3 shade = mix(albedo * uShadeK, uCool * uShadeK * 1.08, 0.18) * sky * wallFill(Nb, N, vec3(0., 1., 0.), .8);
        float n0 = max(dot(N, uLt[0]), 0.), n1 = max(dot(N, uLt[1]), 0.);
        float f0 = n0 > 0. && uI[0] > 0. ? n0 / uRef[0] * lightMask0(vP) * uI[0] : 0.;
        ${t.length>1?`float f1 = n1 > 0. && uI[1] > 0. ? n1 / uRef[1] * lightMask1(vP) * uI[1] : 0.;`:`float f1 = 0.;`}
        vec3 col = mix(shade, albedo * uSunTint * wallSun(Nb, N, uLt[0]), min(f0, 1.));
        col = mix(col, albedo * uSpillTint * wallSun(Nb, N, uLt[1]), min(f1, 1.) * 0.85);
        col *= (1. + uGrad * (smoothstep(0., 2.2, vP.y) - .5)) * paintAo(vP);
        // M6 fix: a blue paint stays blue under the warm sun and the flood (the indigo shutter leaf read mauve): its
        // red never rises past 1.3x the paint's own red-to-blue ratio.
        if (uPaint.b > uPaint.r) col.r = min(col.r, col.b * (uPaint.r / uPaint.b) * 1.3);
        // M6: paint passing the lens settles into its own shade (no screen-door dither).
        col = mix(col, uPaint * uShadeK * sky, nearK * nearK * (3. - 2. * nearK));
        gl_FragColor = vec4(col, 1.);
        #include <colorspace_fragment>
      }`,toneMapped:!1})}function D({low:e,high:t,y0:n=0,y1:r=2.4,grain:i=.02,canopy:a=null,dapple:s=null}){return new o({uniforms:{uLow:{value:e},uHigh:{value:t},uY:{value:[n,r]},uGrain:{value:i},uCanopyC:{value:a?.color??t},uCanopyAmt:{value:a?.amount??0},uDapple:{value:s?[s.zc,s.half,s.amount,1]:[0,0,0,0]}},vertexShader:`varying vec3 vP; void main(){ vec4 w = modelMatrix * vec4(position, 1.); vP = w.xyz; gl_Position = projectionMatrix * viewMatrix * w; }`,fragmentShader:`
      varying vec3 vP; uniform vec3 uLow, uHigh, uCanopyC; uniform float uY[2], uGrain, uCanopyAmt, uDapple[4];
      ${g}
      ${S}
      void main(){
        float t = smoothstep(uY[0], uY[1], vP.y);
        vec3 col = mix(uLow, uHigh, t) * (1. + (vn(vP.zy * 3. + vP.xx) - .5) * uGrain);
        if (uDapple[3] > .5) {
          float a = uDapple[1] - abs(vP.z - uDapple[0]), k = uDapple[2];
          // The reveal keeps the room's light off the jambs and the head; deeper at the floor's line.
          col *= 1. - k * 1.4 * (1. - smoothstep(0., .14, a)) - k * (1. - smoothstep(0., .1, vP.y));
          // The room's own screen: its holes' sun slanted down the back wall, soft (it is metres away).
          vec2 c = vec2(vP.z - uDapple[0] + vP.y * .42, vP.y * 1.15 - .3);
          float h = sdHoaThi(mod(c, ${u.toFixed(2)}) - ${(u/2).toFixed(3)});
          col *= 1. + k * (1. - smoothstep(-.012, .03, h)) * smoothstep(.3, 1.1, vP.y);
        }
        float tree = smoothstep(.54, .66, fbm(vP.zy * vec2(.55, .6) + 3.1) + (clamp(vP.y / 5., 0., 1.) - .45) * .9);
        col = mix(col, uCanopyC, tree * uCanopyAmt);
        gl_FragColor = vec4(col, 1.);
        #include <colorspace_fragment>
      }`,toneMapped:!1})}function O(e,t,n,r,i,a,o=[]){let s=[...new Set([n[0],n[1],...o.flatMap(e=>[e[0],e[1]])])].filter(e=>e>=n[0]&&e<=n[1]).sort((e,t)=>e-t),c=[...new Set([r[0],r[1],...o.flatMap(e=>[e[2],e[3]])])].filter(e=>e>=r[0]&&e<=r[1]).sort((e,t)=>e-t),l=[];for(let n=0;n<s.length-1;n++)for(let r=0;r<c.length-1;r++){let[u,d]=[(s[n]+s[n+1])/2,(c[r]+c[r+1])/2];o.some(e=>u>e[0]&&u<e[1]&&d>e[2]&&d<e[3])||l.push({axis:e,at:t,a:[s[n],s[n+1]],b:[c[r],c[r+1]],facing:i,kind:a})}return l}var k={x:e=>e>0?[0,Math.PI/2,0]:[0,-Math.PI/2,0],y:e=>e>0?[-Math.PI/2,0,0]:[Math.PI/2,0,0],z:e=>e>0?[0,0,0]:[0,Math.PI,0]},A=e=>{let t=(e.a[0]+e.a[1])/2,n=(e.b[0]+e.b[1])/2;return e.axis===`x`?[e.at,n,t]:e.axis===`y`?[t,e.at,n]:[t,n,e.at]};function j(e){let t=[],o=[],c=[],l=[],u=new i;for(let i of e){let e=new n(i.a[1]-i.a[0],i.b[1]-i.b[0]);u.makeRotationFromEuler(new r(...k[i.axis](i.facing))).setPosition(...A(i)),e.applyMatrix4(u);let a=t.length/3;t.push(...e.getAttribute(`position`).array),o.push(...e.getAttribute(`normal`).array);for(let t=0;t<e.getAttribute(`position`).count;t++)c.push(i.kind);l.push(...Array.from(e.index.array,e=>e+a)),e.dispose()}let d=new a;return d.setAttribute(`position`,new s(t,3)),d.setAttribute(`normal`,new s(o,3)),d.setAttribute(`aKind`,new s(c,1)),d.setIndex(l),d}function M(e,{cut:t=null,skip:n=[]}={}){let[r,i,a]=e.min,[o,s,c]=e.max,l={floor:O(`y`,i,[r,o],[a,c],1,1),ceiling:O(`y`,s,[r,o],[a,c],-1,2),xMin:O(`x`,r,[a,c],[i,s],1,0),xMax:O(`x`,o,[a,c],[i,s],-1,0),zMin:O(`z`,a,[r,o],[i,s],1,0),zMax:O(`z`,c,[r,o],[i,s],-1,0)};if(t){let e={xMin:[`x`,r,[a,c],1],xMax:[`x`,o,[a,c],-1],zMin:[`z`,a,[r,o],1],zMax:[`z`,c,[r,o],-1]}[t.wall];l[t.wall]=O(e[0],e[1],e[2],[i,s],e[3],0,t.holes)}return Object.entries(l).filter(([e])=>!n.includes(e)).flatMap(([,e])=>e)}var N=(e,t,n=1,r=c(`sunRoom`))=>e.clone().lerp(e.clone().multiply(t).multiply({r:1/r.r,g:1/r.g,b:1/r.b}),n);function P({box:e,fillDir:n,sun:r,shade:i,sunT:a,spillT:o,refs:s,lime:u,terr:d}){return{...p(),...y(),uShade:{value:i.map(l)},uSunT:{value:a.map(l)},uSpillT:{value:o.map(l)},uGlare:{value:l(c(`sky`))},uBoxMin:{value:new t(...e.min)},uBoxMax:{value:new t(...e.max)},uFillDir:{value:new t(...n).normalize()},uRef:{value:s},uI:{value:[1,1]},uAoK:{value:1},uLime:{value:u},uTerr:{value:d},uSunCol:{value:new t(r.r,r.g,r.b)},uSunI:{value:1},uWind:{value:new t},uTime:{value:0}}}var F=(e,t,n)=>N(e,t,n,c(`sunCorridorA`)),I=()=>[c(`vangShade`),c(`gachShade`),c(`ceilingWarm`)],L=(e,t=.5)=>[F(c(`vangSun`),e,t),F(c(`sun-100`),e,t),F(c(`vangSun`),e,t)],R=(e,t)=>F(c(`dadoSun`),e,t),z=(e,t)=>{let n=R(e,t),r=c(`dadoShade`);return c(`dadoLine`).multiply({r:n.r/r.r,g:n.g/r.g,b:n.b/r.b})},B={dado:.9,line:.035,doorBreak:.1,tile:.2,runner:1.6,threshold:.6,wear:{sigma:.35,lift:.18},loang:.2};function V({gach:t=null,runnerX:n=0,runner:r=B.runner,threshold:i=B.threshold,doorWall:a=0,half:o,doors:s=null,sun:u,k:d=.5}){let f={uGach:{value:t?.field??null},uGachB:{value:t?.border??null},uGachP:{value:new e(n,i,B.tile,r)},uTrace:{value:new e(0,0,0,0)},uChalk:{value:l(H())},uHouse:{value:new e(B.dado,B.line,o+B.doorBreak,a)},uWearP:{value:new e(0,B.wear.sigma,B.wear.lift,B.loang)},uDadoShade:{value:l(c(`dadoShade`))},uDadoSun:{value:l(R(u,d))},uDadoLine:{value:l(c(`dadoLine`))},uDadoLineSun:{value:l(z(u,d))}};return s&&(f.uDoors={value:Array.from({length:8},(t,n)=>new e(s[n]??0,0,0,0))},f.uDoorN={value:s.length}),f}var H=()=>{let e=h(c(`tile-indigo-lit`),c(`lime-50`),.6),t=c(`tile-cream`);return e.multiply({r:1/t.r,g:1/t.g,b:1/t.b})};function U(e,t,n=.5){let r=R(t,n),i=z(t,n);e.uDadoSun.value.set(r.r,r.g,r.b),e.uDadoLineSun.value.set(i.r,i.g,i.b)}var W=()=>[c(`wallShade`),c(`floorShade`),c(`ceiling`)],G=(e,t=1)=>[N(c(`wallSun`),e,t),N(c(`wallSun`),e,t).multiplyScalar(.8),N(c(`wallSun`),e,t)],K=(e=c(`sky`),t=h(c(`sky`),c(`shade-100`),.35),n=h(c(`sky`),c(`unit-green-bamboo`),.2),r=.55)=>({uBehindLow:{value:l(e)},uBehindHigh:{value:l(t)},uBehindY:{value:[.6,6]},uCanopyC:{value:l(n)},uCanopyAmt:{value:r},uBehindDist:{value:6.4}});function q(e,{face:t=c(`screenFace`),glow:n=c(`sky`)}={}){e.uFace.value.copy(l(t)),e.uRimC.value.copy(l(h(c(`wallSun`),n,.3))),e.uWallC.value.copy(l(h(t,n,.38)))}export{w as _,W as a,U as c,N as d,M as f,E as g,D as h,V as i,K as l,j as m,I as n,G as o,O as p,L as r,q as s,B as t,P as u};