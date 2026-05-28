<?php
/**
 * Mobile Smart Bar Module for SCFS Plugin
 * 
 * @package SCFS
 */

namespace SCFS;

if (!defined('ABSPATH')) exit;

class MobileSmartBar {

    private static $instance = null;
    private $option_name = 'scfs_mobile_smart_bar';
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_footer', [$this, 'render_bar']);
        add_action('admin_enqueue_scripts', [$this, 'admin_assets']);
        add_action('wp_enqueue_scripts', [$this, 'frontend_assets']);
    }
    
    public function admin_assets($hook) {
        if (strpos($hook, 'scfs-mobile-bar') === false) {
            return;
        }
        
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_style('dashicons');
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        ?>
        <style>
            @import url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');
            @import url("https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css");
            
            .scfs-msb-wrap {
                background: #f1f1f1;
                min-height: 100vh;
                margin: -20px -20px 0 -20px;
                padding: 20px;
            }
            .scfs-msb-container {
                max-width: 1400px;
                margin: 0 auto;
            }
            .scfs-msb-header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 25px 30px;
                border-radius: 15px;
                margin-bottom: 25px;
            }
            .scfs-msb-header h1 {
                color: white;
                margin: 0 0 8px;
                font-size: 28px;
            }
            .scfs-msb-main-grid {
                display: flex;
                gap: 25px;
                flex-wrap: wrap;
            }
            .scfs-msb-form {
                flex: 2;
                min-width: 300px;
            }
            .scfs-msb-sidebar {
                flex: 1;
                min-width: 280px;
            }
            .scfs-card {
                background: white;
                border-radius: 12px;
                padding: 20px;
                margin-bottom: 20px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            .scfs-card h3 {
                margin: 0 0 15px;
                padding-bottom: 10px;
                border-bottom: 2px solid #eee;
            }
            .scfs-color-row {
                display: flex;
                gap: 15px;
                flex-wrap: wrap;
                margin-bottom: 15px;
            }
            .scfs-color-field {
                flex: 1;
                min-width: 120px;
            }
            .scfs-color-field label {
                display: block;
                margin-bottom: 5px;
                font-weight: 500;
                font-size: 12px;
            }
            .scfs-color-field input {
                width: 100%;
            }
            .custom-colors-section {
                display: none;
            }
            .custom-colors-section.visible {
                display: block;
            }
            .scfs-items-container {
                margin-top: 20px;
            }
            .scfs-item-row {
                background: #fafafa;
                border: 1px solid #e5e5e5;
                border-radius: 10px;
                padding: 15px;
                margin-bottom: 15px;
                display: flex;
                flex-wrap: wrap;
                gap: 15px;
                align-items: flex-start;
                position: relative;
            }
            .scfs-item-fields {
                flex: 1;
                display: flex;
                flex-wrap: wrap;
                gap: 15px;
            }
            .scfs-item-field {
                flex: 1;
                min-width: 150px;
            }
            .scfs-item-field label {
                display: block;
                margin-bottom: 5px;
                font-weight: 500;
                font-size: 12px;
            }
            .scfs-item-field input, .scfs-item-field select {
                width: 100%;
                padding: 8px 10px;
                border: 1px solid #ddd;
                border-radius: 6px;
                font-size: 13px;
            }
            .scfs-item-field textarea {
                width: 100%;
                padding: 8px 10px;
                border: 1px solid #ddd;
                border-radius: 6px;
                font-size: 13px;
                font-family: monospace;
                resize: vertical;
            }
            .scfs-remove-item {
                background: #dc3545;
                color: white;
                border: none;
                padding: 8px 15px;
                border-radius: 6px;
                cursor: pointer;
                font-size: 12px;
                align-self: center;
            }
            .scfs-remove-item:hover {
                background: #c82333;
            }
            .scfs-remove-item.disabled {
                opacity: 0.5;
                cursor: not-allowed;
            }
            .scfs-add-item {
                background: #28a745;
                color: white;
                border: none;
                padding: 10px 20px;
                border-radius: 6px;
                cursor: pointer;
                margin-top: 15px;
            }
            .scfs-add-item:hover {
                background: #218838;
            }
            .scfs-icon-preview {
                background: #f0f0f0;
                padding: 8px;
                border-radius: 6px;
                margin-top: 8px;
                text-align: center;
                font-size: 24px;
            }
            .scfs-note {
                background: #fff3cd;
                border-left: 4px solid #ffc107;
                padding: 10px 15px;
                margin-top: 15px;
                font-size: 12px;
            }
            .scfs-css-editor {
                font-family: monospace;
                background: #1e1e1e;
                color: #d4d4d4;
            }
            @media (max-width: 768px) {
                .scfs-msb-main-grid {
                    flex-direction: column;
                }
                .scfs-item-fields {
                    flex-direction: column;
                }
                .scfs-color-row {
                    flex-direction: column;
                }
            }
        </style>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof jQuery !== 'undefined') {
                (function($) {
                    // Initialize color pickers
                    $('.scfs-color-picker').wpColorPicker();
                    
                    // Toggle custom colors section based on theme selection
                    function toggleCustomColors() {
                        if ($('select[name="scfs_mobile_smart_bar[theme]"]').val() === 'custom') {
                            $('.custom-colors-section').addClass('visible');
                        } else {
                            $('.custom-colors-section').removeClass('visible');
                        }
                    }
                    
                    $('select[name="scfs_mobile_smart_bar[theme]"]').on('change', toggleCustomColors);
                    toggleCustomColors();
                    
                    // Add new button
                    $('#scfs-add-item').on('click', function() {
                        var newId = 'new_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5);
                        var $template = $('.scfs-item-template').clone();
                        $template.removeClass('scfs-item-template').addClass('scfs-item-row');
                        $template.find('.show-field').attr('name', 'scfs_mobile_smart_bar[items][' + newId + '][show]');
                        $template.find('.label-field').attr('name', 'scfs_mobile_smart_bar[items][' + newId + '][label]');
                        $template.find('.url-field').attr('name', 'scfs_mobile_smart_bar[items][' + newId + '][url]');
                        $template.find('.position-field').attr('name', 'scfs_mobile_smart_bar[items][' + newId + '][position]');
                        $template.find('.icon-field').attr('name', 'scfs_mobile_smart_bar[items][' + newId + '][icon]');
                        $template.find('.order-field').attr('name', 'scfs_mobile_smart_bar[items][' + newId + '][order]');
                        $template.find('.show-field').prop('checked', true);
                        $template.find('.label-field').val('');
                        $template.find('.url-field').val('');
                        $template.find('.icon-field').val('');
                        $template.find('.icon-preview').html('');
                        $template.css('display', 'flex');
                        $('.scfs-items-container').append($template);
                        
                        var $deleteBtn = $template.find('.scfs-remove-item');
                        $deleteBtn.addClass('disabled');
                        $deleteBtn.prop('disabled', true);
                    });
                    
                    // Delete button
                    $(document).on('click', '.scfs-remove-item', function() {
                        var $row = $(this).closest('.scfs-item-row');
                        var isEnabled = $row.find('.show-field').is(':checked');
                        
                        if (isEnabled) {
                            alert('Please disable the button first before deleting it!');
                            return;
                        }
                        
                        if (confirm('Are you sure you want to delete this button?')) {
                            $row.remove();
                        }
                    });
                    
                    // Validate only one center position
                    $(document).on('change', '.position-field', function() {
                        var $select = $(this);
                        var selectedValue = $select.val();
                        
                        if (selectedValue === 'center') {
                            var centerCount = $('.position-field').filter(function() {
                                return $(this).val() === 'center';
                            }).length;
                            
                            if (centerCount > 1) {
                                alert('You can only have ONE button with Center position!');
                                $select.val('left');
                            }
                        }
                    });
                    
                    // Disable delete for enabled buttons
                    $(document).on('change', '.show-field', function() {
                        var $row = $(this).closest('.scfs-item-row');
                        var $deleteBtn = $row.find('.scfs-remove-item');
                        
                        if ($(this).is(':checked')) {
                            $deleteBtn.addClass('disabled');
                            $deleteBtn.prop('disabled', true);
                        } else {
                            $deleteBtn.removeClass('disabled');
                            $deleteBtn.prop('disabled', false);
                        }
                    });
                    
                    // Live icon preview
                    $(document).on('input', '.icon-field', function() {
                        var iconValue = $(this).val();
                        var $preview = $(this).closest('.scfs-item-field').find('.icon-preview');
                        if (iconValue.trim()) {
                            $preview.html(iconValue);
                        } else {
                            $preview.html('No icon');
                        }
                    });
                    
                    // Initialize delete buttons state and previews
                    $('.scfs-item-row').each(function() {
                        var $row = $(this);
                        var $deleteBtn = $row.find('.scfs-remove-item');
                        if ($row.find('.show-field').is(':checked')) {
                            $deleteBtn.addClass('disabled');
                            $deleteBtn.prop('disabled', true);
                        }
                        
                        var iconValue = $row.find('.icon-field').val();
                        if (iconValue && iconValue.trim()) {
                            $row.find('.icon-preview').html(iconValue);
                        }
                    });
                })(jQuery);
            } else {
                var script = document.createElement('script');
                script.src = 'https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js';
                script.onload = function() {
                    location.reload();
                };
                document.head.appendChild(script);
            }
        });
        </script>
        <?php
    }
    
    public function frontend_assets() {
        if (!$this->is_enabled()) {
            return;
        }
        
        wp_enqueue_style('dashicons');
        $options = $this->get_options();
        
        $custom_css = '';
        
        // CSS personalizat - se aplică doar când tema este "custom"
        if ($options['theme'] === 'custom') {
            $custom_css .= "
                .scfs-mobile-bar {
                    background: {$options['bg_color']} !important;
                    border-radius: {$options['border_radius']}px !important;
                    box-shadow: 0 5px 25px {$options['shadow_color']} !important;
                }
                .scfs-mobile-bar .scfs-mobile-item {
                    color: {$options['text_color']} !important;
                }
                .scfs-mobile-bar .scfs-mobile-item:hover {
                    color: {$options['hover_color']} !important;
                    transform: translateY(-3px) !important;
                }
                .scfs-mobile-center {
                    background: {$options['center_bg_color']} !important;
                    box-shadow: 0 5px 15px {$options['center_shadow_color']} !important;
                }
                .scfs-mobile-center:hover {
                    background: {$options['center_hover_color']} !important;
                    transform: scale(1.08) !important;
                }
            ";
        }
        
        // Adaugă CSS-ul personalizat scris de utilizator
        if (!empty($options['custom_css'])) {
            $custom_css .= "\n" . $options['custom_css'];
        }
        ?>
        <style>
            .scfs-mobile-bar {
                position: fixed !important;
                bottom: 20px !important;
                left: 20px !important;
                right: 20px !important;
                border-radius: 60px !important;
                padding: 8px 20px !important;
                display: flex !important;
                justify-content: space-between !important;
                align-items: center !important;
                gap: 15px !important;
                z-index: 9999 !important;
                box-shadow: 0 5px 25px rgba(0,0,0,0.15) !important;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
                transition: all 0.3s ease !important;
                background: white !important;
                margin-bottom: env(safe-area-inset-bottom, 0px) !important;
            }
            
            body {
                margin-bottom: 80px !important;
            }
            
            .scfs-mobile-bar.light {
                background: white !important;
            }
            .scfs-mobile-bar.light .scfs-mobile-item {
                color: #333 !important;
            }
            
            .scfs-mobile-bar.dark {
                background: #1a1a1a !important;
            }
            .scfs-mobile-bar.dark .scfs-mobile-item {
                color: white !important;
            }
            
            .scfs-mobile-bar.glass {
                background: rgba(255,255,255,0.95) !important;
                backdrop-filter: blur(10px) !important;
            }
            .scfs-mobile-bar.glass .scfs-mobile-item {
                color: #333 !important;
            }
            
            @media (prefers-color-scheme: dark) {
                .scfs-mobile-bar {
                    background: #1a1a1a !important;
                }
                .scfs-mobile-bar .scfs-mobile-item {
                    color: white !important;
                }
            }
            
            .scfs-mobile-item {
                flex: 1 !important;
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                gap: 5px !important;
                text-decoration: none !important;
                transition: transform 0.2s, color 0.2s !important;
                font-size: 11px !important;
                font-weight: 500 !important;
            }
            .scfs-mobile-item:hover {
                transform: translateY(-3px) !important;
            }
            
            .scfs-mobile-icon {
                display: block !important;
                line-height: 1 !important;
            }
            
            .scfs-mobile-center {
                width: 60px !important;
                height: 60px !important;
                background: #25d366 !important;
                border-radius: 50% !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                text-decoration: none !important;
                margin-top: -35px !important;
                box-shadow: 0 5px 15px rgba(37,211,102,0.4) !important;
                transition: transform 0.2s, background 0.2s !important;
                position: relative !important;
                z-index: 10 !important;
            }
            
            .scfs-mobile-center .scfs-mobile-icon {
                font-size: 28px !important;
            }
            
            <?php if ($options['center_pulse']): ?>
            .scfs-mobile-center::before {
                content: '' !important;
                position: absolute !important;
                top: 50% !important;
                left: 50% !important;
                transform: translate(-50%, -50%) !important;
                width: 100% !important;
                height: 100% !important;
                background: rgba(37, 211, 102, 0.4) !important;
                border-radius: 50% !important;
                animation: pulse-ring 1.5s ease-out infinite !important;
                z-index: -1 !important;
            }
            
            .scfs-mobile-center::after {
                content: '' !important;
                position: absolute !important;
                top: 50% !important;
                left: 50% !important;
                transform: translate(-50%, -50%) !important;
                width: 100% !important;
                height: 100% !important;
                background: rgba(37, 211, 102, 0.2) !important;
                border-radius: 50% !important;
                animation: pulse-ring 1.5s ease-out 0.5s infinite !important;
                z-index: -2 !important;
            }
            
            @keyframes pulse-ring {
                0% {
                    width: 100%;
                    height: 100%;
                    opacity: 0.8;
                }
                100% {
                    width: 180%;
                    height: 180%;
                    opacity: 0;
                }
            }
            <?php endif; ?>
            
            .scfs-mobile-center:hover {
                transform: scale(1.08) !important;
            }
            
            @media (min-width: 769px) {
                body {
                    margin-bottom: 0 !important;
                }
                .scfs-mobile-bar {
                    display: none !important;
                }
            }
            
            @media (max-width: 480px) {
                .scfs-mobile-bar {
                    bottom: 10px !important;
                    left: 10px !important;
                    right: 10px !important;
                    padding: 8px 15px !important;
                    gap: 10px !important;
                }
                .scfs-mobile-center {
                    width: 52px !important;
                    height: 52px !important;
                    margin-top: -31px !important;
                }
                .scfs-mobile-center .scfs-mobile-icon {
                    font-size: 26px !important;
                }
                .scfs-mobile-item {
                    gap: 3px !important;
                }
                .scfs-mobile-item .scfs-mobile-icon {
                    font-size: 20px !important;
                }
                .scfs-mobile-item span {
                    font-size: 10px !important;
                    line-height: 1.2 !important;
                }
                body {
                    margin-bottom: 70px !important;
                }
            }
            
            @media (min-width: 481px) and (max-width: 640px) {
                .scfs-mobile-bar {
                    padding: 10px 18px !important;
                    gap: 12px !important;
                }
                .scfs-mobile-item .scfs-mobile-icon {
                    font-size: 22px !important;
                }
                .scfs-mobile-item span {
                    font-size: 10px !important;
                }
                body {
                    margin-bottom: 80px !important;
                }
            }
            
            @media (min-width: 641px) and (max-width: 768px) {
                .scfs-mobile-item .scfs-mobile-icon {
                    font-size: 24px !important;
                }
                .scfs-mobile-item span {
                    font-size: 11px !important;
                }
                body {
                    margin-bottom: 80px !important;
                }
            }
            
            <?php echo $custom_css; ?>
        </style>
        <?php
    }
    
    public function admin_menu() {
        add_submenu_page(
            'scfs-oop',
            'Mobile Smart Bar',
            'Mobile Bar',
            'manage_options',
            'scfs-mobile-bar',
            array($this, 'admin_page')
        );
    }
    
    public function register_settings() {
        register_setting(
            'scfs_msb_group',
            $this->option_name,
            array(
                'type' => 'array',
                'sanitize_callback' => array($this, 'sanitize_settings'),
                'default' => $this->get_defaults()
            )
        );
    }
    
    private function get_defaults() {
        return array(
            'enabled' => false,
            'theme' => 'auto',
            'custom_css' => '',
            'bg_color' => '#ffffff',
            'text_color' => '#333333',
            'hover_color' => '#667eea',
            'shadow_color' => 'rgba(0,0,0,0.15)',
            'center_bg_color' => '#25d366',
            'center_hover_color' => '#1da15b',
            'center_shadow_color' => 'rgba(37,211,102,0.4)',
            'border_radius' => '60',
            'center_pulse' => true,
            'items' => array(
                'item_1' => array(
                    'show' => true,
                    'label' => 'Messenger',
                    'url' => 'https://m.me/',
                    'icon' => '💬',
                    'position' => 'left',
                    'order' => 0
                ),
                'item_2' => array(
                    'show' => true,
                    'label' => 'WhatsApp',
                    'url' => 'https://wa.me/',
                    'icon' => '💚',
                    'position' => 'right',
                    'order' => 2
                ),
                'item_3' => array(
                    'show' => true,
                    'label' => 'Call',
                    'url' => 'tel:',
                    'icon' => '📞',
                    'position' => 'center',
                    'order' => 1
                )
            )
        );
    }
    
    private function get_options() {
        $options = get_option($this->option_name, $this->get_defaults());
        if (!is_array($options)) {
            $options = $this->get_defaults();
        }
        if (!isset($options['items']) || empty($options['items'])) {
            $options['items'] = $this->get_defaults()['items'];
        }
        // Setează valorile implicite pentru câmpurile noi dacă nu există
        if (!isset($options['shadow_color'])) {
            $options['shadow_color'] = 'rgba(0,0,0,0.15)';
        }
        if (!isset($options['center_shadow_color'])) {
            $options['center_shadow_color'] = 'rgba(37,211,102,0.4)';
        }
        if (!isset($options['center_pulse'])) {
            $options['center_pulse'] = true;
        }
        return $options;
    }
    
    private function is_enabled() {
        $options = $this->get_options();
        return !empty($options['enabled']) && wp_is_mobile();
    }
    
    private function get_theme_class() {
        $options = $this->get_options();
        $theme = $options['theme'];
        return ($theme === 'auto') ? '' : $theme;
    }
    
    public function sanitize_settings($input) {
        if (!is_array($input)) {
            return $this->get_defaults();
        }
        
        $output = array(
            'enabled' => !empty($input['enabled']),
            'theme' => sanitize_text_field($input['theme']),
            'custom_css' => wp_kses_post($input['custom_css']),
            'bg_color' => sanitize_hex_color($input['bg_color']),
            'text_color' => sanitize_hex_color($input['text_color']),
            'hover_color' => sanitize_hex_color($input['hover_color']),
            'shadow_color' => sanitize_text_field($input['shadow_color']),
            'center_bg_color' => sanitize_hex_color($input['center_bg_color']),
            'center_hover_color' => sanitize_hex_color($input['center_hover_color']),
            'center_shadow_color' => sanitize_text_field($input['center_shadow_color']),
            'border_radius' => intval($input['border_radius']),
            'center_pulse' => !empty($input['center_pulse']),
            'items' => array()
        );
        
        if (isset($input['items']) && is_array($input['items'])) {
            foreach ($input['items'] as $key => $item) {
                if (!empty($item['label']) || !empty($item['url'])) {
                    $output['items'][$key] = array(
                        'show' => !empty($item['show']),
                        'label' => sanitize_text_field($item['label']),
                        'url' => esc_url_raw($item['url']),
                        'icon' => wp_kses_post($item['icon']),
                        'position' => sanitize_text_field($item['position']),
                        'order' => intval($item['order'])
                    );
                }
            }
        }
        
        return $output;
    }
    
    private function get_icon_html($icon) {
        if (empty($icon)) {
            return '<span class="scfs-mobile-icon">🔗</span>';
        }
        
        if (strpos($icon, '<') !== false && strpos($icon, '>') !== false) {
            return '<span class="scfs-mobile-icon">' . $icon . '</span>';
        }
        
        return '<span class="scfs-mobile-icon">' . esc_html($icon) . '</span>';
    }
    
    public function admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $options = $this->get_options();
        $items = $options['items'];
        
        uasort($items, function($a, $b) {
            return ($a['order'] ?? 0) - ($b['order'] ?? 0);
        });
        ?>
        <div class="scfs-msb-wrap">
            <div class="scfs-msb-container">
                <div class="scfs-msb-header">
                    <h1>📱 Mobile Smart Bar</h1>
                    <p>Configure your mobile action bar - Add unlimited buttons!</p>
                </div>
                
                <div class="scfs-msb-main-grid">
                    <div class="scfs-msb-form">
                        <form method="post" action="options.php">
                            <?php settings_fields('scfs_msb_group'); ?>
                            
                            <div class="scfs-card">
                                <h3>⚙️ General Settings</h3>
                                <div class="scfs-item-field">
                                    <label>
                                        <input type="checkbox" name="<?php echo $this->option_name; ?>[enabled]" value="1" <?php checked(1, $options['enabled']); ?>>
                                        <strong>Enable Mobile Bar</strong>
                                    </label>
                                </div>
                                <div class="scfs-item-field">
                                    <label>Theme Preset</label>
                                    <select name="<?php echo $this->option_name; ?>[theme]">
                                        <option value="auto" <?php selected($options['theme'], 'auto'); ?>>🌓 Auto (follows device settings)</option>
                                        <option value="light" <?php selected($options['theme'], 'light'); ?>>☀️ Light</option>
                                        <option value="dark" <?php selected($options['theme'], 'dark'); ?>>🌙 Dark</option>
                                        <option value="glass" <?php selected($options['theme'], 'glass'); ?>>✨ Glass</option>
                                        <option value="custom" <?php selected($options['theme'], 'custom'); ?>>🎨 Custom</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="scfs-card custom-colors-section <?php echo ($options['theme'] === 'custom') ? 'visible' : ''; ?>">
                                <h3>🎨 Custom Colors</h3>
                                
                                <h4>Bar Settings</h4>
                                <div class="scfs-color-row">
                                    <div class="scfs-color-field">
                                        <label>Background Color</label>
                                        <input type="text" class="scfs-color-picker" name="<?php echo $this->option_name; ?>[bg_color]" value="<?php echo esc_attr($options['bg_color']); ?>" data-default-color="#ffffff">
                                    </div>
                                    <div class="scfs-color-field">
                                        <label>Text Color</label>
                                        <input type="text" class="scfs-color-picker" name="<?php echo $this->option_name; ?>[text_color]" value="<?php echo esc_attr($options['text_color']); ?>" data-default-color="#333333">
                                    </div>
                                    <div class="scfs-color-field">
                                        <label>Hover Color</label>
                                        <input type="text" class="scfs-color-picker" name="<?php echo $this->option_name; ?>[hover_color]" value="<?php echo esc_attr($options['hover_color']); ?>" data-default-color="#667eea">
                                    </div>
                                </div>
                                <div class="scfs-color-row">
                                    <div class="scfs-color-field">
                                        <label>Shadow Color</label>
                                        <input type="text" class="scfs-color-picker" name="<?php echo $this->option_name; ?>[shadow_color]" value="<?php echo esc_attr($options['shadow_color']); ?>" data-default-color="rgba(0,0,0,0.15)">
                                    </div>
                                    <div class="scfs-color-field">
                                        <label>Border Radius (px)</label>
                                        <input type="number" name="<?php echo $this->option_name; ?>[border_radius]" value="<?php echo esc_attr($options['border_radius']); ?>" min="0" max="100" step="1">
                                    </div>
                                </div>
                                
                                <h4>Center Button Settings</h4>
                                <div class="scfs-color-row">
                                    <div class="scfs-color-field">
                                        <label>Background Color</label>
                                        <input type="text" class="scfs-color-picker" name="<?php echo $this->option_name; ?>[center_bg_color]" value="<?php echo esc_attr($options['center_bg_color']); ?>" data-default-color="#25d366">
                                    </div>
                                    <div class="scfs-color-field">
                                        <label>Hover Color</label>
                                        <input type="text" class="scfs-color-picker" name="<?php echo $this->option_name; ?>[center_hover_color]" value="<?php echo esc_attr($options['center_hover_color']); ?>" data-default-color="#1da15b">
                                    </div>
                                    <div class="scfs-color-field">
                                        <label>Shadow Color</label>
                                        <input type="text" class="scfs-color-picker" name="<?php echo $this->option_name; ?>[center_shadow_color]" value="<?php echo esc_attr($options['center_shadow_color']); ?>" data-default-color="rgba(37,211,102,0.4)">
                                    </div>
                                </div>
                                
                                <div class="scfs-color-row">
                                    <div class="scfs-color-field">
                                        <label>
                                            <input type="checkbox" name="<?php echo $this->option_name; ?>[center_pulse]" value="1" <?php checked(1, $options['center_pulse']); ?>>
                                            <strong>Enable Pulse Effect</strong>
                                        </label>
                                        <p class="description">Ringing/pulsing animation on center button</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="scfs-card">
                                <h3>🎨 Custom CSS</h3>
                                <div class="scfs-item-field">
                                    <textarea class="scfs-css-editor" name="<?php echo $this->option_name; ?>[custom_css]" rows="8" style="font-family: monospace; width: 100%;" placeholder="/* Add your custom CSS here */
