<?php
/**
 * Title: Home page / Trang chủ (English)
 * Slug: hcwa-2026/page-home-en
 * Categories: hcwa
 * Keywords: trang chủ, home, starter, trang
 * Viewport Width: 1400
 * Block Types: core/post-content
 * Post Types: page
 * Description: Full home page: hero, figures, focus areas, centers, programs, news, ways to help, partners, contact.
 *
 * One of a VI/EN pair: keep both files in step (same blocks, classes and locks);
 * wp/tests/patterns-markup-test.php checks this. Draft copy is wrapped in [DRAFT: …]
 * and production refuses to publish it (hcwa-core publish guard).
 *
 * @package hcwa-2026
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"tagName":"section","templateLock":"contentOnly","align":"full","className":"hero","metadata":{"name":"Hero"}} -->
<section class="wp-block-group alignfull hero">
	<!-- wp:group {"className":"container hero__grid"} -->
	<div class="wp-block-group container hero__grid">
		<!-- wp:group {"className":"hero__text"} -->
		<div class="wp-block-group hero__text">
			<!-- wp:paragraph {"className":"hero__eyebrow"} -->
			<p class="hero__eyebrow">[DRAFT: Ho Chi Minh City Child Welfare Association · since [year]]</p>
			<!-- /wp:paragraph -->

			<!-- wp:heading {"level":1,"className":"hero__title"} -->
			<h1 class="wp-block-heading hero__title">[DRAFT: Helping children build safer, brighter futures.]</h1>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"className":"hero__intro"} -->
			<p class="hero__intro">[DRAFT: HCWA supports children, young people and families through education, child protection and community-based programs.]</p>
			<!-- /wp:paragraph -->

			<!-- wp:buttons {"className":"hero__actions"} -->
			<div class="wp-block-buttons hero__actions">
				<!-- wp:button -->
				<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/en/our-work/">Explore our work</a></div>
				<!-- /wp:button -->

				<!-- wp:button {"className":"is-style-outline"} -->
				<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/en/get-involved/">Get Involved</a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:group -->

		<!-- wp:image {"linkDestination":"none","className":"hero__media","metadata":{"name":"Image A2: homepage hero, 4:5, HCWA-approved photos only"}} -->
		<figure class="wp-block-image hero__media"><img alt="A2 · Homepage hero · 4:5"/></figure>
		<!-- /wp:image -->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"className":"container hcwa-impact-slot","metadata":{"name":"Impact figures (verified figures only)"}} -->
	<div class="wp-block-group container hcwa-impact-slot">
		<!-- wp:hcwa/impact-strip {"scopeType":"organization","limit":4,"showLink":true} /-->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

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

<!-- wp:group {"tagName":"section","templateLock":"contentOnly","align":"full","className":"section section\u002d\u002dsunken","metadata":{"name":"Centers"}} -->
<section class="wp-block-group alignfull section section--sunken">
	<!-- wp:group {"className":"container"} -->
	<div class="wp-block-group container">
		<!-- wp:group {"className":"section-head"} -->
		<div class="wp-block-group section-head">
			<!-- wp:heading -->
			<h2 class="wp-block-heading">Centers</h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"className":"section-head__intro"} -->
			<p class="section-head__intro">[DRAFT: Places where children learn, heal and grow, every day.]</p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"className":"hcwa-link"} -->
			<p class="hcwa-link"><a href="/en/our-work/#centers">See all</a></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:hcwa/center-grid {"limit":3} /-->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","templateLock":"contentOnly","align":"full","className":"section","metadata":{"name":"Programs"}} -->
<section class="wp-block-group alignfull section">
	<!-- wp:group {"className":"container"} -->
	<div class="wp-block-group container">
		<!-- wp:group {"className":"section-head"} -->
		<div class="wp-block-group section-head">
			<!-- wp:heading -->
			<h2 class="wp-block-heading">Projects &amp; programs</h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"className":"hcwa-link"} -->
			<p class="hcwa-link"><a href="/en/our-work/#programs">All projects</a></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:hcwa/program-list {"status":"any","limit":4} /-->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","templateLock":"contentOnly","align":"full","className":"section section\u002d\u002dline","metadata":{"name":"Latest news"}} -->
<section class="wp-block-group alignfull section section--line">
	<!-- wp:group {"className":"container"} -->
	<div class="wp-block-group container">
		<!-- wp:group {"className":"section-head"} -->
		<div class="wp-block-group section-head">
			<!-- wp:heading -->
			<h2 class="wp-block-heading">Latest from HCWA</h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"className":"hcwa-link"} -->
			<p class="hcwa-link"><a href="/en/news/">All news</a></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:hcwa/news-latest {"featured":true,"limit":3} /-->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","templateLock":"contentOnly","align":"full","className":"section section\u002d\u002dsunken","metadata":{"name":"Ways to help"}} -->
<section class="wp-block-group alignfull section section--sunken">
	<!-- wp:group {"className":"container"} -->
	<div class="wp-block-group container">
		<!-- wp:group {"className":"section-head"} -->
		<div class="wp-block-group section-head">
			<!-- wp:heading -->
			<h2 class="wp-block-heading">How you can help</h2>
			<!-- /wp:heading -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"tagName":"ul","className":"ways"} -->
		<ul class="wp-block-group ways">
			<!-- wp:group {"tagName":"li","className":"way"} -->
			<li class="wp-block-group way">
				<!-- wp:image {"linkDestination":"none","className":"way__art","metadata":{"name":"Illustration A7: Donate"}} -->
				<figure class="wp-block-image way__art"><img alt="A7 · Donate · 1:1"/></figure>
				<!-- /wp:image -->

				<!-- wp:heading {"level":3} -->
				<h3 class="wp-block-heading"><a href="/en/get-involved/donate/">Donate</a></h3>
				<!-- /wp:heading -->

				<!-- wp:paragraph -->
				<p>[DRAFT: Give once or monthly towards scholarships, meals and healthcare for children.]</p>
				<!-- /wp:paragraph -->
			</li>
			<!-- /wp:group -->

			<!-- wp:group {"tagName":"li","className":"way"} -->
			<li class="wp-block-group way">
				<!-- wp:image {"linkDestination":"none","className":"way__art","metadata":{"name":"Illustration A7: Volunteer"}} -->
				<figure class="wp-block-image way__art"><img alt="A7 · Volunteer · 1:1"/></figure>
				<!-- /wp:image -->

				<!-- wp:heading {"level":3} -->
				<h3 class="wp-block-heading"><a href="/en/get-involved/volunteer/">Volunteer</a></h3>
				<!-- /wp:heading -->

				<!-- wp:paragraph -->
				<p>[DRAFT: Help run activities with children, or support communications, fundraising and sponsor relations.]</p>
				<!-- /wp:paragraph -->
			</li>
			<!-- /wp:group -->

			<!-- wp:group {"tagName":"li","className":"way"} -->
			<li class="wp-block-group way">
				<!-- wp:image {"linkDestination":"none","className":"way__art","metadata":{"name":"Illustration A7: Partner with us"}} -->
				<figure class="wp-block-image way__art"><img alt="A7 · Partner with us · 1:1"/></figure>
				<!-- /wp:image -->

				<!-- wp:heading {"level":3} -->
				<h3 class="wp-block-heading"><a href="/en/get-involved/partner/">Partner with us</a></h3>
				<!-- /wp:heading -->

				<!-- wp:paragraph -->
				<p>[DRAFT: Companies, schools and NGOs can fund a program, send employee volunteers or run an event with us.]</p>
				<!-- /wp:paragraph -->
			</li>
			<!-- /wp:group -->

			<!-- wp:group {"tagName":"li","className":"way"} -->
			<li class="wp-block-group way">
				<!-- wp:image {"linkDestination":"none","className":"way__art","metadata":{"name":"Illustration A7: In-kind gifts and the Little Rose Bakery"}} -->
				<figure class="wp-block-image way__art"><img alt="A7 · In-kind gifts and the Little Rose Bakery · 1:1"/></figure>
				<!-- /wp:image -->

				<!-- wp:heading {"level":3} -->
				<h3 class="wp-block-heading"><a href="/en/get-involved/in-kind/">In-kind gifts and the Little Rose Bakery</a></h3>
				<!-- /wp:heading -->

				<!-- wp:paragraph -->
				<p>[DRAFT: Give school supplies and everyday items, or order from the Little Rose Bakery.]</p>
				<!-- /wp:paragraph -->
			</li>
			<!-- /wp:group -->
		</ul>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","templateLock":"contentOnly","align":"full","className":"section hcwa-partners","metadata":{"name":"Partners"}} -->
<section class="wp-block-group alignfull section hcwa-partners">
	<!-- wp:group {"className":"container"} -->
	<div class="wp-block-group container">
		<!-- wp:group {"className":"section-head"} -->
		<div class="wp-block-group section-head">
			<!-- wp:heading -->
			<h2 class="wp-block-heading">With thanks to our partners</h2>
			<!-- /wp:heading -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"className":"hcwa-partner-slot"} -->
		<div class="wp-block-group hcwa-partner-slot">
			<!-- wp:hcwa/partner-wall {"homeOnly":true,"limit":12} /-->
		</div>
		<!-- /wp:group -->

		<!-- wp:paragraph {"className":"hcwa-links logo-wall__links"} -->
		<p class="hcwa-links logo-wall__links"><a href="/en/about/partners/">Partners &amp; sponsors</a> <a href="/en/get-involved/partner/">Become a partner</a></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","templateLock":"contentOnly","align":"full","className":"cta-band contact-band","metadata":{"name":"Contact band"}} -->
<section class="wp-block-group alignfull cta-band contact-band">
	<!-- wp:group {"className":"container cta-band__inner"} -->
	<div class="wp-block-group container cta-band__inner">
		<!-- wp:heading -->
		<h2 class="wp-block-heading">Have a question or want to visit?</h2>
		<!-- /wp:heading -->

		<!-- wp:buttons {"className":"cta-band__actions"} -->
		<div class="wp-block-buttons cta-band__actions">
			<!-- wp:button {"className":"is-style-outline"} -->
			<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/en/contact/">Contact us</a></div>
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
