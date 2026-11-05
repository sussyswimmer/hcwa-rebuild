<?php
/**
 * Title: Hero split / Phần mở đầu chia đôi (English)
 * Slug: hcwa-2026/hero-split-en
 * Categories: hcwa
 * Keywords: hero, mở đầu, intro, banner, ảnh
 * Viewport Width: 1400
 * Post Types: page
 * Description: Heading (H2), intro, two buttons and the A2 photo slot. The home page composition carries the H1 version.
 *
 * One of a VI/EN pair: keep both files in step (same blocks, classes and locks);
 * wp/tests/patterns-markup-test.php checks this. Draft copy is wrapped in [DRAFT: …]
 * and production refuses to publish it (hcwa-core publish guard).
 *
 * @package hcwa-2026
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"tagName":"section","templateLock":"contentOnly","align":"full","className":"hero","metadata":{"name":"Hero"}} -->
<section class="wp-block-group alignfull hero">
	<!-- wp:group {"className":"container hero__grid"} -->
	<div class="wp-block-group container hero__grid">
		<!-- wp:group {"className":"hero__text"} -->
		<div class="wp-block-group hero__text">
			<!-- wp:paragraph {"className":"hero__eyebrow"} -->
			<p class="hero__eyebrow">[DRAFT: Ho Chi Minh City Child Welfare Association · since [year]]</p>
			<!-- /wp:paragraph -->

			<!-- wp:heading {"className":"hero__title"} -->
			<h2 class="wp-block-heading hero__title">[DRAFT: Helping children build safer, brighter futures.]</h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"className":"hero__intro"} -->
			<p class="hero__intro">[DRAFT: HCWA supports children, young people and families through education, child protection and community-based programs.]</p>
			<!-- /wp:paragraph -->

			<!-- wp:buttons {"className":"hero__actions"} -->
			<div class="wp-block-buttons hero__actions">
				<!-- wp:button -->
				<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/en/our-work/">Explore our work</a></div>
				<!-- /wp:button -->

				<!-- wp:button {"className":"is-style-outline"} -->
				<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/en/get-involved/">Get Involved</a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:group -->

		<!-- wp:image {"linkDestination":"none","className":"hero__media","metadata":{"name":"Image A2: homepage hero, 4:5, HCWA-approved photos only"}} -->
		<figure class="wp-block-image hero__media"><img alt="A2 · Homepage hero · 4:5"/></figure>
		<!-- /wp:image -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
