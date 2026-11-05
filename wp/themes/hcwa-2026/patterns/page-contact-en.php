<?php
/**
 * Title: Contact page / Trang Liên hệ (English)
 * Slug: hcwa-2026/page-contact-en
 * Categories: hcwa
 * Keywords: liên hệ, contact, văn phòng, office, starter
 * Viewport Width: 1400
 * Block Types: core/post-content
 * Post Types: page
 * Description: Contact page: form slot, office and centers.
 *
 * One of a VI/EN pair: keep both files in step (same blocks, classes and locks);
 * wp/tests/patterns-markup-test.php checks this. Draft copy is wrapped in [DRAFT: …]
 * and production refuses to publish it (hcwa-core publish guard).
 *
 * @package hcwa-2026
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"tagName":"header","templateLock":"contentOnly","align":"full","className":"page-header","metadata":{"name":"Page header"}} -->
<header class="wp-block-group alignfull page-header">
	<!-- wp:group {"className":"container"} -->
	<div class="wp-block-group container">
		<!-- wp:heading {"level":1} -->
		<h1 class="wp-block-heading">Contact</h1>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"className":"page-header__intro"} -->
		<p class="page-header__intro">[DRAFT: Send HCWA a message, or call or email us directly.]</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</header>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","templateLock":"contentOnly","align":"full","className":"section section\u002d\u002dtight","metadata":{"name":"Message and offices"}} -->
<section class="wp-block-group alignfull section section--tight">
	<!-- wp:group {"className":"container contact-grid"} -->
	<div class="wp-block-group container contact-grid">
		<!-- wp:group {"className":"form-slot"} -->
		<div class="wp-block-group form-slot">
			<!-- wp:heading {"className":"h3"} -->
			<h2 class="wp-block-heading h3">Send a message</h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph -->
			<p>[DRAFT: The contact form will be added once HCWA chooses a form tool. Until then, please call or email HCWA.]</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"tagName":"aside","className":"offices"} -->
		<aside class="wp-block-group offices">
			<!-- wp:heading {"className":"h3"} -->
			<h2 class="wp-block-heading h3">Office and centers</h2>
			<!-- /wp:heading -->

			<!-- wp:group {"tagName":"article","className":"office"} -->
			<article class="wp-block-group office">
				<!-- wp:heading {"level":3,"className":"h4"} -->
				<h3 class="wp-block-heading h4">HCWA office</h3>
				<!-- /wp:heading -->

				<!-- wp:list {"className":"contact-lines"} -->
				<ul class="wp-block-list contact-lines">
					<!-- wp:list-item -->
					<li>[DRAFT: 85/65 Pham Viet Chanh Street, Thanh My Tay Ward, Ho Chi Minh City]</li>
					<!-- /wp:list-item -->

					<!-- wp:list-item -->
					<li><a href="tel:+842838401406">[DRAFT: +84 28 3840 1406]</a></li>
					<!-- /wp:list-item -->

					<!-- wp:list-item -->
					<li><a href="mailto:hcwa.vn@gmail.com">[DRAFT: hcwa.vn@gmail.com]</a></li>
					<!-- /wp:list-item -->
				</ul>
				<!-- /wp:list -->

				<!-- wp:image {"linkDestination":"none","className":"office__map","metadata":{"name":"Map C7 (optional)"}} -->
				<figure class="wp-block-image office__map"><img alt="C7 · Static map · 4:3"/></figure>
				<!-- /wp:image -->

				<!-- wp:paragraph {"className":"hcwa-link hcwa-link\u002d\u002dexternal"} -->
				<p class="hcwa-link hcwa-link--external"><a href="https://www.google.com/maps/search/?api=1&amp;query=85%2F65+Ph%E1%BA%A1m+Vi%E1%BA%BFt+Ch%C3%A1nh%2C+TP.+H%E1%BB%93+Ch%C3%AD+Minh">Open map</a> [DRAFT: check the map matches the verified address]</p>
				<!-- /wp:paragraph -->
			</article>
			<!-- /wp:group -->

			<!-- wp:group {"tagName":"article","className":"office office\u002d\u002dunit office\u002d\u002dgreen-bamboo"} -->
			<article class="wp-block-group office office--unit office--green-bamboo">
				<!-- wp:heading {"level":3,"className":"h4"} -->
				<h3 class="wp-block-heading h4">Green Bamboo Social Protection Center</h3>
				<!-- /wp:heading -->

				<!-- wp:paragraph -->
				<p>[DRAFT: Approved address and contact]</p>
				<!-- /wp:paragraph -->
			</article>
			<!-- /wp:group -->

			<!-- wp:group {"tagName":"article","className":"office office\u002d\u002dunit office\u002d\u002dlittle-rose"} -->
			<article class="wp-block-group office office--unit office--little-rose">
				<!-- wp:heading {"level":3,"className":"h4"} -->
				<h3 class="wp-block-heading h4">Little Rose Warm Shelter</h3>
				<!-- /wp:heading -->

				<!-- wp:paragraph -->
				<p>[DRAFT: Contact through the HCWA office. HCWA decides whether the shelter's address is published.]</p>
				<!-- /wp:paragraph -->
			</article>
			<!-- /wp:group -->

			<!-- wp:group {"tagName":"article","className":"office office\u002d\u002dunit office\u002d\u002dcau-han"} -->
			<article class="wp-block-group office office--unit office--cau-han">
				<!-- wp:heading {"level":3,"className":"h4"} -->
				<h3 class="wp-block-heading h4">Cau Han Project</h3>
				<!-- /wp:heading -->

				<!-- wp:paragraph -->
				<p>[DRAFT: Approved address and contact]</p>
				<!-- /wp:paragraph -->
			</article>
			<!-- /wp:group -->

			<!-- wp:paragraph {"className":"offices__chat"} -->
			<p class="offices__chat">[DRAFT: Message us on Zalo (number to be confirmed by HCWA) or <a href="https://www.facebook.com/hcwa.org.vn">Facebook Messenger</a>.]</p>
			<!-- /wp:paragraph -->
		</aside>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
