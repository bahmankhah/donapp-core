<?php

namespace App\Providers;

use App\Helpers\GiftConfigHelper;
use Kernel\Container;
use Exception;

class AdminServiceProvider
{
    private $main_menu_slug = 'donap-dashboard';
    private $capability = 'manage_options';

    public function register() {}

    public function boot()
    {
        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_styles']);
    }

    /**
     * Register the main Donap admin menu and submenus
     */
    public function register_admin_menu()
    {
        // Main Donap menu
        add_menu_page(
            'داشبورد دناپ',                 // Page title
            'دناپ',                        // Menu title
            $this->capability,             // Capability
            $this->main_menu_slug,         // Menu slug
            [$this, 'dashboard_page'],     // Function
            $this->get_menu_icon(),        // Icon
            30                             // Position
        );

        // Dashboard submenu (rename the main item)
        add_submenu_page(
            $this->main_menu_slug,
            'داشبورد',
            'داشبورد',
            $this->capability,
            $this->main_menu_slug,
            [$this, 'dashboard_page']
        );

        // Settings submenu
        add_submenu_page(
            $this->main_menu_slug,
            'تنظیمات دناپ',
            'تنظیمات',
            $this->capability,
            'donap-settings',
            [$this, 'settings_page']
        );

        // Wallets submenu
        add_submenu_page(
            $this->main_menu_slug,
            'مدیریت کیف پول‌ها',
            'کیف پول‌ها',
            $this->capability,
            'donap-wallets',
            [$this, 'wallets_page']
        );

        // Transactions submenu
        add_submenu_page(
            $this->main_menu_slug,
            'تراکنش‌ها',
            'تراکنش‌ها',
            $this->capability,
            'donap-transactions',
            [$this, 'transactions_page']
        );

        // Reports submenu
        add_submenu_page(
            $this->main_menu_slug,
            'گزارشات',
            'گزارشات',
            $this->capability,
            'donap-reports',
            [$this, 'reports_page']
        );

        // SSO Users submenu
        add_submenu_page(
            $this->main_menu_slug,
            'کاربران SSO',
            'کاربران SSO',
            $this->capability,
            'donap-sso-users',
            [$this, 'sso_users_page']
        );
    }

    /**
     * Add a new submenu to Donap seamlessly
     */
    public function add_submenu($page_title, $menu_title, $menu_slug, $callback, $capability = null)
    {
        $capability = $capability ?: $this->capability;
        
        add_submenu_page(
            $this->main_menu_slug,
            $page_title,
            $menu_title,
            $capability,
            $menu_slug,
            $callback
        );
    }

    /**
     * Register settings for the options page
     */
    public function register_settings()
    {
        // Register gift values settings
        register_setting('donap_gift_settings', 'donap_gift_values');

        add_settings_section(
            'donap_gift_section',
            'تنظیمات مقادیر هدیه',
            [$this, 'gift_section_callback'],
            'donap-gift-settings'
        );

        add_settings_field(
            'gift_till_50k',
            'تا ۵۰ هزار تومان شارژ',
            [$this, 'gift_field_callback'],
            'donap-gift-settings',
            'donap_gift_section',
            ['field' => 'till_50k', 'label' => 'مقدار هدیه برای شارژ تا ۵۰ هزار تومان']
        );

        add_settings_field(
            'gift_50k_to_100k',
            'از ۵۰ هزار تا ۱۰۰ هزار تومان شارژ',
            [$this, 'gift_field_callback'],
            'donap-gift-settings',
            'donap_gift_section',
            ['field' => '50k_to_100k', 'label' => 'مقدار هدیه برای شارژ از ۵۰ هزار تا ۱۰۰ هزار تومان']
        );

        add_settings_field(
            'gift_100k_to_200k',
            'از ۱۰۰ هزار تا ۲۰۰ هزار تومان شارژ',
            [$this, 'gift_field_callback'],
            'donap-gift-settings',
            'donap_gift_section',
            ['field' => '100k_to_200k', 'label' => 'مقدار هدیه برای شارژ از ۱۰۰ هزار تا ۲۰۰ هزار تومان']
        );

        add_settings_field(
            'gift_above_200k',
            'بالای ۲۰۰ هزار تومان شارژ',
            [$this, 'gift_field_callback'],
            'donap-gift-settings',
            'donap_gift_section',
            ['field' => 'above_200k', 'label' => 'مقدار هدیه برای شارژ بالای ۲۰۰ هزار تومان']
        );
    }

