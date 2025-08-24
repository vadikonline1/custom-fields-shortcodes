<?php
/*
Plugin Name: Concat Custom Fields Shortcodes
Description: Gestionare câmpuri personalizate și construirea mesajului Contact Info.
Version: 0.0.1
Author: Steel..xD
GitHub Plugin URI: https://github.com/vadikonline1/Contact-Custom-Fields-Wordpress
*/

if (!defined('ABSPATH')) exit;

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

// =======================
// 1. Clasa pentru tabel
// =======================
class CFS_List_Table extends WP_List_Table {
    private $fields;

    public function __construct($fields) {
        parent::__construct([
            'singular'=>'field',
            'plural'=>'fields',
            'ajax'=>false
        ]);
        $this->fields = $fields;
    }

    public function get_columns() {
        return [
            'cb' => '<input type="checkbox" />',
            'name' => 'Nume',
            'value' => 'Valoare',
            'shortcode' => 'Shortcode',
            'php_shortcode'=> 'PHP Shortcode',
            'actions' => 'Acțiuni'
        ];
    }

    public function get_sortable_columns() {
        return ['name'=>['name',false]];
    }

    public function prepare_items() {
        $columns = $this->get_columns();
        $hidden = [];
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = [$columns,$hidden,$sortable];

        $data = [];
        foreach($this->fields as $name=>$value){
            $data[] = ['name'=>$name,'value'=>$value];
        }

        // Căutare
        if(!empty($_REQUEST['s'])){
            $search = strtolower($_REQUEST['s']);
            $data = array_filter($data,function($item) use($search){
                return strpos(strtolower($item['name']),$search)!==false;
            });
        }

        // Sortare
        usort($data,function($a,$b){
            $order = $_REQUEST['order'] ?? 'asc';
            $res = strcmp($a['name'],$b['name']);
            return ($order==='asc')?$res:-$res;
        });

        // Paginare
        $per_page = 20;
        $current_page = $this->get_pagenum();
        $total_items = count($data);
        $data = array_slice($data,($current_page-1)*$per_page,$per_page);

        $this->items = $data;
        $this->set_pagination_args(['total_items'=>$total_items,'per_page'=>$per_page]);
    }

    public function column_cb($item){
        return '<input type="checkbox" name="fields[]" value="'.esc_attr($item['name']).'">';
    }

    public function column_name($item){
        return '<strong>'.esc_html($item['name']).'</strong>';
    }

    public function column_value($item){
        $text = wp_strip_all_tags($item['value']);
        if(strlen($text)>100) $text = substr($text,0,100).'…';
        return '<div style="max-width:300px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="'.esc_attr($item['value']).'">'.esc_html($text).'</div>';
    }

    public function column_shortcode($item){
        $code = '[cfs field="'.esc_attr($item['name']).'"]';
        return '<code class="copy-on-dblclick">'.esc_html($code).'</code>';
    }

    public function column_php_shortcode($item){
        $code = 'echo do_shortcode(\'[cfs field="'.esc_attr($item['name']).'"]\');';
        return '<code class="copy-on-dblclick">'.esc_html($code).'</code>';
    }

    public function column_actions($item){
        return '<button type="button" class="button edit-field" data-name="'.esc_attr($item['name']).'" data-value="'.esc_attr($item['value']).'">Editează</button>';
    }

    public function get_bulk_actions(){
        return ['delete'=>'Șterge'];
    }
}

// =======================
// 2. Admin Menu
// =======================
add_action('admin_menu',function(){
    add_menu_page('Contact - Fields','Contact - Fields','manage_options','custom-fields-shortcodes','cfs_admin_page','dashicons-edit',20);
});

