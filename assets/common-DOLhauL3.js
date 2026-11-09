import{$ as e,Ft as t,H as n,J as r,Mt as i,Ot as a,Pt as o,U as s,Y as c,bt as l,ct as u,dt as d,et as f,ft as p,gt as m,ht as h,mt as g,ut as _,vt as v,wt as y}from"./journey-pyRzNSax.js";var b={hoaThi:`return sdHoaThi(mod(uv, B) - 0.5 * B);`,hoaThiOne:`vec2 id = floor(uv / B); return all(equal(id, p.xy)) ? sdHoaThi(mod(uv, B) - 0.5 * B) : 1.;`,hoaThiMissing:`vec2 c = mod(uv, B) - 0.5 * B; float d = sdHoaThi(c); return all(equal(floor(uv / B), p.xy)) ? min(d, sdBox2(c, vec2(0.5 * B - 0.004))) : d;`,dotTre:`return sdDotTre(mod(uv, B) - 0.5 * B);`,vomCau:`return sdVomCau(mod(uv, B) - 0.5 * B);`,open:`return -1.;`,fans:`return -fanBlades(uv);`,solid:`return 1.;`,slats:`float h = 0.5 * p.x; return abs(mod(uv.y, p.x) - h) - h * p.y;`,canopy:`vec2 q = mat2(.8, -.6, .6, .8) * uv * p.x; float n = 0.62 * fbm(q + p.zw) + 0.38 * vn(mat2(.5, .87, -.87, .5) * q * 3.7 + p.wz); return (n - p.y) * 0.5 / p.x;`};function x(t){let n=0,r=[],i=[];for(let[e,a]of t.entries()){if(a.glsl&&r.push(a.glsl),a.mask){i.push(`float lightMask${e}(vec3 P){ vec3 L = uLt[${e}]; return (${a.mask})${a.factor?` * (${a.factor})`:``}; }`);continue}let t=[],o=[];for(let e of a.apertures){let i=n++;r.push(`float hole${i}(vec2 uv, vec4 p){ ${b[e.pattern]} }
float ap${i}(vec3 P, vec3 L){
  vec3 C = uApC[${i}], U = uApU[${i}], V = uApV[${i}], N = cross(U, V);
  float ln = dot(L, N), pn = dot(P - C, N), side = sign(ln);
  if (abs(ln) < 1e-4 || pn * side > -0.001) return 1.;
  vec2 size = uApS[${i}].xy;
  float m = 1.;
  for (int f = 0; f < ${e.thin?1:2}; f++) {
    float t = (float(f) * uApS[${i}].z * side - pn) / ln;
    vec3 Q = P + L * t;
    vec2 uv = vec2(dot(Q - C, U), dot(Q - C, V));
    float w = 0.0025 + 0.0093 * max(t, 0.) * ${e.penumbra==null?`0.6`:(.6*e.penumbra).toFixed(4)};
    float rect = sdBox2(uv - 0.5 * size, 0.5 * size);
    float h = hole${i}(uv, uApP[${i}]);
    ${e.feather?e.mode===`occluder`?`m *= mix(1., smoothstep(w, -w, h), smoothstep(uApS[${i}].w, -uApS[${i}].w, rect));`:`m *= smoothstep(w, -w, h) * smoothstep(uApS[${i}].w, -uApS[${i}].w, rect);`:`m *= smoothstep(w, -w, ${e.mode===`occluder`?`min(-rect, h)`:`max(rect, h)`});`}
  }
  return m;
}`),(e.mode===`occluder`?o:t).push(`ap${i}(P, L)`)}let s=t.length?t.reduce((e,t)=>`max(${e}, ${t})`):`1.`;i.push(`float lightMask${e}(vec3 P){ vec3 L = uLt[${e}]; return ${[s,...o,...a.factor?[`(${a.factor})`]:[]].join(` * `)}; }`)}return`
#define B ${e.toFixed(4)}
uniform vec3 uApC[8], uApU[8], uApV[8];
uniform vec4 uApS[8], uApP[8];
uniform vec3 uLt[2];
${r.join(`
`)}
${i.join(`
`)}
#undef B
`}function S(e,n){let r=e=>Array.from({length:8},e),i={uApC:{value:r(()=>new o)},uApU:{value:r(()=>new o(1,0,0))},uApV:{value:r(()=>new o(0,1,0))},uApS:{value:r(()=>new t)},uApP:{value:r(()=>new t)},uLt:{value:n}},a=0;for(let t of e)for(let e of t.apertures??[]){let t=a++;i.uApC.value[t].set(...e.corner),i.uApU.value[t].set(...e.u).normalize(),i.uApV.value[t].set(...e.v).normalize(),i.uApS.value[t].set(e.size[0],e.size[1],e.depth??0,e.feather??0),i.uApP.value[t].set(...e.params??[0,0,0,0])}if(a>8)throw Error(`apertures: ${a} > 8`);return i}var C={w:.95,h:2.2,t:.04,frame:.06,reveal:.14},w={"green-bamboo":`dotTre`,"cau-han":`vomCau`,"little-rose":`hoaThi`};function T(e){let t=e===`little-rose`;return{transomPattern:t?`open`:w[e]??`hoaThi`,spillTint:t?0:.12,corridorSun:!t}}function E({wx:t,zc:n,unit:r,open:i=72,hinge:a=1,inward:o=!1}){let s=r===`little-rose`,c=C.w/2,l=C.h+C.frame,u={z0:n-1.5*e,z1:n+1.5*e,y0:l+.04,y1:l+.04+e};return{wx:t,zc:n,unit:r,hinge:a,swing:o?-1:1,open:s?0:i,emblem:w[r]??`hoaThi`,holes:[[n-c-C.frame,n+c+C.frame,0,l],[u.z0,u.z1,u.y0,u.y1]],doorway:{z0:n-c,z1:n+c,y0:0,y1:C.h},transom:u}}function D(e,t=[0,0,0]){let n=e.getAttribute(`position`),r=new Float32Array(n.count*3);for(let e=0;e<n.count;e++)r[e*3]=n.getX(e)+t[0],r[e*3+1]=n.getY(e)+t[1],r[e*3+2]=n.getZ(e)+t[2];return e.setAttribute(`aLeafP`,new h(r,3)),e}function O(e){let t=e.getAttribute(`position`).count,n=new Float32Array(t*3);for(let e=0;e<t;e++)n[e*3+1]=-1;return e.setAttribute(`aLeafP`,new h(n,3)),e}function k(e){let t=new m,n=Object.keys(e[0].attributes),r=[],i=0;for(let t of e){for(let e of t.index.array)r.push(e+i);i+=t.attributes.position.count}for(let r of n){let n=e[0].attributes[r].itemSize,a=new Float32Array(i*n),o=0;for(let t of e)a.set(t.attributes[r].array,o),o+=t.attributes[r].array.length;t.setAttribute(r,new h(a,n))}return t.setIndex(r),t}function A(e){let{wx:t,zc:n}=e,r=C.w/2,i=C.frame,a=C.reveal,o=(e,t,n,r,i,a)=>new g(e,t,n).translate(r,i,a),s=O(k([o(a,C.h+i,i,t+a/2-.02,(C.h+i)/2,n-r-i/2),o(a,C.h+i,i,t+a/2-.02,(C.h+i)/2,n+r+i/2),o(a,i,C.w+2*i,t+a/2-.02,C.h+i/2,n)])),c=e.hinge??1,l=[o(C.t,C.h,C.w,0,C.h/2,-c*r)];for(let e of[-1,1])for(let[t,n]of[[.54,.84],[1.6,.96]])for(let i of[-.24,.24])l.push(o(.012,n,.34,e*(C.t/2+.004),t,-c*r+i));return{frame:s,leaf:D(k(l)),hinge:[t+.03,0,n+c*r],side:c}}var j=e=>`
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
`;function M(e,t,{contact:a=null,house:o=!1,nearFade:c=!1}={}){let l=t.some(e=>e.glsl?.includes(`uDoors[`));return new i({uniforms:e,defines:c?{NEAR_FADE:``}:{},vertexShader:`
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
      ${f}
      ${u}
      ${x(t)}
      ${r}
      ${a??`float contact(vec3 P){ return 1.; }`}
      ${o?j(l):``}
      // A surface skips the term of the plane it lies in (facing it, within 3 mm). Distance alone also
      // skipped the last 3 mm of the neighbouring wall, and every corner drew an unshaded bright seam.
      float occ(float d, float r, float s, float self){ return self > .5 && d < 0.003 ? 1. : 1. - s * (1. - smoothstep(0., r, d)); }
      void main(){
        int k = int(vKind + 0.5);
        vec3 N = normalize(vN);
        #ifdef NEAR_FADE
        if (k == 0 && h21(gl_FragCoord.xy) > smoothstep(.7, 1.4, vDepth)) discard;
        #endif
        vec2 uv = abs(N.z) > .5 ? vP.xy : abs(N.x) > .5 ? vP.zy : vP.xz;
        vec4 lw = texture2D(uLime, uv * 0.25);
        float vertical = 1. - step(.5, abs(N.y));
        float mott = (lw.r - .5) * .09 + (lw.g - .5) * .05 + (lw.b - .5) * .04 * vertical;
        vec3 rel = k == 1 ? texture2D(uTerr, vP.xz * 0.5).rgb / ${n.toFixed(3)} * (1. + mott * .5) : vec3(1. + mott);
        vec3 shT = uShade[k], suT = uSunT[k], spT = uSpillT[k];
        ${o?`
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
            rel = mix(g.rgb / ${s.toFixed(3)}, vec3(1.), lift);
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
          rel = mix(rel * (1. + (lw.a - .5) * uWearP.w), vec3(1. + mott * .3), dado);
        } else {
          // The ceiling: the yellow walls' bounce within 0.5 m of them (up to 30%), and the wash's loang.
          float dw = min(vP.x - uBoxMin.x, uBoxMax.x - vP.x);
          shT = mix(shT, uShade[0], .3 * (1. - smoothstep(0., .5, dw)));
          rel *= 1. + (lw.a - .5) * uWearP.w;
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
        vec3 shade = shT * rel * ao * fill;
        vec3 sun = suT * 0.94 * rel * mix(1., ao, .5);
        vec3 col = mix(mix(shade, sun, min(f0, 1.)), uGlare, min(max(f0 - 1., 0.) * .35, .6));
        col = mix(col, spT * 0.94 * rel * mix(1., ao, .5), min(f1, 1.));
        gl_FragColor = vec4(col, 1.);
        #include <colorspace_fragment>
        gl_FragColor.rgb += (h21(gl_FragCoord.xy) - .5) / 255.;
      }`,toneMapped:!1})}var N=`
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
}`;function P(e,t,n,{ao:r=null,vertexPaint:a=!1,leafWear:o=!1,nearFade:s=!1,oldCoat:c=!1}={}){return new i({defines:{...a?{VERTEX_PAINT:``}:{},...o?{LEAF_WEAR:``}:{},...s?{NEAR_FADE:``}:{},...c?{OLD_COAT:``}:{}},uniforms:{...e,uGrad:{value:0},...n},vertexShader:N,fragmentShader:`
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
      ${f}
      ${u}
      ${x(t)}
      ${r??`float paintAo(vec3 P){ return 1.; }`}
      void main(){
        #ifdef NEAR_FADE
        if (h21(gl_FragCoord.xy) > smoothstep(.7, 1.4, vDepth)) discard;
        #endif
        vec3 N = normalize(vN);
        // Edge band in face UV, broken up by noise: chipped paint at corners and edges only.
        vec2 e = min(vUv, 1. - vUv);
        float wear = (1. - smoothstep(0.015, 0.05, min(e.x, e.y))) * uWear;
        vec2 grain = vP.xy * 38. + vP.zz * 21.;
        #ifdef LEAF_WEAR
        if (vL.y > -.5) {
          float az = abs(vL.z), d = min(min(vL.y, ${C.h.toFixed(3)} - vL.y), min(az, ${C.w.toFixed(3)} - az));
          #ifdef OLD_COAT
          float m = max(1. - smoothstep(.03, .045, d), 1. - smoothstep(.01, .125, vL.y)); // edges; scuffs fading up the kick zone
          #else
          float m = max(1. - smoothstep(.03, .045, d), 1. - smoothstep(.02, .25, vL.y)); // edges; scuffs fading up the kick zone
          #endif
          m = max(m, 1. - smoothstep(.085, .105, length(vec2(vL.y - 1.02, az - ${(C.w-.08).toFixed(3)}))));
          #ifdef OLD_COAT
          wear = mix(min(uWear, .06), uWear, m); // the lighter coat shows more than wood did: its face takes less
          #else
          wear = mix(min(uWear, .1), uWear, m);
          #endif
          grain = vL.zy * 38. + vL.xx * 21.; // the chips stay on the leaf as it swings
        }
        #endif
        float chip = smoothstep(0.62, 0.7, vn(grain)) * wear;
        #ifdef OLD_COAT
        vec3 albedo = mix(uPaint, mix(uPaint, uWood, .45), chip);
        #else
        vec3 albedo = mix(uPaint, uWood, chip);
        #endif
        float sky = 0.82 + 0.18 * N.y;
        vec3 shade = mix(albedo * uShadeK, uCool * uShadeK * 1.08, 0.18) * sky;
        float n0 = max(dot(N, uLt[0]), 0.), n1 = max(dot(N, uLt[1]), 0.);
        float f0 = n0 > 0. && uI[0] > 0. ? n0 / uRef[0] * lightMask0(vP) * uI[0] : 0.;
        ${t.length>1?`float f1 = n1 > 0. && uI[1] > 0. ? n1 / uRef[1] * lightMask1(vP) * uI[1] : 0.;`:`float f1 = 0.;`}
        vec3 col = mix(shade, albedo * uSunTint, min(f0, 1.));
        col = mix(col, albedo * uSpillTint, min(f1, 1.) * 0.85);
        col *= (1. + uGrad * (smoothstep(0., 2.2, vP.y) - .5)) * paintAo(vP);
        gl_FragColor = vec4(col, 1.);
        #include <colorspace_fragment>
      }`,toneMapped:!1})}function F({low:t,high:n,y0:r=0,y1:a=2.4,grain:o=.02,canopy:s=null,dapple:c=null}){return new i({uniforms:{uLow:{value:t},uHigh:{value:n},uY:{value:[r,a]},uGrain:{value:o},uCanopyC:{value:s?.color??n},uCanopyAmt:{value:s?.amount??0},uDapple:{value:c?[c.zc,c.half,c.amount,1]:[0,0,0,0]}},vertexShader:`varying vec3 vP; void main(){ vec4 w = modelMatrix * vec4(position, 1.); vP = w.xyz; gl_Position = projectionMatrix * viewMatrix * w; }`,fragmentShader:`
      varying vec3 vP; uniform vec3 uLow, uHigh, uCanopyC; uniform float uY[2], uGrain, uCanopyAmt, uDapple[4];
      ${u}
      ${f}
      void main(){
        float t = smoothstep(uY[0], uY[1], vP.y);
        vec3 col = mix(uLow, uHigh, t) * (1. + (vn(vP.zy * 3. + vP.xx) - .5) * uGrain);
        if (uDapple[3] > .5) {
          float a = uDapple[1] - abs(vP.z - uDapple[0]), k = uDapple[2];
          // The reveal keeps the room's light off the jambs and the head; deeper at the floor's line.
          col *= 1. - k * 1.4 * (1. - smoothstep(0., .14, a)) - k * (1. - smoothstep(0., .1, vP.y));
          // The room's own screen: its holes' sun slanted down the back wall, soft (it is metres away).
          vec2 c = vec2(vP.z - uDapple[0] + vP.y * .42, vP.y * 1.15 - .3);
          float h = sdHoaThi(mod(c, ${e.toFixed(2)}) - ${(e/2).toFixed(3)});
          col *= 1. + k * (1. - smoothstep(-.012, .03, h)) * smoothstep(.3, 1.1, vP.y);
        }
        float tree = smoothstep(.54, .66, fbm(vP.zy * vec2(.55, .6) + 3.1) + (clamp(vP.y / 5., 0., 1.) - .45) * .9);
        col = mix(col, uCanopyC, tree * uCanopyAmt);
        gl_FragColor = vec4(col, 1.);
        #include <colorspace_fragment>
      }`,toneMapped:!1})}function I(e,t,n,r,i,a,o=[]){let s=[...new Set([n[0],n[1],...o.flatMap(e=>[e[0],e[1]])])].filter(e=>e>=n[0]&&e<=n[1]).sort((e,t)=>e-t),c=[...new Set([r[0],r[1],...o.flatMap(e=>[e[2],e[3]])])].filter(e=>e>=r[0]&&e<=r[1]).sort((e,t)=>e-t),l=[];for(let n=0;n<s.length-1;n++)for(let r=0;r<c.length-1;r++){let[u,d]=[(s[n]+s[n+1])/2,(c[r]+c[r+1])/2];o.some(e=>u>e[0]&&u<e[1]&&d>e[2]&&d<e[3])||l.push({axis:e,at:t,a:[s[n],s[n+1]],b:[c[r],c[r+1]],facing:i,kind:a})}return l}var L={x:e=>e>0?[0,Math.PI/2,0]:[0,-Math.PI/2,0],y:e=>e>0?[-Math.PI/2,0,0]:[Math.PI/2,0,0],z:e=>e>0?[0,0,0]:[0,Math.PI,0]},R=e=>{let t=(e.a[0]+e.a[1])/2,n=(e.b[0]+e.b[1])/2;return e.axis===`x`?[e.at,n,t]:e.axis===`y`?[t,e.at,n]:[t,n,e.at]};function z(e){let t=[],n=[],r=[],i=[],o=new y;for(let s of e){let e=new a(s.a[1]-s.a[0],s.b[1]-s.b[0]);o.makeRotationFromEuler(new v(...L[s.axis](s.facing))).setPosition(...R(s)),e.applyMatrix4(o);let c=t.length/3;t.push(...e.getAttribute(`position`).array),n.push(...e.getAttribute(`normal`).array);for(let t=0;t<e.getAttribute(`position`).count;t++)r.push(s.kind);i.push(...Array.from(e.index.array,e=>e+c)),e.dispose()}let s=new m;return s.setAttribute(`position`,new l(t,3)),s.setAttribute(`normal`,new l(n,3)),s.setAttribute(`aKind`,new l(r,1)),s.setIndex(i),s}function B(e,{cut:t=null,skip:n=[]}={}){let[r,i,a]=e.min,[o,s,c]=e.max,l={floor:I(`y`,i,[r,o],[a,c],1,1),ceiling:I(`y`,s,[r,o],[a,c],-1,2),xMin:I(`x`,r,[a,c],[i,s],1,0),xMax:I(`x`,o,[a,c],[i,s],-1,0),zMin:I(`z`,a,[r,o],[i,s],1,0),zMax:I(`z`,c,[r,o],[i,s],-1,0)};if(t){let e={xMin:[`x`,r,[a,c],1],xMax:[`x`,o,[a,c],-1],zMin:[`z`,a,[r,o],1],zMax:[`z`,c,[r,o],-1]}[t.wall];l[t.wall]=I(e[0],e[1],e[2],[i,s],e[3],0,t.holes)}return Object.entries(l).filter(([e])=>!n.includes(e)).flatMap(([,e])=>e)}var V=(e,t,n=1,r=d(`sunRoom`))=>e.clone().lerp(e.clone().multiply(t).multiply({r:1/r.r,g:1/r.g,b:1/r.b}),n);function H({box:e,fillDir:t,sun:n,shade:r,sunT:i,spillT:a,refs:s,lime:l,terr:u}){return{...c(),uShade:{value:r.map(p)},uSunT:{value:i.map(p)},uSpillT:{value:a.map(p)},uGlare:{value:p(d(`sky`))},uBoxMin:{value:new o(...e.min)},uBoxMax:{value:new o(...e.max)},uFillDir:{value:new o(...t).normalize()},uRef:{value:s},uI:{value:[1,1]},uAoK:{value:1},uLime:{value:l},uTerr:{value:u},uSunCol:{value:new o(n.r,n.g,n.b)},uSunI:{value:1},uWind:{value:new o},uTime:{value:0}}}var U=(e,t,n)=>V(e,t,n,d(`sunCorridorA`)),W=()=>[d(`vangShade`),d(`gachShade`),d(`ceilingWarm`)],G=(e,t=.5)=>[U(d(`vangSun`),e,t),U(d(`sun-100`),e,t),U(d(`vangSun`),e,t)],K=(e,t)=>U(d(`dadoSun`),e,t),q=(e,t)=>{let n=K(e,t),r=d(`dadoShade`);return d(`dadoLine`).multiply({r:n.r/r.r,g:n.g/r.g,b:n.b/r.b})},J={dado:.9,line:.035,doorBreak:.1,tile:.2,runner:1.6,threshold:.6,wear:{sigma:.35,lift:.18},loang:.2};function Y({gach:e=null,runnerX:n=0,runner:r=J.runner,threshold:i=J.threshold,doorWall:a=0,half:o,doors:s=null,sun:c,k:l=.5}){let u={uGach:{value:e?.field??null},uGachB:{value:e?.border??null},uGachP:{value:new t(n,i,J.tile,r)},uTrace:{value:new t(0,0,0,0)},uChalk:{value:p(X())},uHouse:{value:new t(J.dado,J.line,o+J.doorBreak,a)},uWearP:{value:new t(0,J.wear.sigma,J.wear.lift,J.loang)},uDadoShade:{value:p(d(`dadoShade`))},uDadoSun:{value:p(K(c,l))},uDadoLine:{value:p(d(`dadoLine`))},uDadoLineSun:{value:p(q(c,l))}};return s&&(u.uDoors={value:Array.from({length:8},(e,n)=>new t(s[n]??0,0,0,0))},u.uDoorN={value:s.length}),u}var X=()=>{let e=_(d(`tile-indigo-lit`),d(`lime-50`),.6),t=d(`tile-cream`);return e.multiply({r:1/t.r,g:1/t.g,b:1/t.b})};function Z(e,t,n=.5){let r=K(t,n),i=q(t,n);e.uDadoSun.value.set(r.r,r.g,r.b),e.uDadoLineSun.value.set(i.r,i.g,i.b)}var Q=()=>[d(`wallShade`),d(`floorShade`),d(`ceiling`)],$=(e,t=1)=>[V(d(`wallSun`),e,t),V(d(`wallSun`),e,t).multiplyScalar(.8),V(d(`wallSun`),e,t)],ee=(e=d(`sky`),t=_(d(`sky`),d(`shade-100`),.35),n=_(d(`sky`),d(`unit-green-bamboo`),.2),r=.55)=>({uBehindLow:{value:p(e)},uBehindHigh:{value:p(t)},uBehindY:{value:[.6,6]},uCanopyC:{value:p(n)},uCanopyAmt:{value:r},uBehindDist:{value:6.4}});function te(e,{face:t=d(`screenFace`),glow:n=d(`sky`)}={}){e.uFace.value.copy(p(t)),e.uRimC.value.copy(p(_(d(`wallSun`),n,.3))),e.uWallC.value.copy(p(_(t,n,.38)))}export{S as C,k as S,M as _,Q as a,T as b,Z as c,V as d,B as f,P as g,F as h,Y as i,ee as l,z as m,W as n,$ as o,I as p,G as r,te as s,J as t,H as u,C as v,x as w,E as x,A as y};