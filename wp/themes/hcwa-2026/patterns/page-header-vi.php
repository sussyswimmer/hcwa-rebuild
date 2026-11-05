<?php
/**
 * Title: Đầu trang / Page header (Tiếng Việt)
 * Slug: hcwa-2026/page-header-vi
 * Categories: hcwa
 * Keywords: page header, đầu trang, tiêu đề, title, intro
 * Viewport Width: 1400
 * Post Types: page
 * Description: Tiêu đề trang (H1) và lời giới thiệu. Dùng một lần, ở đầu trang. Đường dẫn do giao diện tự thêm.
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
		<h1 class="wp-block-heading">[DRAFT: Tiêu đề trang]</h1>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"className":"page-header__intro"} -->
		<p class="page-header__intro">[DRAFT: Một hoặc hai câu cho biết trang này nói về điều gì.]</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</header>
<!-- /wp:group -->
