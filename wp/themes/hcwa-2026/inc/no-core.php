<?php
/**
 * Shown to visitors when the HCWA Core plugin is not active.
 */

defined( 'ABSPATH' ) || exit;
status_header( 503 );
header( 'Retry-After: 3600' );
?><!doctype html>
<html lang="vi">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex"><title>HCWA</title></head>
<body style="font-family:system-ui,sans-serif;line-height:1.6;max-width:40rem;margin:4rem auto;padding:0 1rem">
<p>Trang web đang được bảo trì. Vui lòng quay lại sau.</p>
<p lang="en">The website is under maintenance. Please come back later.</p>
</body>
</html>
