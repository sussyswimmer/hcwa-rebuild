const __vite__mapDeps=(i,m=__vite__mapDeps,d=(m.f||(m.f=["assets/debug-D4ibxs8k.js","assets/frame-Cy9oWa7v.js","assets/corridor-K23JNyc0.js","assets/journey-P5d6QuFC.js","assets/common-BTXyzCHS.js","assets/shutters-D8xkGbI1.js"])))=>i.map(i=>d[i]);
import{a as e,c as t,d as n,f as r,i,l as a,n as o,r as s,s as c,t as l,u}from"./frame-Cy9oWa7v.js";import{n as d,r as f,t as p}from"./main-KuHSxnbg.js";import{$ as m,A as h,B as g,C as _,Ct as v,D as y,Dt as b,E as x,Et as S,F as C,G as w,H as ee,J as T,K as E,L as D,M as te,O,P as ne,R as k,S as A,St as re,T as ie,Tt as j,U as ae,V as M,W as N,Y as oe,Z as se,_ as ce,_t as le,a as ue,at as de,b as fe,bt as P,c as pe,d as me,dt as F,et as I,f as he,ft as L,g as ge,gt as _e,h as ve,ht as R,i as ye,it as z,j as be,k as xe,l as Se,lt as Ce,m as we,mt as Te,n as Ee,o as De,ot as Oe,p as ke,q as B,r as Ae,rt as V,s as je,t as Me,tt as H,u as Ne,ut as Pe,v as Fe,vt as Ie,w as Le,wt as Re,x as ze,xt as Be,y as Ve,yt as He,z as Ue}from"./journey-P5d6QuFC.js";import{t as We,worldOff as Ge}from"./world-Bqbfo1W2.js";function Ke(e){return new Re({uniforms:e,vertexShader:`
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
      ${B}
      ${N}
      ${H}
      ${I}
      ${M}
      float occ(float d, float r, float s){ return d < 0.003 ? 1. : 1. - s * (1. - smoothstep(0., r, d)); }
      void main(){
        int k = int(vKind + 0.5);           // 0 lime wall, 1 terrazzo floor, 2 ceiling
        vec3 N = normalize(vN);
        vec2 uv = abs(N.z) > .5 ? vP.xy : abs(N.x) > .5 ? vP.zy : vP.xz;
        vec4 lw = texture2D(uLime, uv * 0.25);
        float vertical = 1. - step(.5, abs(N.y));
        float mott = (lw.r - .5) * .09 + (lw.g - .5) * .05 + (lw.b - .5) * .04 * vertical;
        vec3 rel = k == 1 ? texture2D(uTerr, vP.xz * 0.5).rgb / ${k.toFixed(3)} * (1. + mott * .5) : vec3(1. + mott);

        // Analytic contact shadow at floor, ceiling and corners: no SSAO.
        float ao = occ(vP.y - uBoxMin.y, .35, .12) * occ(uBoxMax.y - vP.y, .4, .1)
                 * occ(vP.z - uBoxMin.z, .9, .2) * occ(uBoxMax.z - vP.z, .8, .12)
                 * occ(vP.x - uBoxMin.x, .9, .2) * occ(uBoxMax.x - vP.x, .6, .12);
        // Skylight comes through the screen (+x): surfaces near it and facing it are brighter,
        // the far side and the ceiling away from it fall off. Text sits on the back wall
        // left and low, where this stays within a few percent of the target.
        float near = smoothstep(uBoxMin.x, uBoxMax.x, vP.x);
        float fill = (1. + 0.06 * N.x) * (0.92 + 0.14 * near) * (k == 2 ? 0.86 + 0.16 * near : 1.);

        float ndl = max(dot(N, uL), 0.);
        float m = ndl > 0. ? sunMask(vP) : 0.;
        float f = m > 0. ? ndl / uNdlRef * m * leaf(vP, uTime) * capAt(gl_FragCoord.xy) * uSunI : 0.;
        vec3 shade = uShade[k] * rel * ao * fill;
        vec3 sun = uSunT[k] * 0.94 * rel * mix(1., ao, .5);

        vec3 col = mix(mix(shade, sun, min(f, 1.)), uGlare, min(max(f - 1., 0.) * .35, .6));
        gl_FragColor = vec4(col, 1.);
        #include <colorspace_fragment>
        gl_FragColor.rgb += (h21(gl_FragCoord.xy) - .5) / 255.;
      }`,toneMapped:!1})}var qe={1:160,2:420};function Je(e,{min:t,max:n},{mask:r=null}={}){let i=qe[2],a=new Float32Array(i*4),o=7,s=()=>(o=o*16807%2147483647)/2147483647;for(let e=0;e<a.length;e++)a[e]=s();let c=new Ce;c.setAttribute(`position`,new L(new Float32Array(i*3),3)),c.setAttribute(`aSeed`,new L(a,4));let l=new S(...t),u=new S(...n).sub(l),d=new Re({uniforms:{...e,uBoxMin:{value:l},uBoxSize:{value:u},uBurst:{value:0}},vertexShader:`
      attribute vec4 aSeed;
      uniform vec3 uBoxMin, uBoxSize, uWind;
      uniform float uTime, uPx, uMotesA, uStir, uSunI, uBurst;
      varying float vA;
      ${B}
      ${H}
      ${r?r.glsl:N+I}
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
      void main(){ vec2 d = gl_PointCoord - .5; gl_FragColor = vec4(uMoteCol * vA * exp(-dot(d, d) * 18.), 1.); }`,blending:2,transparent:!0,depthWrite:!1,toneMapped:!1}),f=new Be(c,d);return f.frustumCulled=!1,f.renderOrder=5,{points:f,uniforms:d.uniforms,setTier(e){c.setDrawRange(0,qe[e>=2?2:1])},get count(){return c.drawRange.count},wrap(e){e.set((e.x%u.x+u.x)%u.x,(e.y%u.y+u.y)%u.y,(e.z%u.z+u.z)%u.z)},dispose(){c.dispose(),d.dispose()}}}var Ye=`
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
`,Xe=()=>({uSkyLow:{value:z(`sky`)},uSkyTop:{value:V(z(`sky`),z(`shade-100`),.35)},uCanopy:{value:V(z(`sky`),z(`unit-green-bamboo`),.2)},uSunGlow:{value:z(`on-dusk`)},uStreetX:{value:9}});function Ze(e,{x:t,z:n,size:r=[40,20]}){let i=new Re({uniforms:e,vertexShader:`varying vec3 vP; void main(){ vec4 w = modelMatrix * vec4(position, 1.); vP = w.xyz; gl_Position = projectionMatrix * viewMatrix * w; }`,fragmentShader:`
      uniform vec3 uL;
      varying vec3 vP;
      ${H}
      ${m}
      ${Ye}
      void main(){
        gl_FragColor = vec4(street(cameraPosition, normalize(vP - cameraPosition)), 1.);
        #include <colorspace_fragment>
        DITHER_OUT;
      }`,toneMapped:!1}),a=new le(new P(...r),i);return a.rotation.y=-Math.PI/2,a.position.set(t,r[1]/2-2,n),a}function Qe({parapet:e=1.8,canopy:t=2.6,trunk:n=[-1.2,-1]}={}){let r=V(z(`sun-200`),z(`sun-100`),.15),i=z(`sun-100`),a=V(z(`sun-200`),z(`sun-400`),.45),o=V(V(z(`lime-300`),z(`sun-200`),.35),z(`gold-700`),.1);return new Re({uniforms:{uLow:{value:r},uHigh:{value:i},uLeaf:{value:a},uWall:{value:o},uHole:{value:i.clone()},uY:{value:[e,t]},uTrunk:{value:n},uSway:{value:0}},vertexShader:`varying vec3 vP; void main(){ vec4 w = modelMatrix * vec4(position, 1.); vP = w.xyz; gl_Position = projectionMatrix * viewMatrix * w; }`,fragmentShader:`
      varying vec3 vP;
      uniform vec3 uLow, uHigh, uLeaf, uWall, uHole; uniform float uY[2], uTrunk[2], uSway;
      ${H}
      ${B}
      ${m}
      #define B ${E.toFixed(4)}
      void main(){
        vec2 p = vP.zy;
        vec3 c = mix(uLow, uHigh, smoothstep(uY[0], uY[1] + 1.5, p.y));
        // The canopy: leaf masses whose lower edge is lace, turned about the trunk by the sway.
        vec2 q = p - vec2(uTrunk[0], uTrunk[1]);
        float s = sin(uSway), k = cos(uSway);
        q = mat2(k, s, -s, k) * q + vec2(uTrunk[0], uTrunk[1]);
        float mass = fbm(q * vec2(.8, 1.) + 4.3) + 0.7 * smoothstep(uY[1] - .2, uY[1] + 1.2, q.y) - .7;
        float leaves = smoothstep(-.02, .03, mass) * (0.7 + 0.3 * smoothstep(.45, .6, vn(q * 7.3 + 1.7)));
        c = mix(c, uLeaf, leaves * smoothstep(uY[1] - .5, uY[1] + .1, q.y));
        // The parapet: plastered top, then a course of Hoa thị blocks lit from the street behind.
        if (p.y < uY[0]) {
          vec2 b = vec2(p.x, p.y - (uY[0] - 0.12 - B));
          float course = step(0., b.y) * step(b.y, B);
          vec2 cc = mod(b, B) - 0.5 * B;
          float hole = course * (1. - smoothstep(-.004, .004, sdHoaThi(cc)));
          float joint = course * (1. - smoothstep(.003, .007, .5 * B - max(abs(cc.x), abs(cc.y))));
          // In its own shade, a touch deeper toward the ground; the coping catches the light.
          vec3 w = uWall * (0.97 + 0.06 * vn(p * 9.)) * (1. - 0.06 * joint) * mix(.9, 1., smoothstep(uY[0] - 1.6, uY[0], p.y)) * mix(1., 1.06, step(uY[0] - .12, p.y));
          c = mix(w, uHole, hole);
        }
        gl_FragColor = vec4(c, 1.);
        #include <colorspace_fragment>
        DITHER_OUT;
      }
      #undef B`,toneMapped:!1})}var U=`
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
`,W=`varying vec3 vP; varying vec3 vN;
void main(){
  mat4 m = modelMatrix;
  #ifdef USE_INSTANCING
  m = m * instanceMatrix;
  #endif
  vec4 w = m * vec4(position, 1.);
  vP = w.xyz; vN = normalize(mat3(m) * normal);
  gl_Position = projectionMatrix * viewMatrix * w;
}`,$e=`
varying vec3 vP; varying vec3 vN;
${B}
${N}
${H}
${m}
${I}
${Ye}
${U}
`;function et(e,t){let n=new Re({uniforms:e,vertexShader:W,fragmentShader:`
      ${$e}
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
      }`,toneMapped:!1}),r=new P(t.z1-t.z0,t.y1-t.y0),i=new le(r,n);return i.rotation.y=-Math.PI/2,i.position.set(t.sx,(t.y0+t.y1)/2,(t.z0+t.z1)/2),i}function tt(){let e=E/2,{r:t,pipAt:n,pipR:r}=oe,i=new j().moveTo(-e,-e).lineTo(e,-e).lineTo(e,e).lineTo(-e,e).closePath(),a=new Ie;for(let e=0;e<40;e++){let n=-e/40*Math.PI*2,r=2*t*Math.max(Math.cos(n),Math.cos(n-Math.PI/2),Math.cos(n-Math.PI),Math.cos(n+Math.PI/2));e?a.lineTo(r*Math.cos(n),r*Math.sin(n)):a.moveTo(r*Math.cos(n),r*Math.sin(n))}a.closePath(),i.holes.push(a);for(let[e,t]of[[1,1],[-1,1],[-1,-1],[1,-1]])i.holes.push(new Ie().absarc(e*n,t*n,r,0,Math.PI*2,!0));let o=new F(i,{depth:T,bevelEnabled:!1,curveSegments:8}).rotateY(Math.PI/2),s=o.getAttribute(`normal`),c=[];for(let e=0;e<s.count;e+=3)s.getX(e)<.5&&c.push(e,e+1,e+2);return o.setIndex(c),o}function nt(e,t){let n=new Re({uniforms:e,vertexShader:W,fragmentShader:`
      ${$e}
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
      }`,toneMapped:!1}),r=Math.round((t.z1-t.z0)/E),i=Math.round((t.y1-t.y0)/E),a=new Te(tt(),n,r*i),o=new _e,s=0;for(let e=0;e<r;e++)for(let n=0;n<i;n++)a.setMatrixAt(s++,o.makeTranslation(t.sx,t.y0+(n+.5)*E,t.z0+(e+.5)*E));return a.instanceMatrix.needsUpdate=!0,a.computeBoundingSphere(),a}var rt=(e,t,n)=>n>=2?nt(e,t):et(e,t);function it(e,t,n){let r=E/2;return et(e,{sx:t.sx,z0:n.z-r,z1:n.z+r,y0:n.y-r,y1:n.y+r})}function G(e,t){let n=Math.round((e.y1-e.y0)/E);return Math.floor((t.z-e.z0)/E)*n+Math.floor((t.y-e.y0)/E)}var K={min:[-6,0,-4],max:[2.6,3.3,4]},q={sx:2.6,z0:-4,z1:1.4,y0:.9,y1:3.3},J=new S(2.6,1.05,-1.15),at={portrait:new S(2.6,1.05,-3.25)},ot=e=>at[e]??J,st={min:[-3.5,.05,-3.9],max:[2.5,3.2,2]},ct={portrait:{pos:[.83,1.1,-2.93],look:[1.68,.91,-3.43],fov:49.6},tablet:{pos:[-1.3,1.1,3.15],look:[1.2,1.95,-3.6],fov:54},landscape:{pos:[-1.5,1.1,3.3],look:[.5,1.78,-4],fov:46},wide:{pos:[-1.5,1.1,3.3],look:[.5,1.78,-4],fov:44}},lt={silhouette:(e=>[new S(e.sx,e.y0,e.z0),new S(e.sx,e.y1,e.z0),new S(e.sx,e.y1,e.z1),new S(e.sx,e.y0,e.z1)])(q),floor:[new S(K.min[0],0,K.min[2]),new S(K.max[0],0,K.min[2]),new S(K.max[0],0,K.max[2]),new S(K.min[0],0,K.max[2])],heroBlock:J};function ut(){let[e,t,n]=K.min,[r,i,a]=K.max,o=(e+r)/2,s=r-e,c=a-n,l=i-t,u=[[s,l,[0,0,0],[o,l/2,n],0],[c,l,[0,Math.PI/2,0],[e,l/2,(n+a)/2],0],[s,c,[-Math.PI/2,0,0],[o,t,(n+a)/2],1],[s,c,[Math.PI/2,0,0],[o,i,(n+a)/2],2],[q.z1-q.z0,q.y0,[0,-Math.PI/2,0],[r,q.y0/2,(q.z0+q.z1)/2],0],[a-q.z1,l,[0,-Math.PI/2,0],[r,l/2,(q.z1+a)/2],0],[s,l,[0,Math.PI,0],[o,l/2,a],0]],d=[],f=[],p=[],m=[],h=new _e;for(let[e,t,n,r,i]of u){let a=new P(e,t);h.makeRotationFromEuler(new Pe(...n)).setPosition(...r),a.applyMatrix4(h);let o=d.length/3;d.push(...a.getAttribute(`position`).array),f.push(...a.getAttribute(`normal`).array);for(let e=0;e<a.getAttribute(`position`).count;e++)p.push(i);m.push(...Array.from(a.index.array,e=>e+o)),a.dispose()}let g=new Ce;return g.setAttribute(`position`,new L(d,3)),g.setAttribute(`normal`,new L(f,3)),g.setAttribute(`aKind`,new L(p,1)),g.setIndex(m),g}async function dt({gl:e,tier:t,slice:n=!0,shaftSteps:r=null}){let[i,a]=await Promise.all([Ue({slice:n}),g({slice:n})]),o=ne();C(`room`,te.room,o);let s=[z(`wallShade`),z(`floorShade`),z(`ceiling`)].map(de),c=[z(`wallSun`),z(`wallSun`).multiplyScalar(.8),z(`wallSun`)].map(de),l=z(`sunRoom`),u=Math.max(o.dir.z,.05),d=o.dir,f={...w(q,d),...ee(),...Xe(),uShade:{value:s},uSunT:{value:c.map(e=>e.clone())},uNdlRef:{value:u},uGlare:{value:de(z(`sky`))},uSunCol:{value:new S},uSunI:{value:1},uRimI:{value:1},uHaloA:{value:.35},uHaloW:{value:.012},uBoxMin:{value:new S(...K.min)},uBoxMax:{value:new S(...K.max)},uLime:{value:i},uTerr:{value:a},uFace:{value:z(`screenFace`)},uGlow:{value:z(`sky`)},uRim:{value:V(z(`wallSun`),z(`sky`),.3)},uWall:{value:V(z(`screenFace`),z(`sky`),.38)},uTime:{value:0},uLeafAmt:{value:0},uWind:{value:new S},uStir:{value:0},uMotesA:{value:1},uPx:{value:1},uMoteCol:{value:de(V(z(`sky`),z(`sun-200`),.6).convertLinearToSRGB())}},p=new v,m=new le(ut(),Ke(f)),h=rt(f,q,1),_=Ze(f,{x:9,z:-1}),y=Je(f,st);p.add(m,h,_,y.points);let b=D(e.renderer,f,{box:K,tier:t,ortho:e.ortho,steps:r}),x=new He(46,1,.05,60),T=[],E=e=>{if(e>=2!=!!h.isInstancedMesh){p.remove(h),h.geometry.dispose(),h.material.dispose(),h=rt(f,q,e),p.add(h);for(let e of T)p.remove(e),e.geometry.dispose(),e.material.dispose();if(T=[],h.isInstancedMesh){for(let e of[J,...Object.values(at)])h.setMatrixAt(G(q,e),new _e().makeScale(0,0,0)),T.push(it(f,q,e));h.instanceMatrix.needsUpdate=!0,p.add(...T)}}_.visible=e>=2,y.setTier(e),b.setTier(e)};E(t);let O=({t:e=te.room,nudge:t=null,intensity:n=1,rims:r=n}={})=>{C(`room`,e,o,t);let i=o.color.r/l.r,a=o.color.g/l.g,s=o.color.b/l.b;for(let e=0;e<3;e++)f.uSunT.value[e].set(c[e].x*i,c[e].y*a,c[e].z*s);f.uSunCol.value.set(o.color.r,o.color.g,o.color.b),f.uSunI.value=n,f.uRimI.value=r};return O(),{scene:p,camera:x,U:f,shafts:b,motes:y,setTier:E,setSun:O,setShot:(e,t,n,r=ct[e.name])=>{x.position.set(...r.pos),x.fov=r.fov,x.aspect=t/n,x.lookAt(...r.look),x.clearViewOffset(),x.updateProjectionMatrix(),x.updateMatrixWorld()},textures:{lime:i,terr:a},compile:async()=>{await e.renderer.compileAsync(p,x);for(let t of b.scenes)await e.renderer.compileAsync(t,e.ortho)},dispose(){p.traverse(e=>{(e.isMesh||e.isPoints)&&(e.geometry.dispose(),e.material.dispose())}),b.dispose(),i.dispose(),a.dispose()}}}var ft={portrait:{pos:[1.52,1.1,-3.05],fov:56},tablet:{pos:[.3,1.1,.4],fov:50},landscape:{pos:[.2,1.1,.6],fov:42},wide:{pos:[.2,1.1,.6],fov:40}},pt={portrait:{dist:.5,fov:56},tablet:{dist:.42,fov:50},landscape:{dist:.35,fov:42},wide:{dist:.35,fov:40}},mt={portrait:[20,8],tablet:[28,14]},ht=e=>mt[e]??[32,O.yaw],Y={portrait:2.2},gt=e=>Y[e]??.8,X=6,_t=(e,t,n)=>{let r=R.clamp((n-e)/(t-e),0,1);return r*r*(3-2*r)},Z=e=>.5-.5*Math.cos(Math.PI*R.clamp(e,0,1)),vt=e=>Math.sin(R.clamp(e,0,1)*Math.PI/2);function yt(e){let t=Math.tan(R.degToRad(e.fov/2))/e.zoom,n=2*t,r=e.aspect*n,i=-r/2,a=t,o=e.view;return o?.enabled&&(i+=o.offsetX*r/o.fullWidth,a-=o.offsetY*n/o.fullHeight,r*=o.width/o.fullWidth,n*=o.height/o.fullHeight),{l:i,r:i+r,t:a,b:a-n}}var Q=(e,t,n)=>{let r=Math.tan(R.degToRad(e/2));return Object.assign(n,{l:-r*t,r:r*t,t:r,b:-r})};function bt(e,t,n,r){let i=e.near,a=n*(t.t-t.b)/r;e.projectionMatrix.makePerspective(t.l*i,t.r*i,(t.t-a)*i,(t.b-a)*i,i,e.far),e.projectionMatrixInverse.copy(e.projectionMatrix).invert()}var xt=(e,t)=>{let n=t.clone().sub(e).normalize();return[Math.atan2(-n.x,-n.z),Math.asin(R.clamp(n.y,-1,1))]},St=(e,t,n)=>{let r=t-e;for(;r>Math.PI;)r-=2*Math.PI;for(;r<-Math.PI;)r+=2*Math.PI;return e+r*n};function Ct(e,t,n,r,i,a){let o=i*i,s=o*i;for(let c of[`x`,`y`,`z`])a[c]=.5*(2*t[c]+(n[c]-e[c])*i+(2*e[c]-5*t[c]+4*n[c]-r[c])*o+(3*t[c]-e[c]-3*n[c]+r[c])*s);return a}function wt(){let e={pos:new S,yaw:0,pitch:0,fov:46,bounds:{l:-1,r:1,t:1,b:-1}},n=new S,r=new S,i=`landscape`,a=1,o=1,s=ot(`landscape`),l=0,u=0,d=new S,f=new Pe(0,0,0,`YXZ`),p=new re,m={l:0,r:0,t:0,b:0},g={l:0,r:0,t:0,b:0},_=new S,v=(t,c,d)=>{i=c,a=d.w/d.h,o=d.h,e.pos.copy(t.position);let f=new S(0,0,-1).applyQuaternion(t.quaternion);e.yaw=Math.atan2(-f.x,-f.z),e.pitch=Math.asin(R.clamp(f.y,-1,1)),e.fov=t.fov,Object.assign(e.bounds,yt(t)),s=ot(i),n.set(...ft[i].pos),_.copy(n).sub(s).setY(0).normalize().lerp(new S(-1,0,0),.35).normalize(),r.copy(s).addScaledVector(_,pt[i].dist).setY(1.1),[l,u]=xt(n,s)},y=(t,c,h=0)=>{c<=1?Ct(e.pos,e.pos,n,r,c,d):Ct(e.pos,n,r,r,c-1,d),t.position.copy(d);let _,v,y;if(c<=1){let t=Z(c),[n,r]=xt(d,s),a=_t(.6,1,c);_=St(e.yaw,St(l,n,a),t),v=R.lerp(e.pitch,R.lerp(u,r,a),_t(.45,1,c)),y=R.lerp(e.fov,ft[i].fov,t)}else[_,v]=xt(d,s),y=R.lerp(ft[i].fov,pt[i].fov,c-1);t.quaternion.copy(p.setFromEuler(f.set(v,_,0))),t.fov=y,t.updateMatrixWorld();let b=_t(0,.7,c);Q(y,a,g);for(let t of[`l`,`r`,`t`,`b`])m[t]=R.lerp(e.bounds[t],g[t],b);return bt(t,m,h,o),1-b},b=(e,t)=>{let n=vt(t);e.position.lerpVectors(O.from,O.to,n),e.fov=O.fov[i],e.aspect=a;let[r,s]=ht(i);e.lookAt(h(e.position,R.lerp(r,s,n))),e.updateMatrixWorld(),bt(e,Q(e.fov,a,g),0,o)},x=null;return{setRest:v,room:y,exit:b,solve:(e,{heroH:n,approach:r,rects:a,shapes:o,view:s,top:l=0})=>{let u=n+r,d=Math.ceil(u/X)+1,f=n+.6*r,p=e=>e<=n?R.clamp(e/n,0,1)**1.3:1+Z((e-n)/r),m=a.filter(e=>e.kind!==`opaque`&&!e.pinned),h=(n,r,i)=>{y(e,n,r);let a=t(o.silhouette,e,s.w,s.h),u=t(o.floor,e,s.w,s.h);for(let e of m){let t=e.y-i,n=i>80?l:0;if(t+e.h<=n||t>=s.h)continue;let r={x:e.x,y:Math.max(t,n),w:e.w,h:Math.min(t+e.h,s.h)-Math.max(t,n)};if(c(a,0,0,r)||e.kind===`body`&&c(u,0,0,r))return!1}return!0},g=(e,t,n)=>{for(let r=0;r<10;r++){let r=(t+n)/2;e(r)?n=r:t=r}return n},v=(e,t,n)=>h(e,t,n)?e:h(0,t,n)?e-g(r=>h(e-r,t,n),0,e):0,b=[];for(let e=0;e<d;e++){let t=Math.min(e*X,u),r=p(t),i=t<f?t:0,a=r,o=0;t<n&&!h(r,0,t)&&(h(0,0,t)?a=v(r,0,t):(o=i>0&&h(0,i,t)?g(e=>h(0,e,t),0,i):i,a=v(r,o,t))),b.push({y:t,s:a,lift:o,need:o})}for(let e=1;e<d;e++)b[e].lift=Math.max(b[e].need,b[e-1].lift-gt(i)*X);for(let e=d-2;e>=0;e--)b[e].lift=Math.min(b[e].y,Math.max(b[e].lift,b[e+1].lift-X));for(let e of b)e.y>=f&&(e.lift=0);for(let e of b)e.y>=n||e.lift===e.need||h(e.s,e.lift,e.y)||(e.s=h(0,e.lift,e.y)?e.s-g(t=>h(e.s-t,e.lift,e.y),0,e.s):0);for(let e=d-2;e>=0;e--)b[e].s=Math.min(b[e].s,b[e+1].s);let S=R.degToRad(40)*X/(.6*s.h),C=t=>(y(e,t,0),_.set(0,0,-1).applyQuaternion(e.quaternion),Math.atan2(-_.x,-_.z)),w=C(b[0].s);for(let e=1;e<d;e++){let t=b[e-1].s,n=e=>Math.abs(St(0,C(e)-w,1));b[e].s>t&&n(b[e].s)>S&&(b[e].s=t+(b[e].s-t)*(1-g(r=>n(b[e].s-(b[e].s-t)*r)<=S,0,1))),w=C(b[e].s)}return x={rows:b,end:u},{rows:d,held:b.filter(e=>e.s<p(e.y)-.001).length,carried:b.filter(e=>e.lift>0).length}},at:(e,t)=>{if(!x)return Object.assign(t,{s:0,lift:0});let{rows:n}=x,r=R.clamp(e/X,0,n.length-1),i=Math.floor(r),a=Math.min(i+1,n.length-1),o=r-i;return t.s=R.lerp(n[i].s,n[a].s,o),t.lift=R.lerp(n[i].lift,n[a].lift,o),t},get bucket(){return i},get table(){return x}}}function Tt(e,{hero:t,passage:n},r){return r.hero=R.clamp((e-t.top)/t.len,0,1),r.passage=R.clamp((e-n.top)/n.len,0,1),r.y=e-t.top,r}var Et=e=>{let t=R.clamp(e,0,1);return t*t*(3-2*t)};function Dt(){let e=new S,t=new S,n={pos:[0,0,0],yaw:0,fov:0},r={l:0,r:0,t:0,b:0},i={l:0,r:0,t:0,b:0},a=`side`,o=null,s=1,c=1,l=`landscape`,u=null;return{setLayout:({bucket:e,view:t,free:n,name:r,corridor:i})=>{l=e,a=r,u=i,s=t.w/t.h,c=t.h;let d=_(1,a,u),f=u.doors[0]?.zc??d.pos[2]-ue[a].d,[p,m]=ue[a].at,h=(n.x0+p*(n.x1-n.x0))/t.w,g=(n.y0+m*(n.y1-n.y0))/t.h;o=we(d.pos,d.yaw,[Me.wallX,Ee,f],d.fov,s,h,g)},pose:(d,f,p,m,h=0)=>{let g=_(Le(p,m)+A(h),a,u,n),v=Et(f),y=R.degToRad(R.lerp(ht(l)[1],g.yaw,v)),b=R.lerp(O.fov[l],g.fov,v);e.set(R.lerp(O.to.x,g.pos[0],v),1.1,R.lerp(O.to.z,g.pos[2],v)),d.position.copy(e),d.fov=b,d.aspect=s,d.lookAt(t.set(e.x+Math.sin(y)*10,1.1,e.z-Math.cos(y)*10)),d.updateMatrixWorld(),Q(b,s,r);let x=Math.tan(R.degToRad(b/2))/Math.tan(R.degToRad(g.fov/2));for(let e of[`l`,`r`,`t`,`b`])i[e]=R.lerp(r[e],o[e]*x,v);bt(d,i,0,c)},get layout(){return a},get lens(){return o}}}var Ot=6,kt=2.5,At=z(`lime-100`),jt={dip:.82,down:1600,up:2400,min:14e3,max:22e3,first:[3500,5e3]},Mt={rest:te.room,heroOut:.14,hole:.2,corridor:De.sun[0]},Nt={truck:.05,yaw:.8*Math.PI/180,pitch:.4*Math.PI/180},Pt={haloA:[.35,.95],haloW:[.012,.06],density:[1.6,2.8]},Ft=1.8,It={radius:.62,held:.1,peak:.55},Lt=x(Oe.inOut),Rt=()=>new Promise(e=>requestAnimationFrame(e)),zt=(e,t,n)=>e+(t-e)*n,$=e=>Math.min(Math.max(e,0),1),Bt=e=>ke(e)/ye.leafDeg;async function Vt({canvas:t,stage:c,bus:m,probed:h,forced:g}){let _=document.documentElement,v=new URLSearchParams(location.search).get(`debug`),x=h.tier,C=se(t,{context:h.gl,tier:x}),w=await dt({gl:C,tier:x}),ee=c?.closest(`[data-world]`)??document.querySelector(`[data-world="room"]`),T=document.querySelector(`[data-world="passage"]`),E=document.querySelector(`[data-world="corridor"]`),D=document.querySelector(`[data-world="shutters"]`),O=w.camera,ne=wt(),k=Dt(),A=null,j=null,M={x:0,y:0,w:1,h:1},N={w:1,h:1},oe={x:0,y:0,w:1,h:1},le=new S,ue=new re,de=new Pe(0,0,0,`YXZ`),P={hero:{top:0,len:1},passage:{top:0,len:1},corridor:{top:0,len:1,pre:1},shutters:{from:0,top:0,len:1}},F=null,I=!0,L=1,_e=null,R=!1,z=`landscape`,Ce=ve(document.querySelectorAll(`[data-beat]`)),we=Se(Ce),Te=(e,t,n)=>{n&&(e||t)&&(le.set(1,0,0).applyQuaternion(O.quaternion),O.position.addScaledVector(le,e*Nt.truck*L*n),O.quaternion.multiply(ue.setFromEuler(de.set(t*Nt.pitch*L*n,e*Nt.yaw*L*n,0))),O.updateMatrixWorld())},Ee=()=>{Object.assign(M,a(ee)??{x:0,y:0,w:innerWidth,h:innerHeight});let m=t.clientWidth||innerWidth,h=t.clientHeight||innerHeight;C.resize(m,h);let{dpr:g}=C.size;w.shafts.setSize(Math.round(m*g),Math.round(h*g)),A?.shafts.setSize(Math.round(m*g),Math.round(h*g)),j?.shafts.setSize(Math.round(m*g),Math.round(h*g)),j&&(j.U.uPx.value=g),N.w=m,N.h=h;let y=f(_.clientWidth||m,_.clientHeight||h),[b,x]=y.view,S=Math.max(M.w/b,M.h/x),E=e(M,y.view,S);Object.assign(oe,{x:M.x+E.x,y:M.y+E.y,w:b*S,h:x*S}),w.setShot(y,b,x);let D=u(),te=i(r(D,M,y.view),y.name);F=n({camera:O,width:b,height:x,rects:te,...lt,stage:c?r(a(c),M,y.view):null});let k=d[y.name],re=k&&o(F,k.sx,k.sy);re&&Object.assign(F,{sx:k.sx,sy:k.sy,still:!0});let ie=O.quaternion.clone(),se=O.position.clone();L=0;for(let e of[1,.5,.25]){if(L=e,[[-1,-1],[1,-1],[-1,1],[1,1]].every(([e,t])=>(O.position.copy(se),O.quaternion.copy(ie),Te(e,t,1),s(F,O,lt,b,x))))break;L=0}O.position.copy(se),O.quaternion.copy(ie),O.updateMatrixWorld(),l(O,oe,N,{sx:F.sx*S,sy:F.sy*S}),ne.setRest(O,y.name,N);let ce=re&&k.caps?k.caps.map(e=>({...e,x:oe.x+e.x*S,y:oe.y+e.y*S,w:e.w*S,h:e.h*S})):D;ae(w.U,ce,{h,dpr:g}),w.U.uPx.value=g,P.hero.top=M.y,P.hero.len=Math.max(M.h,1);let le=a(T),ue=T?.querySelector(`.passage__frame`)?.offsetHeight??h;P.passage.top=le?le.y:M.y+M.h,P.passage.len=le?Math.max(le.h-(getComputedStyle(T.querySelector(`.passage__frame`)??T).position===`sticky`?ue:0),1):h*1.8,_e=ne.solve(O,{heroH:P.passage.top,approach:Ae.approach[1]*P.passage.len,rects:u(ee),shapes:lt,view:N,top:parseFloat(getComputedStyle(_).getPropertyValue(innerWidth>=1024?`--header-h-compact`:`--header-h`))||0}),v===`frame`&&p(()=>import(`./frame-Cy9oWa7v.js`).then(e=>e.o).then(e=>e.drawFrameOverlay(F,oe,N,S)),[]),z=y.name,Oe(),V(),I=!0},Oe=()=>{let e=E?.querySelector(`.bays__frame`),t=E?.querySelector(`.bays__panel`);if(R=!!(e&&t&&_.classList.contains(`stages`)&&E.classList.contains(`is-stage`)),!R)return;let n=a(E.querySelector(`[data-stage-track]`)??E),r=e.getBoundingClientRect(),i=t.getBoundingClientRect(),o=r.height||N.h;Object.assign(P.corridor,{top:n.y,len:Math.max(n.h-o,1),pre:o});let s=parseFloat(getComputedStyle(_).getPropertyValue(innerWidth>=1024?`--header-h-compact`:`--header-h`))||0,c=i.left-r.left>r.width*.3,l=c?{x0:0,x1:i.left-r.left,y0:s,y1:o}:{x0:0,x1:r.width,y0:s,y1:i.top-r.top};k.setLayout({bucket:z,view:N,name:c?`side`:`below`,corridor:we,free:l})},B=!1,V=()=>{let e=D?.querySelector(`[data-stage-track]`),t=D?.querySelector(`.shutters__frame`);if(B=!!(e&&t&&getComputedStyle(t).position===`sticky`),!B)return;let n=a(D),r=a(e),i=t.offsetHeight||N.h;Object.assign(P.shutters,{from:n.y-N.h,top:r.y,len:Math.max(r.h-i,1)})},Me=e=>B&&e>=P.shutters.from,H={on:!1,v:0},Ie={peak:0,held:0},Le=new b,Re=new S,Be=new b,He={pos:[0,0,0],look:[0,0,0],fov:0},Ue=-1,Ke=-1,qe=null,Je=matchMedia(`(pointer: fine)`).matches,Ye={x:0,v:0},Xe={x:0,v:0},Ze={x:0,y:0},Qe={az:0,el:0},U={level:1,from:1,to:1,start:0,dur:0,next:0},W=0,$e=0,et=w.U.uWind.value,tt=e=>e+jt.min+Math.random()*(jt.max-jt.min),nt=(e,t,n)=>Object.assign(U,{from:U.level,to:t,start:e,dur:n}),rt=e=>{if(Je)return!1;if(U.next||=e+jt.first[0]+Math.random()*(jt.first[1]-jt.first[0]),U.dur){let t=Math.min((e-U.start)/U.dur,1);return U.level=U.from+(U.to-U.from)*Lt(t),t>=1&&(U.dur=0,U.to<1?nt(e,1,jt.up):U.next=tt(e)),!0}return e>=U.next&&(nt(e,jt.dip,jt.down),!0)},it={t:te.room,nudge:Qe,intensity:1,rims:1},G={hero:0,passage:0,y:0,approach:0,stage:0,out:0},K={s:0,lift:0},q=-1,J=!0,at=!1,ot=!1,st=-1,ct=e=>{let t=scrollY;q=t,Tt(t,P,G);let n=G.passage,r=ie(n),i=fe(n);at=Me(t),J=!at&&i===`room`;let a=null,o=O,s=[],c=null,l=null;if(at){let e=P.shutters,n=$((t-e.from)/Math.max(e.top-e.from,1)),i=$((t-e.top)/e.len);if(G.s3=i,!ot&&qe==null&&(H.on=i>=ye.flood,H.v=+!!H.on),r=pe(i),j&&r<1){let e=ze(n,i);n+i!==Ue&&(j.pose(ke(i),e),Ue=n+i);let t=Ve(i);Math.abs(t-Ke)>1e-4&&(j.setSun(t),Ke=t),me(H.v,Ie);let r=ge(i,Ie),l=ce(i,e);j.setExposure(r,l,Math.max(e/ye.slatDeg,Bt(i))),j.shafts.uniforms.uDensity.value=Ft*(1+.6*Bt(i)+.4*Ie.peak),j.shafts.uniforms.uExposure.value=r,j.motes.uniforms.uBurst.value=Ie.peak,j.U.uMotesA.value=.55+.45*l,j.U.uTime.value=$e,j.U.uStir.value=W,j.sway($e);let u=j.camera;Fe(n,i,z,xe,He),u.position.set(...He.pos),u.fov=He.fov,u.aspect=N.w/N.h,u.lookAt(He.look[0],He.look[1],He.look[2]),u.updateMatrixWorld(),u.updateProjectionMatrix(),j.project(u,Re),Le.set(Re.x*.5+.5,Re.y*.5+.5,It.radius,It.held*Ie.held+It.peak*Ie.peak),c=Le,a=j.scene,o=u,s=j.passes}else j||(r=1)}else if(J){ne.at(t,K);let e=ne.room(O,K.s,K.lift);Te(Ye.x,Xe.x,e),Qe.az=-Ye.x*be.az*e,Qe.el=Xe.x*be.el*e,it.t=G.passage>0?zt(Mt.heroOut,Mt.hole,$(n/Ae.approach[1])):zt(Mt.rest,Mt.heroOut,G.hero),it.intensity=it.rims=zt(1,U.level,e),w.setSun(it);let r=$((K.s-1.3)/.7);w.U.uHaloA.value=zt(Pt.haloA[0],Pt.haloA[1],r),w.U.uHaloW.value=zt(Pt.haloW[0],Pt.haloW[1],r*r),w.shafts.uniforms.uDensity.value=zt(Pt.density[0],Pt.density[1],r),w.U.uCapShift.value.set(0,t*C.size.dpr),w.U.uTime.value=$e;let i=Math.min($e/kt,1);w.U.uLeafAmt.value=i*i*(3-2*i),w.U.uStir.value=W,a=w.scene,s=[w.shafts]}else if(A){let e=$((n-Ae.exit[0])/(Ae.exit[1]-Ae.exit[0])),i=A.specs.length,c=P.corridor;G.approach=R?$((t-(c.top-c.pre))/c.pre):0,G.stage=R?$((t-c.top)/c.len):0;let u=je.wash*N.h,d=c.top+De.release*c.len;G.out=R?$((t-d)/((1-De.release)*c.len+u)):0;let f=R?$((t-(c.top+c.len))/u):0;R&&e>=1?k.pose(A.camera,G.approach,G.stage,i,G.out):ne.exit(A.camera,e),f>0&&(r=f*f*(3-2*f),l=At);for(let e=0;e<i;e++)A.setDoor(e,R?Ne(e,G.stage,i):72);x>=2&&A.sway($e);let p=zt(De.sun[0],De.sun[1],G.stage);Math.abs(p-st)>1e-4&&(A.setSun(p),st=p),a=A.scene,o=A.camera,s=[A.shafts]}ot=at;let u=.3*(t-P.passage.top)/N.h;Be.set(!at&&n>0&&n<1?r:0,.25*Math.sin(u*2.1),-11*u,0),Z?.begin(),a?C.frame(a,o,s,r,c,Be,l):C.frame(w.scene,O,[],1,null,at?null:Be.setX(1)),Z?.end(e)},ut=0,ft=0,pt=0,mt=!0,ht=!document.hidden,Y=!!m?.state.paused,gt=!1,X=!1,_t=!1,Z=null,vt=!1,yt=()=>_t&&mt&&ht&&!gt&&!X,Q=()=>{!ut&&yt()&&(ut=requestAnimationFrame(xt))},bt=()=>navigator.scheduling?.isInputPending?.()??!1;function xt(e){if(ut=0,!yt())return;if(bt()){ut=requestAnimationFrame(xt);return}let t=ft?Math.min((e-ft)/1e3,.1):1/60;ft=e;let n=I||scrollY!==q,r=Me(scrollY)&&j&&pe(G.s3??0)<1,i=!J&&!at&&A&&x>=2&&ie(G.passage)<1,a=!Y&&!vt&&(J&&G.y<=1||r||i);if(Me(scrollY)){let e=P.shutters;qe==null&&he(H,$((scrollY-e.top)/e.len),t,Y)&&(n=!0)}if(!Y){Je&&(n=y(Ye,Ze.x,t,Ot)|y(Xe,Ze.y,t,Ot)||n),a&&J&&(n=rt(e)||n);let i=r?j.U.uWind.value:J?et:null;i&&Math.abs(W)>.001&&(i.y+=W*t*.12,i.x-=W*t*.05,(r?j:w).motes.wrap(i),n=!0),$e+=t}let o=!Y&&Z?.measuring;(n||o||a&&e-pt>=33&&!C.busy())&&(ct(e),pt=e,I=!1),(!Y&&a||o||I||n)&&(ut=requestAnimationFrame(xt))}let St=e=>{e.pointerType!==`mouse`||Y||(Ze.x=Math.max(-1,Math.min(1,e.clientX/innerWidth*2-1)),Ze.y=Math.max(-1,Math.min(1,1-e.clientY/innerHeight*2)),Q())},Ct=()=>{Ze.x=Ze.y=0,Q()},Et=e=>{let t=e.detail?.value??0;W=Y?0:t,W&&Q()},Vt=()=>Q(),Ht=new MutationObserver(()=>{vt=!!document.querySelector(`dialog[open]`),I=!0,Q()});document.querySelectorAll(`dialog`).forEach(e=>Ht.observe(e,{attributes:!0,attributeFilter:[`open`]}));let Ut=()=>{ht=!document.hidden,ft=0,Z?.gap(),Q()},Wt=0,Gt=()=>{cancelAnimationFrame(Wt),Wt=requestAnimationFrame(()=>{Ee(),Q()})};Je&&addEventListener(`pointermove`,St,{passive:!0}),Je&&document.documentElement.addEventListener(`mouseleave`,Ct),m?.addEventListener(`velocity`,Et),addEventListener(`scroll`,Vt,{passive:!0}),document.addEventListener(`visibilitychange`,Ut),addEventListener(`resize`,Gt);let Kt=new ResizeObserver(Gt);Kt.observe(ee??t),T&&Kt.observe(T),E&&Kt.observe(E),D&&Kt.observe(D);let qt=E?new MutationObserver(()=>{Oe(),I=!0,Q()}):null;qt?.observe(E,{attributes:!0,attributeFilter:[`class`]});let Jt=new Map,Yt=new IntersectionObserver(e=>{for(let t of e)Jt.set(t.target,t.isIntersecting&&(t.target!==D||B));let n=mt;mt=[...Jt.values()].some(Boolean),mt&&!n&&yt()&&(ct(performance.now()),I=!1),t.style.visibility=mt?``:`hidden`,mt||Z?.gap(),ft=0,Q()},{rootMargin:`10% 0px`});document.querySelectorAll(`[data-world]`).forEach(e=>Yt.observe(e));let Xt=0,Zt=0,Qt=e=>{if(e.preventDefault(),gt=!0,cancelAnimationFrame(ut),ut=0,_.classList.remove(`world-live`),++Xt>1)return tn(`context lost twice`);Zt=setTimeout(()=>tn(`context not restored`),5e3)},$t=async()=>{clearTimeout(Zt),Z?.dispose(),Z=null,gt=!1,Ee(),ct(performance.now()),await Rt(),!gt&&!X&&(_.classList.add(`world-live`),Q())};t.addEventListener(`webglcontextlost`,Qt),t.addEventListener(`webglcontextrestored`,$t);let en=({state:e,p75:t,final:n,source:r})=>{console.info(`[world] gate p75 ${t.toFixed(1)} ms (${r}) → ${e}${n?` (final)`:``}`);let i=()=>Promise.all([w,A,j].filter(Boolean).map(e=>e.compile())).then(()=>{I=!0,Q()});if(e===`t1`)x=1,C.setTier(1),w.setTier(1),A?.setTier(1),j?.setTier(1),Ee(),i();else if(e===`lite`){for(let e of[w,A,j])e&&(e.shafts.enabled=!1);C.setTier(1,{lite:!0}),Ee()}else e===`off`&&tn(`frame time over the Tier 1 limit`)};function tn(e){X||(console.info(`[world] back to stills: ${e}`),rn.dispose())}let nn=null,rn={setPaused(e){Y=!!e,Ye.v=Xe.v=0,W=0,ft=0,Z?.gap(),I=!0,Y||nn?.(),Q()},dispose(){X||(X=!0,nn?.(),cancelAnimationFrame(ut),cancelAnimationFrame(Wt),clearTimeout(Zt),removeEventListener(`pointermove`,St),document.documentElement.removeEventListener(`mouseleave`,Ct),m?.removeEventListener(`velocity`,Et),m?.removeEventListener(`pause`,an),removeEventListener(`scroll`,Vt),document.removeEventListener(`visibilitychange`,Ut),removeEventListener(`resize`,Gt),Kt.disconnect(),Yt.disconnect(),qt?.disconnect(),Ht.disconnect(),t.removeEventListener(`webglcontextlost`,Qt),t.removeEventListener(`webglcontextrestored`,$t),_.classList.remove(`world-live`),t.style.visibility=``,document.getElementById(`world-frame-debug`)?.remove(),Z?.dispose(),A?.dispose(),j?.dispose(),D?.classList.remove(`is-live`),w.dispose(),C.dispose(),Ge(m))}},an=e=>rn.setPaused(e.detail.paused);if(m?.addEventListener(`pause`,an),await document.fonts?.ready,Ee(),await w.compile(),await C.compile(),m&&m.state?.sunIn!==`done`&&await new Promise(e=>{let t=setTimeout(e,4e3);m.addEventListener(`sun-in-end`,()=>{clearTimeout(t),e()},{once:!0})}),Y&&!X&&await new Promise(e=>nn=()=>{nn=null,e()}),X)return rn;Ee(),ct(performance.now());let on=C.renderer.info.render;if(console.info(`[world] room · tier ${x} (${h.reason}; ${h.gpu}) · ${on.calls} draw calls · ${on.triangles} triangles · dpr ${C.size.dpr} · canvas ${Math.round(N.w*C.size.dpr)}×${Math.round(N.h*C.size.dpr)} · ${C.msaa?`4x MSAA`:`no MSAA`} · ~${C.memoryMB} MB · shafts ${w.shafts.size.join(`×`)} · motes ${w.motes.count} · parallax ${L}${F.ok?F.still?` · framed as the still`:` · framed live (the still would touch text)`:` · text-safe framing not found`} · flight held ${_e.held}/${_e.rows}, carried ${_e.carried}`),await Rt(),X||gt)return rn;_.classList.add(`world-live`),_t=!0,g??(Z=We(C.renderer.getContext(),{tier:x,onStep:en})),Q(),v&&p(()=>import(`./debug-D4ibxs8k.js`).then(e=>e.attach({gl:C,room:w,corridor:()=>A,tier:()=>x,frame:()=>F,solved:()=>_e,at:G,path:K,span:P,inRoom:()=>J,walking:()=>R,walk:k,flight:ne,section:ee,parallax:()=>L,shutters:()=>({staged:B,built:!!j,at:G.s3??null,flood:{...H},span:{...P.shutters}}),hold:e=>{qe=e,e!=null&&Object.assign(H,{on:!0,v:e}),I=!0,Q()},redraw:()=>ct(performance.now()),paused:()=>Y,running:yt,pointer:(e,t)=>{Ze.x=Ye.x=e,Ze.y=Xe.x=t,Ye.v=Xe.v=0,I=!0,Q()}})),__vite__mapDeps([0,1]));let sn=()=>new Promise(e=>`requestIdleCallback`in window?requestIdleCallback(e,{timeout:1500}):setTimeout(e,50));return sn().then(async()=>{if(X)return;let{buildCorridor:e}=await p(async()=>{let{buildCorridor:e}=await import(`./corridor-K23JNyc0.js`);return{buildCorridor:e}},__vite__mapDeps([2,3,4]));if(X)return;let t=await e({gl:C,tier:x,beats:Ce,textures:w.textures});if(t.setTier(x),t.shafts.setSize(Math.round(N.w*C.size.dpr),Math.round(N.h*C.size.dpr)),await t.compile(),X)return t.dispose();A=t,I=!0,Q(),console.info(`[world] corridor · ${Ce.length} doors (${Ce.map(e=>`${e.unit} ${e.door}`).join(`, `)})`)}).catch(e=>console.warn(`[world] corridor unavailable, the wash holds`,e)).then(()=>D&&!X?sn():null).then(async()=>{if(!D||X)return;let{buildShutters:e}=await p(async()=>{let{buildShutters:e}=await import(`./shutters-D8xkGbI1.js`);return{buildShutters:e}},__vite__mapDeps([5,3,4]));if(X)return;let t=await e({gl:C,tier:x,textures:w.textures});if(t.shafts.setSize(Math.round(N.w*C.size.dpr),Math.round(N.h*C.size.dpr)),t.U.uPx.value=C.size.dpr,await t.compile(),X)return t.dispose();j=t,D.classList.add(`is-live`),I=!0,Q(),console.info(`[world] shutters ready`)}).catch(e=>console.warn(`[world] shutters unavailable, the stills stay`,e)),rn}export{Vt as live,Je as n,Qe as t};