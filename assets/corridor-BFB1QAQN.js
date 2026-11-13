import{t as e}from"./main-V8EaS64I.js";import{F as t,O as n,P as r,S as i,_ as a,a as o,d as s,g as c,i as l,k as u,u as d,x as f}from"./three-Dgmw-8ZC.js";import{$ as p,Mt as m,Nt as h,St as g,bt as _,ct as v,d as y,et as b,f as x,g as S,h as ee,i as C,jt as w,kt as T,l as te,m as ne,mt as E,nt as D,p as re,pt as O,q as k,r as ie,rt as A,s as ae,tt as oe,u as j,w as se,xt as M}from"./wall-poster-C-wKsBqz.js";import{_ as ce,c as le,d as N,g as ue,h as de,i as fe,l as pe,m as me,n as he,p as P,r as ge,s as _e,t as F,u as ve}from"./common-CYgvHfiY.js";var ye={open:0,dotTre:1,vomCau:2,hoaThi:3},I=e=>e.toFixed(4),L=1.5,be=`
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
  // ${L}× the size about its own centre (the pool lands where it did, ${L}× larger, its shape and its crisp
  // penumbra kept): the slots and the arch read as the unit's light, not as marks on the tiles.
  float wt = 0.0015 + 0.0035 * t0;
  float tr = 1.;
  for (int s = 0; s < 2; s++) {
    float t = (wx + float(s) * ${I(g)} - P.x) / L.x;
    vec3 R = P + L * t;
    vec2 uv = vec2(R.z - D.x, R.y - uDoorWall.y - ${I(.5*_)}) / ${I(L)} + vec2(${I(1.5*_)}, ${I(.5*_)});
    float rect = sdBox2(uv - vec2(${I(1.5*_)}, ${I(.5*_)}), vec2(${I(1.5*_)}, ${I(.5*_)}));
    // Little Rose's (pattern 0): its transom glows with plain daylight on the wall, and casts nothing on its
    // threshold, no pool of any kind (critique r1): its floor has only the corridor's untinted shade.
    if (D.y < 0.5) tr = 0.;
    else tr *= smoothstep(wt, -wt, max(rect, doorPat(D.y, mod(uv, ${I(_)}) - ${I(.5*_)})));
  }
  // Doorway, only while the door stands open, through the 14 cm reveal, past the leaf.
  float dw = 0.;
  if (D.z > 0.01) {
    dw = 1.;
    for (int s = 0; s < 2; s++) {
      float t = (wx + float(s) * ${I(y.reveal)} - P.x) / L.x;
      vec3 R = P + L * t;
      float rect = sdBox2(vec2(R.z - D.x, R.y - ${I(y.h/2)}), vec2(${I(y.w/2)}, ${I(y.h/2)}));
      dw *= smoothstep(w, -w, rect);
    }
    // The leaf swings about its hinge jamb, out into the corridor or (|w| 2) into the room.
    float h = D.w < 0. ? -1. : 1., sw = abs(D.w) > 1.5 ? -1. : 1.;
    vec3 C = vec3(wx + 0.03, 0., D.x + h * ${I(y.w/2)});
    vec3 U = vec3(-sw * sin(D.z), 0., -h * cos(D.z)), N = vec3(h * cos(D.z), 0., -sw * sin(D.z));
    float ln = dot(L, N);
    if (abs(ln) > 1e-4) {
      float t = dot(C - P, N) / ln;
      if (t > 0.) {
        vec3 R = P + L * t;
        vec2 uv = vec2(dot(R - C, U), R.y);
        float leaf = sdBox2(uv - vec2(${I(y.w/2)}, ${I(y.h/2)}), vec2(${I(y.w/2)}, ${I(y.h/2)}));
        dw *= smoothstep(-w, w, leaf);
      }
    }
  }
  return max(tr, dw);
}
`,xe=`
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
`;function Se(){return{uDoors:{value:Array.from({length:8},()=>new t)},uDoorN:{value:0},uDoorWall:{value:new t},uNoSun:{value:[new t,new t]}}}var Ce={hoaThi:`return sdHoaThi(c);`,hoaThiMissing:`return all(equal(id, uOne)) ? min(sdHoaThi(c), sdBox2(c, vec2(0.5 * B - 0.004))) : sdHoaThi(c);`,dotTre:`return sdDotTre(c);`,vomCau:`return sdVomCau(c);`};function we(e,t,{corner:n,u:o,v:s,size:l,pattern:d=`hoaThi`,mode:f=`backlit`,depth:p=g,own:m={},light:h=t.length-1}){let y=new r(...o).normalize(),b=new r(...s).normalize(),x=new r().crossVectors(y,b),S={...e,uO:{value:new r(...n)},uU:{value:y},uV:{value:b},uNf:{value:x},uSize:{value:[l[0],l[1]]},uDepth:{value:p},uOne:{value:[-1,-1]},uFace:{value:new r},uFaceSun:{value:new r},uWallC:{value:new r},uRimC:{value:new r},uBehindLow:{value:new r},uBehindHigh:{value:new r},uBehindY:{value:[0,3]},uHalo:{value:.35},uCanopyC:{value:new r},uCanopyAmt:{value:0},uBehindDist:{value:6},uFall:{value:new r(0,1,0)},...m},ee=new u({uniforms:S,vertexShader:`varying vec3 vP; void main(){ vec4 w = modelMatrix * vec4(position, 1.); vP = w.xyz; gl_Position = projectionMatrix * viewMatrix * w; }`,fragmentShader:`
      varying vec3 vP;
      uniform vec3 uO, uU, uV, uNf, uFace, uFaceSun, uWallC, uRimC, uBehindLow, uBehindHigh, uCanopyC, uFall;
      uniform vec2 uSize, uOne, uBehindY;
      uniform float uDepth, uHalo, uRef[2], uI[2], uCanopyAmt, uBehindDist;
      ${M}
      ${T}
      ${j(t)}
      ${v([`Co`])}
      #define B ${_.toFixed(4)}
      vec2 local(vec3 P){ return vec2(dot(P - uO, uU), dot(P - uO, uV)); }
      float pat(vec2 uv){ vec2 c = mod(uv, B) - 0.5 * B, id = floor(uv / B); ${Ce[d]} }
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
        // The Blender concrete (walls.js; live only): each block its own corner of the map, its tone relief, and its
        // relief under the light from above (backlit) or the sun (frontlit). The face tokens hold.
        vec3 Nf = uNf * sign(toward), Nb = Nf;
        if (uWallK > 0.) {
          vec4 w = wallTap(uWCo, uv / ${A.block.toFixed(1)} + h22(id), .5);
          Nb = wallNB(Nf, uU, uV, w.rg, uWallK);
          tone *= mix(1., wallRel(w.b, .8), uWallK) * wallFill(Nb, Nf, vec3(0., .914, 0.) + .406 * Nf, .8);
        }
        vec3 inward = -uNf * sign(toward);
        float tb = uDepth / max(abs(toward), 1e-3);
        ${f===`frontlit`?`
        vec3 facing = uNf * sign(toward); // the face's normal on the viewer's side
        float n0 = max(dot(facing, uLt[0]), 0.);
        float f0 = n0 > 0. ? n0 / uRef[0] * lightMask0(vP + facing * 0.002) * uI[0] : 0.;
        vec3 col = mix(uFace, uFaceSun * wallSun(Nb, Nf, uLt[0]), min(f0, 1.)) * tone * joint;`:`
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
          float lit = max(dot(n, uLt[${h}]), 0.) * uI[${h}];
          vec3 wall = mix(uWallC * (0.85 + 0.25 * b / tb), uRimC, clamp(lit * 1.8, 0., 1.));`}
          vec3 hole = mix(wall, behind(vP + rd * (uBehindDist / max(abs(toward), 1e-3)), uv.x), smoothstep(fw, -fw, d2));
          col = mix(hole, col, smoothstep(-fw, fw, d));
        }
        gl_FragColor = vec4(col, 1.);
        #include <colorspace_fragment>
      }
      #undef B`,toneMapped:!1}),C=new a(new i(l[0],l[1]),ee),w=new c().makeBasis(y,b,x);return C.quaternion.setFromRotationMatrix(w),C.position.copy(new r(...n).addScaledVector(y,l[0]/2).addScaledVector(b,l[1]/2)),C}var R={x:99.75,y:2.45,dz:-1.6,r:.7,hub:.13,blade:[.14,.7,.0625],pitch:.16,omega:2.1,ceiling:3.1,rod:.6},z={solid:0,fan:1};function B(e){let t=e>>>0;return()=>(t=t+1831565813>>>0,((t^t>>>15)*(t|1)>>>0)/4294967296)}var V=class{constructor(){this.pos=[],this.nor=[],this.uv=[],this.col=[],this.piv=[],this.k=[],this.idx=[]}add(e,t,{col:n,kind:r=z.solid,pivot:i=[0,0,0],seed:a=0,gloss:o=0}){t&&e.applyMatrix4(t);let s=e.getAttribute(`position`),c=e.getAttribute(`normal`),l=e.getAttribute(`uv`),u=this.pos.length/3;for(let e=0;e<s.count;e++)this.pos.push(s.getX(e),s.getY(e),s.getZ(e)),this.nor.push(c.getX(e),c.getY(e),c.getZ(e)),this.uv.push(l?l.getX(e):0,l?l.getY(e):0),this.col.push(n.r,n.g,n.b),this.piv.push(...i),this.k.push(r,a,o,0);let d=e.index?Array.from(e.index.array):[...Array(s.count).keys()];for(let e of d)this.idx.push(e+u);e.dispose()}geometry(){let e=new l;return e.setAttribute(`position`,new d(this.pos,3)),e.setAttribute(`normal`,new d(this.nor,3)),e.setAttribute(`uv`,new d(this.uv,2)),e.setAttribute(`aCol`,new d(this.col,3)),e.setAttribute(`aPivot`,new d(this.piv,3)),e.setAttribute(`aK`,new d(this.k,4)),e.setIndex(this.idx),e.computeBoundingSphere(),e}},H=()=>new c,U=(e,t,n)=>H().makeTranslation(e,t,n);function W(){let[e,t,n]=R.blade,r=t-n,i=[],a=[],o=[[e,-n*.7],[r,-n]];for(let e=1;e<6;e++){let t=-Math.PI/2+e*Math.PI/6;o.push([r+n*Math.cos(t),n*Math.sin(t)])}o.push([r,n],[e,n*.7]);for(let[e,t]of o)i.push(e,t,0);i.push(e,0,0);let s=o.length;for(let e=0;e<s-1;e++)a.push(s,e,e+1);a.push(s,s-1,0);let c=new l;return c.setAttribute(`position`,new d(i,3)),c.setAttribute(`normal`,new d(Array.from({length:s+1},()=>[0,0,1]).flat(),3)),c.setIndex(a),c}function G(e,t){let n=[],r=[],i=e.length;for(let r=0;r<=t;r++){let i=r/t*Math.PI*2,a=Math.cos(i),o=Math.sin(i);for(let[t,r]of e)n.push(t*o,r,t*a)}for(let e=0;e<t;e++)for(let t=0;t<i-1;t++){let n=e*i+t,a=n+i;r.push(n,a,n+1,a,a+1,n+1)}let a=new l;return a.setAttribute(`position`,new d(n,3)),a.setIndex(r),a.computeVertexNormals(),a}var K=(e,t,n,r)=>G([[e,-n/2],[t,n/2]],r);function q(e,t,n,r){let i=m(`tile-jade`),a=m(`tile-cream`),o=R.ceiling,s=[t,R.y,n];e.add(G([[0,-.025],[.085,-.025],[.075,.025],[0,.025]],16),U(t,o-.025,n),{col:i,gloss:.5});let c=o-.05,l=R.y+.075;e.add(K(.013,.013,c-l,6),U(t,(c+l)/2,n),{col:i});let u=G([[0,-.07],[.07,-.066],[.125,-.035],[.135,.005],[.115,.05],[.05,.075],[0,.08]],16);e.add(u,U(t,R.y,n),{col:i,kind:z.fan,pivot:s,seed:r,gloss:.7});for(let o=0;o<3;o++){let c=H().makeRotationY(o*2*Math.PI/3),l=W();e.add(l,U(t,R.y-.01,n).multiply(c).multiply(H().makeRotationX(-Math.PI/2+R.pitch)),{col:a,kind:z.fan,pivot:s,seed:r,gloss:.25});let u=K(.012,.012,.12,5).rotateZ(Math.PI/2).translate(.13,-.004,0);e.add(u,U(t,R.y-.01,n).multiply(c),{col:i,kind:z.fan,pivot:s,seed:r})}}function Te({lay:e}){let t=new V,n=B(29),r=e.doors.filter(e=>e.unit!==`little-rose`).map(e=>({z:e.zc+R.dz,seed:n()*6.283}));for(let e of r)q(t,R.x,e.z,e.seed);return{geometry:t.geometry(),fans:r}}var Ee=e=>`
uniform vec4 uFans[${Math.max(e,1)}];
uniform float uFanA;
float fanBlades(vec2 uv){
float d = 1e3;
for (int i = 0; i < ${Math.max(e,1)}; i++) {
vec4 F = uFans[i];
if (F.w < .5) continue;
vec2 q = uv - F.xy;
float r = length(q);
if (r > ${(R.r+.05).toFixed(3)}) continue;
float a = atan(q.y, q.x) + uFanA + F.z - 1.5707963;
float s = 2.0943951, k = a - s * floor(a / s + .5);
vec2 bq = r * vec2(cos(k), sin(k));
d = min(d, sdBox2(bq - vec2(${((R.blade[0]+R.blade[1])/2).toFixed(3)}, 0.), vec2(${((R.blade[1]-R.blade[0])/2).toFixed(3)}, ${R.blade[2].toFixed(4)})));
d = min(d, r - ${R.hub.toFixed(3)});
}
return d;
}
`;function De({C:e,lay:t}){return{mode:`occluder`,pattern:`fans`,corner:[e.screenX,R.y,t.start],u:[0,0,-1],v:[1,0,0],size:[t.start-t.end,e.wallX-e.screenX],depth:0,thin:!0}}function Oe(e,t,n){return new u({uniforms:{...e,...n},side:2,vertexShader:`
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
${M}
${T}
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
}`,toneMapped:!1})}function ke(e,t,n,r){let i=Oe(e,t,{uShadeK:{value:.62},uCool:{value:h(m(`ceilingWarm`))},uSunTint:{value:h(r)}}),o=new a(n.geometry,i);return o.renderOrder=-1,o.frustumCulled=!1,o}function J(e,t,n,r,i=!1){let a=O(n,e,t);return a.anisotropy=r,i&&(a.wrapT=o),a}var Y=()=>{let e=e=>[e.r,e.g,e.b],t=e(m(`tile-cream`));return Object.fromEntries([`cream`,`ochre`,`indigo`,`jade`].map(n=>[n,e(m(`tile-`+n)).map((e,n)=>e/t[n])]))};function Ae(e,t){return new Promise((n,r)=>{let i;try{i=new Worker(new URL(`/hcwa-rebuild/assets/gach-worker-DC9Ico8l.js`,``+import.meta.url),{type:`module`})}catch(e){r(e);return}let a=()=>i.terminate();i.onmessage=({data:e})=>{a(),n(e)},i.onerror=e=>{a(),r(e)},i.postMessage({size:e,P:t})})}async function X({slice:t=!0,worker:n=!1,aniso:r=4,size:i=512,rows:a=16,onSlice:o=null}={}){let s=Y(),c=i,l=i/2;if(n&&typeof Worker==`function`)try{let[e,t]=await Ae(i,s);return o?.(0),{field:J(c,c,e,r),border:J(c,l,t,r,!0),worker:!0}}catch{}let{gachBaker:u}=await e(async()=>{let{gachBaker:e}=await import(`./gach-bake-Bsr8Uwcj.js`);return{gachBaker:e}},[]),d=async(e,n,r,i,c)=>{let l=performance.now(),d=u(e,n,r,i,c,s);for(let e=0;e<r;e+=a)d(e,Math.min(r,e+a)),o?.(performance.now()-l),t&&await E(),l=performance.now();return d.data},f=await d(`field`,c,c,2,2),p=await d(`border`,c,l,2,1);return{field:J(c,c,f,r),border:J(c,l,p,r,!0),worker:!1}}var Z={unit:`green-bamboo`,len:2.21,gap:.05,clear:1.5};function je(e,t=S){let n=e.doors.find(e=>e.unit===Z.unit),r=e.doors.find(e=>e.unit===`little-rose`)?.bay;if(!n)return null;let i=Math.round(n.zc/F.tile)*F.tile,a=F.threshold+Z.gap,o=t.wallX-(t.wallX-t.screenX-F.runner)/2;for(let[e,t,s,c]of[[i+a,i+a+Z.len,i+a+Z.len,-1],[i-a-Z.len,i-a,i-a-Z.len,1]])if(!(e<n.bay[0]||t>n.bay[1])&&(!r||t+Z.clear<=r[0]||e-Z.clear>=r[1]))return{z0:e,z1:t,from:s,dir:c,x:o};return null}function Q({lime:e,terr:n,gach:r=null,beats:o,open:c=0,t:l=.25,fanShadows:u=!0,fanAngle:f=.6,traces:_=!1}){if(o.length>8)throw Error(`corridor: ${o.length} doors > 8`);let v=se(o),T=S,E=v.screen,D={min:[T.screenX,0,v.end],max:[T.wallX,T.ceiling,v.start]},O=p();b(`corridor`,l,O);let k=oe({az:0,el:57}),A={mode:`window`,pattern:`hoaThi`,corner:[E.sx,E.y0,E.z0],u:[0,0,1],v:[0,1,0],size:[E.z1-E.z0,E.y1-E.y0],depth:g},j={mode:`occluder`,pattern:`canopy`,corner:[E.sx-3.2,-2,v.end-6],u:[0,0,1],v:[0,1,0],size:[v.start-v.end+12,9],depth:0,params:[.9,.56,1.3,4.1]},M=Te({lay:v}),F=u&&M.fans.length>0,I=[{dir:O.dir,apertures:F?[A,j,De({C:T,lay:v})]:[A,j],glsl:xe+(F?Ee(M.fans.length):``),factor:`bayFree(P)`},{dir:k,glsl:be,mask:`doorsMask(P, L)`}],L=w(m(`sky`),m(`wallSun`),.45),Ce=[L,L.clone(),L],z={...ve({box:D,fillDir:[-1,0,0],sun:O.color,shade:he(),sunT:ge(O.color),spillT:Ce,refs:[Math.abs(O.dir.x),k.y],lime:e,terr:n}),...te(I,[O.dir,k]),...Se(),...fe({gach:r,runnerX:T.screenX,doorWall:T.wallX,half:y.w/2+y.frame,sun:O.color}),uFans:{value:Array.from({length:Math.max(M.fans.length,1)},(e,n)=>M.fans[n]?new t(v.start-M.fans[n].z,R.x-T.screenX,M.fans[n].seed,+!!F):new t)},uFanA:{value:f}};z.uI.value=[1,1];let B=_?je(v,T):null;B&&z.uTrace.value.set(B.from,B.dir,B.x,1);let V=v.doors.map(e=>({...ne({wx:T.wallX,zc:e.zc,unit:e.unit,open:0,...e.unit===`little-rose`?{hinge:-1}:{hinge:1,inward:!0}}),beat:e}));z.uDoorN.value=V.length,z.uDoorWall.value.set(T.wallX,V[0]?.transom.y0??2.3,0,0);let H=0;V.forEach((e,t)=>{let n=re(e.unit);z.uDoors.value[t].set(e.zc,ye[n.transomPattern]??ye.hoaThi,0,e.hinge*(e.swing<0?2:1)),!n.corridorSun&&H<2&&z.uNoSun.value[H++].set(e.beat.bay[0],e.beat.bay[1],0,0)});let U=[v.end,v.start],W=[T.screenX,T.wallX],G=T.ceiling,K=T.pier,q=.12,Oe=[...P(`y`,0,W,U,1,1),...P(`y`,G,W,U,-1,2),...P(`x`,T.wallX,U,[0,G],-1,0,V.flatMap(e=>e.holes)),...P(`x`,T.screenX,U,[0,G],1,0,[[E.z0,E.z1,E.y0,E.y1]]),...P(`z`,v.start,W,[0,G],-1,0),...P(`z`,v.end,W,[0,G],1,0),...v.piers.flatMap(e=>[...P(`z`,e+q,[T.wallX-K,T.wallX],[0,G],1,0),...P(`z`,e-q,[T.wallX-K,T.wallX],[0,G],-1,0),...P(`x`,T.wallX-K,[e-q,e+q],[0,G],-1,0)])],J=new a(me(Oe),ce(z,I,{house:!0,nearFade:!0})),Y=we(z,I,{corner:[E.sx,E.y0,E.z1],u:[0,0,-1],v:[0,1,0],size:A.size,pattern:`hoaThi`,light:0,own:pe()});_e(Y.material.uniforms);let Ae=ke(z,I,M,N(m(`lime-50`),O.color,.6)),X=[J,Y,Ae],Z=[],Q=(e,t,n)=>{let r=e.getAttribute(`position`).count,i=new Float32Array(r*4);for(let e=0;e<r;e++)i.set([t.r,t.g,t.b,n],e*4);return e.setAttribute(`aPaint`,new d(i,4)),e},Me=()=>({uWood:{value:h(m(`tile-cream`))},uCool:{value:h(m(`wallShade`))},uSunTint:{value:h(N(m(`lime-50`),O.color))},uSpillTint:{value:h(w(m(`lime-50`),L,.5))},uShadeK:{value:.58}}),Ne=[],$=[];for(let e of V){let t=m(`unit-`+e.unit),n=e.unit===`little-rose`,r=n?.3:.6,o=x(e);Z.push(Q(o.frame,t,r));let c=new s;if(c.position.set(...o.hinge),n)Z.push(Q(o.leaf.clone().translate(...o.hinge),t,r)),o.leaf.dispose();else{let e=ue(z,I,{...Me(),uPaint:{value:h(t)},uWear:{value:r}},{leafWear:!0,nearFade:!0,oldCoat:!0});c.add(new a(o.leaf,e)),X.push(c)}if(Ne.push(c),n)$.push(null);else{let n=new a(new i(y.w+.4,y.h+.3),de({low:w(m(`sun-200`),t,.08),high:w(m(`sun-100`),t,.05),y0:0,y1:y.h,dapple:{zc:e.zc,half:y.w/2,amount:.07}}));n.rotation.y=-Math.PI/2,n.position.set(T.wallX+1.05,y.h/2,e.zc),n.visible=!1,X.push(n),$.push(n)}let l=we(z,I,{corner:[T.wallX,e.transom.y0,e.transom.z0],u:[0,0,1],v:[0,1,0],size:[e.transom.z1-e.transom.z0,e.transom.y1-e.transom.y0],pattern:e.emblem,light:1,own:{uBehindLow:{value:h(n?L:w(m(`sun-100`),t,.06))},uBehindHigh:{value:h(m(n?`sky`:`sun-100`))},uBehindY:{value:[2,3]}}}),u=l.material.uniforms;u.uFace.value.copy(h(w(m(`wallShade`),m(`screenFace`),.7))),u.uWallC.value.copy(h(w(m(`wallShade`),m(`screenFace`),.35))),u.uRimC.value.copy(h(L)),u.uHalo.value=.18,X.push(l)}Z.length&&X.push(new a(ee(Z),ue(z,I,Me(),{vertexPaint:!0,leafWear:!0,nearFade:!0,oldCoat:!0})));let Pe={uSunTint:{value:h(N(m(`lime-50`),O.color,.6))},uSpillTint:{value:h(w(m(`lime-50`),L,.5))},uShadeK:{value:.62}},Fe=C({...z,...ie()},I,Pe,ae(v).map((e,t)=>e.wall===`end`?{id:e.id,w:e.w,at:[e.x,e.y,e.z+.005],rotY:0,seed:3+t*7}:{id:e.id,w:e.w,at:[T.wallX-.005,e.y,e.z],seed:3+t*7}));Fe&&X.push(Fe);let Ie=(e,t)=>{let n=V[e];if(!n)return;let r=n.unit===`little-rose`?0:Math.min(Math.max(t,0),96)*Math.PI/180;Ne[e].rotation.y=n.hinge*n.swing*r,z.uDoors.value[e].z=r,$[e]&&($[e].visible=r>.01)};return V.forEach((e,t)=>Ie(t,c)),{box:D,lights:I,U:z,objects:X,sun:O,layout:v,specs:V,setDoor:Ie,setSun:(e,t=1)=>{b(`corridor`,e,O);let n=ge(O.color);z.uSunT.value.forEach((e,t)=>e.set(n[t].r,n[t].g,n[t].b)),le(z,O.color);let r=N(m(`lime-50`),O.color,.6);Ae.material.uniforms.uSunTint.value.set(r.r,r.g,r.b),Pe.uSunTint.value.set(r.r,r.g,r.b),z.uSunCol.value.set(O.color.r,O.color.g,O.color.b),z.uRef.value[0]=Math.max(Math.abs(O.dir.x),.05),z.uI.value[0]=t,z.uSunI.value=t},animate:e=>{z.uFanA.value=(f+e*R.omega)%(Math.PI*2)},setFanShadows:e=>{for(let t of z.uFans.value)(t.x||t.y)&&(t.w=e&&F?1:0)},lr:v.doors.findIndex(e=>e.unit===`little-rose`),props:M,posters:Fe,subject:[A],glow:[A],shafts:1.4}}async function Me({gl:e,tier:t,beats:r,textures:i,traces:a=!1,onSlice:o=null,gachSize:s=t>=2?512:256}){let c=await X({worker:!0,size:s,aniso:t>=2?4:2,rows:s>=512?16:8,onSlice:o}),l=Q({...i,gach:c,beats:r,fanShadows:t>=2,traces:a}),u=new n;u.add(...l.objects);let d={...l.lights[0],apertures:l.lights[0].apertures.filter(e=>e.pattern!==`fans`)},p=D(e.renderer,l.U,{box:l.box,tier:t,ortho:e.ortho,mask:{glsl:j([d]),call:`lightMask0(p)`}});p.uniforms.uDensity.value=1.4;let m=new f(k.fov.landscape,1,.05,60),h=l.U.uApP.value[1],g=h.clone();return{...l,scene:u,camera:m,shafts:p,bakedInWorker:c.worker,setTier:e=>{p.setTier(e),l.setFanShadows(e>=2)},sway(e){h.z=g.z+.2*Math.sin(e*.31+.6)+.06*Math.sin(e*1.07),h.w=g.w+.1*Math.sin(e*.23+2.1)},compile:async()=>{await e.renderer.compileAsync(u,m);for(let t of p.scenes)await e.renderer.compileAsync(t,e.ortho)},dispose(){u.traverse(e=>{e.isMesh&&(e.geometry.dispose(),e.material.dispose())}),c.field.dispose(),c.border.dispose(),p.dispose()}}}export{Me as buildCorridor};