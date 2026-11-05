<?php
/**
 * The hcwa/* dynamic blocks: registration, the "HCWA" inserter category, the block UI
 * strings (front end via hcwa_t(), editor via wp.i18n) and the helpers the render.php
 * templates share.
 *
 * Every block is server-rendered (blocks/<name>/render.php) with the class names of the
 * prototype components, so the theme's port of the prototype CSS styles it. The safety
 * gates live in the core plugin and are only called from here: hcwa_get_impact_figures()
 * and hcwa_impact_strip_visible() (figures), hcwa_image() (photos),
 * hcwa_partner_logo_allowed() (logos), hcwa_donation_details() (bank details),
 * hcwa_announcement() (announcement bar).
 *
 * Empty states: on the live site a block with nothing to show prints nothing; on staging
 * it prints a short bilingual note, so reviewers can see that the slot exists.
 *
 * @package hcwa-core
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Registration
 * ---------------------------------------------------------------------- */

/**
 * Folder names of the blocks (each has a block.json).
 *
 * @return string[]
 */
function hcwa_blocks_names(): array {
	$names = array();
	foreach ( (array) glob( HCWA_CORE_DIR . 'blocks/*/block.json' ) as $file ) {
		$names[] = basename( dirname( (string) $file ) );
	}
	sort( $names );
	return $names;
}

/**
 * Registers the shared editor script and every block folder.
 */
