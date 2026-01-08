<?php
namespace SCFS;

class Main {
    private static $instance = null;
    private $plugin_file;
    private $custom_fields;
    private $social_buttons;
    private $social_settings;
    private $ajax_handler;
    private $legacy_support;
    
    public static function get_instance($plugin_file = null) {
        if (null === self::$instance) {
            self::$instance = new self($plugin_file);
        }
        return self::$instance;
    }
    
    private function __construct($plugin_file) {
        $this->plugin_file = $plugin_file;
        
        // Initializează toate clasele
        $this->custom_fields = CustomFields::get_instance();
        $this->social_buttons = SocialButtons::get_instance();
        $this->social_settings = SocialSettings::get_instance();
        $this->ajax_handler = AjaxHandler::get_instance();
        $this->legacy_support = LegacySupport::get_instance();
        
        // Hook-uri admin
        add_action('admin_menu', [$this, 'admin_menu'], 10);
        add_action('admin_enqueue_scripts', [$this, 'admin_assets']);
        
        // Plugin action link
        if ($this->plugin_file) {
            add_filter('plugin_action_links_' . plugin_basename($this->plugin_file), [$this, 'settings_link']);
        }
    }
    
    public function admin_menu() {
        // Meniul principal
        add_menu_page(
            'S&C Fields Shortcodes',
            'S&C Fields',
            'manage_options',
            'scfs-oop',
            [$this, 'admin_page'],
            'dashicons-admin-generic',
            30
        );
    }
    
