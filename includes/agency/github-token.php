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
				'page'              => 'smart-purge-for-breeze-cache',
				'bsp_github_saved'  => '1',
			),
			admin_url('options-general.php')
		)
	);
	exit;
}
add_action('admin_init', 'bsp_agency_maybe_save_github_token');

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
		'none'      => __('Not configured', 'smart-purge-for-breeze-cache'),
	);

	$source   = bsp_agency_github_token_source();
	$label    = isset($sources[ $source ]) ? $sources[ $source ] : $sources['none'];
	$configured = bsp_agency_github_token_is_configured();
	$wp_config_locked = defined('BSP_GITHUB_TOKEN') && BSP_GITHUB_TOKEN
		&& defined('BSP_AGENCY_GITHUB_TOKEN_SOURCE')
		&& 'wp-config' === BSP_AGENCY_GITHUB_TOKEN_SOURCE;

	if (isset($_GET['bsp_github_saved']) && '1' === $_GET['bsp_github_saved']) {
		echo '<div class="notice notice-success is-dismissible"><p>';
		esc_html_e('GitHub update settings saved.', 'smart-purge-for-breeze-cache');
		echo '</p></div>';
	}
	?>
	<div style="flex: 1; min-width: 300px; background: #fff; padding: 20px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04); margin-bottom: 20px;">
		<h3 style="margin-top:0;"><?php esc_html_e('Agency GitHub Updates', 'smart-purge-for-breeze-cache'); ?></h3>
		<p class="description">
			<?php esc_html_e('Private GitHub Releases appear under Dashboard → Updates when a token is configured. Tag new releases on GitHub; this site does not read the main branch.', 'smart-purge-for-breeze-cache'); ?>
		</p>
		<p>
			<strong><?php esc_html_e('Status:', 'smart-purge-for-breeze-cache'); ?></strong>
			<?php if ($configured) : ?>
				<span style="color:#2271b1;"><?php esc_html_e('Connected', 'smart-purge-for-breeze-cache'); ?></span>
				— <?php echo esc_html($label); ?>
			<?php else : ?>
				<span style="color:#d63638;"><?php echo esc_html($label); ?></span>
			<?php endif; ?>
		</p>

		<?php if ($wp_config_locked) : ?>
			<p class="description">
				<?php esc_html_e('BSP_GITHUB_TOKEN is defined in wp-config.php. Remove it there to manage the token from this screen instead.', 'smart-purge-for-breeze-cache'); ?>
			</p>
		<?php else : ?>
			<form method="post" action="">
				<?php wp_nonce_field('bsp_agency_github_token'); ?>
				<input type="hidden" name="bsp_agency_save_github_token" value="1" />
				<p>
					<label for="bsp_agency_github_token"><strong><?php esc_html_e('GitHub PAT (read repo contents)', 'smart-purge-for-breeze-cache'); ?></strong></label><br />
					<input type="password" class="regular-text" id="bsp_agency_github_token" name="bsp_agency_github_token" value="" autocomplete="off" placeholder="<?php echo $configured ? esc_attr__('Leave blank to keep current', 'smart-purge-for-breeze-cache') : esc_attr__('ghp_… or github_pat_…', 'smart-purge-for-breeze-cache'); ?>" />
				</p>
				<p class="description">
					<?php esc_html_e('Stored encrypted in the database. Prefer BSP_GITHUB_TOKEN in wp-config or server env on Cloudways for zero-touch client installs.', 'smart-purge-for-breeze-cache'); ?>
				</p>
				<p>
					<button type="submit" class="button button-secondary"><?php esc_html_e('Save GitHub Token', 'smart-purge-for-breeze-cache'); ?></button>
					<?php if ($configured && 'option' === $source) : ?>
						<button type="submit" class="button button-link-delete" name="bsp_agency_clear_github_token" value="1"><?php esc_html_e('Clear stored token', 'smart-purge-for-breeze-cache'); ?></button>
					<?php endif; ?>
				</p>
			</form>
		<?php endif; ?>
	</div>
	<?php
}
add_action('bsp_agency_settings_panel', 'bsp_agency_render_github_settings_panel');

/**
 * Notice when agency build has no GitHub credentials.
 */
function bsp_agency_github_token_admin_notice() {
	if (!current_user_can('manage_options') || bsp_agency_github_token_is_configured()) {
		return;
	}

	$screen = function_exists('get_current_screen') ? get_current_screen() : null;
	if (!$screen || 'settings_page_smart-purge-for-breeze-cache' === $screen->id) {
		return;
	}

	echo '<div class="notice notice-warning"><p>';
	printf(
		/* translators: %s: settings page link */
		esc_html__('%1$s needs a GitHub token for agency updates. Add BSP_GITHUB_TOKEN to wp-config, set a server env var, or configure it on the %2$s settings screen.', 'smart-purge-for-breeze-cache'),
		'<strong>Smart Purge for Breeze Cache</strong>',
		'<a href="' . esc_url(admin_url('options-general.php?page=smart-purge-for-breeze-cache')) . '">Smart Purge</a>'
	);
	echo '</p></div>';
}
add_action('admin_notices', 'bsp_agency_github_token_admin_notice');
