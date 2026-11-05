<?php
/**
 * Breadcrumb (every page except home). Home first; the last crumb is the current page.
 *
 * @package hcwa-2026
 *
 * @var array $args { trail: array<int, array{0: string, 1?: string}> } [label, url]; no url on the last.
 */

defined( 'ABSPATH' ) || exit;

$hcwa_trail = isset( $args['trail'] ) && is_array( $args['trail'] ) ? $args['trail'] : array();
$hcwa_last  = count( $hcwa_trail ) - 1;
?>
<nav class="breadcrumb" aria-label="<?php echo esc_attr( hcwa_theme_t( 'breadcrumb' ) ); ?>">
	<ol>
		<li><a href="<?php echo esc_url( hcwa_theme_url( 'home' ) ); ?>"><?php hcwa_theme_e( 'home' ); ?></a></li>
		<?php foreach ( $hcwa_trail as $hcwa_i => $hcwa_crumb ) : ?>
			<li>
				<?php if ( $hcwa_i === $hcwa_last || empty( $hcwa_crumb[1] ) ) : ?>
					<span<?php echo $hcwa_i === $hcwa_last ? ' aria-current="page"' : ''; ?>><?php echo esc_html( (string) $hcwa_crumb[0] ); ?></span>
				<?php else : ?>
					<a href="<?php echo esc_url( (string) $hcwa_crumb[1] ); ?>"><?php echo esc_html( (string) $hcwa_crumb[0] ); ?></a>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ol>
</nav>