.scfs-mobile-item {
    /* Example: font-weight: bold; */
}"><?php echo esc_textarea($options['custom_css']); ?></textarea>
                                    <p class="description">Add your own CSS rules for advanced customization.</p>
                                </div>
                            </div>
                            
                            <div class="scfs-card">
                                <h3>🔘 Buttons</h3>
                                <p><strong>Note:</strong> You can only have ONE button with Center position. To delete a button, first disable it (uncheck "Enabled"), then click Delete.</p>
                                
                                <div class="scfs-items-container">
                                    <?php foreach ($items as $key => $item): ?>
                                    <div class="scfs-item-row">
                                        <div class="scfs-item-fields">
                                            <div class="scfs-item-field">
                                                <label>Enabled</label>
                                                <input type="checkbox" class="show-field" name="<?php echo $this->option_name; ?>[items][<?php echo $key; ?>][show]" value="1" <?php checked(1, $item['show']); ?>>
                                            </div>
                                            <div class="scfs-item-field">
                                                <label>Label</label>
                                                <input type="text" class="label-field" name="<?php echo $this->option_name; ?>[items][<?php echo $key; ?>][label]" value="<?php echo esc_attr($item['label']); ?>" placeholder="Button text">
                                            </div>
                                            <div class="scfs-item-field">
                                                <label>URL</label>
                                                <input type="url" class="url-field" name="<?php echo $this->option_name; ?>[items][<?php echo $key; ?>][url]" value="<?php echo esc_url($item['url']); ?>" placeholder="https:// or tel: or mailto:">
                                            </div>
                                            <div class="scfs-item-field">
                                                <label>Position</label>
                                                <select class="position-field" name="<?php echo $this->option_name; ?>[items][<?php echo $key; ?>][position]">
                                                    <option value="left" <?php selected($item['position'], 'left'); ?>>Left</option>
                                                    <option value="center" <?php selected($item['position'], 'center'); ?>>Center (Highlighted)</option>
                                                    <option value="right" <?php selected($item['position'], 'right'); ?>>Right</option>
                                                </select>
                                            </div>
                                            <div class="scfs-item-field">
                                                <label>Icon (HTML, Emoji, or Text)</label>
                                                <textarea class="icon-field" name="<?php echo $this->option_name; ?>[items][<?php echo $key; ?>][icon]" rows="3" placeholder="Enter emoji (👍), HTML code, or text"><?php echo esc_textarea($item['icon']); ?></textarea>
                                                <div class="scfs-icon-preview icon-preview">
                                                    <?php echo $this->get_icon_html($item['icon']); ?>
                                                </div>
                                                <p class="description">Examples: "👍", "📞", "💬", "&lt;svg&gt;...&lt;/svg&gt;", "🚀"</p>
                                            </div>
                                            <input type="hidden" class="order-field" name="<?php echo $this->option_name; ?>[items][<?php echo $key; ?>][order]" value="<?php echo esc_attr($item['order'] ?? 0); ?>">
                                        </div>
                                        <button type="button" class="scfs-remove-item <?php echo $item['show'] ? 'disabled' : ''; ?>" <?php echo $item['show'] ? 'disabled' : ''; ?>>Delete</button>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <button type="button" id="scfs-add-item" class="scfs-add-item">+ Add New Button</button>
                                
                                <div class="scfs-note">
                                    <strong>💡 Tips:</strong><br>
                                    • Only ONE button can have Center position (highlighted with pulse effect)<br>
                                    • To delete a button, first uncheck "Enabled", then click Delete<br>
                                    • You can use any emoji: 😊, ❤️, 🎉, 📞, 💬, etc.<br>
                                    • You can use HTML code for custom icons (SVG, Font Awesome, etc.)
                                </div>
                            </div>
                            
                            <?php submit_button('💾 Save Settings', 'primary'); ?>
                        </form>
                    </div>
                    
                    <div class="scfs-msb-sidebar">
                        <div class="scfs-card">
                            <h3>📱 Live Preview</h3>
                            <div class="scfs-phone-mockup">
                                <div class="scfs-preview-bar">
                                    <div class="scfs-preview-item">💬<br>Messenger</div>
                                    <div class="scfs-preview-item">💚<br>WhatsApp</div>
                                    <div class="scfs-preview-center">📞</div>
                                </div>
                            </div>
                            <p style="margin-top: 15px; font-size: 12px; color: #666; text-align: center;">
                                ⚡ The bar appears only on mobile devices
                            </p>
                        </div>
                        
                        <div class="scfs-card">
                            <h3>ℹ️ URL Examples</h3>
                            <ul style="margin: 0; padding-left: 20px; font-size: 13px;">
                                <li><strong>Facebook:</strong> https://facebook.com/username</li>
                                <li><strong>Messenger:</strong> https://m.me/username</li>
                                <li><strong>WhatsApp:</strong> https://wa.me/123456789</li>
                                <li><strong>Phone:</strong> tel:+123456789</li>
                                <li><strong>Email:</strong> mailto:email@example.com</li>
                                <li><strong>Instagram:</strong> https://instagram.com/username</li>
                                <li><strong>Twitter/X:</strong> https://twitter.com/username</li>
                                <li><strong>YouTube:</strong> https://youtube.com/@channel</li>
                                <li><strong>LinkedIn:</strong> https://linkedin.com/in/username</li>
                                <li><strong>TikTok:</strong> https://tiktok.com/@username</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Template for new item -->
        <div class="scfs-item-row scfs-item-template" style="display:none;">
            <div class="scfs-item-fields">
                <div class="scfs-item-field">
                    <label>Enabled</label>
                    <input type="checkbox" class="show-field" value="1" checked>
                </div>
                <div class="scfs-item-field">
                    <label>Label</label>
                    <input type="text" class="label-field" placeholder="Button text">
                </div>
                <div class="scfs-item-field">
                    <label>URL</label>
                    <input type="url" class="url-field" placeholder="https://...">
                </div>
                <div class="scfs-item-field">
                    <label>Position</label>
                    <select class="position-field">
                        <option value="left">Left</option>
                        <option value="center">Center (Highlighted)</option>
                        <option value="right">Right</option>
                    </select>
                </div>
                <div class="scfs-item-field">
                    <label>Icon (HTML, Emoji, or Text)</label>
                    <textarea class="icon-field" rows="3" placeholder="Enter emoji (👍), HTML code, or text"></textarea>
                    <div class="scfs-icon-preview icon-preview">No icon</div>
                </div>
                <input type="hidden" class="order-field" value="0">
            </div>
            <button type="button" class="scfs-remove-item">Delete</button>
        </div>
        <?php
    }
    
    public function render_bar() {
        if (!$this->is_enabled()) {
            return;
        }
        
        $options = $this->get_options();
        $items = $options['items'];
        $theme_class = $this->get_theme_class();
        
        uasort($items, function($a, $b) {
            return ($a['order'] ?? 0) - ($b['order'] ?? 0);
        });
        
        $left_items = array();
        $right_items = array();
        $center_item = null;
        
        foreach ($items as $item) {
            if (empty($item['show']) || empty($item['url']) || empty($item['label'])) {
                continue;
            }
            
            if ($item['position'] === 'center') {
                $center_item = $item;
            } elseif ($item['position'] === 'left') {
                $left_items[] = $item;
            } elseif ($item['position'] === 'right') {
                $right_items[] = $item;
            }
        }
        ?>
        <div class="scfs-mobile-bar <?php echo esc_attr($theme_class); ?>">
            <?php foreach ($left_items as $item): ?>
                <a href="<?php echo esc_url($item['url']); ?>" class="scfs-mobile-item" target="_blank" rel="noopener noreferrer">
                    <?php echo $this->get_icon_html($item['icon']); ?>
                    <span><?php echo esc_html($item['label']); ?></span>
                </a>
            <?php endforeach; ?>
            
            <?php if ($center_item): ?>
                <a href="<?php echo esc_url($center_item['url']); ?>" class="scfs-mobile-center">
                    <?php echo $this->get_icon_html($center_item['icon']); ?>
                </a>
            <?php endif; ?>
            
            <?php foreach ($right_items as $item): ?>
                <a href="<?php echo esc_url($item['url']); ?>" class="scfs-mobile-item" target="_blank" rel="noopener noreferrer">
                    <?php echo $this->get_icon_html($item['icon']); ?>
                    <span><?php echo esc_html($item['label']); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
        <?php
    }
}
