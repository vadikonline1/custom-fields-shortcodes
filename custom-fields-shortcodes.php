<?php
if (!defined('ABSPATH')) exit;

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class CFS_List_Table extends WP_List_Table {
    private $fields;

    public function __construct($fields) {
        parent::__construct([
            'singular' => 'field',
            'plural'   => 'fields',
            'ajax'     => false
        ]);
        $this->fields = $fields;
    }

    public function get_columns() {
        return [
            'cb'            => '<input type="checkbox" />',
            'name'          => 'Name',
            'value'         => 'Value',
            'shortcode'     => 'Shortcode',
            'php_shortcode' => 'PHP Shortcode',
            'actions'       => 'Actions'
        ];
    }

    public function column_cb($item) {
        return '<input type="checkbox" name="fields[]" value="' . esc_attr($item['name']) . '" />';
    }

    public function column_name($item) {
        return '<strong>' . esc_html($item['name']) . '</strong>';
    }

    public function column_value($item) {
        $text = wp_strip_all_tags($item['value']);
        if (strlen($text) > 100) $text = substr($text, 0, 100) . '…';
        return '<div title="' . esc_attr($item['value']) . '">' . esc_html($text) . '</div>';
    }

    public function column_shortcode($item) {
        $code = '[cfs field="' . esc_attr($item['name']) . '"]';
        return '<code class="copy-on-dblclick">' . esc_html($code) . '</code>';
    }

    public function column_php_shortcode($item) {
        $code = 'echo do_shortcode(\'[cfs field="' . esc_attr($item['name']) . '"]\');';
        return '<code class="copy-on-dblclick">' . esc_html($code) . '</code>';
    }

    public function column_actions($item) {
        $delete_url = wp_nonce_url(
            add_query_arg([
                'page'        => 'custom-fields-shortcodes',
                'action'      => 'delete',
                'field'       => $item['name']
            ], admin_url('admin.php')),
            'cfs_delete_field'
        );

        return '
            <button type="button" class="button edit-field"
                data-name="' . esc_attr($item['name']) . '"
                data-value="' . esc_attr($item['value']) . '">Edit</button>
            <a href="' . esc_url($delete_url) . '" class="button delete-field" 
                onclick="return confirm(\'Are you sure you want to delete this field?\');">Delete</a>
        ';
    }

    public function get_bulk_actions() {
        return ['delete' => 'Delete'];
    }

    public function process_bulk_action() {
        if ('delete' === $this->current_action()) {
            check_admin_referer('bulk-' . $this->_args['plural']);
            $fields = get_option('cfs_fields', []);
            if (!empty($_POST['fields'])) {
                foreach ($_POST['fields'] as $f) {
                    unset($fields[$f]);
                }
                update_option('cfs_fields', $fields);
            }
            wp_safe_redirect(menu_page_url('custom-fields-shortcodes', false));
            exit;
        }
    }

    public function prepare_items() {
        $data = [];
        foreach ($this->fields as $name => $value) {
            $data[] = ['name' => $name, 'value' => $value];
        }

        $columns  = $this->get_columns();
        $hidden   = [];
        $sortable = [];

        $this->_column_headers = [$columns, $hidden, $sortable];
        $this->items = $data;

        $this->process_bulk_action();
    }
}

