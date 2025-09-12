<?php
// Gravity Flow Export Buttons Shortcode View
?>
<div class="donap-gravity-export-buttons" style="text-align: <?php echo esc_attr($align); ?>;">
    <?php if ($style === 'dropdown'): ?>
        <div class="donap-frontend-export-dropdown">
            <button class="donap-frontend-export-toggle" type="button">
                <span class="dashicons dashicons-download"></span>
                خروجی گزارش
                <span class="dashicons dashicons-arrow-down-alt2"></span>
            </button>
            <div class="donap-frontend-export-menu" style="display: none;">
                <?php if ($show_csv): ?>
                    <a href="<?php echo rest_url('dnp/v1/gravity/export-csv?uid=' . $user_id); ?>" target="_blank" class="donap-frontend-export-option">
                        <span class="dashicons dashicons-media-spreadsheet"></span>
                        CSV
                    </a>
                <?php endif; ?>
                <?php if ($show_excel): ?>
                    <a href="<?php echo rest_url('dnp/v1/gravity/export-xlsx?uid=' . $user_id); ?>" target="_blank" class="donap-frontend-export-option">
                        <span class="dashicons dashicons-download"></span>
                        Excel
                    </a>
                <?php endif; ?>
                <?php if ($show_pdf): ?>
                    <a href="<?php echo rest_url('dnp/v1/gravity/export-pdf?uid=' . $user_id); ?>" target="_blank" class="donap-frontend-export-option">
                        <span class="dashicons dashicons-media-document"></span>
                        PDF
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="donap-frontend-export-buttons">
            <?php if ($show_csv): ?>
                <a href="<?php echo rest_url('dnp/v1/gravity/export-csv?uid=' . $user_id); ?>" target="_blank" class="donap-frontend-export-btn donap-btn-csv">
                    <span class="dashicons dashicons-media-spreadsheet"></span>
                    خروجی CSV
                </a>
            <?php endif; ?>
            <?php if ($show_excel): ?>
                <a href="<?php echo rest_url('dnp/v1/gravity/export-xlsx?uid=' . $user_id); ?>" target="_blank" class="donap-frontend-export-btn donap-btn-excel">
                    <span class="dashicons dashicons-download"></span>
                    خروجی Excel
                </a>
            <?php endif; ?>
            <?php if ($show_pdf): ?>
                <a href="<?php echo rest_url('dnp/v1/gravity/export-pdf?uid=' . $user_id); ?>" target="_blank" class="donap-frontend-export-btn donap-btn-pdf">
                    <span class="dashicons dashicons-media-document"></span>
                    خروجی PDF
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<style>
    .donap-gravity-export-buttons {
        margin: 20px 0;
    }

    .donap-frontend-export-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .donap-frontend-export-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 16px;
        background: #0073aa;
        color: white;
        text-decoration: none;
        border-radius: 4px;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
    }

    .donap-frontend-export-btn:hover {
        background: #005177;
        color: white;
        text-decoration: none;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0, 115, 170, 0.3);
    }

    .donap-btn-csv {
        background: #28a745;
    }

    .donap-btn-csv:hover {
        background: #218838;
    }

    .donap-btn-excel {
        background: #17a2b8;
    }

    .donap-btn-excel:hover {
        background: #138496;
    }

    .donap-btn-pdf {
        background: #dc3545;
    }

    .donap-btn-pdf:hover {
        background: #c82333;
    }

    /* Dropdown Style */
    .donap-frontend-export-dropdown {
        position: relative;
        display: inline-block;
    }

    .donap-frontend-export-toggle {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 18px;
        background: #0073aa;
        color: white;
        border: none;
        border-radius: 4px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: background-color 0.3s;
    }

    .donap-frontend-export-toggle:hover {
        background: #005177;
    }

    .donap-frontend-export-menu {
        position: absolute;
        top: 100%;
        right: 0;
        background: white;
        border: 1px solid #ccc;
        border-radius: 4px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        min-width: 150px;
        z-index: 1000;
        margin-top: 4px;
        overflow: hidden;
    }

    .donap-frontend-export-option {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 16px;
        text-decoration: none;
        color: #333;
        border-bottom: 1px solid #eee;
        transition: background-color 0.2s;
        font-size: 14px;
    }

    .donap-frontend-export-option:last-child {
        border-bottom: none;
    }

    .donap-frontend-export-option:hover {
        background: #f8f9fa;
        color: #0073aa;
        text-decoration: none;
    }

    .donap-frontend-export-option .dashicons {
        font-size: 18px;
    }

    @media (max-width: 768px) {
        .donap-frontend-export-buttons {
            flex-direction: column;
        }

        .donap-frontend-export-btn {
            justify-content: center;
            text-align: center;
        }

        .donap-frontend-export-menu {
            right: auto;
            left: 0;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle dropdown toggle
        const dropdownToggle = document.querySelector('.donap-frontend-export-toggle');
        const dropdownMenu = document.querySelector('.donap-frontend-export-menu');

        if (dropdownToggle && dropdownMenu) {
            dropdownToggle.addEventListener('click', function(e) {
                e.stopPropagation();
                const isVisible = dropdownMenu.style.display !== 'none';
                dropdownMenu.style.display = isVisible ? 'none' : 'block';
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function() {
                dropdownMenu.style.display = 'none';
            });

            // Prevent dropdown from closing when clicking inside menu
            dropdownMenu.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }
    });
</script>