#!/usr/bin/env bash
#
# Local WordPress for the HCWA rebuild (Phase 2).
#
# One folder per instance under .wp-local/<name>/: WordPress with SQLite (no MySQL),
# Polylang, and hcwa-core + hcwa-2026 symlinked from wp/, so edits in the repo show up
# on the next page load. Served by PHP's built-in server through scripts/wp-router.php.
#
#   scripts/wp-local.sh help
#
# Everything this script downloads goes to .wp-local/cache/ once and is reused.
# Local development only: the admin account is admin/admin and PHP errors are shown.
#
set -euo pipefail

REPO="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd -P)"
LOCAL_DIR="$REPO/.wp-local"
CACHE_DIR="$LOCAL_DIR/cache"

PHP_BIN="${PHP:-php}"
DEFAULT_PORT=8881
WP_VERSION="${HCWA_WP_VERSION:-latest}"
# Pinned to the versions whose source the seed script was checked against
# (.wp-local/ref/src). "latest" takes the newest stable release instead.
POLYLANG_VERSION="${HCWA_POLYLANG_VERSION:-3.9.1}"
SQLITE_VERSION="${HCWA_SQLITE_VERSION:-3.1.3}"
PHP_WORKERS="${HCWA_PHP_WORKERS:-4}"

THEME_SLUG="hcwa-2026"
CORE_SLUG="hcwa-core"
ADMIN_USER="admin"
ADMIN_PASS="admin"
ADMIN_EMAIL="admin@example.com"
SITE_TITLE="Hội Bảo trợ Trẻ em TP. Hồ Chí Minh"

export WP_CLI_CACHE_DIR="$CACHE_DIR/wp-cli"
export WP_CLI_DISABLE_AUTO_CHECK_UPDATE=1

# ---------------------------------------------------------------------------- output

say()  { printf '==> %s\n' "$*"; }
note() { printf '    %s\n' "$*"; }
warn() { printf 'warning: %s\n' "$*" >&2; }
die()  { printf 'error: %s\n' "$*" >&2; exit 1; }

usage() {
	cat <<'EOF'
Local WordPress for the HCWA rebuild.

Usage: scripts/wp-local.sh <command> [arguments]

  setup <name> [port]        New instance in .wp-local/<name> (default port 8881):
                             WordPress + SQLite + Polylang, Vietnamese language pack,
                             hcwa-core and hcwa-2026 linked from wp/ and activated,
                             pretty permalinks. Safe to run again.
  start <name> [port]        Serve it at http://127.0.0.1:<port>/ in the background.
  stop [name]                Stop one instance, or all of them.
  seed <name>                Languages, pages, centers, programs, impact figures, news,
                             menus and HCWA settings from content/draft/. Safe to run
                             again: seeded posts are updated, never duplicated.
  smoke <name> [-- args]     Browser check of every seeded URL at 390 and 1440 wide
                             (scripts/wp-smoke.py). Needs the instance running.
  env <name> <type>          Environment type: local, staging or production.
                             production hides unverified figures and pending chips.
  wp <name> -- <args>        Run WP-CLI against the instance, e.g.
                             scripts/wp-local.sh wp main -- hcwa coverage
  reset <name> [--yes]       Delete the instance and set it up again (same port).
  list                       Instances, their address, environment and state.

A first run, start to finish:

  scripts/wp-local.sh setup main
  scripts/wp-local.sh seed main
  scripts/wp-local.sh start main
  scripts/wp-local.sh smoke main

Environment variables: PHP (php binary), HCWA_WP_VERSION (default latest),
HCWA_POLYLANG_VERSION (3.9.1), HCWA_SQLITE_VERSION (3.1.3), HCWA_PHP_WORKERS (4),
HCWA_WP_LOCAL_OK=1 (permission to download and run WordPress without a terminal).
EOF
}

# ---------------------------------------------------------------------------- checks

