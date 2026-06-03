<?php
namespace SCFS;
if ( ! defined( 'ABSPATH' ) ) exit;
class AjaxHandler {
    private static $instance = null;
    private static $migration_done_key = 'scfs_data_migrated_v3';
    private static $backup_prefix = 'bpk_';
    private static $table_created_key = 'scfs_table_created_v3';
    
    // Lista opțiunilor care trebuie MIGRATE în tabela separată
    private static $options_to_migrate = [
        'sfb_buttons',
        'scfs_social_buttons',
        'cfs_fields',
        'scfs_custom_fields',
    ];
    
    // Lista opțiunilor de SETĂRI care rămân în wp_options
    private static $settings_options = [
        'sfb_auto_display',
        'scfs_social_settings',
        'sfb_icon_cdns',
        'sfb_custom_cdns',
        'scfs_cdn_settings_custom',
        'scfs_cdn_settings_predefined',
    ];
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('wp_ajax_scfs_add_cdn_ajax', [$this, 'add_cdn_ajax']);
        add_action('wp_ajax_scfs_toggle_cdn_status', [$this, 'toggle_cdn_status']);
        add_action('wp_ajax_scfs_update_order', [$this, 'update_order']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_legacy_cdns']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_legacy_cdns']);
        add_action('admin_init', [$this, 'maybe_create_table']);
        add_action('admin_init', [$this, 'check_and_migrate_data']);
        add_action('wp_ajax_scfs_migrate_data', [$this, 'migrate_data_ajax']);
    }
    
    // Helper function for debug logging
    private static function log_debug($message, $data = null) {
        if (defined('WP_DEBUG') && WP_DEBUG === true) {
            if ($data !== null) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_print_r
                error_log('SCFS: ' . $message . ': ' . print_r($data, true));
            } else {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log('SCFS: ' . $message);
            }
        }
    }
    
    // =========================
    // TABLE CREATION
    // =========================
    
    public static function create_database_table_on_activation() {
        self::create_database_table();
    }
    
    public function maybe_create_table() {
        if (get_option(self::$table_created_key)) {
            return;
        }
        
        $created = self::create_database_table();
        if ($created) {
            update_option(self::$table_created_key, current_time('mysql'));
        }
    }
    
    private static function create_database_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'scfs';
        $charset_collate = $wpdb->get_charset_collate();
        // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter
        $sql = "SHOW TABLES LIKE %s";
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared -- No dynamic user data used here.
        $table_exists = $wpdb->get_var($wpdb->prepare($sql, $table_name)) === $table_name;
        
        if ($table_exists) {
            return true;
        }
        // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter
        $sql_create = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            item_id varchar(100) NOT NULL,
            item_type varchar(50) DEFAULT 'custom_field',
            item_data longtext,
            item_order int(11) DEFAULT 0,
            trashed datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY item_id (item_id),
            KEY item_type (item_type),
            KEY trashed (trashed),
            KEY item_order (item_order)
        ) {$charset_collate};";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $result = dbDelta($sql_create);
        // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter
        $sql = "SHOW TABLES LIKE %s";
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared -- No dynamic user data used here.
        $table_exists = $wpdb->get_var($wpdb->prepare($sql, $table_name)) === $table_name;
        
        if (!$table_exists) {
            self::log_debug('Failed to create database table: ' . $table_name);
            return false;
        }
        
        return true;
    }
    
    // =========================
    // MIGRATION FUNCTIONS
    // =========================
    
    public static function is_migration_done() {
        return get_option(self::$migration_done_key, false);
    }
    
    public function check_and_migrate_data() {
        if (!is_admin() || self::is_migration_done()) {
            return;
        }
        
        $this->maybe_create_table();
        
        $has_old_data = $this->has_data_to_migrate();
        
        if ($has_old_data) {
            add_action('admin_notices', [$this, 'show_migration_notice']);
        } else {
            update_option(self::$migration_done_key, current_time('mysql'));
        }
    }
    
    public function has_data_to_migrate() {
        foreach (self::$options_to_migrate as $option) {
            $raw_data = $this->get_raw_option_data($option);
            if ($raw_data !== false) {
                return true;
            }
        }
        return false;
    }
    
    private function get_raw_option_data($option_name) {
        global $wpdb;
        // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter
        $sql = "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s";
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared -- No dynamic user data used here.
        $raw_value = $wpdb->get_var($wpdb->prepare($sql, $option_name));
        
        return $raw_value ? $raw_value : false;
    }
    
    public function show_migration_notice() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $nonce = esc_js(wp_create_nonce('scfs_migrate_nonce'));
        ?>
        <div class="notice notice-warning is-dismissible">
            <p>
                <strong>Social & Custom Fields Shortcodes - Migrare date:</strong><br>
                Am detectat date vechi care trebuie migrate într-o tabelă separată pentru performanță îmbunătățită.<br>
                <small>Setările vor rămâne în wp_options, doar datele de conținut vor fi migrate.</small>
                <a href="#" id="scfs-start-migration" class="button button-primary" style="margin-left: 10px; margin-top: 10px;">
                    Migrează datele acum
                </a>
                <span id="scfs-migration-status" style="margin-left: 10px; display: none;">
                    <span class="spinner is-active" style="float: none; margin: 0 5px;"></span>
                    <span id="scfs-migration-text">Se migrează datele...</span>
                </span>
            </p>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#scfs-start-migration').on('click', function(e) {
                e.preventDefault();
                
                var $button = $(this);
                var $status = $('#scfs-migration-status');
                var $text = $('#scfs-migration-text');
                
                $button.hide();
                $status.show();
                $text.text('Se migrează datele...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'scfs_migrate_data',
                        nonce: '<?php echo esc_js( $nonce ); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $text.text('Migrare completă!');
                            $status.find('.spinner').remove();
                            setTimeout(function() {
                                $status.fadeOut();
                                $button.text('Date migrate cu succes').show();
                                $button.removeClass('button-primary').addClass('button-secondary');
                            }, 2000);
                            
                            setTimeout(function() {
                                location.reload();
                            }, 3000);
                        } else {
                            $text.text('Eroare la migrare: ' + response.data);
                            $status.find('.spinner').remove();
                            $button.text('Încearcă din nou').show();
                        }
                    },
                    error: function() {
                        $text.text('Eroare de conexiune');
                        $status.find('.spinner').remove();
                        $button.text('Încearcă din nou').show();
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    public function migrate_data_ajax() {
        check_ajax_referer('scfs_migrate_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        if (!self::create_database_table()) {
            wp_send_json_error('Failed to create database table. Please check server error logs.');
        }
        
        $result = $this->migrate_all_data_simple();
        
        if ($result['success']) {
            update_option(self::$migration_done_key, current_time('mysql'));
            
            wp_send_json_success([
                'message' => 'Migrarea a fost completată cu succes!',
                'stats' => $result['stats'],
                'migrated_options' => $result['migrated_options']
            ]);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    private function migrate_all_data_simple() {
        $stats = [
            'social_buttons_migrated' => 0,
            'custom_fields_migrated' => 0,
            'failed' => 0
        ];
        
        $migrated_options = [];
        
        self::log_debug('===== STARTING SIMPLE MIGRATION PROCESS =====');
        
        // 1. MIGRARE BUTOANE SOCIALE
        $sfb_buttons_data = $this->extract_and_rebuild_data('sfb_buttons');
        if ($sfb_buttons_data !== false) {
            self::log_debug('Migrating sfb_buttons...');
            $migrated = $this->migrate_social_buttons_simple($sfb_buttons_data, 'sfb_buttons');
            $stats['social_buttons_migrated'] += $migrated['migrated'];
            $stats['failed'] += $migrated['failed'];
            
            if ($migrated['migrated'] > 0) {
                $migrated_options[] = 'sfb_buttons';
                $this->create_backup('sfb_buttons', $sfb_buttons_data);
            }
        }
        
        $scfs_social_buttons_data = $this->extract_and_rebuild_data('scfs_social_buttons');
        if ($scfs_social_buttons_data !== false) {
            self::log_debug('Migrating scfs_social_buttons...');
            $migrated = $this->migrate_social_buttons_simple($scfs_social_buttons_data, 'scfs_social_buttons');
            $stats['social_buttons_migrated'] += $migrated['migrated'];
            $stats['failed'] += $migrated['failed'];
            
            if ($migrated['migrated'] > 0) {
                $migrated_options[] = 'scfs_social_buttons';
                $this->create_backup('scfs_social_buttons', $scfs_social_buttons_data);
            }
        }
        
        // 2. MIGRARE CÂMPURI CUSTOM
        $cfs_fields_data = $this->extract_and_rebuild_data('cfs_fields');
        if ($cfs_fields_data !== false) {
            self::log_debug('Migrating cfs_fields...');
            $migrated = $this->migrate_cfs_fields_simple($cfs_fields_data);
            $stats['custom_fields_migrated'] += $migrated['migrated'];
            $stats['failed'] += $migrated['failed'];
            
            if ($migrated['migrated'] > 0) {
                $migrated_options[] = 'cfs_fields';
                $this->create_backup('cfs_fields', $cfs_fields_data);
            }
        }
        
        $scfs_custom_fields_data = $this->extract_and_rebuild_data('scfs_custom_fields');
        if ($scfs_custom_fields_data !== false) {
            self::log_debug('Migrating scfs_custom_fields...');
            $migrated = $this->migrate_scfs_custom_fields_simple($scfs_custom_fields_data);
            $stats['custom_fields_migrated'] += $migrated['migrated'];
            $stats['failed'] += $migrated['failed'];
            
            if ($migrated['migrated'] > 0) {
                $migrated_options[] = 'scfs_custom_fields';
                $this->create_backup('scfs_custom_fields', $scfs_custom_fields_data);
            }
        }
        
        $total_migrated = $stats['social_buttons_migrated'] + $stats['custom_fields_migrated'];
        
        if ($total_migrated > 0) {
            self::log_debug('===== MIGRATION COMPLETED SUCCESSFULLY =====');
            self::log_debug('Total migrated: ' . $total_migrated);
            self::log_debug('Migrated options: ' . implode(', ', $migrated_options));
            
            foreach ($migrated_options as $option) {
                delete_option($option);
                self::log_debug('Deleted old option: ' . $option);
            }
        } else {
            self::log_debug('===== NO DATA WAS MIGRATED =====');
        }
        
        return [
            'success' => true,
            'stats' => $stats,
            'migrated_options' => $migrated_options,
            'message' => sprintf(
                'Migrarea completă: %d butoane sociale, %d câmpuri custom',
                $stats['social_buttons_migrated'],
                $stats['custom_fields_migrated']
            )
        ];
    }
    
    private function extract_and_rebuild_data($option_name) {
        global $wpdb;
        // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter
        $sql = "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s";
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared -- No dynamic user data used here.
        $raw_value = $wpdb->get_var($wpdb->prepare($sql, $option_name));
        
        if (!$raw_value) {
            self::log_debug('No data found for option: ' . $option_name);
            return false;
        }
        
        self::log_debug('Processing option: ' . $option_name . ' (length: ' . strlen($raw_value) . ')');
        
        $decoded_data = maybe_unserialize($raw_value);
        
        if ($decoded_data !== false && $decoded_data !== $raw_value) {
            self::log_debug('WordPress unserialize worked for ' . $option_name);
            return $decoded_data;
        }
        
        $decoded_data = @unserialize($raw_value);
        if ($decoded_data !== false) {
            self::log_debug('Direct unserialize worked for ' . $option_name);
            return $decoded_data;
        }
        
        if ($option_name === 'cfs_fields') {
            self::log_debug('Using manual extraction for cfs_fields');
            return $this->manual_extract_cfs_fields($raw_value);
        }
        
        if (is_string($raw_value) && !empty(trim($raw_value))) {
            self::log_debug('Treating as simple string for ' . $option_name);
            return $raw_value;
        }
        
        self::log_debug('Could not extract data for ' . $option_name);
        return false;
    }
    
    private function manual_extract_cfs_fields($serialized_string) {
        $result = [];
        
        $pattern = '/s:(\d+):"([^"]*)";s:(\d+):"((?:[^"]|\\\\")*)"/';
        
        preg_match_all($pattern, $serialized_string, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $key = $match[2];
            $value = $match[4];
            
            $value = str_replace('\"', '"', $value);
            $value = str_replace("\\'", "'", $value);
            
            $result[$key] = $value;
        }
        
        if (!empty($result)) {
            self::log_debug('Manual extraction successful, got ' . count($result) . ' items');
            return $result;
        }
        
        return $this->simple_string_extraction($serialized_string);
    }
    
    private function simple_string_extraction($string) {
        $result = [];
        
        $clean_string = preg_replace('/^a:\d+:{/', '', $string);
        $clean_string = preg_replace('/}$/', '', $clean_string);
        
        $parts = explode(';', $clean_string);
        $temp_key = null;
        
        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part)) continue;
            
            if (preg_match('/^s:(\d+):"([^"]*)"$/', $part, $matches)) {
                if ($temp_key === null) {
                    $temp_key = $matches[2];
                } else {
                    $result[$temp_key] = $matches[2];
                    $temp_key = null;
                }
            }
        }
        
        if (!empty($result)) {
            self::log_debug('Simple extraction successful, got ' . count($result) . ' items');
        }
        
        return $result;
    }
    
    private function migrate_social_buttons_simple($buttons_data, $source) {
        $migrated = 0;
        $failed = 0;
        
        if (!is_array($buttons_data) || empty($buttons_data)) {
            self::log_debug('No social buttons data to migrate from ' . $source);
            return ['migrated' => 0, 'failed' => 0];
        }
        
        self::log_debug('Processing ' . count($buttons_data) . ' social buttons from ' . $source);
        
        foreach ($buttons_data as $index => $button) {
            if (is_array($button) && isset($button['id'])) {
                $result = $this->save_item_to_database(
                    $button['id'],
                    'social_button',
                    $button,
                    isset($button['order']) ? intval($button['order']) : ($index + 1),
                    isset($button['trashed']) ? $button['trashed'] : null
                );
                
                if ($result) {
                    $migrated++;
                } else {
                    $failed++;
                }
            }
        }
        
        self::log_debug('Migrated ' . $migrated . ' social buttons from ' . $source);
        return ['migrated' => $migrated, 'failed' => $failed];
    }
    
    private function migrate_cfs_fields_simple($fields_data) {
        $migrated = 0;
        $failed = 0;
        
        if (!is_array($fields_data) || empty($fields_data)) {
            self::log_debug('No cfs_fields data to migrate');
            return ['migrated' => 0, 'failed' => 0];
        }
        
        self::log_debug('Processing ' . count($fields_data) . ' cfs fields');
        $order_counter = 1;
        
        foreach ($fields_data as $key => $value) {
            if (!is_string($key) || empty(trim($key))) {
                $failed++;
                continue;
            }
            
            $field_data = [
                'id' => $key,
                'name' => $this->convert_key_to_name($key),
                'value' => $value,
                'type' => 'text',
                'label' => $this->convert_key_to_label($key),
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ];
            
            $result = $this->save_item_to_database(
                $key,
                'custom_field',
                $field_data,
                $order_counter,
                null
            );
            
            if ($result) {
                $migrated++;
                $order_counter++;
            } else {
                $failed++;
            }
        }
        
        self::log_debug('Migrated ' . $migrated . ' cfs fields');
        return ['migrated' => $migrated, 'failed' => $failed];
    }
    
    private function migrate_scfs_custom_fields_simple($fields_data) {
        $migrated = 0;
        $failed = 0;
        
        if (!is_array($fields_data) || empty($fields_data)) {
            self::log_debug('No scfs_custom_fields data to migrate');
            return ['migrated' => 0, 'failed' => 0];
        }
        
        self::log_debug('Processing ' . count($fields_data) . ' scfs custom fields');
        
        foreach ($fields_data as $index => $field) {
            if (is_array($field) && isset($field['id'])) {
                $result = $this->save_item_to_database(
                    $field['id'],
                    'custom_field',
                    $field,
                    isset($field['order']) ? intval($field['order']) : ($index + 1),
                    isset($field['trashed']) ? $field['trashed'] : null
                );
                
                if ($result) {
                    $migrated++;
                } else {
                    $failed++;
                }
            }
        }
        
        self::log_debug('Migrated ' . $migrated . ' scfs custom fields');
        return ['migrated' => $migrated, 'failed' => $failed];
    }
    
    private function save_item_to_database($item_id, $item_type, $data, $order = 0, $trashed = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'scfs';
        // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter
        $sql = "SELECT id FROM {$table_name} WHERE item_id = %s AND item_type = %s";
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- No dynamic user data used here.
        $existing = $wpdb->get_var($wpdb->prepare($sql, $item_id, $item_type));
        
        $serialized_data = maybe_serialize($data);
        
        if ($existing) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
            $result = $wpdb->update(
                $table_name,
                [
                    'item_data' => $serialized_data,
                    'item_order' => $order,
                    'trashed' => $trashed,
                    'updated_at' => current_time('mysql')
                ],
                [
                    'id' => $existing
                ]
            );
        } else {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
            $result = $wpdb->insert(
                $table_name,
                [
                    'item_id' => $item_id,
                    'item_type' => $item_type,
                    'item_data' => $serialized_data,
                    'item_order' => $order,
                    'trashed' => $trashed,
                    'created_at' => current_time('mysql')
                ]
            );
        }
        
        // Clear cache for this item type
        wp_cache_delete($item_type, 'scfs_items');
        
        return $result !== false;
    }
    
    private function convert_key_to_name($key) {
        return $key;
    }
    
    private function convert_key_to_label($key) {
        $label = str_replace(['_', '-'], ' ', $key);
        $label = ucwords($label);
        return $label;
    }
    
    private function create_backup($option_name, $data) {
        if (!empty($data)) {
            $backup_name = self::$backup_prefix . $option_name;
            $serialized_data = maybe_serialize($data);
            update_option($backup_name, $serialized_data);
            self::log_debug('Created backup: ' . $backup_name);
        }
    }
    
    // =========================
    // DATABASE HELPER FUNCTIONS
    // =========================
    
    public static function get_item_from_database($item_id, $item_type = 'custom_field') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'scfs';
        
        // Try cache first
        $cache_key = $item_type . '_' . $item_id;
        $cached = wp_cache_get($cache_key, 'scfs_items');
        if ($cached !== false) {
            return $cached;
        }
        
        $sql = "SHOW TABLES LIKE %s";
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared -- No dynamic user data used here.
        $table_exists = $wpdb->get_var($wpdb->prepare($sql, $table_name)) === $table_name;
        
        if (!$table_exists) {
            return null;
        }
        
        $sql = "SELECT * FROM {$table_name} 
                WHERE item_id = %s AND item_type = %s AND trashed IS NULL";
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- No dynamic user data used here.
        $result = $wpdb->get_row($wpdb->prepare($sql, $item_id, $item_type), ARRAY_A);
        
        if ($result) {
            $data = maybe_unserialize($result['item_data']);
            if (is_array($data)) {
                $data['_db_id'] = $result['id'];
                $data['_db_created'] = $result['created_at'];
                $data['_db_updated'] = $result['updated_at'];
            }
            
            // Cache the result
            wp_cache_set($cache_key, $data, 'scfs_items', 3600);
            return $data;
        }
        
        return null;
    }
    
    public static function get_all_items_from_database($item_type = 'custom_field', $include_trashed = false) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'scfs';
        
        // Try cache first
        $cache_key = $item_type . '_all_' . ($include_trashed ? 'with_trash' : 'no_trash');
        $cached = wp_cache_get($cache_key, 'scfs_items');
        if ($cached !== false) {
            return $cached;
        }
        // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter
        $sql = "SHOW TABLES LIKE %s";
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared -- No dynamic user data used here.
        $table_exists = $wpdb->get_var($wpdb->prepare($sql, $table_name)) === $table_name;
        
        if (!$table_exists) {
            return [];
        }
        // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter
        $sql_where = $include_trashed 
            ? "SELECT item_id,item_data,id,created_at,updated_at,item_order,trashed
               FROM {$table_name}
               WHERE item_type = %s
               ORDER BY item_order ASC, created_at ASC"
            : "SELECT item_id,item_data,id,created_at,updated_at,item_order,trashed
               FROM {$table_name}
               WHERE item_type = %s AND trashed IS NULL
               ORDER BY item_order ASC, created_at ASC";
        
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- No dynamic user data used here.
        $results = $wpdb->get_results($wpdb->prepare($sql_where, $item_type), ARRAY_A);
        
        $items = [];
        foreach ($results as $row) {
            $data = maybe_unserialize($row['item_data']);
            
            if (is_array($data)) {
                $data['id'] = $row['item_id'];
                $data['_db_id'] = $row['id'];
                $data['_db_order'] = $row['item_order'];
                $data['_db_created'] = $row['created_at'];
                $data['_db_updated'] = $row['updated_at'];
                
                if ($row['trashed']) {
                    $data['trashed'] = $row['trashed'];
                }
                
                $items[] = $data;
            }
        }
        
        // Cache the results
        wp_cache_set($cache_key, $items, 'scfs_items', 3600);
        
        return $items;
    }
    
    public static function save_item_to_database_static($item_id, $item_type, $data, $order = 0, $trashed = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'scfs';
        // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter
        $sql = "SHOW TABLES LIKE %s";
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared -- No dynamic user data used here.
        $table_exists = $wpdb->get_var($wpdb->prepare($sql, $table_name)) === $table_name;
        
        if (!$table_exists) {
            self::log_debug('Cannot save to database, table does not exist');
            return false;
        }
        // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter
        $sql = "SELECT id FROM {$table_name}
                WHERE item_id = %s
                AND item_type = %s";
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- No dynamic user data used here.
        $existing = $wpdb->get_var($wpdb->prepare($sql, $item_id, $item_type));
        
        $serialized_data = maybe_serialize($data);
        
        if ($existing) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
            $result = $wpdb->update(
                $table_name,
                [
                    'item_data' => $serialized_data,
                    'item_order' => $order,
                    'trashed' => $trashed,
                    'updated_at' => current_time('mysql')
                ],
                [
                    'id' => $existing
                ]
            );
        } else {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
            $result = $wpdb->insert(
                $table_name,
                [
                    'item_id' => $item_id,
                    'item_type' => $item_type,
                    'item_data' => $serialized_data,
                    'item_order' => $order,
                    'trashed' => $trashed,
                    'created_at' => current_time('mysql')
                ]
            );
        }
        
        // Clear cache for this item type
        wp_cache_delete($item_type . '_all_no_trash', 'scfs_items');
        wp_cache_delete($item_type . '_all_with_trash', 'scfs_items');
        wp_cache_delete($item_type . '_' . $item_id, 'scfs_items');
        
        return $result !== false;
    }
    
    public static function delete_item_from_database($item_id, $item_type = 'custom_field') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'scfs';
        // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter
        $sql = "SHOW TABLES LIKE %s";
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared -- No dynamic user data used here.
        $table_exists = $wpdb->get_var($wpdb->prepare($sql, $table_name)) === $table_name;
        
        if (!$table_exists) {
            return false;
        }
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $result = $wpdb->delete(
            $table_name,
            [
                'item_id' => $item_id,
                'item_type' => $item_type
            ]
        );
        
        // Clear cache for this item type
        wp_cache_delete($item_type . '_all_no_trash', 'scfs_items');
        wp_cache_delete($item_type . '_all_with_trash', 'scfs_items');
        wp_cache_delete($item_type . '_' . $item_id, 'scfs_items');
        
        return $result !== false;
    }
    
    public static function trash_item_in_database($item_id, $item_type = 'custom_field') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'scfs';
        // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter
        $sql = "SHOW TABLES LIKE %s";
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared -- No dynamic user data used here.
        $table_exists = $wpdb->get_var($wpdb->prepare($sql, $table_name)) === $table_name;
        
        if (!$table_exists) {
            return false;
        }
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $result = $wpdb->update(
            $table_name,
            [
                'trashed' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ],
            [
                'item_id' => $item_id,
                'item_type' => $item_type
            ]
        );
        
        // Clear cache for this item type
        wp_cache_delete($item_type . '_all_no_trash', 'scfs_items');
        wp_cache_delete($item_type . '_all_with_trash', 'scfs_items');
        wp_cache_delete($item_type . '_' . $item_id, 'scfs_items');
        
        return $result !== false;
    }
    
    public static function restore_item_in_database($item_id, $item_type = 'custom_field') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'scfs';
        // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter
        $sql = "SHOW TABLES LIKE %s";
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared -- No dynamic user data used here.
        $table_exists = $wpdb->get_var($wpdb->prepare($sql, $table_name)) === $table_name;
        
        if (!$table_exists) {
            return false;
        }
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $result = $wpdb->update(
            $table_name,
            [
                'trashed' => NULL,
                'updated_at' => current_time('mysql')
            ],
            [
                'item_id' => $item_id,
                'item_type' => $item_type
            ]
        );
        
        // Clear cache for this item type
        wp_cache_delete($item_type . '_all_no_trash', 'scfs_items');
        wp_cache_delete($item_type . '_all_with_trash', 'scfs_items');
        wp_cache_delete($item_type . '_' . $item_id, 'scfs_items');
        
        return $result !== false;
    }
    
    // =========================
    // HELPER FUNCTIONS
    // =========================
    
    public static function slugify($string) {
        if (empty($string)) {
            return '';
        }
        
        $string = trim($string);
        $string = str_replace('-', '--DASH--', $string);
        $string = str_replace(' ', '_', $string);
        $string = str_replace('--DASH--', '-', $string);
        $string = strtolower($string);
        $string = preg_replace('/_+/', '_', $string);
        $string = preg_replace('/-+/', '-', $string);
        $string = preg_replace('/[^a-z0-9_-]/', '', $string);
        $string = trim($string, '_-');
        
        return $string;
    }
    
    public static function generate_slug_with_id($label, $id) {
        $base_slug = self::slugify($label);
        
        if (empty($base_slug)) {
            $base_slug = 'item';
        }
        
        if (preg_match('/[a-zA-Z_]+(\d+)/', $id, $matches)) {
            return $base_slug . '_' . $matches[1];
        }
        
        if (preg_match('/(\d+)/', $id, $matches)) {
            return $base_slug . '_' . $matches[1];
        }
        
        return $base_slug . '_' . substr(md5($id), 0, 6);
    }
    
    public static function is_allowed_scheme($url) {
        if (empty($url) || !is_string($url)) {
            return false;
        }
        
        $allowed_schemes = ['http', 'https', 'tel', 'mailto', 'viber', 'whatsapp', 'tg', 'fb-messenger'];
        $scheme = wp_parse_url($url, PHP_URL_SCHEME);
        return in_array($scheme, $allowed_schemes);
    }
    
    public static function get_correct_url($type, $url) {
        if (empty($url)) {
            return '#';
        }
        
        switch ($type) {
            case 'tel':
                return 'tel:' . preg_replace('/[^0-9+]/', '', $url);
            case 'mailto':
                return 'mailto:' . sanitize_email($url);
            case 'whatsapp':
                if (strpos($url, 'whatsapp://') === 0 || strpos($url, 'https://wa.me/') === 0) {
                    return $url;
                }
                $number = preg_replace('/[^0-9]/', '', $url);
                return 'https://wa.me/' . $number;
            case 'viber':
                if (strpos($url, 'viber://') === 0) {
                    return $url;
                }
                $number = preg_replace('/[^0-9+]/', '', $url);
                return 'viber://chat?number=' . $number;
            case 'telegram':
                if (strpos($url, 'tg://') === 0 || strpos($url, 'https://t.me/') === 0) {
                    return $url;
                }
                return 'https://t.me/' . ltrim($url, '@');
            case 'facebook-messenger':
                if (strpos($url, 'fb-messenger://') === 0) {
                    return $url;
                }
                return 'https://m.me/' . ltrim($url, '@');
            default:
                if (self::is_allowed_scheme($url)) {
                    return $url;
                }
                return esc_url_raw($url);
        }
    }
    
    public function enqueue_legacy_cdns() {
        $settings = get_option('sfb_icon_cdns', []);
        $custom_cdns = get_option('sfb_custom_cdns', []);
        
        $predefined_cdns = [
            'fontawesome_kit' => [
                'name' => 'Font Awesome Kit',
                'type' => 'js',
                'default_url' => 'https://kit.fontawesome.com/0f950e4537.js'
            ]
        ];
        
        foreach($settings as $cdn_id => $cdn_url) {
            if(!empty($cdn_url) && isset($predefined_cdns[$cdn_id])) {
                $cdn_type = $predefined_cdns[$cdn_id]['type'];
                
                if($cdn_type === 'css') {
                    wp_enqueue_style('sfb-legacy-' . $cdn_id, $cdn_url, [], null);
                } elseif($cdn_type === 'js') {
                    wp_enqueue_script('sfb-legacy-' . $cdn_id, $cdn_url, [], null, true);
                }
            }
        }
        
        foreach($custom_cdns as $cdn) {
            if(!empty($cdn['active']) && !empty($cdn['url'])) {
                $handle = 'sfb-legacy-custom-' . sanitize_title($cdn['name']);
                
                if($cdn['type'] === 'css') {
                    wp_enqueue_style($handle, $cdn['url'], [], null);
                } elseif($cdn['type'] === 'js') {
                    wp_enqueue_script($handle, $cdn['url'], [], null, true);
                }
            }
        }
    }
    
    // =========================
    // AJAX HANDLERS
    // =========================
    
    public function add_cdn_ajax() {
        check_ajax_referer('scfs_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $cdn_name = isset($_POST['cdn_name']) ? sanitize_text_field(wp_unslash($_POST['cdn_name'])) : '';
        $cdn_type = isset($_POST['cdn_type']) ? sanitize_text_field(wp_unslash($_POST['cdn_type'])) : '';
        $cdn_url = isset($_POST['cdn_url']) ? esc_url_raw(wp_unslash($_POST['cdn_url'])) : '';
        
        if (empty($cdn_name) || empty($cdn_type) || empty($cdn_url)) {
            wp_send_json_error('All fields are required');
        }
        
        $social_settings = SocialSettings::get_instance();
        $cdn_settings_name = 'scfs_cdn_settings';
        
        $cdns = get_option($cdn_settings_name . '_custom', []);
        $cdn_id = self::slugify($cdn_name) . '_' . uniqid();
        $cdns[$cdn_id] = [
            'name' => $cdn_name,
            'type' => $cdn_type,
            'url' => $cdn_url,
            'active' => true,
            'created' => current_time('mysql')
        ];
        
        update_option($cdn_settings_name . '_custom', $cdns);
        
        wp_send_json_success('CDN added successfully');
    }
    
    public function toggle_cdn_status() {
        check_ajax_referer('scfs_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $cdn_id = isset($_POST['cdn_id']) ? sanitize_text_field(wp_unslash($_POST['cdn_id'])) : '';
        $status = isset($_POST['status']) ? intval($_POST['status']) : 0;
        
        $social_settings = SocialSettings::get_instance();
        $cdn_settings_name = 'scfs_cdn_settings';
        
        $cdns = get_option($cdn_settings_name . '_custom', []);
        
        if (isset($cdns[$cdn_id])) {
            $cdns[$cdn_id]['active'] = (bool) $status;
            update_option($cdn_settings_name . '_custom', $cdns);
            
            wp_send_json_success('CDN status updated');
        } else {
            wp_send_json_error('CDN not found');
        }
    }
    
    public function update_order() {
        check_ajax_referer('scfs_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $id = isset($_POST['id']) ? sanitize_text_field(wp_unslash($_POST['id'])) : '';
        $order = isset($_POST['order']) ? intval($_POST['order']) : 0;
        
        $social_buttons = SocialButtons::get_instance();
        if ($social_buttons->update($id, ['order' => $order])) {
            wp_send_json_success();
        }
        
        $custom_fields = CustomFields::get_instance();
        $field = $custom_fields->get($id);
        if ($field) {
            $custom_fields->update($id, ['order' => $order]);
            wp_send_json_success();
        }
        
        wp_send_json_error('Item not found');
    }
}