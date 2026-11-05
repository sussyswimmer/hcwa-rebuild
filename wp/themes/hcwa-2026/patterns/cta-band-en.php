<?php
/**
 * Title: CTA band / Dải kêu gọi (English)
 * Slug: hcwa-2026/cta-band-en
 * Categories: hcwa
 * Keywords: cta, kêu gọi, ủng hộ, donate, band
 * Viewport Width: 1400
 * Post Types: page
 * Description: Blue band: one sentence and at most two buttons.
 *
 * One of a VI/EN pair: keep both files in step (same blocks, classes and locks);
 * wp/tests/patterns-markup-test.php checks this. Draft copy is wrapped in [DRAFT: …]
 * and production refuses to publish it (hcwa-core publish guard).
 *
 * @package hcwa-2026
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"tagName":"section","templateLock":"contentOnly","align":"full","className":"cta-band","metadata":{"name":"CTA band"}} -->
<section class="wp-block-group alignfull cta-band">
	<!-- wp:group {"className":"container cta-band__inner"} -->
	<div class="wp-block-group container cta-band__inner">
		<!-- wp:heading -->
		<h2 class="wp-block-heading">Support work like this</h2>
		<!-- /wp:heading -->

		<!-- wp:buttons {"className":"cta-band__actions"} -->
		<div class="wp-block-buttons cta-band__actions">
			<!-- wp:button -->
			<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/en/get-involved/donate/">Donate</a></div>
			<!-- /wp:button -->

			<!-- wp:button {"className":"is-style-outline"} -->
			<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/en/get-involved/partner/">Partner on a program</a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
