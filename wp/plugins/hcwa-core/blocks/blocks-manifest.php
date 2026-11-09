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
	'contact-reach'    => array(
		'$schema'      => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion'   => 3,
		'name'         => 'hcwa/contact-reach',
		'title'        => 'Contact form and office',
		'category'     => 'hcwa',
		'icon'         => 'email',
		'description'  => 'The contact form on staging, and the office address and lines HCWA has confirmed.',
		'textdomain'   => 'hcwa',
		'attributes'   => array(
			'formTitle'   => array(
				'type'    => 'string',
				'default' => '',
				'role'    => 'content',
			),
			'officeTitle' => array(
				'type'    => 'string',
				'default' => '',
				'role'    => 'content',
			),
			'chat'        => array(
				'type'    => 'string',
				'default' => '',
				'role'    => 'content',
			),
			'chatPending' => array(
				'type'    => 'string',
				'default' => '',
				'role'    => 'content',
			),
		),
		'supports'     => array(
			'html'     => false,
			'reusable' => false,
		),
		'editorScript' => 'file:./index.js',
		'render'       => 'file:./render.php',
	),
	'contact-units'    => array(
		'$schema'      => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion'   => 3,
		'name'         => 'hcwa/contact-units',
		'title'        => 'Contact: centers',
		'category'     => 'hcwa',
		'icon'         => 'location',
		'description'  => 'Each center with its way in. Little Rose has no address of its own.',
		'textdomain'   => 'hcwa',
		'attributes'   => array(
			'littleRose'    => array(
				'type'    => 'string',
				'default' => '',
				'role'    => 'content',
			),
			'centerPending' => array(
				'type'    => 'string',
				'default' => '',
				'role'    => 'content',
			),
		),
		'supports'     => array(
			'html'     => false,
			'reusable' => false,
		),
		'editorScript' => 'file:./index.js',
		'render'       => 'file:./render.php',
	),
	'corridor'         => array(
		'$schema'      => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion'   => 3,
		'name'         => 'hcwa/corridor',
		'title'        => 'Centers corridor',
		'category'     => 'hcwa',
		'icon'         => 'building',
		'description'  => 'One door per published center, from the center pages. Little Rose follows the safeguarding rules.',
		'textdomain'   => 'hcwa',
		'attributes'   => array(
			'intro' => array(
				'type'    => 'string',
				'default' => '',
				'role'    => 'content',
			),
		),
		'supports'     => array(
			'html'     => false,
			'reusable' => false,
		),
		'editorScript' => 'file:./index.js',
		'render'       => 'file:./render.php',
	),
	'day'              => array(
		'$schema'      => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion'   => 3,
		'name'         => 'hcwa/day',
		'title'        => 'Day',
		'category'     => 'hcwa',
		'icon'         => 'image-filter',
		'description'  => 'The day gradient behind the sections of an inner page.',
		'textdomain'   => 'hcwa',
		'attributes'   => array(
			'variant'      => array(
				'type'    => 'string',
				'enum'    => array(
					'hub',
					'involve',
					'donate',
					'contact',
					'gap',
				),
				'default' => 'involve',
			),
			'templateLock' => array(
				'type'    => 'string',
				'enum'    => array(
					'all',
					'contentOnly',
				),
				'default' => 'contentOnly',
			),
		),
		'supports'     => array(
			'html'     => false,
			'inserter' => false,
			'reusable' => false,
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
		'icon'         => 'money-alt',
		'description'  => 'The transfer slip and the "tell us you gave" form. Only details HCWA finance has confirmed ever show.',
		'textdomain'   => 'hcwa',
		'attributes'   => array(
			'howTitle'        => array(
				'type'    => 'string',
				'default' => '',
				'role'    => 'content',
			),
			'tabsLabel'       => array(
				'type'    => 'string',
				'default' => '',
				'role'    => 'content',
			),
			'inVietnam'       => array(
				'type'    => 'string',
				'default' => '',
				'role'    => 'content',
			),
			'inVietnamShort'  => array(
				'type'    => 'string',
				'default' => '',
				'role'    => 'content',
			),
			'fromAbroad'      => array(
				'type'    => 'string',
				'default' => '',
				'role'    => 'content',
			),
			'fromAbroadShort' => array(
				'type'    => 'string',
				'default' => '',
				'role'    => 'content',
			),
			'qr'              => array(
				'type'    => 'string',
				'default' => '',
				'role'    => 'content',
			),
			'pendingNote'     => array(
				'type'    => 'string',
				'default' => '',
				'role'    => 'content',
			),
			'paypal'          => array(
				'type'    => 'string',
				'default' => '',
				'role'    => 'content',
			),
			'toCenterTitle'   => array(
				'type'    => 'string',
				'default' => '',
				'role'    => 'content',
			),
			'toCenter'        => array(
				'type'    => 'string',
				'default' => '',
				'role'    => 'content',
			),
			'tellTitle'       => array(
				'type'    => 'string',
				'default' => '',
				'role'    => 'content',
			),
			'tellLead'        => array(
				'type'    => 'string',
				'default' => '',
				'role'    => 'content',
			),
			'amount'          => array(
				'type'    => 'string',
				'default' => '',
				'role'    => 'content',
			),
			'amountError'     => array(
				'type'    => 'string',
				'default' => '',
				'role'    => 'content',
			),
			'giftFor'         => array(
				'type'    => 'string',
				'default' => '',
				'role'    => 'content',
			),
			'giftForAny'      => array(
				'type'    => 'string',
				'default' => '',
				'role'    => 'content',
			),
			'thanks'          => array(
				'type'    => 'string',
				'default' => '',
				'role'    => 'content',
			),
			'bankFields'      => array(
				'type'    => 'array',
				'default' => array(),
				'role'    => 'content',
				'items'   => array(
					'type' => 'object',
				),
			),
			'abroadFields'    => array(
				'type'    => 'array',
				'default' => array(),
				'role'    => 'content',
				'items'   => array(
					'type' => 'object',
				),
			),
		),
		'supports'     => array(
			'html'     => false,
			'reusable' => false,
		),
		'editorScript' => 'file:./index.js',
		'render'       => 'file:./render.php',
	),
	'dusk-band'        => array(
		'$schema'      => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion'   => 3,
		'name'         => 'hcwa/dusk-band',
		'title'        => 'Evening band',
		'category'     => 'hcwa',
		'icon'         => 'format-status',
		'description'  => 'The dusk band at the end of a page: a heading, the contact lines and buttons.',
		'textdomain'   => 'hcwa',
		'attributes'   => array(
			'anchor'  => array(
				'type'    => 'string',
				'default' => '',
			),
			'titleId' => array(
				'type'    => 'string',
				'default' => '',
			),
			'title'   => array(
				'type'    => 'string',
				'default' => '',
				'role'    => 'content',
			),
			'lines'   => array(
				'type'    => 'array',
				'default' => array(),
				'items'   => array(
					'type' => 'string',
					'enum' => array(
						'phone',
						'email',
						'sponsorship',
					),
				),
			),
			'actions' => array(
				'type'    => 'array',
				'default' => array(),
				'items'   => array(
					'type' => 'string',
					'enum' => array(
						'donate',
						'volunteer',
						'partner',
						'contact',
					),
				),
			),
			'note'    => array(
				'type'    => 'string',
				'default' => '',
				'role'    => 'content',
			),
		),
		'supports'     => array(
			'html'     => false,
			'reusable' => false,
		),
		'editorScript' => 'file:./index.js',
		'render'       => 'file:./render.php',
	),
	'faq'              => array(
		'$schema'      => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion'   => 3,
		'name'         => 'hcwa/faq',
		'title'        => 'Questions',
		'category'     => 'hcwa',
		'icon'         => 'editor-help',
		'description'  => 'Common questions with one answer each.',
		'textdomain'   => 'hcwa',
		'attributes'   => array(
			'title'  => array(
				'type'    => 'string',
				'default' => '',
				'role'    => 'content',
			),
			'faq'    => array(
				'type'    => 'array',
				'default' => array(),
				'role'    => 'content',
				'items'   => array(
					'type'       => 'object',
					'properties' => array(
						'q' => array(
							'type' => 'string',
						),
						'a' => array(
							'type' => 'string',
						),
					),
				),
			),
			'answer' => array(
				'type'    => 'string',
				'default' => '',
				'role'    => 'content',
			),
		),
		'supports'     => array(
			'html'     => false,
			'reusable' => false,
		),
		'editorScript' => 'file:./index.js',
		'render'       => 'file:./render.php',
	),
	'gifts'            => array(
		'$schema'      => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion'   => 3,
		'name'         => 'hcwa/gifts',
		'title'        => 'Gift examples',
		'category'     => 'hcwa',
		'icon'         => 'awards',
		'description'  => 'What a gift does. Staging only until HCWA sets the amounts.',
		'textdomain'   => 'hcwa',
		'attributes'   => array(
			'title' => array(
				'type'    => 'string',
				'default' => '',
				'role'    => 'content',
			),
			'gifts' => array(
				'type'    => 'array',
				'default' => array(),
				'role'    => 'content',
				'items'   => array(
					'type'       => 'object',
					'properties' => array(
						'amount' => array(
							'type' => 'string',
						),
						'text'   => array(
							'type' => 'string',
						),
					),
				),
			),
		),
		'supports'     => array(
			'html'     => false,
			'reusable' => false,
		),
		'editorScript' => 'file:./index.js',
		'render'       => 'file:./render.php',
	),
	'help'             => array(
		'$schema'      => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion'   => 3,
		'name'         => 'hcwa/help',
		'title'        => 'How you can help',
		'category'     => 'hcwa',
		'icon'         => 'heart',
		'description'  => 'The four ways to help as louver slats.',
		'textdomain'   => 'hcwa',
		'attributes'   => array(
			'title' => array(
				'type'    => 'string',
				'default' => '',
				'role'    => 'content',
			),
			'ways'  => array(
				'type'    => 'array',
				'default' => array(),
				'role'    => 'content',
				'items'   => array(
					'type'       => 'object',
					'properties' => array(
						'id'    => array(
							'type' => 'string',
						),
						'title' => array(
							'type' => 'string',
						),
						'text'  => array(
							'type' => 'string',
						),
					),
				),
			),
		),
		'supports'     => array(
			'html'     => false,
			'reusable' => false,
		),
		'editorScript' => 'file:./index.js',
		'render'       => 'file:./render.php',
	),
	'hero'             => array(
		'$schema'      => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion'   => 3,
		'name'         => 'hcwa/hero',
		'title'        => 'Hero',
		'category'     => 'hcwa',
		'icon'         => 'format-image',
		'description'  => 'The first screen of the home page: heading, intro and two buttons.',
		'textdomain'   => 'hcwa',
		'attributes'   => array(
			'title' => array(
				'type'    => 'string',
				'default' => '',
				'role'    => 'content',
			),
			'intro' => array(
				'type'    => 'string',
				'default' => '',
				'role'    => 'content',
			),
		),
		'supports'     => array(
			'html'     => false,
			'reusable' => false,
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
			'title'     => array(
				'type'    => 'string',
				'default' => '',
				'role'    => 'content',
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
	'lit-header'       => array(
		'$schema'      => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion'   => 3,
		'name'         => 'hcwa/lit-header',
		'title'        => 'Lit header',
		'category'     => 'hcwa',
		'icon'         => 'cover-image',
		'description'  => 'Page header with a picture from the world, the breadcrumb, the heading and an intro.',
		'textdomain'   => 'hcwa',
		'attributes'   => array(
			'still'   => array(
				'type'    => 'string',
				'default' => '',
			),
			'size'    => array(
				'type'    => 'string',
				'enum'    => array(
					'',
					'display',
					'program',
				),
				'default' => '',
			),
			'title'   => array(
				'type'    => 'string',
				'default' => '',
				'role'    => 'content',
			),
			'intro'   => array(
				'type'    => 'string',
				'default' => '',
				'role'    => 'content',
			),
			'actions' => array(
				'type'    => 'string',
				'enum'    => array(
					'',
					'reach',
				),
				'default' => '',
			),
			'callUs'  => array(
				'type'    => 'string',
				'default' => '',
				'role'    => 'content',
			),
			'writeUs' => array(
				'type'    => 'string',
				'default' => '',
				'role'    => 'content',
			),
		),
		'usesContext'  => array(
			'postId',
			'postType',
		),
		'supports'     => array(
			'html'     => false,
			'reusable' => false,
		),
		'editorScript' => 'file:./index.js',
		'render'       => 'file:./render.php',
	),
	'louvers'          => array(
		'$schema'      => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion'   => 3,
		'name'         => 'hcwa/louvers',
		'title'        => 'Ways to help',
		'category'     => 'hcwa',
		'icon'         => 'list-view',
		'description'  => 'The four ways to help as louver slats.',
		'textdomain'   => 'hcwa',
		'attributes'   => array(
			'ways' => array(
				'type'    => 'array',
				'default' => array(),
				'role'    => 'content',
				'items'   => array(
					'type'       => 'object',
					'properties' => array(
						'id'    => array(
							'type' => 'string',
						),
						'title' => array(
							'type' => 'string',
						),
						'text'  => array(
							'type' => 'string',
						),
					),
				),
			),
		),
		'supports'     => array(
			'html'     => false,
			'reusable' => false,
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
		'description'  => 'The three latest posts in this language, and a featured story on staging.',
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
			'html'     => false,
			'reusable' => false,
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
		'description'  => 'Approved one-tone partner logos, shown once at least four are allowed.',
		'textdomain'   => 'hcwa',
		'attributes'   => array(
			'title'    => array(
				'type'    => 'string',
				'default' => '',
				'role'    => 'content',
			),
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
			'html'     => false,
			'reusable' => false,
		),
		'editorScript' => 'file:./index.js',
		'render'       => 'file:./render.php',
	),
	'passage'          => array(
		'$schema'      => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion'   => 3,
		'name'         => 'hcwa/passage',
		'title'        => 'What we do',
		'category'     => 'hcwa',
		'icon'         => 'format-quote',
		'description'  => 'The passage into the light, with the statement read word by word.',
		'textdomain'   => 'hcwa',
		'attributes'   => array(
			'title'     => array(
				'type'    => 'string',
				'default' => '',
				'role'    => 'content',
			),
			'statement' => array(
				'type'    => 'string',
				'default' => '',
				'role'    => 'content',
			),
		),
		'supports'     => array(
			'html'     => false,
			'reusable' => false,
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
			'programId'  => array(
				'type'    => 'integer',
				'default' => 0,
			),
		),
		'supports'     => array(
			'html' => false,
		),
		'editorScript' => 'file:./index.js',
		'render'       => 'file:./render.php',
	),
	'sections'         => array(
		'$schema'      => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion'   => 3,
		'name'         => 'hcwa/sections',
		'title'        => 'Page sections',
		'category'     => 'hcwa',
		'icon'         => 'layout',
		'description'  => 'The locked sections of a page built from the prototype. Change the words inside each section; the sections themselves stay in place.',
		'textdomain'   => 'hcwa',
		'attributes'   => array(
			'templateLock' => array(
				'type'    => 'string',
				'enum'    => array(
					'all',
					'contentOnly',
				),
				'default' => 'contentOnly',
			),
		),
		'supports'     => array(
			'html'     => false,
			'inserter' => false,
			'multiple' => false,
			'reusable' => false,
			'lock'     => false,
		),
		'editorScript' => 'file:./index.js',
		'render'       => 'file:./render.php',
	),
	'shutters'         => array(
		'$schema'      => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion'   => 3,
		'name'         => 'hcwa/shutters',
		'title'        => 'Shutters',
		'category'     => 'hcwa',
		'icon'         => 'align-wide',
		'description'  => 'The shutters opening onto the ways to help. Pictures only.',
		'textdomain'   => 'hcwa',
		'supports'     => array(
			'html'     => false,
			'reusable' => false,
		),
		'editorScript' => 'file:./index.js',
		'render'       => 'file:./render.php',
	),
	'sun-head'         => array(
		'$schema'      => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion'   => 3,
		'name'         => 'hcwa/sun-head',
		'title'        => 'Donate header',
		'category'     => 'hcwa',
		'icon'         => 'money-alt',
		'description'  => 'The Donate page header with its jump links.',
		'textdomain'   => 'hcwa',
		'attributes'   => array(
			'intro'      => array(
				'type'    => 'string',
				'default' => '',
				'role'    => 'content',
			),
			'go'         => array(
				'type'    => 'string',
				'default' => '',
				'role'    => 'content',
			),
			'jumpAbroad' => array(
				'type'    => 'string',
				'default' => '',
				'role'    => 'content',
			),
			'jumpTell'   => array(
				'type'    => 'string',
				'default' => '',
				'role'    => 'content',
			),
		),
		'usesContext'  => array(
			'postId',
			'postType',
		),
		'supports'     => array(
			'html'     => false,
			'reusable' => false,
		),
		'editorScript' => 'file:./index.js',
		'render'       => 'file:./render.php',
	),
	'work-hub'         => array(
		'$schema'      => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion'   => 3,
		'name'         => 'hcwa/work-hub',
		'title'        => 'Our Work hub',
		'category'     => 'hcwa',
		'icon'         => 'networking',
		'description'  => 'The filters, the wall of center doors and the program rows.',
		'textdomain'   => 'hcwa',
		'attributes'   => array(
			'centersLead'  => array(
				'type'    => 'string',
				'default' => '',
				'role'    => 'content',
			),
			'programsLead' => array(
				'type'    => 'string',
				'default' => '',
				'role'    => 'content',
			),
		),
		'supports'     => array(
			'html'     => false,
			'reusable' => false,
		),
		'editorScript' => 'file:./index.js',
		'render'       => 'file:./render.php',
	),
	'work-sheet'       => array(
		'$schema'      => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion'   => 3,
		'name'         => 'hcwa/work-sheet',
		'title'        => 'Areas of work',
		'category'     => 'hcwa',
		'icon'         => 'grid-view',
		'description'  => 'The four focus areas as a floor of cement tiles, the figures and the programs.',
		'textdomain'   => 'hcwa',
		'attributes'   => array(
			'focusTitle' => array(
				'type'    => 'string',
				'default' => '',
				'role'    => 'content',
			),
		),
		'supports'     => array(
			'html'     => false,
			'reusable' => false,
		),
		'editorScript' => 'file:./index.js',
		'render'       => 'file:./render.php',
	),
);
