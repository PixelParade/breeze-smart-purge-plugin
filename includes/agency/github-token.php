<?php
/**
 * Agency GitHub token bootstrap — resolves BSP_GITHUB_TOKEN without wp-config edits when possible.
 *
 * Priority: existing constant (wp-config) → environment variable → encrypted site option.
 *
 * @package Smart_Purge_For_Breeze_Cache
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Resolve and define BSP_GITHUB_TOKEN when not already set in wp-config.
 */
function bsp_agency_bootstrap_github_credentials() {
	if (defined('BSP_GITHUB_TOKEN') && BSP_GITHUB_TOKEN) {
		if (!defined('BSP_AGENCY_GITHUB_TOKEN_SOURCE')) {
			define('BSP_AGENCY_GITHUB_TOKEN_SOURCE', 'wp-config');
		}
		return;
	}

	$env = getenv('BSP_GITHUB_TOKEN');
	if (is_string($env) && '' !== $env) {
		define('BSP_GITHUB_TOKEN', $env);
		define('BSP_AGENCY_GITHUB_TOKEN_SOURCE', 'env');
		return;
	}

	$stored = get_option('bsp_agency_github_token', '');
	if (is_string($stored) && '' !== $stored) {
		$token = bsp_agency_decrypt_token($stored);
		if ('' !== $token) {
			define('BSP_GITHUB_TOKEN', $token);
			define('BSP_AGENCY_GITHUB_TOKEN_SOURCE', 'option');
		}
	}
}

/**
 * @return string Plain token from env or option (empty if unset).
 */
function bsp_agency_resolve_github_token() {
	$env = getenv('BSP_GITHUB_TOKEN');
	if (is_string($env) && '' !== $env) {
		return $env;
	}

	$stored = get_option('bsp_agency_github_token', '');
	if (!is_string($stored) || '' === $stored) {
		return '';
	}

	$decrypted = bsp_agency_decrypt_token($stored);
	return is_string($decrypted) ? $decrypted : '';
}

/**
 * @return bool
 */
function bsp_agency_github_token_is_configured() {
	return defined('BSP_GITHUB_TOKEN') && BSP_GITHUB_TOKEN;
}

/**
 * @return string wp-config|env|option|none
 */
function bsp_agency_github_token_source() {
	if (!bsp_agency_github_token_is_configured()) {
		return 'none';
	}

	if (defined('BSP_AGENCY_GITHUB_TOKEN_SOURCE')) {
		return BSP_AGENCY_GITHUB_TOKEN_SOURCE;
	}

	return 'wp-config';
}

/**
 * @param string $token Plain GitHub PAT.
 * @return string
 */
