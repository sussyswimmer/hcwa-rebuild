<?php
/**
 * hcwa/announcement-bar: the one-line announcement (Settings → HCWA), printed by header.php inside
 * div.announce-slot. Nothing when it is switched off or has no text in this language.
 *
 * @package hcwa-core
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner content (none).
 * @var WP_Block $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

echo hcwa_render_announcement(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
