<?php
/**
 * Staging QA — persistent test CPT for Smart Purge scanner fixtures.
 *
 * Loaded by mu-plugin `ppspb-staging-test-cpt.php` on breeze-smart-purge.pixelparade.dev only.
 * Source of truth in git: scripts/staging/ppspb-test-cpt.php (excluded from plugin zips).
 *
 * @package Breeze_Smart_Purge
 */

add_action(
	'init',
	function () {
		if (post_type_exists('ppspb_test_project')) {
			return;
		}

		register_post_type(
			'ppspb_test_project',
			array(
				'labels'       => array(
					'name'          => 'BSP Test Projects',
					'singular_name' => 'BSP Test Project',
				),
				'public'       => true,
				'has_archive'  => true,
				'rewrite'      => array( 'slug' => 'ppspb-test-projects' ),
				'show_in_rest' => true,
				'supports'     => array( 'title', 'editor', 'thumbnail' ),
			)
		);
	},
	5
);
