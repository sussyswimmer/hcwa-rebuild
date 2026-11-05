<?php
/**
 * Camera metadata never reaches the website (CLAUDE.md child safeguarding: no child's home
 * location). A phone photo carries its GPS position in EXIF or XMP, and WordPress keeps
 * the uploaded file as it is (as `original_image` when it makes a converted or scaled main
 * file, wp-admin/includes/image.php:223), where the web server serves it past the approval
 * gate. So every image is cleaned on disk:
 *
 * - on upload (`wp_handle_upload`, wp-admin/includes/file.php:1073-1081): classic and REST
 *   uploads, client-side sideloads, Insert from URL and `wp media import`, before WordPress
 *   reads the metadata or makes any size, so every size and conversion starts clean;
 * - after WordPress makes the sizes (`wp_generate_attachment_metadata`,
 *   image.php:750 and the REST finalize step), which also covers files that skipped the
 *   upload filter (the WordPress Importer writes with wp_upload_bits()) and sizes an image
 *   editor wrote with the source's profiles (Imagick keeps exif, xmp and iptc:
 *   class-wp-image-editor-imagick.php:1300-1306);
 * - for media already in the library: `wp hcwa media-scrub` (cli.php).
 *
 * JPEG, PNG and WebP are rewritten losslessly (no re-encoding, same file name and URL):
 * EXIF, XMP, IPTC, comments and anything after the image (MPF pictures, vendor trailers)
 * go; the colour profile stays, and the orientation is kept as a one-tag EXIF block so
 * WordPress and browsers still turn the photo the right way. Other formats (HEIC, AVIF,
 * GIF, TIFF) are kept as they are unless they carry a location, which cannot be removed
 * here: such an upload is refused with a message.
 *
 * @package hcwa-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Removes camera metadata from the bytes of an image file.
 *
 * @param string $data File contents.
 * @return array{data: string, changed: bool, gps: bool, ok: bool} ok is false when the
 *         image carries a location that could not be removed.
 */
function hcwa_image_strip_metadata( string $data ): array {
	$result = null;
	if ( 0 === strncmp( $data, "\xFF\xD8\xFF", 3 ) ) {
		$result = hcwa_jpeg_strip_metadata( $data );
	} elseif ( 0 === strncmp( $data, "\x89PNG\r\n\x1A\n", 8 ) ) {
		$result = hcwa_png_strip_metadata( $data );
	} elseif ( strlen( $data ) >= 12 && 'RIFF' === substr( $data, 0, 4 ) && 'WEBP' === substr( $data, 8, 4 ) ) {
		$result = hcwa_webp_strip_metadata( $data );
	}

	if ( null === $result ) {
		// Another format, or a file that does not parse: kept as it is unless it carries a location.
		$gps = hcwa_image_bytes_have_gps( $data );
		return array(
			'data'    => $data,
			'changed' => false,
			'gps'     => $gps,
			'ok'      => ! $gps,
		);
	}

	return array(
		'data'    => $result['data'],
		'changed' => $result['data'] !== $data,
		'gps'     => $result['gps'],
		'ok'      => true,
	);
}

/**
 * JPEG: keeps APP0 (JFIF), the ICC profile (APP2 "ICC_PROFILE") and APP14 (Adobe colour
 * transform); drops every other APPn segment (EXIF and XMP in APP1, MPF in APP2, IPTC in
 * APP13), comments and anything after the end of the image.
 *
 * @param string $data JPEG bytes.
 * @return array{data: string, gps: bool}|null Null when the file does not parse.
 */
