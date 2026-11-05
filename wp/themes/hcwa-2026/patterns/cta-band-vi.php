<?php
/**
 * Title: Dải kêu gọi / CTA band (Tiếng Việt)
 * Slug: hcwa-2026/cta-band-vi
 * Categories: hcwa
 * Keywords: cta, kêu gọi, ủng hộ, donate, band
 * Viewport Width: 1400
 * Post Types: page
 * Description: Dải màu xanh: một câu và tối đa hai nút.
 *
 * One of a VI/EN pair: keep both files in step (same blocks, classes and locks);
 * wp/tests/patterns-markup-test.php checks this. Draft copy is wrapped in [DRAFT: …]
 * and production refuses to publish it (hcwa-core publish guard).
 *
 * @package hcwa-2026
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"tagName":"section","templateLock":"contentOnly","align":"full","className":"cta-band","metadata":{"name":"Dải kêu gọi"}} -->
<section class="wp-block-group alignfull cta-band">
	<!-- wp:group {"className":"container cta-band__inner"} -->
	<div class="wp-block-group container cta-band__inner">
		<!-- wp:heading -->
		<h2 class="wp-block-heading">Đồng hành cùng những dự án như thế này</h2>
		<!-- /wp:heading -->

		<!-- wp:buttons {"className":"cta-band__actions"} -->
		<div class="wp-block-buttons cta-band__actions">
			<!-- wp:button -->
			<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/chung-tay/ung-ho/">Ủng hộ</a></div>
			<!-- /wp:button -->

			<!-- wp:button {"className":"is-style-outline"} -->
			<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/chung-tay/doi-tac-doanh-nghiep/">Hợp tác trong một dự án</a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
