<?php
// =========================
// TRASH MANAGEMENT PAGE
// =========================
function sfb_trash_management_page(){
    $trash_items = get_option('sfb_trash', []);
    
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
        $trash_items = get_option('sfb_trash', []);
    }
    
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

// Apelăm funcția trash management page
sfb_trash_management_page();
?>
