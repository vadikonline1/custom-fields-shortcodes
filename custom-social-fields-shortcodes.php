<?php
/*
Plugin Name: Social & Custom Fields Shortcodes
Description: Manage custom fields and social floating buttons with shortcodes and modals.
Version: 1.0.1
Author: Steel..xD
Author URI: https://github.com/vadikonline1
Text Domain: sc-fields-shortcodes
*/

if (!defined('ABSPATH')) exit;

// Include module files
require_once plugin_dir_path(__FILE__) . 'custom-fields-shortcodes.php';
require_once plugin_dir_path(__FILE__) . 'social-popup-buttons.php';

// Settings link
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'scfs_settings_link');
function scfs_settings_link($links) {
    if (!current_user_can('manage_options')) {
        return $links;
    }
    
    $settings_link = '<a href="' . admin_url('admin.php?page=sc-fields-shortcodes') . '">' . __('Settings', 'sc-fields-shortcodes') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

// Row meta
add_filter('plugin_row_meta', 'scfs_plugin_row_meta', 10, 2);
function scfs_plugin_row_meta($links, $file) {
    if ($file === plugin_basename(__FILE__)) {
        $links[] = '<a href="https://github.com/vadikonline1/custom-fields-shortcodes" target="_blank" rel="noopener">' . __('Documentation', 'sc-fields-shortcodes') . '</a>';
    }
    return $links;
}

// Admin menu
add_action('admin_menu', 'scfs_admin_menu');
function scfs_admin_menu() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // Main menu
    add_menu_page(
        'S&C - Fields Shortcodes',
        'S&C - Fields Shortcodes',
        'manage_options',
        'sc-fields-shortcodes',
        'scfs_admin_overview_page',
        'dashicons-admin-generic',
        20
    );

    // Submenu: Custom Fields Shortcodes
    add_submenu_page(
        'sc-fields-shortcodes',
        'Custom Fields Shortcodes',
        'Custom Fields Shortcodes',
        'manage_options',
        'custom-fields-shortcodes',
        'cfs_admin_page' // Din custom-fields-shortcodes.php
    );

    // Submenu: Social Floating Buttons
    add_submenu_page(
        'sc-fields-shortcodes',
        'Social Floating Buttons',
        'Social Floating Buttons',
        'manage_options',
        'social-floating-buttons',
        'sfb_admin_page' // Din social-popup-buttons.php
    );
}

