<?php
/**
 * Title: Trang Chung tay / Get Involved page (Tiếng Việt)
 * Slug: hcwa-2026/page-get-involved-vi
 * Categories: hcwa
 * Keywords: chung tay, get involved, ủng hộ, tình nguyện, faq, starter
 * Viewport Width: 1400
 * Block Types: core/post-content
 * Post Types: page
 * Description: Trang Chung tay: đầu trang, bốn cách giúp và câu hỏi thường gặp.
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
		<h1 class="wp-block-heading">Chung tay</h1>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"className":"page-header__intro"} -->
		<p class="page-header__intro">[DRAFT: Bạn có thể ủng hộ, tình nguyện, hợp tác hoặc tặng hiện vật.]</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</header>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","templateLock":"contentOnly","align":"full","ariaLabel":"Bạn có thể giúp như thế nào","className":"section section\u002d\u002dtight","metadata":{"name":"Cách giúp"}} -->
<section class="wp-block-group alignfull section section--tight" aria-label="Bạn có thể giúp như thế nào">
	<!-- wp:group {"className":"container"} -->
	<div class="wp-block-group container">
		<!-- wp:group {"tagName":"ul","className":"ways"} -->
		<ul class="wp-block-group ways">
			<!-- wp:group {"tagName":"li","className":"way"} -->
			<li class="wp-block-group way">
				<!-- wp:image {"linkDestination":"none","className":"way__art","metadata":{"name":"Minh họa A7: Ủng hộ"}} -->
				<figure class="wp-block-image way__art"><img alt="A7 · Ủng hộ · 1:1"/></figure>
				<!-- /wp:image -->

				<!-- wp:heading {"className":"h3"} -->
				<h2 class="wp-block-heading h3"><a href="/chung-tay/ung-ho/">Ủng hộ</a></h2>
				<!-- /wp:heading -->

				<!-- wp:paragraph -->
				<p>[DRAFT: Đóng góp một lần hoặc hằng tháng cho học bổng, bữa ăn và chăm sóc sức khỏe của trẻ.]</p>
				<!-- /wp:paragraph -->
			</li>
			<!-- /wp:group -->

			<!-- wp:group {"tagName":"li","className":"way"} -->
			<li class="wp-block-group way">
				<!-- wp:image {"linkDestination":"none","className":"way__art","metadata":{"name":"Minh họa A7: Tình nguyện"}} -->
				<figure class="wp-block-image way__art"><img alt="A7 · Tình nguyện · 1:1"/></figure>
				<!-- /wp:image -->

				<!-- wp:heading {"className":"h3"} -->
				<h2 class="wp-block-heading h3"><a href="/chung-tay/tinh-nguyen/">Tình nguyện</a></h2>
				<!-- /wp:heading -->

				<!-- wp:paragraph -->
				<p>[DRAFT: Cùng tổ chức hoạt động với trẻ, hoặc hỗ trợ truyền thông, gây quỹ và quan hệ nhà tài trợ.]</p>
				<!-- /wp:paragraph -->
			</li>
			<!-- /wp:group -->

			<!-- wp:group {"tagName":"li","className":"way"} -->
			<li class="wp-block-group way">
				<!-- wp:image {"linkDestination":"none","className":"way__art","metadata":{"name":"Minh họa A7: Hợp tác"}} -->
				<figure class="wp-block-image way__art"><img alt="A7 · Hợp tác · 1:1"/></figure>
				<!-- /wp:image -->

				<!-- wp:heading {"className":"h3"} -->
				<h2 class="wp-block-heading h3"><a href="/chung-tay/doi-tac-doanh-nghiep/">Hợp tác</a></h2>
				<!-- /wp:heading -->

				<!-- wp:paragraph -->
				<p>[DRAFT: Doanh nghiệp, trường học và tổ chức có thể tài trợ chương trình, cử nhân viên tình nguyện hoặc cùng tổ chức sự kiện.]</p>
				<!-- /wp:paragraph -->
			</li>
			<!-- /wp:group -->

			<!-- wp:group {"tagName":"li","className":"way"} -->
			<li class="wp-block-group way">
				<!-- wp:image {"linkDestination":"none","className":"way__art","metadata":{"name":"Minh họa A7: Hiện vật và Tiệm bánh Hoa Hồng Nhỏ"}} -->
				<figure class="wp-block-image way__art"><img alt="A7 · Hiện vật và Tiệm bánh Hoa Hồng Nhỏ · 1:1"/></figure>
				<!-- /wp:image -->

				<!-- wp:heading {"className":"h3"} -->
				<h2 class="wp-block-heading h3"><a href="/chung-tay/hien-vat/">Hiện vật và Tiệm bánh Hoa Hồng Nhỏ</a></h2>
				<!-- /wp:heading -->

				<!-- wp:paragraph -->
				<p>[DRAFT: Tặng đồ dùng học tập, nhu yếu phẩm, hoặc đặt bánh từ Tiệm bánh Hoa Hồng Nhỏ.]</p>
				<!-- /wp:paragraph -->
			</li>
			<!-- /wp:group -->
		</ul>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","templateLock":"contentOnly","align":"full","className":"section section\u002d\u002dsunken","metadata":{"name":"Câu hỏi thường gặp"}} -->
<section class="wp-block-group alignfull section section--sunken">
	<!-- wp:group {"className":"container split"} -->
	<div class="wp-block-group container split">
		<!-- wp:group {"className":"split__lead"} -->
		<div class="wp-block-group split__lead">
			<!-- wp:heading {"anchor":"cau-hoi"} -->
			<h2 class="wp-block-heading" id="cau-hoi">Câu hỏi thường gặp</h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph -->
			<p>[DRAFT: Không thấy câu trả lời bạn cần? Hãy liên hệ, Hội sẽ trả lời qua email.]</p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"className":"hcwa-link"} -->
			<p class="hcwa-link"><a href="/lien-he/">Liên hệ với chúng tôi</a></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"className":"accordion"} -->
		<div class="wp-block-group accordion">
			<!-- wp:details -->
			<details class="wp-block-details"><summary>Tôi có thể đến thăm một cơ sở không?</summary>
				<!-- wp:paragraph -->
				<p>[DRAFT: Câu trả lời do HCWA cung cấp.]</p>
				<!-- /wp:paragraph -->
			</details>
			<!-- /wp:details -->

			<!-- wp:details -->
			<details class="wp-block-details"><summary>Hội có gửi biên nhận cho khoản ủng hộ không?</summary>
				<!-- wp:paragraph -->
				<p>[DRAFT: Câu trả lời do HCWA cung cấp.]</p>
				<!-- /wp:paragraph -->
			</details>
			<!-- /wp:details -->

			<!-- wp:details -->
			<details class="wp-block-details"><summary>Công ty tôi có thể tình nguyện theo nhóm không?</summary>
				<!-- wp:paragraph -->
				<p>[DRAFT: Câu trả lời do HCWA cung cấp.]</p>
				<!-- /wp:paragraph -->
			</details>
			<!-- /wp:details -->

			<!-- wp:details -->
			<details class="wp-block-details"><summary>Tôi có thể tặng hiện vật gì?</summary>
				<!-- wp:paragraph -->
				<p>[DRAFT: Câu trả lời do HCWA cung cấp.]</p>
				<!-- /wp:paragraph -->
			</details>
			<!-- /wp:details -->

			<!-- wp:details -->
			<details class="wp-block-details"><summary>Tình nguyện viên cần đáp ứng yêu cầu gì về an toàn trẻ em?</summary>
				<!-- wp:paragraph -->
				<p>[DRAFT: Câu trả lời do HCWA cung cấp.]</p>
				<!-- /wp:paragraph -->
			</details>
			<!-- /wp:details -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
