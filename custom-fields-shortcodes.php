<?php
if (!defined('ABSPATH')) exit;

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

// Table class
class CFS_List_Table extends WP_List_Table {
    private $fields;
    public function __construct($fields) {
        parent::__construct(['singular'=>'field','plural'=>'fields','ajax'=>false]);
        $this->fields = $fields;
    }
    public function get_columns() {
        return [
            'cb'=>'<input type="checkbox" />',
            'name'=>'Name',
            'value'=>'Value',
            'shortcode'=>'Shortcode',
            'php_shortcode'=>'PHP Shortcode',
            'actions'=>'Actions'
        ];
    }
    public function prepare_items() {
        $data = [];
        foreach($this->fields as $name=>$value){
            $data[] = ['name'=>$name,'value'=>$value];
        }
        $columns=$this->get_columns();
        $this->_column_headers=[$columns,[],[]];
        $this->items=$data;
    }
    public function column_cb($item){ return '<input type="checkbox" name="fields[]" value="'.esc_attr($item['name']).'">'; }
    public function column_name($item){ return '<strong>'.esc_html($item['name']).'</strong>'; }
    public function column_value($item){
        $text=wp_strip_all_tags($item['value']);
        if(strlen($text)>100) $text=substr($text,0,100).'…';
        return '<div title="'.esc_attr($item['value']).'">'.esc_html($text).'</div>';
    }
    public function column_shortcode($item){
        $code='[cfs field="'.esc_attr($item['name']).'"]';
        return '<code class="copy-on-dblclick">'.esc_html($code).'</code>';
    }
    public function column_php_shortcode($item){
        $code='echo do_shortcode(\'[cfs field="'.esc_attr($item['name']).'"]\');';
        return '<code class="copy-on-dblclick">'.esc_html($code).'</code>';
    }
    public function column_actions($item){
        return '<button type="button" class="button edit-field" data-name="'.esc_attr($item['name']).'" data-value="'.esc_attr($item['value']).'">Edit</button>';
    }
    public function get_bulk_actions(){ return ['delete'=>'Delete']; }
}

