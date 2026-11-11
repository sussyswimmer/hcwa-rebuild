const __vite__mapDeps=(i,m=__vite__mapDeps,d=(m.f||(m.f=["assets/frame-CQsXUThy.js","assets/rolldown-runtime-DK3Fl9T5.js","assets/debug-B29ph466.js","assets/corridor-anEKWKKE.js","assets/main-BK1AJWoq.js","assets/main-C99b77tU.css","assets/three-Dgmw-8ZC.js","assets/wall-poster--HyE4DWl.js","assets/common-BsVAAssA.js","assets/shutters-BczKilyt.js"])))=>i.map(i=>d[i]);
import{n as e,r as t,t as n}from"./main-BK1AJWoq.js";import{A as r,C as i,F as a,O as o,P as s,S as c,_ as l,b as u,c as d,f,g as p,h as m,i as h,k as g,l as _,u as v,w as y,x as b}from"./three-Dgmw-8ZC.js";import{a as x,c as S,d as C,f as w,i as ee,l as T,n as te,r as ne,s as re,t as ie,u as ae}from"./frame-CQsXUThy.js";import{t as oe,worldOff as se}from"./world-xAUZNAzn.js";import{$ as ce,A as le,B as ue,C as de,Ct as E,D as fe,Dt as D,E as O,F as pe,G as k,H as me,I as he,J as ge,K as _e,L as ve,M as ye,Mt as A,N as be,Nt as xe,O as Se,Ot as Ce,P as we,Pt as j,R as Te,S as Ee,St as M,T as De,Tt as Oe,U as ke,V as N,W as Ae,X as je,Y as Me,Z as Ne,_ as Pe,_t as Fe,at as Ie,b as P,bt as F,c as Le,ct as Re,dt as ze,et as Be,ft as Ve,g as He,gt as Ue,ht as We,it as Ge,j as Ke,jt as I,k as qe,kt as L,lt as Je,mt as Ye,n as Xe,nt as Ze,o as Qe,ot as $e,q as R,r as et,rt as tt,st as nt,t as rt,v as it,vt as at,w as ot,x as st,xt as ct,y as lt,yt as ut,z as dt}from"./wall-poster--HyE4DWl.js";function ft(e){return new g({uniforms:e,vertexShader:`
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
      ${ct}
      ${at}
      ${L}
      ${Ce}
      ${We}
      ${Re([`Pl`])}
      float occ(float d, float r, float s){ return d < 0.003 ? 1. : 1. - s * (1. - smoothstep(0., r, d)); }
      void main(){
        int k = int(vKind + 0.5);           // 0 lime wall, 1 terrazzo floor, 2 ceiling
        vec3 N = normalize(vN);
        vec2 uv = abs(N.z) > .5 ? vP.xy : abs(N.x) > .5 ? vP.zy : vP.xz;
        vec4 lw = texture2D(uLime, uv * 0.25);
        float vertical = 1. - step(.5, abs(N.y));
        float mott = (lw.r - .5) * .09 + (lw.g - .5) * .05 + (lw.b - .5) * .04 * vertical;
        vec3 rel = k == 1 ? texture2D(uTerr, vP.xz * 0.5).rgb / ${Je.toFixed(3)} * (1. + mott * .5) : vec3(1. + mott);
        vec3 Nb = N;
        if (k != 1 && uWallK > 0.) {
          vec2 wuv = uv / ${tt.wall.toFixed(1)};
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
      }`,toneMapped:!1})}var pt={1:160,2:420};function mt(e,{min:t,max:n},{mask:r=null}={}){let a=pt[2],o=new Float32Array(a*4),c=7,l=()=>(c=c*16807%2147483647)/2147483647;for(let e=0;e<o.length;e++)o[e]=l();let u=new h;u.setAttribute(`position`,new v(new Float32Array(a*3),3)),u.setAttribute(`aSeed`,new v(o,4));let d=new s(...t),f=new s(...n).sub(d),p=new g({uniforms:{...e,uBoxMin:{value:d},uBoxSize:{value:f},uBurst:{value:0}},vertexShader:`
      attribute vec4 aSeed;
      uniform vec3 uBoxMin, uBoxSize, uWind;
      uniform float uTime, uPx, uMotesA, uStir, uSunI, uBurst;
      varying float vA;
      ${ct}
      ${L}
      ${r?r.glsl:at+Ce}
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
      void main(){ vec2 d = gl_PointCoord - .5; gl_FragColor = vec4(uMoteCol * vA * exp(-dot(d, d) * 18.), 1.); }`,blending:2,transparent:!0,depthWrite:!1,toneMapped:!1}),m=new i(u,p);return m.frustumCulled=!1,m.renderOrder=5,{points:m,uniforms:p.uniforms,setTier(e){u.setDrawRange(0,pt[e>=2?2:1])},get count(){return u.drawRange.count},wrap(e){e.set((e.x%f.x+f.x)%f.x,(e.y%f.y+f.y)%f.y,(e.z%f.z+f.z)%f.z)},dispose(){u.dispose(),p.dispose()}}}var ht=`
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
`,gt=()=>({uSkyLow:{value:A(`sky`)},uSkyTop:{value:I(A(`sky`),A(`shade-100`),.35)},uCanopy:{value:I(A(`sky`),A(`unit-green-bamboo`),.2)},uSunGlow:{value:A(`on-dusk`)},uStreetX:{value:9}});function z(e,{x:t,z:n,size:r=[40,20]}){let i=new g({uniforms:e,vertexShader:`varying vec3 vP; void main(){ vec4 w = modelMatrix * vec4(position, 1.); vP = w.xyz; gl_Position = projectionMatrix * viewMatrix * w; }`,fragmentShader:`
      uniform vec3 uL;
      varying vec3 vP;
      ${L}
      ${D}
      ${ht}
      void main(){
        gl_FragColor = vec4(street(cameraPosition, normalize(vP - cameraPosition)), 1.);
        #include <colorspace_fragment>
        DITHER_OUT;
      }`,toneMapped:!1}),a=new l(new c(...r),i);return a.rotation.y=-Math.PI/2,a.position.set(t,r[1]/2-2,n),a}function _t({parapet:e=1.8,canopy:t=2.6,trunk:n=[-1.2,-1]}={}){let r=I(A(`sun-200`),A(`sun-100`),.15),i=A(`sun-100`),a=I(A(`sun-200`),A(`sun-400`),.45),o=I(I(A(`lime-300`),A(`sun-200`),.35),A(`gold-700`),.1);return new g({uniforms:{uLow:{value:r},uHigh:{value:i},uLeaf:{value:a},uWall:{value:o},uHole:{value:i.clone()},uY:{value:[e,t]},uTrunk:{value:n},uSway:{value:0}},vertexShader:`varying vec3 vP; void main(){ vec4 w = modelMatrix * vec4(position, 1.); vP = w.xyz; gl_Position = projectionMatrix * viewMatrix * w; }`,fragmentShader:`
      varying vec3 vP;
      uniform vec3 uLow, uHigh, uLeaf, uWall, uHole; uniform float uY[2], uTrunk[2], uSway;
      ${L}
      ${ct}
      ${D}
      #define B ${F.toFixed(4)}
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
      #undef B`,toneMapped:!1})}var B=`
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
    vec4 w = wallTap(uWCo, P.zy / ${tt.block.toFixed(1)} + h22(id), .5);
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
`,V=`varying vec3 vP; varying vec3 vN;
void main(){
  mat4 m = modelMatrix;
  #ifdef USE_INSTANCING
  m = m * instanceMatrix;
  #endif
  vec4 w = m * vec4(position, 1.);
  vP = w.xyz; vN = normalize(mat3(m) * normal);
  gl_Position = projectionMatrix * viewMatrix * w;
}`,vt=`
varying vec3 vP; varying vec3 vN;
${ct}
${at}
${L}
${D}
${Ce}
${ht}
${Re([`Co`])}
${B}
`;function yt(e,t){let n=new g({uniforms:e,vertexShader:V,fragmentShader:`
      ${vt}
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
      }`,toneMapped:!1}),r=new c(t.z1-t.z0,t.y1-t.y0),i=new l(r,n);return i.rotation.y=-Math.PI/2,i.position.set(t.sx,(t.y0+t.y1)/2,(t.z0+t.z1)/2),i}function bt(){let e=F/2,{r:t,pipAt:n,pipR:i}=E,a=new r().moveTo(-e,-e).lineTo(e,-e).lineTo(e,e).lineTo(-e,e).closePath(),o=new u;for(let e=0;e<40;e++){let n=-e/40*Math.PI*2,r=2*t*Math.max(Math.cos(n),Math.cos(n-Math.PI/2),Math.cos(n-Math.PI),Math.cos(n+Math.PI/2));e?o.lineTo(r*Math.cos(n),r*Math.sin(n)):o.moveTo(r*Math.cos(n),r*Math.sin(n))}o.closePath(),a.holes.push(o);for(let[e,t]of[[1,1],[-1,1],[-1,-1],[1,-1]])a.holes.push(new u().absarc(e*n,t*n,i,0,Math.PI*2,!0));let s=new _(a,{depth:M,bevelEnabled:!1,curveSegments:8}).rotateY(Math.PI/2),c=s.getAttribute(`normal`),l=[];for(let e=0;e<c.count;e+=3)c.getX(e)<.5&&l.push(e,e+1,e+2);return s.setIndex(l),s}function xt(e,t){let n=new g({uniforms:e,vertexShader:V,fragmentShader:`
      ${vt}
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
      }`,toneMapped:!1}),r=Math.round((t.z1-t.z0)/F),i=Math.round((t.y1-t.y0)/F),a=new f(bt(),n,r*i),o=new p,s=0;for(let e=0;e<r;e++)for(let n=0;n<i;n++)a.setMatrixAt(s++,o.makeTranslation(t.sx,t.y0+(n+.5)*F,t.z0+(e+.5)*F));return a.instanceMatrix.needsUpdate=!0,a.computeBoundingSphere(),a}var St=(e,t,n)=>n>=2?xt(e,t):yt(e,t);function Ct(e,t,n){let r=F/2;return yt(e,{sx:t.sx,z0:n.z-r,z1:n.z+r,y0:n.y-r,y1:n.y+r})}function H(e,t){let n=Math.round((e.y1-e.y0)/F);return Math.floor((t.z-e.z0)/F)*n+Math.floor((t.y-e.y0)/F)}var U={min:[-6,0,-4],max:[2.6,3.3,4]},W={sx:2.6,z0:-4,z1:1.4,y0:.9,y1:3.3},wt=new s(2.6,1.05,-1.15),Tt={portrait:new s(2.6,1.05,-3.25)},G=e=>Tt[e]??wt,K={min:[-3.5,.05,-3.9],max:[2.5,3.2,2]},Et={portrait:{pos:[.83,1.1,-2.93],look:[1.68,.91,-3.43],fov:49.6},tablet:{pos:[-1.3,1.1,3.15],look:[1.2,1.95,-3.6],fov:54},landscape:{pos:[-1.5,1.1,3.3],look:[.5,1.78,-4],fov:46},wide:{pos:[-1.5,1.1,3.3],look:[.5,1.78,-4],fov:44}},Dt={silhouette:(e=>[new s(e.sx,e.y0,e.z0),new s(e.sx,e.y1,e.z0),new s(e.sx,e.y1,e.z1),new s(e.sx,e.y0,e.z1)])(W),floor:[new s(U.min[0],0,U.min[2]),new s(U.max[0],0,U.min[2]),new s(U.max[0],0,U.max[2]),new s(U.min[0],0,U.max[2])],heroBlock:wt};function Ot(){let[e,t,n]=U.min,[r,i,a]=U.max,o=(e+r)/2,s=r-e,l=a-n,u=i-t,f=[[s,u,[0,0,0],[o,u/2,n],0],[l,u,[0,Math.PI/2,0],[e,u/2,(n+a)/2],0],[s,l,[-Math.PI/2,0,0],[o,t,(n+a)/2],1],[s,l,[Math.PI/2,0,0],[o,i,(n+a)/2],2],[W.z1-W.z0,W.y0,[0,-Math.PI/2,0],[r,W.y0/2,(W.z0+W.z1)/2],0],[a-W.z1,u,[0,-Math.PI/2,0],[r,u/2,(W.z1+a)/2],0],[s,u,[0,Math.PI,0],[o,u/2,a],0]],m=[],g=[],_=[],y=[],b=new p;for(let[e,t,n,r,i]of f){let a=new c(e,t);b.makeRotationFromEuler(new d(...n)).setPosition(...r),a.applyMatrix4(b);let o=m.length/3;m.push(...a.getAttribute(`position`).array),g.push(...a.getAttribute(`normal`).array);for(let e=0;e<a.getAttribute(`position`).count;e++)_.push(i);y.push(...Array.from(a.index.array,e=>e+o)),a.dispose()}let x=new h;return x.setAttribute(`position`,new v(m,3)),x.setAttribute(`normal`,new v(g,3)),x.setAttribute(`aKind`,new v(_,1)),x.setIndex(y),x}async function kt({gl:e,tier:t,slice:n=!0,shaftSteps:r=null}){let[i,a]=await Promise.all([ze({slice:n}),Ve({slice:n})]),c=ce();Be(`room`,Ne.room,c);let u=[A(`wallShade`),A(`floorShade`),A(`ceiling`)].map(xe),d=[A(`wallSun`),A(`wallSun`).multiplyScalar(.8),A(`wallSun`)].map(xe),f=A(`sunRoom`),m=Math.max(c.dir.z,.05),h=c.dir,g={...ut(W,h),...Ue(),...gt(),...nt(),uShade:{value:u},uSunT:{value:d.map(e=>e.clone())},uNdlRef:{value:m},uGlare:{value:xe(A(`sky`))},uSunCol:{value:new s},uSunI:{value:1},uRimI:{value:1},uHaloA:{value:.35},uHaloW:{value:.012},uBoxMin:{value:new s(...U.min)},uBoxMax:{value:new s(...U.max)},uLime:{value:i},uTerr:{value:a},uFace:{value:A(`screenFace`)},uGlow:{value:A(`sky`)},uRim:{value:I(A(`wallSun`),A(`sky`),.3)},uWall:{value:I(A(`screenFace`),A(`sky`),.38)},uTime:{value:0},uLeafAmt:{value:0},uWind:{value:new s},uStir:{value:0},uMotesA:{value:1},uPx:{value:1},uMoteCol:{value:xe(I(A(`sky`),A(`sun-200`),.6).convertLinearToSRGB())}},_=new o,v=new l(Ot(),ft(g)),y=St(g,W,1),x=z(g,{x:9,z:-1}),S=mt(g,K);_.add(v,y,x,S.points);let C=Ze(e.renderer,g,{box:U,tier:t,ortho:e.ortho,steps:r}),w=new b(46,1,.05,60),ee=[],T=e=>{if(e>=2!=!!y.isInstancedMesh){_.remove(y),y.geometry.dispose(),y.material.dispose(),y=St(g,W,e),_.add(y);for(let e of ee)_.remove(e),e.geometry.dispose(),e.material.dispose();if(ee=[],y.isInstancedMesh){for(let e of[wt,...Object.values(Tt)])y.setMatrixAt(H(W,e),new p().makeScale(0,0,0)),ee.push(Ct(g,W,e));y.instanceMatrix.needsUpdate=!0,_.add(...ee)}}x.visible=e>=2,S.setTier(e),C.setTier(e)};T(t);let te=({t:e=Ne.room,nudge:t=null,intensity:n=1,rims:r=n}={})=>{Be(`room`,e,c,t);let i=c.color.r/f.r,a=c.color.g/f.g,o=c.color.b/f.b;for(let e=0;e<3;e++)g.uSunT.value[e].set(d[e].x*i,d[e].y*a,d[e].z*o);g.uSunCol.value.set(c.color.r,c.color.g,c.color.b),g.uSunI.value=n,g.uRimI.value=r};return te(),{scene:_,camera:w,U:g,shafts:C,motes:S,setTier:T,setSun:te,setShot:(e,t,n,r=Et[e.name])=>{w.position.set(...r.pos),w.fov=r.fov,w.aspect=t/n,w.lookAt(...r.look),w.clearViewOffset(),w.updateProjectionMatrix(),w.updateMatrixWorld()},textures:{lime:i,terr:a},compile:async()=>{await e.renderer.compileAsync(_,w);for(let t of C.scenes)await e.renderer.compileAsync(t,e.ortho)},dispose(){_.traverse(e=>{(e.isMesh||e.isPoints)&&(e.geometry.dispose(),e.material.dispose())}),C.dispose(),i.dispose(),a.dispose()}}}var At={portrait:{pos:[1.52,1.1,-3.05],fov:56},tablet:{pos:[.3,1.1,.4],fov:50},landscape:{pos:[.2,1.1,.6],fov:42},wide:{pos:[.2,1.1,.6],fov:40}},jt={portrait:{dist:.5,fov:56},tablet:{dist:.42,fov:50},landscape:{dist:.35,fov:42},wide:{dist:.35,fov:40}},Mt={portrait:[20,8],tablet:[28,14]},Nt=e=>Mt[e]??[32,R.yaw],Pt={portrait:2.2},Ft=e=>Pt[e]??.8,It=6,q=(e,t,n)=>{let r=m.clamp((n-e)/(t-e),0,1);return r*r*(3-2*r)},Lt=e=>.5-.5*Math.cos(Math.PI*m.clamp(e,0,1)),J=e=>Math.sin(m.clamp(e,0,1)*Math.PI/2);function Rt(e){let t=Math.tan(m.degToRad(e.fov/2))/e.zoom,n=2*t,r=e.aspect*n,i=-r/2,a=t,o=e.view;return o?.enabled&&(i+=o.offsetX*r/o.fullWidth,a-=o.offsetY*n/o.fullHeight,r*=o.width/o.fullWidth,n*=o.height/o.fullHeight),{l:i,r:i+r,t:a,b:a-n}}var Y=(e,t,n)=>{let r=Math.tan(m.degToRad(e/2));return Object.assign(n,{l:-r*t,r:r*t,t:r,b:-r})};function zt(e,t,n,r){let i=e.near,a=n*(t.t-t.b)/r;e.projectionMatrix.makePerspective(t.l*i,t.r*i,(t.t-a)*i,(t.b-a)*i,i,e.far),e.projectionMatrixInverse.copy(e.projectionMatrix).invert()}var X=(e,t)=>{let n=t.clone().sub(e).normalize();return[Math.atan2(-n.x,-n.z),Math.asin(m.clamp(n.y,-1,1))]},Bt=(e,t,n)=>{let r=t-e;for(;r>Math.PI;)r-=2*Math.PI;for(;r<-Math.PI;)r+=2*Math.PI;return e+r*n};function Vt(e,t,n,r,i,a){let o=i*i,s=o*i;for(let c of[`x`,`y`,`z`])a[c]=.5*(2*t[c]+(n[c]-e[c])*i+(2*e[c]-5*t[c]+4*n[c]-r[c])*o+(3*t[c]-e[c]-3*n[c]+r[c])*s);return a}function Ht(){let e={pos:new s,yaw:0,pitch:0,fov:46,bounds:{l:-1,r:1,t:1,b:-1}},t=new s,n=new s,r=`landscape`,i=1,a=1,o=G(`landscape`),c=0,l=0,u=new s,f=new d(0,0,0,`YXZ`),p=new y,h={l:0,r:0,t:0,b:0},g={l:0,r:0,t:0,b:0},_=new s,v=(u,d,f)=>{r=d,i=f.w/f.h,a=f.h,e.pos.copy(u.position);let p=new s(0,0,-1).applyQuaternion(u.quaternion);e.yaw=Math.atan2(-p.x,-p.z),e.pitch=Math.asin(m.clamp(p.y,-1,1)),e.fov=u.fov,Object.assign(e.bounds,Rt(u)),o=G(r),t.set(...At[r].pos),_.copy(t).sub(o).setY(0).normalize().lerp(new s(-1,0,0),.35).normalize(),n.copy(o).addScaledVector(_,jt[r].dist).setY(1.1),[c,l]=X(t,o)},b=(s,d,_=0)=>{d<=1?Vt(e.pos,e.pos,t,n,d,u):Vt(e.pos,t,n,n,d-1,u),s.position.copy(u);let v,y,b;if(d<=1){let t=Lt(d),[n,i]=X(u,o),a=q(.6,1,d);v=Bt(e.yaw,Bt(c,n,a),t),y=m.lerp(e.pitch,m.lerp(l,i,a),q(.45,1,d)),b=m.lerp(e.fov,At[r].fov,t)}else[v,y]=X(u,o),b=m.lerp(At[r].fov,jt[r].fov,d-1);s.quaternion.copy(p.setFromEuler(f.set(y,v,0))),s.fov=b,s.updateMatrixWorld();let x=q(0,.7,d);Y(b,i,g);for(let t of[`l`,`r`,`t`,`b`])h[t]=m.lerp(e.bounds[t],g[t],x);return zt(s,h,_,a),1-x},x=(e,t)=>{let n=J(t);e.position.lerpVectors(R.from,R.to,n),e.fov=R.fov[r],e.aspect=i;let[o,s]=Nt(r);e.lookAt(Me(e.position,m.lerp(o,s,n))),e.updateMatrixWorld(),zt(e,Y(e.fov,i,g),0,a)},C=null;return{setRest:v,room:b,exit:x,solve:(e,{heroH:t,approach:n,rects:i,shapes:a,view:o,top:s=0})=>{let c=t+n,l=Math.ceil(c/It)+1,u=t+.6*n,d=e=>e<=t?m.clamp(e/t,0,1)**1.3:1+Lt((e-t)/n),f=i.filter(e=>e.kind!==`opaque`&&!e.pinned),p=(t,n,r)=>{b(e,t,n);let i=S(a.silhouette,e,o.w,o.h),c=S(a.floor,e,o.w,o.h);for(let e of f){let t=e.y-r,n=r>80?s:0;if(t+e.h<=n||t>=o.h)continue;let a={x:e.x,y:Math.max(t,n),w:e.w,h:Math.min(t+e.h,o.h)-Math.max(t,n)};if(re(i,0,0,a)||e.kind===`body`&&re(c,0,0,a))return!1}return!0},h=(e,t,n)=>{for(let r=0;r<10;r++){let r=(t+n)/2;e(r)?n=r:t=r}return n},g=(e,t,n)=>p(e,t,n)?e:p(0,t,n)?e-h(r=>p(e-r,t,n),0,e):0,v=[];for(let e=0;e<l;e++){let n=Math.min(e*It,c),r=d(n),i=n<u?n:0,a=r,o=0;n<t&&!p(r,0,n)&&(p(0,0,n)?a=g(r,0,n):(o=i>0&&p(0,i,n)?h(e=>p(0,e,n),0,i):i,a=g(r,o,n))),v.push({y:n,s:a,lift:o,need:o})}for(let e=1;e<l;e++)v[e].lift=Math.max(v[e].need,v[e-1].lift-Ft(r)*It);for(let e=l-2;e>=0;e--)v[e].lift=Math.min(v[e].y,Math.max(v[e].lift,v[e+1].lift-It));for(let e of v)e.y>=u&&(e.lift=0);for(let e of v)e.y>=t||e.lift===e.need||p(e.s,e.lift,e.y)||(e.s=p(0,e.lift,e.y)?e.s-h(t=>p(e.s-t,e.lift,e.y),0,e.s):0);for(let e=l-2;e>=0;e--)v[e].s=Math.min(v[e].s,v[e+1].s);let y=m.degToRad(40)*It/(.6*o.h),x=t=>(b(e,t,0),_.set(0,0,-1).applyQuaternion(e.quaternion),Math.atan2(-_.x,-_.z)),w=x(v[0].s);for(let e=1;e<l;e++){let t=v[e-1].s,n=e=>Math.abs(Bt(0,x(e)-w,1));v[e].s>t&&n(v[e].s)>y&&(v[e].s=t+(v[e].s-t)*(1-h(r=>n(v[e].s-(v[e].s-t)*r)<=y,0,1))),w=x(v[e].s)}return C={rows:v,end:c},{rows:l,held:v.filter(e=>e.s<d(e.y)-.001).length,carried:v.filter(e=>e.lift>0).length}},at:(e,t)=>{if(!C)return Object.assign(t,{s:0,lift:0});let{rows:n}=C,r=m.clamp(e/It,0,n.length-1),i=Math.floor(r),a=Math.min(i+1,n.length-1),o=r-i;return t.s=m.lerp(n[i].s,n[a].s,o),t.lift=m.lerp(n[i].lift,n[a].lift,o),t},get bucket(){return r},get table(){return C}}}function Ut(e,{hero:t,passage:n},r){return r.hero=m.clamp((e-t.top)/t.len,0,1),r.passage=m.clamp((e-n.top)/n.len,0,1),r.y=e-t.top,r}var Wt=e=>{let t=m.clamp(e,0,1);return t*t*(3-2*t)},Z=Math.tan(m.degToRad(3));function Gt(){let e=new s,t=new s,n={pos:[0,0,0],yaw:0,fov:0},r={l:0,r:0,t:0,b:0},i={l:0,r:0,t:0,b:0},a=`side`,o=null,c=1,l=1,u=`landscape`,d=null;return{setLayout:({bucket:e,view:t,free:n,name:r,corridor:i})=>{u=e,a=r,d=i,c=t.w/t.h,l=t.h;let s=N(1,a,d),f=d.doors[0]?.zc??s.pos[2]-P[a].d,[p,m]=P[a].at,h=(n.x0+p*(n.x1-n.x0))/t.w,g=(n.y0+m*(n.y1-n.y0))/t.h;o=Ke(s.pos,s.yaw,[He.wallX,Pe,f],s.fov,c,h,g)},pose:(s,f,p,h,g=0,_=0,v=0)=>{let y=N(me(p,h)+dt(g),a,d,n),b=Wt(f),x=m.degToRad(m.lerp(Nt(u)[1],y.yaw,b)),S=m.lerp(R.fov[u],y.fov,b);e.set(m.lerp(R.to.x,y.pos[0],b),1.1,m.lerp(R.to.z,y.pos[2],b)),s.position.copy(e),s.fov=S,s.aspect=c;let C=m.degToRad(_),w=Math.cos(C)*10;s.lookAt(t.set(e.x+Math.sin(x)*w,1.1-Math.sin(C)*10,e.z-Math.cos(x)*w)),s.updateMatrixWorld(),Y(S,c,r);let ee=Math.tan(m.degToRad(S/2))/Math.tan(m.degToRad(y.fov/2));for(let e of[`l`,`r`,`t`,`b`])i[e]=m.lerp(r[e],o[e]*ee,b);if(a===`side`){let e=Z*O(p,h)*b;i.t-=e,i.b-=e}zt(s,i,v,l)},get layout(){return a},get lens(){return o}}}var Kt=6,qt=9,Jt=2.5,Yt=2.5,Xt=A(`lime-100`),Zt=A(`wash`),Qt={dip:.82,down:1600,up:2400,min:14e3,max:22e3,first:[3500,5e3]},$t={rest:Ne.room,heroOut:.14,hole:.2,corridor:st.sun[0]},en={truck:.05,yaw:.8*Math.PI/180,pitch:.4*Math.PI/180},tn={haloA:[.35,.95],haloW:[.012,.06],density:[1.6,2.8]},nn=1.8,rn={radius:.62,held:.1,peak:.55},an=k(j.inOut),on=()=>new Promise(e=>requestAnimationFrame(e)),sn=(e,t,n)=>e+(t-e)*n,Q=e=>Math.min(Math.max(e,0),1),cn=e=>le(e)/lt.leafDeg;async function ln({canvas:r,stage:i,bus:o,probed:c,forced:l}){let u=document.documentElement,f=new URLSearchParams(location.search).get(`debug`),p=c.tier,m=Oe(r,{context:c.gl,tier:p}),h=await kt({gl:m,tier:p}),g=i?.closest(`[data-world]`)??document.querySelector(`[data-world="room"]`),_=document.querySelector(`[data-world="passage"]`),v=document.querySelector(`[data-world="corridor"]`),b=document.querySelector(`[data-world="shutters"]`),S=h.camera,re=Ht(),ce=Gt(),E=null,D=null,O={x:0,y:0,w:1,h:1},k={w:1,h:1},me={x:0,y:0,w:1,h:1},A=new s,xe=new y,Ce=new d(0,0,0,`YXZ`),j={hero:{top:0,len:1},passage:{top:0,len:1},corridor:{top:0,len:1,pre:1,next:0},shutters:{from:0,top:0,len:1}},M=null,N=!0,Me=1,Pe=null,P=!1,F=`landscape`,Re=ye(document.querySelectorAll(`[data-beat]`)),ze=ot(Re),Be=(e,t,n)=>{n&&(e||t)&&(A.set(1,0,0).applyQuaternion(S.quaternion),S.position.addScaledVector(A,e*en.truck*Me*n),S.quaternion.multiply(xe.setFromEuler(Ce.set(t*en.pitch*Me*n,e*en.yaw*Me*n,0))),S.updateMatrixWorld())},Ve=()=>{Object.assign(O,T(g)??{x:0,y:0,w:innerWidth,h:innerHeight});let a=r.clientWidth||innerWidth,o=r.clientHeight||innerHeight;m.resize(a,o);let{dpr:s}=m.size;h.shafts.setSize(Math.round(a*s),Math.round(o*s)),E?.shafts.setSize(Math.round(a*s),Math.round(o*s)),D?.shafts.setSize(Math.round(a*s),Math.round(o*s)),D&&(D.U.uPx.value=s),k.w=a,k.h=o;let c=t(u.clientWidth||a,u.clientHeight||o),[l,d]=c.view,p=Math.max(O.w/l,O.h/d),v=x(O,c.view,p);Object.assign(me,{x:O.x+v.x,y:O.y+v.y,w:l*p,h:d*p}),h.setShot(c,l,d);let y=ae(),b=ee(w(y,O,c.view),c.name);M=C({camera:S,width:l,height:d,rects:b,...Dt,stage:i?w(T(i),O,c.view):null});let oe=e[c.name],se=oe&&te(M,oe.sx,oe.sy);se&&Object.assign(M,{sx:oe.sx,sy:oe.sy,still:!0});let ce=S.quaternion.clone(),le=S.position.clone();Me=0;for(let e of[1,.5,.25]){if(Me=e,[[-1,-1],[1,-1],[-1,1],[1,1]].every(([e,t])=>(S.position.copy(le),S.quaternion.copy(ce),Be(e,t,1),ne(M,S,Dt,l,d))))break;Me=0}S.position.copy(le),S.quaternion.copy(ce),S.updateMatrixWorld(),ie(S,me,k,{sx:M.sx*p,sy:M.sy*p}),re.setRest(S,c.name,k);let ue=se&&oe.caps?oe.caps.map(e=>({...e,x:me.x+e.x*p,y:me.y+e.y*p,w:e.w*p,h:e.h*p})):y;Fe(h.U,ue,{h:o,dpr:s}),h.U.uPx.value=s,j.hero.top=O.y,j.hero.len=Math.max(O.h,1);let de=T(_),fe=_?.querySelector(`.passage__frame`)?.offsetHeight??o;j.passage.top=de?de.y:O.y+O.h,j.passage.len=de?Math.max(de.h-(getComputedStyle(_.querySelector(`.passage__frame`)??_).position===`sticky`?fe:0),1):o*1.8,Pe=re.solve(S,{heroH:j.passage.top,approach:it.approach[1]*j.passage.len,rects:ae(g),shapes:Dt,view:k,top:parseFloat(getComputedStyle(u).getPropertyValue(innerWidth>=1024?`--header-h-compact`:`--header-h`))||0}),f===`frame`&&n(()=>import(`./frame-CQsXUThy.js`).then(e=>e.o).then(e=>e.drawFrameOverlay(M,me,k,p)),__vite__mapDeps([0,1])),F=c.name,He(),We(),N=!0},He=()=>{let e=v?.querySelector(`.bays__frame`),t=v?.querySelector(`.bays__panel`);if(P=!!(e&&t&&u.classList.contains(`stages`)&&v.classList.contains(`is-stage`)),!P)return;let n=T(v.querySelector(`[data-stage-track]`)??v),r=e.getBoundingClientRect(),i=t.getBoundingClientRect(),a=r.height||k.h,o=v.nextElementSibling,s=o?T(o).y:n.y+n.h+k.h;Object.assign(j.corridor,{top:n.y,len:Math.max(n.h-a,1),pre:a,next:s});let c=parseFloat(getComputedStyle(u).getPropertyValue(innerWidth>=1024?`--header-h-compact`:`--header-h`))||0,l=i.left-r.left>r.width*.3,d=l?{x0:0,x1:i.left-r.left,y0:c,y1:a}:{x0:0,x1:r.width,y0:c,y1:i.top-r.top};ce.setLayout({bucket:F,view:k,name:l?`side`:`below`,corridor:ze,free:d})},Ue=!1,We=()=>{let e=b?.querySelector(`[data-stage-track]`),t=b?.querySelector(`.shutters__frame`);if(Ue=!!(e&&t&&getComputedStyle(t).position===`sticky`),!Ue)return;let n=T(b),r=T(e),i=t.offsetHeight||k.h;Object.assign(j.shutters,{from:n.y-k.h,top:r.y,len:Math.max(r.h-i,1)})},Ke=e=>Ue&&e>=j.shutters.from,I={on:!1,v:0},L={peak:0,held:0},Je=new a,Ze=new s,R=new a,tt=new a,at=new a,ct=Zt.clone(),ut={pos:[0,0,0],look:[0,0,0],fov:0},dt=-1,ft=-1,pt=null,mt=matchMedia(`(pointer: fine)`).matches,ht={x:0,v:0},gt={x:0,v:0},z={x:0,y:0},_t={az:0,el:0},B={level:1,from:1,to:1,start:0,dur:0,next:0},V=0,vt=0,yt=h.U.uWind.value,bt=e=>e+Qt.min+Math.random()*(Qt.max-Qt.min),xt=(e,t,n)=>Object.assign(B,{from:B.level,to:t,start:e,dur:n}),St=e=>{if(mt)return!1;if(B.next||=e+Qt.first[0]+Math.random()*(Qt.first[1]-Qt.first[0]),B.dur){let t=Math.min((e-B.start)/B.dur,1);return B.level=B.from+(B.to-B.from)*an(t),t>=1&&(B.dur=0,B.to<1?xt(e,1,Qt.up):B.next=bt(e)),!0}return e>=B.next&&(xt(e,Qt.dip,Qt.down),!0)},Ct={t:Ne.room,nudge:_t,intensity:1,rims:1},H={hero:0,passage:0,y:0,approach:0,stage:0,out:0},U={s:0,lift:0},W={x:scrollY,v:0},wt=0,Tt=-1,G=!0,K=!1,Et=!1,Ot=-1,At=0,jt=1,Mt=e=>J||!zt?e:W.x,Nt=e=>{let t=scrollY;Tt=t,Ut(t,j,H);let n=H.passage,r=ke(n),i=ve(n);K=Ke(t),G=!K&&i===`room`;let a=null,o=S,s=[],c=null,l=null;if(K){let e=j.shutters,n=Mt(t),i=Q((n-e.from)/Math.max(e.top-e.from,1)),l=Q((t-e.top)/e.len),u=Q((n-e.top)/e.len);if(H.s3=l,!Et&&pt==null&&(I.on=l>=lt.flood,I.v=+!!I.on),r=de(l),D&&r<1){let e=Te(i,u);i+u!==dt&&(D.pose(le(u),e),dt=i+u);let t=he(l);Math.abs(t-ft)>1e-4&&(D.setSun(t),ft=t),fe(I.v,L);let n=be(u,L),r=we(u,e);D.setExposure(n,r,Math.max(e/lt.slatDeg,cn(u))),D.shafts.uniforms.uDensity.value=nn*(1+.6*cn(u)+.4*L.peak),D.shafts.uniforms.uExposure.value=n,D.motes.uniforms.uBurst.value=L.peak,D.U.uMotesA.value=.55+.45*r,D.U.uTime.value=vt,D.U.uStir.value=V,D.sway(vt);let d=D.camera;pe(i,u,F,ge,ut),d.position.set(...ut.pos),d.fov=ut.fov,d.aspect=k.w/k.h,d.lookAt(ut.look[0],ut.look[1],ut.look[2]),d.updateMatrixWorld(),d.updateProjectionMatrix(),D.project(d,Ze),Je.set(Ze.x*.5+.5,Ze.y*.5+.5,rn.radius,rn.held*L.held+rn.peak*L.peak),c=Je,a=D.scene,o=d,s=D.passes}else D||(r=1)}else if(G){re.at(t,U);let e=re.room(S,U.s,U.lift);Be(ht.x,gt.x,e),_t.az=-ht.x*je.az*e,_t.el=gt.x*je.el*e,Ct.t=H.passage>0?sn($t.heroOut,$t.hole,Q(n/it.approach[1])):sn($t.rest,$t.heroOut,H.hero),Ct.intensity=Ct.rims=sn(1,B.level,e),h.setSun(Ct);let r=Q((U.s-1.3)/.7);h.U.uHaloA.value=sn(tn.haloA[0],tn.haloA[1],r),h.U.uHaloW.value=sn(tn.haloW[0],tn.haloW[1],r*r),h.shafts.uniforms.uDensity.value=sn(tn.density[0],tn.density[1],r),h.U.uCapShift.value.set(0,t*m.size.dpr),h.U.uTime.value=vt;let i=Math.min(vt/Yt,1);h.U.uLeafAmt.value=i*i*(3-2*i),h.U.uStir.value=V,a=h.scene,s=[h.shafts]}else if(E){let e=Q((n-it.exit[0])/(it.exit[1]-it.exit[0])),i=E.specs.length,c=j.corridor,u=Mt(t);H.approach=P?Q((u-(c.top-c.pre))/c.pre):0,H.stage=P?Q((u-c.top)/c.len):0;let d=Ae(c.top+c.len,c.next,k.w,k.h),f=c.top+st.release*c.len;H.out=P?Q((u-f)/(d.y0+d.len-f)):0;let m=P?Q((t-d.y0)/d.len):0;P&&e>=1?ce.pose(E.camera,H.approach,H.stage,i,H.out,ue(H.stage),Math.max(t-d.y0,0)):re.exit(E.camera,e),m>0?(r=an(m),l=Xt,tt.set(1,Ee.feather,Q(1-(c.next-t-28)/k.h),0)):tt.x=0,P&&qe(c.next-t,k.w,k.h,at)&&(l=Xt);for(let e=0;e<i;e++)E.setDoor(e,P?De(e,H.stage,i):96);J||(At=p>=2?vt+t/300:t/300),E.animate(At),E.sway(At),jt=r;let h=sn(st.sun[0],st.sun[1],H.stage);Math.abs(h-Ot)>1e-4&&(E.setSun(h),Ot=h),a=E.scene,o=E.camera,s=[E.shafts]}Et=K;let u=.3*(t-j.passage.top)/k.h;if(R.set(!K&&n>0&&n<1?r:0,.25*Math.sin(u*2.1),-11*u,0),X?.begin(),a)m.frame(a,o,s,r,c,R,l,!K&&!G?tt:null,!K&&!G?at:null);else if(K)m.frame(h.scene,S,[],1,null,null);else{let e=0,n=!1;if(P&&!G&&j.corridor.next>0){let r=j.corridor,i=Ae(r.top+r.len,r.next,k.w,k.h);e=an(Q((t-i.y0)/i.len)),n=qe(r.next-t,k.w,k.h,at)}ct.copy(Zt).lerp(Xt,e),m.frame(h.scene,S,[],1,null,R.setX(1-e),ct,null,n?at:null,Xt)}X?.end(e)},Pt=0,Ft=0,It=0,q=!0,Lt=!document.hidden,J=!!o?.state.paused,Rt=!1,Y=!1,zt=!1,X=null,Bt=!1,Vt=nt(),Wt=new AbortController,Z=null,ln=null,un=et(),dn={walls:0,posters:0},fn=()=>+(m.memoryMB+dn.walls+dn.posters).toFixed(1),pn=null,mn=()=>zt&&q&&Lt&&!Rt&&!Y,$=()=>{!Pt&&mn()&&(Pt=requestAnimationFrame(gn))},hn=()=>navigator.scheduling?.isInputPending?.()??!1;function gn(e){if(Pt=0,!mn())return;if(hn()){Pt=requestAnimationFrame(gn);return}let t=Ft?Math.min((e-Ft)/1e3,.1):1/60;Ft=e;let n=N||scrollY!==Tt,r=wt;if(wt=e,J||Math.abs(scrollY-W.x)>Jt*k.h)W.x=scrollY,W.v=0;else if(W.x!==scrollY){let i=Math.min((e-(r||e))/1e3,1)||t,a=W.x-scrollY,o=W.v+qt*a,s=Math.exp(-9*i);W.x=scrollY+(a+o*i)*s,W.v=(o-qt*(a+o*i))*s,Math.abs(scrollY-W.x)<.5&&Math.abs(W.v)<8&&(W.x=scrollY,W.v=0),n=!0}let i=Ke(scrollY)&&D&&de(H.s3??0)<1,a=!G&&!K&&E&&p>=2&&ke(H.passage)<1&&jt<.999&&(Dn.get(v)||Dn.get(_)),o=!J&&!Bt&&(G&&H.y<=1||i||a);if(Ke(scrollY)){let e=j.shutters;pt==null&&Se(I,Q((scrollY-e.top)/e.len),t,J)&&(n=!0)}if(!J){Z&&Z.k<1&&(Z.k=Math.min(Z.k+t*1e3/700,1),Vt.uWallK.value=an(Z.k),n=!0,Z.k>=1&&Pn()),pn&&pn.k<1&&(pn.k=Math.min(pn.k+t*1e3/700,1),un.uPosterK.value=an(pn.k),n=!0),mt&&(n=_e(ht,z.x,t,Kt)|_e(gt,z.y,t,Kt)||n),o&&G&&(n=St(e)||n);let r=i?D.U.uWind.value:G?yt:null;r&&Math.abs(V)>.001&&(r.y+=V*t*.12,r.x-=V*t*.05,(i?D:h).motes.wrap(r),n=!0),vt+=t}let s=!J&&X?.measuring;(n||s||o&&e-It>=33&&!m.busy())&&(Nt(e),It=e,N=!1),(!J&&o||s||N||n)&&(Pt=requestAnimationFrame(gn))}let _n=e=>{e.pointerType!==`mouse`||J||(z.x=Math.max(-1,Math.min(1,e.clientX/innerWidth*2-1)),z.y=Math.max(-1,Math.min(1,1-e.clientY/innerHeight*2)),$())},vn=()=>{z.x=z.y=0,$()},yn=e=>{let t=e.detail?.value??0;V=J?0:t,V&&$()},bn=()=>$(),xn=new MutationObserver(()=>{Bt=!!document.querySelector(`dialog[open]`),N=!0,$()});document.querySelectorAll(`dialog`).forEach(e=>xn.observe(e,{attributes:!0,attributeFilter:[`open`]}));let Sn=()=>{Lt=!document.hidden,Ft=0,X?.gap(),$()},Cn=0,wn=()=>{cancelAnimationFrame(Cn),Cn=requestAnimationFrame(()=>{Ve(),$()})};mt&&addEventListener(`pointermove`,_n,{passive:!0}),mt&&document.documentElement.addEventListener(`mouseleave`,vn),o?.addEventListener(`velocity`,yn),addEventListener(`scroll`,bn,{passive:!0}),document.addEventListener(`visibilitychange`,Sn),addEventListener(`resize`,wn);let Tn=new ResizeObserver(wn);Tn.observe(g??r),_&&Tn.observe(_),v&&Tn.observe(v),b&&Tn.observe(b);let En=v?new MutationObserver(()=>{He(),N=!0,$()}):null;En?.observe(v,{attributes:!0,attributeFilter:[`class`]});let Dn=new Map,On=new IntersectionObserver(e=>{for(let t of e)Dn.set(t.target,t.isIntersecting&&(t.target!==b||Ue));let t=q;q=[...Dn.values()].some(Boolean),q&&!t&&mn()&&(Nt(performance.now()),N=!1),r.style.visibility=q?``:`hidden`,q||X?.gap(),Ft=0,$()},{rootMargin:`10% 0px`});document.querySelectorAll(`[data-world]`).forEach(e=>On.observe(e));let kn=0,An=0,jn=e=>{if(e.preventDefault(),Rt=!0,cancelAnimationFrame(Pt),Pt=0,Wt.abort(),Ge(),Z=null,u.classList.remove(`world-live`),++kn>1)return Fn(`context lost twice`);An=setTimeout(()=>Fn(`context not restored`),5e3)},Mn=async()=>{clearTimeout(An),X?.dispose(),X=null,Rt=!1,Ve(),Nt(performance.now()),await on(),!Rt&&!Y&&(u.classList.add(`world-live`),$())};r.addEventListener(`webglcontextlost`,jn),r.addEventListener(`webglcontextrestored`,Mn);let Nn=({state:e,p75:t,final:n,source:r})=>{console.info(`[world] gate p75 ${t.toFixed(1)} ms (${r}) → ${e}${n?` (final)`:``}`),ln=e;let i=()=>Promise.all([h,E,D].filter(Boolean).map(e=>e.compile())).then(()=>{N=!0,$()});if(e===`t1`)p=1,m.setTier(1),$e(1),h.setTier(1),E?.setTier(1),D?.setTier(1),Ve(),i();else if(e===`lite`){for(let e of[h,E,D])e&&(e.shafts.enabled=!1);m.setTier(1,{lite:!0}),Ve()}else e===`off`&&Fn(`frame time over the Tier 1 limit`)};function Pn(){l!=null||Y||!zt||ln&&ln!==`t2`&&ln!==`t1`||X?.measuring||(X?.dispose(),X=oe(m.renderer.getContext(),{tier:p,onStep:Nn}))}function Fn(e){Y||(console.info(`[world] back to stills: ${e}`),Ln.dispose())}let In=null,Ln={setPaused(e){J=!!e,ht.v=gt.v=0,V=0,Ft=0,X?.gap(),N=!0,J||In?.(),$()},dispose(){Y||(Y=!0,In?.(),cancelAnimationFrame(Pt),cancelAnimationFrame(Cn),clearTimeout(An),Wt.abort(),Ge(),rt(),removeEventListener(`pointermove`,_n),document.documentElement.removeEventListener(`mouseleave`,vn),o?.removeEventListener(`velocity`,yn),o?.removeEventListener(`pause`,Rn),removeEventListener(`scroll`,bn),document.removeEventListener(`visibilitychange`,Sn),removeEventListener(`resize`,wn),Tn.disconnect(),On.disconnect(),En?.disconnect(),xn.disconnect(),r.removeEventListener(`webglcontextlost`,jn),r.removeEventListener(`webglcontextrestored`,Mn),u.classList.remove(`world-live`),r.style.visibility=``,document.getElementById(`world-frame-debug`)?.remove(),X?.dispose(),E?.dispose(),D?.dispose(),b?.classList.remove(`is-live`),h.dispose(),m.dispose(),se(o))}},Rn=e=>Ln.setPaused(e.detail.paused);if(o?.addEventListener(`pause`,Rn),await document.fonts?.ready,Ve(),await h.compile(),await m.compile(),o&&o.state?.sunIn!==`done`&&await new Promise(e=>{let t=setTimeout(e,4e3);o.addEventListener(`sun-in-end`,()=>{clearTimeout(t),e()},{once:!0})}),J&&!Y&&await new Promise(e=>In=()=>{In=null,e()}),Y)return Ln;Ve(),Nt(performance.now());let zn=m.renderer.info.render;if(console.info(`[world] room · tier ${p} (${c.reason}; ${c.gpu}) · ${zn.calls} draw calls · ${zn.triangles} triangles · dpr ${m.size.dpr} · canvas ${Math.round(k.w*m.size.dpr)}×${Math.round(k.h*m.size.dpr)} · ${m.msaa?`4x MSAA`:`no MSAA`} · ~${m.memoryMB} MB · shafts ${h.shafts.size.join(`×`)} · motes ${h.motes.count} · parallax ${Me}${M.ok?M.still?` · framed as the still`:` · framed live (the still would touch text)`:` · text-safe framing not found`} · flight held ${Pe.held}/${Pe.rows}, carried ${Pe.carried}`),await on(),Y||Rt)return Ln;u.classList.add(`world-live`),zt=!0,l??(X=oe(m.renderer.getContext(),{tier:p,onStep:Nn})),$(),f&&n(()=>import(`./debug-B29ph466.js`).then(e=>e.attach({gl:m,memoryMB:fn,texMB:dn,room:h,corridor:()=>E,tier:()=>p,frame:()=>M,solved:()=>Pe,at:H,path:U,span:j,inRoom:()=>G,walking:()=>P,walk:ce,flight:re,section:g,parallax:()=>Me,shutters:()=>({staged:Ue,built:!!D,at:H.s3??null,flood:{...I},span:{...j.shutters}}),hold:e=>{pt=e,e!=null&&Object.assign(I,{on:!0,v:e}),N=!0,$()},redraw:()=>Nt(performance.now()),paused:()=>J,running:mn,pointer:(e,t)=>{z.x=ht.x=e,z.y=gt.x=t,ht.v=gt.v=0,N=!0,$()},wallK:e=>(e!=null&&(Vt.uWallK.value=e,Z&&(Z.k=1)),Vt.uWallK.value)})),__vite__mapDeps([2,0,1]));let Bn=()=>new Promise(e=>`requestIdleCallback`in window?requestIdleCallback(e,{timeout:1500}):setTimeout(e,50));return Bn().then(async()=>{if(Y)return;let e=performance.now(),t=await Ie({renderer:m.renderer,tier:p,yieldNow:Ye,signal:Wt.signal});if(Y||Rt)return Ge();$e(p),Z={k:0},N=!0,$(),dn.walls=t.gpuMB,console.info(`[world] walls · ${t.size} px maps · ${t.kb} KB · GPU ~${t.gpuMB} MB · in ${Math.round(performance.now()-e)} ms`)}).catch(e=>{!Y&&!Wt.signal.aborted&&console.warn(`[world] walls unavailable, the procedural walls stay`,e)}),Bn().then(async()=>{if(Y)return;let e=await Xe({size:Qe({tier:p,width:innerWidth}),signal:Wt.signal});if(Y||Rt)return rt();pn={k:0},N=!0,$(),dn.posters=Le(e.size),console.info(`[world] posters · ${e.size} px atlas · ${e.kb} KB · GPU ~${dn.posters} MB · world ~${fn()} MB`)}).catch(e=>{!Y&&!Wt.signal.aborted&&console.warn(`[world] posters unavailable, the walls stay bare`,e)}),Bn().then(async()=>{if(Y)return;let{buildCorridor:e}=await n(async()=>{let{buildCorridor:e}=await import(`./corridor-anEKWKKE.js`);return{buildCorridor:e}},__vite__mapDeps([3,4,5,6,7,8]));if(Y)return;let t=0,r=performance.now(),i=await e({gl:m,tier:p,beats:Re,textures:h.textures,traces:!!v?.hasAttribute(`data-traces`),onSlice:e=>{t=Math.max(t,e)}}),a=performance.now()-r;if(i.setTier(p),i.shafts.setSize(Math.round(k.w*m.size.dpr),Math.round(k.h*m.size.dpr)),await i.compile(),Y)return i.dispose();E=i,N=!0,$(),console.info(`[world] corridor · ${Re.length} doors (${Re.map(e=>`${e.unit} ${e.door}`).join(`, `)}) · gạch bông baked ${i.bakedInWorker?`in a worker`:`on the main thread`} in ${Math.round(a)} ms, longest main-thread slice ${t.toFixed(1)} ms`)}).catch(e=>console.warn(`[world] corridor unavailable, the wash holds`,e)).then(()=>b&&!Y?Bn():null).then(async()=>{if(!b||Y)return;let{buildShutters:e}=await n(async()=>{let{buildShutters:e}=await import(`./shutters-BczKilyt.js`);return{buildShutters:e}},__vite__mapDeps([9,6,7,8]));if(Y)return;let t=await e({gl:m,tier:p,textures:h.textures});if(t.shafts.setSize(Math.round(k.w*m.size.dpr),Math.round(k.h*m.size.dpr)),t.U.uPx.value=m.size.dpr,await t.compile(),Y)return t.dispose();D=t,b.classList.add(`is-live`),N=!0,$(),console.info(`[world] shutters ready`)}).catch(e=>console.warn(`[world] shutters unavailable, the stills stay`,e)),Ln}export{ln as live,mt as n,_t as t};