<?php
if (!defined('ABSPATH')) exit;

// =========================
// ADMIN PAGE
// =========================
function sfb_admin_page(){
    $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'buttons';
    ?>
    <div class="wrap">
        <h1>Social Floating Buttons</h1>
        
        <h2 class="nav-tab-wrapper">
            <a href="<?php echo admin_url('admin.php?page=social-floating-buttons'); ?>" class="nav-tab <?php echo $current_tab === 'buttons' ? 'nav-tab-active' : ''; ?>">
                📋 Buttons
            </a>
            <a href="<?php echo admin_url('admin.php?page=social-floating-buttons&tab=cdn'); ?>" class="nav-tab <?php echo $current_tab === 'cdn' ? 'nav-tab-active' : ''; ?>">
                🌐 CDN Libraries
            </a>
            <a href="<?php echo admin_url('admin.php?page=social-floating-buttons&tab=trash'); ?>" class="nav-tab <?php echo $current_tab === 'trash' ? 'nav-tab-active' : ''; ?>">
                🗑️ Trash (<?php echo sfb_get_trash_count(); ?>)
            </a>
            <a href="<?php echo admin_url('admin.php?page=social-floating-buttons&tab=settings'); ?>" class="nav-tab <?php echo $current_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
                ⚙️ Settings
            </a>
        </h2>

        <?php
        if($current_tab === 'cdn'){
            sfb_cdn_management_page();
        } elseif($current_tab === 'trash'){
            sfb_trash_management_page();
        } elseif($current_tab === 'settings'){
            sfb_settings_page();
        } elseif(isset($_GET['action']) && in_array($_GET['action'], ['add','edit'])){
            sfb_add_edit_form();
        } else {
            $table = new SFB_List_Table();
            $table->prepare_items();
            echo '<form method="post" id="sfb-buttons-form">';
            $table->display();
            echo '</form>';
        }
        ?>
    </div>
    <?php
}

