<?php
/**
 * Search box (404 and search results), labels in the page language.
 */

defined( 'ABSPATH' ) || exit;
?>
<form class="search" action="<?php echo esc_url( hcwa_url( 'home' ) ); ?>" method="get" role="search">
	<label for="s"><?php hcwa_e( 'nf_search' ); ?></label>
	<div class="search__row">
		<input type="search" id="s" name="s" value="<?php echo esc_attr( get_search_query() ); ?>">
		<button class="btn btn--secondary" type="submit"><?php echo hcwa_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php hcwa_e( 'nf_searchBtn' ); ?></span></button>
	</div>
</form>
