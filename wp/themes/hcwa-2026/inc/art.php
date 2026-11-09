<?php
/**
 * The theme's art for hcwa-core (docs/plan/11-WP-PORT.md 1.4): stills, the hero posters and the
 * gạch, transom and emblem SVG, all built from the export's files in assets/generated/
 * (stills.json and svg/) and the bundle in assets/dist/, so the markup is the prototype's own.
 *
 * hcwa-core declares the HCWA_Art interface and wraps whatever provider the 'hcwa_art_provider'
 * filter gives in HCWA_Art_Guard (which refuses the stills showing the Little Rose door until
 * that door is approved). This file declares HCWA_Theme_Art only when the interface exists, in
 * an after_setup_theme callback, so the theme still loads without hcwa-core: the pages then
 * print text only.
 *
 * Every picture is byte-equal to the prototype's stillPicture(), litStill() and poster() for
 * the same call (wp/tests/theme-assets-test.php checks each against wp/tests/fixtures/golden.json).
 *
 * @package hcwa-2026
 */

defined( 'ABSPATH' ) || exit;

add_action( 'after_setup_theme', 'hcwa_theme_register_art' );

/**
 * Declares HCWA_Theme_Art and registers it as hcwa-core's art provider; nothing without hcwa-core.
 */
function hcwa_theme_register_art(): void {
	if ( ! interface_exists( 'HCWA_Art' ) ) {
		return;
	}
	if ( ! class_exists( 'HCWA_Theme_Art', false ) ) {
		hcwa_theme_declare_art();
	}
	add_filter( 'hcwa_art_provider', 'hcwa_theme_art_provider' );
}

/**
 * The provider for this request (one instance, so the gạch sprite is printed once).
 *
 * @param mixed $provider A provider another plugin already gave, kept as it is.
 * @return mixed
 */
function hcwa_theme_art_provider( $provider ) {
	static $art = null;
	if ( null !== $provider ) {
		return $provider;
	}
	if ( null === $art && class_exists( 'HCWA_Theme_Art', false ) ) {
		$art = new HCWA_Theme_Art();
	}
	return $art;
}

/**
 * Declares HCWA_Theme_Art. Called once, and only when hcwa-core's HCWA_Art interface exists.
 */
