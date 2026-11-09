import{t as e}from"./main-Cs3bRrRB.js";import{$ as t,B as n,Dt as r,Ft as i,K as a,M as o,Mt as s,Ot as c,Pt as l,R as u,Tt as d,V as f,_t as p,bt as m,ct as h,dt as g,et as _,ft as v,gt as y,jt as b,l as ee,q as x,t as S,tt as C,ut as w,wt as T,xt as te,z as E}from"./journey-pyRzNSax.js";import{C as ne,S as re,_ as ie,b as ae,c as oe,d as D,g as se,h as ce,i as le,l as ue,m as de,n as fe,p as O,r as pe,s as me,t as k,u as he,v as A,w as j,x as ge,y as _e}from"./common-DOLhauL3.js";var ve={open:0,dotTre:1,vomCau:2,hoaThi:3},M=e=>e.toFixed(4),N=1.5,ye=`
uniform vec4 uDoors[8];  // zc, pattern id, leaf angle (rad), hinge side (+1 the +z jamb, −1 the −z; ±2: into the room)
uniform int uDoorN;
uniform vec4 uDoorWall;             // wx, transom y0, 0, 0
float doorPat(float id, vec2 c){
  if (id < 0.5) return -1.;
  if (id < 1.5) return sdDotTre(c);
  if (id < 2.5) return sdVomCau(c);
  return sdHoaThi(c);
}
float doorsMask(vec3 P, vec3 L){
  float wx = uDoorWall.x;
  if (P.x > wx - 0.001 || L.x < 1e-4) return 0.;
  float t0 = (wx - P.x) / L.x;
  float qz = P.z + L.z * t0;
  int k = -1; float bd = 1e9;
  for (int i = 0; i < 8; i++) {
    if (i >= uDoorN) break;
    float dz = abs(qz - uDoors[i].x);
    if (dz < bd) { bd = dz; k = i; }
  }
  if (k < 0) return 0.;
  vec4 D = uDoors[k];
  float w = 0.0025 + 0.0093 * t0 * 0.6;
  // Transom: three blocks, two face tests through the block's depth. Its emblem lands on the plain threshold
  // (critique r1): drawn crisper than the doorway's soft spill, so the slots and the arch read on the floor.
  // Critique r2: at 1440 the Đốt tre pool was three pairs of 6 px dashes. The light is cast as through a transom
  // ${N}× the size about its own centre (the pool lands where it did, ${N}× larger, its shape and its crisp
  // penumbra kept): the slots and the arch read as the unit's light, not as marks on the tiles.
  float wt = 0.0015 + 0.0035 * t0;
  float tr = 1.;
  for (int s = 0; s < 2; s++) {
    float t = (wx + float(s) * ${M(C)} - P.x) / L.x;
    vec3 R = P + L * t;
    vec2 uv = vec2(R.z - D.x, R.y - uDoorWall.y - ${M(.5*t)}) / ${M(N)} + vec2(${M(1.5*t)}, ${M(.5*t)});
    float rect = sdBox2(uv - vec2(${M(1.5*t)}, ${M(.5*t)}), vec2(${M(1.5*t)}, ${M(.5*t)}));
    // Little Rose's (pattern 0): its transom glows with plain daylight on the wall, and casts nothing on its
    // threshold, no pool of any kind (critique r1): its floor has only the corridor's untinted shade.
    if (D.y < 0.5) tr = 0.;
    else tr *= smoothstep(wt, -wt, max(rect, doorPat(D.y, mod(uv, ${M(t)}) - ${M(.5*t)})));
  }
  // Doorway, only while the door stands open, through the 14 cm reveal, past the leaf.
  float dw = 0.;
  if (D.z > 0.01) {
    dw = 1.;
    for (int s = 0; s < 2; s++) {
      float t = (wx + float(s) * ${M(A.reveal)} - P.x) / L.x;
      vec3 R = P + L * t;
      float rect = sdBox2(vec2(R.z - D.x, R.y - ${M(A.h/2)}), vec2(${M(A.w/2)}, ${M(A.h/2)}));
      dw *= smoothstep(w, -w, rect);
    }
    // The leaf swings about its hinge jamb, out into the corridor or (|w| 2) into the room.
    float h = D.w < 0. ? -1. : 1., sw = abs(D.w) > 1.5 ? -1. : 1.;
    vec3 C = vec3(wx + 0.03, 0., D.x + h * ${M(A.w/2)});
    vec3 U = vec3(-sw * sin(D.z), 0., -h * cos(D.z)), N = vec3(h * cos(D.z), 0., -sw * sin(D.z));
    float ln = dot(L, N);
    if (abs(ln) > 1e-4) {
      float t = dot(C - P, N) / ln;
      if (t > 0.) {
        vec3 R = P + L * t;
        vec2 uv = vec2(dot(R - C, U), R.y);
        float leaf = sdBox2(uv - vec2(${M(A.w/2)}, ${M(A.h/2)}), vec2(${M(A.w/2)}, ${M(A.h/2)}));
        dw *= smoothstep(-w, w, leaf);
      }
    }
  }
  return max(tr, dw);
}
`,be=`
uniform vec4 uNoSun[2]; // z0, z1 of a bay (z0 < z1); an empty range when unused
float bayFree(vec3 P){
  float m = 1.;
  for (int i = 0; i < 2; i++) {
    vec4 r = uNoSun[i];
    if (r.y <= r.x) continue;
    m *= 1. - smoothstep(-0.3, 0.3, min(P.z - r.x, r.y - P.z));
  }
  return m;
}
`;function xe(){return{uDoors:{value:Array.from({length:8},()=>new i)},uDoorN:{value:0},uDoorWall:{value:new i},uNoSun:{value:[new i,new i]}}}var P={hoaThi:`return sdHoaThi(c);`,hoaThiMissing:`return all(equal(id, uOne)) ? min(sdHoaThi(c), sdBox2(c, vec2(0.5 * B - 0.004))) : sdHoaThi(c);`,dotTre:`return sdDotTre(c);`,vomCau:`return sdVomCau(c);`};function F(e,n,{corner:r,u:i,v:a,size:o,pattern:u=`hoaThi`,mode:f=`backlit`,depth:p=C,own:m={},light:g=n.length-1}){let v=new l(...i).normalize(),y=new l(...a).normalize(),b=new l().crossVectors(v,y),ee={...e,uO:{value:new l(...r)},uU:{value:v},uV:{value:y},uNf:{value:b},uSize:{value:[o[0],o[1]]},uDepth:{value:p},uOne:{value:[-1,-1]},uFace:{value:new l},uFaceSun:{value:new l},uWallC:{value:new l},uRimC:{value:new l},uBehindLow:{value:new l},uBehindHigh:{value:new l},uBehindY:{value:[0,3]},uHalo:{value:.35},uCanopyC:{value:new l},uCanopyAmt:{value:0},uBehindDist:{value:6},uFall:{value:new l(0,1,0)},...m},x=new s({uniforms:ee,vertexShader:`varying vec3 vP; void main(){ vec4 w = modelMatrix * vec4(position, 1.); vP = w.xyz; gl_Position = projectionMatrix * viewMatrix * w; }`,fragmentShader:`
      varying vec3 vP;
      uniform vec3 uO, uU, uV, uNf, uFace, uFaceSun, uWallC, uRimC, uBehindLow, uBehindHigh, uCanopyC, uFall;
      uniform vec2 uSize, uOne, uBehindY;
      uniform float uDepth, uHalo, uRef[2], uI[2], uCanopyAmt, uBehindDist;
      ${_}
      ${h}
      ${j(n)}
      #define B ${t.toFixed(4)}
      vec2 local(vec3 P){ return vec2(dot(P - uO, uU), dot(P - uO, uV)); }
      float pat(vec2 uv){ vec2 c = mod(uv, B) - 0.5 * B, id = floor(uv / B); ${P[u]} }
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
        ${f===`frontlit`?`
        vec3 facing = uNf * sign(toward); // the face's normal on the viewer's side
        float n0 = max(dot(facing, uLt[0]), 0.);
        float f0 = n0 > 0. ? n0 / uRef[0] * lightMask0(vP + facing * 0.002) * uI[0] : 0.;
        vec3 col = mix(uFace, uFaceSun, min(f0, 1.)) * tone * joint;`:`
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
          ${f===`frontlit`?`
          float lit = max(dot(n, uLt[0]), 0.) * uI[0] * lightMask0(H);
          vec3 wall = mix(uWallC, uRimC, clamp(lit * 1.6, 0., 1.));`:`
          float lit = max(dot(n, uLt[${g}]), 0.) * uI[${g}];
          vec3 wall = mix(uWallC * (0.85 + 0.25 * b / tb), uRimC, clamp(lit * 1.8, 0., 1.));`}
          vec3 hole = mix(wall, behind(vP + rd * (uBehindDist / max(abs(toward), 1e-3)), uv.x), smoothstep(fw, -fw, d2));
          col = mix(hole, col, smoothstep(-fw, fw, d));
        }
        gl_FragColor = vec4(col, 1.);
        #include <colorspace_fragment>
      }
      #undef B`,toneMapped:!1}),S=new d(new c(o[0],o[1]),x),w=new T().makeBasis(v,y,b);return S.quaternion.setFromRotationMatrix(w),S.position.copy(new l(...r).addScaledVector(v,o[0]/2).addScaledVector(y,o[1]/2)),S}var I={x:99.75,y:2.45,dz:-1.6,r:.7,hub:.13,blade:[.14,.7,.0625],pitch:.16,omega:2.1,ceiling:3.1,rod:.6},L={solid:0,fan:1};function R(e){let t=e>>>0;return()=>(t=t+1831565813>>>0,((t^t>>>15)*(t|1)>>>0)/4294967296)}var z=class{constructor(){this.pos=[],this.nor=[],this.uv=[],this.col=[],this.piv=[],this.k=[],this.idx=[]}add(e,t,{col:n,kind:r=L.solid,pivot:i=[0,0,0],seed:a=0,gloss:o=0}){t&&e.applyMatrix4(t);let s=e.getAttribute(`position`),c=e.getAttribute(`normal`),l=e.getAttribute(`uv`),u=this.pos.length/3;for(let e=0;e<s.count;e++)this.pos.push(s.getX(e),s.getY(e),s.getZ(e)),this.nor.push(c.getX(e),c.getY(e),c.getZ(e)),this.uv.push(l?l.getX(e):0,l?l.getY(e):0),this.col.push(n.r,n.g,n.b),this.piv.push(...i),this.k.push(r,a,o,0);let d=e.index?Array.from(e.index.array):[...Array(s.count).keys()];for(let e of d)this.idx.push(e+u);e.dispose()}geometry(){let e=new y;return e.setAttribute(`position`,new m(this.pos,3)),e.setAttribute(`normal`,new m(this.nor,3)),e.setAttribute(`uv`,new m(this.uv,2)),e.setAttribute(`aCol`,new m(this.col,3)),e.setAttribute(`aPivot`,new m(this.piv,3)),e.setAttribute(`aK`,new m(this.k,4)),e.setIndex(this.idx),e.computeBoundingSphere(),e}},B=()=>new T,V=(e,t,n)=>B().makeTranslation(e,t,n);function H(){let[e,t,n]=I.blade,r=t-n,i=[],a=[],o=[[e,-n*.7],[r,-n]];for(let e=1;e<6;e++){let t=-Math.PI/2+e*Math.PI/6;o.push([r+n*Math.cos(t),n*Math.sin(t)])}o.push([r,n],[e,n*.7]);for(let[e,t]of o)i.push(e,t,0);i.push(e,0,0);let s=o.length;for(let e=0;e<s-1;e++)a.push(s,e,e+1);a.push(s,s-1,0);let c=new y;return c.setAttribute(`position`,new m(i,3)),c.setAttribute(`normal`,new m(Array.from({length:s+1},()=>[0,0,1]).flat(),3)),c.setIndex(a),c}function U(e,t){let n=[],r=[],i=e.length;for(let r=0;r<=t;r++){let i=r/t*Math.PI*2,a=Math.cos(i),o=Math.sin(i);for(let[t,r]of e)n.push(t*o,r,t*a)}for(let e=0;e<t;e++)for(let t=0;t<i-1;t++){let n=e*i+t,a=n+i;r.push(n,a,n+1,a,a+1,n+1)}let a=new y;return a.setAttribute(`position`,new m(n,3)),a.setIndex(r),a.computeVertexNormals(),a}var W=(e,t,n,r)=>U([[e,-n/2],[t,n/2]],r);function G(e,t,n,r){let i=g(`tile-jade`),a=g(`tile-cream`),o=I.ceiling,s=[t,I.y,n];e.add(U([[0,-.025],[.085,-.025],[.075,.025],[0,.025]],16),V(t,o-.025,n),{col:i,gloss:.5});let c=o-.05,l=I.y+.075;e.add(W(.013,.013,c-l,6),V(t,(c+l)/2,n),{col:i});let u=U([[0,-.07],[.07,-.066],[.125,-.035],[.135,.005],[.115,.05],[.05,.075],[0,.08]],16);e.add(u,V(t,I.y,n),{col:i,kind:L.fan,pivot:s,seed:r,gloss:.7});for(let o=0;o<3;o++){let c=B().makeRotationY(o*2*Math.PI/3),l=H();e.add(l,V(t,I.y-.01,n).multiply(c).multiply(B().makeRotationX(-Math.PI/2+I.pitch)),{col:a,kind:L.fan,pivot:s,seed:r,gloss:.25});let u=W(.012,.012,.12,5).rotateZ(Math.PI/2).translate(.13,-.004,0);e.add(u,V(t,I.y-.01,n).multiply(c),{col:i,kind:L.fan,pivot:s,seed:r})}}function Se({lay:e}){let t=new z,n=R(29),r=e.doors.filter(e=>e.unit!==`little-rose`).map(e=>({z:e.zc+I.dz,seed:n()*6.283}));for(let e of r)G(t,I.x,e.z,e.seed);return{geometry:t.geometry(),fans:r}}var Ce=e=>`
uniform vec4 uFans[${Math.max(e,1)}];
uniform float uFanA;
float fanBlades(vec2 uv){
float d = 1e3;
for (int i = 0; i < ${Math.max(e,1)}; i++) {
vec4 F = uFans[i];
if (F.w < .5) continue;
vec2 q = uv - F.xy;
float r = length(q);
if (r > ${(I.r+.05).toFixed(3)}) continue;
float a = atan(q.y, q.x) + uFanA + F.z - 1.5707963;
float s = 2.0943951, k = a - s * floor(a / s + .5);
vec2 bq = r * vec2(cos(k), sin(k));
d = min(d, sdBox2(bq - vec2(${((I.blade[0]+I.blade[1])/2).toFixed(3)}, 0.), vec2(${((I.blade[1]-I.blade[0])/2).toFixed(3)}, ${I.blade[2].toFixed(4)})));
d = min(d, r - ${I.hub.toFixed(3)});
}
return d;
}
`;function we({C:e,lay:t}){return{mode:`occluder`,pattern:`fans`,corner:[e.screenX,I.y,t.start],u:[0,0,-1],v:[1,0,0],size:[t.start-t.end,e.wallX-e.screenX],depth:0,thin:!0}}function K(e,t,n){return new s({uniforms:{...e,...n},side:2,vertexShader:`
attribute vec3 aCol, aPivot; attribute vec4 aK;
uniform float uFanA;
varying vec3 vP, vN, vCol; varying vec4 vK;
vec3 rotY(vec3 v, float a){ float c = cos(a), s = sin(a); return vec3(c * v.x + s * v.z, v.y, -s * v.x + c * v.z); }
void main(){
vec3 p = position, n = normal;
if (aK.x > .5) { float a = uFanA + aK.y; p = aPivot + rotY(p - aPivot, a); n = rotY(n, a); }
vec4 w = modelMatrix * vec4(p, 1.);
vP = w.xyz; vN = normalize(mat3(modelMatrix) * n); vCol = aCol; vK = aK;
gl_Position = projectionMatrix * viewMatrix * w;
}`,fragmentShader:`
varying vec3 vP, vN, vCol; varying vec4 vK;
uniform vec3 uCool, uSunTint; uniform float uShadeK, uRef[2], uI[2];
${_}
${h}
${j(t)}
void main(){
vec3 N = normalize(vN);
if (!gl_FrontFacing) N = -N;
float ndl = dot(N, uLt[0]);
float f0 = ndl > 0. && uI[0] > 0. ? min(ndl / uRef[0], 1.4) * lightMask0(vP) * uI[0] : 0.;
float sky = .8 + .2 * N.y;
vec3 shade = mix(vCol * uShadeK, uCool * uShadeK, .12) * sky;
vec3 col = mix(shade, vCol * uSunTint, min(f0, 1.));
vec3 V = normalize(cameraPosition - vP), R = reflect(-uLt[0], N);
col += uSunTint * pow(max(dot(R, V), 0.), 24.) * vK.z * min(f0, 1.) * .5;
gl_FragColor = vec4(col, 1.);
#include <colorspace_fragment>
}`,toneMapped:!1})}function Te(e,t,n,r){let i=K(e,t,{uShadeK:{value:.62},uCool:{value:v(g(`ceilingWarm`))},uSunTint:{value:v(r)}}),a=new d(n.geometry,i);return a.renderOrder=-1,a.frustumCulled=!1,a}function q(e,t,n,r,i=!1){let o=a(n,e,t);return o.anisotropy=r,i&&(o.wrapT=p),o}var Ee=()=>{let e=e=>[e.r,e.g,e.b],t=e(g(`tile-cream`));return Object.fromEntries([`cream`,`ochre`,`indigo`,`jade`].map(n=>[n,e(g(`tile-`+n)).map((e,n)=>e/t[n])]))};function De(e,t){return new Promise((n,r)=>{let i;try{i=new Worker(new URL(`/hcwa-rebuild/assets/gach-worker-DC9Ico8l.js`,``+import.meta.url),{type:`module`})}catch(e){r(e);return}let a=()=>i.terminate();i.onmessage=({data:e})=>{a(),n(e)},i.onerror=e=>{a(),r(e)},i.postMessage({size:e,P:t})})}async function J({slice:t=!0,worker:n=!1,aniso:r=4,size:i=512,rows:a=16,onSlice:o=null}={}){let s=Ee(),c=i,l=i/2;if(n&&typeof Worker==`function`)try{let[e,t]=await De(i,s);return o?.(0),{field:q(c,c,e,r),border:q(c,l,t,r,!0),worker:!0}}catch{}let{gachBaker:u}=await e(async()=>{let{gachBaker:e}=await import(`./gach-bake-Bsr8Uwcj.js`);return{gachBaker:e}},[]),d=async(e,n,r,i,c)=>{let l=performance.now(),d=u(e,n,r,i,c,s);for(let e=0;e<r;e+=a)d(e,Math.min(r,e+a)),o?.(performance.now()-l),t&&await x(),l=performance.now();return d.data},f=await d(`field`,c,c,2,2),p=await d(`border`,c,l,2,1);return{field:q(c,c,f,r),border:q(c,l,p,r,!0),worker:!1}}var Y={unit:`green-bamboo`,len:2.21,gap:.05,clear:1.5};function Oe(e,t=S){let n=e.doors.find(e=>e.unit===Y.unit),r=e.doors.find(e=>e.unit===`little-rose`)?.bay;if(!n)return null;let i=Math.round(n.zc/k.tile)*k.tile,a=k.threshold+Y.gap,o=t.wallX-(t.wallX-t.screenX-k.runner)/2;for(let[e,t,s,c]of[[i+a,i+a+Y.len,i+a+Y.len,-1],[i-a-Y.len,i-a,i-a-Y.len,1]])if(!(e<n.bay[0]||t>n.bay[1])&&(!r||t+Y.clear<=r[0]||e-Y.clear>=r[1]))return{z0:e,z1:t,from:s,dir:c,x:o};return null}function X({lime:e,terr:t,gach:r=null,beats:a,open:o=0,t:s=.25,fanShadows:l=!0,fanAngle:f=.6,traces:p=!1}){if(a.length>8)throw Error(`corridor: ${a.length} doors > 8`);let h=ee(a),_=S,y=h.screen,b={min:[_.screenX,0,h.end],max:[_.wallX,_.ceiling,h.start]},x=u();E(`corridor`,s,x);let T=n({az:0,el:57}),k={mode:`window`,pattern:`hoaThi`,corner:[y.sx,y.y0,y.z0],u:[0,0,1],v:[0,1,0],size:[y.z1-y.z0,y.y1-y.y0],depth:C},j={mode:`occluder`,pattern:`canopy`,corner:[y.sx-3.2,-2,h.end-6],u:[0,0,1],v:[0,1,0],size:[h.start-h.end+12,9],depth:0,params:[.9,.56,1.3,4.1]},M=Se({lay:h}),N=l&&M.fans.length>0,P=[{dir:x.dir,apertures:N?[k,j,we({C:_,lay:h})]:[k,j],glsl:be+(N?Ce(M.fans.length):``),factor:`bayFree(P)`},{dir:T,glsl:ye,mask:`doorsMask(P, L)`}],L=w(g(`sky`),g(`wallSun`),.45),R=[L,L.clone(),L],z={...he({box:b,fillDir:[-1,0,0],sun:x.color,shade:fe(),sunT:pe(x.color),spillT:R,refs:[Math.abs(x.dir.x),T.y],lime:e,terr:t}),...ne(P,[x.dir,T]),...xe(),...le({gach:r,runnerX:_.screenX,doorWall:_.wallX,half:A.w/2+A.frame,sun:x.color}),uFans:{value:Array.from({length:Math.max(M.fans.length,1)},(e,t)=>M.fans[t]?new i(h.start-M.fans[t].z,I.x-_.screenX,M.fans[t].seed,+!!N):new i)},uFanA:{value:f}};z.uI.value=[1,1];let B=p?Oe(h,_):null;B&&z.uTrace.value.set(B.from,B.dir,B.x,1);let V=h.doors.map(e=>({...ge({wx:_.wallX,zc:e.zc,unit:e.unit,open:0,...e.unit===`little-rose`?{hinge:-1}:{hinge:1,inward:!0}}),beat:e}));z.uDoorN.value=V.length,z.uDoorWall.value.set(_.wallX,V[0]?.transom.y0??2.3,0,0);let H=0;V.forEach((e,t)=>{let n=ae(e.unit);z.uDoors.value[t].set(e.zc,ve[n.transomPattern]??ve.hoaThi,0,e.hinge*(e.swing<0?2:1)),!n.corridorSun&&H<2&&z.uNoSun.value[H++].set(e.beat.bay[0],e.beat.bay[1],0,0)});let U=[h.end,h.start],W=[_.screenX,_.wallX],G=_.ceiling,K=_.pier,q=.12,Ee=[...O(`y`,0,W,U,1,1),...O(`y`,G,W,U,-1,2),...O(`x`,_.wallX,U,[0,G],-1,0,V.flatMap(e=>e.holes)),...O(`x`,_.screenX,U,[0,G],1,0,[[y.z0,y.z1,y.y0,y.y1]]),...O(`z`,h.start,W,[0,G],-1,0),...O(`z`,h.end,W,[0,G],1,0),...h.piers.flatMap(e=>[...O(`z`,e+q,[_.wallX-K,_.wallX],[0,G],1,0),...O(`z`,e-q,[_.wallX-K,_.wallX],[0,G],-1,0),...O(`x`,_.wallX-K,[e-q,e+q],[0,G],-1,0)])],De=new d(de(Ee),ie(z,P,{house:!0,nearFade:!0})),J=F(z,P,{corner:[y.sx,y.y0,y.z1],u:[0,0,-1],v:[0,1,0],size:k.size,pattern:`hoaThi`,light:0,own:ue()});me(J.material.uniforms);let Y=Te(z,P,M,D(g(`lime-50`),x.color,.6)),X=[De,J,Y],Z=[],ke=(e,t,n)=>{let r=e.getAttribute(`position`).count,i=new Float32Array(r*4);for(let e=0;e<r;e++)i.set([t.r,t.g,t.b,n],e*4);return e.setAttribute(`aPaint`,new m(i,4)),e},Ae=()=>({uWood:{value:v(g(`tile-cream`))},uCool:{value:v(g(`wallShade`))},uSunTint:{value:v(D(g(`lime-50`),x.color))},uSpillTint:{value:v(w(g(`lime-50`),L,.5))},uShadeK:{value:.58}}),je=[],Q=[];for(let e of V){let t=g(`unit-`+e.unit),n=e.unit===`little-rose`,r=n?.3:.6,i=_e(e);Z.push(ke(i.frame,t,r));let a=new te;if(a.position.set(...i.hinge),n)Z.push(ke(i.leaf.clone().translate(...i.hinge),t,r)),i.leaf.dispose();else{let e=se(z,P,{...Ae(),uPaint:{value:v(t)},uWear:{value:r}},{leafWear:!0,nearFade:!0,oldCoat:!0});a.add(new d(i.leaf,e)),X.push(a)}if(je.push(a),n)Q.push(null);else{let n=new d(new c(A.w+.4,A.h+.3),ce({low:w(g(`sun-200`),t,.08),high:w(g(`sun-100`),t,.05),y0:0,y1:A.h,dapple:{zc:e.zc,half:A.w/2,amount:.07}}));n.rotation.y=-Math.PI/2,n.position.set(_.wallX+1.05,A.h/2,e.zc),n.visible=!1,X.push(n),Q.push(n)}let o=F(z,P,{corner:[_.wallX,e.transom.y0,e.transom.z0],u:[0,0,1],v:[0,1,0],size:[e.transom.z1-e.transom.z0,e.transom.y1-e.transom.y0],pattern:e.emblem,light:1,own:{uBehindLow:{value:v(n?L:w(g(`sun-100`),t,.06))},uBehindHigh:{value:v(g(n?`sky`:`sun-100`))},uBehindY:{value:[2,3]}}}),s=o.material.uniforms;s.uFace.value.copy(v(w(g(`wallShade`),g(`screenFace`),.7))),s.uWallC.value.copy(v(w(g(`wallShade`),g(`screenFace`),.35))),s.uRimC.value.copy(v(L)),s.uHalo.value=.18,X.push(o)}Z.length&&X.push(new d(re(Z),se(z,P,Ae(),{vertexPaint:!0,leafWear:!0,nearFade:!0,oldCoat:!0})));let $=(e,t)=>{let n=V[e];if(!n)return;let r=n.unit===`little-rose`?0:Math.min(Math.max(t,0),96)*Math.PI/180;je[e].rotation.y=n.hinge*n.swing*r,z.uDoors.value[e].z=r,Q[e]&&(Q[e].visible=r>.01)};return V.forEach((e,t)=>$(t,o)),{box:b,lights:P,U:z,objects:X,sun:x,layout:h,specs:V,setDoor:$,setSun:(e,t=1)=>{E(`corridor`,e,x);let n=pe(x.color);z.uSunT.value.forEach((e,t)=>e.set(n[t].r,n[t].g,n[t].b)),oe(z,x.color);let r=D(g(`lime-50`),x.color,.6);Y.material.uniforms.uSunTint.value.set(r.r,r.g,r.b),z.uSunCol.value.set(x.color.r,x.color.g,x.color.b),z.uRef.value[0]=Math.max(Math.abs(x.dir.x),.05),z.uI.value[0]=t,z.uSunI.value=t},animate:e=>{z.uFanA.value=(f+e*I.omega)%(Math.PI*2)},setFanShadows:e=>{for(let t of z.uFans.value)(t.x||t.y)&&(t.w=e&&N?1:0)},lr:h.doors.findIndex(e=>e.unit===`little-rose`),props:M,subject:[k],glow:[k],shafts:1.4}}async function Z({gl:e,tier:t,beats:n,textures:i,traces:a=!1,onSlice:s=null}){let c=await J({worker:!0,size:t>=2?512:256,aniso:t>=2?4:2,rows:t>=2?16:8,onSlice:s}),l=X({...i,gach:c,beats:n,fanShadows:t>=2,traces:a}),u=new b;u.add(...l.objects);let d={...l.lights[0],apertures:l.lights[0].apertures.filter(e=>e.pattern!==`fans`)},p=f(e.renderer,l.U,{box:l.box,tier:t,ortho:e.ortho,mask:{glsl:j([d]),call:`lightMask0(p)`}});p.uniforms.uDensity.value=1.4;let m=new r(o.fov.landscape,1,.05,60),h=l.U.uApP.value[1],g=h.clone();return{...l,scene:u,camera:m,shafts:p,bakedInWorker:c.worker,setTier:e=>{p.setTier(e),l.setFanShadows(e>=2)},sway(e){h.z=g.z+.2*Math.sin(e*.31+.6)+.06*Math.sin(e*1.07),h.w=g.w+.1*Math.sin(e*.23+2.1)},compile:async()=>{await e.renderer.compileAsync(u,m);for(let t of p.scenes)await e.renderer.compileAsync(t,e.ortho)},dispose(){u.traverse(e=>{e.isMesh&&(e.geometry.dispose(),e.material.dispose())}),c.field.dispose(),c.border.dispose(),p.dispose()}}}export{Z as buildCorridor};