#!/usr/bin/env python3
"""Static cross-check of the Phase 2 WordPress build (hcwa-core + hcwa-2026).

WordPress never runs here, so this script proves what it can without it: that the
plugin, the blocks, the theme, the patterns and the seed agree with each other and
with wp/CONTRACT.md.

    python3 scripts/wp-static-check.py            # errors and warnings, exit 1 on any error
    python3 scripts/wp-static-check.py --quiet    # errors only

Checks (E = error, W = warning):
  lint      php -l on every PHP file, node --check on every JS file, bash -n, Python compile
  strings   every hcwa_t / hcwa_t_in / hcwa_theme_t / hcwa_theme_e key used in PHP exists in
            the dictionary with non-empty vi and en. The dictionary is what hcwa_strings()
            returns once the 'hcwa_strings' filters have run: includes/strings-data.php
            (generated from content/draft/ui.json) + core keys + block keys + theme keys.
            Also: site.js i18n keys, block editor labels have Vietnamese, no clashes.
  functions every hcwa_* function called (or hooked by name) is defined in the right place:
            the plugin never calls theme functions; the theme guards core-only calls.
  contract  contract function signatures, block names, pattern names, meta keys.
  blocks    every <!-- wp:hcwa/... --> in patterns and the seed, and every
            hcwa_theme_block() call, names a block folder and only attributes its
            block.json declares (type and enum checked); attribute JSON parses;
            blocks/blocks-manifest.php equals the block.json files.
  patterns  headers, slugs, VI/EN twins, referenced slugs, balanced block comments.
  pages     every page key passed to hcwa_page_url / hcwa_theme_url is known.
  css       every class used in block markup, patterns and theme templates exists in
            assets/css/site.css or patterns.css (wp-*, has-*, is-*, align* allowed;
            classes only site.js uses are warnings).
  meta      every meta key read or written is registered in includes/meta.php.
  text      no text-transform: uppercase, no emoji in templates or strings, no ALL CAPS
            Vietnamese; hard-coded visible text in theme templates (warning).

Only the Python standard library plus the php, node and bash binaries are used. Nothing
is downloaded and WordPress is never loaded: PHP is used to tokenize files and to read
the dictionary through the test stubs in wp/tests/bootstrap.php.
"""

from __future__ import annotations

import argparse
import json
import re
import subprocess
import sys
from collections import defaultdict
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
WP = ROOT / "wp"
CORE = WP / "plugins" / "hcwa-core"
THEME = WP / "themes" / "hcwa-2026"
PATTERNS = THEME / "patterns"
BLOCKS = CORE / "blocks"
TESTS = WP / "tests"
SEED = ROOT / "scripts" / "wp-seed.php"
CONTRACT = WP / "CONTRACT.md"
CSS_FILES = [THEME / "assets" / "css" / "site.css", THEME / "assets" / "css" / "patterns.css"]
SITE_JS = THEME / "assets" / "js" / "site.js"

# Functions whose first argument is a dictionary key.
KEY_FUNCS = {"hcwa_t", "hcwa_t_in", "hcwa_theme_t", "hcwa_theme_t_in", "hcwa_theme_e", "hcwa_blocks_empty_note"}

# Dynamic keys built as 'prefix' . $value: every value the code can produce.
KEY_EXPANSIONS = {
    "status_": ["active", "completed", "planned"],
    "rt_": ["annual", "financial", "program", "policy"],
    "blk_type_": ["annual", "financial", "program", "policy"],
    "type_": ["page", "center", "program", "report"],
}

# Literal prefixes that only ever name dictionary keys: any literal with one must exist.
KEY_FAMILIES = ("pay_", "blk_", "cat_", "rt_", "status_", "type_")

# Functions whose first argument is a logical page key (hcwa_page_paths()).
PAGE_FUNCS = {"hcwa_page_url", "hcwa_theme_url", "hcwa_page_path"}

# Functions whose second argument is a post meta key.
META_FUNCS = {
    "get_post_meta": 1, "update_post_meta": 1, "add_post_meta": 1, "delete_post_meta": 1,
    "hcwa_meta": 1, "hcwa_meta_lines": 1, "hcwa_theme_meta_lines": 1, "hcwa_theme_meta_ids": 1,
    "register_post_meta": 1,
}
# Meta keys that are deliberately not registered (with the reason).
META_UNREGISTERED = {
    "_hcwa_publish_blocked": "protected (leading underscore) bookkeeping of the production publish guard",
    "_hcwa_seed_id": "protected seed idempotency key, local instances only (scripts/wp-seed.php)",
}

# Functions taking a callback name: argument index of the callback.
CALLBACK_ARGS = {
    "add_action": 1, "add_filter": 1, "remove_action": 1, "remove_filter": 1, "has_action": 1,
    "has_filter": 1, "register_activation_hook": 1, "register_deactivation_hook": 1,
    "array_map": 0, "array_filter": 1, "usort": 1, "uasort": 1, "uksort": 1,
    "call_user_func": 0, "call_user_func_array": 0, "add_shortcode": 1,
    "add_settings_section": 2, "add_settings_field": 2, "add_meta_box": 2,
    "add_options_page": 4, "add_submenu_page": 5, "add_menu_page": 4,
    "WP_CLI::add_command": 1, "register_shutdown_function": 0,
}

# Class names that need no rule in our CSS.
CSS_ALLOWED_PREFIXES = ("wp-", "has-", "is-", "align", "screen-reader-text")

# Classes printed on purpose without a rule of their own (reviewed; the reason says why).
# A BEM block root whose elements are styled (.not-found with .not-found__body) needs no entry.
CSS_UNSTYLED = {
    "story-card--featured": "marks the lead card; .latest lays it out (prototype has no rule either)",
    "figure__year": "inline year inside .figure__label, inherits its style",
    "pay__qr": "VietQR image or its .ph placeholder; sized by the .pay grid column and img/.ph rules",
    "foot-col__body": "footer column body; .foot-col rules style it (prototype parity)",
    "entry-content--home": "hook for the front page body; .entry-content--page does the layout",
    "lang-switch--header": "hook for the header copy of the switch; .lang-switch styles both",
    "contact-band": "variant name of .cta-band in the contact-band pattern; .cta-band styles it",
    "focus-item__text": "text column of .focus-item; the .focus-item grid places it",
    "hero__title": "H1 of the hero; .hero h1 rules style it",
    "donate-methods": "wrapper of the donation-methods block in the donate pattern; .methods styles the block",
    "news-archive": "wrapper of the news hub; .news-grid and .filters style its parts",
    "report-cover": "report single cover image; .report-cover-wrap img styles it",
}

EMOJI_RE = re.compile("[\U0001F000-\U0001FAFF☀-➿⬀-⯿️‍⃣]")
VI_DIACRITIC_UPPER = re.compile("[ÀÁẢÃẠĂẰẮẲẴẶÂẦẤẨẪẬĐÈÉẺẼẸÊỀẾỂỄỆÌÍỈĨỊÒÓỎÕỌÔỒỐỔỖỘƠỜỚỞỠỢÙÚỦŨỤƯỪỨỬỮỰỲÝỶỸỴ]")

