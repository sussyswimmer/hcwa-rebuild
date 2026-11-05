<?php
/**
 * Title: Focus areas / Lĩnh vực hoạt động (English)
 * Slug: hcwa-2026/focus-areas-en
 * Categories: hcwa
 * Keywords: focus, lĩnh vực, chúng tôi làm gì, what we do
 * Viewport Width: 1400
 * Post Types: page
 * Description: What we do: summary and the four focus areas, each linking to Our Work filtered by that area.
 *
 * One of a VI/EN pair: keep both files in step (same blocks, classes and locks);
 * wp/tests/patterns-markup-test.php checks this. Draft copy is wrapped in [DRAFT: …]
 * and production refuses to publish it (hcwa-core publish guard).
 *
 * @package hcwa-2026
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"tagName":"section","templateLock":"contentOnly","align":"full","className":"section","metadata":{"name":"What we do"}} -->
<section class="wp-block-group alignfull section">
	<!-- wp:group {"className":"container split"} -->
	<div class="wp-block-group container split">
		<!-- wp:group {"className":"split__lead"} -->
		<div class="wp-block-group split__lead">
			<!-- wp:heading -->
			<h2 class="wp-block-heading">What we do</h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph -->
			<p>[DRAFT: We care for, protect and educate children at risk of early work, abuse, dropping out of school or the effects of HIV/AIDS. We work with families, communities, local government and partner organizations.]</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"tagName":"ul","className":"focus-list"} -->
		<ul class="wp-block-group focus-list">
			<!-- wp:group {"tagName":"li","className":"focus-item"} -->
			<li class="wp-block-group focus-item">
				<!-- wp:image {"linkDestination":"none","className":"focus-item__icon","metadata":{"name":"Icon A6: Education"}} -->
				<figure class="wp-block-image focus-item__icon"><img alt="A6 · Education"/></figure>
				<!-- /wp:image -->

				<!-- wp:group {"className":"focus-item__text"} -->
				<div class="wp-block-group focus-item__text">
					<!-- wp:heading {"level":3} -->
					<h3 class="wp-block-heading"><a href="/en/our-work/?area=education">Education</a></h3>
					<!-- /wp:heading -->

					<!-- wp:paragraph -->
					<p>[DRAFT: Free classes, scholarships and life skills for children who can't attend school.]</p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->
			</li>
			<!-- /wp:group -->

			<!-- wp:group {"tagName":"li","className":"focus-item"} -->
			<li class="wp-block-group focus-item">
				<!-- wp:image {"linkDestination":"none","className":"focus-item__icon","metadata":{"name":"Icon A6: Child protection"}} -->
				<figure class="wp-block-image focus-item__icon"><img alt="A6 · Child protection"/></figure>
				<!-- /wp:image -->

				<!-- wp:group {"className":"focus-item__text"} -->
				<div class="wp-block-group focus-item__text">
					<!-- wp:heading {"level":3} -->
					<h3 class="wp-block-heading"><a href="/en/our-work/?area=protection">Child protection</a></h3>
					<!-- /wp:heading -->

					<!-- wp:paragraph -->
					<p>[DRAFT: Safe shelter, counseling and support for children at risk of abuse or exploitation.]</p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->
			</li>
			<!-- /wp:group -->

			<!-- wp:group {"tagName":"li","className":"focus-item"} -->
			<li class="wp-block-group focus-item">
				<!-- wp:image {"linkDestination":"none","className":"focus-item__icon","metadata":{"name":"Icon A6: Family \u0026 community support"}} -->
				<figure class="wp-block-image focus-item__icon"><img alt="A6 · Family &amp; community support"/></figure>
				<!-- /wp:image -->

				<!-- wp:group {"className":"focus-item__text"} -->
				<div class="wp-block-group focus-item__text">
					<!-- wp:heading {"level":3} -->
					<h3 class="wp-block-heading"><a href="/en/our-work/?area=family">Family &amp; community support</a></h3>
					<!-- /wp:heading -->

					<!-- wp:paragraph -->
					<p>[DRAFT: Family counseling, healthcare and access to social services in the community.]</p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->
			</li>
			<!-- /wp:group -->

			<!-- wp:group {"tagName":"li","className":"focus-item"} -->
			<li class="wp-block-group focus-item">
				<!-- wp:image {"linkDestination":"none","className":"focus-item__icon","metadata":{"name":"Icon A6: Policy \u0026 advocacy"}} -->
				<figure class="wp-block-image focus-item__icon"><img alt="A6 · Policy &amp; advocacy"/></figure>
				<!-- /wp:image -->

				<!-- wp:group {"className":"focus-item__text"} -->
				<div class="wp-block-group focus-item__text">
					<!-- wp:heading {"level":3} -->
					<h3 class="wp-block-heading"><a href="/en/our-work/?area=policy">Policy &amp; advocacy</a></h3>
					<!-- /wp:heading -->

					<!-- wp:paragraph -->
					<p>[DRAFT: Working with local government and partners on better policies for children.]</p>
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
