<?php
/*
Plugin Name: Social & Custom Fields Shortcodes
Description: Manage custom fields and social floating buttons with shortcodes.
Version: 1.0.1
Author: Steel..xD
Text Domain: scfs-oop
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

// Hook pentru crearea tabelei la activarea plugin-ului
register_activation_hook(__FILE__, ['SCFS\\AjaxHandler', 'create_database_table_on_activation']);