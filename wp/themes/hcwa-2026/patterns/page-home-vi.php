<?php
/**
 * Title: Trang chủ / Home page (Tiếng Việt)
 * Slug: hcwa-2026/page-home-vi
 * Categories: hcwa
 * Keywords: trang chủ, home, starter, trang
 * Viewport Width: 1400
 * Block Types: core/post-content
 * Post Types: page
 * Description: Toàn bộ trang chủ: mở đầu, số liệu, lĩnh vực, cơ sở, dự án, tin, cách giúp, đối tác, liên hệ.
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

			<!-- wp:heading {"level":1,"className":"hero__title"} -->
			<h1 class="wp-block-heading hero__title">[DRAFT: Cùng trẻ em xây dựng tương lai an toàn và tươi sáng hơn.]</h1>
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

	<!-- wp:group {"className":"container hcwa-impact-slot","metadata":{"name":"Số liệu tác động (chỉ hiện số đã xác minh)"}} -->
	<div class="wp-block-group container hcwa-impact-slot">
		<!-- wp:hcwa/impact-strip {"scopeType":"organization","limit":4,"showLink":true} /-->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","templateLock":"contentOnly","align":"full","className":"section","metadata":{"name":"Chúng tôi làm gì"}} -->
<section class="wp-block-group alignfull section">
	<!-- wp:group {"className":"container split"} -->
	<div class="wp-block-group container split">
		<!-- wp:group {"className":"split__lead"} -->
		<div class="wp-block-group split__lead">
			<!-- wp:heading -->
			<h2 class="wp-block-heading">Chúng tôi làm gì</h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph -->
			<p>[DRAFT: Hội chăm sóc, bảo vệ và giáo dục trẻ em có nguy cơ phải lao động sớm, bị xâm hại, bỏ học hoặc bị ảnh hưởng bởi HIV/AIDS. Chúng tôi làm việc cùng gia đình, cộng đồng, chính quyền và các tổ chức đối tác.]</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"tagName":"ul","className":"focus-list"} -->
		<ul class="wp-block-group focus-list">
			<!-- wp:group {"tagName":"li","className":"focus-item"} -->
			<li class="wp-block-group focus-item">
				<!-- wp:image {"linkDestination":"none","className":"focus-item__icon","metadata":{"name":"Biểu tượng A6: Giáo dục"}} -->
				<figure class="wp-block-image focus-item__icon"><img alt="A6 · Giáo dục"/></figure>
				<!-- /wp:image -->

				<!-- wp:group {"className":"focus-item__text"} -->
				<div class="wp-block-group focus-item__text">
					<!-- wp:heading {"level":3} -->
					<h3 class="wp-block-heading"><a href="/hoat-dong/?area=education">Giáo dục</a></h3>
					<!-- /wp:heading -->

					<!-- wp:paragraph -->
					<p>[DRAFT: Lớp học miễn phí, học bổng và kỹ năng sống cho trẻ chưa thể đến trường.]</p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->
			</li>
			<!-- /wp:group -->

			<!-- wp:group {"tagName":"li","className":"focus-item"} -->
			<li class="wp-block-group focus-item">
				<!-- wp:image {"linkDestination":"none","className":"focus-item__icon","metadata":{"name":"Biểu tượng A6: Bảo vệ trẻ em"}} -->
				<figure class="wp-block-image focus-item__icon"><img alt="A6 · Bảo vệ trẻ em"/></figure>
				<!-- /wp:image -->

				<!-- wp:group {"className":"focus-item__text"} -->
				<div class="wp-block-group focus-item__text">
					<!-- wp:heading {"level":3} -->
					<h3 class="wp-block-heading"><a href="/hoat-dong/?area=protection">Bảo vệ trẻ em</a></h3>
					<!-- /wp:heading -->

					<!-- wp:paragraph -->
					<p>[DRAFT: Nơi ở an toàn, tư vấn và hỗ trợ cho trẻ có nguy cơ bị xâm hại hoặc bóc lột.]</p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->
			</li>
			<!-- /wp:group -->

			<!-- wp:group {"tagName":"li","className":"focus-item"} -->
			<li class="wp-block-group focus-item">
				<!-- wp:image {"linkDestination":"none","className":"focus-item__icon","metadata":{"name":"Biểu tượng A6: Hỗ trợ gia đình và cộng đồng"}} -->
				<figure class="wp-block-image focus-item__icon"><img alt="A6 · Hỗ trợ gia đình và cộng đồng"/></figure>
				<!-- /wp:image -->

				<!-- wp:group {"className":"focus-item__text"} -->
				<div class="wp-block-group focus-item__text">
					<!-- wp:heading {"level":3} -->
					<h3 class="wp-block-heading"><a href="/hoat-dong/?area=family">Hỗ trợ gia đình và cộng đồng</a></h3>
					<!-- /wp:heading -->

					<!-- wp:paragraph -->
					<p>[DRAFT: Tư vấn gia đình, chăm sóc sức khỏe và kết nối dịch vụ xã hội ngay tại cộng đồng.]</p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->
			</li>
			<!-- /wp:group -->

			<!-- wp:group {"tagName":"li","className":"focus-item"} -->
			<li class="wp-block-group focus-item">
				<!-- wp:image {"linkDestination":"none","className":"focus-item__icon","metadata":{"name":"Biểu tượng A6: Vận động chính sách"}} -->
				<figure class="wp-block-image focus-item__icon"><img alt="A6 · Vận động chính sách"/></figure>
				<!-- /wp:image -->

				<!-- wp:group {"className":"focus-item__text"} -->
				<div class="wp-block-group focus-item__text">
					<!-- wp:heading {"level":3} -->
					<h3 class="wp-block-heading"><a href="/hoat-dong/?area=policy">Vận động chính sách</a></h3>
					<!-- /wp:heading -->

					<!-- wp:paragraph -->
					<p>[DRAFT: Làm việc cùng chính quyền và đối tác để chính sách cho trẻ em tốt hơn.]</p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->
			</li>
			<!-- /wp:group -->
		</ul>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","templateLock":"contentOnly","align":"full","className":"section section\u002d\u002dsunken","metadata":{"name":"Cơ sở"}} -->
<section class="wp-block-group alignfull section section--sunken">
	<!-- wp:group {"className":"container"} -->
	<div class="wp-block-group container">
		<!-- wp:group {"className":"section-head"} -->
		<div class="wp-block-group section-head">
			<!-- wp:heading -->
			<h2 class="wp-block-heading">Cơ sở trực thuộc</h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"className":"section-head__intro"} -->
			<p class="section-head__intro">[DRAFT: Nơi trẻ em được học, được chữa lành và lớn lên, mỗi ngày.]</p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"className":"hcwa-link"} -->
			<p class="hcwa-link"><a href="/hoat-dong/#co-so">Xem tất cả</a></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:hcwa/center-grid {"limit":3} /-->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","templateLock":"contentOnly","align":"full","className":"section","metadata":{"name":"Dự án"}} -->
<section class="wp-block-group alignfull section">
	<!-- wp:group {"className":"container"} -->
	<div class="wp-block-group container">
		<!-- wp:group {"className":"section-head"} -->
		<div class="wp-block-group section-head">
			<!-- wp:heading -->
			<h2 class="wp-block-heading">Dự án &amp; chương trình</h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"className":"hcwa-link"} -->
			<p class="hcwa-link"><a href="/hoat-dong/#du-an">Tất cả dự án</a></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:hcwa/program-list {"status":"any","limit":4} /-->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","templateLock":"contentOnly","align":"full","className":"section section\u002d\u002dline","metadata":{"name":"Tin mới"}} -->
<section class="wp-block-group alignfull section section--line">
	<!-- wp:group {"className":"container"} -->
	<div class="wp-block-group container">
		<!-- wp:group {"className":"section-head"} -->
		<div class="wp-block-group section-head">
			<!-- wp:heading -->
			<h2 class="wp-block-heading">Tin mới từ Hội</h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"className":"hcwa-link"} -->
			<p class="hcwa-link"><a href="/tin-tuc/">Tất cả tin tức</a></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:hcwa/news-latest {"featured":true,"limit":3} /-->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","templateLock":"contentOnly","align":"full","className":"section section\u002d\u002dsunken","metadata":{"name":"Cách giúp"}} -->
<section class="wp-block-group alignfull section section--sunken">
	<!-- wp:group {"className":"container"} -->
	<div class="wp-block-group container">
		<!-- wp:group {"className":"section-head"} -->
		<div class="wp-block-group section-head">
			<!-- wp:heading -->
			<h2 class="wp-block-heading">Bạn có thể giúp như thế nào</h2>
			<!-- /wp:heading -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"tagName":"ul","className":"ways"} -->
		<ul class="wp-block-group ways">
			<!-- wp:group {"tagName":"li","className":"way"} -->
			<li class="wp-block-group way">
				<!-- wp:image {"linkDestination":"none","className":"way__art","metadata":{"name":"Minh họa A7: Ủng hộ"}} -->
				<figure class="wp-block-image way__art"><img alt="A7 · Ủng hộ · 1:1"/></figure>
				<!-- /wp:image -->

				<!-- wp:heading {"level":3} -->
				<h3 class="wp-block-heading"><a href="/chung-tay/ung-ho/">Ủng hộ</a></h3>
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

				<!-- wp:heading {"level":3} -->
				<h3 class="wp-block-heading"><a href="/chung-tay/tinh-nguyen/">Tình nguyện</a></h3>
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

				<!-- wp:heading {"level":3} -->
				<h3 class="wp-block-heading"><a href="/chung-tay/doi-tac-doanh-nghiep/">Hợp tác</a></h3>
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

				<!-- wp:heading {"level":3} -->
				<h3 class="wp-block-heading"><a href="/chung-tay/hien-vat/">Hiện vật và Tiệm bánh Hoa Hồng Nhỏ</a></h3>
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

<!-- wp:group {"tagName":"section","templateLock":"contentOnly","align":"full","className":"section hcwa-partners","metadata":{"name":"Đối tác"}} -->
<section class="wp-block-group alignfull section hcwa-partners">
	<!-- wp:group {"className":"container"} -->
	<div class="wp-block-group container">
		<!-- wp:group {"className":"section-head"} -->
		<div class="wp-block-group section-head">
			<!-- wp:heading -->
			<h2 class="wp-block-heading">Cảm ơn các đối tác đã đồng hành</h2>
			<!-- /wp:heading -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"className":"hcwa-partner-slot"} -->
		<div class="wp-block-group hcwa-partner-slot">
			<!-- wp:hcwa/partner-wall {"homeOnly":true,"limit":12} /-->
		</div>
		<!-- /wp:group -->

		<!-- wp:paragraph {"className":"hcwa-links logo-wall__links"} -->
		<p class="hcwa-links logo-wall__links"><a href="/ve-chung-toi/doi-tac/">Đối tác &amp; nhà tài trợ</a> <a href="/chung-tay/doi-tac-doanh-nghiep/">Trở thành đối tác</a></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","templateLock":"contentOnly","align":"full","className":"cta-band contact-band","metadata":{"name":"Dải liên hệ"}} -->
<section class="wp-block-group alignfull cta-band contact-band">
	<!-- wp:group {"className":"container cta-band__inner"} -->
	<div class="wp-block-group container cta-band__inner">
		<!-- wp:heading -->
		<h2 class="wp-block-heading">Bạn có câu hỏi hoặc muốn đến thăm?</h2>
		<!-- /wp:heading -->

		<!-- wp:buttons {"className":"cta-band__actions"} -->
		<div class="wp-block-buttons cta-band__actions">
			<!-- wp:button {"className":"is-style-outline"} -->
			<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/lien-he/">Liên hệ với chúng tôi</a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->

		<!-- wp:list {"className":"cta-band__contact"} -->
		<ul class="wp-block-list cta-band__contact">
			<!-- wp:list-item -->
			<li><a href="tel:+842838401406">[DRAFT: +84 28 3840 1406]</a></li>
			<!-- /wp:list-item -->

			<!-- wp:list-item -->
			<li><a href="mailto:hcwa.vn@gmail.com">[DRAFT: hcwa.vn@gmail.com]</a></li>
			<!-- /wp:list-item -->
		</ul>
		<!-- /wp:list -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
