jQuery(document).ready(function($){
    // Settings page functionality
    function toggleTransparentOptions() {
        var showNames = $('#sfb_show_names').is(':checked');
        
        if(showNames) {
            $('#transparent_icons_row').hide();
            $('#sfb_transparent_icons').prop('checked', false);
        } else {
            $('#transparent_icons_row').show();
        }
    }
    
    $('#sfb_show_names').change(toggleTransparentOptions);
    toggleTransparentOptions();

    // Add/Edit form functionality
    $('#sfb_icon_type').change(function(){
        if($(this).val() === 'html'){
            $('#icon_class_row').hide();
            $('#icon_html_row').show();
        } else {
            $('#icon_class_row').show();
            $('#icon_html_row').hide();
        }
    });
});