// =========================
// SETTINGS PAGE - VARIANTĂ SIMPLIFICATĂ
// =========================
function sfb_settings_page(){
    if(isset($_POST['sfb_save_settings'])){
        $settings = [
            'position' => sanitize_text_field($_POST['sfb_position']),
            'button_color' => sanitize_text_field($_POST['sfb_button_color']),
            'button_icon' => sanitize_text_field($_POST['sfb_button_icon']),
            'animation' => sanitize_text_field($_POST['sfb_animation']),
            'mobile_enabled' => isset($_POST['sfb_mobile_enabled']) ? 1 : 0,
            'show_names' => isset($_POST['sfb_show_names']) ? 1 : 0,
            'transparent_icons' => isset($_POST['sfb_transparent_icons']) ? 1 : 0 // NOU - doar pentru iconițe
        ];
        update_option('sfb_settings', $settings);
        echo '<div class="updated"><p>Settings saved successfully!</p></div>';
    }

    $settings = get_option('sfb_settings', [
        'position' => 'right',
        'button_color' => '#0073aa',
        'button_icon' => '☰',
        'animation' => 'slide',
        'mobile_enabled' => 1,
        'show_names' => 1,
        'transparent_icons' => 0 // NOU
    ]);
    ?>
    
    <div class="sfb-settings-page">
        <form method="post">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="sfb_position">Button Position</label></th>
                    <td>
                        <select name="sfb_position" id="sfb_position">
                            <option value="right" <?php selected($settings['position'], 'right'); ?>>Bottom Right</option>
                            <option value="left" <?php selected($settings['position'], 'left'); ?>>Bottom Left</option>
                            <option value="top-right" <?php selected($settings['position'], 'top-right'); ?>>Top Right</option>
                            <option value="top-left" <?php selected($settings['position'], 'top-left'); ?>>Top Left</option>
                        </select>
                        <p class="description">Select where the floating button should appear</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="sfb_button_color">Main Button Color</label></th>
                    <td>
                        <input type="color" name="sfb_button_color" id="sfb_button_color" value="<?php echo esc_attr($settings['button_color']); ?>">
                        <p class="description">Choose the main floating button color</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="sfb_button_icon">Button Icon</label></th>
                    <td>
                        <input type="text" name="sfb_button_icon" id="sfb_button_icon" value="<?php echo esc_attr($settings['button_icon']); ?>" class="regular-text">
                        <p class="description">Enter icon character or HTML code (e.g., ☰, ⚙️, <i class="fas fa-bars"></i>)</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="sfb_animation">Animation Type</label></th>
                    <td>
                        <select name="sfb_animation" id="sfb_animation">
                            <option value="slide" <?php selected($settings['animation'], 'slide'); ?>>Slide</option>
                            <option value="fade" <?php selected($settings['animation'], 'fade'); ?>>Fade</option>
                            <option value="scale" <?php selected($settings['animation'], 'scale'); ?>>Scale</option>
                        </select>
                        <p class="description">Choose how buttons appear when opened</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="sfb_show_names">Display Style</label></th>
                    <td>
                        <label>
                            <input type="checkbox" name="sfb_show_names" id="sfb_show_names" value="1" <?php checked($settings['show_names'], 1); ?>>
                            Show button names next to icons
                        </label>
                        <p class="description">If unchecked, only icons will be displayed</p>
                    </td>
                </tr>
                
                <tr id="transparent_icons_row" style="<?php echo $settings['show_names'] ? 'display: none;' : ''; ?>">
                    <th scope="row"><label for="sfb_transparent_icons">Transparent Icons</label></th>
                    <td>
                        <label>
                            <input type="checkbox" name="sfb_transparent_icons" id="sfb_transparent_icons" value="1" <?php checked($settings['transparent_icons'], 1); ?>>
                            Show icons without background and border
                        </label>
                        <p class="description">Only available when "Show button names" is disabled</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="sfb_mobile_enabled">Mobile Display</label></th>
                    <td>
                        <label>
                            <input type="checkbox" name="sfb_mobile_enabled" id="sfb_mobile_enabled" value="1" <?php checked($settings['mobile_enabled'], 1); ?>>
                            Enable on mobile devices
                        </label>
                        <p class="description">Show the floating button on mobile devices</p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button('Save Settings', 'primary', 'sfb_save_settings'); ?>
        </form>
        
        <div class="sfb-preview">
            <h3>🎨 Preview</h3>
            
            <!-- Main Button Preview -->
            <div class="sfb-preview-main" style="background-color: <?php echo esc_attr($settings['button_color']); ?>;">
                <span class="sfb-preview-icon"><?php echo $settings['button_icon']; ?></span>
            </div>
            
            <!-- Buttons List Preview -->
            <div class="sfb-preview-items">
                <?php if($settings['show_names']): ?>
                    <!-- Preview cu nume -->
                    <div class="sfb-preview-item with-names">
                        <span class="sfb-preview-icon">🔗</span>
                        <span class="sfb-preview-text">Example Button</span>
                    </div>
                    <div class="sfb-preview-item with-names">
                        <span class="sfb-preview-icon">📱</span>
                        <span class="sfb-preview-text">Mobile Only</span>
                    </div>
                <?php else: ?>
                    <!-- Preview doar cu iconițe -->
                    <div class="sfb-preview-item icons-only <?php echo $settings['transparent_icons'] ? 'transparent' : ''; ?>">
                        <span class="sfb-preview-icon">🔗</span>
                    </div>
                    <div class="sfb-preview-item icons-only <?php echo $settings['transparent_icons'] ? 'transparent' : ''; ?>">
                        <span class="sfb-preview-icon">📱</span>
                    </div>
                <?php endif; ?>
            </div>
            
            <p class="description">
                <?php if($settings['show_names']): ?>
                    ✅ Showing icons with names
                <?php else: ?>
                    <?php if($settings['transparent_icons']): ?>
                        🔓 Showing transparent icons only
                    <?php else: ?>
                        🔒 Showing icons only with background
                    <?php endif; ?>
                <?php endif; ?>
            </p>
        </div>
        
        <style>
        .sfb-preview {
            margin-top: 30px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 5px;
            max-width: 300px;
        }
        .sfb-preview-main {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            color: white;
            font-size: 18px;
            margin: 0 auto 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        .sfb-preview-items {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .sfb-preview-item {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            background: #ffffff;
        }
        .sfb-preview-item.icons-only {
            justify-content: center;
            min-width: 50px;
            padding: 10px;
        }
        .sfb-preview-item.icons-only.transparent {
            background: transparent !important;
            box-shadow: none;
            border: none;
        }
        .sfb-preview-icon {
            margin-right: 10px;
            font-size: 18px;
        }
        .sfb-preview-item.icons-only .sfb-preview-icon {
            margin-right: 0;
            font-size: 20px;
        }
        .sfb-preview-text {
            font-weight: 600;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Toggle transparent options based on show_names
            function toggleTransparentOptions() {
                var showNames = $('#sfb_show_names').is(':checked');
                
                if(showNames) {
                    $('#transparent_icons_row').hide();
                    $('#sfb_transparent_icons').prop('checked', false);
                } else {
                    $('#transparent_icons_row').show();
                }
            }
            
            $('#sfb_show_names').change(toggleTransparentOptions);
            toggleTransparentOptions(); // Inițializează la încărcare
        });
        </script>
    </div>
    <?php
}

// =========================
// CDN MANAGEMENT PAGE
// =========================
function sfb_cdn_management_page(){
    // Handle form submissions
    if(isset($_POST['sfb_add_cdn'])){
        $cdn_name = sanitize_text_field($_POST['cdn_name']);
        $cdn_type = sanitize_text_field($_POST['cdn_type']);
        $cdn_url = esc_url_raw($_POST['cdn_url']);
        
        if(!empty($cdn_name) && !empty($cdn_type) && !empty($cdn_url)){
            $cdns = get_option('sfb_custom_cdns', []);
            $cdn_id = sanitize_title($cdn_name) . '_' . uniqid();
            $cdns[$cdn_id] = [
                'name' => $cdn_name,
                'type' => $cdn_type, // CSS sau JS
                'url' => $cdn_url,
                'active' => true
            ];
            update_option('sfb_custom_cdns', $cdns);
            echo '<div class="updated"><p>CDN added successfully!</p></div>';
        }
    }
    
    // Handle predefined CDN updates
    if(isset($_POST['sfb_save_predefined_cdn'])){
        $cdn_id = sanitize_text_field($_POST['cdn_id']);
        $cdn_url = esc_url_raw($_POST['cdn_url']);
        
        if(wp_verify_nonce($_POST['_wpnonce'], 'save_cdn_' . $cdn_id)){
            $settings = get_option('sfb_icon_cdns', []);
            if(empty($cdn_url)){
                unset($settings[$cdn_id]);
            } else {
                $settings[$cdn_id] = $cdn_url;
            }
            update_option('sfb_icon_cdns', $settings);
            echo '<div class="updated"><p>CDN settings updated!</p></div>';
        }
    }
    
    // Handle CDN deletion
    if(isset($_GET['delete_cdn'])){
        $cdn_id = sanitize_text_field($_GET['delete_cdn']);
        if(wp_verify_nonce($_GET['_wpnonce'], 'delete_cdn_' . $cdn_id)){
            $cdns = get_option('sfb_custom_cdns', []);
            unset($cdns[$cdn_id]);
            update_option('sfb_custom_cdns', $cdns);
            echo '<div class="updated"><p>CDN deleted successfully!</p></div>';
        }
    }
    
    // Handle CDN toggle
    if(isset($_GET['toggle_cdn'])){
        $cdn_id = sanitize_text_field($_GET['toggle_cdn']);
        if(wp_verify_nonce($_GET['_wpnonce'], 'toggle_cdn_' . $cdn_id)){
            $cdns = get_option('sfb_custom_cdns', []);
            if(isset($cdns[$cdn_id])){
                $cdns[$cdn_id]['active'] = !$cdns[$cdn_id]['active'];
                update_option('sfb_custom_cdns', $cdns);
            }
        }
    }
    
    // Handle predefined CDN disable
    if(isset($_GET['disable_cdn'])){
        $cdn_id = sanitize_text_field($_GET['disable_cdn']);
        if(wp_verify_nonce($_GET['_wpnonce'], 'disable_cdn_' . $cdn_id)){
            $settings = get_option('sfb_icon_cdns', []);
            unset($settings[$cdn_id]);
            update_option('sfb_icon_cdns', $settings);
            echo '<div class="updated"><p>CDN disabled successfully!</p></div>';
        }
    }

    $predefined_cdns = sfb_get_predefined_cdns();
    $custom_cdns = get_option('sfb_custom_cdns', []);
    ?>
    
    <div class="sfb-cdn-management">
        <<div class="sfb-cdn-section">
            <h3>➕ Add New CDN</h3>
            <form method="post" class="sfb-cdn-form">
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="cdn_name">CDN Name</label></th>
                        <td><input type="text" id="cdn_name" name="cdn_name" required class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="cdn_type">CDN Type</label></th>
                        <td>
                            <select name="cdn_type" id="cdn_type" required>
                                <option value="css">CSS Stylesheet</option>
                                <option value="js">JavaScript</option>
                            </select>
                            <p class="description">Select the type of resource</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="cdn_url">CDN URL</label></th>
                        <td>
                            <input type="url" id="cdn_url" name="cdn_url" required class="regular-text" placeholder="https://cdn.example.com/library.css">
                            <p class="description">Enter the full URL to the resource file</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Add CDN', 'primary', 'sfb_add_cdn'); ?>
            </form>
        </div>

        <div class="sfb-cdn-section">
            <h3>📚 Predefined CDN Libraries</h3>
            <div class="sfb-cdn-flex">
                <?php 
                $settings = get_option('sfb_icon_cdns', []);
                foreach($predefined_cdns as $cdn_id => $cdn): 
                    $is_active = !empty($settings[$cdn_id]);
                    $current_url = $settings[$cdn_id] ?? '';
                ?>
                    <div class="sfb-cdn-card <?php echo $is_active ? 'active' : 'inactive'; ?>">
                        <div class="sfb-cdn-header">
                            <h4><?php echo esc_html($cdn['name']); ?></h4>
                            <span class="sfb-cdn-type"><?php echo strtoupper($cdn['type']); ?></span>
                            <span class="sfb-cdn-status"><?php echo $is_active ? '✅ Active' : '❌ Inactive'; ?></span>
                        </div>
                        <div class="sfb-cdn-body">
                            <form method="post">
                                <?php wp_nonce_field('save_cdn_' . $cdn_id); ?>
                                <input type="hidden" name="cdn_id" value="<?php echo esc_attr($cdn_id); ?>">
                                <div class="sfb-cdn-url">
                                    <label>CDN URL: <a href="<?php echo esc_url($cdn['docs']); ?>" target="_blank" class="sfb-docs-link">📚 Documentation</a> </label>
                                    <input type="url" name="cdn_url" value="<?php echo esc_url($current_url); ?>" 
                                           placeholder="<?php echo esc_attr($cdn['default_url']); ?>" class="regular-text">
                                </div>
                                <div class="sfb-cdn-actions">
                                    <button type="submit" name="sfb_save_predefined_cdn" class="button button-primary">
                                        <?php echo $is_active ? 'Update' : 'Activate'; ?>
                                    </button>
                                    <?php if($is_active): ?>
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=social-floating-buttons&tab=cdn&disable_cdn=' . $cdn_id), 'disable_cdn_' . $cdn_id); ?>" class="button button-secondary">
                                            Disable
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="sfb-cdn-section">
            <h3>🔧 Custom CDN Libraries</h3>
            <?php if(empty($custom_cdns)): ?>
                <p>No custom CDNs added yet.</p>
            <?php else: ?>
                <div class="sfb-cdn-list">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th>URL</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($custom_cdns as $cdn_id => $cdn): ?>
                                <tr>
                                    <td><?php echo esc_html($cdn['name']); ?></td>
                                    <td>
                                        <span class="sfb-cdn-type-badge sfb-type-<?php echo esc_attr($cdn['type']); ?>">
                                            <?php echo strtoupper(esc_html($cdn['type'])); ?>
                                        </span>
                                    </td>
                                    <td><code><?php echo esc_html($cdn['url']); ?></code></td>
                                    <td>
                                        <span class="sfb-status-badge <?php echo $cdn['active'] ? 'active' : 'inactive'; ?>">
                                            <?php echo $cdn['active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=social-floating-buttons&tab=cdn&toggle_cdn=' . $cdn_id), 'toggle_cdn_' . $cdn_id); ?>" class="button button-small">
                                            <?php echo $cdn['active'] ? 'Deactivate' : 'Activate'; ?>
                                        </a>
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=social-floating-buttons&tab=cdn&delete_cdn=' . $cdn_id), 'delete_cdn_' . $cdn_id); ?>" class="button button-small button-danger" onclick="return confirm('Are you sure you want to delete this CDN?')">
                                            Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <style>
    .sfb-cdn-flex {
        display: flex;
        flex-direction: row;
        flex-wrap: wrap;
        gap: 20px;
        margin: 20px 0;
    }
    .sfb-cdn-card {
        border: 1px solid #ccd0d4;
        border-radius: 4px;
        padding: 15px;
        background: #fff;
    }
    .sfb-cdn-card.active {
        border-left: 4px solid #46b450;
    }
    .sfb-cdn-card.inactive {
        border-left: 4px solid #dc3232;
    }
    .sfb-cdn-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }
    .sfb-cdn-url {
        margin-bottom: 15px;
    }
    .sfb-cdn-url label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
    }
    .sfb-cdn-actions {
        display: flex;
        gap: 10px;
    }
    .sfb-status-badge {
        padding: 4px 8px;
        border-radius: 3px;
        font-size: 12px;
        font-weight: 600;
    }
    .sfb-status-badge.active {
        background: #46b450;
        color: white;
    }
    .sfb-status-badge.inactive {
        background: #dc3232;
        color: white;
    }
    .button-danger {
        background: #dc3232;
        border-color: #dc3232;
        color: white;
    }
    .button-danger:hover {
        background: #a00;
        border-color: #a00;
    }
    </style>
    <?php
}

