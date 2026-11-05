<?php
/**
 * The SEO plugin (Yoast SEO, kept per 03-STRATEGY) under the same rules as the pages:
 * photo approval (contract rule 2) and no draft text on the live site (rule 4).
 *
 * Yoast builds og:image, twitter:image and the schema ImageObject (with the photo's
 * caption) from the post's own fields, its featured image and the first image in its raw
 * content, stores them in its indexable table at save time, and prints them in the head,
 * in its REST fields and in its image sitemaps. None of that passes through the img
 * filters in media-approval.php, so every image Yoast prints is checked here with
 * hcwa_image_allowed() in the post's context. The hooks are inert without Yoast.
 *
 * Yoast's own code is not in .wp-local/ref: the hook names and the presentation fields
 * (open_graph_images, twitter_image, model->object_type / object_id) follow Yoast's
 * public filters and Polylang's integration (polylang src/integrations/wpseo/wpseo.php:
 * 44, 358-391, 431-461, 505-515) and must be checked against HCWA's Yoast version.
 *
 * @package hcwa-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Context for hcwa_image_allowed() of a Yoast indexable: the post for a post, page,
 * center, program or report; for archives and the blog home a strict context (approved,
 * no identifiable child).
 *
 * @param object|null $indexable Yoast indexable (presentation->model or context->indexable).
 * @return int|bool
 */
function hcwa_seo_image_context( $indexable ) {
	if ( is_object( $indexable ) && 'post' === ( $indexable->object_type ?? '' ) && (int) ( $indexable->object_id ?? 0 ) > 0 ) {
		return (int) $indexable->object_id;
	}
	return true;
}

/**
 * Whether Yoast may print an image: a library photo must pass the approval gate; a file of
 * the site outside the uploads folder (theme, plugin) may; another site's image or an
 * upload that matches no attachment may not.
 *
 * @param string   $url     Image URL.
 * @param int      $id      Attachment ID when Yoast gives one, else 0.
 * @param int|bool $context hcwa_seo_image_context().
 */
function hcwa_seo_image_allowed( string $url, int $id, $context ): bool {
	if ( $id <= 0 ) {
		if ( '' === trim( $url ) ) {
			return false;
		}
		$id = hcwa_content_url_attachment_id( $url );
		if ( 0 === $id ) {
			return true;
		}
	}
	return $id > 0 && hcwa_image_allowed( $id, $context );
}

/**
 * Yoast's default social image (Social → Default image), in the current language, when it
 * is approved for web: the fallback 09-QA asks for ("Open Graph image per page type, per
 * language") once a refused photo is taken out.
 *
 * @return array<string, array{url: string, width: int, height: int, type: string}>
 */
function hcwa_seo_fallback_images(): array {
	$id = 0;
	if ( class_exists( 'WPSEO_Options' ) && is_callable( array( 'WPSEO_Options', 'get' ) ) ) {
		$id = (int) WPSEO_Options::get( 'og_default_image_id' );
	}
	/**
	 * Filters the image shared when a page has no approved image of its own.
	 *
	 * @param int    $id   Attachment ID (Yoast's default image), 0 for none.
	 * @param string $lang Current language.
	 */
	$id = (int) apply_filters( 'hcwa_seo_fallback_image_id', $id, hcwa_lang() );
	if ( $id <= 0 || ! hcwa_image_allowed( $id, true ) ) {
		return array();
	}
	$src = wp_get_attachment_image_src( $id, 'full' );
	if ( ! is_array( $src ) || empty( $src[0] ) ) {
		return array();
	}
	return array(
		(string) $src[0] => array(
			'url'    => (string) $src[0],
			'width'  => (int) $src[1],
			'height' => (int) $src[2],
			'type'   => (string) get_post_mime_type( $id ),
		),
	);
}

/**
 * wpseo_frontend_presentation (after Polylang's, priority 10): takes refused images out of
 * og:image and twitter:image. The presentation is what the head, yoast_head_json and
 * /yoast/v1/get_head all print, and the post comes from the indexable, not from a global,
 * so REST output is covered too. Fails closed: if Yoast's fields cannot be read, no image.
 *
 * @param object $presentation Yoast indexable presentation.
 * @return object
 */
