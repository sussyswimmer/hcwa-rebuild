<?php
/**
 * The hcwa/* dynamic blocks (11-WP-PORT.md 4.1): registration, the "HCWA" inserter
 * category, the block UI strings (front end via hcwa_t(), editor via wp.i18n), and one PHP
 * builder per prototype component (prototype/src/partials/components.mjs, layout.mjs,
 * lit-header.mjs, louvers.mjs, pages/*.mjs), used by the blocks and, through the theme's
 * hcwa_theme_* bridge, by the templates.
 *
 * Section blocks print the prototype's markup exactly and no wrapper element (2.1 rule 3):
 * they never call get_block_wrapper_attributes(). Every text attribute passes
 * hcwa_attr_text() and hcwa_draft_text() (2.1 rules 7 and 13). The safety gates are only
 * called from here: hcwa_get_impact_figures() and hcwa_impact_strip_visible() (figures),
 * hcwa_image() (photos), hcwa_partner_logo_allowed() (logos), hcwa_donation_details()
 * (bank details), hcwa_announcement() (announcement bar), and the Little Rose switches in
 * sections.php.
 *
 * @package hcwa-core
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/blocks-sections.php';

/* -------------------------------------------------------------------------
 * Registration
 * ---------------------------------------------------------------------- */

/**
 * Folder names of the blocks: the keys of blocks/blocks-manifest.php, which holds each
 * folder's block.json as PHP (wp/tests/blocks-files-test.php keeps the two equal).
 *
 * @return string[]
 */
function hcwa_blocks_names(): array {
	$names = array_keys( (array) require HCWA_CORE_DIR . 'blocks/blocks-manifest.php' );
	sort( $names );
	return $names;
}

/**
 * Registers the shared editor script and every block folder.
 *
 * WordPress 6.7+ reads the block metadata from the manifest (one PHP file that opcache
 * keeps) instead of reading and decoding each block.json on every request
 * (wp_register_block_metadata_collection()). Older versions, or a manifest WordPress
 * refuses, fall back to the block.json files.
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

	if ( function_exists( 'wp_register_block_metadata_collection' ) ) {
		wp_register_block_metadata_collection( HCWA_CORE_DIR . 'blocks', HCWA_CORE_DIR . 'blocks/blocks-manifest.php' );
	}
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
		// The placeholder a center's "since" line shows until HCWA verifies the year (work.json sinceToken).
		'blk_yearToken'       => array( 'vi' => '[năm]', 'en' => '[year]' ),
		// The center page's evening band when the center has no heading of its own; a protected
		// center is supported, never visited (moved here from the theme, which no longer prints it).
		'visitTitle'          => array( 'vi' => 'Muốn đến thăm {name}?', 'en' => 'Want to visit {name}?' ),
		'supportTitle'        => array( 'vi' => 'Muốn hỗ trợ {name}?', 'en' => 'Want to support {name}?' ),

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
 * wp/tests/blocks-files-test.php checks that every label has an entry.
 *
 * @return array<string, string> English => Vietnamese.
 */