function hcwa_theme_declare_art(): void {
	/**
	 * Stills, posters and SVG art from assets/generated/ and assets/dist/.
	 */
	class HCWA_Theme_Art implements HCWA_Art {
		/**
		 * URL of assets/dist/, with its trailing slash.
		 *
		 * @var string
		 */
		private $base;

		/**
		 * Folder of the generated files.
		 *
		 * @var string
		 */
		private $dir;

		/**
		 * stills.json, decoded on first use.
		 *
		 * @var array|null
		 */
		private $data = null;

		/**
		 * Whether this request already printed the gạch sprite.
		 *
		 * @var bool
		 */
		private static $sprite_done = false;

		/**
		 * SVG files read so far.
		 *
		 * @var array<string, string>
		 */
		private $svg = array();

		/**
		 * @param string|null $base      URL of the bundle folder (tests use '/'); default assets/dist/.
		 * @param string|null $generated Folder of the generated files; default assets/generated/.
		 */
		public function __construct( ?string $base = null, ?string $generated = null ) {
			$this->base = $base ?? hcwa_theme_dist_url( '' );
			$this->dir  = rtrim( $generated ?? get_theme_file_path( 'assets/generated' ), '/' );
		}

		/**
		 * Tests only: the next gach_sprite_once() prints the sprite again.
		 */
		public static function reset_sprite(): void {
			self::$sprite_done = false;
		}

		/**
		 * Escapes as the prototype's html`` tag does (& < > " '), so the markup stays byte-equal.
		 *
		 * @param string $s Text.
		 */
		private static function esc( string $s ): string {
			return strtr(
				$s,
				array(
					'&'  => '&amp;',
					'<'  => '&lt;',
					'>'  => '&gt;',
					'"'  => '&quot;',
					"'"  => '&#39;',
				)
			);
		}

		/**
		 * stills.json: { nothing, stills: { name: {…} }, hero: { layer: {…} } }.
		 */
		private function data(): array {
			if ( null === $this->data ) {
				$file       = $this->dir . '/stills.json';
				$json       = is_readable( $file ) ? json_decode( (string) file_get_contents( $file ), true ) : null; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local theme file.
				$this->data = is_array( $json ) ? $json : array();
			}
			return $this->data;
		}

		/**
		 * One still's entry, or null.
		 *
		 * @param string $name Still name.
		 */
		private function entry( string $name ): ?array {
			$s = $this->data()['stills'][ $name ] ?? null;
			return is_array( $s ) ? $s : null;
		}

		/** @inheritDoc */
		public function has_still( string $name ): bool {
			$s = $this->entry( $name );
			return null !== $s && ! empty( $s['ready'] );
		}

		/** @inheritDoc */
		public function shows_little_rose_door( string $name ): bool {
			$s = $this->entry( $name );
			// A name the export did not list counts as showing the door (fails closed).
			return null === $s || ! array_key_exists( 'showsLittleRoseDoor', $s ) || false !== $s['showsLittleRoseDoor'];
		}

		/** @inheritDoc */
		public function still_aspect( string $name ): float {
			$s = $this->entry( $name );
			return null === $s ? 0.0 : (float) ( $s['aspect'] ?? 0 );
		}

		/** @inheritDoc */
		public function still( string $name, array $o = array() ): string {
			$s = $this->entry( $name );
			if ( ! $this->has_still( $name ) || ! isset( $s['pictures']['still'] ) ) {
				return '';
			}
			return $this->picture( $s['pictures']['still'], 'still', $o );
		}

		/** @inheritDoc */
		public function lit_still( string $name ): string {
			$s = $this->entry( $name );
			if ( ! $this->has_still( $name ) || ! isset( $s['pictures']['base'], $s['pictures']['lightroom'] ) ) {
				return '';
			}
			$motion = ! empty( $s['motion'] ) ? ' lit--' . $s['motion'] : '';
			$screen = isset( $s['pictures']['lightscreen'] ) ? $this->picture( $s['pictures']['lightscreen'], 'lightscreen', array( 'cls' => 'lit__screen' ) ) : '';
			return '<div class="lit lit--still' . self::esc( $motion ) . '" aria-hidden="true" data-still="' . self::esc( $name ) . '">'
				. "\n  " . $this->picture( $s['pictures']['base'], 'base', array( 'cls' => 'lit__base', 'eager' => true ) )
				. "\n  " . $this->picture( $s['pictures']['lightroom'], 'lightroom', array( 'cls' => 'lit__light' ) )
				. "\n  " . $screen
				. "\n</div>";
		}

		/** @inheritDoc */
		public function poster( string $shot, string $layer, array $o = array() ): string {
			$pic = 'hero' === $shot ? ( $this->data()['hero'][ $layer ] ?? null ) : null;
			if ( ! is_array( $pic ) ) {
				return '';
			}
			$sizes = (string) ( $o['sizes'] ?? '100vw' );
			$out   = '<picture class="' . self::esc( (string) ( $o['cls'] ?? '' ) ) . '">';
			foreach ( (array) ( $pic['sources'] ?? array() ) as $s ) {
				$media = ! empty( $s['media'] ) ? ' media="' . self::esc( (string) $s['media'] ) . '"' : '';
				if ( empty( $s['srcset'] ) ) {
					$out .= '<source' . $media . ' srcset="' . self::esc( $this->nothing() ) . '">';
					continue;
				}
				$out .= '<source type="' . self::esc( (string) $s['type'] ) . '"' . $media . ' srcset="' . self::esc( $this->srcset( $s['srcset'] ) ) . '" sizes="' . self::esc( (string) ( $s['sizes'] ?? $sizes ) ) . '">';
			}
			$priority = 'base' === $layer ? ' fetchpriority="high"' : ' loading="lazy" decoding="async"';
			return $out . '<img src="' . self::esc( $this->img_src( $pic ) ) . '" alt="" width="' . (int) ( $pic['img']['width'] ?? 0 ) . '" height="' . (int) ( $pic['img']['height'] ?? 0 ) . '"' . $priority . ' data-layer="' . self::esc( $layer ) . '"></picture>';
		}

		/**
		 * One layer of a named still as <picture> (posters.mjs stillPicture()).
		 *
		 * @param array  $pic   The layer's sources and fallback image from stills.json.
		 * @param string $layer Layer name.
		 * @param array  $o     cls, sizes (string, or crop name => sizes), eager, alt.
		 */
		private function picture( array $pic, string $layer, array $o ): string {
			$sizes = $o['sizes'] ?? '100vw';
			$out   = '<picture class="' . self::esc( (string) ( $o['cls'] ?? '' ) ) . '">';
			foreach ( (array) ( $pic['sources'] ?? array() ) as $s ) {
				$media = ! empty( $s['media'] ) ? ' media="' . self::esc( (string) $s['media'] ) . '"' : '';
				$box   = ' width="' . (int) ( $s['width'] ?? 0 ) . '" height="' . (int) ( $s['height'] ?? 0 ) . '"';
				if ( empty( $s['srcset'] ) ) {
					$out .= '<source' . $media . ' srcset="' . self::esc( $this->nothing() ) . '"' . $box . '>';
					continue;
				}
				$size = is_array( $sizes ) ? (string) ( $sizes[ $s['crop'] ?? '' ] ?? '100vw' ) : (string) $sizes;
				$out .= '<source type="' . self::esc( (string) $s['type'] ) . '"' . $media . ' srcset="' . self::esc( $this->srcset( $s['srcset'] ) ) . '" sizes="' . self::esc( $size ) . '"' . $box . '>';
			}
			$load = ! empty( $o['eager'] ) ? ' fetchpriority="high"' : ' loading="lazy" decoding="async"';
			return $out . '<img src="' . self::esc( $this->img_src( $pic ) ) . '" alt="' . self::esc( (string) ( $o['alt'] ?? '' ) ) . '" width="' . (int) ( $pic['img']['width'] ?? 0 ) . '" height="' . (int) ( $pic['img']['height'] ?? 0 ) . '"' . $load . ' data-layer="' . self::esc( $layer ) . '"></picture>';
		}

		/**
		 * The 1 px image a source without files points at, so a crop never falls through to another.
		 */
		private function nothing(): string {
			return (string) ( $this->data()['nothing'] ?? '' );
		}

		/**
		 * [[path, width], …] as a srcset list under the bundle URL.
		 *
		 * @param array $list Candidates.
		 */
		private function srcset( array $list ): string {
			$out = array();
			foreach ( $list as $c ) {
				$out[] = $this->base . ltrim( (string) ( $c[0] ?? '' ), '/' ) . ' ' . (int) ( $c[1] ?? 0 ) . 'w';
			}
			return implode( ', ', $out );
		}

		/**
		 * A layer's fallback <img> URL.
		 *
		 * @param array $pic Layer from stills.json.
		 */
		private function img_src( array $pic ): string {
			$src = $pic['img']['src'] ?? null;
			return is_string( $src ) && '' !== $src ? $this->base . ltrim( $src, '/' ) : $this->nothing();
		}

		/**
		 * One file from assets/generated/svg/, '' when missing.
		 *
		 * @param string $file File name.
		 */
		private function svg_file( string $file ): string {
			if ( ! preg_match( '/^[a-zA-Z0-9-]+\.svg$/', $file ) ) {
				return '';
			}
			if ( ! isset( $this->svg[ $file ] ) ) {
				$path               = $this->dir . '/svg/' . $file;
				$this->svg[ $file ] = is_readable( $path ) ? (string) file_get_contents( $path ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local theme file.
			}
			return $this->svg[ $file ];
		}

		/**
		 * The same SVG with another class on its root element.
		 *
		 * @param string $svg SVG markup.
		 * @param string $cls Class attribute.
		 */
		private static function with_class( string $svg, string $cls ): string {
			if ( '' === $svg ) {
				return '';
			}
			return (string) preg_replace( '/^<svg class="[^"]*"/', '<svg class="' . str_replace( '$', '\$', self::esc( $cls ) ) . '"', $svg, 1 );
		}

		/** @inheritDoc */
		public function gach_sprite_once(): string {
			if ( self::$sprite_done ) {
				return '';
			}
			$svg = $this->svg_file( 'gach-sprite.svg' );
			if ( '' !== $svg ) {
				self::$sprite_done = true;
			}
			return $svg;
		}

		/** @inheritDoc */
		public function gach_art( string $area, string $variant ): string {
			if ( ! in_array( $variant, array( 'art', 'lit' ), true ) || ! preg_match( '/^[a-z]+$/', $area ) ) {
				return '';
			}
			return $this->svg_file( 'gach-' . $variant . '-' . $area . '.svg' );
		}

		/**
		 * One tile per known area, side by side at the tile pitch, a grout joint between them
		 * (blocks.mjs gachStrip()). Needs the page's sprite. The numbers come from hcwa-core's
		 * prototype-data.php.
		 *
		 * @param string[] $areas Area keys.
		 * @param string   $cls   Class attribute.
		 */
		public function gach_strip( array $areas, string $cls ): string {
			$data  = function_exists( 'hcwa_prototype_data' ) ? hcwa_prototype_data() : array();
			$block = (array) ( $data['area_block'] ?? array() );
			$tile  = (int) ( $data['tile'] ?? 0 );
			$known = array_values( array_filter( array_map( 'strval', $areas ), static fn( string $a ): bool => isset( $block[ $a ] ) ) );
			if ( ! $known || $tile <= 0 ) {
				return '';
			}
			$uses   = '';
			$joints = '';
			foreach ( $known as $i => $area ) {
				$uses .= '<use href="#gach-' . self::esc( (string) $block[ $area ] ) . '" class="pig--' . self::esc( $area ) . '"' . ( $i ? ' x="' . ( $i * $tile ) . '"' : '' ) . '/>';
				if ( $i ) {
					$joints .= 'M' . ( $i * $tile ) . ' 0V' . $tile;
				}
			}
			$grout = '' !== $joints ? '<path fill="none" stroke-width="' . self::number( $data['grout_px'] ?? 1.5 ) . '" vector-effect="non-scaling-stroke" style="stroke:var(--grout)" d="' . $joints . '"/>' : '';
			return '<svg class="' . self::esc( $cls ) . '" viewBox="0 0 ' . ( count( $known ) * $tile ) . ' ' . $tile . '" aria-hidden="true" focusable="false">' . $uses . $grout . '</svg>';
		}

		/**
		 * A number as JavaScript prints it (1.5, 2, 0.25).
		 *
		 * @param mixed $n Number.
		 */
		private static function number( $n ): string {
			$n = (float) $n;
			return floor( $n ) === $n ? (string) (int) $n : (string) wp_json_encode( $n );
		}

		/** @inheritDoc */
		public function screen_art( string $emblem, string $cls ): string {
			return self::with_class( $this->svg_file( 'rail-transom-' . $emblem . '.svg' ), $cls );
		}

		/** @inheritDoc */
		public function block_icon( string $emblem, string $cls ): string {
			return self::with_class( $this->svg_file( 'plate-mark-' . $emblem . '.svg' ), $cls );
		}

		/** @inheritDoc */
		public function asset_url( string $path ): string {
			return $this->base . ltrim( $path, '/' );
		}
	}
}
