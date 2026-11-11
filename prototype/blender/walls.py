"""Bake seamless, physically based wall textures for the world (Maxwell, 11 Nov 2026: "the walls look like
play-doh; use Blender to render a high quality wall with textures").

Run headless:  blender -b --python prototype/blender/walls.py -- <out_dir> [size] [name,name,...]

Every pattern is evaluated on a torus in 4D noise space (u, v in [0, 1] -> cos/sin of both), so each map tiles
seamlessly in both directions with no visible repeat seam. Each material is baked with Cycles to:
  <name>-albedo.png    sRGB colour (the material's real colour; the world divides by its mean to get relief
                       of tone, so the house tokens still set the hue)
  <name>-normal.png    tangent-space normal map (OpenGL, +Y up) from the material's height
  <name>-rough.png     roughness (linear)
Materials: plaster (the hero room's white lime plaster), limewash (the corridor's Saigon yellow wash),
dado (mint oil paint over plaster, chipped), paint (an old door's painted wood, neutral, tinted per unit in
the shader), concrete (cast breeze block). Nothing here depicts a person or text.
"""
import math
import os
import sys

import bpy

argv = sys.argv[sys.argv.index("--") + 1:] if "--" in sys.argv else []
OUT = os.path.abspath(argv[0] if argv else "blender-out")
SIZE = int(argv[1]) if len(argv) > 1 else 1024
os.makedirs(OUT, exist_ok=True)

bpy.ops.wm.read_factory_settings(use_empty=True)
scene = bpy.context.scene
scene.render.engine = "CYCLES"
scene.cycles.device = "CPU"
scene.cycles.samples = 4
scene.render.bake.margin = 0

bpy.ops.mesh.primitive_plane_add(size=1)
plane = bpy.context.active_object


# ---------------------------------------------------------------- node helpers
class G:
    def __init__(self, mat):
        self.nt = mat.node_tree
        self.n = self.nt.nodes
        self.l = self.nt.links
        for node in list(self.n):
            self.n.remove(node)
        self.x = 0

    def node(self, kind, **props):
        nd = self.n.new(kind)
        nd.location = (self.x, 0)
        self.x += 200
        for k, v in props.items():
            if k.startswith("in_"):
                nd.inputs[k[3:].replace("_", " ")].default_value = v
            else:
                setattr(nd, k, v)
        return nd

    def link(self, a, b):
        self.l.new(a, b)
        return b

    def math(self, op, a, b=None, clamp=False):
        m = self.node("ShaderNodeMath", operation=op, use_clamp=clamp)
        for i, v in enumerate((a, b)):
            if v is None:
                continue
            if isinstance(v, (int, float)):
                m.inputs[i].default_value = v
            else:
                self.link(v, m.inputs[i])
        return m.outputs[0]

    def mix(self, fac, a, b, blend="MIX"):
        m = self.node("ShaderNodeMix", data_type="RGBA", blend_type=blend)
        for sock, v in ((m.inputs[0], fac), (m.inputs[6], a), (m.inputs[7], b)):
            if isinstance(v, (tuple, list)):
                sock.default_value = v if len(v) == 4 else (*v, 1)
            elif isinstance(v, (int, float)):
                sock.default_value = v
            else:
                self.link(v, sock)
        return m.outputs[2]

    def ramp(self, src, stops):
        r = self.node("ShaderNodeValToRGB")
        cr = r.color_ramp
        cr.interpolation = "EASE"
        while len(cr.elements) < len(stops):
            cr.elements.new(0.5)
        for el, (pos, col) in zip(cr.elements, stops):
            el.position = pos
            el.color = col if len(col) == 4 else (*col, 1)
        self.link(src, r.inputs[0])
        return r.outputs[0]