function hcwa_blocks_register(): void {
	// Shared editor helpers (window.hcwaBlocks); each block's index.asset.php depends on it.
	wp_register_script(
		'hcwa-blocks-editor',
		HCWA_CORE_URL . 'blocks/editor.js',
		array( 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-core-data', 'wp-data', 'wp-element', 'wp-html-entities', 'wp-i18n', 'wp-server-side-render' ),
		HCWA_CORE_VERSION,
		array( 'in_footer' => true )
	);
	wp_set_script_translations( 'hcwa-blocks-editor', 'hcwa' );

	foreach ( hcwa_blocks_names() as $name ) {
		register_block_type( HCWA_CORE_DIR . 'blocks/' . $name );
	}
}
add_action( 'init', 'hcwa_blocks_register' );

/**
 * Adds the "HCWA" category at the top of the block inserter.
 *
 * @param array $categories Block categories.
 * @return array
 */
function hcwa_blocks_category( $categories ): array {
	$categories = is_array( $categories ) ? $categories : array();
	foreach ( $categories as $category ) {
		if ( is_array( $category ) && 'hcwa' === ( $category['slug'] ?? '' ) ) {
			return $categories;
		}
	}
	array_unshift(
		$categories,
		array(
			'slug'  => 'hcwa',
			'title' => 'HCWA', // The organization's name: the same in both languages.
			'icon'  => null,
		)
	);
	return $categories;
}
add_filter( 'block_categories_all', 'hcwa_blocks_category' );

/* -------------------------------------------------------------------------
 * Strings
 * ---------------------------------------------------------------------- */

/**
 * Front-end keys the blocks add to the hcwa_t() dictionary. Vietnamese is draft
 * microcopy for native review, like content/draft/ui.json. All keys are new: existing
 * ui.json and core keys are reused, never redefined.
 *
 * @return array<string, array{vi: string, en: string}>
 */
function hcwa_blocks_strings(): array {
	return array(
		// Empty states (staging only) and review hints.
		'blk_noFigures'       => array( 'vi' => 'Chưa có số liệu tác động nào để hiển thị.', 'en' => 'No impact figures to show yet.' ),
		'blk_hiddenOnLive'    => array( 'vi' => 'Trên trang chính thức, phần này chỉ hiện khi có ít nhất 2 số liệu đã được xác minh.', 'en' => 'On the live site, this section shows only once at least 2 figures are verified.' ),
		'blk_noCenters'       => array( 'vi' => 'Chưa có cơ sở nào được đăng.', 'en' => 'No centers published yet.' ),
		'blk_noPrograms'      => array( 'vi' => 'Chưa có dự án nào phù hợp.', 'en' => 'No matching programs yet.' ),
		'blk_noReports'       => array( 'vi' => 'Chưa có báo cáo nào phù hợp.', 'en' => 'No matching reports yet.' ),
		'blk_noPartners'      => array( 'vi' => 'Chưa có đối tác nào được chọn để hiển thị.', 'en' => 'No partners selected for display yet.' ),
		'blk_noNews'          => array( 'vi' => 'Chưa có bài viết nào.', 'en' => 'No posts yet.' ),
		'blk_noDonation'      => array( 'vi' => 'Chưa có thông tin ủng hộ nào được nhập.', 'en' => 'No donation details entered yet.' ),
		'blk_noPhoto'         => array( 'vi' => 'Chưa có ảnh được duyệt', 'en' => 'No approved photo yet' ),
		'blk_logoPermission'  => array( 'vi' => 'Chưa được phép hiển thị logo', 'en' => 'Logo permission pending' ),

		// Donation methods.
		'blk_payInVietnam'    => array( 'vi' => 'Chuyển khoản trong nước', 'en' => 'Bank transfer in Vietnam' ),
		'blk_payFromAbroad'   => array( 'vi' => 'Ủng hộ từ nước ngoài', 'en' => 'Giving from abroad' ),

		// Card labels. The theme has keys with the same text (cat_announcement, rt_*); these
		// have their own names so neither side silently overrides the other.
		'blk_catAnnouncement' => array( 'vi' => 'Thông báo', 'en' => 'Announcement' ),
		'blk_type_annual'     => array( 'vi' => 'Báo cáo thường niên', 'en' => 'Annual report' ),
		'blk_type_financial'  => array( 'vi' => 'Báo cáo tài chính', 'en' => 'Financial statement' ),
		'blk_type_program'    => array( 'vi' => 'Báo cáo dự án', 'en' => 'Program report' ),
		'blk_type_policy'     => array( 'vi' => 'Chính sách', 'en' => 'Policy' ),
	);
}

/**
 * Adds the block keys to hcwa-core's dictionary. Existing keys win, so ui.json and the
 * core keys are never replaced. Added at plugin load, before 'init', as strings.php asks.
 *
 * @param array $strings key => ['vi' => string, 'en' => string].
 * @return array
 */
function hcwa_blocks_add_strings( $strings ): array {
	return ( is_array( $strings ) ? $strings : array() ) + hcwa_blocks_strings();
}
add_filter( 'hcwa_strings', 'hcwa_blocks_add_strings' );

/**
 * Vietnamese for the editor labels in blocks/*.js (wp.i18n, text domain "hcwa").
 * wp/tests/blocks-strings-test.php checks that every label has an entry.
 *
 * @return array<string, string> English => Vietnamese.
 */
function hcwa_blocks_editor_vi(): array {
	return array(
		// Shared.
		'Settings'                                    => 'Cài đặt',
		'This page'                                   => 'Trang này',

		// Impact strip.
		'Impact strip'                                => 'Dải số liệu tác động',
		'Verified impact figures for the organization, a center or a program. Unverified figures never show on the live site.' => 'Số liệu tác động đã xác minh của Hội, của một cơ sở hoặc một dự án. Số liệu chưa xác minh không bao giờ hiện trên trang chính thức.',
		'Figures for'                                 => 'Số liệu của',
		'The whole organization'                      => 'Toàn Hội',
		'A center'                                    => 'Một cơ sở',
		'A program'                                   => 'Một dự án',
		'Center'                                      => 'Cơ sở',
		'Program'                                     => 'Dự án',
		'Number of figures'                           => 'Số lượng số liệu',
		'Show the link to the impact page'            => 'Hiện liên kết đến trang Tác động',
		'No figures to show. On the live site the strip needs at least 2 verified figures.' => 'Chưa có số liệu để hiển thị. Trên trang chính thức, dải số liệu cần ít nhất 2 số liệu đã xác minh.',

		// Center grid.
		'Center grid'                                 => 'Lưới cơ sở',
		'Cards for the centers HCWA runs, with approved photos only.' => 'Thẻ giới thiệu các cơ sở của Hội, chỉ dùng ảnh đã được duyệt.',
		'Number of centers'                           => 'Số lượng cơ sở',
		'No published centers yet.'                   => 'Chưa có cơ sở nào được đăng.',

		// Program list.
		'Program list'                                => 'Danh sách dự án',
		'Rows of projects and programs, active ones first.' => 'Danh sách dự án và chương trình, dự án đang hoạt động hiện trước.',
		'Status'                                      => 'Trạng thái',
		'All'                                         => 'Tất cả',
		'Active'                                      => 'Đang hoạt động',
		'Completed'                                   => 'Đã hoàn thành',
		'Number of programs'                          => 'Số lượng dự án',
		'All centers (on a center page: that center)' => 'Tất cả cơ sở (trên trang cơ sở: chính cơ sở đó)',
		'No programs match these settings.'           => 'Không có dự án nào phù hợp với cài đặt này.',

		// Report list.
		'Report list'                                 => 'Danh sách báo cáo',
		'Reports with a language badge and file size, newest year first.' => 'Báo cáo kèm nhãn ngôn ngữ và dung lượng tệp, năm mới nhất ở trên.',
		'Report type'                                 => 'Loại báo cáo',
		'All types'                                   => 'Tất cả các loại',
		'Annual report'                               => 'Báo cáo thường niên',
		'Financial statement'                         => 'Báo cáo tài chính',
		'Program report'                              => 'Báo cáo dự án',
		'Policy'                                      => 'Chính sách',
		'Number of reports'                           => 'Số lượng báo cáo',
		'No published reports match these settings.'  => 'Không có báo cáo nào phù hợp với cài đặt này.',

		// Partner wall.
		'Partner wall'                                => 'Tường logo đối tác',
		'One-tone partner logos. On the live site a logo shows only with the partner\'s written permission.' => 'Logo đối tác một màu. Trên trang chính thức, logo chỉ hiện khi đối tác đã đồng ý bằng văn bản.',
		'Only partners marked "Show on the home page"' => 'Chỉ các đối tác được đánh dấu “Hiển thị trên trang chủ”',
		'Number of logos'                             => 'Số lượng logo',
		'No partner logos to show. Check "Show on the home page" and "Permission to show the logo" on each partner.' => 'Chưa có logo đối tác nào để hiển thị. Hãy kiểm tra ô “Hiển thị trên trang chủ” và “Được phép hiển thị logo” ở từng đối tác.',

		// Latest news.
		'Latest news'                                 => 'Tin mới nhất',
		'A featured story and the latest posts in this language.' => 'Một câu chuyện nổi bật và các bài viết mới nhất bằng ngôn ngữ của trang.',
		'Show a featured story'                       => 'Hiện một câu chuyện nổi bật',
		'A sticky post first, otherwise the latest story.' => 'Ưu tiên bài được ghim, nếu không có thì lấy câu chuyện mới nhất.',
		'Number of news items'                        => 'Số lượng tin',
		'No published posts in this language yet.'    => 'Chưa có bài viết nào được đăng bằng ngôn ngữ này.',

		// Donation methods.
		'Donation methods'                            => 'Cách ủng hộ',
		'Bank transfer and giving from abroad. Shows only details HCWA finance has confirmed in Settings → HCWA.' => 'Chuyển khoản trong nước và ủng hộ từ nước ngoài. Chỉ hiện thông tin đã được bộ phận tài chính của Hội xác nhận trong Cài đặt → HCWA.',
		'Open Settings → HCWA'                        => 'Mở Cài đặt → HCWA',
		'Donation details appear here once HCWA finance has confirmed them in Settings → HCWA.' => 'Thông tin ủng hộ sẽ hiện ở đây sau khi bộ phận tài chính của Hội xác nhận trong Cài đặt → HCWA.',

		// Language notice.
		'Language notice'                             => 'Thông báo ngôn ngữ',
		'Tells visitors that an article exists in one language only, with links to it and to the news page.' => 'Báo cho khách rằng bài viết chỉ có bằng một ngôn ngữ, kèm liên kết đến bài và trang tin tức.',
		'Language the visitor asked for'              => 'Ngôn ngữ khách yêu cầu',
		'Only when the language switch asks for it'   => 'Chỉ khi khách bấm nút chuyển ngôn ngữ',
		'Vietnamese'                                  => 'Tiếng Việt',
		'English'                                     => 'Tiếng Anh',
		'Shows on a post when a visitor switches to a language the post is not translated into. Pick the other language to preview it.' => 'Hiện trên bài viết khi khách chuyển sang ngôn ngữ mà bài chưa được dịch. Chọn ngôn ngữ còn lại để xem trước.',

		// Announcement bar.
		'Announcement bar'                            => 'Thanh thông báo',
		'The one-line announcement above the header. Edit it in Settings → HCWA.' => 'Dòng thông báo ngắn phía trên phần đầu trang. Chỉnh sửa trong Cài đặt → HCWA.',
		'The announcement bar is switched off, or has no text in this language.' => 'Thanh thông báo đang tắt, hoặc chưa có nội dung bằng ngôn ngữ này.',
	);
}

/**
 * Whether a script handle is one of ours: the shared editor script or a block's
 * generated editor script handle (generate_block_asset_handle(): "hcwa-<name>-editor-script").
 *
 * @param string $handle Script handle.
 */
function hcwa_blocks_is_editor_handle( string $handle ): bool {
	return 'hcwa-blocks-editor' === $handle || (bool) preg_match( '/^hcwa-[a-z0-9-]+-editor-script(?:-\d+)?$/', $handle );
}

/**
 * Serves the Vietnamese editor labels to wp.i18n for our block scripts, in the JSON shape
 * WP_Scripts::print_translations() expects. Other locales keep the English source.
 *
 * @param string|false|null $translations JSON or null.
 * @param string|false      $file         Translation file WordPress would read.
 * @param string            $handle       Script handle.
 * @param string            $domain       Text domain.
 * @return string|false|null
 */
function hcwa_blocks_script_translations( $translations, $file, $handle, $domain ) {
	if ( null !== $translations || 'hcwa' !== $domain || ! hcwa_blocks_is_editor_handle( (string) $handle ) ) {
		return $translations;
	}
	if ( 0 !== strpos( strtolower( (string) determine_locale() ), 'vi' ) ) {
		return $translations;
	}

	return (string) wp_json_encode( hcwa_blocks_locale_data( hcwa_blocks_editor_vi() ) );
}
add_filter( 'pre_load_script_translations', 'hcwa_blocks_script_translations', 10, 4 );

/**
 * Jed locale data for wp.i18n.setLocaleData().
 *
 * @param array<string, string> $messages English => translation.
 * @return array
 */
function hcwa_blocks_locale_data( array $messages ): array {
	$data = array(
		'' => array(
			'domain'       => 'hcwa',
			'lang'         => 'vi',
			'plural-forms' => 'nplurals=1; plural=0;',
		),
	);
	foreach ( $messages as $original => $translation ) {
		$data[ $original ] = array( $translation );
	}
	return array( 'locale_data' => array( 'hcwa' => $data ) );
}

/* -------------------------------------------------------------------------
 * Attribute and context helpers
 * ---------------------------------------------------------------------- */

/**
 * Integer attribute clamped to a range.
 *
 * @param array  $attributes Block attributes.
 * @param string $key        Attribute name.
 * @param int    $default    Value when missing or not numeric.
 * @param int    $min        Lowest allowed value.
 * @param int    $max        Highest allowed value.
 */
function hcwa_blocks_int( array $attributes, string $key, int $default, int $min, int $max ): int {
	$value = $attributes[ $key ] ?? $default;
	$value = is_numeric( $value ) ? (int) $value : $default;
	return max( $min, min( $max, $value ) );
}

/**
 * Attribute limited to a list of values.
 *
 * @param array    $attributes Block attributes.
 * @param string   $key        Attribute name.
 * @param string[] $allowed    Allowed values.
 * @param string   $default    Value when missing or not allowed.
 */
function hcwa_blocks_enum( array $attributes, string $key, array $allowed, string $default ): string {
	$value = $attributes[ $key ] ?? $default;
	return ( is_string( $value ) && in_array( $value, $allowed, true ) ) ? $value : $default;
}

/**
 * Boolean attribute.
 *
 * @param array  $attributes Block attributes.
 * @param string $key        Attribute name.
 * @param bool   $default    Value when missing.
 */
function hcwa_blocks_bool( array $attributes, string $key, bool $default ): bool {
	if ( ! array_key_exists( $key, $attributes ) ) {
		return $default;
	}
	return in_array( $attributes[ $key ], array( true, 1, '1', 'true' ), true );
}

/**
 * The post the block is shown on: block context, then the global post (set by the loop,
 * the theme's single templates and the editor preview), then the queried object.
 *
 * @param mixed $block WP_Block instance or null.
 */
function hcwa_blocks_context_post_id( $block = null ): int {
	if ( $block instanceof WP_Block && ! empty( $block->context['postId'] ) ) {
		return (int) $block->context['postId'];
	}
	$post = get_post();
	if ( $post instanceof WP_Post ) {
		return (int) $post->ID;
	}
	return (int) get_queried_object_id();
}

/**
 * Base get_posts() arguments: published, in the current language, filters on (Polylang
 * adds its language clause through them).
 *
 * @param string $post_type Post type.
 * @param int    $number    Maximum number of posts.
 * @return array
 */
function hcwa_blocks_query_args( string $post_type, int $number ): array {
	$args = array(
		'post_type'        => $post_type,
		'post_status'      => 'publish',
		'numberposts'      => $number,
		'suppress_filters' => false,
		'no_found_rows'    => true,
	);
	if ( function_exists( 'pll_get_post' ) ) {
		$args['lang'] = hcwa_lang();
	}
	return $args;
}

/**
 * Translation of a post in the current language when it exists and is published,
 * otherwise the post itself (shared fields store the Vietnamese original's ID).
 *
 * @param int $post_id Post ID.
 */
function hcwa_blocks_in_lang( int $post_id ): int {
	if ( $post_id > 0 && function_exists( 'pll_get_post' ) ) {
		$translated = (int) pll_get_post( $post_id, hcwa_lang() );
		if ( $translated > 0 && 'publish' === get_post_status( $translated ) ) {
			return $translated;
		}
	}
	return $post_id;
}

/* -------------------------------------------------------------------------
 * Small markup helpers
 * ---------------------------------------------------------------------- */

/**
 * Lucide outline icon (ISC licence), the same set and paths as the prototype.
 *
 * @param string $name  arrow | chevron | close | copy | info.
 * @param string $class CSS class.
 */
function hcwa_blocks_icon( string $name, string $class = 'icon' ): string {
	$paths = array(
		'arrow'   => '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
		'chevron' => '<path d="m6 9 6 6 6-6"/>',
		'close'   => '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
		'copy'    => '<rect width="14" height="14" x="8" y="8" rx="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>',
		'info'    => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>',
	);
	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}
	return '<svg class="' . esc_attr( $class ) . '" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[ $name ] . '</svg>';
}

