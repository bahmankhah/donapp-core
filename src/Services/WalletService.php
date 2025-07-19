<?php

namespace App\Services;

use App\Facades\Wallet as FacadesWallet;
use App\Core\TransactionType;
use App\Core\WalletType;
use App\Models\Wallet;
use Exception;
use Kernel\Container;

class WalletService{
    
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
        $wallets = (new Wallet())->where('identifier', '=', $identifier)->get();
        return $wallets;
    }

    public function getAvailableCredit($identifier, $useCash = true)
    {
        if(!$useCash) {
            return FacadesWallet::credit()->getBalance($identifier);
        }
        return FacadesWallet::virtualCreditCash()->getBalance($identifier);

    }

    public function increaseCredit($identifier, $amount){
        return $this->updateBalance($identifier, WalletType::CREDIT, abs($amount), TransactionType::CREDIT_CHARGE);
    }

    public function addGift($identifier, $amount){
        return $this->updateBalance($identifier, WalletType::CREDIT, abs($amount), TransactionType::CHARGE_GIFT);
    }

    public function decreaseCredit($identifier, $amount, $useCash = true){
        if(!$useCash) {
            return FacadesWallet::credit()->updateBalance($identifier, -$amount);
        }
        return FacadesWallet::virtualCreditCash()->decreaseBalance($identifier, $amount);
    }

    public function updateBalance($identifier, $walletType, $amount, $transactionType = null){
        if(!in_array($walletType, ['coin', 'credit', 'cash', 'suspended'])){
            throw new \Exception('allowed wallets: coin, credit, cash', 400);
        }
        $updated = FacadesWallet::$walletType()->updateBalance($identifier, $amount, $transactionType);
        return $updated;
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
        
        $table = $wpdb->prefix . 'dnp_user_wallets';
        
        $where_conditions = ['1=1'];
        $where_values = [];
        
        // Apply filters if provided
        if (!empty($filters['identifier'])) {
            $where_conditions[] = 'identifier LIKE %s';
            $where_values[] = '%' . $filters['identifier'] . '%';
        }
        
        if (!empty($filters['type'])) {
            $where_conditions[] = 'type = %s';
            $where_values[] = $filters['type'];
        }
        
        if (isset($filters['min_balance']) && is_numeric($filters['min_balance'])) {
            $where_conditions[] = 'balance >= %d';
            $where_values[] = intval($filters['min_balance']);
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        // Get total count for pagination
        $count_query = "SELECT COUNT(*) FROM $table WHERE $where_clause";
        
        if (!empty($where_values)) {
            $total_items = $wpdb->get_var($wpdb->prepare($count_query, $where_values));
        } else {
            $total_items = $wpdb->get_var($count_query);
        }
        
        $total_pages = ceil($total_items / $per_page);
        $page = max(1, min($page, $total_pages));
        $offset = ($page - 1) * $per_page;
        
        // Get paginated results
        $query = "
            SELECT * FROM $table 
            WHERE $where_clause
            ORDER BY created_at DESC 
            LIMIT %d OFFSET %d
        ";
        
        $query_values = array_merge($where_values, [$per_page, $offset]);
        
        if (!empty($where_values)) {
            $results = $wpdb->get_results($wpdb->prepare($query, $query_values));
        } else {
            $results = $wpdb->get_results($wpdb->prepare($query, [$per_page, $offset]));
        }

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
                'active_wallets' => 0,
                'total_balance' => 0,
                'avg_balance' => 0
            ];
        }
        
        $table = $wpdb->prefix . 'dnp_user_wallets';
        
        return [
            'total_wallets' => $wpdb->get_var("SELECT COUNT(*) FROM $table") ?: 0,
            'active_wallets' => $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE balance > 0") ?: 0,
            'total_balance' => $wpdb->get_var("SELECT SUM(balance) FROM $table WHERE type = 'credit'") ?: 0,
            'avg_balance' => $wpdb->get_var("SELECT AVG(balance) FROM $table WHERE type = 'credit' AND balance > 0") ?: 0
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
        
        $table = $wpdb->prefix . 'dnp_user_wallets';
        $count = $wpdb->get_var("SELECT COUNT(DISTINCT identifier) FROM $table");
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
        
        $table = $wpdb->prefix . 'dnp_user_wallets';
        $total = $wpdb->get_var("SELECT SUM(balance) FROM $table WHERE type = 'credit'");
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
        
        $table = $wpdb->prefix . 'dnp_user_wallets';
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE balance > 0");
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

}