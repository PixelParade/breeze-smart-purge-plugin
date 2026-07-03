<?php
/**
 * GitHub Releases update checker — private-repo / MainWP lane only.
 * Excluded from wordpress.org builds via .distignore; loaded only when BSP_GITHUB_TOKEN is set.
 *
 * @package Breeze_Smart_Purge
 */

if (!defined('ABSPATH')) {
	exit;
}

$bsp_main_plugin = dirname(__DIR__) . '/breeze-smart-purge.php';

add_filter('pre_set_site_transient_update_plugins', 'bsp_pre_set_github_plugin_update');
add_filter('plugins_api', 'bsp_plugins_api_github_info', 20, 3);
add_filter('upgrader_pre_download', 'bsp_github_authenticated_download', 10, 4);

function bsp_get_github_repo() {
	return defined('BSP_GITHUB_REPO') ? BSP_GITHUB_REPO : 'PixelParade/breeze-smart-purge-plugin';
}

function bsp_get_github_token() {
	return (defined('BSP_GITHUB_TOKEN') && BSP_GITHUB_TOKEN) ? BSP_GITHUB_TOKEN : '';
}

function bsp_github_request_args($args = array()) {
	$token = bsp_get_github_token();
	if ($token) {
		$args['headers']['Authorization'] = 'Bearer ' . $token;
		$args['headers']['Accept']        = 'application/vnd.github+json';
	}
	return $args;
}

function bsp_is_github_package_url($url) {
	return strpos($url, 'github.com') !== false || strpos($url, 'githubusercontent.com') !== false;
}

function bsp_fetch_latest_github_release() {
	$cached = get_transient('bsp_github_release');
	if (false !== $cached) {
		return $cached ? $cached : null;
	}

	$url      = 'https://api.github.com/repos/' . bsp_get_github_repo() . '/releases/latest';
	$response = wp_remote_get($url, bsp_github_request_args(array('timeout' => 15)));

	if (is_wp_error($response) || 200 !== (int) wp_remote_retrieve_response_code($response)) {
		set_transient('bsp_github_release', '', HOUR_IN_SECONDS);
		return null;
	}

	$data = json_decode(wp_remote_retrieve_body($response), true);
	if (empty($data['tag_name'])) {
		set_transient('bsp_github_release', '', HOUR_IN_SECONDS);
		return null;
	}

	$package = '';
	if (!empty($data['assets']) && is_array($data['assets'])) {
		foreach ($data['assets'] as $asset) {
			if (!empty($asset['name']) && 'breeze-smart-purge.zip' === $asset['name']) {
				$package = $asset['browser_download_url'];
				break;
			}
		}
	}

	$release = array(
		'version' => ltrim($data['tag_name'], 'vV'),
		'package' => $package,
		'url'     => !empty($data['html_url']) ? $data['html_url'] : 'https://github.com/' . bsp_get_github_repo(),
		'notes'   => !empty($data['body']) ? $data['body'] : '',
	);

	set_transient('bsp_github_release', $release, 12 * HOUR_IN_SECONDS);
	return $release;
}

function bsp_pre_set_github_plugin_update($transient) {
	global $bsp_main_plugin;

	if (!is_object($transient) || empty($transient->checked)) {
		return $transient;
	}

	$plugin_file     = plugin_basename($bsp_main_plugin);
	$current_version = isset($transient->checked[ $plugin_file ]) ? $transient->checked[ $plugin_file ] : '';
	$release         = bsp_fetch_latest_github_release();

	if (!$release || empty($release['version']) || empty($release['package'])) {
		return $transient;
	}

	$update = (object) array(
		'slug'         => 'breeze-smart-purge',
		'plugin'       => $plugin_file,
		'new_version'  => $release['version'],
		'url'          => $release['url'],
		'package'      => $release['package'],
		'tested'       => get_bloginfo('version'),
		'requires'     => '6.0',
		'requires_php' => '7.4',
	);

	if (version_compare($release['version'], $current_version, '>')) {
		$transient->response[ $plugin_file ] = $update;
	} else {
		$transient->no_update[ $plugin_file ] = $update;
	}

	return $transient;
}

function bsp_plugins_api_github_info($result, $action, $args) {
	if ('plugin_information' !== $action) {
		return $result;
	}
	if (empty($args->slug) || 'breeze-smart-purge' !== $args->slug) {
		return $result;
	}

	$release = bsp_fetch_latest_github_release();
	if (!$release) {
		return $result;
	}

	return (object) array(
		'name'          => 'Smart Purge for Breeze Cache',
		'slug'          => 'breeze-smart-purge',
		'version'       => $release['version'],
		'author'        => '<a href="https://pixelparade.co">PixelParade LLC</a>',
		'homepage'      => 'https://pixelparade.co',
		'requires'      => '6.0',
		'requires_php'  => '7.4',
		'download_link' => $release['package'],
		'sections'      => array(
			'description' => 'Intelligently purges CPT archives, taxonomies, and page-builder hub pages via Breeze and Cloudflare.',
			'changelog'   => !empty($release['notes']) ? wp_kses_post($release['notes']) : '',
		),
	);
}

function bsp_github_authenticated_download($reply, $package, $upgrader, $hook_extra = null) {
	global $bsp_main_plugin;

	if (false !== $reply) {
		return $reply;
	}
	if (!bsp_is_github_package_url($package)) {
		return $reply;
	}

	$plugin_file = plugin_basename($bsp_main_plugin);
	if (!empty($hook_extra['plugin']) && $hook_extra['plugin'] !== $plugin_file) {
		return $reply;
	}

	$token = bsp_get_github_token();
	if (!$token) {
		return $reply;
	}

	$response = wp_remote_get(
		$package,
		array(
			'timeout' => 300,
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Accept'        => 'application/octet-stream',
			),
		)
	);

	if (is_wp_error($response)) {
		return $response;
	}

	if (200 !== (int) wp_remote_retrieve_response_code($response)) {
		return new WP_Error(
			'bsp_github_download_failed',
			__('GitHub release download failed. Check BSP_GITHUB_TOKEN in wp-config.php.', 'breeze-smart-purge')
		);
	}

	$tmp = wp_tempnam($package);
	if (!$tmp) {
		return new WP_Error('bsp_temp_file', __('Could not create a temporary file for the update.', 'breeze-smart-purge'));
	}

	file_put_contents($tmp, wp_remote_retrieve_body($response));
	return $tmp;
}