/**
 * Text link with arrow (prototype textLink()).
 *
 * @param string $url   Link target.
 * @param string $label Visible text (unescaped).
 * @param array  $attrs Extra attributes, name => value (unescaped).
 */
function hcwa_blocks_text_link( string $url, string $label, array $attrs = array() ): string {
	$extra = '';
	foreach ( $attrs as $name => $value ) {
		$extra .= ' ' . esc_attr( (string) $name ) . '="' . esc_attr( (string) $value ) . '"';
	}
	return '<a class="text-link" href="' . esc_url( $url ) . '"' . $extra . '><span>' . esc_html( $label ) . '</span>' . hcwa_blocks_icon( 'arrow' ) . '</a>';
}

/**
 * ' data-draft' when any text still holds a draft marker, on staging only (the theme
 * outlines [data-draft] there; production refuses to publish draft text anyway).
 *
 * @param bool   $production Whether this is the live site.
 * @param string ...$texts   Texts to check.
 */
function hcwa_blocks_draft_attr( bool $production, string ...$texts ): string {
	if ( $production || ! function_exists( 'hcwa_contains_draft' ) ) {
		return '';
	}
	foreach ( $texts as $text ) {
		if ( hcwa_contains_draft( $text ) ) {
			return ' data-draft';
		}
	}
	return '';
}

/**
 * The staging-only note for a block with nothing to show; '' on the live site.
 * Must be called while the block renders (it uses get_block_wrapper_attributes()).
 *
 * @param string $key hcwa_t() key.
 */
function hcwa_blocks_empty_note( string $key ): string {
	if ( hcwa_is_production() ) {
		return '';
	}
	return '<p ' . get_block_wrapper_attributes(
		array(
			'class'      => 'empty-state',
			'data-draft' => '',
		)
	) . '>' . esc_html( hcwa_t( $key ) ) . '</p>';
}

/**
 * Card title: tags and leading emoji removed (05-DESIGN: no emoji in UI).
 *
 * @param WP_Post $post Post.
 */
function hcwa_blocks_title( WP_Post $post ): string {
	return trim( hcwa_strip_leading_emoji( wp_strip_all_tags( get_the_title( $post ) ) ) );
}

/**
 * Short text for a card: the "Short summary" field, else the excerpt, else the start of
 * the content.
 *
 * @param WP_Post $post  Post.
 * @param int     $words Words to keep from the content.
 */
function hcwa_blocks_summary( WP_Post $post, int $words = 28 ): string {
	$summary = trim( (string) get_post_meta( $post->ID, 'hcwa_summary', true ) );
	if ( '' !== $summary ) {
		return $summary;
	}
	if ( has_excerpt( $post ) ) {
		return trim( wp_strip_all_tags( get_the_excerpt( $post ) ) );
	}
	return trim( wp_trim_words( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ), $words, '…' ) );
}

/**
 * Focus area key for the Our Work ?area= filter: the term slug without a language
 * suffix (contract: VI terms may get "-vi" when Polylang needs distinct slugs).
 *
 * @param string $slug Term slug.
 */
