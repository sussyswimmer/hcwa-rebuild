<?php
/**
 * Title: Contact band / Dải liên hệ (English)
 * Slug: hcwa-2026/contact-band-en
 * Categories: hcwa
 * Keywords: contact, liên hệ, phone, email, band
 * Viewport Width: 1400
 * Post Types: page
 * Description: Blue band with a contact button, phone and email.
 *
 * One of a VI/EN pair: keep both files in step (same blocks, classes and locks);
 * wp/tests/patterns-markup-test.php checks this. Draft copy is wrapped in [DRAFT: …]
 * and production refuses to publish it (hcwa-core publish guard).
 *
 * @package hcwa-2026
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"tagName":"section","templateLock":"contentOnly","align":"full","className":"cta-band contact-band","metadata":{"name":"Contact band"}} -->
<section class="wp-block-group alignfull cta-band contact-band">
	<!-- wp:group {"className":"container cta-band__inner"} -->
	<div class="wp-block-group container cta-band__inner">
		<!-- wp:heading -->
		<h2 class="wp-block-heading">Have a question or want to visit?</h2>
		<!-- /wp:heading -->

		<!-- wp:buttons {"className":"cta-band__actions"} -->
		<div class="wp-block-buttons cta-band__actions">
			<!-- wp:button {"className":"is-style-outline"} -->
			<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/en/contact/">Contact us</a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->

		<!-- wp:list {"className":"cta-band__contact"} -->
		<ul class="wp-block-list cta-band__contact">
			<!-- wp:list-item -->
			<li><a href="tel:+842838401406">[DRAFT: +84 28 3840 1406]</a></li>
			<!-- /wp:list-item -->

			<!-- wp:list-item -->
			<li><a href="mailto:hcwa.vn@gmail.com">[DRAFT: hcwa.vn@gmail.com]</a></li>
			<!-- /wp:list-item -->
		</ul>
		<!-- /wp:list -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