// =========================
// PREDEFINED CDNs
// =========================
function sfb_get_predefined_cdns(){
    return [
        'fontawesome' => [
            'name' => 'Font Awesome (CSS CDN)',
            'default_url' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
            'docs' => 'https://fontawesome.com/icons',
            'type' => 'css' // Tipul resursei
        ],
        'fontawesome_kit' => [
            'name' => 'Font Awesome Kit (JS)',
            'default_url' => 'https://kit.fontawesome.com/0f950e4537.js',
            'docs' => 'https://fontawesome.com/kits',
            'type' => 'js' // Tipul resursei - SCRIPT!
        ],
        'feather' => [
            'name' => 'Feather Icons',
            'default_url' => 'https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.css',
            'docs' => 'https://feathericons.com/',
            'type' => 'css'
        ],
        'material' => [
            'name' => 'Material Icons',
            'default_url' => 'https://fonts.googleapis.com/icon?family=Material+Icons',
            'docs' => 'https://fonts.google.com/icons',
            'type' => 'css'
        ],
        'heroicons' => [
            'name' => 'Heroicons',
            'default_url' => 'https://cdn.jsdelivr.net/npm/heroicons@1.0.1/outline/style.css',
            'docs' => 'https://heroicons.com/',
            'type' => 'css'
        ],
        'simple' => [
            'name' => 'Simple Icons',
            'default_url' => 'https://cdn.jsdelivr.net/npm/simple-icons@v11/icons/%s.svg',
            'docs' => 'https://simpleicons.org/',
            'type' => 'css'
        ]
    ];
}

