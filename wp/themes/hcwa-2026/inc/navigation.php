<?php
/**
 * Menus: the walker that prints the prototype's markup, the fallback menus built from the
 * page keys, and "current section" marking for pages WordPress does not relate to a menu
 * item (center and program singles, posts).
 *
 * @package hcwa-2026
 */

defined( 'ABSPATH' ) || exit;

/**
 * Prints a menu as the desktop nav (`nav__*`, dropdown toggles) or the mobile sheet
 * (`sheet__*`, details/summary accordions). Two levels.
 */
class HCWA_Theme_Nav_Walker extends Walker_Nav_Menu {

	/**
	 * 'desktop' or 'sheet'.
	 *
	 * @var string
	 */
	private string $variant;

	/**
	 * Parent of the submenu being opened (start_lvl does not receive it).
	 *
	 * @var object|null
	 */
	private $parent_item = null;

	/**
	 * Whether each open top-level item has children, so end_el can close what start_el opened.
	 *
	 * @var bool[]
	 */
	private array $open_parents = array();

	public function __construct( string $variant = 'desktop' ) {
		parent::__construct();
		$this->variant = 'sheet' === $variant ? 'sheet' : 'desktop';
	}

	public function start_lvl( &$output, $depth = 0, $args = null ) {
		if ( 'sheet' === $this->variant ) {
			$output .= '<ul>';
			// The hub page itself, since the summary only opens the accordion.
			if ( $this->parent_item ) {
				$output .= '<li>' . $this->link( $this->parent_item, $args, $depth, '' ) . '</li>';
			}
			return;
		}
		$id      = $this->parent_item ? 'sub-' . $this->parent_item->ID : 'sub-' . wp_unique_id();
		$output .= '<ul class="nav__sub" id="' . esc_attr( $id ) . '" hidden>';
	}

	public function end_lvl( &$output, $depth = 0, $args = null ) {
		$output .= '</ul>';
	}

	public function start_el( &$output, $data_object, $depth = 0, $args = null, $current_object_id = 0 ) {
		$item         = $data_object;
		$has_children = ! empty( $this->has_children ) && 0 === (int) $depth;
		$label        = $this->label( $item, $args, $depth );

		if ( 0 === (int) $depth ) {
			$this->open_parents[] = $has_children;
			$this->parent_item    = $has_children ? $item : null;
		}

		if ( 'sheet' === $this->variant ) {
			if ( $has_children ) {
				$open    = ! empty( $item->current_item_ancestor ) || ! empty( $item->current_item_parent ) || ! empty( $item->current );
				$output .= '<li><details' . ( $open ? ' open' : '' ) . '><summary>' . esc_html( $label ) . hcwa_theme_icon( 'chevron' ) . '</summary>';
				return;
			}
			$output .= '<li>' . $this->link( $item, $args, $depth, 0 === (int) $depth ? 'sheet__link' : '' );
			return;
		}

		if ( 0 !== (int) $depth ) {
			$output .= '<li>' . $this->link( $item, $args, $depth, '' );
			return;
		}

		$output .= '<li class="nav__item' . ( $has_children ? ' has-sub' : '' ) . '">' . $this->link( $item, $args, $depth, 'nav__link' );
		if ( $has_children ) {
			$output .= sprintf(
				'<button class="nav__toggle" type="button" aria-expanded="false" aria-controls="%s" aria-label="%s">%s</button>',
				esc_attr( 'sub-' . $item->ID ),
				esc_attr( hcwa_theme_t( 'submenu', array( 'x' => $label ) ) ),
				hcwa_theme_icon( 'chevron' )
			);
		}
	}

	public function end_el( &$output, $data_object, $depth = 0, $args = null ) {
		if ( 0 === (int) $depth && 'sheet' === $this->variant && array_pop( $this->open_parents ) ) {
			$output .= '</details></li>';
			return;
		}
		if ( 0 === (int) $depth && 'desktop' === $this->variant ) {
			array_pop( $this->open_parents );
		}
		$output .= '</li>';
	}