function hcwa_seo_presentation( $presentation ) {
	if ( ! is_object( $presentation ) ) {
		return $presentation;
	}
	try {
		$context = hcwa_seo_image_context( $presentation->model ?? null );
		$images  = $presentation->open_graph_images;
		$kept    = array();
		foreach ( is_array( $images ) ? $images : array() as $key => $image ) {
			$url = is_array( $image ) ? (string) ( $image['url'] ?? '' ) : (string) $image;
			$id  = is_array( $image ) ? (int) ( $image['id'] ?? 0 ) : 0;
			if ( hcwa_seo_image_allowed( $url, $id, $context ) ) {
				$kept[ $key ] = $image;
			}
		}
		$presentation->open_graph_images = $kept ? $kept : hcwa_seo_fallback_images();

		$twitter = $presentation->twitter_image;
		if ( is_string( $twitter ) && '' !== $twitter && ! hcwa_seo_image_allowed( $twitter, 0, $context ) ) {
			$presentation->twitter_image = '';
		}
	} catch ( Throwable $e ) {
		$presentation->open_graph_images = array();
		$presentation->twitter_image     = '';
	}
	return $presentation;
}
add_filter( 'wpseo_frontend_presentation', 'hcwa_seo_presentation', 20 );

/**
 * wpseo_opengraph_image and wpseo_twitter_image: the same check on each printed URL, in
 * case another filter adds one after the presentation was built.
 *
 * @param string      $url          Image URL.
 * @param object|null $presentation Yoast presentation.
 * @return string
 */
function hcwa_seo_filter_image_url( $url, $presentation = null ) {
	if ( ! is_string( $url ) || '' === $url ) {
		return $url;
	}
	$context = hcwa_seo_image_context( is_object( $presentation ) ? ( $presentation->model ?? null ) : null );
	return hcwa_seo_image_allowed( $url, 0, $context ) ? $url : '';
}
add_filter( 'wpseo_opengraph_image', 'hcwa_seo_filter_image_url', 20, 2 );
add_filter( 'wpseo_twitter_image', 'hcwa_seo_filter_image_url', 20, 2 );

/**
 * wpseo_schema_graph: removes refused ImageObject nodes (they carry the photo's caption,
 * which often names a child) and every image, primaryImageOfPage and thumbnailUrl that
 * points at a refused photo. The site logo node is left alone.
 *
 * @param array       $graph   Schema graph (list of nodes).
 * @param object|null $context Yoast Meta_Tags_Context.
 * @return array
 */
function hcwa_seo_schema_graph( $graph, $context = null ) {
	if ( ! is_array( $graph ) ) {
		return $graph;
	}
	$object  = hcwa_seo_image_context( is_object( $context ) ? ( $context->indexable ?? null ) : null );
	$refused = array();

	foreach ( $graph as $index => $node ) {
		if ( hcwa_seo_image_node_refused( $node, $object ) ) {
			foreach ( array( '@id', 'url', 'contentUrl' ) as $key ) {
				if ( ! empty( $node[ $key ] ) && is_string( $node[ $key ] ) ) {
					$refused[ $node[ $key ] ] = true;
				}
			}
			unset( $graph[ $index ] );
		}
	}

	foreach ( $graph as $index => $node ) {
		if ( ! is_array( $node ) ) {
			continue;
		}
		foreach ( array( 'image', 'primaryImageOfPage', 'thumbnailUrl' ) as $key ) {
			if ( ! array_key_exists( $key, $node ) ) {
				continue;
			}
			$list = ( is_array( $node[ $key ] ) && array_is_list( $node[ $key ] ) ) ? $node[ $key ] : array( $node[ $key ] );
			$list = array_values( array_filter( $list, static fn( $ref ) => ! hcwa_seo_image_ref_refused( $ref, $refused, $object ) ) );
			if ( ! $list ) {
				unset( $node[ $key ] );
			} else {
				$node[ $key ] = ( is_array( $node[ $key ] ) && array_is_list( $node[ $key ] ) ) ? $list : $list[0];
			}
		}
		$graph[ $index ] = $node;
	}

	return array_values( $graph );
}
add_filter( 'wpseo_schema_graph', 'hcwa_seo_schema_graph', 20, 2 );

/**
 * Whether a schema node is an ImageObject of a refused photo (not the site logo).
 *
 * @param mixed    $node    Schema node.
 * @param int|bool $context hcwa_seo_image_context().
 */
function hcwa_seo_image_node_refused( $node, $context ): bool {
	if ( ! is_array( $node ) || ! in_array( 'ImageObject', (array) ( $node['@type'] ?? array() ), true ) ) {
		return false;
	}
	if ( false !== strpos( (string) ( $node['@id'] ?? '' ), '/schema/logo/image/' ) ) {
		return false;
	}
	$url = (string) ( $node['contentUrl'] ?? ( $node['url'] ?? '' ) );
	return ! hcwa_seo_image_allowed( $url, 0, $context );
}

/**
 * Whether an image reference in a schema node (an {"@id"} object, a URL or an inline
 * ImageObject) points at a refused photo.
 *
 * @param mixed               $ref     Reference.
 * @param array<string, true> $refused @id and URLs of removed nodes.
 * @param int|bool            $context hcwa_seo_image_context().
 */
