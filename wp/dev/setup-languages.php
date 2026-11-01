<?php
/**
 * Local dev, step 1 of 2: Polylang languages and settings, permalinks, site options.
 * Run inside WordPress Playground by wp/scripts/dev.mjs (blueprint "runPHP" step). Mirrors
 * what HCWA's live site needs (07-BILINGUAL "Polylang configuration"), so it doubles as the
 * checklist for configuring staging.
 */

if ( ! defined( 'PLL_ADMIN' ) ) {
	define( 'PLL_ADMIN', true ); // Polylang's admin model can create languages
}
// Playground mounts WordPress at /wordpress; wp-env puts this folder at <wp root>/hcwa/dev.
require file_exists( '/wordpress/wp-load.php' ) ? '/wordpress/wp-load.php' : dirname( __DIR__, 2 ) . '/wp-load.php';

$model = PLL()->model;
if ( ! $model->has_languages() ) {
	// Vietnamese first: the first language is the default (no URL prefix).
	$model->add_language( array( 'name' => 'Tiếng Việt', 'slug' => 'vi', 'locale' => 'vi', 'rtl' => false, 'term_group' => 0, 'flag' => 'vn' ) );
	$model->add_language( array( 'name' => 'English', 'slug' => 'en', 'locale' => 'en_GB', 'rtl' => false, 'term_group' => 1, 'flag' => 'gb' ) );
}

$options                  = get_option( 'polylang' );
$options['default_lang']  = 'vi';
$options['force_lang']    = 1;     // language from the directory name
$options['hide_default']  = 1;     // no /vi/ prefix
$options['rewrite']       = 1;     // /en/ not /language/en/
$options['browser']       = 0;     // no automatic redirect by browser language (07-BILINGUAL)
$options['redirect_lang'] = 0;
$options['media_support'] = 1;     // alt text per language
// Copied between translations so staff enter them once (07-BILINGUAL "Synchronization").
$options['sync'] = array( 'taxonomies', 'post_meta', '_thumbnail_id', 'menu_order', 'post_parent', 'post_date', 'sticky_posts' );
update_option( 'polylang', $options );

update_option( 'permalink_structure', '/%postname%/' );
update_option( 'blogname', 'Hội Bảo trợ Trẻ em TP. Hồ Chí Minh' );
update_option( 'blogdescription', '' );
update_option( 'timezone_string', 'Asia/Ho_Chi_Minh' );
update_option( 'date_format', 'd/m/Y' );
update_option( 'posts_per_page', 12 );
update_option( 'blog_public', 0 );
update_option( 'uploads_use_yearmonth_folders', 1 );

echo "languages: " . implode( ',', $model->get_languages_list( array( 'fields' => 'slug' ) ) ) . "\n";