function hcwa_blocks_area_key( string $slug ): string {
	return (string) preg_replace( '/-(vi|en)$/', '', $slug );
}

/**
 * Focus area keys of a post, for data-areas.
 *
 * @param int $post_id Post ID.
 * @return string[]
 */
function hcwa_blocks_post_areas( int $post_id ): array {
	$terms = get_the_terms( $post_id, 'hcwa_focus_area' );
	if ( ! is_array( $terms ) ) {
		return array();
	}
	$keys = array();
	foreach ( $terms as $term ) {
		if ( $term instanceof WP_Term ) {
			$keys[] = hcwa_blocks_area_key( function_exists( 'hcwa_original_term_slug' ) ? hcwa_original_term_slug( $term ) : $term->slug );
		}
	}
	return array_values( array_unique( $keys ) );
}

/**
 * ' data-areas="…"' or ''.
 *
 * @param string[] $areas Focus area keys.
 */
function hcwa_blocks_areas_attr( array $areas ): string {
	$areas = array_filter( array_map( 'strval', $areas ), 'strlen' );
	return $areas ? ' data-areas="' . esc_attr( implode( ' ', $areas ) ) . '"' : '';
}

/* -------------------------------------------------------------------------
 * Impact strip
 * ---------------------------------------------------------------------- */

/**
 * Decimal places to show: none for whole numbers, otherwise up to 2.
 *
 * @param float $value Number.
 */
function hcwa_blocks_decimals( float $value ): int {
	if ( floor( $value ) === $value ) {
		return 0;
	}
	return abs( round( $value, 1 ) - $value ) < 0.000001 ? 1 : 2;
}

/**
 * The big number of a figure: formatted value + suffix, or the "[N]" placeholder token
 * (staging only: production never receives a figure without a verified value).
 *
 * @param array  $figure Figure from hcwa_get_impact_figures().
 * @param string $lang   'vi' or 'en'.
 */
function hcwa_blocks_figure_value( array $figure, string $lang ): string {
	$value = $figure['value'] ?? null;
	if ( ! is_numeric( $value ) ) {
		return '[N]';
	}
	$value = (float) $value;
	return hcwa_number_in( $value, $lang, hcwa_blocks_decimals( $value ) ) . (string) ( $figure['suffix'] ?? '' );
}

/**
 * One impact figure (prototype figure()). Unverified figures carry the pending chip,
 * which the theme shows on staging only.
 *
 * @param array  $figure     Figure from hcwa_get_impact_figures().
 * @param string $lang       'vi' or 'en'.
 * @param bool   $production Whether this is the live site.
 */
function hcwa_blocks_figure_html( array $figure, string $lang, bool $production ): string {
	$label = trim( hcwa_strip_leading_emoji( wp_strip_all_tags( (string) ( $figure['label'] ?? '' ) ) ) );
	$year  = isset( $figure['year'] ) && is_numeric( $figure['year'] ) && (int) $figure['year'] > 0 ? (int) $figure['year'] : 0;

	$html  = '<div class="figure"' . hcwa_blocks_draft_attr( $production, $label ) . '>';
	$html .= '<p class="figure__value">' . esc_html( hcwa_blocks_figure_value( $figure, $lang ) ) . '</p>';
	$html .= '<p class="figure__label">' . esc_html( $label );
	if ( $year ) {
		$html .= ' <span class="figure__year">(' . esc_html( (string) $year ) . ')</span>';
	}
	$html .= '</p>';
	if ( empty( $figure['verified'] ) ) {
		$html .= '<span class="chip-pending" data-pending>' . esc_html( hcwa_t_in( 'pending', $lang ) ) . '</span>';
	}
	return $html . '</div>';
}

/* -------------------------------------------------------------------------
 * Centers
 * ---------------------------------------------------------------------- */

/**
 * Accent for the unit color band; unknown values fall back to HCWA blue.
 *
 * @param string $accent Stored hcwa_accent.
 */
function hcwa_blocks_accent( string $accent ): string {
	return in_array( $accent, array( 'green-bamboo', 'little-rose', 'cau-han', 'blue' ), true ) ? $accent : 'blue';
}

/**
 * Data for one center card. The cover photo goes through hcwa_image() with the center as
 * context, so Little Rose never shows an identifiable child.
 *
 * @param WP_Post $center Center in the current language.
 * @return array{title: string, url: string, since: int, serves: string, accent: string, areas: string[], image: string}
 */
function hcwa_blocks_center_data( WP_Post $center ): array {
	return array(
		'title'  => hcwa_blocks_title( $center ),
		'url'    => (string) get_permalink( $center ),
		'since'  => (int) get_post_meta( $center->ID, 'hcwa_since_year', true ),
		'serves' => hcwa_blocks_summary( $center, 20 ),
		'accent' => (string) get_post_meta( $center->ID, 'hcwa_accent', true ),
		'areas'  => hcwa_blocks_post_areas( $center->ID ),
		'image'  => hcwa_image(
			(int) get_post_thumbnail_id( $center ),
			'medium_large',
			array(
				'loading' => 'lazy',
				'sizes'   => '(min-width: 900px) 33vw, 82vw',
			),
			$center
		),
	);
}

/**
 * Center card (prototype centerCard()). Without an approved photo: nothing on the live
 * site, a labelled slot on staging.
 *
 * @param array  $c          From hcwa_blocks_center_data().
 * @param string $lang       'vi' or 'en'.
 * @param bool   $production Whether this is the live site.
 */
function hcwa_blocks_center_card( array $c, string $lang, bool $production ): string {
	$title  = (string) ( $c['title'] ?? '' );
	$serves = (string) ( $c['serves'] ?? '' );
	$image  = (string) ( $c['image'] ?? '' );
	$since  = (int) ( $c['since'] ?? 0 );

	$html = '<article class="center-card" style="--unit:var(--color-unit-' . esc_attr( hcwa_blocks_accent( (string) ( $c['accent'] ?? '' ) ) ) . ')"' . hcwa_blocks_areas_attr( (array) ( $c['areas'] ?? array() ) ) . '>';

	if ( '' !== $image ) {
		$html .= '<div class="center-card__media">' . $image . '</div>';
	} elseif ( ! $production ) {
		$html .= '<div class="ph" role="img" aria-label="' . esc_attr( hcwa_t_in( 'blk_noPhoto', $lang ) ) . '" style="--ratio:4/3"><span class="ph__label">' . esc_html( hcwa_t_in( 'blk_noPhoto', $lang ) ) . '</span></div>';
	}

	$html .= '<div class="center-card__body">';
	$html .= '<h3 class="center-card__name"><a href="' . esc_url( (string) ( $c['url'] ?? '' ) ) . '">' . esc_html( $title ) . '</a></h3>';
	if ( $since > 0 ) {
		$html .= '<p class="center-card__since">' . esc_html( hcwa_t_in( 'since', $lang ) . ' ' . $since ) . '</p>';
	}
	if ( '' !== $serves ) {
		$html .= '<p class="center-card__serves"' . hcwa_blocks_draft_attr( $production, $serves ) . '>' . esc_html( $serves ) . '</p>';
	}
	// The whole card is the link (title ::after); this line is visual only.
	$html .= '<span class="text-link" aria-hidden="true"><span>' . esc_html( hcwa_t_in( 'visitCenter', $lang ) ) . '</span>' . hcwa_blocks_icon( 'arrow' ) . '</span>';

	return $html . '</div></article>';
}

/* -------------------------------------------------------------------------
 * Programs
 * ---------------------------------------------------------------------- */

