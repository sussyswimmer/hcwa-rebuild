<?php
/**
 * Local dev, step 2 of 2: fills an empty WordPress with the prototype's draft content
 * (content/draft/*.json) in both languages, linked as translations, so every template can be
 * checked. Nothing here is verified: figures stay unverified, years stay empty, donation and
 * contact details stay unconfirmed. Real content arrives in Phase 3 from content/verified/.
 *
 * Runs once on a fresh Playground site (wp/scripts/dev.mjs). Not for staging or production.
 */

if ( ! defined( 'PLL_ADMIN' ) ) {
	define( 'PLL_ADMIN', true );
}
require file_exists( '/wordpress/wp-load.php' ) ? '/wordpress/wp-load.php' : dirname( __DIR__, 2 ) . '/wp-load.php';

if ( get_option( 'hcwa_seeded' ) ) {
	echo "already seeded\n";
	return;
}

// WordPress's sample content has no language and would show up in the coverage check.
foreach ( array( 'hello-world' => 'post', 'sample-page' => 'page', 'privacy-policy' => 'page' ) as $slug => $type ) {
	$sample = get_page_by_path( $slug, OBJECT, $type );
	if ( $sample ) {
		wp_delete_post( $sample->ID, true );
	}
}

$content_dir = dirname( __DIR__ ) . '/content/draft/'; // the repo's content/ folder, mounted next to dev/
$load        = function ( $name ) use ( $content_dir ) {
	return json_decode( file_get_contents( $content_dir . $name . '.json' ), true );
};
$site    = $load( 'site' );
$work    = $load( 'work' );
$impact  = $load( 'impact' );
$news    = $load( 'news' );
$involve = $load( 'involve' );
$langs   = array( 'vi', 'en' );

/* ---------- helpers ---------- */

