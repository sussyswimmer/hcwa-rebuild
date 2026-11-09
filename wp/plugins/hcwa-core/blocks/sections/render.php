<?php
/**
 * hcwa/sections: The locked sections of a page built from the prototype. Change the words inside each section; the sections themselves stay in place. Prints the prototype's markup with no wrapper element
 * (docs/plan/11-WP-PORT.md 2.1 rule 3); the markup is built in includes/blocks-sections.php.
 *
 * @package hcwa-core
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Rendered inner blocks.
 * @var WP_Block $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

echo $content; // Inner blocks only: no wrapper, no literal HTML (hcwa_sections_clean()). // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
