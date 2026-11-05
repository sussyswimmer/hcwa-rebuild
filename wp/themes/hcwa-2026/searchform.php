<?php
/**
 * Search form (get_search_form()). The action is the current language's home, so results
 * stay in the page language.
 *
 * @package hcwa-2026
 */

defined( 'ABSPATH' ) || exit;

$hcwa_search_id = wp_unique_id( 'search-' );
?>
<form class="search" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get" role="search">
	<label for="<?php echo esc_attr( $hcwa_search_id ); ?>"><?php hcwa_theme_e( 'nf_search' ); ?></label>
	<div class="search__row">
		<input type="search" id="<?php echo esc_attr( $hcwa_search_id ); ?>" name="s" value="<?php echo esc_attr( get_search_query() ); ?>">
		<button class="btn btn--secondary" type="submit"><?php echo hcwa_theme_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?><span><?php hcwa_theme_e( 'nf_searchBtn' ); ?></span></button>
	</div>
</form>
