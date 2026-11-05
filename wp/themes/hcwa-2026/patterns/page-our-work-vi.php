<?php
/**
 * Title: Trang Hoạt động / Our Work page (Tiếng Việt)
 * Slug: hcwa-2026/page-our-work-vi
 * Categories: hcwa
 * Keywords: hoạt động, our work, cơ sở, dự án, centers, programs, starter
 * Viewport Width: 1400
 * Block Types: core/post-content
 * Post Types: page
 * Description: Trang Hoạt động: đầu trang, bộ lọc lĩnh vực, cơ sở, dự án và ghi chú.
 *
 * One of a VI/EN pair: keep both files in step (same blocks, classes and locks);
 * wp/tests/patterns-markup-test.php checks this. Draft copy is wrapped in [DRAFT: …]
 * and production refuses to publish it (hcwa-core publish guard).
 *
 * @package hcwa-2026
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"tagName":"header","templateLock":"contentOnly","align":"full","className":"page-header","metadata":{"name":"Đầu trang"}} -->
<header class="wp-block-group alignfull page-header">
	<!-- wp:group {"className":"container"} -->
	<div class="wp-block-group container">
		<!-- wp:heading {"level":1} -->
		<h1 class="wp-block-heading">Hoạt động</h1>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"className":"page-header__intro"} -->
		<p class="page-header__intro">[DRAFT: Hội vận hành các cơ sở lâu dài và triển khai các dự án có thời hạn cùng đối tác.]</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</header>
<!-- /wp:group -->

<!-- wp:group {"tagName":"nav","templateLock":"contentOnly","align":"full","ariaLabel":"Lọc theo lĩnh vực","className":"filters","metadata":{"name":"Bộ lọc lĩnh vực"}} -->
<nav class="wp-block-group alignfull filters" aria-label="Lọc theo lĩnh vực">
	<!-- wp:group {"className":"container"} -->
	<div class="wp-block-group container">
		<!-- wp:list {"className":"hcwa-chips"} -->
		<ul class="wp-block-list hcwa-chips">
			<!-- wp:list-item -->
			<li><a href="/hoat-dong/">Tất cả</a></li>
			<!-- /wp:list-item -->

			<!-- wp:list-item -->
			<li><a href="/hoat-dong/?area=education">Giáo dục</a></li>
			<!-- /wp:list-item -->

			<!-- wp:list-item -->
			<li><a href="/hoat-dong/?area=protection">Bảo vệ trẻ em</a></li>
			<!-- /wp:list-item -->

			<!-- wp:list-item -->
			<li><a href="/hoat-dong/?area=family">Hỗ trợ gia đình và cộng đồng</a></li>
			<!-- /wp:list-item -->

			<!-- wp:list-item -->
			<li><a href="/hoat-dong/?area=policy">Vận động chính sách</a></li>
			<!-- /wp:list-item -->
		</ul>
		<!-- /wp:list -->
	</div>
	<!-- /wp:group -->
</nav>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","templateLock":"contentOnly","align":"full","className":"section section\u002d\u002dtight","metadata":{"name":"Cơ sở"}} -->
<section class="wp-block-group alignfull section section--tight">
	<!-- wp:group {"className":"container"} -->
	<div class="wp-block-group container">
		<!-- wp:heading {"anchor":"co-so"} -->
		<h2 class="wp-block-heading" id="co-so">Cơ sở trực thuộc</h2>
		<!-- /wp:heading -->

		<!-- wp:hcwa/center-grid {"limit":12} /-->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","templateLock":"contentOnly","align":"full","className":"section section\u002d\u002dtight","metadata":{"name":"Dự án"}} -->
<section class="wp-block-group alignfull section section--tight">
	<!-- wp:group {"className":"container"} -->
	<div class="wp-block-group container">
		<!-- wp:heading {"anchor":"du-an"} -->
		<h2 class="wp-block-heading" id="du-an">Dự án &amp; chương trình</h2>
		<!-- /wp:heading -->

		<!-- wp:hcwa/program-list {"status":"any","limit":12} /-->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","templateLock":"contentOnly","align":"full","className":"section section\u002d\u002dtight","metadata":{"name":"Ghi chú"}} -->
<section class="wp-block-group alignfull section section--tight">
	<!-- wp:group {"className":"container"} -->
	<div class="wp-block-group container">
		<!-- wp:group {"tagName":"aside","className":"note"} -->
		<aside class="wp-block-group note">
			<!-- wp:paragraph -->
			<p>[DRAFT: Cơ sở là nơi Hội vận hành lâu dài. Dự án và chương trình được tài trợ, có thời gian bắt đầu và kết thúc.]</p>
			<!-- /wp:paragraph -->
		</aside>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