// General Info Page (Main menu)
function scfs_admin_overview_page(){
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    
    // Verificare versiune pentru badge
    $version_check = scfs_check_version_status();
    ?>
    <div class="wrap">
        <h1>Social & Custom Fields Shortcodes</h1>
        
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 20px;">
            <!-- Left Column - Plugin Info -->
            <div style="display: inline-flex;gap: 10px;">
                <div class="card" style="background: #f6f7f7; padding: 20px; border-radius: 4px; border-left: 4px solid #2271b1;margin-top: 0px;">
                    <h2 style="margin-top: 0;">Plugin Features</h2>
                    <p>This plugin allows you to manage:</p>
                    <ul style="list-style: disc; margin-left: 20px; margin-bottom: 20px;">
                        <li><strong>Custom Fields Shortcodes</strong> – create custom fields and display them with shortcodes.</li>
                        <li><strong>Social Floating Buttons</strong> – add social buttons with icons and floating styles.</li>
                    </ul>
                    
                    <!-- Quick Links Buttons -->
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <a href="<?php echo admin_url('admin.php?page=custom-fields-shortcodes'); ?>" class="button button-primary">
                            🛠️ Custom Fields Settings
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=social-floating-buttons'); ?>" class="button button-primary">
                            🔗 Social Buttons Settings
                        </a>
                    </div>
                </div>

                <!-- Shortcode Examples -->
                <div class="card" style="background: #f0f6fc; padding: 20px; border-radius: 4px; border-left: 4px solid #0073aa; margin-top: 0px;">
                    <h3>Shortcode Examples</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div>
                            <h4>Custom Fields</h4>
                            <code style="display: block; background: #fff; padding: 8px; border-radius: 3px;">[custom_field name="field_name"]</code>
                            <code style="display: block; background: #fff; padding: 8px; border-radius: 3px; margin-top: 5px;">[custom_field name="phone"]</code>
                        </div>
                        <div>
                            <h4>Social Buttons</h4>
                            <code style="display: block; background: #fff; padding: 8px; border-radius: 3px;">[sfb_floating]</code>
                            <code style="display: block; background: #fff; padding: 8px; border-radius: 3px; margin-top: 5px;">[sfb_popup]</code>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Developer Info -->
            <div style="width: 300px; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; box-shadow: 0 1px 1px rgba(0,0,0,0.04);">
                <h3 style="margin-top: 0; border-bottom: 1px solid #ddd; padding-bottom: 10px;">Developer Information</h3>
                
                <div style="margin-bottom: 15px;">
                    <strong>👨‍💻 Author:</strong><br>
                    <span style="color: #2271b1;">Steel..xD</span>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <strong>🔗 GitHub:</strong><br>
                    <a href="https://github.com/vadikonline1/custom-fields-shortcodes" target="_blank" rel="noopener" style="text-decoration: none;">
                        📁 vadikonline1/custom-fields-shortcodes
                    </a>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <strong>🔄 Version:</strong><br>
                    <span style="color: #0073aa;">v1.0.0</span>
                    <?php if ($version_check['status'] === 'latest') : ?>
                        <span style="background: #46b450; color: white; padding: 2px 8px; border-radius: 10px; font-size: 11px; margin-left: 5px;">Latest</span>
                    <?php elseif ($version_check['status'] === 'update_available') : ?>
                        <span style="background: #ffb900; color: white; padding: 2px 8px; border-radius: 10px; font-size: 11px; margin-left: 5px;">Update Available</span>
                    <?php else : ?>
                        <span style="background: #dc3232; color: white; padding: 2px 8px; border-radius: 10px; font-size: 11px; margin-left: 5px;">Check Failed</span>
                    <?php endif; ?>
                </div>
                
                <!-- Version Check Button -->
                <div style="text-align: center; margin-top: 20px; padding-top: 15px; border-top: 1px solid #ddd;">
                    <button type="button" id="scfs-check-version" class="button button-secondary" style="width: 100%;">
                        🔄 Check for Updates
                    </button>
                    <div id="scfs-version-result" style="margin-top: 10px; font-size: 12px; display: none;"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        $('#scfs-check-version').on('click', function() {
            var button = $(this);
            var resultDiv = $('#scfs-version-result');
            
            button.prop('disabled', true).text('Checking...');
            resultDiv.hide().empty();
            
            $.post(ajaxurl, {
                action: 'scfs_check_version',
                nonce: '<?php echo wp_create_nonce("scfs_version_check"); ?>'
            }, function(response) {
                if (response.success) {
                    resultDiv.html('<div style="color: #46b450;">✓ ' + response.data.message + '</div>').show();
                } else {
                    resultDiv.html('<div style="color: #dc3232;">✗ ' + response.data + '</div>').show();
                }
            }).fail(function() {
                resultDiv.html('<div style="color: #dc3232;">✗ Check failed. Try again.</div>').show();
            }).always(function() {
                button.prop('disabled', false).text('🔄 Check for Updates');
            });
        });
    });
    </script>
    
    <style>
    .card {
        background: #fff;
        border: 1px solid #ccd0d4;
        border-radius: 4px;
        box-shadow: 0 1px 1px rgba(0,0,0,0.04);
    }
    code {
        font-family: 'Courier New', monospace;
        font-size: 13px;
    }
    </style>
    <?php
}

// Funcție pentru verificarea statusului versiunii
function scfs_check_version_status() {
    $current_version = '1.0.0';
    $cache_key = 'scfs_version_check';
    $cached = get_transient($cache_key);
    
    if ($cached !== false) {
        return $cached;
    }
    
    $result = [
        'status' => 'check_failed',
        'current_version' => $current_version,
        'latest_version' => $current_version,
        'message' => 'Version check failed'
    ];
    
    $api_url = "https://api.github.com/repos/vadikonline1/custom-fields-shortcodes/releases/latest";
    
    $response = wp_remote_get($api_url, [
        'headers' => [
            'User-Agent' => 'WordPress Plugin',
            'Accept' => 'application/vnd.github.v3+json'
        ],
        'timeout' => 10
    ]);
    
    if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
        $body = wp_remote_retrieve_body($response);
        $release = json_decode($body);
        
        if (isset($release->tag_name)) {
            $latest_version = ltrim($release->tag_name, 'v');
            
            if (version_compare($latest_version, $current_version, '>')) {
                $result = [
                    'status' => 'update_available',
                    'current_version' => $current_version,
                    'latest_version' => $latest_version,
                    'message' => 'Update available: v' . $latest_version
                ];
            } else {
                $result = [
                    'status' => 'latest',
                    'current_version' => $current_version,
                    'latest_version' => $latest_version,
                    'message' => 'You have the latest version'
                ];
            }
        }
    }
    
    // Cache pentru 1 oră
    set_transient($cache_key, $result, HOUR_IN_SECONDS);
    
    return $result;
}

