<?php
/**
 * Camera metadata removal (media-privacy.php): GPS never reaches the uploads folder.
 *
 * Fixtures are built here: a 4×3 JPEG and WebP made with GD (base64 below), metadata
 * blocks added by hand.
 *
 * @package hcwa-core
 */

$jpeg = base64_decode( '/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gODAK/9sAQwAGBAUGBQQGBgUGBwcGCAoQCgoJCQoUDg8MEBcUGBgXFBYWGh0lHxobIxwWFiAsICMmJykqKRkfLTAtKDAlKCko/9sAQwEHBwcKCAoTCgoTKBoWGigoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgo/8AAEQgAAwAEAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A4Ciiivnj9hP/2Q==' );
$progressive = base64_decode( '/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gODAK/9sAQwAGBAUGBQQGBgUGBwcGCAoQCgoJCQoUDg8MEBcUGBgXFBYWGh0lHxobIxwWFiAsICMmJykqKRkfLTAtKDAlKCko/9sAQwEHBwcKCAoTCgoTKBoWGigoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgo/8IAEQgAAwAEAwEiAAIRAQMRAf/EABUAAQEAAAAAAAAAAAAAAAAAAAAG/8QAFQEBAQAAAAAAAAAAAAAAAAAABQb/2gAMAwEAAhADEAAAAZ8HWH//xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAEFAn//xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oACAEDAQE/AX//xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oACAECAQE/AX//xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAY/An//xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAE/IX//2gAMAwEAAgADAAAAEP8A/8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAwEBPxB//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAgEBPxB//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxB//9k=' );
$webp = base64_decode( 'UklGRjoAAABXRUJQVlA4IC4AAACwAQCdASoEAAMAAUAmJaACdLoABDAAAP7x3I/4DdfFtMv/vYL/3YL/3YL/WwAA' );

