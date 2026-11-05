<?php
/**
 * Interface strings the theme needs that are not in content/draft/ui.json.
 *
 * Same shape as ui.json. They join hcwa-core's dictionary through its 'hcwa_strings'
 * filter (added before init, as core asks), so they are registered with Polylang (group
 * "HCWA UI") and staff can edit them in Languages → Translations. Core's own keys win on a
 * clash. Vietnamese is draft microcopy for native review, like the rest of ui.json.
 * Never put statistics, partner names or quotes in here.
 *
 * @package hcwa-2026
 */

defined( 'ABSPATH' ) || exit;

/**
 * @return array<string, array{vi: string, en: string}>
 */
function hcwa_theme_strings(): array {
	static $strings = null;
	if ( null !== $strings ) {
		return $strings;
	}
	$strings = array(
		// Header, footer, staging.
		'logoLine1'        => array( 'vi' => 'Hội Bảo trợ Trẻ em', 'en' => 'HCMC Child Welfare' ),
		'logoLine2'        => array( 'vi' => 'TP. Hồ Chí Minh', 'en' => 'Association' ),
		'stagingNote'      => array( 'vi' => 'Trang thử nghiệm để HCWA góp ý. Khung nét đứt là nội dung nháp hoặc số liệu chờ HCWA duyệt.', 'en' => 'Staging site for HCWA review. A dashed outline marks draft text or figures waiting for HCWA approval.' ),
		'stagingNoteShort' => array( 'vi' => 'Trang thử nghiệm. Khung nét đứt: nội dung chờ HCWA duyệt.', 'en' => 'Staging site. Dashed outline: waiting for HCWA approval.' ),
		'newTab'           => array( 'vi' => '(mở trong thẻ mới)', 'en' => '(opens in a new tab)' ),

		// News archive, cards, pagination.
		'filterCategory'   => array( 'vi' => 'Lọc theo chuyên mục', 'en' => 'Filter by category' ),
		'cat_announcement' => array( 'vi' => 'Thông báo', 'en' => 'Announcement' ),
		'noPosts'          => array( 'vi' => 'Chưa có bài viết nào trong mục này.', 'en' => 'Nothing here yet.' ),
		'pagination'       => array( 'vi' => 'Phân trang', 'en' => 'Pagination' ),
		'prevPage'         => array( 'vi' => 'Trang trước', 'en' => 'Previous page' ),
		'nextPage'         => array( 'vi' => 'Trang sau', 'en' => 'Next page' ),
		'page'             => array( 'vi' => 'Trang', 'en' => 'Page' ),

		// Single post.
		'share'            => array( 'vi' => 'Chia sẻ', 'en' => 'Share' ),
		'copyLink'         => array( 'vi' => 'Sao chép liên kết', 'en' => 'Copy link' ),
		'shareApp'         => array( 'vi' => 'Zalo và ứng dụng khác', 'en' => 'Zalo and other apps' ),
		'relatedNews'      => array( 'vi' => 'Tin liên quan', 'en' => 'Related news' ),
		'helpTitle'        => array( 'vi' => 'Bạn có thể giúp như thế nào', 'en' => 'How you can help' ),

		// Center page.
		'atGlance'         => array( 'vi' => 'Thông tin nhanh', 'en' => 'At a glance' ),
		'whoWeServe'       => array( 'vi' => 'Đối tượng', 'en' => 'Who we serve' ),
		'whatWeDo'         => array( 'vi' => 'Hoạt động', 'en' => 'What we do' ),
		'location'         => array( 'vi' => 'Địa điểm', 'en' => 'Location' ),
		'centerStory'      => array( 'vi' => 'Câu chuyện của chúng tôi', 'en' => 'Our story' ),
		'fullHistory'      => array( 'vi' => 'Đọc toàn bộ lịch sử', 'en' => 'Read the full history' ),
		'centerFigures'    => array( 'vi' => 'Kết quả tại {name}', 'en' => '{name} in numbers' ),
		'photos'           => array( 'vi' => 'Hình ảnh hoạt động', 'en' => 'Photos' ),
		'openLarger'       => array( 'vi' => 'phóng to', 'en' => 'open larger' ),
		'prevPhoto'        => array( 'vi' => 'Ảnh trước', 'en' => 'Previous photo' ),
		'nextPhoto'        => array( 'vi' => 'Ảnh sau', 'en' => 'Next photo' ),
		'programsHere'     => array( 'vi' => 'Dự án tại cơ sở', 'en' => 'Programs run here' ),
		'helpCenter'       => array( 'vi' => 'Cách giúp {name}', 'en' => 'How to help {name}' ),
		'donateTo'         => array( 'vi' => 'Ủng hộ {name}', 'en' => 'Donate to {name}' ),
		'visitTitle'       => array( 'vi' => 'Muốn đến thăm {name}?', 'en' => 'Want to visit {name}?' ),
		'unitLogo'         => array( 'vi' => 'Logo {name}', 'en' => '{name} logo' ),

		// Program page.
		'summary'          => array( 'vi' => 'Tóm tắt', 'en' => 'Summary' ),
		'goals'            => array( 'vi' => 'Mục tiêu', 'en' => 'Goals' ),
		'activities'       => array( 'vi' => 'Hoạt động', 'en' => 'Activities' ),
		'results'          => array( 'vi' => 'Kết quả', 'en' => 'Results' ),
		'whereAndWho'      => array( 'vi' => 'Nơi thực hiện và nhà tài trợ', 'en' => 'Where it runs and who funds it' ),
		'runsAt'           => array( 'vi' => 'Cơ sở', 'en' => 'Center' ),
		'fundedBy'         => array( 'vi' => 'Nhà tài trợ', 'en' => 'Funded by' ),
		'supportWork'      => array( 'vi' => 'Đồng hành cùng những dự án như thế này', 'en' => 'Support work like this' ),
		'partnerProgram'   => array( 'vi' => 'Hợp tác trong một dự án', 'en' => 'Partner on a program' ),

		// Reports.
		'reportsTitle'     => array( 'vi' => 'Thư viện báo cáo', 'en' => 'Reports library' ),
		'filterType'       => array( 'vi' => 'Lọc theo loại báo cáo', 'en' => 'Filter by report type' ),
		'rt_annual'        => array( 'vi' => 'Báo cáo thường niên', 'en' => 'Annual report' ),
		'rt_financial'     => array( 'vi' => 'Báo cáo tài chính', 'en' => 'Financial statement' ),
		'rt_program'       => array( 'vi' => 'Báo cáo dự án', 'en' => 'Program report' ),
		'rt_policy'        => array( 'vi' => 'Chính sách', 'en' => 'Policy' ),
		'download'         => array( 'vi' => 'Tải về', 'en' => 'Download' ),
		'noReports'        => array( 'vi' => 'Chưa có báo cáo nào trong mục này.', 'en' => 'No reports here yet.' ),

		// Search and content types.
		'searchTitle'      => array( 'vi' => 'Tìm kiếm', 'en' => 'Search' ),
		'searchResults'    => array( 'vi' => 'Kết quả tìm kiếm cho “{q}”', 'en' => 'Search results for “{q}”' ),
		'searchEmpty'      => array( 'vi' => 'Không tìm thấy kết quả nào. Hãy thử từ khóa khác.', 'en' => 'Nothing found. Try different words.' ),
		'type_page'        => array( 'vi' => 'Trang', 'en' => 'Page' ),
		'type_center'      => array( 'vi' => 'Cơ sở', 'en' => 'Center' ),
		'type_program'     => array( 'vi' => 'Dự án', 'en' => 'Project' ),
		'type_report'      => array( 'vi' => 'Báo cáo', 'en' => 'Report' ),
	);
	return $strings;
}

add_filter( 'hcwa_strings', 'hcwa_theme_add_strings' );

/**
 * Adds the theme's keys to hcwa-core's dictionary without replacing any core key.
 *
 * @param array $strings key => ['vi' => string, 'en' => string].
 * @return array
 */
function hcwa_theme_add_strings( array $strings ): array {
	return $strings + hcwa_theme_strings();
}
