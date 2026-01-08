<?php
namespace SCFS;

class Activator {
    public static function activate() {
        // Creează tabela în baza de date
        AjaxHandler::create_database_table_on_activation();
        
        // Verifică dacă există date de migrat
        $ajax_handler = AjaxHandler::get_instance();
        if ($ajax_handler->has_data_to_migrate()) {
            // Nu migrăm automat la activare - utilizatorul va primi notificare în admin
            error_log('SCFS: Data migration needed on activation');
        }
        
        // Adaugă opțiuni default dacă nu există
        self::add_default_options();
    }
    
    private static function add_default_options() {
        // Adaugă opțiuni pentru settings dacă nu există
        if (!get_option('scfs_social_settings')) {
            update_option('scfs_social_settings', [
                'auto_display' => true,
                'position' => 'right',
                'vertical_position' => 'middle',
                'animation' => 'slide',
                'mobile_hide' => false
            ]);
        }
    }
}