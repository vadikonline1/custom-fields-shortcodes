<?php
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
            'custom_message' => sanitize_text_field($_POST['sfb_custom_message']),
            'show_custom_message' => isset($_POST['sfb_show_custom_message']) ? 1 : 0,
            'show_shortcut_names' => isset($_POST['sfb_show_shortcut_names']) ? 1 : 0
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
        'custom_message' => "Let's chat with US!",
        'show_custom_message' => 1,
        'show_shortcut_names' => 1
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
                    <th scope="row"><label for="sfb_show_custom_message">Display Custom Message</label></th>
                    <td>
                        <label>
                            <input type="checkbox" name="sfb_show_custom_message" id="sfb_show_custom_message" value="1" <?php checked($settings['show_custom_message'], 1); ?>>
                            Show custom message tooltip
                        </label>
                        <p class="description">Enable or disable the display of the custom message tooltip</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="sfb_show_shortcut_names">Shortcut Display</label></th>
                    <td>
                        <label>
                            <input type="checkbox" name="sfb_show_shortcut_names" id="sfb_show_shortcut_names" value="1" <?php checked($settings['show_shortcut_names'], 1); ?>>
                            Show button names in shortcuts
                        </label>
                        <p class="description">When enabled, shortcodes will display button names; when disabled, only icons will be shown</p>
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
                    <div class="sfb-preview-item with-names">
                        <span class="sfb-preview-icon">📱</span>
                        <span class="sfb-preview-text">Mobile Only</span>
                    </div>
                <?php else: ?>
                    <div class="sfb-preview-item icons-only <?php echo ($settings['transparent_icons'] && !$settings['show_names']) ? 'transparent' : ''; ?>">
                        <span class="sfb-preview-icon">🔗</span>
                    </div>
                    <div class="sfb-preview-item icons-only <?php echo ($settings['transparent_icons'] && !$settings['show_names']) ? 'transparent' : ''; ?>">
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
                <br>
                <?php echo $settings['show_custom_message'] ? '✅ Custom message enabled' : '❌ Custom message disabled'; ?>
            </p>
        </div>
    </div>
    <?php
}

// Apelăm funcția settings page
sfb_settings_page();
?>