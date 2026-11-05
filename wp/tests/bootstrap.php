<?php
/**
 * Minimal WordPress stand-ins for unit tests of the plugin's and theme's pure functions.
 * WordPress itself is never loaded. Run the suite with: php wp/tests/run.php
 *
 * Every stub is guarded with function_exists()/class_exists(). To add stubs for your own
 * area, create wp/tests/<area>-stubs.php: it is loaded after this file's stubs and
 * before the hcwa-core plugin, so it can also stub things the plugin calls at load time.
 *
 * Test state lives in $GLOBALS['hcwa_test'] and is reset before each test file:
 *   options      get_option() values ('home' defaults to https://hcwa.test)
 *   env          wp_get_environment_type() ('staging' by default)
 *   post_meta    [post_id][key] => value for get_post_meta()
 *   posts        [post_id] => WP_Post for get_post() / get_post_type()
 *   can          current_user_can() / user_can() result
 *   hooks        add_action/add_filter calls, [hook][] => [callback, priority, args]
 *   fired        do_action calls, [] => [hook, args]
 * Optional (absent after a reset, read with defaults):
 *   translations [post_id] => [lang => id] for pll_get_post_translations() (the only
 *                Polylang function stubbed, so other code keeps its no-Polylang paths)
 *   is           [conditional tag name] => bool for is_embed(), is_feed(), ...
 *   query_vars   get_query_var() values
 *   upload_urls  [url] => attachment ID for attachment_url_to_postid()
 *   children     [parent_id] => attachment IDs for get_children()
 *   updated      wp_update_post() calls
 *   current_post the global post: get_post() without an argument
 *
 * @package hcwa-core
 */

error_reporting( E_ALL );
// hcwa_log() also writes to the PHP error log; keep test output clean (tests listen to the 'hcwa_log' action instead).
ini_set( 'error_log', '/' === DIRECTORY_SEPARATOR ? '/dev/null' : 'NUL' );

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__, 2 ) . '/.wp-test-abspath/' );
}
foreach ( array(
	'MINUTE_IN_SECONDS' => 60,
	'HOUR_IN_SECONDS'   => 3600,
	'DAY_IN_SECONDS'    => 86400,
	'WEEK_IN_SECONDS'   => 604800,
	'OBJECT'            => 'OBJECT',
	'ARRAY_A'           => 'ARRAY_A',
) as $hcwa_const => $hcwa_value ) {
	if ( ! defined( $hcwa_const ) ) {
		define( $hcwa_const, $hcwa_value );
	}
}
unset( $hcwa_const, $hcwa_value );

/**
 * Resets the shared test state. run.php calls it before each test file.
 */
function hcwa_test_reset(): void {
	$GLOBALS['hcwa_test'] = array(
		'options'   => array(
			'home'                 => 'https://hcwa.test',
			'siteurl'              => 'https://hcwa.test',
			'permalink_structure'  => '/%postname%/',
		),
		'env'       => 'staging',
		'post_meta' => array(),
		'posts'     => array(),
		'can'       => true,
		'hooks'     => $GLOBALS['hcwa_test']['hooks'] ?? array(),
		'fired'     => array(),
	);
}
hcwa_test_reset();

/* ---------- classes ---------- */

if ( ! class_exists( 'WP_Post' ) ) {
	/** Property bag standing in for WP_Post. */
	#[AllowDynamicProperties]
	class WP_Post {
		public $ID = 0;
		public $post_type = 'post';
		public $post_status = 'publish';
		public $post_title = '';
		public $post_name = '';
		public $post_content = '';
		public $post_excerpt = '';
		public $post_parent = 0;
		public $menu_order = 0;

		/** @param array $props Properties. */
		public function __construct( $props = array() ) {
			foreach ( (array) $props as $key => $value ) {
				$this->$key = $value;
			}
		}
	}
}

if ( ! class_exists( 'WP_Term' ) ) {
	/** Property bag standing in for WP_Term. */
	#[AllowDynamicProperties]
	class WP_Term {
		public $term_id = 0;
		public $name = '';
		public $slug = '';
		public $taxonomy = '';
		public $description = '';
		public $parent = 0;

		/** @param array $props Properties. */
		public function __construct( $props = array() ) {
			foreach ( (array) $props as $key => $value ) {
				$this->$key = $value;
			}
		}
	}
}

