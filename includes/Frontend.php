<?php
namespace SCFS;

class Frontend {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Încărcare assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        
        // Shortcode principal
        add_shortcode('scfs_social', [$this, 'social_shortcode']);
        
        // Auto display based on settings
        add_action('wp_footer', [$this, 'auto_display']);
    }
    
    public function enqueue_assets() {
        wp_enqueue_style('scfs-frontend', plugins_url('../assets/frontend.css', __FILE__), array(), '1.0.0');
        wp_enqueue_script('scfs-frontend', plugins_url('../assets/frontend.js', __FILE__), array('jquery'), '1.0.0', true);
        
        // Verificare forțată pentru iconițe
        add_action('wp_footer', function() {
            echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                // Verifică iconițele după 1 secundă
                setTimeout(function() {
                    var icons = document.querySelectorAll(".sfb-item i, .scfs-inline-button i, .scfs-single-button i");
                    
                    icons.forEach(function(icon, index) {
                        // Forțează vizibilitatea
                        icon.style.opacity = "1";
                        icon.style.visibility = "visible";
                        icon.style.display = "inline-flex";
                        
                        // Verifică dacă Font Awesome este încărcat
                        var style = window.getComputedStyle(icon);
                        var fontFamily = style.fontFamily;
                        
                        if (fontFamily.indexOf("Font Awesome") === -1 && 
                            fontFamily.indexOf("FontAwesome") === -1) {
                            console.warn("SCFS: Icon #" + index + " doesnt have Font Awesome font");
                            
                            // Încearcă să re-aplici font-ul
                            icon.style.fontFamily = \'"Font Awesome 6 Free", "Font Awesome 6 Brands", sans-serif\';
                            
                            // Dacă tot nu merge, aplică fallback
                            setTimeout(function() {
                                var newStyle = window.getComputedStyle(icon);
                                if (newStyle.fontFamily.indexOf("Font Awesome") === -1) {
                                    // Aplică fallback emoji
                                    var iconClass = icon.className;
                                    var emoji = "🔗";
                                    
                                    if (iconClass.includes("fa-facebook")) emoji = "📘";
                                    else if (iconClass.includes("fa-twitter")) emoji = "🐦";
                                    else if (iconClass.includes("fa-instagram")) emoji = "📷";
                                    else if (iconClass.includes("fa-whatsapp")) emoji = "💬";
                                    else if (iconClass.includes("fa-phone")) emoji = "📞";
                                    else if (iconClass.includes("fa-envelope")) emoji = "✉️";
                                    
                                    icon.innerHTML = emoji;
                                    icon.style.fontFamily = "sans-serif";
                                }
                            }, 100);
                        }
                    });
                    
                    // Log pentru debugging
                    console.log("SCFS: Checked " + icons.length + " icons");
                    
                }, 1000);
            });
            </script>';
        });
    }
    
    public function social_shortcode($atts) {
        $atts = shortcode_atts([
            'name' => '',
            'type' => 'inline'
        ], $atts);
        
        $social_buttons = SocialButtons::get_instance();
        $social_settings = SocialSettings::get_instance();
        $settings = $social_settings->get_settings();
        
        $buttons = $social_buttons->get_all();
        
        if ($atts['type'] === 'floating') {
            return $this->render_floating($buttons, $settings);
        }
        
        if ($atts['name']) {
            foreach ($buttons as $button) {
                if (isset($button['name']) && $button['name'] === $atts['name']) {
                    return $this->render_single_button($button, $settings);
                }
            }
        }
        
        // Default: inline buttons
        $inline_buttons = array_filter($buttons, function($btn) {
            return isset($btn['floating']) && !$btn['floating'];
        });
        
        return $this->render_inline($inline_buttons, $settings);
    }
    
private function render_floating($buttons, $settings) {
    $floating_buttons = array_filter($buttons, function($btn) {
        return isset($btn['floating']) && $btn['floating'];
    });
    
    if (empty($floating_buttons)) return '';
    
    usort($floating_buttons, function($a, $b) {
        $order_a = $a['order'] ?? 9999;
        $order_b = $b['order'] ?? 9999;
        return $order_a <=> $order_b;
    });
    
    $position_class = 'sfb-position-' . $settings['position'];
    $animation_class = 'sfb-animation-' . $settings['animation'];
    $mobile_class = $settings['mobile_enabled'] ? 'sfb-mobile-enabled' : 'sfb-mobile-disabled';
    $show_names = $settings['show_names'];
    $show_custom_message = $settings['show_custom_message'];
    $transparent_icons = !$show_names && $settings['transparent_icons'];
    $button_color = $settings['button_color'];
    
    // Determine custom message position - opposite of button position
    $custom_message_class = '';
    switch ($settings['position']) {
        case 'right':
        case 'top-right':
            $custom_message_class = 'sfb-custom-message-left';
            break;
        case 'left':
        case 'top-left':
            $custom_message_class = 'sfb-custom-message-right';
            break;
    }
    
    ob_start(); ?>
    
    <div class="sfb-container <?php echo esc_attr("$position_class $animation_class $mobile_class"); ?> <?php echo $show_names ? 'sfb-show-names' : 'sfb-icons-only'; ?> <?php echo $transparent_icons ? 'sfb-transparent-icons' : ''; ?>">
        
        <?php if($show_custom_message): ?>
        <!-- Custom message - VIZIBIL MEREU inițial -->
        <div class="sfb-custom-message <?php echo $custom_message_class; ?>" 
             id="sfb_custom_message"
             style="opacity: 1; visibility: visible;">
            <?php echo esc_html($settings['custom_message']); ?>
        </div>
        <?php endif; ?>
        
        <div class="sfb-cta" style="background-color: <?php echo esc_attr($button_color); ?>" aria-label="Open social menu" role="button" tabindex="0">
            <span class="sfb-cta-icon"><?php echo $settings['button_icon']; ?></span>
        </div>
        
        <div class="sfb-popup" role="menu" aria-label="Social media links">
            <?php foreach($floating_buttons as $index => $button): ?>
                <?php 
                $href = $this->get_button_url($button);
                $icon = $this->get_button_icon($button);
                // DETERMINE BACKGROUND COLOR FOR BEFORE ELEMENT
                $before_style = !$show_names ? 'style="background-color: ' . esc_attr($button_color) . ';"' : '';
                ?>
                
                <a href="<?php echo esc_attr($href); ?>" class="sfb-item" 
                   <?php echo $this->get_button_target($button); ?> 
                   role="menuitem"
                   style="--item-index: <?php echo $index; ?>">
                    <?php if(!$show_names): ?>
                        <!-- Afișăm elementul before doar dacă nu sunt nume -->
                        <span class="sfb-item-before" <?php echo $before_style; ?>></span>
                    <?php endif; ?>
                    <?php echo $icon; ?>
                    <?php if($show_names): ?>
                        <span class="sfb-item-label"><?php echo esc_html($button['label'] ?? 'Button'); ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    
    <?php
    return ob_get_clean();
}