# Block comment, as WordPress's parser reads it (WP_Block_Parser::next_token()).
BLOCK_COMMENT_RE = re.compile(
    r"<!--\s+(?P<closer>/)?wp:(?P<name>[a-z][a-z0-9_-]*(?:/[a-z][a-z0-9_-]*)?)\s+(?P<attrs>\{.*?\}\s+)?(?P<void>/)?-->",
    re.S,
)


# ---------------------------------------------------------------------------
# Reporting
# ---------------------------------------------------------------------------

class Report:
    def __init__(self) -> None:
        self.items: list[tuple[str, str, str, str]] = []

    def error(self, check: str, where: str, msg: str) -> None:
        self.items.append(("E", check, where, msg))

    def warn(self, check: str, where: str, msg: str) -> None:
        self.items.append(("W", check, where, msg))

    def count(self, level: str) -> int:
        return sum(1 for item in self.items if item[0] == level)

    def print(self, quiet: bool) -> None:
        seen = set()
        for level, check, where, msg in sorted(self.items, key=lambda i: (i[0], i[1], i[2])):
            if quiet and level == "W":
                continue
            line = f"{level} [{check}] {where}: {msg}"
            if line in seen:
                continue
            seen.add(line)
            print(line)


def rel(path: Path | str) -> str:
    try:
        return str(Path(path).resolve().relative_to(ROOT))
    except ValueError:
        return str(path)


def read(path: Path) -> str:
    return path.read_text(encoding="utf-8")


def area_of(path: Path) -> str:
    p = path.resolve()
    if CORE in p.parents:
        return "core"
    if THEME in p.parents:
        return "theme"
    if TESTS in p.parents:
        return "tests"
    if p == SEED:
        return "seed"
    return "other"


def php_files() -> list[Path]:
    files = sorted(WP.rglob("*.php")) + sorted((ROOT / "scripts").glob("wp-*.php"))
    return [f for f in files if ".wp-local" not in f.parts]


def js_files() -> list[Path]:
    return sorted(WP.rglob("*.js"))


def run(cmd: list[str], stdin: str | None = None) -> subprocess.CompletedProcess:
    return subprocess.run(cmd, input=stdin, capture_output=True, text=True, cwd=ROOT)


# ---------------------------------------------------------------------------
# PHP helpers: tokenizer and dictionary dump (run through the php binary)
# ---------------------------------------------------------------------------

PHP_TOKENIZE = r"""
error_reporting(E_ALL);
function hcwa_sc_simple(array $tk): array {
    [$type, $text] = $tk;
    if ($type === 'T_CONSTANT_ENCAPSED_STRING') {
        $body = substr($text, 1, -1);
        $val  = $text[0] === "'" ? strtr($body, ['\\\\' => '\\', "\\'" => "'"]) : stripcslashes($body);
        return ['s', $val];
    }
    if ($type === 'T_VARIABLE') { return ['v', $text]; }
    if ($type === 'T_LNUMBER' || $type === 'T_DNUMBER') { return ['n', $text]; }
    if (in_array($type, ['T_STRING', 'T_NAME_QUALIFIED', 'T_NAME_FULLY_QUALIFIED'], true)) { return ['c', $text]; }
    if ($type === 'T_ARRAY') { return ['o', 'array']; }
    return ['o', $text];
}
$out = [];
foreach (array_slice($argv, 1) as $file) {
    $raw  = token_get_all(file_get_contents($file));
    $t    = [];
    $line = 1;
    foreach ($raw as $tok) {
        if (is_array($tok)) {
            [$id, $text, $at] = $tok;
            $line = $at + substr_count($text, "\n");
            if ($id === T_WHITESPACE || $id === T_COMMENT || $id === T_DOC_COMMENT) { continue; }
            if ($id === T_OPEN_TAG || $id === T_INLINE_HTML) { $t[] = ['sep', '', $at]; continue; }
            if ($id === T_CLOSE_TAG) { $t[] = [';', ';', $at]; continue; }
            if ($id === T_OPEN_TAG_WITH_ECHO) { $t[] = ['T_ECHO', 'echo', $at]; continue; }
            $t[] = [token_name($id), $text, $at];
        } else {
            $t[] = [$tok, $tok, $line];
        }
    }
    $defs = [];
    $calls = [];
    $n = count($t);
    for ($i = 0; $i < $n; $i++) {
        $type = $t[$i][0];
        if ($type === 'T_FUNCTION') {
            $j = $i + 1;
            if (($t[$j][0] ?? '') === '&') { $j++; }
            if (($t[$j][0] ?? '') === 'T_STRING') { $defs[] = [$t[$j][1], $t[$j][2]]; }
            continue;
        }
        if (!in_array($type, ['T_STRING', 'T_NAME_QUALIFIED', 'T_NAME_FULLY_QUALIFIED'], true) || ($t[$i + 1][0] ?? '') !== '(') { continue; }
        $prev = $t[$i - 1][0] ?? '';
        if (in_array($prev, ['T_FUNCTION', 'T_OBJECT_OPERATOR', 'T_NULLSAFE_OBJECT_OPERATOR', 'T_NEW', 'T_CONST'], true)) { continue; }
        if ($prev === '&' && ($t[$i - 2][0] ?? '') === 'T_FUNCTION') { continue; }
        $name = ltrim($t[$i][1], '\\');
        if ($prev === 'T_DOUBLE_COLON') { $name = ($t[$i - 2][1] ?? '') . '::' . $name; }
        $depth = 0;
        $args  = [[]];
        for ($j = $i + 1; $j < $n; $j++) {
            $c = $t[$j][0];
            if (in_array($c, ['(', '[', '{', 'T_CURLY_OPEN', 'T_DOLLAR_OPEN_CURLY_BRACES'], true)) {
                $depth++;
                if ($depth === 1) { continue; }
            } elseif (in_array($c, [')', ']', '}'], true)) {
                $depth--;
                if ($depth === 0) { break; }
            } elseif ($c === ',' && $depth === 1) {
                $args[] = [];
                continue;
            }
            $args[count($args) - 1][] = hcwa_sc_simple($t[$j]);
        }
        if ($args === [[]]) { $args = []; }
        $calls[] = ['name' => $name, 'line' => $t[$i][2], 'args' => $args];
    }
    $out[$file] = ['defs' => $defs, 'calls' => $calls];
}
echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
"""

