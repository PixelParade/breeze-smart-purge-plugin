<?php

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__) . '/includes/scanner-detection.php';

/**
 * Scanner detection unit tests — no WordPress bootstrap required.
 */
class ScannerDetectionTest extends TestCase {

	public function test_normalize_builder_meta_handles_strings_and_arrays() {
		$this->assertSame('', bsp_normalize_builder_meta(''));
		$this->assertSame('{"post_type":"project"}', bsp_normalize_builder_meta(array('post_type' => 'project')));
		$this->assertSame('plain', bsp_normalize_builder_meta('plain'));
	}

	public function test_detects_gutenberg_query_block_reference() {
		$content = '<!-- wp:query {"query":{"postType":"bsp_test_project"}} /-->';
		$found   = bsp_detect_post_type_hub_builders(
			array(
				'post_type' => 'bsp_test_project',
				'content'   => $content,
			)
		);

		$this->assertContains('Gutenberg/Shortcode', $found);
	}

	public function test_detects_elementor_explicit_post_type() {
		$found = bsp_detect_post_type_hub_builders(
			array(
				'post_type' => 'portfolio',
				'elementor' => '{"settings":{"post_type":"portfolio"}}',
			)
		);

		$this->assertContains('Elementor', $found);
	}

	public function test_detects_elementor_implicit_posts_widget() {
		$found = bsp_detect_post_type_hub_builders(
			array(
				'post_type' => 'post',
				'elementor' => '{"widgetType":"posts-grid"}',
			)
		);

		$this->assertContains('Elementor (Implicit Posts)', $found);
	}

	public function test_detects_bricks_query_loop() {
		$found = bsp_detect_post_type_hub_builders(
			array(
				'post_type' => 'event',
				'bricks'    => '{"query":{"post_type":"event"}}',
			)
		);

		$this->assertContains('Bricks', $found);
	}

	public function test_detects_beaver_builder_post_type_string() {
		$found = bsp_detect_post_type_hub_builders(
			array(
				'post_type' => 'bsp_test_project',
				'beaver'    => '{"settings":{"type":"posts","post_type":"bsp_test_project"}}',
			)
		);

		$this->assertContains('Beaver Builder', $found);
	}

	public function test_detects_oxygen_json_reference() {
		$found = bsp_detect_post_type_hub_builders(
			array(
				'post_type' => 'team',
				'oxygen'    => '{"query":{"postType":"team"}}',
			)
		);

		$this->assertContains('Oxygen', $found);
	}

	public function test_returns_empty_when_no_match() {
		$found = bsp_detect_post_type_hub_builders(
			array(
				'post_type' => 'orphan_cpt',
				'content'   => '<p>Hello world</p>',
			)
		);

		$this->assertSame(array(), $found);
	}
}
