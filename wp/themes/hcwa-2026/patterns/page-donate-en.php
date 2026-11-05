<?php
/**
 * Title: Donate page / Trang Ủng hộ (English)
 * Slug: hcwa-2026/page-donate-en
 * Categories: hcwa
 * Keywords: ủng hộ, donate, chuyển khoản, bank, starter
 * Viewport Width: 1400
 * Block Types: core/post-content
 * Post Types: page
 * Description: Donate page: what a gift does and how to give (shows finance-confirmed details only).
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
		<h1 class="wp-block-heading">Donate</h1>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"className":"page-header__intro"} -->
		<p class="page-header__intro">[DRAFT: Small or large, every gift matters.]</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</header>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","templateLock":"contentOnly","align":"full","className":"section section\u002d\u002dtight","metadata":{"name":"What your gift does"}} -->
<section class="wp-block-group alignfull section section--tight">
	<!-- wp:group {"className":"container"} -->
	<div class="wp-block-group container">
		<!-- wp:heading {"className":"h3"} -->
		<h2 class="wp-block-heading h3">What your gift does</h2>
		<!-- /wp:heading -->

		<!-- wp:list {"className":"gift-list"} -->
		<ul class="wp-block-list gift-list">
			<!-- wp:list-item -->
			<li><strong>[DRAFT: amount]</strong> [DRAFT: provides a scholarship so a child can attend school]</li>
			<!-- /wp:list-item -->

			<!-- wp:list-item -->
			<li><strong>[DRAFT: amount]</strong> [DRAFT: covers counseling, nutrition and healthcare for a family affected by HIV/AIDS]</li>
			<!-- /wp:list-item -->

			<!-- wp:list-item -->
			<li><strong>[DRAFT: amount]</strong> [DRAFT: supports a child's place in the Charity Class for a year]</li>
			<!-- /wp:list-item -->
		</ul>
		<!-- /wp:list -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","templateLock":"contentOnly","align":"full","className":"section section\u002d\u002dtight","metadata":{"name":"How to give"}} -->
<section class="wp-block-group alignfull section section--tight">
	<!-- wp:group {"className":"container donate-grid"} -->
	<div class="wp-block-group container donate-grid">
		<!-- wp:group {"className":"donate-methods"} -->
		<div class="wp-block-group donate-methods">
			<!-- wp:heading -->
			<h2 class="wp-block-heading">How to give</h2>
			<!-- /wp:heading -->

			<!-- wp:hcwa/donation-methods /-->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"tagName":"aside","className":"tell-us"} -->
		<aside class="wp-block-group tell-us">
			<!-- wp:heading {"className":"h3"} -->
			<h2 class="wp-block-heading h3">Tell us you donated</h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph -->
			<p>So HCWA can thank you and send a receipt.</p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph -->
			<p>[DRAFT: The form will be added once HCWA chooses a form tool. Until then, email HCWA the amount, the date you sent it and your name.]</p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph -->
			<p><a href="mailto:hcwa.vn@gmail.com">[DRAFT: hcwa.vn@gmail.com]</a></p>
			<!-- /wp:paragraph -->
		</aside>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
