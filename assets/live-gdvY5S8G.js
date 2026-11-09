const __vite__mapDeps=(i,m=__vite__mapDeps,d=(m.f||(m.f=["assets/debug-D4ibxs8k.js","assets/frame-Cy9oWa7v.js","assets/corridor-Slq4L6l2.js","assets/main-qbjrt-6l.js","assets/main-B6g6pUZ4.css","assets/journey-pyRzNSax.js","assets/common-DOLhauL3.js","assets/shutters-1aebQVtV.js"])))=>i.map(i=>d[i]);
import{a as e,c as t,d as n,f as r,i,l as a,n as o,r as s,s as c,t as l,u}from"./frame-Cy9oWa7v.js";import{n as d,r as f,t as p}from"./main-qbjrt-6l.js";import{$ as m,A as h,At as g,C as _,Ct as v,D as y,Dt as b,E as x,Et as S,F as C,Ft as w,G as T,H as ee,I as te,J as ne,M as E,Mt as D,N as re,Nt as O,O as ie,Ot as ae,P as oe,Pt as k,Q as A,R as j,S as se,St as M,T as ce,Tt as N,V as le,W as ue,X as de,Y as fe,Z as pe,_ as me,a as P,b as he,bt as F,c as ge,ct as I,d as _e,dt as L,et as R,f as ve,ft as ye,g as be,gt as xe,h as Se,i as Ce,it as we,j as Te,jt as Ee,k as De,kt as Oe,l as ke,m as Ae,n as je,nt as Me,o as Ne,ot as Pe,p as Fe,pt as Ie,r as Le,s as Re,st as z,t as ze,tt as Be,u as Ve,ut as B,v as He,vt as Ue,w as We,wt as Ge,x as Ke,y as qe,yt as Je,z as Ye}from"./journey-pyRzNSax.js";import{t as Xe,worldOff as Ze}from"./world-C4DHLpD0.js";function Qe(e){return new D({uniforms:e,vertexShader:`
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
      ${R}
      ${pe}
      ${I}
      ${z}
      ${ne}
      float occ(float d, float r, float s){ return d < 0.003 ? 1. : 1. - s * (1. - smoothstep(0., r, d)); }
      void main(){
        int k = int(vKind + 0.5);           // 0 lime wall, 1 terrazzo floor, 2 ceiling
        vec3 N = normalize(vN);
        vec2 uv = abs(N.z) > .5 ? vP.xy : abs(N.x) > .5 ? vP.zy : vP.xz;
        vec4 lw = texture2D(uLime, uv * 0.25);
        float vertical = 1. - step(.5, abs(N.y));
        float mott = (lw.r - .5) * .09 + (lw.g - .5) * .05 + (lw.b - .5) * .04 * vertical;
        vec3 rel = k == 1 ? texture2D(uTerr, vP.xz * 0.5).rgb / ${ee.toFixed(3)} * (1. + mott * .5) : vec3(1. + mott);

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
      }`,toneMapped:!1})}var $e={1:160,2:420};function et(e,{min:t,max:n},{mask:r=null}={}){let i=$e[2],a=new Float32Array(i*4),o=7,s=()=>(o=o*16807%2147483647)/2147483647;for(let e=0;e<a.length;e++)a[e]=s();let c=new xe;c.setAttribute(`position`,new F(new Float32Array(i*3),3)),c.setAttribute(`aSeed`,new F(a,4));let l=new k(...t),u=new k(...n).sub(l),d=new D({uniforms:{...e,uBoxMin:{value:l},uBoxSize:{value:u},uBurst:{value:0}},vertexShader:`
      attribute vec4 aSeed;
      uniform vec3 uBoxMin, uBoxSize, uWind;
      uniform float uTime, uPx, uMotesA, uStir, uSunI, uBurst;
      varying float vA;
      ${R}
      ${I}
      ${r?r.glsl:pe+z}
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
      void main(){ vec2 d = gl_PointCoord - .5; gl_FragColor = vec4(uMoteCol * vA * exp(-dot(d, d) * 18.), 1.); }`,blending:2,transparent:!0,depthWrite:!1,toneMapped:!1}),f=new Oe(c,d);return f.frustumCulled=!1,f.renderOrder=5,{points:f,uniforms:d.uniforms,setTier(e){c.setDrawRange(0,$e[e>=2?2:1])},get count(){return c.drawRange.count},wrap(e){e.set((e.x%u.x+u.x)%u.x,(e.y%u.y+u.y)%u.y,(e.z%u.z+u.z)%u.z)},dispose(){c.dispose(),d.dispose()}}}var tt=`
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
`,nt=()=>({uSkyLow:{value:L(`sky`)},uSkyTop:{value:B(L(`sky`),L(`shade-100`),.35)},uCanopy:{value:B(L(`sky`),L(`unit-green-bamboo`),.2)},uSunGlow:{value:L(`on-dusk`)},uStreetX:{value:9}});function rt(e,{x:t,z:n,size:r=[40,20]}){let i=new D({uniforms:e,vertexShader:`varying vec3 vP; void main(){ vec4 w = modelMatrix * vec4(position, 1.); vP = w.xyz; gl_Position = projectionMatrix * viewMatrix * w; }`,fragmentShader:`
      uniform vec3 uL;
      varying vec3 vP;
      ${I}
      ${Pe}
      ${tt}
      void main(){
        gl_FragColor = vec4(street(cameraPosition, normalize(vP - cameraPosition)), 1.);
        #include <colorspace_fragment>
        DITHER_OUT;
      }`,toneMapped:!1}),a=new N(new ae(...r),i);return a.rotation.y=-Math.PI/2,a.position.set(t,r[1]/2-2,n),a}function it({parapet:e=1.8,canopy:t=2.6,trunk:n=[-1.2,-1]}={}){let r=B(L(`sun-200`),L(`sun-100`),.15),i=L(`sun-100`),a=B(L(`sun-200`),L(`sun-400`),.45),o=B(B(L(`lime-300`),L(`sun-200`),.35),L(`gold-700`),.1);return new D({uniforms:{uLow:{value:r},uHigh:{value:i},uLeaf:{value:a},uWall:{value:o},uHole:{value:i.clone()},uY:{value:[e,t]},uTrunk:{value:n},uSway:{value:0}},vertexShader:`varying vec3 vP; void main(){ vec4 w = modelMatrix * vec4(position, 1.); vP = w.xyz; gl_Position = projectionMatrix * viewMatrix * w; }`,fragmentShader:`
      varying vec3 vP;
      uniform vec3 uLow, uHigh, uLeaf, uWall, uHole; uniform float uY[2], uTrunk[2], uSway;
      ${I}
      ${R}
      ${Pe}
      #define B ${m.toFixed(4)}
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
      #undef B`,toneMapped:!1})}var at=`
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
`,ot=`varying vec3 vP; varying vec3 vN;
void main(){
  mat4 m = modelMatrix;
  #ifdef USE_INSTANCING
  m = m * instanceMatrix;
  #endif
  vec4 w = m * vec4(position, 1.);
  vP = w.xyz; vN = normalize(mat3(m) * normal);
  gl_Position = projectionMatrix * viewMatrix * w;
}`,V=`
varying vec3 vP; varying vec3 vN;
${R}
${pe}
${I}
${Pe}
${z}
${tt}
${at}
`;function H(e,t){let n=new D({uniforms:e,vertexShader:ot,fragmentShader:`
      ${V}
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
      }`,toneMapped:!1}),r=new ae(t.z1-t.z0,t.y1-t.y0),i=new N(r,n);return i.rotation.y=-Math.PI/2,i.position.set(t.sx,(t.y0+t.y1)/2,(t.z0+t.z1)/2),i}function st(){let e=m/2,{r:t,pipAt:n,pipR:r}=Me,i=new O().moveTo(-e,-e).lineTo(e,-e).lineTo(e,e).lineTo(-e,e).closePath(),a=new S;for(let e=0;e<40;e++){let n=-e/40*Math.PI*2,r=2*t*Math.max(Math.cos(n),Math.cos(n-Math.PI/2),Math.cos(n-Math.PI),Math.cos(n+Math.PI/2));e?a.lineTo(r*Math.cos(n),r*Math.sin(n)):a.moveTo(r*Math.cos(n),r*Math.sin(n))}a.closePath(),i.holes.push(a);for(let[e,t]of[[1,1],[-1,1],[-1,-1],[1,-1]])i.holes.push(new S().absarc(e*n,t*n,r,0,Math.PI*2,!0));let o=new Je(i,{depth:Be,bevelEnabled:!1,curveSegments:8}).rotateY(Math.PI/2),s=o.getAttribute(`normal`),c=[];for(let e=0;e<s.count;e+=3)s.getX(e)<.5&&c.push(e,e+1,e+2);return o.setIndex(c),o}function ct(e,t){let n=new D({uniforms:e,vertexShader:ot,fragmentShader:`
      ${V}
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
      }`,toneMapped:!1}),r=Math.round((t.z1-t.z0)/m),i=Math.round((t.y1-t.y0)/m),a=new M(st(),n,r*i),o=new Ge,s=0;for(let e=0;e<r;e++)for(let n=0;n<i;n++)a.setMatrixAt(s++,o.makeTranslation(t.sx,t.y0+(n+.5)*m,t.z0+(e+.5)*m));return a.instanceMatrix.needsUpdate=!0,a.computeBoundingSphere(),a}var lt=(e,t,n)=>n>=2?ct(e,t):H(e,t);function ut(e,t,n){let r=m/2;return H(e,{sx:t.sx,z0:n.z-r,z1:n.z+r,y0:n.y-r,y1:n.y+r})}function dt(e,t){let n=Math.round((e.y1-e.y0)/m);return Math.floor((t.z-e.z0)/m)*n+Math.floor((t.y-e.y0)/m)}var U={min:[-6,0,-4],max:[2.6,3.3,4]},W={sx:2.6,z0:-4,z1:1.4,y0:.9,y1:3.3},ft=new k(2.6,1.05,-1.15),pt={portrait:new k(2.6,1.05,-3.25)},G=e=>pt[e]??ft,K={min:[-3.5,.05,-3.9],max:[2.5,3.2,2]},mt={portrait:{pos:[.83,1.1,-2.93],look:[1.68,.91,-3.43],fov:49.6},tablet:{pos:[-1.3,1.1,3.15],look:[1.2,1.95,-3.6],fov:54},landscape:{pos:[-1.5,1.1,3.3],look:[.5,1.78,-4],fov:46},wide:{pos:[-1.5,1.1,3.3],look:[.5,1.78,-4],fov:44}},ht={silhouette:(e=>[new k(e.sx,e.y0,e.z0),new k(e.sx,e.y1,e.z0),new k(e.sx,e.y1,e.z1),new k(e.sx,e.y0,e.z1)])(W),floor:[new k(U.min[0],0,U.min[2]),new k(U.max[0],0,U.min[2]),new k(U.max[0],0,U.max[2]),new k(U.min[0],0,U.max[2])],heroBlock:ft};function gt(){let[e,t,n]=U.min,[r,i,a]=U.max,o=(e+r)/2,s=r-e,c=a-n,l=i-t,u=[[s,l,[0,0,0],[o,l/2,n],0],[c,l,[0,Math.PI/2,0],[e,l/2,(n+a)/2],0],[s,c,[-Math.PI/2,0,0],[o,t,(n+a)/2],1],[s,c,[Math.PI/2,0,0],[o,i,(n+a)/2],2],[W.z1-W.z0,W.y0,[0,-Math.PI/2,0],[r,W.y0/2,(W.z0+W.z1)/2],0],[a-W.z1,l,[0,-Math.PI/2,0],[r,l/2,(W.z1+a)/2],0],[s,l,[0,Math.PI,0],[o,l/2,a],0]],d=[],f=[],p=[],m=[],h=new Ge;for(let[e,t,n,r,i]of u){let a=new ae(e,t);h.makeRotationFromEuler(new Ue(...n)).setPosition(...r),a.applyMatrix4(h);let o=d.length/3;d.push(...a.getAttribute(`position`).array),f.push(...a.getAttribute(`normal`).array);for(let e=0;e<a.getAttribute(`position`).count;e++)p.push(i);m.push(...Array.from(a.index.array,e=>e+o)),a.dispose()}let g=new xe;return g.setAttribute(`position`,new F(d,3)),g.setAttribute(`normal`,new F(f,3)),g.setAttribute(`aKind`,new F(p,1)),g.setIndex(m),g}async function _t({gl:e,tier:t,slice:n=!0,shaftSteps:r=null}){let[i,a]=await Promise.all([ue({slice:n}),T({slice:n})]),o=j();Ye(`room`,te.room,o);let s=[L(`wallShade`),L(`floorShade`),L(`ceiling`)].map(ye),c=[L(`wallSun`),L(`wallSun`).multiplyScalar(.8),L(`wallSun`)].map(ye),l=L(`sunRoom`),u=Math.max(o.dir.z,.05),d=o.dir,f={...A(W,d),...fe(),...nt(),uShade:{value:s},uSunT:{value:c.map(e=>e.clone())},uNdlRef:{value:u},uGlare:{value:ye(L(`sky`))},uSunCol:{value:new k},uSunI:{value:1},uRimI:{value:1},uHaloA:{value:.35},uHaloW:{value:.012},uBoxMin:{value:new k(...U.min)},uBoxMax:{value:new k(...U.max)},uLime:{value:i},uTerr:{value:a},uFace:{value:L(`screenFace`)},uGlow:{value:L(`sky`)},uRim:{value:B(L(`wallSun`),L(`sky`),.3)},uWall:{value:B(L(`screenFace`),L(`sky`),.38)},uTime:{value:0},uLeafAmt:{value:0},uWind:{value:new k},uStir:{value:0},uMotesA:{value:1},uPx:{value:1},uMoteCol:{value:ye(B(L(`sky`),L(`sun-200`),.6).convertLinearToSRGB())}},p=new Ee,m=new N(gt(),Qe(f)),h=lt(f,W,1),g=rt(f,{x:9,z:-1}),_=et(f,K);p.add(m,h,g,_.points);let v=le(e.renderer,f,{box:U,tier:t,ortho:e.ortho,steps:r}),y=new b(46,1,.05,60),x=[],S=e=>{if(e>=2!=!!h.isInstancedMesh){p.remove(h),h.geometry.dispose(),h.material.dispose(),h=lt(f,W,e),p.add(h);for(let e of x)p.remove(e),e.geometry.dispose(),e.material.dispose();if(x=[],h.isInstancedMesh){for(let e of[ft,...Object.values(pt)])h.setMatrixAt(dt(W,e),new Ge().makeScale(0,0,0)),x.push(ut(f,W,e));h.instanceMatrix.needsUpdate=!0,p.add(...x)}}g.visible=e>=2,_.setTier(e),v.setTier(e)};S(t);let C=({t:e=te.room,nudge:t=null,intensity:n=1,rims:r=n}={})=>{Ye(`room`,e,o,t);let i=o.color.r/l.r,a=o.color.g/l.g,s=o.color.b/l.b;for(let e=0;e<3;e++)f.uSunT.value[e].set(c[e].x*i,c[e].y*a,c[e].z*s);f.uSunCol.value.set(o.color.r,o.color.g,o.color.b),f.uSunI.value=n,f.uRimI.value=r};return C(),{scene:p,camera:y,U:f,shafts:v,motes:_,setTier:S,setSun:C,setShot:(e,t,n,r=mt[e.name])=>{y.position.set(...r.pos),y.fov=r.fov,y.aspect=t/n,y.lookAt(...r.look),y.clearViewOffset(),y.updateProjectionMatrix(),y.updateMatrixWorld()},textures:{lime:i,terr:a},compile:async()=>{await e.renderer.compileAsync(p,y);for(let t of v.scenes)await e.renderer.compileAsync(t,e.ortho)},dispose(){p.traverse(e=>{(e.isMesh||e.isPoints)&&(e.geometry.dispose(),e.material.dispose())}),v.dispose(),i.dispose(),a.dispose()}}}var vt={portrait:{pos:[1.52,1.1,-3.05],fov:56},tablet:{pos:[.3,1.1,.4],fov:50},landscape:{pos:[.2,1.1,.6],fov:42},wide:{pos:[.2,1.1,.6],fov:40}},yt={portrait:{dist:.5,fov:56},tablet:{dist:.42,fov:50},landscape:{dist:.35,fov:42},wide:{dist:.35,fov:40}},bt={portrait:[20,8],tablet:[28,14]},q=e=>bt[e]??[32,E.yaw],xt={portrait:2.2},St=e=>xt[e]??.8,J=6,Ct=(e,t,n)=>{let r=v.clamp((n-e)/(t-e),0,1);return r*r*(3-2*r)},Y=e=>.5-.5*Math.cos(Math.PI*v.clamp(e,0,1)),wt=e=>Math.sin(v.clamp(e,0,1)*Math.PI/2);function X(e){let t=Math.tan(v.degToRad(e.fov/2))/e.zoom,n=2*t,r=e.aspect*n,i=-r/2,a=t,o=e.view;return o?.enabled&&(i+=o.offsetX*r/o.fullWidth,a-=o.offsetY*n/o.fullHeight,r*=o.width/o.fullWidth,n*=o.height/o.fullHeight),{l:i,r:i+r,t:a,b:a-n}}var Tt=(e,t,n)=>{let r=Math.tan(v.degToRad(e/2));return Object.assign(n,{l:-r*t,r:r*t,t:r,b:-r})};function Z(e,t,n,r){let i=e.near,a=n*(t.t-t.b)/r;e.projectionMatrix.makePerspective(t.l*i,t.r*i,(t.t-a)*i,(t.b-a)*i,i,e.far),e.projectionMatrixInverse.copy(e.projectionMatrix).invert()}var Et=(e,t)=>{let n=t.clone().sub(e).normalize();return[Math.atan2(-n.x,-n.z),Math.asin(v.clamp(n.y,-1,1))]},Dt=(e,t,n)=>{let r=t-e;for(;r>Math.PI;)r-=2*Math.PI;for(;r<-Math.PI;)r+=2*Math.PI;return e+r*n};function Q(e,t,n,r,i,a){let o=i*i,s=o*i;for(let c of[`x`,`y`,`z`])a[c]=.5*(2*t[c]+(n[c]-e[c])*i+(2*e[c]-5*t[c]+4*n[c]-r[c])*o+(3*t[c]-e[c]-3*n[c]+r[c])*s);return a}function Ot(){let e={pos:new k,yaw:0,pitch:0,fov:46,bounds:{l:-1,r:1,t:1,b:-1}},n=new k,r=new k,i=`landscape`,a=1,o=1,s=G(`landscape`),l=0,u=0,d=new k,f=new Ue(0,0,0,`YXZ`),p=new g,m={l:0,r:0,t:0,b:0},h={l:0,r:0,t:0,b:0},_=new k,y=(t,c,d)=>{i=c,a=d.w/d.h,o=d.h,e.pos.copy(t.position);let f=new k(0,0,-1).applyQuaternion(t.quaternion);e.yaw=Math.atan2(-f.x,-f.z),e.pitch=Math.asin(v.clamp(f.y,-1,1)),e.fov=t.fov,Object.assign(e.bounds,X(t)),s=G(i),n.set(...vt[i].pos),_.copy(n).sub(s).setY(0).normalize().lerp(new k(-1,0,0),.35).normalize(),r.copy(s).addScaledVector(_,yt[i].dist).setY(1.1),[l,u]=Et(n,s)},b=(t,c,g=0)=>{c<=1?Q(e.pos,e.pos,n,r,c,d):Q(e.pos,n,r,r,c-1,d),t.position.copy(d);let _,y,b;if(c<=1){let t=Y(c),[n,r]=Et(d,s),a=Ct(.6,1,c);_=Dt(e.yaw,Dt(l,n,a),t),y=v.lerp(e.pitch,v.lerp(u,r,a),Ct(.45,1,c)),b=v.lerp(e.fov,vt[i].fov,t)}else[_,y]=Et(d,s),b=v.lerp(vt[i].fov,yt[i].fov,c-1);t.quaternion.copy(p.setFromEuler(f.set(y,_,0))),t.fov=b,t.updateMatrixWorld();let x=Ct(0,.7,c);Tt(b,a,h);for(let t of[`l`,`r`,`t`,`b`])m[t]=v.lerp(e.bounds[t],h[t],x);return Z(t,m,g,o),1-x},x=(e,t)=>{let n=wt(t);e.position.lerpVectors(E.from,E.to,n),e.fov=E.fov[i],e.aspect=a;let[r,s]=q(i);e.lookAt(oe(e.position,v.lerp(r,s,n))),e.updateMatrixWorld(),Z(e,Tt(e.fov,a,h),0,o)},S=null;return{setRest:y,room:b,exit:x,solve:(e,{heroH:n,approach:r,rects:a,shapes:o,view:s,top:l=0})=>{let u=n+r,d=Math.ceil(u/J)+1,f=n+.6*r,p=e=>e<=n?v.clamp(e/n,0,1)**1.3:1+Y((e-n)/r),m=a.filter(e=>e.kind!==`opaque`&&!e.pinned),h=(n,r,i)=>{b(e,n,r);let a=t(o.silhouette,e,s.w,s.h),u=t(o.floor,e,s.w,s.h);for(let e of m){let t=e.y-i,n=i>80?l:0;if(t+e.h<=n||t>=s.h)continue;let r={x:e.x,y:Math.max(t,n),w:e.w,h:Math.min(t+e.h,s.h)-Math.max(t,n)};if(c(a,0,0,r)||e.kind===`body`&&c(u,0,0,r))return!1}return!0},g=(e,t,n)=>{for(let r=0;r<10;r++){let r=(t+n)/2;e(r)?n=r:t=r}return n},y=(e,t,n)=>h(e,t,n)?e:h(0,t,n)?e-g(r=>h(e-r,t,n),0,e):0,x=[];for(let e=0;e<d;e++){let t=Math.min(e*J,u),r=p(t),i=t<f?t:0,a=r,o=0;t<n&&!h(r,0,t)&&(h(0,0,t)?a=y(r,0,t):(o=i>0&&h(0,i,t)?g(e=>h(0,e,t),0,i):i,a=y(r,o,t))),x.push({y:t,s:a,lift:o,need:o})}for(let e=1;e<d;e++)x[e].lift=Math.max(x[e].need,x[e-1].lift-St(i)*J);for(let e=d-2;e>=0;e--)x[e].lift=Math.min(x[e].y,Math.max(x[e].lift,x[e+1].lift-J));for(let e of x)e.y>=f&&(e.lift=0);for(let e of x)e.y>=n||e.lift===e.need||h(e.s,e.lift,e.y)||(e.s=h(0,e.lift,e.y)?e.s-g(t=>h(e.s-t,e.lift,e.y),0,e.s):0);for(let e=d-2;e>=0;e--)x[e].s=Math.min(x[e].s,x[e+1].s);let C=v.degToRad(40)*J/(.6*s.h),w=t=>(b(e,t,0),_.set(0,0,-1).applyQuaternion(e.quaternion),Math.atan2(-_.x,-_.z)),T=w(x[0].s);for(let e=1;e<d;e++){let t=x[e-1].s,n=e=>Math.abs(Dt(0,w(e)-T,1));x[e].s>t&&n(x[e].s)>C&&(x[e].s=t+(x[e].s-t)*(1-g(r=>n(x[e].s-(x[e].s-t)*r)<=C,0,1))),T=w(x[e].s)}return S={rows:x,end:u},{rows:d,held:x.filter(e=>e.s<p(e.y)-.001).length,carried:x.filter(e=>e.lift>0).length}},at:(e,t)=>{if(!S)return Object.assign(t,{s:0,lift:0});let{rows:n}=S,r=v.clamp(e/J,0,n.length-1),i=Math.floor(r),a=Math.min(i+1,n.length-1),o=r-i;return t.s=v.lerp(n[i].s,n[a].s,o),t.lift=v.lerp(n[i].lift,n[a].lift,o),t},get bucket(){return i},get table(){return S}}}function kt(e,{hero:t,passage:n},r){return r.hero=v.clamp((e-t.top)/t.len,0,1),r.passage=v.clamp((e-n.top)/n.len,0,1),r.y=e-t.top,r}var At=e=>{let t=v.clamp(e,0,1);return t*t*(3-2*t)},jt=Math.tan(v.degToRad(3));function Mt(){let e=new k,t=new k,n={pos:[0,0,0],yaw:0,fov:0},r={l:0,r:0,t:0,b:0},i={l:0,r:0,t:0,b:0},a=`side`,o=null,s=1,c=1,l=`landscape`,u=null;return{setLayout:({bucket:e,view:t,free:n,name:r,corridor:i})=>{l=e,a=r,u=i,s=t.w/t.h,c=t.h;let d=x(1,a,u),f=u.doors[0]?.zc??d.pos[2]-P[a].d,[p,m]=P[a].at,h=(n.x0+p*(n.x1-n.x0))/t.w,g=(n.y0+m*(n.y1-n.y0))/t.h;o=be(d.pos,d.yaw,[ze.wallX,je,f],d.fov,s,h,g)},pose:(d,f,p,m,h=0,g=0,_=0)=>{let b=x(y(p,m)+We(h),a,u,n),S=At(f),C=v.degToRad(v.lerp(q(l)[1],b.yaw,S)),w=v.lerp(E.fov[l],b.fov,S);e.set(v.lerp(E.to.x,b.pos[0],S),1.1,v.lerp(E.to.z,b.pos[2],S)),d.position.copy(e),d.fov=w,d.aspect=s;let T=v.degToRad(g),ee=Math.cos(T)*10;d.lookAt(t.set(e.x+Math.sin(C)*ee,1.1-Math.sin(T)*10,e.z-Math.cos(C)*ee)),d.updateMatrixWorld(),Tt(w,s,r);let te=Math.tan(v.degToRad(w/2))/Math.tan(v.degToRad(b.fov/2));for(let e of[`l`,`r`,`t`,`b`])i[e]=v.lerp(r[e],o[e]*te,S);if(a===`side`){let e=jt*_e(p,m)*S;i.t-=e,i.b-=e}Z(d,i,_,c)},get layout(){return a},get lens(){return o}}}var Nt=6,Pt=2.5,Ft=L(`lime-100`),It=L(`wash`),Lt={dip:.82,down:1600,up:2400,min:14e3,max:22e3,first:[3500,5e3]},Rt={rest:te.room,heroOut:.14,hole:.2,corridor:Ne.sun[0]},zt={truck:.05,yaw:.8*Math.PI/180,pitch:.4*Math.PI/180},Bt={haloA:[.35,.95],haloW:[.012,.06],density:[1.6,2.8]},Vt=1.8,Ht={radius:.62,held:.1,peak:.55},Ut=h(Ie.inOut),Wt=()=>new Promise(e=>requestAnimationFrame(e)),Gt=(e,t,n)=>e+(t-e)*n,$=e=>Math.min(Math.max(e,0),1),Kt=e=>Se(e)/Ce.leafDeg;async function qt({canvas:t,stage:c,bus:m,probed:h,forced:v}){let y=document.documentElement,b=new URLSearchParams(location.search).get(`debug`),x=h.tier,S=we(t,{context:h.gl,tier:x}),T=await _t({gl:S,tier:x}),ee=c?.closest(`[data-world]`)??document.querySelector(`[data-world="room"]`),ne=document.querySelector(`[data-world="passage"]`),E=document.querySelector(`[data-world="corridor"]`),D=document.querySelector(`[data-world="shutters"]`),O=T.camera,ae=Ot(),oe=Mt(),A=null,j=null,M={x:0,y:0,w:1,h:1},N={w:1,h:1},le={x:0,y:0,w:1,h:1},ue=new k,fe=new g,pe=new Ue(0,0,0,`YXZ`),P={hero:{top:0,len:1},passage:{top:0,len:1},corridor:{top:0,len:1,pre:1,next:0},shutters:{from:0,top:0,len:1}},F=null,I=!0,_e=1,L=null,R=!1,ye=`landscape`,be=me(document.querySelectorAll(`[data-beat]`)),xe=ke(be),Ee=(e,t,n)=>{n&&(e||t)&&(ue.set(1,0,0).applyQuaternion(O.quaternion),O.position.addScaledVector(ue,e*zt.truck*_e*n),O.quaternion.multiply(fe.setFromEuler(pe.set(t*zt.pitch*_e*n,e*zt.yaw*_e*n,0))),O.updateMatrixWorld())},Oe=()=>{Object.assign(M,a(ee)??{x:0,y:0,w:innerWidth,h:innerHeight});let m=t.clientWidth||innerWidth,h=t.clientHeight||innerHeight;S.resize(m,h);let{dpr:g}=S.size;T.shafts.setSize(Math.round(m*g),Math.round(h*g)),A?.shafts.setSize(Math.round(m*g),Math.round(h*g)),j?.shafts.setSize(Math.round(m*g),Math.round(h*g)),j&&(j.U.uPx.value=g),N.w=m,N.h=h;let _=f(y.clientWidth||m,y.clientHeight||h),[v,x]=_.view,C=Math.max(M.w/v,M.h/x),w=e(M,_.view,C);Object.assign(le,{x:M.x+w.x,y:M.y+w.y,w:v*C,h:x*C}),T.setShot(_,v,x);let te=u(),E=i(r(te,M,_.view),_.name);F=n({camera:O,width:v,height:x,rects:E,...ht,stage:c?r(a(c),M,_.view):null});let D=d[_.name],re=D&&o(F,D.sx,D.sy);re&&Object.assign(F,{sx:D.sx,sy:D.sy,still:!0});let ie=O.quaternion.clone(),oe=O.position.clone();_e=0;for(let e of[1,.5,.25]){if(_e=e,[[-1,-1],[1,-1],[-1,1],[1,1]].every(([e,t])=>(O.position.copy(oe),O.quaternion.copy(ie),Ee(e,t,1),s(F,O,ht,v,x))))break;_e=0}O.position.copy(oe),O.quaternion.copy(ie),O.updateMatrixWorld(),l(O,le,N,{sx:F.sx*C,sy:F.sy*C}),ae.setRest(O,_.name,N);let k=re&&D.caps?D.caps.map(e=>({...e,x:le.x+e.x*C,y:le.y+e.y*C,w:e.w*C,h:e.h*C})):te;de(T.U,k,{h,dpr:g}),T.U.uPx.value=g,P.hero.top=M.y,P.hero.len=Math.max(M.h,1);let se=a(ne),ce=ne?.querySelector(`.passage__frame`)?.offsetHeight??h;P.passage.top=se?se.y:M.y+M.h,P.passage.len=se?Math.max(se.h-(getComputedStyle(ne.querySelector(`.passage__frame`)??ne).position===`sticky`?ce:0),1):h*1.8,L=ae.solve(O,{heroH:P.passage.top,approach:Le.approach[1]*P.passage.len,rects:u(ee),shapes:ht,view:N,top:parseFloat(getComputedStyle(y).getPropertyValue(innerWidth>=1024?`--header-h-compact`:`--header-h`))||0}),b===`frame`&&p(()=>import(`./frame-Cy9oWa7v.js`).then(e=>e.o).then(e=>e.drawFrameOverlay(F,le,N,C)),[]),ye=_.name,je(),Pe(),I=!0},je=()=>{let e=E?.querySelector(`.bays__frame`),t=E?.querySelector(`.bays__panel`);if(R=!!(e&&t&&y.classList.contains(`stages`)&&E.classList.contains(`is-stage`)),!R)return;let n=a(E.querySelector(`[data-stage-track]`)??E),r=e.getBoundingClientRect(),i=t.getBoundingClientRect(),o=r.height||N.h,s=E.nextElementSibling,c=s?a(s).y:n.y+n.h+N.h;Object.assign(P.corridor,{top:n.y,len:Math.max(n.h-o,1),pre:o,next:c});let l=parseFloat(getComputedStyle(y).getPropertyValue(innerWidth>=1024?`--header-h-compact`:`--header-h`))||0,u=i.left-r.left>r.width*.3,d=u?{x0:0,x1:i.left-r.left,y0:l,y1:o}:{x0:0,x1:r.width,y0:l,y1:i.top-r.top};oe.setLayout({bucket:ye,view:N,name:u?`side`:`below`,corridor:xe,free:d})},Me=!1,Pe=()=>{let e=D?.querySelector(`[data-stage-track]`),t=D?.querySelector(`.shutters__frame`);if(Me=!!(e&&t&&getComputedStyle(t).position===`sticky`),!Me)return;let n=a(D),r=a(e),i=t.offsetHeight||N.h;Object.assign(P.shutters,{from:n.y-N.h,top:r.y,len:Math.max(r.h-i,1)})},Ie=e=>Me&&e>=P.shutters.from,z={on:!1,v:0},ze={peak:0,held:0},Be=new w,B=new k,We=new w,Ge=new w,Je=new w,Ye=It.clone(),Qe={pos:[0,0,0],look:[0,0,0],fov:0},$e=-1,et=-1,tt=null,nt=matchMedia(`(pointer: fine)`).matches,rt={x:0,v:0},it={x:0,v:0},at={x:0,y:0},ot={az:0,el:0},V={level:1,from:1,to:1,start:0,dur:0,next:0},H=0,st=0,ct=T.U.uWind.value,lt=e=>e+Lt.min+Math.random()*(Lt.max-Lt.min),ut=(e,t,n)=>Object.assign(V,{from:V.level,to:t,start:e,dur:n}),dt=e=>{if(nt)return!1;if(V.next||=e+Lt.first[0]+Math.random()*(Lt.first[1]-Lt.first[0]),V.dur){let t=Math.min((e-V.start)/V.dur,1);return V.level=V.from+(V.to-V.from)*Ut(t),t>=1&&(V.dur=0,V.to<1?ut(e,1,Lt.up):V.next=lt(e)),!0}return e>=V.next&&(ut(e,Lt.dip,Lt.down),!0)},U={t:te.room,nudge:ot,intensity:1,rims:1},W={hero:0,passage:0,y:0,approach:0,stage:0,out:0},ft={s:0,lift:0},pt=-1,G=!0,K=!1,mt=!1,gt=-1,vt=0,yt=1,bt=e=>{let t=scrollY;pt=t,kt(t,P,W);let n=W.passage,r=ie(n),i=se(n);K=Ie(t),G=!K&&i===`room`;let a=null,o=O,s=[],c=null,l=null;if(K){let e=P.shutters,n=$((t-e.from)/Math.max(e.top-e.from,1)),i=$((t-e.top)/e.len);if(W.s3=i,!mt&&tt==null&&(z.on=i>=Ce.flood,z.v=+!!z.on),r=ge(i),j&&r<1){let e=_(n,i);n+i!==$e&&(j.pose(Se(i),e),$e=n+i);let t=Ke(i);Math.abs(t-et)>1e-4&&(j.setSun(t),et=t),ve(z.v,ze);let r=He(i,ze),l=qe(i,e);j.setExposure(r,l,Math.max(e/Ce.slatDeg,Kt(i))),j.shafts.uniforms.uDensity.value=Vt*(1+.6*Kt(i)+.4*ze.peak),j.shafts.uniforms.uExposure.value=r,j.motes.uniforms.uBurst.value=ze.peak,j.U.uMotesA.value=.55+.45*l,j.U.uTime.value=st,j.U.uStir.value=H,j.sway(st);let u=j.camera;he(n,i,ye,re,Qe),u.position.set(...Qe.pos),u.fov=Qe.fov,u.aspect=N.w/N.h,u.lookAt(Qe.look[0],Qe.look[1],Qe.look[2]),u.updateMatrixWorld(),u.updateProjectionMatrix(),j.project(u,B),Be.set(B.x*.5+.5,B.y*.5+.5,Ht.radius,Ht.held*ze.held+Ht.peak*ze.peak),c=Be,a=j.scene,o=u,s=j.passes}else j||(r=1)}else if(G){ae.at(t,ft);let e=ae.room(O,ft.s,ft.lift);Ee(rt.x,it.x,e),ot.az=-rt.x*C.az*e,ot.el=it.x*C.el*e,U.t=W.passage>0?Gt(Rt.heroOut,Rt.hole,$(n/Le.approach[1])):Gt(Rt.rest,Rt.heroOut,W.hero),U.intensity=U.rims=Gt(1,V.level,e),T.setSun(U);let r=$((ft.s-1.3)/.7);T.U.uHaloA.value=Gt(Bt.haloA[0],Bt.haloA[1],r),T.U.uHaloW.value=Gt(Bt.haloW[0],Bt.haloW[1],r*r),T.shafts.uniforms.uDensity.value=Gt(Bt.density[0],Bt.density[1],r),T.U.uCapShift.value.set(0,t*S.size.dpr),T.U.uTime.value=st;let i=Math.min(st/Pt,1);T.U.uLeafAmt.value=i*i*(3-2*i),T.U.uStir.value=H,a=T.scene,s=[T.shafts]}else if(A){let e=$((n-Le.exit[0])/(Le.exit[1]-Le.exit[0])),i=A.specs.length,c=P.corridor;W.approach=R?$((t-(c.top-c.pre))/c.pre):0,W.stage=R?$((t-c.top)/c.len):0;let u=De(c.top+c.len,c.next,N.w,N.h),d=c.top+Ne.release*c.len;W.out=R?$((t-d)/(u.y0+u.len-d)):0;let f=R?$((t-u.y0)/u.len):0;R&&e>=1?oe.pose(A.camera,W.approach,W.stage,i,W.out,ce(W.stage),Math.max(t-u.y0,0)):ae.exit(A.camera,e),f>0?(r=Ut(f),l=Ft,Ge.set(1,Re.feather,$(1-(c.next-t-28)/N.h),0)):Ge.x=0,R&&Ae(c.next-t,N.w,N.h,Je)&&(l=Ft);for(let e=0;e<i;e++)A.setDoor(e,R?Ve(e,W.stage,i):96);Y||(vt=x>=2?st+t/300:t/300),A.animate(vt),A.sway(vt),yt=r;let p=Gt(Ne.sun[0],Ne.sun[1],W.stage);Math.abs(p-gt)>1e-4&&(A.setSun(p),gt=p),a=A.scene,o=A.camera,s=[A.shafts]}mt=K;let u=.3*(t-P.passage.top)/N.h;if(We.set(!K&&n>0&&n<1?r:0,.25*Math.sin(u*2.1),-11*u,0),Z?.begin(),a)S.frame(a,o,s,r,c,We,l,!K&&!G?Ge:null,!K&&!G?Je:null);else if(K)S.frame(T.scene,O,[],1,null,null);else{let e=0,n=!1;if(R&&!G&&P.corridor.next>0){let r=P.corridor,i=De(r.top+r.len,r.next,N.w,N.h);e=Ut($((t-i.y0)/i.len)),n=Ae(r.next-t,N.w,N.h,Je)}Ye.copy(It).lerp(Ft,e),S.frame(T.scene,O,[],1,null,We.setX(1-e),Ye,null,n?Je:null,Ft)}Z?.end(e)},q=0,xt=0,St=0,J=!0,Ct=!document.hidden,Y=!!m?.state.paused,wt=!1,X=!1,Tt=!1,Z=null,Et=!1,Dt=()=>Tt&&J&&Ct&&!wt&&!X,Q=()=>{!q&&Dt()&&(q=requestAnimationFrame(jt))},At=()=>navigator.scheduling?.isInputPending?.()??!1;function jt(e){if(q=0,!Dt())return;if(At()){q=requestAnimationFrame(jt);return}let t=xt?Math.min((e-xt)/1e3,.1):1/60;xt=e;let n=I||scrollY!==pt,r=Ie(scrollY)&&j&&ge(W.s3??0)<1,i=!G&&!K&&A&&x>=2&&ie(W.passage)<1&&yt<.999&&(rn.get(E)||rn.get(ne)),a=!Y&&!Et&&(G&&W.y<=1||r||i);if(Ie(scrollY)){let e=P.shutters;tt==null&&Fe(z,$((scrollY-e.top)/e.len),t,Y)&&(n=!0)}if(!Y){nt&&(n=Te(rt,at.x,t,Nt)|Te(it,at.y,t,Nt)||n),a&&G&&(n=dt(e)||n);let i=r?j.U.uWind.value:G?ct:null;i&&Math.abs(H)>.001&&(i.y+=H*t*.12,i.x-=H*t*.05,(r?j:T).motes.wrap(i),n=!0),st+=t}let o=!Y&&Z?.measuring;(n||o||a&&e-St>=33&&!S.busy())&&(bt(e),St=e,I=!1),(!Y&&a||o||I||n)&&(q=requestAnimationFrame(jt))}let qt=e=>{e.pointerType!==`mouse`||Y||(at.x=Math.max(-1,Math.min(1,e.clientX/innerWidth*2-1)),at.y=Math.max(-1,Math.min(1,1-e.clientY/innerHeight*2)),Q())},Jt=()=>{at.x=at.y=0,Q()},Yt=e=>{let t=e.detail?.value??0;H=Y?0:t,H&&Q()},Xt=()=>Q(),Zt=new MutationObserver(()=>{Et=!!document.querySelector(`dialog[open]`),I=!0,Q()});document.querySelectorAll(`dialog`).forEach(e=>Zt.observe(e,{attributes:!0,attributeFilter:[`open`]}));let Qt=()=>{Ct=!document.hidden,xt=0,Z?.gap(),Q()},$t=0,en=()=>{cancelAnimationFrame($t),$t=requestAnimationFrame(()=>{Oe(),Q()})};nt&&addEventListener(`pointermove`,qt,{passive:!0}),nt&&document.documentElement.addEventListener(`mouseleave`,Jt),m?.addEventListener(`velocity`,Yt),addEventListener(`scroll`,Xt,{passive:!0}),document.addEventListener(`visibilitychange`,Qt),addEventListener(`resize`,en);let tn=new ResizeObserver(en);tn.observe(ee??t),ne&&tn.observe(ne),E&&tn.observe(E),D&&tn.observe(D);let nn=E?new MutationObserver(()=>{je(),I=!0,Q()}):null;nn?.observe(E,{attributes:!0,attributeFilter:[`class`]});let rn=new Map,an=new IntersectionObserver(e=>{for(let t of e)rn.set(t.target,t.isIntersecting&&(t.target!==D||Me));let n=J;J=[...rn.values()].some(Boolean),J&&!n&&Dt()&&(bt(performance.now()),I=!1),t.style.visibility=J?``:`hidden`,J||Z?.gap(),xt=0,Q()},{rootMargin:`10% 0px`});document.querySelectorAll(`[data-world]`).forEach(e=>an.observe(e));let on=0,sn=0,cn=e=>{if(e.preventDefault(),wt=!0,cancelAnimationFrame(q),q=0,y.classList.remove(`world-live`),++on>1)return dn(`context lost twice`);sn=setTimeout(()=>dn(`context not restored`),5e3)},ln=async()=>{clearTimeout(sn),Z?.dispose(),Z=null,wt=!1,Oe(),bt(performance.now()),await Wt(),!wt&&!X&&(y.classList.add(`world-live`),Q())};t.addEventListener(`webglcontextlost`,cn),t.addEventListener(`webglcontextrestored`,ln);let un=({state:e,p75:t,final:n,source:r})=>{console.info(`[world] gate p75 ${t.toFixed(1)} ms (${r}) → ${e}${n?` (final)`:``}`);let i=()=>Promise.all([T,A,j].filter(Boolean).map(e=>e.compile())).then(()=>{I=!0,Q()});if(e===`t1`)x=1,S.setTier(1),T.setTier(1),A?.setTier(1),j?.setTier(1),Oe(),i();else if(e===`lite`){for(let e of[T,A,j])e&&(e.shafts.enabled=!1);S.setTier(1,{lite:!0}),Oe()}else e===`off`&&dn(`frame time over the Tier 1 limit`)};function dn(e){X||(console.info(`[world] back to stills: ${e}`),pn.dispose())}let fn=null,pn={setPaused(e){Y=!!e,rt.v=it.v=0,H=0,xt=0,Z?.gap(),I=!0,Y||fn?.(),Q()},dispose(){X||(X=!0,fn?.(),cancelAnimationFrame(q),cancelAnimationFrame($t),clearTimeout(sn),removeEventListener(`pointermove`,qt),document.documentElement.removeEventListener(`mouseleave`,Jt),m?.removeEventListener(`velocity`,Yt),m?.removeEventListener(`pause`,mn),removeEventListener(`scroll`,Xt),document.removeEventListener(`visibilitychange`,Qt),removeEventListener(`resize`,en),tn.disconnect(),an.disconnect(),nn?.disconnect(),Zt.disconnect(),t.removeEventListener(`webglcontextlost`,cn),t.removeEventListener(`webglcontextrestored`,ln),y.classList.remove(`world-live`),t.style.visibility=``,document.getElementById(`world-frame-debug`)?.remove(),Z?.dispose(),A?.dispose(),j?.dispose(),D?.classList.remove(`is-live`),T.dispose(),S.dispose(),Ze(m))}},mn=e=>pn.setPaused(e.detail.paused);if(m?.addEventListener(`pause`,mn),await document.fonts?.ready,Oe(),await T.compile(),await S.compile(),m&&m.state?.sunIn!==`done`&&await new Promise(e=>{let t=setTimeout(e,4e3);m.addEventListener(`sun-in-end`,()=>{clearTimeout(t),e()},{once:!0})}),Y&&!X&&await new Promise(e=>fn=()=>{fn=null,e()}),X)return pn;Oe(),bt(performance.now());let hn=S.renderer.info.render;if(console.info(`[world] room · tier ${x} (${h.reason}; ${h.gpu}) · ${hn.calls} draw calls · ${hn.triangles} triangles · dpr ${S.size.dpr} · canvas ${Math.round(N.w*S.size.dpr)}×${Math.round(N.h*S.size.dpr)} · ${S.msaa?`4x MSAA`:`no MSAA`} · ~${S.memoryMB} MB · shafts ${T.shafts.size.join(`×`)} · motes ${T.motes.count} · parallax ${_e}${F.ok?F.still?` · framed as the still`:` · framed live (the still would touch text)`:` · text-safe framing not found`} · flight held ${L.held}/${L.rows}, carried ${L.carried}`),await Wt(),X||wt)return pn;y.classList.add(`world-live`),Tt=!0,v??(Z=Xe(S.renderer.getContext(),{tier:x,onStep:un})),Q(),b&&p(()=>import(`./debug-D4ibxs8k.js`).then(e=>e.attach({gl:S,room:T,corridor:()=>A,tier:()=>x,frame:()=>F,solved:()=>L,at:W,path:ft,span:P,inRoom:()=>G,walking:()=>R,walk:oe,flight:ae,section:ee,parallax:()=>_e,shutters:()=>({staged:Me,built:!!j,at:W.s3??null,flood:{...z},span:{...P.shutters}}),hold:e=>{tt=e,e!=null&&Object.assign(z,{on:!0,v:e}),I=!0,Q()},redraw:()=>bt(performance.now()),paused:()=>Y,running:Dt,pointer:(e,t)=>{at.x=rt.x=e,at.y=it.x=t,rt.v=it.v=0,I=!0,Q()}})),__vite__mapDeps([0,1]));let gn=()=>new Promise(e=>`requestIdleCallback`in window?requestIdleCallback(e,{timeout:1500}):setTimeout(e,50));return gn().then(async()=>{if(X)return;let{buildCorridor:e}=await p(async()=>{let{buildCorridor:e}=await import(`./corridor-Slq4L6l2.js`);return{buildCorridor:e}},__vite__mapDeps([2,3,4,5,6]));if(X)return;let t=0,n=performance.now(),r=await e({gl:S,tier:x,beats:be,textures:T.textures,traces:!!E?.hasAttribute(`data-traces`),onSlice:e=>{t=Math.max(t,e)}}),i=performance.now()-n;if(r.setTier(x),r.shafts.setSize(Math.round(N.w*S.size.dpr),Math.round(N.h*S.size.dpr)),await r.compile(),X)return r.dispose();A=r,I=!0,Q(),console.info(`[world] corridor · ${be.length} doors (${be.map(e=>`${e.unit} ${e.door}`).join(`, `)}) · gạch bông baked ${r.bakedInWorker?`in a worker`:`on the main thread`} in ${Math.round(i)} ms, longest main-thread slice ${t.toFixed(1)} ms`)}).catch(e=>console.warn(`[world] corridor unavailable, the wash holds`,e)).then(()=>D&&!X?gn():null).then(async()=>{if(!D||X)return;let{buildShutters:e}=await p(async()=>{let{buildShutters:e}=await import(`./shutters-1aebQVtV.js`);return{buildShutters:e}},__vite__mapDeps([7,5,6]));if(X)return;let t=await e({gl:S,tier:x,textures:T.textures});if(t.shafts.setSize(Math.round(N.w*S.size.dpr),Math.round(N.h*S.size.dpr)),t.U.uPx.value=S.size.dpr,await t.compile(),X)return t.dispose();j=t,D.classList.add(`is-live`),I=!0,Q(),console.info(`[world] shutters ready`)}).catch(e=>console.warn(`[world] shutters unavailable, the stills stay`,e)),pn}export{qt as live,et as n,it as t};