/** Creates a post per language from [ 'vi' => args, 'en' => args ], links them, returns ids. */
function seed_pair( $type, $pair, $meta = array(), $parents = null ) {
	$ids = array();
	foreach ( array( 'vi', 'en' ) as $lang ) {
		$args       = $pair[ $lang ];
		// wp_insert_post() unslashes its input; slash it first or "\u0026" in block attributes loses its backslash.
		$id         = wp_insert_post(
			wp_slash(
				array(
				'post_type'    => $type,
				'post_status'  => 'publish',
				'post_title'   => $args['title'],
				'post_name'    => isset( $args['slug'] ) ? $args['slug'] : '',
				'post_content' => isset( $args['content'] ) ? $args['content'] : '',
				'post_excerpt' => isset( $args['excerpt'] ) ? $args['excerpt'] : '',
				'post_date'    => isset( $args['date'] ) ? $args['date'] . ' 09:00:00' : current_time( 'mysql' ),
				'menu_order'   => isset( $args['order'] ) ? $args['order'] : 0,
				'post_parent'  => $parents ? $parents[ $lang ] : 0,
				)
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			throw new RuntimeException( $id->get_error_message() );
		}
		pll_set_post_language( $id, $lang );
		$ids[ $lang ] = $id;
	}
	pll_save_post_translations( $ids );
	foreach ( $ids as $lang => $id ) {
		foreach ( $meta as $key => $value ) {
			$v = is_array( $value ) && isset( $value['vi'], $value['en'] ) && ! isset( $value[0] ) ? $value[ $lang ] : $value;
			if ( is_array( $v ) ) {
				$v = implode( "\n", $v );
			}
			if ( '' !== $v && null !== $v && false !== $v ) {
				update_post_meta( $id, $key, $v );
			}
		}
		// Polylang's slug check runs before the language is set; restore the slug we asked for.
		if ( isset( $pair[ $lang ]['slug'] ) && get_post_field( 'post_name', $id ) !== $pair[ $lang ]['slug'] ) {
			wp_update_post( array( 'ID' => $id, 'post_name' => $pair[ $lang ]['slug'] ) );
		}
	}
	return $ids;
}

function seed_term_pair( $taxonomy, $pair, $meta = array() ) {
	$ids = array();
	foreach ( array( 'vi', 'en' ) as $lang ) {
		$term = wp_insert_term( $pair[ $lang ]['name'], $taxonomy, array( 'slug' => $pair[ $lang ]['slug'], 'description' => isset( $pair[ $lang ]['description'] ) ? $pair[ $lang ]['description'] : '' ) );
		if ( is_wp_error( $term ) ) {
			throw new RuntimeException( $taxonomy . ': ' . $term->get_error_message() );
		}
		pll_set_term_language( $term['term_id'], $lang );
		foreach ( $meta as $key => $value ) {
			update_term_meta( $term['term_id'], $key, $value );
		}
		$ids[ $lang ] = $term['term_id'];
	}
	pll_save_term_translations( $ids );
	return $ids;
}

function seed_tx( $field, $lang ) {
	return is_array( $field ) ? $field[ $lang ] : (string) $field;
}

/** Paragraph blocks for a list of draft strings. */
function seed_paras( $texts, $draft = true ) {
	$out = '';
	foreach ( (array) $texts as $text ) {
		$out .= hcwa_b_p( esc_html( $text ), '', '', $draft );
	}
	return $out;
}

$t = function ( $key, $lang ) {
	return hcwa_t( $key, array(), $lang );
};

/* ---------- focus areas and categories ---------- */

$areas = array();
foreach ( $work['focusAreas'] as $i => $area ) {
	$slugs           = array( 'education' => 'giao-duc', 'protection' => 'bao-ve-tre-em', 'family' => 'gia-dinh-cong-dong', 'policy' => 'van-dong-chinh-sach' );
	$areas[ $area['id'] ] = seed_term_pair(
		'hcwa_area',
		array(
			'vi' => array( 'name' => $area['name']['vi'], 'slug' => $slugs[ $area['id'] ], 'description' => $area['text']['vi'] ),
			'en' => array( 'name' => $area['name']['en'], 'slug' => $area['id'], 'description' => $area['text']['en'] ),
		),
		array( 'hcwa_order' => $i + 1, 'hcwa_draft' => ! empty( $area['text']['draft'] ) ? 1 : 0 )
	);
}

$cats = array();
foreach ( array(
	'news'     => array( 'Tin tức', 'tin-tuc-chung', 'News', 'news' ),
	'story'    => array( 'Câu chuyện', 'cau-chuyen', 'Stories', 'stories' ),
	'event'    => array( 'Sự kiện', 'su-kien', 'Events', 'events' ),
	'announce' => array( 'Thông báo', 'thong-bao', 'Announcements', 'announcements' ),
) as $key => $c ) {
	$cats[ $key ] = seed_term_pair( 'category', array( 'vi' => array( 'name' => $c[0], 'slug' => $c[1] ), 'en' => array( 'name' => $c[2], 'slug' => $c[3] ) ) );
}

/* ---------- partners (not translated) ---------- */

$partner_ids = array();
foreach ( $work['programs'] as $program ) {
	foreach ( $program['partners'] as $name ) {
		if ( ! isset( $partner_ids[ $name ] ) ) {
			$partner_ids[ $name ] = wp_insert_post( array( 'post_type' => 'hcwa_partner', 'post_status' => 'publish', 'post_title' => $name ) );
			update_post_meta( $partner_ids[ $name ], 'hcwa_type', 'corporate' );
			update_post_meta( $partner_ids[ $name ], 'hcwa_homepage', 1 );
			// hcwa_logo_permission stays unset: HCWA has not confirmed it (shows as a C5 slot).
		}
	}
}

/* ---------- centers and programs ---------- */

$centers = array();
foreach ( $work['centers'] as $i => $center ) {
	$slug_from = function ( $path ) {
		$parts = array_values( array_filter( explode( '/', $path ) ) );
		return end( $parts );
	};
	$pair = array();
	foreach ( $langs as $lang ) {
		$content = isset( $center['story'] ) ? seed_paras( $center['story'][ $lang ] ) : hcwa_b_p( '', '', 'Câu chuyện của cơ sở · The center\'s story' );
		$content .= '<!-- wp:details {"hcwaDraft":true} --><details class="wp-block-details"><summary>' . esc_html( 'vi' === $lang ? 'Đọc toàn bộ lịch sử' : 'Read the full history' ) . '</summary>' . hcwa_b_p( esc_html( 'vi' === $lang ? '[Lịch sử đầy đủ do HCWA cung cấp, kèm các năm đã được xác minh.]' : '[Full history supplied by HCWA, with verified years.]' ) ) . '</details><!-- /wp:details -->';
		$pair[ $lang ] = array(
			'title'   => $center['name'][ $lang ],
			'slug'    => $slug_from( $center['path'][ $lang ] ),
			'content' => $content,
			'excerpt' => $center['serves'][ $lang ],
			'order'   => $i + 1,
		);
	}
	$centers[ $center['id'] ] = seed_pair(
		'hcwa_center',
		$pair,
		array(
			'hcwa_draft'             => 1,
			'hcwa_key'               => $center['id'],
			'hcwa_short_name'        => $center['shortName'],
			'hcwa_accent'            => $center['accent'],
			'hcwa_serves'            => $center['serves'],
			'hcwa_activities'        => isset( $center['activities'] ) ? $center['activities'] : '',
			'hcwa_location'          => isset( $center['location'] ) ? $center['location'] : '',
			'hcwa_help'              => isset( $center['help'] ) ? $center['help'] : '',
			'hcwa_illustration_only' => ! empty( $center['illustrationOnly'] ) ? 1 : '',
		)
	);
	foreach ( $langs as $lang ) {
		$term_ids = array();
		foreach ( $center['areas'] as $area ) {
			$term_ids[] = $areas[ $area ][ $lang ];
		}
		wp_set_object_terms( $centers[ $center['id'] ][ $lang ], $term_ids, 'hcwa_area' );
	}
}

$programs = array();
foreach ( $work['programs'] as $i => $program ) {
	$pair = array();
	foreach ( $langs as $lang ) {
		$parts         = array_values( array_filter( explode( '/', $program['path'][ $lang ] ) ) );
		$pair[ $lang ] = array(
			'title'   => isset( $program['fullName'] ) ? $program['fullName'][ $lang ] : $program['name'][ $lang ],
			'slug'    => end( $parts ),
			'excerpt' => $program['summary'][ $lang ],
			'content' => isset( $program['focus'] ) ? seed_paras( array( $program['focus'][ $lang ] ) ) : '',
			'order'   => $i + 1,
		);
	}
	$programs[ $program['id'] ] = seed_pair(
		'hcwa_program',
		$pair,
		array(
			'hcwa_draft'      => 1,
			'hcwa_key'        => $program['id'],
			'hcwa_short_name' => $program['name'],
			'hcwa_status'     => $program['status'],
			'hcwa_centers'    => implode( ',', $program['centers'] ),
			'hcwa_partners'   => implode( ',', array_map( function ( $name ) use ( $partner_ids ) { return $partner_ids[ $name ]; }, $program['partners'] ) ),
			'hcwa_goals'      => isset( $program['goals'] ) ? $program['goals'] : '',
			'hcwa_activities' => isset( $program['activities'] ) ? $program['activities'] : '',
		)
	);
	foreach ( $langs as $lang ) {
		$term_ids = array();
		foreach ( $program['areas'] as $area ) {
			$term_ids[] = $areas[ $area ][ $lang ];
		}
		wp_set_object_terms( $programs[ $program['id'] ][ $lang ], $term_ids, 'hcwa_area' );
	}
}

// Green Bamboo's unit logo (the only unit logo file we have so far, from the prototype).
$logo_file = dirname( __DIR__ ) . '/img/unit-green-bamboo.png';
if ( file_exists( $logo_file ) ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$tmp = wp_tempnam( 'unit-green-bamboo.png' );
	copy( $logo_file, $tmp );
	$logo_id = media_handle_sideload( array( 'name' => 'unit-green-bamboo.png', 'tmp_name' => $tmp ), 0, 'Logo Tre Xanh' );
	if ( ! is_wp_error( $logo_id ) ) {
		update_post_meta( $logo_id, '_wp_attachment_image_alt', 'Logo Cơ sở Bảo trợ Xã hội Tre Xanh' );
		foreach ( $centers['green-bamboo'] as $center_id ) {
			update_post_meta( $center_id, 'hcwa_unit_logo', $logo_id );
		}
	}
}

/* ---------- impact figures (all unverified) ---------- */

foreach ( $impact['figures'] as $i => $figure ) {
	seed_pair(
		'hcwa_impact',
		array(
			'vi' => array( 'title' => $figure['label']['vi'], 'order' => $i + 1 ),
			'en' => array( 'title' => $figure['label']['en'], 'order' => $i + 1 ),
		),
		array(
			'hcwa_scope'  => $figure['scope'],
			'hcwa_suffix' => isset( $figure['suffix'] ) ? $figure['suffix'] : '',
			'hcwa_source' => isset( $figure['source'] ) ? $figure['source'] : '',
		)
	);
}

/* ---------- reports: two placeholder rows so the Documents section shows ---------- */

foreach ( array( 'vi', 'en' ) as $i => $doc_lang ) {
	seed_pair(
		'hcwa_report',
		array(
			'vi' => array( 'title' => '[Tên báo cáo dự án]', 'slug' => 'bao-cao-du-an-mau-' . ( $i + 1 ) ),
			'en' => array( 'title' => '[Program report title]', 'slug' => 'program-report-sample-' . ( $i + 1 ) ),
		),
		array( 'hcwa_draft' => 1, 'hcwa_type' => 'program', 'hcwa_doc_lang' => $doc_lang, 'hcwa_program' => 'thrive-together' )
	);
}

/* ---------- pages ---------- */

$page = function ( $vi, $en, $parent = null ) {
	$ids = seed_pair( 'page', array( 'vi' => $vi, 'en' => $en ), array(), $parent );
	foreach ( array( 'vi' => $vi, 'en' => $en ) as $lang => $args ) {
		if ( ! empty( $args['intro_draft'] ) ) {
			update_post_meta( $ids[ $lang ], 'hcwa_intro_draft', 1 );
		}
	}
	return $ids;
};
$p = array();
$p['home']  = $page( array( 'title' => 'Trang chủ', 'slug' => 'trang-chu' ), array( 'title' => 'Home', 'slug' => 'home' ) );
$p['about'] = $page(
	array( 'title' => $t( 'about', 'vi' ), 'slug' => 've-chung-toi', 'excerpt' => $site['description']['vi'], 'intro_draft' => true, 'content' => hcwa_pattern_text( array( 'title' => 'Sứ mệnh, tầm nhìn và giá trị', 'paragraphs' => array( '[Sứ mệnh, tầm nhìn và giá trị từ trang Giới thiệu hiện tại, HCWA cập nhật.]' ), 'draft' => true ) ) ),
	array( 'title' => $t( 'about', 'en' ), 'slug' => 'about', 'excerpt' => $site['description']['en'], 'intro_draft' => true, 'content' => hcwa_pattern_text( array( 'title' => 'Mission, vision and values', 'paragraphs' => array( '[Mission, vision and values from the current About page, updated by HCWA.]' ), 'draft' => true ) ) )
);
foreach ( array(
	'ourStory' => array( 'cau-chuyen', 'our-story', '[Mốc thời gian: năm thành lập, Hoa Hồng Nhỏ, Tre Xanh, Cầu Hàn, mô hình mới của Tre Xanh. Mọi năm đều cần HCWA xác minh.]', '[Timeline: founding year, Little Rose, Green Bamboo, Cau Han, Green Bamboo\'s new model. Every year needs HCWA verification.]' ),
	'team'     => array( 'doi-ngu', 'team', '[Ban lãnh đạo và nhân viên đồng ý được giới thiệu.]', '[Leadership and staff who agree to be listed.]' ),
	'partners' => array( 'doi-tac', 'partners', '', '' ),
	'policies' => array( 'chinh-sach', 'policies', '', '' ),
) as $role => $c ) {
	$vi_content = $c[2] ? hcwa_pattern_text( array( 'paragraphs' => array( $c[2] ), 'draft' => true ) ) : '';
	$en_content = $c[3] ? hcwa_pattern_text( array( 'paragraphs' => array( $c[3] ), 'draft' => true ) ) : '';
	if ( 'partners' === $role ) {
		$vi_content = $en_content = hcwa_b_group( 'section section--tight', hcwa_b_group( 'container', hcwa_b_dyn( 'partner-wall', array( 'homepageOnly' => false, 'count' => 48 ) ) ), array( 'tag' => 'section' ) );
	}
	if ( 'policies' === $role ) {
		$vi_content = hcwa_pattern_text( array( 'title' => $t( 'safeguarding', 'vi' ), 'anchor' => 'bao-ve-tre-em', 'paragraphs' => array( '[Tóm tắt chính sách bảo vệ trẻ em của HCWA, kèm tệp PDF.]' ), 'draft' => true ) ) . hcwa_pattern_text( array( 'title' => $t( 'privacy', 'vi' ), 'anchor' => 'quyen-rieng-tu', 'paragraphs' => array( '[Thông báo quyền riêng tư cho các biểu mẫu, HCWA kiểm tra theo Luật Bảo vệ dữ liệu cá nhân.]' ), 'draft' => true ) );
		$en_content = hcwa_pattern_text( array( 'title' => $t( 'safeguarding', 'en' ), 'anchor' => 'safeguarding', 'paragraphs' => array( '[Summary of HCWA\'s child safeguarding policy, with the PDF.]' ), 'draft' => true ) ) . hcwa_pattern_text( array( 'title' => $t( 'privacy', 'en' ), 'anchor' => 'privacy', 'paragraphs' => array( '[Privacy notice for the forms, checked by HCWA against the Personal Data Protection Law.]' ), 'draft' => true ) );
	}
	$p[ $role ] = $page(
		array( 'title' => $t( $role, 'vi' ), 'slug' => $c[0], 'content' => $vi_content ),
		array( 'title' => $t( $role, 'en' ), 'slug' => $c[1], 'content' => $en_content ),
		$p['about']
	);
}

$our_work = function ( $lang ) use ( $t ) {
	return hcwa_pattern_our_work(
		array(
			'centers'  => $t( 'centers', $lang ),
			'programs' => $t( 'programs', $lang ),
			'anchors'  => 'vi' === $lang ? array( 'co-so', 'du-an' ) : array( 'centers', 'programs' ),
			'note'     => 'vi' === $lang ? 'Cơ sở là nơi Hội vận hành lâu dài. Dự án và chương trình được tài trợ, có thời gian bắt đầu và kết thúc.' : 'Centers are long-term places HCWA runs. Projects and programs are funded work with a start and an end.',
			'draft'    => array( 'note' ),
		)
	);
};
$p['ourWork'] = $page(
	array( 'title' => $t( 'ourWork', 'vi' ), 'slug' => 'hoat-dong', 'excerpt' => 'Hội vận hành các cơ sở lâu dài và triển khai các dự án có thời hạn cùng đối tác.', 'intro_draft' => true, 'content' => $our_work( 'vi' ) ),
	array( 'title' => $t( 'ourWork', 'en' ), 'slug' => 'our-work', 'excerpt' => 'HCWA runs long-term centers and carries out time-bound projects with partners.', 'intro_draft' => true, 'content' => $our_work( 'en' ) )
);

$impact_page = function ( $lang ) use ( $t ) {
	$figures = hcwa_b_group( 'section section--tight', hcwa_b_group( 'container', hcwa_b_dyn( 'impact-strip', array( 'scope' => 'organization' ) ) ), array( 'tag' => 'section' ) );
	$reports = hcwa_b_group( 'section section--tight', hcwa_b_group( 'container', hcwa_b_h( 2, esc_html( $t( 'reportsTitle', $lang ) ), array( 'anchor' => 'vi' === $lang ? 'bao-cao' : 'reports' ) ) . hcwa_b_dyn( 'report-list', array( 'count' => 6 ) ) ), array( 'tag' => 'section' ) );
	$open    = hcwa_pattern_text( array( 'title' => 'vi' === $lang ? 'Minh bạch' : 'Transparency', 'paragraphs' => array( 'vi' === $lang ? '[Ai kiểm toán và phê duyệt báo cáo tài chính, nếu HCWA muốn nêu rõ.]' : '[Who audits and approves the accounts, if HCWA wants to state it.]' ), 'draft' => true ) );
	return $figures . $reports . $open;
};
$p['impact'] = $page(
	array( 'title' => $t( 'impact', 'vi' ), 'slug' => 'tac-dong', 'excerpt' => '[Một câu về cách Hội tính các con số, HCWA viết.]', 'intro_draft' => true, 'content' => $impact_page( 'vi' ) ),
	array( 'title' => $t( 'impact', 'en' ), 'slug' => 'impact', 'excerpt' => '[One sentence on how HCWA counts these numbers, written by HCWA.]', 'intro_draft' => true, 'content' => $impact_page( 'en' ) )
);
$p['news'] = $page( array( 'title' => $t( 'news', 'vi' ), 'slug' => 'tin-tuc' ), array( 'title' => $t( 'news', 'en' ), 'slug' => 'news' ) );

$p['getInvolved'] = $page(
	array( 'title' => $t( 'getInvolved', 'vi' ), 'slug' => 'chung-tay', 'excerpt' => 'Bạn có thể ủng hộ, tình nguyện, hợp tác hoặc tặng hiện vật.', 'intro_draft' => true ),
	array( 'title' => $t( 'getInvolved', 'en' ), 'slug' => 'get-involved', 'excerpt' => 'You can donate, volunteer, partner with us or give in kind.', 'intro_draft' => true )
);
$sub = array(
	'donate'    => array( 'ung-ho', 'donate', 'Dù lớn hay nhỏ, mỗi khoản ủng hộ đều có ý nghĩa.', 'Small or large, every gift matters.' ),
	'volunteer' => array( 'tinh-nguyen', 'volunteer', '', '' ),
	'partner'   => array( 'doi-tac-doanh-nghiep', 'partner', '', '' ),
	'inKind'    => array( 'hien-vat', 'in-kind', '', '' ),
);
$ways_by_page = array();
foreach ( $involve['ways'] as $way ) {
	$ways_by_page[ $way['page'] ] = $way;
}
foreach ( $sub as $role => $c ) {
	$contents = array();
	foreach ( $langs as $lang ) {
		if ( 'donate' === $role ) {
			$gifts = array();
			foreach ( $involve['giftExamples'] as $gift ) {
				$gifts[] = $gift['amount'][ $lang ] . ' ' . $gift['text'][ $lang ];
			}
			$contents[ $lang ] = hcwa_pattern_donate(
				array(
					'gifts_title'   => 'vi' === $lang ? 'Khoản ủng hộ của bạn giúp được gì' : 'What your gift does',
					'gifts'         => $gifts,
					'methods_title' => 'vi' === $lang ? 'Cách ủng hộ' : 'How to give',
					'vietnam'       => 'vi' === $lang ? 'Chuyển khoản trong nước' : 'Bank transfer in Vietnam',
					'abroad'        => 'vi' === $lang ? 'Ủng hộ từ nước ngoài' : 'Giving from abroad',
					'center'        => 'vi' === $lang ? 'Ủng hộ cho một cơ sở' : 'Give to a specific center',
					'center_text'   => 'vi' === $lang ? '[HCWA quyết định có hiển thị tài khoản riêng của từng cơ sở, ví dụ Mái ấm Hoa Hồng Nhỏ, hay không.]' : '[HCWA decides whether each center, for example Little Rose, shows its own account.]',
				)
			);
			continue;
		}
		$key     = array( 'volunteer' => 'volunteer', 'partner' => 'partner', 'inKind' => 'in-kind' )[ $role ];
		$way     = $ways_by_page[ $key ];
		$content = hcwa_pattern_text( array( 'paragraphs' => array( $way['text'][ $lang ] ), 'draft' => true ) );
		if ( 'inKind' === $role ) {
			$content .= hcwa_pattern_text( array( 'title' => $t( 'bakery', $lang ), 'anchor' => 'vi' === $lang ? 'tiem-banh' : 'bakery', 'paragraphs' => array( 'vi' === $lang ? '[Giới thiệu Tiệm bánh Hoa Hồng Nhỏ và cách đặt bánh, HCWA cung cấp.]' : '[About the Little Rose Bakery and how to order, supplied by HCWA.]' ), 'draft' => true ) );
		}
		$contents[ $lang ] = $content;
	}
	$title_key  = 'partner' === $role ? 'partnerWithUs' : $role;
	$p[ $role ] = $page(
		array( 'title' => $t( $title_key, 'vi' ), 'slug' => $c[0], 'excerpt' => $c[2], 'intro_draft' => (bool) $c[2], 'content' => $contents['vi'] ),
		array( 'title' => $t( $title_key, 'en' ), 'slug' => $c[1], 'excerpt' => $c[3], 'intro_draft' => (bool) $c[3], 'content' => $contents['en'] ),
		$p['getInvolved']
	);
}

$p['contact'] = $page(
	array( 'title' => $t( 'contact', 'vi' ), 'slug' => 'lien-he', 'excerpt' => 'Gửi lời nhắn cho Hội, hoặc gọi điện và email trực tiếp.', 'intro_draft' => true ),
	array( 'title' => $t( 'contact', 'en' ), 'slug' => 'contact', 'excerpt' => 'Send HCWA a message, or call or email us directly.', 'intro_draft' => true )
);

// Role pages for the theme (Settings → HCWA → Pages by role).
$roles = array();
foreach ( array_keys( hcwa_page_roles() ) as $role ) {
	if ( isset( $p[ $role ] ) ) {
		$roles[ $role ] = $p[ $role ]['vi'];
	}
}
update_option( 'hcwa_pages', $roles );
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $p['home']['vi'] );
update_option( 'page_for_posts', $p['news']['vi'] );