PHP_DUMP = r"""
error_reporting(E_ALL);
$root = $argv[1];
ob_start();
require $root . '/wp/tests/bootstrap.php';
require_once $root . '/wp/themes/hcwa-2026/inc/strings.php';
$noise = ob_get_clean();

$core  = hcwa_strings();
$dict  = $core;
$hooks = $GLOBALS['hcwa_test']['hooks']['hcwa_strings'] ?? [];
usort($hooks, fn($a, $b) => $a[1] <=> $b[1]);
$sources = array_fill_keys(array_keys($core), 'core');
foreach ($hooks as $hook) {
    $before = $dict;
    $dict   = call_user_func($hook[0], $dict);
    foreach (array_diff_key($dict, $before) as $key => $_) {
        $sources[$key] = is_string($hook[0]) ? $hook[0] : 'closure';
    }
}
// The seed returns early outside WP-CLI; its functions are still declared.
$seed_blocks = [];
if (is_file($root . '/scripts/wp-seed.php')) {
    require_once $root . '/scripts/wp-seed.php';
    if (function_exists('hcwa_seed_page_defs')) {
        foreach (hcwa_seed_page_defs() as $key => $def) {
            foreach ((array) ($def['blocks'] ?? []) as $b) { $seed_blocks[] = [$key, $b[0], (object) $b[1]]; }
            foreach ((array) ($def['sections'] ?? []) as $sec) {
                foreach ((array) ($sec[3] ?? []) as $b) { $seed_blocks[] = [$key, $b[0], (object) $b[1]]; }
            }
        }
    }
}
// blocks-manifest.php is what WordPress registers from: it must equal the block.json files.
$manifest_diff = [];
$manifest_file = $root . '/wp/plugins/hcwa-core/blocks/blocks-manifest.php';
$manifest      = is_file($manifest_file) ? require $manifest_file : null;
if (!is_array($manifest)) {
    $manifest_diff[] = 'missing or not an array';
} else {
    $folders = [];
    foreach (glob($root . '/wp/plugins/hcwa-core/blocks/*/block.json') as $file) {
        $folders[basename(dirname($file))] = json_decode((string) file_get_contents($file), true);
    }
    foreach (array_diff(array_keys($folders), array_keys($manifest)) as $name) { $manifest_diff[] = "$name has no entry"; }
    foreach (array_diff(array_keys($manifest), array_keys($folders)) as $name) { $manifest_diff[] = "$name has no block folder"; }
    foreach ($folders as $name => $meta) {
        if (array_key_exists($name, $manifest) && $manifest[$name] !== $meta) { $manifest_diff[] = "$name differs from its block.json"; }
    }
}
$blocks = function_exists('hcwa_blocks_strings') ? hcwa_blocks_strings() : [];
$theme  = function_exists('hcwa_theme_strings') ? hcwa_theme_strings() : [];

$sigs = [];
foreach (get_defined_functions()['user'] as $fn) {
    if (strpos($fn, 'hcwa_') !== 0) { continue; }
    $rf = new ReflectionFunction($fn);
    $params = [];
    foreach ($rf->getParameters() as $p) {
        $params[] = [
            'name'     => $p->getName(),
            'type'     => $p->hasType() ? (string) $p->getType() : '',
            'optional' => $p->isOptional(),
        ];
    }
    $sigs[$rf->getName()] = [
        'params' => $params,
        'return' => $rf->hasReturnType() ? (string) $rf->getReturnType() : '',
        'file'   => $rf->getFileName(),
    ];
}

echo json_encode([
    'noise'          => $noise,
    'dict'           => $dict,
    'sources'        => $sources,
    'shadow_blocks'  => array_keys(array_intersect_key($blocks, $core)),
    'shadow_theme'   => array_keys(array_intersect_key($theme, $core + $blocks)),
    'editor_vi'      => function_exists('hcwa_blocks_editor_vi') ? hcwa_blocks_editor_vi() : [],
    'meta'           => array_map('array_keys', hcwa_meta_fields()),
    'page_keys'      => array_keys(hcwa_page_paths()),
    'page_paths'     => array_map(fn($k) => ['vi' => hcwa_page_path($k, 'vi'), 'en' => hcwa_page_path($k, 'en')], array_combine(array_keys(hcwa_page_paths()), array_keys(hcwa_page_paths()))),
    'post_types'     => hcwa_post_types(),
    'sigs'           => $sigs,
    'seed_blocks'    => $seed_blocks,
    'manifest_diff'  => $manifest_diff,
], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
"""


def php_tokenize(files: list[Path], report: Report) -> dict[Path, dict]:
    res = run(["php", "-d", "display_errors=stderr", "-r", PHP_TOKENIZE, "--", *[str(f) for f in files]])
    if res.returncode != 0:
        report.error("functions", "php tokenizer", res.stderr.strip()[:500])
        return {}
    data = json.loads(res.stdout)
    return {Path(k): v for k, v in data.items()}


def php_dump(report: Report) -> dict:
    res = run(["php", "-d", "display_errors=stderr", "-r", PHP_DUMP, "--", str(ROOT)])
    if res.returncode != 0 or not res.stdout.strip():
        report.error("strings", "wp/tests/bootstrap.php", "could not load the plugin under the test stubs: " + (res.stderr or res.stdout).strip()[:800])
        return {}
    data = json.loads(res.stdout)
    if data.get("noise", "").strip():
        report.warn("strings", "wp/tests/bootstrap.php", "loading the plugin printed output: " + data["noise"].strip()[:200])
    if res.stderr.strip():
        report.error("strings", "wp/tests/bootstrap.php", "PHP notices while loading the plugin: " + res.stderr.strip()[:500])
    return data


def arg_literal(arg: list) -> str | None:
    """The value of an argument that is exactly one string literal."""
    if len(arg) == 1 and arg[0][0] == "s":
        return arg[0][1]
    return None


def arg_prefix(arg: list) -> str | None:
    """'prefix' . $something -> 'prefix'."""
    if len(arg) >= 3 and arg[0][0] == "s" and arg[1] == ["o", "."]:
        return arg[0][1]
    return None


def array_pairs(arg: list) -> list[tuple[str, list]]:
    """Top-level 'key' => value pairs of an array( ... ) / [ ... ] argument."""
    toks = arg[:]
    if toks and toks[0] == ["o", "array"]:
        toks = toks[1:]
    if toks and toks[0][1] in ("(", "["):
        toks = toks[1:-1]
    pairs, depth, cur = [], 0, []
    for tok in toks + [["o", ","]]:
        if tok[0] == "o" and tok[1] in ("(", "[", "{"):
            depth += 1
        elif tok[0] == "o" and tok[1] in (")", "]", "}"):
            depth -= 1
        if tok == ["o", ","] and depth == 0:
            if len(cur) >= 3 and cur[0][0] == "s" and cur[1] == ["o", "=>"]:
                pairs.append((cur[0][1], cur[2:]))
            cur = []
            continue
        cur.append(tok)
    return pairs


# ---------------------------------------------------------------------------
# Checks
# ---------------------------------------------------------------------------

def check_lint(report: Report) -> None:
    for f in php_files():
        res = run(["php", "-d", "display_errors=stderr", "-l", str(f)])
        if res.returncode != 0 or "No syntax errors" not in res.stdout:
            lines = [ln for ln in (res.stderr + res.stdout).splitlines() if ln.strip()]
            detail = next((ln for ln in lines if "error" in ln.lower() and "line" in ln.lower()), lines[0] if lines else "php -l failed")
            report.error("lint", rel(f), detail.replace(str(ROOT) + "/", ""))
    for f in js_files():
        res = run(["node", "--check", str(f)])
        if res.returncode != 0:
            report.error("lint", rel(f), res.stderr.strip().splitlines()[-1] if res.stderr.strip() else "node --check failed")
    for f in sorted((ROOT / "scripts").glob("wp-*.sh")):
        res = run(["bash", "-n", str(f)])
        if res.returncode != 0:
            report.error("lint", rel(f), res.stderr.strip()[:300])
    for f in sorted((ROOT / "scripts").glob("wp-*.py")):
        try:
            compile(read(f), str(f), "exec")
        except SyntaxError as exc:
            report.error("lint", rel(f), f"Python syntax error: {exc}")
    for f in sorted(WP.rglob("*.json")):
        try:
            json.loads(read(f))
        except json.JSONDecodeError as exc:
            report.error("lint", rel(f), f"invalid JSON: {exc}")