# Downloading and running WordPress in the shared build environment needs Maxwell's
# permission (wp/README.md). A person at a terminal has it; automated runs must say so.
require_permission() {
	if [[ "${HCWA_WP_LOCAL_OK:-}" == "1" ]] || [[ -t 0 && -t 1 ]]; then
		return 0
	fi
	die "this command downloads or runs WordPress and WP-CLI, which needs Maxwell's permission in the shared build environment (see wp/README.md). Run it from a terminal, or set HCWA_WP_LOCAL_OK=1 once permission is given."
}

need_cmd() {
	command -v "$1" >/dev/null 2>&1 || die "'$1' is required but was not found on PATH"
}

check_php() {
	need_cmd "$PHP_BIN"
	"$PHP_BIN" -r 'exit( version_compare( PHP_VERSION, "8.1.0", "<" ) ? 1 : 0 );' \
		|| die "PHP 8.1 or newer is required (found $("$PHP_BIN" -r 'echo PHP_VERSION;'))"
	local ext
	for ext in pdo_sqlite mbstring phar; do
		"$PHP_BIN" -r "exit( extension_loaded( '$ext' ) ? 0 : 1 );" \
			|| die "the PHP extension '$ext' is required (php -m lists the loaded ones)"
	done
	"$PHP_BIN" -r "exit( extension_loaded( 'intl' ) ? 0 : 1 );" \
		|| warn "the PHP extension 'intl' is missing; Vietnamese text is not Unicode-normalized before the draft check"
}

check_name() {
	[[ -n "${1:-}" ]] || die "missing instance name (for example: main)"
	[[ "$1" =~ ^[a-z0-9][a-z0-9_-]{0,39}$ ]] \
		|| die "instance name '$1': use lowercase letters, digits, '-' and '_' (at most 40)"
	case "$1" in
		cache | ref) die "'$1' is reserved: .wp-local/$1 holds downloads or reference sources" ;;
	esac
}

check_port() {
	[[ "$1" =~ ^[0-9]+$ ]] && (( $1 >= 1024 && $1 <= 65535 )) \
		|| die "port '$1': use a number from 1024 to 65535"
}

check_env_type() {
	case "${1:-}" in
		local | staging | production) ;;
		*) die "environment type '${1:-}': use local, staging or production" ;;
	esac
}

# ---------------------------------------------------------------------------- instance paths

inst_dir() { printf '%s/%s' "$LOCAL_DIR" "$1"; }
docroot()  { printf '%s/wp' "$(inst_dir "$1")"; }

inst_port() {
	local file
	file="$(inst_dir "$1")/port"
	if [[ -s "$file" ]]; then cat "$file"; else printf '%s' "$DEFAULT_PORT"; fi
}

inst_url() { printf 'http://127.0.0.1:%s' "$(inst_port "$1")"; }

inst_env() {
	local file
	file="$(inst_dir "$1")/environment"
	if [[ -s "$file" ]]; then tr -d '[:space:]' <"$file"; else printf 'local'; fi
}

is_set_up() { [[ -f "$(inst_dir "$1")/wp-config.php" && -f "$(docroot "$1")/wp-settings.php" ]]; }

require_instance() {
	is_set_up "$1" || die "there is no instance '$1' yet: scripts/wp-local.sh setup $1"
}

# PID of the instance's running server, or nothing (exit 1).
server_pid() {
	local file pid
	file="$(inst_dir "$1")/server.pid"
	[[ -s "$file" ]] || return 1
	pid="$(cat "$file")"
	if [[ "$pid" =~ ^[0-9]+$ ]] && kill -0 "$pid" 2>/dev/null \
		&& ps -p "$pid" -o args= 2>/dev/null | grep -q -- "-S 127.0.0.1:"; then
		printf '%s' "$pid"
		return 0
	fi
	rm -f "$file"
	return 1
}

port_in_use() {
	(exec 3<>"/dev/tcp/127.0.0.1/$1") 2>/dev/null
}

