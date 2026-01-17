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
 * Requires Plugins: github-plugin-manager-main
 */

if (!defined('ABSPATH')) exit;

add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($actions) {
    $settings_link = '<a href="' . admin_url('options-general.php?page=popup-banner-settings') . '">⚙️ Settings</a>';
    array_unshift($actions, $settings_link);
    $required_plugin = 'github-plugin-manager-main/github-plugin-manager.php';
    if (!function_exists('is_plugin_active')) {
        include_once(ABSPATH . 'wp-admin/includes/plugin.php');
    }
    
    if (!is_plugin_active($required_plugin)) {
        $plugin_path = WP_PLUGIN_DIR . '/' . $required_plugin;
        
        if (!file_exists($plugin_path)) {
            $download_link = '<a href="https://github.com/vadikonline1/github-plugin-manager/archive/refs/heads/main.zip" style="color: red;">
                              ⬇️ Requires Download
                            </a>';
            array_unshift($actions, $download_link);
        } else {
            $activate_link = '<span style="color: #f0ad4e;">⚠️ Plugin installed but not activated</span>';
            array_unshift($actions, $activate_link);
        }
    }    
    return $actions;
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
