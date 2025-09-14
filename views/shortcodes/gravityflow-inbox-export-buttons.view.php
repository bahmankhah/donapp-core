<?php
/**
 * Gravity Flow Inbox Export Buttons Template
 */

$base_url = home_url('donapp-api');
$csv_url = add_query_arg(['uid' => $user_id], $base_url . '/gravity/inbox/export-csv');
$excel_url = add_query_arg(['uid' => $user_id], $base_url . '/gravity/inbox/export-xlsx');
$pdf_url = add_query_arg(['uid' => $user_id], $base_url . '/gravity/inbox/export-pdf');
?>

<div class="donap-inbox-export-wrapper" style="text-align: <?php echo esc_attr($align); ?>; margin: 20px 0;">
    <!-- Styles -->
    <style>
        .donap-inbox-export-wrapper {
            direction: rtl;
            font-family: 'Vazir', Arial, sans-serif;
        }
        
        .donap-export-buttons {
            display: inline-flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .donap-export-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(102, 126, 234, 0.2);
        }
        
        .donap-export-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
            color: white;
            text-decoration: none;
        }
        
        .donap-export-btn:active {
            transform: translateY(0);
        }
        
        .donap-export-btn.csv {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        
        .donap-export-btn.csv:hover {
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        }
        
        .donap-export-btn.excel {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
        }
        
        .donap-export-btn.excel:hover {
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.4);
        }
        
        .donap-export-btn.pdf {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
        }
        
        .donap-export-btn.pdf:hover {
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.4);
        }
        
        .donap-export-dropdown {
            position: relative;
            display: inline-block;
        }
        
        .donap-dropdown-content {
            display: none;
            position: absolute;
            background: white;
            min-width: 200px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            border-radius: 8px;
            z-index: 1000;
            top: 100%;
            right: 0;
            margin-top: 5px;
            border: 1px solid #e2e8f0;
        }
        
        .donap-dropdown-content a {
            color: #374151;
            padding: 12px 16px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            transition: background 0.2s ease;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .donap-dropdown-content a:last-child {
            border-bottom: none;
            border-radius: 0 0 8px 8px;
        }
        
        .donap-dropdown-content a:first-child {
            border-radius: 8px 8px 0 0;
        }
        
        .donap-dropdown-content a:hover {
            background: #f8fafc;
        }
        
        .donap-export-dropdown:hover .donap-dropdown-content {
            display: block;
        }
        
        .donap-export-label {
            margin-left: 10px;
            color: #64748b;
            font-size: 14px;
            font-weight: 500;
        }
        
        @media (max-width: 768px) {
            .donap-export-buttons {
                justify-content: center;
            }
            
            .donap-export-btn {
                font-size: 12px;
                padding: 8px 12px;
            }
            
            .donap-dropdown-content {
                min-width: 180px;
            }
        }
    </style>
    
    <?php if ($style === 'dropdown'): ?>
        <div class="donap-export-dropdown">
            <button class="donap-export-btn" type="button">
                <i class="fas fa-download"></i>
                <?php echo esc_html($button_text); ?>
                <i class="fas fa-chevron-down"></i>
            </button>
            <div class="donap-dropdown-content">
                <?php if ($show_csv): ?>
                    <a href="<?php echo esc_url($csv_url); ?>" target="_blank">
                        <i class="fas fa-file-csv" style="color: #10b981;"></i>
                        صادرات CSV
                    </a>
                <?php endif; ?>
                <?php if ($show_excel): ?>
                    <a href="<?php echo esc_url($excel_url); ?>" target="_blank">
                        <i class="fas fa-file-excel" style="color: #059669;"></i>
                        صادرات Excel
                    </a>
                <?php endif; ?>
                <?php if ($show_pdf): ?>
                    <a href="<?php echo esc_url($pdf_url); ?>" target="_blank">
                        <i class="fas fa-file-pdf" style="color: #dc2626;"></i>
                        صادرات PDF
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
    <?php else: // buttons style ?>
        <span class="donap-export-label"><?php echo esc_html($button_text); ?>:</span>
        <div class="donap-export-buttons">
            <?php if ($show_csv): ?>
                <a href="<?php echo esc_url($csv_url); ?>" 
                   class="donap-export-btn csv" 
                   target="_blank"
                   title="صادرات فایل CSV">
                    <i class="fas fa-file-csv"></i>
                    CSV
                </a>
            <?php endif; ?>
            
            <?php if ($show_excel): ?>
                <a href="<?php echo esc_url($excel_url); ?>" 
                   class="donap-export-btn excel" 
                   target="_blank"
                   title="صادرات فایل Excel">
                    <i class="fas fa-file-excel"></i>
                    Excel
                </a>
            <?php endif; ?>
            
            <?php if ($show_pdf): ?>
                <a href="<?php echo esc_url($pdf_url); ?>" 
                   class="donap-export-btn pdf" 
                   target="_blank"
                   title="صادرات فایل PDF">
                    <i class="fas fa-file-pdf"></i>
                    PDF
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <!-- Loading indicator (hidden by default) -->
    <div class="donap-export-loading" style="display: none; margin-right: 10px;">
        <i class="fas fa-spinner fa-spin"></i>
        در حال تولید فایل...
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add loading indicator for export buttons
    const exportButtons = document.querySelectorAll('.donap-export-btn[href]');
    const loadingIndicator = document.querySelector('.donap-export-loading');
    
    exportButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (loadingIndicator) {
                loadingIndicator.style.display = 'inline-flex';
                
                // Hide loading after 3 seconds (assuming download starts)
                setTimeout(() => {
                    loadingIndicator.style.display = 'none';
                }, 3000);
            }
        });
    });
});
</script>