# WP-CLI against an instance. HCWA_WP_URL tells wp-config.php the instance's address.
wpcli() {
	local name=$1
	shift
	[[ -f "$CACHE_DIR/wp-cli.phar" ]] || die "WP-CLI is not downloaded yet: scripts/wp-local.sh setup $name"
	HCWA_WP_URL="$(inst_url "$name")" "$PHP_BIN" -d memory_limit=512M "$CACHE_DIR/wp-cli.phar" \
		--path="$(docroot "$name")" "$@"
}

python_bin() {
	if [[ -x "$REPO/.venv/bin/python" ]]; then
		printf '%s' "$REPO/.venv/bin/python"
	elif [[ -x "$REPO/.venv/Scripts/python.exe" ]]; then
		printf '%s' "$REPO/.venv/Scripts/python.exe"
	else
		need_cmd python3
		printf 'python3'
	fi
}

# ---------------------------------------------------------------------------- downloads

download() {
	local url=$1 dest=$2
	[[ -s "$dest" ]] && return 0
	mkdir -p "$(dirname "$dest")"
	note "downloading $url"
	if ! curl -fsSL --retry 3 --proto '=https' --tlsv1.2 -o "$dest.part" "$url"; then
		rm -f "$dest.part"
		die "download failed: $url"
	fi
	mv "$dest.part" "$dest"
}

# verify_hash <algo> <file> <checksum file>: removes the download when it does not match.
verify_hash() {
	local algo=$1 file=$2 sumfile=$3 want got
	want="$(awk 'NR == 1 { print $1 }' "$sumfile")"
	got="$("$PHP_BIN" -r 'echo hash_file( $argv[1], $argv[2] );' "$algo" "$file")"
	if [[ -z "$want" || "$got" != "$want" ]]; then
		rm -f "$file" "$sumfile"
		die "checksum mismatch for $(basename "$file"); the download was removed, run setup again"
	fi
}

WP_ZIP=""
POLYLANG_ZIP=""
SQLITE_ZIP=""

fetch_downloads() {
	need_cmd curl
	mkdir -p "$CACHE_DIR"

	local wp_name wp_url
	if [[ "$WP_VERSION" == "latest" ]]; then
		wp_name="wordpress-latest.zip"
		wp_url="https://wordpress.org/latest.zip"
	else
		wp_name="wordpress-$WP_VERSION.zip"
		wp_url="https://wordpress.org/wordpress-$WP_VERSION.zip"
	fi
	WP_ZIP="$CACHE_DIR/$wp_name"
	download "$wp_url" "$WP_ZIP"
	download "$wp_url.sha1" "$WP_ZIP.sha1"
	verify_hash sha1 "$WP_ZIP" "$WP_ZIP.sha1"

	local cli_url="https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar"
	download "$cli_url" "$CACHE_DIR/wp-cli.phar"
	download "$cli_url.sha512" "$CACHE_DIR/wp-cli.phar.sha512"
	verify_hash sha512 "$CACHE_DIR/wp-cli.phar" "$CACHE_DIR/wp-cli.phar.sha512"

	POLYLANG_ZIP="$CACHE_DIR/$(plugin_zip_name polylang "$POLYLANG_VERSION")"
	download "https://downloads.wordpress.org/plugin/$(basename "$POLYLANG_ZIP")" "$POLYLANG_ZIP"

	SQLITE_ZIP="$CACHE_DIR/$(plugin_zip_name sqlite-database-integration "$SQLITE_VERSION")"
	download "https://downloads.wordpress.org/plugin/$(basename "$SQLITE_ZIP")" "$SQLITE_ZIP"
}

plugin_zip_name() {
	if [[ "$2" == "latest" ]]; then printf '%s.latest-stable.zip' "$1"; else printf '%s.%s.zip' "$1" "$2"; fi
}

