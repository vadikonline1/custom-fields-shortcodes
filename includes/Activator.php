<?php
namespace SCFS;

class Activator {
    private static $plugin_status_option = 'scfs_plugin_status';
    
    public static function activate() {
        // Creează tabela în baza de date
        AjaxHandler::create_database_table_on_activation();
        
        // Verifică dacă există date de migrat
        $ajax_handler = AjaxHandler::get_instance();
        if ($ajax_handler->has_data_to_migrate()) {
            error_log('SCFS: Data migration needed on activation');
        }
        
        // Adaugă opțiuni default dacă nu există
        self::add_default_options();
        
        // Setează statusul plugin-ului ca activ
        update_option(self::$plugin_status_option, 1);
        
        error_log('SCFS: Plugin activated');
    }

    public static function deactivate() {
        // Setează statusul plugin-ului ca inactiv
        update_option(self::$plugin_status_option, 0);
        
        error_log('SCFS: Plugin deactivated');
    }
    
    public static function uninstall() {
        // Curăță opțiunile la dezinstalare
        delete_option(self::$plugin_status_option);
        delete_option('scfs_social_settings');
        delete_option('scfs_cdn_settings_predefined');
        delete_option('scfs_cdn_settings_custom');
        delete_option('scfs_social_buttons');
        
        error_log('SCFS: Plugin uninstalled');
    }
    
    public static function is_active() {
        return (bool) get_option(self::$plugin_status_option, 1);
    }
    
    private static function add_default_options() {
        // Adaugă opțiuni pentru settings dacă nu există
        if (!get_option('scfs_social_settings')) {
            update_option('scfs_social_settings', [
                'position' => 'right',
                'button_color' => '#0073aa',
                'button_icon' => '☰',
                'animation' => 'slide',
                'mobile_enabled' => 1,
                'show_names' => 1,
                'transparent_icons' => 0,
                'custom_message' => "Let`s chat with US!",
                'show_custom_message' => 1,
                'show_shortcut_names' => 1,
                'button_primary_color' => 'var(--e-global-color-primary)',
                'button_secondary_color' => 'var(--e-global-color-secondary)',
                'use_theme_colors' => 1
            ]);
        }
        
        // Adaugă statusul plugin-ului dacă nu există
        if (!get_option(self::$plugin_status_option)) {
            update_option(self::$plugin_status_option, 1);
        }
    }
}
