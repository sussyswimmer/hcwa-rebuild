import{Ct as e,Dt as t,Et as n,F as r,I as i,J as a,K as o,L as s,O as c,P as l,_t as u,at as d,bt as f,ct as p,gt as m,it as h,l as g,lt as _,pt as v,q as y,rt as b,st as x,t as S,tt as C,wt as w,yt as T}from"./journey-P5d6QuFC.js";import{a as E,c as D,d as ee,f as te,i as ne,l as re,m as O,n as k,o as A,p as j,r as M,t as N,u as P}from"./common-BTXyzCHS.js";function F(e,t=!1){let n=e[0].index!==null,r=new Set(Object.keys(e[0].attributes)),i=new Set(Object.keys(e[0].morphAttributes)),a={},o={},s=e[0].morphTargetsRelative,c=new _;c.morphTargetsRelative=s;let l=0;for(let u=0;u<e.length;++u){let d=e[u],f=0,p=0;if(n!==(d.index!==null))return console.error(`THREE.BufferGeometryUtils: .mergeGeometries() failed with geometry at index `+u+`. All geometries must have compatible attributes; make sure index attribute exists among all geometries, or in none of them.`),null;for(let e in d.attributes){if(!r.has(e))return console.error(`THREE.BufferGeometryUtils: .mergeGeometries() failed with geometry at index `+u+`. All geometries must have compatible attributes; make sure "`+e+`" attribute exists among all geometries, or in none of them.`),null;a[e]===void 0&&(a[e]=[]),a[e].push(d.attributes[e]),f++}if(f!==r.size)return console.error(`THREE.BufferGeometryUtils: .mergeGeometries() failed with geometry at index `+u+`. Make sure all geometries have the same number of attributes.`),null;if(s!==d.morphTargetsRelative)return console.error(`THREE.BufferGeometryUtils: .mergeGeometries() failed with geometry at index `+u+`. .morphTargetsRelative must be consistent throughout all geometries.`),null;for(let t in d.morphAttributes){if(!i.has(t))return console.error(`THREE.BufferGeometryUtils: .mergeGeometries() failed with geometry at index `+u+`.  .morphAttributes must be consistent throughout all geometries.`),null;if(d.morphAttributes[t].length!==e[0].morphAttributes[t].length)return console.error(`THREE.BufferGeometryUtils: .mergeGeometries() failed with geometry at index `+u+`. All geometries must have the same number of morph targets for the "`+t+`" morph attribute.`),null;o[t]===void 0&&(o[t]=[]),o[t].push(d.morphAttributes[t]),p++}if(p!==i.size)return console.error(`THREE.BufferGeometryUtils: .mergeGeometries() failed with geometry at index `+u+`. Make sure all geometries have the same number of morph attributes.`),null;if(t){let e;if(n)e=d.index.count;else if(d.attributes.position!==void 0)e=d.attributes.position.count;else return console.error(`THREE.BufferGeometryUtils: .mergeGeometries() failed with geometry at index `+u+`. The geometry must have either an index or a position attribute`),null;c.addGroup(l,e,u),l+=e}}if(n){let t=0,n=[];for(let r=0;r<e.length;++r){let i=e[r].index;for(let e=0;e<i.count;++e)n.push(i.getX(e)+t);t+=e[r].attributes.position.count}c.setIndex(n)}for(let e in a){let t=I(a[e]);if(!t)return console.error(`THREE.BufferGeometryUtils: .mergeGeometries() failed while trying to merge the `+e+` attribute.`),null;c.setAttribute(e,t)}for(let e in o){let t=o[e][0].length;if(t!==0){c.morphAttributes=c.morphAttributes||{},c.morphAttributes[e]=[];for(let n=0;n<t;++n){let t=[];for(let r=0;r<o[e].length;++r)t.push(o[e][r][n]);let r=I(t);if(!r)return console.error(`THREE.BufferGeometryUtils: .mergeGeometries() failed while trying to merge the `+e+` morphAttribute.`),null;c.morphAttributes[e].push(r)}}}return c}function I(e){let t,n,r,i=-1,a=0;for(let o=0;o<e.length;++o){let s=e[o];if(t===void 0&&(t=s.array.constructor),t!==s.array.constructor)return console.error(`THREE.BufferGeometryUtils: .mergeAttributes() failed. BufferAttribute.array must be of consistent array types across matching attributes.`),null;if(n===void 0&&(n=s.itemSize),n!==s.itemSize)return console.error(`THREE.BufferGeometryUtils: .mergeAttributes() failed. BufferAttribute.itemSize must be consistent across matching attributes.`),null;if(r===void 0&&(r=s.normalized),r!==s.normalized)return console.error(`THREE.BufferGeometryUtils: .mergeAttributes() failed. BufferAttribute.normalized must be consistent across matching attributes.`),null;if(i===-1&&(i=s.gpuType),i!==s.gpuType)return console.error(`THREE.BufferGeometryUtils: .mergeAttributes() failed. BufferAttribute.gpuType must be consistent across matching attributes.`),null;a+=s.count*n}let o=new t(a),s=new p(o,n,r),c=0;for(let t=0;t<e.length;++t){let r=e[t];if(r.isInterleavedBufferAttribute){let e=c/n;for(let t=0,i=r.count;t<i;t++)for(let i=0;i<n;i++){let n=r.getComponent(t,i);s.setComponent(t+e,i,n)}}else o.set(r.array,c);c+=r.count*n}return i!==void 0&&(s.gpuType=i),s}var L={w:.95,h:2.2,t:.04,frame:.06,reveal:.14},R={"green-bamboo":`dotTre`,"cau-han":`vomCau`,"little-rose":`hoaThi`};function z(e){let t=e===`little-rose`;return{transomPattern:t?`open`:R[e]??`hoaThi`,spillTint:t?0:.12,corridorSun:!t}}function B({wx:e,zc:t,unit:n,open:r=72,hinge:i=1,inward:a=!1}){let s=n===`little-rose`,c=L.w/2,l=L.h+L.frame,u={z0:t-1.5*o,z1:t+1.5*o,y0:l+.04,y1:l+.04+o};return{wx:e,zc:t,unit:n,hinge:i,swing:a?-1:1,open:s?0:r,emblem:R[n]??`hoaThi`,holes:[[t-c-L.frame,t+c+L.frame,0,l],[u.z0,u.z1,u.y0,u.y1]],doorway:{z0:t-c,z1:t+c,y0:0,y1:L.h},transom:u}}function ie(e){let{wx:t,zc:n}=e,r=L.w/2,i=L.frame,a=L.reveal,o=(e,t,n,r,i,a)=>new x(e,t,n).translate(r,i,a),s=F([o(a,L.h+i,i,t+a/2-.02,(L.h+i)/2,n-r-i/2),o(a,L.h+i,i,t+a/2-.02,(L.h+i)/2,n+r+i/2),o(a,i,L.w+2*i,t+a/2-.02,L.h+i/2,n)]),c=e.hinge??1,l=[o(L.t,L.h,L.w,0,L.h/2,-c*r)];for(let e of[-1,1])for(let[t,n]of[[.54,.84],[1.6,.96]])for(let i of[-.24,.24])l.push(o(.012,n,.34,e*(L.t/2+.004),t,-c*r+i));return{frame:s,leaf:F(l),hinge:[t+.03,0,n+c*r],side:c}}var V={open:0,dotTre:1,vomCau:2,hoaThi:3},H=e=>e.toFixed(4),ae=`
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
    float t = (wx + float(s) * ${H(a)} - P.x) / L.x;
    vec3 R = P + L * t;
    vec2 uv = vec2(R.z - (D.x - ${H(1.5*o)}), R.y - uDoorWall.y);
    float rect = sdBox2(uv - vec2(${H(1.5*o)}, ${H(.5*o)}), vec2(${H(1.5*o)}, ${H(.5*o)}));
    if (D.y < 0.5) { float fe = 0.05 + w; tr *= smoothstep(fe, -fe, rect); }
    else tr *= smoothstep(w, -w, max(rect, doorPat(D.y, mod(uv, ${H(o)}) - ${H(.5*o)})));
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
`;function se(){return{uDoors:{value:Array.from({length:8},()=>new t)},uDoorN:{value:0},uDoorWall:{value:new t},uNoSun:{value:[new t,new t]}}}var U={hoaThi:`return sdHoaThi(c);`,hoaThiMissing:`return all(equal(id, uOne)) ? min(sdHoaThi(c), sdBox2(c, vec2(0.5 * B - 0.004))) : sdHoaThi(c);`,dotTre:`return sdDotTre(c);`,vomCau:`return sdVomCau(c);`};function W(e,t,{corner:r,u:i,v:s,size:c,pattern:l=`hoaThi`,mode:d=`backlit`,depth:p=a,own:h={},light:g=t.length-1}){let _=new n(...i).normalize(),v=new n(...s).normalize(),b=new n().crossVectors(_,v),x={...e,uO:{value:new n(...r)},uU:{value:_},uV:{value:v},uNf:{value:b},uSize:{value:[c[0],c[1]]},uDepth:{value:p},uOne:{value:[-1,-1]},uFace:{value:new n},uFaceSun:{value:new n},uWallC:{value:new n},uRimC:{value:new n},uBehindLow:{value:new n},uBehindHigh:{value:new n},uBehindY:{value:[0,3]},uHalo:{value:.35},uCanopyC:{value:new n},uCanopyAmt:{value:0},uBehindDist:{value:6},uFall:{value:new n(0,1,0)},...h},S=new w({uniforms:x,vertexShader:`varying vec3 vP; void main(){ vec4 w = modelMatrix * vec4(position, 1.); vP = w.xyz; gl_Position = projectionMatrix * viewMatrix * w; }`,fragmentShader:`
      varying vec3 vP;
      uniform vec3 uO, uU, uV, uNf, uFace, uFaceSun, uWallC, uRimC, uBehindLow, uBehindHigh, uCanopyC, uFall;
      uniform vec2 uSize, uOne, uBehindY;
      uniform float uDepth, uHalo, uRef[2], uI[2], uCanopyAmt, uBehindDist;
      ${y}
      ${C}
      ${O(t)}
      #define B ${o.toFixed(4)}
      vec2 local(vec3 P){ return vec2(dot(P - uO, uU), dot(P - uO, uV)); }
      float pat(vec2 uv){ vec2 c = mod(uv, B) - 0.5 * B, id = floor(uv / B); ${U[l]} }
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
        ${d===`frontlit`?`
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
          ${d===`frontlit`?`
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
      #undef B`,toneMapped:!1}),T=new u(new f(c[0],c[1]),S),E=new m().makeBasis(_,v,b);return T.quaternion.setFromRotationMatrix(E),T.position.copy(new n(...r).addScaledVector(_,c[0]/2).addScaledVector(v,c[1]/2)),T}function G({lime:e,terr:t,beats:n,open:o=0,t:s=.25}){if(n.length>8)throw Error(`corridor: ${n.length} doors > 8`);let c=g(n),p=S,m=c.screen,_={min:[p.screenX,0,c.end],max:[p.wallX,p.ceiling,c.start]},y=l();r(`corridor`,s,y);let x=i({az:0,el:57}),C={mode:`window`,pattern:`hoaThi`,corner:[m.sx,m.y0,m.z0],u:[0,0,1],v:[0,1,0],size:[m.z1-m.z0,m.y1-m.y0],depth:a},w={mode:`occluder`,pattern:`canopy`,corner:[m.sx-3.2,-2,c.end-6],u:[0,0,1],v:[0,1,0],size:[c.start-c.end+12,9],depth:0,params:[.9,.56,1.3,4.1]},T=[{dir:y.dir,apertures:[C,w],glsl:oe,factor:`bayFree(P)`},{dir:x,glsl:ae,mask:`doorsMask(P, L)`}],O=b(h(`sky`),h(`wallSun`),.45),F=[O,O.clone().multiplyScalar(.86),O],I={...E({box:_,fillDir:[-1,0,0],sun:y.color,shade:N(),sunT:k(y.color,.5),spillT:F,refs:[Math.abs(y.dir.x),x.y],lime:e,terr:t}),...j(T,[y.dir,x]),...se()};I.uI.value=[1,.9];let R=c.doors.map(e=>({...B({wx:p.wallX,zc:e.zc,unit:e.unit,open:0,hinge:-1}),beat:e}));I.uDoorN.value=R.length,I.uDoorWall.value.set(p.wallX,R[0]?.transom.y0??2.3,0,0);let H=0;R.forEach((e,t)=>{let n=z(e.unit);I.uDoors.value[t].set(e.zc,V[n.transomPattern]??V.hoaThi,0,e.hinge),!n.corridorSun&&H<2&&I.uNoSun.value[H++].set(e.beat.bay[0],e.beat.bay[1],0,0)});let U=[c.end,c.start],G=[p.screenX,p.wallX],K=p.ceiling,q=p.pier,J=.12,ce=[...D(`y`,0,G,U,1,1),...D(`y`,K,G,U,-1,2),...D(`x`,p.wallX,U,[0,K],-1,0,R.flatMap(e=>e.holes)),...D(`x`,p.screenX,U,[0,K],1,0,[[m.z0,m.z1,m.y0,m.y1]]),...D(`z`,c.start,G,[0,K],-1,0),...D(`z`,c.end,G,[0,K],1,0),...c.piers.flatMap(e=>[...D(`z`,e+J,[p.wallX-q,p.wallX],[0,K],1,0),...D(`z`,e-J,[p.wallX-q,p.wallX],[0,K],-1,0),...D(`x`,p.wallX-q,[e-J,e+J],[0,K],-1,0)])],le=new u(re(ce),te(I,T)),Y=W(I,T,{corner:[m.sx,m.y0,m.z1],u:[0,0,-1],v:[0,1,0],size:C.size,pattern:`hoaThi`,light:0,own:ne()});M(Y.material.uniforms);let X=[le,Y],Z=[],Q=[];for(let e of R){let t=h(`unit-`+e.unit),n=e.unit===`little-rose`,r=ee(I,T,{uPaint:{value:d(t)},uWood:{value:d(h(`wood`))},uCool:{value:d(h(`wallShade`))},uSunTint:{value:d(A(h(`lime-50`),y.color))},uSpillTint:{value:d(b(h(`lime-50`),O,.5))},uShadeK:{value:.58},uWear:{value:n?.3:.6}}),i=ie(e),a=new v;if(a.position.set(...i.hinge),a.add(new u(i.leaf,r)),X.push(new u(i.frame,r),a),Z.push(a),n)Q.push(null);else{let n=new u(new f(L.w+.4,L.h+.3),P({low:b(h(`sun-200`),t,.08),high:b(h(`sun-100`),t,.05),y0:0,y1:2.4}));n.rotation.y=-Math.PI/2,n.position.set(p.wallX+.9,L.h/2,e.zc),n.visible=!1,X.push(n),Q.push(n)}let o=W(I,T,{corner:[p.wallX,e.transom.y0,e.transom.z0],u:[0,0,1],v:[0,1,0],size:[e.transom.z1-e.transom.z0,e.transom.y1-e.transom.y0],pattern:e.emblem,light:1,own:{uBehindLow:{value:d(n?O:b(h(`sun-100`),t,.06))},uBehindHigh:{value:d(h(n?`sky`:`sun-100`))},uBehindY:{value:[2,3]}}}),s=o.material.uniforms;s.uFace.value.copy(d(b(h(`wallShade`),h(`screenFace`),.7))),s.uWallC.value.copy(d(b(h(`wallShade`),h(`screenFace`),.35))),s.uRimC.value.copy(d(O)),s.uHalo.value=.18,X.push(o)}let $=(e,t)=>{let n=R[e];if(!n)return;let r=n.unit===`little-rose`?0:Math.min(Math.max(t,0),72)*Math.PI/180;Z[e].rotation.y=n.hinge*r,I.uDoors.value[e].z=r,Q[e]&&(Q[e].visible=r>.01)};return R.forEach((e,t)=>$(t,o)),{box:_,lights:T,U:I,objects:X,sun:y,layout:c,specs:R,setDoor:$,setSun:(e,t=1)=>{r(`corridor`,e,y);let n=k(y.color,.5);I.uSunT.value.forEach((e,t)=>e.set(n[t].r,n[t].g,n[t].b)),I.uSunCol.value.set(y.color.r,y.color.g,y.color.b),I.uRef.value[0]=Math.max(Math.abs(y.dir.x),.05),I.uI.value[0]=t,I.uSunI.value=t},subject:[C],glow:[C],shafts:1.4}}async function K({gl:t,tier:n,beats:r,textures:i}){let a=G({...i,beats:r}),o=new e;o.add(...a.objects);let l=s(t.renderer,a.U,{box:a.box,tier:n,ortho:t.ortho,mask:{glsl:O(a.lights.slice(0,1)),call:`lightMask0(p)`}});l.uniforms.uDensity.value=1.4;let u=new T(c.fov.landscape,1,.05,60),d=a.U.uApP.value[1],f=d.clone();return{...a,scene:o,camera:u,shafts:l,setTier:e=>l.setTier(e),sway(e){d.z=f.z+.2*Math.sin(e*.31+.6)+.06*Math.sin(e*1.07),d.w=f.w+.1*Math.sin(e*.23+2.1)},compile:async()=>{await t.renderer.compileAsync(o,u);for(let e of l.scenes)await t.renderer.compileAsync(e,t.ortho)},dispose(){o.traverse(e=>{e.isMesh&&(e.geometry.dispose(),e.material.dispose())}),l.dispose()}}}export{K as buildCorridor};