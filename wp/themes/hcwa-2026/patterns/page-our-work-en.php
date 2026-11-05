<?php
/**
 * Title: Our Work page / Trang Hoạt động (English)
 * Slug: hcwa-2026/page-our-work-en
 * Categories: hcwa
 * Keywords: hoạt động, our work, cơ sở, dự án, centers, programs, starter
 * Viewport Width: 1400
 * Block Types: core/post-content
 * Post Types: page
 * Description: Our Work hub: header, focus area filter, centers, programs and a note.
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
		<h1 class="wp-block-heading">Our Work</h1>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"className":"page-header__intro"} -->
		<p class="page-header__intro">[DRAFT: HCWA runs long-term centers and carries out time-bound projects with partners.]</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</header>
<!-- /wp:group -->

<!-- wp:group {"tagName":"nav","templateLock":"contentOnly","align":"full","ariaLabel":"Filter by focus area","className":"filters","metadata":{"name":"Focus area filter"}} -->
<nav class="wp-block-group alignfull filters" aria-label="Filter by focus area">
	<!-- wp:group {"className":"container"} -->
	<div class="wp-block-group container">
		<!-- wp:list {"className":"hcwa-chips"} -->
		<ul class="wp-block-list hcwa-chips">
			<!-- wp:list-item -->
			<li><a href="/en/our-work/">All</a></li>
			<!-- /wp:list-item -->

			<!-- wp:list-item -->
			<li><a href="/en/our-work/?area=education">Education</a></li>
			<!-- /wp:list-item -->

			<!-- wp:list-item -->
			<li><a href="/en/our-work/?area=protection">Child protection</a></li>
			<!-- /wp:list-item -->

			<!-- wp:list-item -->
			<li><a href="/en/our-work/?area=family">Family &amp; community support</a></li>
			<!-- /wp:list-item -->

			<!-- wp:list-item -->
			<li><a href="/en/our-work/?area=policy">Policy &amp; advocacy</a></li>
			<!-- /wp:list-item -->
		</ul>
		<!-- /wp:list -->
	</div>
	<!-- /wp:group -->
</nav>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","templateLock":"contentOnly","align":"full","className":"section section\u002d\u002dtight hcwa-block-section","metadata":{"name":"Centers"}} -->
<section class="wp-block-group alignfull section section--tight hcwa-block-section">
	<!-- wp:group {"className":"container"} -->
	<div class="wp-block-group container">
		<!-- wp:heading {"anchor":"centers"} -->
		<h2 class="wp-block-heading" id="centers">Centers</h2>
		<!-- /wp:heading -->

		<!-- wp:hcwa/center-grid {"limit":12} /-->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","templateLock":"contentOnly","align":"full","className":"section section\u002d\u002dtight hcwa-block-section","metadata":{"name":"Programs"}} -->
<section class="wp-block-group alignfull section section--tight hcwa-block-section">
	<!-- wp:group {"className":"container"} -->
	<div class="wp-block-group container">
		<!-- wp:heading {"anchor":"programs"} -->
		<h2 class="wp-block-heading" id="programs">Projects &amp; programs</h2>
		<!-- /wp:heading -->

		<!-- wp:hcwa/program-list {"status":"any","limit":12} /-->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","templateLock":"contentOnly","align":"full","className":"section section\u002d\u002dtight","metadata":{"name":"Note"}} -->
<section class="wp-block-group alignfull section section--tight">
	<!-- wp:group {"className":"container"} -->
	<div class="wp-block-group container">
		<!-- wp:group {"tagName":"aside","className":"note"} -->
		<aside class="wp-block-group note">
			<!-- wp:paragraph -->
			<p>[DRAFT: Centers are long-term places HCWA runs. Projects and programs are funded work with a start and an end.]</p>
			<!-- /wp:paragraph -->
		</aside>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
