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

            // Create form for CSV export
            const $form = $('<form>', {
                method: 'POST',
                action: donapSessionScores.ajaxUrl,
                style: 'display: none;'
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
            if (donapSessionScores.viewId) {
                $form.append($('<input>', {
                    type: 'hidden',
                    name: 'view_id',
                    value: donapSessionScores.viewId
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

            // Reset button state after a delay
            setTimeout(function() {
                $btn.prop('disabled', false).text(originalText);
                $form.remove();
            }, 2000);
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

    // Summary Table functionality
    const SummaryTableExport = {
        init: function() {
            this.bindEvents();
            this.updateSummarySelectionCount();
        },

        bindEvents: function() {
            // Summary table select all functionality
            $(document).on('change', '#donap-summary-select-all-checkbox', this.handleSummarySelectAll.bind(this));
            $(document).on('click', '#donap-summary-select-all', this.toggleSummarySelectAll.bind(this));
            
            // Individual summary checkbox selection
            $(document).on('change', '.donap-summary-row-checkbox', this.handleSummaryIndividualSelect.bind(this));
            
            // Summary export buttons
            $(document).on('click', '#donap-summary-export-selected', this.exportSummarySelected.bind(this));
            $(document).on('click', '#donap-summary-export-all', this.exportSummaryAll.bind(this));
        },

        handleSummarySelectAll: function() {
            const isChecked = $('#donap-summary-select-all-checkbox').is(':checked');
            $('.donap-summary-row-checkbox').prop('checked', isChecked);
            this.updateSummarySelectionCount();
        },

        toggleSummarySelectAll: function(e) {
            e.preventDefault();
            const selectAllCheckbox = $('#donap-summary-select-all-checkbox');
            const currentState = selectAllCheckbox.is(':checked');
            selectAllCheckbox.prop('checked', !currentState).trigger('change');
        },

        handleSummaryIndividualSelect: function() {
            this.updateSummarySelectionCount();
            
            // Update select all checkbox state
            const totalCheckboxes = $('.donap-summary-row-checkbox').length;
            const checkedCheckboxes = $('.donap-summary-row-checkbox:checked').length;
            
            $('#donap-summary-select-all-checkbox').prop('checked', totalCheckboxes === checkedCheckboxes);
        },

        updateSummarySelectionCount: function() {
            const selectedCount = $('.donap-summary-row-checkbox:checked').length;
            $('#donap-summary-selected-count').text(selectedCount);
            
            // Enable/disable export selected button
            $('#donap-summary-export-selected').prop('disabled', selectedCount === 0);
        },

        getSelectedSummaryColumns: function() {
            const selectedColumns = [];
            $('.donap-summary-row-checkbox:checked').each(function() {
                selectedColumns.push($(this).val());
            });
            return selectedColumns;
        },

        exportSummarySelected: function(e) {
            e.preventDefault();
            
            const selectedColumns = this.getSelectedSummaryColumns();
            
            if (selectedColumns.length === 0) {
                alert('لطفا حداقل یک ستون را انتخاب کنید');
                return;
            }

            this.performSummaryExport(selectedColumns);
        },

        exportSummaryAll: function(e) {
            e.preventDefault();
            this.performSummaryExport([]);
        },

        performSummaryExport: function(selectedColumns) {
            // Show loading state
            const exportBtn = selectedColumns.length > 0 ? '#donap-summary-export-selected' : '#donap-summary-export-all';
            const $btn = $(exportBtn);
            const originalText = $btn.text();
            
            $btn.prop('disabled', true).text('در حال اکسپورت...');

            // Create form for CSV export
            const $form = $('<form>', {
                method: 'POST',
                action: donapSessionScores.ajaxUrl,
                style: 'display: none;'
            });

            // Add form fields
            $form.append($('<input>', {
                type: 'hidden',
                name: 'action',
                value: 'donap_export_summary_table'
            }));

            $form.append($('<input>', {
                type: 'hidden',
                name: 'nonce',
                value: donapSessionScores.nonce
            }));

            // Add view_id if available
            if (donapSessionScores.viewId) {
                $form.append($('<input>', {
                    type: 'hidden',
                    name: 'view_id',
                    value: donapSessionScores.viewId
                }));
            }

            // Add form_id
            const formId = $('#donap-form-id').val();
            if (formId) {
                $form.append($('<input>', {
                    type: 'hidden',
                    name: 'form_id',
                    value: formId
                }));
            }

            // Add selected columns
            if (selectedColumns.length > 0) {
                selectedColumns.forEach(function(column) {
                    $form.append($('<input>', {
                        type: 'hidden',
                        name: 'selected_columns[]',
                        value: column
                    }));
                });
            }

            // Add form to page and submit
            $('body').append($form);
            $form.submit();

            // Reset button state after a delay
            setTimeout(function() {
                $btn.prop('disabled', false).text(originalText);
                $form.remove();
            }, 2000);
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        if ($('.donap-session-scores-wrapper').length > 0) {
            SessionScoresTable.init();
            SummaryTableExport.init();
        }
    });

})(jQuery);