/* ---------- pages whose content links to other pages (now that URLs exist) ---------- */

$url = function ( $role, $lang ) use ( $p ) {
	return get_permalink( $p[ $role ][ $lang ] );
};

foreach ( $langs as $lang ) {
	$ways = array();
	foreach ( $involve['ways'] as $way ) {
		$target = array( 'donate' => 'donate', 'volunteer' => 'volunteer', 'partner' => 'partner', 'in-kind' => 'inKind' )[ $way['page'] ];
		$ways[] = array( $way['title'][ $lang ], $way['text'][ $lang ], $url( $target, $lang ), ! empty( $way['text']['draft'] ) );
	}
	$faq = array();
	foreach ( $involve['faq'] as $item ) {
		$faq[] = array( $item['q'][ $lang ], $involve['faqAnswer'][ $lang ], true );
	}
	wp_update_post(
		wp_slash( array(
			'ID'           => $p['getInvolved'][ $lang ],
			'post_content' => hcwa_pattern_ways( array( 'ways' => $ways, 'tone' => 'section section--tight' ), 2 ) . hcwa_pattern_faq(
				array(
					'title' => 'vi' === $lang ? 'Câu hỏi thường gặp' : 'Common questions',
					'intro' => 'vi' === $lang ? 'Không thấy câu trả lời bạn cần? Hãy liên hệ, Hội sẽ trả lời qua email.' : 'Can\'t find your answer? Get in touch and HCWA will reply by email.',
					'link'  => $t( 'contactUs', $lang ),
					'items' => $faq,
					'draft' => array( 'intro' ),
				)
			),
		) )
	);

	$contact_note = 'vi' === $lang ? 'Biểu mẫu liên hệ sẽ được thêm ở bước 2.7 (plugin biểu mẫu, có ô đồng ý và chống spam).' : 'The contact form is added in task 2.7 (form plugin with consent box and spam protection).';
	wp_update_post(
		wp_slash( array(
			'ID'           => $p['contact'][ $lang ],
			'post_content' => hcwa_b_group( 'section section--tight', hcwa_b_group( 'container contact-grid', hcwa_b_group( 'contact-form-slot', hcwa_b_group( 'note', hcwa_b_p( esc_html( $contact_note ), '', '', true ) ) ) . hcwa_b_group( 'offices', hcwa_b_dyn( 'contact-details', array( 'variant' => 'card' ) ) ) ), array( 'tag' => 'section' ) ),
		) )
	);

	$hero = $site['hero'];
	$home = hcwa_pattern_hero(
		array(
			'eyebrow'   => $hero['eyebrow'][ $lang ],
			'title'     => $hero['title'][ $lang ],
			'intro'     => $hero['intro'][ $lang ],
			'primary'   => $t( 'exploreWork', $lang ),
			'secondary' => $t( 'getInvolved', $lang ),
			'draft'     => array( 'eyebrow', 'title', 'intro' ),
		)
	)
	. hcwa_pattern_what_we_do( array( 'title' => $site['whatWeDo']['title'][ $lang ], 'summary' => $site['whatWeDo']['summary'][ $lang ], 'draft' => array( 'summary' ) ) )
	. hcwa_pattern_centers( array( 'title' => $t( 'centers', $lang ), 'intro' => $site['centersIntro'][ $lang ], 'link' => $t( 'seeAll', $lang ), 'draft' => array( 'intro' ) ) )
	. hcwa_pattern_programs( array( 'title' => $t( 'programs', $lang ), 'link' => $t( 'allPrograms', $lang ) ) )
	. hcwa_pattern_latest( array( 'title' => $t( 'latest', $lang ), 'link' => $t( 'allNews', $lang ) ) )
	. hcwa_pattern_ways( array( 'title' => $site['helpTitle'][ $lang ], 'ways' => $ways ) )
	. hcwa_pattern_partners( array( 'title' => $site['partnersTitle'][ $lang ], 'all' => $t( 'partners', $lang ), 'become' => $t( 'becomePartner', $lang ) ) )
	. hcwa_pattern_contact_band( array( 'title' => $site['contactBand']['title'][ $lang ], 'button' => $t( 'contactUs', $lang ) ) );
	wp_update_post( wp_slash( array( 'ID' => $p['home'][ $lang ], 'post_content' => $home ) ) );
}