function hcwa_seo_image_ref_refused( $ref, array $refused, $context ): bool {
	if ( is_string( $ref ) ) {
		return isset( $refused[ $ref ] ) || ! hcwa_seo_image_allowed( $ref, 0, $context );
	}
	if ( ! is_array( $ref ) ) {
		return false;
	}
	if ( isset( $ref['@id'] ) && is_string( $ref['@id'] ) && isset( $refused[ $ref['@id'] ] ) ) {
		return true;
	}
	return hcwa_seo_image_node_refused( $ref, $context );
}

/**
 * wpseo_schema_organization: the Organization node Yoast builds from its "Site
 * representation" settings, under the same rules as the pages.
 *
 * - foundingDate and numberOfEmployees are never printed: no verified source exists for
 *   either (CLAUDE.md: no unverified founding year or statistic). When HCWA verifies the
 *   founding year it comes from `content/verified/`, not from a free text field.
 * - description is left out: free text typed once, in one language (Polylang translates
 *   only company_name, polylang src/integrations/wpseo/wpseo.php:68-85), that nobody
 *   checks for figures.
 * - Any other text that still carries a draft marker is left out on the live site.
 * - alternateName gains "HCWA"; address comes from Settings → HCWA in the page language
 *   (hcwa_setting_l(), '' on production while it has a draft marker); sameAs gains the
 *   Facebook and Zalo links from the same settings.
 *
 * Yoast's code is not in .wp-local/ref: the filter name follows its per-piece
 * "wpseo_schema_{piece}" filters and must be checked against HCWA's Yoast version.
 *
 * @param mixed       $data    Organization node.
 * @param object|null $context Yoast Meta_Tags_Context (unused).
 * @return mixed
 */
function hcwa_seo_schema_organization( $data, $context = null ) {
	if ( ! is_array( $data ) ) {
		return $data;
	}
	unset( $data['foundingDate'], $data['numberOfEmployees'], $data['description'] );

	if ( hcwa_is_production() ) {
		foreach ( $data as $key => $value ) {
			if ( is_string( $value ) && hcwa_contains_draft( $value ) ) {
				unset( $data[ $key ] );
			}
		}
	}

	$alternate = array_values( array_filter( array_map( 'strval', (array) ( $data['alternateName'] ?? array() ) ), static fn( string $name ): bool => '' !== trim( $name ) && ! ( hcwa_is_production() && hcwa_contains_draft( $name ) ) ) );
	if ( ! in_array( 'HCWA', $alternate, true ) ) {
		$alternate[] = 'HCWA';
	}
	$data['alternateName'] = 1 === count( $alternate ) ? $alternate[0] : $alternate;

	$address = trim( (string) preg_replace( '/\s*\n\s*/', ', ', hcwa_setting_l( 'address' ) ) );
	if ( '' !== $address ) {
		$data['address'] = array(
			'@type'          => 'PostalAddress',
			'streetAddress'  => $address,
			'addressCountry' => 'VN',
		);
	}

	$same_as = array_values( array_filter( array_map( 'strval', (array) ( $data['sameAs'] ?? array() ) ) ) );
	foreach ( array( 'facebook', 'zalo' ) as $key ) {
		$url = esc_url_raw( (string) hcwa_setting( $key, '' ) );
		if ( '' !== $url && ! in_array( $url, $same_as, true ) ) {
			$same_as[] = $url;
		}
	}
	if ( $same_as ) {
		$data['sameAs'] = $same_as;
	}
	return $data;
}
add_filter( 'wpseo_schema_organization', 'hcwa_seo_schema_organization', 20, 2 );

/*
 * Image sitemaps list the featured image and the images parsed from raw content of every
 * post, none of which is rendered, so refused photos would go to Google Images (task 4.2
 * submits the sitemaps). The site gains little from image sitemaps: they are switched off,
 * and attachments never get a sitemap of their own even if media pages are switched on.
 */
add_filter( 'wpseo_xml_sitemap_include_images', '__return_false' );

/**
 * wpseo_sitemap_exclude_post_type: no attachment sitemap.
 *
 * @param bool   $excluded  Whether the post type is left out.
 * @param string $post_type Post type.
 */
function hcwa_seo_sitemap_exclude_attachments( $excluded, $post_type = '' ): bool {
	return 'attachment' === $post_type || (bool) $excluded;
}
add_filter( 'wpseo_sitemap_exclude_post_type', 'hcwa_seo_sitemap_exclude_attachments', 10, 2 );

