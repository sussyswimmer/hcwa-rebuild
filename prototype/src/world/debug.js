import { readTextRects } from "./frame.js";

// QA hooks for the live world (?debug, prototype only): window.__hcwaWorld, read by the check scripts and the
// Playwright probes. Loaded as its own chunk, so production pages never carry it.
export function attach(w) {
  const { gl } = w;
  const ext = gl.renderer.getContext().getExtension("WEBGL_lose_context");
  window.__hcwaWorld = {
    room: w.room, corridor: w.corridor, tier: w.tier, frame: w.frame,
    flight: () => ({ ...w.solved(), at: { ...w.at }, path: { ...w.path }, span: JSON.parse(JSON.stringify(w.span)), set: w.inRoom() ? "room" : "corridor", walking: w.walking(), walk: w.walk.layout, lens: w.walk.lens }),
    table: () => w.flight.table?.rows, rects: () => readTextRects(w.section), parallax: w.parallax, shutters: w.shutters,
    // Freeze the flood at tween position v (QA frames of its peak on a slow GPU); null lets it run again.
    flood: w.hold, memoryMB: () => w.memoryMB(), memory: () => ({ ...gl.memory, plan: w.plan(), cap: Number.isFinite(gl.cap) ? gl.cap : null }), msaa: gl.msaa, info: () => ({ ...gl.renderer.info.render }), redraw: w.redraw,
    lose: () => ext?.loseContext(), restore: () => ext?.restoreContext(), paused: w.paused, running: w.running, pointer: w.pointer,
    // The Blender wall maps' strength (0: the procedural walls), for before/after captures and the perf proxy.
    wallK: w.wallK,
    // The live S3 set (M7), for tuning frames of the street outside: its uniforms, passes and outside material.
    s3Set: w.s3Set,
  };
}
