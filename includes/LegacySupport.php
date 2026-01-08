<?php
namespace SCFS;

class LegacySupport {
    private static $instance = null;
    private $custom_fields;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->custom_fields = CustomFields::get_instance();
        
        // Hook pentru a procesa conținutul și a înlocui automat shortcode-urile (opțional)
        add_filter('the_content', [$this, 'maybe_replace_legacy_shortcodes'], 1);
        add_filter('widget_text', [$this, 'maybe_replace_legacy_shortcodes'], 1);
    }
    
    public function maybe_replace_legacy_shortcodes($content) {
        // Înlocuiește automat [cfs field="nume"] cu [scfs_field name="nume"] în conținut
        // Aceasta este opțională - shortcode-urile vechi vor funcționa oricum
        if (strpos($content, '[cfs field=') !== false) {
            $content = preg_replace_callback(
                '/\[cfs\s+field=["\']([^"\']+)["\'](?:\s+default=["\']([^"\']+)["\'])?\]/',
                [$this, 'replace_cfs_shortcode'],
                $content
            );
        }
        
        return $content;
    }
    
    private function replace_cfs_shortcode($matches) {
        $field_name = $matches[1];
        $default = isset($matches[2]) ? $matches[2] : '';
        
        if (!empty($default)) {
            return '[scfs_field name="' . esc_attr($field_name) . '" default="' . esc_attr($default) . '"]';
        } else {
            return '[scfs_field name="' . esc_attr($field_name) . '"]';
        }
    }
    
    /**
     * Funcție pentru a migra automat shortcode-urile din conținut (opțional)
     */
    public function migrate_shortcodes_in_content() {
        global $wpdb;
        
        // 1. Migrează în post_content
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$wpdb->posts} 
                 SET post_content = REPLACE(post_content, %s, %s)
                 WHERE post_content LIKE %s",
                '[cfs field="',
                '[scfs_field name="',
                '%[cfs field="%'
            )
        );
        
        // 2. Migrează în post_excerpt
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$wpdb->posts} 
                 SET post_excerpt = REPLACE(post_excerpt, %s, %s)
                 WHERE post_excerpt LIKE %s",
                '[cfs field="',
                '[scfs_field name="',
                '%[cfs field="%'
            )
        );
        
        // 3. Migrează în widget-uri
        $widget_instances = get_option('widget_text', []);
        if (is_array($widget_instances)) {
            foreach ($widget_instances as $key => $instance) {
                if (is_array($instance) && isset($instance['text'])) {
                    $instance['text'] = str_replace(
                        '[cfs field="',
                        '[scfs_field name="',
                        $instance['text']
                    );
                    $widget_instances[$key] = $instance;
                }
            }
            update_option('widget_text', $widget_instances);
        }
        
        return true;
    }
}