    /**
     * Get the menu icon (Dashicon or SVG)
     */
    private function get_menu_icon()
    {
        // Using wallet dashicon, you can replace with custom SVG
        return 'dashicons-money-alt';
    }

    /**
     * Dashboard page content
     */
    public function dashboard_page()
    {
        $userService = Container::resolve('UserService');
        $walletService = Container::resolve('WalletService');
        $transactionService = Container::resolve('TransactionService');
        
        $data = [
            'total_users' => $userService->getTotalSSOUsersCount(),
            'total_balance' => $walletService->getTotalWalletBalance(),
            'total_transactions' => $transactionService->getTotalTransactionsCount(),
            'recent_activity' => $transactionService->getRecentActivity()
        ];
        
        echo view('admin/dashboard', $data);
    }

    /**
     * Settings page content
     */
    public function settings_page()
    {
        echo view('admin/settings');
    }

    /**
     * Wallet Management page content
     */
    public function wallets_page()
    {
        $walletService = Container::resolve('WalletService');
        $userService = Container::resolve('UserService');
        
        // Handle wallet creation
        if (isset($_POST['create_wallet']) && wp_verify_nonce($_POST['wallet_create_nonce'], 'create_wallet_action')) {
            $user_id = sanitize_text_field($_POST['selected_user_id']);
            $initial_amount = intval($_POST['initial_amount']) ?: 0;
            
            if ($user_id) {
                try {
                    $walletService->createWalletForUser($user_id, $initial_amount);
                    $message = 'کیف پول با موفقیت ایجاد شد.';
                } catch (Exception $e) {
                    $error = 'خطا در ایجاد کیف پول: ' . $e->getMessage();
                }
            }
        }
        
        // Handle wallet balance modifications
        if (isset($_POST['modify_wallet']) && wp_verify_nonce($_POST['wallet_nonce'], 'modify_wallet_action')) {
            $user_id = sanitize_text_field($_POST['user_id']);
            $amount = intval($_POST['amount']);
            $action_type = sanitize_text_field($_POST['action_type']);
            $description = sanitize_text_field($_POST['description'] ?? '');
            
            if ($user_id && $amount > 0) {
                try {
                    $walletService->modifyWalletBalance($user_id, $amount, $action_type, $description);
                    $message = 'تراکنش با موفقیت انجام شد.';
                } catch (Exception $e) {
                    $error = 'خطا در انجام تراکنش: ' . $e->getMessage();
                }
            }
        }
        
        // Get pagination parameters
        $page = max(1, intval($_GET['paged'] ?? 1));
        $per_page = 20;
        
        // Get filters
        $filters = [
            'identifier' => $_GET['identifier_filter'] ?? '',
            'type' => $_GET['type_filter'] ?? '',
            'min_balance' => $_GET['min_balance'] ?? ''
        ];
        
        $wallets_result = $walletService->getAllWallets($page, $per_page, $filters);
        
        // Get SSO users for wallet creation dropdown
        $sso_users_result = $userService->getSSOUsersForDropdown(1, 100);
        
        $data = [
            'wallets' => $wallets_result['data'],
            'pagination' => $wallets_result['pagination'],
            'wallet_stats' => $walletService->getWalletStats(),
            'current_filters' => $filters,
            'sso_users' => $sso_users_result
        ];
        
        if (isset($message)) {
            $data['message'] = $message;
        }
        if (isset($error)) {
            $data['error'] = $error;
        }
        
        echo view('admin/wallets', $data);
    }

    /**
     * Transactions page content
     */
    public function transactions_page()
    {
        $transactionService = Container::resolve('TransactionService');
        
        // Get pagination parameters
        $page = max(1, intval($_GET['paged'] ?? 1));
        $per_page = 20;
        
        // Get filters from request
        $filters = [
            'user_filter' => $_GET['user_filter'] ?? '',
            'type_filter' => $_GET['type_filter'] ?? '',
            'start_date' => $_GET['start_date'] ?? '',
            'end_date' => $_GET['end_date'] ?? ''
        ];
        
        $transactions_result = $transactionService->getAllTransactions($filters, $page, $per_page);
        
        $data = [
            'transactions' => $transactions_result['data'],
            'pagination' => $transactions_result['pagination'],
            'transaction_stats' => $transactionService->getTransactionStats(),
            'current_filters' => $filters
        ];
        
        echo view('admin/transactions', $data);
    }