def check_strings(report: Report, dump: dict, tokens: dict[Path, dict]) -> set[str]:
    dict_ = dump.get("dict", {})
    used: set[str] = set()

    for key, entry in dict_.items():
        for lang in ("vi", "en"):
            if not str(entry.get(lang, "")).strip():
                report.error("strings", f"dictionary:{key}", f"empty '{lang}' text (source: {dump['sources'].get(key, '?')})")
        vi = str(entry.get("vi", ""))
        for word in re.findall(r"\w{2,}", vi):
            if word.isupper() and VI_DIACRITIC_UPPER.search(word):
                report.error("text", f"dictionary:{key}", f"Vietnamese in ALL CAPS: {word}")
        for lang in ("vi", "en"):
            if EMOJI_RE.search(str(entry.get(lang, ""))):
                report.error("text", f"dictionary:{key}", f"emoji in '{lang}' text")

    # Polylang keeps one translation per source string, and the Vietnamese text is the
    # source: keys with the same Vietnamese but different English would share one saved
    # translation, so hcwa_translate_entry() ignores Polylang's English for them.
    by_vi: dict[str, list[str]] = defaultdict(list)
    for key, entry in dict_.items():
        by_vi[str(entry.get("vi", ""))].append(key)
    for vi, keys in sorted(by_vi.items()):
        ens = {str(dict_[k].get("en", "")) for k in keys}
        if len(keys) > 1 and len(ens) > 1:
            report.warn("strings", "dictionary", f"keys {', '.join(sorted(keys))} share the Vietnamese source {vi!r} but differ in English ({' / '.join(sorted(ens))}): hcwa_t() keeps their English from the dictionary, so Languages > Translations cannot change it; give them distinct Vietnamese wording to make them editable")

    for key in dump.get("shadow_blocks", []):
        report.warn("strings", "includes/blocks.php", f"block key '{key}' is already a core key; the block's wording is ignored")
    for key in dump.get("shadow_theme", []):
        report.warn("strings", "inc/strings.php", f"theme key '{key}' is already a core or block key; the theme's wording is ignored")

    def need(key: str, where: str, how: str) -> None:
        used.add(key)
        if key not in dict_:
            report.error("strings", where, f"{how} '{key}' is not in the dictionary")

    for path, data in tokens.items():
        if area_of(path) == "tests":
            continue
        for call in data["calls"]:
            where = f"{rel(path)}:{call['line']}"
            if call["name"] in KEY_FUNCS and call["args"]:
                first = call["args"][0]
                lit = arg_literal(first)
                prefix = arg_prefix(first)
                if lit is not None:
                    need(lit, where, f"{call['name']}()")
                elif prefix is not None:
                    if prefix in KEY_EXPANSIONS:
                        for value in KEY_EXPANSIONS[prefix]:
                            need(prefix + value, where, f"{call['name']}() dynamic key")
                    else:
                        report.warn("strings", where, f"dynamic key '{prefix}' . … cannot be checked; add it to KEY_EXPANSIONS")

        # Literals of key-only families (labels passed through variables, maps, closures).
        text = read(path)
        for m in re.finditer(r"(?<!\[)'((?:%s)[A-Za-z0-9][A-Za-z0-9_]*)'(?!\s*(?:=>|\]))" % "|".join(KEY_FAMILIES), text):
            key = m.group(1)
            if key.endswith("_"):
                continue
            line = text.count("\n", 0, m.start()) + 1
            need(key, f"{rel(path)}:{line}", "key-family literal")

    # Keys carried through data tables into hcwa_t() calls.
    carriers = [
        (THEME / "inc" / "navigation.php", "hcwa_theme_fallback_tree", r"array\(\s*'(\w+)',\s*'(\w+)'", 2),
        (THEME / "inc" / "assets.php", "hcwa_theme_js_strings", r"'(\w+)'", 1),
        (THEME / "single-hcwa_program.php", None, r"foreach\s*\(\s*array\(\s*'(\w+)'\s*=>[^,]+,\s*'(\w+)'\s*=>", (1, 2)),
        (SEED, "hcwa_seed_page_defs", r"'label'\s*=>\s*'(\w+)'", 1),
        (SEED, "hcwa_seed_page_defs", r"array\(\s*'(\w+)',\s*'(\w+)',\s*array\(", 2),
        (SEED, "hcwa_seed_menu_trees", r"array\(\s*'(?:page|anchor)',\s*'(\w+)',\s*'(\w+)'", 2),
    ]
    for path, func, pattern, groups in carriers:
        if not path.exists():
            continue
        body = function_body(read(path), func) if func else read(path)
        if body is None:
            report.warn("strings", rel(path), f"function {func}() not found; its keys were not checked")
            continue
        for m in re.finditer(pattern, body):
            for g in (groups if isinstance(groups, tuple) else (groups,)):
                key = m.group(g)
                if func == "hcwa_theme_js_strings" and key in ("lang",):
                    continue
                need(key, f"{rel(path)} {func or ''}".strip(), "table key")

    # site.js reads window.hcwaI18n: every key it reads must be exported by assets.php.
    assets = THEME / "inc" / "assets.php"
    if SITE_JS.exists() and assets.exists():
        body = function_body(read(assets), "hcwa_theme_js_strings") or ""
        exported = set(re.findall(r"'(\w+)'", body))
        for key in sorted(set(re.findall(r"\bi18n\.(\w+)", read(SITE_JS)))):
            if key not in exported:
                report.error("strings", rel(SITE_JS), f"reads hcwaI18n.{key}, which inc/assets.php does not export")

    # Block editor labels (wp.i18n, domain hcwa) need Vietnamese.
    editor_vi = dump.get("editor_vi", {})
    for js in sorted(BLOCKS.rglob("*.js")):
        for m in re.finditer(r"""\b_[_x]\(\s*(['"])((?:\\.|(?!\1).)*)\1\s*,\s*(?:(['"])(?:\\.|(?!\3).)*\3\s*,\s*)?['"]hcwa['"]""", read(js)):
            label = m.group(2).replace("\\'", "'").replace('\\"', '"')
            if label not in editor_vi:
                report.error("strings", rel(js), f"editor label without Vietnamese in hcwa_blocks_editor_vi(): {label!r}")
    for bj in sorted(BLOCKS.glob("*/block.json")):
        meta = json.loads(read(bj))
        for field in ("title", "description"):
            if meta.get(field) and meta[field] not in editor_vi:
                report.error("strings", rel(bj), f"block {field} without Vietnamese in hcwa_blocks_editor_vi()")

    unused = sorted(k for k, src in dump.get("sources", {}).items() if k not in used and src != "core")
    if unused:
        report.warn("strings", "dictionary", f"{len(unused)} block/theme keys are never used: {', '.join(unused[:20])}")
    return used