# Package URL for one language from a wordpress.org translations API response, or nothing.
lang_pack_url() {
	local api=$1 lang=$2
	curl -fsSL --retry 2 --proto '=https' "$api" 2>/dev/null | "$PHP_BIN" -r '
		$data = json_decode( (string) stream_get_contents( STDIN ), true );
		foreach ( (array) ( $data["translations"] ?? array() ) as $t ) {
			$package = (string) ( $t["package"] ?? "" );
			if ( ( $t["language"] ?? "" ) === $argv[1] && 0 === strpos( $package, "https://downloads.wordpress.org/" ) ) {
				echo $package;
				break;
			}
		}' "$lang" || true
}

install_language_packs() {
	local name=$1 wp langdir version url zip
	wp="$(docroot "$name")"
	langdir="$wp/wp-content/languages"
	mkdir -p "$langdir/plugins"

	if [[ -f "$langdir/vi.mo" ]]; then
		note "Vietnamese core language pack already installed"
	else
		version="$(sed -n "s/^\$wp_version = '\([^']*\)';.*/\1/p" "$wp/wp-includes/version.php")"
		[[ -n "$version" ]] || die "could not read the WordPress version from wp-includes/version.php"
		url="$(lang_pack_url "https://api.wordpress.org/translations/core/1.0/?version=$version" vi)"
		[[ -n "$url" ]] || die "no Vietnamese language pack found for WordPress $version on api.wordpress.org"
		zip="$CACHE_DIR/translations/wordpress-$version-vi.zip"
		download "$url" "$zip"
		unzip -q -o "$zip" -d "$langdir"
		[[ -f "$langdir/vi.mo" ]] || die "the Vietnamese language pack did not contain vi.mo"
		note "Vietnamese core language pack installed (WordPress $version)"
	fi

	# Polylang's own Vietnamese admin strings: nice to have, so only a warning if missing.
	if [[ ! -f "$langdir/plugins/polylang-vi.mo" ]]; then
		version="$(sed -n 's/^ \* Version: *\([0-9.]*\).*/\1/p' "$wp/wp-content/plugins/polylang/polylang.php" | head -n 1)"
		url="$(lang_pack_url "https://api.wordpress.org/translations/plugins/1.0/?slug=polylang&version=$version" vi)"
		if [[ -n "$url" ]]; then
			zip="$CACHE_DIR/translations/polylang-$version-vi.zip"
			download "$url" "$zip"
			unzip -q -o "$zip" -d "$langdir/plugins"
		else
			warn "no Vietnamese language pack found for Polylang $version; its admin screens stay in English"
		fi
	fi
}

# ---------------------------------------------------------------------------- setup steps

unpack_wordpress() {
	local name=$1 dir tmp
	dir="$(inst_dir "$name")"
	if [[ -f "$(docroot "$name")/wp-settings.php" ]]; then
		note "WordPress already unpacked"
		return 0
	fi
	tmp="$(mktemp -d "$dir/.unpack.XXXXXX")"
	unzip -q "$WP_ZIP" -d "$tmp"
	[[ -f "$tmp/wordpress/wp-settings.php" ]] || die "$(basename "$WP_ZIP") does not look like a WordPress release"
	rm -rf "$(docroot "$name")"
	mv "$tmp/wordpress" "$(docroot "$name")"
	rm -rf "$tmp"
	note "WordPress unpacked into .wp-local/$name/wp"
}

unpack_plugin() {
	local name=$1 slug=$2 zip=$3 plugins
	plugins="$(docroot "$name")/wp-content/plugins"
	if [[ -d "$plugins/$slug" ]]; then
		note "$slug already unpacked"
		return 0
	fi
	unzip -q "$zip" -d "$plugins"
	[[ -d "$plugins/$slug" ]] || die "$(basename "$zip") did not contain a $slug folder"
	note "$slug unpacked"
}

