<?php

namespace App\Providers;

use App\Helpers\GiftConfigHelper;
use Kernel\Container;

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
            'Donap Dashboard',           // Page title
            'Donap',                    // Menu title
            $this->capability,          // Capability
            $this->main_menu_slug,      // Menu slug
            [$this, 'dashboard_page'],  // Function
            $this->get_menu_icon(),     // Icon
            30                          // Position
        );

        // Dashboard submenu (rename the main item)
        add_submenu_page(
            $this->main_menu_slug,
            'Dashboard',
            'Dashboard',
            $this->capability,
            $this->main_menu_slug,
            [$this, 'dashboard_page']
        );

        // Settings submenu
        add_submenu_page(
            $this->main_menu_slug,
            'Donap Settings',
            'Settings',
            $this->capability,
            'donap-settings',
            [$this, 'settings_page']
        );

        // Wallet Management submenu
        add_submenu_page(
            $this->main_menu_slug,
            'Wallet Management',
            'Wallet Management',
            $this->capability,
            'donap-wallet-management',
            [$this, 'wallet_management_page']
        );

        // Reports submenu
        add_submenu_page(
            $this->main_menu_slug,
            'Reports',
            'Reports',
            $this->capability,
            'donap-reports',
            [$this, 'reports_page']
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
            'Gift Values Configuration',
            [$this, 'gift_section_callback'],
            'donap-gift-settings'
        );

        add_settings_field(
            'gift_till_50k',
            'Till 50,000 Charge',
            [$this, 'gift_field_callback'],
            'donap-gift-settings',
            'donap_gift_section',
            ['field' => 'till_50k', 'label' => 'Gift percentage for charges up to 50,000']
        );

        add_settings_field(
            'gift_50k_to_100k',
            'From 50,000 Till 100,000 Charge',
            [$this, 'gift_field_callback'],
            'donap-gift-settings',
            'donap_gift_section',
            ['field' => '50k_to_100k', 'label' => 'Gift percentage for charges from 50,000 to 100,000']
        );

        add_settings_field(
            'gift_100k_to_200k',
            'From 100,000 Till 200,000 Charge',
            [$this, 'gift_field_callback'],
            'donap-gift-settings',
            'donap_gift_section',
            ['field' => '100k_to_200k', 'label' => 'Gift percentage for charges from 100,000 to 200,000']
        );

        add_settings_field(
            'gift_above_200k',
            'Above 200,000 Charge',
            [$this, 'gift_field_callback'],
            'donap-gift-settings',
            'donap_gift_section',
            ['field' => 'above_200k', 'label' => 'Gift percentage for charges above 200,000']
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
        $data = [
            'total_users' => $this->get_total_users(),
            'total_balance' => $this->get_total_wallet_balance(),
            'total_transactions' => $this->get_total_transactions(),
            'recent_activity' => $this->get_recent_activity_data()
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
    public function wallet_management_page()
    {
        $data = [
            'active_wallets' => $this->get_active_wallets_count(),
            'pending_transactions' => $this->get_pending_transactions_count(),
            'daily_volume' => $this->get_daily_volume(),
            'wallet_activities' => $this->get_wallet_activities()
        ];
        
        echo view('admin/wallet-management', $data);
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
     * Gift section description
     */
    public function gift_section_callback()
    {
        echo '<p>Configure gift percentages for different charge ranges. These values will be used to calculate bonus credits when users top up their wallets.</p>';
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
     * Get total users with wallets
     */
    private function get_total_users()
    {
        global $wpdb;
        
        if (!$wpdb) {
            return 0;
        }
        
        $table = $wpdb->prefix . 'dnp_user_wallets';
        $count = $wpdb->get_var("SELECT COUNT(DISTINCT identifier) FROM $table");
        return $count ?: 0;
    }

    /**
     * Get total wallet balance across all users
     */
    private function get_total_wallet_balance()
    {
        global $wpdb;
        
        if (!$wpdb) {
            return 0;
        }
        
        $table = $wpdb->prefix . 'dnp_user_wallets';
        $total = $wpdb->get_var("SELECT SUM(balance) FROM $table WHERE type = 'credit'");
        return $total ?: 0;
    }

    /**
     * Get total transactions count
     */
    private function get_total_transactions()
    {
        global $wpdb;
        
        if (!$wpdb) {
            return 0;
        }
        
        $table = $wpdb->prefix . 'dnp_user_wallet_transactions';
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        return $count ?: 0;
    }

    /**
     * Get recent activity data
     */
    private function get_recent_activity_data()
    {
        global $wpdb;
        
        if (!$wpdb) {
            return [];
        }
        
        $table = $wpdb->prefix . 'dnp_user_wallet_transactions';
        
        $results = $wpdb->get_results("
            SELECT * FROM $table 
            ORDER BY created_at DESC 
            LIMIT 10
        ");

        return $results ?: [];
    }

    /**
     * Get active wallets count
     */
    private function get_active_wallets_count()
    {
        global $wpdb;
        
        if (!$wpdb) {
            return 0;
        }
        
        $table = $wpdb->prefix . 'dnp_user_wallets';
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE balance > 0");
        return $count ?: 0;
    }

    /**
     * Get pending transactions count
     */
    private function get_pending_transactions_count()
    {
        global $wpdb;
        
        if (!$wpdb) {
            return 0;
        }
        
        $table = $wpdb->prefix . 'dnp_user_wallet_transactions';
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'pending'");
        return $count ?: 0;
    }

    /**
     * Get daily volume
     */
    private function get_daily_volume()
    {
        global $wpdb;
        
        if (!$wpdb) {
            return 0;
        }
        
        $table = $wpdb->prefix . 'dnp_user_wallet_transactions';
        $today = date('Y-m-d');
        $volume = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(amount) FROM $table WHERE DATE(created_at) = %s AND type IN ('credit_charge', 'charge_gift')",
            $today
        ));
        return $volume ?: 0;
    }

    /**
     * Get wallet activities
     */
    private function get_wallet_activities()
    {
        global $wpdb;
        
        if (!$wpdb) {
            return [];
        }
        
        $table = $wpdb->prefix . 'dnp_user_wallet_transactions';
        
        $results = $wpdb->get_results("
            SELECT * FROM $table 
            ORDER BY created_at DESC 
            LIMIT 20
        ");

        return $results ?: [];
    }

    /**
     * Generate report data
     */
    private function generate_report_data($report_type, $start_date, $end_date)
    {
        global $wpdb;
        
        if (!$wpdb) {
            return [
                'report_data' => [],
                'total_amount' => 0,
                'table_headers' => []
            ];
        }
        
        $data = [
            'report_data' => [],
            'total_amount' => 0,
            'table_headers' => []
        ];
        
        switch ($report_type) {
            case 'transactions':
                $data['table_headers'] = ['ID', 'User ID', 'Type', 'Amount', 'Date'];
                $table = $wpdb->prefix . 'dnp_user_wallet_transactions';
                $where = '';
                if ($start_date && $end_date) {
                    $where = $wpdb->prepare(" WHERE DATE(created_at) BETWEEN %s AND %s", $start_date, $end_date);
                }
                $results = $wpdb->get_results("SELECT * FROM $table $where ORDER BY created_at DESC");
                foreach ($results as $row) {
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
                $table = $wpdb->prefix . 'dnp_user_wallets';
                $results = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC");
                foreach ($results as $row) {
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
