
(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Auto-suggest icon classes based on type
        $('#type').on('change', function() {
            var type = $(this).val();
            var iconSuggestions = {
                'url': 'fas fa-link',
                'tel': 'fas fa-phone',
                'mailto': 'fas fa-envelope',
                'whatsapp': 'fab fa-whatsapp',
                'facebook': 'fab fa-facebook-f',
                'twitter': 'fab fa-twitter',
                'instagram': 'fab fa-instagram',
                'linkedin': 'fab fa-linkedin-in',
                'youtube': 'fab fa-youtube'
            };
            
            if (iconSuggestions[type] && !$('#icon').val()) {
                $('#icon').val(iconSuggestions[type]);
            }
        });
        
        // Order field auto-increment for new items
        if ($('#order').val() === '0' || $('#order').val() === '') {
            $('.scfs-order').each(function() {
                var maxOrder = 0;
                var currentOrder = parseInt($(this).text());
                if (currentOrder > maxOrder) {
                    maxOrder = currentOrder;
                }
                $('#order').val(maxOrder + 1);
            });
        }
        
        // Copy shortcode on click
        $(document).on('click', 'code', function() {
            var text = $(this).text();
            var $temp = $('<textarea>');
            $('body').append($temp);
            $temp.val(text).select();
            document.execCommand('copy');
            $temp.remove();
            
            // Show feedback
            var original = $(this).text();
            $(this).text('Copied!').css('color', '#46b450');
            setTimeout(function() {
                $(this).text(original).css('color', '');
            }.bind(this), 1000);
        });
        
        // Toggle form fields based on selection
        $('.scfs-form select, .scfs-form input[type="checkbox"]').on('change', function() {
            var $form = $(this).closest('form');
            
            // Show/hide URL prefix based on type
            var type = $('#type').val();
            var $urlField = $('#url');
            
            if (type === 'tel') {
                if (!$urlField.val().startsWith('tel:')) {
                    $urlField.val('tel:' + $urlField.val().replace('tel:', ''));
                }
            } else if (type === 'mailto') {
                if (!$urlField.val().startsWith('mailto:')) {
                    $urlField.val('mailto:' + $urlField.val().replace('mailto:', ''));
                }
            }
        });
        
        // Quick edit for order
        $(document).on('dblclick', '.scfs-order', function() {
            var $cell = $(this);
            var currentValue = $cell.text().trim();
            var $input = $('<input type="number" class="scfs-order-edit" min="0" step="1">')
                .val(currentValue)
                .css({
                    width: '60px',
                    textAlign: 'center'
                });
            
            $cell.html($input);
            $input.focus().select();
            
            function saveOrder() {
                var newValue = $input.val();
                var id = $cell.closest('tr').find('input[type="checkbox"]').val();
                
                $.post(scfs_admin.ajax_url, {
                    action: 'scfs_update_order',
                    id: id,
                    order: newValue,
                    nonce: scfs_admin.nonce
                }, function(response) {
                    if (response.success) {
                        $cell.text(newValue);
                    } else {
                        alert('Error updating order');
                        $cell.text(currentValue);
                    }
                });
            }
            
            $input.on('blur', saveOrder);
            $input.on('keypress', function(e) {
                if (e.which === 13) { // Enter key
                    saveOrder();
                }
            });
        });
        
        // Bulk actions confirmation
        $('#doaction, #doaction2').on('click', function(e) {
            var action = $(this).closest('.tablenav').find('select[name="action"]').val();
            var action2 = $(this).closest('.tablenav').find('select[name="action2"]').val();
            var selectedAction = action || action2;
            
            if (selectedAction === 'trash' || selectedAction === 'delete_permanently') {
                var message = selectedAction === 'trash' 
                    ? 'Move selected items to trash?' 
                    : 'Permanently delete selected items? This cannot be undone.';
                
                if (!confirm(message)) {
                    e.preventDefault();
                    return false;
                }
            }
        });
        
        // Responsive table handling
        function handleResponsiveTables() {
            if ($(window).width() < 783) {
                $('.wp-list-table').each(function() {
                    var $table = $(this);
                    var $headers = $table.find('thead th');
                    var $rows = $table.find('tbody tr');
                    
                    $rows.each(function() {
                        var $row = $(this);
                        var $cells = $row.find('td');
                        
                        $cells.each(function(index) {
                            if (index >= $headers.length) return;
                            
                            var headerText = $headers.eq(index).text().trim();
                            var $cell = $(this);
                            
                            if (!$cell.find('.mobile-header').length) {
                                $cell.prepend('<span class="mobile-header" style="font-weight:bold;display:block;margin-bottom:5px;color:#0073aa;">' + 
                                              headerText + ':</span>');
                            }
                        });
                    });
                });
            } else {
                $('.mobile-header').remove();
            }
        }
        
        $(window).on('resize', handleResponsiveTables);
        handleResponsiveTables();
        
        // Enhanced CDN form functionality
        function initCDNForm() {
            var $cdnForm = $('.sfb-cdn-form-inline');
            if (!$cdnForm.length) return;
            
            var $header = $cdnForm.find('.sfb-cdn-form-header');
            var $content = $cdnForm.find('.sfb-cdn-form-content');
            var $toggle = $cdnForm.find('.sfb-cdn-form-toggle');
            
            // Collapse/expand functionality
            $header.on('click', function() {
                $cdnForm.toggleClass('collapsed');
                var isCollapsed = $cdnForm.hasClass('collapsed');
                $toggle.text(isCollapsed ? 'Expand form to add CDN' : 'Collapse form');
                
                // Save state to localStorage
                localStorage.setItem('scfs_cdn_form_collapsed', isCollapsed);
            });
            
            // Restore state from localStorage
            var wasCollapsed = localStorage.getItem('scfs_cdn_form_collapsed') === 'true';
            if (wasCollapsed) {
                $cdnForm.addClass('collapsed');
                $toggle.text('Expand form to add CDN');
            }
            
            // Form validation
            var $form = $cdnForm.find('form');
            $form.on('submit', function(e) {
                e.preventDefault();
                
                var $nameField = $('#cdn_name');
                var $typeField = $('#cdn_type');
                var $urlField = $('#cdn_url');
                var hasErrors = false;
                
                // Clear previous errors
                $('.sfb-cdn-form-row').removeClass('error');
                $('.sfb-cdn-form-error').hide();
                
                // Validate name
                if (!$nameField.val().trim()) {
                    $nameField.closest('.sfb-cdn-form-row').addClass('error')
                        .find('.sfb-cdn-form-error').text('Please enter a CDN name').show();
                    hasErrors = true;
                }
                
                // Validate URL
                var url = $urlField.val().trim();
                if (!url) {
                    $urlField.closest('.sfb-cdn-form-row').addClass('error')
                        .find('.sfb-cdn-form-error').text('Please enter a CDN URL').show();
                    hasErrors = true;
                } else if (!isValidUrl(url)) {
                    $urlField.closest('.sfb-cdn-form-row').addClass('error')
                        .find('.sfb-cdn-form-error').text('Please enter a valid URL').show();
                    hasErrors = true;
                }
                
                if (hasErrors) return false;
                
                // Show loading state
                var $submitButton = $(this).find('[name="scfs_add_cdn"]');
                var originalText = $submitButton.text();
                $submitButton.addClass('loading').prop('disabled', true);
                
                // Submit form via AJAX
                $.ajax({
                    url: scfs_admin.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'scfs_add_cdn_ajax',
                        nonce: scfs_admin.nonce,
                        cdn_name: $nameField.val(),
                        cdn_type: $typeField.val(),
                        cdn_url: url
                    },
                    success: function(response) {
                        $submitButton.removeClass('loading').prop('disabled', false).text(originalText);
                        
                        if (response.success) {
                            // Show success message
                            showSuccessMessage('CDN added successfully!');
                            
                            // Clear form
                            $nameField.val('');
                            $urlField.val('');
                            
                            // Reload page after 1 second
                            setTimeout(function() {
                                window.location.reload();
                            }, 1000);
                        } else {
                            alert('Error: ' + response.data);
                        }
                    },
                    error: function() {
                        $submitButton.removeClass('loading').prop('disabled', false).text(originalText);
                        alert('An error occurred. Please try again.');
                    }
                });
            });
            
            // URL validation helper
            function isValidUrl(string) {
                try {
                    new URL(string);
                    return true;
                } catch (_) {
                    return false;
                }
            }
            
            // Success message
            function showSuccessMessage(message) {
                var $message = $('<div class="sfb-cdn-success-message"></div>')
                    .html('<span class="dashicons dashicons-yes"></span> ' + message)
                    .hide()
                    .appendTo('body')
                    .fadeIn(300);
                
                setTimeout(function() {
                    $message.fadeOut(300, function() {
                        $(this).remove();
                    });
                }, 3000);
            }
            
            // Auto-expand form if fields have values
            var $fields = $form.find('input[type="text"], input[type="url"], select');
            $fields.on('input change', function() {
                if ($(this).val() && $cdnForm.hasClass('collapsed')) {
                    $cdnForm.removeClass('collapsed');
                    $toggle.text('Collapse form');
                }
            });
            
            // Search functionality for CDN list
            var $searchInput = $('.sfb-cdn-search input');
            if ($searchInput.length) {
                $searchInput.on('keyup', function() {
                    var searchTerm = $(this).val().toLowerCase();
                    
                    $('.sfb-cdn-list tbody tr').each(function() {
                        var $row = $(this);
                        var text = $row.text().toLowerCase();
                        
                        if (text.indexOf(searchTerm) > -1) {
                            $row.show();
                        } else {
                            $row.hide();
                        }
                    });
                });
            }
        }
        
        // Initialize CDN form
        initCDNForm();
        
        // Toggle CDN card status
        $(document).on('click', '.sfb-status-badge', function(e) {
            e.preventDefault();
            
            var $badge = $(this);
            var $row = $badge.closest('tr');
            var cdnId = $row.find('.button-small').attr('href').split('=')[1];
            var currentStatus = $badge.hasClass('active');
            var newStatus = !currentStatus;
            
            $.ajax({
                url: scfs_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'scfs_toggle_cdn_status',
                    nonce: scfs_admin.nonce,
                    cdn_id: cdnId,
                    status: newStatus ? 1 : 0
                },
                success: function(response) {
                    if (response.success) {
                        $badge.toggleClass('active inactive')
                            .text(newStatus ? 'Active' : 'Inactive');
                        
                        // Update button text
                        var $button = $row.find('.button-small').first();
                        $button.text(newStatus ? 'Deactivate' : 'Activate');
                    }
                }
            });
        });
        
        // Preview CDN changes
        $(document).on('input', 'input[name="cdn_url"]', function() {
            var $input = $(this);
            var url = $input.val();
            var $card = $input.closest('.sfb-cdn-card');
            
            if (url) {
                $card.removeClass('inactive').addClass('active');
            } else {
                $card.removeClass('active').addClass('inactive');
            }
        });
        
        // Migration functionality
        $(document).on('click', '#scfs-start-migration', function(e) {
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
                    nonce: scfs_admin.nonce
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
                        
                        // Reîncarcă pagina după 3 secunde
                        setTimeout(function() {
                            location.reload();
                        }, 3000);
                    } else {
                        $text.text('Eroare la migrare: ' + response.data);
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
    
})(jQuery);