/**
 * Yoast's head fields (yoast_head, yoast_head_json) stay out of public REST responses:
 * the site renders on the server and needs no headless head, and in bulk they would list
 * every post's share image and schema (post-types.php closes /wp/v2/media for the same
 * reason; /yoast/v1/get_head is closed there too). Editors still get them.
 *
 * @param WP_REST_Response $response Response.
 * @param mixed            $object   Post or term (unused).
 * @return WP_REST_Response
 */
function hcwa_seo_rest_hide_head( $response, $object = null ) {
	if ( ! $response instanceof WP_REST_Response || current_user_can( 'edit_posts' ) ) {
		return $response;
	}
	$data = $response->get_data();
	if ( is_array( $data ) && ( array_key_exists( 'yoast_head', $data ) || array_key_exists( 'yoast_head_json', $data ) ) ) {
		unset( $data['yoast_head'], $data['yoast_head_json'] );
		$response->set_data( $data );
	}
	return $response;
}
foreach ( array( 'post', 'page', 'hcwa_center', 'hcwa_program', 'hcwa_report', 'category', 'post_tag' ) as $hcwa_seo_rest_type ) {
	add_filter( "rest_prepare_{$hcwa_seo_rest_type}", 'hcwa_seo_rest_hide_head', 10, 2 );
}
unset( $hcwa_seo_rest_type );

/**
 * Yoast's "Headless REST endpoints" switch (Site features → APIs) stays off.
 *
 * @param mixed $value The wpseo option.
 * @return mixed
 */
function hcwa_seo_no_headless_endpoints( $value ) {
	if ( is_array( $value ) && array_key_exists( 'enable_headless_rest_endpoints', $value ) ) {
		$value['enable_headless_rest_endpoints'] = false;
	}
	return $value;
}
add_filter( 'option_wpseo', 'hcwa_seo_no_headless_endpoints' );

/**
 * Yoast's per-post text fields, with the label editors know them by. Polylang copies all
 * eight into a new translation (polylang src/integrations/wpseo/wpseo.php:432-435, 505-515).
 *
 * @return array<string, string> Meta key => label.
 */
function hcwa_seo_text_meta_keys(): array {
	return array(
		'_yoast_wpseo_title'                 => __( 'SEO title', 'hcwa' ),
		'_yoast_wpseo_metadesc'              => __( 'Meta description', 'hcwa' ),
		'_yoast_wpseo_bctitle'               => __( 'Breadcrumbs title', 'hcwa' ),
		'_yoast_wpseo_focuskw'               => __( 'Focus keyphrase', 'hcwa' ),
		'_yoast_wpseo_opengraph-title'       => __( 'Social title', 'hcwa' ),
		'_yoast_wpseo_opengraph-description' => __( 'Social description', 'hcwa' ),
		'_yoast_wpseo_twitter-title'         => __( 'X title', 'hcwa' ),
		'_yoast_wpseo_twitter-description'   => __( 'X description', 'hcwa' ),
	);
}

/**
 * On the live site a Yoast title, description or social text that still contains a draft
 * marker is not printed (Yoast then falls back to its template, or prints nothing). The
 * publish guard and `wp hcwa draft-scan` report these fields too.
 *
 * @param mixed $text Text Yoast is about to print.
 * @return mixed
 */
function hcwa_seo_drop_draft_text( $text ) {
	return ( is_string( $text ) && hcwa_is_production() && hcwa_contains_draft( $text ) ) ? '' : $text;
}
foreach ( array( 'wpseo_title', 'wpseo_metadesc', 'wpseo_opengraph_title', 'wpseo_opengraph_desc', 'wpseo_twitter_title', 'wpseo_twitter_description' ) as $hcwa_seo_text_hook ) {
	add_filter( $hcwa_seo_text_hook, 'hcwa_seo_drop_draft_text', 99 );
}
unset( $hcwa_seo_text_hook );

/**
 * pll_copy_post_metas, after Polylang's own Yoast filter (priority 10, added on pll_init,
 * so after hcwa_pll_copy_post_metas()): a new translation does not start with the source
 * language's SEO texts, so an English page nobody re-edits gets Yoast's template built
 * from its own English title instead of a Vietnamese <title> and description
 * (07-BILINGUAL:46). Synchronizing ($sync true) is left alone.
 *
 * @param string[] $keys Meta keys Polylang will copy.
 * @param bool     $sync True when synchronizing, false when creating a translation.
 * @return string[]
 */
function hcwa_pll_drop_seo_text( $keys, $sync = false ) {
	if ( $sync ) {
		return $keys;
	}
	return array_values( array_diff( array_map( 'strval', (array) $keys ), array_keys( hcwa_seo_text_meta_keys() ) ) );
}
add_filter( 'pll_copy_post_metas', 'hcwa_pll_drop_seo_text', 15, 2 );