# wp-content/db.php from the SQLite plugin's db.copy, with the same two placeholders
# replaced that the plugin's own activation code replaces (activate.php).
write_db_dropin() {
	local name=$1 content impl
	content="$(docroot "$name")/wp-content"
	impl="$content/plugins/sqlite-database-integration"
	if [[ -f "$content/db.php" ]] && grep -q "SQLITE_DB_DROPIN_VERSION" "$content/db.php"; then
		note "SQLite drop-in already in place"
		return 0
	fi
	[[ -f "$impl/db.copy" ]] || die "sqlite-database-integration/db.copy is missing"
	"$PHP_BIN" -r '
		$source = (string) file_get_contents( $argv[1] );
		echo str_replace(
			array( "{SQLITE_IMPLEMENTATION_FOLDER_PATH}", "{SQLITE_PLUGIN}" ),
			array( $argv[2], "sqlite-database-integration/load.php" ),
			$source
		);' "$impl/db.copy" "$impl" >"$content/db.php.part"
	mv "$content/db.php.part" "$content/db.php"
	note "SQLite drop-in written to wp-content/db.php"
}

write_env_file() {
	local name=$1 type=$2 dir
	dir="$(inst_dir "$name")"
	printf '%s\n' "$type" >"$dir/environment"
	cat >"$dir/hcwa-env.php" <<EOF
<?php
// Written by scripts/wp-local.sh: local, staging or production.
define( 'WP_ENVIRONMENT_TYPE', '$type' );
EOF
}

salt() { "$PHP_BIN" -r 'echo bin2hex( random_bytes( 32 ) );'; }

# wp-config.php one folder above WordPress, so the web server never sees it (WordPress
# and WP-CLI both look there when ABSPATH has none).
write_config() {
	local name=$1 port=$2 dir key
	dir="$(inst_dir "$name")"
	if [[ -f "$dir/wp-config.php" ]]; then
		note "wp-config.php already written"
		return 0
	fi
	{
		cat <<EOF
<?php
/**
 * Local HCWA WordPress instance "$name", written by scripts/wp-local.sh setup.
 * Local development only: admin/admin, PHP errors on screen. Lost on reset.
 */

// SQLite instead of MySQL, through wp-content/db.php (sqlite-database-integration).
define( 'DB_ENGINE', 'sqlite' );
define( 'DB_PATH', __DIR__ . '/data/hcwa.sqlite' );
define( 'DB_NAME', 'wordpress' );
define( 'DB_USER', '' );
define( 'DB_PASSWORD', '' );
define( 'DB_HOST', '' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

// The address comes from scripts/wp-local.sh (HCWA_WP_URL), so "start $name <port>"
// can move the instance to another port without touching the database.
\$hcwa_local_url = getenv( 'HCWA_WP_URL' );
if ( ! is_string( \$hcwa_local_url ) || ! preg_match( '#^http://(?:127\\.0\\.0\\.1|localhost):[0-9]{2,5}\$#', \$hcwa_local_url ) ) {
	\$hcwa_local_url = 'http://127.0.0.1:$port';
}
define( 'WP_HOME', \$hcwa_local_url );
define( 'WP_SITEURL', \$hcwa_local_url );
unset( \$hcwa_local_url );

EOF
		for key in AUTH_KEY SECURE_AUTH_KEY LOGGED_IN_KEY NONCE_KEY AUTH_SALT SECURE_AUTH_SALT LOGGED_IN_SALT NONCE_SALT; do
			printf "define( '%s', '%s' );\n" "$key" "$(salt)"
		done
		cat <<'EOF'

$table_prefix = 'wp_';

// local, staging or production: scripts/wp-local.sh env <name> <type>.
require __DIR__ . '/hcwa-env.php';

define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', __DIR__ . '/logs/debug.log' );
define( 'WP_DEBUG_DISPLAY', true );

// The theme changes all the time here: never cache its theme.json or pattern list.
define( 'WP_DEVELOPMENT_MODE', 'theme' );

define( 'DISALLOW_FILE_EDIT', true );
define( 'AUTOMATIC_UPDATER_DISABLED', true );
define( 'WP_AUTO_UPDATE_CORE', false );

// No background requests: no cron loopbacks, no calls to wordpress.org or anyone else.
define( 'DISABLE_WP_CRON', true );
define( 'WP_HTTP_BLOCK_EXTERNAL', true );

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/wp/' );
}

require_once ABSPATH . 'wp-settings.php';
EOF
	} >"$dir/wp-config.php.part"
	mv "$dir/wp-config.php.part" "$dir/wp-config.php"
	note "wp-config.php written"
}