def torus(g, freq, fv=None):
    """A 4D point on a torus for the plane's UV: (cos u, sin u, cos v, sin v) * freq / (2 pi), so a noise of
    scale 1 shows about `freq` features across one tile and wraps seamlessly."""
    tc = g.node("ShaderNodeTexCoord")
    sep = g.node("ShaderNodeSeparateXYZ")
    g.link(tc.outputs["UV"], sep.inputs[0])
    parts = []
    for comp in (0, 1):
        R = (freq if comp == 0 or fv is None else fv) / (2 * math.pi)
        ang = g.math("MULTIPLY", sep.outputs[comp], 2 * math.pi)
        parts.append(g.math("MULTIPLY", g.math("COSINE", ang), R))
        parts.append(g.math("MULTIPLY", g.math("SINE", ang), R))
    comb = g.node("ShaderNodeCombineXYZ")
    for i in range(3):
        g.link(parts[i], comb.inputs[i])
    return comb.outputs[0], parts[3]


SEED = [0.0]  # per-material offset in noise space, so the dado and the wash above it do not share a pattern


def noise(g, freq, detail=6.0, rough=0.55, distortion=0.0, offset=0.0, kind="FBM", fv=None):
    vec, w = torus(g, freq, fv)
    nz = g.node("ShaderNodeTexNoise", noise_dimensions="4D", noise_type=kind, in_Scale=1.0, in_Detail=detail, in_Roughness=rough, in_Distortion=distortion)
    g.link(vec, nz.inputs["Vector"])
    g.link(g.math("ADD", w, offset + SEED[0]), nz.inputs["W"])
    return nz.outputs["Fac"]


def cracks(g, freq, width, sparsity, offset=0.0):
    """Hairline cracks: Voronoi edges, warped, thin, and only where a low-frequency mask allows."""
    vec, w = torus(g, freq)
    warp = g.node("ShaderNodeTexNoise", noise_dimensions="4D", in_Scale=2.2, in_Detail=4.0)
    g.link(vec, warp.inputs["Vector"])
    g.link(g.math("ADD", w, offset + 7.0), warp.inputs["W"])
    wv = g.node("ShaderNodeMix", data_type="VECTOR", blend_type="MIX")
    wv.inputs[0].default_value = 0.35
    g.link(vec, wv.inputs[4])
    g.link(warp.outputs["Color"], wv.inputs[5])
    vor = g.node("ShaderNodeTexVoronoi", voronoi_dimensions="4D", feature="DISTANCE_TO_EDGE", in_Scale=1.0)
    g.link(wv.outputs[1], vor.inputs["Vector"])
    g.link(g.math("ADD", w, offset + SEED[0]), vor.inputs["W"])
    line = g.math("LESS_THAN", vor.outputs["Distance"], width)
    soft = g.math("SUBTRACT", 1.0, g.math("DIVIDE", vor.outputs["Distance"], width * 2.5), clamp=True)
    line = g.math("MAXIMUM", line, g.math("MULTIPLY", soft, 0.4))
    mask = g.math("GREATER_THAN", noise(g, freq * 0.18, detail=2.0, offset=offset + 3.0), sparsity)
    return g.math("MULTIPLY", line, mask)


def output(g, albedo, height, rough, bump_strength):
    bsdf = g.node("ShaderNodeBsdfPrincipled")
    bump = g.node("ShaderNodeBump", in_Strength=bump_strength, in_Distance=0.02)
    g.link(height, bump.inputs["Height"])
    g.link(bump.outputs["Normal"], bsdf.inputs["Normal"])
    g.link(albedo, bsdf.inputs["Base Color"])
    g.link(rough, bsdf.inputs["Roughness"])
    emit = g.node("ShaderNodeEmission")
    out = g.node("ShaderNodeOutputMaterial")
    g.link(bsdf.outputs[0], out.inputs["Surface"])
    return bsdf, emit, out


def rgb(hexstr):
    h = hexstr.lstrip("#")
    lin = lambda c: (c / 12.92) if c <= 0.04045 else ((c + 0.055) / 1.055) ** 2.4
    return tuple(lin(int(h[i:i + 2], 16) / 255) for i in (0, 2, 4)) + (1.0,)