    /**
     * Reports page content
     */
    public function reports_page()
    {
        $report_type = $_GET['report_type'] ?? '';
        $start_date = $_GET['start_date'] ?? '';
        $end_date = $_GET['end_date'] ?? '';
        $format = $_GET['format'] ?? 'html';
        
        $data = [
            'report_type' => $report_type,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'format' => $format,
            'report_data' => [],
            'total_amount' => 0,
            'table_headers' => []
        ];
        
        if (isset($_GET['generate_report']) && !empty($report_type)) {
            $data = array_merge($data, $this->generate_report_data($report_type, $start_date, $end_date));
        }
        
        echo view('admin/reports', $data);
    }

    /**
     * SSO Users page content
     */
    public function sso_users_page()
    {
        $userService = Container::resolve('UserService');
        
        // Get pagination parameters
        $page = max(1, intval($_GET['paged'] ?? 1));
        $per_page = 20;
        $search = $_GET['search'] ?? '';
        
        $sso_users_result = $userService->getAllSSOUsers($page, $per_page, $search);
        
        $data = [
            'sso_users' => $sso_users_result['data'],
            'pagination' => $sso_users_result['pagination'],
            'current_search' => $search,
            'total_sso_users' => $userService->getTotalSSOUsersCount()
        ];
        
        echo view('admin/sso-users', $data);
    }

    /**
     * Gift section description
     */
    public function gift_section_callback()
    {
        echo '<p>تنظیم مقادیر ثابت هدیه برای بازه‌های مختلف شارژ. این مقادیر برای محاسبه اعتبار هدیه هنگام شارژ کیف پول کاربران استفاده می‌شود.</p>';
    }

    /**
     * Gift field callback
     */
    public function gift_field_callback($args)
    {
        $options = get_option('donap_gift_values', []);
        $value = isset($options[$args['field']]) ? $options[$args['field']] : '';
        
        $data = [
            'field' => $args['field'],
            'label' => $args['label'],
            'value' => $value,
            'description' => $args['label']
        ];
        
        echo view('admin/components/gift-field', $data);
    }

    /**
     * Enqueue admin styles
     */
    public function enqueue_admin_styles($hook)
    {
        if (strpos($hook, 'donap') !== false) {
            $plugin_url = plugin_dir_url(dirname(dirname(__FILE__)));
            wp_enqueue_style(
                'donap-admin-styles',
                $plugin_url . 'assets/admin/css/donap-admin.css',
                [],
                '1.0.0'
            );
        }
    }

    /**
     * Generate report data
     */
    private function generate_report_data($report_type, $start_date, $end_date)
    {
        $transactionService = Container::resolve('TransactionService');
        $walletService = Container::resolve('WalletService');
        
        $data = [
            'report_data' => [],
            'total_amount' => 0,
            'table_headers' => []
        ];
        
        switch ($report_type) {
            case 'transactions':
                $data['table_headers'] = ['ID', 'User ID', 'Type', 'Amount', 'Date'];
                $filters = [];
                if ($start_date && $end_date) {
                    $filters['start_date'] = $start_date;
                    $filters['end_date'] = $end_date;
                }
                $results = $transactionService->getAllTransactions($filters, 1, 1000); // Get more for reports
                foreach ($results['data'] as $row) {
                    $data['report_data'][] = [
                        $row->id,
                        $row->identifier,
                        $row->type,
                        number_format($row->amount),
                        date('Y-m-d H:i', strtotime($row->created_at))
                    ];
                    $data['total_amount'] += $row->amount;
                }
                break;
                
            case 'wallets':
                $data['table_headers'] = ['User ID', 'Type', 'Balance', 'Created'];
                $results = $walletService->getAllWallets(1, 1000); // Get more for reports
                foreach ($results['data'] as $row) {
                    $data['report_data'][] = [
                        $row->identifier,
                        $row->type,
                        number_format($row->balance),
                        date('Y-m-d H:i', strtotime($row->created_at))
                    ];
                    $data['total_amount'] += $row->balance;
                }
                break;
                
            default:
                $data['table_headers'] = ['No Data'];
                $data['report_data'] = [['No data available for this report type.']];
                break;
        }
        
        return $data;
    }

    /**
     * Get gift value for a specific range
     */
    public static function get_gift_value($range)
    {
        $options = get_option('donap_gift_values', []);
        return isset($options[$range]) ? floatval($options[$range]) : 0;
    }
}