// =======================
// 3. Pagina Admin
// =======================
function cfs_admin_page(){
    $fields = get_option('cfs_fields',[]);
    $contact_message = get_option('contact_info_message','');

    if(isset($_POST['cfs_action']) && check_admin_referer('cfs_save_fields')){
        switch($_POST['cfs_action']){
            case 'add':
                $name = sanitize_key($_POST['field_name']);
                if(!empty($name) && !isset($fields[$name])){
                    $fields[$name] = wp_kses_post($_POST['field_value']);
                    update_option('cfs_fields',$fields);
                }
                break;
            case 'edit':
                $name = sanitize_key($_POST['field_name']);
                if(isset($fields[$name])){
                    $fields[$name] = wp_kses_post($_POST['field_value']);
                    update_option('cfs_fields',$fields);
                }
                break;
            case 'delete_bulk':
                if(!empty($_POST['fields'])){
                    foreach($_POST['fields'] as $f) unset($fields[$f]);
                    update_option('cfs_fields',$fields);
                }
                break;
            case 'import':
                if(!empty($_FILES['import_file']['tmp_name'])){
                    $content = file_get_contents($_FILES['import_file']['tmp_name']);
                    if($_POST['import_type']==='json'){
                        $data = json_decode($content,true);
                    }else{
                        $lines = explode("\n",$content);
                        $data=[];
                        foreach($lines as $line){
                            $parts = str_getcsv($line);
                            if(count($parts)===2) $data[$parts[0]]=$parts[1];
                        }
                    }
                    if(is_array($data)){
                        $fields = array_merge($fields,$data);
                        update_option('cfs_fields',$fields);
                    }
                }
                break;
            case 'save_contact_info':
                $contact_message = wp_kses_post($_POST['contact_info_message']);
                update_option('contact_info_message',$contact_message);
                break;
        }
    }

    $table = new CFS_List_Table($fields);
    $table->prepare_items();
    ?>
    <style>
    /* POPUP ELEGANT CSS */
    .cfs-modal-overlay {
        position: fixed; top:0; left:0; width:100%; height:100%;
        background: rgba(0,0,0,0.6); display:none; z-index:9998;
    }
    .cfs-modal {
        background:#fff; border-radius:8px; padding:20px;
        max-width:700px; width:90%; margin:50px auto;
        position:relative; z-index:9999; box-shadow:0 5px 20px rgba(0,0,0,0.3);
    }
    .cfs-modal h2 { margin-top:0; font-size:1.5em; }
    .cfs-modal textarea { width:100%; padding:8px; font-size:14px; border:1px solid #ccc; border-radius:4px; }
    .cfs-modal select { width:100%; padding:6px; border-radius:4px; margin-bottom:10px; }
    .cfs-modal .cfs-close { position:absolute; top:10px; right:10px; cursor:pointer; font-size:20px; color:#888; }
    .cfs-modal button.button { margin-top:10px; }
    </style>

    <div class="wrap">
        <h1>Custom Fields Shortcodes</h1>
        <div style="margin-bottom:15px;">
            <button id="openImportModal" class="button">Export/Import</button>
            <button id="openAddModal" class="button button-primary">Adaugă un nou câmp</button>
            <button id="openContactInfoModal" class="button button-secondary">Contact Info</button>
        </div>

        <form method="post">
            <?php wp_nonce_field('cfs_save_fields'); ?>
            <input type="hidden" name="cfs_action" value="delete_bulk">
            <?php $table->search_box('Caută','search_id'); ?>
            <?php $table->display(); ?>
        </form>
    </div>

    <!-- MODAL ELEGANT ADD -->
    <div class="cfs-modal-overlay" id="modalAdd">
        <div class="cfs-modal">
            <span class="cfs-close" data-target="#modalAdd">&times;</span>
            <h2>Adaugă un nou câmp</h2>
            <form method="post">
                <?php wp_nonce_field('cfs_save_fields'); ?>
                <input type="hidden" name="cfs_action" value="add">
                <p><label>Nume (minuscule):</label></p>
                <input type="text" name="field_name" required>
                <p><label>Valoare:</label></p>
                <textarea name="field_value" rows="6"></textarea>
                <p><button type="submit" class="button button-primary">Salvează</button></p>
            </form>
        </div>
    </div>

    <!-- MODAL ELEGANT EDIT -->
    <div class="cfs-modal-overlay" id="modalEdit">
        <div class="cfs-modal">
            <span class="cfs-close" data-target="#modalEdit">&times;</span>
            <h2>Editează valoare</h2>
            <form method="post">
                <?php wp_nonce_field('cfs_save_fields'); ?>
                <input type="hidden" name="cfs_action" value="edit">
                <input type="hidden" name="field_name" id="edit_field_name">
                <p><label>Nume:</label></p>
                <input type="text" id="display_field_name" disabled style="background:#f0f0f0;">
                <p><label>Valoare:</label></p>
                <textarea id="edit_field_value" name="field_value" rows="6"></textarea>
                <p><button type="submit" class="button button-primary">Actualizează</button></p>
            </form>
        </div>
    </div>

    <!-- MODAL ELEGANT CONTACT INFO -->
    <div class="cfs-modal-overlay" id="modalContactInfo">
        <div class="cfs-modal">
            <span class="cfs-close" data-target="#modalContactInfo">&times;</span>
            <h2>Contact Info</h2>
            <form method="post">
                <?php wp_nonce_field('cfs_save_fields'); ?>
                <input type="hidden" name="cfs_action" value="save_contact_info">
                <p><label>Text mesaj:</label></p>
                <textarea id="contact_info_message" name="contact_info_message" rows="10"><?php echo esc_textarea(stripslashes($contact_message)); ?></textarea>
                <p><label>Inserare shortcut din tabel:</label></p>
                <select id="cfs_shortcut_select">
                    <option value="">Selectează câmp</option>
                    <?php foreach($fields as $name=>$value): ?>
                        <option value="<?php echo esc_attr($name); ?>"><?php echo esc_html($name); ?></option>
                    <?php endforeach; ?>
                </select>
                <p><button type="button" id="insert_selected_shortcut" class="button">Insert Shortcut</button></p>
                <p><button type="submit" class="button button-primary">Salvează mesajul</button></p>
            </form>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($){
        function openModal(id){ $(id).fadeIn(); }
        function closeModal(id){ $(id).fadeOut(); }

        $('#openAddModal').click(function(){ openModal('#modalAdd'); });
        $('#openContactInfoModal').click(function(){ openModal('#modalContactInfo'); });
        $('.edit-field').click(function(){
            var name = $(this).data('name');
            var value = $(this).data('value');
            $('#edit_field_name').val(name);
            $('#display_field_name').val(name);
            $('#edit_field_value').val(value);
            openModal('#modalEdit');
        });

        $('.cfs-close').click(function(){ closeModal($(this).data('target')); });
        $(window).on('click',function(e){
            if($(e.target).hasClass('cfs-modal-overlay')) $(e.target).fadeOut();
        });

        // Copiere la dublu click
        $(document).on('dblclick','.copy-on-dblclick',function(){
            navigator.clipboard.writeText($(this).text());
            alert('Copiat: '+$(this).text());
        });

        // Insert shortcut în textarea
        $('#insert_selected_shortcut').click(function(){
            var field = $('#cfs_shortcut_select').val();
            if(!field) return;
            var shortcode = '[cfs field="'+field+'"]';
            var ta = $('#contact_info_message');
            ta.val(ta.val()+shortcode);
        });
    });
    </script>

<?php
}

// =======================
// 4. Shortcode
// =======================
add_shortcode('cfs',function($atts){
    $atts=shortcode_atts(['field'=>''],$atts);
    $fields=get_option('cfs_fields',[]);
    if($atts['field']==='contact_info_message') {
        $text = stripslashes(get_option('contact_info_message',''));
        return do_shortcode($text); // procesăm shortcode-urile din text
    }
    return isset($fields[$atts['field']]) ? stripslashes($fields[$atts['field']]) : '';
});
