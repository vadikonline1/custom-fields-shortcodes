jQuery(document).ready(function($){
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