// =========================
// TRASH MANAGEMENT PAGE
// =========================
function sfb_trash_management_page(){
    $trash_items = get_option('sfb_trash', []);
    
    // Handle bulk actions
    if(isset($_POST['sfb_bulk_action']) && isset($_POST['trash_items'])){
        $action = sanitize_text_field($_POST['sfb_bulk_action']);
        $items = array_map('sanitize_text_field', $_POST['trash_items']);
        
        foreach($items as $item_id){
            if($action === 'restore'){
                sfb_restore_from_trash($item_id);
            } elseif($action === 'delete_permanently'){
                sfb_delete_permanently($item_id);
            }
        }
        
        echo '<div class="updated"><p>Bulk action completed successfully!</p></div>';
        $trash_items = get_option('sfb_trash', []); // Refresh data
    }
    
    // Handle individual actions
    if(isset($_GET['restore'])){
        $item_id = sanitize_text_field($_GET['restore']);
        if(wp_verify_nonce($_GET['_wpnonce'], 'restore_' . $item_id)){
            sfb_restore_from_trash($item_id);
            echo '<div class="updated"><p>Item restored successfully!</p></div>';
            $trash_items = get_option('sfb_trash', []);
        }
    }
    
    if(isset($_GET['delete'])){
        $item_id = sanitize_text_field($_GET['delete']);
        if(wp_verify_nonce($_GET['_wpnonce'], 'delete_' . $item_id)){
            sfb_delete_permanently($item_id);
            echo '<div class="updated"><p>Item permanently deleted!</p></div>';
            $trash_items = get_option('sfb_trash', []);
        }
    }
    ?>
    
    <div class="sfb-trash-management">
        <h3>🗑️ Trash Management</h3>
        
        <?php if(empty($trash_items)): ?>
            <div class="notice notice-info">
                <p>Trash is empty.</p>
            </div>
        <?php else: ?>
            <form method="post" id="sfb-trash-form">
                <div class="tablenav top">
                    <div class="alignleft actions bulkactions">
                        <select name="sfb_bulk_action" id="bulk-action-selector-top">
                            <option value="">Bulk Actions</option>
                            <option value="restore">Restore</option>
                            <option value="delete_permanently">Delete Permanently</option>
                        </select>
                        <input type="submit" class="button action" value="Apply">
                    </div>
                    <div class="tablenav-pages">
                        <span class="displaying-num"><?php echo count($trash_items); ?> items</span>
                    </div>
                </div>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <td class="manage-column column-cb check-column">
                                <input type="checkbox" id="cb-select-all-1">
                            </td>
                            <th>Name</th>
                            <th>Icon</th>
                            <th>Type</th>
                            <th>URL</th>
                            <th>Date Trashed</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($trash_items as $item): ?>
                            <tr>
                                <th scope="row" class="check-column">
                                    <input type="checkbox" name="trash_items[]" value="<?php echo esc_attr($item['id']); ?>">
                                </th>
                                <td><?php echo esc_html($item['name']); ?></td>
                                <td><span class="<?php echo esc_attr($item['icon']); ?>"></span></td>
                                <td><?php echo esc_html($item['type']); ?></td>
                                <td><?php echo esc_html($item['url']); ?></td>
                                <td><?php echo date('Y-m-d H:i', $item['trashed_time']); ?></td>
                                <td>
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=social-floating-buttons&tab=trash&restore=' . $item['id']), 'restore_' . $item['id']); ?>" class="button button-small">
                                        Restore
                                    </a>
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=social-floating-buttons&tab=trash&delete=' . $item['id']), 'delete_' . $item['id']); ?>" class="button button-small button-danger" onclick="return confirm('Are you sure you want to permanently delete this item?')">
                                        Delete Permanently
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </form>
        <?php endif; ?>
    </div>
    <?php
}

