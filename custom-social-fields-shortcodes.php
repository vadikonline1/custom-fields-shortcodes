<?php
/*
Plugin Name: Social & Custom Fields Shortcodes
Description: Manage custom fields and social floating buttons with shortcodes and modals.
Version: 0.0.4
Author: Steel..xD
Plugin URI: https://github.com/vadikonline1/custom-fields-shortcodes/
Author URI: https://github.com/vadikonline1/custom-fields-shortcodes/
GitHub Plugin URI: https://github.com/vadikonline1/custom-fields-shortcodes/
Text Domain: sc-fields-shortcodes
*/

if (!defined('ABSPATH')) exit;

// Include module files
require_once plugin_dir_path(__FILE__) . 'custom-fields-shortcodes.php';
require_once plugin_dir_path(__FILE__) . 'social-popup-buttons.php';

// Funcție pentru a extrage versiunea din header
function scfs_get_plugin_version() {
    static $version = null;
    
    if ($version === null) {
        $plugin_data = get_file_data(__FILE__, array('Version' => 'Version'), 'plugin');
        $version = $plugin_data['Version'];
    }
    
    return $version;
}

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

// General Info Page (Main menu) - MODIFICATĂ CU BUTOANELE SOCIAL
function scfs_admin_overview_page(){
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    
    // Verificare versiune pentru badge
    $version_check = scfs_check_version_status();
    $current_version = scfs_get_plugin_version();
    
    // Obține butoanele sociale pentru a le afișa
    $social_buttons = get_option('sfb_buttons', []);
    $settings = get_option('sfb_settings', [
        'show_shortcut_names' => 1,
        'transparent_icons' => 0
    ]);
    ?>
    <div class="wrap">
        <h1>Social & Custom Fields Shortcodes</h1>
        
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 20px;">
            <!-- Left Column - Plugin Info -->
            <div style="flex: 1;">
                <div class="card" style="background: #f6f7f7; padding: 20px; border-radius: 4px; border-left: 4px solid #2271b1; margin-bottom: 20px;">
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

<!-- Social Buttons Shortcodes Section -->
<div class="card" style="background: #f0f6fc; padding: 20px; border-radius: 4px; border-left: 4px solid #0073aa; margin-bottom: 20px;">
    <h3>🔗 Social Buttons Shortcodes</h3>
    
    <!-- Butoane individuale - MODIFICAT: DOAR EXEMPLU -->
    <?php if(!empty($social_buttons)): ?>
    <div style="margin-bottom: 25px;">
        <h4>Individual Button Shortcodes</h4>
        <div style="background: #fff; padding: 15px; border-radius: 4px; border: 1px solid #ddd;">
            <p style="margin-top: 0; margin-bottom: 15px; color: #666;">
                <strong>Example for button "<?php echo esc_html($social_buttons[0]['name']); ?>":</strong>
            </p>
            <div style="display: grid; grid-template-columns: 1fr; gap: 8px;">
                <div>
                    <strong>Default:</strong>
                    <code style="display: block; background: #f1f1f1; padding: 8px; border-radius: 3px; margin-top: 5px; font-size: 12px; cursor: pointer;" onclick="copyToClipboard(this)">
                        [sfb_button id="<?php echo esc_attr($social_buttons[0]['id']); ?>"]
                    </code>
                </div>
                <div>
                    <strong>Transparent:</strong>
                    <code style="display: block; background: #f1f1f1; padding: 8px; border-radius: 3px; margin-top: 5px; font-size: 12px; cursor: pointer;" onclick="copyToClipboard(this)">
                        [sfb_button id="<?php echo esc_attr($social_buttons[0]['id']); ?>" transparent="true"]
                    </code>
                </div>
                <div>
                    <strong>Icon Only (Transparent):</strong>
                    <code style="display: block; background: #f1f1f1; padding: 8px; border-radius: 3px; margin-top: 5px; font-size: 12px; cursor: pointer;" onclick="copyToClipboard(this)">
                        [sfb_button id="<?php echo esc_attr($social_buttons[0]['id']); ?>" show_name="false" transparent="true"]
                    </code>
                </div>
            </div>
            <p style="margin: 10px 0 0 0; font-size: 12px; color: #666;">
                💡 <strong>Replace the ID</strong> with any button ID from the Social Buttons settings page.
            </p>
        </div>
    </div>
    <?php else: ?>
    <div style="margin-bottom: 25px;">
        <h4>Individual Button Shortcodes</h4>
        <div style="background: #fff; padding: 15px; border-radius: 4px; border: 1px solid #ddd; text-align: center;">
            <p style="margin: 0; color: #666;">
                No social buttons created yet. 
                <a href="<?php echo admin_url('admin.php?page=social-floating-buttons&action=add'); ?>">Create your first button</a>
            </p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Toate butoanele -->
    <div style="margin-bottom: 20px;">
        <h4>All Buttons Shortcodes</h4>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
            <div style="background: #fff; padding: 12px; border-radius: 4px; border: 1px solid #ddd;">
                <strong>Default Display</strong>
                <code style="display: block; background: #f1f1f1; padding: 8px; border-radius: 3px; margin-top: 8px; font-size: 12px; cursor: pointer;" onclick="copyToClipboard(this)">
                    [sfb_all_buttons]
                </code>
            </div>
            <div style="background: #fff; padding: 12px; border-radius: 4px; border: 1px solid #ddd;">
                <strong>Icons Only</strong>
                <code style="display: block; background: #f1f1f1; padding: 8px; border-radius: 3px; margin-top: 8px; font-size: 12px; cursor: pointer;" onclick="copyToClipboard(this)">
                    [sfb_all_buttons show_names="false"]
                </code>
            </div>
            <div style="background: #fff; padding: 12px; border-radius: 4px; border: 1px solid #ddd;">
                <strong>Transparent Icons</strong>
                <code style="display: block; background: #f1f1f1; padding: 8px; border-radius: 3px; margin-top: 8px; font-size: 12px; cursor: pointer;" onclick="copyToClipboard(this)">
                    [sfb_all_buttons transparent="true"]
                </code>
            </div>
            <div style="background: #fff; padding: 12px; border-radius: 4px; border: 1px solid #ddd;">
                <strong>Transparent Icons Only</strong>
                <code style="display: block; background: #f1f1f1; padding: 8px; border-radius: 3px; margin-top: 8px; font-size: 12px; cursor: pointer;" onclick="copyToClipboard(this)">
                    [sfb_all_buttons show_names="false" transparent="true"]
                </code>
            </div>
        </div>
    </div>

    <!-- Floating Buttons -->
    <div>
        <h4>Floating Buttons</h4>
        <div style="background: #fff; padding: 12px; border-radius: 4px; border: 1px solid #ddd;">
            <code style="display: block; background: #f1f1f1; padding: 8px; border-radius: 3px; font-size: 12px; cursor: pointer;" onclick="copyToClipboard(this)">
                [sfb_floating]
            </code>
            <p style="margin: 8px 0 0 0; font-size: 12px; color: #666;">
                Displays the floating social buttons menu
            </p>
        </div>
    </div>
</div>

                <!-- Custom Fields Shortcodes -->
                <div class="card" style="background: #f0f8f4; padding: 20px; border-radius: 4px; border-left: 4px solid #46b450;">
                    <h3>🛠️ Custom Fields Shortcodes</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div>
                            <h4>Basic Usage</h4>
                            <code style="display: block; background: #fff; padding: 8px; border-radius: 3px;">[custom_field name="field_name"]</code>
                            <code style="display: block; background: #fff; padding: 8px; border-radius: 3px; margin-top: 5px;">[custom_field name="phone"]</code>
                        </div>
                        <div>
                            <h4>Advanced Usage</h4>
                            <code style="display: block; background: #fff; padding: 8px; border-radius: 3px;">[custom_field name="email" default="example@email.com"]</code>
                            <code style="display: block; background: #fff; padding: 8px; border-radius: 3px; margin-top: 5px;">[custom_field name="address" class="my-class"]</code>
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
                    <span style="color: #0073aa;">v<?php echo esc_html($current_version); ?></span>
                    <?php if ($version_check['status'] === 'latest') : ?>
                        <span style="background: #46b450; color: white; padding: 2px 8px; border-radius: 10px; font-size: 11px; margin-left: 5px;">Latest</span>
                    <?php elseif ($version_check['status'] === 'update_available') : ?>
                        <span style="background: #ffb900; color: white; padding: 2px 8px; border-radius: 10px; font-size: 11px; margin-left: 5px;">Update Available: v<?php echo esc_html($version_check['latest_version']); ?></span>
                    <?php else : ?>
                        <span style="background: #dc3232; color: white; padding: 2px 8px; border-radius: 10px; font-size: 11px; margin-left: 5px;">Check Failed</span>
                        <div style="font-size: 11px; color: #666; margin-top: 5px;">Error: <?php echo esc_html($version_check['error']); ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- Quick Stats -->
                <div style="margin-bottom: 15px; padding: 10px; background: #f9f9f9; border-radius: 4px;">
                    <strong>📊 Quick Stats:</strong><br>
                    <div style="font-size: 12px; margin-top: 5px;">
                        • Social Buttons: <?php echo count($social_buttons); ?><br>
                        • Custom Fields: <?php echo count(get_option('cfs_custom_fields', [])); ?>
                    </div>
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
    function copyToClipboard(element) {
        var text = element.textContent || element.innerText;
        var textArea = document.createElement("textarea");
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand("copy");
        document.body.removeChild(textArea);
        
        // Show copied message
        var originalText = element.textContent;
        element.textContent = 'Copied!';
        element.style.background = '#d1f7c4';
        
        setTimeout(function() {
            element.textContent = originalText;
            element.style.background = '#f1f1f1';
        }, 1500);
    }

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
                    if (response.data.status === 'update_available') {
                        resultDiv.html('<div style="color: #ffb900;">⚠ ' + response.data.message + '</div>').show();
                    } else {
                        resultDiv.html('<div style="color: #46b450;">✓ ' + response.data.message + '</div>').show();
                    }
                } else {
                    resultDiv.html('<div style="color: #dc3232;">✗ ' + response.data + '</div>').show();
                }
            }).fail(function() {
                resultDiv.html('<div style="color: #dc3232;">✗ Check failed. Try again.</div>').show();
            }).always(function() {
                button.prop('disabled', false).text('🔄 Check for Updates');
                // Reîmprospătare pagină pentru a afișa statusul actualizat
                setTimeout(function() {
                    location.reload();
                }, 2000);
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
        transition: all 0.3s ease;
    }
    code:hover {
        background: #e8e8e8 !important;
    }
    </style>
    <?php
}