link_repo_code() {
	local name=$1 content
	content="$(docroot "$name")/wp-content"
	link_one "$REPO/wp/plugins/$CORE_SLUG" "$content/plugins/$CORE_SLUG"
	link_one "$REPO/wp/themes/$THEME_SLUG" "$content/themes/$THEME_SLUG"
}

link_one() {
	local target=$1 link=$2
	[[ -d "$target" ]] || die "missing $target"
	if [[ -e "$link" && ! -L "$link" ]]; then
		die "$link exists and is not a symlink; remove it so the repo copy can be linked"
	fi
	ln -sfn "$target" "$link"
	note "linked ${link#"$LOCAL_DIR"/} -> ${target#"$REPO"/}"
}

# ---------------------------------------------------------------------------- commands

cmd_setup() {
	local name=${1:-} port=${2:-}
	check_name "$name"
	if [[ -z "$port" ]]; then port="$(inst_port "$name")"; fi
	check_port "$port"
	require_permission
	check_php
	need_cmd unzip

	local dir
	dir="$(inst_dir "$name")"
	say "Setting up '$name' in .wp-local/$name (http://127.0.0.1:$port)"
	mkdir -p "$dir/data" "$dir/logs"
	printf '%s\n' "$port" >"$dir/port"

	say "Downloads (cached in .wp-local/cache)"
	fetch_downloads

	say "Files"
	unpack_wordpress "$name"
	unpack_plugin "$name" polylang "$POLYLANG_ZIP"
	unpack_plugin "$name" sqlite-database-integration "$SQLITE_ZIP"
	write_db_dropin "$name"
	[[ -f "$dir/hcwa-env.php" ]] || write_env_file "$name" local
	write_config "$name" "$port"
	link_repo_code "$name"

	say "Installing WordPress"
	if wpcli "$name" core is-installed >/dev/null 2>&1; then
		note "already installed"
	else
		wpcli "$name" core install --url="http://127.0.0.1:$port" --title="$SITE_TITLE" \
			--admin_user="$ADMIN_USER" --admin_password="$ADMIN_PASS" --admin_email="$ADMIN_EMAIL" --skip-email
	fi

	say "Language packs"
	install_language_packs "$name"

	say "Plugins and theme"
	# Separate runs: hcwa-core's activation flushes the rewrite rules, which should happen
	# in a request where Polylang is already loaded.
	wpcli "$name" plugin activate sqlite-database-integration polylang
	wpcli "$name" plugin activate "$CORE_SLUG"
	wpcli "$name" theme activate "$THEME_SLUG"

	say "Permalinks"
	wpcli "$name" rewrite structure '/%postname%/'

	say "Ready: '$name' ($(inst_env "$name"))"
	note "Next: scripts/wp-local.sh seed $name"
	note "      scripts/wp-local.sh start $name"
	note "Admin: http://127.0.0.1:$port/wp-admin/ ($ADMIN_USER / $ADMIN_PASS, this computer only)"
}