function bsp_agency_encrypt_token($token) {
	if (!function_exists('openssl_encrypt')) {
		return base64_encode($token);
	}

	$key       = hash('sha256', wp_salt('auth') . 'bsp_agency_github_token', true);
	$iv        = openssl_random_pseudo_bytes(16);
	$encrypted = openssl_encrypt($token, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

	if (false === $encrypted) {
		return '';
	}

	return base64_encode($iv . $encrypted);
}

/**
 * @param string $payload Encrypted payload from the option table.
 * @return string
 */
function bsp_agency_decrypt_token($payload) {
	if (!is_string($payload) || '' === $payload) {
		return '';
	}

	$raw = base64_decode($payload, true);
	if (false === $raw) {
		return '';
	}

	if (!function_exists('openssl_decrypt') || strlen($raw) < 17) {
		return base64_decode($payload, true) ?: '';
	}

	$iv        = substr($raw, 0, 16);
	$encrypted = substr($raw, 16);
	$key       = hash('sha256', wp_salt('auth') . 'bsp_agency_github_token', true);
	$plain     = openssl_decrypt($encrypted, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

	return is_string($plain) ? $plain : '';
}

/**
 * Admin: save or clear encrypted token option.
 */
function bsp_agency_maybe_save_github_token() {
	if (!is_admin() || !current_user_can('manage_options')) {
		return;
	}

	if (empty($_POST['bsp_agency_save_github_token'])) {
		return;
	}

	check_admin_referer('bsp_agency_github_token');

	// wp-config constant wins; stored option is ignored at runtime.
	if (defined('BSP_GITHUB_TOKEN') && BSP_GITHUB_TOKEN) {
		return;
	}

	$raw = isset($_POST['bsp_agency_github_token'])
		? sanitize_text_field(wp_unslash($_POST['bsp_agency_github_token']))
		: '';

	if (!empty($_POST['bsp_agency_clear_github_token'])) {
		delete_option('bsp_agency_github_token');
		delete_transient('bsp_github_release');
		delete_site_transient('update_plugins');
	} elseif ('' !== $raw) {
		$encrypted = bsp_agency_encrypt_token($raw);
		if ('' !== $encrypted) {
			update_option('bsp_agency_github_token', $encrypted, false);
			if (!defined('BSP_GITHUB_TOKEN')) {
				define('BSP_GITHUB_TOKEN', $raw);
			}
			if (!defined('BSP_AGENCY_GITHUB_TOKEN_SOURCE')) {
				define('BSP_AGENCY_GITHUB_TOKEN_SOURCE', 'option');
			}
		}
		delete_transient('bsp_github_release');
		delete_site_transient('update_plugins');
	} else {
		return;
	}

	wp_safe_redirect(
		add_query_arg(
			array(
				'page'             => 'smart-purge-for-breeze-cache',
				'tab'              => 'updates',
				'bsp_github_saved' => '1',
			),
			admin_url('options-general.php')
		)
	);
	exit;
}
add_action('admin_init', 'bsp_agency_maybe_save_github_token');

/**
 * Settings → Smart Purge: force a fresh GitHub update check.
 */
function bsp_agency_maybe_refresh_github_updates() {
	if (!is_admin() || !current_user_can('manage_options')) {
		return;
	}
	if (empty($_GET['bsp_refresh_github_updates']) || '1' !== $_GET['bsp_refresh_github_updates']) {
		return;
	}
	check_admin_referer('bsp_refresh_github_updates');
	if (function_exists('bsp_force_github_update_check')) {
		bsp_force_github_update_check();
	} else {
		delete_transient('bsp_github_release');
		delete_site_transient('update_plugins');
	}
	wp_safe_redirect(
		add_query_arg(
			array(
				'page'                     => 'smart-purge-for-breeze-cache',
				'tab'                      => 'updates',
				'bsp_github_updates_reset' => '1',
			),
			admin_url('options-general.php')
		)
	);
	exit;
}
add_action('admin_init', 'bsp_agency_maybe_refresh_github_updates');

/**
 * Settings panel on Smart Purge screen (agency builds only).
 */
function bsp_agency_render_github_settings_panel() {
	if (!current_user_can('manage_options')) {
		return;
	}

	$sources = array(
		'wp-config' => __('wp-config.php constant', 'smart-purge-for-breeze-cache'),
		'env'       => __('Server environment variable', 'smart-purge-for-breeze-cache'),
		'option'    => __('Encrypted setting (this site)', 'smart-purge-for-breeze-cache'),
	);

	$source         = bsp_agency_github_token_source();
	$token_override = bsp_agency_github_token_is_configured();
	$release        = function_exists('bsp_fetch_latest_github_release') ? bsp_fetch_latest_github_release() : null;
	$release_ok     = is_array($release) && !empty($release['version']);

	if (isset($_GET['bsp_github_saved']) && '1' === $_GET['bsp_github_saved']) {
		echo '<div class="notice notice-success is-dismissible"><p>';
		esc_html_e('GitHub update settings saved.', 'smart-purge-for-breeze-cache');
		echo '</p></div>';
	}
	if (isset($_GET['bsp_github_updates_reset']) && '1' === $_GET['bsp_github_updates_reset']) {
		echo '<div class="notice notice-success is-dismissible"><p>';
		esc_html_e('Update cache cleared. Open Dashboard → Updates and click Check Again.', 'smart-purge-for-breeze-cache');
		echo '</p></div>';
	}

	$installed_version = function_exists('bsp_get_plugin_version') ? bsp_get_plugin_version() : '';
	$latest_version    = $release_ok ? $release['version'] : '';
	?>
	<div class="bsp-updates-panel" style="max-width: 640px; background: #fff; padding: 20px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04); margin-top: 20px;">
		<h2 style="margin-top:0;"><?php esc_html_e('Plugin Updates', 'smart-purge-for-breeze-cache'); ?></h2>
		<p class="description">
			<?php esc_html_e('Updates come from public GitHub Releases. No token or setup is required on client sites.', 'smart-purge-for-breeze-cache'); ?>
		</p>
		<?php if ($installed_version) : ?>
		<p>
			<strong><?php esc_html_e('Installed:', 'smart-purge-for-breeze-cache'); ?></strong>
			<?php echo esc_html($installed_version); ?>
			<?php if ($latest_version) : ?>
				&nbsp;|&nbsp;
				<strong><?php esc_html_e('Latest release:', 'smart-purge-for-breeze-cache'); ?></strong>
				<?php echo esc_html($latest_version); ?>
			<?php endif; ?>
		</p>
		<?php endif; ?>
		<p>
			<strong><?php esc_html_e('Status:', 'smart-purge-for-breeze-cache'); ?></strong>
			<?php if ($release_ok) : ?>
				<span style="color:#2271b1;"><?php esc_html_e('Ready', 'smart-purge-for-breeze-cache'); ?></span>
				— <?php esc_html_e('Public GitHub Releases', 'smart-purge-for-breeze-cache'); ?>
			<?php else : ?>
				<span style="color:#d63638;"><?php esc_html_e('Could not reach GitHub Releases', 'smart-purge-for-breeze-cache'); ?></span>
			<?php endif; ?>
		</p>
		<p>
			<a class="button button-secondary" href="<?php echo esc_url(wp_nonce_url(admin_url('options-general.php?page=smart-purge-for-breeze-cache&tab=updates&bsp_refresh_github_updates=1'), 'bsp_refresh_github_updates')); ?>">
				<?php esc_html_e('Refresh update check', 'smart-purge-for-breeze-cache'); ?>
			</a>
		</p>
		<?php if ($token_override && isset($sources[ $source ])) : ?>
			<p class="description">
				<?php
				printf(
					/* translators: %s: token source label */
					esc_html__('Optional BSP_GITHUB_TOKEN override is active (%s). Not required for the public PixelParade repo.', 'smart-purge-for-breeze-cache'),
					esc_html($sources[ $source ])
				);
				?>
			</p>
		<?php endif; ?>
	</div>
	<?php
}
add_action('bsp_agency_settings_panel', 'bsp_agency_render_github_settings_panel');

/**
 * Notice when agency build cannot reach GitHub Releases (not when token is merely unset on a public repo).
 */
function bsp_agency_github_token_admin_notice() {
	if (!current_user_can('manage_options')) {
		return;
	}

	$screen = function_exists('get_current_screen') ? get_current_screen() : null;
	if (!$screen || 'settings_page_smart-purge-for-breeze-cache' === $screen->id) {
		return;
	}

	if (!function_exists('bsp_fetch_latest_github_release')) {
		return;
	}

	$release = bsp_fetch_latest_github_release();
	if ($release && !empty($release['version'])) {
		return;
	}

	echo '<div class="notice notice-warning"><p>';
	printf(
		/* translators: %s: settings page link */
		esc_html__('%1$s could not reach GitHub Releases. Try %2$s → Refresh update check, or check again later if the API rate limit was hit.', 'smart-purge-for-breeze-cache'),
		'<strong>Smart Purge for Breeze Cache</strong>',
		'<a href="' . esc_url(admin_url('options-general.php?page=smart-purge-for-breeze-cache')) . '">' . esc_html__('Settings → Smart Purge', 'smart-purge-for-breeze-cache') . '</a>'
	);
	echo '</p></div>';
}
add_action('admin_notices', 'bsp_agency_github_token_admin_notice');