if ( ! class_exists( 'WP_Error' ) ) {
	/** Minimal WP_Error. */
	class WP_Error {
		public $code;
		public $message;

		public function __construct( $code = '', $message = '' ) {
			$this->code    = $code;
			$this->message = $message;
		}

		public function get_error_message() {
			return $this->message;
		}
	}
}

/* ---------- hooks ---------- */

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		$GLOBALS['hcwa_test']['hooks'][ $hook ][] = array( $callback, $priority, $accepted_args );
		return true;
	}
}
if ( ! function_exists( 'add_action' ) ) {
	function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		return add_filter( $hook, $callback, $priority, $accepted_args );
	}
}
if ( ! function_exists( 'remove_filter' ) ) {
	function remove_filter( $hook, $callback, $priority = 10 ) {
		return true;
	}
}
if ( ! function_exists( 'remove_action' ) ) {
	function remove_action( $hook, $callback, $priority = 10 ) {
		return true;
	}
}
if ( ! function_exists( 'has_filter' ) ) {
	function has_filter( $hook, $callback = false ) {
		return ! empty( $GLOBALS['hcwa_test']['hooks'][ $hook ] );
	}
}
if ( ! function_exists( 'has_action' ) ) {
	function has_action( $hook, $callback = false ) {
		return has_filter( $hook, $callback );
	}
}
if ( ! function_exists( 'apply_filters' ) ) {
	/** Passthrough: returns the value unchanged. */
	function apply_filters( $hook, $value, ...$args ) {
		return $value;
	}
}
if ( ! function_exists( 'do_action' ) ) {
	/** Records the call; runs nothing. */
	function do_action( $hook, ...$args ) {
		$GLOBALS['hcwa_test']['fired'][] = array( $hook, $args );
	}
}
if ( ! function_exists( 'did_action' ) ) {
	function did_action( $hook ) {
		return 0;
	}
}
if ( ! function_exists( 'doing_action' ) ) {
	function doing_action( $hook = null ) {
		return false;
	}
}
if ( ! function_exists( 'register_activation_hook' ) ) {
	function register_activation_hook( $file, $callback ) {
		add_action( 'activate_' . basename( $file ), $callback );
	}
}
if ( ! function_exists( 'register_deactivation_hook' ) ) {
	function register_deactivation_hook( $file, $callback ) {
		add_action( 'deactivate_' . basename( $file ), $callback );
	}
}

/* ---------- paths and URLs ---------- */

if ( ! function_exists( 'plugin_dir_path' ) ) {
	function plugin_dir_path( $file ) {
		return rtrim( dirname( $file ), '/' ) . '/';
	}
}
if ( ! function_exists( 'plugin_dir_url' ) ) {
	function plugin_dir_url( $file ) {
		return 'https://hcwa.test/wp-content/plugins/' . basename( dirname( $file ) ) . '/';
	}
}
if ( ! function_exists( 'plugin_basename' ) ) {
	function plugin_basename( $file ) {
		return basename( dirname( $file ) ) . '/' . basename( $file );
	}
}
if ( ! function_exists( 'trailingslashit' ) ) {
	function trailingslashit( $value ) {
		return rtrim( (string) $value, '/\\' ) . '/';
	}
}
if ( ! function_exists( 'untrailingslashit' ) ) {
	function untrailingslashit( $value ) {
		return rtrim( (string) $value, '/\\' );
	}
}
if ( ! function_exists( 'user_trailingslashit' ) ) {
	function user_trailingslashit( $value ) {
		return trailingslashit( $value );
	}
}
if ( ! function_exists( 'home_url' ) ) {
	function home_url( $path = '' ) {
		return rtrim( (string) get_option( 'home' ), '/' ) . '/' . ltrim( (string) $path, '/' );
	}
}
if ( ! function_exists( 'admin_url' ) ) {
	function admin_url( $path = '' ) {
		return 'https://hcwa.test/wp-admin/' . ltrim( (string) $path, '/' );
	}
}
if ( ! function_exists( 'wp_parse_url' ) ) {
	function wp_parse_url( $url, $component = -1 ) {
		return parse_url( $url, $component );
	}
}
if ( ! function_exists( 'add_query_arg' ) ) {
	/** Supports add_query_arg( key, value, url ) and add_query_arg( array, url ). */
	function add_query_arg( ...$args ) {
		if ( is_array( $args[0] ) ) {
			$params = $args[0];
			$url    = $args[1] ?? '';
		} else {
			$params = array( $args[0] => $args[1] ?? '' );
			$url    = $args[2] ?? '';
		}
		$parts = explode( '#', (string) $url, 2 );
		$base  = $parts[0];
		$frag  = isset( $parts[1] ) ? '#' . $parts[1] : '';
		$query = array();
		if ( false !== strpos( $base, '?' ) ) {
			list( $base, $qs ) = explode( '?', $base, 2 );
			parse_str( $qs, $query );
		}
		$query = array_merge( $query, $params );
		$query = array_filter( $query, static fn( $v ) => false !== $v );
		return $base . ( $query ? '?' . http_build_query( $query ) : '' ) . $frag;
	}
}
if ( ! function_exists( 'remove_query_arg' ) ) {
	function remove_query_arg( $keys, $url = '' ) {
		return add_query_arg( array_fill_keys( (array) $keys, false ), $url );
	}
}