// AJAX handler pentru verificarea versiunii
add_action('wp_ajax_scfs_check_version', 'scfs_ajax_check_version');
function scfs_ajax_check_version() {
    if (!wp_verify_nonce($_POST['nonce'], 'scfs_version_check') || !current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    
    // Șterge cache-ul pentru forțarea unei verificări fresh
    delete_transient('scfs_version_check');
    
    $version_info = scfs_check_version_status();
    
    wp_send_json_success([
        'message' => $version_info['message'],
        'status' => $version_info['status'],
        'current' => $version_info['current_version'],
        'latest' => $version_info['latest_version']
    ]);
}

// Activation hook
register_activation_hook(__FILE__, 'scfs_plugin_activation');
function scfs_plugin_activation() {
    update_option('sfb_auto_display', 1);
    add_option('scfs_plugin_activated', true);
    // Șterge cache-ul versiunii la activare
    delete_transient('scfs_version_check');
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'scfs_plugin_deactivation');
function scfs_plugin_deactivation() {
    delete_option('scfs_plugin_activated');
    delete_transient('scfs_version_check');
}

// Auto-display social buttons in footer
add_action('wp_footer', 'scfs_display_social_buttons');
function scfs_display_social_buttons() {
    if (get_option('sfb_auto_display', 0)) {
        echo do_shortcode('[sfb_floating]');
    }
}

// GitHub updater (rămâne la fel ca în versiunea anterioară)
add_filter('site_transient_update_plugins', 'scfs_github_updater');
function scfs_github_updater($transient) {
    if (empty($transient->checked)) {
        return $transient;
    }

    $plugin_slug = plugin_basename(__FILE__);
    
    if (!isset($transient->checked[$plugin_slug])) {
        return $transient;
    }

    $current_version = $transient->checked[$plugin_slug];
    $repo_user = 'vadikonline1';
    $repo_name = 'custom-fields-shortcodes';
    $api_url = "https://api.github.com/repos/$repo_user/$repo_name/releases/latest";

    $cache_key = 'scfs_github_latest_version';
    $cached_data = get_transient($cache_key);

    if (false === $cached_data) {
        $response = wp_remote_get($api_url, [
            'headers' => [
                'User-Agent' => 'WordPress Plugin Updater',
                'Accept' => 'application/vnd.github.v3+json'
            ],
            'timeout' => 10
        ]);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            set_transient($cache_key, ['error' => true], 30 * MINUTE_IN_SECONDS);
            return $transient;
        }

        $body = wp_remote_retrieve_body($response);
        $release = json_decode($body);

        if (!isset($release->tag_name, $release->zipball_url)) {
            set_transient($cache_key, ['error' => true], 30 * MINUTE_IN_SECONDS);
            return $transient;
        }

        $remote_version = ltrim($release->tag_name, 'v');
        $cached_data = [
            'version' => $remote_version,
            'package' => $release->zipball_url,
            'url' => "https://github.com/$repo_user/$repo_name/releases/latest"
        ];

        set_transient($cache_key, $cached_data, 12 * HOUR_IN_SECONDS);
    }

    if (isset($cached_data['error'])) {
        return $transient;
    }

    if (version_compare($cached_data['version'], $current_version, '>')) {
        $transient->response[$plugin_slug] = (object) [
            'slug' => dirname($plugin_slug),
            'new_version' => $cached_data['version'],
            'url' => $cached_data['url'],
            'package' => $cached_data['package'],
            'tested' => get_bloginfo('version')
        ];
    }

    return $transient;
}

// Load textdomain
add_action('plugins_loaded', 'scfs_load_textdomain');
function scfs_load_textdomain() {
    load_plugin_textdomain('sc-fields-shortcodes', false, dirname(plugin_basename(__FILE__)) . '/languages');
}

// Admin notice for activation
add_action('admin_notices', 'scfs_activation_notice');
function scfs_activation_notice() {
    if (get_option('scfs_plugin_activated')) {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Social & Custom Fields Shortcodes plugin has been activated! Configure it from the <strong>S&C - Fields Shortcodes</strong> menu.', 'sc-fields-shortcodes'); ?></p>
        </div>
        <?php
        delete_option('scfs_plugin_activated');
    }
}