// A little-endian EXIF block: Orientation, and optionally a GPS IFD (GPSLatitudeRef = N).
$tiff = static function ( int $orientation, bool $gps ): string {
	$count   = $gps ? 2 : 1;
	$entries = pack( 'vvVv', 0x0112, 3, 1, $orientation ) . "\0\0";
	if ( $gps ) {
		$entries .= pack( 'vvVV', 0x8825, 4, 1, 8 + 2 + 12 * $count + 4 );
	}
	$out = "II*\0" . pack( 'V', 8 ) . pack( 'v', $count ) . $entries . pack( 'V', 0 );
	return $gps ? $out . pack( 'v', 1 ) . pack( 'vvV', 1, 2, 2 ) . "N\0\0\0" . pack( 'V', 0 ) : $out;
};
$segment = static fn( int $marker, string $body ) => "\xFF" . chr( $marker ) . pack( 'n', strlen( $body ) + 2 ) . $body;
$xmp     = "http://ns.adobe.com/xap/1.0/\0<x:xmpmeta><rdf:Description exif:GPSLatitude=\"10,46.5N\"/></x:xmpmeta>";
$exif_of = static function ( string $bytes ): array {
	if ( ! function_exists( 'exif_read_data' ) ) {
		return array( 'skipped' => true );
	}
	$file = tempnam( sys_get_temp_dir(), 'hcwa' );
	file_put_contents( $file, $bytes );
	$data = @exif_read_data( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors -- a file without EXIF warns.
	unlink( $file );
	return is_array( $data ) ? $data : array();
};
$gps_keys = static fn( array $exif ): array => array_values( array_filter( array_keys( $exif ), static fn( $key ) => 0 === strpos( (string) $key, 'GPS' ) ) );
$decodes  = static fn( string $bytes ): bool => ! function_exists( 'imagecreatefromstring' ) || false !== @imagecreatefromstring( $bytes ); // phpcs:ignore WordPress.PHP.NoSilencedErrors

// JPEG: APP0 first, then a phone's EXIF (orientation 6, GPS), XMP with GPS, ICC, MPF, IPTC,
// and a second picture with its own EXIF after the end of the image.
$app0_end = 4 + unpack( 'n', substr( $jpeg, 4, 2 ) )[1];
$phone    = substr( $jpeg, 0, $app0_end )
	. $segment( 0xE1, "Exif\0\0" . $tiff( 6, true ) )
	. $segment( 0xE1, $xmp )
	. $segment( 0xE2, "ICC_PROFILE\0\x01\x01profile" )
	. $segment( 0xE2, "MPF\0II*\0" )
	. $segment( 0xED, "Photoshop 3.0\08BIM Phuong 7" )
	. substr( $jpeg, $app0_end )
	. "\xFF\xD8" . $segment( 0xE1, "Exif\0\0" . $tiff( 1, true ) ) . "\xFF\xD9";
hcwa_assert_true( isset( $exif_of( $phone )['skipped'] ) || array() !== $gps_keys( $exif_of( $phone ) ), 'fixture: the phone JPEG carries GPS' );

$clean = hcwa_image_strip_metadata( $phone );
hcwa_assert_true( $clean['ok'] && $clean['changed'] && $clean['gps'], 'JPEG: cleaned, and the location is reported' );
hcwa_assert_same( "\xFF\xD8\xFF\xE0", substr( $clean['data'], 0, 4 ), 'JPEG: JFIF APP0 stays first' );
foreach ( array( 'GPS', 'xmpmeta', 'MPF', 'Photoshop', 'CREATOR: gd-jpeg' ) as $gone ) {
	hcwa_assert_false( strpos( $clean['data'], $gone ), "JPEG: $gone removed" );
}
hcwa_assert_true( false !== strpos( $clean['data'], 'ICC_PROFILE' ), 'JPEG: colour profile kept' );
hcwa_assert_same( "\xFF\xD9", substr( $clean['data'], -2 ), 'JPEG: nothing after the end of the image (MPF pictures, trailers)' );
$exif = $exif_of( $clean['data'] );
if ( ! isset( $exif['skipped'] ) ) {
	hcwa_assert_same( 6, $exif['Orientation'] ?? null, 'JPEG: orientation kept as a one-tag EXIF block' );
	hcwa_assert_same( array(), $gps_keys( $exif ), 'JPEG: exif_read_data finds no GPS' );
}
hcwa_assert_true( $decodes( $clean['data'] ), 'JPEG: still decodes' );
hcwa_assert_false( hcwa_image_strip_metadata( $clean['data'] )['changed'], 'JPEG: cleaning twice changes nothing' );

$upright = hcwa_image_strip_metadata( substr( $jpeg, 0, 2 ) . $segment( 0xE1, "Exif\0\0" . $tiff( 1, true ) ) . substr( $jpeg, 2 ) );
hcwa_assert_false( strpos( $upright['data'], 'Exif' ), 'JPEG: orientation 1 leaves no EXIF at all' );
hcwa_assert_true( $decodes( $upright['data'] ), 'JPEG without APP0 first: still decodes' );

$prog = hcwa_image_strip_metadata( $progressive );
hcwa_assert_true( $prog['ok'] && ! $prog['gps'] && $decodes( $prog['data'] ), 'progressive JPEG (several scans) still decodes' );
hcwa_assert_same( strlen( $progressive ) - strlen( $segment( 0xFE, "CREATOR: gd-jpeg v1.0 (using IJG JPEG v80), quality = 80\n" ) ), strlen( $prog['data'] ), 'progressive JPEG: only the comment went' );

// Entropy-coded data keeps stuffed bytes and restart markers; a fake scan ends at EOI.
$scan = "\xFF\xD8" . $segment( 0xDA, "\x01\x01\x00\x00\x3F\x00" ) . "ab\xFF\x00cd\xFF\xD3ef" . "\xFF\xD9";
hcwa_assert_same( $scan, hcwa_image_strip_metadata( $scan . 'trailer' )['data'], 'scan data copied byte for byte, trailer dropped' );
hcwa_assert_same( array( 'data' => "\xFF\xD8\xFF garbage", 'changed' => false, 'gps' => false, 'ok' => true ), hcwa_image_strip_metadata( "\xFF\xD8\xFF garbage" ), 'a JPEG that does not parse and has no GPS is kept as it is' );

// PNG: eXIf (orientation 3, GPS), a text chunk naming a child, XMP with GPS.
$chunk = static fn( string $type, string $body ) => pack( 'N', strlen( $body ) ) . $type . $body . pack( 'N', crc32( $type . $body ) );
$png   = "\x89PNG\r\n\x1A\n" . $chunk( 'IHDR', pack( 'NNCCCCC', 1, 1, 8, 2, 0, 0, 0 ) ) . $chunk( 'sRGB', "\0" )
	. $chunk( 'eXIf', $tiff( 3, true ) ) . $chunk( 'tEXt', "Comment\0Lan, 9, Phuong 7" )
	. $chunk( 'iTXt', "XML:com.adobe.xmp\0\0\0\0\0<exif:GPSLongitude>106,40E</exif:GPSLongitude>" )
	. $chunk( 'IDAT', (string) gzcompress( "\0\xC8\x1E\x1E" ) ) . $chunk( 'IEND', '' ) . 'trailing';
$png_clean = hcwa_image_strip_metadata( $png );
hcwa_assert_true( $png_clean['ok'] && $png_clean['changed'] && $png_clean['gps'], 'PNG: cleaned, location reported' );
hcwa_assert_same(
	"\x89PNG\r\n\x1A\n" . $chunk( 'IHDR', pack( 'NNCCCCC', 1, 1, 8, 2, 0, 0, 0 ) ) . $chunk( 'eXIf', hcwa_exif_orientation_tiff( 3 ) ) . $chunk( 'sRGB', "\0" ) . $chunk( 'IDAT', (string) gzcompress( "\0\xC8\x1E\x1E" ) ) . $chunk( 'IEND', '' ),
	$png_clean['data'],
	'PNG: text and XMP gone, orientation-only eXIf after IHDR, valid CRCs, nothing after IEND'
);
hcwa_assert_true( $decodes( $png_clean['data'] ), 'PNG: still decodes' );
hcwa_assert_false( hcwa_image_strip_metadata( $png_clean['data'] )['changed'], 'PNG: cleaning twice changes nothing' );

// WebP (extended format): EXIF (orientation 6, GPS) and XMP chunks.
$riff  = static fn( string $chunks ) => 'RIFF' . pack( 'V', strlen( $chunks ) + 4 ) . 'WEBP' . $chunks;
$vp8x  = static fn( int $flags ) => 'VP8X' . pack( 'V', 10 ) . chr( $flags ) . "\0\0\0" . substr( pack( 'V', 3 ), 0, 3 ) . substr( pack( 'V', 2 ), 0, 3 );
$wchunk = static fn( string $type, string $body ) => $type . pack( 'V', strlen( $body ) ) . $body . ( strlen( $body ) % 2 ? "\0" : '' );
$image = substr( $webp, 12 );
$phone_webp = $riff( $vp8x( 0x0C ) . $image . $wchunk( 'EXIF', $tiff( 6, true ) ) . $wchunk( 'XMP ', '<x:xmpmeta exif:GPSLatitude="10N"/>' ) );
$webp_clean = hcwa_image_strip_metadata( $phone_webp );
hcwa_assert_true( $webp_clean['ok'] && $webp_clean['changed'] && $webp_clean['gps'], 'WebP: cleaned, location reported' );
hcwa_assert_same( $riff( $vp8x( 0x08 ) . $image . $wchunk( 'EXIF', hcwa_exif_orientation_tiff( 6 ) ) ), $webp_clean['data'], 'WebP: XMP gone, orientation-only EXIF, flags and RIFF size right' );
hcwa_assert_true( $decodes( $webp_clean['data'] ), 'WebP: still decodes' );
hcwa_assert_same( $riff( $vp8x( 0 ) . $image ), hcwa_image_strip_metadata( $riff( $vp8x( 0x0C ) . $image . $wchunk( 'EXIF', $tiff( 1, true ) ) ) )['data'], 'WebP: orientation 1 leaves no EXIF chunk' );
hcwa_assert_false( hcwa_image_strip_metadata( $webp )['changed'], 'a plain WebP (sizes WordPress or the browser made) is left byte for byte' );

// Other formats (HEIC from an iPhone): kept unless they carry a location.
$heic = "\0\0\0\x18ftypheic\0\0\0\0mif1heic\0\0\0\x06Exif\0\0";
hcwa_assert_same( array( false, true, false ), array( hcwa_image_strip_metadata( $heic . $tiff( 1, true ) )['ok'], hcwa_image_strip_metadata( $heic . $tiff( 1, true ) )['gps'], hcwa_image_strip_metadata( $heic . $tiff( 1, true ) )['changed'] ), 'HEIC with GPS: cannot be cleaned here' );
hcwa_assert_true( hcwa_image_strip_metadata( $heic . $tiff( 1, false ) )['ok'], 'HEIC without GPS: accepted as it is' );
hcwa_assert_false( hcwa_image_strip_metadata( 'GIF89a <x:xmpmeta exif:GPSLatitude="1"/>' )['ok'], 'GIF with XMP GPS: refused' );

// Files on disk: the upload filter, the scrub after sizes are made, the replaced original.
$dir = sys_get_temp_dir() . '/hcwa-privacy-' . getmypid();
mkdir( $dir . '/2026/11', 0777, true );
$GLOBALS['hcwa_test']['upload_basedir'] = $dir;

file_put_contents( $dir . '/2026/11/visit.jpg', $phone );
chmod( $dir . '/2026/11/visit.jpg', 0640 );
$upload = array(
	'file' => $dir . '/2026/11/visit.jpg',
	'url'  => 'https://hcwa.test/wp-content/uploads/2026/11/visit.jpg',
	'type' => 'image/jpeg',
);
hcwa_assert_same( $upload, hcwa_strip_upload_metadata( $upload, 'upload' ), 'upload: the same file is passed on' );
hcwa_assert_same( $clean['data'], file_get_contents( $dir . '/2026/11/visit.jpg' ), 'upload: cleaned on disk before WordPress reads it' );
hcwa_assert_same( 0640, fileperms( $dir . '/2026/11/visit.jpg' ) & 0777, 'upload: file permissions kept' );
hcwa_assert_false( file_exists( $dir . '/2026/11/visit.jpg.hcwa-tmp' ), 'upload: no temporary file left' );

file_put_contents( $dir . '/2026/11/IMG_0001.heic', $heic . $tiff( 1, true ) );
$refused = hcwa_strip_upload_metadata( array( 'file' => $dir . '/2026/11/IMG_0001.heic', 'url' => 'x', 'type' => 'image/heic' ), 'sideload' );
hcwa_assert_true( isset( $refused['error'] ) && false !== strpos( $refused['error'], 'location' ), 'upload: a HEIC with GPS is refused with a message' );
hcwa_assert_false( file_exists( $dir . '/2026/11/IMG_0001.heic' ), 'upload: and the file is deleted' );
$pdf = array( 'file' => $dir . '/2026/11/report.pdf', 'url' => 'x', 'type' => 'application/pdf' );
hcwa_assert_same( $pdf, hcwa_strip_upload_metadata( $pdf ), 'upload: other files untouched' );
hcwa_assert_same( array( 'error' => 'x' ), hcwa_strip_upload_metadata( array( 'error' => 'x' ) ), 'upload: an earlier error is kept' );
hcwa_assert_true( has_filter( 'wp_handle_upload' ), 'hooked to wp_handle_upload' );

// After WordPress makes the sizes (importer path: the upload filter never ran).
foreach ( array( 'kid.jpg', 'kid-scaled.jpg', 'kid-300x225.jpg', 'kid-e1.jpg' ) as $name ) {
	file_put_contents( $dir . '/2026/11/' . $name, $phone );
}
hcwa_test_post( array( 'ID' => 600, 'post_type' => 'attachment', 'post_mime_type' => 'image/jpeg' ), array( '_wp_attached_file' => '2026/11/kid-scaled.jpg', '_wp_attachment_backup_sizes' => array( 'full-orig' => array( 'file' => 'kid-e1.jpg' ) ) ) );
$meta = array(
	'file'           => '2026/11/kid-scaled.jpg',
	'filesize'       => strlen( $phone ),
	'original_image' => 'kid.jpg',
	'sizes'          => array(
		'medium' => array( 'file' => 'kid-300x225.jpg', 'filesize' => strlen( $phone ) ),
		'gone'   => array( 'file' => 'kid-1x1.jpg' ),
	),
);
hcwa_assert_same(
	array( 'file', 'original_image', 'size:medium', 'backup:full-orig' ),
	array_keys( hcwa_attachment_files( 600, $meta ) ),
	'attachment files: main, original, sizes and editor backups that exist'
);
$after = hcwa_scrub_attachment_files( $meta, 600, 'create' );
foreach ( array( 'kid.jpg', 'kid-scaled.jpg', 'kid-300x225.jpg', 'kid-e1.jpg' ) as $name ) {
	hcwa_assert_same( $clean['data'], file_get_contents( $dir . '/2026/11/' . $name ), "generated files cleaned: $name" );
}
hcwa_assert_same( strlen( $clean['data'] ), $after['filesize'], 'recorded main file size updated' );
hcwa_assert_same( strlen( $clean['data'] ), $after['sizes']['medium']['filesize'], 'recorded size file size updated' );

// Client-side flow: finalize replaces original_image (IMG.jpg) with the converted IMG.webp.
foreach ( array( 'IMG.jpg', 'IMG.webp', 'IMG-scaled.webp', 'IMG-300x200.webp' ) as $name ) {
	file_put_contents( $dir . '/2026/11/' . $name, 'x' );
}
hcwa_test_post(
	array( 'ID' => 601, 'post_type' => 'attachment', 'post_mime_type' => 'image/webp' ),
	array(
		'_wp_attached_file'       => '2026/11/IMG-scaled.webp',
		'_wp_attachment_metadata' => array( 'file' => '2026/11/IMG.webp', 'original_image' => 'IMG.jpg' ),
	)
);
$final = array(
	'file'           => '2026/11/IMG-scaled.webp',
	'original_image' => 'IMG.webp',
	'sizes'          => array( 'medium' => array( 'file' => 'IMG-300x200.webp' ) ),
);
hcwa_assert_same( $final, hcwa_delete_replaced_original( $final, 601 ), 'metadata passed on unchanged' );
hcwa_assert_false( file_exists( $dir . '/2026/11/IMG.jpg' ), 'the untouched upload nobody references any more is deleted' );
hcwa_assert_true( file_exists( $dir . '/2026/11/IMG.webp' ) && file_exists( $dir . '/2026/11/IMG-scaled.webp' ), 'the new original and main file stay' );

file_put_contents( $dir . '/2026/11/IMG.jpg', 'x' );
$GLOBALS['hcwa_test']['post_meta'][601]['_wp_attachment_metadata']['original_image'] = 'IMG.jpg';
hcwa_delete_replaced_original( array( 'original_image' => 'IMG.webp', 'sizes' => array( 'legacy' => array( 'file' => 'IMG.jpg' ) ) ), 601 );
hcwa_assert_true( file_exists( $dir . '/2026/11/IMG.jpg' ), 'still referenced by a size: kept' );
$GLOBALS['hcwa_test']['upload_urls']['https://hcwa.test/wp-content/uploads/2026/11/IMG.jpg'] = 777;
hcwa_delete_replaced_original( array( 'original_image' => 'IMG.webp' ), 601 );
hcwa_assert_true( file_exists( $dir . '/2026/11/IMG.jpg' ), 'another attachment\'s main file: kept' );
hcwa_delete_replaced_original( array( 'original_image' => 'IMG.jpg' ), 601 );
hcwa_assert_true( file_exists( $dir . '/2026/11/IMG.jpg' ), 'original_image unchanged: nothing deleted' );

// Clean up.
$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST );
foreach ( $files as $file ) {
	$file->isDir() ? rmdir( $file->getPathname() ) : unlink( $file->getPathname() );
}
rmdir( $dir );