private function get_button_icon($button) {
    // DEBUG: Afișează structura butonului pentru debugging
    // error_log('Button data: ' . print_r($button, true));
    
    // Verificăm icon_type din baza de date
    if (isset($button['icon_type'])) {
        switch ($button['icon_type']) {
            case 'class': // ATENȚIE: În DB este 'class', nu 'fa'
                // Iconiță Font Awesome cu clasă
                $icon_class = $button['icon'] ?? 'fas fa-link';
                return '<i class="' . esc_attr($icon_class) . '"></i>';
                
            case 'html':
                // Iconiță HTML directă
                return $button['icon'] ?? '<i class="fas fa-link"></i>';
                
            case 'custom':
                // Iconiță custom
                return $button['custom_icon'] ?? '<i class="fas fa-link"></i>';
                
            default:
                // Fallback
                return '<i class="fas fa-link"></i>';
        }
    } else {
        // Verificăm dacă există icon în vechiul format
        if (isset($button['icon'])) {
            // Verificăm dacă este o clasă Font Awesome
            if (strpos($button['icon'], 'fa-') !== false) {
                return '<i class="' . esc_attr($button['icon']) . '"></i>';
            } else {
                // Presupunem că este HTML
                return $button['icon'];
            }
        } else {
            // Fallback complet
            return '<i class="fas fa-link"></i>';
        }
    }
}
    
    private function render_inline($buttons, $settings) {
        if (empty($buttons)) return '';
        
        // Folosim show_shortcut_names pentru butoanele inline
        $show_names = $settings['show_shortcut_names'];
        $is_transparent = !$show_names && $settings['transparent_icons'];
        
        ob_start(); ?>
        
        <div class="scfs-inline-buttons">
            <?php foreach($buttons as $button): ?>
                <?php 
                $href = $this->get_button_url($button);
                $icon = $this->get_button_icon($button);
                ?>
                
                <a href="<?php echo esc_attr($href); ?>" class="scfs-inline-button <?php echo $is_transparent ? 'scfs-transparent' : ''; ?>" 
                   <?php echo $this->get_button_target($button); ?>>
                    <?php echo $icon; ?>
                    <?php if($show_names): ?>
                        <span><?php echo esc_html($button['label'] ?? 'Button'); ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
        
        <?php
        return ob_get_clean();
    }
    
    private function render_single_button($button, $settings) {
        $href = $this->get_button_url($button);
        $icon = $this->get_button_icon($button);
        $show_names = $settings['show_shortcut_names'];
        $is_transparent = !$show_names && $settings['transparent_icons'];
        
        return sprintf(
            '<a href="%s" class="scfs-single-button %s" %s>%s%s</a>',
            esc_attr($href),
            $is_transparent ? 'scfs-transparent' : '',
            $this->get_button_target($button),
            $icon,
            $show_names ? '<span>' . esc_html($button['label'] ?? 'Button') . '</span>' : ''
        );
    }
    
    private function get_button_url($button) {
        $url = $button['url'] ?? '#';
        $type = $button['type'] ?? 'url';
        
        return AjaxHandler::get_correct_url($type, $url);
    }

    private function get_button_target($button) {
        $type = $button['type'] ?? 'url';
        $url = $button['url'] ?? '#';
        
        if ($type === 'url') {
            $scheme = parse_url($url, PHP_URL_SCHEME);
            if (!in_array($scheme, ['tel', 'mailto', 'viber', 'whatsapp', 'tg', 'fb-messenger'])) {
                return 'target="_blank" rel="noopener"';
            }
        }
        return '';
    }
    
    public function auto_display() {
        $auto_display = get_option('sfb_auto_display', 0);
        
        if (!$auto_display) {
            return;
        }
        
        // Get settings
        $social_settings = SocialSettings::get_instance();
        $settings = $social_settings->get_settings();
        
        // Get buttons
        $social_buttons = SocialButtons::get_instance();
        $buttons = $social_buttons->get_all();
        
        // Filter only floating buttons
        $floating_buttons = array_filter($buttons, function($btn) {
            return isset($btn['floating']) && $btn['floating'];
        });
        
        if (empty($floating_buttons)) {
            return;
        }
        
        // Output floating buttons
        echo $this->render_floating($floating_buttons, $settings);
    }
}