// Funcție pentru verificarea statusului versiunii - verifică direct fișierul de pe GitHub
function scfs_check_version_status() {
    $current_version = scfs_get_plugin_version();
    $cache_key = 'scfs_version_check';
    $cached = get_transient($cache_key);
    
    if ($cached !== false) {
        return $cached;
    }
    
    $result = [
        'status' => 'check_failed',
        'current_version' => $current_version,
        'latest_version' => $current_version,
        'message' => 'Version check failed',
        'error' => 'Unknown error'
    ];
    
    // URL-ul principal pentru verificare
    $remote_url = 'https://raw.githubusercontent.com/vadikonline1/custom-fields-shortcodes/main/custom-social-fields-shortcodes.php';
    
    $response = wp_remote_get($remote_url, ['timeout' => 10]);
    
    if (is_wp_error($response)) {
        $result['error'] = 'HTTP Error: ' . $response->get_error_message();
        set_transient($cache_key, $result, 15 * MINUTE_IN_SECONDS);
        return $result;
    }
    
    if (wp_remote_retrieve_response_code($response) !== 200) {
        $result['error'] = 'HTTP Status: ' . wp_remote_retrieve_response_code($response) . ' - URL: ' . $remote_url;
        set_transient($cache_key, $result, 15 * MINUTE_IN_SECONDS);
        return $result;
    }
    
    $remote_plugin_data = wp_remote_retrieve_body($response);
    
    if (preg_match('/Version:\s*([0-9.]+)/', $remote_plugin_data, $matches)) {
        $latest_version = trim($matches[1]);
        
        if (version_compare($latest_version, $current_version, '>')) {
            $result = [
                'status' => 'update_available',
                'current_version' => $current_version,
                'latest_version' => $latest_version,
                'message' => 'Update available: v' . $latest_version,
                'error' => ''
            ];
        } else {
            $result = [
                'status' => 'latest',
                'current_version' => $current_version,
                'latest_version' => $latest_version,
                'message' => 'You have the latest version',
                'error' => ''
            ];
        }
    } else {
        $result['error'] = 'Could not extract version from remote file. URL: ' . $remote_url;
    }
    
    // Cache pentru 1 oră (sau 15 minute pentru erori)
    $cache_time = ($result['status'] === 'check_failed') ? 15 * MINUTE_IN_SECONDS : HOUR_IN_SECONDS;
    set_transient($cache_key, $result, $cache_time);
    
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

// Auto-update from GitHub - METODA SIMPLĂ DIRECTĂ
add_filter('site_transient_update_plugins', function($transient){
    if(empty($transient->checked)) return $transient;

    $plugin_slug = plugin_basename(__FILE__);
    
    // Verifică dacă plugin-ul curent este în lista de checked
    if (!isset($transient->checked[$plugin_slug])) {
        return $transient;
    }

    // Folosim același URL ca în funcția de verificare
    $remote_url = 'https://raw.githubusercontent.com/vadikonline1/custom-fields-shortcodes/main/custom-social-fields-shortcodes.php';
    
    $response = wp_remote_get($remote_url, ['timeout' => 10]);
    if(is_wp_error($response)) return $transient;

    $remote_plugin_data = $response['body'];
    
    if(preg_match('/Version:\s*([0-9.]+)/', $remote_plugin_data, $matches)){
        $remote_version = trim($matches[1]);
        $current_version = $transient->checked[$plugin_slug];

        if(version_compare($remote_version, $current_version, '>')){
            $transient->response[$plugin_slug] = (object) [
                'slug' => dirname($plugin_slug),
                'plugin' => $plugin_slug,
                'new_version' => $remote_version,
                'url' => 'https://github.com/vadikonline1/custom-fields-shortcodes/',
                'package' => 'https://github.com/vadikonline1/custom-fields-shortcodes/archive/refs/heads/main.zip',
                'tested' => get_bloginfo('version')
            ];
        }
    }
    return $transient;
});

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