/* ---------- escaping, sanitizing, i18n ---------- */

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}
if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}
if ( ! function_exists( 'esc_textarea' ) ) {
	function esc_textarea( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}
if ( ! function_exists( 'esc_js' ) ) {
	function esc_js( $text ) {
		return addslashes( (string) $text );
	}
}
if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( $url ) {
		$url = trim( (string) $url );
		if ( '' === $url || preg_match( '/^\s*(javascript|data|vbscript):/i', $url ) ) {
			return '';
		}
		return htmlspecialchars( $url, ENT_QUOTES, 'UTF-8' );
	}
}
if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( $url ) {
		$url = trim( (string) $url );
		return ( '' === $url || preg_match( '/^\s*(javascript|data|vbscript):/i', $url ) ) ? '' : $url;
	}
}
if ( ! function_exists( 'wp_kses_post' ) ) {
	function wp_kses_post( $data ) {
		return preg_replace( '#<(script|style|iframe)[^>]*>.*?</\1>#is', '', (string) $data );
	}
}
if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	function wp_strip_all_tags( $text ) {
		return trim( strip_tags( (string) $text ) );
	}
}
if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $str ) {
		return trim( (string) preg_replace( '/[\r\n\t ]+/', ' ', strip_tags( (string) $str ) ) );
	}
}
if ( ! function_exists( 'sanitize_textarea_field' ) ) {
	function sanitize_textarea_field( $str ) {
		return trim( strip_tags( (string) $str ) );
	}
}
if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $key ) {
		return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) );
	}
}
if ( ! function_exists( 'sanitize_title' ) ) {
	function sanitize_title( $title ) {
		return trim( (string) preg_replace( '/[^a-z0-9]+/', '-', strtolower( (string) $title ) ), '-' );
	}
}
if ( ! function_exists( 'sanitize_email' ) ) {
	function sanitize_email( $email ) {
		$email = trim( (string) $email );
		return false === filter_var( $email, FILTER_VALIDATE_EMAIL ) ? '' : $email;
	}
}
if ( ! function_exists( 'absint' ) ) {
	function absint( $value ) {
		return abs( (int) $value );
	}
}
if ( ! function_exists( 'wp_unslash' ) ) {
	function wp_unslash( $value ) {
		return is_array( $value ) ? array_map( 'wp_unslash', $value ) : ( is_string( $value ) ? stripslashes( $value ) : $value );
	}
}
if ( ! function_exists( 'wp_slash' ) ) {
	function wp_slash( $value ) {
		return is_array( $value ) ? array_map( 'wp_slash', $value ) : ( is_string( $value ) ? addslashes( $value ) : $value );
	}
}
if ( ! function_exists( 'wp_parse_args' ) ) {
	function wp_parse_args( $args, $defaults = array() ) {
		if ( is_string( $args ) ) {
			parse_str( $args, $args );
		}
		return array_merge( (array) $defaults, (array) $args );
	}
}
if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $value, $flags = 0, $depth = 512 ) {
		return json_encode( $value, $flags, $depth );
	}
}
if ( ! function_exists( 'rest_sanitize_boolean' ) ) {
	function rest_sanitize_boolean( $value ) {
		return is_string( $value ) ? ! in_array( strtolower( $value ), array( '', '0', 'false' ), true ) : (bool) $value;
	}
}
if ( ! function_exists( 'wp_basename' ) ) {
	function wp_basename( $path, $suffix = '' ) {
		return basename( (string) $path, $suffix );
	}
}
if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) {
		return $thing instanceof WP_Error;
	}
}
if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) {
		return $text;
	}
}
if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( $text, $domain = 'default' ) {
		return esc_html( $text );
	}
}
if ( ! function_exists( 'esc_attr__' ) ) {
	function esc_attr__( $text, $domain = 'default' ) {
		return esc_attr( $text );
	}
}
if ( ! function_exists( '_e' ) ) {
	function _e( $text, $domain = 'default' ) {
		echo $text; // phpcs:ignore WordPress.Security.EscapeOutput
	}
}
if ( ! function_exists( 'esc_html_e' ) ) {
	function esc_html_e( $text, $domain = 'default' ) {
		echo esc_html( $text );
	}
}
if ( ! function_exists( 'esc_attr_e' ) ) {
	function esc_attr_e( $text, $domain = 'default' ) {
		echo esc_attr( $text );
	}
}
if ( ! function_exists( '_x' ) ) {
	function _x( $text, $context, $domain = 'default' ) {
		return $text;
	}
}
if ( ! function_exists( '_n' ) ) {
	function _n( $single, $plural, $number, $domain = 'default' ) {
		return 1 === (int) $number ? $single : $plural;
	}
}
if ( ! function_exists( 'determine_locale' ) ) {
	function determine_locale() {
		return 'vi';
	}
}
if ( ! function_exists( 'get_locale' ) ) {
	function get_locale() {
		return 'vi';
	}
}

