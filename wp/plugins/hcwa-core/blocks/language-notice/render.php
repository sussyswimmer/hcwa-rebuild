<?php
/**
 * hcwa/language-notice (07-BILINGUAL "The switch"): a visitor asked for a language this
 * article is not translated into. The panel speaks the language they asked for, says the
 * article exists only in the page's language, and links to the article and to the news
 * page in their language. Never a silent redirect to the home page.
 *
 * Shown when ?hcwa_lang_notice=<lang> is present (hcwa_language_links() adds it) or when
 * forceLang is set; never in the page's own language.
 *
 * @package hcwa-core
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner content (none).
 * @var WP_Block $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

$hcwa_page_lang   = hcwa_lang();
$hcwa_notice_lang = hcwa_blocks_notice_lang(
	hcwa_blocks_enum( $attributes, 'forceLang', array( '', 'vi', 'en' ), '' ),
	hcwa_lang_notice_request(),
	$hcwa_page_lang
);

if ( '' === $hcwa_notice_lang ) {
	return;
}

// "Read it in Vietnamese": the article itself, without the notice argument.
$hcwa_post_id  = hcwa_blocks_context_post_id( $block ?? null );
$hcwa_read_url = $hcwa_post_id ? (string) get_permalink( $hcwa_post_id ) : '';
if ( '' === $hcwa_read_url ) {
	$hcwa_read_url = hcwa_current_url();
}
?>
<aside <?php echo get_block_wrapper_attributes( array( 'class' => 'lang-notice', 'lang' => $hcwa_notice_lang ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by WordPress. ?>>
	<?php echo hcwa_blocks_icon( 'info' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
	<div>
		<p><?php echo esc_html( hcwa_t_in( 'langNotice', $hcwa_notice_lang ) ); ?></p>
		<p class="lang-notice__links">
			<?php
			echo hcwa_blocks_text_link( $hcwa_read_url, hcwa_t_in( 'langNoticeRead', $hcwa_notice_lang ), array( 'hreflang' => $hcwa_page_lang ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
			echo ' ';
			echo hcwa_blocks_text_link( hcwa_page_url( 'news', $hcwa_notice_lang ), hcwa_t_in( 'langNoticeHub', $hcwa_notice_lang ), array( 'hreflang' => $hcwa_notice_lang ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
			?>
		</p>
	</div>
</aside>
