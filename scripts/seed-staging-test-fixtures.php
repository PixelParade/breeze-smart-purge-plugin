<?php
/**
 * Seed staging / local test fixtures for Smart Purge scanner + purge verification.
 *
 * Usage (on a WordPress server with the plugin active):
 *   wp eval-file scripts/seed-staging-test-fixtures.php
 *
 * Idempotent: re-running updates existing fixture pages/posts by slug.
 *
 * The test CPT must persist across requests. On staging, `wp-content/novamira-sandbox/bsp-test-cpt.php`
 * registers `bsp_test_project`. For other environments, register the CPT via a snippet, mu-plugin, or ASE CCT.
 *
 * @package Breeze_Smart_Purge
 */

if (!defined('ABSPATH')) {
	exit("Run via WP-CLI: wp eval-file scripts/seed-staging-test-fixtures.php\n");
}

if (!function_exists('bsp_execute_auto_scanner')) {
	exit("Smart Purge for Breeze Cache must be active.\n");
}

/**
 * @param string $slug      Post slug.
 * @param string $post_type Post type.
 * @return WP_Post|null
 */
function bsp_seed_get_post_by_slug($slug, $post_type = 'page') {
	$posts = get_posts(
		array(
			'post_type'      => $post_type,
			'name'           => $slug,
			'post_status'    => 'any',
			'posts_per_page' => 1,
		)
	);
	return $posts ? $posts[0] : null;
}

/**
 * @param string $slug Post slug.
 * @param array  $args wp_insert_post args.
 * @return int Post ID.
 */
function bsp_seed_upsert_page($slug, array $args) {
	$existing = bsp_seed_get_post_by_slug($slug, 'page');
	$defaults = array(
		'post_type'   => 'page',
		'post_status' => 'publish',
		'post_name'   => $slug,
	);

	if ($existing) {
		$args['ID'] = $existing->ID;
		$post_id    = wp_update_post(array_merge($defaults, $args), true);
	} else {
		$post_id = wp_insert_post(array_merge($defaults, $args), true);
	}

	if (is_wp_error($post_id)) {
		if (defined('WP_CLI') && WP_CLI) {
			WP_CLI::error($post_id->get_error_message());
		}
		return 0;
	}

	return (int) $post_id;
}

/**
 * Register a public CPT used only for plugin QA (idempotent).
 */
function bsp_seed_register_test_cpt() {
	$slug = 'bsp_test_project';

	if (post_type_exists($slug)) {
		return $slug;
	}

	register_post_type(
		$slug,
		array(
			'labels'       => array(
				'name'          => 'BSP Test Projects',
				'singular_name' => 'BSP Test Project',
			),
			'public'       => true,
			'has_archive'  => true,
			'rewrite'      => array('slug' => 'bsp-test-projects'),
			'show_in_rest' => true,
			'supports'     => array('title', 'editor', 'thumbnail'),
		)
	);

	flush_rewrite_rules(false);
	return $slug;
}

function bsp_seed_create_sample_posts($cpt, $count = 3) {
	$ids = array();
	for ($i = 1; $i <= $count; $i++) {
		$slug     = 'bsp-test-project-' . $i;
		$existing = bsp_seed_get_post_by_slug($slug, $cpt);
		if ($existing) {
			$ids[] = (int) $existing->ID;
			continue;
		}

		$post_id = wp_insert_post(
			array(
				'post_type'    => $cpt,
				'post_status'  => 'publish',
				'post_title'   => 'BSP Test Project ' . $i,
				'post_name'    => $slug,
				'post_content' => '<p>Fixture post ' . $i . ' for Smart Purge QA.</p>',
			),
			true
		);

		if (!is_wp_error($post_id)) {
			$ids[] = (int) $post_id;
		}
	}
	return $ids;
}

$cpt = bsp_seed_register_test_cpt();
bsp_seed_create_sample_posts($cpt);

// Gutenberg hub — Query Loop block referencing the test CPT.
$gutenberg_content = '<!-- wp:heading --><h2>BSP Gutenberg Hub</h2><!-- /wp:heading -->'
	. '<!-- wp:query {"query":{"perPage":3,"postType":"' . $cpt . '","order":"desc","orderBy":"date"}} -->'
	. '<div class="wp-block-query"><!-- wp:post-template -->'
	. '<!-- wp:post-title {"isLink":true} /--><!-- wp:post-excerpt /-->'
	. '<!-- /wp:post-template --></div><!-- /wp:query -->';

$gutenberg_id = bsp_seed_upsert_page(
	'bsp-test-gutenberg-hub',
	array(
		'post_title'   => 'BSP Test — Gutenberg Hub',
		'post_content' => $gutenberg_content,
	)
);

// Simulated Beaver Builder hub (meta only — no BB plugin required for scanner).
$beaver_id = bsp_seed_upsert_page(
	'bsp-test-beaver-hub',
	array(
		'post_title'   => 'BSP Test — Beaver Builder Hub (simulated)',
		'post_content' => '<p>Scanner fixture: Beaver Builder post grid meta only.</p>',
	)
);
update_post_meta(
	$beaver_id,
	'_fl_builder_data',
	wp_json_encode(
		array(
			'node_1' => array(
				'settings' => array(
					'type'      => 'posts',
					'post_type' => $cpt,
					'layout'    => 'grid',
				),
			),
		)
	)
);
update_post_meta($beaver_id, '_fl_builder_enabled', '1');

// Simulated Elementor hub.
$elementor_id = bsp_seed_upsert_page(
	'bsp-test-elementor-hub',
	array(
		'post_title'   => 'BSP Test — Elementor Hub (simulated)',
		'post_content' => '',
	)
);
update_post_meta(
	$elementor_id,
	'_elementor_data',
	wp_json_encode(
		array(
			array(
				'widgetType' => 'posts',
				'settings'   => array('post_type' => $cpt),
			),
		)
	)
);

// Blog posts index helper (standard post type hub).
$blog_id = (int) get_option('page_for_posts');
if (!$blog_id) {
	$blog_id = bsp_seed_upsert_page(
		'bsp-test-blog',
		array(
			'post_title'   => 'BSP Test Blog',
			'post_content' => '<p>Posts index for purge tests.</p>',
		)
	);
	update_option('page_for_posts', $blog_id);
}

// Run scanner and report.
$settings = wp_parse_args(get_option('bsp_settings', array()), array('hide_utility' => 'yes', 'force_sync' => 'yes'));
$log      = bsp_execute_auto_scanner($settings);
$map      = get_option('bsp_scanned_map', array());

$summary = array(
	'cpt'              => $cpt,
	'fixture_pages'    => array(
		'gutenberg' => get_permalink($gutenberg_id),
		'beaver'    => get_permalink($beaver_id),
		'elementor' => get_permalink($elementor_id),
		'blog'      => $blog_id ? get_permalink($blog_id) : '',
	),
	'scanned_map_cpt'  => isset($map[ $cpt ]) ? $map[ $cpt ] : array(),
	'scanned_map_post' => isset($map['post']) ? $map['post'] : array(),
);

if (defined('WP_CLI') && WP_CLI) {
	WP_CLI::log($log);
	WP_CLI::success(wp_json_encode($summary, JSON_PRETTY_PRINT));
} else {
	echo $log . "\n";
	echo wp_json_encode($summary, JSON_PRETTY_PRINT) . "\n";
}
