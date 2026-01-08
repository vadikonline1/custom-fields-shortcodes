<?php
namespace SCFS;
use SCFS\AjaxHandler;

class CustomFields {
    private static $instance = null;
    private $table;
    private $option_name = 'scfs_custom_fields';
    private $backup_name = 'bpk_cfs_fields';
    private $use_database = false;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', [$this, 'admin_menu'], 20);
        add_shortcode('scfs_field', [$this, 'shortcode']);
        
        if (is_admin()) {
            add_action('admin_init', [$this, 'handle_form_submissions']);
            add_action('admin_init', [$this, 'handle_single_actions']);
        }
        
        // Verifică dacă să folosească baza de date
        $this->use_database = AjaxHandler::is_migration_done();
    }

    public function admin_menu() {
        // Submenu pentru Custom Fields
        add_submenu_page(
            'scfs-oop',
            'Custom Fields',
            'Custom Fields',
            'manage_options',
            'scfs-custom-fields',
            [$this, 'admin_page']
        );
    }

    public function handle_form_submissions() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'scfs-custom-fields') {
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['scfs_action'])) {
            check_admin_referer('scfs_save_field');
            
            $action = sanitize_text_field($_POST['scfs_action']);
            $id = sanitize_text_field($_POST['id'] ?? '');
            
            switch ($action) {
                case 'save':
                    $label = sanitize_text_field($_POST['label']);
                    
                    // Determină name-ul
                    if (empty($id)) {
                        // ADĂUGARE NOUĂ - generează ID și apoi slug
                        $new_id = uniqid('field_');
                        $name = AjaxHandler::generate_slug_with_id($label, $new_id);
                    } else {
                        // EDITARE - FORȚE folosirea slug-ului din baza de date
                        $existing_field = $this->get($id);
                        $name = $existing_field['name'] ?? '';
                    }
                    
                    $data = [
                        'name' => $name,
                        'label' => $label,
                        'value' => wp_kses_post($_POST['value'])
                    ];
                    
                    $fields = $this->get_all(true);
                    $existing_field = null;
                    
                    // Găsește field-ul existent
                    foreach ($fields as $key => $field) {
                        if (isset($field['id']) && $field['id'] === $id) {
                            $existing_field = $field;
                            break;
                        }
                    }
                    
                    if ($existing_field) {
                        // Update - păstrează toate datele existente
                        $updated_data = array_merge($existing_field, $data);
                        $this->update($id, $updated_data);
                        $message = 'Field updated successfully!';
                    } else {
                        // Crează field-ul cu ID-ul generat
                        $data['id'] = $new_id;
                        $this->create_with_id($data);
                        $message = 'Field created successfully!';
                    }
                    
                    ob_start();
                    wp_redirect(admin_url('admin.php?page=scfs-custom-fields&message=' . urlencode($message)));
                    exit;
            }
        }
    }
    
    public function handle_single_actions() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'scfs-custom-fields') {
            return;
        }
        
        $action = $_GET['action'] ?? '';
        $id = $_GET['id'] ?? '';
        
        if (empty($action) || empty($id)) {
            return;
        }
        
        $nonce_action = '';
        $redirect_url = admin_url('admin.php?page=scfs-custom-fields');
        
        switch ($action) {
            case 'trash_single':
                $nonce_action = 'trash_field_' . $id;
                if (wp_verify_nonce($_GET['_wpnonce'] ?? '', $nonce_action)) {
                    $this->trash($id);
                    $redirect_url .= '&message=' . urlencode('Field moved to trash!');
                }
                break;
                
            case 'restore_single':
                $nonce_action = 'restore_field_' . $id;
                if (wp_verify_nonce($_GET['_wpnonce'] ?? '', $nonce_action)) {
                    $this->restore($id);
                    $redirect_url .= '&message=' . urlencode('Field restored!');
                }
                break;
                
            case 'delete_single':
                $nonce_action = 'delete_field_' . $id;
                if (wp_verify_nonce($_GET['_wpnonce'] ?? '', $nonce_action)) {
                    $this->delete($id);
                    $redirect_url .= '&message=' . urlencode('Field permanently deleted!');
                }
                break;
        }
        
        if ($nonce_action) {
            ob_start();
            wp_redirect($redirect_url);
            exit;
        }
    }
    
    public function admin_page() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'scfs-custom-fields') {
            return;
        }

        $action = $_GET['action'] ?? '';
        $id = $_GET['id'] ?? 0;
        $message = $_GET['message'] ?? '';
        
        echo '<div class="wrap scfs-admin">';
        
        // Adaugă breadcrumb
        echo '<nav class="scfs-breadcrumb">';
        echo '<a href="' . admin_url('admin.php?page=scfs-oop') . '">Dashboard</a> &raquo; ';
        echo '<span>Custom Fields</span>';
        echo '</nav>';
        
        // Show messages
        if ($message) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html(urldecode($message)) . '</p></div>';
        }
        
        switch ($action) {
            case 'add':
            case 'edit':
                $this->edit_form($id);
                break;
            case 'trash':
                $this->trash_page();
                break;
            default:
                $this->list_page();
        }
        
        echo '</div>';
    }
    
    private function list_page() {
        if (!class_exists('SCFS\\CustomFieldsTable')) {
            require_once plugin_dir_path(__FILE__) . 'CustomFieldsTable.php';
        }
        
        $this->table = new CustomFieldsTable();
        $this->table->prepare_items();
        
        ?>
        <h1 class="wp-heading-inline">Custom Fields</h1>
        <a href="<?php echo admin_url('admin.php?page=scfs-custom-fields&action=add'); ?>" class="page-title-action">
            Add New
        </a>
        <a href="<?php echo admin_url('admin.php?page=scfs-custom-fields&action=trash'); ?>" class="page-title-action">
            Trash (<?php echo $this->get_trash_count(); ?>)
        </a>
        
        <form method="post" action="<?php echo admin_url('admin.php?page=scfs-custom-fields'); ?>">
            <?php $this->table->display(); ?>
        </form>
        <?php
    }
    
    private function edit_form($id) {
        $field = $id ? $this->get($id) : null;
        $is_edit = (bool) $field;
        
        ?>
        <h1><?php echo $is_edit ? 'Edit Field' : 'Add New Field'; ?></h1>
        
        <form method="post" class="scfs-form">
            <?php wp_nonce_field('scfs_save_field'); ?>
            <input type="hidden" name="scfs_action" value="save">
            <?php if ($is_edit): ?>
                <input type="hidden" name="id" value="<?php echo esc_attr($id); ?>">
                <!-- Slug-ul este FORȚAT în PHP, nu în formular -->
                <input type="hidden" name="name" id="name_hidden" value="<?php echo esc_attr($field['name'] ?? ''); ?>">
            <?php endif; ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="label">Label</label></th>
                    <td>
                        <input type="text" name="label" id="label" 
                               value="<?php echo esc_attr($field['label'] ?? ''); ?>"
                               class="regular-text" required>
                        <p class="description">Display name for the field</p>
                    </td>
                </tr>
                
                <?php if ($is_edit && isset($field['name'])): ?>
                <tr>
                    <th scope="row"><label>Name (Slug)</label></th>
                    <td>
                        <div style="padding: 8px 10px; background-color: #f5f5f5; border: 1px solid #ddd; border-radius: 4px; font-family: monospace;">
                            <?php echo esc_html($field['name']); ?>
                        </div>
                        <p class="description">
                            <strong>Auto-generated from Label with ID suffix. Cannot be changed.</strong><br>
                            Use in shortcode: <code>[scfs_field name="<?php echo esc_attr($field['name']); ?>"]</code>
                        </p>
                    </td>
                </tr>
                <?php endif; ?>
                
                <tr>
                    <th scope="row"><label for="value">Value</label></th>
                    <td>
                        <?php 
                        $editor_id = 'value';
                        $editor_settings = [
                            'textarea_name' => 'value',
                            'editor_height' => 300,
                            'media_buttons' => true,
                            'tinymce' => true,
                            'quicktags' => true,
                            'teeny' => false
                        ];
                        
                        wp_editor(
                            $field['value'] ?? '',
                            $editor_id,
                            $editor_settings
                        );
                        ?>
                    </td>
                </tr>
            </table>
            
            <?php submit_button($is_edit ? 'Update Field' : 'Add Field'); ?>
            <a href="<?php echo admin_url('admin.php?page=scfs-custom-fields'); ?>" class="button button-secondary">
                Cancel
            </a>
        </form>
        <?php
    }
    
    private function trash_page() {
        if (!class_exists('SCFS\\CustomFieldsTable')) {
            require_once plugin_dir_path(__FILE__) . 'CustomFieldsTable.php';
        }
        
        $table = new CustomFieldsTable(true);
        $table->prepare_items();
        
        ?>
        <h1>Trash</h1>
        <a href="<?php echo admin_url('admin.php?page=scfs-custom-fields'); ?>" class="button">Back to Custom Fields</a>
        
        <form method="post">
            <?php $table->display(); ?>
        </form>
        <?php
    }
    
    // CRUD Methods
    public function get_all($include_trash = false) {
        if ($this->use_database) {
            // Folosește baza de date pentru valori
            $items = AjaxHandler::get_all_items_from_database('custom_field', $include_trash);
            
            // Dacă nu există date în baza de date, verifică backup-urile
            if (empty($items)) {
                return $this->get_from_backup($include_trash);
            }
            
            return $this->format_database_items($items);
        } else {
            // Folosește wp_options pentru compatibilitate backward
            return $this->get_from_wp_options($include_trash);
        }
    }
    
    private function get_from_wp_options($include_trash = false) {
        $fields_data = get_option($this->option_name, []);
        
        // Dacă nu există date, verifică backup-ul
        if (empty($fields_data)) {
            $fields_data = get_option($this->backup_name, []);
        }
        
        // Transformă datele vechi în formatul nou
        $formatted_data = $this->format_legacy_data($fields_data);
        
        if (!$include_trash) {
            $formatted_data = array_filter($formatted_data, function($field) {
                return empty($field['trashed']);
            });
        }
        
        return array_values($formatted_data);
    }
    
    private function get_from_backup($include_trash = false) {
        $backup_data = get_option($this->backup_name, []);
        
        if (empty($backup_data)) {
            return [];
        }
        
        $formatted_data = $this->format_legacy_data($backup_data);
        
        if (!$include_trash) {
            $formatted_data = array_filter($formatted_data, function($field) {
                return empty($field['trashed']);
            });
        }
        
        return array_values($formatted_data);
    }
    
    private function format_database_items($items) {
        $formatted = [];
        
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            
            // Asigură-te că toate câmpurile necesare există
            $defaults = [
                'name' => '',
                'label' => '',
                'value' => '',
                'created' => current_time('mysql'),
                'trashed' => false,
                'order' => count($formatted) + 1,
                'is_legacy' => false
            ];
            
            $formatted[] = array_merge($defaults, $item);
        }
        
        // Sortează după order
        usort($formatted, function($a, $b) {
            $order_a = $a['order'] ?? 9999;
            $order_b = $b['order'] ?? 9999;
            return $order_a <=> $order_b;
        });
        
        return $formatted;
    }
    
    private function format_legacy_data($data) {
        $formatted = [];
        
        // Dacă datele sunt în format vechi (cheie => valoare)
        if (isset($data[0]) && is_array($data[0])) {
            // Format nou (deja structurat)
            foreach ($data as $item) {
                if (!is_array($item)) continue;
                
                $defaults = [
                    'id' => $item['id'] ?? uniqid('field_'),
                    'name' => '',
                    'label' => '',
                    'value' => '',
                    'created' => current_time('mysql'),
                    'trashed' => false,
                    'order' => count($formatted) + 1,
                    'is_legacy' => true
                ];
                
                $formatted[] = array_merge($defaults, $item);
            }
            
            return $formatted;
        }
        
        // Format vechi - convertește
        foreach ($data as $name => $value) {
            // Skip dacă nu este un field valid
            if (empty($name) || !is_string($name)) {
                continue;
            }
            
            $formatted[] = [
                'id' => uniqid('legacy_'),
                'name' => $name,
                'label' => ucfirst(str_replace(['_', '-'], ' ', $name)),
                'value' => $value,
                'created' => current_time('mysql'),
                'trashed' => false,
                'order' => count($formatted) + 1,
                'is_legacy' => true
            ];
        }
        
        return $formatted;
    }
    
    public function get($id) {
        if ($this->use_database) {
            // Caută în baza de date
            $item = AjaxHandler::get_item_from_database($id, 'custom_field');
            if ($item) {
                return $this->format_single_item($item);
            }
        }
        
        // Fallback la wp_options sau backup
        $fields = $this->get_all(true);
        
        foreach ($fields as $field) {
            if (isset($field['id']) && $field['id'] === $id) {
                return $field;
            }
        }
        
        return null;
    }
    
    private function format_single_item($item) {
        if (!is_array($item)) {
            return null;
        }
        
        $defaults = [
            'name' => '',
            'label' => '',
            'value' => '',
            'created' => current_time('mysql'),
            'trashed' => false,
            'order' => 0,
            'is_legacy' => false
        ];
        
        return array_merge($defaults, $item);
    }
    
    public function create($data) {
        $id = uniqid('field_');
        
        // Generează slug-ul cu ID
        if (!isset($data['name']) || empty($data['name'])) {
            $data['name'] = AjaxHandler::generate_slug_with_id($data['label'] ?? '', $id);
        }
        
        // Adaugă metadate
        $data['id'] = $id;
        $data['created'] = current_time('mysql');
        $data['trashed'] = false;
        $data['is_legacy'] = false;
        
        // Setează order-ul
        if (!isset($data['order']) || empty($data['order'])) {
            $all_fields = $this->get_all();
            $data['order'] = count($all_fields) + 1;
        }
        
        if ($this->use_database) {
            // Salvează în baza de date
            $success = AjaxHandler::save_item_to_database_static(
                $id,
                'custom_field',
                $data,
                $data['order'] ?? 0,
                null
            );
            
            return $success ? $id : false;
        } else {
            // Salvează în wp_options
            $fields = $this->get_all(true);
            $fields[] = $data;
            update_option($this->option_name, $fields);
            return $id;
        }
    }
    
    public function create_with_id($data) {
        // Asigură-te că ID-ul există
        if (!isset($data['id']) || empty($data['id'])) {
            $data['id'] = uniqid('field_');
        }
        
        // Generează slug-ul cu ID
        if (!isset($data['name']) || empty($data['name'])) {
            $data['name'] = AjaxHandler::generate_slug_with_id($data['label'] ?? '', $data['id']);
        }
        
        // Adaugă metadate
        $data['created'] = current_time('mysql');
        $data['trashed'] = false;
        $data['is_legacy'] = false;
        
        // Setează order-ul
        if (!isset($data['order']) || empty($data['order'])) {
            $all_fields = $this->get_all();
            $data['order'] = count($all_fields) + 1;
        }
        
        if ($this->use_database) {
            // Salvează în baza de date
            $success = AjaxHandler::save_item_to_database_static(
                $data['id'],
                'custom_field',
                $data,
                $data['order'] ?? 0,
                null
            );
            
            return $success ? $data['id'] : false;
        } else {
            // Salvează în wp_options
            $fields = $this->get_all(true);
            $fields[] = $data;
            update_option($this->option_name, $fields);
            return $data['id'];
        }
    }
    
    public function update($id, $data) {
        if ($this->use_database) {
            // Obține item-ul existent
            $existing_item = $this->get($id);
            if (!$existing_item) {
                return false;
            }
            
            // Merge datele
            $updated_data = array_merge($existing_item, $data);
            
            // Salvează în baza de date
            return AjaxHandler::save_item_to_database_static(
                $id,
                'custom_field',
                $updated_data,
                $updated_data['order'] ?? 0,
                isset($updated_data['trashed']) && !empty($updated_data['trashed']) ? $updated_data['trashed'] : null
            );
        } else {
            // Update în wp_options
            $fields = $this->get_all(true);
            $updated = false;
            
            foreach ($fields as &$field) {
                if (isset($field['id']) && $field['id'] === $id) {
                    // Păstrează slug-ul original, nu-l suprascrie
                    if (isset($field['name']) && isset($data['name']) && $field['name'] !== $data['name']) {
                        unset($data['name']);
                    }
                    
                    $field = array_merge($field, $data);
                    $updated = true;
                    break;
                }
            }
            
            if ($updated) {
                update_option($this->option_name, $fields);
            }
            
            return $updated;
        }
    }
    
    public function trash($id) {
        if ($this->use_database) {
            return AjaxHandler::trash_item_in_database($id, 'custom_field');
        } else {
            $fields = $this->get_all(true);
            $trashed = false;
            
            foreach ($fields as &$field) {
                if (isset($field['id']) && $field['id'] === $id) {
                    $field['trashed'] = current_time('mysql');
                    $trashed = true;
                    break;
                }
            }
            
            if ($trashed) {
                update_option($this->option_name, $fields);
            }
            
            return $trashed;
        }
    }
    
    public function restore($id) {
        if ($this->use_database) {
            return AjaxHandler::restore_item_in_database($id, 'custom_field');
        } else {
            $fields = $this->get_all(true);
            $restored = false;
            
            foreach ($fields as &$field) {
                if (isset($field['id']) && $field['id'] === $id) {
                    unset($field['trashed']);
                    $restored = true;
                    break;
                }
            }
            
            if ($restored) {
                update_option($this->option_name, $fields);
            }
            
            return $restored;
        }
    }
    
    public function delete($id) {
        if ($this->use_database) {
            return AjaxHandler::delete_item_from_database($id, 'custom_field');
        } else {
            $fields = $this->get_all(true);
            $new_fields = [];
            $deleted = false;
            
            foreach ($fields as $field) {
                if (!isset($field['id']) || $field['id'] !== $id) {
                    $new_fields[] = $field;
                } else {
                    // Dacă este field legacy, îl păstrăm dar îl marcam ca trashed
                    if (isset($field['is_legacy']) && $field['is_legacy']) {
                        $field['trashed'] = current_time('mysql');
                        $new_fields[] = $field;
                    }
                    $deleted = true;
                }
            }
            
            if ($deleted) {
                update_option($this->option_name, $new_fields);
            }
            
            return $deleted;
        }
    }
    
    public function get_trash_count() {
        $fields = $this->get_all(true);
        $count = 0;
        
        foreach ($fields as $field) {
            if (!empty($field['trashed'])) $count++;
        }
        
        return $count;
    }
    
    public function shortcode($atts) {
        $atts = shortcode_atts([
            'name' => '',
            'default' => ''
        ], $atts);
        
        // Caută field-ul
        $fields = $this->get_all();
        
        foreach ($fields as $field) {
            if (isset($field['name']) && $field['name'] === $atts['name']) {
                return do_shortcode($field['value'] ?? '') ?: $atts['default'];
            }
        }
        
        // Fallback direct la datele vechi din backup
        $old_fields = get_option($this->backup_name, []);
        if (is_array($old_fields) && isset($old_fields[$atts['name']])) {
            return stripslashes($old_fields[$atts['name']]);
        }
        
        return $atts['default'];
    }
}