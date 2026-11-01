<?php
/**
 * Header navigation: one menu per language (Polylang assigns the right one to the "primary"
 * location), rendered twice from the same tree: desktop dropdowns and the phone sheet.
 * Markup matches prototype/src/partials/layout.mjs.
 */

defined( 'ABSPATH' ) || exit;

/** Menu items of a location as a two-level tree. */
function hcwa_menu_tree( $location = 'primary' ) {
	$locations = get_nav_menu_locations();
	if ( empty( $locations[ $location ] ) ) {
		return array();
	}
	$items = wp_get_nav_menu_items( $locations[ $location ] );
	if ( ! $items ) {
		return array();
	}
	_wp_menu_item_classes_by_context( $items ); // sets current flags like wp_nav_menu() does
	$tree = array();
	foreach ( $items as $item ) {
		if ( ! $item->menu_item_parent ) {
			$tree[ $item->ID ] = array( 'item' => $item, 'children' => array() );
		}
	}
	foreach ( $items as $item ) {
		if ( $item->menu_item_parent && isset( $tree[ $item->menu_item_parent ] ) ) {
			$tree[ $item->menu_item_parent ]['children'][] = $item;
		}
	}
	return array_values( $tree );
}

/** Page ID of the section the current request belongs to (for the "you are here" state). */
function hcwa_section_page_id() {
	if ( is_singular( array( 'hcwa_center', 'hcwa_program' ) ) ) {
		return hcwa_page_id( 'ourWork' );
	}
	if ( is_singular( 'hcwa_report' ) || is_post_type_archive( 'hcwa_report' ) ) {
		return hcwa_page_id( 'impact' );
	}
	if ( is_singular( 'post' ) || is_home() || is_category() || is_tag() ) {
		$news = (int) get_option( 'page_for_posts' );
		return $news && function_exists( 'pll_get_post' ) ? (int) pll_get_post( $news, hcwa_lang() ) : $news;
	}
	if ( is_page() ) {
		$ancestors = get_post_ancestors( get_queried_object_id() );
		return $ancestors ? (int) end( $ancestors ) : get_queried_object_id();
	}
	return 0;
}

/** aria-current value for a top-level item: "page" on the page itself, "true" inside its section. */
function hcwa_item_current( $item, $children ) {
	$object = (int) $item->object_id;
	if ( 'custom' !== $item->type && is_singular() && $object === get_queried_object_id() ) {
		return 'page';
	}
	if ( is_home() && ! is_front_page() && 'custom' !== $item->type && $object === hcwa_section_page_id() ) {
		return 'page';
	}
	if ( 'custom' !== $item->type && $object && $object === hcwa_section_page_id() ) {
		return 'true';
	}
	foreach ( $children as $child ) {
		if ( ! empty( $child->current ) ) {
			return 'true';
		}
	}
	return '';
}

function hcwa_desktop_nav() {
	$tree = hcwa_menu_tree();
	if ( ! $tree ) {
		return '';
	}
	$out = '<nav class="nav" aria-label="' . esc_attr( hcwa_t( 'mainNav' ) ) . '"><ul class="nav__list">';
	foreach ( $tree as $i => $node ) {
		$item    = $node['item'];
		$current = hcwa_item_current( $item, $node['children'] );
		$out    .= '<li class="nav__item' . ( $node['children'] ? ' has-sub' : '' ) . '">';
		$out    .= '<a class="nav__link" href="' . esc_url( $item->url ) . '"' . ( $current ? ' aria-current="' . esc_attr( $current ) . '"' : '' ) . '>' . esc_html( $item->title ) . '</a>';
		if ( $node['children'] ) {
			$out .= '<button class="nav__toggle" type="button" aria-expanded="false" aria-controls="sub-' . (int) $i . '" aria-label="' . esc_attr( hcwa_t( 'submenu', array( 'x' => $item->title ) ) ) . '">' . hcwa_icon( 'chevron' ) . '</button>';
			$out .= '<ul class="nav__sub" id="sub-' . (int) $i . '" hidden>';
			foreach ( $node['children'] as $child ) {
				$out .= '<li><a href="' . esc_url( $child->url ) . '"' . ( ! empty( $child->current ) ? ' aria-current="page"' : '' ) . '>' . esc_html( $child->title ) . '</a></li>';
			}
			$out .= '</ul>';
		}
		$out .= '</li>';
	}
	return $out . '</ul></nav>';
}

function hcwa_mobile_menu() {
	$out  = '<dialog class="sheet" id="menu-sheet" aria-labelledby="menu-title">';
	$out .= '<div class="sheet__head"><p class="sheet__title" id="menu-title">' . esc_html( hcwa_t( 'menu' ) ) . '</p>';
	$out .= '<button class="icon-btn" type="button" data-close-sheet aria-label="' . esc_attr( hcwa_t( 'close' ) ) . '">' . hcwa_icon( 'close' ) . '</button></div>';
	$out .= '<nav aria-label="' . esc_attr( hcwa_t( 'mainNav' ) ) . '"><ul class="sheet__list">';
	foreach ( hcwa_menu_tree() as $node ) {
		$item = $node['item'];
		if ( $node['children'] ) {
			$out .= '<li><details><summary>' . esc_html( $item->title ) . hcwa_icon( 'chevron' ) . '</summary><ul>';
			$out .= '<li><a href="' . esc_url( $item->url ) . '">' . esc_html( $item->title ) . '</a></li>';
			foreach ( $node['children'] as $child ) {
				$out .= '<li><a href="' . esc_url( $child->url ) . '">' . esc_html( $child->title ) . '</a></li>';
			}
			$out .= '</ul></details></li>';
		} else {
			$out .= '<li><a class="sheet__link" href="' . esc_url( $item->url ) . '">' . esc_html( $item->title ) . '</a></li>';
		}
	}
	$out .= '</ul></nav>';
	$out .= '<div class="sheet__foot">' . hcwa_button( hcwa_url( 'donate' ), hcwa_t( 'donate' ) ) . hcwa_lang_switch() . '</div>';
	return $out . '</dialog>';
}

/** VI / EN switch: text, not flags; the visible code stays in the accessible name (WCAG 2.5.3). */
function hcwa_lang_switch( $class = '' ) {
	$parts = array();
	foreach ( hcwa_language_links() as $lang => $link ) {
		$parts[] = sprintf(
			'<a href="%s" lang="%s" hreflang="%s"%s>%s<span class="sr-only"> (%s)</span></a>',
			esc_url( $link['url'] ),
			esc_attr( $lang ),
			esc_attr( $lang ),
			$link['current'] ? ' aria-current="true"' : '',
			esc_html( strtoupper( $lang ) ),
			esc_html( $link['name'] )
		);
	}
	return '<div class="lang-switch' . ( $class ? ' ' . esc_attr( $class ) : '' ) . '" role="group" aria-label="' . esc_attr( hcwa_t( 'language' ) ) . '">' . implode( '<span aria-hidden="true">/</span>', $parts ) . '</div>';
}

function hcwa_logo() {
	$src = get_template_directory_uri() . '/assets/img/hcwa-logo.png';
	return '<a class="logo" href="' . esc_url( hcwa_url( 'home' ) ) . '"><img src="' . esc_url( $src ) . '" width="97" height="128" alt="' . esc_attr( hcwa_t( 'orgName' ) ) . '"><span class="logo__name" aria-hidden="true"><span>' . esc_html( hcwa_t( 'logoLine1' ) ) . '</span><span>' . esc_html( hcwa_t( 'logoLine2' ) ) . '</span></span></a>';
}
