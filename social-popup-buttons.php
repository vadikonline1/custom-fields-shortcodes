<?php
if (!defined('ABSPATH')) exit;

// =========================
// ADMIN PAGE - COMPLETĂ CU NONCE FIELD
// =========================
function sfb_admin_page(){
    $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'buttons';
    ?>
    <div class="wrap">
        <h1>Social Floating Buttons</h1>
        
        <!-- ADD BUTTONUL DE "ADD NEW" -->
        <?php if($current_tab === 'buttons' && !isset($_GET['action'])): ?>
            <a href="<?php echo admin_url('admin.php?page=social-floating-buttons&action=add'); ?>" class="page-title-action">
                + Add New Button
            </a>
        <?php endif; ?>
        
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
            wp_nonce_field('sfb_bulk_action', 'sfb_nonce');
            $table->display();
            echo '</form>';
        }
        ?>
    </div>
    <?php
}

// =========================
// SETTINGS PAGE
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
            'transparent_icons' => isset($_POST['sfb_transparent_icons']) ? 1 : 0,
            'custom_message' => sanitize_text_field($_POST['sfb_custom_message']) // NOU
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
        'transparent_icons' => 0,
        'custom_message' => "Let's chat with US!" // VALOARE IMPLICITĂ
    ]);
    ?>
    
    <div class="sfb-settings-page">
        <form method="post">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="sfb_custom_message">Custom Message</label></th>
                    <td>
                        <input type="text" name="sfb_custom_message" id="sfb_custom_message" 
                               value="<?php echo esc_attr($settings['custom_message']); ?>" 
                               class="regular-text" placeholder="Let's chat with US!">
                        <p class="description">This message will be displayed in the UI when hovering or interacting with the button</p>
                    </td>
                </tr>
                
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
                
                <!-- Restul setărilor rămân la fel -->
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
            
            <!-- Custom Message Preview -->
            <div class="sfb-custom-message-preview">
                <strong>Custom Message:</strong> "<?php echo esc_html($settings['custom_message']); ?>"
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
        .sfb-custom-message-preview {
            background: #fff;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
            border-left: 4px solid <?php echo esc_attr($settings['button_color']); ?>;
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
        <div class="sfb-cdn-section">
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
            'order' => 'Order',
            'name' => 'Name',
            'icon' => 'Icon',
            'type' => 'Type',
            'url' => 'URL',
            'shortcode' => 'Shortcode',
            'actions' => 'Actions'
        ];
    }

    function prepare_items(){
        $this->_column_headers = [$this->get_columns(), [], ['order' => 'asc']];
        $buttons = get_option('sfb_buttons', []);
        
        // Sort buttons by order field
        usort($buttons, function($a, $b) {
            $order_a = isset($a['order']) ? intval($a['order']) : 0;
            $order_b = isset($b['order']) ? intval($b['order']) : 0;
            return $order_a - $order_b;
        });
        
        $this->items = $buttons;
        
        // Process bulk actions
        $this->process_bulk_action();
    }

    function column_cb($item){
        return sprintf('<input type="checkbox" name="id[]" value="%s" />', $item['id']);
    }

    function column_order($item){
        $order = isset($item['order']) ? intval($item['order']) : 0;
        return sprintf(
            '<input type="number" name="order[%s]" value="%d" class="sfb-order-input" min="0" size="3" />',
            $item['id'],
            $order
        );
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
            'delete_permanently' => 'Delete Permanently',
            'update_order' => 'Update Order'
        ];
    }

    function process_bulk_action(){
        if(!isset($_POST['sfb_nonce']) || !wp_verify_nonce($_POST['sfb_nonce'], 'sfb_bulk_action')) { return; }

        $ids = is_array($_POST['id']) ? $_POST['id'] : [$_POST['id']];
        $ids = array_map('sanitize_text_field', $ids);

        switch($this->current_action()){
            case 'trash':
                foreach($ids as $id){
                    sfb_move_to_trash($id);
                }
                echo '<div class="updated"><p>Items moved to trash!</p></div>';
                break;
            case 'delete_permanently':
                foreach($ids as $id){
                    sfb_delete_permanently($id);
                }
                echo '<div class="updated"><p>Items permanently deleted!</p></div>';
                break;
            case 'update_order':
                if(isset($_POST['order']) && is_array($_POST['order'])){
                    $buttons = get_option('sfb_buttons', []);
                    foreach($_POST['order'] as $button_id => $order_value){
                        $order_value = intval($order_value);
                        foreach($buttons as &$button){
                            if($button['id'] === $button_id){
                                $button['order'] = $order_value;
                                break;
                            }
                        }
                    }
                    update_option('sfb_buttons', $buttons);
                    echo '<div class="updated"><p>Order updated successfully!</p></div>';
                }
                break;
        }

        // Re-preparați items după acțiune
        $this->prepare_items();
    }

    function display(){
        echo '<form method="post" id="sfb-buttons-form">';
        wp_nonce_field('sfb_bulk_action', 'sfb_nonce');
        parent::display();
        echo '</form>';
        
        // Adaugă stiluri pentru afișarea mai bună a multor butoane
        echo '
        <style>
        .wp-list-table.buttons {
            table-layout: auto;
        }
        .wp-list-table.buttons th.column-order,
        .wp-list-table.buttons td.column-order {
            width: 80px;
            text-align: center;
        }
        .sfb-order-input {
            width: 60px;
            text-align: center;
        }
        .tablenav .actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        </style>
        ';
    }
}

