<?php

// =========================
// ADMIN PAGE
// =========================
function sfb_admin_page(){
    $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'buttons';
    
    // Încarcă CSS și JS extern DOAR pentru admin
    wp_enqueue_style('sfb-admin-style', plugins_url('assets/spb_style.css', __FILE__));
    wp_enqueue_script('sfb-admin-script', plugins_url('assets/spb_script.js', __FILE__), ['jquery'], false, true);
    ?>
    <div class="wrap">
        <h1>Social Floating Buttons</h1>
        
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
            // Include CDN management page
            if(file_exists(plugin_dir_path(__FILE__) . 'spb/spb-cdn.php')){
                include_once plugin_dir_path(__FILE__) . 'spb/spb-cdn.php';
            } else {
                echo '<div class="error"><p>CDN management file not found.</p></div>';
            }
        } elseif($current_tab === 'trash'){
            // Include Trash management page
            if(file_exists(plugin_dir_path(__FILE__) . 'spb/spb-trash.php')){
                include_once plugin_dir_path(__FILE__) . 'spb/spb-trash.php';
            } else {
                echo '<div class="error"><p>Trash management file not found.</p></div>';
            }
        } elseif($current_tab === 'settings'){
            // Include Settings page
            if(file_exists(plugin_dir_path(__FILE__) . 'spb/spb-settings.php')){
                include_once plugin_dir_path(__FILE__) . 'spb/spb-settings.php';
            } else {
                echo '<div class="error"><p>Settings file not found.</p></div>';
            }
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
// PREDEFINED CDNs
// =========================
function sfb_get_predefined_cdns(){
    return [
        'fontawesome' => [
            'name' => 'Font Awesome (CSS CDN)',
            'default_url' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
            'docs' => 'https://fontawesome.com/icons',
            'type' => 'css'
        ],
        'fontawesome_kit' => [
            'name' => 'Font Awesome Kit (JS)',
            'default_url' => 'https://kit.fontawesome.com/0f950e4537.js',
            'docs' => 'https://fontawesome.com/kits',
            'type' => 'js'
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
        
        usort($buttons, function($a, $b) {
            $order_a = isset($a['order']) ? intval($a['order']) : 0;
            $order_b = isset($b['order']) ? intval($b['order']) : 0;
            return $order_a - $order_b;
        });
        
        $this->items = $buttons;
        $this->process_bulk_action();
    }

    function column_cb($item){
        return sprintf('<input type="checkbox" name="id[]" value="%s" />', $item['id']);
    }

    function column_order($item){
        $order = isset($item['order']) ? intval($item['order']) : 0;
        return sprintf('<span class="sfb-order-display">%d</span>', $order);
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
        }

        $this->prepare_items();
    }
}

// =========================
// HANDLE ACTIONS
// =========================
function sfb_handle_actions() {
    if ( ! isset($_GET['page']) || $_GET['page'] !== 'social-floating-buttons' ) {
        return;
    }

    if ( isset($_GET['action']) && $_GET['action'] === 'trash' && !empty($_GET['id']) ) {
        $id = $_GET['id'];

        if (is_array($id)) {
            $id = reset($id);
        }

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
    $current = ['id'=>'', 'icon'=>'', 'name'=>'', 'type'=>'url', 'url'=>'', 'icon_type'=>'class', 'order'=>0, 'show_in_floating'=>1];

    if(isset($_GET['action']) && $_GET['action']==='edit' && !empty($_GET['id'])){
        foreach($buttons as $b){ 
            if($b['id']===$_GET['id']){ 
                $current = $b; 
                $editing = true; 
                $current['icon_type'] = (strpos($current['icon'], '<') !== false) ? 'html' : 'class';
                if(!isset($current['order'])) {
                    $current['order'] = 0;
                }
                if(!isset($current['show_in_floating'])) {
                    $current['show_in_floating'] = 1;
                }
                break; 
            } 
        }
    }

    if(isset($_POST['sfb_save_button'])){
        $icon_type = sanitize_text_field($_POST['sfb_icon_type']);
        $icon_content = $icon_type === 'html' ? wp_kses_post($_POST['sfb_icon_html']) : sanitize_text_field($_POST['sfb_icon_class']);
        $show_in_floating = isset($_POST['sfb_show_in_floating']) ? 1 : 0;
        
        if($editing){
            $order = isset($_POST['sfb_order']) ? intval($_POST['sfb_order']) : 0;
            foreach($buttons as &$b){ 
                if($b['id']===$current['id']){
                    $b['icon'] = $icon_content;
                    $b['name'] = sanitize_text_field($_POST['sfb_name']);
                    $b['type'] = sanitize_text_field($_POST['sfb_type']);
                    $b['url'] = sanitize_text_field($_POST['sfb_url']);
                    $b['icon_type'] = $icon_type;
                    $b['order'] = $order;
                    $b['show_in_floating'] = $show_in_floating;
                }
            }
        } else {
            $id = uniqid('sfb_');
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
                'order' => $max_order + 1,
                'show_in_floating' => $show_in_floating
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
            <?php if($editing): ?>
            <tr>
                <th>Order</th>
                <td>
                    <input type="number" name="sfb_order" value="<?php echo esc_attr($current['order']); ?>" min="0" step="1" class="small-text">
                    <p class="description">Lower numbers appear first. Use this to control the display order.</p>
                </td>
            </tr>
            <?php endif; ?>
            
            <tr>
                <th>Show in Floating Menu</th>
                <td>
                    <label>
                        <input type="checkbox" name="sfb_show_in_floating" value="1" <?php checked($current['show_in_floating'], 1); ?>>
                        Display this button in the floating social menu
                    </label>
                    <p class="description">If unchecked, this button will only be available via shortcodes and not in the floating menu</p>
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
                    <textarea name="sfb_icon_html" rows="3" cols="50" class="large-text code" placeholder='<i class="fab fa-facebook-f"></i> or <svg>...'>><?php echo $current['icon_type'] === 'html' ? esc_textarea($current['icon']) : ''; ?></textarea>
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
    <?php
}

// =========================
// ENQUEUE CDN STYLES & SCRIPTS
// =========================
function sfb_enqueue_icon_cdns(){
    $settings = get_option('sfb_icon_cdns', []);
    $custom_cdns = get_option('sfb_custom_cdns', []);
    $predefined_cdns = sfb_get_predefined_cdns();
    
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
// ENQUEUE FRONTEND STYLES
// =========================
function sfb_enqueue_frontend_styles() {
    wp_enqueue_style('sfb-frontend-style', plugins_url('assets/spb_frontend.css', __FILE__));
}
add_action('wp_enqueue_scripts', 'sfb_enqueue_frontend_styles');

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
        'custom_message' => "Let's chat with US!",
        'show_custom_message' => 1,
        'show_shortcut_names' => 1
    ]);
    
    $atts = shortcode_atts([
        'position' => $settings['position'],
        'animation' => $settings['animation'],
        'mobile' => $settings['mobile_enabled'] ? 'true' : 'false',
        'show_names' => $settings['show_names'] ? 'true' : 'false',
        'show_custom_message' => $settings['show_custom_message'] ? 'true' : 'false'
    ], $atts);
    
    $buttons = get_option('sfb_buttons', []);
    
    $buttons = array_filter($buttons, function($button) {
        return isset($button['show_in_floating']) ? $button['show_in_floating'] : true;
    });
    
    if(empty($buttons)) return '';
    
    usort($buttons, function($a, $b) {
        $order_a = isset($a['order']) ? intval($a['order']) : 0;
        $order_b = isset($b['order']) ? intval($b['order']) : 0;
        return $order_a - $order_b;
    });
    
    $position_class = 'sfb-position-' . $atts['position'];
    $animation_class = 'sfb-animation-' . $atts['animation'];
    $mobile_class = $atts['mobile'] === 'true' ? 'sfb-mobile-enabled' : 'sfb-mobile-disabled';
    $show_names = $atts['show_names'] === 'true';
    $show_custom_message = $atts['show_custom_message'] === 'true';
    $transparent_icons = !$show_names && $settings['transparent_icons'];
    
    ob_start(); ?>
    
    <div class="sfb-container <?php echo esc_attr("$position_class $animation_class $mobile_class"); ?>  <?php echo $show_names ? 'sfb-show-names' : 'sfb-icons-only'; ?> <?php echo $transparent_icons ? 'sfb-transparent-icons' : ''; ?>">
        
        <div class="sfb-cta" style="background-color: <?php echo esc_attr($settings['button_color']); ?>" aria-label="Open social menu" role="button" tabindex="0">
            <span class="sfb-cta-icon"><?php echo $settings['button_icon']; ?></span>
        </div>
        
        <?php if($show_custom_message): ?>
        <div class="sfb-custom-message" id="sfb_custom_message">
            <?php echo esc_html($settings['custom_message']); ?>
        </div>
        <?php endif; ?>
        
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
    
    <script>
    document.addEventListener("DOMContentLoaded", function(){
        const containers = document.querySelectorAll('.sfb-container');
        let isScrolling = false;
        let scrollTimer;
        let messageTimer;

        containers.forEach(container => {
            const cta = container.querySelector('.sfb-cta');
            const message = container.querySelector('.sfb-custom-message');
            const items = container.querySelectorAll('.sfb-item');
            
            if (cta) {
                const mainColor = getComputedStyle(cta).backgroundColor;
                container.style.setProperty('--sfb-main-color', mainColor);
                
                if (message) {
                    cta.classList.add('message-pulse');
                }
            }
            
            items.forEach((item, index) => {
                item.style.setProperty('--item-index', index);
            });

            function showMessageWithTimeout() {
                if (message && !container.classList.contains('active')) {
                    message.classList.add('visible');
                    if (cta) {
                        cta.classList.add('message-visible');
                    }
                    
                    clearTimeout(messageTimer);
                    messageTimer = setTimeout(() => {
                        if (message.classList.contains('visible')) {
                            message.classList.remove('visible');
                            if (cta) {
                                cta.classList.remove('message-visible');
                            }
                        }
                    }, 30000);
                }
            }

            if (message) {
                setTimeout(() => {
                    if (!container.classList.contains('active')) {
                        showMessageWithTimeout();
                    }
                }, 2000);
            }

            if(cta){
                cta.addEventListener('click', (e) => {
                    e.stopPropagation();
                    container.classList.toggle('active');
                    
                    if (message) {
                        message.classList.remove('visible');
                        cta.classList.remove('message-visible');
                        clearTimeout(messageTimer);
                    }
                });

                if (message) {
                    cta.addEventListener('blur', function() {
                        if (!container.classList.contains('active')) {
                            setTimeout(() => {
                                if (!container.matches(':hover') && !isScrolling) {
                                    showMessageWithTimeout();
                                }
                            }, 1000);
                        }
                    });

                    cta.addEventListener('mouseenter', function() {
                        if (!container.classList.contains('active')) {
                            showMessageWithTimeout();
                        }
                    });
                }
            }

            if (message) {
                message.setAttribute('role', 'tooltip');
                message.setAttribute('aria-hidden', 'true');
            }
        });

        function handleScroll() {
            if (!isScrolling) {
                containers.forEach(container => {
                    container.classList.add('scrolling');
                    const message = container.querySelector('.sfb-custom-message');
                    const cta = container.querySelector('.sfb-cta');
                    
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

        window.addEventListener('scroll', handleScroll, { passive: true });

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
            }, 30000);
        }, { passive: true });

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

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                containers.forEach(container => {
                    container.classList.remove('active');
                });
            }
        });

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

        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
        if (reduceMotion.matches) {
            document.documentElement.style.setProperty('--sfb-transition-duration', '0.1s');
            containers.forEach(container => {
                const cta = container.querySelector('.sfb-cta');
                if (cta) {
                    cta.style.animation = 'none';
                }
            });
        }

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

        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                containers.forEach(container => {
                    if (!container.classList.contains('active')) {
                        const message = container.querySelector('.sfb-custom-message');
                        const cta = container.querySelector('.sfb-cta');
                        if (message && !message.classList.contains('visible')) {
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
        if(!isset($button['show_in_floating'])) {
            $button['show_in_floating'] = 1;
            $updated = true;
        }
    }
    
    if($updated) {
        update_option('sfb_buttons', $buttons);
    }
}
add_action('admin_init', 'sfb_update_existing_buttons_with_order');

// =========================
// FUNCTION TO DISPLAY ALL SHORTCUTS ANYWHERE
// =========================
function sfb_display_all_shortcuts($args = array()) {
    $defaults = array(
        'show_names' => null,
        'transparent' => null,
        'container_class' => 'sfb-shortcuts-container',
        'item_class' => 'sfb-shortcut-item'
    );
    
    $args = wp_parse_args($args, $defaults);
    
    $buttons = get_option('sfb_buttons', []);
    if(empty($buttons)) return '';
    
    if ($args['show_names'] === null) {
        $settings = get_option('sfb_settings', []);
        $args['show_names'] = isset($settings['show_shortcut_names']) ? $settings['show_shortcut_names'] : true;
    }
    
    if ($args['transparent'] === null) {
        $settings = get_option('sfb_settings', []);
        $args['transparent'] = isset($settings['transparent_icons']) ? $settings['transparent_icons'] : false;
    }
    
    ob_start();
    ?>
    <div class="<?php echo esc_attr($args['container_class']); ?>">
        <?php foreach($buttons as $button): ?>
            <div class="<?php echo esc_attr($args['item_class']); ?>">
                <?php 
                echo do_shortcode('[sfb_button id="' . $button['id'] . '" show_name="' . ($args['show_names'] ? 'true' : 'false') . '" transparent="' . ($args['transparent'] ? 'true' : 'false') . '"]');
                ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}

// Adăugare shortcode pentru a afișa toate butoanele
add_shortcode('sfb_all_buttons', function($atts) {
    $atts = shortcode_atts(array(
        'show_names' => null,
        'transparent' => null,
        'container_class' => 'sfb-shortcuts-container',
        'item_class' => 'sfb-shortcut-item'
    ), $atts);
    
    if ($atts['show_names'] !== null) {
        $atts['show_names'] = filter_var($atts['show_names'], FILTER_VALIDATE_BOOLEAN);
    }
    if ($atts['transparent'] !== null) {
        $atts['transparent'] = filter_var($atts['transparent'], FILTER_VALIDATE_BOOLEAN);
    }
    
    return sfb_display_all_shortcuts($atts);
});

// =========================
// INDIVIDUAL BUTTON SHORTCODE
// =========================
add_shortcode('sfb_button', function($atts){
    $atts = shortcode_atts([
        'id' => '',
        'class' => '',
        'style' => '',
        'show_name' => '',
        'transparent' => ''
    ], $atts);
    
    if(empty($atts['id'])) return '';
    
    $buttons = get_option('sfb_buttons', []);
    $button = null;
    
    foreach($buttons as $b) {
        if($b['id'] === $atts['id']) {
            $button = $b;
            break;
        }
    }
    
    if(!$button) return '';
    
    $href = $button['type'] === 'tel' ? 'tel:' . $button['url'] : 
           ($button['type'] === 'mailto' ? 'mailto:' . $button['url'] : $button['url']);
    
    $icon_html = isset($button['icon_type']) && $button['icon_type'] === 'html' ? 
                $button['icon'] : 
                '<span class="' . esc_attr($button['icon']) . '" aria-hidden="true"></span>';
    
    $classes = ['sfb-single-button'];
    if(!empty($atts['class'])) $classes[] = $atts['class'];
    
    $show_name = true;
    if ($atts['show_name'] !== '') {
        $show_name = filter_var($atts['show_name'], FILTER_VALIDATE_BOOLEAN);
    } else {
        $settings = get_option('sfb_settings', []);
        $show_name = isset($settings['show_shortcut_names']) ? $settings['show_shortcut_names'] : true;
    }
    
    $is_transparent = false;
    if ($atts['transparent'] !== '') {
        $is_transparent = filter_var($atts['transparent'], FILTER_VALIDATE_BOOLEAN);
    } else {
        if (!$show_name) {
            $settings = get_option('sfb_settings', []);
            $is_transparent = isset($settings['transparent_icons']) ? $settings['transparent_icons'] : false;
        }
    }
    
    if ($is_transparent && !$show_name) {
        $classes[] = 'sfb-transparent';
    }
    
    $output = sprintf(
        '<a href="%s" class="%s" style="%s" target="_blank" rel="noopener">%s%s</a>',
        esc_url($href),
        esc_attr(implode(' ', $classes)),
        esc_attr($atts['style']),
        $icon_html,
        $show_name ? esc_html($button['name']) : ''
    );
    
    return $output;
});