def function_body(src: str, name: str | None) -> str | None:
    if name is None:
        return src
    m = re.search(r"function\s+%s\s*\([^)]*\)[^{]*\{" % re.escape(name), src)
    if not m:
        return None
    depth, i = 1, m.end()
    while i < len(src) and depth:
        if src[i] == "{":
            depth += 1
        elif src[i] == "}":
            depth -= 1
        i += 1
    return src[m.end():i - 1]


def check_functions(report: Report, tokens: dict[Path, dict]) -> dict[str, set[str]]:
    defined: dict[str, set[str]] = defaultdict(set)
    for path, data in tokens.items():
        for name, line in data["defs"]:
            if name.startswith("hcwa_"):
                if area_of(path) in defined[name] and area_of(path) != "tests":
                    report.error("functions", f"{rel(path)}:{line}", f"{name}() is defined twice in {area_of(path)}")
                defined[name].add(area_of(path))

    allowed = {
        "core": {"core"},
        "theme": {"theme"},
        "seed": {"seed", "core", "theme"},
        "tests": {"tests", "core", "theme", "seed"},  # runtime-scripts-test.php loads the seed
        "other": {"core"},
    }

    for path, data in tokens.items():
        area = area_of(path)
        text = read(path)
        guarded = set(re.findall(r"function_exists\(\s*'(hcwa_\w+)'\s*\)", text))

        def check(name: str, where: str, how: str) -> None:
            where_def = defined.get(name, set())
            if where_def & allowed[area]:
                return
            if area == "theme" and "core" in where_def:
                if name not in guarded:
                    report.warn("functions", where, f"theme {how} core function {name}() without a function_exists() guard (fatal if hcwa-core is inactive)")
                return
            if name in guarded:
                if not where_def:
                    report.warn("functions", where, f"{how} {name}() only behind function_exists(); it is defined nowhere")
                return
            if where_def:
                report.error("functions", where, f"{area} {how} {name}(), which is defined only in {', '.join(sorted(where_def))}")
            else:
                report.error("functions", where, f"{how} undefined function {name}()")

        for call in data["calls"]:
            where = f"{rel(path)}:{call['line']}"
            name = call["name"]
            if name.startswith("hcwa_"):
                check(name, where, "calls")
            if name in CALLBACK_ARGS and len(call["args"]) > CALLBACK_ARGS[name]:
                cb = arg_literal(call["args"][CALLBACK_ARGS[name]])
                if cb and cb.startswith("hcwa_"):
                    check(cb, where, f"hooks ({name})")
            if name == "function_exists" and call["args"]:
                fn = arg_literal(call["args"][0])
                if fn and fn.startswith("hcwa_") and not defined.get(fn):
                    report.warn("functions", where, f"function_exists('{fn}'): {fn}() is defined nowhere, so that branch never runs")
        for m in re.finditer(r"'\w*(?:callback|_cb)'\s*=>\s*'(hcwa_\w+)'", text):
            check(m.group(1), f"{rel(path)}:{text.count(chr(10), 0, m.start()) + 1}", "names callback")
    return defined


def check_contract_functions(report: Report, dump: dict) -> None:
    text = read(CONTRACT)
    m = re.search(r"## Functions the core plugin provides.*?```php\n(.*?)```", text, re.S)
    if not m:
        report.error("contract", rel(CONTRACT), "function list not found")
        return
    sigs = dump.get("sigs", {})
    for line in m.group(1).splitlines():
        sm = re.match(r"\s*(hcwa_\w+)\(\s*(.*?)\s*\)(?::\s*(\??[\w|]+))?", line)
        if not sm:
            continue
        name, params, ret = sm.group(1), sm.group(2), sm.group(3) or ""
        if name not in sigs:
            report.error("contract", f"CONTRACT.md {name}", "contract function is not defined in hcwa-core")
            continue
        if area_of(Path(sigs[name]["file"])) != "core":
            report.error("contract", f"CONTRACT.md {name}", f"defined outside hcwa-core ({rel(sigs[name]['file'])})")
        want = []
        for p in [p.strip() for p in params.split(",") if p.strip()]:
            pm = re.match(r"(?:([\w|?]+)\s+)?\$(\w+)(\s*=.*)?", p)
            if pm:
                want.append((pm.group(1) or "", pm.group(2), bool(pm.group(3))))
        have = [(p["type"], p["name"], p["optional"]) for p in sigs[name]["params"]]
        if [(w[1], w[2]) for w in want] != [(h[1], h[2]) for h in have]:
            report.error("contract", f"CONTRACT.md {name}", f"parameters differ: contract {want}, code {have}")
        for w, h in zip(want, have):
            if w[0] and h[0] and w[0].lstrip("?") != h[0].lstrip("?").replace("float|int", "float|int"):
                if set(w[0].lstrip("?").split("|")) != set(h[0].lstrip("?").split("|")):
                    report.error("contract", f"CONTRACT.md {name}", f"type of ${w[1]}: contract {w[0]}, code {h[0]}")
        if ret and sigs[name]["return"] and ret.replace(" ", "") != sigs[name]["return"].replace(" ", ""):
            report.error("contract", f"CONTRACT.md {name}", f"return type: contract {ret}, code {sigs[name]['return']}")


def load_blocks(report: Report, dump: dict | None = None) -> dict[str, dict]:
    blocks: dict[str, dict] = {}
    for d in sorted(p for p in BLOCKS.iterdir() if p.is_dir()):
        bj = d / "block.json"
        if not bj.exists():
            report.error("blocks", rel(d), "block folder without block.json")
            continue
        meta = json.loads(read(bj))
        name = meta.get("name", "")
        if name != f"hcwa/{d.name}":
            report.error("blocks", rel(bj), f"name {name!r} does not match the folder (hcwa/{d.name})")
        for f in ("render.php", "index.js", "index.asset.php"):
            if not (d / f).exists():
                report.error("blocks", rel(d), f"missing {f}")
        if meta.get("render") != "file:./render.php":
            report.error("blocks", rel(bj), "render must be file:./render.php (all blocks are dynamic)")
        if meta.get("category") != "hcwa":
            report.error("blocks", rel(bj), "category must be 'hcwa'")
        blocks[name] = meta

    for diff in dump.get("manifest_diff", []) if dump else []:
        report.error("blocks", rel(BLOCKS / "blocks-manifest.php"),
                     f"{diff}; regenerate with php wp/tests/blocks-manifest-build.php")

    m = re.search(r"- Blocks: (.*?)Category", read(CONTRACT), re.S)
    if m:
        names = set(re.findall(r"`(hcwa/[\w-]+)`", m.group(1)))
        for n in sorted(names - set(blocks)):
            report.error("contract", "CONTRACT.md Blocks", f"{n} has no block folder")
        for n in sorted(set(blocks) - names):
            report.error("contract", "CONTRACT.md Blocks", f"{n} is not in the contract")
    return blocks


