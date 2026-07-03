<?php
/**
 * PHPUnit bootstrap — stub WordPress helpers used by scanner-detection.php.
 */

if (!function_exists('wp_json_encode')) {
	function wp_json_encode($data) {
		return json_encode($data);
	}
}
