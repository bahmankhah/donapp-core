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

        // Get total count for pagination
        $count_query = clone $query;
        $count_query->select('COUNT(*)');
        $total_items = $wpdb->get_var($count_query->sql()) ?: 0;

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
        
        $total_wallets_query = $this->walletModel->newQuery()->select('COUNT(*)');
        $total_balance_query = $this->walletModel->newQuery()->select('SUM(balance)')->where('type', '=', 'credit');
        $avg_balance_query = $this->walletModel->newQuery()->select('AVG(balance)')->where('type', '=', 'credit')->where('balance', '>', 0, '%d');
        
        return [
            'total_wallets' => intval($wpdb->get_var($total_wallets_query->sql()) ?: 0),
            'total_balance' => floatval($wpdb->get_var($total_balance_query->sql()) ?: 0),
            'avg_balance' => floatval($wpdb->get_var($avg_balance_query->sql()) ?: 0)
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
        
        $query = $this->walletModel->newQuery()->select('COUNT(DISTINCT identifier)');
        $count = $wpdb->get_var($query->sql());
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
        
        $query = $this->walletModel->newQuery()->select('SUM(balance)')->where('type', '=', 'credit');
        $total = $wpdb->get_var($query->sql());
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
        
        $query = $this->walletModel->newQuery()->select('COUNT(*)')->where('balance', '>', 0, '%d');
        $count = $wpdb->get_var($query->sql());
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