/**
 * Years of a program: "2023–2026", "2023–" (still running) or "".
 * Same rule as the theme's hcwa_theme_year_range().
 *
 * @param int $start Start year.
 * @param int $end   End year.
 */
function hcwa_blocks_year_range( int $start, int $end ): string {
	if ( $start <= 0 ) {
		return $end > 0 ? (string) $end : '';
	}
	if ( $end <= 0 ) {
		return $start . '–';
	}
	return $start === $end ? (string) $start : $start . '–' . $end;
}

/**
 * Sort rank of a program status: active first, then planned, then completed.
 *
 * @param string $status Program status.
 */
function hcwa_blocks_status_rank( string $status ): int {
	$ranks = array(
		'active'    => 0,
		'planned'   => 1,
		'completed' => 2,
	);
	return $ranks[ $status ] ?? 3;
}

/**
 * Programs ordered by status rank, keeping the query order inside each status
 * (06-PAGES: "Show active ones first").
 *
 * @param array[] $rows Rows with a 'status' key.
 * @return array[]
 */
function hcwa_blocks_sort_programs( array $rows ): array {
	$indexed = array();
	foreach ( array_values( $rows ) as $i => $row ) {
		$indexed[] = array( hcwa_blocks_status_rank( (string) ( $row['status'] ?? '' ) ), $i, $row );
	}
	usort(
		$indexed,
		static function ( $a, $b ) {
			return array( $a[0], $a[1] ) <=> array( $b[0], $b[1] );
		}
	);
	return array_map( static fn( $item ) => $item[2], $indexed );
}

/**
 * Data for one program row. Partner names come from the shared hcwa_partner_ids (VI
 * originals), shown in the current language when a translation exists.
 *
 * @param WP_Post $program Program in the current language.
 * @return array{title: string, url: string, status: string, years: string, summary: string, partners: string[], areas: string[]}
 */
function hcwa_blocks_program_data( WP_Post $program ): array {
	$status = (string) hcwa_meta( $program->ID, 'hcwa_status' );

	$partners = array();
	foreach ( hcwa_id_list( get_post_meta( $program->ID, 'hcwa_partner_ids', true ) ) as $partner_id ) {
		$partner_id = hcwa_blocks_in_lang( $partner_id );
		if ( 'publish' === get_post_status( $partner_id ) ) {
			$name = trim( wp_strip_all_tags( get_the_title( $partner_id ) ) );
			if ( '' !== $name ) {
				$partners[] = $name;
			}
		}
	}

	return array(
		'title'    => hcwa_blocks_title( $program ),
		'url'      => (string) get_permalink( $program ),
		'status'   => in_array( $status, array( 'active', 'completed', 'planned' ), true ) ? $status : 'active',
		'years'    => hcwa_blocks_year_range( (int) get_post_meta( $program->ID, 'hcwa_start_year', true ), (int) get_post_meta( $program->ID, 'hcwa_end_year', true ) ),
		'summary'  => hcwa_blocks_summary( $program, 28 ),
		'partners' => $partners,
		'areas'    => hcwa_blocks_post_areas( $program->ID ),
	);
}

/**
 * Program row (prototype programRow()).
 *
 * @param array  $p          From hcwa_blocks_program_data().
 * @param string $lang       'vi' or 'en'.
 * @param bool   $production Whether this is the live site.
 */
function hcwa_blocks_program_row( array $p, string $lang, bool $production ): string {
	$status   = in_array( $p['status'] ?? '', array( 'active', 'completed', 'planned' ), true ) ? $p['status'] : 'active';
	$summary  = (string) ( $p['summary'] ?? '' );
	$years    = (string) ( $p['years'] ?? '' );
	$partners = array_filter( array_map( 'strval', (array) ( $p['partners'] ?? array() ) ), 'strlen' );

	$html  = '<li class="program-row"' . hcwa_blocks_areas_attr( (array) ( $p['areas'] ?? array() ) ) . ' data-status="' . esc_attr( $status ) . '">';
	$html .= '<div class="program-row__meta"><span class="status status--' . esc_attr( $status ) . '">' . esc_html( hcwa_t_in( 'status_' . $status, $lang ) ) . '</span>';
	if ( '' !== $years ) {
		$html .= '<span class="program-row__years">' . esc_html( $years ) . '</span>';
	}
	$html .= '</div>';
	$html .= '<div class="program-row__main">';
	$html .= '<h3 class="program-row__name"><a href="' . esc_url( (string) ( $p['url'] ?? '' ) ) . '">' . esc_html( (string) ( $p['title'] ?? '' ) ) . '</a></h3>';
	if ( '' !== $summary ) {
		$html .= '<p' . hcwa_blocks_draft_attr( $production, $summary ) . '>' . esc_html( $summary ) . '</p>';
	}
	if ( $partners ) {
		$html .= '<p class="program-row__partners">' . esc_html( implode( ', ', $partners ) ) . '</p>';
	}
	$html .= '</div>';
	$html .= hcwa_blocks_icon( 'arrow', 'icon program-row__arrow' );

	return $html . '</li>';
}

/* -------------------------------------------------------------------------
 * Reports
 * ---------------------------------------------------------------------- */

/**
 * Report types (contract meta hcwa_report_type).
 *
 * @return string[]
 */
function hcwa_blocks_report_types(): array {
	return array( 'annual', 'financial', 'program', 'policy' );
}

/**
 * Rows ordered by 'year', newest first; rows without a year (0) last. The incoming order
 * (newest published first) breaks ties.
 *
 * @param array[] $rows Rows with a 'year' key.
 * @return array[]
 */
function hcwa_blocks_sort_by_year( array $rows ): array {
	$indexed = array();
	foreach ( array_values( $rows ) as $i => $row ) {
		$year      = (int) ( $row['year'] ?? 0 );
		$indexed[] = array( $year > 0 ? 0 : 1, -$year, $i, $row );
	}
	usort(
		$indexed,
		static function ( $a, $b ) {
			return array( $a[0], $a[1], $a[2] ) <=> array( $b[0], $b[1], $b[2] );
		}
	);
	return array_map( static fn( $item ) => $item[3], $indexed );
}

/**
 * File size in the page language: "820 KB", "1,2 MB" (vi) / "1.2 MB" (en); '' for 0.
 *
 * @param int    $bytes Size in bytes.
 * @param string $lang  'vi' or 'en'.
 */
function hcwa_blocks_file_size( int $bytes, string $lang ): string {
	if ( $bytes <= 0 ) {
		return '';
	}
	$kb = $bytes / 1024;
	if ( $kb < 1000 ) {
		return hcwa_number_in( max( 1, (int) round( $kb ) ), $lang ) . ' KB';
	}
	$mb = $kb / 1024;
	return hcwa_number_in( round( $mb, 1 ), $lang, $mb < 10 ? 1 : 0 ) . ' MB';
}

/**
 * Document badge: "PDF · Tiếng Việt". Language names are endonyms with their own lang
 * attribute (07-BILINGUAL: every PDF link shows its language).
 *
 * @param string $format   File extension, e.g. 'pdf'.
 * @param string $doc_lang vi | en | both.
 */