/* ---------- news: the three real posts from the live site (titles and dates only) ---------- */

foreach ( array_reverse( $news['posts'] ) as $post ) {
	$pair = array();
	foreach ( $langs as $lang ) {
		$body          = hcwa_b_p( esc_html( 'vi' === $lang ? '[Nội dung đầy đủ sẽ được chuyển từ trang hiện tại ở giai đoạn 3.]' : '[Full text moves over from the live site in Phase 3.]' ), '', '', true )
			. hcwa_b_p( '<a href="' . esc_url( $post['url'][ $lang ] ) . '">' . esc_html( 'vi' === $lang ? 'Đọc bài trên trang hiện tại' : 'Read it on the current site' ) . '</a>' );
		$pair[ $lang ] = array( 'title' => $post['title'][ $lang ], 'date' => $post['published'], 'content' => $body );
	}
	$ids = seed_pair( 'post', $pair );
	foreach ( $langs as $lang ) {
		wp_set_post_categories( $ids[ $lang ], array( $cats[ 'event' === $post['category'] ? 'event' : 'news' ][ $lang ] ) );
	}
}
// One Vietnamese-only post, to check the language notice (07-BILINGUAL). Review mode only.
$vi_only = hcwa_review_mode() ? wp_insert_post(
	array(
		'post_type'    => 'post',
		'post_status'  => 'publish',
		'post_title'   => 'Bài viết thử: chỉ có tiếng Việt',
		'post_name'    => 'bai-viet-chi-co-tieng-viet',
		'post_content' => wp_slash( hcwa_b_p( esc_html( '[Bài mẫu cục bộ. Không chuyển lên trang thật.]' ), '', '', true ) ),
		'post_date'    => '2026-02-08 09:00:00',
	)
) : 0;
if ( $vi_only ) {
	pll_set_post_language( $vi_only, 'vi' );
	wp_set_post_categories( $vi_only, array( $cats['announce']['vi'] ) );
}

