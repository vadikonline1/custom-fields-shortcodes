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
        
        // Asigură-te că jQuery este încărcat
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_style('dashicons');
        
        ?>
        <style>
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
            .scfs-icon-selector {
                display: flex;
                gap: 8px;
                flex-wrap: wrap;
                margin-top: 8px;
            }
            .scfs-icon-btn {
                padding: 6px 12px;
                background: white;
                border: 1px solid #ddd;
                border-radius: 6px;
                cursor: pointer;
                font-size: 12px;
            }
            .scfs-icon-btn:hover {
                border-color: #667eea;
                background: #f0f0ff;
            }
            .scfs-icon-btn.active {
                background: #667eea;
                color: white;
                border-color: #667eea;
            }
            .scfs-custom-icon {
                margin-top: 8px;
            }
            .scfs-custom-icon input {
                width: 100%;
                padding: 6px 10px;
                font-size: 12px;
            }
            .scfs-phone-mockup {
                background: #1a1a1a;
                border-radius: 35px;
                padding: 20px 12px;
            }
            .scfs-preview-bar {
                background: white;
                border-radius: 50px;
                padding: 10px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 5px;
                flex-wrap: wrap;
            }
            .scfs-preview-item {
                flex: 1;
                text-align: center;
                font-size: 10px;
                min-width: 50px;
            }
            .scfs-preview-center {
                width: 50px;
                height: 50px;
                background: #25d366;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: -25px auto 0;
            }
            .scfs-note {
                background: #fff3cd;
                border-left: 4px solid #ffc107;
                padding: 10px 15px;
                margin-top: 15px;
                font-size: 12px;
            }
            @media (max-width: 768px) {
                .scfs-msb-main-grid {
                    flex-direction: column;
                }
                .scfs-item-fields {
                    flex-direction: column;
                }
            }
        </style>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Check if jQuery is available
            if (typeof jQuery !== 'undefined') {
                (function($) {
                    // Icon selector - predefined icons
                    $(document).on('click', '.scfs-icon-btn', function() {
                        var iconValue = $(this).data('icon');
                        var $row = $(this).closest('.scfs-item-row');
                        $row.find('.icon-field').val(iconValue);
                        $row.find('.scfs-icon-btn').removeClass('active');
                        $(this).addClass('active');
                        $row.find('.custom-icon-input').val('');
                    });
                    
                    // Custom icon input
                    $(document).on('input', '.custom-icon-input', function() {
                        var customValue = $(this).val();
                        var $row = $(this).closest('.scfs-item-row');
                        if (customValue) {
                            $row.find('.icon-field').val(customValue);
                            $row.find('.scfs-icon-btn').removeClass('active');
                        }
                    });
                    
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
                        $template.find('.custom-icon-input').val('');
                        $template.find('.scfs-icon-btn').removeClass('active');
                        $template.css('display', 'flex');
                        $('.scfs-items-container').append($template);
                        
                        // Disable delete button for new enabled item
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
                    
                    // Initialize delete buttons state
                    $('.scfs-item-row').each(function() {
                        var $row = $(this);
                        var $deleteBtn = $row.find('.scfs-remove-item');
                        if ($row.find('.show-field').is(':checked')) {
                            $deleteBtn.addClass('disabled');
                            $deleteBtn.prop('disabled', true);
                        }
                    });
                })(jQuery);
            } else {
                console.log('jQuery not loaded yet');
                // Reload jQuery if needed
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
        ?>
        <style>
            /* Bara mobilă - cea mai de sus */
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
                z-index: 9999999 !important;
                box-shadow: 0 5px 25px rgba(0,0,0,0.15) !important;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
                transition: all 0.3s ease !important;
                background: white !important;
                margin-bottom: env(safe-area-inset-bottom, 0px) !important;
            }
            
            /* Adaugă spațiu la sfârșitul paginii */
            body {
                margin-bottom: 80px !important;
            }
            
            /* Asigură-te că bara este deasupra tuturor elementelor fixe */
            .scfs-mobile-bar {
                z-index: 9999999 !important;
            }
            
            /* Suprimă toate celelalte elemente care ar putea fi deasupra */
            .scrollToTop,
            .scroll-to-top,
            .back-to-top,
            .go-top,
            .scrolltop,
            .totop,
            .top-button,
            .scrollup,
            .btn-scroll-top,
            .scroll-top-button,
            .sfb-container,
            #scrollToTop,
            #scroll-to-top,
            #back-to-top,
            #go-top,
            .elementor-element .scroll-top,
            .wp-block-button__link.scroll-top,
            button.scroll-top,
            a.scroll-top,
            .scrollTop,
            .scroll-top-btn,
            .page-scroll-top,
            .scrolltop-button,
            .scrolling-top,
            .top-scroll,
            .scrollToTop-button,
            .scrollToTop-btn,
            .scroll_To_Top {
                z-index: 9999998 !important;
                bottom: 100px !important;
            }
            
            /* Chat widgets și alte elemente fixe */
            .wp-chatbot-container,
            .wc-chatbot-container,
            .fb-customerchat,
            .fb_dialog,
            .fb_dialog_content,
            .whatsapp-chat-widget,
            .tawk-mini,
            .tawk-button,
            .tawk-chat-widget,
            #tawk-widget,
            .tawkio-chat,
            .crpnt-chat,
            .chat-widget,
            .chatbot-container,
            .widget_chat,
            .wp-live-chat,
            .live-chat,
            .chat-button,
            .chat-icon,
            .chat-circle,
            .chat-widget-button,
            .c-widget,
            .customer-chat {
                z-index: 9999998 !important;
                margin-bottom: 80px !important;
            }
            
            /* Cookie banners și GDPR */
            #cookie-law-info-bar,
            .cc-window,
            .gdpr-cookie-banner,
            .cookie-notice,
            .cli-modal,
            .cli-barmodal,
            .cookie-popup,
            .cookies-banner,
            .gdpr-banner,
            .cookie-consent,
            .cookiebanner,
            .cookie-banner {
                z-index: 9999997 !important;
                bottom: 100px !important;
            }
            
            /* Floating buttons, contact forms, etc */
            .floating-btn,
            .floating-button,
            .contact-floating,
            .contact-bar,
            .fixed-contact,
            .sticky-contact,
            .floating-contact,
            .floating-widget,
            .fixed-chat,
            .sticky-chat,
            .fixed-sidebar,
            .sticky-sidebar {
                z-index: 9999998 !important;
                margin-bottom: 80px !important;
            }
            
            /* Notification bars, top bars */
            .top-bar,
            .notification-bar,
            .announcement-bar,
            .top-notification,
            .sticky-header,
            .fixed-header {
                z-index: 9999996 !important;
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
                transition: transform 0.2s !important;
                font-size: 11px !important;
                font-weight: 500 !important;
            }
            .scfs-mobile-item:hover {
                transform: translateY(-3px) !important;
            }
            .scfs-mobile-item .dashicons {
                font-size: 24px !important;
                width: 24px !important;
                height: 24px !important;
            }
            .scfs-mobile-item svg {
                width: 24px !important;
                height: 24px !important;
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
                transition: transform 0.2s !important;
                position: relative !important;
                z-index: 10 !important;
            }
            
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
            
            .scfs-mobile-center:hover {
                transform: scale(1.08) !important;
            }
            
            .scfs-mobile-center .dashicons {
                color: white !important;
                font-size: 30px !important;
                width: 30px !important;
                height: 30px !important;
            }
            .scfs-mobile-center svg {
                width: 30px !important;
                height: 30px !important;
                color: white !important;
            }
            
            /* Resetare pentru desktop */
            @media (min-width: 769px) {
                body {
                    margin-bottom: 0 !important;
                }
                .scfs-mobile-bar {
                    display: none !important;
                }
            }
            
            /* Responsive */
            @media (max-width: 480px) {
                .scfs-mobile-bar {
                    bottom: 10px !important;
                    left: 10px !important;
                    right: 10px !important;
                    padding: 6px 12px !important;
                    gap: 8px !important;
                }
                .scfs-mobile-center {
                    width: 50px !important;
                    height: 50px !important;
                    margin-top: -30px !important;
                }
                .scfs-mobile-item span {
                    font-size: 9px !important;
                }
                body {
                    margin-bottom: 70px !important;
                }
                
                /* Scroll to top buttons on mobile */
                .scrollToTop,
                .scroll-to-top,
                .back-to-top,
                .go-top,
                .scrolltop {
                    bottom: 80px !important;
                }
            }
            
            /* Pentru ecrane mici dar nu foarte mici */
            @media (min-width: 481px) and (max-width: 768px) {
                body {
                    margin-bottom: 80px !important;
                }
                
                .scrollToTop,
                .scroll-to-top,
                .back-to-top,
                .go-top,
                .scrolltop {
                    bottom: 100px !important;
                }
            }
            
            /* Forță pentru orice element care ar putea fi deasupra */
            *:not(.scfs-mobile-bar):not(.scfs-mobile-bar *) {
                z-index: auto !important;
            }
            
            /* Excepție pentru butoanele de scroll - le dăm un z-index mai mic */
            [class*="scrollToTop"],
            [class*="scroll-to-top"],
            [class*="back-to-top"],
            [class*="go-top"],
            [class*="scrolltop"],
            [id*="scrollToTop"],
            [id*="scroll-to-top"],
            [id*="back-to-top"],
            [class*="scrollTop"],
            button[class*="scroll"],
            a[class*="scroll"] {
                z-index: 9999998 !important;
            }
            
            /* Elemente specifice din diverse teme și plugin-uri */
            .td-scroll-up,
            .td-scroll-up-visible,
            .elementor-scroll-top,
            .et-scroll-to-top,
            .ast-scroll-to-top,
            .ai-scroll-top,
            .ghostkit-scroll-top,
            .wpdt-scroll-top,
            .scroll-top-wrapper,
            .scroll-top-container,
            .go-to-top,
            .backToTop,
            .toTop,
            .topbutton,
            .scrollup-button {
                z-index: 9999998 !important;
                bottom: 100px !important;
            }
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
            'items' => array(
                'item_1' => array(
                    'show' => true,
                    'label' => 'Messenger',
                    'url' => 'https://m.me/',
                    'icon' => 'dashicons-facebook',
                    'position' => 'left',
                    'order' => 0
                ),
                'item_2' => array(
                    'show' => true,
                    'label' => 'WhatsApp',
                    'url' => 'https://wa.me/',
                    'icon' => 'dashicons-whatsapp',
                    'position' => 'right',
                    'order' => 2
                ),
                'item_3' => array(
                    'show' => true,
                    'label' => 'Call',
                    'url' => 'tel:',
                    'icon' => 'phone',
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
            'items' => array()
        );
        
        if (isset($input['items']) && is_array($input['items'])) {
            foreach ($input['items'] as $key => $item) {
                if (!empty($item['label']) || !empty($item['url'])) {
                    $output['items'][$key] = array(
                        'show' => !empty($item['show']),
                        'label' => sanitize_text_field($item['label']),
                        'url' => esc_url_raw($item['url']),
                        'icon' => sanitize_text_field($item['icon']),
                        'position' => sanitize_text_field($item['position']),
                        'order' => intval($item['order'])
                    );
                }
            }
        }
        
        return $output;
    }
    
    private function get_icon_html($icon) {
        // Check if it's a Dashicon
        if (strpos($icon, 'dashicons-') !== false) {
            return '<span class="dashicons ' . esc_attr($icon) . '"></span>';
        }
        // Check if it's the phone icon
        if ($icon === 'phone') {
            return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>
                    </svg>';
        }
        // Check if it's a custom text/emoji
        if (!empty($icon) && strpos($icon, 'dashicons') === false && $icon !== 'phone') {
            return '<span style="font-size: 22px;">' . esc_html($icon) . '</span>';
        }
        // Default fallback
        return '<span class="dashicons dashicons-admin-links"></span>';
    }
    
    public function admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $options = $this->get_options();
        $items = $options['items'];
        
        // Sort items by order
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
                                    <label>Theme</label>
                                    <select name="<?php echo $this->option_name; ?>[theme]">
                                        <option value="auto" <?php selected($options['theme'], 'auto'); ?>>🌓 Auto (follows device settings)</option>
                                        <option value="light" <?php selected($options['theme'], 'light'); ?>>☀️ Light</option>
                                        <option value="dark" <?php selected($options['theme'], 'dark'); ?>>🌙 Dark</option>
                                        <option value="glass" <?php selected($options['theme'], 'glass'); ?>>✨ Glass</option>
                                    </select>
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
                                                <label>Icon</label>
                                                <input type="hidden" class="icon-field" name="<?php echo $this->option_name; ?>[items][<?php echo $key; ?>][icon]" value="<?php echo esc_attr($item['icon']); ?>">
                                                <input type="hidden" class="order-field" name="<?php echo $this->option_name; ?>[items][<?php echo $key; ?>][order]" value="<?php echo esc_attr($item['order'] ?? 0); ?>">
                                                <div class="scfs-icon-selector">
                                                    <span class="scfs-icon-btn <?php echo $item['icon'] == 'dashicons-facebook' ? 'active' : ''; ?>" data-icon="dashicons-facebook">📘 FB</span>
                                                    <span class="scfs-icon-btn <?php echo $item['icon'] == 'dashicons-instagram' ? 'active' : ''; ?>" data-icon="dashicons-instagram">📸 IG</span>
                                                    <span class="scfs-icon-btn <?php echo $item['icon'] == 'dashicons-whatsapp' ? 'active' : ''; ?>" data-icon="dashicons-whatsapp">💚 WA</span>
                                                    <span class="scfs-icon-btn <?php echo $item['icon'] == 'dashicons-email' ? 'active' : ''; ?>" data-icon="dashicons-email">✉️ Mail</span>
                                                    <span class="scfs-icon-btn <?php echo $item['icon'] == 'phone' ? 'active' : ''; ?>" data-icon="phone">📞 Tel</span>
                                                    <span class="scfs-icon-btn <?php echo $item['icon'] == 'dashicons-twitter' ? 'active' : ''; ?>" data-icon="dashicons-twitter">🐦 X</span>
                                                    <span class="scfs-icon-btn <?php echo $item['icon'] == 'dashicons-youtube' ? 'active' : ''; ?>" data-icon="dashicons-youtube">📺 YT</span>
                                                    <span class="scfs-icon-btn <?php echo $item['icon'] == 'dashicons-linkedin' ? 'active' : ''; ?>" data-icon="dashicons-linkedin">🔗 IN</span>
                                                    <span class="scfs-icon-btn <?php echo $item['icon'] == 'dashicons-tiktok' ? 'active' : ''; ?>" data-icon="dashicons-tiktok">🎵 TT</span>
                                                </div>
                                                <div class="scfs-custom-icon">
                                                    <input type="text" class="custom-icon-input" placeholder="Or enter custom icon (emoji or text)" value="<?php echo !in_array($item['icon'], ['dashicons-facebook', 'dashicons-instagram', 'dashicons-whatsapp', 'dashicons-email', 'phone', 'dashicons-twitter', 'dashicons-youtube', 'dashicons-linkedin', 'dashicons-tiktok']) ? esc_attr($item['icon']) : ''; ?>">
                                                </div>
                                            </div>
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
                                    • You can use custom icons: emojis (😊, ❤️, 🎉) or any text (☎️, ✉️, 🌐)
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
                                    <div class="scfs-preview-item">📘<br>Messenger</div>
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
                    <label>Icon</label>
                    <input type="hidden" class="icon-field" value="">
                    <input type="hidden" class="order-field" value="0">
                    <div class="scfs-icon-selector">
                        <span class="scfs-icon-btn" data-icon="dashicons-facebook">📘 FB</span>
                        <span class="scfs-icon-btn" data-icon="dashicons-instagram">📸 IG</span>
                        <span class="scfs-icon-btn" data-icon="dashicons-whatsapp">💚 WA</span>
                        <span class="scfs-icon-btn" data-icon="dashicons-email">✉️ Mail</span>
                        <span class="scfs-icon-btn" data-icon="phone">📞 Tel</span>
                        <span class="scfs-icon-btn" data-icon="dashicons-twitter">🐦 X</span>
                        <span class="scfs-icon-btn" data-icon="dashicons-youtube">📺 YT</span>
                        <span class="scfs-icon-btn" data-icon="dashicons-linkedin">🔗 IN</span>
                        <span class="scfs-icon-btn" data-icon="dashicons-tiktok">🎵 TT</span>
                    </div>
                    <div class="scfs-custom-icon">
                        <input type="text" class="custom-icon-input" placeholder="Or enter custom icon (emoji or text)">
                    </div>
                </div>
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
        
        // Sort items by order
        uasort($items, function($a, $b) {
            return ($a['order'] ?? 0) - ($b['order'] ?? 0);
        });
        
        // Separate items by position
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
