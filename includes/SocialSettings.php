<?php
namespace SCFS;

class SocialSettings {
    private static $instance = null;
    private $settings_name = 'scfs_social_settings';
    private $cdn_settings_name = 'scfs_cdn_settings';
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', [$this, 'admin_menu'], 20);
        add_action('admin_init', [$this, 'handle_settings_submissions']);
        add_action('admin_init', [$this, 'handle_cdn_submissions']);
        
        // Încărcare CDN în frontend
        add_action('wp_enqueue_scripts', [$this, 'load_cdn_libraries']);
    }
    
    public function load_cdn_libraries() {
        // 1. ÎNCĂRCARE FONT AWESOME GARANTATĂ
        if (!wp_style_is('font-awesome', 'enqueued') && 
            !wp_style_is('fontawesome', 'enqueued') &&
            !wp_style_is('scfs-font-awesome', 'enqueued')) {
            
            // Verifică dacă utilizatorul a configurat CDN-ul Font Awesome
            $cdn_settings_key = $this->cdn_settings_name . '_predefined';
            $predefined_settings = get_option($cdn_settings_key, []);
            
            $fontawesome_url = '';
            $use_custom_fa = false;
            
            if (!empty($predefined_settings['fontawesome'])) {
                // Folosește URL-ul configurat de utilizator
                $fontawesome_url = $predefined_settings['fontawesome'];
                $use_custom_fa = true;
            } else {
                // Folosește URL-ul default
                $fontawesome_url = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css';
            }
            
            if (!empty($fontawesome_url)) {
                wp_enqueue_style(
                    'scfs-font-awesome',
                    $fontawesome_url,
                    array(),
                    $use_custom_fa ? null : '6.4.0',
                    'all'
                );
            }
        }
        
        // 2. ÎNCĂRCARE CDN-URI PREDEFINITE
        $cdn_settings_key = $this->cdn_settings_name . '_predefined';
        $predefined_settings = get_option($cdn_settings_key, []);
        
        $predefined_cdns = $this->get_predefined_cdns();
        
        foreach ($predefined_cdns as $cdn_id => $cdn_info) {
            // Sări peste Font Awesome deja încărcat mai sus
            if ($cdn_id === 'fontawesome') continue;
            
            $cdn_url = $predefined_settings[$cdn_id] ?? '';
            
            // Dacă utilizatorul a furnizat un URL
            if (!empty($cdn_url)) {
                $handle = 'scfs-cdn-' . $cdn_id;
                
                // Verifică dacă este deja încărcat
                $is_loaded = ($cdn_info['type'] === 'css') ? 
                    wp_style_is($handle, 'enqueued') : 
                    wp_script_is($handle, 'enqueued');
                
                if (!$is_loaded) {
                    if ($cdn_info['type'] === 'css') {
                        wp_enqueue_style(
                            $handle,
                            $cdn_url,
                            array(),
                            null,
                            'all'
                        );
                        
                        // Adăugare atribut de integritate pentru CDN-uri cunoscute
                        if ($cdn_id === 'fontawesome_kit') {
                            wp_style_add_data($handle, 'crossorigin', 'anonymous');
                        }
                        
                    } elseif ($cdn_info['type'] === 'js') {
                        wp_enqueue_script(
                            $handle,
                            $cdn_url,
                            array(),
                            null,
                            false
                        );
                        
                        // Adăugare atribut pentru Font Awesome Kit
                        if ($cdn_id === 'fontawesome_kit') {
                            wp_script_add_data($handle, 'crossorigin', 'anonymous');
                        }
                    }
                }
            }
        }
        
        // 3. ÎNCĂRCARE CDN-URI CUSTOM
        $custom_cdn_key = $this->cdn_settings_name . '_custom';
        $custom_cdns = get_option($custom_cdn_key, array());
        
        foreach ($custom_cdns as $cdn_id => $cdn_data) {
            // Verifică dacă CDN-ul este activ și are URL valid
            if (isset($cdn_data['active']) && $cdn_data['active'] && 
                !empty($cdn_data['url']) && filter_var($cdn_data['url'], FILTER_VALIDATE_URL)) {
                
                $handle = 'scfs-custom-cdn-' . $cdn_id;
                $cdn_type = isset($cdn_data['type']) ? $cdn_data['type'] : 'css';
                
                // Verifică dacă este deja încărcat
                $is_loaded = ($cdn_type === 'css') ? 
                    wp_style_is($handle, 'enqueued') : 
                    wp_script_is($handle, 'enqueued');
                
                if (!$is_loaded) {
                    if ($cdn_type === 'css') {
                        wp_enqueue_style(
                            $handle,
                            esc_url_raw($cdn_data['url']),
                            array(),
                            null,
                            'all'
                        );
                        
                        // Adăugare atribut de securitate
                        wp_style_add_data($handle, 'crossorigin', 'anonymous');
                        
                    } elseif ($cdn_type === 'js') {
                        wp_enqueue_script(
                            $handle,
                            esc_url_raw($cdn_data['url']),
                            array(),
                            null,
                            false
                        );
                        
                        // Adăugare atribut de securitate
                        wp_script_add_data($handle, 'crossorigin', 'anonymous');
                    }
                }
            }
        }
        
        // 4. INLINE FALLBACK PENTRU ICONIȚE
        add_action('wp_head', function() {
            // Verifică dacă există butoane sociale pe pagină
            global $post;
            $has_shortcode = false;
            
            if ($post && isset($post->post_content)) {
                $has_shortcode = has_shortcode($post->post_content, 'scfs_social') || 
                                has_shortcode($post->post_content, 'scfs-social');
            }
            
            // Verifică dacă Font Awesome este încărcat
            $fa_loaded = wp_style_is('scfs-font-awesome', 'done') || 
                        wp_style_is('font-awesome', 'done') ||
                        wp_style_is('fontawesome', 'done');
            
            // Dacă avem butoane și Font Awesome nu este încărcat, adăugăm fallback
            if (($has_shortcode || get_option('sfb_auto_display', 0)) && !$fa_loaded) {
                // Verifică dacă există vreun CDN Font Awesome în așteptare
                $fa_in_queue = false;
                
                // Verifică toate CDN-urile încărcate
                foreach (wp_styles()->registered as $handle => $style) {
                    if (strpos($handle, 'fontawesome') !== false || 
                        strpos($handle, 'font-awesome') !== false ||
                        strpos($style->src, 'fontawesome') !== false ||
                        strpos($style->src, 'font-awesome') !== false) {
                        $fa_in_queue = true;
                        break;
                    }
                }
                
                // Dacă niciun Font Awesome nu este în așteptare, adăugăm fallback inline
                if (!$fa_in_queue) {
                    echo '<style>
                    /* Font Awesome Fallback */
                    @import url("https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css");
                    
                    /* Forțare vizibilitate iconițe */
                    .sfb-item i,
                    .scfs-inline-button i,
                    .scfs-single-button i {
                        font-family: "Font Awesome 6 Free", "Font Awesome 6 Brands", sans-serif !important;
                        font-weight: 900 !important;
                        font-style: normal !important;
                        opacity: 1 !important;
                        visibility: visible !important;
                        display: inline-flex !important;
                    }
                    
                    /* Fallback pentru browsere vechi */
                    .fa,
                    .fas,
                    .far,
                    .fal,
                    .fab {
                        font-family: "Font Awesome 6 Free", "Font Awesome 6 Brands", sans-serif !important;
                    }
                    
                    .fas,
                    .fa-solid {
                        font-weight: 900 !important;
                    }
                    
                    .far,
                    .fa-regular {
                        font-weight: 400 !important;
                    }
                    
                    .fab,
                    .fa-brands {
                        font-weight: 400 !important;
                    }
                    </style>';
                }
            }
        }, 99);
        
        // 5. VERIFICARE DEBUG
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('wp_footer', function() {
                echo '<!-- SCFS CDN Debug Info -->';
                echo '<!-- ';
                
                $loaded_styles = array();
                $loaded_scripts = array();
                
                // Colectează toate stilurile încărcate
                foreach (wp_styles()->done as $handle) {
                    $style = wp_styles()->registered[$handle] ?? null;
                    if ($style && (strpos($handle, 'scfs') !== false || 
                                   strpos($handle, 'font') !== false)) {
                        $loaded_styles[] = $handle . ': ' . ($style->src ?? 'inline');
                    }
                }
                
                // Colectează toate scripturile încărcate
                foreach (wp_scripts()->done as $handle) {
                    $script = wp_scripts()->registered[$handle] ?? null;
                    if ($script && strpos($handle, 'scfs') !== false) {
                        $loaded_scripts[] = $handle . ': ' . ($script->src ?? 'inline');
                    }
                }
                
                echo 'Loaded SCFS Styles: ' . implode(', ', $loaded_styles);
                echo ' | ';
                echo 'Loaded SCFS Scripts: ' . implode(', ', $loaded_scripts);
                
                echo ' -->';
            }, 999);
        }
    }
    
    private function get_cdn_info_by_id($cdn_id) {
        $predefined_cdns = $this->get_predefined_cdns();
        return $predefined_cdns[$cdn_id] ?? null;
    }
    
    private function get_predefined_cdns() {
        return [
            'fontawesome' => [
                'name' => 'Font Awesome (CSS CDN)',
                'default_url' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
                'docs' => 'https://fontawesome.com/icons',
                'type' => 'css',
                'integrity' => 'sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==',
                'crossorigin' => 'anonymous'
            ],
            'fontawesome_kit' => [
                'name' => 'Font Awesome Kit (JS)',
                'default_url' => 'https://kit.fontawesome.com/YOUR_KIT_ID.js',
                'docs' => 'https://fontawesome.com/kits',
                'type' => 'js',
                'crossorigin' => 'anonymous'
            ],
            'feather' => [
                'name' => 'Feather Icons',
                'default_url' => 'https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.css',
                'docs' => 'https://feathericons.com/',
                'type' => 'css',
                'integrity' => 'sha512-8f3Pc8sL7zU7Q0O5q6Q5Q5Q5Q5Q5Q5Q5Q5Q5',
                'crossorigin' => 'anonymous'
            ],
            'material' => [
                'name' => 'Material Icons',
                'default_url' => 'https://fonts.googleapis.com/icon?family=Material+Icons',
                'docs' => 'https://fonts.google.com/icons',
                'type' => 'css',
                'crossorigin' => 'anonymous'
            ],
            'heroicons' => [
                'name' => 'Heroicons',
                'default_url' => 'https://cdn.jsdelivr.net/npm/heroicons@1.0.1/outline/style.css',
                'docs' => 'https://heroicons.com/',
                'type' => 'css',
                'integrity' => 'sha512-8f3Pc8sL7zU7Q0O5q6Q5Q5Q5Q5Q5Q5Q5Q5Q5',
                'crossorigin' => 'anonymous'
            ],
            'bootstrap_icons' => [
                'name' => 'Bootstrap Icons',
                'default_url' => 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css',
                'docs' => 'https://icons.getbootstrap.com/',
                'type' => 'css',
                'integrity' => 'sha512-8f3Pc8sL7zU7Q0O5q6Q5Q5Q5Q5Q5Q5Q5Q5Q5',
                'crossorigin' => 'anonymous'
            ],
            'remixicon' => [
                'name' => 'Remix Icon',
                'default_url' => 'https://cdn.jsdelivr.net/npm/remixicon@3.2.0/fonts/remixicon.css',
                'docs' => 'https://remixicon.com/',
                'type' => 'css',
                'integrity' => 'sha512-8f3Pc8sL7zU7Q0O5q6Q5Q5Q5Q5Q5Q5Q5Q5Q5',
                'crossorigin' => 'anonymous'
            ],
            'boxicons' => [
                'name' => 'Boxicons',
                'default_url' => 'https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css',
                'docs' => 'https://boxicons.com/',
                'type' => 'css',
                'integrity' => 'sha512-8f3Pc8sL7zU7Q0O5q6Q5Q5Q5Q5Q5Q5Q5Q5Q5',
                'crossorigin' => 'anonymous'
            ]
        ];
    }
    
    public function admin_menu() {
        add_submenu_page(
            'scfs-oop',
            'Social Settings',
            'Social Settings',
            'manage_options',
            'scfs-social-settings',
            [$this, 'settings_page']
        );

        add_submenu_page(
            'scfs-oop',
            'CDN Libraries',
            'CDN Libraries',
            'manage_options',
            'scfs-social-cdn',
            [$this, 'cdn_page']
        );
    }

    public function handle_settings_submissions() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'scfs-social-settings') {
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['scfs_save_settings'])) {
            check_admin_referer('scfs_save_settings');
            
            $settings = [
                'position' => sanitize_text_field($_POST['position']),
                'button_color' => sanitize_text_field($_POST['button_color']),
                'button_icon' => sanitize_text_field($_POST['button_icon']),
                'animation' => sanitize_text_field($_POST['animation']),
                'mobile_enabled' => isset($_POST['mobile_enabled']) ? 1 : 0,
                'show_names' => isset($_POST['show_names']) ? 1 : 0,
                'transparent_icons' => isset($_POST['transparent_icons']) ? 1 : 0,
                'custom_message' => sanitize_text_field($_POST['custom_message']),
                'show_custom_message' => isset($_POST['show_custom_message']) ? 1 : 0,
                'show_shortcut_names' => isset($_POST['show_shortcut_names']) ? 1 : 0
            ];
            
            // Salvează în wp_options
            update_option($this->settings_name, $settings);
            
            wp_redirect(admin_url('admin.php?page=scfs-social-settings&message=' . urlencode('Settings saved successfully!')));
            exit;
        }
    }
    
    public function handle_cdn_submissions() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'scfs-social-cdn') {
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['scfs_add_cdn'])) {
                check_admin_referer('scfs_add_cdn');
                
                $cdn_name = sanitize_text_field($_POST['cdn_name']);
                $cdn_type = sanitize_text_field($_POST['cdn_type']);
                $cdn_url = esc_url_raw($_POST['cdn_url']);
                
                if (!empty($cdn_name) && !empty($cdn_type) && !empty($cdn_url)) {
                    $cdn_settings_key = $this->cdn_settings_name . '_custom';
                    $cdns = get_option($cdn_settings_key, []);
                    
                    $cdn_id = AjaxHandler::slugify($cdn_name) . '_' . uniqid();
                    $cdns[$cdn_id] = [
                        'name' => $cdn_name,
                        'type' => $cdn_type,
                        'url' => $cdn_url,
                        'active' => true
                    ];
                    
                    update_option($cdn_settings_key, $cdns);
                    
                    wp_redirect(admin_url('admin.php?page=scfs-social-cdn&message=' . urlencode('CDN added successfully!')));
                    exit;
                }
            }
            
            if (isset($_POST['scfs_save_predefined_cdn'])) {
                $cdn_id = sanitize_text_field($_POST['cdn_id']);
                check_admin_referer('save_cdn_' . $cdn_id);
                
                $cdn_url = esc_url_raw($_POST['cdn_url']);
                $cdn_settings_key = $this->cdn_settings_name . '_predefined';
                $settings = get_option($cdn_settings_key, []);
                
                if (empty($cdn_url)) {
                    unset($settings[$cdn_id]);
                } else {
                    $settings[$cdn_id] = $cdn_url;
                }
                
                update_option($cdn_settings_key, $settings);
                
                wp_redirect(admin_url('admin.php?page=scfs-social-cdn&message=' . urlencode('CDN settings updated!')));
                exit;
            }
        }
    }
    
    public function settings_page() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'scfs-social-settings') {
            return;
        }
        
        $message = $_GET['message'] ?? '';
        
        // Obține setările cu valori default complete
        $settings = $this->get_settings();
        
        echo '<div class="wrap scfs-admin">';
        
        // Breadcrumb
        echo '<nav class="scfs-breadcrumb">';
        echo '<a href="' . admin_url('admin.php?page=scfs-oop') . '">Dashboard</a> &raquo; ';
        echo '<span>Social Settings</span>';
        echo '</nav>';
        
        if ($message) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html(urldecode($message)) . '</p></div>';
        }
        
        ?>
        <h1>Social Settings</h1>
        
        <div class="sfb-settings-page">
            <form method="post">
                <?php wp_nonce_field('scfs_save_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="custom_message">Custom Message</label></th>
                        <td>
                            <input type="text" name="custom_message" id="custom_message" 
                                   value="<?php echo esc_attr($settings['custom_message']); ?>" 
                                   class="regular-text" placeholder="Let's chat with US!">
                            <p class="description">This message will be displayed when hovering the floating button</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="show_custom_message">Display Custom Message</label></th>
                        <td>
                            <label>
                                <input type="checkbox" name="show_custom_message" id="show_custom_message" value="1" <?php checked($settings['show_custom_message'], 1); ?>>
                                Show custom message tooltip
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="show_shortcut_names">Shortcut Display</label></th>
                        <td>
                            <label>
                                <input type="checkbox" name="show_shortcut_names" id="show_shortcut_names" value="1" <?php checked($settings['show_shortcut_names'], 1); ?>>
                                Show button names in shortcuts
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="position">Button Position</label></th>
                        <td>
                            <select name="position" id="position">
                                <option value="right" <?php selected($settings['position'], 'right'); ?>>Bottom Right</option>
                                <option value="left" <?php selected($settings['position'], 'left'); ?>>Bottom Left</option>
                                <option value="top-right" <?php selected($settings['position'], 'top-right'); ?>>Top Right</option>
                                <option value="top-left" <?php selected($settings['position'], 'top-left'); ?>>Top Left</option>
                            </select>
                            <p class="description">Select where the floating button should appear</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="button_color">Main Button Color</label></th>
                        <td>
                            <input type="color" name="button_color" id="button_color" value="<?php echo esc_attr($settings['button_color']); ?>">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="button_icon">Button Icon</label></th>
                        <td>
                            <input type="text" name="button_icon" id="button_icon" value="<?php echo esc_attr($settings['button_icon']); ?>" class="regular-text">
                            <p class="description">Enter icon character or HTML code (e.g., ☰, ⚙️, <i class="fas fa-bars"></i>)</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="animation">Animation Type</label></th>
                        <td>
                            <select name="animation" id="animation">
                                <option value="slide" <?php selected($settings['animation'], 'slide'); ?>>Slide</option>
                                <option value="fade" <?php selected($settings['animation'], 'fade'); ?>>Fade</option>
                                <option value="scale" <?php selected($settings['animation'], 'scale'); ?>>Scale</option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="show_names">Display Style</label></th>
                        <td>
                            <label>
                                <input type="checkbox" name="show_names" id="show_names" value="1" <?php checked($settings['show_names'], 1); ?>>
                                Show button names next to icons
                            </label>
                        </td>
                    </tr>
                    
                    <tr id="transparent_icons_row" style="<?php echo $settings['show_names'] ? 'display: none;' : ''; ?>">
                        <th scope="row"><label for="transparent_icons">Transparent Icons</label></th>
                        <td>
                            <label>
                                <input type="checkbox" name="transparent_icons" id="transparent_icons" value="1" <?php checked($settings['transparent_icons'], 1); ?>>
                                Show icons without background and border
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="mobile_enabled">Mobile Display</label></th>
                        <td>
                            <label>
                                <input type="checkbox" name="mobile_enabled" id="mobile_enabled" value="1" <?php checked($settings['mobile_enabled'], 1); ?>>
                                Enable on mobile devices
                            </label>
                        </td>
                    </tr>
                </table>

                
                <?php submit_button('Save Settings', 'primary', 'scfs_save_settings'); ?>
            </form>
            
            <div class="sfb-preview">
                <h3>🎨 Preview</h3>
                
                <div class="sfb-preview-main" style="background-color: <?php echo esc_attr($settings['button_color']); ?>;">
                    <span class="sfb-preview-icon"><?php echo $settings['button_icon']; ?></span>
                </div>
                
                <?php if($settings['show_custom_message']): ?>
                <div class="sfb-custom-message-preview">
                    <strong>Custom Message:</strong> "<?php echo esc_html($settings['custom_message']); ?>"
                </div>
                <?php endif; ?>
                
                <div class="sfb-preview-items">
                    <?php if($settings['show_names']): ?>
                        <div class="sfb-preview-item with-names">
                            <span class="sfb-preview-icon">🔗</span>
                            <span class="sfb-preview-text">Example Button</span>
                        </div>
                    <?php else: ?>
                        <div class="sfb-preview-item icons-only <?php echo ($settings['transparent_icons'] && !$settings['show_names']) ? 'transparent' : ''; ?>">
                            <span class="sfb-preview-icon">🔗</span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <p class="description">
                    Position: <strong><?php echo esc_html($settings['position']); ?></strong>
                </p>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#show_names').change(function() {
                if ($(this).is(':checked')) {
                    $('#transparent_icons_row').hide();
                    $('#transparent_icons').prop('checked', false);
                } else {
                    $('#transparent_icons_row').show();
                }
            });
        });
        </script>
        <?php
        
        echo '</div>';
    }
    
    public function cdn_page() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'scfs-social-cdn') {
            return;
        }
        
        $message = $_GET['message'] ?? '';
        $predefined_cdns = $this->get_predefined_cdns();
        
        $cdn_custom_key = $this->cdn_settings_name . '_custom';
        $cdn_predefined_key = $this->cdn_settings_name . '_predefined';
        
        $custom_cdns = get_option($cdn_custom_key, []);
        $predefined_settings = get_option($cdn_predefined_key, []);
        
        echo '<div class="wrap scfs-admin">';
        
        // Breadcrumb
        echo '<nav class="scfs-breadcrumb">';
        echo '<a href="' . admin_url('admin.php?page=scfs-oop') . '">Dashboard</a> &raquo; ';
        echo '<span>CDN Libraries</span>';
        echo '</nav>';
        
        if ($message) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html(urldecode($message)) . '</p></div>';
        }
        
        ?>
        <h1>CDN Libraries</h1>
        
        <?php if (isset($this->use_database) && $this->use_database): ?>
        <div class="notice notice-info">
            <p>📊 Datele sunt stocate în baza de date separată (tabela <?php echo $GLOBALS['wpdb']->prefix; ?>scfs)</p>
        </div>
        <?php endif; ?>
        
        <div class="sfb-cdn-management">
            <!-- Inline Collapsible Form -->
            <div class="sfb-cdn-form-inline collapsed">
                <div class="sfb-cdn-form-header">
                    <h3><span class="dashicons dashicons-plus"></span> Add New CDN</h3>
                    <a href="#" class="sfb-cdn-form-toggle">Expand form to add CDN</a>
                </div>
                <div class="sfb-cdn-form-content">
                    <form method="post" class="sfb-cdn-form">
                        <?php wp_nonce_field('scfs_add_cdn'); ?>
                        <div class="sfb-cdn-form-row">
                            <label for="cdn_name">CDN Name</label>
                            <input type="text" id="cdn_name" name="cdn_name" required 
                                   placeholder="Enter CDN name (e.g., Bootstrap Icons)">
                            <div class="sfb-cdn-form-error"></div>
                        </div>
                        <div class="sfb-cdn-form-row">
                            <label for="cdn_type">CDN Type</label>
                            <select name="cdn_type" id="cdn_type" required>
                                <option value="css">CSS Stylesheet</option>
                                <option value="js">JavaScript</option>
                            </select>
                        </div>
                        <div class="sfb-cdn-form-row">
                            <label for="cdn_url">CDN URL</label>
                            <input type="url" id="cdn_url" name="cdn_url" required 
                                   placeholder="https://cdn.example.com/library.css" 
                                   pattern="https?://.+" 
                                   title="Enter a valid URL starting with http:// or https://">
                            <div class="sfb-cdn-form-error"></div>
                        </div>
                        <div class="sfb-cdn-form-actions">
                            <?php submit_button('Add CDN', 'primary', 'scfs_add_cdn'); ?>
                            <button type="button" class="button button-secondary sfb-cdn-form-clear">Clear</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="sfb-cdn-section">
                <h3>📚 Predefined CDN Libraries</h3>
                
                <div class="sfb-cdn-search">
                    <input type="search" placeholder="Search CDN libraries...">
                </div>
                
                <div class="sfb-cdn-flex">
                    <?php 
                    foreach($predefined_cdns as $cdn_id => $cdn): 
                        $is_active = !empty($predefined_settings[$cdn_id]);
                        $current_url = $predefined_settings[$cdn_id] ?? '';
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
                                        <label>CDN URL: 
                                            <a href="<?php echo esc_url($cdn['docs']); ?>" target="_blank" class="sfb-docs-link" title="Open documentation">
                                                <span class="dashicons dashicons-external"></span>
                                            </a>
                                        </label>
                                        <input type="url" name="cdn_url" value="<?php echo esc_url($current_url); ?>" 
                                               placeholder="<?php echo esc_attr($cdn['default_url']); ?>" class="regular-text">
                                        <p class="description">Leave empty to disable this CDN</p>
                                    </div>
                                    <div class="sfb-cdn-actions">
                                        <button type="submit" name="scfs_save_predefined_cdn" class="button button-primary">
                                            <?php echo $is_active ? 'Update' : 'Activate'; ?>
                                        </button>
                                        <?php if($is_active): ?>
                                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=scfs-social-cdn&disable_cdn=' . $cdn_id), 'disable_cdn_' . $cdn_id); ?>" class="button button-secondary">
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
                    <div class="sfb-cdn-empty-state">
                        <span class="dashicons dashicons-download"></span>
                        <h3>No custom CDNs added yet</h3>
                        <p>Use the form above to add your first custom CDN library.</p>
                    </div>
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
                                        <td>
                                            <code title="<?php echo esc_attr($cdn['url']); ?>">
                                                <?php echo esc_html(strlen($cdn['url']) > 50 ? substr($cdn['url'], 0, 47) . '...' : $cdn['url']); ?>
                                            </code>
                                        </td>
                                        <td>
                                            <span class="sfb-status-badge <?php echo $cdn['active'] ? 'active' : 'inactive'; ?>">
                                                <?php echo $cdn['active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=scfs-social-cdn&toggle_cdn=' . $cdn_id), 'toggle_cdn_' . $cdn_id); ?>" class="button button-small">
                                                <?php echo $cdn['active'] ? 'Deactivate' : 'Activate'; ?>
                                            </a>
                                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=scfs-social-cdn&delete_cdn=' . $cdn_id), 'delete_cdn_' . $cdn_id); ?>" class="button button-small button-danger" onclick="return confirm('Are you sure you want to delete this CDN?')">
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
        
        <script>
        jQuery(document).ready(function($) {
            // Toggle form collapse
            $('.sfb-cdn-form-toggle').click(function(e) {
                e.preventDefault();
                var $form = $(this).closest('.sfb-cdn-form-inline');
                $form.toggleClass('collapsed');
                $(this).text($form.hasClass('collapsed') ? 'Expand form to add CDN' : 'Collapse form');
                
                localStorage.setItem('scfs_cdn_form_collapsed', $form.hasClass('collapsed'));
            });
            
            // Clear form
            $('.sfb-cdn-form-clear').click(function() {
                $(this).closest('form').find('input[type="text"], input[type="url"]').val('');
            });
            
            // URL validation
            $('input[type="url"]').on('blur', function() {
                var url = $(this).val();
                if (url && !url.match(/^https?:\/\//)) {
                    $(this).val('https://' + url);
                }
            });
            
            // Search functionality
            $('.sfb-cdn-search input').on('keyup', function() {
                var searchTerm = $(this).val().toLowerCase();
                
                $('.sfb-cdn-flex .sfb-cdn-card').each(function() {
                    var $card = $(this);
                    var text = $card.text().toLowerCase();
                    
                    if (text.indexOf(searchTerm) > -1) {
                        $card.show();
                    } else {
                        $card.hide();
                    }
                });
            });
        });
        </script>
        
        <?php
        
        echo '</div>';
    }
    
    public function get_settings() {
        $default_settings = [
            'position' => 'right',
            'button_color' => '#0073aa',
            'button_icon' => '☰',
            'animation' => 'slide',
            'mobile_enabled' => 1,
            'show_names' => 1,
            'transparent_icons' => 0,
            'custom_message' => "Let`s chat with US!",
            'show_custom_message' => 1,
            'show_shortcut_names' => 1
        ];
        
        $saved_settings = get_option($this->settings_name, []);
        
        return array_merge($default_settings, $saved_settings);
    }
}