function hcwa_jpeg_strip_metadata( string $data ): ?array {
	$len         = strlen( $data );
	$pos         = 2;
	$segments    = array();
	$orientation = 1;
	$gps         = false;

	while ( $pos < $len ) {
		if ( "\xFF" !== $data[ $pos ] ) {
			return null;
		}
		while ( $pos < $len && "\xFF" === $data[ $pos ] ) {
			++$pos; // Fill bytes.
		}
		if ( $pos >= $len ) {
			return null;
		}
		$marker = ord( $data[ $pos ] );
		++$pos;

		if ( 0xD9 === $marker ) {
			$segments[] = "\xFF\xD9";
			break; // End of image: MPF pictures and trailers after it are dropped.
		}
		if ( 0x01 === $marker || ( $marker >= 0xD0 && $marker <= 0xD7 ) ) {
			$segments[] = "\xFF" . chr( $marker );
			continue;
		}
		if ( $pos + 2 > $len ) {
			return null;
		}
		$size = unpack( 'n', substr( $data, $pos, 2 ) )[1];
		if ( $size < 2 || $pos + $size > $len ) {
			return null;
		}
		$body    = substr( $data, $pos + 2, $size - 2 );
		$segment = "\xFF" . chr( $marker ) . substr( $data, $pos, $size );
		$pos    += $size;

		if ( 0xDA === $marker ) {
			// Start of scan: the entropy-coded data runs to the next marker that is not a
			// stuffed 0xFF00 or a restart marker.
			$end = $pos;
			while ( true ) {
				$end = strpos( $data, "\xFF", $end );
				if ( false === $end || $end + 1 >= $len ) {
					$end = $len;
					break;
				}
				$next = ord( $data[ $end + 1 ] );
				if ( 0x00 === $next || ( $next >= 0xD0 && $next <= 0xD7 ) ) {
					$end += 2;
					continue;
				}
				break;
			}
			$segments[] = $segment . substr( $data, $pos, $end - $pos );
			$pos        = $end;
			continue;
		}

		if ( ( $marker >= 0xE0 && $marker <= 0xEF ) || 0xFE === $marker ) {
			if ( 0xE1 === $marker && 0 === strncmp( $body, "Exif\0\0", 6 ) ) {
				$tags        = hcwa_tiff_ifd0_tags( substr( $body, 6 ) );
				$orientation = hcwa_valid_orientation( $tags[0x0112] ?? null, $orientation );
				$gps         = $gps || array_key_exists( 0x8825, $tags );
			} else {
				$gps = $gps || hcwa_text_has_gps( $body );
			}
			$keep = 0xE0 === $marker || 0xEE === $marker || ( 0xE2 === $marker && 0 === strncmp( $body, "ICC_PROFILE\0", 12 ) );
			if ( $keep ) {
				$segments[] = $segment;
			}
			continue;
		}

		$segments[] = $segment;
	}

	$head = ( $segments && 0 === strncmp( $segments[0], "\xFF\xE0", 2 ) ) ? array_shift( $segments ) : '';
	$exif = '';
	if ( $orientation > 1 ) {
		$tiff = "Exif\0\0" . hcwa_exif_orientation_tiff( $orientation );
		$exif = "\xFF\xE1" . pack( 'n', strlen( $tiff ) + 2 ) . $tiff;
	}

	return array(
		'data' => "\xFF\xD8" . $head . $exif . implode( '', $segments ),
		'gps'  => $gps,
	);
}

/**
 * PNG: drops the eXIf, tEXt, zTXt, iTXt (XMP) and tIME chunks and anything after IEND.
 *
 * @param string $data PNG bytes.
 * @return array{data: string, gps: bool}|null Null when the file does not parse.
 */
function hcwa_png_strip_metadata( string $data ): ?array {
	$len         = strlen( $data );
	$pos         = 8;
	$chunks      = array();
	$orientation = 1;
	$gps         = false;

	while ( $pos + 12 <= $len ) {
		$size = unpack( 'N', substr( $data, $pos, 4 ) )[1];
		$type = substr( $data, $pos + 4, 4 );
		if ( $pos + 12 + $size > $len ) {
			return null;
		}
		$body  = substr( $data, $pos + 8, $size );
		$chunk = substr( $data, $pos, 12 + $size );
		$pos  += 12 + $size;

		if ( 'eXIf' === $type ) {
			$tags        = hcwa_tiff_ifd0_tags( $body );
			$orientation = hcwa_valid_orientation( $tags[0x0112] ?? null, $orientation );
			$gps         = $gps || array_key_exists( 0x8825, $tags );
			continue;
		}
		if ( in_array( $type, array( 'tEXt', 'zTXt', 'iTXt', 'tIME' ), true ) ) {
			$gps = $gps || hcwa_text_has_gps( $body );
			continue;
		}
		$chunks[] = array( $type, $chunk );
		if ( 'IEND' === $type ) {
			break;
		}
	}

	if ( ! $chunks || 'IHDR' !== $chunks[0][0] ) {
		return null;
	}
	if ( $orientation > 1 ) {
		$tiff = hcwa_exif_orientation_tiff( $orientation );
		array_splice( $chunks, 1, 0, array( array( 'eXIf', pack( 'N', strlen( $tiff ) ) . 'eXIf' . $tiff . pack( 'N', crc32( 'eXIf' . $tiff ) ) ) ) );
	}

	return array(
		'data' => substr( $data, 0, 8 ) . implode( '', array_column( $chunks, 1 ) ),
		'gps'  => $gps,
	);
}