// =========================
// TRASH FUNCTIONS
// =========================
function sfb_get_trash_count(){
    $trash = get_option('sfb_trash', []);
    return count($trash);
}

function sfb_move_to_trash($button_id){
    $buttons = get_option('sfb_buttons', []);
    $trash = get_option('sfb_trash', []);
    
    foreach($buttons as $key => $button){
        if($button['id'] === $button_id){
            $button['trashed_time'] = time();
            $trash[] = $button;
            unset($buttons[$key]);
            break;
        }
    }
    
    update_option('sfb_buttons', array_values($buttons));
    update_option('sfb_trash', $trash);
}

function sfb_restore_from_trash($button_id){
    $buttons = get_option('sfb_buttons', []);
    $trash = get_option('sfb_trash', []);
    
    foreach($trash as $key => $item){
        if($item['id'] === $button_id){
            $buttons[] = $item;
            unset($trash[$key]);
            break;
        }
    }
    
    update_option('sfb_buttons', array_values($buttons));
    update_option('sfb_trash', array_values($trash));
}

function sfb_delete_permanently($button_id){
    $trash = get_option('sfb_trash', []);
    $trash = array_filter($trash, function($item) use ($button_id){
        return $item['id'] !== $button_id;
    });
    update_option('sfb_trash', array_values($trash));
}