function hcwa_blocks_doc_badge( string $format, string $doc_lang ): string {
	$langs = 'both' === $doc_lang ? array( 'vi', 'en' ) : ( in_array( $doc_lang, array( 'vi', 'en' ), true ) ? array( $doc_lang ) : array() );
	$parts = array();
	foreach ( $langs as $lang ) {
		$parts[] = '<span lang="' . esc_attr( $lang ) . '">' . esc_html( hcwa_language_name( $lang ) ) . '</span>';
	}
	$format = '' !== $format ? strtoupper( $format ) : 'PDF';
	return '<span class="badge">' . esc_html( $format ) . ( $parts ? ' · ' . implode( ', ', $parts ) : '' ) . '</span>';
}

/**
 * Data for one report row. The link goes to the file when there is one.
 *
 * @param WP_Post $report Report in the current language.
 * @return array{title: string, url: string, year: int, type: string, doc_lang: string, format: string, bytes: int, cover: string}
 */
function hcwa_blocks_report_data( WP_Post $report ): array {
	$file_id  = (int) get_post_meta( $report->ID, 'hcwa_file_id', true );
	$file_url = $file_id > 0 ? (string) wp_get_attachment_url( $file_id ) : '';
	$format   = '' !== $file_url ? strtolower( (string) pathinfo( (string) wp_parse_url( $file_url, PHP_URL_PATH ), PATHINFO_EXTENSION ) ) : '';

	// WordPress stores the size at upload; read the file only for older uploads.
	$meta  = $file_id > 0 ? wp_get_attachment_metadata( $file_id ) : false;
	$bytes = is_array( $meta ) && ! empty( $meta['filesize'] ) ? (int) $meta['filesize'] : 0;
	if ( ! $bytes && $file_id > 0 ) {
		$path  = (string) get_attached_file( $file_id );
		$bytes = ( '' !== $path && is_readable( $path ) ) ? (int) filesize( $path ) : 0;
	}

	return array(
		'title'    => hcwa_blocks_title( $report ),
		'url'      => '' !== $file_url ? $file_url : (string) get_permalink( $report ),
		'year'     => (int) get_post_meta( $report->ID, 'hcwa_year', true ),
		'type'     => (string) hcwa_meta( $report->ID, 'hcwa_report_type' ),
		'doc_lang' => (string) hcwa_meta( $report->ID, 'hcwa_doc_language' ),
		'format'   => '' !== $format ? $format : 'pdf',
		'bytes'    => $bytes,
		'cover'    => hcwa_image(
			(int) get_post_thumbnail_id( $report ),
			'thumbnail',
			array(
				'class'   => 'report-row__cover',
				'loading' => 'lazy',
				'alt'     => '',
			),
			$report
		),
	);
}

/**
 * Report row (prototype reportRow(), theme template-parts/report-row.php).
 *
 * @param array  $r          From hcwa_blocks_report_data().
 * @param string $lang       'vi' or 'en'.
 * @param bool   $production Whether this is the live site.
 */
function hcwa_blocks_report_row( array $r, string $lang, bool $production ): string {
	$title  = (string) ( $r['title'] ?? '' );
	$type   = in_array( $r['type'] ?? '', hcwa_blocks_report_types(), true ) ? $r['type'] : '';
	$year   = (int) ( $r['year'] ?? 0 );
	$format = (string) ( $r['format'] ?? 'pdf' );
	$meta   = implode( ' · ', array_filter( array( $year > 0 ? (string) $year : '', '' !== $type ? hcwa_t_in( 'blk_type_' . $type, $lang ) : '' ), 'strlen' ) );
	$cover  = (string) ( $r['cover'] ?? '' );

	$html = '<li class="report-row"' . hcwa_blocks_draft_attr( $production, $title ) . '>';
	if ( '' !== $cover ) {
		$html .= $cover;
	} else {
		$html .= '<span class="report-row__cover report-row__cover--empty" aria-hidden="true">' . esc_html( strtoupper( '' !== $format ? $format : 'pdf' ) ) . '</span>';
	}
	$html .= '<div><h3 class="report-row__title"><a href="' . esc_url( (string) ( $r['url'] ?? '' ) ) . '">' . esc_html( $title ) . '</a></h3>';
	if ( '' !== $meta ) {
		$html .= '<p class="report-row__meta">' . esc_html( $meta ) . '</p>';
	}
	$html .= '</div>';
	$html .= hcwa_blocks_doc_badge( $format, (string) ( $r['doc_lang'] ?? '' ) );
	$html .= '<span class="report-row__size">' . esc_html( hcwa_blocks_file_size( (int) ( $r['bytes'] ?? 0 ), $lang ) ) . '</span>';

	return $html . '</li>';
}

/* -------------------------------------------------------------------------
 * Partners
 * ---------------------------------------------------------------------- */

/**
 * Data for one partner logo, or null when it must not show. Live site: only with
 * "Permission to show the logo" and a logo (contract rule 5). Staging: every partner,
 * marked when permission is missing.
 *
 * @param WP_Post $partner    Partner in the current language.
 * @param bool    $production Whether this is the live site.
 * @return array{name: string, url: string, logo: string, permitted: bool}|null
 */
function hcwa_blocks_partner_data( WP_Post $partner, bool $production ): ?array {
	$permitted = hcwa_partner_logo_allowed( $partner->ID, true );
	if ( $production && ! $permitted ) {
		return null;
	}

	$name    = trim( wp_strip_all_tags( get_the_title( $partner ) ) );
	$logo_id = (int) get_post_thumbnail_id( $partner );
	$logo    = $logo_id > 0 ? (string) wp_get_attachment_image(
		$logo_id,
		'medium',
		false,
		array(
			'class'   => 'partner-logo',
			'alt'     => $name,
			'loading' => 'lazy',
		)
	) : '';

	if ( $production && '' === $logo ) {
		return null;
	}

	return array(
		'name'      => $name,
		'url'       => (string) get_post_meta( $partner->ID, 'hcwa_website', true ),
		'logo'      => $logo,
		'permitted' => $permitted,
	);
}

/**
 * One logo in the wall. The logo's alt text is the partner name (05-DESIGN).
 *
 * @param array  $p    From hcwa_blocks_partner_data().
 * @param string $lang 'vi' or 'en'.
 */
function hcwa_blocks_partner_item( array $p, string $lang ): string {
	$name = (string) ( $p['name'] ?? '' );
	$logo = (string) ( $p['logo'] ?? '' );
	$url  = (string) ( $p['url'] ?? '' );

	if ( '' !== $logo ) {
		$inner = '' !== $url ? '<a href="' . esc_url( $url ) . '">' . $logo . '</a>' : $logo;
	} else {
		$inner = '<span class="ph ph--logo" role="img" aria-label="' . esc_attr( $name ) . '"><span class="ph__label">' . esc_html( $name ) . '</span></span>';
	}
	if ( empty( $p['permitted'] ) ) {
		$inner .= ' <span class="chip-pending" data-pending>' . esc_html( hcwa_t_in( 'blk_logoPermission', $lang ) ) . '</span>';
	}

	return '<li>' . $inner . '</li>';
}

/* -------------------------------------------------------------------------
 * News
 * ---------------------------------------------------------------------- */

/**
 * Seeded post categories: logical key => slugs in both languages (contract Names).
 *
 * @return array<string, string[]>
 */
function hcwa_blocks_category_slugs(): array {
	return array(
		'news'          => array( 'news', 'tin-tuc' ),
		'stories'       => array( 'stories', 'cau-chuyen' ),
		'events'        => array( 'events', 'su-kien' ),
		'announcements' => array( 'announcements', 'thong-bao' ),
	);
}

