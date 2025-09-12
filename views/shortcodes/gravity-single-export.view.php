<?php
// Single Gravity Flow Entry Export Buttons Shortcode View
?>
<div class="donap-gravity-single-export">
    <?php if ($style === 'dropdown'): ?>
        <div class="donap-frontend-single-export-dropdown">
            <button class="donap-frontend-single-export-toggle" type="button">
                <span class="dashicons dashicons-download"></span>
                خروجی ورودی
                <span class="dashicons dashicons-arrow-down-alt2"></span>
            </button>
            <div class="donap-frontend-single-export-menu" style="display: none;">
                <?php if ($show_pdf): ?>
                    <a href="<?php echo rest_url('dnp/v1/gravity/entry/export-pdf?entry_id=' . $entry_id . '&form_id=' . $form_id); ?>"
                        target="_blank" class="donap-frontend-single-export-option">
                        <span class="dashicons dashicons-media-document"></span>
                        PDF
                    </a>
                <?php endif; ?>
                <?php if ($show_excel): ?>
                    <a href="<?php echo rest_url('dnp/v1/gravity/entry/export-excel?entry_id=' . $entry_id . '&form_id=' . $form_id); ?>"
                        target="_blank" class="donap-frontend-single-export-option">
                        <span class="dashicons dashicons-download"></span>
                        Excel
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="donap-frontend-single-export-buttons">
            <?php if ($show_pdf): ?>
                <a href="<?php echo rest_url('dnp/v1/gravity/entry/export-pdf?entry_id=' . $entry_id . '&form_id=' . $form_id); ?>"
                    target="_blank" class="donap-frontend-single-export-btn donap-single-btn-pdf">
                    <span class="dashicons dashicons-media-document"></span>
                    خروجی PDF
                </a>
            <?php endif; ?>
            <?php if ($show_excel): ?>
                <a href="<?php echo rest_url('dnp/v1/gravity/entry/export-excel?entry_id=' . $entry_id . '&form_id=' . $form_id); ?>"
                    target="_blank" class="donap-frontend-single-export-btn donap-single-btn-excel">
                    <span class="dashicons dashicons-download"></span>
                    خروجی Excel
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<style>
    .donap-gravity-single-export {
        margin: 15px 0;
    }

    .donap-frontend-single-export-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .donap-frontend-single-export-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 14px;
        background: #0073aa;
        color: white;
        text-decoration: none;
        border-radius: 4px;
        font-size: 13px;
        font-weight: 500;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
    }

    .donap-frontend-single-export-btn:hover {
        background: #005177;
        color: white;
        text-decoration: none;
        transform: translateY(-1px);
        box-shadow: 0 2px 6px rgba(0, 115, 170, 0.3);
    }

    .donap-single-btn-excel {
        background: #17a2b8;
    }

    .donap-single-btn-excel:hover {
        background: #138496;
    }

    .donap-single-btn-pdf {
        background: #dc3545;
    }

    .donap-single-btn-pdf:hover {
        background: #c82333;
    }

    /* Single Entry Dropdown Style */
    .donap-frontend-single-export-dropdown {
        position: relative;
        display: inline-block;
    }

    .donap-frontend-single-export-toggle {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 10px 14px;
        background: #0073aa;
        color: white;
        border: none;
        border-radius: 4px;
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        transition: background-color 0.3s;
    }

    .donap-frontend-single-export-toggle:hover {
        background: #005177;
    }

    .donap-frontend-single-export-menu {
        position: absolute;
        top: 100%;
        right: 0;
        background: white;
        border: 1px solid #ccc;
        border-radius: 4px;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.15);
        min-width: 130px;
        z-index: 1000;
        margin-top: 3px;
        overflow: hidden;
    }

    .donap-frontend-single-export-option {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 14px;
        text-decoration: none;
        color: #333;
        border-bottom: 1px solid #eee;
        transition: background-color 0.2s;
        font-size: 13px;
    }

    .donap-frontend-single-export-option:last-child {
        border-bottom: none;
    }

    .donap-frontend-single-export-option:hover {
        background: #f8f9fa;
        color: #0073aa;
        text-decoration: none;
    }

    .donap-frontend-single-export-option .dashicons {
        font-size: 16px;
    }

    @media (max-width: 768px) {
        .donap-frontend-single-export-buttons {
            flex-direction: column;
        }

        .donap-frontend-single-export-btn {
            justify-content: center;
            text-align: center;
        }

        .donap-frontend-single-export-menu {
            right: auto;
            left: 0;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle single entry dropdown toggle
        const singleDropdownToggle = document.querySelector('.donap-frontend-single-export-toggle');
        const singleDropdownMenu = document.querySelector('.donap-frontend-single-export-menu');

        if (singleDropdownToggle && singleDropdownMenu) {
            singleDropdownToggle.addEventListener('click', function(e) {
                e.stopPropagation();
                const isVisible = singleDropdownMenu.style.display !== 'none';
                singleDropdownMenu.style.display = isVisible ? 'none' : 'block';
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function() {
                singleDropdownMenu.style.display = 'none';
            });

            // Prevent dropdown from closing when clicking inside menu
            singleDropdownMenu.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }
    });
</script>