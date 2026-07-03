<?php
/**
 * Pure scanner detection helpers — unit-testable without WordPress bootstrap.
 *
 * @package Breeze_Smart_Purge
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Normalize builder meta for regex scanning.
 *
 * @param mixed $meta Post meta value.
 * @return string
 */
function bsp_normalize_builder_meta($meta) {
	if (empty($meta)) {
		return '';
	}
	if (is_string($meta)) {
		return stripslashes($meta);
	}
	if (is_array($meta) || is_object($meta)) {
		return wp_json_encode($meta);
	}
	return '';
}

/**
 * JSON pattern shared by Elementor, Bricks, Oxygen, and Beaver Builder scanners.
 *
 * @param string $post_type Post type slug.
 * @return string PCRE pattern (with delimiters).
 */
function bsp_build_post_type_json_regex($post_type) {
	return '/"[a-zA-Z0-9_-]*(?:post_type|postType|source|query)"\s*:\s*(?:\[[^\]]*?)?"' . preg_quote($post_type, '/') . '"/i';
}

/**
 * Detect which page builders reference a post type on a hub page.
 *
 * @param array $context {
 *     @type string $post_type   Post type slug.
 *     @type string $content     Raw post_content.
 *     @type string $elementor   Normalized Elementor JSON.
 *     @type string $bricks      Normalized Bricks JSON.
 *     @type string $oxygen      Normalized Oxygen JSON.
 *     @type string $beaver      Normalized Beaver Builder JSON.
 * }
 * @return string[] Human-readable builder labels (may be empty).
 */
function bsp_detect_post_type_hub_builders(array $context) {
	$post_type = isset($context['post_type']) ? (string) $context['post_type'] : '';
	if ('' === $post_type) {
		return array();
	}

	$content   = isset($context['content']) ? (string) $context['content'] : '';
	$elementor = isset($context['elementor']) ? (string) $context['elementor'] : '';
	$bricks    = isset($context['bricks']) ? (string) $context['bricks'] : '';
	$oxygen    = isset($context['oxygen']) ? (string) $context['oxygen'] : '';
	$beaver    = isset($context['beaver']) ? (string) $context['beaver'] : '';

	$found_builders = array();
	$json_regex     = bsp_build_post_type_json_regex($post_type);
	$post_type_quoted = preg_quote($post_type, '/');

	// WPBakery / Visual Composer shortcodes in post_content.
	if (preg_match('/\[vc_[^\]]*post_type=[\'"]' . $post_type_quoted . '[\'"]/i', $content)) {
		$found_builders[] = 'WPBakery';
	}

	// Divi Builder shortcodes in post_content.
	if (preg_match('/\[et_pb_[^\]]*post_type=[\'"]' . $post_type_quoted . '[\'"]/i', $content)) {
		$found_builders[] = 'Divi';
	}

	if (empty($found_builders)
		&& (preg_match('/post_type=[\'"]' . $post_type_quoted . '[\'"]/i', $content)
		|| strpos($content, '"postType":"' . $post_type . '"') !== false)) {
		$found_builders[] = 'Gutenberg/Shortcode';
	}

	if (preg_match($json_regex, $elementor)) {
		$found_builders[] = 'Elementor';
	} elseif ('post' === $post_type
		&& preg_match('/"widgetType"\s*:\s*"[a-zA-Z0-9_-]*(?:post|loop|blog|magazine)[a-zA-Z0-9_-]*"/i', $elementor)) {
		$found_builders[] = 'Elementor (Implicit Posts)';
	}

	if (preg_match($json_regex, $bricks)) {
		$found_builders[] = 'Bricks';
	} elseif ('post' === $post_type
		&& strpos($bricks, '"hasLoop":true') !== false
		&& preg_match_all('/"query"\s*:\s*\{([^{}]*)\}/i', $bricks, $matches)) {
		foreach ($matches[1] as $query_settings) {
			if (strpos($query_settings, '"post_type"') === false && strpos($query_settings, '"postType"') === false) {
				$found_builders[] = 'Bricks (Implicit Posts)';
				break;
			}
		}
	}

	if (preg_match($json_regex, $oxygen)) {
		$found_builders[] = 'Oxygen';
	}

	if (preg_match($json_regex, $beaver)
		|| (strpos($beaver, '"' . $post_type . '"') !== false && strpos($beaver, 'post_type') !== false)) {
		$found_builders[] = 'Beaver Builder';
	}

	return $found_builders;
}