def check_block_attrs(report: Report, blocks: dict, name: str, attrs: dict, where: str) -> None:
    if name not in blocks:
        report.error("blocks", where, f"{name} is not a registered block folder")
        return
    meta = blocks[name]
    declared = meta.get("attributes", {})
    allowed = {"lock", "metadata"}
    if meta.get("supports", {}).get("customClassName", True) is not False:
        allowed.add("className")
    for key, value in attrs.items():
        if key in allowed:
            continue
        if key not in declared:
            report.error("blocks", where, f"{name}: attribute '{key}' is not declared in block.json")
            continue
        spec = declared[key]
        if value is None:
            continue
        types = spec.get("type")
        types = types if isinstance(types, list) else [types]
        py = {"string": str, "integer": int, "number": (int, float), "boolean": bool, "array": list, "object": dict}
        ok = any(
            isinstance(value, py[t]) and not (t in ("integer", "number") and isinstance(value, bool))
            for t in types if t in py
        )
        if not ok:
            report.error("blocks", where, f"{name}: attribute '{key}' = {value!r} is not of type {spec.get('type')}")
        if "enum" in spec and value not in spec["enum"]:
            report.error("blocks", where, f"{name}: attribute '{key}' = {value!r} is not one of {spec['enum']}")


def parse_block_comments(text: str, where: str, report: Report, blocks: dict, balance: bool) -> list[str]:
    stack: list[str] = []
    names = []
    for m in BLOCK_COMMENT_RE.finditer(text):
        name = m.group("name")
        full = name if "/" in name else "core/" + name
        line = text.count("\n", 0, m.start()) + 1
        loc = f"{where}:{line}"
        attrs = {}
        if m.group("attrs"):
            try:
                attrs = json.loads(m.group("attrs"))
            except json.JSONDecodeError as exc:
                report.error("blocks", loc, f"attribute JSON of {full} does not parse: {exc}")
        if m.group("closer"):
            if balance:
                if not stack:
                    report.error("patterns", loc, f"closer /wp:{name} without an opener")
                elif stack[-1] != full:
                    report.error("patterns", loc, f"closer /wp:{name} but the open block is {stack[-1]}")
                    if full in stack:
                        while stack and stack.pop() != full:
                            pass
                else:
                    stack.pop()
            continue
        names.append(full)
        if full.startswith("hcwa/"):
            check_block_attrs(report, blocks, full, attrs, loc)
            if not m.group("void"):
                report.error("blocks", loc, f"{full} is dynamic and must be saved as a self-closing comment")
        if full == "core/pattern":
            names.append("pattern:" + str(attrs.get("slug", "")))
        if not m.group("void"):
            stack.append(full)
    if balance and stack:
        report.error("patterns", where, f"blocks left open: {', '.join(stack)}")
    # Comments that look like block comments but do not parse.
    for m in re.finditer(r"<!--\s*/?wp:[^>]*-->", text):
        if re.search(r"['\"]\s*\.|\.\s*['\"]|\{\$|\$\w", m.group(0)):
            continue  # built by PHP string concatenation (the seed's block helpers)
        if not BLOCK_COMMENT_RE.fullmatch(m.group(0)):
            report.error("patterns", f"{where}:{text.count(chr(10), 0, m.start()) + 1}", f"malformed block comment: {m.group(0)[:80]}")
    return names


def check_patterns(report: Report, blocks: dict, tokens: dict[Path, dict], dump: dict) -> dict[str, Path]:
    slugs: dict[str, Path] = {}
    categories = set(re.findall(r"register_block_pattern_category\(\s*'([\w-]+)'", read(THEME / "inc" / "setup.php")))
    core_cats = set(re.findall(r"register_block_pattern_category\(\s*'([\w-]+)'", read_ref("wp-includes/block-patterns.php")))
    refs: list[tuple[str, str]] = []

    for f in sorted(PATTERNS.glob("*.php")):
        text = read(f)
        head = text[:2000]
        fields = {}
        for field in ("Title", "Slug", "Categories", "Block Types", "Post Types", "Inserter"):
            m = re.search(r"^[\s*#@]*%s:(.*)$" % re.escape(field), head, re.M | re.I)
            if m:
                fields[field] = m.group(1).strip()
        where = rel(f)
        slug = fields.get("Slug", "")
        if not slug:
            report.error("patterns", where, "no Slug header (WordPress skips the file)")
            continue
        if not fields.get("Title"):
            report.error("patterns", where, "no Title header (WordPress skips the file)")
        if not re.fullmatch(r"[A-z0-9/_-]+", slug):
            report.error("patterns", where, f"invalid slug {slug!r}")
        if slug != f"hcwa-2026/{f.stem}":
            report.error("patterns", where, f"slug {slug!r} does not match the file name (hcwa-2026/{f.stem})")
        if slug in slugs:
            report.error("patterns", where, f"slug {slug} also used by {rel(slugs[slug])}")
        slugs[slug] = f
        for cat in [c.strip() for c in fields.get("Categories", "").split(",") if c.strip()]:
            if cat not in categories | core_cats:
                report.error("patterns", where, f"category {cat!r} is not registered")
        for name in parse_block_comments(text, where, report, blocks, balance=True):
            if name.startswith("pattern:"):
                refs.append((name[8:], where))
        if "<!-- wp:" not in text:
            report.error("patterns", where, "no block markup")

    for slug, f in slugs.items():
        base, _, lang = slug.rpartition("-")
        if lang not in ("vi", "en"):
            report.error("patterns", rel(f), f"slug {slug} does not end in -vi or -en")
            continue
        twin = f"{base}-{'en' if lang == 'vi' else 'vi'}"
        if twin not in slugs:
            report.error("patterns", rel(f), f"no {twin} twin")

    m = re.search(r"- Pattern slugs:(.*?)\n- Menu", read(CONTRACT), re.S)
    if m:
        for name in re.findall(r"`([a-z-]+)`", m.group(1)):
            if name in ("hcwa", "hcwa-2026"):
                continue
            for lang in ("vi", "en"):
                if f"hcwa-2026/{name}-{lang}" not in slugs:
                    report.error("contract", "CONTRACT.md Pattern slugs", f"hcwa-2026/{name}-{lang} does not exist")

    seed = read(SEED) if SEED.exists() else ""
    for m in re.finditer(r"'pattern'\s*=>\s*'([\w-]+)'", seed):
        for lang in ("vi", "en"):
            refs.append((f"hcwa-2026/{m.group(1)}-{lang}", f"{rel(SEED)}:{seed.count(chr(10), 0, m.start()) + 1}"))
    for slug, where in refs:
        if slug not in slugs:
            report.error("patterns", where, f"references pattern {slug!r}, which does not exist")

    # Block markup the seed writes itself, and the hcwa/* blocks its page table places.
    if seed:
        parse_block_comments(seed, rel(SEED), report, blocks, balance=False)
    for page, name, attrs in dump.get("seed_blocks", []):
        check_block_attrs(report, blocks, name, attrs if isinstance(attrs, dict) else {}, f"{rel(SEED)} hcwa_seed_page_defs() {page}")

    # Blocks rendered from PHP templates.
    for path, data in tokens.items():
        for call in data["calls"]:
            if call["name"] not in ("hcwa_theme_block", "render_block") or not call["args"]:
                continue
            name = arg_literal(call["args"][0])
            if call["name"] == "render_block" or name is None:
                continue
            attrs = {}
            if len(call["args"]) > 1:
                for key, value in array_pairs(call["args"][1]):
                    if len(value) == 1:
                        kind, raw = value[0]
                        attrs[key] = (
                            raw if kind == "s" else
                            int(raw) if kind == "n" and raw.isdigit() else
                            float(raw) if kind == "n" else
                            {"true": True, "false": False}.get(raw.lower()) if kind == "c" else None
                        )
                    else:
                        attrs[key] = None
            check_block_attrs(report, blocks, name, attrs, f"{rel(path)}:{call['line']}")
    return slugs


