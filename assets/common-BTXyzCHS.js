import{Dt as e,Et as t,H as n,K as r,R as i,V as a,at as o,bt as s,ft as c,gt as l,it as u,lt as d,q as f,rt as p,tt as m,ut as h,wt as g}from"./journey-P5d6QuFC.js";var _={hoaThi:`return sdHoaThi(mod(uv, B) - 0.5 * B);`,hoaThiOne:`vec2 id = floor(uv / B); return all(equal(id, p.xy)) ? sdHoaThi(mod(uv, B) - 0.5 * B) : 1.;`,hoaThiMissing:`vec2 c = mod(uv, B) - 0.5 * B; float d = sdHoaThi(c); return all(equal(floor(uv / B), p.xy)) ? min(d, sdBox2(c, vec2(0.5 * B - 0.004))) : d;`,dotTre:`return sdDotTre(mod(uv, B) - 0.5 * B);`,vomCau:`return sdVomCau(mod(uv, B) - 0.5 * B);`,open:`return -1.;`,solid:`return 1.;`,slats:`float h = 0.5 * p.x; return abs(mod(uv.y, p.x) - h) - h * p.y;`,canopy:`vec2 q = mat2(.8, -.6, .6, .8) * uv * p.x; float n = 0.62 * fbm(q + p.zw) + 0.38 * vn(mat2(.5, .87, -.87, .5) * q * 3.7 + p.wz); return (n - p.y) * 0.5 / p.x;`};function v(e){let t=0,n=[],i=[];for(let[r,a]of e.entries()){if(a.glsl&&n.push(a.glsl),a.mask){i.push(`float lightMask${r}(vec3 P){ vec3 L = uLt[${r}]; return (${a.mask})${a.factor?` * (${a.factor})`:``}; }`);continue}let e=[],o=[];for(let r of a.apertures){let i=t++;n.push(`float hole${i}(vec2 uv, vec4 p){ ${_[r.pattern]} }
float ap${i}(vec3 P, vec3 L){
  vec3 C = uApC[${i}], U = uApU[${i}], V = uApV[${i}], N = cross(U, V);
  float ln = dot(L, N), pn = dot(P - C, N), side = sign(ln);
  if (abs(ln) < 1e-4 || pn * side > -0.001) return 1.;
  vec2 size = uApS[${i}].xy;
  float m = 1.;
  for (int f = 0; f < 2; f++) {
    float t = (float(f) * uApS[${i}].z * side - pn) / ln;
    vec3 Q = P + L * t;
    vec2 uv = vec2(dot(Q - C, U), dot(Q - C, V));
    float w = 0.0025 + 0.0093 * max(t, 0.) * ${r.penumbra==null?`0.6`:(.6*r.penumbra).toFixed(4)};
    float rect = sdBox2(uv - 0.5 * size, 0.5 * size);
    float h = hole${i}(uv, uApP[${i}]);
    ${r.feather?r.mode===`occluder`?`m *= mix(1., smoothstep(w, -w, h), smoothstep(uApS[${i}].w, -uApS[${i}].w, rect));`:`m *= smoothstep(w, -w, h) * smoothstep(uApS[${i}].w, -uApS[${i}].w, rect);`:`m *= smoothstep(w, -w, ${r.mode===`occluder`?`min(-rect, h)`:`max(rect, h)`});`}
  }
  return m;
}`),(r.mode===`occluder`?o:e).push(`ap${i}(P, L)`)}let s=e.length?e.reduce((e,t)=>`max(${e}, ${t})`):`1.`;i.push(`float lightMask${r}(vec3 P){ vec3 L = uLt[${r}]; return ${[s,...o,...a.factor?[`(${a.factor})`]:[]].join(` * `)}; }`)}return`
#define B ${r.toFixed(4)}
uniform vec3 uApC[8], uApU[8], uApV[8];
uniform vec4 uApS[8], uApP[8];
uniform vec3 uLt[2];
${n.join(`
`)}
${i.join(`
`)}
#undef B
`}function y(n,r){let i=e=>Array.from({length:8},e),a={uApC:{value:i(()=>new t)},uApU:{value:i(()=>new t(1,0,0))},uApV:{value:i(()=>new t(0,1,0))},uApS:{value:i(()=>new e)},uApP:{value:i(()=>new e)},uLt:{value:r}},o=0;for(let e of n)for(let t of e.apertures??[]){let e=o++;a.uApC.value[e].set(...t.corner),a.uApU.value[e].set(...t.u).normalize(),a.uApV.value[e].set(...t.v).normalize(),a.uApS.value[e].set(t.size[0],t.size[1],t.depth??0,t.feather??0),a.uApP.value[e].set(...t.params??[0,0,0,0])}if(o>8)throw Error(`apertures: ${o} > 8`);return a}function b(e,t,{contact:n=null}={}){return new g({uniforms:e,vertexShader:`
      attribute float aKind;
      varying vec3 vP; varying vec3 vN; varying float vKind;
      void main(){
        vec4 w = modelMatrix * vec4(position, 1.);
        vP = w.xyz; vN = normalize(mat3(modelMatrix) * normal); vKind = aKind;
        gl_Position = projectionMatrix * viewMatrix * w;
      }`,fragmentShader:`
      varying vec3 vP; varying vec3 vN; varying float vKind;
      uniform vec3 uShade[3], uSunT[3], uSpillT[3], uGlare, uBoxMin, uBoxMax, uFillDir;
      uniform float uRef[2], uI[2], uAoK;
      uniform sampler2D uLime, uTerr;
      ${f}
      ${m}
      ${v(t)}
      ${a}
      ${n??`float contact(vec3 P){ return 1.; }`}
      // A surface skips the term of the plane it lies in (facing it, within 3 mm). Distance alone also
      // skipped the last 3 mm of the neighbouring wall, and every corner drew an unshaded bright seam.
      float occ(float d, float r, float s, float self){ return self > .5 && d < 0.003 ? 1. : 1. - s * (1. - smoothstep(0., r, d)); }
      void main(){
        int k = int(vKind + 0.5);
        vec3 N = normalize(vN);
        vec2 uv = abs(N.z) > .5 ? vP.xy : abs(N.x) > .5 ? vP.zy : vP.xz;
        vec4 lw = texture2D(uLime, uv * 0.25);
        float vertical = 1. - step(.5, abs(N.y));
        float mott = (lw.r - .5) * .09 + (lw.g - .5) * .05 + (lw.b - .5) * .04 * vertical;
        vec3 rel = k == 1 ? texture2D(uTerr, vP.xz * 0.5).rgb / ${i.toFixed(3)} * (1. + mott * .5) : vec3(1. + mott);
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
        vec3 shade = uShade[k] * rel * ao * fill;
        vec3 sun = uSunT[k] * 0.94 * rel * mix(1., ao, .5);
        vec3 col = mix(mix(shade, sun, min(f0, 1.)), uGlare, min(max(f0 - 1., 0.) * .35, .6));
        col = mix(col, uSpillT[k] * 0.94 * rel * mix(1., ao, .5), min(f1, 1.));
        gl_FragColor = vec4(col, 1.);
        #include <colorspace_fragment>
        gl_FragColor.rgb += (h21(gl_FragCoord.xy) - .5) / 255.;
      }`,toneMapped:!1})}var x=`
varying vec3 vP; varying vec3 vN; varying vec2 vUv;
void main(){
  mat4 m = modelMatrix;
  #ifdef USE_INSTANCING
  m = m * instanceMatrix;
  #endif
  vec4 w = m * vec4(position, 1.);
  vP = w.xyz; vN = normalize(mat3(m) * normal); vUv = uv;
  gl_Position = projectionMatrix * viewMatrix * w;
}`;function S(e,t,n,{ao:r=null}={}){return new g({uniforms:{...e,uGrad:{value:0},...n},vertexShader:x,fragmentShader:`
      varying vec3 vP; varying vec3 vN; varying vec2 vUv;
      uniform vec3 uPaint, uWood, uCool, uSpillTint, uSunTint;
      uniform float uShadeK, uWear, uGrad, uRef[2], uI[2];
      ${f}
      ${m}
      ${v(t)}
      ${r??`float paintAo(vec3 P){ return 1.; }`}
      void main(){
        vec3 N = normalize(vN);
        // Edge band in face UV, broken up by noise: chipped paint at corners and edges only.
        vec2 e = min(vUv, 1. - vUv);
        float edge = 1. - smoothstep(0.015, 0.05, min(e.x, e.y));
        float chip = smoothstep(0.62, 0.7, vn(vP.xy * 38. + vP.zz * 21.)) * edge * uWear;
        vec3 albedo = mix(uPaint, uWood, chip);
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
      }`,toneMapped:!1})}function C({low:e,high:t,y0:n=0,y1:r=2.4,grain:i=.02,canopy:a=null}){return new g({uniforms:{uLow:{value:e},uHigh:{value:t},uY:{value:[n,r]},uGrain:{value:i},uCanopyC:{value:a?.color??t},uCanopyAmt:{value:a?.amount??0}},vertexShader:`varying vec3 vP; void main(){ vec4 w = modelMatrix * vec4(position, 1.); vP = w.xyz; gl_Position = projectionMatrix * viewMatrix * w; }`,fragmentShader:`
      varying vec3 vP; uniform vec3 uLow, uHigh, uCanopyC; uniform float uY[2], uGrain, uCanopyAmt;
      ${m}
      void main(){
        float t = smoothstep(uY[0], uY[1], vP.y);
        vec3 col = mix(uLow, uHigh, t) * (1. + (vn(vP.zy * 3. + vP.xx) - .5) * uGrain);
        float tree = smoothstep(.54, .66, fbm(vP.zy * vec2(.55, .6) + 3.1) + (clamp(vP.y / 5., 0., 1.) - .45) * .9);
        col = mix(col, uCanopyC, tree * uCanopyAmt);
        gl_FragColor = vec4(col, 1.);
        #include <colorspace_fragment>
      }`,toneMapped:!1})}function w(e,t,n,r,i,a,o=[]){let s=[...new Set([n[0],n[1],...o.flatMap(e=>[e[0],e[1]])])].filter(e=>e>=n[0]&&e<=n[1]).sort((e,t)=>e-t),c=[...new Set([r[0],r[1],...o.flatMap(e=>[e[2],e[3]])])].filter(e=>e>=r[0]&&e<=r[1]).sort((e,t)=>e-t),l=[];for(let n=0;n<s.length-1;n++)for(let r=0;r<c.length-1;r++){let[u,d]=[(s[n]+s[n+1])/2,(c[r]+c[r+1])/2];o.some(e=>u>e[0]&&u<e[1]&&d>e[2]&&d<e[3])||l.push({axis:e,at:t,a:[s[n],s[n+1]],b:[c[r],c[r+1]],facing:i,kind:a})}return l}var T={x:e=>e>0?[0,Math.PI/2,0]:[0,-Math.PI/2,0],y:e=>e>0?[-Math.PI/2,0,0]:[Math.PI/2,0,0],z:e=>e>0?[0,0,0]:[0,Math.PI,0]},E=e=>{let t=(e.a[0]+e.a[1])/2,n=(e.b[0]+e.b[1])/2;return e.axis===`x`?[e.at,n,t]:e.axis===`y`?[t,e.at,n]:[t,n,e.at]};function D(e){let t=[],n=[],r=[],i=[],a=new l;for(let o of e){let e=new s(o.a[1]-o.a[0],o.b[1]-o.b[0]);a.makeRotationFromEuler(new h(...T[o.axis](o.facing))).setPosition(...E(o)),e.applyMatrix4(a);let c=t.length/3;t.push(...e.getAttribute(`position`).array),n.push(...e.getAttribute(`normal`).array);for(let t=0;t<e.getAttribute(`position`).count;t++)r.push(o.kind);i.push(...Array.from(e.index.array,e=>e+c)),e.dispose()}let o=new d;return o.setAttribute(`position`,new c(t,3)),o.setAttribute(`normal`,new c(n,3)),o.setAttribute(`aKind`,new c(r,1)),o.setIndex(i),o}function O(e,{cut:t=null,skip:n=[]}={}){let[r,i,a]=e.min,[o,s,c]=e.max,l={floor:w(`y`,i,[r,o],[a,c],1,1),ceiling:w(`y`,s,[r,o],[a,c],-1,2),xMin:w(`x`,r,[a,c],[i,s],1,0),xMax:w(`x`,o,[a,c],[i,s],-1,0),zMin:w(`z`,a,[r,o],[i,s],1,0),zMax:w(`z`,c,[r,o],[i,s],-1,0)};if(t){let e={xMin:[`x`,r,[a,c],1],xMax:[`x`,o,[a,c],-1],zMin:[`z`,a,[r,o],1],zMax:[`z`,c,[r,o],-1]}[t.wall];l[t.wall]=w(e[0],e[1],e[2],[i,s],e[3],0,t.holes)}return Object.entries(l).filter(([e])=>!n.includes(e)).flatMap(([,e])=>e)}var k=(e,t,n=1,r=u(`sunRoom`))=>e.clone().lerp(e.clone().multiply(t).multiply({r:1/r.r,g:1/r.g,b:1/r.b}),n);function A({box:e,fillDir:r,sun:i,shade:a,sunT:s,spillT:c,refs:l,lime:d,terr:f}){return{...n(),uShade:{value:a.map(o)},uSunT:{value:s.map(o)},uSpillT:{value:c.map(o)},uGlare:{value:o(u(`sky`))},uBoxMin:{value:new t(...e.min)},uBoxMax:{value:new t(...e.max)},uFillDir:{value:new t(...r).normalize()},uRef:{value:l},uI:{value:[1,1]},uAoK:{value:1},uLime:{value:d},uTerr:{value:f},uSunCol:{value:new t(i.r,i.g,i.b)},uSunI:{value:1},uWind:{value:new t},uTime:{value:0}}}var j=()=>[u(`wallShade`),u(`floorShade`),u(`ceiling`)],M=(e,t=1)=>[k(u(`wallSun`),e,t),k(u(`wallSun`),e,t).multiplyScalar(.8),k(u(`wallSun`),e,t)],N=(e=u(`sky`),t=p(u(`sky`),u(`shade-100`),.35),n=p(u(`sky`),u(`unit-green-bamboo`),.2),r=.55)=>({uBehindLow:{value:o(e)},uBehindHigh:{value:o(t)},uBehindY:{value:[.6,6]},uCanopyC:{value:o(n)},uCanopyAmt:{value:r},uBehindDist:{value:6.4}});function P(e,{face:t=u(`screenFace`),glow:n=u(`sky`)}={}){e.uFace.value.copy(o(t)),e.uRimC.value.copy(o(p(u(`wallSun`),n,.3))),e.uWallC.value.copy(o(p(t,n,.38)))}export{A as a,w as c,S as d,b as f,N as i,D as l,v as m,M as n,k as o,y as p,P as r,O as s,j as t,C as u};