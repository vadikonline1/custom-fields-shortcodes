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
        add_shortcode('cfs', [$this, 'legacy_shortcode']); // Shortcode pentru compatibilitate
        
        if (is_admin()) {
            add_action('admin_init', [$this, 'handle_form_submissions']);
            add_action('admin_init', [$this, 'handle_single_actions']);
        }
        
        $this->use_database = AjaxHandler::is_migration_done();
    }

    public function admin_menu() {
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
                    
                    if (empty($id)) {
                        $new_id = uniqid('field_');
                        $name = AjaxHandler::generate_slug_with_id($label, $new_id);
                    } else {
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
                    
                    foreach ($fields as $key => $field) {
                        if (isset($field['id']) && $field['id'] === $id) {
                            $existing_field = $field;
                            break;
                        }
                    }
                    
                    if ($existing_field) {
                        $updated_data = array_merge($existing_field, $data);
                        $this->update($id, $updated_data);
                        $message = 'Field updated successfully!';
                    } else {
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
        
        echo '<nav class="scfs-breadcrumb">';
        echo '<a href="' . admin_url('admin.php?page=scfs-oop') . '">Dashboard</a> &raquo; ';
        echo '<span>Custom Fields</span>';
        echo '</nav>';
        
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
                            Use in shortcodes: <br>
                            <code>[scfs_field name="<?php echo esc_attr($field['name']); ?>"]</code><br>
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
            $items = AjaxHandler::get_all_items_from_database('custom_field', $include_trash);
            
            if (empty($items)) {
                return $this->get_from_backup($include_trash);
            }
            
            return $this->format_database_items($items);
        } else {
            return $this->get_from_wp_options($include_trash);
        }
    }
    
    private function get_from_wp_options($include_trash = false) {
        $fields_data = get_option($this->option_name, []);
        
        if (empty($fields_data)) {
            $fields_data = get_option($this->backup_name, []);
        }
        
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
        
        usort($formatted, function($a, $b) {
            $order_a = $a['order'] ?? 9999;
            $order_b = $b['order'] ?? 9999;
            return $order_a <=> $order_b;
        });
        
        return $formatted;
    }
    
    private function format_legacy_data($data) {
        $formatted = [];
        
        if (isset($data[0]) && is_array($data[0])) {
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
        
        foreach ($data as $name => $value) {
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
            $item = AjaxHandler::get_item_from_database($id, 'custom_field');
            if ($item) {
                return $this->format_single_item($item);
            }
        }
        
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
        
        if (!isset($data['name']) || empty($data['name'])) {
            $data['name'] = AjaxHandler::generate_slug_with_id($data['label'] ?? '', $id);
        }
        
        $data['id'] = $id;
        $data['created'] = current_time('mysql');
        $data['trashed'] = false;
        $data['is_legacy'] = false;
        
        if (!isset($data['order']) || empty($data['order'])) {
            $all_fields = $this->get_all();
            $data['order'] = count($all_fields) + 1;
        }
        
        if ($this->use_database) {
            $success = AjaxHandler::save_item_to_database_static(
                $id,
                'custom_field',
                $data,
                $data['order'] ?? 0,
                null
            );
            
            return $success ? $id : false;
        } else {
            $fields = $this->get_all(true);
            $fields[] = $data;
            update_option($this->option_name, $fields);
            return $id;
        }
    }
    
    public function create_with_id($data) {
        if (!isset($data['id']) || empty($data['id'])) {
            $data['id'] = uniqid('field_');
        }
        
        if (!isset($data['name']) || empty($data['name'])) {
            $data['name'] = AjaxHandler::generate_slug_with_id($data['label'] ?? '', $data['id']);
        }
        
        $data['created'] = current_time('mysql');
        $data['trashed'] = false;
        $data['is_legacy'] = false;
        
        if (!isset($data['order']) || empty($data['order'])) {
            $all_fields = $this->get_all();
            $data['order'] = count($all_fields) + 1;
        }
        
        if ($this->use_database) {
            $success = AjaxHandler::save_item_to_database_static(
                $data['id'],
                'custom_field',
                $data,
                $data['order'] ?? 0,
                null
            );
            
            return $success ? $data['id'] : false;
        } else {
            $fields = $this->get_all(true);
            $fields[] = $data;
            update_option($this->option_name, $fields);
            return $data['id'];
        }
    }
    
    public function update($id, $data) {
        if ($this->use_database) {
            $existing_item = $this->get($id);
            if (!$existing_item) {
                return false;
            }
            
            $updated_data = array_merge($existing_item, $data);
            
            return AjaxHandler::save_item_to_database_static(
                $id,
                'custom_field',
                $updated_data,
                $updated_data['order'] ?? 0,
                isset($updated_data['trashed']) && !empty($updated_data['trashed']) ? $updated_data['trashed'] : null
            );
        } else {
            $fields = $this->get_all(true);
            $updated = false;
            
            foreach ($fields as &$field) {
                if (isset($field['id']) && $field['id'] === $id) {
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
    
    // Shortcode pentru formatul nou
    public function shortcode($atts) {
        $atts = shortcode_atts([
            'name' => '',
            'default' => ''
        ], $atts);
        
        $fields = $this->get_all();
        
        foreach ($fields as $field) {
            if (isset($field['name']) && $field['name'] === $atts['name']) {
                return do_shortcode($field['value'] ?? '') ?: $atts['default'];
            }
        }
        
        // Fallback la backup-uri pentru compatibilitate
        $old_fields = get_option($this->backup_name, []);
        if (is_array($old_fields) && isset($old_fields[$atts['name']])) {
            return stripslashes($old_fields[$atts['name']]);
        }
        
        return $atts['default'];
    }
    
    // Shortcode pentru compatibilitate cu versiunea veche [cfs field="nume"]
    public function legacy_shortcode($atts) {
        $atts = shortcode_atts([
            'field' => '',
            'default' => ''
        ], $atts);
        
        if (empty($atts['field'])) {
            return $atts['default'];
        }
        
        // Folosește funcția principală de shortcode cu parametrul 'name'
        return $this->shortcode(['name' => $atts['field'], 'default' => $atts['default']]);
    }
}
