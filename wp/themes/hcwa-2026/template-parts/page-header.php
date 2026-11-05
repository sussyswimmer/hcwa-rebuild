<?php
/**
 * Inner page header: unit band (centers), breadcrumb, the page's only H1, VI name on EN
 * pages, intro line, and an optional meta row printed by a callback.
 *
 * @package hcwa-2026
 *
 * @var array $args {
 *     @type array    $trail   Breadcrumb trail after Home ([label, url] pairs).
 *     @type string   $title   H1 text (plain).
 *     @type string   $intro   Intro line (plain).
 *     @type string   $accent  Unit accent: green-bamboo | little-rose | cau-han | blue.
 *     @type string   $vi_name Vietnamese name, shown in brackets on EN pages.
 *     @type callable $eyebrow Prints a line above the H1 (post category and date).
 *     @type callable $meta    Prints the meta row (escaping its own output).
 *     @type string   $class   Extra class.
 * }
 */

defined( 'ABSPATH' ) || exit;

$hcwa_args = wp_parse_args(
	$args ?? array(),
	array(
		'trail'   => array(),
		'title'   => '',
		'intro'   => '',
		'accent'  => '',
		'vi_name' => '',
		'eyebrow' => null,
		'meta'    => null,
		'class'   => '',
	)
);
$hcwa_accent = in_array( $hcwa_args['accent'], array( 'green-bamboo', 'little-rose', 'cau-han', 'blue' ), true ) ? $hcwa_args['accent'] : '';
?>
<header class="<?php echo esc_attr( trim( 'page-header ' . $hcwa_args['class'] ) ); ?>"<?php echo $hcwa_accent ? ' style="--unit:var(--color-unit-' . esc_attr( $hcwa_accent ) . ')" data-unit' : ''; ?>>
	<div class="container">
		<?php
		get_template_part( 'template-parts/breadcrumb', null, array( 'trail' => $hcwa_args['trail'] ) );
		if ( is_callable( $hcwa_args['eyebrow'] ) ) {
			call_user_func( $hcwa_args['eyebrow'] );
		}
		?>
		<h1><?php echo esc_html( $hcwa_args['title'] ); ?></h1>
		<?php if ( '' !== $hcwa_args['vi_name'] ) : ?>
			<p class="page-header__vi" lang="vi">(<?php echo esc_html( $hcwa_args['vi_name'] ); ?>)</p>
		<?php endif; ?>
		<?php if ( '' !== trim( $hcwa_args['intro'] ) ) : ?>
			<p class="page-header__intro"><?php echo esc_html( $hcwa_args['intro'] ); ?></p>
		<?php endif; ?>
		<?php
		if ( is_callable( $hcwa_args['meta'] ) ) {
			call_user_func( $hcwa_args['meta'] );
		}
		?>
	</div>
</header>