/**
 * WebP: drops the EXIF and XMP chunks and clears their flags in the VP8X header.
 *
 * @param string $data WebP bytes.
 * @return array{data: string, gps: bool}|null Null when the file does not parse.
 */
function hcwa_webp_strip_metadata( string $data ): ?array {
	$len         = strlen( $data );
	$pos         = 12;
	$chunks      = array();
	$orientation = 1;
	$gps         = false;

	while ( $pos + 8 <= $len ) {
		$type = substr( $data, $pos, 4 );
		$size = unpack( 'V', substr( $data, $pos + 4, 4 ) )[1];
		if ( $pos + 8 + $size > $len ) {
			return null;
		}
		$body  = substr( $data, $pos + 8, $size );
		$chunk = substr( $data, $pos, 8 + $size ) . ( $size % 2 ? "\0" : '' );
		$pos  += 8 + $size + $size % 2;

		if ( 'EXIF' === $type ) {
			$tags        = hcwa_tiff_ifd0_tags( 0 === strncmp( $body, "Exif\0\0", 6 ) ? substr( $body, 6 ) : $body );
			$orientation = hcwa_valid_orientation( $tags[0x0112] ?? null, $orientation );
			$gps         = $gps || array_key_exists( 0x8825, $tags );
			continue;
		}
		if ( 'XMP ' === $type ) {
			$gps = $gps || hcwa_text_has_gps( $body );
			continue;
		}
		$chunks[] = array( $type, $chunk );
	}

	if ( ! $chunks ) {
		return null;
	}
	if ( 'VP8X' === $chunks[0][0] && strlen( $chunks[0][1] ) > 8 ) {
		$flags = ord( $chunks[0][1][8] ) & ~0x0C; // EXIF (0x08) and XMP (0x04) flags.
		if ( $orientation > 1 ) {
			$tiff     = hcwa_exif_orientation_tiff( $orientation );
			$chunks[] = array( 'EXIF', 'EXIF' . pack( 'V', strlen( $tiff ) ) . $tiff );
			$flags   |= 0x08;
		}
		$chunks[0][1][8] = chr( $flags );
	}

	$body = 'WEBP' . implode( '', array_column( $chunks, 1 ) );
	return array(
		'data' => 'RIFF' . pack( 'V', strlen( $body ) ) . $body,
		'gps'  => $gps,
	);
}

/**
 * Tags of the first IFD of a TIFF/EXIF block: tag => value for single SHORT or LONG
 * values, null for the others (present, value not read).
 *
 * @param string $tiff TIFF bytes (starting with II*\0 or MM\0*).
 * @return array<int, int|null>
 */
function hcwa_tiff_ifd0_tags( string $tiff ): array {
	$len   = strlen( $tiff );
	$order = substr( $tiff, 0, 2 );
	if ( $len < 8 || ! in_array( $order, array( 'II', 'MM' ), true ) ) {
		return array();
	}
	$short = 'II' === $order ? 'v' : 'n';
	$long  = 'II' === $order ? 'V' : 'N';
	if ( 42 !== unpack( $short, substr( $tiff, 2, 2 ) )[1] ) {
		return array();
	}
	$ifd = unpack( $long, substr( $tiff, 4, 4 ) )[1];
	if ( $ifd < 8 || $ifd + 2 > $len ) {
		return array();
	}

	$tags  = array();
	$count = unpack( $short, substr( $tiff, $ifd, 2 ) )[1];
	for ( $i = 0; $i < $count; $i++ ) {
		$entry = $ifd + 2 + 12 * $i;
		if ( $entry + 12 > $len ) {
			break;
		}
		$tag   = unpack( $short, substr( $tiff, $entry, 2 ) )[1];
		$type  = unpack( $short, substr( $tiff, $entry + 2, 2 ) )[1];
		$items = unpack( $long, substr( $tiff, $entry + 4, 4 ) )[1];
		$value = null;
		if ( 1 === $items && 3 === $type ) {
			$value = unpack( $short, substr( $tiff, $entry + 8, 2 ) )[1];
		} elseif ( 1 === $items && 4 === $type ) {
			$value = unpack( $long, substr( $tiff, $entry + 8, 4 ) )[1];
		}
		$tags[ $tag ] = $value;
	}
	return $tags;
}