/* ---------- menus (04-SITEMAP), one per language ---------- */

$menu_ids = array();
foreach ( $langs as $lang ) {
	$menu_id = wp_create_nav_menu( 'vi' === $lang ? 'Menu chính' : 'Main menu' );
	$add     = function ( $args, $parent = 0 ) use ( $menu_id ) {
		return wp_update_nav_menu_item( $menu_id, 0, $args + array( 'menu-item-status' => 'publish', 'menu-item-parent-id' => $parent ) );
	};
	$page_item = function ( $role, $parent = 0, $title = '' ) use ( $add, $p, $lang ) {
		return $add( array( 'menu-item-type' => 'post_type', 'menu-item-object' => 'page', 'menu-item-object-id' => $p[ $role ][ $lang ], 'menu-item-title' => $title ), $parent );
	};
	$link_item = function ( $title, $link, $parent ) use ( $add ) {
		return $add( array( 'menu-item-type' => 'custom', 'menu-item-url' => $link, 'menu-item-title' => $title ), $parent );
	};

	$about = $page_item( 'about' );
	$page_item( 'about', $about, $t( 'whoWeAre', $lang ) );
	foreach ( array( 'ourStory', 'team', 'partners', 'policies' ) as $role ) {
		$page_item( $role, $about );
	}
	$work_item = $page_item( 'ourWork' );
	$link_item( $t( 'centers', $lang ), $url( 'ourWork', $lang ) . ( 'vi' === $lang ? '#co-so' : '#centers' ), $work_item );
	$link_item( $t( 'programs', $lang ), $url( 'ourWork', $lang ) . ( 'vi' === $lang ? '#du-an' : '#programs' ), $work_item );
	$page_item( 'ourWork', $work_item, $t( 'allWork', $lang ) );
	$page_item( 'impact' );
	$page_item( 'news' );
	$involved = $page_item( 'getInvolved' );
	foreach ( array( 'donate', 'volunteer', 'partner', 'inKind' ) as $role ) {
		$page_item( $role, $involved );
	}
	$link_item( $t( 'bakery', $lang ), $url( 'inKind', $lang ) . ( 'vi' === $lang ? '#tiem-banh' : '#bakery' ), $involved );
	$page_item( 'contact' );
	$menu_ids[ $lang ] = $menu_id;
}
$pll                                         = get_option( 'polylang' );
$pll['nav_menus'][ get_stylesheet() ]['primary'] = $menu_ids;
update_option( 'polylang', $pll );
set_theme_mod( 'nav_menu_locations', array( 'primary' => $menu_ids['vi'] ) );