// Neutral names for uploaded images (no child's name or school in any URL), and no text
// from the file on a new upload.
hcwa_assert_same( 'hcwa-20261105-ab12cd34.jpg', hcwa_neutral_upload_name( 'Nguyen Van A - truong X.JPG', 'ab12cd34', '20261105' ), 'image: neutral name, lowercase extension' );
hcwa_assert_same( 'hcwa-20261105-ab12cd34.heic', hcwa_neutral_upload_name( 'IMG_0001.HEIC', 'ab12cd34', '20261105' ), 'phone format renamed too' );
hcwa_assert_same( '', hcwa_neutral_upload_name( 'bao-cao-thuong-nien-2025.pdf', 'ab12cd34', '20261105' ), 'a report PDF keeps its name' );
hcwa_assert_same( 1, preg_match( '/^hcwa-\d{8}-[a-z0-9]{8}\.png$/', hcwa_neutral_upload_name( 'be-lan.png' ) ), 'random part: eight lowercase letters or digits' );

$renamed = hcwa_rename_upload( array( 'name' => 'be-lan-lop-3.jpg', 'type' => 'image/jpeg', 'tmp_name' => '/tmp/php1', 'error' => 0, 'size' => 10 ) );
hcwa_assert_same( 1, preg_match( '/^hcwa-\d{8}-[a-z0-9]{8}\.jpg$/', $renamed['name'] ), 'upload prefilter renames the image' );
$stored_base = pathinfo( $renamed['name'], PATHINFO_FILENAME );
hcwa_assert_same( 'be-lan-lop-3', hcwa_request_upload_original( '/srv/uploads/2026/11/' . $stored_base . '.jpg' ), 'the request remembers the original name' );
hcwa_assert_same( 'be-lan-lop-3', hcwa_request_upload_original( $stored_base . '-1.jpg' ), 'also after wp_unique_filename() adds -1' );
hcwa_assert_same( null, hcwa_request_upload_original( '/srv/uploads/2019/05/old-photo.jpg' ), 'media not uploaded in this request: unknown' );
hcwa_assert_same( array( 'name' => 'report.pdf', 'error' => 0 ), hcwa_rename_upload( array( 'name' => 'report.pdf', 'error' => 0 ) ), 'other files untouched' );
hcwa_assert_same( array( 'name' => 'x.jpg', 'error' => 1 ), hcwa_rename_upload( array( 'name' => 'x.jpg', 'error' => 1 ) ), 'failed upload untouched' );
hcwa_note_rest_route( null, null, new WP_REST_Request( 'POST', '/wp/v2/media/12/sideload' ) );
hcwa_assert_same( 'kid-300x200.jpg', hcwa_rename_upload( array( 'name' => 'kid-300x200.jpg', 'error' => 0 ) )['name'], 'a client-side size keeps its attachment\'s name' );
hcwa_clear_rest_route( null );
hcwa_assert_same( '', hcwa_current_rest_route(), 'route cleared after the callbacks' );
foreach ( array( 'wp_handle_upload_prefilter', 'wp_handle_sideload_prefilter' ) as $hook ) {
	hcwa_assert_true( has_filter( $hook, 'hcwa_rename_upload' ), "$hook renames images" );
}
hcwa_assert_true( has_filter( 'wp_client_side_media_processing_enabled', '__return_false' ), 'client-side media processing off (it names sizes after the local file)' );