# ---------------------------------------------------------------- materials
def plaster(g, base, light, dark, stain, crack_freq=3.0, crack_w=0.007, rough=0.9, chips=None):
    """Lime plaster: a patchy wash (brush laps), mottling, trowel marks, fine grit, hairline cracks, damp
    stains and scuffs."""
    laps = noise(g, 2.2, detail=3.0, rough=0.5, distortion=0.6, offset=1.0)
    mott = noise(g, 9.0, detail=8.0, rough=0.62, offset=2.0)
    grit = noise(g, 140.0, detail=2.0, rough=0.5, offset=3.0)
    trowel = noise(g, 4.0, detail=2.0, distortion=2.4, offset=4.0, kind="RIDGED_MULTIFRACTAL")
    tone = g.math("ADD", g.math("MULTIPLY", laps, 0.6), g.math("MULTIPLY", mott, 0.4))
    col = g.ramp(tone, [(0.30, dark), (0.52, base), (0.72, light)])
    # damp stains: thin tide marks of a darker, warmer tone where a slow noise crosses a band
    st = noise(g, 1.6, detail=4.0, distortion=1.2, offset=5.0)
    band = g.ramp(st, [(0.60, (0, 0, 0, 1)), (0.66, (1, 1, 1, 1)), (0.70, (0.25, 0.25, 0.25, 1)), (0.78, (0, 0, 0, 1))])
    col = g.mix(g.math("MULTIPLY", band_bw(g, band), 0.55), col, stain)
    # scuffs: short dark streaks
    sc = noise(g, 30.0, detail=1.0, distortion=4.0, offset=6.0, kind="RIDGED_MULTIFRACTAL")
    scuff = g.math("POWER", g.math("SUBTRACT", sc, 0.55, clamp=True), 1.0)
    col = g.mix(g.math("MULTIPLY", scuff, 0.5), col, dark)
    cr = cracks(g, crack_freq, crack_w, 0.57, offset=8.0)
    col = g.mix(g.math("MULTIPLY", cr, 0.6), col, tuple(c * 0.55 for c in dark[:3]) + (1,))
    # rain and grime streaks running down the wall (stretched vertically), and soft repaint patches
    streak = noise(g, 34.0, detail=3.0, rough=0.5, offset=9.0, fv=1.6)
    col = g.mix(g.math("MULTIPLY", g.math("SUBTRACT", streak, 0.55, clamp=True), 1.1), col, stain)
    patch = noise(g, 3.0, detail=1.0, rough=0.3, offset=10.0)
    col = g.mix(g.math("MULTIPLY", g.math("GREATER_THAN", patch, 0.62), 0.22), col, light)
    height = g.math("ADD", g.math("MULTIPLY", grit, 0.25), g.math("ADD", g.math("MULTIPLY", trowel, 0.35), g.math("MULTIPLY", laps, 0.2)))
    height = g.math("SUBTRACT", height, g.math("MULTIPLY", cr, 0.6))
    if chips:
        ch_col, under, under_h = chips
        cm = g.math("GREATER_THAN", noise(g, 4.5, detail=7.0, rough=0.72, distortion=0.4, offset=11.0), 0.63)
        col = g.mix(cm, col, under)
        height = g.math("SUBTRACT", height, g.math("MULTIPLY", cm, under_h))
    rgh = g.math("ADD", rough, g.math("MULTIPLY", g.math("SUBTRACT", grit, 0.5), 0.1))
    return col, height, rgh


def band_bw(g, color_socket):
    bw = g.node("ShaderNodeRGBToBW")
    g.link(color_socket, bw.inputs[0])
    return bw.outputs[0]


