<?php
// =========================
// CDN MANAGEMENT PAGE
// =========================
function sfb_cdn_management_page(){
    if(isset($_POST['sfb_add_cdn'])){
        $cdn_name = sanitize_text_field($_POST['cdn_name']);
        $cdn_type = sanitize_text_field($_POST['cdn_type']);
        $cdn_url = esc_url_raw($_POST['cdn_url']);
        
        if(!empty($cdn_name) && !empty($cdn_type) && !empty($cdn_url)){
            $cdns = get_option('sfb_custom_cdns', []);
            $cdn_id = sanitize_title($cdn_name) . '_' . uniqid();
            $cdns[$cdn_id] = [
                'name' => $cdn_name,
                'type' => $cdn_type,
                'url' => $cdn_url,
                'active' => true
            ];
            update_option('sfb_custom_cdns', $cdns);
            echo '<div class="updated"><p>CDN added successfully!</p></div>';
        }
    }
    
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
    
    if(isset($_GET['delete_cdn'])){
        $cdn_id = sanitize_text_field($_GET['delete_cdn']);
        if(wp_verify_nonce($_GET['_wpnonce'], 'delete_cdn_' . $cdn_id)){
            $cdns = get_option('sfb_custom_cdns', []);
            unset($cdns[$cdn_id]);
            update_option('sfb_custom_cdns', $cdns);
            echo '<div class="updated"><p>CDN deleted successfully!</p></div>';
        }
    }
    
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
    <?php
}

// Apelăm funcția CDN management page
sfb_cdn_management_page();
?>