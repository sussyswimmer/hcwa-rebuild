<?php
/**
 * Admin notice when hcwa-core is not active.
 *
 * Shown in both languages: without hcwa-core its Vietnamese admin translations are not loaded.
 *
 * @package hcwa-2026
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_notices', 'hcwa_theme_core_missing_notice' );

function hcwa_theme_core_missing_notice(): void {
	if ( hcwa_theme_core_active() || ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	?>
	<div class="notice notice-error">
		<p lang="vi"><strong>HCWA 2026:</strong> <?php echo esc_html( 'Plugin hcwa-core chưa được kích hoạt. Trang web đang hiển thị ở chế độ rút gọn: không có ảnh trong Thư viện, số liệu, nút chuyển ngôn ngữ đầy đủ hay thông tin ủng hộ, và không còn bước chặn đăng bài có nội dung nháp. Hãy kích hoạt plugin trong mục Plugin.' ); ?></p>
		<p lang="en"><strong>HCWA 2026:</strong> <?php echo esc_html( 'The hcwa-core plugin is not active. The site is showing a reduced version: no photos from the Media Library, figures, full language switching or donation details, and nothing stops draft text from being published. Activate it under Plugins.' ); ?></p>
	</div>
	<?php
}
