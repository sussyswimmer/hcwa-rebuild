import { Color, Vector3 } from "three";
import { color, world } from "./tokens.js";

// The renderer's exposure (05-DESIGN D6.3). Surfaces, screen, street and motes are drawn as
// display targets from the world-* tokens; the shafts tone-map themselves with this value.
export const EXPOSURE = 1.22;

// Token → linear working-space color (three converts sRGB hex on construction).
export const tok = (name) => new Color(world[name] ?? color[name]);
export const vec = (c) => new Vector3(c.r, c.g, c.b);
export const mixc = (a, b, t) => a.clone().lerp(b, t);
