<?php
namespace SCFS;

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class SocialButtonsTable extends \WP_List_Table {
    private $is_trash = false;
    private $social_buttons;
    
    public function __construct($is_trash = false) {
        $this->is_trash = $is_trash;
        $this->social_buttons = SocialButtons::get_instance();
        
        parent::__construct([
            'singular' => 'button',
            'plural' => 'buttons',
            'ajax' => false
        ]);
    }
    
    public function get_columns() {
        $columns = [
            'cb' => '<input type="checkbox" />',
            'order' => 'Order',
            'name' => 'Name',
            'label' => 'Label',
            'icon' => 'Icon',
            'type' => 'Type',
            'shortcode' => 'Shortcode',
        ];
        
        if (!$this->is_trash) {
            $columns['floating'] = 'Floating';
            $columns['actions'] = 'Actions';
        } else {
            $columns['trashed'] = 'Trashed Date';
            $columns['actions'] = 'Actions';
        }
        
        return $columns;
    }
    
    public function get_sortable_columns() {
        return [
            'order' => ['order', true],
            'name' => ['name', false],
            'label' => ['label', false]
        ];
    }
    
    public function prepare_items() {
        // Obține toate butoanele (inclusiv cele din trash)
        $all_buttons = $this->social_buttons->get_all(true);
        
        // Filtrează butoanele: doar cele din trash sau doar cele active
        $buttons = [];
        foreach ($all_buttons as $button) {
            $is_trashed = !empty($button['trashed']);
            
            if ($this->is_trash && $is_trashed) {
                $buttons[] = $button;
            } elseif (!$this->is_trash && !$is_trashed) {
                $buttons[] = $button;
            }
        }
        
        // Adaugă index pentru fiecare buton pentru a preveni erorile
        $buttons = array_values($buttons);
        
        // Sortare
        $orderby = $_GET['orderby'] ?? 'order';
        $order = $_GET['order'] ?? 'asc';
        
        usort($buttons, function($a, $b) use ($orderby, $order) {
            if (!isset($a[$orderby]) || !isset($b[$orderby])) {
                return 0;
            }
            
            $a_val = $a[$orderby] ?? '';
            $b_val = $b[$orderby] ?? '';
            
            $result = 0;
            if (is_numeric($a_val) && is_numeric($b_val)) {
                $result = $a_val <=> $b_val;
            } else {
                $result = strcasecmp(strval($a_val), strval($b_val));
            }
            
            return $order === 'desc' ? -$result : $result;
        });
        
        // Paginare
        $per_page = 20;
        $current_page = $this->get_pagenum();
        $total_items = count($buttons);
        $offset = ($current_page - 1) * $per_page;
        
        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ]);
        
        $this->items = array_slice($buttons, $offset, $per_page);
        $this->_column_headers = [$this->get_columns(), [], $this->get_sortable_columns()];
        
        // Procesează bulk actions doar dacă sunt trimise
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
    
    public function column_order($item) {
        $order = isset($item['order']) ? intval($item['order']) : 0;
        return '<span class="scfs-order">' . $order . '</span>';
    }
    
    public function column_name($item) {
        if (!isset($item['id'])) {
            return '<strong>Error: No ID</strong>';
        }
        
        $name = '<strong>' . esc_html($item['name'] ?? 'Unnamed') . '</strong>';
        
        if (!$this->is_trash) {
            $actions = [
                'edit' => sprintf(
                    '<a href="%s">Edit</a>',
                    admin_url('admin.php?page=scfs-social-buttons&action=edit&id=' . $item['id'])
                ),
                'trash' => sprintf(
                    '<a href="%s" onclick="return confirm(\'Move to trash?\')">Trash</a>',
                    wp_nonce_url(
                        admin_url('admin.php?page=scfs-social-buttons&action=trash_single&id=' . $item['id']),
                        'trash_button_' . $item['id']
                    )
                )
            ];
            
            $name .= $this->row_actions($actions);
        }
        
        return $name;
    }
    
    public function column_label($item) {
        return esc_html($item['label'] ?? '');
    }
    
    public function column_icon($item) {
        return '<i class="' . esc_attr($item['icon'] ?? 'fas fa-link') . '"></i>';
    }
    
    public function column_type($item) {
        $types = [
            'url' => 'URL',
            'tel' => 'Phone',
            'mailto' => 'Email',
            'whatsapp' => 'WhatsApp',
            'facebook' => 'Facebook',
            'instagram' => 'Instagram',
            'twitter' => 'Twitter',
            'linkedin' => 'LinkedIn',
            'youtube' => 'YouTube'
        ];
        
        $type = $item['type'] ?? 'url';
        return $types[$type] ?? esc_html(ucfirst($type));
    }
    
    public function column_shortcode($item) {
        $name = $item['name'] ?? '';
        if (empty($name)) {
            return '<span style="color:#999;">No shortcode</span>';
        }
        return '<code>[scfs_social name="' . esc_attr($name) . '"]</code>';
    }
    
    public function column_floating($item) {
        if (isset($item['floating']) && $item['floating']) {
            return '<span class="dashicons dashicons-yes" style="color:#46b450;"></span>';
        }
        return '<span class="dashicons dashicons-no" style="color:#dc3232;"></span>';
    }
    
    public function column_trashed($item) {
        return isset($item['trashed']) && !empty($item['trashed']) 
            ? date_i18n('Y-m-d H:i', strtotime($item['trashed'])) 
            : '';
    }
    
    public function column_actions($item) {
        if (!isset($item['id'])) {
            return '';
        }
        
        if ($this->is_trash) {
            $restore_url = wp_nonce_url(
                admin_url('admin.php?page=scfs-social-buttons&action=restore_single&id=' . $item['id']),
                'restore_button_' . $item['id']
            );
            $delete_url = wp_nonce_url(
                admin_url('admin.php?page=scfs-social-buttons&action=delete_single&id=' . $item['id']),
                'delete_button_' . $item['id']
            );
            
            return sprintf(
                '<a href="%s" class="button button-small">Restore</a> ' .
                '<a href="%s" class="button button-small button-danger" onclick="return confirm(\'Delete permanently?\')">Delete</a>',
                $restore_url,
                $delete_url
            );
        } else {
            $edit_url = admin_url('admin.php?page=scfs-social-buttons&action=edit&id=' . $item['id']);
            $trash_url = wp_nonce_url(
                admin_url('admin.php?page=scfs-social-buttons&action=trash_single&id=' . $item['id']),
                'trash_button_' . $item['id']
            );
            
            return sprintf(
                '<a href="%s" class="button button-small">Edit</a> ' .
                '<a href="%s" class="button button-small button-link-delete" onclick="return confirm(\'Move to trash?\')">Trash</a>',
                $edit_url,
                $trash_url
            );
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
        
        // Verifică nonce pentru bulk actions
        if (!isset($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'], 'bulk-' . $this->_args['plural'])) {
            return;
        }
        
        if (!isset($_POST['id']) || empty($_POST['id'])) {
            return;
        }
        
        $ids = is_array($_POST['id']) ? $_POST['id'] : [$_POST['id']];
        $ids = array_map('sanitize_text_field', $ids);
        
        $results = [];
        
        switch ($this->current_action()) {
            case 'trash':
                foreach ($ids as $id) {
                    if ($this->social_buttons->trash($id)) {
                        $results[] = $id;
                    }
                }
                break;
                
            case 'restore':
                foreach ($ids as $id) {
                    if ($this->social_buttons->restore($id)) {
                        $results[] = $id;
                    }
                }
                break;
                
            case 'delete_permanently':
                foreach ($ids as $id) {
                    if ($this->social_buttons->delete($id)) {
                        $results[] = $id;
                    }
                }
                break;
        }
        
        // Redirect după bulk action
        $redirect_url = admin_url('admin.php?page=scfs-social-buttons');
        if ($this->is_trash) {
            $redirect_url .= '&action=trash';
        }
        
        if (!empty($results)) {
            $count = count($results);
            $message = sprintf(
                '%d item%s %s successfully!',
                $count,
                $count > 1 ? 's' : '',
                $this->current_action() === 'trash' ? 'moved to trash' : 
                ($this->current_action() === 'restore' ? 'restored' : 'permanently deleted')
            );
            $redirect_url .= '&message=' . urlencode($message);
        }
        
        wp_redirect($redirect_url);
        exit;
    }
    
    public function extra_tablenav($which) {
        if ($which === 'top' && !$this->is_trash) {
            $current_type = $_GET['type_filter'] ?? '';
            $current_floating = $_GET['floating_filter'] ?? '';
            ?>
            <div class="alignleft actions">
                <label for="filter-type" class="screen-reader-text">Filter by type</label>
                <select name="type_filter" id="filter-type">
                    <option value="">All Types</option>
                    <option value="url" <?php selected($current_type, 'url'); ?>>URL</option>
                    <option value="tel" <?php selected($current_type, 'tel'); ?>>Phone</option>
                    <option value="mailto" <?php selected($current_type, 'mailto'); ?>>Email</option>
                    <option value="whatsapp" <?php selected($current_type, 'whatsapp'); ?>>WhatsApp</option>
                </select>
                
                <label for="filter-floating" class="screen-reader-text">Filter by floating</label>
                <select name="floating_filter" id="filter-floating">
                    <option value="">All</option>
                    <option value="1" <?php selected($current_floating, '1'); ?>>Floating Only</option>
                    <option value="0" <?php selected($current_floating, '0'); ?>>Non-Floating</option>
                </select>
                
                <input type="submit" name="filter_action" class="button" value="Filter">
                <?php if ($current_type || $current_floating): ?>
                <a href="<?php echo remove_query_arg(['type_filter', 'floating_filter']); ?>" class="button">Clear filters</a>
                <?php endif; ?>
            </div>
            <?php
        }
    }
    
    public function no_items() {
        if ($this->is_trash) {
            echo 'No buttons found in trash.';
        } else {
            echo 'No buttons found.';
        }
    }
}
