<?php
/**
 * The art interface (11-WP-PORT.md 1.4). Stills, posters and SVG art are theme assets, and
 * hcwa-core never calls a theme function, so hcwa-core declares the interface and a null
 * implementation, the theme registers its own through the 'hcwa_art_provider' filter, and
 * every caller goes through hcwa_art(), which wraps the provider in HCWA_Art_Guard.
 *
 * The guard is the second lock under the per-place Little Rose rules (3.4): while the door
 * is not approved on production, every still the provider reports as showing the Little Rose
 * door reads as missing, whatever it is named.
 *
 * @package hcwa-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * What the theme's art provider gives the blocks and templates.
 */
interface HCWA_Art {
	/**
	 * Whether every file of a named still is on disk.
	 *
	 * @param string $name Still name (stills.json).
	 */
	public function has_still( string $name ): bool;

	/**
	 * Whether the still's scene contains the Little Rose door (true for an unknown name).
	 *
	 * @param string $name Still name.
	 */
	public function shows_little_rose_door( string $name ): bool;

	/**
	 * Width over height of the still's widest crop, 0 if none.
	 *
	 * @param string $name Still name.
	 */
	public function still_aspect( string $name ): float;

	/**
	 * <picture> of the still's 'still' layer, '' when not ready.
	 *
	 * @param string $name Still name.
	 * @param array  $o    cls, sizes (string, or crop name => sizes), eager, alt.
	 */
	public function still( string $name, array $o = array() ): string;

	/**
	 * div.lit.lit--still with the base and light pictures, '' when not ready.
	 *
	 * @param string $name Still name.
	 */
	public function lit_still( string $name ): string;

	/**
	 * <picture> of one layer of a hero poster (base, lightroom, lightscreen).
	 *
	 * @param string $shot  Poster shot ('hero').
	 * @param string $layer Layer.
	 * @param array  $o     cls, sizes.
	 */
	public function poster( string $shot, string $layer, array $o = array() ): string;

	/**
	 * The gạch tile sprite the first time per request, then ''.
	 */
	public function gach_sprite_once(): string;

	/**
	 * One focus area's patch of tiles.
	 *
	 * @param string $area    Area key.
	 * @param string $variant 'art' or 'lit'.
	 */
	public function gach_art( string $area, string $variant ): string;

	/**
	 * A strip of one tile per area.
	 *
	 * @param string[] $areas Area keys.
	 * @param string   $cls   Class attribute.
	 */
	public function gach_strip( array $areas, string $cls ): string;

	/**
	 * A transom of three blocks of the emblem, backlit.
	 *
	 * @param string $emblem Emblem (dotTre, vomCau, hoaThi).
	 * @param string $cls    Class attribute.
	 */
	public function screen_art( string $emblem, string $cls ): string;

	/**
	 * One block of the emblem in line work.
	 *
	 * @param string $emblem Emblem.
	 * @param string $cls    Class attribute.
	 */
	public function block_icon( string $emblem, string $cls ): string;

	/**
	 * Absolute URL of a file in the bundle ('svg/hole-dot-tre.svg', 'img/hcwa-logo.webp').
	 *
	 * @param string $path Path relative to the bundle.
	 */
	public function asset_url( string $path ): string;
}

/**
 * No theme art: every still is missing, every picture and SVG is ''. The blocks then print
 * the prototype's missing-still output (an empty lit block, an empty passage exit), so the
 * pages stay complete in words.
 */
class HCWA_Art_None implements HCWA_Art {
	/** @inheritDoc */
	public function has_still( string $name ): bool {
		return false;
	}

	/** @inheritDoc */
	public function shows_little_rose_door( string $name ): bool {
		return true;
	}

	/** @inheritDoc */
	public function still_aspect( string $name ): float {
		return 0.0;
	}

	/** @inheritDoc */
	public function still( string $name, array $o = array() ): string {
		return '';
	}

	/** @inheritDoc */
	public function lit_still( string $name ): string {
		return '';
	}

	/** @inheritDoc */
	public function poster( string $shot, string $layer, array $o = array() ): string {
		return '';
	}

	/** @inheritDoc */
	public function gach_sprite_once(): string {
		return '';
	}

	/** @inheritDoc */
	public function gach_art( string $area, string $variant ): string {
		return '';
	}

