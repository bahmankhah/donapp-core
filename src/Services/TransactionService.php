<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Wallet;

class TransactionService{
    
    protected $transactionModel;

    public function __construct()
    {
        $this->transactionModel = new Transaction();
    }
    
    /**
     * Create a new transaction
     */
    public function create($wallet, $amount, $remain, $type = null, $description = null){
        $data = [];
        if(!is_numeric($amount) || intval($amount) == 0){
            return;
        }
        if(!is_numeric($remain)){
            return;
        }
        $amount = intval($amount);
        $remain = intval($remain);
        if($amount == 0){
            return;
        }elseif($amount > 0){
            $data['credit'] = $amount;
        }else{
            $data['debit'] = abs($amount);
        }
        $data['wallet_id'] = $wallet['id'];
        $data['remain'] = $remain;
        $data['type'] = $type;
        if ($description) {
            $data['description'] = $description;
        }
        return (new Transaction)->create($data);
    }
    
    /**
     * Get all transactions with filtering and pagination
     */
    public function getAllTransactions($filters = [], $page = 1, $per_page = 20)
    {
        return $this->transactionModel->getTransactionsWithFilters($filters, $page, $per_page);
    }
    
    /**
     * Get transaction statistics
     */
    public function getTransactionStats()
    {
        return $this->transactionModel->getTransactionStats();
    }
    
    /**
     * Get recent activity data
     */
    public function getRecentActivity($limit = 10)
    {
        return $this->transactionModel->getRecentTransactions($limit);
    }
    
    /**
     * Get total transactions count
     */
    public function getTotalTransactionsCount()
    {
        global $wpdb;
        
        if (!$wpdb) {
            return 0;
        }
        
        $table_name = $wpdb->prefix . 'dnp_user_wallet_transactions';
        $count = intval($wpdb->get_var("SELECT COUNT(*) FROM {$table_name}"));
        return $count ?: 0;
    }
    
    /**
     * Get daily volume for specific types
     */
    public function getDailyVolume($types = ['credit_charge', 'charge_gift'])
    {
        global $wpdb;
        
        if (!$wpdb) {
            return 0;
        }
        
        $table = $wpdb->prefix . 'dnp_user_wallet_transactions';
        $today = date('Y-m-d');
        $placeholders = implode(',', array_fill(0, count($types), '%s'));
        
        $query = "SELECT SUM(COALESCE(credit, 0) + COALESCE(debit, 0)) FROM $table WHERE DATE(created_at) = %s AND type IN ($placeholders)";
        $params = array_merge([$today], $types);
        
        $volume = $wpdb->get_var($wpdb->prepare($query, $params));
        return $volume ?: 0;
    }
    
    /**
     * Get pending transactions count
     */
    public function getPendingTransactionsCount()
    {
        global $wpdb;
        
        if (!$wpdb) {
            return 0;
        }
        
        $table = $wpdb->prefix . 'dnp_user_wallet_transactions';
        // Note: The current schema doesn't have a status field, so this returns 0
        // If status field is added to the schema later, update this query
        return 0;
    }
}