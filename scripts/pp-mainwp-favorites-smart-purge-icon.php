<?php
/**
 * MainWP Dashboard — show Smart Purge icon in Favorites and plugin lists.
 *
 * Copy to wp-content/mu-plugins/ on mainwp.pixelparade.co (keep this filename).
 * Child sites do not need this file; they use icons bundled in the plugin zip.
 *
 * @package Smart_Purge_For_Breeze_Cache
 */

if (!defined('ABSPATH')) {
	exit;
}

define('PP_BSP_FAVORITES_SLUG', 'smart-purge-for-breeze-cache');
define(
	'PP_BSP_FAVORITES_ICON_1X',
	'https://github.com/PixelParade/breeze-smart-purge-plugin/releases/latest/download/icon-128x128.png'
);
define(
	'PP_BSP_FAVORITES_ICON_2X',
	'https://github.com/PixelParade/breeze-smart-purge-plugin/releases/latest/download/icon-256x256.png'
);

/**
 * @param string $slug Plugin slug.
 */
function pp_bsp_favorites_is_smart_purge_slug($slug) {
	$slug = (string) $slug;
	return PP_BSP_FAVORITES_SLUG === $slug
		|| false !== strpos($slug, PP_BSP_FAVORITES_SLUG);
}

/**
 * @param array<string, mixed>|object $data Favorite item payload.
 */
function pp_bsp_favorites_is_smart_purge_item($data) {
	$blob = strtolower(wp_json_encode($data));
	return false !== strpos($blob, PP_BSP_FAVORITES_SLUG)
		|| false !== strpos($blob, 'smart purge for breeze');
}

/**
 * @return string
 */
function pp_bsp_favorites_icon_html() {
	return '<img src="' . esc_url(PP_BSP_FAVORITES_ICON_1X) . '" class="pp-bsp-favorites-icon" alt="" width="128" height="128" />';
}

/**
 * @return array<string, string>
 */
function pp_bsp_favorites_icon_set() {
	return array(
		'1x'      => PP_BSP_FAVORITES_ICON_1X,
		'2x'      => PP_BSP_FAVORITES_ICON_2X,
		'default' => PP_BSP_FAVORITES_ICON_1X,
	);
}

/**
 * Seed MainWP's plugin icon cache (Manage Plugins + Favorites use this slug).
 */
function pp_bsp_favorites_seed_icon_cache() {
	if (!is_admin()
		|| !class_exists('MainWP\Dashboard\MainWP_System_Utility')
		|| !class_exists('MainWP\Dashboard\MainWP_DB')) {
		return;
	}

	$cached = \MainWP\Dashboard\MainWP_DB::instance()->get_general_option('plugins_icons', 'array');
	if (is_array($cached)
		&& !empty($cached[ PP_BSP_FAVORITES_SLUG ])
		&& (
			!empty($cached[ PP_BSP_FAVORITES_SLUG ]['path'])
			|| !empty($cached[ PP_BSP_FAVORITES_SLUG ]['path_custom'])
		)) {
		return;
	}

	\MainWP\Dashboard\MainWP_System_Utility::update_cached_icons(
		PP_BSP_FAVORITES_ICON_1X,
		PP_BSP_FAVORITES_SLUG,
		'plugin'
	);
}
add_action('admin_init', 'pp_bsp_favorites_seed_icon_cache', 20);

/**
 * @param string $icon  Existing icon HTML.
 * @param string $slug  Plugin slug.
 * @param string $type  plugin|theme.
 */
function pp_bsp_favorites_filter_plugin_theme_icon($icon, $slug, $type) {
	if ('plugin' !== $type || !pp_bsp_favorites_is_smart_purge_slug($slug) || !empty($icon)) {
		return $icon;
	}
	return pp_bsp_favorites_icon_html();
}
add_filter('mainwp_get_plugin_theme_icon', 'pp_bsp_favorites_filter_plugin_theme_icon', 10, 3);

/**
 * @param string $icon Existing icon HTML.
 * @param string $slug Plugin slug.
 */
function pp_bsp_favorites_filter_plugin_icon($icon, $slug) {
	if (!pp_bsp_favorites_is_smart_purge_slug($slug) || !empty($icon)) {
		return $icon;
	}
	return pp_bsp_favorites_icon_html();
}
add_filter('mainwp_get_plugin_icon', 'pp_bsp_favorites_filter_plugin_icon', 10, 2);

/**
 * @param array<string, mixed> $data Item data while Favorites parses a zip/URL.
 */
function pp_bsp_favorites_process_item_data($data) {
	if (!is_array($data) || !pp_bsp_favorites_is_smart_purge_item($data)) {
		return $data;
	}

	$icons = pp_bsp_favorites_icon_set();
	$data['icon']     = PP_BSP_FAVORITES_ICON_1X;
	$data['icon_url'] = PP_BSP_FAVORITES_ICON_1X;
	$data['icons']    = $icons;

	pp_bsp_favorites_seed_icon_cache();

	return $data;
}
add_filter('mainwp_favorites_process_item_data', 'pp_bsp_favorites_process_item_data', 10, 1);

/**
 * @param array<string, mixed>|object $info    Favorite item info.
 * @param int|string                  $item_id Favorite row ID.
 */
function pp_bsp_favorites_get_item_info($info, $item_id = 0) {
	if (!pp_bsp_favorites_is_smart_purge_item($info)) {
		return $info;
	}

	$icons = pp_bsp_favorites_icon_set();

	if (is_object($info)) {
		$info->icon     = PP_BSP_FAVORITES_ICON_1X;
		$info->icon_url = PP_BSP_FAVORITES_ICON_1X;
		$info->icons    = $icons;
		return $info;
	}

	if (is_array($info)) {
		$info['icon']     = PP_BSP_FAVORITES_ICON_1X;
		$info['icon_url'] = PP_BSP_FAVORITES_ICON_1X;
		$info['icons']    = $icons;
	}

	return $info;
}
add_filter('mainwp_favorites_get_item_info', 'pp_bsp_favorites_get_item_info', 10, 2);

/**
 * @param array<int, array<string, mixed>> $items Favorites list rows.
 */
function pp_bsp_favorites_items_list($items) {
	if (!is_array($items)) {
		return $items;
	}

	$icons = pp_bsp_favorites_icon_set();

	foreach ($items as $key => $item) {
		if (!is_array($item) || !pp_bsp_favorites_is_smart_purge_item($item)) {
			continue;
		}
		$items[ $key ]['icon']     = PP_BSP_FAVORITES_ICON_1X;
		$items[ $key ]['icon_url'] = PP_BSP_FAVORITES_ICON_1X;
		$items[ $key ]['icons']    = $icons;
	}

	return $items;
}
add_filter('mainwp_favorites_items_list', 'pp_bsp_favorites_items_list', 10, 1);