/**
 * A one-tag big-endian TIFF block: Orientation (0x0112, SHORT).
 *
 * @param int $orientation 2 to 8.
 */
function hcwa_exif_orientation_tiff( int $orientation ): string {
	return "MM\0\x2A" . pack( 'N', 8 ) . pack( 'n', 1 ) . pack( 'nnN', 0x0112, 3, 1 ) . pack( 'n', $orientation ) . "\0\0" . pack( 'N', 0 );
}

/**
 * An EXIF orientation value, or the fallback when it is not 1 to 8.
 *
 * @param mixed $value    Value read from the tag.
 * @param int   $fallback Value so far.
 */
function hcwa_valid_orientation( $value, int $fallback ): int {
	return ( is_int( $value ) && $value >= 1 && $value <= 8 ) ? $value : $fallback;
}

/**
 * Whether text (XMP, PNG text, any metadata block) names a GPS position.
 *
 * @param string $text Bytes.
 */
function hcwa_text_has_gps( string $text ): bool {
	return (bool) preg_match( '/GPS(?:Latitude|Longitude|Coordinates|Position)/i', $text );
}

/**
 * Whether a file in another format (HEIC, AVIF, TIFF, GIF) carries a GPS position: an
 * EXIF block with a GPS pointer, a TIFF file with one, or XMP naming a position.
 *
 * @param string $data File contents.
 */
function hcwa_image_bytes_have_gps( string $data ): bool {
	$offset = 0;
	while ( false !== ( $at = strpos( $data, "Exif\0\0", $offset ) ) ) {
		if ( array_key_exists( 0x8825, hcwa_tiff_ifd0_tags( substr( $data, $at + 6, 65536 ) ) ) ) {
			return true;
		}
		$offset = $at + 6;
	}
	if ( array_key_exists( 0x8825, hcwa_tiff_ifd0_tags( substr( $data, 0, 65536 ) ) ) ) {
		return true;
	}
	return hcwa_text_has_gps( $data );
}

/**
 * Cleans one image file in place (written only when something changes).
 *
 * @param string $path  File path.
 * @param bool   $write False to only report (`wp hcwa media-scrub --dry-run`).
 * @return array{changed: bool, gps: bool, ok: bool}
 */