/**
 * Logical key for a category from its slugs (any language, with or without a -vi/-en
 * suffix), or '' for a category that is not one of the seeded four.
 *
 * @param string[] $slugs Slugs of one category and its translations.
 */
function hcwa_blocks_category_key( array $slugs ): string {
	$slugs = array_map( static fn( $s ) => hcwa_blocks_area_key( (string) $s ), $slugs );
	foreach ( hcwa_blocks_category_slugs() as $key => $known ) {
		if ( array_intersect( $slugs, $known ) ) {
			return $key;
		}
	}
	return '';
}

/**
 * hcwa_t() key of the card label for a category key ('' if none).
 *
 * @param string $key Logical category key.
 */
function hcwa_blocks_category_label_key( string $key ): string {
	$map = array(
		'news'          => 'cat_news',
		'stories'       => 'cat_story',
		'events'        => 'cat_event',
		'announcements' => 'blk_catAnnouncement',
	);
	return $map[ $key ] ?? '';
}

/**
 * Category of a post for its card: the first seeded category, else the first real one.
 *
 * @param WP_Post $post Post.
 * @return array{key: string, label: string}
 */
function hcwa_blocks_post_category( WP_Post $post ): array {
	$fallback = '';
	foreach ( (array) get_the_category( $post->ID ) as $term ) {
		if ( ! $term instanceof WP_Term ) {
			continue;
		}
		if ( 'uncategorized' === $term->slug && (int) get_option( 'default_category' ) === (int) $term->term_id ) {
			continue;
		}
		$slugs = array( $term->slug );
		if ( function_exists( 'hcwa_original_term_slug' ) ) {
			$slugs[] = hcwa_original_term_slug( $term );
		}
		$key = hcwa_blocks_category_key( $slugs );
		if ( '' !== $key ) {
			return array(
				'key'   => $key,
				'label' => hcwa_t( hcwa_blocks_category_label_key( $key ) ),
			);
		}
		if ( '' === $fallback ) {
			$fallback = $term->name;
		}
	}
	return array(
		'key'   => '',
		'label' => $fallback,
	);
}

/**
 * Which row is the featured story: the first sticky post, else the first story, else the
 * newest post. -1 when there are no rows.
 *
 * @param array[] $rows Rows with 'sticky' (bool) and 'category' (logical key), newest first.
 */
function hcwa_blocks_pick_featured( array $rows ): int {
	$rows = array_values( $rows );
	foreach ( $rows as $i => $row ) {
		if ( ! empty( $row['sticky'] ) ) {
			return $i;
		}
	}
	foreach ( $rows as $i => $row ) {
		if ( 'stories' === ( $row['category'] ?? '' ) ) {
			return $i;
		}
	}
	return $rows ? 0 : -1;
}

/**
 * Data for a news card or row.
 *
 * @param WP_Post $post       Post in the current language.
 * @param bool    $with_image Whether to build the (gated) image and excerpt (featured card).
 * @return array{title: string, url: string, date: string, label: string, category: string, excerpt: string, image: string}
 */
function hcwa_blocks_news_data( WP_Post $post, bool $with_image = false ): array {
	$category = hcwa_blocks_post_category( $post );
	return array(
		'title'    => hcwa_blocks_title( $post ),
		'url'      => (string) get_permalink( $post ),
		'date'     => substr( (string) $post->post_date, 0, 10 ),
		'label'    => $category['label'],
		'category' => $category['key'],
		'excerpt'  => $with_image ? hcwa_blocks_summary( $post, 28 ) : '',
		'image'    => $with_image ? hcwa_image(
			(int) get_post_thumbnail_id( $post ),
			'medium_large',
			array(
				'loading' => 'lazy',
				'sizes'   => '(min-width: 900px) 58vw, 100vw',
			),
			$post
		) : '',
	);
}

/**
 * Meta line of a news card: category label and localized date.
 *
 * @param array  $n     From hcwa_blocks_news_data().
 * @param string $lang  'vi' or 'en'.
 * @param string $class CSS class of the line.
 */
function hcwa_blocks_news_meta( array $n, string $lang, string $class ): string {
	$html  = '<p class="' . esc_attr( $class ) . '">';
	$label = (string) ( $n['label'] ?? '' );
	if ( '' !== $label ) {
		$html .= '<span class="label">' . esc_html( $label ) . '</span>';
	}
	$date = (string) ( $n['date'] ?? '' );
	if ( '' !== $date ) {
		$html .= '<time datetime="' . esc_attr( $date ) . '">' . esc_html( hcwa_date_in( $date, $lang ) ) . '</time>';
	}
	return $html . '</p>';
}

/**
 * Featured story card (prototype storyCard()). Stories carry the "names changed" line
 * (CLAUDE.md safeguarding). Without an approved photo the media slot is left out on
 * the live site.
 *
 * @param array  $n          From hcwa_blocks_news_data( $post, true ).
 * @param string $lang       'vi' or 'en'.
 * @param bool   $production Whether this is the live site.
 */
function hcwa_blocks_story_card( array $n, string $lang, bool $production ): string {
	$title   = (string) ( $n['title'] ?? '' );
	$excerpt = (string) ( $n['excerpt'] ?? '' );
	$image   = (string) ( $n['image'] ?? '' );

	$html = '<article class="story-card story-card--featured"' . hcwa_blocks_draft_attr( $production, $title, $excerpt ) . '>';
	if ( '' !== $image ) {
		$html .= '<div class="story-card__media">' . $image . '</div>';
	} elseif ( ! $production ) {
		$html .= '<div class="story-card__media story-card__media--empty" aria-hidden="true"></div>';
	}
	$html .= hcwa_blocks_news_meta( $n, $lang, 'story-card__meta' );
	$html .= '<h3 class="story-card__title"><a href="' . esc_url( (string) ( $n['url'] ?? '' ) ) . '">' . esc_html( $title ) . '</a></h3>';
	if ( '' !== $excerpt ) {
		$html .= '<p class="story-card__excerpt">' . esc_html( $excerpt ) . '</p>';
	}
	if ( 'stories' === ( $n['category'] ?? '' ) ) {
		$html .= '<p class="story-card__note">' . esc_html( hcwa_t_in( 'namesChanged', $lang ) ) . '</p>';
	}
	return $html . '</article>';
}

/**
 * Compact news row (prototype newsItem()).
 *
 * @param array  $n          From hcwa_blocks_news_data().
 * @param string $lang       'vi' or 'en'.
 * @param bool   $production Whether this is the live site.
 */
function hcwa_blocks_news_item( array $n, string $lang, bool $production ): string {
	$title = (string) ( $n['title'] ?? '' );
	$html  = '<li class="news-item"' . hcwa_blocks_draft_attr( $production, $title ) . '>';
	$html .= hcwa_blocks_news_meta( $n, $lang, 'news-item__meta' );
	$html .= '<h3 class="news-item__title"><a href="' . esc_url( (string) ( $n['url'] ?? '' ) ) . '">' . esc_html( $title ) . '</a></h3>';
	return $html . '</li>';
}

/* -------------------------------------------------------------------------
 * Language notice
 * ---------------------------------------------------------------------- */

/**
 * Language the notice speaks: the block's forceLang, else ?hcwa_lang_notice=. '' when
 * neither is set, or when it is the page's own language (nothing to explain then).
 *
 * @param string $force     forceLang attribute.
 * @param string $requested hcwa_lang_notice_request().
 * @param string $current   Language of the page.
 */
