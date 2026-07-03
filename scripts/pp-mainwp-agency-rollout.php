<?php
/**
 * One-time MainWP bulk install for Smart Purge agency zip.
 * Copy to wp-content/mu-plugins/ on mainwp.pixelparade.co, trigger once, then delete.
 *
 * Trigger (secret key — change before upload):
 *   /wp-admin/admin-ajax.php?action=pp_smart_purge_rollout&key=YOUR_KEY
 */

if (!defined('ABSPATH')) {
	exit;
}

define('PP_SMART_PURGE_ROLLOUT_KEY', 'bsp_agency_rollout_20260702');
define('PP_SMART_PURGE_ZIP_URL', 'https://pixelparade.co/wp-content/uploads/pp-releases/smart-purge-for-breeze-cache.zip');

/**
 * MainWP site lookups require an authenticated admin user.
 */
function pp_smart_purge_rollout_set_admin_user() {
	if (is_user_logged_in() && current_user_can('manage_options')) {
		return;
	}
	$admins = get_users(
		array(
			'role'   => 'administrator',
			'number' => 1,
			'fields' => array('ID'),
		)
	);
	if (!empty($admins[0]->ID)) {
		wp_set_current_user((int) $admins[0]->ID);
	}
}

add_action('wp_ajax_pp_smart_purge_rollout', 'pp_smart_purge_rollout_run');
add_action('wp_ajax_nopriv_pp_smart_purge_rollout', 'pp_smart_purge_rollout_run');

/**
 * Site IDs with breeze-smart-purge (active or inactive). PixelParade (16) updated separately.
 *
 * @return int[]
 */
function pp_smart_purge_rollout_site_ids() {
	return array(
		36, 42, 47, 58, 43, 44, 26, 6, 34, 32, 41, 55, 51, 35, 27, 31, 2, 25, 11, 7, 33, 28, 48, 30, 37, 5, 53, 39, 56, 57, 54, 29, 49, 40, 50,
	);
}

/**
 * Run rollout.
 */
function pp_smart_purge_rollout_run() {
	if (!isset($_GET['key']) || !hash_equals(PP_SMART_PURGE_ROLLOUT_KEY, sanitize_text_field(wp_unslash($_GET['key'])))) {
		wp_send_json_error(array('message' => 'Invalid key'), 403);
	}

	pp_smart_purge_rollout_set_admin_user();

	if (isset($_GET['debug']) && '1' === $_GET['debug']) {
		$db     = \MainWP\Dashboard\MainWP_DB::instance();
		$sample = $db->get_website_by_id(36);
		wp_send_json_success(
			array(
				'user_id' => get_current_user_id(),
				'sample'  => $sample ? array('id' => $sample->id, 'url' => $sample->url) : null,
			)
		);
	}

	if (!class_exists('MainWP\Dashboard\MainWP_Connect')) {
		wp_send_json_error(array('message' => 'MainWP Dashboard classes not loaded'), 500);
	}

	@set_time_limit(0);

	$site_ids = pp_smart_purge_rollout_site_ids();
	$websites = array();

	foreach ($site_ids as $site_id) {
		$website = \MainWP\Dashboard\MainWP_DB::instance()->get_website_by_id((int) $site_id);
		if ($website && empty($website->sync_errors)) {
			$websites[] = $website;
		}
	}

	if (empty($websites)) {
		wp_send_json_error(array('message' => 'No eligible websites found'), 404);
	}

	$install_data = array(
		'type'           => 'plugin',
		'activatePlugin' => 'no',
		'url'            => wp_json_encode(array(PP_SMART_PURGE_ZIP_URL)),
	);

	$install_output = new stdClass();
	$install_output->ok     = array();
	$install_output->errors = array();

	\MainWP\Dashboard\MainWP_Connect::fetch_urls_authed(
		$websites,
		'installplugintheme',
		$install_data,
		array('PP_Smart_Purge_Rollout_Handler', 'install_handler'),
		$install_output
	);

	$deactivate_data = array(
		'action' => 'deactivate',
		'plugin' => 'breeze-smart-purge/breeze-smart-purge.php',
	);

	$deactivate_output = new stdClass();
	\MainWP\Dashboard\MainWP_Connect::fetch_urls_authed(
		$websites,
		'plugin_action',
		$deactivate_data,
		null,
		$deactivate_output
	);

	$activate_data = array(
		'action' => 'activate',
		'plugin' => 'smart-purge-for-breeze-cache/smart-purge-for-breeze-cache.php',
	);

	$activate_output = new stdClass();
	\MainWP\Dashboard\MainWP_Connect::fetch_urls_authed(
		$websites,
		'plugin_action',
		$activate_data,
		null,
		$activate_output
	);

	$delete_data = array(
		'action' => 'delete',
		'plugin' => 'breeze-smart-purge/breeze-smart-purge.php',
	);

	$delete_output = new stdClass();
	\MainWP\Dashboard\MainWP_Connect::fetch_urls_authed(
		$websites,
		'plugin_action',
		$delete_data,
		null,
		$delete_output
	);

	wp_send_json_success(
		array(
			'sites'      => count($websites),
			'install'    => $install_output,
			'deactivate' => $deactivate_output,
			'activate'   => $activate_output,
			'delete'     => $delete_output,
		)
	);
}

/**
 * Install result handler.
 */
class PP_Smart_Purge_Rollout_Handler {

	/**
	 * @param mixed  $data     Response data.
	 * @param object $website  Website object.
	 * @param object $output   Output object.
	 */
	public static function install_handler( $data, $website, &$output ) {
		\MainWP\Dashboard\MainWP_Install_Bulk::install_plugin_theme_handler($data, $website, $output);
	}
}