// =========================
// HANDLE ACTIONS
// =========================
function sfb_handle_actions() {
    // Verificăm că suntem pe pagina pluginului nostru
    if ( ! isset($_GET['page']) || $_GET['page'] !== 'social-floating-buttons' ) {
        return;
    }

    if ( isset($_GET['action']) && $_GET['action'] === 'trash' && !empty($_GET['id']) ) {
        $id = $_GET['id'];

        if (is_array($id)) {
            $id = reset($id); // luam primul element dacă e array
        }

        // ID-ul poate fi alfanumeric, deci nu facem intval
        $id = sanitize_text_field($id);

        if ( isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'sfb_trash_' . $id) ) {
            sfb_move_to_trash($id);
            wp_redirect(admin_url('admin.php?page=social-floating-buttons&message=trashed'));
            exit;
        } else {
            wp_die('Nonce verification failed. Action not allowed.');
        }
    }
}
add_action('admin_init', 'sfb_handle_actions');

// =========================
// ENHANCED ADD/EDIT FORM
// =========================
function sfb_add_edit_form(){
    $buttons = get_option('sfb_buttons', []);
    $editing = false;
    $current = ['id'=>'', 'icon'=>'', 'name'=>'', 'type'=>'url', 'url'=>'', 'icon_type'=>'class', 'order'=>0];

    if(isset($_GET['action']) && $_GET['action']==='edit' && !empty($_GET['id'])){
        foreach($buttons as $b){ 
            if($b['id']===$_GET['id']){ 
                $current = $b; 
                $editing = true; 
                // Detect icon type
                $current['icon_type'] = (strpos($current['icon'], '<') !== false) ? 'html' : 'class';
                // Set default order if not exists
                if(!isset($current['order'])) {
                    $current['order'] = 0;
                }
                break; 
            } 
        }
    }

    if(isset($_POST['sfb_save_button'])){
        $icon_type = sanitize_text_field($_POST['sfb_icon_type']);
        $icon_content = $icon_type === 'html' ? wp_kses_post($_POST['sfb_icon_html']) : sanitize_text_field($_POST['sfb_icon_class']);
        $order = isset($_POST['sfb_order']) ? intval($_POST['sfb_order']) : 0;
        
        if($editing){
            foreach($buttons as &$b){ 
                if($b['id']===$current['id']){
                    $b['icon'] = $icon_content;
                    $b['name'] = sanitize_text_field($_POST['sfb_name']);
                    $b['type'] = sanitize_text_field($_POST['sfb_type']);
                    $b['url'] = sanitize_text_field($_POST['sfb_url']);
                    $b['icon_type'] = $icon_type;
                    $b['order'] = $order;
                }
            }
        } else {
            $id = uniqid('sfb_');
            // Get max order for new item
            $max_order = 0;
            foreach($buttons as $b) {
                if(isset($b['order']) && $b['order'] > $max_order) {
                    $max_order = $b['order'];
                }
            }
            $buttons[] = [
                'id' => $id,
                'icon' => $icon_content,
                'name' => sanitize_text_field($_POST['sfb_name']),
                'type' => sanitize_text_field($_POST['sfb_type']),
                'url' => sanitize_text_field($_POST['sfb_url']),
                'icon_type' => $icon_type,
                'order' => $max_order + 1
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
                <th>Order</th>
                <td>
                    <input type="number" name="sfb_order" value="<?php echo esc_attr($current['order']); ?>" min="0" step="1" class="small-text">
                    <p class="description">Lower numbers appear first. Use this to control the display order.</p>
                </td>
            </tr>
            
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
// ENHANCED FRONTEND SHORTCODE
// =========================
add_shortcode('sfb_floating', function($atts){
    $settings = get_option('sfb_settings', [
        'position' => 'right',
        'button_color' => '#0073aa',
        'button_icon' => '☰',
        'animation' => 'slide',
        'mobile_enabled' => 1,
        'show_names' => 1,
        'transparent_icons' => 0,
        'custom_message' => "Let's chat with US!"
    ]);
    
    $atts = shortcode_atts([
        'position' => $settings['position'],
        'animation' => $settings['animation'],
        'mobile' => $settings['mobile_enabled'] ? 'true' : 'false',
        'show_names' => $settings['show_names'] ? 'true' : 'false'
    ], $atts);
    
    $buttons = get_option('sfb_buttons', []);
    if(empty($buttons)) return '';
    
    // Sort buttons by order
    usort($buttons, function($a, $b) {
        $order_a = isset($a['order']) ? intval($a['order']) : 0;
        $order_b = isset($b['order']) ? intval($b['order']) : 0;
        return $order_a - $order_b;
    });
    
    $position_class = 'sfb-position-' . $atts['position'];
    $animation_class = 'sfb-animation-' . $atts['animation'];
    $mobile_class = $atts['mobile'] === 'true' ? 'sfb-mobile-enabled' : 'sfb-mobile-disabled';
    $show_names = $atts['show_names'] === 'true';
    $transparent_icons = !$show_names && $settings['transparent_icons'];
    
    ob_start(); ?>
    
    <div class="sfb-container <?php echo esc_attr("$position_class $animation_class $mobile_class"); ?>  <?php echo $show_names ? 'sfb-show-names' : 'sfb-icons-only'; ?> <?php echo $transparent_icons ? 'sfb-transparent-icons' : ''; ?>">
        
        <div class="sfb-cta" style="background-color: <?php echo esc_attr($settings['button_color']); ?>" aria-label="Open social menu" role="button" tabindex="0">
            <span class="sfb-cta-icon"><?php echo $settings['button_icon']; ?></span>
        </div>
        
        <!-- Custom Message Tooltip -->
        <div class="sfb-custom-message" id="sfb-custom-message">
            <?php echo esc_html($settings['custom_message']); ?>
        </div>
        
        <div class="sfb-popup" role="menu" aria-label="Social media links">
            <?php foreach($buttons as $b): ?>
                <?php 
                $href = $b['type'] === 'tel' ? 'tel:' . $b['url'] : 
                       ($b['type'] === 'mailto' ? 'mailto:' . $b['url'] : $b['url']);
                $icon_html = isset($b['icon_type']) && $b['icon_type'] === 'html' ? $b['icon'] : '<span class="' . esc_attr($b['icon']) . '" aria-hidden="true"></span>';
                ?>
                
                <a href="<?php echo esc_url($href); ?>" class="sfb-item" target="_blank" rel="noopener" role="menuitem">
                    <?php echo $icon_html; ?>
                    <?php if($show_names): ?>
                        <span class="sfb-item-label"><?php echo esc_html($b['name']); ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    
    <style>
/* Social Floating Buttons - CSS Final Modificat */
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

/* CTA Button cu pulsatie cand mesajul nu este afisat */
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
    animation: buttonPulse 2s ease-in-out infinite;
}
.sfb-cta:hover {
    transform: scale(1.1);
    animation: none; /* Opreste pulsatia la hover */
}

@keyframes buttonPulse {
    0%, 100% { 
        transform: scale(1);
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    }
    50% { 
        transform: scale(1.05);
        box-shadow: 0 4px 15px rgba(0,0,0,0.3);
    }
}

/* Opreste pulsatia cand mesajul este afisat */
.sfb-cta.message-visible {
    animation: none;
    transform: scale(1);
}

/* Custom Message Tooltip - cu timeout */
.sfb-custom-message {
    position: absolute;
    background: #333;
    color: white;
    padding: 8px 12px;
    border-radius: 4px;
    font-size: 12px;
    white-space: nowrap;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
    z-index: 10001;
    pointer-events: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.3);
}

/* Poziționare mesaj */
.sfb-position-right .sfb-custom-message {
    right: 100%;
    top: 50%;
    transform: translateY(-50%);
    margin-right: 10px;
}
.sfb-position-left .sfb-custom-message {
    left: 100%;
    top: 50%;
    transform: translateY(-50%);
    margin-left: 10px;
}
.sfb-position-top-right .sfb-custom-message {
    right: 100%;
    bottom: 50%;
    transform: translateY(50%);
    margin-right: 10px;
}
.sfb-position-top-left .sfb-custom-message {
    left: 100%;
    bottom: 50%;
    transform: translateY(50%);
    margin-left: 10px;
}

/* Stil pentru mesaj vizibil */
.sfb-custom-message.visible {
    opacity: 1;
    visibility: visible;
}

/* Efect de transparenta la scroll pentru butonul principal */
.sfb-container.scrolling .sfb-cta {
    opacity: 0.7;
    transform: scale(0.95);
}

/* Efect de transparenta la scroll pentru mesaj */
.sfb-container.scrolling .sfb-custom-message {
    opacity: 0.3 !important;
    transform: translateY(-50%) scale(0.95) !important;
}

/* Popup */
.sfb-popup {
    display: none;
    flex-direction: column;
    position: absolute;
    z-index: 9998;
    max-height: 70vh;
    overflow-y: auto;
    padding: 5px 0;
}

/* Scrollbar styling for many buttons */
.sfb-popup::-webkit-scrollbar {
    width: 6px;
}
.sfb-popup::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}
.sfb-popup::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}
.sfb-popup::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
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
    border-left: 4px solid var(--sfb-main-color, #0073aa);
    min-width: 200px;
    white-space: nowrap;
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
    border-left: 4px solid var(--sfb-main-color, #0073aa);
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
    color: var(--sfb-main-color, #0073aa);
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
    background: var(--sfb-main-color, #0073aa) !important;
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

/* Delay pentru animații - optimizat pentru multe butoane */
.sfb-container.active .sfb-item {
    transition-delay: calc(0.05s * var(--item-index, 0));
}

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
    
    /* Pe mobile, mesajul apare mai scurt */
    .sfb-custom-message {
        font-size: 11px;
        padding: 6px 10px;
    }
}
    </style>
    
    <script>
document.addEventListener("DOMContentLoaded", function(){
    const containers = document.querySelectorAll('.sfb-container');
    let isScrolling = false;
    let scrollTimer;
    let messageTimer;
    let pageLoadTime = Date.now();

    containers.forEach(container => {
        const cta = container.querySelector('.sfb-cta');
        const message = container.querySelector('.sfb-custom-message');
        const items = container.querySelectorAll('.sfb-item');
        
        // Setează variabila CSS pentru culoarea principală
        if (cta) {
            const mainColor = getComputedStyle(cta).backgroundColor;
            container.style.setProperty('--sfb-main-color', mainColor);
        }
        
        // Set item indexes for staggered animation
        items.forEach((item, index) => {
            item.style.setProperty('--item-index', index);
        });

        // Funcție pentru afișarea mesajului cu timeout
        function showMessageWithTimeout() {
            if (message && !container.classList.contains('active')) {
                message.classList.add('visible');
                if (cta) {
                    cta.classList.add('message-visible');
                }
                
                // Ascunde mesajul după 30 de secunde
                clearTimeout(messageTimer);
                messageTimer = setTimeout(() => {
                    if (message.classList.contains('visible')) {
                        message.classList.remove('visible');
                        if (cta) {
                            cta.classList.remove('message-visible');
                        }
                    }
                }, 30000); // 30 de secunde
            }
        }

        // Afișează mesajul la 2 secunde după încărcarea paginii
        setTimeout(() => {
            if (!container.classList.contains('active')) {
                showMessageWithTimeout();
            }
        }, 2000);

        if(cta){
            cta.addEventListener('click', (e) => {
                e.stopPropagation();
                container.classList.toggle('active');
                
                // Ascunde mesajul când se deschide meniul
                if (message) {
                    message.classList.remove('visible');
                    cta.classList.remove('message-visible');
                    clearTimeout(messageTimer);
                }
            });

            // Re-afișează mesajul când se închide meniul (dacă nu s-a făcut scroll)
            cta.addEventListener('blur', function() {
                if (!container.classList.contains('active')) {
                    setTimeout(() => {
                        if (!container.matches(':hover') && !isScrolling) {
                            showMessageWithTimeout();
                        }
                    }, 1000);
                }
            });

            // Afișează mesajul la hover (opțional)
            cta.addEventListener('mouseenter', function() {
                if (!container.classList.contains('active')) {
                    showMessageWithTimeout();
                }
            });
        }

        // Îmbunătățiri accessibility
        if (message) {
            message.setAttribute('role', 'tooltip');
            message.setAttribute('aria-hidden', 'true');
        }
    });

    // Gestionare scroll cu efect de transparență
    function handleScroll() {
        if (!isScrolling) {
            containers.forEach(container => {
                container.classList.add('scrolling');
                const message = container.querySelector('.sfb-custom-message');
                const cta = container.querySelector('.sfb-cta');
                
                // Ascunde mesajul în timpul scroll-ului activ
                if (message) {
                    message.style.opacity = '0.3';
                }
                if (cta) {
                    cta.style.opacity = '0.7';
                }
            });
            isScrolling = true;
        }

        clearTimeout(scrollTimer);
        scrollTimer = setTimeout(function() {
            containers.forEach(container => {
                container.classList.remove('scrolling');
                const message = container.querySelector('.sfb-custom-message');
                const cta = container.querySelector('.sfb-cta');
                
                // Restabilește opacitatea după scroll
                if (message && message.classList.contains('visible')) {
                    message.style.opacity = '1';
                }
                if (cta) {
                    cta.style.opacity = '1';
                    cta.classList.remove('message-visible');
                }
            });
            isScrolling = false;
        }, 150);
    }

    // Optimizare pentru performanță la scroll
    window.addEventListener('scroll', handleScroll, { passive: true });

    // Re-afișează mesajul după scroll (după 30 de secunde de la sfârșitul scroll-ului)
    let scrollEndMessageTimer;
    window.addEventListener('scroll', function() {
        clearTimeout(scrollEndMessageTimer);
        scrollEndMessageTimer = setTimeout(() => {
            if (!isScrolling) {
                containers.forEach(container => {
                    if (!container.classList.contains('active')) {
                        const message = container.querySelector('.sfb-custom-message');
                        const cta = container.querySelector('.sfb-cta');
                        if (message && !message.classList.contains('visible')) {
                            message.classList.add('visible');
                            if (cta) {
                                cta.classList.add('message-visible');
                            }
                            
                            // Ascunde din nou după 30 de secunde
                            setTimeout(() => {
                                if (message.classList.contains('visible')) {
                                    message.classList.remove('visible');
                                    if (cta) {
                                        cta.classList.remove('message-visible');
                                    }
                                }
                            }, 30000);
                        }
                    }
                });
            }
        }, 30000); // Re-afișează după 30 de secunde de la sfârșitul scroll-ului
    }, { passive: true });

    // Close on outside click
    document.addEventListener('click', function(e){
        let clickedInside = false;
        containers.forEach(container => {
            if (container.contains(e.target)) {
                clickedInside = true;
            } else {
                container.classList.remove('active');
            }
        });
    });

    // Close on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            containers.forEach(container => {
                container.classList.remove('active');
            });
        }
    });

    // Handle touch events for mobile
    document.addEventListener('touchstart', function(e) {
        let touchedInside = false;
        containers.forEach(container => {
            if (container.contains(e.target)) {
                touchedInside = true;
            } else {
                container.classList.remove('active');
            }
        });
    }, { passive: true });

    // Performance optimization
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    if (reduceMotion.matches) {
        document.documentElement.style.setProperty('--sfb-transition-duration', '0.1s');
        // Oprește animația de puls pentru utilizatorii care preferă mișcare redusă
        containers.forEach(container => {
            const cta = container.querySelector('.sfb-cta');
            if (cta) {
                cta.style.animation = 'none';
            }
        });
    }

    // Resize observer
    const resizeObserver = new ResizeObserver(entries => {
        containers.forEach(container => {
            if (container.classList.contains('active')) {
                container.style.transform = 'translateZ(0)';
            }
        });
    });

    containers.forEach(container => {
        resizeObserver.observe(container);
    });

    // Reîncarcă mesajul la vizibilitatea paginii (dacă utilizatorul revine la tab)
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            // Pagina a devenit vizibilă din nou
            containers.forEach(container => {
                if (!container.classList.contains('active')) {
                    const message = container.querySelector('.sfb-custom-message');
                    const cta = container.querySelector('.sfb-cta');
                    if (message && !message.classList.contains('visible')) {
                        // Așteaptă 2 secunde după ce utilizatorul revine
                        setTimeout(() => {
                            message.classList.add('visible');
                            if (cta) {
                                cta.classList.add('message-visible');
                            }
                            
                            setTimeout(() => {
                                if (message.classList.contains('visible')) {
                                    message.classList.remove('visible');
                                    if (cta) {
                                        cta.classList.remove('message-visible');
                                    }
                                }
                            }, 30000);
                        }, 2000);
                    }
                }
            });
        }
    });
});
		
    </script>
    
    <?php
    return ob_get_clean();
});

// =========================
// UPDATE EXISTING BUTTONS WITH ORDER FIELD
// =========================
function sfb_update_existing_buttons_with_order() {
    $buttons = get_option('sfb_buttons', []);
    $updated = false;
    
    foreach($buttons as &$button) {
        if(!isset($button['order'])) {
            $button['order'] = 0;
            $updated = true;
        }
    }
    
    if($updated) {
        update_option('sfb_buttons', $buttons);
    }
}
add_action('admin_init', 'sfb_update_existing_buttons_with_order');