def read_ref(relpath: str) -> str:
    p = ROOT / ".wp-local" / "ref" / "src" / "wordpress" / relpath
    return read(p) if p.exists() else ""


def check_pages(report: Report, dump: dict, tokens: dict[Path, dict]) -> None:
    keys = set(dump.get("page_keys", []))
    if not keys:
        return

    # Patterns link with root-relative paths (they cannot call hcwa_page_url()): each one
    # must be a default page path of hcwa_page_paths() in the pattern's own language.
    paths = {"vi": set(), "en": set()}
    for key, by_lang in dump.get("page_paths", {}).items():
        for lang in ("vi", "en"):
            paths[lang].add("/" + str(by_lang[lang] or ""))
    for f in sorted(PATTERNS.glob("*.php")):
        lang = "en" if f.stem.endswith("-en") else "vi"
        text = read(f)
        for m in re.finditer(r'href="(/[^"]*)"', text):
            href = m.group(1).replace("&amp;", "&")
            path, _, rest = href.partition("?")
            path, hash_sep, frag = path.partition("#")
            target = path + (hash_sep + frag if frag else "")
            where = f"{rel(f)}:{text.count(chr(10), 0, m.start()) + 1}"
            if target not in paths[lang] and path not in paths[lang]:
                report.error("pages", where, f"link {href} is not a {lang} page path in hcwa_page_paths()")
            elif frag and target not in paths[lang]:
                report.warn("pages", where, f"anchor #{frag} on {path} is not one of the anchor keys in hcwa_page_paths()")
    for path, data in tokens.items():
        if area_of(path) == "tests":
            continue
        for call in data["calls"]:
            if call["name"] in PAGE_FUNCS and call["args"]:
                lit = arg_literal(call["args"][0])
                if lit is not None and lit not in keys:
                    report.error("pages", f"{rel(path)}:{call['line']}", f"{call['name']}('{lit}'): unknown page key")
    for path, func, pattern in (
        (THEME / "inc" / "navigation.php", "hcwa_theme_fallback_tree", r"array\(\s*'(\w+)',\s*'\w+'"),
        (SEED, "hcwa_seed_menu_trees", r"array\(\s*'(?:page|anchor)',\s*'(\w+)'"),
    ):
        body = function_body(read(path), func) if path.exists() else None
        for m in re.finditer(pattern, body or ""):
            if m.group(1) not in keys:
                report.error("pages", f"{rel(path)} {func}", f"unknown page key '{m.group(1)}'")


def css_classes() -> set[str]:
    classes: set[str] = set()
    for f in CSS_FILES:
        if not f.exists():
            continue
        text = re.sub(r"/\*.*?\*/", "", read(f), flags=re.S)
        text = re.sub(r"url\([^)]*\)", "url()", text)
        prelude = []
        for ch in text:
            if ch == "{":
                sel = "".join(prelude)
                if not sel.strip().startswith("@"):
                    sel = re.sub(r"\[[^\]]*\]", "", sel)
                    classes.update(re.findall(r"\.(-?[_a-zA-Z][\w-]*)", sel))
                prelude = []
            elif ch in "};":
                prelude = []
            else:
                prelude.append(ch)
    return classes


def markup_files() -> list[Path]:
    files = sorted(BLOCKS.glob("*/render.php")) + [CORE / "includes" / "blocks.php"]
    files += sorted(PATTERNS.glob("*.php"))
    files += sorted(THEME.glob("*.php")) + sorted((THEME / "template-parts").glob("*.php"))
    files += [THEME / "inc" / n for n in ("navigation.php", "template-tags.php", "core-bridge.php")]
    return [f for f in files if f.exists()]


def class_uses(text: str) -> list[tuple[str, int, bool]]:
    """(class or prefix, line, is_prefix) for every class name the source can print."""
    uses: list[tuple[str, int, bool]] = []

    def add(value: str, pos: int, dynamic_tail: bool) -> None:
        line = text.count("\n", 0, pos) + 1
        tokens = value.split()
        for i, tok in enumerate(tokens):
            last = i == len(tokens) - 1 and not value[-1:].isspace()
            if not re.fullmatch(r"-?[_a-zA-Z][\w-]*", tok):
                continue
            if last and dynamic_tail and tok.endswith(("-", "_")):
                uses.append((tok, line, True))
            else:
                uses.append((tok, line, False))

    stop = re.compile(r"""<\?php|<\?=|'\s*\.|"\s*\.|\$|\{|%""")
    for m in re.finditer(r"""\bclass=(["'])(.*?)\1""", text, re.S):
        value = m.group(2)
        cut = stop.search(value)
        if cut:
            add(value[:cut.start()], m.start(), True)
        else:
            add(value, m.start(), False)
    # 'class=' inside a PHP single-quoted string: class="...' . $x . '..."
    for m in re.finditer(r"""\bclass="([^"'$<{]*)'\s*\.""", text):
        add(m.group(1), m.start(), True)
    for m in re.finditer(r"""'(?:class|menu_class|container_class)'\s*=>\s*'([^']*)'""", text):
        add(m.group(1), m.start(), False)
    for m in re.finditer(r"""\bhcwa_(?:blocks|theme)_icon\(\s*'[\w-]+'\s*,\s*'([^']+)'""", text):
        add(m.group(1), m.start(), False)
    for m in re.finditer(r"""\bhcwa_theme_lang_switch\(\s*'([^']+)'""", text):
        add(m.group(1), m.start(), False)
    # BEM-shaped literals are class names wherever they appear.
    for m in re.finditer(r"""'([a-z][a-z0-9]*(?:-[a-z0-9]+)*(?:__[a-z0-9-]+|--[a-z0-9-]+)+)'""", text):
        if re.search(r"__(?:not_)?(?:in|and)$", m.group(1)):
            continue  # WP_Query arguments (post__in, category__not_in, ...)
        add(m.group(1), m.start(), False)
    return uses