// =========================
// UPDATED LIST TABLE CLASS
// =========================
if(!class_exists('WP_List_Table')){
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class SFB_List_Table extends WP_List_Table {
    function __construct(){
        parent::__construct([
            'singular' => 'button',
            'plural' => 'buttons',
            'ajax' => false
        ]);
    }

    function get_columns(){
        return [
            'cb' => '<input type="checkbox" />',
            'name' => 'Name',
            'icon' => 'Icon',
            'type' => 'Type',
            'url' => 'URL',
            'shortcode' => 'Shortcode',
            'actions' => 'Actions'
        ];
    }

    function prepare_items(){
        $this->_column_headers = [$this->get_columns(), [], []];
        $this->items = get_option('sfb_buttons', []);
        
        // Process bulk actions
        $this->process_bulk_action();
    }

    function column_cb($item){
        return sprintf('<input type="checkbox" name="id[]" value="%s" />', $item['id']);
    }

    function column_name($item){
        $edit_url = admin_url('admin.php?page=social-floating-buttons&action=edit&id='.$item['id']);
        $delete_url = wp_nonce_url(admin_url('admin.php?page=social-floating-buttons&action=trash&id='.$item['id']), 'sfb_trash_'.$item['id']);
        
        $actions = [
            'edit' => '<a href="'.$edit_url.'">Edit</a>',
            'trash' => '<a href="'.$delete_url.'" onclick="return confirm(\'Move to trash?\')">Trash</a>'
        ];
        
        return '<strong>' . esc_html($item['name']) . '</strong>' . $this->row_actions($actions);
    }

    function column_default($item, $column_name){
        switch($column_name){
            case 'icon': 
                return '<span class="' . esc_attr($item['icon']) . '"></span>';
            case 'type': 
                return esc_html(ucfirst($item['type']));
            case 'url': 
                return esc_html($item['url']);
            case 'shortcode': 
                return '<code>[sfb_button id="' . $item['id'] . '"]</code>';
            case 'actions':
                $edit_url = admin_url('admin.php?page=social-floating-buttons&action=edit&id='.$item['id']);
                $delete_url = wp_nonce_url(admin_url('admin.php?page=social-floating-buttons&action=trash&id='.$item['id']), 'sfb_trash_'.$item['id']);
                return '<a href="'.$edit_url.'" class="button">Edit</a> <a href="'.$delete_url.'" class="button button-danger" onclick="return confirm(\'Move to trash?\')">Trash</a>';
            default: 
                return '';
        }
    }

    function get_bulk_actions(){
        return [
            'trash' => 'Move to Trash',
            'delete_permanently' => 'Delete Permanently'
        ];
    }

    function process_bulk_action(){
        if(!isset($_POST['id']) || empty($_POST['id'])) return;
        
        $ids = is_array($_POST['id']) ? $_POST['id'] : [$_POST['id']];
        
        switch($this->current_action()){
            case 'trash':
                foreach($ids as $id){
                    sfb_move_to_trash($id);
                }
                echo '<div class="updated"><p>Items moved to trash!</p></div>';
                break;
            case 'delete_permanently':
                $buttons = get_option('sfb_buttons', []);
                $buttons = array_filter($buttons, function($button) use ($ids){
                    return !in_array($button['id'], $ids);
                });
                update_option('sfb_buttons', array_values($buttons));
                echo '<div class="updated"><p>Items permanently deleted!</p></div>';
                break;
        }
    }
}

// =========================
// HANDLE ACTIONS
// =========================
function sfb_handle_actions(){
    if(isset($_GET['action']) && $_GET['action'] === 'trash' && !empty($_GET['id'])){
        $id = sanitize_text_field($_GET['id']);
        check_admin_referer('sfb_trash_'.$id);
        sfb_move_to_trash($id);
        wp_redirect(admin_url('admin.php?page=social-floating-buttons&message=trashed'));
        exit;
    }
}
add_action('admin_init', 'sfb_handle_actions');

// =========================
// ENHANCED ADD/EDIT FORM
// =========================
function sfb_add_edit_form(){
    $buttons = get_option('sfb_buttons', []);
    $editing = false;
    $current = ['id'=>'', 'icon'=>'', 'name'=>'', 'type'=>'url', 'url'=>'', 'icon_type'=>'class'];

    if(isset($_GET['action']) && $_GET['action']==='edit' && !empty($_GET['id'])){
        foreach($buttons as $b){ 
            if($b['id']===$_GET['id']){ 
                $current = $b; 
                $editing = true; 
                // Detect icon type
                $current['icon_type'] = (strpos($current['icon'], '<') !== false) ? 'html' : 'class';
                break; 
            } 
        }
    }

    if(isset($_POST['sfb_save_button'])){
        $icon_type = sanitize_text_field($_POST['sfb_icon_type']);
        $icon_content = $icon_type === 'html' ? wp_kses_post($_POST['sfb_icon_html']) : sanitize_text_field($_POST['sfb_icon_class']);
        
        if($editing){
            foreach($buttons as &$b){ 
                if($b['id']===$current['id']){
                    $b['icon'] = $icon_content;
                    $b['name'] = sanitize_text_field($_POST['sfb_name']);
                    $b['type'] = sanitize_text_field($_POST['sfb_type']);
                    $b['url'] = sanitize_text_field($_POST['sfb_url']);
                    $b['icon_type'] = $icon_type;
                }
            }
        } else {
            $id = uniqid('sfb_');
            $buttons[] = [
                'id' => $id,
                'icon' => $icon_content,
                'name' => sanitize_text_field($_POST['sfb_name']),
                'type' => sanitize_text_field($_POST['sfb_type']),
                'url' => sanitize_text_field($_POST['sfb_url']),
                'icon_type' => $icon_type
            ];
        }
        update_option('sfb_buttons', $buttons);
        echo '<div class="updated"><p>Button saved!</p></div>';
        echo '<a href="'.admin_url('admin.php?page=social-floating-buttons').'" class="button button-primary">Back to list</a>';
        return;
    }
    ?>
    
    <h2><?php echo $editing ? 'Edit Social Floating Button' : 'Add Social Floating Button'; ?></h2>
    <form method="post">
        <table class="form-table">
            <tr>
                <th>Icon Type</th>
                <td>
                    <select name="sfb_icon_type" id="sfb_icon_type">
                        <option value="class" <?php selected($current['icon_type'], 'class'); ?>>CSS Class</option>
                        <option value="html" <?php selected($current['icon_type'], 'html'); ?>>HTML Code</option>
                    </select>
                </td>
            </tr>
            
            <tr id="icon_class_row" style="<?php echo $current['icon_type'] === 'html' ? 'display:none;' : ''; ?>">
                <th>Icon Class</th>
                <td>
                    <input type="text" name="sfb_icon_class" value="<?php echo $current['icon_type'] === 'class' ? esc_attr($current['icon']) : ''; ?>" class="regular-text" placeholder="fab fa-facebook-f">
                    <p class="description">Enter CSS class(es) for the icon</p>
                </td>
            </tr>
            
            <tr id="icon_html_row" style="<?php echo $current['icon_type'] === 'class' ? 'display:none;' : ''; ?>">
                <th>Icon HTML Code</th>
                <td>
                    <textarea name="sfb_icon_html" rows="3" cols="50" class="large-text code" placeholder='<i class="fab fa-facebook-f"></i> or <svg>...</svg>'><?php echo $current['icon_type'] === 'html' ? esc_textarea($current['icon']) : ''; ?></textarea>
                    <p class="description">Enter full HTML code for the icon (SVG, i tag, etc.)</p>
                </td>
            </tr>
            
            <tr>
                <th>Button Name</th>
                <td><input type="text" name="sfb_name" value="<?php echo esc_attr($current['name']); ?>" required class="regular-text"></td>
            </tr>
            
            <tr>
                <th>Action Type</th>
                <td>
                    <select name="sfb_type">
                        <option value="url" <?php selected($current['type'], 'url'); ?>>URL</option>
                        <option value="tel" <?php selected($current['type'], 'tel'); ?>>Phone</option>
                        <option value="mailto" <?php selected($current['type'], 'mailto'); ?>>Email</option>
                    </select>
                </td>
            </tr>
            
            <tr>
                <th>Action Value</th>
                <td><input type="text" name="sfb_url" value="<?php echo esc_attr($current['url']); ?>" required class="regular-text"></td>
            </tr>
        </table>
        
        <?php submit_button('Save Button', 'primary', 'sfb_save_button'); ?>
        <a href="<?php echo admin_url('admin.php?page=social-floating-buttons'); ?>" class="button button-secondary">Cancel</a>
    </form>
    
    <script>
    jQuery(document).ready(function($){
        $('#sfb_icon_type').change(function(){
            if($(this).val() === 'html'){
                $('#icon_class_row').hide();
                $('#icon_html_row').show();
            } else {
                $('#icon_class_row').show();
                $('#icon_html_row').hide();
            }
        });
    });
    </script>
    <?php
}

// =========================
// ENQUEUE CDN STYLES & SCRIPTS
// =========================
function sfb_enqueue_icon_cdns(){
    $settings = get_option('sfb_icon_cdns', []);
    $custom_cdns = get_option('sfb_custom_cdns', []);
    $predefined_cdns = sfb_get_predefined_cdns();
    
    // Enqueue predefined CDNs
    foreach($settings as $cdn_id => $cdn_url){
        if(!empty($cdn_url) && isset($predefined_cdns[$cdn_id])){
            $cdn_type = $predefined_cdns[$cdn_id]['type'];
            
            if($cdn_type === 'css'){
                wp_enqueue_style('sfb-' . $cdn_id, $cdn_url, [], null);
            } elseif($cdn_type === 'js'){
                wp_enqueue_script('sfb-' . $cdn_id, $cdn_url, [], null, true);
            }
        }
    }
    
    // Enqueue custom CDNs (cu suport pentru ambele tipuri)
    foreach($custom_cdns as $cdn){
        if($cdn['active'] && !empty($cdn['url'])){
            $handle = 'sfb-custom-' . sanitize_title($cdn['name']);
            
            if($cdn['type'] === 'css'){
                wp_enqueue_style($handle, $cdn['url'], [], null);
            } elseif($cdn['type'] === 'js'){
                wp_enqueue_script($handle, $cdn['url'], [], null, true);
            }
        }
    }
}
add_action('wp_enqueue_scripts', 'sfb_enqueue_icon_cdns');
add_action('admin_enqueue_scripts', 'sfb_enqueue_icon_cdns');

// =========================
// ENHANCED FRONTEND SHORTCODE - VARIANTĂ SIMPLIFICATĂ
// =========================
add_shortcode('sfb_floating', function($atts){
    $settings = get_option('sfb_settings', [
        'position' => 'right',
        'button_color' => '#0073aa',
        'button_icon' => '☰',
        'animation' => 'slide',
        'mobile_enabled' => 1,
        'show_names' => 1,
        'transparent_icons' => 0
    ]);
    
    $atts = shortcode_atts([
        'position' => $settings['position'],
        'animation' => $settings['animation'],
        'mobile' => $settings['mobile_enabled'] ? 'true' : 'false',
        'show_names' => $settings['show_names'] ? 'true' : 'false'
    ], $atts);
    
    $buttons = get_option('sfb_buttons', []);
    if(empty($buttons)) return '';
    
    $position_class = 'sfb-position-' . $atts['position'];
    $animation_class = 'sfb-animation-' . $atts['animation'];
    $mobile_class = $atts['mobile'] === 'true' ? 'sfb-mobile-enabled' : 'sfb-mobile-disabled';
    $show_names = $atts['show_names'] === 'true';
    $transparent_icons = !$show_names && $settings['transparent_icons'];
    
    ob_start(); ?>
    
    <div class="sfb-container <?php echo esc_attr("$position_class $animation_class $mobile_class"); ?> 
         <?php echo $show_names ? 'sfb-show-names' : 'sfb-icons-only'; ?>
         <?php echo $transparent_icons ? 'sfb-transparent-icons' : ''; ?>">
        
        <div class="sfb-cta" style="background-color: <?php echo esc_attr($settings['button_color']); ?>">
            <span class="sfb-cta-icon"><?php echo $settings['button_icon']; ?></span>
        </div>
        
        <div class="sfb-popup">
            <?php foreach($buttons as $b): ?>
                <?php 
                $href = $b['type'] === 'tel' ? 'tel:' . $b['url'] : 
                       ($b['type'] === 'mailto' ? 'mailto:' . $b['url'] : $b['url']);
                $icon_html = isset($b['icon_type']) && $b['icon_type'] === 'html' ? $b['icon'] : '<span class="' . esc_attr($b['icon']) . '"></span>';
                ?>
                
                <a href="<?php echo esc_url($href); ?>" class="sfb-item" target="_blank">
                    <?php echo $icon_html; ?>
                    <?php if($show_names): ?>
                        <span class="sfb-item-label"><?php echo esc_html($b['name']); ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    
    <style>
    .sfb-container {
        position: fixed;
        z-index: 9999;
        font-family: Arial, sans-serif;
    }
    
    /* Position classes */
    .sfb-position-right { 
        right: 20px; 
        bottom: 20px; 
    }
    .sfb-position-left { 
        left: 20px; 
        bottom: 20px; 
    }
    .sfb-position-top-right { 
        right: 20px; 
        top: 20px; 
    }
    .sfb-position-top-left { 
        left: 20px; 
        top: 20px; 
    }
    
    /* CTA Button */
    .sfb-cta {
        color: #fff;
        padding: 15px;
        border-radius: 50%;
        cursor: pointer;
        font-size: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 50px;
        height: 50px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        transition: all 0.3s ease;
        position: relative;
        z-index: 10000;
    }
    .sfb-cta:hover {
        transform: scale(1.1);
    }
    
    /* Popup */
    .sfb-popup {
        display: none;
        flex-direction: column;
        position: absolute;
        z-index: 9998;
    }
    
    /* Positionare popup */
    .sfb-position-right .sfb-popup,
    .sfb-position-left .sfb-popup {
        bottom: 100%;
        margin-bottom: 10px;
    }
    .sfb-position-right .sfb-popup { right: 0; }
    .sfb-position-left .sfb-popup { left: 0; }
    
    .sfb-position-top-right .sfb-popup,
    .sfb-position-top-left .sfb-popup {
        top: 100%;
        margin-top: 10px;
    }
    .sfb-position-top-right .sfb-popup { right: 0; }
    .sfb-position-top-left .sfb-popup { left: 0; }
    
    /* Items - STILURI DIFERITE ÎN FUNCȚIE DE SETĂRI */
    
    /* Stil pentru afișare cu nume */
    .sfb-show-names .sfb-item {
        display: flex;
        align-items: center;
        padding: 12px 15px;
        margin: 5px 0;
        border-radius: 8px;
        text-decoration: none;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        color: #333;
        transition: all 0.2s ease;
        opacity: 0;
        background: #ffffff;
        border-left: 4px solid <?php echo esc_attr($settings['button_color']); ?>;
        min-width: 200px;
    }
    
    /* Stil pentru afișare doar cu iconițe (cu background) */
    .sfb-icons-only:not(.sfb-transparent-icons) .sfb-item {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 10px;
        margin: 5px 0;
        border-radius: 8px;
        text-decoration: none;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        color: #333;
        transition: all 0.2s ease;
        opacity: 0;
        background: #ffffff;
        border-left: 4px solid <?php echo esc_attr($settings['button_color']); ?>;
        min-width: 50px;
        min-height: 50px;
    }
    
    /* Stil pentru afișare doar cu iconițe (TRANSPARENT) */
    .sfb-transparent-icons .sfb-item {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 10px;
        margin: 5px 0;
        border-radius: 50%;
        text-decoration: none;
        color: <?php echo esc_attr($settings['button_color']); ?>;
        transition: all 0.2s ease;
        opacity: 0;
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
        min-width: 40px;
        min-height: 40px;
        font-size: 20px;
    }
    
    /* Hover effects pentru toate variantele */
    .sfb-item:hover {
        transform: scale(1.1);
    }
    .sfb-show-names .sfb-item:hover,
    .sfb-icons-only:not(.sfb-transparent-icons) .sfb-item:hover {
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    .sfb-transparent-icons .sfb-item:hover {
        background: <?php echo esc_attr($settings['button_color']); ?> !important;
        color: #fff !important;
    }
    
    .sfb-item-label {
        margin-left: 10px;
        font-size: 14px;
        font-weight: 600;
    }
    
    /* Animații */
    .sfb-position-right .sfb-item,
    .sfb-position-left .sfb-item {
        transform: translateY(20px);
    }
    .sfb-position-top-right .sfb-item,
    .sfb-position-top-left .sfb-item {
        transform: translateY(-20px);
    }
    
    /* Active state */
    .sfb-container.active .sfb-popup {
        display: flex;
    }
    .sfb-container.active .sfb-item {
        opacity: 1;
        transform: translateY(0);
    }
    
    /* Delay pentru animații */
    .sfb-container.active .sfb-item:nth-child(1) { transition-delay: 0.1s; }
    .sfb-container.active .sfb-item:nth-child(2) { transition-delay: 0.2s; }
    .sfb-container.active .sfb-item:nth-child(3) { transition-delay: 0.3s; }
    .sfb-container.active .sfb-item:nth-child(4) { transition-delay: 0.4s; }
    
    /* Mobile responsive */
    @media (max-width: 768px) {
        .sfb-mobile-enabled .sfb-cta {
            width: 45px;
            height: 45px;
            font-size: 16px;
            padding: 12px;
        }
        .sfb-mobile-enabled .sfb-show-names .sfb-item {
            padding: 10px 12px;
            font-size: 14px;
            min-width: 180px;
        }
        .sfb-mobile-enabled .sfb-icons-only .sfb-item {
            padding: 8px;
            min-width: 45px;
            min-height: 45px;
        }
        .sfb-mobile-enabled .sfb-transparent-icons .sfb-item {
            min-width: 35px;
            min-height: 35px;
            font-size: 18px;
        }
        .sfb-mobile-disabled {
            display: none !important;
        }
        
        .sfb-position-right,
        .sfb-position-left {
            right: 10px;
            bottom: 10px;
        }
        .sfb-position-left {
            left: 10px;
            right: auto;
        }
    }
    </style>
    
    <script>
    document.addEventListener("DOMContentLoaded", function(){
        const containers = document.querySelectorAll('.sfb-container');
        
        containers.forEach(container => {
            const cta = container.querySelector('.sfb-cta');
            if(cta){
                cta.addEventListener('click', (e) => {
                    e.stopPropagation();
                    container.classList.toggle('active');
                });
            }
        });
        
        // Close on outside click
        document.addEventListener('click', function(){
            containers.forEach(container => {
                container.classList.remove('active');
            });
        });
        
        // Prevent closing when clicking inside container
        containers.forEach(container => {
            container.addEventListener('click', (e) => {
                e.stopPropagation();
            });
        });
    });
    </script>
    
    <?php
    return ob_get_clean();
});
