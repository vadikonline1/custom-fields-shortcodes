<?php
namespace SCFS;

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class CustomFieldsTable extends \WP_List_Table {
    private $is_trash = false;
    private $custom_fields;
    
    public function __construct($is_trash = false) {
        $this->is_trash = $is_trash;
        $this->custom_fields = CustomFields::get_instance();
        
        parent::__construct([
            'singular' => 'field',
            'plural' => 'fields',
            'ajax' => false
        ]);
    }
    
    public function get_columns() {
        $columns = [
            'cb' => '<input type="checkbox" />',
            'name' => 'Name',
            'label' => 'Label',
            'shortcode' => 'Shortcode',
        ];
        
        if (!$this->is_trash) {
            $columns['actions'] = 'Actions';
        } else {
            $columns['trashed'] = 'Trashed Date';
            $columns['actions'] = 'Actions';
        }
        
        return $columns;
    }
    
    public function prepare_items() {
        $all_fields = $this->custom_fields->get_all(true);
        
        $fields = [];
        foreach ($all_fields as $field) {
            $is_trashed = !empty($field['trashed']);
            
            if ($this->is_trash && $is_trashed) {
                $fields[] = $field;
            } elseif (!$this->is_trash && !$is_trashed) {
                $fields[] = $field;
            }
        }
        
        $data = [];
        foreach ($fields as $field) {
            if (isset($field['id'])) {
                $data[] = array_merge($field, ['id' => $field['id']]);
            }
        }
        
        $this->_column_headers = [$this->get_columns(), [], $this->get_sortable_columns()];
        $this->items = $data;
        
        if ($this->current_action() && isset($_POST['id'])) {
            $this->process_bulk_action();
        }
    }
    
    public function column_cb($item) {
        if (isset($item['id'])) {
            return sprintf('<input type="checkbox" name="id[]" value="%s" />', $item['id']);
        }
        return '';
    }
    
    public function column_name($item) {
        $name = isset($item['name']) ? '<strong>' . esc_html($item['name']) . '</strong>' : '<strong>No Name</strong>';
        
        if (!$this->is_trash && isset($item['id'])) {
            $actions = [
                'edit' => sprintf(
                    '<a href="%s">Edit</a>',
                    admin_url('admin.php?page=scfs-custom-fields&action=edit&id=' . $item['id'])
                ),
                'trash' => sprintf(
                    '<a href="%s" onclick="return confirm(\'Move to trash?\')">Trash</a>',
                    wp_nonce_url(
                        admin_url('admin.php?page=scfs-custom-fields&action=trash_single&id=' . $item['id']),
                        'trash_field_' . $item['id']
                    )
                )
            ];
            
            $name .= $this->row_actions($actions);
        }
        
        return $name;
    }
    
    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'label':
                return esc_html($item['label'] ?? '');
            case 'shortcode':
                $new_shortcode = '<code>[scfs_field name="' . esc_attr($item['name'] ?? '') . '"]</code>';
                return $new_shortcode;
            case 'trashed':
                return isset($item['trashed']) && !empty($item['trashed']) 
                    ? date_i18n('Y-m-d H:i', strtotime($item['trashed'])) 
                    : '';
            case 'actions':
                if (!isset($item['id'])) return '';
                
                if ($this->is_trash) {
                    $restore_url = wp_nonce_url(
                        admin_url('admin.php?page=scfs-custom-fields&action=restore_single&id=' . $item['id']),
                        'restore_field_' . $item['id']
                    );
                    $delete_url = wp_nonce_url(
                        admin_url('admin.php?page=scfs-custom-fields&action=delete_single&id=' . $item['id']),
                        'delete_field_' . $item['id']
                    );
                    
                    return sprintf(
                        '<a href="%s" class="button button-small">Restore</a> ' .
                        '<a href="%s" class="button button-small button-danger" onclick="return confirm(\'Delete permanently?\')">Delete</a>',
                        $restore_url,
                        $delete_url
                    );
                } else {
                    $edit_url = admin_url('admin.php?page=scfs-custom-fields&action=edit&id=' . $item['id']);
                    $trash_url = wp_nonce_url(
                        admin_url('admin.php?page=scfs-custom-fields&action=trash_single&id=' . $item['id']),
                        'trash_field_' . $item['id']
                    );
                    
                    return sprintf(
                        '<a href="%s" class="button">Edit</a> ' .
                        '<a href="%s" class="button button-link-delete" onclick="return confirm(\'Move to trash?\')">Trash</a>',
                        $edit_url,
                        $trash_url
                    );
                }
            default:
                return '';
        }
    }
    
    public function get_bulk_actions() {
        if ($this->is_trash) {
            return [
                'restore' => 'Restore',
                'delete_permanently' => 'Delete Permanently'
            ];
        } else {
            return [
                'trash' => 'Move to Trash'
            ];
        }
    }
    
    public function process_bulk_action() {
        if (empty($this->current_action())) return;
        
        if (!isset($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'], 'bulk-' . $this->_args['plural'])) {
            return;
        }
        
        if (!isset($_POST['id']) || empty($_POST['id'])) {
            return;
        }
        
        $ids = is_array($_POST['id']) ? $_POST['id'] : [$_POST['id']];
        $ids = array_map('sanitize_text_field', $ids);
        
        switch ($this->current_action()) {
            case 'trash':
                foreach ($ids as $id) {
                    $this->custom_fields->trash($id);
                }
                break;
                
            case 'restore':
                foreach ($ids as $id) {
                    $this->custom_fields->restore($id);
                }
                break;
                
            case 'delete_permanently':
                foreach ($ids as $id) {
                    $this->custom_fields->delete($id);
                }
                break;
        }
        
        $redirect_url = admin_url('admin.php?page=scfs-custom-fields');
        if ($this->is_trash) {
            $redirect_url .= '&action=trash';
        }
        
        wp_redirect($redirect_url);
        exit;
    }
    
    public function no_items() {
        if ($this->is_trash) {
            echo 'No custom fields found in trash.';
        } else {
            echo 'No custom fields found.';
        }
    }
}