def build(name):
    mat = bpy.data.materials.new(name)
    mat.use_nodes = True
    SEED[0] = {"plaster": 0.0, "limewash": 13.7, "dado": 41.3, "paint": 67.1, "concrete": 89.9}[name]
    g = G(mat)
    if name == "plaster":
        col, h, r = plaster(g, rgb("#ECEBE6"), rgb("#F7F6F1"), rgb("#D2CFC6"), rgb("#CFC4AA"))
        bs = 1.0
    elif name == "limewash":
        col, h, r = plaster(g, rgb("#E9C877"), rgb("#F3D892"), rgb("#C9A55A"), rgb("#B88E4A"), crack_freq=2.5)
        bs = 1.1
    elif name == "dado":
        col, h, r = plaster(g, rgb("#7FB79A"), rgb("#8EC6A8"), rgb("#5E9479"), rgb("#4F7E66"), crack_freq=3.5, crack_w=0.006, rough=0.45,
                            chips=(None, rgb("#E4DCC8"), 0.5))
        bs = 0.8
    elif name == "paint":
        # neutral painted wood, the shader tints it per unit: grain under several coats, worn edges show bare wood
        n_grain = noise(g, 3.0, detail=6.0, distortion=1.0, offset=21.0)
        fine = noise(g, 60.0, detail=3.0, offset=22.0)
        streak = noise(g, 60.0, detail=4.0, distortion=0.8, offset=23.0, fv=2.0)
        tone = g.math("ADD", g.math("MULTIPLY", n_grain, 0.5), g.math("MULTIPLY", streak, 0.5))
        col = g.ramp(tone, [(0.3, rgb("#BDBDBD")), (0.55, rgb("#D9D9D9")), (0.75, rgb("#E8E8E8"))])
        chips = g.math("GREATER_THAN", noise(g, 6.0, detail=8.0, rough=0.75, distortion=0.5, offset=24.0), 0.64)
        col = g.mix(chips, col, rgb("#7A5C40"))
        h = g.math("ADD", g.math("MULTIPLY", streak, 0.4), g.math("MULTIPLY", fine, 0.2))
        h = g.math("SUBTRACT", h, g.math("MULTIPLY", chips, 0.5))
        r = g.math("ADD", 0.5, g.math("MULTIPLY", chips, 0.35))
        bs = 0.9
    elif name == "concrete":
        col, h, r = plaster(g, rgb("#C9C7C1"), rgb("#D8D6D0"), rgb("#9E9B93"), rgb("#8C877C"), crack_freq=4.0, crack_w=0.006, rough=0.95)
        pores = g.math("LESS_THAN", noise(g, 220.0, detail=1.0, offset=31.0), 0.32)
        col = g.mix(g.math("MULTIPLY", pores, 0.6), col, rgb("#77746C"))
        h = g.math("SUBTRACT", h, g.math("MULTIPLY", pores, 0.4))
        bs = 1.2
    else:
        raise SystemExit(f"unknown material {name}")
    bsdf, emit, out = output(g, col, h, r, bs)
    return mat, g, col, r, bsdf, emit, out


def bake(name):
    mat, g, col, rough, bsdf, emit, out = build(name)
    plane.data.materials.clear()
    plane.data.materials.append(mat)
    img_node = g.node("ShaderNodeTexImage")
    g.n.active = img_node

    def run(kind, socket=None, colorspace="sRGB", fname=""):
        img = bpy.data.images.new(f"{name}-{fname}", SIZE, SIZE, float_buffer=False)
        img.colorspace_settings.name = colorspace
        img_node.image = img
        g.n.active = img_node
        if kind == "EMIT":
            g.link(socket, emit.inputs["Color"])
            g.link(emit.outputs[0], out.inputs["Surface"])
        else:
            g.link(bsdf.outputs[0], out.inputs["Surface"])
        bpy.ops.object.bake(type=kind, normal_space="TANGENT", use_clear=True, margin=0)
        img.filepath_raw = os.path.join(OUT, f"{name}-{fname}.png")
        img.file_format = "PNG"
        img.save()
        print(f"walls: {name}-{fname}.png")

    run("EMIT", col, "sRGB", "albedo")
    rough_rgb = g.node("ShaderNodeCombineColor")
    for i in range(3):
        g.link(rough, rough_rgb.inputs[i])
    run("EMIT", rough_rgb.outputs[0], "Non-Color", "rough")
    run("NORMAL", None, "Non-Color", "normal")


for m in (argv[2].split(",") if len(argv) > 2 else ["plaster", "limewash", "dado", "paint", "concrete"]):
    bake(m)
print("walls: done")
