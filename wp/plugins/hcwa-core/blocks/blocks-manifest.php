<?php
/**
 * Every block folder's block.json as PHP, keyed by folder name. WordPress reads this one
 * file (kept by opcache) instead of reading and decoding each block.json on every request
 * (wp_register_block_metadata_collection(), includes/blocks.php).
 *
 * Generated: do not edit. Change a block.json, then run
 * `php wp/tests/blocks-manifest-build.php`; wp/tests/blocks-files-test.php fails while
 * the two differ.
 *
 * @package hcwa-core
 */

defined( 'ABSPATH' ) || exit;

return array(
	'announcement-bar' => array(
		'$schema'      => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion'   => 3,
		'name'         => 'hcwa/announcement-bar',
		'title'        => 'Announcement bar',
		'category'     => 'hcwa',
		'icon'         => 'megaphone',
		'description'  => 'The one-line announcement above the header. Edit it in Settings → HCWA.',
		'textdomain'   => 'hcwa',
		'supports'     => array(
			'html'     => false,
			'inserter' => false,
			'multiple' => false,
		),
		'editorScript' => 'file:./index.js',
		'render'       => 'file:./render.php',
	),
	'center-grid'      => array(
		'$schema'      => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion'   => 3,
		'name'         => 'hcwa/center-grid',
		'title'        => 'Center grid',
		'category'     => 'hcwa',
		'icon'         => 'building',
		'description'  => 'Cards for the centers HCWA runs, with approved photos only.',
		'textdomain'   => 'hcwa',
		'attributes'   => array(
			'limit' => array(
				'type'    => 'integer',
				'default' => 3,
			),
		),
		'supports'     => array(
			'html' => false,
		),
		'editorScript' => 'file:./index.js',
		'render'       => 'file:./render.php',
	),
	'donation-methods' => array(
		'$schema'      => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion'   => 3,
		'name'         => 'hcwa/donation-methods',
		'title'        => 'Donation methods',
		'category'     => 'hcwa',
		'icon'         => 'heart',
		'description'  => 'Bank transfer and giving from abroad. Shows only details HCWA finance has confirmed in Settings → HCWA.',
		'textdomain'   => 'hcwa',
		'supports'     => array(
			'html'     => false,
			'multiple' => false,
		),
		'editorScript' => 'file:./index.js',
		'render'       => 'file:./render.php',
	),
	'impact-strip'     => array(
		'$schema'      => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion'   => 3,
		'name'         => 'hcwa/impact-strip',
		'title'        => 'Impact strip',
		'category'     => 'hcwa',
		'icon'         => 'chart-bar',
		'description'  => 'Verified impact figures for the organization, a center or a program. Unverified figures never show on the live site.',
		'textdomain'   => 'hcwa',
		'attributes'   => array(
			'scopeType' => array(
				'type'    => 'string',
				'enum'    => array(
					'organization',
					'center',
					'program',
				),
				'default' => 'organization',
			),
			'scopeId'   => array(
				'type'    => 'integer',
				'default' => 0,
			),
			'limit'     => array(
				'type'    => 'integer',
				'default' => 4,
			),
			'showLink'  => array(
				'type'    => 'boolean',
				'default' => true,
			),
		),
		'usesContext'  => array(
			'postId',
			'postType',
		),
		'supports'     => array(
			'html' => false,
		),
		'editorScript' => 'file:./index.js',
		'render'       => 'file:./render.php',
	),
	'language-notice'  => array(
		'$schema'      => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion'   => 3,
		'name'         => 'hcwa/language-notice',
		'title'        => 'Language notice',
		'category'     => 'hcwa',
		'icon'         => 'translation',
		'description'  => 'Tells visitors that an article exists in one language only, with links to it and to the news page.',
		'textdomain'   => 'hcwa',
		'attributes'   => array(
			'forceLang' => array(
				'type'    => 'string',
				'enum'    => array(
					'',
					'vi',
					'en',
				),
				'default' => '',
			),
		),
		'usesContext'  => array(
			'postId',
			'postType',
		),
		'supports'     => array(
			'html' => false,
		),
		'editorScript' => 'file:./index.js',
		'render'       => 'file:./render.php',
	),
	'news-latest'      => array(
		'$schema'      => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion'   => 3,
		'name'         => 'hcwa/news-latest',
		'title'        => 'Latest news',
		'category'     => 'hcwa',
		'icon'         => 'megaphone',
		'description'  => 'A featured story and the latest posts in this language.',
		'textdomain'   => 'hcwa',
		'attributes'   => array(
			'featured' => array(
				'type'    => 'boolean',
				'default' => true,
			),
			'limit'    => array(
				'type'    => 'integer',
				'default' => 3,
			),
		),
		'supports'     => array(
			'html' => false,
		),
		'editorScript' => 'file:./index.js',
		'render'       => 'file:./render.php',
	),
	'partner-wall'     => array(
		'$schema'      => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion'   => 3,
		'name'         => 'hcwa/partner-wall',
		'title'        => 'Partner wall',
		'category'     => 'hcwa',
		'icon'         => 'groups',
		'description'  => 'One-tone partner logos. On the live site a logo shows only with the partner\'s written permission.',
		'textdomain'   => 'hcwa',
		'attributes'   => array(
			'homeOnly' => array(
				'type'    => 'boolean',
				'default' => true,
			),
			'limit'    => array(
				'type'    => 'integer',
				'default' => 12,
			),
		),
		'supports'     => array(
			'html' => false,
		),
		'editorScript' => 'file:./index.js',
		'render'       => 'file:./render.php',
	),
	'program-list'     => array(
		'$schema'      => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion'   => 3,
		'name'         => 'hcwa/program-list',
		'title'        => 'Program list',
		'category'     => 'hcwa',
		'icon'         => 'portfolio',
		'description'  => 'Rows of projects and programs, active ones first.',
		'textdomain'   => 'hcwa',
		'attributes'   => array(
			'status'   => array(
				'type'    => 'string',
				'enum'    => array(
					'any',
					'active',
					'completed',
				),
				'default' => 'any',
			),
			'limit'    => array(
				'type'    => 'integer',
				'default' => 4,
			),
			'centerId' => array(
				'type'    => 'integer',
				'default' => 0,
			),
		),
		'usesContext'  => array(
			'postId',
			'postType',
		),
		'supports'     => array(
			'html' => false,
		),
		'editorScript' => 'file:./index.js',
		'render'       => 'file:./render.php',
	),
	'report-list'      => array(
		'$schema'      => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion'   => 3,
		'name'         => 'hcwa/report-list',
		'title'        => 'Report list',
		'category'     => 'hcwa',
		'icon'         => 'media-document',
		'description'  => 'Reports with a language badge and file size, newest year first.',
		'textdomain'   => 'hcwa',
		'attributes'   => array(
			'reportType' => array(
				'type'    => 'string',
				'enum'    => array(
					'any',
					'annual',
					'financial',
					'program',
					'policy',
				),
				'default' => 'any',
			),
			'limit'      => array(
				'type'    => 'integer',
				'default' => 6,
			),
		),
		'supports'     => array(
			'html' => false,
		),
		'editorScript' => 'file:./index.js',
		'render'       => 'file:./render.php',
	),
);
