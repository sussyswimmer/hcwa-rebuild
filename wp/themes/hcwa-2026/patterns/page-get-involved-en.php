<?php
/**
 * Title: Get Involved page / Trang Chung tay (English)
 * Slug: hcwa-2026/page-get-involved-en
 * Categories: hcwa
 * Keywords: chung tay, get involved, ủng hộ, tình nguyện, faq, starter
 * Viewport Width: 1400
 * Block Types: core/post-content
 * Post Types: page
 * Description: Get Involved hub: header, four ways to help and common questions.
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
		<h1 class="wp-block-heading">Get Involved</h1>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"className":"page-header__intro"} -->
		<p class="page-header__intro">[DRAFT: You can donate, volunteer, partner with us or give in kind.]</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</header>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","templateLock":"contentOnly","align":"full","ariaLabel":"How you can help","className":"section section\u002d\u002dtight","metadata":{"name":"Ways to help"}} -->
<section class="wp-block-group alignfull section section--tight" aria-label="How you can help">
	<!-- wp:group {"className":"container"} -->
	<div class="wp-block-group container">
		<!-- wp:group {"tagName":"ul","className":"ways"} -->
		<ul class="wp-block-group ways">
			<!-- wp:group {"tagName":"li","className":"way"} -->
			<li class="wp-block-group way">
				<!-- wp:image {"linkDestination":"none","className":"way__art","metadata":{"name":"Illustration A7: Donate"}} -->
				<figure class="wp-block-image way__art"><img alt="A7 · Donate · 1:1"/></figure>
				<!-- /wp:image -->

				<!-- wp:heading {"className":"h3"} -->
				<h2 class="wp-block-heading h3"><a href="/en/get-involved/donate/">Donate</a></h2>
				<!-- /wp:heading -->

				<!-- wp:paragraph -->
				<p>[DRAFT: Give once or monthly towards scholarships, meals and healthcare for children.]</p>
				<!-- /wp:paragraph -->
			</li>
			<!-- /wp:group -->

			<!-- wp:group {"tagName":"li","className":"way"} -->
			<li class="wp-block-group way">
				<!-- wp:image {"linkDestination":"none","className":"way__art","metadata":{"name":"Illustration A7: Volunteer"}} -->
				<figure class="wp-block-image way__art"><img alt="A7 · Volunteer · 1:1"/></figure>
				<!-- /wp:image -->

				<!-- wp:heading {"className":"h3"} -->
				<h2 class="wp-block-heading h3"><a href="/en/get-involved/volunteer/">Volunteer</a></h2>
				<!-- /wp:heading -->

				<!-- wp:paragraph -->
				<p>[DRAFT: Help run activities with children, or support communications, fundraising and sponsor relations.]</p>
				<!-- /wp:paragraph -->
			</li>
			<!-- /wp:group -->

			<!-- wp:group {"tagName":"li","className":"way"} -->
			<li class="wp-block-group way">
				<!-- wp:image {"linkDestination":"none","className":"way__art","metadata":{"name":"Illustration A7: Partner with us"}} -->
				<figure class="wp-block-image way__art"><img alt="A7 · Partner with us · 1:1"/></figure>
				<!-- /wp:image -->

				<!-- wp:heading {"className":"h3"} -->
				<h2 class="wp-block-heading h3"><a href="/en/get-involved/partner/">Partner with us</a></h2>
				<!-- /wp:heading -->

				<!-- wp:paragraph -->
				<p>[DRAFT: Companies, schools and NGOs can fund a program, send employee volunteers or run an event with us.]</p>
				<!-- /wp:paragraph -->
			</li>
			<!-- /wp:group -->

			<!-- wp:group {"tagName":"li","className":"way"} -->
			<li class="wp-block-group way">
				<!-- wp:image {"linkDestination":"none","className":"way__art","metadata":{"name":"Illustration A7: In-kind gifts and the Little Rose Bakery"}} -->
				<figure class="wp-block-image way__art"><img alt="A7 · In-kind gifts and the Little Rose Bakery · 1:1"/></figure>
				<!-- /wp:image -->

				<!-- wp:heading {"className":"h3"} -->
				<h2 class="wp-block-heading h3"><a href="/en/get-involved/in-kind/">In-kind gifts and the Little Rose Bakery</a></h2>
				<!-- /wp:heading -->

				<!-- wp:paragraph -->
				<p>[DRAFT: Give school supplies and everyday items, or order from the Little Rose Bakery.]</p>
				<!-- /wp:paragraph -->
			</li>
			<!-- /wp:group -->
		</ul>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","templateLock":"contentOnly","align":"full","className":"section section\u002d\u002dsunken","metadata":{"name":"FAQ"}} -->
<section class="wp-block-group alignfull section section--sunken">
	<!-- wp:group {"className":"container split"} -->
	<div class="wp-block-group container split">
		<!-- wp:group {"className":"split__lead"} -->
		<div class="wp-block-group split__lead">
			<!-- wp:heading {"anchor":"faq"} -->
			<h2 class="wp-block-heading" id="faq">Common questions</h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph -->
			<p>[DRAFT: Can't find your answer? Get in touch and HCWA will reply by email.]</p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"className":"hcwa-link"} -->
			<p class="hcwa-link"><a href="/en/contact/">Contact us</a></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"className":"accordion"} -->
		<div class="wp-block-group accordion">
			<!-- wp:details -->
			<details class="wp-block-details"><summary>Can I visit a center?</summary>
				<!-- wp:paragraph -->
				<p>[DRAFT: Answer supplied by HCWA.]</p>
				<!-- /wp:paragraph -->
			</details>
			<!-- /wp:details -->

			<!-- wp:details -->
			<details class="wp-block-details"><summary>Do you issue receipts for donations?</summary>
				<!-- wp:paragraph -->
				<p>[DRAFT: Answer supplied by HCWA.]</p>
				<!-- /wp:paragraph -->
			</details>
			<!-- /wp:details -->

			<!-- wp:details -->
			<details class="wp-block-details"><summary>Can my company volunteer as a team?</summary>
				<!-- wp:paragraph -->
				<p>[DRAFT: Answer supplied by HCWA.]</p>
				<!-- /wp:paragraph -->
			</details>
			<!-- /wp:details -->

			<!-- wp:details -->
			<details class="wp-block-details"><summary>What in-kind gifts are useful?</summary>
				<!-- wp:paragraph -->
				<p>[DRAFT: Answer supplied by HCWA.]</p>
				<!-- /wp:paragraph -->
			</details>
			<!-- /wp:details -->

			<!-- wp:details -->
			<details class="wp-block-details"><summary>What child-safety checks do volunteers go through?</summary>
				<!-- wp:paragraph -->
				<p>[DRAFT: Answer supplied by HCWA.]</p>
				<!-- /wp:paragraph -->
			</details>
			<!-- /wp:details -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
