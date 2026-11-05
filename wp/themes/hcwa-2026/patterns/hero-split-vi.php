<?php
/**
 * Title: Phần mở đầu chia đôi / Hero split (Tiếng Việt)
 * Slug: hcwa-2026/hero-split-vi
 * Categories: hcwa
 * Keywords: hero, mở đầu, intro, banner, ảnh
 * Viewport Width: 1400
 * Post Types: page
 * Description: Tiêu đề (H2), lời giới thiệu, hai nút và ô ảnh A2. Trang chủ dùng bản có H1 trong mẫu trang chủ.
 *
 * One of a VI/EN pair: keep both files in step (same blocks, classes and locks);
 * wp/tests/patterns-markup-test.php checks this. Draft copy is wrapped in [DRAFT: …]
 * and production refuses to publish it (hcwa-core publish guard).
 *
 * @package hcwa-2026
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"tagName":"section","templateLock":"contentOnly","align":"full","className":"hero","metadata":{"name":"Phần mở đầu"}} -->
<section class="wp-block-group alignfull hero">
	<!-- wp:group {"className":"container hero__grid"} -->
	<div class="wp-block-group container hero__grid">
		<!-- wp:group {"className":"hero__text"} -->
		<div class="wp-block-group hero__text">
			<!-- wp:paragraph {"className":"hero__eyebrow"} -->
			<p class="hero__eyebrow">[DRAFT: Hội Bảo trợ Trẻ em TP. Hồ Chí Minh · từ năm [năm]]</p>
			<!-- /wp:paragraph -->

			<!-- wp:heading {"className":"hero__title"} -->
			<h2 class="wp-block-heading hero__title">[DRAFT: Cùng trẻ em xây dựng tương lai an toàn và tươi sáng hơn.]</h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"className":"hero__intro"} -->
			<p class="hero__intro">[DRAFT: Hội hỗ trợ trẻ em, thanh thiếu niên và gia đình qua giáo dục, bảo vệ trẻ em và các chương trình dựa vào cộng đồng.]</p>
			<!-- /wp:paragraph -->

			<!-- wp:buttons {"className":"hero__actions"} -->
			<div class="wp-block-buttons hero__actions">
				<!-- wp:button -->
				<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/hoat-dong/">Khám phá hoạt động</a></div>
				<!-- /wp:button -->

				<!-- wp:button {"className":"is-style-outline"} -->
				<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/chung-tay/">Chung tay</a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:group -->

		<!-- wp:image {"linkDestination":"none","className":"hero__media","metadata":{"name":"Ảnh A2: ảnh chính trang chủ, 4:5, chỉ dùng ảnh HCWA đã duyệt"}} -->
		<figure class="wp-block-image hero__media"><img alt="A2 · Ảnh chính trang chủ · 4:5"/></figure>
		<!-- /wp:image -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