cmd_start() {
	local name=${1:-} port=${2:-}
	check_name "$name"
	require_instance "$name"
	require_permission

	local dir pid url
	dir="$(inst_dir "$name")"
	if pid="$(server_pid "$name")"; then
		say "'$name' is already running at $(inst_url "$name")/ (pid $pid)"
		return 0
	fi
	if [[ -n "$port" ]]; then
		check_port "$port"
		printf '%s\n' "$port" >"$dir/port"
	fi
	port="$(inst_port "$name")"
	url="$(inst_url "$name")"
	port_in_use "$port" && die "port $port is in use; pick another: scripts/wp-local.sh start $name <port>"

	mkdir -p "$dir/logs"
	say "Starting '$name' at $url/"
	HCWA_WP_URL="$url" PHP_CLI_SERVER_WORKERS="$PHP_WORKERS" \
		nohup "$PHP_BIN" -S "127.0.0.1:$port" -t "$(docroot "$name")" "$REPO/scripts/wp-router.php" \
		>>"$dir/logs/server.log" 2>&1 &
	printf '%s\n' "$!" >"$dir/server.pid"

	local tries=0 code=""
	while ((tries < 50)); do
		code="$(curl --noproxy '*' -s -o /dev/null -w '%{http_code}' "$url/wp-login.php" || true)"
		[[ "$code" =~ ^[23] ]] && break
		if ! server_pid "$name" >/dev/null; then
			tail -n 20 "$dir/logs/server.log" >&2 || true
			die "the server stopped right away (log above, full log in .wp-local/$name/logs/server.log)"
		fi
		sleep 0.2
		tries=$((tries + 1))
	done
	[[ "$code" =~ ^[23] ]] || warn "no answer from $url/wp-login.php yet (last status: ${code:-none}); see .wp-local/$name/logs/"

	note "Site:  $url/   English: $url/en/"
	note "Admin: $url/wp-admin/ ($ADMIN_USER / $ADMIN_PASS)"
	note "Environment: $(inst_env "$name"). Logs: .wp-local/$name/logs/ (server.log, debug.log)"
	note "Stop:  scripts/wp-local.sh stop $name"
}

stop_one() {
	local name=$1 pid children="" tries=0
	if ! pid="$(server_pid "$name")"; then
		note "'$name' is not running"
		return 0
	fi
	# PHP_CLI_SERVER_WORKERS forks workers: note them before the main process goes away.
	if command -v pgrep >/dev/null 2>&1; then children="$(pgrep -P "$pid" || true)"; fi
	kill "$pid" 2>/dev/null || true
	while kill -0 "$pid" 2>/dev/null && ((tries < 25)); do
		sleep 0.2
		tries=$((tries + 1))
	done
	kill -0 "$pid" 2>/dev/null && kill -9 "$pid" 2>/dev/null || true
	if [[ -n "$children" ]]; then
		# shellcheck disable=SC2086 # one PID per word
		kill $children 2>/dev/null || true
	fi
	rm -f "$(inst_dir "$name")/server.pid"
	say "Stopped '$name'"
}