// Admin page
function cfs_admin_page(){
    if(!current_user_can('administrator')) wp_die('Access denied.');

    $fields = get_option('cfs_fields',[]);
    $contact_message = get_option('contact_info_message','');

    if(isset($_POST['cfs_action']) && check_admin_referer('cfs_save_fields')){
        switch($_POST['cfs_action']){
            case 'add':
                $name = sanitize_key($_POST['field_name']);
                if(!empty($name) && !isset($fields[$name])){
                    $fields[$name] = wp_unslash($_POST['field_value']);
                    update_option('cfs_fields',$fields);
                }
                break;
            case 'edit':
                $name = sanitize_key($_POST['field_name']);
                if(isset($fields[$name])){
                    $fields[$name] = wp_unslash($_POST['field_value']);
                    update_option('cfs_fields',$fields);
                }
                break;
            case 'delete_bulk':
                if(!empty($_POST['fields'])){
                    foreach($_POST['fields'] as $f) unset($fields[$f]);
                    update_option('cfs_fields',$fields);
                }
                break;
            case 'import_json':
                if(!empty($_FILES['import_file']['tmp_name'])){
                    $content = file_get_contents($_FILES['import_file']['tmp_name']);
                    $data = json_decode($content,true);
                    if(is_array($data)){
                        $mode = $_POST['import_mode'] ?? 'overwrite';
                        if($mode==='rewrite_all') $fields = [];
                        foreach($data as $key=>$val){
                            $new_key = sanitize_key($key);
                            if($mode==='merge' && isset($fields[$new_key])){
                                $suffix = 1;
                                while(isset($fields[$new_key.'_'.$suffix])) $suffix++;
                                $new_key = $new_key.'_'.$suffix;
                            }
                            $fields[$new_key] = $val;
                        }
                        update_option('cfs_fields', $fields);
                    }
                }
                break;
            case 'save_contact_info':
                $contact_message = wp_unslash($_POST['contact_info_message']);
                update_option('contact_info_message',$contact_message);
                break;
            case 'export_json':
                header('Content-Type: application/json');
                header('Content-Disposition: attachment; filename="cfs-export.json"');
                echo json_encode($fields, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                exit;
        }
        wp_safe_redirect(admin_url('admin.php?page=custom-fields-shortcodes'));
        exit;
    }

    $table = new CFS_List_Table($fields);
    $table->prepare_items();
    ?>
    <div class="wrap">
        <h1>Custom Fields Shortcodes</h1>

        <div style="margin-bottom:15px;">
            <button id="openImportModal" class="button">Import JSON</button>
            <form method="post" style="display:inline-block; margin-left:10px;">
                <?php wp_nonce_field('cfs_save_fields'); ?>
                <input type="hidden" name="cfs_action" value="export_json">
                <button type="submit" class="button">Export JSON</button>
            </form>
            <button id="openAddModal" class="button button-primary" style="margin-left:10px;">Add New Field</button>
            <button id="openContactInfoModal" class="button button-secondary" style="margin-left:10px;">Contact Info</button>
        </div>

        <form method="post">
            <?php wp_nonce_field('cfs_save_fields'); ?>
            <input type="hidden" name="cfs_action" value="delete_bulk">
            <?php $table->display(); ?>
        </form>
    </div>

    <!-- MODALS -->
    <!-- Import JSON Modal -->
    <div class="cfs-modal-overlay" id="modalImport">
        <div class="cfs-modal">
            <span class="cfs-close" data-target="#modalImport">&times;</span>
            <h2>Import JSON</h2>
            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('cfs_save_fields'); ?>
                <input type="hidden" name="cfs_action" value="import_json">
                <p><label>Select JSON File:</label></p>
                <input type="file" name="import_file" accept=".json" required>
                <p><strong>Import Options:</strong></p>
                <p><label><input type="radio" name="import_mode" value="overwrite" checked> Overwrite existing fields</label><br>
                <small>If a field exists, its value will be replaced with the new one.</small></p>
                <p><label><input type="radio" name="import_mode" value="merge"> Merge with existing fields</label><br>
                <small>If a field exists, a suffix _1, _2, ... will be added until unique.</small></p>
                <p><label><input type="radio" name="import_mode" value="rewrite_all"> Rewrite all</label><br>
                <small>All existing fields will be deleted before import.</small></p>
                <div class="cfs-modal-footer">
                    <button type="submit" class="button button-primary">Import JSON</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Field Modal -->
    <div class="cfs-modal-overlay" id="modalAdd">
        <div class="cfs-modal">
            <span class="cfs-close" data-target="#modalAdd">&times;</span>
            <h2>Add New Field</h2>
            <form method="post">
                <?php wp_nonce_field('cfs_save_fields'); ?>
                <input type="hidden" name="cfs_action" value="add">
                <p><label>Name (lowercase):</label></p>
                <input type="text" name="field_name" required>
                <p><label>Value:</label></p>
                <?php wp_editor('', 'field_value', [
                    'textarea_name'=>'field_value',
                    'media_buttons'=>true,
                    'teeny'=>false,
                    'quicktags'=>true,
                    'textarea_rows'=>10
                ]); ?>
                <div class="cfs-modal-footer">
                    <button type="submit" class="button button-primary">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Field Modal -->
    <div class="cfs-modal-overlay" id="modalEdit">
        <div class="cfs-modal">
            <span class="cfs-close" data-target="#modalEdit">&times;</span>
            <h2>Edit Field</h2>
            <form method="post">
                <?php wp_nonce_field('cfs_save_fields'); ?>
                <input type="hidden" name="cfs_action" value="edit">
                <input type="hidden" name="field_name" id="edit_field_name">
                <p><label>Name:</label></p>
                <input type="text" id="display_field_name" disabled style="background:#f0f0f0;">
                <p><label>Value:</label></p>
                <?php wp_editor('', 'edit_field_value', [
                    'textarea_name'=>'field_value',
                    'media_buttons'=>true,
                    'teeny'=>false,
                    'quicktags'=>true,
                    'textarea_rows'=>10
                ]); ?>
                <div class="cfs-modal-footer">
                    <button type="submit" class="button button-primary">Update</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Contact Info Modal -->
    <div class="cfs-modal-overlay" id="modalContactInfo">
        <div class="cfs-modal">
            <span class="cfs-close" data-target="#modalContactInfo">&times;</span>
            <h2>Contact Info</h2>
            <form method="post">
                <?php wp_nonce_field('cfs_save_fields'); ?>
                <input type="hidden" name="cfs_action" value="save_contact_info">
                <?php wp_editor(stripslashes($contact_message), 'contact_info_message', [
                    'textarea_name'=>'contact_info_message',
                    'media_buttons'=>true,
                    'teeny'=>false,
                    'quicktags'=>true,
                    'textarea_rows'=>12
                ]); ?>
                <p><button type="button" id="toggle_fields_list" class="button">Insert Field</button></p>
                <div id="fields_list">
                    <?php foreach($fields as $name=>$value): ?>
                        <div class="cfs-field-item" data-field="<?php echo esc_attr($name); ?>"><?php echo esc_html($name); ?></div>
                    <?php endforeach; ?>
                </div>
                <div class="cfs-modal-footer">
                    <button type="submit" class="button button-primary">Save Message</button>
                </div>
            </form>
        </div>
    </div>

    <!-- JS -->
    <script>
    jQuery(document).ready(function($){
        $('#openImportModal').click(()=>$('#modalImport').fadeIn());
        $('#openAddModal').click(()=>$('#modalAdd').fadeIn());
        $('#openContactInfoModal').click(()=>$('#modalContactInfo').fadeIn());

        $('.cfs-close').click(function(){ $($(this).data('target')).fadeOut(); });

        // Fix: load value in editor after modal open
        $('.edit-field').click(function(){
            var name=$(this).data('name');
            var value=$(this).data('value');
            $('#edit_field_name').val(name);
            $('#display_field_name').val(name);
            $('#modalEdit').fadeIn(function(){
                if(tinymce.get('edit_field_value')){
                    tinymce.get('edit_field_value').setContent(value);
                }else{
                    $('#edit_field_value').val(value);
                }
            });
        });

        $(document).on('dblclick','.copy-on-dblclick',function(){
            navigator.clipboard.writeText($(this).text());
            alert('Copied: '+$(this).text());
        });

        $('#toggle_fields_list').click(function(){ $('#fields_list').toggle(); });

        $(document).on('dblclick','.cfs-field-item',function(){
            var field=$(this).data('field');
            var shortcode='[cfs field="'+field+'"]';
            if(tinymce.get('contact_info_message')){
                tinymce.get('contact_info_message').execCommand('mceInsertContent',false,shortcode);
            }else $('#contact_info_message').val($('#contact_info_message').val()+shortcode);
        });
    });
    </script>

    <!-- CSS -->
    <style>
    .cfs-modal-overlay{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);backdrop-filter: blur(2px);display:none;z-index:9998;}
    .cfs-modal{background:#fff;border-radius:12px;padding:25px;max-width:700px;width:90%;margin:50px auto;position:relative;z-index:9999;box-shadow:0 8px 25px rgba(0,0,0,0.35);max-height:80vh;overflow-y:auto;transition: all 0.3s ease;display:flex;flex-direction:column;}
    .cfs-modal h2{margin-top:0;font-size:1.7em;font-weight:600;color:#333;}
    .cfs-modal .cfs-close{position:absolute;top:12px;right:12px;cursor:pointer;font-size:24px;color:#555;transition:color 0.2s ease;}
    .cfs-modal .cfs-close:hover{color:#000;}
    .cfs-modal-footer{position:sticky;bottom:0;background:#fff;padding-top:10px;margin-top:auto;border-top:1px solid #ddd;}
    #fields_list{display:none;border:1px solid #ccc;border-radius:6px;padding:10px;margin:10px 0;max-height:200px;overflow:auto;background:#fafafa;}
    .cfs-field-item{cursor:pointer;padding:5px 8px;border-radius:4px;transition: background 0.2s ease;}
    .cfs-field-item:hover{background:#e0f7ff;}
    @media (max-width: 768px){.cfs-modal{width:95%;margin:30px auto;padding:20px;} .cfs-modal h2{font-size:1.4em;} #fields_list{max-height:150px;}}
    </style>

<?php
}

// Shortcode
add_shortcode('cfs',function($atts){
    $atts=shortcode_atts(['field'=>''],$atts);
    $fields=get_option('cfs_fields',[]);
    if($atts['field']==='contact_info_message') return do_shortcode(stripslashes(get_option('contact_info_message','')));
    return isset($fields[$atts['field']])?stripslashes($fields[$atts['field']]):'';
});

