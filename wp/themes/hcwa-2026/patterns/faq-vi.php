<?php
/**
 * Title: Câu hỏi thường gặp / FAQ (Tiếng Việt)
 * Slug: hcwa-2026/faq-vi
 * Categories: hcwa
 * Keywords: faq, câu hỏi, hỏi đáp, questions, accordion
 * Viewport Width: 1400
 * Post Types: page
 * Description: Câu hỏi thường gặp dạng mở/đóng, kèm liên kết liên hệ.
 *
 * One of a VI/EN pair: keep both files in step (same blocks, classes and locks);
 * wp/tests/patterns-markup-test.php checks this. Draft copy is wrapped in [DRAFT: …]
 * and production refuses to publish it (hcwa-core publish guard).
 *
 * @package hcwa-2026
 */

defined( 'ABSPATH' ) || exit;
?>
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
