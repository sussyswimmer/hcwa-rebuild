// Little Rose's bay holds none of the props (proposal saigon-home): every vertex of every fan, pot, plant,
// blossom, and every leaflet's whole drift (spawn z ± 0.3 m sway, ± its half-length), lies outside the bay,
// and no fan's shadow disc (radius + 0.1 m) reaches it. Run: node lr-props-check.mjs (from prototype/).
import { buildProps, FAN } from "./src/world/objects/props.js";
import { CORRIDOR, corridorLayout } from "./src/world/journey.js";
const orders = [["green-bamboo", "little-rose", "cau-han"], ["little-rose", "green-bamboo", "cau-han"], ["cau-han", "green-bamboo", "little-rose"]];
let bad = 0;
for (const units of orders) {
  const beats = units.map((unit) => ({ unit, door: unit === "little-rose" ? "closed" : "open", emblem: "hoaThi" }));
  const lay = corridorLayout(beats), lr = lay.doors.find((d) => d.unit === "little-rose");
  const [z0, z1] = lr.bay;
  const { geometry, fans } = buildProps({ C: CORRIDOR, lay, specs: lay.doors, leaflets: 32 });
  const pos = geometry.getAttribute("position"), piv = geometry.getAttribute("aPivot"), k = geometry.getAttribute("aK");
  let n = 0;
  for (let i = 0; i < pos.count; i++) {
    const kind = k.getX(i);
    const z = kind === 3 ? piv.getZ(i) : pos.getZ(i), reach = kind === 3 ? 0.32 : kind === 1 ? FAN.r : 0;
    if (z + reach > z0 && z - reach < z1) n++;
  }
  for (const f of fans) if (f.z + FAN.r + 0.1 > z0 && f.z - FAN.r - 0.1 < z1) n++;
  console.log(units.join(" / "), "LR bay", z0, z1, n === 0 ? "PASS" : `FAIL ${n}`);
  bad += n;
}
process.exit(bad ? 1 : 0);
