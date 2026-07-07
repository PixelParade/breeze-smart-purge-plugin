<?php
/**
 * Plugin Name: PixelParade Smart Purge for Breeze Cache
 * Description: Intelligently purges CPT archives, taxonomies, and page-builder hub pages when content changes in Breeze Cache.
 * Version: 1.1.15
 * Author: PixelParade LLC
 * Author URI: https://pixelparade.co
 * License: GPL v2 or later
 * Text Domain: smart-purge-for-breeze-cache
 * Requires Plugins: breeze
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('BSP_PLUGIN_FILE')) {
    define('BSP_PLUGIN_FILE', __FILE__);
}
if (!defined('BSP_PLUGIN_SLUG')) {
    define('BSP_PLUGIN_SLUG', 'smart-purge-for-breeze-cache');
}
if (!defined('BSP_PLUGIN_DISPLAY_NAME')) {
    define('BSP_PLUGIN_DISPLAY_NAME', 'PixelParade Smart Purge for Breeze Cache');
}
if (!defined('BSP_VERSION')) {
    define('BSP_VERSION', '1.1.14');
}

// Agency bootstrap first — may define BSP_GITHUB_TOKEN from env or encrypted option.
$bsp_agency_bootstrap = __DIR__ . '/includes/agency/bootstrap.php';
if (file_exists($bsp_agency_bootstrap)) {
    require_once $bsp_agency_bootstrap;
}

// GitHub Releases updater — agency / MainWP lane only (file omitted from wordpress.org builds).
if (file_exists(__DIR__ . '/includes/github-updater.php')) {
    require_once __DIR__ . '/includes/github-updater.php';
}

$bsp_scanner_detection = __DIR__ . '/includes/scanner-detection.php';
if (file_exists($bsp_scanner_detection)) {
    require_once $bsp_scanner_detection;
}

add_action('admin_enqueue_scripts', 'bsp_enqueue_admin_plugin_asset_styles');
add_action('admin_enqueue_scripts', 'bsp_enqueue_settings_assets');

/**
 * object-fit: cover for plugin icon/banner on Updates, Plugins, and View details screens.
 *
 * @param string $hook_suffix Current admin screen hook.
 */
function bsp_enqueue_admin_plugin_asset_styles($hook_suffix) {
	$screens = array('update-core.php', 'plugins.php', 'plugin-install.php');
	if (!in_array($hook_suffix, $screens, true)) {
		return;
	}

	$css_path = __DIR__ . '/assets/admin/plugin-assets.css';
	if (!file_exists($css_path)) {
		return;
	}

	wp_enqueue_style(
		'bsp-plugin-assets',
		plugins_url('assets/admin/plugin-assets.css', __FILE__),
		array(),
		(string) filemtime($css_path)
	);
}

/**
 * Settings screen CSS/JS (scan + save AJAX).
 *
 * @param string $hook_suffix Current admin screen hook.
 */
function bsp_enqueue_settings_assets($hook_suffix) {
	if ('settings_page_' . BSP_PLUGIN_SLUG !== $hook_suffix) {
		return;
	}

	$css_path = __DIR__ . '/assets/admin/settings.css';
	$js_path  = __DIR__ . '/assets/admin/settings.js';

	if (file_exists($css_path)) {
		wp_enqueue_style(
			'bsp-settings',
			plugins_url('assets/admin/settings.css', BSP_PLUGIN_FILE),
			array(),
			(string) filemtime($css_path)
		);
	}

	if (!file_exists($js_path)) {
		return;
	}

	wp_enqueue_script(
		'bsp-settings',
		plugins_url('assets/admin/settings.js', BSP_PLUGIN_FILE),
		array(),
		(string) filemtime($js_path),
		true
	);

	wp_localize_script(
		'bsp-settings',
		'bspSettings',
		array(
			'ajaxUrl'    => admin_url('admin-ajax.php'),
			'nonce'      => wp_create_nonce('bsp_save_action'),
			'scanAction'   => 'bsp_run_ajax_scan',
			'statusAction' => 'bsp_ajax_scan_status',
			'saveAction'   => 'bsp_run_ajax_save',
			'i18n'         => array(
				'scanning'         => __('Scanning...', 'smart-purge-for-breeze-cache'),
				'scanStarting'     => __('Starting scan...', 'smart-purge-for-breeze-cache'),
				'scanInProgress'   => __('Scanning in progress... this may take a few seconds.', 'smart-purge-for-breeze-cache'),
				'scanComplete'     => __('Scan Complete!', 'smart-purge-for-breeze-cache'),
				'scanFailed'       => __('Scan failed. Please refresh and try again.', 'smart-purge-for-breeze-cache'),
				'scanFailedShort'  => __('Scan failed.', 'smart-purge-for-breeze-cache'),
				'saving'           => __('Saving...', 'smart-purge-for-breeze-cache'),
				'saveSuccess'      => __('Settings Saved Successfully!', 'smart-purge-for-breeze-cache'),
				'saveError'        => __('Error saving settings.', 'smart-purge-for-breeze-cache'),
				'serverError'      => __('Server error.', 'smart-purge-for-breeze-cache'),
				'noPagesDetected'  => '[No pages auto-detected]',
			),
		)
	);
}

// ====================================================================
// 0. DEPENDENCY CHECK, ACTIVATION & ADMIN BAR LINK
// ====================================================================

// Check if Breeze is active before running any logic
function bsp_check_dependencies() {
    if (!defined('BREEZE_VERSION')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p><strong>' . esc_html(BSP_PLUGIN_DISPLAY_NAME) . '</strong> ' . esc_html__('requires the', 'smart-purge-for-breeze-cache') . ' <a href="https://wordpress.org/plugins/breeze/" target="_blank">Breeze Cache</a> ' . esc_html__('plugin to be active. Please activate it to enable smart purging.', 'smart-purge-for-breeze-cache') . '</p></div>';
        });
        return false;
    }
    return true;
}

