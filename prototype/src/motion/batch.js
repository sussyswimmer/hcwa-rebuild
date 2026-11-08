// The scroll handlers only read; what they write (custom properties, classes) waits for one batch at the
// next frame, after every read, so a native scroll event never forces a layout between ScrollTrigger's
// writes and the next read (fixing-motion-performance: no read after write in a scroll handler).
const queue = new Set();
let pending = 0;
const flush = () => {
  pending = 0;
  for (const fn of queue) { queue.delete(fn); fn(); }
};
export function write(fn) {
  queue.add(fn);
  if (!pending) pending = requestAnimationFrame(flush);
}
