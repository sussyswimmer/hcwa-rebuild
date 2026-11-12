import{D as e,E as t,F as n,I as r,M as i,N as a,O as o,P as s,S as c,T as l,_ as u,g as d,h as f,i as p,j as m,k as h,m as g,n as _,o as v,p as y,r as b,s as x,t as S,u as C,v as w,y as ee}from"./three-Dgmw-8ZC.js";import{c as T,i as te,n as E,r as ne}from"./memory-Pyl4VpNY.js";var D={"lime-50":`#F7F2E9`,"lime-100":`#EFE7DA`,"lime-300":`#CDBFAB`,"shade-100":`#E3E1EC`,"shade-300":`#C3C0D8`,border:`#7A6F5E`,"ink-900":`#1D2131`,"ink-700":`#434859`,"ink-500":`#5A5F70`,"sun-400":`#FFC42E`,"sun-500":`#F2B312`,"sun-200":`#FFE6AE`,"sun-300":`#FFD58A`,"sun-100":`#FFF3D3`,"gold-700":`#8A5A12`,"blue-700":`#313188`,"blue-900":`#20205A`,"blue-950":`#16163F`,"on-dusk":`#FFFFFF`,"on-dusk-2":`#C8CAEB`,"unit-green-bamboo":`#008E3A`,"unit-little-rose":`#DC4C98`,"unit-cau-han":`#417CBD`,ok:`#256B43`,"ok-100":`#E2EFE6`,warn:`#8A5A00`,error:`#B42318`,focus:`#313188`,"focus-on-dark":`#FFC42E`,"world-sky":`#FFF7E6`,"world-wall-shade":`#C7CBD2`,"world-wall-sun":`#FFE6AE`,"world-ceiling":`#D5D9DF`,"world-floor-shade":`#A5A9B1`,"world-screen-face":`#3D4370`,"world-wash":`#FFF3D3`,"tile-cream":`#F3E9D6`,"tile-ochre":`#C4861C`,"tile-indigo":`#2C3584`,"tile-jade":`#176F5E`,"tile-brick":`#A5472A`,"tile-ochre-lit":`#F0B13F`,"tile-indigo-lit":`#4265C2`,"tile-jade-lit":`#449C80`,"tile-brick-lit":`#D7703B`,"world-sun-room":`#FFD9A0`,"world-sun-passage":`#FFE4B8`,"world-sun-corridor-a":`#FFE9C8`,"world-sun-corridor-b":`#FFF2DE`,"world-sun-shutters-a":`#FFD08A`,"world-sun-shutters-b":`#FFB866`,"world-wood":`#8A6A48`,"world-vang-shade":`#D5BB7E`,"world-vang-sun":`#FFE09A`,"world-dado-shade":`#5B8F78`,"world-dado-sun":`#A2D4B6`,"world-dado-line":`#1C5A4C`,"world-gach-shade":`#BDAF93`,"world-ceiling-warm":`#DDD5C4`,"world-face-sand":`#D7CDBD`},O={sky:`#FFF7E6`,wallShade:`#C7CBD2`,wallSun:`#FFE6AE`,ceiling:`#D5D9DF`,floorShade:`#A5A9B1`,screenFace:`#3D4370`,wash:`#FFF3D3`,sunRoom:`#FFD9A0`,sunPassage:`#FFE4B8`,sunCorridorA:`#FFE9C8`,sunCorridorB:`#FFF2DE`,sunShuttersA:`#FFD08A`,sunShuttersB:`#FFB866`,wood:`#8A6A48`,vangShade:`#D5BB7E`,vangSun:`#FFE09A`,dadoShade:`#5B8F78`,dadoSun:`#A2D4B6`,dadoLine:`#1C5A4C`,gachShade:`#BDAF93`,ceilingWarm:`#DDD5C4`,faceSand:`#D7CDBD`},re={out:[.23,1,.32,1],inOut:[.77,0,.175,1],drawer:[.32,.72,0,1],sun:[.16,1,.3,1],hover:[.215,.61,.355,1]},ie=1.22,k=e=>new v(O[e]??D[e]),ae=e=>new s(e.r,e.g,e.b),oe=(e,t,n)=>e.clone().lerp(t,n),se=`
float h21(vec2 p){ p = fract(p * vec2(123.34, 456.21)); p += dot(p, p + 45.32); return fract(p.x * p.y); }
float vn(vec2 p){ vec2 i = floor(p), f = fract(p); f = f * f * (3. - 2. * f);
  return mix(mix(h21(i), h21(i + vec2(1, 0)), f.x), mix(h21(i + vec2(0, 1)), h21(i + vec2(1, 1)), f.x), f.y); }
float fbm(vec2 p){ float s = 0., a = .5; for (int i = 0; i < 4; i++) { s += a * vn(p); p *= 2.03; a *= .5; } return s; }
vec2 h22(vec2 p){ return fract(sin(vec2(dot(p, vec2(127.1, 311.7)), dot(p, vec2(269.5, 183.3)))) * 43758.5453); }
`,ce=`
vec3 neutralTM(vec3 c, float exposure){
  c *= exposure;
  float x = min(c.r, min(c.g, c.b));
  float off = x < 0.08 ? x - 6.25 * x * x : 0.04;
  c -= off;
  float peak = max(c.r, max(c.g, c.b));
  if (peak < 0.76) return c;
  float d = 0.24;
  float np = 1. - d * d / (peak + d - 0.76);
  c *= np / peak;
  float g = 1. - 1. / (0.15 * (peak - np) + 1.);
  return mix(c, vec3(np), g);
}
vec3 toSRGB(vec3 c){ c = max(c, 0.); return mix(c * 12.92, 1.055 * pow(c, vec3(1. / 2.4)) - 0.055, step(0.0031308, c)); }
`,le=`
uniform float uLeafAmt;
float leaf(vec3 P, float time){
  if (uLeafAmt <= 0.) return 1.;
  vec2 q = (P + uL * ((uSX - P.x) / uL.x)).zy;
  // Two layers of leaves drifting apart: a phone frame that holds only a few blocks' patches still
  // spans several cells, so its light never sits on one plateau for seconds.
  float n = 0.5 * vn(q * 1.2 + vec2(time * 0.55, sin(time * 0.6) * 0.5)) + 0.5 * vn(q * 3.6 + vec2(-time * 0.9, time * 0.4 + 7.3));
  return mix(1., 0.42 + 0.68 * smoothstep(0.36, 0.64, n), uLeafAmt);
}
`,ue=`
float ign(vec2 p){ return fract(52.9829189 * fract(dot(p, vec2(0.06711056, 0.00583715)))); }
#define DITHER_OUT gl_FragColor.rgb += (ign(gl_FragCoord.xy) - .5) / 255.
`,de=()=>{let e=new p;return e.setAttribute(`position`,new C([-1,-1,0,3,-1,0,-1,3,0],3)),e.setAttribute(`uv`,new C([0,0,2,0,0,2],2)),e},A=`varying vec2 vU; void main(){ vU = uv; gl_Position = vec4(position.xy, 0., 1.); }`;function fe(e,t,n,r,i=!0,a=1/0){let o=globalThis.devicePixelRatio||1,s=Math.max(e*t,1),c=e=>Math.sqrt(e/s),l=c(Math.min(E,a)),u=e=>(Number.isFinite(a)?Math.floor(e*100+1e-6):Math.round(e*100))/100;if(r)return u(Math.min(o,1,l));if(n>=2)return u(i?Math.min(o,1.75,c(Math.min(ne,a))):Math.min(Math.max(Math.min(o,1.75),1.5),l));let d=Math.min(o,1.25);return u(Math.min(s*d*d>16e5?Math.min(o,1):d,l))}var j=new WeakMap;function pe(e){let t=j.get(e);return t||j.set(e,t={rt:new r(1,1,{depthBuffer:!1,minFilter:y,magFilter:y}),users:0}),t.users++,t.rt}function me(e){let t=j.get(e);!t||--t.users>0||(t.rt.dispose(),j.delete(e))}var he=e=>{let t=j.get(e)?.rt;return t&&t.width>1?t.width*t.height*4/2**20:0};function ge(t,{context:r,tier:i,poster:a=!1}){let s=!!r?.getContextAttributes?.().antialias,c=new S({canvas:t,context:r,antialias:s,alpha:!1,powerPreference:`high-performance`,preserveDrawingBuffer:a});c.toneMapping=7,c.toneMappingExposure=ie,c.outputColorSpace=e,c.autoClear=!1,c.info.autoReset=!1,c.setClearColor(k(`wash`),1),c.debug.checkShaderErrors=a||new URLSearchParams(globalThis.location?.search??``).has(`debug`);let l={value:new n},d={value:1},f={value:new n},p={value:new n},m={value:new n},g=k(`wash`),_={value:g.clone()},v=new h({uniforms:{uWash:{value:0},uColor:{value:g.clone()},uRim:{value:k(`sun-200`)},uFlare:l,uAspect:d,uHole:f,uSweep:p,uHem:m,uHemC:_},vertexShader:A,fragmentShader:`uniform float uWash, uAspect; uniform vec3 uColor, uRim, uHemC; uniform vec4 uFlare, uHole, uSweep, uHem; varying vec2 vU;
      ${ue}
      float h12(vec2 p){ p = fract(p * vec2(123.34, 456.21)); p += dot(p, p + 45.32); return fract(p.x * p.y); }
      float lobes(vec2 p, float r){ return min(min(length(p - vec2(r, 0.)), length(p + vec2(r, 0.))), min(length(p - vec2(0., r)), length(p + vec2(0., r)))) - r; }
      void main(){
        vec2 d = (vU - uFlare.xy) * vec2(uAspect, 1.);
        float f = uFlare.w > 0. ? uFlare.w * exp(-dot(d, d) / (uFlare.z * uFlare.z)) : 0.;
        vec3 col = uColor;
        if (uHole.x > 0.) {
          vec2 p = (vU - 0.5) * vec2(uAspect, 1.);
          float inside = 1. - smoothstep(-0.09, 0.09, lobes(p, 0.3 * clamp(uAspect, 0.55, 1.2)));
          vec3 light = mix(uRim, uColor, inside);
          // Dust: one speck in some cells of a grid that slides with the scroll, the opposite tone of the
          // light it is in (a fleck of gold on the plateau, a glint in the vignette).
          vec2 g = p * 11. + uHole.yz, c = floor(g), q = fract(g) - 0.5;
          float k = h12(c), m = step(0.58, k);
          vec2 at = vec2(h12(c + 7.1), h12(c + 3.3)) - 0.5;
          float speck = m * smoothstep(0.075, 0.02, length(q - at * 0.7)) * (0.35 + 0.4 * h12(c + 1.9));
          light = mix(light, mix(uRim, uColor, 1. - inside), speck);
          col = mix(uColor, light, uHole.x);
        }
        float a = uWash;
        if (uSweep.x > 0.) {
          // r: 0 at the frame's top, 1 at the foot of what is still seen; the front runs down it, feathered.
          float r = clamp((1. - vU.y) / max(1. - uSweep.z, .05), 0., 1.), F = uSweep.y, g = uWash * (1. + F) - F * .5;
          a = 1. - smoothstep(g - F * .5, g + F * .5, r);
        }
        if (uHem.x > 0.) {
          float h = clamp((vU.y - uHem.y) / uHem.z, 0., 1.);
          // M6: a smoothstep, not three straight pieces: a piecewise ramp's corners read as faint lines (Mach bands).
          float v = h * h * (3. - 2. * h);
          float A = 1. - (1. - a) * v;
          col = (uHemC * (1. - v) + col * a * v) / max(A, 1e-4);
          a = A;
        }
        gl_FragColor = vec4(col, max(a, f));
      #include <colorspace_fragment>
        DITHER_OUT;
        gl_FragColor.a += (ign(gl_FragCoord.yx) - .5) / 255.;
      }`,transparent:!0,depthTest:!1,depthWrite:!1,toneMapped:!1}),y=new o().add(new u(de(),v));y.children[0].frustumCulled=!1;let b=new ee(-1,1,1,-1,0,1),x={w:0,h:0,dpr:1},C=!1,w=null,T=1/0,E={},ne=g.clone(),D=c.getContext();return{renderer:c,ortho:b,get size(){return x},get buffersMB(){return+te(Math.round(x.w*x.dpr),Math.round(x.h*x.dpr),s).toFixed(1)},track(e,t){t>0?E[e]=t:delete E[e]},get memory(){let e={buffers:this.buffersMB,shafts:+he(c).toFixed(1)};for(let[t,n]of Object.entries(E))e[t]=+n.toFixed(1);let t=te(Math.round(x.w*x.dpr),Math.round(x.h*x.dpr),s)+he(c)+Object.values(E).reduce((e,t)=>e+t,0);return{...e,total:+t.toFixed(1)}},get memoryMB(){return this.memory.total},msaa:s,get tier(){return i},setTier(e,{lite:t=!1}={}){i=e,C=t},limit(e){T=e>0?e:1/0},get cap(){return T},resize(e,t){let n=a?x.dpr:fe(e,t,i,C,s,T);return e===x.w&&t===x.h&&n===x.dpr?!1:(x={w:e,h:t,dpr:n},c.setPixelRatio(n),c.setSize(e,t,!1),!0)},setPosterSize(e,t,n){x={w:e,h:t,dpr:n/e},c.setPixelRatio(x.dpr),c.setSize(e,t,!1)},frame(e,t,n=[],r=0,i=null,o=null,s=null,u=null,h=null,S=null){c.info.reset(),c.setRenderTarget(null);let C=s??g;v.uniforms.uColor.value.equals(C)||v.uniforms.uColor.value.copy(C);let ee=r>=1?C:g;if(ne.equals(ee)||(ne.copy(ee),c.setClearColor(ee,1)),c.clear(),r<1){c.render(e,t);for(let e of n)e.render(t)}let T=i&&i.w>0,te=o&&o.x>0&&r>0,E=h&&h.x>0,O=S??C;E&&!_.value.equals(O)&&_.value.copy(O),(r<1&&(r>0||T||E)||te||E&&!O.equals(C))&&(v.uniforms.uWash.value=r,T?l.value.copy(i):l.value.w=0,te?f.value.copy(o):f.value.x=0,u&&u.x>0?p.value.copy(u):p.value.x=0,E?m.value.copy(h):m.value.x=0,d.value=x.w/Math.max(x.h,1),c.render(y,b)),!w&&!a&&(w=D.fenceSync(D.SYNC_GPU_COMMANDS_COMPLETE,0))},compile:()=>c.compileAsync(y,b),busy(){return w?D.isContextLost()?(w=null,!1):D.getSyncParameter(w,D.SYNC_STATUS)===D.UNSIGNALED||(D.deleteSync(w),w=null,!1):!1},dispose(){w&&!D.isContextLost()&&D.deleteSync(w),w=null,v.dispose(),y.children[0].geometry.dispose(),c.dispose()}}}var M=.3,_e=.06,ve={r:.056,pipAt:.112,pipR:.021},ye={cell:.075,half:.026},be={R:.105,w:.022},xe={step:.05,w:.018},N={slotX:.052,slotW:.036,slotH:.25,nodes:[-.06,.06],nodeH:.016},P={R:.09,w:.024,deckY:-.07,deckW:.2,deckH:.026},F=e=>e.toFixed(4),Se=`
float sdBox2(vec2 p, vec2 b){ vec2 d = abs(p) - b; return length(max(d, 0.)) + min(max(d.x, d.y), 0.); }

// Hoa thị: quatrefoil (four overlapping circles) + four corner pips that make a rosette
// where four blocks meet. The house block: the screen, the hero block, Little Rose's transom.
float sdHoaThi(vec2 p){
  float r = ${F(ve.r)};
  float d = min(min(length(p - vec2(r, 0.)), length(p + vec2(r, 0.))), min(length(p - vec2(0., r)), length(p + vec2(0., r)))) - r;
  vec2 a = abs(p) - vec2(${F(ve.pipAt)});
  return min(d, length(a) - ${F(ve.pipR)});
}

// Mắt cáo: dense diamond lattice.
float sdMatCao(vec2 p){
  vec2 q = mod(p + 0.5 * ${F(ye.cell)}, ${F(ye.cell)}) - 0.5 * ${F(ye.cell)};
  float d = (abs(q.x) + abs(q.y) - ${F(ye.half)}) * 0.7071;
  return max(d, max(abs(p.x), abs(p.y)) - 0.13);
}

// Vòng tròn: quarter rings in the corners; the full circle only exists where four blocks meet.
float sdVongTron(vec2 p){
  return abs(length(abs(p) - vec2(${F(M/2)})) - ${F(be.R)}) - ${F(be.w)} * 0.5;
}

// Bậc thang: stepped zigzag slot.
float sdBacThang(vec2 p){
  float s = ${F(xe.step)};
  vec2 q = vec2(p.x, p.y - s * (floor(p.x / s + 0.5)));
  float d = sdBox2(q, vec2(0.5 * s + 0.002, ${F(xe.w)} * 0.5));
  return max(d, max(abs(p.x), abs(p.y)) - 0.125);
}

// Đốt tre (Tre Xanh's transom): two vertical slots bridged by bamboo nodes.
float sdDotTre(vec2 p){
  vec2 q = vec2(abs(p.x) - ${F(N.slotX)}, p.y);
  float d = sdBox2(q, vec2(${F(N.slotW/2)}, ${F(N.slotH/2)}));
  float n = min(abs(p.y - (${F(N.nodes[0])})), abs(p.y - (${F(N.nodes[1])}))) - ${F(N.nodeH/2)};
  return max(d, -n);
}

// Vòm cầu (Cầu Hàn's transom): an arch over a deck slot.
float sdVomCau(vec2 p){
  float arch = max(abs(length(p - vec2(0., ${F(P.deckY)})) - ${F(P.R)}) - ${F(P.w/2)}, ${F(P.deckY)} - p.y);
  float deck = sdBox2(p - vec2(0., ${F(P.deckY-.03)}), vec2(${F(P.deckW/2)}, ${F(P.deckH/2)}));
  return min(arch, deck);
}
`,Ce=`
uniform vec3 uL;
uniform float uSX, uB, uD, uZ0, uZ1, uY0, uY1;
#ifndef HOLE
#define HOLE sdHoaThi
#endif
vec2 blockLocal(vec2 zy){ return mod(zy - vec2(uZ0, uY0), uB) - 0.5 * uB; }
bool onScreen(vec3 Q){ return Q.z >= uZ0 && Q.z <= uZ1 && Q.y >= uY0 && Q.y <= uY1; }
float apert(vec3 P, float x){
  float t = (x - P.x) / uL.x;
  vec3 Q = P + uL * t;
  if (!onScreen(Q)) return 0.0;
  float w = 0.0025 + 0.0093 * max(t, 0.0) * 0.6;
  return smoothstep(w, -w, HOLE(blockLocal(Q.zy)));
}
float sunMask(vec3 P){
  float side = sign(uL.x);
  if ((P.x - uSX) * side > -0.001) return 0.0;
  return apert(P, uSX) * apert(P, uSX + side * uD);
}
`;function we({sx:e,z0:t,z1:n,y0:r,y1:i},a=new s(1,0,0)){return{uL:{value:a},uSX:{value:e},uB:{value:M},uD:{value:_e},uZ0:{value:t},uZ1:{value:n},uY0:{value:r},uY1:{value:i}}}var Te={display:1,body:.4},Ee=`
uniform vec4 uCaps[8];
uniform float uCapLv[8];
uniform int uCapN;
uniform float uCapFeather;
uniform vec2 uCapShift; // the text's scroll since the rest pose (device px, y up): the canvas is fixed
float capAt(vec2 p){
  p -= uCapShift;
  float m = 1.;
  for (int i = 0; i < 8; i++) {
    if (i >= uCapN) break;
    vec4 r = uCaps[i];
    vec2 d = max(max(r.xy - p, p - r.zw), 0.);
    m = min(m, mix(1., uCapLv[i], 1. - smoothstep(0., uCapFeather, length(d))));
  }
  return m;
}
`,De=()=>({uCaps:{value:Array.from({length:8},()=>new n)},uCapLv:{value:Array(8).fill(1)},uCapN:{value:0},uCapFeather:{value:24},uCapShift:{value:new a}});function Oe(e,t,{h:n,dpr:r}){let i=t.filter(e=>!e.pinned&&(Te[e.kind]??1)<1).sort((e,t)=>t.w*t.h-e.w*e.h).slice(0,8);i.forEach((t,i)=>{e.uCaps.value[i].set((t.x-24)*r,(n-(t.y+t.h+24))*r,(t.x+t.w+24)*r,(n-(t.y-24))*r),e.uCapLv.value[i]=Te[t.kind]}),e.uCapN.value=i.length,e.uCapFeather.value=24*r}var I=512,ke=()=>globalThis.scheduler?.yield?scheduler.yield():new Promise(e=>setTimeout(e,0));function Ae(e){let t=e>>>0;return()=>(t=t+1831565813>>>0,((t^t>>>15)*(t|1)>>>0)/4294967296+0)}function L(e,t){let n=Ae(t),r=new Float32Array(e*e);for(let e=0;e<r.length;e++)r[e]=n();return(t,n)=>{let i=t/I*e,a=n/I*e,o=Math.floor(i),s=Math.floor(a),c=i-o,l=a-s,u=c*c*(3-2*c),d=l*l*(3-2*l),f=(t,n)=>r[(n%e+e)%e*e+(t%e+e)%e];return(f(o,s)*(1-u)+f(o+1,s)*u)*(1-d)+(f(o,s+1)*(1-u)+f(o+1,s+1)*u)*d}}function je(e,n=I,r=I){let a=new x(e,n,r,l,i);return a.wrapS=a.wrapT=t,a.generateMipmaps=!0,a.minFilter=g,a.magFilter=y,a.needsUpdate=!0,a}async function Me({slice:e=!0}={}){let t=L(6,11),n=L(14,23),r=L(40,37),i=L(96,41),a=L(64,53),o=L(4,59),s=L(3,71),c=L(7,73),l=new Uint8Array(I*I*4);for(let u=0;u<I;u++){for(let e=0;e<I;e++){let d=(u*I+e)*4;l[d]=255*(.62*t(e,u)+.38*n(e,u)),l[d+1]=255*(.6*r(e,u)+.4*i(e,u)),l[d+2]=255*(.7*a(e,u)+.3*o(e,u)),l[d+3]=255*(.7*s(e,u)+.3*c(e,u))}e&&u%64==63&&await ke()}return je(l)}var Ne=.92;async function Pe({slice:e=!0}={}){let t=k(`lime-100`),n=[[`lime-300`,0],[`ink-500`,.45],[`gold-700`,.5],[`blue-700`,.6]].map(([e,n])=>{let r=k(e).lerp(t,n);return[r.r/t.r,r.g/t.g,r.b/t.b]}),r=[.5,.78,.92,1],i=Ae(97),a=L(10,101),o=new Uint8Array(I*I*4);for(let e=0;e<I;e++)for(let t=0;t<I;t++){let n=(e*I+t)*4,r=Ne*(.97+.06*a(t,e))*255;o[n]=o[n+1]=o[n+2]=r,o[n+3]=255}for(let t=0;t<9e3;t++){let a=i()*I,s=i()*I,c=.8+i()*i()*2.2,l=i(),u=n[r.findIndex(e=>l<=e)];for(let e=Math.floor(s-c-1);e<=s+c+1;e++)for(let t=Math.floor(a-c-1);t<=a+c+1;t++){let n=Math.min(1,Math.max(0,c+.5-Math.hypot(t+.5-a,e+.5-s)))*.4;if(n<=0)continue;let r=((e+I)%I*I+(t+I)%I)*4;for(let e=0;e<3;e++)o[r+e]=Math.min(255,o[r+e]*(1-n)+Ne*255*u[e]*n)}e&&t%3e3==2999&&await ke()}return je(o)}var Fe=.92,Ie=e=>`
uniform float uWallK, uWallMix;
${e.map(e=>`uniform sampler2D uW${e};`).join(`
`)}
vec4 wallTap(sampler2D t, vec2 uv, float w){
  vec4 a = texture2D(t, uv);
  vec4 s = vec4(a.rg * 2. - 1., a.b * 2., a.a);
  if (uWallMix > 0.) {
    vec4 b = texture2D(t, mat2(.829, .559, -.559, .829) * uv * 1.37 + vec2(.31, .17));
    vec2 n2 = b.rg * 2. - 1.;
    s = mix(s, vec4(.829 * n2.x + .559 * n2.y, -.559 * n2.x + .829 * n2.y, b.b * 2., b.a), w * uWallMix);
  }
  return s;
}
// The relief normal on an axis-aligned surface (N: its normal), at strength k.
vec3 wallN(vec3 N, vec2 n, float k){
  vec3 T = abs(N.x) > .5 ? vec3(0., 0., 1.) : vec3(1., 0., 0.);
  vec3 B = abs(N.y) > .5 ? vec3(0., 0., 1.) : vec3(0., 1., 0.);
  n *= k;
  return normalize(N * sqrt(max(1. - dot(n, n), .04)) + T * n.x + B * n.y);
}
// Any basis (a panel's u, v; a door's derivative frame).
vec3 wallNB(vec3 N, vec3 T, vec3 B, vec2 n, float k){ n *= k; return normalize(N * sqrt(max(1. - dot(n, n), .04)) + T * n.x + B * n.y); }
float wallAge(float a){ return mix(.3, 1., smoothstep(.3, .72, a)); }
// Cracks. A map has few, and a 2 m map repeats, so a crack seen in every repeat reads as a stamp. They come in only
// where a gate says (0..1), and the gate is non-periodic (wallCrackGate()).
// wallRelC(): the tone relief split at 10% darker: above it the mottle, laps and stains (k), below it the cracks and
// deep pits (kc). wallRel(): a constant k (paint, blocks), its cracks in only from k 0.82 up.
float wallRelC(float r, float k, float kc){ float d = r - 1., m = max(d, -.1); d = m * k + (d - m) * kc; return 1. + min(d, min(.5 * d, .05)); }
float wallCrack(float k){ return smoothstep(.82, 1., k); }
float wallRel(float r, float k){ return wallRelC(r, k, k * wallCrack(k)); }
// Plaster and lime wash carry a crack mask in A (walls-pack.js: the crack's core and the 8 texels of lips its normal
// gives it): tone and relief under the mask take the gate g, so a crack left out leaves no trace, not even its lit lip.
float wallRelM(vec4 w, float k, float g){ float d = w.b - 1.; d *= k * mix(1., g, w.a); return 1. + min(d, min(.5 * d, .05)); }
vec2 wallCrackN(vec4 w, float g){ return w.rg * mix(1., g, w.a); }
// The walls' crack gate (surface.js, still-surface.js): well aged (wallCrack(age)) and, per repeat of the map, a
// random draw (a value noise whose lattice points sit on each 2 m tile's middle, so a crack, which keeps inside its
// tile, takes its own tile's draw, and the draw never cuts across a crack): about one repeat in three keeps its cracks.
// The Tier 2 second sample draws on its own tiles. wuv: the map's uv; w: wallTap's blend.
float wallH(vec2 p){ return fract(sin(dot(p, vec2(127.1, 311.7))) * 43758.5453); }
float wallVN(vec2 p){ vec2 i = floor(p), f = fract(p); f = f * f * (3. - 2. * f); return mix(mix(wallH(i), wallH(i + vec2(1., 0.)), f.x), mix(wallH(i + vec2(0., 1.)), wallH(i + 1.), f.x), f.y); }
float wallCrackGate(vec2 wuv, float w, float age){
  float d = mix(wallVN(wuv - .5), wallVN(mat2(.829, .559, -.559, .829) * wuv * 1.37 + vec2(.31, .17) - .5 + 17.), w * uWallMix);
  return wallCrack(age) * smoothstep(.55, .8, d);
}
float wallSun(vec3 Nb, vec3 N, vec3 L){ float g = dot(N, L); return clamp(1. + .8 * (dot(Nb, L) - g) / max(g, .25), .55, 1.05); }
float wallFill(vec3 Nb, vec3 N, vec3 D, float k){ return clamp(1. + k * dot(Nb - N, D), .7, 1.04); }
`,R={names:[`plaster`,`limewash`,`dado`,`paint`,`concrete`],sizes:[512,1024],mean:{plaster:[.8123,.8007,.7601],limewash:[.793,.5684,.1899],dado:[.2161,.4658,.3234],paint:[.6652,.6648,.6645],concrete:[.5522,.5434,.5049]},bytes:{512:{plaster:[3228,48074],limewash:[4980,54264],dado:[6044,38800],paint:[5252,21816],concrete:[14988,59732]},1024:{plaster:[7608,131332],limewash:[11250,143960],dado:[15678,107638],paint:[12414,63400],concrete:[53068,159366]}}},Le=new Float32Array(256);for(let e=0;e<256;e++){let t=e/255;Le[e]=t<=.04045?t/12.92:((t+.055)/1.055)**2.4}var Re=(e,t,n)=>{let r=Math.min(Math.max((n-e)/(t-e),0),1);return r*r*(3-2*r)},ze={dado:(e,t)=>Re(-6,10,e-t),paint:(e,t,n)=>Re(18,40,e-n)};function Be(e,t){let n=t*t,r=Math.max(1,Math.round(8*t/1024)),i=1/(r+1),a=new Float32Array(n),o=new Float32Array(n);for(let t=0;t<n;t++)a[t]=Re(.06,.16,1-e[t]);for(let e=0;e<r;e++){for(let e=0;e<t;e++){let n=e*t,r=(e+t-1)%t*t,s=(e+1)%t*t;for(let e=0;e<t;e++){let c=(e+t-1)%t,l=(e+1)%t,u=Math.max(a[n+c],a[n+l],a[r+e],a[s+e])-i;o[n+e]=Math.max(a[n+e],u)}}[a,o]=[o,a]}return a}function Ve(e,t,n){let r=t.length/4,i=new Uint8Array(r*4),a=new Float32Array(r),o=ze[e],s=0;for(let e=0;e<r;e++){let n=e*4,r=.2126*Le[t[n]]+.7152*Le[t[n+1]]+.0722*Le[t[n+2]];a[e]=r,s+=r}let c=s/r||1;for(let e=0;e<r;e++)a[e]/=c;let l=Math.round(Math.sqrt(r)),u=o||l*l!==r?null:Be(a,l);for(let e=0;e<r;e++){let r=e*4;i[r]=n[r],i[r+1]=n[r+1],i[r+2]=Math.min(255,Math.round(a[e]*127.5)),i[r+3]=o?Math.round(255*o(t[r],t[r+1],t[r+2])):u?Math.round(255*u[e]):0}return i}function He(e){let{width:t,height:n}=e,r=(typeof OffscreenCanvas==`function`?new OffscreenCanvas(t,n):Object.assign(document.createElement(`canvas`),{width:t,height:n})).getContext(`2d`,{willReadFrequently:!0,colorSpace:`srgb`});return r.drawImage(e,0,0),r.getImageData(0,0,t,n,{colorSpace:`srgb`}).data}async function Ue(e,t){let n=await fetch(e,{signal:t,priority:`low`});if(!n.ok)throw Error(`${e}: ${n.status}`);return createImageBitmap(await n.blob(),{imageOrientation:`flipY`,premultiplyAlpha:`none`,colorSpaceConversion:`none`})}async function We(e,t,n,r){let[i,a]=await Promise.all([Ue(n[0],r),Ue(n[1],r)]);if(i.width!==t||a.width!==t)throw Error(`${e}: expected ${t} px`);let o=Ve(e,He(i),He(a));return i.close?.(),a.close?.(),{name:e,size:t,data:o}}var Ge={plaster:`Pl`,limewash:`Lw`,dado:`Da`,paint:`Pt`,concrete:`Co`},Ke={1:512,2:1024},qe={wall:2,paint:1,block:1},z={uWallK:{value:0},uWallMix:{value:0}};for(let e of Object.values(Ge))z[`uW${e}`]={value:null};var Je=()=>z,Ye={BASE_URL:`/hcwa-rebuild/`,DEV:!1,MODE:`production`,PROD:!0,SSR:!1},Xe=import.meta.url;function Ze(e,t,n=!1){let r=`textures/${e}${n?`-n`:``}-${t}.webp`,i=Ye.BASE_URL??`/`;return i.startsWith(`/`)?i+r:new URL(`../${r}`,Xe).href}var Qe=e=>Ke[e>=2?2:1],$e=e=>+(R.names.length*(e?.size??Qe(e))**2*4*4/3/2**20).toFixed(1);function et({data:e,size:n},r){let a=new x(e,n,n,l,i);return a.wrapS=a.wrapT=t,a.minFilter=g,a.magFilter=y,a.generateMipmaps=!0,a.anisotropy=r,a.colorSpace=``,a.needsUpdate=!0,a}function tt(e,t,n){let r=[Ze(e,t),Ze(e,t,!0)];return typeof Worker!=`function`||typeof OffscreenCanvas!=`function`?We(e,t,r,n):new Promise((i,a)=>{let o;try{o=new Worker(new URL(`/hcwa-rebuild/assets/walls-worker-7kSare9d.js`,``+import.meta.url),{type:`module`})}catch{i(We(e,t,r,n));return}let s=()=>{o.terminate(),n?.removeEventListener(`abort`,c)},c=()=>{s(),a(Error(`walls: aborted`))};n?.addEventListener(`abort`,c,{once:!0}),o.onmessage=({data:e})=>{s(),e.error?a(Error(e.error)):i(e)},o.onerror=a=>{s(),a.preventDefault?.(),i(We(e,t,r,n))},o.postMessage({name:e,size:t,urls:r})})}async function nt({renderer:e,tier:t,yieldNow:n,signal:r,size:i=Qe(t)}){let a=t>=2?4:2,o=await Promise.all(R.names.map(e=>tt(e,i,r))),s=[];try{for(let t of o){if(r?.aborted)throw Error(`walls: aborted`);let i=et(t,a);e.initTexture(i),s.push([`uW${Ge[t.name]}`,i]),await n()}}catch(e){for(let[,e]of s)e.dispose();throw e}for(let[e,t]of s)z[e].value=t,t.image.data=null;z.uWallMix.value=+(t>=2);let c=R.names.reduce((e,t)=>e+R.bytes[i][t].reduce((e,t)=>e+t,0),0);return{size:i,kb:Math.round(c/1024),gpuMB:$e({size:i})}}var rt=e=>{z.uWallMix.value=e>=2&&z.uWPl.value?1:0};function it(){for(let e of Object.keys(z))z[e].value?.isTexture&&(z[e].value.dispose(),z[e].value=null);z.uWallK.value=0,z.uWallMix.value=0}function at(e,t,{box:n,tier:r,ortho:i,steps:c=null,mask:l=null}){let f=pe(e),p={uInvP:{value:new d},uInvV:{value:new d},uCam:{value:new s},uMin:{value:new s(...n.min)},uMax:{value:new s(...n.max)},uDensity:{value:1.6},uExposure:{value:ie},uCapScale:{value:4}},m=new h({defines:{STEPS:c??(r>=2?24:16)},uniforms:{...t,...p},vertexShader:A,fragmentShader:`
      varying vec2 vU;
      uniform mat4 uInvP, uInvV; uniform vec3 uCam, uMin, uMax, uSunCol, uWind;
      uniform float uSunI, uDensity, uExposure, uCapScale, uTime;
      uniform sampler2D uLime;
      ${Se}
      ${se}
      ${l?l.glsl+`
#define SHAFT_L uLt[0]`:Ce+le+`
#define SHAFT_L uL`}
      ${ce}
      ${Ee}
      void main(){
        vec4 vp = uInvP * vec4(vU * 2. - 1., 1., 1.); vp /= vp.w;
        vec3 rd = normalize((uInvV * vec4(vp.xyz, 0.)).xyz), ro = uCam;
        vec3 tf = max((uMin - ro) / rd, (uMax - ro) / rd);
        float tExit = min(min(min(tf.x, tf.y), tf.z), 14.);
        float dt = tExit / float(STEPS), j = h21(gl_FragCoord.xy) * dt, acc = 0.;
        for (int i = 0; i < STEPS; i++) {
          vec3 p = ro + rd * (j + dt * float(i));
          float m = ${l?l.call:`sunMask(p)`};
          if (m > 0.) acc += m ${l?``:`* leaf(p, uTime)`} * (0.5 + 0.5 * texture2D(uLime, (vec2(p.x + p.y, p.z) + uWind.xz) * 0.3).r) * dt;
        }
        float g = .45, mu = dot(rd, SHAFT_L), hg = (1. - g * g) / pow(1. + g * g - 2. * g * mu, 1.5) / 12.566;
        vec3 col = uSunCol * uSunI * uDensity * acc * (0.02 + 0.3 * hg) * capAt(gl_FragCoord.xy * uCapScale);
        gl_FragColor = vec4(toSRGB(neutralTM(col, uExposure)), 1.);
      }`,depthTest:!1,depthWrite:!1}),g=new a,_=new h({uniforms:{uTex:{value:f.texture},uTexel:{value:g}},vertexShader:A,fragmentShader:`
      uniform sampler2D uTex; uniform vec2 uTexel; varying vec2 vU;
      vec3 s(vec2 o){ return texture2D(uTex, vU + o * uTexel).rgb; }
      void main(){
        vec3 c = s(vec2(0)) * 4. + (s(vec2(1, 0)) + s(vec2(-1, 0)) + s(vec2(0, 1)) + s(vec2(0, -1))) * 2.
               + s(vec2(1, 1)) + s(vec2(-1, 1)) + s(vec2(1, -1)) + s(vec2(-1, -1));
        gl_FragColor = vec4(c / 16., 1.);
      }`,blending:5,blendEquation:100,blendSrc:209,blendDst:201,transparent:!0,depthTest:!1,depthWrite:!1,toneMapped:!1}),v=de(),y=new o().add(new u(v,m)),b=new o().add(new u(v,_));for(let e of[y,b])e.children[0].frustumCulled=!1;let x=T(r),S=!0;return{scenes:[y,b],get enabled(){return S},set enabled(e){S=e},setTier(e){x=T(e),m.defines.STEPS=c??(e>=2?24:16),m.needsUpdate=!0},setSize(e,t){f.setSize(Math.max(1,Math.ceil(e/x)),Math.max(1,Math.ceil(t/x))),g.set(1/f.width,1/f.height),p.uCapScale.value=e/f.width},get size(){return[f.width,f.height]},render(t){if(!S)return;p.uInvP.value.copy(t.projectionMatrixInverse),p.uInvV.value.copy(t.matrixWorld),p.uCam.value.copy(t.position);let n=e.getRenderTarget();e.setRenderTarget(f),e.render(y,i),e.setRenderTarget(n),e.render(b,i)},uniforms:p,dispose(){me(e),m.dispose(),_.dispose(),v.dispose()}}}var ot={room:{mirror:!1,keys:[[.08,40,24,`sunRoom`],[.2,36,26,`sunPassage`]]},corridor:{mirror:!0,keys:[[.25,8,24,`sunCorridorA`],[.45,-8,28,`sunCorridorB`]]},shutters:{mirror:!1,keys:[[.85,-11,25,`sunShuttersA`],[.95,-16,20,`sunShuttersB`]]}},st=Object.fromEntries(Object.values(ot).flatMap(e=>e.keys.map(e=>[e[3],k(e[3])]))),ct={room:.08},lt={az:6,el:3},ut=new v,dt=new v;function ft(e,t,n,r){let i=ot[e],[a,o]=i.keys,s=f.clamp((t-a[0])/(o[0]-a[0]),0,1),c=f.degToRad(f.lerp(a[1],o[1],s)+(r?.az??0)),l=f.degToRad(f.lerp(a[2],o[2],s)+(r?.el??0));return n.dir.set(Math.cos(l)*Math.cos(c)*(i.mirror?-1:1),Math.sin(l),Math.cos(l)*Math.sin(c)).normalize(),n.color.copy(ut.copy(st[a[3]])).lerp(dt.copy(st[o[3]]),s),n}var pt=()=>({dir:new s,color:new v}),mt={frontal:{az:-18,el:42,color:`sunCorridorA`},ourWork:{az:46,el:24,color:`sunCorridorA`},door:{az:6,el:14,color:`sunCorridorA`,mirror:!0},hub:{az:4,el:15,color:`sunCorridorA`,mirror:!0},gallery:{az:50,el:12,color:`sunCorridorA`,mirror:!0},louvers:{az:50,el:18,color:`sunCorridorA`},shutters:{az:-48,el:16,color:`sunShuttersB`},street:{az:-58,el:20,color:`sunShuttersA`},story:{az:40,el:24,color:`sunRoom`},about:{az:64,el:13,color:`sunCorridorA`},impact:{az:72,el:61,color:`sunCorridorB`}};function ht({az:e,el:t,mirror:n=!1},r=new s){let i=f.degToRad(e),a=f.degToRad(t);return r.set(Math.cos(a)*Math.cos(i)*(n?-1:1),Math.sin(a),Math.cos(a)*Math.sin(i)).normalize()}var gt={from:new s(98.86,1.1,1.5),to:new s(99.7,1.1,1.5),yaw:20,fov:{portrait:64,tablet:58,landscape:52,wide:50}},_t=(e,t=gt.yaw)=>new s(e.x+Math.sin(t*Math.PI/180)*10,1.1,e.z-Math.cos(t*Math.PI/180)*10),vt=1.4;function yt([e,t,n,r]){let i=3*e,a=3*(n-e)-i,o=1-i-a,s=3*t,c=3*(r-t)-s,l=1-s-c,u=e=>((o*e+a)*e+i)*e,d=e=>(3*o*e+2*a)*e+i;return e=>{if(e<=0)return 0;if(e>=1)return 1;let t=e;for(let n=0;n<6;n++){let n=d(t);if(Math.abs(n)<1e-6)break;t-=(u(t)-e)/n}if(t<0||t>1||Math.abs(u(t)-e)>1e-4){let n=0,r=1;for(let i=0;i<24;i++)t=(n+r)/2,u(t)<e?n=t:r=t}return((l*t+c)*t+s)*t}}function bt(e,t,n,r){return e.v+=(r*r*(t-e.x)-2*r*e.v)*n,e.x+=e.v*n,Math.abs(t-e.x)<1e-4&&Math.abs(e.v)<1e-4?(e.x=t,e.v=0,!1):!0}var xt={in:.18,out:.82,at:.47,drift:.06},St=e=>Math.sin(e*Math.PI/2),Ct=e=>1-Math.cos(e*Math.PI/2),B=(e,t,n)=>{let r=Math.min(Math.max((n-e)/(t-e),0),1);return r*r*(3-2*r)},wt=e=>{let t=Math.min(Math.max(e,0),1),n=xt,r=1-n.at-n.drift;return t<n.in?n.at*St(t/n.in):t<n.out?n.at+n.drift*((t-n.in)/(n.out-n.in)):n.at+n.drift+r*Ct((t-n.out)/(1-n.out))},Tt=new Set([`dotTre`,`vomCau`,`hoaThi`]);function Et(e){return[...e].map(e=>{let t=e.dataset??{},n=String(t.unit??``),r=n===`little-rose`;return{unit:n,door:r||t.door===`closed`?`closed`:`open`,emblem:r?`hoaThi`:Tt.has(t.emblem)?t.emblem:`hoaThi`}})}var V={screenX:98.9,wallX:101.3,ceiling:3.1,lead:3,bay:6.5,tail:2,start:3,parapet:.9,rows:7,pier:.32},H={z:1.5,y:1.05},U=.3;function Dt(e){let t=e.length,n=V,r=-(n.bay*t+n.tail),i=e.map((e,t)=>({...e,index:t,zc:-(n.bay*t+n.bay/2),bay:[-(n.bay*(t+1)),-n.bay*t]})),a=H.z-U/2-U*Math.ceil((H.z-U/2-r)/U),o=H.z+U/2+U*Math.floor((n.start-(H.z+U/2))/U);return{n:t,doors:i,end:r,start:n.start,screen:{sx:n.screenX,z0:a,z1:o,y0:n.parapet,y1:n.parapet+n.rows*U},piers:i.map(e=>e.bay[0])}}var W={head:[0,.12],beats:[.12,.92],cardIn:.18,cardOut:.98,release:.95,swing:[.1,.45],sun:[.25,.45]},G={leave:.45,wash:.24,washPhone:.16,end:.12,feather:.6,pitch:14,tilt:[.92,1],hem:.38,hemPhone:.36},Ot=e=>G.leave*Ct(K(e)),kt=e=>.5-.5*Math.cos(Math.PI*e),At=e=>G.pitch*kt(K((e-G.tilt[0])/(G.tilt[1]-G.tilt[0])));function jt(e,t,n,r){let i=(n<600?G.washPhone:G.wash)*r;return{y0:e,len:Math.max(t-G.end*r-e,i)}}function Mt(e,t,n,r){let i=t<600?G.hemPhone:G.hem;return e>=n*(1+i)?(r.x=0,!1):(r.x=1,r.y=1-e/n,r.z=i,r.w=0,!0)}var K=e=>Math.min(Math.max(e,0),1);function Nt(e,t){if(t<=0)return 0;let n=(e-W.beats[0])/q(t)-Pt(e,t);return B(.08,.3,n)*(1-B(.7,.92,n))}var q=e=>(W.beats[1]-W.beats[0])/Math.max(e,1),Pt=(e,t)=>Math.min(Math.max(Math.floor((e-W.beats[0])/q(t)),0),Math.max(t-1,0)),Ft=yt(re.out);function It(e,t,n){let r=K(((t-W.beats[0])/q(n)-e-W.swing[0])/(W.swing[1]-W.swing[0]));return r>1-1e-9?96:96*Ft(r)}function Lt(e,t){if(t<=0)return 0;let n=xt,r=W.head[1],i=q(t),a=Pt(e,t),o=(e-W.beats[0])/i-a,s=.5+n.at,c=s*r/(n.in*i+r);if(e<=r)return c*Ct(K(e/r));let l=Math.min(Math.max(o,0),1);if(a===t-1&&l>=n.out){let r=W.beats[0]+(t-1+n.out)*i,a=K((e-r)/(1-r)),o=t-.5+n.at+n.drift,s=o+.05,c=n.drift/(n.out-n.in)/i*(1-r);return(2*a**3-3*a**2+1)*o+(a**3-2*a**2+a)*c+(3*a**2-2*a**3)*s}let u=wt(l);return a>0||u>=n.at?a+.5+u:c+(s-c)*(u/n.at)}function Rt(e,t,n){let r=H.z,i=(n.doors[0]?.zc??-V.bay/2)+t.d,a=Math.min(i+.05*V.bay,r);return e<=.95?r+(a-r)*(e/.95):a-(e-.95)*V.bay}var zt={side:{x:99.5,d:4.4,yaw:15,fov:50,at:[.8,.52]},below:{x:100.05,d:4.4,yaw:2,fov:78,at:[.64,.44]}},Bt=1.3;function Vt(e,t,n,r,i,a,o){let s=t*Math.PI/180,c=n[0]-e[0],l=n[1]-e[1],u=n[2]-e[2],d=c*Math.sin(s)-u*Math.cos(s),f=c*Math.cos(s)+u*Math.sin(s),p=Math.tan(r*Math.PI/360),m=p*i,h=f/d,g=l/d,_=h-a*2*m,v=g+o*2*p;return{l:_,r:_+2*m,t:v,b:v-2*p}}function Ht(e,t,n,r={pos:[0,0,0],yaw:0,fov:0}){let i=zt[t];return r.pos[0]=i.x,r.pos[1]=1.1,r.pos[2]=Rt(e,i,n),r.yaw=i.yaw,r.fov=i.fov,r}var J={approach:[0,.16],washIn:[.06,.16],plateau:[.16,.78],exit:[.78,1],clear:[.78,.94],read:[.18,.74]},Ut=e=>(1-K((e-J.clear[0])/(J.clear[1]-J.clear[0])))**3,Wt=e=>e<=J.plateau[0]?B(...J.washIn,e):e<=J.plateau[1]?1:Ut(e),Gt=e=>e<(J.plateau[0]+J.plateau[1])/2?`room`:`corridor`,Y={slats:[.12,.5],leaves:[.5,1],flood:.3,unflood:.22,blend:[.28,.62],sun:[.85,.95],push:.45,slatDeg:65,leafDeg:110,floodMs:900,back:1.6},Kt=e=>Y.slatDeg*B(...Y.slats,e),qt=e=>Y.leafDeg*B(...Y.leaves,e),Jt=e=>B(...Y.blend,e),Yt=e=>Y.sun[0]+(Y.sun[1]-Y.sun[0])*K(e/2);function Xt(e,t,n,r=!1){!e.on&&t>=Y.flood?e.on=!0:e.on&&t<Y.unflood&&(e.on=!1);let i=+!!e.on;if(e.v===i)return!1;if(r)return e.v=i,!0;let a=n*1e3/Y.floodMs*(e.on?1:Y.back);return e.v=e.on?Math.min(e.v+a,1):Math.max(e.v-a,0),!0}var Zt=e=>1-(1-e)**4;function Qt(e,t={peak:0,held:0}){return t.peak=e<.3?Zt(e/.3):1-B(.3,1,e),t.held=B(0,.5,e),t}var $t=(e,t=Kt(e))=>.25*(t/Y.slatDeg)+.75*B(...Y.leaves,e),en=(e,t)=>1.22+.33*B(...Y.leaves,e)+.05*t.held+.15*t.peak,tn={portrait:{fov:72,pos:[[-5.93,-.9],[-4.4,-.45],[-4.15,-.35],[-3.95,-.22],[-3.85,-.06],[-3.8,0]],look:[[-.4,.55,.15],[-.38,.6,.12],[-.35,.62,.1],[-.2,.72,.05],[0,.95,0],[0,1.05,0]]},tablet:{fov:54,pos:[[-5.8,-1.65],[-2.75,-.95],[-2.55,-.75],[-2.3,-.42],[-1.95,-.12],[-1.5,0]],look:[[-.5,.55,.35],[-.42,.6,.18],[-.38,.62,.12],[-.25,.72,.08],[0,.93,.03],[0,1.04,0]]},landscape:{fov:48,pos:[[-5.43,-2.55],[-2.56,-1.15],[-2.4,-.9],[-2.2,-.5],[-1.9,-.15],[-1.6,0]],look:[[-.5,.55,.45],[-.4,.6,.12],[-.35,.62,.1],[-.2,.72,.05],[0,.95,.03],[0,1.05,0]]},wide:{fov:46,pos:[[-5.43,-2.55],[-2.56,-1.15],[-2.4,-.9],[-2.2,-.5],[-1.9,-.15],[-1.6,0]],look:[[-.5,.58,.45],[-.4,.62,.12],[-.35,.64,.1],[-.2,.74,.05],[0,.96,.03],[0,1.06,0]]}},nn=[0,Y.push,.58,.78,Y.leaves[1],2];function rn(e,t,n){let r=e.length,i=Math.min(Math.max(e.findLastIndex(e=>e<=n),0),r-2),a=n=>(t[n+1]-t[n])/(e[n+1]-e[n]),o=t=>{if(t===0)return a(0);if(t===r-1)return a(r-2);let n=a(t-1),i=a(t);return n*i<=0?0:3*(e[t+1]-e[t-1])/((2*e[t+1]-e[t]-e[t-1])/n+(e[t+1]+e[t]-2*e[t-1])/i)},s=e[i+1]-e[i],c=K((n-e[i])/s),l=c*c,u=l*c;return(2*u-3*l+1)*t[i]+(u-2*l+c)*s*o(i)+(-2*u+3*l)*t[i+1]+(u-l)*s*o(i+1)}var an=(e,t)=>e.map(e=>e[t]);function on(e,t,n,r,i={pos:[0,0,0],look:[0,0,0],fov:0}){let a=tn[n]??tn.landscape,o=K(e)+K(t);i.pos[0]=rn(nn,an(a.pos,0),o),i.pos[1]=1.1,i.pos[2]=r+rn(nn,an(a.pos,1),o);for(let e=0;e<3;e++)i.look[e]=rn(nn,an(a.look,e),o)+(e===2?r:0);return i.fov=a.fov,i}var X={w:.95,h:2.2,t:.04,frame:.06,reveal:.14},sn={"green-bamboo":`dotTre`,"cau-han":`vomCau`,"little-rose":`hoaThi`};function cn(e){let t=e===`little-rose`;return{transomPattern:t?`open`:sn[e]??`hoaThi`,spillTint:t?0:.12,corridorSun:!t}}function ln({wx:e,zc:t,unit:n,open:r=72,hinge:i=1,inward:a=!1}){let o=n===`little-rose`,s=X.w/2,c=X.h+X.frame,l={z0:t-1.5*M,z1:t+1.5*M,y0:c+.04,y1:c+.04+M};return{wx:e,zc:t,unit:n,hinge:i,swing:a?-1:1,open:o?0:r,emblem:sn[n]??`hoaThi`,holes:[[t-s-X.frame,t+s+X.frame,0,c],[l.z0,l.z1,l.y0,l.y1]],doorway:{z0:t-s,z1:t+s,y0:0,y1:X.h},transom:l}}function un(e,t=[0,0,0]){let n=e.getAttribute(`position`),r=new Float32Array(n.count*3);for(let e=0;e<n.count;e++)r[e*3]=n.getX(e)+t[0],r[e*3+1]=n.getY(e)+t[1],r[e*3+2]=n.getZ(e)+t[2];return e.setAttribute(`aLeafP`,new b(r,3)),e}function dn(e){let t=e.getAttribute(`position`).count,n=new Float32Array(t*3);for(let e=0;e<t;e++)n[e*3+1]=-1;return e.setAttribute(`aLeafP`,new b(n,3)),e}function fn(e){let t=new p,n=Object.keys(e[0].attributes),r=[],i=0;for(let t of e){for(let e of t.index.array)r.push(e+i);i+=t.attributes.position.count}for(let r of n){let n=e[0].attributes[r].itemSize,a=new Float32Array(i*n),o=0;for(let t of e)a.set(t.attributes[r].array,o),o+=t.attributes[r].array.length;t.setAttribute(r,new b(a,n))}return t.setIndex(r),t}function pn(e){let{wx:t,zc:n}=e,r=X.w/2,i=X.frame,a=X.reveal,o=(e,t,n,r,i,a)=>new _(e,t,n).translate(r,i,a),s=dn(fn([o(a,X.h+i,i,t+a/2-.02,(X.h+i)/2,n-r-i/2),o(a,X.h+i,i,t+a/2-.02,(X.h+i)/2,n+r+i/2),o(a,i,X.w+2*i,t+a/2-.02,X.h+i/2,n)])),c=e.hinge??1,l=[o(X.t,X.h,X.w,0,X.h/2,-c*r)];for(let e of[-1,1])for(let[t,n]of[[.54,.84],[1.6,.96]])for(let i of[-.24,.24])l.push(o(.012,n,.34,e*(X.t/2+.004),t,-c*r+i));return{frame:s,leaf:un(fn(l)),hinge:[t+.03,0,n+c*r],side:c}}var mn={hoaThi:`return sdHoaThi(mod(uv, B) - 0.5 * B);`,hoaThiOne:`vec2 id = floor(uv / B); return all(equal(id, p.xy)) ? sdHoaThi(mod(uv, B) - 0.5 * B) : 1.;`,hoaThiMissing:`vec2 c = mod(uv, B) - 0.5 * B; float d = sdHoaThi(c); return all(equal(floor(uv / B), p.xy)) ? min(d, sdBox2(c, vec2(0.5 * B - 0.004))) : d;`,dotTre:`return sdDotTre(mod(uv, B) - 0.5 * B);`,vomCau:`return sdVomCau(mod(uv, B) - 0.5 * B);`,open:`return -1.;`,fans:`return -fanBlades(uv);`,solid:`return 1.;`,slats:`float h = 0.5 * p.x; return abs(mod(uv.y, p.x) - h) - h * p.y;`,canopy:`vec2 q = mat2(.8, -.6, .6, .8) * uv * p.x; float n = 0.62 * fbm(q + p.zw) + 0.38 * vn(mat2(.5, .87, -.87, .5) * q * 3.7 + p.wz); return (n - p.y) * 0.5 / p.x;`};function hn(e){let t=0,n=[],r=[];for(let[i,a]of e.entries()){if(a.glsl&&n.push(a.glsl),a.mask){r.push(`float lightMask${i}(vec3 P){ vec3 L = uLt[${i}]; return (${a.mask})${a.factor?` * (${a.factor})`:``}; }`);continue}let e=[],o=[];for(let r of a.apertures){let i=t++;n.push(`float hole${i}(vec2 uv, vec4 p){ ${mn[r.pattern]} }
float ap${i}(vec3 P, vec3 L){
  vec3 C = uApC[${i}], U = uApU[${i}], V = uApV[${i}], N = cross(U, V);
  float ln = dot(L, N), pn = dot(P - C, N), side = sign(ln);
  if (abs(ln) < 1e-4 || pn * side > -0.001) return 1.;
  vec2 size = uApS[${i}].xy;
  float m = 1.;
  for (int f = 0; f < ${r.thin?1:2}; f++) {
    float t = (float(f) * uApS[${i}].z * side - pn) / ln;
    vec3 Q = P + L * t;
    vec2 uv = vec2(dot(Q - C, U), dot(Q - C, V));
    float w = 0.0025 + 0.0093 * max(t, 0.) * ${r.penumbra==null?`0.6`:(.6*r.penumbra).toFixed(4)};
    float rect = sdBox2(uv - 0.5 * size, 0.5 * size);
    float h = hole${i}(uv, uApP[${i}]);
    ${r.feather?r.mode===`occluder`?`m *= mix(1., smoothstep(w, -w, h), smoothstep(uApS[${i}].w, -uApS[${i}].w, rect));`:`m *= smoothstep(w, -w, h) * smoothstep(uApS[${i}].w, -uApS[${i}].w, rect);`:`m *= smoothstep(w, -w, ${r.mode===`occluder`?`min(-rect, h)`:`max(rect, h)`});`}
  }
  return m;
}`),(r.mode===`occluder`?o:e).push(`ap${i}(P, L)`)}let s=e.length?e.reduce((e,t)=>`max(${e}, ${t})`):`1.`;r.push(`float lightMask${i}(vec3 P){ vec3 L = uLt[${i}]; return ${[s,...o,...a.factor?[`(${a.factor})`]:[]].join(` * `)}; }`)}return`
#define B ${M.toFixed(4)}
uniform vec3 uApC[8], uApU[8], uApV[8];
uniform vec4 uApS[8], uApP[8];
uniform vec3 uLt[2];
${n.join(`
`)}
${r.join(`
`)}
#undef B
`}function gn(e,t){let r=e=>Array.from({length:8},e),i={uApC:{value:r(()=>new s)},uApU:{value:r(()=>new s(1,0,0))},uApV:{value:r(()=>new s(0,1,0))},uApS:{value:r(()=>new n)},uApP:{value:r(()=>new n)},uLt:{value:t}},a=0;for(let t of e)for(let e of t.apertures??[]){let t=a++;i.uApC.value[t].set(...e.corner),i.uApU.value[t].set(...e.u).normalize(),i.uApV.value[t].set(...e.v).normalize(),i.uApS.value[t].set(e.size[0],e.size[1],e.depth??0,e.feather??0),i.uApP.value[t].set(...e.params??[0,0,0,0])}if(a>8)throw Error(`apertures: ${a} > 8`);return i}var _n=[{id:`tet-hoa-mai`,word:null,inks:[`tile-ochre`,`tile-ochre-lit`,`sun-300`,`tile-jade`,`ink-900`],atlas:0,what:`A hoa mai branch across a pale sun disc, flower-market baskets at its foot`},{id:`ca-phe-phin`,word:null,inks:[`world-wood`,`tile-cream`,`tile-indigo`],atlas:1,what:`A phin dripping into a glass, a low stool, steam`},{id:`cub-duoi-hang-me`,word:null,inks:[`tile-jade`,`sun-200`,`tile-indigo`,`tile-cream`],atlas:2,what:`A Cub-type step-through (leg shield, small wheels; no badge, no plate) under a tamarind's fine pinnate leaves, dappled light`},{id:`ao-sen`,word:null,inks:[`tile-jade`,`tile-jade-lit`,`tile-cream`,`tile-indigo-lit`,`tile-indigo`,`sun-300`,`sun-200`],atlas:3,what:`Round lotus leaves raised above the water, an apricot-hearted flower, a bud, a seed pod, ripples, a dragonfly`},{id:`pho-mua`,word:null,inks:[`tile-indigo`,`lime-300`,`sun-200`,`tile-cream`],atlas:4,what:`Rain over shophouse shutters, reflections, one bicycle`},{id:`dieu-dong-lua`,word:null,inks:[`tile-jade`,`tile-jade-lit`,`sun-300`,`tile-indigo`,`tile-cream`,`ink-900`],atlas:5,what:`One kite high over terraced rice; its string runs off the edge, no flyer`},{id:`xe-banh-mi`,word:null,inks:[`tile-ochre`,`tile-cream`,`ink-900`],atlas:null,what:`A bánh mì cart with a glass case and a parasol, no lettering`},{id:`cua-hieu`,word:null,inks:[`tile-indigo`,`lime-300`,`sun-200`,`tile-ochre`,`tile-jade`],atlas:null,what:`A folding shophouse shutter half up, a potted plant and a stool`}],Z={cols:3,rows:2,sizes:{512:{cell:[168,235],gutter:2},1024:{cell:[336,470],gutter:4},2048:{cell:[672,940],gutter:8}},budgetKB:{512:34,1024:100,2048:240},margin:[36/672,50/940]},vn=[Math.round(672*(1-2*Z.margin[0])),Math.round(940*(1-2*Z.margin[1]))],yn=({tier:e,width:t})=>t<600||e<2?512:1024,bn=e=>+(e*e*4*4/3/2**20).toFixed(1);function xn(e,t=1024){let n=Z.sizes[t],r=e%Z.cols,i=Math.floor(e/Z.cols),a=r*(n.cell[0]+n.gutter),o=i*(n.cell[1]+n.gutter);return[a/t,1-(o+n.cell[1])/t,(a+n.cell[0])/t,1-o/t]}var Sn=[{id:`cub-duoi-hang-me`,at:`end`,w:.76,x:100.43,y:1.7},{id:`tet-hoa-mai`,at:`end`,w:.5,x:99.47},{id:`dieu-dong-lua`,unit:`green-bamboo`,w:.6,off:1.8},{id:`ao-sen`,unit:`cau-han`,w:.62,off:1.9}],Cn=e=>e*vn[1]/vn[0];function wn(e){let t=e.doors.find(e=>e.unit===`little-rose`),n=e.doors.indexOf(t),r=[];for(let i of Sn){let a,o=`door`;if(i.at===`end`)a=e.end,o=`end`;else{let r=e.doors.find(e=>e.unit===i.unit);if(!r)continue;let o=t&&n<r.index?-1:1;a=r.zc+o*i.off}let s=i.w,c=Cn(s),l=o===`end`?0:s/2;t&&Math.abs(a-t.zc)-l<=4.75||r.push({id:i.id,wall:o,x:i.x??null,z:a,y:i.y??1.6,w:s,h:c})}return r}var Tn=[{id:`tet-hoa-mai`,dz:-1.75,y:1.55,w:.5},{id:`ca-phe-phin`,dz:1.6,y:1.35,w:.36}],Q={uPosterTex:{value:null},uPosterK:{value:0}},En=()=>Q,$=null;async function Dn({size:t=1024,signal:n}={}){if($?.size===t)return $;let r=Ze(`wall-posters`,t),i=await fetch(r,{signal:n,priority:`low`});if(!i.ok)throw Error(`wall posters: ${i.status} ${r}`);let a=await i.blob(),o=await createImageBitmap(a,{imageOrientation:`flipY`,premultiplyAlpha:`none`,colorSpaceConversion:`none`}),s=new m(o);return s.flipY=!1,s.colorSpace=e,s.minFilter=g,s.magFilter=y,s.generateMipmaps=!0,s.anisotropy=4,s.needsUpdate=!0,Q.uPosterTex.value?.dispose?.(),Q.uPosterTex.value=s,$={size:t,texture:s,kb:Math.round(a.size/1024)},$}function On(){Q.uPosterTex.value?.dispose?.(),Q.uPosterTex.value=null,Q.uPosterK.value=0,$=null}var kn=`
attribute vec4 aCell; // the print's cell in the atlas (uv rect)
attribute vec4 aCurl; // corner (uv), lift in metres, reach (fraction of the shorter side)
attribute vec2 aSize; // the sheet's size, m
varying vec3 vP; varying vec3 vN; varying vec2 vUv; varying float vCurl; varying vec4 vCell;
void main(){
  vec2 d = (uv - aCurl.xy) * aSize;
  float k = 1. - smoothstep(0., aCurl.w * min(aSize.x, aSize.y), length(d));
  vCurl = k * k;
  vec3 p = position + normal * (aCurl.z * vCurl);
  vec4 w = modelMatrix * vec4(p, 1.);
  vP = w.xyz; vN = normalize(mat3(modelMatrix) * normal); vUv = uv; vCell = aCell;
  gl_Position = projectionMatrix * viewMatrix * w;
}`;function An(e,t,n){return new h({uniforms:{...e,...Q,...n},vertexShader:kn,fragmentShader:`
      varying vec3 vP; varying vec3 vN; varying vec2 vUv; varying float vCurl; varying vec4 vCell;
      uniform sampler2D uPosterTex;
      uniform float uPosterK, uShadeK, uRef[2], uI[2];
      uniform vec3 uSunTint, uSpillTint;
      ${Se}
      ${se}
      ${hn(t)}
      void main(){
        vec4 t = texture2D(uPosterTex, mix(vCell.xy, vCell.zw, vUv));
        float a = t.a * uPosterK;
        if (a < .004) discard;
        vec3 albedo = t.rgb, N = normalize(vN);
        float sky = 0.82 + 0.18 * N.y;
        vec3 shade = albedo * uShadeK * sky;
        float n0 = max(dot(N, uLt[0]), 0.), n1 = max(dot(N, uLt[1]), 0.);
        float f0 = n0 > 0. && uI[0] > 0. ? n0 / uRef[0] * lightMask0(vP) * uI[0] : 0.;
        ${t.length>1?`float f1 = n1 > 0. && uI[1] > 0. ? n1 / uRef[1] * lightMask1(vP) * uI[1] : 0.;`:`float f1 = 0.;`}
        vec3 col = mix(shade, albedo * uSunTint, min(f0, 1.));
        col = mix(col, albedo * uSpillTint, min(f1, 1.) * .85);
        col *= 1. - .1 * vCurl;
        gl_FragColor = vec4(col, a);
        #include <colorspace_fragment>
      }`,transparent:!0,depthWrite:!1,toneMapped:!1})}var jn=e=>{let t=Math.sin(e*127.1+311.7)*43758.5453;return t-Math.floor(t)};function Mn({id:e,w:t,at:n,rotY:r=-Math.PI/2,seed:i=1}){let a=_n.find(t=>t.id===e);if(!a||a.atlas==null)throw Error(`wall poster ${e}: not in the atlas`);let[o,s]=Z.margin,l=t/(1-2*o),[u,d]=Z.sizes[2048].cell,f=l*d/u,p=new c(l,f,8,10),m=p.getAttribute(`position`).count,h=(e,t)=>new b(new Float32Array(Array.from({length:m},()=>t).flat()),e);p.setAttribute(`aCell`,h(4,xn(a.atlas,2048))),p.setAttribute(`aCurl`,h(4,[jn(i)<.5?o:1-o,s,.01+.008*jn(i+1),.32])),p.setAttribute(`aSize`,h(2,[l,f]));let g=new w;return g.position.set(...n),g.rotation.set(0,r,(jn(i+2)-.5)*2.4*Math.PI/180,`YXZ`),g.updateMatrix(),p.applyMatrix4(g.matrix),p}function Nn(e,t,n,r){if(!r.length)return null;let i=new u(fn(r.map(Mn)),An(e,t,n));return i.renderOrder=2,i.name=`posters:${r.map(e=>e.id).join(`,`)}`,i}export{pt as $,qt as A,ie as At,At as B,Jt as C,ve as Ct,Qt as D,ue as Dt,Nt as E,de as Et,on as F,yt as G,Lt as H,Yt as I,vt as J,bt as K,Gt as L,Et as M,k as Mt,en as N,ae as Nt,Xt as O,le as Ot,$t as P,re as Pt,mt as Q,Kt as R,G as S,_e as St,It as T,ge as Tt,Wt as U,Ht as V,jt as W,lt as X,_t as Y,ct as Z,Bt as _,Oe as _t,Tn as a,nt as at,zt as b,M as bt,bn as c,Ie as ct,X as d,Me as dt,ft as et,pn as f,Pe as ft,V as g,De as gt,fn as h,Ee as ht,Nn as i,it,Vt as j,oe as jt,Mt as k,se as kt,gn as l,Ne as lt,ln as m,ke as mt,Dn as n,at as nt,yn as o,rt as ot,cn as p,je as pt,gt as q,En as r,qe as rt,wn as s,Je as st,On as t,ht as tt,hn as u,Fe as ut,J as v,Ce as vt,Dt as w,A as wt,W as x,Se as xt,Y as y,we as yt,Ot as z};