	/** @inheritDoc */
	public function gach_strip( array $areas, string $cls ): string {
		return '';
	}

	/** @inheritDoc */
	public function screen_art( string $emblem, string $cls ): string {
		return '';
	}

	/** @inheritDoc */
	public function block_icon( string $emblem, string $cls ): string {
		return '';
	}

	/** @inheritDoc */
	public function asset_url( string $path ): string {
		return '';
	}
}

/**
 * Passes every call to the provider, except that a still showing the Little Rose door acts
 * as missing while hcwa_little_rose_door_allowed() is false.
 */
class HCWA_Art_Guard implements HCWA_Art {
	/**
	 * The theme's provider (or HCWA_Art_None).
	 *
	 * @var HCWA_Art
	 */
	private $inner;

	/**
	 * Whether the door may show; null asks hcwa_little_rose_door_allowed() on every call.
	 *
	 * @var bool|null
	 */
	private $allowed;

	/**
	 * @param HCWA_Art  $inner   Provider.
	 * @param bool|null $allowed Tests only: fixes the approval state.
	 */
	public function __construct( HCWA_Art $inner, ?bool $allowed = null ) {
		$this->inner   = $inner;
		$this->allowed = $allowed;
	}

	/**
	 * Whether the named still must act as missing.
	 *
	 * @param string $name Still name.
	 */
	public function refuses( string $name ): bool {
		$allowed = $this->allowed ?? hcwa_little_rose_door_allowed();
		return ! $allowed && $this->inner->shows_little_rose_door( $name );
	}

	/** @inheritDoc */
	public function has_still( string $name ): bool {
		return ! $this->refuses( $name ) && $this->inner->has_still( $name );
	}

	/** @inheritDoc */
	public function shows_little_rose_door( string $name ): bool {
		return $this->inner->shows_little_rose_door( $name );
	}

	/** @inheritDoc */
	public function still_aspect( string $name ): float {
		return $this->refuses( $name ) ? 0.0 : $this->inner->still_aspect( $name );
	}

	/** @inheritDoc */
	public function still( string $name, array $o = array() ): string {
		return $this->refuses( $name ) ? '' : $this->inner->still( $name, $o );
	}

	/** @inheritDoc */
	public function lit_still( string $name ): string {
		return $this->refuses( $name ) ? '' : $this->inner->lit_still( $name );
	}

	/** @inheritDoc */
	public function poster( string $shot, string $layer, array $o = array() ): string {
		return $this->inner->poster( $shot, $layer, $o );
	}

	/** @inheritDoc */
	public function gach_sprite_once(): string {
		return $this->inner->gach_sprite_once();
	}

	/** @inheritDoc */
	public function gach_art( string $area, string $variant ): string {
		return $this->inner->gach_art( $area, $variant );
	}

	/** @inheritDoc */
	public function gach_strip( array $areas, string $cls ): string {
		return $this->inner->gach_strip( $areas, $cls );
	}

	/** @inheritDoc */
	public function screen_art( string $emblem, string $cls ): string {
		return $this->inner->screen_art( $emblem, $cls );
	}

	/** @inheritDoc */
	public function block_icon( string $emblem, string $cls ): string {
		return $this->inner->block_icon( $emblem, $cls );
	}

	/** @inheritDoc */
	public function asset_url( string $path ): string {
		return $this->inner->asset_url( $path );
	}
}

/**
 * The art provider for this request: the theme's (filter 'hcwa_art_provider'), or
 * HCWA_Art_None, always behind HCWA_Art_Guard.
 *
 * @param bool $reset Tests only: forget the cached provider.
 */
function hcwa_art( bool $reset = false ): HCWA_Art {
	static $art = null;

	if ( $reset ) {
		$art = null;
	}
	if ( null !== $art ) {
		return $art;
	}
	$provider = apply_filters( 'hcwa_art_provider', null );
	$guarded  = new HCWA_Art_Guard( $provider instanceof HCWA_Art ? $provider : new HCWA_Art_None() );
	// The theme adds its provider on after_setup_theme: an earlier call is not cached.
	if ( ! function_exists( 'did_action' ) || did_action( 'after_setup_theme' ) ) {
		$art = $guarded;
	}
	return $guarded;
}
