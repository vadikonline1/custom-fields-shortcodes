<?php
namespace SCFS;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use SCFS\AjaxHandler;

class SocialButtons {
    private static $instance = null;
    private $option_name = 'scfs_social_buttons';
    private $backup_name = 'bpk_sfb_buttons';
    private $use_database = false;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', [$this, 'admin_menu'], 20);
        add_shortcode('scfs_social', [$this, 'shortcode']);
        
        if (is_admin()) {
            add_action('admin_init', [$this, 'handle_form_submissions']);
            add_action('admin_init', [$this, 'handle_single_actions']);
        }
        
        $this->use_database = AjaxHandler::is_migration_done();
    }
    
    public function admin_menu() {
        add_submenu_page(
            'scfs-oop',
            'Social Buttons',
            'Social Buttons',
            'manage_options',
            'scfs-social-buttons',
            [$this, 'admin_page']
        );
    }

    public function handle_form_submissions() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'scfs-social-buttons') {
            return;
        }

        $method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : '';
        
        if ('POST' !== $method) {
            return;
        }

        if ( !isset($_POST['_wpnonce']) || !wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'scfs_save_button' ) ) {
            return;
        }
        
        if ( !isset($_POST['scfs_action']) ) {
            return;
        }
        
        $action = sanitize_text_field( wp_unslash( $_POST['scfs_action'] ) );
        
        if ( $action !== 'save' ) {
            return;
        }
        
        $id = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
        
        $label = sanitize_text_field( wp_unslash( $_POST['label'] ?? '' ) );
        $url = sanitize_text_field( wp_unslash( $_POST['url'] ?? '' ) );
        $type = sanitize_text_field( wp_unslash( $_POST['type'] ?? 'url' ) );
        $icon = sanitize_text_field( wp_unslash( $_POST['icon'] ?? 'fas fa-link' ) );
        $order = intval( $_POST['order'] ?? 0 );
        $floating = isset( $_POST['floating'] ) ? 1 : 0;
        
        // Clean URL based on type
        switch ($type) {
            case 'tel':
                $url = preg_replace('/[^0-9+]/', '', $url);
                if (strpos($url, 'tel:') === 0) {
                    $url = substr($url, 4);
                }
                break;
            case 'mailto':
                if (strpos($url, 'mailto:') === 0) {
                    $url = substr($url, 7);
                }
                $url = sanitize_email($url);
                break;
            case 'whatsapp':
                if (strpos($url, 'whatsapp://') === 0) {
                    $url = substr($url, 11);
                }
                break;
            default:
                $url = esc_url_raw($url);
                break;
        }
        
        // Verifică dacă este editare sau adăugare
        if ( ! empty( $id ) ) {
            $existing_button = $this->get( $id );
            
            if ( $existing_button ) {
                $data = [
                    'id'       => $id,
                    'name'     => $existing_button['name'],
                    'label'    => $label,
                    'url'      => $url,
                    'icon'     => $icon,
                    'type'     => $type,
                    'order'    => $order,
                    'floating' => $floating,
                ];
                
                $this->update( $id, $data );
                $message = 'Button updated successfully!';
            } else {
                $message = 'Button not found!';
            }
        } else {
            $new_id = uniqid('btn_');
            $name = AjaxHandler::generate_slug_with_id( $label, $new_id );
            
            $data = [
                'id'       => $new_id,
                'name'     => $name,
                'label'    => $label,
                'url'      => $url,
                'icon'     => $icon,
                'type'     => $type,
                'order'    => $order,
                'floating' => $floating,
            ];
            
            $this->create_with_id( $data );
            $message = 'Button created successfully!';
        }
        
        wp_safe_redirect( admin_url( 'admin.php?page=scfs-social-buttons&message=' . urlencode( $message ) ) );
        exit;
    }
    
    public function handle_single_actions() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'scfs-social-buttons') {
            return;
        }

        if (!isset($_GET['_wpnonce'])) {
            return;
        }

        $action = isset($_GET['action'])
            ? sanitize_text_field(wp_unslash($_GET['action']))
            : '';

        $id = isset($_GET['id'])
            ? sanitize_text_field(wp_unslash($_GET['id']))
            : '';

        if (empty($action) || empty($id)) {
            return;
        }
        
        $nonce_action = '';
        $redirect_url = admin_url('admin.php?page=scfs-social-buttons');
        
        switch ($action) {
            case 'trash_single':
                $nonce_action = 'trash_button_' . $id;
                if (wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'] ?? '')), $nonce_action)) {
                    $this->trash($id);
                    $redirect_url .= '&message=' . urlencode('Button moved to trash!');
                }
                break;
            case 'restore_single':
                $nonce_action = 'restore_button_' . $id;
                if (wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'] ?? '')), $nonce_action)) {
                    $this->restore($id);
                    $redirect_url .= '&message=' . urlencode('Button restored!');
                }
                break;
            case 'delete_single':
                $nonce_action = 'delete_button_' . $id;
                if (wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'] ?? '')), $nonce_action)) {
                    $this->delete($id);
                    $redirect_url .= '&message=' . urlencode('Button permanently deleted!');
                }
                break;
        }
        
        if ($nonce_action) {
            wp_safe_redirect($redirect_url);
            exit;
        }
    }

    public function admin_page() {
        $action = isset( $_GET['action'] )
            ? sanitize_key( wp_unslash( $_GET['action'] ) )
            : '';

        // NU folosi absint() - ID-urile sunt string-uri (ex: btn_123)
        $id = isset( $_GET['id'] )
            ? sanitize_text_field( wp_unslash( $_GET['id'] ) )
            : '';

        $message = isset( $_GET['message'] )
            ? sanitize_text_field( wp_unslash( $_GET['message'] ) )
            : '';

        if ( 'trash' === $action && empty( $id ) ) {
            $this->trash_page();
            return;
        }

        echo '<div class="wrap scfs-admin">';

        echo '<nav class="scfs-breadcrumb">';
        echo '<a href="' . esc_url( admin_url( 'admin.php?page=scfs-oop' ) ) . '">';
        echo esc_html__( 'Dashboard', 'custom-fields-shortcodes-main' );
        echo '</a> &raquo; ';
        echo '<span>' . esc_html__( 'Social Buttons', 'custom-fields-shortcodes-main' ) . '</span>';
        echo '</nav>';

        if ( ! empty( $message ) ) {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p>' . esc_html( urldecode( $message ) ) . '</p>';
            echo '</div>';
        }

        switch ( $action ) {
            case 'add':
            case 'edit':
                $this->edit_form( $id );
                break;
            case 'trash':
                $this->trash_page();
                break;
            default:
                $this->list_page();
                break;
        }

        echo '</div>';
    }
    
    private function list_page() {
        if (!class_exists('SCFS\\SocialButtonsTable')) {
            require_once plugin_dir_path(__FILE__) . 'SocialButtonsTable.php';
        }
        
        $table = new SocialButtonsTable(false);
        $table->prepare_items();
        
        ?>
        <h1 class="wp-heading-inline">Social Buttons</h1>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=scfs-social-buttons&action=add' ) ); ?>" class="page-title-action">
            <?php esc_html_e( 'Add New', 'custom-fields-shortcodes-main' ); ?>
        </a>

        <a href="<?php echo esc_url( admin_url( 'admin.php?page=scfs-social-buttons&action=trash' ) ); ?>" class="page-title-action">
            <?php
            printf(
                esc_html__( 'Trash (%d)', 'custom-fields-shortcodes-main' ),
                absint( $this->get_trash_count() )
            );
            ?>
        </a>

        <form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=scfs-social-buttons' ) ); ?>">
            <?php $table->display(); ?>
        </form>
        <?php
    }
    
    private function edit_form($id) {
        $button = !empty($id) ? $this->get($id) : null;
        $is_edit = (bool) $button;
        
        $default_order = 0;
        if (!$is_edit) {
            $buttons = $this->get_all();
            foreach ($buttons as $btn) {
                if (isset($btn['order']) && $btn['order'] > $default_order) {
                    $default_order = $btn['order'];
                }
            }
            $default_order++;
        }
        
        ?>
        <h1><?php echo $is_edit ? 'Edit Button' : 'Add New Button'; ?></h1>
        
        <form method="post" class="scfs-form">
            <?php wp_nonce_field('scfs_save_button'); ?>
            <input type="hidden" name="scfs_action" value="save">
            <?php if ($is_edit): ?>
                <input type="hidden" name="id" value="<?php echo esc_attr($id); ?>">
            <?php endif; ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="label">Label</label></th>
                    <td>
                        <input type="text" name="label" id="label" 
                               value="<?php echo esc_attr($button['label'] ?? ''); ?>"
                               class="regular-text" required>
                        <p class="description">Display name for the button</p>
                    </td>
                </tr>
                
                <?php if ($is_edit && isset($button['name'])): ?>
                <tr>
                    <th scope="row"><label>Name (Slug)</label></th>
                    <td>
                        <div style="padding: 8px 10px; background-color: #f5f5f5; border: 1px solid #ddd; border-radius: 4px; font-family: monospace;">
                            <?php echo esc_html($button['name']); ?>
                        </div>
                        <p class="description">
                            <strong>Auto-generated from Label with ID suffix. Cannot be changed.</strong><br>
                            Use in shortcode: <code>[scfs_social name="<?php echo esc_attr($button['name']); ?>"]</code>
                        </p>
                    </td>
                </tr>
                <?php endif; ?>
                
                <tr>
                    <th scope="row"><label for="type">Button Type</label></th>
                    <td>
                        <select name="type" id="type" class="regular-text">
                            <option value="url" <?php selected($button['type'] ?? '', 'url'); ?>>URL</option>
                            <option value="tel" <?php selected($button['type'] ?? '', 'tel'); ?>>Phone</option>
                            <option value="mailto" <?php selected($button['type'] ?? '', 'mailto'); ?>>Email</option>
                            <option value="whatsapp" <?php selected($button['type'] ?? '', 'whatsapp'); ?>>WhatsApp</option>
                            <option value="facebook" <?php selected($button['type'] ?? '', 'facebook'); ?>>Facebook</option>
                            <option value="instagram" <?php selected($button['type'] ?? '', 'instagram'); ?>>Instagram</option>
                            <option value="twitter" <?php selected($button['type'] ?? '', 'twitter'); ?>>Twitter</option>
                            <option value="linkedin" <?php selected($button['type'] ?? '', 'linkedin'); ?>>LinkedIn</option>
                            <option value="youtube" <?php selected($button['type'] ?? '', 'youtube'); ?>>YouTube</option>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="url">URL / Value</label></th>
                    <td>
                        <input type="text" name="url" id="url" 
                               value="<?php echo esc_attr($button['url'] ?? ''); ?>"
                               class="regular-text" required>
                        <p class="description">
                            <span id="url-description">
                                <?php if (isset($button['type'])): ?>
                                    <?php 
                                    switch($button['type']) {
                                        case 'tel': echo 'Phone number (e.g., +1234567890)'; break;
                                        case 'mailto': echo 'Email address (e.g., example@email.com)'; break;
                                        case 'whatsapp': echo 'WhatsApp number (e.g., 1234567890)'; break;
                                        default: echo 'Full URL (e.g., https://example.com)'; break;
                                    }
                                    ?>
                                <?php else: ?>
                                    Enter URL or value based on selected type
                                <?php endif; ?>
                            </span>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="icon">Icon Class</label></th>
                    <td>
                        <input type="text" name="icon" id="icon" 
                               value="<?php echo esc_attr($button['icon'] ?? 'fas fa-link'); ?>"
                               class="regular-text" required>
                        <p class="description">e.g., fab fa-facebook, fas fa-phone, fab fa-whatsapp</p>
                        <div id="icon-preview" style="margin-top: 5px;"></div>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="order">Display Order</label></th>
                    <td>
                        <input type="number" name="order" id="order" 
                               value="<?php echo esc_attr($button['order'] ?? $default_order); ?>"
                               min="0" step="1" class="small-text">
                        <p class="description">Lower numbers appear first</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="floating">Floating Menu</label></th>
                    <td>
                        <label>
                            <input type="checkbox" name="floating" id="floating" value="1" 
                                   <?php checked($button['floating'] ?? 1, 1); ?>>
                            Show in floating menu
                        </label>
                        <p class="description">If unchecked, button will only be available via shortcodes</p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button($is_edit ? 'Update Button' : 'Add Button'); ?>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=scfs-social-buttons' ) ); ?>" class="button button-secondary">
                Cancel
            </a>
        </form>
        
        <script>
        jQuery(document).ready(function($) {
            function updateIconPreview() {
                var iconClass = $('#icon').val();
                if (iconClass) {
                    $('#icon-preview').html('<i class="' + iconClass + '" style="font-size: 24px; color: #0073aa;"></i>');
                }
            }
            
            $('#icon').on('input', updateIconPreview);
            updateIconPreview();
            
            $('#type').change(function() {
                var type = $(this).val();
                var description = $('#url-description');
                var iconMap = {
                    'url': 'fas fa-link',
                    'tel': 'fas fa-phone',
                    'mailto': 'fas fa-envelope',
                    'whatsapp': 'fab fa-whatsapp',
                    'facebook': 'fab fa-facebook-f',
                    'instagram': 'fab fa-instagram',
                    'twitter': 'fab fa-twitter',
                    'linkedin': 'fab fa-linkedin-in',
                    'youtube': 'fab fa-youtube'
                };
                
                switch(type) {
                    case 'tel':
                        description.text('Phone number (e.g., +1234567890)');
                        break;
                    case 'mailto':
                        description.text('Email address (e.g., example@email.com)');
                        break;
                    case 'whatsapp':
                        description.text('WhatsApp number (e.g., 1234567890)');
                        break;
                    default:
                        description.text('Full URL (e.g., https://example.com)');
                        break;
                }
                
                var currentIcon = $('#icon').val();
                var isDefaultIcon = currentIcon === 'fas fa-link' || 
                                   currentIcon === 'fas fa-phone' || 
                                   currentIcon === 'fas fa-envelope' ||
                                   currentIcon === 'fab fa-whatsapp';
                
                if (iconMap[type] && (isDefaultIcon || !currentIcon)) {
                    $('#icon').val(iconMap[type]);
                    updateIconPreview();
                }
            }).trigger('change');
            
            $('#url').blur(function() {
                var type = $('#type').val();
                var url = $(this).val().trim();
                
                if (!url) return;
                
                switch(type) {
                    case 'tel':
                        url = url.replace(/[^0-9+]/g, '');
                        if (url && url.charAt(0) !== '+') {
                            url = '+' + url;
                        }
                        break;
                    case 'mailto':
                        if (url.toLowerCase().startsWith('mailto:')) {
                            url = url.substring(7);
                        }
                        break;
                    case 'whatsapp':
                        if (url.toLowerCase().startsWith('whatsapp://')) {
                            url = url.substring(11);
                        }
                        break;
                    default:
                        if (url && !url.match(/^https?:\/\//) && !url.match(/^\/\//) && !url.match(/^#/) && !url.match(/^mailto:/) && !url.match(/^tel:/)) {
                            url = 'https://' + url;
                        }
                        break;
                }
                
                $(this).val(url);
            });
        });
        </script>
        <?php
    }
    
    private function trash_page() {
        if (!class_exists('SCFS\\SocialButtonsTable')) {
            require_once plugin_dir_path(__FILE__) . 'SocialButtonsTable.php';
        }
        
        $table = new SocialButtonsTable(true);
        $table->prepare_items();
        
        ?>
        <h1>Trash</h1>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=scfs-social-buttons' ) ); ?>" class="button">
            <?php esc_html_e( 'Back to Buttons', 'custom-fields-shortcodes-main' ); ?>
        </a>
        
        <form method="post">
            <?php $table->display(); ?>
        </form>
        <?php
    }
    
    // CRUD Methods
    public function get_all($include_trash = false) {
        if ($this->use_database) {
            $items = AjaxHandler::get_all_items_from_database('social_button', $include_trash);
            if (empty($items)) {
                return $this->get_from_backup($include_trash);
            }
            return $this->format_database_items($items);
        } else {
            return $this->get_from_wp_options($include_trash);
        }
    }
    
    private function get_from_wp_options($include_trash = false) {
        $buttons_data = get_option($this->option_name, []);
        if (empty($buttons_data)) {
            $buttons_data = get_option($this->backup_name, []);
        }
        
        $formatted_data = $this->format_legacy_data($buttons_data);
        
        if (!$include_trash) {
            $formatted_data = array_filter($formatted_data, function($btn) {
                return empty($btn['trashed']);
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
            $formatted_data = array_filter($formatted_data, function($btn) {
                return empty($btn['trashed']);
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
                'id'        => $item['id'] ?? uniqid('btn_'),
                'name'      => $item['name'] ?? '',
                'label'     => $item['label'] ?? '',
                'url'       => $item['url'] ?? '',
                'icon'      => $item['icon'] ?? 'fas fa-link',
                'type'      => $item['type'] ?? 'url',
                'order'     => $item['order'] ?? (count($formatted) + 1),
                'floating'  => $item['floating'] ?? 1,
                'created'   => $item['created'] ?? current_time('mysql'),
                'trashed'   => $item['trashed'] ?? false,
                'icon_type' => 'class',
                'is_legacy' => false
            ];
            
            $formatted[] = $defaults;
        }
        
        usort($formatted, function($a, $b) {
            return ($a['order'] ?? 9999) <=> ($b['order'] ?? 9999);
        });
        
        return $formatted;
    }
    
    private function format_legacy_data($data) {
        $formatted = [];
        
        if (isset($data[0]) && is_array($data[0]) && isset($data[0]['name'])) {
            foreach ($data as $item) {
                if (!is_array($item)) continue;
                
                $defaults = [
                    'id'        => $item['id'] ?? uniqid('btn_'),
                    'created'   => current_time('mysql'),
                    'trashed'   => false,
                    'icon_type' => 'class',
                    'is_legacy' => true
                ];
                
                $formatted[] = array_merge($defaults, $item);
            }
            
            return $formatted;
        }
        
        foreach ($data as $button) {
            if (!is_array($button)) {
                continue;
            }
            
            $label = $button['name'] ?? 'Unnamed Button';
            $old_id = $button['id'] ?? uniqid('legacy_');
            $name = AjaxHandler::generate_slug_with_id($label, $old_id);
            
            $formatted[] = [
                'id'        => $old_id,
                'name'      => $name,
                'label'     => $label,
                'url'       => $button['url'] ?? '',
                'icon'      => $button['icon'] ?? 'fas fa-link',
                'type'      => $button['type'] ?? 'url',
                'order'     => $button['order'] ?? 0,
                'floating'  => $button['show_in_floating'] ?? 1,
                'created'   => current_time('mysql'),
                'trashed'   => false,
                'icon_type' => $button['icon_type'] ?? 'class',
                'is_legacy' => true
            ];
        }
        
        return $formatted;
    }
    
    public function get($id) {
        if (empty($id)) {
            return null;
        }
        
        if ($this->use_database) {
            $item = AjaxHandler::get_item_from_database($id, 'social_button');
            if ($item) {
                return $this->format_single_item($item);
            }
        }
        
        $buttons = $this->get_all(true);
        
        foreach ($buttons as $button) {
            if (isset($button['id']) && (string) $button['id'] === (string) $id) {
                return $button;
            }
        }
        
        return null;
    }
    
    private function format_single_item($item) {
        if (!is_array($item)) {
            return null;
        }
        
        $defaults = [
            'name'      => '',
            'label'     => '',
            'url'       => '',
            'icon'      => 'fas fa-link',
            'type'      => 'url',
            'order'     => 0,
            'floating'  => 1,
            'created'   => current_time('mysql'),
            'trashed'   => false,
            'icon_type' => 'class',
            'is_legacy' => false
        ];
        
        return array_merge($defaults, $item);
    }
    
    public function create($data) {
        $id = uniqid('btn_');
        
        if (!isset($data['name']) || empty($data['name'])) {
            $data['name'] = AjaxHandler::generate_slug_with_id($data['label'] ?? '', $id);
        }
        
        $data['id'] = $id;
        $data['created'] = current_time('mysql');
        $data['trashed'] = false;
        $data['is_legacy'] = false;
        $data['icon_type'] = 'class';
        
        if (!isset($data['order']) || empty($data['order'])) {
            $all_buttons = $this->get_all();
            $data['order'] = count($all_buttons) + 1;
        }
        
        if ($this->use_database) {
            $success = AjaxHandler::save_item_to_database_static(
                $id,
                'social_button',
                $data,
                $data['order'] ?? 0,
                null
            );
            return $success ? $id : false;
        } else {
            $buttons = $this->get_all(true);
            $buttons[] = $data;
            update_option($this->option_name, $buttons);
            return $id;
        }
    }
    
    public function create_with_id($data) {
        if (!isset($data['id']) || empty($data['id'])) {
            $data['id'] = uniqid('btn_');
        }
        
        if (!isset($data['name']) || empty($data['name'])) {
            $data['name'] = AjaxHandler::generate_slug_with_id($data['label'] ?? '', $data['id']);
        }
        
        $data['created'] = current_time('mysql');
        $data['trashed'] = false;
        $data['is_legacy'] = false;
        $data['icon_type'] = 'class';
        
        if (!isset($data['order']) || empty($data['order'])) {
            $all_buttons = $this->get_all();
            $data['order'] = count($all_buttons) + 1;
        }
        
        if ($this->use_database) {
            $success = AjaxHandler::save_item_to_database_static(
                $data['id'],
                'social_button',
                $data,
                $data['order'] ?? 0,
                null
            );
            return $success ? $data['id'] : false;
        } else {
            $buttons = $this->get_all(true);
            $buttons[] = $data;
            update_option($this->option_name, $buttons);
            return $data['id'];
        }
    }
    
    public function update($id, $data) {
        if (empty($id)) {
            return false;
        }
        
        if ($this->use_database) {
            $existing_item = $this->get($id);
            if (!$existing_item) {
                return false;
            }
            
            $updated_data = array_merge($existing_item, $data);
            
            return AjaxHandler::save_item_to_database_static(
                $id,
                'social_button',
                $updated_data,
                $updated_data['order'] ?? 0,
                isset($updated_data['trashed']) && !empty($updated_data['trashed']) ? $updated_data['trashed'] : null
            );
        } else {
            $buttons = $this->get_all(true);
            $updated = false;
            
            foreach ($buttons as &$button) {
                if (isset($button['id']) && (string) $button['id'] === (string) $id) {
                    if (isset($button['name']) && isset($data['name']) && $button['name'] !== $data['name']) {
                        unset($data['name']);
                    }
                    
                    $button = array_merge($button, $data);
                    $updated = true;
                    break;
                }
            }
            
            if ($updated) {
                update_option($this->option_name, $buttons);
            }
            
            return $updated;
        }
    }
    
    public function trash($id) {
        if (empty($id)) {
            return false;
        }
        
        if ($this->use_database) {
            return AjaxHandler::trash_item_in_database($id, 'social_button');
        } else {
            $buttons = $this->get_all(true);
            $trashed = false;
            
            foreach ($buttons as &$button) {
                if (isset($button['id']) && (string) $button['id'] === (string) $id) {
                    $button['trashed'] = current_time('mysql');
                    $trashed = true;
                    break;
                }
            }
            
            if ($trashed) {
                update_option($this->option_name, $buttons);
            }
            
            return $trashed;
        }
    }
    
    public function restore($id) {
        if (empty($id)) {
            return false;
        }
        
        if ($this->use_database) {
            return AjaxHandler::restore_item_in_database($id, 'social_button');
        } else {
            $buttons = $this->get_all(true);
            $restored = false;
            
            foreach ($buttons as &$button) {
                if (isset($button['id']) && (string) $button['id'] === (string) $id) {
                    unset($button['trashed']);
                    $restored = true;
                    break;
                }
            }
            
            if ($restored) {
                update_option($this->option_name, $buttons);
            }
            
            return $restored;
        }
    }
    
    public function delete($id) {
        if (empty($id)) {
            return false;
        }
        
        if ($this->use_database) {
            return AjaxHandler::delete_item_from_database($id, 'social_button');
        } else {
            $buttons = $this->get_all(true);
            $new_buttons = [];
            $deleted = false;
            
            foreach ($buttons as $button) {
                if (!isset($button['id']) || (string) $button['id'] !== (string) $id) {
                    $new_buttons[] = $button;
                } else {
                    if (isset($button['is_legacy']) && $button['is_legacy']) {
                        $button['trashed'] = current_time('mysql');
                        $new_buttons[] = $button;
                    }
                    $deleted = true;
                }
            }
            
            if ($deleted) {
                update_option($this->option_name, $new_buttons);
            }
            
            return $deleted;
        }
    }
    
    public function get_trash_count() {
        $buttons = $this->get_all(true);
        $count = 0;
        
        foreach ($buttons as $button) {
            if (!empty($button['trashed'])) $count++;
        }
        
        return $count;
    }
    
    public function shortcode($atts) {
        $atts = shortcode_atts([
            'name' => '',
            'type' => 'inline'
        ], $atts);
        
        $frontend = Frontend::get_instance();
        $social_settings = SocialSettings::get_instance();
        $settings = $social_settings->get_settings();
        
        $buttons = $this->get_all();
        
        if ($atts['type'] === 'floating') {
            return $frontend->render_floating($buttons, $settings);
        }
        
        if ($atts['name']) {
            foreach ($buttons as $button) {
                if (isset($button['name']) && $button['name'] === $atts['name']) {
                    return $frontend->render_single_button($button, $settings);
                }
            }
        }
        
        return $frontend->render_inline($buttons, $settings);
    }
}