    public function admin_page() {
        $migration_done = AjaxHandler::is_migration_done();
        $show_debug = isset($_GET['debug']) && current_user_can('manage_options');
        ?>
        <div class="wrap scfs-admin">
            <h1 class="scfs-title">Social & Custom Fields Shortcodes</h1>
            
            <?php if (!$migration_done && $this->ajax_handler->has_data_to_migrate()): ?>
            <div class="notice notice-warning">
                <p>
                    <strong>⚠️ Migrare necesară:</strong> Există date vechi în wp_options care trebuie migrate pentru performanță îmbunătățită.
                    <a href="#" id="scfs-start-migration" class="button button-primary" style="margin-left: 10px;">
                        Migrează datele acum
                    </a>
                    <span id="scfs-migration-status" style="margin-left: 10px; display: none;">
                        <span class="spinner is-active" style="float: none; margin: 0 5px;"></span>
                        <span id="scfs-migration-text">Se migrează datele...</span>
                    </span>
                </p>
            </div>
            
            <script>
            jQuery(document).ready(function($) {
                $('#scfs-start-migration').on('click', function(e) {
                    e.preventDefault();
                    
                    var $button = $(this);
                    var $status = $('#scfs-migration-status');
                    var $text = $('#scfs-migration-text');
                    
                    $button.hide();
                    $status.show();
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'scfs_migrate_data',
                            nonce: '<?php echo wp_create_nonce('scfs_migrate_nonce'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                $text.text('Migrare completă!');
                                $status.find('.spinner').remove();
                                setTimeout(function() {
                                    $status.fadeOut();
                                    $button.text('Date migrate cu succes').show();
                                    $button.removeClass('button-primary').addClass('button-secondary');
                                }, 2000);
                                
                                setTimeout(function() {
                                    location.reload();
                                }, 3000);
                            } else {
                                $text.text('Eroare la migrare: ' . response.data);
                                $status.find('.spinner').remove();
                                $button.text('Încearcă din nou').show();
                            }
                        },
                        error: function() {
                            $text.text('Eroare de conexiune');
                            $status.find('.spinner').remove();
                            $button.text('Încearcă din nou').show();
                        }
                    });
                });
            });
            </script>
            <?php endif; ?>
            
            <?php if ($show_debug): ?>
            <div class="scfs-debug-section">
                <?php echo AjaxHandler::debug_database_state(); ?>
                <?php echo AjaxHandler::debug_old_data(); ?>
            </div>
            <?php endif; ?>
            
            <div class="scfs-cards">
                <div class="scfs-card">
                    <h2><span class="dashicons dashicons-admin-settings"></span> Custom Fields</h2>
                    <p>Create and manage custom fields with shortcodes.</p>
                    <div class="scfs-card-actions">
                        <a href="<?php echo admin_url('admin.php?page=scfs-custom-fields'); ?>" class="button button-primary">
                            Manage Fields
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=scfs-custom-fields&action=add'); ?>" class="button">
                            Add New
                        </a>
                    </div>
                </div>
                
                <div class="scfs-card">
                    <h2><span class="dashicons dashicons-share"></span> Social Buttons</h2>
                    <p>Create floating social buttons with icons.</p>
                    <div class="scfs-card-actions">
                        <a href="<?php echo admin_url('admin.php?page=scfs-social-buttons'); ?>" class="button button-primary">
                            Manage Buttons
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=scfs-social-buttons&action=add'); ?>" class="button">
                            Add New
                        </a>
                    </div>
                </div>
                
                <div class="scfs-card">
                    <h2><span class="dashicons dashicons-admin-settings"></span> Social Settings</h2>
                    <p>Configure floating button appearance and behavior.</p>
                    <div class="scfs-card-actions">
                        <a href="<?php echo admin_url('admin.php?page=scfs-social-settings'); ?>" class="button button-primary">
                            Configure
                        </a>
                    </div>
                </div>
                
                <div class="scfs-card">
                    <h2><span class="dashicons dashicons-download"></span> CDN Libraries</h2>
                    <p>Manage icon libraries and external resources.</p>
                    <div class="scfs-card-actions">
                        <a href="<?php echo admin_url('admin.php?page=scfs-social-cdn'); ?>" class="button button-primary">
                            Manage CDNs
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="scfs-stats">
                <?php 
                $custom_fields_count = count($this->custom_fields->get_all());
                $social_buttons_count = count($this->social_buttons->get_all());
                ?>
                <div class="scfs-stat">
                    <span class="count"><?php echo $custom_fields_count; ?></span>
                    <span class="label">Custom Fields</span>
                </div>
                <div class="scfs-stat">
                    <span class="count"><?php echo $social_buttons_count; ?></span>
                    <span class="label">Social Buttons</span>
                </div>
                <div class="scfs-stat">
                    <span class="count"><?php echo $migration_done ? '✅' : '❌'; ?></span>
                    <span class="label">Optimized Storage</span>
                </div>
            </div>
            
            <?php if (current_user_can('manage_options')): ?>
            <div class="scfs-debug-link">
                <a href="<?php echo add_query_arg('debug', '1'); ?>" class="button button-secondary">
                    Debug Database
                </a>
                <a href="<?php echo remove_query_arg('debug'); ?>" class="button button-secondary">
                    Hide Debug
                </a>
            </div>
            
            <div class="scfs-tools" style="margin-top: 30px; padding: 20px; background: #f5f5f5; border-radius: 5px;">
                <h3>Legacy Support Tools</h3>
                <p style="margin-bottom: 15px;">
                    <strong>Compatibilitate:</strong> Sistemul suportă automat ambele formate de shortcode:<br>
                    • <code>[scfs_field name="nume"]</code> (format nou)<br>
                    • <code>[cfs field="nume"]</code> (format vechi - compatibilitate full)
                </p>
                <p style="color: #666; font-size: 13px;">
                    <em>Toate shortcode-urile vechi vor continua să funcționeze fără modificări.</em>
                </p>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    public function admin_assets($hook) {
        if (strpos($hook, 'scfs-') !== false) {
            wp_enqueue_style('scfs-admin', plugins_url('../assets/admin.css', __FILE__));
            wp_enqueue_script('scfs-admin', plugins_url('../assets/admin.js', __FILE__), ['jquery'], null, true);
            
            wp_localize_script('scfs-admin', 'scfs_admin', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('scfs_admin_nonce')
            ]);
        }
    }
    
    public function settings_link($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=scfs-oop') . '">Settings</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
}