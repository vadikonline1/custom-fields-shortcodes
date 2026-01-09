<?php
/*
 * Plugin Name: Social & Custom Fields Shortcodes
 * Plugin URI: https://github.com/vadikonline1/custom-fields-shortcodes
 * Description: Manage custom fields and social floating buttons with shortcodes.
 * Version: 1.2.0
 * Author: Steel..xD
 * License: GPL v2 or later
 * Text Domain: scfs-oop
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) exit;

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
register_uninstall_hook(__FILE__, ['SCFS\\Uninstaller', 'uninstall']);

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