$data = array( 'post_title' => 'be-lan-lop-3', 'post_name' => 'be-lan-lop-3', 'post_mime_type' => 'image/jpeg' );
$out  = hcwa_neutral_attachment_title( $data, array( 'file' => '/srv/uploads/2026/11/' . $stored_base . '.jpg' ), array(), false );
hcwa_assert_same( $stored_base, $out['post_title'], 'title from the original file name becomes the neutral name' );
hcwa_assert_same( $stored_base, $out['post_name'], 'and so does the attachment page slug' );
$out = hcwa_neutral_attachment_title( array( 'post_title' => 'Lớp học vẽ', 'post_name' => 'lop-hoc-ve' ), array( 'file' => '/srv/uploads/2026/11/' . $stored_base . '.jpg' ), array(), false );
hcwa_assert_same( 'Lớp học vẽ', $out['post_title'], 'a title the uploader typed is kept' );
hcwa_assert_same( $data, hcwa_neutral_attachment_title( $data, array( 'file' => '/srv/uploads/2019/05/be-lan-lop-3.jpg' ), array(), false ), 'older media untouched' );
hcwa_assert_same( $data, hcwa_neutral_attachment_title( $data, array( 'file' => '/srv/uploads/2026/11/' . $stored_base . '.jpg' ), array(), true ), 'updates untouched' );

