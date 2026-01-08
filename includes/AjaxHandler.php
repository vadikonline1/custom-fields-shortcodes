<?php
namespace SCFS;

class AjaxHandler {
    private static $instance = null;
    private static $migration_done_key = 'scfs_data_migrated_v3';
    private static $backup_prefix = 'bpk_';
    private static $table_created_key = 'scfs_table_created_v3';
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Add CDN via AJAX
        add_action('wp_ajax_scfs_add_cdn_ajax', [$this, 'add_cdn_ajax']);
        
        // Toggle CDN status via AJAX
        add_action('wp_ajax_scfs_toggle_cdn_status', [$this, 'toggle_cdn_status']);
        
        // Update order via AJAX
        add_action('wp_ajax_scfs_update_order', [$this, 'update_order']);
        
        // Încărcarea CDN-urilor vechi
        add_action('wp_enqueue_scripts', [$this, 'enqueue_legacy_cdns']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_legacy_cdns']);
        
        // Adaugă hook pentru crearea tabelei
        add_action('admin_init', [$this, 'maybe_create_table']);
        
        // Adaugă hook pentru migrare
        add_action('admin_init', [$this, 'check_and_migrate_data']);
        
        // Adaugă AJAX pentru migrare
        add_action('wp_ajax_scfs_migrate_data', [$this, 'migrate_data_ajax']);
    }
    
    // =========================
    // TABLE CREATION - Static Method for Activation
    // =========================
    
    public static function create_database_table_on_activation() {
        self::create_database_table();
    }
    