/* -------------------------------
   Admin Page
--------------------------------- */
function cfs_admin_page() {
    if (!current_user_can('administrator')) wp_die('Access denied.');

    $fields = get_option('cfs_fields', []);
    $contact_message = get_option('contact_info_message', '');

    /* ✅ Handle GET delete (single item) */
    if (isset($_GET['action'], $_GET['field']) && $_GET['action'] === 'delete' && check_admin_referer('cfs_delete_field')) {
        $name = sanitize_key($_GET['field']);
        if (isset($fields[$name])) {
            unset($fields[$name]);
            update_option('cfs_fields', $fields);
        }
        wp_safe_redirect(menu_page_url('custom-fields-shortcodes', false));
        exit;
    }

    /* ✅ Handle POST actions (add, edit, save contact info) */
    if (!empty($_POST['cfs_action']) && check_admin_referer('cfs_save_fields', '_wpnonce_cfs')) {
        switch ($_POST['cfs_action']) {
            case 'add':
                $name = sanitize_key($_POST['field_name']);
                if (!empty($name) && !isset($fields[$name])) {
                    $fields[$name] = wp_unslash($_POST['field_value']);
                    update_option('cfs_fields', $fields);
                }
                break;

            case 'edit':
                $name = sanitize_key($_POST['field_name']);
                if (isset($fields[$name])) {
                    $fields[$name] = wp_unslash($_POST['field_value']);
                    update_option('cfs_fields', $fields);
                }
                break;

            case 'save_contact_info':
                $contact_message = wp_unslash($_POST['contact_info_message']);
                update_option('contact_info_message', $contact_message);
                break;
        }

        wp_safe_redirect(menu_page_url('custom-fields-shortcodes', false));
        exit;
    }

    $table = new CFS_List_Table($fields);
    $table->prepare_items();

    wp_enqueue_style('cfs-style', plugins_url('assets/cfs_style.css', __FILE__));
    wp_enqueue_script('cfs-script', plugins_url('assets/cfs_script.js', __FILE__), ['jquery'], false, true);
    ?>

    <div class="wrap">
        <h1>Custom Fields Shortcodes</h1>

        <div style="margin-bottom:15px;">
            <button id="openAddModal" class="button button-primary">Add New Field</button>
            <button id="openContactInfoModal" class="button button-secondary" style="margin-left:10px;">Contact Info</button>
        </div>

        <!-- ✅ FORMULAR corect pentru Bulk Actions -->
        <form method="post">
            <?php wp_nonce_field('bulk-fields'); ?>
            <?php $table->display(); ?>
        </form>
    </div>

    <!-- Add/Edit/Contact Modals (aceleași ca la tine) -->
    <div class="cfs-modal-overlay" id="modalAdd">
        <div class="cfs-modal">
            <span class="cfs-close" data-target="#modalAdd">&times;</span>
            <h2>Add New Field</h2>
            <form method="post">
                <?php wp_nonce_field('cfs_save_fields', '_wpnonce_cfs'); ?>
                <input type="hidden" name="cfs_action" value="add">
                <p><label>Name (lowercase):</label></p>
                <input type="text" name="field_name" required>
                <p><label>Value:</label></p>
                <?php wp_editor('', 'field_value', [
                    'textarea_name' => 'field_value',
                    'media_buttons' => true,
                    'teeny' => false,
                    'quicktags' => true,
                    'textarea_rows' => 10
                ]); ?>
                <div class="cfs-modal-footer">
                    <button type="submit" class="button button-primary">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit -->
    <div class="cfs-modal-overlay" id="modalEdit">
        <div class="cfs-modal">
            <span class="cfs-close" data-target="#modalEdit">&times;</span>
            <h2>Edit Field</h2>
            <form method="post">
                <?php wp_nonce_field('cfs_save_fields', '_wpnonce_cfs'); ?>
                <input type="hidden" name="cfs_action" value="edit">
                <input type="hidden" name="field_name" id="edit_field_name">
                <p><label>Name:</label></p>
                <input type="text" id="display_field_name" disabled style="background:#f0f0f0;">
                <p><label>Value:</label></p>
                <?php wp_editor('', 'edit_field_value', [
                    'textarea_name' => 'field_value',
                    'media_buttons' => true,
                    'teeny' => false,
                    'quicktags' => true,
                    'textarea_rows' => 10
                ]); ?>
                <div class="cfs-modal-footer">
                    <button type="submit" class="button button-primary">Update</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Contact Info Modal -->
    <div class="cfs-modal-overlay" id="modalContactInfo">
        <div class="cfs-modal">
            <span class="cfs-close" data-target="#modalContactInfo">&times;</span>
            <h2>Contact Info</h2>
            <form method="post">
                <?php wp_nonce_field('cfs_save_fields', '_wpnonce_cfs'); ?>
                <input type="hidden" name="cfs_action" value="save_contact_info">
                <?php wp_editor(stripslashes($contact_message), 'contact_info_message', [
                    'textarea_name' => 'contact_info_message',
                    'media_buttons' => true,
                    'teeny' => false,
                    'quicktags' => true,
                    'textarea_rows' => 12
                ]); ?>
                <p><button type="button" id="toggle_fields_list" class="button">Insert Field</button></p>
                <div id="fields_list">
                    <?php foreach ($fields as $name => $value): ?>
                        <div class="cfs-field-item" data-field="<?php echo esc_attr($name); ?>"><?php echo esc_html($name); ?></div>
                    <?php endforeach; ?>
                </div>
                <div class="cfs-modal-footer">
                    <button type="submit" class="button button-primary">Save Message</button>
                </div>
            </form>
        </div>
    </div>

<?php
}

// Shortcode
add_shortcode('cfs', function ($atts) {
    $atts = shortcode_atts(['field' => ''], $atts);
    $fields = get_option('cfs_fields', []);
    if ($atts['field'] === 'contact_info_message')
        return do_shortcode(stripslashes(get_option('contact_info_message', '')));
    return isset($fields[$atts['field']]) ? stripslashes($fields[$atts['field']]) : '';
});