def check_css(report: Report) -> None:
    defined = css_classes()
    if not defined:
        report.error("css", "assets/css", "no classes found in site.css / patterns.css")
        return
    js = read(SITE_JS) if SITE_JS.exists() else ""
    js_classes = set(re.findall(r"""["'.\s]([a-z][\w-]*)""", js))
    button_kinds = re.findall(r"array\(\s*'primary',\s*'secondary',\s*'on-blue'\s*\)", read(THEME / "inc" / "template-tags.php"))
    expansions = {"btn--": ["primary", "secondary", "on-blue", "small"] if button_kinds else [], "status--": ["active", "completed", "planned"]}

    for f in markup_files():
        text = read(f)
        for cls, line, is_prefix in class_uses(text):
            where = f"{rel(f)}:{line}"
            if cls.startswith(CSS_ALLOWED_PREFIXES):
                continue
            if is_prefix:
                for value in expansions.get(cls, []):
                    if cls + value not in defined:
                        report.error("css", where, f"class .{cls}{value} has no rule in site.css or patterns.css")
                if cls not in expansions and not any(d.startswith(cls) for d in defined):
                    report.error("css", where, f"no class in the CSS starts with .{cls}")
                continue
            if cls in defined or cls in CSS_UNSTYLED:
                continue
            if "__" not in cls and "--" not in cls and any(d.startswith(cls + "__") for d in defined):
                continue  # BEM block root with styled elements
            if cls in js_classes:
                report.warn("css", where, f".{cls} has no CSS rule (site.js hook)")
            else:
                report.error("css", where, f"class .{cls} has no rule in site.css or patterns.css")


def check_meta(report: Report, dump: dict, tokens: dict[Path, dict]) -> None:
    registered = {k for keys in dump.get("meta", {}).values() for k in keys}
    if not registered:
        report.error("meta", "includes/meta.php", "no registered meta keys found")
        return

    warned: set[str] = set()

    def need(key: str, where: str) -> None:
        if key in registered:
            return
        if key in META_UNREGISTERED:
            if key not in warned:
                warned.add(key)
                report.warn("meta", where, f"{key} is not registered: {META_UNREGISTERED[key]}")
            return
        report.error("meta", where, f"meta key {key} is not registered in includes/meta.php")

    for path, data in tokens.items():
        if area_of(path) == "tests":
            continue
        for call in data["calls"]:
            idx = META_FUNCS.get(call["name"])
            if idx is not None and len(call["args"]) > idx:
                key = arg_literal(call["args"][idx])
                if key and re.match(r"_?hcwa_", key):
                    need(key, f"{rel(path)}:{call['line']}")
        text = read(path)
        for m in re.finditer(r"'key'\s*=>\s*'(_?hcwa_\w+)'", text):
            need(m.group(1), f"{rel(path)}:{text.count(chr(10), 0, m.start()) + 1}")

    seed = read(SEED) if SEED.exists() else ""
    for m in re.finditer(r"'(hcwa_[a-z_]+)'\s*=>", seed):
        need(m.group(1), f"{rel(SEED)}:{seed.count(chr(10), 0, m.start()) + 1}")

    m = re.search(r"## Meta keys(.*?)\n## ", read(CONTRACT), re.S)
    if m:
        for key in sorted(set(re.findall(r"`(hcwa_\w+)`", m.group(1)))):
            if key in dump.get("post_types", []) or key == "hcwa_focus_area":
                continue
            if key not in registered:
                report.error("contract", "CONTRACT.md Meta keys", f"{key} is not registered in includes/meta.php")


def check_text(report: Report) -> None:
    for css in sorted(WP.rglob("*.css")):
        text = read(css)
        for m in re.finditer(r"text-transform\s*:\s*uppercase", text, re.I):
            report.error("text", f"{rel(css)}:{text.count(chr(10), 0, m.start()) + 1}", "text-transform: uppercase (rule 6)")
        for m in re.finditer(r"font-variant(?:-caps)?\s*:\s*(?:all-)?small-caps", text, re.I):
            report.error("text", f"{rel(css)}:{text.count(chr(10), 0, m.start()) + 1}", "small caps (rule 6)")

    scan = markup_files() + [THEME / "inc" / "strings.php", CORE / "includes" / "strings.php", CORE / "includes" / "strings-data.php"]
    scan += js_files()
    for f in scan:
        if not f.exists() or TESTS in f.resolve().parents:
            continue
        text = read(f)
        for m in EMOJI_RE.finditer(text):
            report.error("text", f"{rel(f)}:{text.count(chr(10), 0, m.start()) + 1}", f"emoji U+{ord(m.group(0)):04X}")

    # Hard-coded visible text in theme templates (heuristic): HTML text outside PHP, and
    # gettext calls, which are for admin screens only.
    brand = {"Zalo", "Facebook", "HCWA", "PDF", "VI", "EN", "/"}
    templates = sorted(THEME.glob("*.php")) + sorted((THEME / "template-parts").glob("*.php"))
    for f in templates:
        text = read(f)
        html = re.sub(r"<\?(?:php|=).*?(?:\?>|\Z)", lambda m: "\x00" * 0 + "\n" * m.group(0).count("\n"), text, flags=re.S)
        for m in re.finditer(r">([^<>]+)<", html):
            chunk = m.group(1).strip()
            words = re.sub(r"&#?\w+;", " ", chunk).split()
            if not chunk or chunk in brand or (words and all(w in brand for w in words)) or not re.search(r"[A-Za-zÀ-ỹ]{2,}", chunk):
                continue
            if re.fullmatch(r"&\w+;.*", chunk) and not re.search(r"[A-Za-zÀ-ỹ]{3,}", chunk.split(";", 1)[1]):
                continue
            line = html.count("\n", 0, m.start()) + 1
            report.warn("text", f"{rel(f)}:{line}", f"hard-coded visible text outside hcwa_t: {chunk[:60]!r}")
        for m in re.finditer(r"\b(?:esc_html__|esc_html_e|esc_attr__|esc_attr_e|__|_e|_x|_n)\(\s*'([^']+)'", text):
            line = text.count("\n", 0, m.start()) + 1
            report.warn("text", f"{rel(f)}:{line}", f"gettext in a front-end template (use hcwa_t): {m.group(1)[:60]!r}")


# ---------------------------------------------------------------------------

def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__.split("\n\n")[0])
    parser.add_argument("--quiet", action="store_true", help="print errors only")
    args = parser.parse_args()

    report = Report()
    check_lint(report)
    tokens = php_tokenize(php_files(), report)
    dump = php_dump(report)
    if dump:
        check_strings(report, dump, tokens)
        check_contract_functions(report, dump)
        check_pages(report, dump, tokens)
        check_meta(report, dump, tokens)
    check_functions(report, tokens)
    blocks = load_blocks(report, dump)
    check_patterns(report, blocks, tokens, dump)
    check_css(report)
    check_text(report)

    report.print(args.quiet)
    errors, warnings = report.count("E"), report.count("W")
    print(f"\nwp-static-check: {errors} error(s), {warnings} warning(s); "
          f"{len(php_files())} PHP, {len(js_files())} JS, {len(list(PATTERNS.glob('*.php')))} patterns, "
          f"{len(dump.get('dict', {}))} strings, {len(blocks)} blocks checked.")
    return 1 if errors else 0


if __name__ == "__main__":
    sys.exit(main())
