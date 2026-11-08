import { Vector3 } from "three";

// Where the corridor and the shutters' window are, for the camera paths (flight.js, walk.js, live.js), without
// the sets themselves: live.js loads sets/corridor.js and sets/shutters.js in their own idle-time chunks.

// The camera coming out of the corridor screen (D6.2 K3 → K4): from inside the exit hole to 0.9 m into
// the corridor, looking down it with 35° of yaw toward the doors. FOV per frame shape.
export const EXIT = { from: new Vector3(98.86, 1.1, 1.5), to: new Vector3(99.7, 1.1, 1.5), yaw: 20, fov: { portrait: 64, tablet: 58, landscape: 52, wide: 50 } };
export const exitLook = (pos, yaw = EXIT.yaw) => new Vector3(pos.x + Math.sin((yaw * Math.PI) / 180) * 10, 1.1, pos.z - Math.cos((yaw * Math.PI) / 180) * 10);

// The shutters' window: in the x = 0 wall of S3's room, its centre at z = WIN_Z.
export const WIN_Z = 1.4;
