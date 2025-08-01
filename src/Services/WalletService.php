<?php

namespace App\Services;

use App\Facades\Wallet as FacadesWallet;
use App\Core\TransactionType;
use App\Core\WalletType;
use App\Models\Wallet;
use Exception;
use Kernel\Container;

class WalletService{
    
    protected $walletModel;

    public function __construct()
    {
        $this->walletModel = new Wallet();
    }
    
    public function settlementRequest($identifier){
        $balance = FacadesWallet::cash()->getBalance($identifier);
        if($balance <= 0){
            throw new Exception('Wallet balance is zero', 400);
        }
        $this->updateBalance($identifier, WalletType::CASH, -$balance, TransactionType::SETTLEMENT_REQUEST);
        $this->updateBalance($identifier, WalletType::SUSPENDED, $balance, TransactionType::SETTLEMENT_REQUEST);
        return $balance;
    }

    public function findUserWallets($identifier)
    {
        return $this->walletModel->where('identifier', '=', $identifier)->get();
    }

    public function getAvailableCredit($identifier, $useCash = true)
    {
        if(!$useCash) {
            return FacadesWallet::credit()->getBalance($identifier);
        }
        return FacadesWallet::virtualCreditCash()->getBalance($identifier);

    }

    public function increaseCredit($identifier, $amount){
        appLogger("WalletService::increaseCredit called - Identifier: {$identifier}, Amount: {$amount}");
        
        $result = $this->updateBalance($identifier, WalletType::CREDIT, abs($amount), TransactionType::CREDIT_CHARGE);
        
        appLogger("WalletService::increaseCredit result: " . ($result ? 'success' : 'failed'));
        
        return $result;
    }

    public function addGift($identifier, $amount){
        appLogger("WalletService::addGift called - Identifier: {$identifier}, Amount: {$amount}");
        
        $result = $this->updateBalance($identifier, WalletType::CREDIT, abs($amount), TransactionType::CHARGE_GIFT);
        
        appLogger("WalletService::addGift result: " . ($result ? 'success' : 'failed'));
        
        return $result;
    }

    public function decreaseCredit($identifier, $amount, $useCash = true){
        if(!$useCash) {
            return FacadesWallet::credit()->updateBalance($identifier, -$amount);
        }
        return FacadesWallet::virtualCreditCash()->decreaseBalance($identifier, $amount);
    }