/* ---------- options, environment, users ---------- */

if ( ! function_exists( 'get_option' ) ) {
	function get_option( $name, $default = false ) {
		return array_key_exists( $name, $GLOBALS['hcwa_test']['options'] ) ? $GLOBALS['hcwa_test']['options'][ $name ] : $default;
	}
}
if ( ! function_exists( 'update_option' ) ) {
	function update_option( $name, $value, $autoload = null ) {
		$GLOBALS['hcwa_test']['options'][ $name ] = $value;
		return true;
	}
}
if ( ! function_exists( 'add_option' ) ) {
	function add_option( $name, $value = '', $deprecated = '', $autoload = null ) {
		return update_option( $name, $value );
	}
}
if ( ! function_exists( 'delete_option' ) ) {
	function delete_option( $name ) {
		unset( $GLOBALS['hcwa_test']['options'][ $name ] );
		return true;
	}
}
if ( ! function_exists( 'get_transient' ) ) {
	function get_transient( $name ) {
		return get_option( '_transient_' . $name );
	}
}
if ( ! function_exists( 'set_transient' ) ) {
	function set_transient( $name, $value, $expiration = 0 ) {
		return update_option( '_transient_' . $name, $value );
	}
}
if ( ! function_exists( 'delete_transient' ) ) {
	function delete_transient( $name ) {
		return delete_option( '_transient_' . $name );
	}
}
if ( ! function_exists( 'wp_get_environment_type' ) ) {
	function wp_get_environment_type() {
		return $GLOBALS['hcwa_test']['env'];
	}
}
if ( ! function_exists( 'is_admin' ) ) {
	function is_admin() {
		return false;
	}
}
if ( ! function_exists( 'is_ssl' ) ) {
	function is_ssl() {
		return true;
	}
}
if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( $capability, ...$args ) {
		return (bool) $GLOBALS['hcwa_test']['can'];
	}
}
if ( ! function_exists( 'user_can' ) ) {
	function user_can( $user, $capability, ...$args ) {
		return (bool) $GLOBALS['hcwa_test']['can'];
	}
}
if ( ! function_exists( 'get_current_user_id' ) ) {
	function get_current_user_id() {
		return 1;
	}
}

/* ---------- posts and meta ---------- */

