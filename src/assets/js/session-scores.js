/**
 * Session Scores Table JavaScript
 * Handles interactive features like checkboxes and CSV export
 */

(function($) {
    'use strict';

    const SessionScoresTable = {
        init: function() {
            this.bindEvents();
            this.updateSelectionCount();
            this.updateSelectAllButtonText();
        },

        bindEvents: function() {
            // Select All functionality - bind to both checkbox and button
            $(document).on('change', '#donap-select-all-checkbox', this.handleSelectAll.bind(this));
            $(document).on('click', '#donap-select-all', this.toggleSelectAll.bind(this));
            
            // Individual checkbox selection
            $(document).on('change', '.donap-entry-checkbox', this.handleIndividualSelect.bind(this));
            
            // Export buttons
            $(document).on('click', '#donap-export-selected', this.exportSelected.bind(this));
            $(document).on('click', '#donap-export-all', this.exportAll.bind(this));
        },

        handleSelectAll: function() {
            const isChecked = $('#donap-select-all-checkbox').is(':checked');
            $('.donap-entry-checkbox').prop('checked', isChecked);
            this.updateSelectionCount();
            this.updateSelectAllButtonText();
        },

        toggleSelectAll: function(e) {
            e.preventDefault();
            const selectAllCheckbox = $('#donap-select-all-checkbox');
            const currentState = selectAllCheckbox.is(':checked');
            selectAllCheckbox.prop('checked', !currentState).trigger('change');
        },

        handleIndividualSelect: function() {
            SessionScoresTable.updateSelectionCount();
            
            // Update select all checkbox state
            const totalCheckboxes = $('.donap-entry-checkbox').length;
            const checkedCheckboxes = $('.donap-entry-checkbox:checked').length;
            
            $('#donap-select-all-checkbox').prop('checked', totalCheckboxes === checkedCheckboxes);
            SessionScoresTable.updateSelectAllButtonText();
        },

        updateSelectionCount: function() {
            const selectedCount = $('.donap-entry-checkbox:checked').length;
            $('#donap-selected-count').text(selectedCount);
            
            // Enable/disable export selected button
            $('#donap-export-selected').prop('disabled', selectedCount === 0);
        },

        updateSelectAllButtonText: function() {
            const totalCheckboxes = $('.donap-entry-checkbox').length;
            const checkedCheckboxes = $('.donap-entry-checkbox:checked').length;
            const selectAllButton = $('#donap-select-all');
            
            if (checkedCheckboxes === totalCheckboxes && totalCheckboxes > 0) {
                selectAllButton.text('لغو انتخاب همه');
            } else {
                selectAllButton.text('انتخاب همه');
            }
        },

        getSelectedIds: function() {
            const selectedIds = [];
            $('.donap-entry-checkbox:checked').each(function() {
                selectedIds.push($(this).val());
            });
            return selectedIds;
        },

        exportSelected: function(e) {
            e.preventDefault();
            
            const selectedIds = SessionScoresTable.getSelectedIds();
            
            if (selectedIds.length === 0) {
                alert(donapSessionScores.strings.selectItems);
                return;
            }

            SessionScoresTable.performExport(selectedIds);
        },

        exportAll: function(e) {
            e.preventDefault();
            SessionScoresTable.performExport([]);
        },

        performExport: function(selectedIds) {
            // Show loading state
            const exportBtn = selectedIds.length > 0 ? '#donap-export-selected' : '#donap-export-all';
            const $btn = $(exportBtn);
            const originalText = $btn.text();
            
            $btn.prop('disabled', true).text('در حال اکسپورت...');

            // Get view_id from the inline script
            let viewId = '';
            if (typeof window.donapViewId !== 'undefined') {
                viewId = window.donapViewId;
            }

            // Create form for CSV export
            const $form = $('<form>', {
                method: 'POST',
                action: donapSessionScores.ajaxUrl,
                style: 'display: none;',
                target: '_blank' // Open in new tab to avoid page reload
            });

            // Add form fields
            $form.append($('<input>', {
                type: 'hidden',
                name: 'action',
                value: 'donap_export_selected_scores'
            }));

            $form.append($('<input>', {
                type: 'hidden',
                name: 'nonce',
                value: donapSessionScores.nonce
            }));

            // Add view_id if available
            if (viewId) {
                $form.append($('<input>', {
                    type: 'hidden',
                    name: 'view_id',
                    value: viewId
                }));
            }

            // Add selected IDs
            if (selectedIds.length > 0) {
                selectedIds.forEach(function(id) {
                    $form.append($('<input>', {
                        type: 'hidden',
                        name: 'selected_ids[]',
                        value: id
                    }));
                });
            }

            // Add form to page and submit
            $('body').append($form);
            $form.submit();

            // Reset button state and remove form after a delay
            setTimeout(function() {
                $btn.prop('disabled', false).text(originalText);
                $form.remove();
                SessionScoresTable.showNotification('درخواست اکسپورت ارسال شد', 'success');
            }, 1000);
        },

        showNotification: function(message, type) {
            type = type || 'info';
            
            const $notification = $('<div>', {
                class: 'donap-notification donap-notification-' + type,
                text: message,
                css: {
                    position: 'fixed',
                    top: '20px',
                    right: '20px',
                    backgroundColor: type === 'error' ? '#dc3545' : '#28a745',
                    color: 'white',
                    padding: '12px 20px',
                    borderRadius: '4px',
                    zIndex: 9999,
                    boxShadow: '0 4px 6px rgba(0,0,0,0.1)'
                }
            });

            $('body').append($notification);

            // Auto remove after 3 seconds
            setTimeout(function() {
                $notification.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 3000);
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        if ($('.donap-session-scores-wrapper').length > 0) {
            SessionScoresTable.init();
        }
    });

})(jQuery);