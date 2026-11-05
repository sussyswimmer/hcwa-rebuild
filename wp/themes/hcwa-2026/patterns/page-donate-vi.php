<?php
/**
 * Title: Trang Ủng hộ / Donate page (Tiếng Việt)
 * Slug: hcwa-2026/page-donate-vi
 * Categories: hcwa
 * Keywords: ủng hộ, donate, chuyển khoản, bank, starter
 * Viewport Width: 1400
 * Block Types: core/post-content
 * Post Types: page
 * Description: Trang Ủng hộ: khoản ủng hộ giúp được gì và cách ủng hộ (chỉ hiện thông tin tài chính đã xác nhận).
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
		<h1 class="wp-block-heading">Ủng hộ</h1>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"className":"page-header__intro"} -->
		<p class="page-header__intro">[DRAFT: Dù lớn hay nhỏ, mỗi khoản ủng hộ đều có ý nghĩa.]</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</header>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","templateLock":"contentOnly","align":"full","className":"section section\u002d\u002dtight","metadata":{"name":"Khoản ủng hộ giúp được gì"}} -->
<section class="wp-block-group alignfull section section--tight">
	<!-- wp:group {"className":"container"} -->
	<div class="wp-block-group container">
		<!-- wp:heading {"className":"h3"} -->
		<h2 class="wp-block-heading h3">Khoản ủng hộ của bạn giúp được gì</h2>
		<!-- /wp:heading -->

		<!-- wp:list {"className":"gift-list"} -->
		<ul class="wp-block-list gift-list">
			<!-- wp:list-item -->
			<li><strong>[DRAFT: số tiền]</strong> [DRAFT: giúp một em có học bổng để đến trường]</li>
			<!-- /wp:list-item -->

			<!-- wp:list-item -->
			<li><strong>[DRAFT: số tiền]</strong> [DRAFT: chi trả tư vấn, dinh dưỡng và chăm sóc sức khỏe cho một gia đình bị ảnh hưởng bởi HIV/AIDS]</li>
			<!-- /wp:list-item -->

			<!-- wp:list-item -->
			<li><strong>[DRAFT: số tiền]</strong> [DRAFT: giúp một em theo học Lớp học tình thương trong một năm]</li>
			<!-- /wp:list-item -->
		</ul>
		<!-- /wp:list -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","templateLock":"contentOnly","align":"full","className":"section section\u002d\u002dtight","metadata":{"name":"Cách ủng hộ"}} -->
<section class="wp-block-group alignfull section section--tight">
	<!-- wp:group {"className":"container donate-grid"} -->
	<div class="wp-block-group container donate-grid">
		<!-- wp:group {"className":"donate-methods"} -->
		<div class="wp-block-group donate-methods">
			<!-- wp:heading -->
			<h2 class="wp-block-heading">Cách ủng hộ</h2>
			<!-- /wp:heading -->

			<!-- wp:hcwa/donation-methods /-->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"tagName":"aside","className":"tell-us"} -->
		<aside class="wp-block-group tell-us">
			<!-- wp:heading {"className":"h3"} -->
			<h2 class="wp-block-heading h3">Báo cho Hội biết bạn đã ủng hộ</h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph -->
			<p>Để Hội cảm ơn và gửi biên nhận cho bạn.</p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph -->
			<p>[DRAFT: Biểu mẫu sẽ được thêm khi HCWA chọn công cụ biểu mẫu. Trong lúc chờ, hãy gửi email cho Hội số tiền, ngày chuyển và tên của bạn.]</p>
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