if ( ! function_exists( 'get_post' ) ) {
	function get_post( $post = null ) {
		if ( $post instanceof WP_Post ) {
			return $post;
		}
		if ( empty( $post ) ) {
			return $GLOBALS['hcwa_test']['current_post'] ?? null;
		}
		return $GLOBALS['hcwa_test']['posts'][ (int) $post ] ?? null;
	}
}
if ( ! function_exists( 'get_post_type' ) ) {
	function get_post_type( $post = null ) {
		$post = get_post( $post );
		return $post ? $post->post_type : false;
	}
}
if ( ! function_exists( 'get_post_status' ) ) {
	function get_post_status( $post = null ) {
		$post = get_post( $post );
		return $post ? $post->post_status : false;
	}
}
if ( ! function_exists( 'get_post_meta' ) ) {
	function get_post_meta( $post_id, $key = '', $single = false ) {
		$meta = $GLOBALS['hcwa_test']['post_meta'][ (int) $post_id ] ?? array();
		if ( '' === $key ) {
			return $meta;
		}
		if ( ! array_key_exists( $key, $meta ) ) {
			return $single ? '' : array();
		}
		return $single ? $meta[ $key ] : array( $meta[ $key ] );
	}
}
if ( ! function_exists( 'update_post_meta' ) ) {
	function update_post_meta( $post_id, $key, $value, $prev = '' ) {
		$GLOBALS['hcwa_test']['post_meta'][ (int) $post_id ][ $key ] = wp_unslash( $value );
		return true;
	}
}
if ( ! function_exists( 'delete_post_meta' ) ) {
	function delete_post_meta( $post_id, $key, $value = '' ) {
		unset( $GLOBALS['hcwa_test']['post_meta'][ (int) $post_id ][ $key ] );
		return true;
	}
}
if ( ! function_exists( 'get_the_title' ) ) {
	function get_the_title( $post = 0 ) {
		$post = get_post( $post );
		return $post ? (string) $post->post_title : '';
	}
}
if ( ! function_exists( 'get_permalink' ) ) {
	function get_permalink( $post = 0 ) {
		$post = get_post( $post );
		return $post ? home_url( $post->post_name . '/' ) : false;
	}
}
if ( ! function_exists( 'get_posts' ) ) {
	function get_posts( $args = array() ) {
		return array();
	}
}
if ( ! function_exists( 'wp_force_plain_post_permalink' ) ) {
	function wp_force_plain_post_permalink( $post = null, $sample = null ) {
		$post = get_post( $post );
		return ! $post || ! in_array( $post->post_status, array( 'publish', 'private' ), true );
	}
}
if ( ! function_exists( 'get_queried_object_id' ) ) {
	function get_queried_object_id() {
		return 0;
	}
}

if ( ! function_exists( 'metadata_exists' ) ) {
	function metadata_exists( $meta_type, $object_id, $meta_key ) {
		return array_key_exists( $meta_key, $GLOBALS['hcwa_test']['post_meta'][ (int) $object_id ] ?? array() );
	}
}
if ( ! function_exists( 'wp_update_post' ) ) {
	/** Records the call and applies the status to the test post. */
	function wp_update_post( $postarr = array(), $wp_error = false ) {
		$GLOBALS['hcwa_test']['updated'][] = $postarr;
		$post = get_post( (int) ( $postarr['ID'] ?? 0 ) );
		if ( $post && isset( $postarr['post_status'] ) ) {
			$post->post_status = $postarr['post_status'];
		}
		return $post ? $post->ID : 0;
	}
}
if ( ! function_exists( 'get_post_types' ) ) {
	function get_post_types( $args = array(), $output = 'names' ) {
		return array( 'post' => 'post', 'page' => 'page', 'attachment' => 'attachment', 'hcwa_center' => 'hcwa_center', 'hcwa_program' => 'hcwa_program', 'hcwa_report' => 'hcwa_report' );
	}
}
if ( ! function_exists( 'get_registered_meta_keys' ) ) {
	function get_registered_meta_keys( $object_type, $object_subtype = '' ) {
		return array();
	}
}
if ( ! function_exists( 'wp_get_post_parent_id' ) ) {
	function wp_get_post_parent_id( $post = null ) {
		$post = get_post( $post );
		return $post ? (int) $post->post_parent : false;
	}
}
if ( ! function_exists( 'get_children' ) ) {
	function get_children( $args = '', $output = OBJECT ) {
		return $GLOBALS['hcwa_test']['children'][ (int) ( $args['post_parent'] ?? 0 ) ] ?? array();
	}
}
if ( ! function_exists( 'pll_get_post_translations' ) ) {
	function pll_get_post_translations( $post_id ) {
		return $GLOBALS['hcwa_test']['translations'][ (int) $post_id ] ?? array();
	}
}

