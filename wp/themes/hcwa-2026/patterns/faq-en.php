<?php
/**
 * Title: FAQ / Câu hỏi thường gặp (English)
 * Slug: hcwa-2026/faq-en
 * Categories: hcwa
 * Keywords: faq, câu hỏi, hỏi đáp, questions, accordion
 * Viewport Width: 1400
 * Post Types: page
 * Description: Common questions as an accordion, with a contact link.
 *
 * One of a VI/EN pair: keep both files in step (same blocks, classes and locks);
 * wp/tests/patterns-markup-test.php checks this. Draft copy is wrapped in [DRAFT: …]
 * and production refuses to publish it (hcwa-core publish guard).
 *
 * @package hcwa-2026
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"tagName":"section","templateLock":"contentOnly","align":"full","className":"section section\u002d\u002dsunken","metadata":{"name":"FAQ"}} -->
<section class="wp-block-group alignfull section section--sunken">
	<!-- wp:group {"className":"container split"} -->
	<div class="wp-block-group container split">
		<!-- wp:group {"className":"split__lead"} -->
		<div class="wp-block-group split__lead">
			<!-- wp:heading {"anchor":"faq"} -->
			<h2 class="wp-block-heading" id="faq">Common questions</h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph -->
			<p>[DRAFT: Can't find your answer? Get in touch and HCWA will reply by email.]</p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"className":"hcwa-link"} -->
			<p class="hcwa-link"><a href="/en/contact/">Contact us</a></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"className":"accordion"} -->
		<div class="wp-block-group accordion">
			<!-- wp:details -->
			<details class="wp-block-details"><summary>Can I visit a center?</summary>
				<!-- wp:paragraph -->
				<p>[DRAFT: Answer supplied by HCWA.]</p>
				<!-- /wp:paragraph -->
			</details>
			<!-- /wp:details -->

			<!-- wp:details -->
			<details class="wp-block-details"><summary>Do you issue receipts for donations?</summary>
				<!-- wp:paragraph -->
				<p>[DRAFT: Answer supplied by HCWA.]</p>
				<!-- /wp:paragraph -->
			</details>
			<!-- /wp:details -->

			<!-- wp:details -->
			<details class="wp-block-details"><summary>Can my company volunteer as a team?</summary>
				<!-- wp:paragraph -->
				<p>[DRAFT: Answer supplied by HCWA.]</p>
				<!-- /wp:paragraph -->
			</details>
			<!-- /wp:details -->

			<!-- wp:details -->
			<details class="wp-block-details"><summary>What in-kind gifts are useful?</summary>
				<!-- wp:paragraph -->
				<p>[DRAFT: Answer supplied by HCWA.]</p>
				<!-- /wp:paragraph -->
			</details>
			<!-- /wp:details -->

			<!-- wp:details -->
			<details class="wp-block-details"><summary>What child-safety checks do volunteers go through?</summary>
				<!-- wp:paragraph -->
				<p>[DRAFT: Answer supplied by HCWA.]</p>
				<!-- /wp:paragraph -->
			</details>
			<!-- /wp:details -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