    public function maybe_create_table() {
        // Verifică dacă tabela a fost deja creată
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
        
        // Verifică dacă tabela există deja
        $table_exists = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s", 
            $table_name
        )) === $table_name;
        
        if ($table_exists) {
            return true;
        }
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
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
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $result = dbDelta($sql);
        
        // Verifică dacă tabela a fost creată cu succes
        $table_exists = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s", 
            $table_name
        )) === $table_name;
        
        if (!$table_exists) {
            error_log('SCFS: Failed to create database table: ' . $table_name);
            error_log('SCFS: SQL Error: ' . print_r($result, true));
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
        // Verifică dacă suntem în admin și dacă migrarea nu a fost făcută
        if (!is_admin() || self::is_migration_done()) {
            return;
        }
        
        // Asigură-te că tabela există înainte de a verifica datele
        $this->maybe_create_table();
        
        // Verifică dacă există date de migrat
        $has_old_data = $this->has_old_wp_options_data();
        
        if ($has_old_data) {
            // Adaugă notificare pentru administrator
            add_action('admin_notices', [$this, 'show_migration_notice']);
        }
    }
    
    public function has_old_wp_options_data() {
        $old_options = [
            'scfs_custom_fields',
            'cfs_fields',
            'scfs_social_buttons',
            'sfb_buttons'
        ];
        
        foreach ($old_options as $option) {
            if (get_option($option, false) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    public function show_migration_notice() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        ?>
        <div class="notice notice-warning is-dismissible">
            <p>
                <strong>Social & Custom Fields Shortcodes:</strong> 
                Există date vechi în wp_options care trebuie migrate într-o tabelă separată pentru performanță îmbunătățită.
                <a href="#" id="scfs-start-migration" class="button button-primary" style="margin-left: 10px;">
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
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'scfs_migrate_data',
                        nonce: '<?php echo wp_create_nonce('scfs_migrate_nonce'); ?>'
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
                            
                            // Reîncarcă pagina după 3 secunde
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
        
        // Asigură-te că tabela există înainte de migrare
        if (!self::create_database_table()) {
            wp_send_json_error('Failed to create database table. Please check server error logs.');
        }
        
        $result = $this->migrate_all_data();
        
        if ($result['success']) {
            // Marchează migrarea ca fiind completă
            update_option(self::$migration_done_key, current_time('mysql'));
            
            wp_send_json_success([
                'message' => 'Migrarea a fost completată cu succes!',
                'stats' => $result['stats']
            ]);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    private function migrate_all_data() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'scfs';
        
        // Verifică dacă tabela există
        $table_exists = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s", 
            $table_name
        )) === $table_name;
        
        if (!$table_exists) {
            return [
                'success' => false,
                'message' => 'Database table does not exist'
            ];
        }
        
        $stats = [
            'custom_fields_migrated' => 0,
            'social_buttons_migrated' => 0,
            'failed' => 0
        ];
        
        // 1. Migrare Custom Fields
        $custom_fields_old = get_option('scfs_custom_fields', []);
        $cfs_fields_old = get_option('cfs_fields', []);
        
        $all_custom_fields = array_merge(
            is_array($custom_fields_old) ? $custom_fields_old : [],
            is_array($cfs_fields_old) ? $cfs_fields_old : []
        );
        
        if (!empty($all_custom_fields)) {
            foreach ($all_custom_fields as $index => $field) {
                if (is_array($field) && isset($field['id'])) {
                    $result = $this->save_item_to_database(
                        $field['id'],
                        'custom_field',
                        $field,
                        isset($field['order']) ? intval($field['order']) : ($index + 1),
                        isset($field['trashed']) ? $field['trashed'] : null
                    );
                    
                    if ($result) {
                        $stats['custom_fields_migrated']++;
                    } else {
                        $stats['failed']++;
                    }
                }
            }
        }
        
        // 2. Migrare Social Buttons
        $social_buttons_old = get_option('scfs_social_buttons', []);
        $sfb_buttons_old = get_option('sfb_buttons', []);
        
        $all_social_buttons = array_merge(
            is_array($social_buttons_old) ? $social_buttons_old : [],
            is_array($sfb_buttons_old) ? $sfb_buttons_old : []
        );
        
        if (!empty($all_social_buttons)) {
            foreach ($all_social_buttons as $index => $button) {
                if (is_array($button) && isset($button['id'])) {
                    $result = $this->save_item_to_database(
                        $button['id'],
                        'social_button',
                        $button,
                        isset($button['order']) ? intval($button['order']) : ($index + 1),
                        isset($button['trashed']) ? $button['trashed'] : null
                    );
                    
                    if ($result) {
                        $stats['social_buttons_migrated']++;
                    } else {
                        $stats['failed']++;
                    }
                }
            }
        }
        
        // Șterge datele vechi DOAR dacă migrarea a fost completă
        if ($stats['custom_fields_migrated'] > 0 || $stats['social_buttons_migrated'] > 0) {
            // Creează backup-uri
            if (!empty($custom_fields_old)) {
                update_option('bpk_scfs_custom_fields', $custom_fields_old);
                delete_option('scfs_custom_fields');
            }
            
            if (!empty($cfs_fields_old)) {
                update_option('bpk_cfs_fields', $cfs_fields_old);
                delete_option('cfs_fields');
            }
            
            if (!empty($social_buttons_old)) {
                update_option('bpk_scfs_social_buttons', $social_buttons_old);
                delete_option('scfs_social_buttons');
            }
            
            if (!empty($sfb_buttons_old)) {
                update_option('bpk_sfb_buttons', $sfb_buttons_old);
                delete_option('sfb_buttons');
            }
        }
        
        return [
            'success' => true,
            'stats' => $stats,
            'message' => sprintf(
                'Migrat: %d câmpuri custom, %d butoane sociale, Eșuat: %d',
                $stats['custom_fields_migrated'],
                $stats['social_buttons_migrated'],
                $stats['failed']
            )
        ];
    }
    
    private function save_item_to_database($item_id, $item_type, $data, $order = 0, $trashed = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'scfs';
        
        // Verifică dacă există deja
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE item_id = %s AND item_type = %s",
            $item_id,
            $item_type
        ));
        
        $serialized_data = maybe_serialize($data);
        
        if ($existing) {
            // Update existent
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
            // Insert nou
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
        
        if ($result === false) {
            error_log('SCFS Database Error for ' . $item_id . ': ' . $wpdb->last_error);
            return false;
        }
        
        return true;
    }
    
    // =========================
    // DATABASE HELPER FUNCTIONS
    // =========================
    
    public static function get_item_from_database($item_id, $item_type = 'custom_field') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'scfs';
        
        // Verifică dacă tabela există
        $table_exists = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s", 
            $table_name
        )) === $table_name;
        
        if (!$table_exists) {
            // Tabela nu există, returnează null
            return null;
        }
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name 
             WHERE item_id = %s AND item_type = %s AND trashed IS NULL",
            $item_id,
            $item_type
        ), ARRAY_A);
        
        if ($result) {
            $data = maybe_unserialize($result['item_data']);
            if (is_array($data)) {
                // Adaugă metadatele din baza de date
                $data['_db_id'] = $result['id'];
                $data['_db_created'] = $result['created_at'];
                $data['_db_updated'] = $result['updated_at'];
            }
            return $data;
        }
        
        return null;
    }
    
    public static function get_all_items_from_database($item_type = 'custom_field', $include_trashed = false) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'scfs';
        
        // Verifică dacă tabela există
        $table_exists = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s", 
            $table_name
        )) === $table_name;
        
        if (!$table_exists) {
            return [];
        }
        
        $where_clause = "item_type = %s";
        $params = [$item_type];
        
        if (!$include_trashed) {
            $where_clause .= " AND trashed IS NULL";
        }
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT item_id, item_data, id, created_at, updated_at, item_order, trashed 
             FROM $table_name 
             WHERE $where_clause 
             ORDER BY item_order ASC, created_at ASC",
            $item_type
        ), ARRAY_A);
        
        $items = [];
        foreach ($results as $row) {
            $data = maybe_unserialize($row['item_data']);
            
            if (is_array($data)) {
                // Asigură-te că ID-ul item-ului este setat corect
                $data['id'] = $row['item_id'];
                
                // Adaugă metadatele din baza de date
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
        
        return $items;
    }
    
    public static function save_item_to_database_static($item_id, $item_type, $data, $order = 0, $trashed = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'scfs';
        
        // Verifică dacă tabela există
        $table_exists = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s", 
            $table_name
        )) === $table_name;
        
        if (!$table_exists) {
            error_log('SCFS: Cannot save to database, table does not exist: ' . $table_name);
            return false;
        }
        
        // Verifică dacă există deja
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE item_id = %s AND item_type = %s",
            $item_id,
            $item_type
        ));
        
        $serialized_data = maybe_serialize($data);
        
        if ($existing) {
            // Update
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
            // Insert
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
        
        if ($result === false) {
            error_log('SCFS Database Error: ' . $wpdb->last_error);
        }
        
        return $result !== false;
    }
    
    public static function delete_item_from_database($item_id, $item_type = 'custom_field') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'scfs';
        
        // Verifică dacă tabela există
        $table_exists = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s", 
            $table_name
        )) === $table_name;
        
        if (!$table_exists) {
            return false;
        }
        
        return $wpdb->delete(
            $table_name,
            [
                'item_id' => $item_id,
                'item_type' => $item_type
            ]
        ) !== false;
    }
    
    public static function trash_item_in_database($item_id, $item_type = 'custom_field') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'scfs';
        
        // Verifică dacă tabela există
        $table_exists = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s", 
            $table_name
        )) === $table_name;
        
        if (!$table_exists) {
            return false;
        }
        
        return $wpdb->update(
            $table_name,
            [
                'trashed' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ],
            [
                'item_id' => $item_id,
                'item_type' => $item_type
            ]
        ) !== false;
    }
    
    public static function restore_item_in_database($item_id, $item_type = 'custom_field') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'scfs';
        
        // Verifică dacă tabela există
        $table_exists = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s", 
            $table_name
        )) === $table_name;
        
        if (!$table_exists) {
            return false;
        }
        
        return $wpdb->update(
            $table_name,
            [
                'trashed' => NULL,
                'updated_at' => current_time('mysql')
            ],
            [
                'item_id' => $item_id,
                'item_type' => $item_type
            ]
        ) !== false;
    }
    
    // =========================
    // HELPER FUNCTIONS (rămân la fel)
    // =========================
    
    public static function slugify($string) {
        if (empty($string)) {
            return '';
        }
        
        // Trim whitespace
        $string = trim($string);
        
        // Păstrează dash-urile existente (înlocuiește-le temporar)
        $string = str_replace('-', '--DASH--', $string);
        
        // Înlocuiește spațiile cu underscore
        $string = str_replace(' ', '_', $string);
        
        // Restaurează dash-urile
        $string = str_replace('--DASH--', '-', $string);
        
        // Convert to lowercase
        $string = strtolower($string);
        
        // Înlocuiește multiple underscores/dashes cu single ones
        $string = preg_replace('/_+/', '_', $string);
        $string = preg_replace('/-+/', '-', $string);
        
        // Remove all non-alphanumeric characters except underscore and dash
        $string = preg_replace('/[^a-z0-9_-]/', '', $string);
        
        // Remove leading/trailing underscores/dashes
        $string = trim($string, '_-');
        
        return $string;
    }
    
    public static function generate_slug_with_id($label, $id) {
        // Generează slug-ul de bază din label
        $base_slug = self::slugify($label);
        
        // Dacă slug-ul este gol, folosește un default
        if (empty($base_slug)) {
            $base_slug = 'item';
        }
        
        // Extrage numărul din ID (dacă există)
        $id_number = self::extract_id_number($id);
        
        // Dacă avem număr din ID, folosește-l
        if ($id_number !== null) {
            return $base_slug . '_' . $id_number;
        }
        
        // Altfel, generează un număr unic
        return $base_slug . '_' . substr(md5($id), 0, 6);
    }
    
    private static function extract_id_number($id) {
        // Încearcă să extragă numărul din ID
        if (preg_match('/[a-zA-Z_]+(\d+)/', $id, $matches)) {
            return $matches[1];
        }
        
        // Încearcă să găsească un număr în ID
        if (preg_match('/(\d+)/', $id, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    public static function is_allowed_scheme($url) {
        // Verifică dacă URL-ul nu este null sau gol
        if (empty($url) || !is_string($url)) {
            return false;
        }
        
        $allowed_schemes = ['http', 'https', 'tel', 'mailto', 'viber', 'whatsapp', 'tg', 'fb-messenger'];
        $scheme = parse_url($url, PHP_URL_SCHEME);
        return in_array($scheme, $allowed_schemes);
    }
    
    public static function get_correct_url($type, $url) {
        // Verifică dacă URL-ul este gol
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
    // AJAX HANDLERS (rămân la fel)
    // =========================
    
    public function add_cdn_ajax() {
        check_ajax_referer('scfs_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $cdn_name = sanitize_text_field($_POST['cdn_name']);
        $cdn_type = sanitize_text_field($_POST['cdn_type']);
        $cdn_url = esc_url_raw($_POST['cdn_url']);
        
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
        
        $cdn_id = sanitize_text_field($_POST['cdn_id']);
        $status = intval($_POST['status']);
        
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
        
        $id = sanitize_text_field($_POST['id']);
        $order = intval($_POST['order']);
        
        // Try social buttons first
        $social_buttons = SocialButtons::get_instance();
        if ($social_buttons->update($id, ['order' => $order])) {
            wp_send_json_success();
        }
        
        // Try custom fields if not found in social buttons
        $custom_fields = CustomFields::get_instance();
        $field = $custom_fields->get($id);
        if ($field) {
            $custom_fields->update($id, ['order' => $order]);
            wp_send_json_success();
        }
        
        wp_send_json_error('Item not found');
    }
    
    // =========================
    // DEBUG FUNCTIONS
    // =========================
    
    public static function debug_database_state() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'scfs';
        
        // Verifică dacă tabela există
        $table_exists = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s", 
            $table_name
        )) === $table_name;
        
        if (!$table_exists) {
            return '<div class="notice notice-error"><p>Table does not exist: ' . $table_name . '</p></div>';
        }
        
        // Obține toate înregistrările
        $results = $wpdb->get_results(
            "SELECT id, item_id, item_type, item_order, LENGTH(item_data) as size, 
                    created_at, updated_at, trashed 
             FROM $table_name 
             ORDER BY item_type, item_order, created_at",
            ARRAY_A
        );
        
        $output = '<h3>Database Table State</h3>';
        $output .= '<table class="widefat fixed striped">';
        $output .= '<thead><tr>
                    <th>DB ID</th>
                    <th>Item ID</th>
                    <th>Type</th>
                    <th>Order</th>
                    <th>Size (bytes)</th>
                    <th>Created</th>
                    <th>Updated</th>
                    <th>Trashed</th>
                   </tr></thead>';
        $output .= '<tbody>';
        
        if (empty($results)) {
            $output .= '<tr><td colspan="8" style="text-align: center;">No records found</td></tr>';
        } else {
            foreach ($results as $row) {
                $output .= '<tr>';
                $output .= '<td>' . esc_html($row['id']) . '</td>';
                $output .= '<td>' . esc_html($row['item_id']) . '</td>';
                $output .= '<td>' . esc_html($row['item_type']) . '</td>';
                $output .= '<td>' . esc_html($row['item_order']) . '</td>';
                $output .= '<td>' . esc_html($row['size']) . '</td>';
                $output .= '<td>' . esc_html($row['created_at']) . '</td>';
                $output .= '<td>' . esc_html($row['updated_at']) . '</td>';
                $output .= '<td>' . ($row['trashed'] ? 'Yes' : 'No') . '</td>';
                $output .= '</tr>';
            }
        }
        
        $output .= '</tbody></table>';
        
        // Adaugă și statistici
        $stats = $wpdb->get_row(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN trashed IS NULL THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN trashed IS NOT NULL THEN 1 ELSE 0 END) as trashed,
                GROUP_CONCAT(DISTINCT item_type) as types
             FROM $table_name",
            ARRAY_A
        );
        
        $output .= '<div class="notice notice-info">';
        $output .= '<p><strong>Statistics:</strong> ' . 
                   'Total: ' . $stats['total'] . ' | ' .
                   'Active: ' . $stats['active'] . ' | ' .
                   'Trashed: ' . $stats['trashed'] . ' | ' .
                   'Types: ' . $stats['types'] . '</p>';
        $output .= '</div>';
        
        return $output;
    }
    
    public static function debug_old_data() {
        $old_options = [
            'scfs_custom_fields',
            'scfs_social_buttons',
            'scfs_social_settings',
            'scfs_cdn_settings_custom',
            'scfs_cdn_settings_predefined',
            'cfs_fields',
            'sfb_buttons',
            'sfb_settings',
            'sfb_icon_cdns',
            'sfb_custom_cdns'
        ];
        
        $output = '<h3>Old wp_options Data</h3>';
        
        foreach ($old_options as $option) {
            $data = get_option($option, null);
            $output .= '<div class="debug-section">';
            $output .= '<h4>' . esc_html($option) . '</h4>';
            
            if ($data === null) {
                $output .= '<p>Option does not exist</p>';
            } else if ($data === false) {
                $output .= '<p>Option is false</p>';
            } else if (is_array($data)) {
                $output .= '<p>Array with ' . count($data) . ' elements</p>';
                if (count($data) <= 10) {
                    $output .= '<pre>' . esc_html(print_r($data, true)) . '</pre>';
                } else {
                    $output .= '<p>Data too large to display (' . count($data) . ' elements)</p>';
                }
            } else {
                $output .= '<p>String: ' . esc_html($data) . '</p>';
            }
            
            $output .= '</div>';
        }
        
        return $output;
    }
}