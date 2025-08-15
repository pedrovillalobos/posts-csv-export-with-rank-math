jQuery(document).ready(function($) {
    'use strict';
    
    // Handle form submission
    $('#wperm-export-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitBtn = $('#wperm-export-btn');
        var $spinner = $('.spinner');
        
        // Get form data
        var formData = {
            action: 'wperm_export_csv',
            nonce: wperm_ajax.nonce,
            post_type: $('#post_type').val(),
            post_status: $('#post_status').val(),
            date_from: $('#date_from').val(),
            date_to: $('#date_to').val()
        };
        
        // Show loading state
        $submitBtn.prop('disabled', true).text(wperm_ajax.strings.exporting);
        $spinner.addClass('is-active');
        
        // Make AJAX request
        $.ajax({
            url: wperm_ajax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    // Create and download CSV file
                    downloadCSV(response.data.csv_data, response.data.filename);
                    
                    // Show success message
                    showMessage(wperm_ajax.strings.export_complete, 'success');
                } else {
                    // Show error message
                    showMessage(response.data || wperm_ajax.strings.export_error, 'error');
                }
            },
            error: function() {
                // Show error message
                showMessage(wperm_ajax.strings.export_error, 'error');
            },
            complete: function() {
                // Reset loading state
                $submitBtn.prop('disabled', false).text('Export to CSV');
                $spinner.removeClass('is-active');
            }
        });
    });
    
    /**
     * Download CSV file
     */
    function downloadCSV(csvData, filename) {
        // Create blob
        var blob = new Blob([csvData], { type: 'text/csv;charset=utf-8;' });
        
        // Create download link
        var link = document.createElement('a');
        if (link.download !== undefined) {
            // Create a link to the blob
            var url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', filename);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    }
    
    /**
     * Show message
     */
    function showMessage(message, type) {
        // Remove existing messages
        $('.wperm-message').remove();
        
        // Create message element
        var $message = $('<div class="wperm-message notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        
        // Add to page
        $('.wperm-container').prepend($message);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $message.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    // Date validation
    $('#date_from, #date_to').on('change', function() {
        var dateFrom = $('#date_from').val();
        var dateTo = $('#date_to').val();
        
        if (dateFrom && dateTo && dateFrom > dateTo) {
            showMessage('Date "From" cannot be later than date "To".', 'error');
            $(this).val('');
        }
    });
    
    // Debug button handler
    $('#wperm-debug-btn').on('click', function() {
        var $btn = $(this);
        
        $btn.prop('disabled', true).text('Debugging...');
        
        $.ajax({
            url: wperm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wperm_debug_data',
                nonce: wperm_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    var debugInfo = 'Post: ' + response.data.post_title + ' (ID: ' + response.data.post_id + ')\n\n';
                    debugInfo += 'Rank Math Meta Data:\n';
                    
                    for (var key in response.data.debug_data) {
                        debugInfo += key + ': ' + JSON.stringify(response.data.debug_data[key]) + '\n';
                    }
                    
                    alert(debugInfo);
                } else {
                    showMessage('Debug failed: ' + response.data, 'error');
                }
            },
            error: function() {
                showMessage('Debug request failed.', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false).text('Debug Rank Math Data');
            }
        });
    });
});
