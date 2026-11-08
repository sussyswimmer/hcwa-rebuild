import{$ as e,E as t,F as n,G as r,K as i,M as a,N as o,P as s,St as c,Tt as l,W as u,_t as d,at as f,c as p,dt as m,ht as h,mt as g,nt as _,ot as v,rt as y,st as b,t as x,tt as S,vt as C,wt as w,xt as T}from"./journey-DqgUY1_x.js";import{a as E,c as D,d as ee,f as te,i as ne,l as re,m as O,n as k,o as A,p as j,r as M,t as N,u as P}from"./common-BGvMh2-M.js";function F(e,t=!1){let n=e[0].index!==null,r=new Set(Object.keys(e[0].attributes)),i=new Set(Object.keys(e[0].morphAttributes)),a={},o={},s=e[0].morphTargetsRelative,c=new b;c.morphTargetsRelative=s;let l=0;for(let u=0;u<e.length;++u){let d=e[u],f=0,p=0;if(n!==(d.index!==null))return console.error(`THREE.BufferGeometryUtils: .mergeGeometries() failed with geometry at index `+u+`. All geometries must have compatible attributes; make sure index attribute exists among all geometries, or in none of them.`),null;for(let e in d.attributes){if(!r.has(e))return console.error(`THREE.BufferGeometryUtils: .mergeGeometries() failed with geometry at index `+u+`. All geometries must have compatible attributes; make sure "`+e+`" attribute exists among all geometries, or in none of them.`),null;a[e]===void 0&&(a[e]=[]),a[e].push(d.attributes[e]),f++}if(f!==r.size)return console.error(`THREE.BufferGeometryUtils: .mergeGeometries() failed with geometry at index `+u+`. Make sure all geometries have the same number of attributes.`),null;if(s!==d.morphTargetsRelative)return console.error(`THREE.BufferGeometryUtils: .mergeGeometries() failed with geometry at index `+u+`. .morphTargetsRelative must be consistent throughout all geometries.`),null;for(let t in d.morphAttributes){if(!i.has(t))return console.error(`THREE.BufferGeometryUtils: .mergeGeometries() failed with geometry at index `+u+`.  .morphAttributes must be consistent throughout all geometries.`),null;if(d.morphAttributes[t].length!==e[0].morphAttributes[t].length)return console.error(`THREE.BufferGeometryUtils: .mergeGeometries() failed with geometry at index `+u+`. All geometries must have the same number of morph targets for the "`+t+`" morph attribute.`),null;o[t]===void 0&&(o[t]=[]),o[t].push(d.morphAttributes[t]),p++}if(p!==i.size)return console.error(`THREE.BufferGeometryUtils: .mergeGeometries() failed with geometry at index `+u+`. Make sure all geometries have the same number of morph attributes.`),null;if(t){let e;if(n)e=d.index.count;else if(d.attributes.position!==void 0)e=d.attributes.position.count;else return console.error(`THREE.BufferGeometryUtils: .mergeGeometries() failed with geometry at index `+u+`. The geometry must have either an index or a position attribute`),null;c.addGroup(l,e,u),l+=e}}if(n){let t=0,n=[];for(let r=0;r<e.length;++r){let i=e[r].index;for(let e=0;e<i.count;++e)n.push(i.getX(e)+t);t+=e[r].attributes.position.count}c.setIndex(n)}for(let e in a){let t=I(a[e]);if(!t)return console.error(`THREE.BufferGeometryUtils: .mergeGeometries() failed while trying to merge the `+e+` attribute.`),null;c.setAttribute(e,t)}for(let e in o){let t=o[e][0].length;if(t!==0){c.morphAttributes=c.morphAttributes||{},c.morphAttributes[e]=[];for(let n=0;n<t;++n){let t=[];for(let r=0;r<o[e].length;++r)t.push(o[e][r][n]);let r=I(t);if(!r)return console.error(`THREE.BufferGeometryUtils: .mergeGeometries() failed while trying to merge the `+e+` morphAttribute.`),null;c.morphAttributes[e].push(r)}}}return c}function I(e){let t,n,r,i=-1,a=0;for(let o=0;o<e.length;++o){let s=e[o];if(t===void 0&&(t=s.array.constructor),t!==s.array.constructor)return console.error(`THREE.BufferGeometryUtils: .mergeAttributes() failed. BufferAttribute.array must be of consistent array types across matching attributes.`),null;if(n===void 0&&(n=s.itemSize),n!==s.itemSize)return console.error(`THREE.BufferGeometryUtils: .mergeAttributes() failed. BufferAttribute.itemSize must be consistent across matching attributes.`),null;if(r===void 0&&(r=s.normalized),r!==s.normalized)return console.error(`THREE.BufferGeometryUtils: .mergeAttributes() failed. BufferAttribute.normalized must be consistent across matching attributes.`),null;if(i===-1&&(i=s.gpuType),i!==s.gpuType)return console.error(`THREE.BufferGeometryUtils: .mergeAttributes() failed. BufferAttribute.gpuType must be consistent across matching attributes.`),null;a+=s.count*n}let o=new t(a),s=new v(o,n,r),c=0;for(let t=0;t<e.length;++t){let r=e[t];if(r.isInterleavedBufferAttribute){let e=c/n;for(let t=0,i=r.count;t<i;t++)for(let i=0;i<n;i++){let n=r.getComponent(t,i);s.setComponent(t+e,i,n)}}else o.set(r.array,c);c+=r.count*n}return i!==void 0&&(s.gpuType=i),s}var L={w:.95,h:2.2,t:.04,frame:.06,reveal:.14},R={"green-bamboo":`dotTre`,"cau-han":`vomCau`,"little-rose":`hoaThi`};function z(e){let t=e===`little-rose`;return{transomPattern:t?`open`:R[e]??`hoaThi`,spillTint:t?0:.12,corridorSun:!t}}function B({wx:e,zc:t,unit:n,open:r=72,hinge:i=1}){let a=n===`little-rose`,o=L.w/2,s=L.h+L.frame,c={z0:t-1.5*u,z1:t+1.5*u,y0:s+.04,y1:s+.04+u};return{wx:e,zc:t,unit:n,hinge:i,open:a?0:r,emblem:R[n]??`hoaThi`,holes:[[t-o-L.frame,t+o+L.frame,0,s],[c.z0,c.z1,c.y0,c.y1]],doorway:{z0:t-o,z1:t+o,y0:0,y1:L.h},transom:c}}function ie(e){let{wx:t,zc:n}=e,r=L.w/2,i=L.frame,a=L.reveal,o=(e,t,n,r,i,a)=>new f(e,t,n).translate(r,i,a),s=F([o(a,L.h+i,i,t+a/2-.02,(L.h+i)/2,n-r-i/2),o(a,L.h+i,i,t+a/2-.02,(L.h+i)/2,n+r+i/2),o(a,i,L.w+2*i,t+a/2-.02,L.h+i/2,n)]),c=e.hinge??1,l=[o(L.t,L.h,L.w,0,L.h/2,-c*r)];for(let e of[-1,1])for(let[t,n]of[[.54,.84],[1.6,.96]])for(let i of[-.24,.24])l.push(o(.012,n,.34,e*(L.t/2+.004),t,-c*r+i));return{frame:s,leaf:F(l),hinge:[t+.03,0,n+c*r],side:c}}var V={open:0,dotTre:1,vomCau:2,hoaThi:3},H=e=>e.toFixed(4),ae=`
uniform vec4 uDoors[8];  // zc, pattern id, leaf angle (rad), hinge side (+1 the +z jamb, −1 the −z)
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
  // Transom: three blocks, two face tests through the block's depth.
  float tr = 1.;
  for (int s = 0; s < 2; s++) {
    float t = (wx + float(s) * ${H(i)} - P.x) / L.x;
    vec3 R = P + L * t;
    vec2 uv = vec2(R.z - (D.x - ${H(1.5*u)}), R.y - uDoorWall.y);
    float rect = sdBox2(uv - vec2(${H(1.5*u)}, ${H(.5*u)}), vec2(${H(1.5*u)}, ${H(.5*u)}));
    if (D.y < 0.5) { float fe = 0.05 + w; tr *= smoothstep(fe, -fe, rect); }
    else tr *= smoothstep(w, -w, max(rect, doorPat(D.y, mod(uv, ${H(u)}) - ${H(.5*u)})));
  }
  // Doorway, only while the door stands open, through the 14 cm reveal, past the leaf.
  float dw = 0.;
  if (D.z > 0.01) {
    dw = 1.;
    for (int s = 0; s < 2; s++) {
      float t = (wx + float(s) * ${H(L.reveal)} - P.x) / L.x;
      vec3 R = P + L * t;
      float rect = sdBox2(vec2(R.z - D.x, R.y - ${H(L.h/2)}), vec2(${H(L.w/2)}, ${H(L.h/2)}));
      dw *= smoothstep(w, -w, rect);
    }
    // The leaf swings out into the corridor about its hinge jamb.
    float h = D.w < 0. ? -1. : 1.;
    vec3 C = vec3(wx + 0.03, 0., D.x + h * ${H(L.w/2)});
    vec3 U = vec3(-sin(D.z), 0., -h * cos(D.z)), N = vec3(h * cos(D.z), 0., -sin(D.z));
    float ln = dot(L, N);
    if (abs(ln) > 1e-4) {
      float t = dot(C - P, N) / ln;
      if (t > 0.) {
        vec3 R = P + L * t;
        vec2 uv = vec2(dot(R - C, U), R.y);
        float leaf = sdBox2(uv - vec2(${H(L.w/2)}, ${H(L.h/2)}), vec2(${H(L.w/2)}, ${H(L.h/2)}));
        dw *= smoothstep(-w, w, leaf);
      }
    }
  }
  return max(tr, dw);
}
`,oe=`
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
`;function se(){return{uDoors:{value:Array.from({length:8},()=>new l)},uDoorN:{value:0},uDoorWall:{value:new l},uNoSun:{value:[new l,new l]}}}var U={hoaThi:`return sdHoaThi(c);`,hoaThiMissing:`return all(equal(id, uOne)) ? min(sdHoaThi(c), sdBox2(c, vec2(0.5 * B - 0.004))) : sdHoaThi(c);`,dotTre:`return sdDotTre(c);`,vomCau:`return sdVomCau(c);`};function W(t,n,{corner:a,u:o,v:s,size:l,pattern:d=`hoaThi`,mode:f=`backlit`,depth:p=i,own:m={},light:_=n.length-1}){let v=new w(...o).normalize(),y=new w(...s).normalize(),b=new w().crossVectors(v,y),x={...t,uO:{value:new w(...a)},uU:{value:v},uV:{value:y},uNf:{value:b},uSize:{value:[l[0],l[1]]},uDepth:{value:p},uOne:{value:[-1,-1]},uFace:{value:new w},uFaceSun:{value:new w},uWallC:{value:new w},uRimC:{value:new w},uBehindLow:{value:new w},uBehindHigh:{value:new w},uBehindY:{value:[0,3]},uHalo:{value:.35},uCanopyC:{value:new w},uCanopyAmt:{value:0},uBehindDist:{value:6},uFall:{value:new w(0,1,0)},...m},S=new c({uniforms:x,vertexShader:`varying vec3 vP; void main(){ vec4 w = modelMatrix * vec4(position, 1.); vP = w.xyz; gl_Position = projectionMatrix * viewMatrix * w; }`,fragmentShader:`
      varying vec3 vP;
      uniform vec3 uO, uU, uV, uNf, uFace, uFaceSun, uWallC, uRimC, uBehindLow, uBehindHigh, uCanopyC, uFall;
      uniform vec2 uSize, uOne, uBehindY;
      uniform float uDepth, uHalo, uRef[2], uI[2], uCanopyAmt, uBehindDist;
      ${r}
      ${e}
      ${O(n)}
      #define B ${u.toFixed(4)}
      vec2 local(vec3 P){ return vec2(dot(P - uO, uU), dot(P - uO, uV)); }
      float pat(vec2 uv){ vec2 c = mod(uv, B) - 0.5 * B, id = floor(uv / B); ${U[d]} }
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
          float lit = max(dot(n, uLt[${_}]), 0.) * uI[${_}];
          vec3 wall = mix(uWallC * (0.85 + 0.25 * b / tb), uRimC, clamp(lit * 1.8, 0., 1.));`}
          vec3 hole = mix(wall, behind(vP + rd * (uBehindDist / max(abs(toward), 1e-3)), uv.x), smoothstep(fw, -fw, d2));
          col = mix(hole, col, smoothstep(-fw, fw, d));
        }
        gl_FragColor = vec4(col, 1.);
        #include <colorspace_fragment>
      }
      #undef B`,toneMapped:!1}),T=new h(new C(l[0],l[1]),S),E=new g().makeBasis(v,y,b);return T.quaternion.setFromRotationMatrix(E),T.position.copy(new w(...a).addScaledVector(v,l[0]/2).addScaledVector(y,l[1]/2)),T}function G({lime:e,terr:t,beats:n,open:r=0,t:c=.25}){if(n.length>8)throw Error(`corridor: ${n.length} doors > 8`);let l=p(n),u=x,d=l.screen,f={min:[u.screenX,0,l.end],max:[u.wallX,u.ceiling,l.start]},g=a();o(`corridor`,c,g);let v=s({az:0,el:57}),b={mode:`window`,pattern:`hoaThi`,corner:[d.sx,d.y0,d.z0],u:[0,0,1],v:[0,1,0],size:[d.z1-d.z0,d.y1-d.y0],depth:i},w={mode:`occluder`,pattern:`canopy`,corner:[d.sx-3.2,-2,l.end-6],u:[0,0,1],v:[0,1,0],size:[l.start-l.end+12,9],depth:0,params:[.9,.56,1.3,4.1]},T=[{dir:g.dir,apertures:[b,w],glsl:oe,factor:`bayFree(P)`},{dir:v,glsl:ae,mask:`doorsMask(P, L)`}],O=S(_(`sky`),_(`wallSun`),.45),F=[O,O.clone().multiplyScalar(.86),O],I={...E({box:f,fillDir:[-1,0,0],sun:g.color,shade:N(),sunT:k(g.color,.5),spillT:F,refs:[Math.abs(g.dir.x),v.y],lime:e,terr:t}),...j(T,[g.dir,v]),...se()};I.uI.value=[1,.9];let R=l.doors.map(e=>({...B({wx:u.wallX,zc:e.zc,unit:e.unit,open:0,hinge:-1}),beat:e}));I.uDoorN.value=R.length,I.uDoorWall.value.set(u.wallX,R[0]?.transom.y0??2.3,0,0);let H=0;R.forEach((e,t)=>{let n=z(e.unit);I.uDoors.value[t].set(e.zc,V[n.transomPattern]??V.hoaThi,0,e.hinge),!n.corridorSun&&H<2&&I.uNoSun.value[H++].set(e.beat.bay[0],e.beat.bay[1],0,0)});let U=[l.end,l.start],G=[u.screenX,u.wallX],K=u.ceiling,q=u.pier,J=.12,ce=[...D(`y`,0,G,U,1,1),...D(`y`,K,G,U,-1,2),...D(`x`,u.wallX,U,[0,K],-1,0,R.flatMap(e=>e.holes)),...D(`x`,u.screenX,U,[0,K],1,0,[[d.z0,d.z1,d.y0,d.y1]]),...D(`z`,l.start,G,[0,K],-1,0),...D(`z`,l.end,G,[0,K],1,0),...l.piers.flatMap(e=>[...D(`z`,e+J,[u.wallX-q,u.wallX],[0,K],1,0),...D(`z`,e-J,[u.wallX-q,u.wallX],[0,K],-1,0),...D(`x`,u.wallX-q,[e-J,e+J],[0,K],-1,0)])],le=new h(re(ce),te(I,T)),Y=W(I,T,{corner:[d.sx,d.y0,d.z1],u:[0,0,-1],v:[0,1,0],size:b.size,pattern:`hoaThi`,light:0,own:ne()});M(Y.material.uniforms);let X=[le,Y],Z=[],Q=[];for(let e of R){let t=_(`unit-`+e.unit),n=e.unit===`little-rose`,r=ee(I,T,{uPaint:{value:y(t)},uWood:{value:y(_(`wood`))},uCool:{value:y(_(`wallShade`))},uSunTint:{value:y(A(_(`lime-50`),g.color))},uSpillTint:{value:y(S(_(`lime-50`),O,.5))},uShadeK:{value:.58},uWear:{value:n?.3:.6}}),i=ie(e),a=new m;if(a.position.set(...i.hinge),a.add(new h(i.leaf,r)),X.push(new h(i.frame,r),a),Z.push(a),n)Q.push(null);else{let n=new h(new C(L.w+.4,L.h+.3),P({low:S(_(`sun-200`),t,.08),high:S(_(`sun-100`),t,.05),y0:0,y1:2.4}));n.rotation.y=-Math.PI/2,n.position.set(u.wallX+.9,L.h/2,e.zc),n.visible=!1,X.push(n),Q.push(n)}let o=W(I,T,{corner:[u.wallX,e.transom.y0,e.transom.z0],u:[0,0,1],v:[0,1,0],size:[e.transom.z1-e.transom.z0,e.transom.y1-e.transom.y0],pattern:e.emblem,light:1,own:{uBehindLow:{value:y(n?O:S(_(`sun-100`),t,.06))},uBehindHigh:{value:y(_(n?`sky`:`sun-100`))},uBehindY:{value:[2,3]}}}),s=o.material.uniforms;s.uFace.value.copy(y(S(_(`wallShade`),_(`screenFace`),.7))),s.uWallC.value.copy(y(S(_(`wallShade`),_(`screenFace`),.35))),s.uRimC.value.copy(y(O)),s.uHalo.value=.18,X.push(o)}let $=(e,t)=>{let n=R[e];if(!n)return;let r=n.unit===`little-rose`?0:Math.min(Math.max(t,0),72)*Math.PI/180;Z[e].rotation.y=n.hinge*r,I.uDoors.value[e].z=r,Q[e]&&(Q[e].visible=r>.01)};return R.forEach((e,t)=>$(t,r)),{box:f,lights:T,U:I,objects:X,sun:g,layout:l,specs:R,setDoor:$,setSun:(e,t=1)=>{o(`corridor`,e,g);let n=k(g.color,.5);I.uSunT.value.forEach((e,t)=>e.set(n[t].r,n[t].g,n[t].b)),I.uSunCol.value.set(g.color.r,g.color.g,g.color.b),I.uRef.value[0]=Math.max(Math.abs(g.dir.x),.05),I.uI.value[0]=t,I.uSunI.value=t},subject:[b],glow:[b],shafts:1.4}}async function K({gl:e,tier:r,beats:i,textures:a}){let o=G({...a,beats:i}),s=new T;s.add(...o.objects);let c=n(e.renderer,o.U,{box:o.box,tier:r,ortho:e.ortho,mask:{glsl:O(o.lights.slice(0,1)),call:`lightMask0(p)`}});c.uniforms.uDensity.value=1.4;let l=new d(t.fov.landscape,1,.05,60),u=o.U.uApP.value[1],f=u.clone();return{...o,scene:s,camera:l,shafts:c,setTier:e=>c.setTier(e),sway(e){u.z=f.z+.2*Math.sin(e*.31+.6)+.06*Math.sin(e*1.07),u.w=f.w+.1*Math.sin(e*.23+2.1)},compile:async()=>{await e.renderer.compileAsync(s,l);for(let t of c.scenes)await e.renderer.compileAsync(t,e.ortho)},dispose(){s.traverse(e=>{e.isMesh&&(e.geometry.dispose(),e.material.dispose())}),c.dispose()}}}export{K as buildCorridor};