// Add link to the Plugins page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'bsp_add_settings_link');
function bsp_add_settings_link($links) {
    $settings_link = '<a href="' . esc_url(admin_url('options-general.php?page=' . BSP_PLUGIN_SLUG)) . '">' . esc_html__('Settings', 'smart-purge-for-breeze-cache') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

// ====================================================================
// ADMIN BAR — Breeze dropdown on frontend + per-page clear cache
// ====================================================================

add_action('admin_bar_menu', 'bsp_register_breeze_frontend_admin_bar', 999);
add_action('admin_bar_menu', 'bsp_register_breeze_admin_bar_items', 1001);
add_action('template_redirect', 'bsp_handle_frontend_breeze_purge_links');
add_action('wp_enqueue_scripts', 'bsp_enqueue_frontend_breeze_toolbar_assets');

function bsp_breeze_toolbar_enabled() {
    if (!defined('BREEZE_VERSION') || !class_exists('Breeze_Options_Reader')) {
        return false;
    }
    $display = Breeze_Options_Reader::get_option_value('breeze-display-clean');
    return !empty($display);
}

function bsp_user_can_use_breeze_toolbar() {
    if (current_user_can('manage_options') || current_user_can('editor')) {
        return true;
    }
    return function_exists('is_plugin_active')
        && is_plugin_active('woocommerce/woocommerce.php')
        && current_user_can('manage_woocommerce');
}

function bsp_get_current_request_url() {
    $path = '/';
    if (isset($_SERVER['REQUEST_URI'])) {
        $path = sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']));
    }
    return home_url($path);
}

function bsp_get_breeze_purge_url($query_arg, $nonce_action) {
    $url = remove_query_arg(
        ['breeze_purge', 'breeze_purge_cloudflare', 'breeze_purge_cache_cloudflare', '_wpnonce', 'breeze_post_cache'],
        bsp_get_current_request_url()
    );
    return wp_nonce_url(add_query_arg($query_arg, 1, $url), $nonce_action);
}

function bsp_get_clear_post_cache_url($post) {
    if (!$post instanceof WP_Post) {
        $post = get_post($post);
    }
    if (!$post || !current_user_can('edit_post', $post->ID)) {
        return '';
    }
    $post_type_object = get_post_type_object($post->post_type);
    if (!$post_type_object || empty($post_type_object->_edit_link)) {
        return '';
    }
    return wp_nonce_url(
        admin_url(sprintf($post_type_object->_edit_link . '&action=clear-breeze-cache', $post->ID)),
        'clear-cache-post_' . $post->ID
    );
}

function bsp_get_admin_bar_singular_post() {
    if (!is_singular()) {
        return null;
    }
    $post = get_queried_object();
    return ($post instanceof WP_Post) ? $post : null;
}

function bsp_get_admin_bar_context_post() {
    $post = bsp_get_admin_bar_singular_post();
    if ($post) {
        return $post;
    }
    if (!is_admin() || !current_user_can('edit_posts')) {
        return null;
    }
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin post ID from query string; capability checked above.
    if (!isset($_GET['post'])) {
        return null;
    }
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $post = get_post((int) $_GET['post']);
    return ($post instanceof WP_Post) ? $post : null;
}

/**
 * Register Breeze toolbar nodes on the frontend (Breeze only hooks admin by default).
 *
 * @param WP_Admin_Bar $wp_admin_bar Admin bar instance.
 */
function bsp_register_breeze_frontend_admin_bar($wp_admin_bar) {
    if (is_admin() || !bsp_breeze_toolbar_enabled() || !bsp_user_can_use_breeze_toolbar()) {
        return;
    }
    if ($wp_admin_bar->get_node('breeze-topbar')) {
        return;
    }

    $wp_admin_bar->add_node(
        array(
            'id'    => 'breeze-topbar',
            'title' => esc_html__('Breeze', 'breeze'),
            'meta'  => array(
                'classname' => 'breeze',
            ),
        )
    );

    $is_network = is_multisite() && is_network_admin();
    $purge_id   = (!is_multisite() || $is_network) ? 'breeze-purge-all' : 'breeze-purge-site';

    $wp_admin_bar->add_node(
        array(
            'id'     => $purge_id,
            'title'  => (!is_multisite() || $is_network)
                ? esc_html__('Purge All Cache', 'breeze')
                : esc_html__('Purge Site Cache', 'breeze'),
            'parent' => 'breeze-topbar',
            'href'   => bsp_get_breeze_purge_url('breeze_purge', 'breeze_purge_cache'),
            'meta'   => array(
                'class' => 'breeze-toolbar-group',
            ),
        )
    );

    if (!current_user_can('manage_options')) {
        return;
    }

    if (!class_exists('Breeze_CloudFlare_Helper') || !Breeze_CloudFlare_Helper::is_cloudflare_enabled()) {
        return;
    }

    $wp_admin_bar->add_node(
        array(
            'id'     => 'breeze-purge-modules',
            'title'  => esc_html__('Purge Modules', 'breeze'),
            'parent' => 'breeze-topbar',
            'meta'   => array(
                'class' => 'breeze-toolbar-group',
            ),
        )
    );

    $wp_admin_bar->add_node(
        array(
            'id'     => 'breeze-purge-cloudflare',
            'title'  => esc_html__('Purge Cloudflare Cache', 'breeze'),
            'parent' => 'breeze-purge-modules',
            'href'   => bsp_get_breeze_purge_url('breeze_purge_cloudflare', 'breeze_purge_cache_cloudflare'),
            'meta'   => array(
                'class' => 'breeze-toolbar-group',
            ),
        )
    );
}

function bsp_register_breeze_admin_bar_items($wp_admin_bar) {
    if (!bsp_breeze_toolbar_enabled() || !bsp_user_can_use_breeze_toolbar()) {
        return;
    }
    if (!$wp_admin_bar->get_node('breeze-topbar')) {
        return;
    }

    $post = bsp_get_admin_bar_context_post();

    if ($post) {
        $clear_url = bsp_get_clear_post_cache_url($post);
        if ($clear_url) {
            $wp_admin_bar->add_node([
                'id'     => 'breeze-clear-this-page',
                'parent' => 'breeze-topbar',
                'title'  => __('Clear cache for this page', 'smart-purge-for-breeze-cache'),
                'href'   => $clear_url,
                'meta'   => ['class' => 'breeze-toolbar-group'],
            ]);
        }
    }

    if (current_user_can('manage_options')) {
        $wp_admin_bar->add_node([
            'id'     => 'breeze-smart-purge-link',
            'parent' => 'breeze-topbar',
            'title'  => __('Smart Purge Settings', 'smart-purge-for-breeze-cache'),
            'href'   => admin_url('options-general.php?page=' . BSP_PLUGIN_SLUG),
            'meta'   => ['class' => 'breeze-toolbar-group'],
        ]);
    }
}

function bsp_should_load_frontend_breeze_toolbar_assets() {
    return !is_admin()
        && bsp_breeze_toolbar_enabled()
        && bsp_user_can_use_breeze_toolbar()
        && current_user_can('manage_options')
        && defined('BREEZE_VERSION')
        && defined('BREEZE_PLUGIN_DIR');
}

function bsp_enqueue_frontend_breeze_toolbar_assets() {
    if (!bsp_should_load_frontend_breeze_toolbar_assets()) {
        return;
    }

    if (!wp_script_is('jquery', 'enqueued')) {
        wp_enqueue_script('jquery');
    }

    wp_register_script('bsp-ajaxurl', false, array(), BSP_VERSION, true);
    wp_enqueue_script('bsp-ajaxurl');
    wp_add_inline_script(
        'bsp-ajaxurl',
        'var ajaxurl=' . wp_json_encode(admin_url('admin-ajax.php')) . ';',
        'before'
    );

    $min              = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';
    $breeze_admin_file = BREEZE_PLUGIN_DIR . 'inc/breeze-admin.php';

    wp_enqueue_style(
        'breeze-notice',
        plugins_url('assets/css/breeze-admin-global.css', $breeze_admin_file),
        [],
        BREEZE_VERSION
    );

    wp_enqueue_script(
        'breeze-backend',
        plugins_url('assets/js/breeze-main' . $min . '.js', $breeze_admin_file),
        array('jquery', 'bsp-ajaxurl'),
        BREEZE_VERSION,
        true
    );

    wp_localize_script(
        'breeze-backend',
        'breeze_token_name',
        [
            'breeze_purge_varnish'      => wp_create_nonce('_breeze_purge_varnish'),
            'breeze_purge_database'     => wp_create_nonce('_breeze_purge_database'),
            'breeze_purge_cache'        => wp_create_nonce('_breeze_purge_cache'),
            'breeze_save_options'       => wp_create_nonce('_breeze_save_options'),
            'breeze_purge_opcache'      => wp_create_nonce('_breeze_purge_opcache'),
            'breeze_import_settings'    => wp_create_nonce('_breeze_import_settings'),
            'breeze_reset_default'      => wp_create_nonce('_breeze_reset_default'),
            'breeze_check_cdn_url'      => wp_create_nonce('_breeze_check_cdn_url'),
            'breeze_check_compat'       => wp_create_nonce('_breeze_check_compat'),
            'breeze_check_permission'   => wp_create_nonce('_breeze_check_permission'),
            'breeze_export_json'        => wp_create_nonce('_breeze_export_json'),
            'breeze_apply_optimization' => wp_create_nonce('_breeze_apply_optimization'),
            'breeze_restore_settings'   => wp_create_nonce('_breeze_restore_settings'),
        ]
    );

    wp_add_inline_script(
        'breeze-backend',
        'jQuery(function($){if(!$(\'#wpbody-content\').length){$(\'body\').append(\'<div id="wpbody-content" style="position:fixed;bottom:20px;right:20px;z-index:99999;max-width:400px;"></div>\');}});',
        'after'
    );
}

function bsp_handle_frontend_breeze_purge_links() {
    if (is_admin() || !is_user_logged_in() || !bsp_user_can_use_breeze_toolbar()) {
        return;
    }

    $redirect = remove_query_arg(
        ['breeze_purge', 'breeze_purge_cloudflare', 'breeze_purge_cache_cloudflare', '_wpnonce'],
        bsp_get_current_request_url()
    );

    if (isset($_GET['breeze_purge'])) {
        check_admin_referer('breeze_purge_cache');
        do_action('breeze_clear_all_cache');
        wp_safe_redirect(add_query_arg('breeze_post_cache', 'cleared', $redirect));
        exit;
    }

    if (isset($_GET['breeze_purge_cloudflare']) && current_user_can('manage_options')) {
        check_admin_referer('breeze_purge_cache_cloudflare');
        if (class_exists('Breeze_CloudFlare_Helper')) {
            Breeze_CloudFlare_Helper::reset_all_cache();
        }
        wp_safe_redirect(add_query_arg('breeze_post_cache', 'cleared', $redirect));
        exit;
    }
}

add_action('wp_footer', 'bsp_frontend_cache_cleared_notice');
function bsp_frontend_cache_cleared_notice() {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only notice after Breeze purge redirect.
    if (is_admin() || !isset($_GET['breeze_post_cache']) || 'cleared' !== sanitize_text_field(wp_unslash($_GET['breeze_post_cache']))) {
        return;
    }
    if (!bsp_user_can_use_breeze_toolbar()) {
        return;
    }
    echo '<div class="notice notice-success" style="position:fixed;bottom:20px;right:20px;z-index:99999;padding:12px 16px;background:#fff;border-left:4px solid #46b450;box-shadow:0 2px 8px rgba(0,0,0,.15);">'
        . esc_html__('Cache has been purged.', 'smart-purge-for-breeze-cache')
        . '</div>';
}

// ====================================================================
// DUPLICATE INSTALL GUARD (fleet rollout lesson — Jul 2026)
// ====================================================================

/**
 * Extra plugin directories that must not coexist with the canonical slug.
 *
 * @return string[] Basenames under wp-content/plugins/.
 */
function bsp_get_conflicting_plugin_dirs() {
	$conflicts = array();
	$plugins   = wp_normalize_path(WP_PLUGIN_DIR);

	if (is_dir($plugins . '/breeze-smart-purge')) {
		$conflicts[] = 'breeze-smart-purge';
	}

	$matches = glob($plugins . '/smart-purge-for-breeze-cache*', GLOB_ONLYDIR);
	if (is_array($matches)) {
		foreach ($matches as $dir) {
			$name = basename($dir);
			if ('smart-purge-for-breeze-cache' !== $name) {
				$conflicts[] = $name;
			}
		}
	}

	return array_values(array_unique($conflicts));
}

add_action('admin_notices', 'bsp_admin_notice_conflicting_plugin_dirs');
function bsp_admin_notice_conflicting_plugin_dirs() {
	if (!current_user_can('activate_plugins')) {
		return;
	}

	$conflicts = bsp_get_conflicting_plugin_dirs();
	if (empty($conflicts)) {
		return;
	}

	$list = implode(', ', array_map('esc_html', $conflicts));
	?>
	<div class="notice notice-error">
		<p>
			<strong><?php esc_html_e('Smart Purge: duplicate or legacy plugin folders detected', 'smart-purge-for-breeze-cache'); ?></strong>
		</p>
		<p>
			<?php
			echo esc_html__(
				'Remove extra copies via SFTP or MainWP before updates. Only the folder smart-purge-for-breeze-cache should remain. Two active copies can double-run purge hooks.',
				'smart-purge-for-breeze-cache'
			);
			?>
			<code><?php echo esc_html($list); ?></code>
		</p>
		<p><?php esc_html_e('If a folder name contains backslashes or random suffixes, delete files via SFTP (WP Admin delete may fail). See docs/MAINWP_ROLLOUT.md.', 'smart-purge-for-breeze-cache'); ?></p>
	</div>
	<?php
}

// Set a flag to run the initial scan safely AFTER activation
register_activation_hook(__FILE__, 'bsp_on_activation');
function bsp_on_activation() {
    update_option('bsp_needs_initial_scan', true);
}

// Run the deferred scan to prevent server timeouts during plugin activation
add_action('admin_init', 'bsp_run_deferred_scan');
function bsp_run_deferred_scan() {
    if (!bsp_check_dependencies()) return; // Abort if Breeze is missing

    if (get_option('bsp_needs_initial_scan')) {
        delete_option('bsp_needs_initial_scan');
        $settings = wp_parse_args(get_option('bsp_settings', []), ['hide_utility' => 'yes', 'force_sync' => 'yes']);
        $log = bsp_execute_auto_scanner($settings);
        set_transient('bsp_scan_summary_notice', $log, 60); // Save log to display once
    }
}

// Display the success notice after initial scan
add_action('admin_notices', 'bsp_display_scan_notice');
function bsp_display_scan_notice() {
    if ($notice = get_transient('bsp_scan_summary_notice')) {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><strong><?php echo esc_html(sprintf(/* translators: %s: plugin display name */ __('%s activated!', 'smart-purge-for-breeze-cache'), BSP_PLUGIN_DISPLAY_NAME)); ?></strong> <?php esc_html_e('Initial auto-scan complete.', 'smart-purge-for-breeze-cache'); ?></p>
            <p>
                <a href="<?php echo esc_url(admin_url('options-general.php?page=' . BSP_PLUGIN_SLUG)); ?>" class="button button-primary"><?php esc_html_e('Review settings', 'smart-purge-for-breeze-cache'); ?></a>
            </p>
            <p style="font-family: monospace; font-size: 13px;"><?php echo nl2br(esc_html($notice)); ?></p>
        </div>
        <?php
        delete_transient('bsp_scan_summary_notice');
    }
}

// ====================================================================
// 1. CORE CACHE LOGIC
// ====================================================================

// Only run the core logic if Breeze is active
if (defined('BREEZE_VERSION')) {
    $bsp_global_settings = wp_parse_args(get_option('bsp_settings', []), ['hide_utility' => 'yes', 'force_sync' => 'yes']);

    // Toggleable Synchronous Purge
    if ($bsp_global_settings['force_sync'] === 'yes') {
        add_filter('breeze_cf_purge_type_on_post_update', function() {
            return 'synchronous';
        });
    }

    add_action('breeze_clear_all_cache', 'bsp_force_cloudflare_flush');
    function bsp_force_cloudflare_flush() {
        if (class_exists('Breeze_CloudFlare_Helper')) {
            Breeze_CloudFlare_Helper::reset_all_cache();
        }
    }

    add_filter('breeze_purge_post_cache_urls', 'bsp_master_breeze_strategy', 10, 2);
    function bsp_master_breeze_strategy($urls, $post_id) {
        if (!is_array($urls)) $urls = [];
        
        $post = get_post($post_id);
        if (!$post) return $urls;

        $scanned_map = get_option('bsp_scanned_map', []);
        $manual_map  = get_option('bsp_manual_map', []);
        $ignored_map = get_option('bsp_ignored_map', []);
        
        $disable_archive_map = get_option('bsp_disable_archive_map', []);
        $disable_tax_map     = get_option('bsp_disable_tax_map', []);

        $combined_urls = [];
        if (isset($scanned_map[$post->post_type])) {
            $combined_urls = array_merge($combined_urls, $scanned_map[$post->post_type]);
        }
        if (isset($manual_map[$post->post_type])) {
            $combined_urls = array_merge($combined_urls, $manual_map[$post->post_type]);
        }

        // --- APPLY EXCLUSIONS ---
        $ignored_urls = isset($ignored_map[$post->post_type]) ? $ignored_map[$post->post_type] : [];
        
        // If the wildcard '*' is used, wipe all mapped pages for this post type
        if (in_array('*', $ignored_urls)) {
            $combined_urls = []; 
        } else {
            // Otherwise, subtract the ignored URLs from the combined list
            $combined_urls = array_diff($combined_urls, $ignored_urls);
        }

        foreach ($combined_urls as $page_path) {
            $page_path = trim($page_path);
            if (!empty($page_path)) {
                $urls[] = trailingslashit(home_url($page_path));
            }
        }

        // Native Archive Purging
        if (!in_array($post->post_type, $disable_archive_map)) {
            $archive_link = get_post_type_archive_link($post->post_type);
            if ($archive_link) {
                $urls[] = trailingslashit($archive_link);
            }
        }

        // Native Taxonomy Purging
        if (!in_array($post->post_type, $disable_tax_map)) {
            $taxonomies = get_object_taxonomies($post);
            foreach ($taxonomies as $tax_slug) {
                $terms = get_the_terms($post_id, $tax_slug);
                if (!empty($terms) && !is_wp_error($terms)) {
                    foreach ($terms as $term) {
                        $term_link = get_term_link($term);
                        if (!is_wp_error($term_link)) {
                            $urls[] = trailingslashit($term_link);
                        }
                    }
                }
            }
        }

        if ($post->post_type === 'post') {
            $page_for_posts_id = get_option('page_for_posts');
            if ($page_for_posts_id) {
                $urls[] = trailingslashit(get_permalink($page_for_posts_id));
            }
        }

        return array_unique($urls);
    }
}

// ====================================================================
// 2. HELPER: DYNAMIC UTILITY DETECTION
// ====================================================================

function bsp_get_utility_post_types() {
    $utility_types = [];
    $all_types = get_post_types(['public' => true], 'objects');
    
    foreach ($all_types as $slug => $pt) {
        if ($slug === 'attachment' || $slug === 'page') {
            $utility_types[$slug] = $pt->labels->name;
        } elseif (
            empty($pt->publicly_queryable) || 
            strpos($slug, 'fl-builder') !== false || 
            strpos($slug, 'cmplz') !== false || 
            strpos($slug, 'ppwp') !== false || 
            strpos($slug, 'wp_') === 0
        ) {
            $utility_types[$slug] = $pt->labels->name;
        }
    }
    return $utility_types;
}

// ====================================================================
// 3. ADMIN SETTINGS PAGE & UI
// ====================================================================

add_action('admin_menu', 'bsp_register_settings_page');
function bsp_register_settings_page() {
    add_options_page(
        BSP_PLUGIN_DISPLAY_NAME,
        __('Smart Purge', 'smart-purge-for-breeze-cache'),
        'manage_options',
        BSP_PLUGIN_SLUG,
        'bsp_render_settings_page'
    );
}

function bsp_render_settings_page() {
    if (!current_user_can('manage_options')) return;
    if (!bsp_check_dependencies()) return; // Stop rendering if Breeze is missing

    $is_agency_build = defined('BSP_AGENCY_BUILD') && BSP_AGENCY_BUILD;
    $allowed_tabs    = array('settings');
    if ($is_agency_build) {
        $allowed_tabs[] = 'updates';
    }
    $active_tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'settings';
    if (!in_array($active_tab, $allowed_tabs, true)) {
        $active_tab = 'settings';
    }
    $settings_base_url = admin_url('options-general.php?page=' . BSP_PLUGIN_SLUG);

    $settings = wp_parse_args(get_option('bsp_settings', []), [
        'hide_utility' => 'yes',
        'force_sync'   => 'yes'
    ]);
    
    $scan_log = get_option('bsp_scan_log', "System ready. Click 'Run Smart Scan' to begin.");
    $scanned_map = get_option('bsp_scanned_map', []);
    $manual_map  = get_option('bsp_manual_map', []);
    $ignored_map = get_option('bsp_ignored_map', []);
    $disable_archive_map = get_option('bsp_disable_archive_map', []);
    $disable_tax_map     = get_option('bsp_disable_tax_map', []);
    
    $public_post_types = get_post_types(['public' => true], 'objects');
    $utility_types = bsp_get_utility_post_types();
    $hidden_type_slugs = array_keys($utility_types);

    ?>

    <div class="wrap">
        <h1><?php echo esc_html(BSP_PLUGIN_DISPLAY_NAME); ?></h1>
        <nav class="nav-tab-wrapper wp-clearfix" aria-label="<?php esc_attr_e('Smart Purge settings sections', 'smart-purge-for-breeze-cache'); ?>">
            <a href="<?php echo esc_url(add_query_arg('tab', 'settings', $settings_base_url)); ?>" class="nav-tab<?php echo 'settings' === $active_tab ? ' nav-tab-active' : ''; ?>">
                <?php esc_html_e('Smart Purge', 'smart-purge-for-breeze-cache'); ?>
            </a>
            <?php if ($is_agency_build) : ?>
            <a href="<?php echo esc_url(add_query_arg('tab', 'updates', $settings_base_url)); ?>" class="nav-tab<?php echo 'updates' === $active_tab ? ' nav-tab-active' : ''; ?>">
                <?php esc_html_e('Plugin Updates', 'smart-purge-for-breeze-cache'); ?>
            </a>
            <?php endif; ?>
        </nav>

        <?php if ('updates' === $active_tab && $is_agency_build) : ?>
            <?php do_action('bsp_agency_settings_panel'); ?>
        <?php else : ?>
        <details class="bsp-intro-details">
            <summary>
                <span class="bsp-intro-summary-title"><?php esc_html_e('What does Smart Purge do?', 'smart-purge-for-breeze-cache'); ?></span>
                <span class="bsp-intro-summary-hint"><?php esc_html_e('Clears hub pages, grids, and archives when you update posts - expand for details.', 'smart-purge-for-breeze-cache'); ?></span>
            </summary>
            <div class="bsp-intro-body">
                <p><strong><?php esc_html_e('The Problem:', 'smart-purge-for-breeze-cache'); ?></strong> <?php esc_html_e('By default, Breeze aggressively caches content. When you update a post, it only clears the cache for that specific post. This leaves your important hub pages like: post grids, custom taxonomy archives, and page builder layouts, serving stale content to users.', 'smart-purge-for-breeze-cache'); ?></p>
                <p><strong><?php esc_html_e('The Solution:', 'smart-purge-for-breeze-cache'); ?></strong> <?php esc_html_e('This tool acts as a traffic controller. The Auto-Scanner detects which pages are querying specific Post Types, ensuring Breeze safely clears the cache for the parent pages whenever a post is updated.', 'smart-purge-for-breeze-cache'); ?></p>
            </div>
        </details>
        
        <form id="bsp-settings-form" method="post" action="" onsubmit="return false;">
            <?php wp_nonce_field('bsp_save_action'); ?>
            
            <div style="display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 20px;">
                <div style="flex: 1; min-width: 300px; background: #fff; padding: 20px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                    <h3 style="margin-top:0;">Global Settings</h3>
                    
                    <label>
                        <input type="checkbox" name="setting_force_sync" <?php checked($settings['force_sync'], 'yes'); ?>>
                        <strong>Force Synchronous Cloudflare Purge</strong>
                    </label>
                    <p class="description" style="margin: 0 0 15px 24px;">Bypasses the default WP-Cron delay so cache purges happen instantly on "Update".</p>

                    <label>
                        <input type="checkbox" name="setting_hide_utility" <?php checked($settings['hide_utility'], 'yes'); ?>>
                        <strong>Hide Utility Post Types from UI</strong>
                    </label>
                    <p class="description" style="margin: 0 0 20px 24px;">Hides background CPTs. <br><em>Auto-Detected: <code><?php echo esc_html(implode(', ', $hidden_type_slugs)); ?></code></em></p>
                    
                    <div style="display: flex; gap: 10px; margin-top: 20px;">
                        <button type="button" id="bsp-btn-scan" class="button button-secondary">Run Smart Scan</button>
                        <button type="button" class="button button-primary bsp-btn-save">Save All Changes</button>
                    </div>
                </div>

                <div style="flex: 2; min-width: 400px; background: #1e1e1e; color: #00ff00; padding: 15px; border-radius: 4px; font-family: monospace; min-height: 190px; max-height:400px; overflow-y: auto;">
                    <div style="color: #aaa; margin-bottom: 10px;">--- SYSTEM LOG & NOTIFICATIONS ---</div>
                    <span id="bsp-scan-log-text"><?php echo nl2br(esc_html($scan_log)); ?></span>
                </div>
            </div>

            <hr>

            <table class="form-table">
                <?php foreach ($public_post_types as $slug => $pt): ?>
                    <?php 
                        if ($settings['hide_utility'] === 'yes' && in_array($slug, $hidden_type_slugs)) continue; 
                        
                        $scanned_urls = (isset($scanned_map[$slug]) && !empty($scanned_map[$slug])) ? implode("\n", $scanned_map[$slug]) : '[No pages auto-detected]';
                        $manual_urls  = isset($manual_map[$slug]) ? implode("\n", $manual_map[$slug]) : '';
                        $ignored_urls = isset($ignored_map[$slug]) ? implode("\n", $ignored_map[$slug]) : '';
                    ?>
                    <tr>
                        <th scope="row" style="vertical-align: top; padding-right: 25px; padding-top:15px; padding-bottom:15px;">
                            <label>
                                <strong style="font-size: 1.1em;"><?php echo esc_html($pt->labels->name); ?></strong><br>
                                <code style="background: #f0f0f1; padding: 3px 6px;"><?php echo esc_html($slug); ?></code>
                            </label>
                            
                            <div style="margin-top: 5px; background: #f9f9f9; border: 1px solid #e2e4e7; padding: 12px; border-radius: 4px; font-weight: normal; font-size: 12px;">
                                <label style="display: block; margin-bottom: 6px;">
                                    <input type="checkbox" name="bsp_disable_archive[]" value="<?php echo esc_attr($slug); ?>" <?php checked(in_array($slug, $disable_archive_map)); ?>>
                                    Disable Archive Purge
                                </label>
                                <label style="display: block;">
                                    <input type="checkbox" name="bsp_disable_tax[]" value="<?php echo esc_attr($slug); ?>" <?php checked(in_array($slug, $disable_tax_map)); ?>>
                                    Disable Taxonomy Purge
                                </label>
                            </div>
                        </th>
                        <td>
                            <div style="display: flex; gap: 15px;">
                                <div style="flex: 1;">
                                    <strong>Auto-Scanned (Read Only)</strong><br>
                                    <textarea 
                                        id="scanned-map-<?php echo esc_attr($slug); ?>"
                                        readonly
                                        rows="4" 
                                        style="width: 100%; font-family: monospace; background: #f0f0f1; border-color: #ddd; color: #666; margin-top: 4px;"
                                    ><?php echo esc_textarea($scanned_urls); ?></textarea>
                                </div>

                                <div style="flex: 1;">
                                    <strong>Manual Additions</strong><br>
                                    <textarea 
                                        name="bsp_manual_map[<?php echo esc_attr($slug); ?>]" 
                                        rows="4" 
                                        style="width: 100%; font-family: monospace; margin-top: 4px;"
                                        placeholder="/example-custom-url/"
                                    ><?php echo esc_textarea($manual_urls); ?></textarea>
                                </div>

                                <div style="flex: 1;">
                                    <strong>Ignored URLs</strong> <span style="font-weight:normal; font-size:12px; color:#666;">(Type <code>*</code> to disable all)</span><br>
                                    <textarea 
                                        name="bsp_ignored_map[<?php echo esc_attr($slug); ?>]" 
                                        rows="4" 
                                        style="width: 100%; font-family: monospace; border-color: #ffbba1; margin-top: 4px;"
                                    ><?php echo esc_textarea($ignored_urls); ?></textarea>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>

            <p class="submit">
                <button type="button" class="button button-primary button-large bsp-btn-save">Save All Changes</button>
            </p>
        </form>
        <?php endif; ?>
    </div>

    <?php if ('settings' === $active_tab) : ?>
    <div id="bsp-toast" aria-live="polite"></div>
    <?php endif; ?>
    <?php
}

// ====================================================================
// 4. THE AUTO-SCANNER ALGORITHM
// ====================================================================

/**
 * Store in-progress scan log for polling (TTL 5 minutes).
 *
 * @param string $log      Accumulated log lines.
 * @param string $progress Current page / phase message.
 * @param string $status   running|complete|error|idle.
 * @param array|null $map  Scanned map when complete.
 */
function bsp_scan_progress_set( $log, $progress = '', $status = 'running', $map = null ) {
    $data = array(
        'status'   => $status,
        'log'      => $log,
        'progress' => $progress,
    );
    if ( null !== $map ) {
        $data['map'] = $map;
    }
    set_transient( 'bsp_scan_progress', $data, 300 );
}

/**
 * @return array{status:string,log:string,progress:string,map?:array}
 */
function bsp_scan_progress_get() {
    $progress = get_transient( 'bsp_scan_progress' );
    if ( ! is_array( $progress ) ) {
        return array(
            'status'   => 'idle',
            'log'      => '',
            'progress' => '',
        );
    }
    return $progress;
}

function bsp_execute_auto_scanner( $settings, $progress_callback = null ) {
    $scanned_map = [];
    $public_post_types = get_post_types(['public' => true], 'names');
    
    $utility_types = bsp_get_utility_post_types();
    $hidden_type_slugs = array_keys($utility_types);
    
    $pages = get_posts([
        'post_type' => 'page',
        'post_status' => 'publish',
        'posts_per_page' => -1
    ]);

    $total_pages = count( $pages );
    $log_output = "Scan initiated at " . current_time('mysql') . "\n";
    $log_output .= "Total Pages to scan: " . $total_pages . "\n";
    $log_output .= "----------------------------------------\n";
    $relations_found = 0;

    if ( is_callable( $progress_callback ) ) {
        $progress_callback( $log_output, __( 'Preparing scan...', 'smart-purge-for-breeze-cache' ) );
    }

    $page_index = 0;
    foreach ($pages as $page) {
        $page_index++;
        $content = $page->post_content;
        
        $page_path = wp_parse_url(get_permalink($page->ID), PHP_URL_PATH);
        if (empty($page_path) || $page_path === '') {
            $page_path = '/';
        }

        $elementor_data = bsp_normalize_builder_meta(get_post_meta($page->ID, '_elementor_data', true));
        $oxygen_data    = bsp_normalize_builder_meta(get_post_meta($page->ID, 'ct_builder_json', true));
        $beaver_data    = bsp_normalize_builder_meta(get_post_meta($page->ID, '_fl_builder_data', true));
        
        $bricks_data_1  = bsp_normalize_builder_meta(get_post_meta($page->ID, '_bricks_page_content', true));
        $bricks_data_2  = bsp_normalize_builder_meta(get_post_meta($page->ID, '_bricks_page_content_2', true));
        $bricks_data    = $bricks_data_1 . ' ' . $bricks_data_2;

        foreach ($public_post_types as $pt) {
            if ($settings['hide_utility'] === 'yes' && in_array($pt, $hidden_type_slugs)) continue;

            $found_builders = bsp_detect_post_type_hub_builders(
                array(
                    'post_type' => $pt,
                    'content'   => $content,
                    'elementor' => $elementor_data,
                    'bricks'    => $bricks_data,
                    'oxygen'    => $oxygen_data,
                    'beaver'    => $beaver_data,
                )
            );
            
            if (!empty($found_builders)) {
                if (!isset($scanned_map[$pt])) {
                    $scanned_map[$pt] = [];
                }
                if (!in_array($page_path, $scanned_map[$pt])) {
                    $scanned_map[$pt][] = $page_path;
                    
                    $b_names = implode(' & ', array_unique($found_builders));
                    $log_output .= "[DETECTED] $b_names mapped '$pt' to $page_path \n";
                    $relations_found++;

                    if ( is_callable( $progress_callback ) ) {
                        $progress_callback(
                            $log_output,
                            sprintf(
                                /* translators: 1: current page number, 2: total pages, 3: page title */
                                __( 'Scanning page %1$d of %2$d: %3$s', 'smart-purge-for-breeze-cache' ),
                                $page_index,
                                $total_pages,
                                $page->post_title
                            )
                        );
                    }
                }
            }
        }

        if ( is_callable( $progress_callback ) ) {
            $progress_callback(
                $log_output,
                sprintf(
                    /* translators: 1: current page number, 2: total pages, 3: page title */
                    __( 'Scanning page %1$d of %2$d: %3$s', 'smart-purge-for-breeze-cache' ),
                    $page_index,
                    $total_pages,
                    $page->post_title
                )
            );
        }
    }

    $log_output .= "----------------------------------------\n";
    $log_output .= "Scan Complete. Found $relations_found automatic URL mapping(s).\n";
    
    update_option('bsp_scanned_map', $scanned_map, false);
    return $log_output;
}

// ====================================================================
// 5. AJAX SERVER ENDPOINTS
// ====================================================================

add_action('wp_ajax_bsp_run_ajax_scan', 'bsp_ajax_scan_handler');
function bsp_ajax_scan_handler() {
    check_ajax_referer('bsp_save_action', '_wpnonce');
    if (!current_user_can('manage_options')) wp_send_json_error();
    
    $settings = wp_parse_args(get_option('bsp_settings', []), ['hide_utility' => 'yes', 'force_sync' => 'yes']);

    $progress_callback = function ( $log, $progress ) {
        bsp_scan_progress_set( $log, $progress, 'running' );
    };

    bsp_scan_progress_set(
        "Scan initiated at " . current_time( 'mysql' ) . "\n",
        __( 'Starting scan...', 'smart-purge-for-breeze-cache' ),
        'running'
    );

    $log = bsp_execute_auto_scanner( $settings, $progress_callback );
    update_option('bsp_scan_log', $log, false);

    $scanned_map = get_option( 'bsp_scanned_map', [] );
    bsp_scan_progress_set( $log, '', 'complete', $scanned_map );
    
    wp_send_json_success([
        'log' => esc_html($log),
        'map' => $scanned_map,
    ]);
}

add_action('wp_ajax_bsp_ajax_scan_status', 'bsp_ajax_scan_status_handler');
function bsp_ajax_scan_status_handler() {
    check_ajax_referer('bsp_save_action', '_wpnonce');
    if (!current_user_can('manage_options')) wp_send_json_error();

    $progress = bsp_scan_progress_get();
    wp_send_json_success( $progress );
}

add_action('wp_ajax_bsp_run_ajax_save', 'bsp_ajax_save_handler');
function bsp_ajax_save_handler() {
    check_ajax_referer('bsp_save_action', '_wpnonce');
    if (!current_user_can('manage_options')) wp_send_json_error();

    // Save Manual Map
    $new_manual_map = [];
    if (isset($_POST['bsp_manual_map']) && is_array($_POST['bsp_manual_map'])) {
        $unslashed_map = map_deep(wp_unslash($_POST['bsp_manual_map']), 'sanitize_textarea_field');
        foreach ($unslashed_map as $post_type => $urls_string) {
            $urls_array = array_unique(array_filter(array_map('sanitize_text_field', array_map('trim', explode("\n", $urls_string)))));
            $new_manual_map[sanitize_text_field($post_type)] = $urls_array;
        }
    }
    update_option('bsp_manual_map', $new_manual_map, false);

    // Save Ignored Map
    $new_ignored_map = [];
    if (isset($_POST['bsp_ignored_map']) && is_array($_POST['bsp_ignored_map'])) {
        $unslashed_ignored = map_deep(wp_unslash($_POST['bsp_ignored_map']), 'sanitize_textarea_field');
        foreach ($unslashed_ignored as $post_type => $urls_string) {
            $urls_array = array_unique(array_filter(array_map('sanitize_text_field', array_map('trim', explode("\n", $urls_string)))));
            $new_ignored_map[sanitize_text_field($post_type)] = $urls_array;
        }
    }
    update_option('bsp_ignored_map', $new_ignored_map, false);

    // Save Disable Archive & Tax Maps
    $disable_archive = isset($_POST['bsp_disable_archive']) && is_array($_POST['bsp_disable_archive']) 
        ? array_map('sanitize_text_field', wp_unslash($_POST['bsp_disable_archive'])) 
        : [];
    update_option('bsp_disable_archive_map', $disable_archive);

    $disable_tax = isset($_POST['bsp_disable_tax']) && is_array($_POST['bsp_disable_tax']) 
        ? array_map('sanitize_text_field', wp_unslash($_POST['bsp_disable_tax'])) 
        : [];
    update_option('bsp_disable_tax_map', $disable_tax);

    // Save Global Settings
    $settings = [
        'hide_utility' => isset($_POST['setting_hide_utility']) ? 'yes' : 'no',
        'force_sync'   => isset($_POST['setting_force_sync']) ? 'yes' : 'no'
    ];
    update_option('bsp_settings', $settings);

    wp_send_json_success();
}

// ====================================================================
// 6. UNINSTALL CLEANUP
// ====================================================================

register_uninstall_hook(__FILE__, 'bsp_plugin_uninstall');

function bsp_plugin_uninstall() {
    delete_option('bsp_settings');
    delete_option('bsp_scanned_map');
    delete_option('bsp_manual_map');
    delete_option('bsp_ignored_map');
    delete_option('bsp_disable_archive_map');
    delete_option('bsp_disable_tax_map');
    delete_option('bsp_scan_log');
    delete_option('bsp_needs_initial_scan');
    delete_transient('bsp_scan_summary_notice');
    delete_transient('bsp_scan_progress');
    delete_transient('bsp_github_release');
}