cmd_stop() {
	if [[ -n "${1:-}" ]]; then
		check_name "$1"
		stop_one "$1"
		return 0
	fi
	local found=0 pidfile
	for pidfile in "$LOCAL_DIR"/*/server.pid; do
		[[ -e "$pidfile" ]] || continue
		found=1
		stop_one "$(basename "$(dirname "$pidfile")")"
	done
	((found)) || note "no instance is running"
}

cmd_wp() {
	local name=${1:-}
	check_name "$name"
	shift
	[[ "${1:-}" == "--" ]] && shift
	require_instance "$name"
	require_permission
	wpcli "$name" "$@"
}

cmd_env() {
	local name=${1:-} type=${2:-}
	check_name "$name"
	check_env_type "$type"
	require_instance "$name"
	write_env_file "$name" "$type"
	say "'$name' is now $type (WP_ENVIRONMENT_TYPE, read on every request: no restart needed)"
	if [[ "$type" == "production" ]]; then
		note "Unverified impact figures, pending chips, draft outlines, the donation placeholder"
		note "and the noindex tag are gone; publishing text with [DRAFT is refused."
		note "Check it: scripts/wp-local.sh smoke $name (runs with --production)."
	else
		note "Unverified figures show with a pending chip, drafts are outlined, pages are noindex."
	fi
}

cmd_seed() {
	local name=${1:-}
	check_name "$name"
	require_instance "$name"
	require_permission
	[[ "$(inst_env "$name")" != "production" ]] \
		|| die "'$name' is set to production; seed only local or staging instances (scripts/wp-local.sh env $name local)"

	local manifest
	manifest="$(inst_dir "$name")/seed-urls.json"
	# Two WP-CLI runs: Polylang wires its post and term hooks at startup only when
	# languages exist, so content goes in through a fresh run after the languages.
	say "Seeding languages and Polylang settings"
	wpcli "$name" --user="$ADMIN_USER" eval-file "$REPO/scripts/wp-seed.php" languages "$REPO"
	say "Seeding content from content/draft/"
	wpcli "$name" --user="$ADMIN_USER" eval-file "$REPO/scripts/wp-seed.php" content "$REPO" "$manifest"
	say "Rebuilding rewrite rules"
	wpcli "$name" rewrite flush
	say "Seeded '$name'"
	note "URL list for the smoke test: .wp-local/$name/seed-urls.json"
	note "Check: scripts/wp-local.sh wp $name -- hcwa coverage"
}

cmd_smoke() {
	local name=${1:-}
	check_name "$name"
	shift
	[[ "${1:-}" == "--" ]] && shift
	require_instance "$name"
	server_pid "$name" >/dev/null || die "'$name' is not running: scripts/wp-local.sh start $name"

	local manifest args
	manifest="$(inst_dir "$name")/seed-urls.json"
	[[ -s "$manifest" ]] || die "no seed-urls.json for '$name': scripts/wp-local.sh seed $name"
	args=("$(inst_url "$name")" --manifest "$manifest")
	[[ "$(inst_env "$name")" == "production" ]] && args+=(--production)
	"$(python_bin)" -I "$REPO/scripts/wp-smoke.py" "${args[@]}" "$@"
}

cmd_reset() {
	local name=${1:-} yes=${2:-}
	check_name "$name"
	require_permission
	local dir port answer
	dir="$(inst_dir "$name")"
	[[ -d "$dir" ]] || die "there is no instance '$name'"
	if [[ "$yes" != "--yes" ]]; then
		[[ -t 0 ]] || die "reset deletes .wp-local/$name; add --yes when running without a terminal"
		read -r -p "Delete .wp-local/$name (database, uploads, settings)? Type the name to confirm: " answer
		[[ "$answer" == "$name" ]] || die "not confirmed; nothing was deleted"
	fi
	port="$(inst_port "$name")"
	stop_one "$name"
	# rm does not follow the plugin and theme symlinks: the repo copies stay untouched.
	rm -rf -- "${LOCAL_DIR:?}/$name"
	say "Deleted .wp-local/$name"
	cmd_setup "$name" "$port"
}

cmd_list() {
	local dir name state pid found=0
	for dir in "$LOCAL_DIR"/*/; do
		name="$(basename "$dir")"
		[[ "$name" == "cache" || "$name" == "ref" ]] && continue
		[[ -f "$dir/wp-config.php" ]] || continue
		found=1
		if pid="$(server_pid "$name")"; then state="running (pid $pid)"; else state="stopped"; fi
		printf '%-16s %-26s %-11s %s\n' "$name" "$(inst_url "$name")/" "$(inst_env "$name")" "$state"
	done
	((found)) || note "no instances yet: scripts/wp-local.sh setup main"
}

main() {
	local command=${1:-help}
	[[ $# -gt 0 ]] && shift
	case "$command" in
		setup) cmd_setup "$@" ;;
		start) cmd_start "$@" ;;
		stop) cmd_stop "$@" ;;
		wp) cmd_wp "$@" ;;
		env) cmd_env "$@" ;;
		seed) cmd_seed "$@" ;;
		smoke) cmd_smoke "$@" ;;
		reset) cmd_reset "$@" ;;
		list) cmd_list ;;
		help | -h | --help) usage ;;
		*)
			usage >&2
			die "unknown command '$command'"
			;;
	esac
}

main "$@"