    public function updateBalance($identifier, $walletType, $amount, $transactionType = null){
        appLogger("WalletService::updateBalance called - Identifier: {$identifier}, WalletType: {$walletType}, Amount: {$amount}, TransactionType: {$transactionType}");
        
        if(!in_array($walletType, ['coin', 'credit', 'cash', 'suspended'])){
            appLogger("WalletService::updateBalance error - Invalid wallet type: {$walletType}");
            throw new \Exception('allowed wallets: coin, credit, cash', 400);
        }
        
        try {
            $updated = FacadesWallet::$walletType()->updateBalance($identifier, $amount, $transactionType);
            appLogger("WalletService::updateBalance result: " . ($updated ? 'success' : 'failed') . " - Updated: " . json_encode($updated));
            return $updated;
        } catch (\Exception $e) {
            appLogger("WalletService::updateBalance exception: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get all wallets for admin view with pagination
     */
    public function getAllWallets($page = 1, $per_page = 20, $filters = [])
    {
        global $wpdb;
        
        if (!$wpdb) {
            return [
                'data' => [],
                'pagination' => [
                    'current_page' => 1,
                    'per_page' => $per_page,
                    'total_items' => 0,
                    'total_pages' => 0
                ]
            ];
        }
        
        $offset = ($page - 1) * $per_page;
        
        $query = $this->walletModel->newQuery()
            ->orderBy('created_at', 'DESC');

        // Apply filters if provided
        if (!empty($filters['identifier'])) {
            $query->where('identifier', 'LIKE', '%' . $filters['identifier'] . '%');
        }
        
        if (!empty($filters['type'])) {
            $query->where('type', '=', $filters['type']);
        }
        
        if (isset($filters['min_balance']) && is_numeric($filters['min_balance'])) {
            $query->where('balance', '>=', intval($filters['min_balance']), '%d');
        }

        // Get total count for pagination (using direct SQL to avoid query builder issues)
        $table_name = $wpdb->prefix . 'dnp_user_wallets';
        $where_conditions = [];
        $where_values = [];

        if (!empty($filters['identifier'])) {
            $where_conditions[] = "identifier LIKE %s";
            $where_values[] = '%' . $filters['identifier'] . '%';
        }
        
        if (!empty($filters['type'])) {
            $where_conditions[] = "type = %s";
            $where_values[] = $filters['type'];
        }
        
        if (isset($filters['min_balance']) && is_numeric($filters['min_balance'])) {
            $where_conditions[] = "balance >= %d";
            $where_values[] = intval($filters['min_balance']);
        }

        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        $count_sql = "SELECT COUNT(*) FROM {$table_name} {$where_clause}";
        
        if (!empty($where_values)) {
            $total_items = intval($wpdb->get_var($wpdb->prepare($count_sql, $where_values)));
        } else {
            $total_items = intval($wpdb->get_var($count_sql));
        }

        $total_pages = ceil($total_items / $per_page);
        $page = max(1, min($page, $total_pages));

        // Get paginated results
        $query->limit($per_page);
        $sql = $query->sql() . " OFFSET $offset";
        $results = $wpdb->get_results($sql);

        return [
            'data' => $results ?: [],
            'pagination' => [
                'current_page' => $page,
                'per_page' => $per_page,
                'total_items' => (int)$total_items,
                'total_pages' => $total_pages
            ]
        ];
    }
    
    /**
     * Get wallet statistics for admin dashboard
     */
    public function getWalletStats()
    {
        global $wpdb;
        
        if (!$wpdb) {
            return [
                'total_wallets' => 0,
                'total_balance' => 0,
                'avg_balance' => 0
            ];
        }
        
        // Use direct SQL queries instead of model query builder to avoid issues
        $table_name = $wpdb->prefix . 'dnp_user_wallets';
        
        // Count total wallets
        $total_wallets = intval($wpdb->get_var("SELECT COUNT(*) FROM {$table_name}"));
        
        // Sum of credit wallet balances
        $total_balance = floatval($wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(balance), 0) FROM {$table_name} WHERE type = %s",
            'credit'
        )));
        
        // Average balance for credit wallets with balance > 0
        $avg_balance = floatval($wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(AVG(balance), 0) FROM {$table_name} WHERE type = %s AND balance > 0",
            'credit'
        )));
        
        return [
            'total_wallets' => $total_wallets,
            'total_balance' => $total_balance,
            'avg_balance' => $avg_balance
        ];
    }
    
    /**
     * Get total users with wallets
     */
    public function getTotalUsersWithWallets()
    {
        global $wpdb;
        
        if (!$wpdb) {
            return 0;
        }
        
        $table_name = $wpdb->prefix . 'dnp_user_wallets';
        $count = intval($wpdb->get_var("SELECT COUNT(DISTINCT identifier) FROM {$table_name}"));
        return $count ?: 0;
    }
    
    /**
     * Get total wallet balance across all users
     */
    public function getTotalWalletBalance()
    {
        global $wpdb;
        
        if (!$wpdb) {
            return 0;
        }
        
        $table_name = $wpdb->prefix . 'dnp_user_wallets';
        $total = floatval($wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(balance), 0) FROM {$table_name} WHERE type = %s",
            'credit'
        )));
        return $total ?: 0;
    }
    
    /**
     * Get active wallets count
     */
    public function getActiveWalletsCount()
    {
        global $wpdb;
        
        if (!$wpdb) {
            return 0;
        }
        
        $table_name = $wpdb->prefix . 'dnp_user_wallets';
        $count = intval($wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE balance > 0"));
        return $count ?: 0;
    }
    
    /**
     * Modify wallet balance (for admin use)
     */
    public function modifyWalletBalance($identifier, $amount, $action_type, $description = null)
    {
        $transactionType = null;
        
        switch ($action_type) {
            case 'add':
                $transactionType = TransactionType::ADMIN;
                return $this->updateBalance($identifier, WalletType::CREDIT, abs($amount), $transactionType);
                
            case 'subtract':
                $transactionType = TransactionType::ADMIN;
                return $this->updateBalance($identifier, WalletType::CREDIT, -abs($amount), $transactionType);
                
            default:
                throw new Exception('Invalid action type', 400);
        }
    }
    
    /**
     * Create wallet for user with initial balance
     */
    public function createWalletForUser($user_id, $initial_amount = 0, $wallet_type = WalletType::CREDIT)
    {
        if (!$user_id) {
            throw new Exception('User ID is required', 400);
        }
        
        // Validate wallet type
        $valid_types = [WalletType::CREDIT, WalletType::CASH, WalletType::SUSPENDED, WalletType::COIN];
        if (!in_array($wallet_type, $valid_types)) {
            throw new Exception('Invalid wallet type', 400);
        }
        
        // Use UserService to get user details
        $userService = Container::resolve('UserService');
        $user = $userService->getUserForWalletCreation($user_id);
        
        if (!$user) {
            throw new Exception('SSO user not found', 404);
        }
        
        if ($user->has_wallet) {
            throw new Exception('User already has a wallet', 400);
        }
        
        $identifier = $user->sso_global_id;
        
        // Create wallet with initial balance
        if ($initial_amount > 0) {
            return $this->updateBalance($identifier, $wallet_type, abs($initial_amount), TransactionType::ADMIN);
        } else {
            // Create empty wallet by adding and removing 0
            $this->updateBalance($identifier, $wallet_type, 0, TransactionType::ADMIN);
            return true;
        }
    }

}