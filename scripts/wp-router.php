<?php
/**
 * Router for PHP's built-in web server, used by `scripts/wp-local.sh start`:
 *
 *   php -S 127.0.0.1:8881 -t .wp-local/<name>/wp scripts/wp-router.php
 *
 * Does what Apache's or nginx's WordPress rewrite rules do:
 * - existing static files are served as they are;
 * - existing PHP files run;
 * - directories get a trailing slash and run their index.php;
 * - everything else (pretty permalinks) runs WordPress's index.php.
 *
 * Dotfiles (.htaccess, .git, .ht.sqlite), database files, logs, PHAR archives and
 * wp-config.php are never served: the built-in server would otherwise hand them out as
 * plain downloads.
 *
 * Local development only. The built-in server must never face a public network.
 *
 * The WordPress files are required at the top level of this file (not inside a function)
 * because WordPress expects its bootstrap variables to be globals.
 */

if ( 'cli-server' !== PHP_SAPI ) {
	http_response_code( 404 );
	exit;
}

$hcwa_router_root = rtrim( (string) $_SERVER['DOCUMENT_ROOT'], '/' );
$hcwa_router_uri  = (string) ( $_SERVER['REQUEST_URI'] ?? '/' );
$hcwa_router_path = '/' . ltrim( rawurldecode( (string) parse_url( $hcwa_router_uri, PHP_URL_PATH ) ), '/' );

// Refuse NUL bytes, ".." segments, dotfiles and files that must stay private.
if (
	str_contains( $hcwa_router_path, "\0" )
	|| preg_match( '#(?:^|/)\.#', $hcwa_router_path )
	|| preg_match( '#\.(?:sqlite3?|db|log|sql|ini|phar|bak|swp)$#i', $hcwa_router_path )
	|| preg_match( '#(?:^|/)wp-config\.php$#i', $hcwa_router_path )
) {
	http_response_code( 404 );
	header( 'Content-Type: text/plain; charset=utf-8' );
	echo "Not found\n";
	return true;
}

$hcwa_router_file = $hcwa_router_root . $hcwa_router_path;

if ( is_dir( $hcwa_router_file ) ) {
	if ( ! str_ends_with( $hcwa_router_path, '/' ) ) {
		$hcwa_router_query = (string) parse_url( $hcwa_router_uri, PHP_URL_QUERY );
		header( 'Location: ' . $hcwa_router_path . '/' . ( '' !== $hcwa_router_query ? '?' . $hcwa_router_query : '' ), true, 301 );
		return true;
	}
	if ( ! is_file( $hcwa_router_file . 'index.php' ) ) {
		// No index.php (for example a plugin's assets folder): let the server answer.
		return false;
	}
	$hcwa_router_path .= 'index.php';
	$hcwa_router_file .= 'index.php';
}

if ( is_file( $hcwa_router_file ) ) {
	if ( ! preg_match( '#\.php$#i', $hcwa_router_file ) ) {
		// Static file: the built-in server sends it with its MIME type.
		return false;
	}
	$hcwa_router_script = $hcwa_router_path;
} else {
	// Pretty permalink, or a missing file that WordPress answers with its 404 page.
	$hcwa_router_script = '/index.php';
	$hcwa_router_file   = $hcwa_router_root . '/index.php';
}

// What a rewrite to the script would set on Apache. WordPress reads PHP_SELF to find the
// current admin screen, and must not see a PATH_INFO the built-in server guessed.
$_SERVER['SCRIPT_NAME']     = $hcwa_router_script;
$_SERVER['PHP_SELF']        = $hcwa_router_script;
$_SERVER['SCRIPT_FILENAME'] = $hcwa_router_file;
unset( $_SERVER['PATH_INFO'], $_SERVER['ORIG_PATH_INFO'] );

chdir( dirname( $hcwa_router_file ) );

unset( $hcwa_router_root, $hcwa_router_uri, $hcwa_router_path, $hcwa_router_query, $hcwa_router_script );

require $hcwa_router_file;