function hcwa_blocks_editor_vi(): array {
	return array(
		// Shared.
		'Settings'                                    => 'Cài đặt',
		'This page'                                   => 'Trang này',
		'Heading'                                     => 'Tiêu đề',
		'Intro'                                       => 'Giới thiệu',
		'Text'                                        => 'Nội dung',
		'Title'                                       => 'Tiêu đề',
		'Item %d'                                     => 'Mục %d',
		'Question'                                    => 'Câu hỏi',
		'Amount'                                      => 'Số tiền',
		'Little Rose'                                 => 'Hoa Hồng Nhỏ',
		'Open Settings → HCWA'                        => 'Mở Cài đặt → HCWA',
		'Show the link to the impact page'            => 'Hiện liên kết đến trang Tác động',
		'Draft text shows only on staging. On the live site it is left out.' => 'Nội dung nháp chỉ hiện trên bản thử. Trên website chính thức, nội dung này được bỏ đi.',

		// Containers.
		'Page sections'                               => 'Các phần của trang',
		'The locked sections of a page built from the prototype. Change the words inside each section; the sections themselves stay in place.' => 'Các phần đã khóa của một trang dựng theo bản mẫu. Hãy sửa chữ trong từng phần; bản thân các phần được giữ nguyên vị trí.',
		'Day'                                         => 'Dải ngày',
		'The day gradient behind the sections of an inner page.' => 'Dải màu một ngày phía sau các phần của trang con.',

		// Headers and bands.
		'Lit header'                                  => 'Đầu trang có ánh nắng',
		'Page header with a picture from the world, the breadcrumb, the heading and an intro.' => 'Đầu trang có hình từ không gian 3D, đường dẫn trang, tiêu đề và đoạn giới thiệu.',
		'Donate header'                               => 'Đầu trang Ủng hộ',
		'The Donate page header with its jump links.' => 'Đầu trang Ủng hộ kèm các liên kết nhảy tới từng phần.',
		'First button'                                => 'Nút đầu tiên',
		'Jump to giving from abroad'                  => 'Liên kết tới phần ủng hộ từ nước ngoài',
		'Jump to the form'                            => 'Liên kết tới biểu mẫu',
		'Call us'                                     => 'Gọi cho chúng tôi',
		'Write to us'                                 => 'Viết cho chúng tôi',
		'Evening band'                                => 'Dải hoàng hôn',
		'The dusk band at the end of a page: a heading, the contact lines and buttons.' => 'Dải hoàng hôn ở cuối trang: tiêu đề, thông tin liên hệ và các nút.',
		'Note'                                        => 'Ghi chú',

		// Home.
		'Hero'                                        => 'Phần mở đầu',
		'The first screen of the home page: heading, intro and two buttons.' => 'Màn hình đầu tiên của trang chủ: tiêu đề, giới thiệu và hai nút.',
		'What we do'                                  => 'Chúng tôi làm gì',
		'The passage into the light, with the statement read word by word.' => 'Lối đi vào vùng sáng, với câu giới thiệu được đọc từng chữ.',
		'Statement'                                   => 'Câu giới thiệu',
		'Centers corridor'                            => 'Hành lang các cơ sở',
		'One door per published center, from the center pages. Little Rose follows the safeguarding rules.' => 'Mỗi cơ sở đã đăng là một cánh cửa, lấy từ trang của cơ sở. Hoa Hồng Nhỏ theo các quy tắc bảo vệ trẻ em.',
		'Areas of work'                               => 'Lĩnh vực hoạt động',
		'The four focus areas as a floor of cement tiles, the figures and the programs.' => 'Bốn lĩnh vực hoạt động như một nền gạch bông, cùng các số liệu và dự án.',
		'Latest news'                                 => 'Tin mới nhất',
		'The three latest posts in this language, and a featured story on staging.' => 'Ba bài viết mới nhất bằng ngôn ngữ này, và một câu chuyện nổi bật trên bản thử.',
		'Shutters'                                    => 'Cửa chớp',
		'The shutters opening onto the ways to help. Pictures only.' => 'Cửa chớp mở ra các cách giúp đỡ. Chỉ có hình ảnh.',
		'How you can help'                            => 'Bạn có thể giúp như thế nào',
		'The four ways to help as louver slats.'      => 'Bốn cách giúp đỡ dưới dạng các lá chớp.',
		'Partner wall'                                => 'Tường logo đối tác',
		'Approved one-tone partner logos, shown once at least four are allowed.' => 'Logo đối tác một màu đã được duyệt, chỉ hiện khi có ít nhất bốn logo được phép.',

		// Inner pages.
		'Our Work hub'                                => 'Trang Hoạt động',
		'The filters, the wall of center doors and the program rows.' => 'Bộ lọc, bức tường cửa các cơ sở và danh sách dự án.',
		'Centers lead'                                => 'Giới thiệu các cơ sở',
		'Programs lead'                               => 'Giới thiệu các dự án',
		'Ways to help'                                => 'Các cách giúp đỡ',
		'Questions'                                   => 'Câu hỏi',
		'Common questions with one answer each.'      => 'Các câu hỏi thường gặp, mỗi câu một câu trả lời.',
		'Answer'                                      => 'Câu trả lời',
		'Gift examples'                               => 'Ví dụ khoản ủng hộ',
		'What a gift does. Staging only until HCWA sets the amounts.' => 'Khoản ủng hộ giúp được gì. Chỉ hiện trên bản thử cho tới khi HCWA quyết định số tiền.',
		'Donation methods'                            => 'Cách ủng hộ',
		'The transfer slip and the "tell us you gave" form. Only details HCWA finance has confirmed ever show.' => 'Phiếu chuyển khoản và biểu mẫu báo cho Hội. Chỉ thông tin đã được bộ phận tài chính của Hội xác nhận mới được hiện.',
		'Contact form and office'                     => 'Biểu mẫu liên hệ và văn phòng',
		'The contact form on staging, and the office address and lines HCWA has confirmed.' => 'Biểu mẫu liên hệ trên bản thử, cùng địa chỉ văn phòng và thông tin liên hệ đã được HCWA xác nhận.',
		'Contact: centers'                            => 'Liên hệ: các cơ sở',
		'Each center with its way in. Little Rose has no address of its own.' => 'Từng cơ sở với cách liên hệ. Hoa Hồng Nhỏ không có địa chỉ riêng.',

		// Kept blocks.
		'Impact strip'                                => 'Dải số liệu tác động',
		'Verified impact figures for the organization, a center or a program. Unverified figures never show on the live site.' => 'Số liệu tác động đã xác minh của Hội, của một cơ sở hoặc một dự án. Số liệu chưa xác minh không bao giờ hiện trên trang chính thức.',
		'Figures for'                                 => 'Số liệu của',
		'The whole organization'                      => 'Toàn Hội',
		'A center'                                    => 'Một cơ sở',
		'A program'                                   => 'Một dự án',
		'Center'                                      => 'Cơ sở',
		'Program'                                     => 'Dự án',
		'Number of figures'                           => 'Số lượng số liệu',
		'No figures to show. On the live site the strip needs at least 2 verified figures.' => 'Chưa có số liệu để hiển thị. Trên trang chính thức, dải số liệu cần ít nhất 2 số liệu đã xác minh.',
		'Program list'                                => 'Danh sách dự án',
		'Rows of projects and programs, active ones first.' => 'Danh sách dự án và chương trình, dự án đang hoạt động hiện trước.',
		'Status'                                      => 'Trạng thái',
		'All'                                         => 'Tất cả',
		'Active'                                      => 'Đang hoạt động',
		'Completed'                                   => 'Đã hoàn thành',
		'Number of programs'                          => 'Số lượng dự án',
		'All centers (on a center page: that center)' => 'Tất cả cơ sở (trên trang cơ sở: chính cơ sở đó)',
		'No programs match these settings.'           => 'Không có dự án nào phù hợp với cài đặt này.',
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
		'Language notice'                             => 'Thông báo ngôn ngữ',
		'Tells visitors that an article exists in one language only, with links to it and to the news page.' => 'Báo cho khách rằng bài viết chỉ có bằng một ngôn ngữ, kèm liên kết đến bài và trang tin tức.',
		'Preview for a visitor who asked for'         => 'Xem trước cho khách đã chọn',
		'Changes only this preview. Nothing is saved with the post.' => 'Chỉ thay đổi bản xem trước này, không lưu gì vào bài viết.',
		'No preview'                                  => 'Không xem trước',
		'Vietnamese'                                  => 'Tiếng Việt',
		'English'                                     => 'Tiếng Anh',
		'Shows only to visitors the language switch sends here because this post has no translation in their language. Choose a language under "Preview for a visitor who asked for" to see it.' => 'Chỉ hiện cho khách được nút chuyển ngôn ngữ đưa đến đây vì bài viết chưa có bản dịch bằng ngôn ngữ của họ. Chọn một ngôn ngữ ở mục “Xem trước cho khách đã chọn” để xem thử.',
		'Announcement bar'                            => 'Thanh thông báo',
		'The one-line announcement above the header. Edit it in Settings → HCWA.' => 'Dòng thông báo ngắn phía trên phần đầu trang. Chỉnh sửa trong Cài đặt → HCWA.',
		'The announcement bar is switched off, or has no text in this language.' => 'Thanh thông báo đang tắt, hoặc chưa có nội dung bằng ngôn ngữ này.',
		'Nothing to show here yet.'                   => 'Chưa có gì để hiển thị ở đây.',
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
 * A text attribute as it prints (2.1 rules 7 and 13): plain text, unwrapped off production,
 * '' on production when it is draft.
 *
 * @param array  $attributes Block attributes.
 * @param string $key        Attribute name.
 * @return array{text: string, draft: bool}
 */
function hcwa_blocks_text( array $attributes, string $key ): array {
	$value = $attributes[ $key ] ?? '';
	return hcwa_draft_text( hcwa_attr_text( is_scalar( $value ) ? (string) $value : '' ) );
}

/**
 * A text attribute inside an array item (ways[], faq[], gifts[]).
 *
 * @param mixed  $item Array item.
 * @param string $key  Field name.
 * @return array{text: string, draft: bool}
 */
function hcwa_blocks_item_text( $item, string $key ): array {
	$value = is_array( $item ) ? ( $item[ $key ] ?? '' ) : '';
	return hcwa_draft_text( hcwa_attr_text( is_scalar( $value ) ? (string) $value : '' ) );
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

/**
 * Library IDs referenced by posts' meta, for one cache warm-up.
 *
 * @param WP_Post[] $posts Posts.
 * @param string[]  $keys  Meta keys holding one ID or a list of IDs.
 * @return int[]
 */
function hcwa_blocks_attachment_ids( array $posts, array $keys = array( '_thumbnail_id' ) ): array {
	$ids = array();
	foreach ( $posts as $post ) {
		if ( ! $post instanceof WP_Post ) {
			continue;
		}
		foreach ( $keys as $key ) {
			foreach ( hcwa_id_list( (array) get_post_meta( $post->ID, $key, true ) ) as $id ) {
				$ids[ $id ] = true;
			}
		}
	}
	return array_keys( $ids );
}

/**
 * Loads the attachments the posts print in one go (core's _prime_post_caches()).
 *
 * @param WP_Post[] $posts Posts.
 * @param string[]  $keys  Meta keys holding attachment IDs.
 */
function hcwa_blocks_prime_attachments( array $posts, array $keys = array( '_thumbnail_id' ) ): void {
	$ids = hcwa_blocks_attachment_ids( $posts, $keys );
	if ( count( $ids ) > 1 && function_exists( '_prime_post_caches' ) ) {
		_prime_post_caches( $ids, true, true );
	}
}

/**
 * Card title: tags and leading emoji removed (05-DESIGN: no emoji in UI).
 *
 * @param WP_Post $post Post.
 */
function hcwa_blocks_title( WP_Post $post ): string {
	return trim( hcwa_strip_leading_emoji( hcwa_attr_text( (string) get_the_title( $post ) ) ) );
}

/* -------------------------------------------------------------------------
 * Markup helpers (prototype partials/components.mjs)
 * ---------------------------------------------------------------------- */

/**
 * ' data-draft' when the flag is set, else ''. Never on production (7.7: no data-draft there),
 * whatever the caller decided: a draft text prints nothing on production, but a line built
 * from a confirmed part and a draft part must not carry the outline either.
 *
 * @param bool $draft Whether the element carries draft text.
 */
function hcwa_draft_attr( bool $draft ): string {
	return $draft && ! hcwa_is_production() ? ' data-draft' : '';
}

/**
 * Button (prototype button()): an <a> with href, else a <button type=button>.
 *
 * @param string $href  Link target, '' for a button.
 * @param string $label Visible text (plain).
 * @param string $kind  primary | secondary | on-dusk.
 * @param string $attrs Extra attributes, already escaped (' data-donate').
 * @param string $cls   Extra classes.
 */
function hcwa_button_html( string $href, string $label, string $kind = 'primary', string $attrs = '', string $cls = '' ): string {
	$class = 'btn btn--' . $kind . ( '' !== $cls ? ' ' . $cls : '' );
	$attrs = '' !== $attrs ? ' ' . ltrim( $attrs ) : '';
	if ( '' !== $href ) {
		return '<a class="' . esc_attr( $class ) . '" href="' . esc_url( $href ) . '"' . $attrs . '>' . esc_html( $label ) . '</a>';
	}
	return '<button class="' . esc_attr( $class ) . '" type="button"' . $attrs . '>' . esc_html( $label ) . '</button>';
}

/**
 * Text link with arrow (prototype textLink()).
 *
 * @param string $href  Link target.
 * @param string $label Visible text (plain).
 * @param string $attrs Extra attributes, already escaped.
 */
function hcwa_text_link_html( string $href, string $label, string $attrs = '' ): string {
	return '<a class="text-link" href="' . esc_url( $href ) . '"' . ( '' !== $attrs ? ' ' . ltrim( $attrs ) : '' ) . '><span>' . esc_html( $label ) . '</span>' . hcwa_icon( 'arrow' ) . '</a>';
}

/**
 * The dashed "Pending verification" chip; '' on production (no data-pending there).
 */
function hcwa_pending_chip_html(): string {
	return hcwa_is_production() ? '' : '<span class="chip-pending" data-pending>' . esc_html( hcwa_t( 'pending' ) ) . '</span>';
}

/**
 * Status pill (prototype statusPill()).
 *
 * @param string $status active | completed | planned.
 */
function hcwa_status_pill_html( string $status ): string {
	return '<span class="status status--' . esc_attr( $status ) . '">' . esc_html( hcwa_t( 'status_' . $status ) ) . '</span>';
}

/**
 * Breadcrumb (prototype breadcrumb()): Home, then the trail; the last item is the page.
 *
 * @param array<int, array{0: string, 1?: string}> $trail [label, url] pairs, the last without url.
 */
function hcwa_breadcrumb_html( array $trail ): string {
	$items = '';
	$last  = count( $trail ) - 1;
	foreach ( array_values( $trail ) as $i => $step ) {
		$label  = (string) ( $step[0] ?? '' );
		$items .= '<li>' . ( $i === $last || empty( $step[1] ) ? '<span aria-current="page">' . esc_html( $label ) . '</span>' : '<a href="' . esc_url( (string) $step[1] ) . '">' . esc_html( $label ) . '</a>' ) . '</li>';
	}
	return '<nav class="breadcrumb" aria-label="' . esc_attr( hcwa_t( 'breadcrumb' ) ) . '">' . "\n  <ol>\n    "
		. '<li><a href="' . esc_url( hcwa_page_url( 'home' ) ) . '">' . esc_html( hcwa_t( 'home' ) ) . '</a></li>' . "\n    "
		. $items . "\n  </ol>\n</nav>";
}

/**
 * The WCAG 2.2.2 pause button (prototype pauseToggle()).
 *
 * @param string $cls       Extra class.
 * @param string $text_safe data-text-safe value, '' for none.
 */
function hcwa_pause_toggle_html( string $cls = '', string $text_safe = '' ): string {
	return '<button class="pause-toggle' . ( '' !== $cls ? ' ' . esc_attr( $cls ) : '' ) . '" type="button" data-paused="false" data-motion-toggle' . ( '' !== $text_safe ? ' data-text-safe="' . esc_attr( $text_safe ) . '"' : '' ) . ">\n  "
		. hcwa_icon( 'pause', 'icon pause-toggle__pause' ) . hcwa_icon( 'play', 'icon pause-toggle__play' ) . '<span class="pause-toggle__label">' . esc_html( hcwa_t( 'motionPause' ) ) . "</span>\n</button>";
}

/**
 * Unit colour and emblem of a center for inline style.
 *
 * @param array $c From hcwa_center_view().
 */
function hcwa_unit_style( array $c ): string {
	return '--unit:' . $c['color'];
}

/* -------------------------------------------------------------------------
 * Data readers
 * ---------------------------------------------------------------------- */

/**
 * Published centers in the current language, in menu order.
 *
 * @return WP_Post[]
 */
function hcwa_blocks_centers(): array {
	static $cache = array();
	$lang = hcwa_lang();
	if ( ! isset( $cache[ $lang ] ) ) {
		$args            = hcwa_blocks_query_args( 'hcwa_center', 50 );
		$args['orderby'] = array(
			'menu_order' => 'ASC',
			'ID'         => 'ASC',
		);
		$cache[ $lang ] = array_values( array_filter( (array) get_posts( $args ), static fn( $p ) => $p instanceof WP_Post ) );
	}
	return $cache[ $lang ];
}

/**
 * Published programs in the current language: active first, then completed, then planned,
 * each in menu order (prototype STATUSES order).
 *
 * @return WP_Post[]
 */
function hcwa_blocks_programs(): array {
	static $cache = array();
	$lang = hcwa_lang();
	if ( ! isset( $cache[ $lang ] ) ) {
		$args            = hcwa_blocks_query_args( 'hcwa_program', 100 );
		$args['orderby'] = array(
			'menu_order' => 'ASC',
			'ID'         => 'ASC',
		);
		$posts = array_values( array_filter( (array) get_posts( $args ), static fn( $p ) => $p instanceof WP_Post ) );
		$rank  = array(
			'active'    => 0,
			'completed' => 1,
			'planned'   => 2,
		);
		$order = array_flip( array_map( static fn( WP_Post $p ) => $p->ID, $posts ) );
		usort(
			$posts,
			static fn( WP_Post $a, WP_Post $b ) => array( $rank[ hcwa_program_status( $a->ID ) ] ?? 3, $order[ $a->ID ] ) <=> array( $rank[ hcwa_program_status( $b->ID ) ] ?? 3, $order[ $b->ID ] )
		);
		$cache[ $lang ] = $posts;
	}
	return $cache[ $lang ];
}

/**
 * A program's status (shared field), active by default.
 *
 * @param int $program_id Program ID.
 */
function hcwa_program_status( int $program_id ): string {
	$status = (string) get_post_meta( $program_id, 'hcwa_status', true );
	return in_array( $status, array( 'active', 'completed', 'planned' ), true ) ? $status : 'active';
}

/**
 * Focus area key of a term slug (education-vi → education); '' when not one of the four.
 *
 * @param string $slug Term slug.
 */
function hcwa_area_key( string $slug ): string {
	$key = (string) preg_replace( '/-(vi|en)$/', '', $slug );
	return in_array( $key, hcwa_area_keys(), true ) ? $key : '';
}

/**
 * The focus areas of a post, as keys in the order they were given.
 *
 * @param int $post_id Post ID.
 * @return string[]
 */
function hcwa_post_areas( int $post_id ): array {
	$terms = get_the_terms( $post_id, 'hcwa_focus_area' );
	$keys  = array();
	foreach ( is_array( $terms ) ? $terms : array() as $term ) {
		$key = hcwa_area_key( (string) $term->slug );
		if ( '' === $key && function_exists( 'hcwa_original_term_slug' ) ) {
			$key = hcwa_area_key( hcwa_original_term_slug( $term ) );
		}
		$keys[] = $key;
	}
	// In the order the areas were given (the taxonomy sorts by term_order).
	return array_values( array_unique( array_filter( $keys ) ) );
}

/**
 * The four focus areas in the current language, in display order: key, name and text as
 * they print. An area without a term in this language is left out.
 *
 * @return array<int, array{key: string, name: array, text: array}>
 */
function hcwa_blocks_areas(): array {
	$terms = get_terms(
		array(
			'taxonomy'   => 'hcwa_focus_area',
			'hide_empty' => false,
			'lang'       => function_exists( 'pll_get_term' ) ? hcwa_lang() : '',
		)
	);
	$by = array();
	foreach ( is_array( $terms ) ? $terms : array() as $term ) {
		$key = hcwa_area_key( (string) $term->slug );
		if ( '' === $key && function_exists( 'hcwa_original_term_slug' ) ) {
			$key = hcwa_area_key( hcwa_original_term_slug( $term ) );
		}
		if ( '' !== $key && ! isset( $by[ $key ] ) ) {
			$by[ $key ] = array(
				'key'  => $key,
				'name' => hcwa_draft_text( hcwa_attr_text( (string) $term->name ) ),
				'text' => hcwa_draft_text( hcwa_attr_text( (string) $term->description ) ),
			);
		}
	}
	$out = array();
	foreach ( hcwa_area_keys() as $key ) {
		if ( isset( $by[ $key ] ) ) {
			$out[] = $by[ $key ];
		}
	}
	return $out;
}

/**
 * A meta text as it prints.
 *
 * @param int    $post_id Post ID.
 * @param string $key     Meta key.
 * @return array{text: string, draft: bool}
 */
function hcwa_meta_text( int $post_id, string $key ): array {
	return hcwa_draft_text( hcwa_attr_text( (string) get_post_meta( $post_id, $key, true ) ) );
}

/**
 * A one-item-per-line meta as it prints: each line through hcwa_draft_text(), empty lines
 * (draft on production) dropped.
 *
 * @param int    $post_id Post ID.
 * @param string $key     Meta key.
 * @return array<int, array{text: string, draft: bool}>
 */
function hcwa_meta_text_lines( int $post_id, string $key ): array {
	$out = array();
	foreach ( hcwa_meta_lines( $post_id, $key ) as $line ) {
		$shown = hcwa_draft_text( hcwa_attr_text( $line ) );
		if ( '' !== $shown['text'] ) {
			$out[] = $shown;
		}
	}
	return $out;
}

/**
 * The "since" line of a center: the verified year (both environments), else off production
 * the token "{since} [năm]" marked draft; null on production without a verified year.
 *
 * @param int $center_id Center ID.
 * @return array{text: string, draft: bool}|null
 */
function hcwa_center_since( int $center_id ): ?array {
	$year     = (int) get_post_meta( $center_id, 'hcwa_since_year', true );
	$verified = in_array( get_post_meta( $center_id, 'hcwa_since_verified', true ), array( true, 1, '1', 'true' ), true );
	if ( $year > 0 && $verified ) {
		return array(
			'text'  => hcwa_t( 'since' ) . ' ' . $year,
			'draft' => false,
		);
	}
	if ( hcwa_is_production() ) {
		return null;
	}
	return array(
		'text'  => hcwa_t( 'since' ) . ' ' . ( $year > 0 ? (string) $year : hcwa_t( 'blk_yearToken' ) ),
		'draft' => true,
	);
}

/**
 * Everything the templates print about one center.
 *
 * @param WP_Post $center Center in the current language.
 * @return array
 */
function hcwa_center_view( WP_Post $center ): array {
	static $cache = array();
	if ( isset( $cache[ $center->ID ] ) ) {
		return $cache[ $center->ID ];
	}
	$unit      = hcwa_center_unit( $center->ID );
	$protected = hcwa_center_is_protected( $center->ID );
	$short     = hcwa_meta_text( $center->ID, 'hcwa_short_name' );
	$name      = hcwa_draft_text( hcwa_blocks_title( $center ) );

	$cache[ $center->ID ] = array(
		'id'        => $center->ID,
		'post'      => $center,
		'unit'      => $unit,
		'key'       => '' !== $unit ? $unit : (string) $center->post_name,
		'color'     => hcwa_unit_color( $unit ),
		'emblem'    => hcwa_unit_emblem( $unit ),
		'protected' => $protected,
		'name'      => $name,
		'short'     => '' !== $short['text'] ? $short : $name,
		'url'       => (string) get_permalink( $center ),
		'serves'    => hcwa_meta_text( $center->ID, 'hcwa_who_we_serve' ),
		'location'  => $protected ? array(
			'text'  => '',
			'draft' => false,
		) : hcwa_meta_text( $center->ID, 'hcwa_location' ),
		'areas'     => hcwa_post_areas( $center->ID ),
		'since'     => hcwa_center_since( $center->ID ),
	);
	return $cache[ $center->ID ];
}

/**
 * The years line of a program: start–end when both are set, else the years label (tokens
 * until HCWA confirms the years).
 *
 * @param int $program_id Program ID.
 * @return array{text: string, draft: bool}
 */
function hcwa_program_years( int $program_id ): array {
	$start = (int) get_post_meta( $program_id, 'hcwa_start_year', true );
	$end   = (int) get_post_meta( $program_id, 'hcwa_end_year', true );
	if ( $start > 0 && $end > 0 ) {
		return array(
			'text'  => $start . '–' . $end,
			'draft' => false,
		);
	}
	return hcwa_meta_text( $program_id, 'hcwa_years_label' );
}

/**
 * Everything the templates print about one program.
 *
 * @param WP_Post $program Program in the current language.
 * @return array
 */
function hcwa_program_view( WP_Post $program ): array {
	static $cache = array();
	if ( isset( $cache[ $program->ID ] ) ) {
		return $cache[ $program->ID ];
	}
	$partners = array();
	foreach ( hcwa_id_list( get_post_meta( $program->ID, 'hcwa_partner_ids', true ) ) as $id ) {
		$local = hcwa_blocks_in_lang( $id );
		if ( 'publish' === get_post_status( $local ) ) {
			$partners[] = hcwa_attr_text( (string) get_the_title( $local ) );
		}
	}
	$centers = array();
	foreach ( hcwa_program_center_ids( $program->ID ) as $id ) {
		$post = get_post( $id );
		if ( $post instanceof WP_Post ) {
			$centers[] = hcwa_center_view( $post );
		}
	}
	$cache[ $program->ID ] = array(
		'id'       => $program->ID,
		'post'     => $program,
		'name'     => hcwa_draft_text( hcwa_blocks_title( $program ) ),
		'full'     => hcwa_meta_text( $program->ID, 'hcwa_full_name' ),
		'url'      => (string) get_permalink( $program ),
		'status'   => hcwa_program_status( $program->ID ),
		'years'    => hcwa_program_years( $program->ID ),
		'summary'  => hcwa_meta_text( $program->ID, 'hcwa_summary' ),
		'partners' => array_values( array_filter( $partners, 'strlen' ) ),
		'centers'  => $centers,
		'areas'    => hcwa_post_areas( $program->ID ),
	);
	return $cache[ $program->ID ];
}

/* -------------------------------------------------------------------------
 * Shared components (11-WP-PORT.md 2.6)
 * ---------------------------------------------------------------------- */

/**
 * Door chip of a center in a program row (prototype doorChip(), 3.4). Every chip carries
 * data-unit (2.8). A protected center's chip before the door is approved on production:
 * the short name only, no door span and no --unit.
 *
 * @param array $c From hcwa_center_view().
 */
function hcwa_door_chip_html( array $c ): string {
	$name = esc_html( $c['short']['text'] );
	if ( $c['protected'] && ! hcwa_little_rose_door_allowed() ) {
		return '<li class="door-chip" data-unit="' . esc_attr( $c['key'] ) . '">' . $name . '</li>';
	}
	return '<li class="door-chip" data-unit="' . esc_attr( $c['key'] ) . '" style="' . esc_attr( hcwa_unit_style( $c ) ) . '"><span class="door-chip__door" aria-hidden="true"></span>' . $name . '</li>';
}

/**
 * Program row (prototype programRow()): rows, never cards; its areas as a strip of tiles;
 * the centers as door chips.
 *
 * @param array $p From hcwa_program_view().
 */
function hcwa_program_row_html( array $p ): string {
	$art   = hcwa_art();
	$strip = $p['areas'] ? $art->gach_strip( $p['areas'], 'gach-strip program-row__tiles' ) : '';
	$facts = '';
	if ( $p['partners'] || $p['centers'] ) {
		$facts = "\n    " . '<dl class="program-row__facts">' . "\n      "
			. ( $p['partners'] ? '<div><dt>' . esc_html( hcwa_t( 'fundedBy' ) ) . '</dt><dd>' . esc_html( implode( ', ', $p['partners'] ) ) . '</dd></div>' : '' ) . "\n      "
			. ( $p['centers'] ? '<div><dt>' . esc_html( hcwa_t( 'runsAt' ) ) . '</dt><dd><ul class="door-chips">' . implode( '', array_map( 'hcwa_door_chip_html', $p['centers'] ) ) . '</ul></dd></div>' : '' ) . "\n    "
			. '</dl>';
	}
	$years = '' !== $p['years']['text'] ? '<span class="program-row__years"' . hcwa_draft_attr( $p['years']['draft'] ) . '>' . esc_html( $p['years']['text'] ) . '</span>' : '';
	$sum   = '' !== $p['summary']['text'] ? '<p class="program-row__summary"' . hcwa_draft_attr( $p['summary']['draft'] ) . '>' . esc_html( $p['summary']['text'] ) . '</p>' : '';

	return '<li class="program-row" data-areas="' . esc_attr( implode( ' ', $p['areas'] ) ) . '" data-status="' . esc_attr( $p['status'] ) . '">' . "\n  "
		. '<div class="program-row__head">' . "\n    "
		. ( '' !== $strip ? '<span class="program-row__floor" aria-hidden="true">' . $art->gach_sprite_once() . $strip . '</span>' : '' ) . "\n    "
		. '<p class="program-row__meta">' . hcwa_status_pill_html( $p['status'] ) . $years . '</p>' . "\n    "
		. '<h3 class="program-row__name"><a href="' . esc_url( $p['url'] ) . '">' . esc_html( hcwa_vi_keep( $p['name']['text'] ) ) . '</a></h3>' . "\n  "
		. '</div>' . "\n  "
		. '<div class="program-row__body">' . "\n    "
		. $sum
		. $facts . "\n  "
		. '</div>' . "\n  "
		. hcwa_icon( 'arrow', 'icon program-row__arrow' ) . "\n"
		. '</li>';
}

/**
 * Dated post (prototype datedPost(), 2.8: local permalinks, no external label or icon).
 *
 * @param WP_Post $post Post in the current language.
 */
function hcwa_dated_post_html( WP_Post $post ): string {
	$date  = substr( (string) $post->post_date, 0, 10 );
	$parts = hcwa_date_parts( $date );
	$title = hcwa_draft_text( hcwa_blocks_title( $post ) );
	$cat   = hcwa_post_category_label( $post );
	return '<li class="dated">' . "\n  "
		. '<time class="dated__date" datetime="' . esc_attr( $date ) . '"><span class="dated__dm">' . esc_html( $parts[0] ) . '</span><span class="sr-only">' . esc_html( $parts[1] ) . '</span><span class="dated__y">' . esc_html( $parts[2] ) . '</span></time>' . "\n  "
		. '<div class="dated__body">' . "\n    "
		. ( '' !== $cat ? '<p class="dated__cat">' . esc_html( $cat ) . '</p>' : '' ) . "\n    "
		. '<h3 class="dated__title"' . hcwa_draft_attr( $title['draft'] ) . '><a href="' . esc_url( (string) get_permalink( $post ) ) . '">' . esc_html( $title['text'] ) . '</a></h3>' . "\n  "
		. '</div>' . "\n"
		. '</li>';
}

/**
 * The section label of a post: news, story, event or announcement (cat_* keys), else the
 * first category's name.
 *
 * @param WP_Post $post Post.
 */
function hcwa_post_category_label( WP_Post $post ): string {
	$map      = array(
		'news'          => 'cat_news',
		'stories'       => 'cat_story',
		'events'        => 'cat_event',
		'announcements' => 'blk_catAnnouncement',
	);
	$fallback = '';
	foreach ( (array) get_the_category( $post->ID ) as $term ) {
		if ( ! $term instanceof WP_Term || ( 'uncategorized' === $term->slug && (int) get_option( 'default_category' ) === (int) $term->term_id ) ) {
			continue;
		}
		$slugs = array( $term->slug );
		if ( function_exists( 'hcwa_original_term_slug' ) ) {
			$slugs[] = hcwa_original_term_slug( $term );
		}
		$key = hcwa_category_key( $slugs );
		if ( isset( $map[ $key ] ) ) {
			return hcwa_t( $map[ $key ] );
		}
		if ( '' === $fallback ) {
			$fallback = hcwa_attr_text( (string) $term->name );
		}
	}
	return $fallback;
}

/**
 * The dusk still above a dusk band (prototype duskStill()): '' until the still is rendered.
 */
function hcwa_dusk_still_html(): string {
	$still = hcwa_art()->still( 'dusk', array( 'cls' => 'dusk__img' ) );
	return '' !== $still ? '<div class="dusk__still" aria-hidden="true">' . $still . '</div>' : '';
}

/**
 * Dusk band (prototype duskBand(), 2.6).
 *
 * @param array $a anchor (section id, '' for none), title_id, title (plain), draft, body (HTML).
 */
function hcwa_dusk_band_html( array $a ): string {
	$anchor   = (string) ( $a['anchor'] ?? '' );
	$title_id = (string) ( $a['title_id'] ?? '' );
	$title    = (string) ( $a['title'] ?? '' );
	$label    = '' !== $title ? ' aria-labelledby="' . esc_attr( $title_id ) . '"' : '';
	return '<section class="dusk"' . ( '' !== $anchor ? ' id="' . esc_attr( $anchor ) . '"' : '' ) . $label . ">\n  "
		. hcwa_dusk_still_html() . "\n  "
		. '<div class="container dusk__inner">' . "\n    "
		. ( '' !== $title ? '<h2 class="dusk__title" id="' . esc_attr( $title_id ) . '"' . hcwa_draft_attr( ! empty( $a['draft'] ) ) . '>' . esc_html( hcwa_vi_keep( $title ) ) . '</h2>' : '' ) . "\n    "
		. (string) ( $a['body'] ?? '' ) . "\n  "
		. '</div>' . "\n"
		. '</section>';
}

/**
 * Contact lines as a dl (dusk__lines, office__lines): only lines rule 12 prints; the list
 * carries data-draft only when one of its lines is draft; '' when none is left.
 *
 * @param string[] $which phone | email | sponsorship.
 * @param string   $cls   Class of the dl.
 * @param string   $extra Extra rows before the contact lines (already escaped HTML).
 * @param bool     $extra_draft Whether the extra rows are draft.
 */
function hcwa_contact_lines_html( array $which, string $cls, string $extra = '', bool $extra_draft = false ): string {
	$rows  = $extra;
	$draft = $extra_draft;
	foreach ( $which as $key ) {
		$line = hcwa_contact_line( $key );
		if ( '' === $line['text'] ) {
			continue;
		}
		$draft = $draft || $line['draft'];
		$label = 'phone' === $key ? hcwa_t( 'phone' ) : hcwa_t( 'email' );
		$rows .= "\n      " . '<div><dt>' . esc_html( $label ) . '</dt><dd><a href="' . esc_url( $line['href'], array( 'tel', 'mailto' ) ) . '">' . esc_html( $line['text'] ) . '</a></dd></div>';
	}
	if ( '' === $rows ) {
		return '';
	}
	return '<dl class="' . esc_attr( $cls ) . '"' . hcwa_draft_attr( $draft ) . '>' . $rows . "\n    </dl>";
}

/**
 * Lit header (prototype litHeader(), 2.5).
 *
 * @param array $a still, trail, title (plain, or ['main' => …, 'sub' => …]), intro (plain),
 *                 intro_draft, meta (HTML), extra (HTML), unit_color ('' for none), size.
 */
function hcwa_lit_header_html( array $a ): string {
	$still = (string) ( $a['still'] ?? '' );
	$size  = (string) ( $a['size'] ?? '' );
	$unit  = (string) ( $a['unit_color'] ?? '' );
	$title = $a['title'] ?? '';
	$t     = is_array( $title )
		? '<span class="lit-head__main">' . esc_html( hcwa_vi_keep( (string) $title['main'] ) ) . '</span><span class="sr-only">: </span><span class="lit-head__sub">' . esc_html( (string) $title['sub'] ) . '</span>'
		: esc_html( hcwa_vi_keep( (string) $title ) );
	$lit   = '' !== $still ? hcwa_art()->lit_still( $still ) : '';
	$media = '' !== $lit ? $lit : '<div class="lit lit--still" aria-hidden="true"></div>';
	$meta  = (string) ( $a['meta'] ?? '' );
	$intro = (string) ( $a['intro'] ?? '' );

	return '<header class="lit-head' . ( '' !== $size ? ' lit-head--' . esc_attr( $size ) : '' ) . ( '' !== $unit ? ' lit-head--unit' : '' ) . '"' . ( '' !== $unit ? ' style="--unit:' . esc_attr( $unit ) . '"' : '' ) . ">\n  "
		. '<div class="lit-head__media" data-still="' . esc_attr( $still ) . '">' . $media . '</div>' . "\n  "
		. '<div class="container lit-head__text">' . "\n    "
		. hcwa_breadcrumb_html( (array) ( $a['trail'] ?? array() ) ) . "\n    "
		. ( '' !== $meta ? '<div class="lit-head__meta">' . $meta . '</div>' : '' ) . "\n    "
		. '<h1 class="lit-head__title">' . $t . '</h1>' . "\n    "
		. ( '' !== $intro ? '<p class="lit-head__intro"' . hcwa_draft_attr( ! empty( $a['intro_draft'] ) ) . '>' . esc_html( $intro ) . '</p>' : '' ) . "\n    "
		. (string) ( $a['extra'] ?? '' ) . "\n  "
		. '</div>' . "\n"
		. '</header>';
}

/**
 * Sun head (prototype donate header, 2.5 P5). Jump links are printed only when their target
 * is (2.1 rule 11): pass them only for printed panels.
 *
 * @param array $a title, trail (defaults to the title alone), intro, intro_draft, go
 *                 (['href', 'label'] or null), jumps ([href, label] pairs).
 */
function hcwa_sun_head_html( array $a ): string {
	$title = (string) ( $a['title'] ?? '' );
	$trail = (array) ( $a['trail'] ?? array( array( $title ) ) );
	$intro = (string) ( $a['intro'] ?? '' );
	$go    = $a['go'] ?? null;
	$jumps = (array) ( $a['jumps'] ?? array() );
	$side  = '';
	if ( '' !== $intro ) {
		$side .= "\n      " . '<p class="sun-head__intro"' . hcwa_draft_attr( ! empty( $a['intro_draft'] ) ) . '>' . esc_html( $intro ) . '</p>';
	}
	if ( is_array( $go ) && '' !== (string) ( $go['label'] ?? '' ) ) {
		$side .= "\n      " . hcwa_button_html( (string) $go['href'], (string) $go['label'], 'primary', '', 'sun-head__go' );
	}
	if ( $jumps ) {
		$side .= "\n      " . '<ul class="sun-head__jump">';
		foreach ( $jumps as $jump ) {
			$side .= "\n        <li>" . hcwa_text_link_html( (string) $jump[0], (string) $jump[1] ) . '</li>';
		}
		$side .= "\n      </ul>";
	}
	return '<header class="sun-head">' . "\n  "
		. '<span class="sun-layer sun-layer--donate" aria-hidden="true"></span>' . "\n  "
		. '<div class="container sun-head__inner">' . "\n    "
		. hcwa_breadcrumb_html( $trail ) . "\n    "
		. '<h1 class="sun-head__title">' . esc_html( hcwa_vi_keep( $title ) ) . '</h1>' . "\n    "
		. ( '' !== $side ? '<div class="sun-head__side">' . $side . "\n    </div>" : '' ) . "\n  "
		. '</div>' . "\n"
		. '</header>';
}

/**
 * Counts (prototype figuresFor(), 2.6): unverified figures only off production, with the
 * pending chip; '' when none is visible. Never animated.
 *
 * @param array[] $figures From hcwa_get_impact_figures().
 * @param string  $title   Heading (plain).
 * @param string  $id      Heading id.
 * @param bool    $nested  h3 inside a story section instead of a section of its own.
 */
function hcwa_counts_html( array $figures, string $title, string $id, bool $nested = false ): string {
	$items = '';
	foreach ( $figures as $f ) {
		$label    = hcwa_draft_text( hcwa_attr_text( (string) ( $f['label'] ?? '' ) ) );
		$verified = ! empty( $f['verified'] ) && null !== ( $f['value'] ?? null );
		if ( '' === $label['text'] || ( ! $verified && hcwa_is_production() ) ) {
			continue;
		}
		$value  = $verified ? hcwa_number( (float) $f['value'] ) . (string) ( $f['suffix'] ?? '' ) : hcwa_figure_token( $f );
		$items .= '<li class="count"' . hcwa_draft_attr( $label['draft'] || ! $verified ) . ">\n    "
			. '<p class="count__value num">' . esc_html( $value ) . '</p>' . "\n    "
			. '<p class="count__label">' . esc_html( $label['text'] ) . '</p>' . "\n    "
			. ( $verified ? '' : hcwa_pending_chip_html() ) . "\n  "
			. '</li>';
	}
	if ( '' === $items ) {
		return '';
	}
	$h = $nested ? 'h3' : 'h2';
	return '<section class="counts' . ( $nested ? ' counts--nested' : ' container' ) . '" aria-labelledby="' . esc_attr( $id ) . '">' . "\n  "
		. '<' . $h . ' class="counts__title" id="' . esc_attr( $id ) . '">' . esc_html( $title ) . '</' . $h . '>' . "\n  "
		. '<ul class="counts__list">' . $items . '</ul>' . "\n"
		. '</section>';
}

/**
 * The token an unverified figure shows instead of its number ([N]).
 *
 * @param array $f Figure.
 */
function hcwa_figure_token( array $f ): string {
	return isset( $f['token'] ) && '' !== (string) $f['token'] ? (string) $f['token'] : '[N]';
}

/**
 * One louver row (prototype louver()): the title is the link, except on the donate row,
 * which carries the page's one sun button.
 *
 * @param array $w     id, title, text (plain or {text, draft}).
 * @param int   $level Heading level (3 under the home H2, 2 on Get Involved).
 */
function hcwa_louver_html( array $w, int $level = 3 ): string {
	$routes = array(
		'donate'    => 'donate',
		'volunteer' => 'volunteer',
		'partner'   => 'partner',
		'in-kind'   => 'inKind',
	);
	$id     = (string) ( $w['id'] ?? '' );
	$donate = 'donate' === $id;
	$title  = hcwa_blocks_item_text( $w, 'title' );
	$text   = hcwa_blocks_item_text( $w, 'text' );
	$label  = '<span class="louver__label">' . esc_html( hcwa_vi_keep( $title['text'] ) ) . '</span>';
	$h      = 'h' . max( 2, min( 4, $level ) );
	$extra  = '';
	if ( $donate ) {
		$extra = hcwa_button_html( hcwa_page_url( 'donate' ), hcwa_t( 'donate' ), 'primary', '', 'louver__btn' );
	} elseif ( 'in-kind' === $id ) {
		$extra = '<a class="text-link louver__extra" href="' . esc_url( hcwa_page_url( 'bakery' ) ) . '"><span>' . esc_html( hcwa_t( 'bakery' ) ) . '</span>' . hcwa_icon( 'arrow' ) . '</a>';
	}
	return '<li class="louver' . ( $donate ? ' louver--donate' : '' ) . '" data-way="' . esc_attr( $id ) . '">' . "\n    "
		. '<span class="louver__face" aria-hidden="true"></span>' . "\n    "
		. '<' . $h . ' class="louver__title">' . ( $donate || ! isset( $routes[ $id ] ) ? $label : '<a href="' . esc_url( hcwa_page_url( $routes[ $id ] ) ) . '">' . $label . '</a>' ) . '</' . $h . '>' . "\n    "
		. '<div class="louver__body">' . "\n      "
		. ( '' !== $text['text'] ? '<p class="louver__text"' . hcwa_draft_attr( $text['draft'] ) . '>' . esc_html( $text['text'] ) . '</p>' : '' ) . "\n      "
		. $extra . "\n    "
		. '</div>' . "\n    "
		. ( $donate ? '' : hcwa_icon( 'arrow', 'icon louver__go' ) ) . "\n  "
		. '</li>';
}

/**
 * The louver rows (prototype louvers()).
 *
 * @param array[] $ways  Ways to help.
 * @param int     $level Heading level.
 * @param string  $cls   Extra class.
 */
function hcwa_louvers_html( array $ways, int $level = 3, string $cls = '' ): string {
	$items = '';
	foreach ( $ways as $w ) {
		if ( is_array( $w ) ) {
			$items .= hcwa_louver_html( $w, $level );
		}
	}
	return '<ul class="louvers' . ( '' !== $cls ? ' ' . esc_attr( $cls ) : '' ) . '" data-reveal="stagger">' . $items . '</ul>';
}

/* -------------------------------------------------------------------------
 * Form controls (prototype field(), choiceField(), consentField(), formSent(), copyButton())
 * ---------------------------------------------------------------------- */

/**
 * One form field: label above, helper, error below, tied with aria-describedby.
 *
 * @param array $f id, name (defaults to id), label, type, required, help, error, autocomplete,
 *                 options ['placeholder', 'items' => [[value, label]]], rows.
 */
function hcwa_field_html( array $f ): string {
	$id       = (string) $f['id'];
	$name     = (string) ( $f['name'] ?? $id );
	$help     = (string) ( $f['help'] ?? '' );
	$describe = trim( ( '' !== $help ? $id . '-help ' : '' ) . $id . '-error' );
	$common   = 'id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" aria-describedby="' . esc_attr( $describe ) . '"' . ( ! empty( $f['required'] ) ? ' required' : '' ) . ( ! empty( $f['autocomplete'] ) ? ' autocomplete="' . esc_attr( (string) $f['autocomplete'] ) . '"' : '' );
	if ( ! empty( $f['options'] ) ) {
		$options = '<option value="">' . esc_html( (string) $f['options']['placeholder'] ) . '</option>';
		foreach ( (array) $f['options']['items'] as $item ) {
			$options .= '<option value="' . esc_attr( (string) $item[0] ) . '">' . esc_html( (string) $item[1] ) . '</option>';
		}
		$control = '<span class="select"><select ' . $common . '>' . $options . '</select></span>';
	} elseif ( ! empty( $f['rows'] ) ) {
		$control = '<textarea ' . $common . ' rows="' . (int) $f['rows'] . '"></textarea>';
	} else {
		$control = '<input type="' . esc_attr( (string) ( $f['type'] ?? 'text' ) ) . '" ' . $common . '>';
	}
	return '<div class="field" data-field>' . "\n    "
		. '<label for="' . esc_attr( $id ) . '">' . esc_html( (string) $f['label'] ) . '</label>' . "\n    "
		. ( '' !== $help ? '<p class="field__help" id="' . esc_attr( $id ) . '-help">' . esc_html( $help ) . '</p>' : '' ) . "\n    "
		. $control . "\n    "
		. '<p class="field__error" id="' . esc_attr( $id ) . '-error" data-error="' . esc_attr( (string) ( $f['error'] ?? '' ) ) . '" hidden>' . hcwa_icon( 'alert' ) . '<span></span></p>' . "\n  "
		. '</div>';
}

/**
 * Radio choices as pills (contact topics). The first radio carries the group id.
 *
 * @param array $f id, name, label, items [[value, label]], required, error.
 */
function hcwa_choice_field_html( array $f ): string {
	$id    = (string) $f['id'];
	$name  = (string) ( $f['name'] ?? $id );
	$items = '';
	foreach ( array_values( (array) $f['items'] ) as $i => $item ) {
		$items .= '<label class="choice"><input type="radio" id="' . esc_attr( $i ? $id . '-' . $item[0] : $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $item[0] ) . '"' . ( ! empty( $f['required'] ) ? ' required' : '' ) . '><span>' . esc_html( (string) $item[1] ) . '</span></label>';
	}
	return '<fieldset class="field field--choices" data-field aria-describedby="' . esc_attr( $id ) . '-error">' . "\n    "
		. '<legend>' . esc_html( (string) $f['label'] ) . '</legend>' . "\n    "
		. '<div class="choices">' . $items . '</div>' . "\n    "
		. '<p class="field__error" id="' . esc_attr( $id ) . '-error" data-error="' . esc_attr( (string) ( $f['error'] ?? '' ) ) . '" hidden>' . hcwa_icon( 'alert' ) . '<span></span></p>' . "\n  "
		. '</fieldset>';
}

/**
 * The consent line every form ends on (its wording is HCWA's to approve: draft).
 *
 * @param string $id   Field id.
 * @param string $name Field name.
 */
function hcwa_consent_field_html( string $id = 'consent', string $name = '' ): string {
	$name = '' !== $name ? $name : $id;
	return '<div class="field field--check" data-field>' . "\n    "
		. '<input type="checkbox" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" required aria-describedby="' . esc_attr( $id ) . '-error">' . "\n    "
		. '<label for="' . esc_attr( $id ) . '"><span data-draft>' . esc_html( hcwa_t( 'f_consent' ) ) . '</span> <a href="' . esc_url( hcwa_page_url( 'privacy' ) ) . '">' . esc_html( hcwa_t( 'privacy' ) ) . '</a></label>' . "\n    "
		. '<p class="field__error" id="' . esc_attr( $id ) . '-error" data-error="' . esc_attr( hcwa_t( 'e_consent' ) ) . '" hidden>' . hcwa_icon( 'alert' ) . '<span></span></p>' . "\n  "
		. '</div>';
}

/**
 * A form's sent state (the prototype says plainly that nothing left the browser).
 *
 * @param string $message Plain text.
 * @param bool   $draft   Whether the message is draft.
 */
function hcwa_form_sent_html( string $message, bool $draft = false ): string {
	return '<div class="form__success" data-success tabindex="-1" role="status" hidden>' . "\n    "
		. hcwa_icon( 'check' ) . "\n    "
		. '<div class="form__success-text">' . "\n      "
		. '<p class="form__sent"' . hcwa_draft_attr( $draft ) . '>' . esc_html( $message ) . '</p>' . "\n      "
		. ( hcwa_is_production() ? '' : '<p class="form__note">' . esc_html( hcwa_t( 'f_prototypeSent' ) ) . '</p>' ) . "\n      "
		. '<button class="text-link form__again" type="button" data-form-again><span>' . esc_html( hcwa_t( 'f_sendAnother' ) ) . '</span>' . hcwa_icon( 'arrow' ) . '</button>' . "\n    "
		. '</div>' . "\n  "
		. '</div>';
}

/**
 * Copy button (prototype copyButton()): disabled until there is a value to copy.
 *
 * @param string $label       The row's label (plain).
 * @param string $value       Value to copy, '' while not confirmed.
 * @param string $described_by Id of the line saying why it is disabled.
 */
function hcwa_copy_button_html( string $label, string $value = '', string $described_by = '' ): string {
	return '<button class="copy-btn" type="button" data-copy="' . esc_attr( $value ) . '"' . ( '' === $value ? ' disabled' : '' ) . ' aria-label="' . esc_attr( hcwa_t( 'copy' ) . ': ' . $label ) . '"' . ( '' !== $described_by ? ' aria-describedby="' . esc_attr( $described_by ) . '"' : '' ) . '>'
		. hcwa_icon( 'copy', 'icon copy-btn__copy' ) . hcwa_icon( 'check', 'icon copy-btn__done' ) . '<span>' . esc_html( hcwa_t( 'copy' ) ) . '</span></button>';
}

/* -------------------------------------------------------------------------
 * Language notice (kept block)
 * ---------------------------------------------------------------------- */

/**
 * Language the notice speaks: the block's forceLang (set by single.php, never by the
 * editor), else ?hcwa_lang_notice=. '' when neither is set, or when it is the page's own
 * language (nothing to explain then).
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

/**
 * Published translation of a post in a language, or 0 (none, not published, or no
 * Polylang). A post with one needs no "only available in" notice.
 *
 * @param int    $post_id Post ID.
 * @param string $lang    'vi' or 'en'.
 */
function hcwa_blocks_published_translation( int $post_id, string $lang ): int {
	if ( $post_id <= 0 || ! function_exists( 'pll_get_post_translations' ) ) {
		return 0;
	}
	$translation = (int) ( ( (array) pll_get_post_translations( $post_id ) )[ $lang ] ?? 0 );
	if ( $translation <= 0 || $translation === $post_id ) {
		return 0;
	}
	return 'publish' === get_post_status( $translation ) ? $translation : 0;
}

/**
 * One language notice per request: true the first time (the caller prints the panel),
 * false after that. single.php prints the notice above the article, so a notice block
 * an editor placed inside the article does not add a second panel.
 *
 * @param bool $reset Forget an earlier notice (tests render many pages in one process).
 */
function hcwa_blocks_notice_claim( bool $reset = false ): bool {
	static $claimed = false;
	if ( $reset ) {
		$claimed = false;
		return false;
	}
	$first   = ! $claimed;
	$claimed = true;
	return $first;
}
