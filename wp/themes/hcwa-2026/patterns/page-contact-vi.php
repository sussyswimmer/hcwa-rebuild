<?php
/**
 * Title: Trang Liên hệ / Contact page (Tiếng Việt)
 * Slug: hcwa-2026/page-contact-vi
 * Categories: hcwa
 * Keywords: liên hệ, contact, văn phòng, office, starter
 * Viewport Width: 1400
 * Block Types: core/post-content
 * Post Types: page
 * Description: Trang Liên hệ: chỗ cho biểu mẫu, văn phòng và các cơ sở.
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
		<h1 class="wp-block-heading">Liên hệ</h1>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"className":"page-header__intro"} -->
		<p class="page-header__intro">[DRAFT: Gửi lời nhắn cho Hội, hoặc gọi điện và email trực tiếp.]</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</header>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","templateLock":"contentOnly","align":"full","className":"section section\u002d\u002dtight","metadata":{"name":"Lời nhắn và văn phòng"}} -->
<section class="wp-block-group alignfull section section--tight">
	<!-- wp:group {"className":"container contact-grid"} -->
	<div class="wp-block-group container contact-grid">
		<!-- wp:group {"className":"form-slot"} -->
		<div class="wp-block-group form-slot">
			<!-- wp:heading {"className":"h3"} -->
			<h2 class="wp-block-heading h3">Gửi lời nhắn</h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph -->
			<p>[DRAFT: Biểu mẫu liên hệ sẽ được thêm khi HCWA chọn công cụ biểu mẫu. Trong lúc chờ, hãy gọi điện hoặc gửi email cho Hội.]</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"tagName":"aside","className":"offices"} -->
		<aside class="wp-block-group offices">
			<!-- wp:heading {"className":"h3"} -->
			<h2 class="wp-block-heading h3">Văn phòng và cơ sở</h2>
			<!-- /wp:heading -->

			<!-- wp:group {"tagName":"article","className":"office"} -->
			<article class="wp-block-group office">
				<!-- wp:heading {"level":3,"className":"h4"} -->
				<h3 class="wp-block-heading h4">Văn phòng Hội</h3>
				<!-- /wp:heading -->

				<!-- wp:list {"className":"contact-lines"} -->
				<ul class="wp-block-list contact-lines">
					<!-- wp:list-item -->
					<li>[DRAFT: 85/65 Phạm Viết Chánh, Phường Thạnh Mỹ Tây, TP. Hồ Chí Minh]</li>
					<!-- /wp:list-item -->

					<!-- wp:list-item -->
					<li><a href="tel:+842838401406">[DRAFT: +84 28 3840 1406]</a></li>
					<!-- /wp:list-item -->

					<!-- wp:list-item -->
					<li><a href="mailto:hcwa.vn@gmail.com">[DRAFT: hcwa.vn@gmail.com]</a></li>
					<!-- /wp:list-item -->
				</ul>
				<!-- /wp:list -->

				<!-- wp:image {"linkDestination":"none","className":"office__map","metadata":{"name":"Bản đồ C7 (không bắt buộc)"}} -->
				<figure class="wp-block-image office__map"><img alt="C7 · Bản đồ tĩnh · 4:3"/></figure>
				<!-- /wp:image -->

				<!-- wp:paragraph {"className":"hcwa-link hcwa-link\u002d\u002dexternal"} -->
				<p class="hcwa-link hcwa-link--external"><a href="https://www.google.com/maps/search/?api=1&amp;query=85%2F65+Ph%E1%BA%A1m+Vi%E1%BA%BFt+Ch%C3%A1nh%2C+TP.+H%E1%BB%93+Ch%C3%AD+Minh">Mở bản đồ</a> [DRAFT: kiểm tra bản đồ khớp với địa chỉ đã xác minh]</p>
				<!-- /wp:paragraph -->
			</article>
			<!-- /wp:group -->

			<!-- wp:group {"tagName":"article","className":"office office\u002d\u002dunit office\u002d\u002dgreen-bamboo"} -->
			<article class="wp-block-group office office--unit office--green-bamboo">
				<!-- wp:heading {"level":3,"className":"h4"} -->
				<h3 class="wp-block-heading h4">Cơ sở Bảo trợ Xã hội Tre Xanh</h3>
				<!-- /wp:heading -->

				<!-- wp:paragraph -->
				<p>[DRAFT: Địa chỉ và liên hệ đã được duyệt]</p>
				<!-- /wp:paragraph -->
			</article>
			<!-- /wp:group -->

			<!-- wp:group {"tagName":"article","className":"office office\u002d\u002dunit office\u002d\u002dlittle-rose"} -->
			<article class="wp-block-group office office--unit office--little-rose">
				<!-- wp:heading {"level":3,"className":"h4"} -->
				<h3 class="wp-block-heading h4">Mái ấm Hoa Hồng Nhỏ</h3>
				<!-- /wp:heading -->

				<!-- wp:paragraph -->
				<p>[DRAFT: Liên hệ qua văn phòng Hội. HCWA quyết định có công bố địa chỉ mái ấm hay không.]</p>
				<!-- /wp:paragraph -->
			</article>
			<!-- /wp:group -->

			<!-- wp:group {"tagName":"article","className":"office office\u002d\u002dunit office\u002d\u002dcau-han"} -->
			<article class="wp-block-group office office--unit office--cau-han">
				<!-- wp:heading {"level":3,"className":"h4"} -->
				<h3 class="wp-block-heading h4">Dự án Cầu Hàn</h3>
				<!-- /wp:heading -->

				<!-- wp:paragraph -->
				<p>[DRAFT: Địa chỉ và liên hệ đã được duyệt]</p>
				<!-- /wp:paragraph -->
			</article>
			<!-- /wp:group -->

			<!-- wp:paragraph {"className":"offices__chat"} -->
			<p class="offices__chat">[DRAFT: Nhắn tin qua Zalo (số do HCWA xác nhận) hoặc <a href="https://www.facebook.com/hcwa.org.vn">Facebook Messenger</a>.]</p>
			<!-- /wp:paragraph -->
		</aside>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