/* ---------- settings (all unverified) ---------- */

$pair = function ( $field ) {
	return array( 'vi' => seed_tx( $field, 'vi' ), 'en' => seed_tx( $field, 'en' ) );
};
$settings = array(
	'description'   => $pair( $site['description'] ),
	'registration'  => $pair( $site['registration'] ),
	'address'       => $pair( $site['address'] ),
	'phone_display' => $site['phone']['display'],
	'phone_tel'     => $site['phone']['tel'],
	'email'         => $site['email']['general'],
	'email_partner' => $site['email']['sponsorship'],
	'map_url'       => $site['mapUrl'],
	'facebook_url'  => $site['facebook'],
	'announce_on'   => false,
);
// Donation details only ever come from content/verified/donation.json (CLAUDE.md hard rule).
$verified = dirname( __DIR__ ) . '/content/verified/donation.json';
if ( file_exists( $verified ) ) {
	$donation = json_decode( file_get_contents( $verified ), true );
	foreach ( array( 'bank_name', 'bank_account', 'bank_holder', 'abroad_bank', 'abroad_swift', 'abroad_account', 'abroad_holder', 'paypal_url', 'donation_confirmed_by', 'donation_confirmed_on' ) as $key ) {
		if ( isset( $donation[ $key ] ) ) {
			$settings[ $key ] = $donation[ $key ];
		}
	}
	if ( isset( $donation['transfer_note'] ) ) {
		$settings['transfer_note'] = $donation['transfer_note'];
	}
}
update_option( 'hcwa_settings', $settings );

update_option( 'hcwa_seeded', gmdate( 'c' ) );
flush_rewrite_rules( false );
echo "seeded: " . count( $p ) . " page pairs, " . count( $centers ) . " centers, " . count( $programs ) . " programs, " . count( $impact['figures'] ) . " figures\n";