	/**
	 * Plain-text label of a menu item.
	 *
	 * @param object $item  Menu item.
	 * @param mixed  $args  wp_nav_menu() arguments.
	 * @param int    $depth Depth.
	 */
	private function label( $item, $args, $depth ): string {
		$title = apply_filters( 'the_title', $item->title, $item->ID );
		$title = apply_filters( 'nav_menu_item_title', $title, $item, $args, $depth );
		return html_entity_decode( wp_strip_all_tags( (string) $title ), ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * The <a> for an item, with aria-current: "page" on the page itself, "true" on its section.
	 *
	 * @param object $item  Menu item.
	 * @param mixed  $args  wp_nav_menu() arguments.
	 * @param int    $depth Depth.
	 * @param string $class Link class.
	 */
	private function link( $item, $args, $depth, string $class ): string {
		$current = '';
		if ( ! empty( $item->current ) ) {
			$current = 'page';
		} elseif ( ! empty( $item->current_item_ancestor ) || ! empty( $item->current_item_parent ) ) {
			$current = 'true';
		}
		$atts = array(
			'class'        => $class,
			'href'         => $item->url ?? '',
			'aria-current' => $current,
			'target'       => $item->target ?? '',
			'rel'          => ( '_blank' === ( $item->target ?? '' ) ) ? 'noopener' : ( $item->xfn ?? '' ),
			'title'        => $item->attr_title ?? '',
		);
		$atts = apply_filters( 'nav_menu_link_attributes', $atts, $item, $args, $depth );
		return '<a' . $this->build_atts( $atts ) . '>' . esc_html( $this->label( $item, $args, $depth ) ) . '</a>';
	}
}

/**
 * Prints the primary menu in one of the two variants.
 *
 * @param string $variant 'desktop' or 'sheet'.
 */
function hcwa_theme_primary_menu( string $variant ): void {
	$class = 'sheet' === $variant ? 'sheet__list' : 'nav__list';
	wp_nav_menu(
		array(
			'theme_location' => 'primary',
			'container'      => false,
			'items_wrap'     => '<ul class="' . $class . '">%3$s</ul>',
			'depth'          => 2,
			'walker'         => new HCWA_Theme_Nav_Walker( $variant ),
			'fallback_cb'    => 'hcwa_theme_menu_fallback',
			'hcwa_fallback'  => 'primary',
		)
	);
}

/**
 * Prints a footer menu (plain list).
 *
 * @param string $location footer-explore | footer-trust.
 */
function hcwa_theme_footer_menu( string $location ): void {
	wp_nav_menu(
		array(
			'theme_location' => $location,
			'container'      => false,
			'items_wrap'     => '<ul>%3$s</ul>',
			'depth'          => 1,
			'fallback_cb'    => 'hcwa_theme_menu_fallback',
			'hcwa_fallback'  => $location,
		)
	);
}

/**
 * Menu trees used when no menu is assigned to a location (04-SITEMAP).
 * Each entry: [ page or anchor key, label key, children ].
 *
 * @param string $location Menu location.
 * @return array<int, array{0: string, 1: string, 2: array}>
 */
function hcwa_theme_fallback_tree( string $location ): array {
	switch ( $location ) {
		case 'footer-explore':
			return array(
				array( 'about', 'about', array() ),
				array( 'ourWork', 'ourWork', array() ),
				array( 'impact', 'impact', array() ),
				array( 'news', 'news', array() ),
				array( 'getInvolved', 'getInvolved', array() ),
			);
		case 'footer-trust':
			return array(
				array( 'reports', 'annualReports', array() ),
				array( 'safeguarding', 'safeguarding', array() ),
				array( 'privacy', 'privacy', array() ),
				array( 'partners', 'partners', array() ),
			);
		default:
			return array(
				array(
					'about',
					'about',
					array(
						array( 'about', 'whoWeAre' ),
						array( 'ourStory', 'ourStory' ),
						array( 'team', 'team' ),
						array( 'partners', 'partners' ),
						array( 'policies', 'policies' ),
					),
				),
				array(
					'ourWork',
					'ourWork',
					array(
						array( 'centers', 'centers' ),
						array( 'programs', 'programs' ),
						array( 'ourWork', 'allWork' ),
					),
				),
				array( 'impact', 'impact', array() ),
				array( 'news', 'news', array() ),
				array(
					'getInvolved',
					'getInvolved',
					array(
						array( 'donate', 'donate' ),
						array( 'volunteer', 'volunteer' ),
						array( 'partner', 'partnerWithUs' ),
						array( 'inKind', 'inKind' ),
						array( 'bakery', 'bakery' ),
					),
				),
				array( 'contact', 'contact', array() ),
			);
	}
}

/**
 * wp_nav_menu() fallback: builds menu-item-like objects from the page keys and walks them
 * with the same walker, so the markup is identical to a real menu.
 *
 * @param array $args wp_nav_menu() arguments.
 * @return string|void
 */
function hcwa_theme_menu_fallback( array $args ) {
	$location = (string) ( $args['hcwa_fallback'] ?? 'primary' );
	// Distinct fake IDs per location: the default walker prints them as HTML ids.
	$bases = array(
		'primary'        => 910000,
		'footer-explore' => 920000,
		'footer-trust'   => 930000,
	);
	$next  = $bases[ $location ] ?? 940000;
	$items = array();
	foreach ( hcwa_theme_fallback_tree( $location ) as $entry ) {
		$parent_id = ++$next;
		$items[]   = hcwa_theme_fake_menu_item( $parent_id, 0, $entry[0], $entry[1], count( $items ) + 1 );
		foreach ( $entry[2] as $child ) {
			$items[] = hcwa_theme_fake_menu_item( ++$next, $parent_id, $child[0], $child[1], count( $items ) + 1 );
		}
	}
	$items = hcwa_theme_mark_current( $items );

	$walk_args = (object) $args;
	$html      = walk_nav_menu_tree( $items, (int) ( $args['depth'] ?? 0 ), $walk_args );
	$wrap      = (string) ( $args['items_wrap'] ?? '<ul>%3$s</ul>' );
	$output    = sprintf( $wrap, '', '', $html );

	if ( ! empty( $args['echo'] ) ) {
		echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts by the walker.
		return;
	}
	return $output;
}

/**
 * A stand-in for a nav_menu_item post with the fields Walker_Nav_Menu reads.
 *
 * @param int    $id        Fake ID.
 * @param int    $parent_id Parent fake ID (0 for top level).
 * @param string $url_key   Page or anchor key.
 * @param string $label_key Dictionary key.
 * @param int    $order     Menu order.
 */
function hcwa_theme_fake_menu_item( int $id, int $parent_id, string $url_key, string $label_key, int $order ): object {
	return (object) array(
		'ID'                    => $id,
		'db_id'                 => $id,
		'menu_item_parent'      => $parent_id,
		'menu_order'            => $order,
		'object_id'             => 0,
		'object'                => 'custom',
		'type'                  => 'custom',
		'title'                 => hcwa_theme_t( $label_key ),
		'url'                   => hcwa_theme_url( $url_key ),
		'target'                => '',
		'attr_title'            => '',
		'description'           => '',
		'classes'               => array(),
		'xfn'                   => '',
		'current'               => false,
		'current_item_ancestor' => false,
		'current_item_parent'   => false,
	);
}

add_filter( 'wp_nav_menu_objects', 'hcwa_theme_mark_current' );

/**
 * Marks the menu item for the current section by URL, for pages WordPress cannot relate to
 * a menu item itself: center and program singles under Our Work, posts under News.
 *
 * @param array $items Menu items.
 * @return array
 */
function hcwa_theme_mark_current( array $items ): array {
	global $wp;
	$request = isset( $wp->request ) ? (string) $wp->request : '';
	$current = '/' . ltrim( trailingslashit( $request ), '/' );
	$home    = (string) wp_parse_url( (string) get_option( 'home' ), PHP_URL_PATH );
	$home    = '/' . trim( $home, '/' );
	$news    = is_singular( 'post' ) || is_category() || is_tag() ? wp_parse_url( hcwa_theme_url( 'news' ), PHP_URL_PATH ) : null;

	$has_current = false;
	foreach ( $items as $item ) {
		if ( ! empty( $item->current ) ) {
			$has_current = true;
			break;
		}
	}

	foreach ( $items as $item ) {
		$path = (string) wp_parse_url( (string) ( $item->url ?? '' ), PHP_URL_PATH );
		if ( '' === $path || str_contains( (string) ( $item->url ?? '' ), '#' ) ) {
			continue;
		}
		$path = trailingslashit( $path );
		if ( '/' !== $home && str_starts_with( $path, trailingslashit( $home ) ) ) {
			$path = '/' . substr( $path, strlen( trailingslashit( $home ) ) );
		}
		if ( ! $has_current && $path === $current ) {
			$item->current = true;
			continue;
		}
		$is_section = hcwa_theme_is_section_path( $path, $current ) || ( $news && trailingslashit( (string) $news ) === trailingslashit( (string) wp_parse_url( (string) $item->url, PHP_URL_PATH ) ) );
		if ( empty( $item->current ) && $is_section ) {
			$item->current_item_ancestor = true;
		}
	}
	return $items;
}