function hcwa_blocks_notice_lang( string $force, string $requested, string $current ): string {
	$lang = '' !== $force ? $force : $requested;
	if ( ! in_array( $lang, array( 'vi', 'en' ), true ) || $lang === $current ) {
		return '';
	}
	return $lang;
}

/* -------------------------------------------------------------------------
 * Donation methods
 * ---------------------------------------------------------------------- */

/**
 * Anchor IDs of the two methods in each language (links may point at them).
 *
 * @param string $lang 'vi' or 'en'.
 * @return array{in_vietnam: string, abroad: string}
 */
function hcwa_blocks_method_ids( string $lang ): array {
	return 'en' === $lang
		? array(
			'in_vietnam' => 'in-vietnam',
			'abroad'     => 'from-abroad',
		)
		: array(
			'in_vietnam' => 'trong-nuoc',
			'abroad'     => 'quoc-te',
		);
}

/**
 * Rows of the labelled placeholder panel (staging, before finance confirms): the same
 * rows as hcwa_donation_details_from(), without values.
 *
 * @param string $lang 'vi' or 'en'.
 * @return array{in_vietnam: array[], abroad: array[]}
 */
function hcwa_blocks_placeholder_rows( string $lang ): array {
	$row = static fn( string $id, string $label, bool $copy, string $hint = '' ): array => array(
		'id'    => $id,
		'label' => hcwa_t_in( $label, $lang ),
		'value' => '',
		'copy'  => $copy,
		'hint'  => '' !== $hint ? hcwa_t_in( $hint, $lang ) : '',
	);
	return array(
		'in_vietnam' => array(
			$row( 'bank', 'pay_bank', false ),
			$row( 'account', 'pay_account', true ),
			$row( 'holder', 'pay_holder', false ),
			$row( 'note', 'pay_note', true, 'pay_noteHint' ),
		),
		'abroad'     => array(
			$row( 'bank', 'pay_bank', false ),
			$row( 'swift', 'pay_swift', true ),
			$row( 'account', 'pay_accountAbroad', true ),
			$row( 'holder', 'pay_holder', false ),
		),
	);
}

/**
 * Payment detail rows with copy buttons (prototype detailRows()). A row without a value
 * is a placeholder: "Waiting for HCWA finance" and a disabled copy button.
 *
 * @param array[] $rows Rows: id, label, value, copy, hint.
 * @param string  $lang 'vi' or 'en'.
 */
function hcwa_blocks_pay_rows( array $rows, string $lang ): string {
	$html = '<dl class="pay-details">';
	foreach ( $rows as $row ) {
		$label = (string) ( $row['label'] ?? '' );
		$value = (string) ( $row['value'] ?? '' );
		$hint  = (string) ( $row['hint'] ?? '' );
		$copy  = ! empty( $row['copy'] );

		$html .= '<div class="pay-details__row"><dt>' . esc_html( $label ) . '</dt><dd>';
		if ( '' === $value ) {
			$html .= '<span class="pending-value" data-donation-placeholder>' . esc_html( hcwa_t_in( 'pendingFinance', $lang ) ) . '</span>';
		} elseif ( $copy ) {
			$html .= '<code>' . esc_html( $value ) . '</code>';
		} else {
			$html .= esc_html( $value );
		}
		if ( '' !== $hint ) {
			$html .= '<span class="pay-details__hint">' . esc_html( $hint ) . '</span>';
		}
		$html .= '</dd>';

		if ( $copy ) {
			$html .= '<button class="btn btn--small btn--secondary" type="button" data-copy="' . esc_attr( $value ) . '"' . ( '' === $value ? ' disabled' : '' ) . ' aria-label="' . esc_attr( hcwa_t_in( 'copy', $lang ) . ': ' . $label ) . '">' . hcwa_blocks_icon( 'copy' ) . '<span>' . esc_html( hcwa_t_in( 'copy', $lang ) ) . '</span></button>';
		} else {
			// Keeps the two-column grid of the row (prototype).
			$html .= '<span></span>';
		}
		$html .= '</div>';
	}
	return $html . '</dl>';
}

/**
 * One payment method as an accordion item (prototype method()).
 *
 * @param string $id    Anchor ID.
 * @param string $title Summary text (unescaped).
 * @param string $body  Inner HTML (already escaped).
 * @param bool   $open  Open on load.
 */
function hcwa_blocks_method( string $id, string $title, string $body, bool $open = false ): string {
	return '<details class="method" id="' . esc_attr( $id ) . '"' . ( $open ? ' open' : '' ) . '><summary><span>' . esc_html( $title ) . '</span>' . hcwa_blocks_icon( 'chevron' ) . '</summary><div class="method__body">' . $body . '</div></details>';
}

/**
 * The methods, from finance-confirmed details or as the labelled placeholder panel.
 * Returns '' when there is nothing to show; render.php decides about empty states.
 *
 * @param array|null $details hcwa_donation_details(), or null for the placeholder panel.
 * @param string     $lang    'vi' or 'en'.
 * @param string     $qr_html VietQR <img> (confirmed details only), '' for none.
 */
function hcwa_blocks_donation_methods( ?array $details, string $lang, string $qr_html = '' ): string {
	$ids         = hcwa_blocks_method_ids( $lang );
	$placeholder = null === $details;

	if ( $placeholder ) {
		$rows    = hcwa_blocks_placeholder_rows( $lang );
		$qr_html = '<div class="ph pay__qr" role="img" aria-label="' . esc_attr( hcwa_t_in( 'pay_qr', $lang ) . ': ' . hcwa_t_in( 'pendingFinance', $lang ) ) . '" style="--ratio:1/1" data-donation-placeholder><span class="ph__code">C6</span><span class="ph__label">' . esc_html( hcwa_t_in( 'pendingFinance', $lang ) ) . '</span></div>';
		$paypal  = '<p class="method__alt"><button class="btn btn--secondary btn--small" type="button" disabled data-donation-placeholder>' . esc_html( hcwa_t_in( 'pay_paypal', $lang ) ) . '</button></p>';
	} else {
		$rows       = array(
			'in_vietnam' => array_values( (array) ( $details['in_vietnam'] ?? array() ) ),
			'abroad'     => array_values( (array) ( $details['abroad'] ?? array() ) ),
		);
		$paypal_url = (string) ( $details['paypal_url'] ?? '' );
		$paypal     = '' !== $paypal_url ? '<p class="method__alt"><a class="btn btn--secondary btn--small" href="' . esc_url( $paypal_url ) . '">' . esc_html( hcwa_t_in( 'pay_paypal', $lang ) ) . '</a></p>' : '';
	}

	$html = '';
	if ( $rows['in_vietnam'] || '' !== $qr_html ) {
		$body  = '<div class="pay">' . ( $rows['in_vietnam'] ? hcwa_blocks_pay_rows( $rows['in_vietnam'], $lang ) : '' ) . $qr_html . '</div>';
		$html .= hcwa_blocks_method( $ids['in_vietnam'], hcwa_t_in( 'blk_payInVietnam', $lang ), $body, true );
	}
	if ( $rows['abroad'] || '' !== $paypal ) {
		$body  = ( $rows['abroad'] ? hcwa_blocks_pay_rows( $rows['abroad'], $lang ) : '' ) . $paypal;
		$html .= hcwa_blocks_method( $ids['abroad'], hcwa_t_in( 'blk_payFromAbroad', $lang ), $body, '' === $html );
	}
	return $html;
}