function hcwa_media_strip_file( string $path, bool $write = true ): array {
	$data = is_file( $path ) && is_readable( $path ) ? file_get_contents( $path ) : false; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	if ( false === $data ) {
		return array(
			'changed' => false,
			'gps'     => false,
			'ok'      => false,
		);
	}

	$result = hcwa_image_strip_metadata( $data );
	$ok     = $result['ok'];
	if ( $write && $ok && $result['changed'] ) {
		$tmp = $path . '.hcwa-tmp';
		$ok  = false !== file_put_contents( $tmp, $result['data'], LOCK_EX ) // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			&& chmod( $tmp, fileperms( $path ) & 0777 ) // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod
			&& rename( $tmp, $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename
		if ( ! $ok && file_exists( $tmp ) ) {
			wp_delete_file( $tmp );
		}
		clearstatcache( true, $path );
	}

	return array(
		'changed' => $result['changed'],
		'gps'     => $result['gps'],
		'ok'      => $ok,
	);
}

/**
 * wp_handle_upload: cleans every uploaded image before WordPress reads it. A file whose
 * location cannot be removed is deleted and the upload refused (media_handle_upload() and
 * the REST controller turn 'error' into a WP_Error and leave the file on disk otherwise).
 *
 * @param array  $upload  ['file', 'url', 'type'].
 * @param string $context 'upload' or 'sideload'.
 * @return array
 */
function hcwa_strip_upload_metadata( $upload, $context = 'upload' ) {
	if ( ! is_array( $upload ) || isset( $upload['error'] ) || empty( $upload['file'] ) || 0 !== strpos( (string) ( $upload['type'] ?? '' ), 'image/' ) ) {
		return $upload;
	}
	if ( hcwa_media_strip_file( (string) $upload['file'] )['ok'] ) {
		return $upload;
	}
	wp_delete_file( (string) $upload['file'] );
	return array(
		'error' => __( 'This photo carries location data that could not be removed on this server, so it was not uploaded. Save it as JPEG, or turn off the camera\'s location setting, and upload it again.', 'hcwa' ),
	);
}
add_filter( 'wp_handle_upload', 'hcwa_strip_upload_metadata', 5, 2 );

/**
 * Every image file an attachment owns: main file, original_image, source_image (the
 * client-side source-format original), the poster of an animated GIF, every size and every
 * backup left by the image editor. Only files that exist.
 *
 * @param int        $attachment_id Attachment ID.
 * @param array|null $metadata      Metadata to read instead of the stored one.
 * @return array<string, string> Label ('file', 'original_image', 'size:large', 'backup:full-orig', …) => path.
 */
function hcwa_attachment_files( int $attachment_id, ?array $metadata = null ): array {
	$main = (string) get_attached_file( $attachment_id, true );
	if ( '' === $main ) {
		return array();
	}
	$meta  = $metadata ?? wp_get_attachment_metadata( $attachment_id, true );
	$meta  = is_array( $meta ) ? $meta : array();
	$dir   = dirname( $main );
	$files = array( 'file' => $main );

	foreach ( array( 'original_image', 'source_image', 'animated_video_poster' ) as $key ) {
		if ( ! empty( $meta[ $key ] ) && is_string( $meta[ $key ] ) ) {
			$files[ $key ] = $dir . '/' . wp_basename( $meta[ $key ] );
		}
	}
	$lists = array(
		'size'   => $meta['sizes'] ?? array(),
		'backup' => get_post_meta( $attachment_id, '_wp_attachment_backup_sizes', true ),
	);
	foreach ( $lists as $prefix => $list ) {
		foreach ( is_array( $list ) ? $list : array() as $name => $size ) {
			if ( is_array( $size ) && ! empty( $size['file'] ) && is_string( $size['file'] ) ) {
				$files[ $prefix . ':' . $name ] = $dir . '/' . wp_basename( $size['file'] );
			}
		}
	}

	return array_filter( $files, 'is_file' );
}

/**
 * wp_generate_attachment_metadata (late): cleans every file of the attachment once
 * WordPress has made the sizes, and keeps the recorded file sizes right.
 *
 * @param array  $metadata      Attachment metadata.
 * @param int    $attachment_id Attachment ID.
 * @param string $context       'create' or 'update'.
 * @return array
 */
function hcwa_scrub_attachment_files( $metadata, $attachment_id = 0, $context = 'create' ) {
	if ( ! is_array( $metadata ) || ! wp_attachment_is_image( (int) $attachment_id ) ) {
		return $metadata;
	}
	foreach ( hcwa_attachment_files( (int) $attachment_id, $metadata ) as $label => $path ) {
		$result = hcwa_media_strip_file( $path );
		if ( ! $result['ok'] ) {
			hcwa_log( sprintf( 'Could not remove location data from %s (attachment %d). Run wp hcwa media-scrub.', $path, (int) $attachment_id ) );
			continue;
		}
		if ( ! $result['changed'] ) {
			continue;
		}
		if ( 'file' === $label && isset( $metadata['filesize'] ) ) {
			$metadata['filesize'] = (int) filesize( $path );
		} elseif ( 0 === strpos( $label, 'size:' ) && isset( $metadata['sizes'][ substr( $label, 5 ) ]['filesize'] ) ) {
			$metadata['sizes'][ substr( $label, 5 ) ]['filesize'] = (int) filesize( $path );
		}
	}
	return $metadata;
}
add_filter( 'wp_generate_attachment_metadata', 'hcwa_scrub_attachment_files', 99, 3 );

/**
 * wp_update_attachment_metadata: when a new original_image replaces the old one and
 * nothing references the old file any more, it is deleted. In the client-side flow the
 * server first records the uploaded JPEG as original_image (the main file becomes WebP
 * through hcwa_image_output_format()); the 'scaled' sideload then records the WebP as
 * original_image (class-wp-rest-attachments-controller.php:2948 and :3330-3331), and the
 * JPEG would stay in uploads with no attachment pointing at it, so deleting the photo would
 * leave it public (wp_delete_attachment_files() removes only the current original_image).
 *
 * @param array $data          New metadata.
 * @param int   $attachment_id Attachment ID.
 * @return array
 */
function hcwa_delete_replaced_original( $data, $attachment_id = 0 ) {
	$attachment_id = (int) $attachment_id;
	if ( ! is_array( $data ) || empty( $data['original_image'] ) || ! is_string( $data['original_image'] ) ) {
		return $data;
	}
	$old      = wp_get_attachment_metadata( $attachment_id, true );
	$previous = is_array( $old ) && ! empty( $old['original_image'] ) && is_string( $old['original_image'] ) ? wp_basename( $old['original_image'] ) : '';
	$main     = (string) get_attached_file( $attachment_id, true );
	if ( '' === $previous || '' === $main || wp_basename( $data['original_image'] ) === $previous ) {
		return $data;
	}

	$referenced = array_merge(
		array( $main, (string) ( $data['file'] ?? '' ), $data['original_image'] ),
		array_values( hcwa_attachment_files( $attachment_id, $data ) )
	);
	if ( in_array( $previous, array_map( 'wp_basename', $referenced ), true ) ) {
		return $data;
	}

	$uploads  = wp_get_upload_dir();
	$basedir  = untrailingslashit( (string) ( $uploads['basedir'] ?? '' ) );
	$path     = dirname( $main ) . '/' . $previous;
	$relative = ( '' !== $basedir && 0 === strpos( $path, $basedir . '/' ) ) ? substr( $path, strlen( $basedir ) + 1 ) : '';
	if ( '' === $relative || ! is_file( $path ) ) {
		return $data;
	}
	$owner = (int) attachment_url_to_postid( untrailingslashit( (string) ( $uploads['baseurl'] ?? '' ) ) . '/' . $relative );
	if ( $owner > 0 && $owner !== $attachment_id ) {
		return $data; // Another attachment's file.
	}

	wp_delete_file_from_directory( $path, $basedir );
	return $data;
}
add_filter( 'wp_update_attachment_metadata', 'hcwa_delete_replaced_original', 10, 2 );

/*
 * File names and text from the file. Staff upload phone photos named after the child or
 * the school ("nguyen-van-a-truong-x.jpg"), and WordPress makes that name the file's URL in
 * every src and srcset, the attachment's title and its page slug (media.php:313-320, REST
 * controller:876-888). Text a camera or app wrote into the file (IPTC, EXIF, XMP) becomes
 * the title, caption and alt (media.php:392-407, image.php:886-997). So an image uploaded
 * through WordPress is stored as hcwa-{date}-{random}.{ext}, takes that name as its title
 * until staff write one, and contributes no text of its own. Media already in the library
 * keeps its names (Phase 3 review); its text is handled at print time by
 * hcwa_attachment_text() (media-approval.php), which compares it with what WordPress read
 * from the file, so that record ('image_meta') is left intact outside new uploads.
 *
 * Client-side media processing (WordPress 7.1, on by default over HTTPS:
 * media.php:6580-6592) is switched off: the browser names every size after the local file
 * (wp-includes/js/dist/upload-media.js:731-740), which would put the original name back
 * into each size's URL, and sizes made on the server run through the upload clean-up above.
 */

/**
 * Neutral stored name for an uploaded image ('' for other files, which keep their name:
 * a report PDF's name is what a visitor saves). Pure apart from the random part (tested).
 *
 * @param string      $name   Original file name.
 * @param string|null $random Eight lowercase letters and digits; random when null.
 * @param string|null $date   Ymd; today (UTC) when null.
 */
function hcwa_neutral_upload_name( string $name, ?string $random = null, ?string $date = null ): string {
	$ext = strtolower( (string) pathinfo( $name, PATHINFO_EXTENSION ) );
	if ( ! in_array( $ext, array( 'jpg', 'jpeg', 'jpe', 'png', 'gif', 'webp', 'avif', 'heic', 'heif', 'tif', 'tiff', 'bmp' ), true ) ) {
		return '';
	}
	$random = $random ?? strtolower( wp_generate_password( 8, false, false ) );
	return 'hcwa-' . ( $date ?? gmdate( 'Ymd' ) ) . '-' . $random . '.' . $ext;
}

/**
 * Images uploaded through WordPress in this request: stored base name => original base name.
 *
 * @param string|null $stored   Neutral base name to record.
 * @param string      $original Original base name.
 * @return array<string, string>
 */
function hcwa_request_uploads( ?string $stored = null, string $original = '' ): array {
	static $uploads = array();
	if ( null !== $stored && '' !== $stored ) {
		$uploads[ $stored ] = $original;
	}
	return $uploads;
}

/**
 * The original base name of a file uploaded in this request (by its stored path or name,
 * wp_unique_filename()'s "-1" suffix allowed), or null for any other file.
 *
 * @param string $file Path or file name.
 */
function hcwa_request_upload_original( string $file ): ?string {
	$base = (string) pathinfo( wp_basename( $file ), PATHINFO_FILENAME );
	foreach ( hcwa_request_uploads() as $stored => $original ) {
		if ( $base === $stored || 1 === preg_match( '/^' . preg_quote( $stored, '/' ) . '-\d+$/', $base ) ) {
			return $original;
		}
	}
	return null;
}

/**
 * The REST route being dispatched ('' outside REST), noted on rest_request_before_callbacks
 * and cleared after, so an upload filter can tell a client-side size from a new upload.
 *
 * @param string|null $route Route to note.
 */
function hcwa_current_rest_route( ?string $route = null ): string {
	static $current = '';
	if ( null !== $route ) {
		$current = $route;
	}
	return $current;
}

/**
 * rest_request_before_callbacks (class-wp-rest-server.php): notes the route.
 *
 * @param mixed                $response Response (passed through).
 * @param mixed                $handler  Route handler (unused).
 * @param WP_REST_Request|null $request  Request.
 * @return mixed
 */
function hcwa_note_rest_route( $response, $handler = null, $request = null ) {
	hcwa_current_rest_route( is_object( $request ) && method_exists( $request, 'get_route' ) ? (string) $request->get_route() : '' );
	return $response;
}
add_filter( 'rest_request_before_callbacks', 'hcwa_note_rest_route', 1, 3 );

/**
 * rest_request_after_callbacks: the route is done.
 *
 * @param mixed $response Response (passed through).
 * @return mixed
 */
function hcwa_clear_rest_route( $response ) {
	hcwa_current_rest_route( '' );
	return $response;
}
add_filter( 'rest_request_after_callbacks', 'hcwa_clear_rest_route', 1 );

/**
 * wp_handle_upload_prefilter and wp_handle_sideload_prefilter (file.php:840): an image gets
 * its neutral name before WordPress stores it. A client-side size sent to
 * /wp/v2/media/<id>/sideload keeps its name, which must match its attachment's (the route
 * only exists while client-side processing is on).
 *
 * @param array $file $_FILES-like array.
 * @return array
 */
function hcwa_rename_upload( $file ) {
	if ( ! is_array( $file ) || ! empty( $file['error'] ) || empty( $file['name'] ) || 1 === preg_match( '#^/wp/v2/media/\d+/sideload/?$#i', hcwa_current_rest_route() ) ) {
		return $file;
	}
	$name = hcwa_neutral_upload_name( (string) $file['name'] );
	if ( '' === $name ) {
		return $file;
	}
	hcwa_request_uploads( (string) pathinfo( $name, PATHINFO_FILENAME ), (string) pathinfo( (string) $file['name'], PATHINFO_FILENAME ) );
	$file['name'] = $name;
	return $file;
}
add_filter( 'wp_handle_upload_prefilter', 'hcwa_rename_upload', 5 );
add_filter( 'wp_handle_sideload_prefilter', 'hcwa_rename_upload', 5 );
add_filter( 'wp_client_side_media_processing_enabled', '__return_false', 20 );

/**
 * wp_insert_attachment_data (post.php:4963) for an image uploaded in this request: the
 * title WordPress took from the original file name (or left empty) becomes the neutral
 * stored name, and so does the slug of its attachment page. A title the uploader typed is
 * kept. hcwa-2026 treats a title equal to the file name as unwritten ("Ảnh" / "Photo").
 *
 * @param array $data    Slashed, sanitized attachment data.
 * @param array $postarr Post array; 'file' is the stored path (wp_insert_attachment()).
 * @param array $raw     Unsanitized post array (unused).
 * @param bool  $update  Whether an existing attachment is updated.
 * @return array
 */
function hcwa_neutral_attachment_title( $data, $postarr = array(), $raw = array(), $update = false ) {
	if ( $update || ! is_array( $data ) || ! is_array( $postarr ) || empty( $postarr['file'] ) ) {
		return $data;
	}
	$original = hcwa_request_upload_original( (string) $postarr['file'] );
	if ( null === $original ) {
		return $data;
	}
	$title = trim( wp_unslash( (string) ( $data['post_title'] ?? '' ) ) );
	if ( '' !== $title && sanitize_title( $title ) !== sanitize_title( $original ) ) {
		return $data;
	}
	$stored             = (string) pathinfo( wp_basename( (string) $postarr['file'] ), PATHINFO_FILENAME );
	$data['post_title'] = $stored;
	$data['post_name']  = sanitize_title( $stored );
	return $data;
}
add_filter( 'wp_insert_attachment_data', 'hcwa_neutral_attachment_title', 10, 4 );

/**
 * wp_read_image_metadata (image.php:1077) for an image uploaded in this request: no title,
 * caption, alt, credit, copyright or keywords from the file, so none become the
 * attachment's text. Orientation, the date taken and the camera settings stay. Other reads
 * (the WordPress Importer, regenerating sizes) keep what the file says: it is the record
 * hcwa_attachment_text() compares with.
 *
 * @param mixed  $meta Metadata read from the file.
 * @param string $file File path.
 * @return mixed
 */
function hcwa_upload_image_meta_text( $meta, $file = '' ) {
	if ( ! is_array( $meta ) || null === hcwa_request_upload_original( (string) $file ) ) {
		return $meta;
	}
	foreach ( array( 'title', 'caption', 'alt', 'credit', 'copyright' ) as $key ) {
		if ( array_key_exists( $key, $meta ) ) {
			$meta[ $key ] = '';
		}
	}
	if ( array_key_exists( 'keywords', $meta ) ) {
		$meta['keywords'] = array();
	}
	return $meta;
}
add_filter( 'wp_read_image_metadata', 'hcwa_upload_image_meta_text', 10, 2 );

/**
 * wp_update_attachment_metadata (post.php:7181): keeps the record of the text WordPress
 * once read from the file. After `wp hcwa media-scrub` the files carry no text, so
 * regenerating sizes (`wp media regenerate`, an image edit) would store an empty
 * 'image_meta' and old captions and alt texts filled in from the file would count as
 * written by staff (hcwa_attachment_text()). Text already recorded is kept when the new
 * record has none.
 *
 * @param array $data          New metadata.
 * @param int   $attachment_id Attachment ID.
 * @return array
 */
function hcwa_keep_image_meta_text( $data, $attachment_id = 0 ) {
	if ( ! is_array( $data ) || ! isset( $data['image_meta'] ) || ! is_array( $data['image_meta'] ) ) {
		return $data;
	}
	$old = wp_get_attachment_metadata( (int) $attachment_id, true );
	$old = is_array( $old ) && is_array( $old['image_meta'] ?? null ) ? $old['image_meta'] : array();
	foreach ( array( 'title', 'caption', 'alt', 'credit', 'copyright', 'keywords' ) as $key ) {
		if ( ! empty( $old[ $key ] ) && empty( $data['image_meta'][ $key ] ) ) {
			$data['image_meta'][ $key ] = $old[ $key ];
		}
	}
	return $data;
}
add_filter( 'wp_update_attachment_metadata', 'hcwa_keep_image_meta_text', 5, 2 );