/* ---------- conditional tags and query vars ---------- */

/**
 * Value of a stubbed conditional tag (false unless a test sets it).
 *
 * @param string $tag Function name, e.g. 'is_embed'.
 */
function hcwa_test_is( string $tag ): bool {
	return ! empty( $GLOBALS['hcwa_test']['is'][ $tag ] );
}
if ( ! function_exists( 'is_embed' ) ) {
	function is_embed() {
		return hcwa_test_is( 'is_embed' );
	}
}
if ( ! function_exists( 'is_feed' ) ) {
	function is_feed( $feeds = '' ) {
		return hcwa_test_is( 'is_feed' );
	}
}
if ( ! function_exists( 'is_preview' ) ) {
	function is_preview() {
		return hcwa_test_is( 'is_preview' );
	}
}
if ( ! function_exists( 'is_attachment' ) ) {
	function is_attachment( $attachment = '' ) {
		return hcwa_test_is( 'is_attachment' );
	}
}
if ( ! function_exists( 'is_category' ) ) {
	function is_category( $category = '' ) {
		return hcwa_test_is( 'is_category' );
	}
}
if ( ! function_exists( 'get_query_var' ) ) {
	function get_query_var( $name, $default = '' ) {
		return $GLOBALS['hcwa_test']['query_vars'][ $name ] ?? $default;
	}
}

/* ---------- media and REST ---------- */

if ( ! function_exists( 'wp_get_upload_dir' ) ) {
	function wp_get_upload_dir() {
		return array( 'baseurl' => rtrim( (string) get_option( 'home' ), '/' ) . '/wp-content/uploads' );
	}
}
if ( ! function_exists( 'attachment_url_to_postid' ) ) {
	function attachment_url_to_postid( $url ) {
		return (int) ( $GLOBALS['hcwa_test']['upload_urls'][ $url ] ?? 0 );
	}
}
if ( ! function_exists( 'gallery_shortcode' ) ) {
	/** Prints the IDs it was given, so tests can see what the gate passed on. */
	function gallery_shortcode( $attr ) {
		return '<div class="gallery">' . esc_html( (string) ( $attr['ids'] ?? '' ) ) . '</div>';
	}
}
if ( ! function_exists( 'status_header' ) ) {
	function status_header( $code, $description = '' ) {
		$GLOBALS['hcwa_test']['status'] = (int) $code;
	}
}
if ( ! function_exists( 'nocache_headers' ) ) {
	function nocache_headers() {
	}
}
if ( ! function_exists( 'rest_authorization_required_code' ) ) {
	function rest_authorization_required_code() {
		return 401;
	}
}
if ( ! class_exists( 'WP_REST_Request' ) ) {
	/** Minimal WP_REST_Request: a route. */
	class WP_REST_Request {
		private $route;

		public function __construct( $method = '', $route = '' ) {
			$this->route = $route;
		}

		public function get_route() {
			return $this->route;
		}
	}
}

/**
 * Adds a post (and optional meta) to the test state.
 *
 * @param array $props WP_Post properties; ID is required.
 * @param array $meta  key => value.
 */
function hcwa_test_post( array $props, array $meta = array() ): WP_Post {
	$post = new WP_Post( $props );
	$GLOBALS['hcwa_test']['posts'][ (int) $post->ID ] = $post;
	foreach ( $meta as $key => $value ) {
		$GLOBALS['hcwa_test']['post_meta'][ (int) $post->ID ][ $key ] = $value;
	}
	return $post;
}

/* ---------- extra stubs from other areas, then the plugin ---------- */

foreach ( glob( __DIR__ . '/*-stubs.php' ) as $hcwa_stub_file ) {
	require_once $hcwa_stub_file;
}
unset( $hcwa_stub_file );

require_once dirname( __DIR__ ) . '/plugins/hcwa-core/hcwa-core.php';
