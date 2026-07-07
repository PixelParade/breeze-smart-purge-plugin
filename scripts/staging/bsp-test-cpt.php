<?php
/**
 * Staging QA — persistent test CPT for Smart Purge scanner fixtures.
 *
 * Loaded by mu-plugin `bsp-staging-test-cpt.php` on breeze-smart-purge.pixelparade.dev only.
 * Source of truth in git: scripts/staging/bsp-test-cpt.php (excluded from plugin zips).
 *
 * @package Breeze_Smart_Purge
 */

add_action(
	'init',
	function () {
		if (post_type_exists('bsp_test_project')) {
			return;
		}

		register_post_type(
			'bsp_test_project',
			array(
				'labels'       => array(
					'name'          => 'BSP Test Projects',
					'singular_name' => 'BSP Test Project',
				),
				'public'       => true,
				'has_archive'  => true,
				'rewrite'      => array( 'slug' => 'bsp-test-projects' ),
				'show_in_rest' => true,
				'supports'     => array( 'title', 'editor', 'thumbnail' ),
			)
		);
	},
	5
);