$read = array( 'title' => 'Bé Lan, lớp 3', 'caption' => 'Trường X', 'alt' => 'Bé Lan', 'credit' => 'Phụ huynh', 'copyright' => 'x', 'keywords' => array( 'lan' ), 'orientation' => 6, 'created_timestamp' => 1700000000, 'camera' => 'Pixel' );
$out  = hcwa_upload_image_meta_text( $read, '/srv/uploads/2026/11/' . $stored_base . '.jpg' );
hcwa_assert_same( array( '', '', '', '', '' ), array( $out['title'], $out['caption'], $out['alt'], $out['credit'], $out['copyright'] ), 'new upload: no text from the file' );
hcwa_assert_same( array( array(), 6, 1700000000, 'Pixel' ), array( $out['keywords'], $out['orientation'], $out['created_timestamp'], $out['camera'] ), 'keywords dropped; orientation, date and camera kept' );
hcwa_assert_same( $read, hcwa_upload_image_meta_text( $read, '/srv/uploads/2019/05/old-photo.jpg' ), 'import or regenerate: the record of what the file says is kept' );

// Regenerating sizes after media-scrub keeps the record of what WordPress read from the file.
hcwa_test_post( array( 'ID' => 640, 'post_type' => 'attachment', 'post_mime_type' => 'image/jpeg' ), array( '_wp_attachment_metadata' => array( 'image_meta' => array( 'title' => '', 'caption' => 'Nguyen Thi Lan, lop 5', 'alt' => 'Lan', 'keywords' => array( 'lan' ) ) ) ) );
$regenerated = hcwa_keep_image_meta_text( array( 'file' => '2019/05/a.jpg', 'image_meta' => array( 'title' => '', 'caption' => '', 'alt' => '', 'keywords' => array(), 'orientation' => 1 ) ), 640 );
hcwa_assert_same( array( 'title' => '', 'caption' => 'Nguyen Thi Lan, lop 5', 'alt' => 'Lan', 'keywords' => array( 'lan' ), 'orientation' => 1 ), $regenerated['image_meta'], 'old caption, alt and keywords kept; nothing invented' );
hcwa_assert_same( array( 'file' => 'x.jpg' ), hcwa_keep_image_meta_text( array( 'file' => 'x.jpg' ), 640 ), 'metadata without image_meta: unchanged' );
hcwa_assert_true( has_filter( 'wp_update_attachment_metadata', 'hcwa_keep_image_meta_text' ), 'filter added' );
