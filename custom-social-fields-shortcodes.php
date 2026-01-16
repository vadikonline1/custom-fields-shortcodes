<?php
/*
 * Plugin Name: Social & Custom Fields Shortcodes
 * Plugin URI: https://github.com/vadikonline1/custom-fields-shortcodes
 * Description: Manage custom fields and social floating buttons with shortcodes.
 * Version: 1.3.0
 * Author: Steel..xD
 * License: GPL v2 or later
 * Text Domain: scfs-oop
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) exit;

add_action('admin_init', function() {
    // Only run in admin area
    if (!is_admin()) return;
    
    if (!function_exists('is_plugin_active')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    
    $required_plugin = 'github-plugin-manager/github-plugin-manager.php';
    $current_plugin = plugin_basename(__FILE__);
    
    // If current plugin is active but required plugin is not
    if (is_plugin_active($current_plugin) && !is_plugin_active($required_plugin)) {
        // Deactivate current plugin
        deactivate_plugins($current_plugin);
        
        // Show admin notice
        add_action('admin_notices', function() {
            $plugin_name = get_plugin_data(__FILE__)['Name'] ?? 'This plugin';
            ?>
            <div class="notice notice-error">
                <p>
                    <strong><?php echo esc_html($plugin_name); ?></strong> has been deactivated.
                    <br>
                    This plugin requires <strong>GitHub Plugin Manager</strong> to function properly.
                </p>
                <p>
                    <strong>How to fix:</strong>
                    <ol style="margin-left: 20px;">
                        <li>Download <a href="https://github.com/vadikonline1/github-plugin-manager" target="_blank">GitHub Plugin Manager from GitHub</a></li>
                        <li>Go to WordPress Admin → Plugins → Add New → Upload Plugin</li>
                        <li>Upload the downloaded ZIP file and activate it</li>
                        <li>Reactivate <?php echo esc_html($plugin_name); ?></li>
                    </ol>
                </p>
                <p>
                    <a href="https://github.com/vadikonline1/github-plugin-manager/archive/refs/heads/main.zip" 
                       class="button button-primary"
                       style="margin-right: 10px;">
                        ⬇️ Download Plugin (ZIP)
                    </a>
                    <a href="<?php echo admin_url('plugin-install.php?tab=upload'); ?>" 
                       class="button">
                        📤 Upload to WordPress
                    </a>
                </p>
            </div>
            <?php
        });
    }
});

// Prevent activation without required plugin
register_activation_hook(__FILE__, function() {
    if (!function_exists('is_plugin_active')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    
    if (!is_plugin_active('github-plugin-manager/github-plugin-manager.php')) {
        $plugin_name = get_plugin_data(__FILE__)['Name'] ?? 'This plugin';
        
        // Create a user-friendly error message
        $error_message = '
        <div style="max-width: 700px; margin: 50px auto; padding: 30px; background: #fff; border: 2px solid #d63638; border-radius: 5px;">
            <h2 style="color: #d63638; margin-top: 0;">
                <span style="font-size: 24px;">⚠️</span> Missing Required Plugin
            </h2>
            
            <p><strong>' . esc_html($plugin_name) . '</strong> cannot be activated because it requires another plugin to be installed first.</p>
            
            <div style="background: #f0f6fc; padding: 20px; border-radius: 4px; margin: 20px 0;">
                <h3 style="margin-top: 0;">Required Plugin: GitHub Plugin Manager</h3>
                <p>This plugin manages GitHub repositories directly from your WordPress dashboard.</p>
            </div>
            
            <h3>Installation Steps:</h3>
            <ol>
                <li><strong>Download:</strong> Get the plugin from <a href="https://github.com/vadikonline1/github-plugin-manager" target="_blank">GitHub</a></li>
                <li><strong>Upload:</strong> Go to <a href="' . admin_url('plugin-install.php?tab=upload') . '">Plugins → Add New → Upload Plugin</a></li>
                <li><strong>Activate:</strong> Activate the GitHub Plugin Manager</li>
                <li><strong>Return:</strong> Come back and activate ' . esc_html($plugin_name) . '</li>
            </ol>
            
            <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid #ddd;">
                <a href="https://github.com/vadikonline1/github-plugin-manager/archive/refs/heads/main.zip" 
                   class="button button-primary button-large"
                   style="margin-right: 10px;">
                    Download ZIP File
                </a>
                <a href="' . admin_url('plugins.php') . '" class="button button-large">
                    Return to Plugins
                </a>
            </div>
            
            <p style="margin-top: 20px; color: #666; font-size: 13px;">
                <strong>Note:</strong> All plugins that require GitHub Plugin Manager will be deactivated until it is installed.
            </p>
        </div>';
        
        // Stop activation with the error message
        wp_die($error_message, 'Missing Required Plugin', 200);
    }
});

// Autoloader pentru clase
spl_autoload_register(function ($class) {
    $prefix = 'SCFS\\';
    $base_dir = plugin_dir_path(__FILE__) . 'includes/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) require_once $file;
});

// Inițializare plugin
add_action('plugins_loaded', function() {
    // Initializează Main class care va înregistra toate meniurile
    $main = SCFS\Main::get_instance(__FILE__);
    
    // Initializează AJAX handler
    SCFS\AjaxHandler::get_instance();
    
    // Initializează Frontend
    SCFS\Frontend::get_instance();
});

register_activation_hook(__FILE__, ['SCFS\\AjaxHandler', 'create_database_table_on_activation']);

// Hook pentru activare
register_activation_hook(__FILE__, ['SCFS\\Activator', 'activate']);

// Hook pentru dezactivare
register_deactivation_hook(__FILE__, ['SCFS\\Activator', 'deactivate']);

// Hook pentru dezinstalare
register_uninstall_hook(__FILE__, ['SCFS\\Activator', 'uninstall']);

// Funcție pentru a obține instanța principală a plugin-ului (pentru compatibilitate)
function scfs() {
    return SCFS\Main::get_instance();
}

// Adaugă link-uri pentru donații/recenzii (opțional)
add_filter('plugin_row_meta', function($links, $file) {
    if (plugin_basename(__FILE__) === $file) {
        $row_meta = [
            'docs' => '<a href="https://github.com/vadikonline1/custom-fields-shortcodes/wiki" target="_blank">Documentation</a>',
            'support' => '<a href="https://github.com/vadikonline1/custom-fields-shortcodes/issues" target="_blank">Support</a>',
        ];
        
        return array_merge($links, $row_meta);
    }
    
    return $links;
}, 10, 2);

// Funcție pentru compatibilitate cu cod vechi (dacă era folosită clasa SCFS_Main_Plugin)
if (!class_exists('SCFS_Main_Plugin')) {
    class SCFS_Main_Plugin {
        public static function get_instance() {
            return SCFS\Main::get_instance();
        }
    }
}
