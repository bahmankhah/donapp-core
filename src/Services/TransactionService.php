<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Wallet;

class TransactionService{
    
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
    public function getAllTransactions($filters = [], $limit = 50)
    {
        global $wpdb;
        
        if (!$wpdb) {
            return [];
        }
        
        $user_filter = $filters['user_filter'] ?? '';
        $type_filter = $filters['type_filter'] ?? '';
        $start_date = $filters['start_date'] ?? '';
        $end_date = $filters['end_date'] ?? '';
        
        $tx_table = $wpdb->prefix . 'dnp_user_wallet_transactions';
        $wallet_table = $wpdb->prefix . 'dnp_user_wallets';
        
        $where_conditions = ['1=1'];
        $where_values = [];
        
        if (!empty($user_filter)) {
            $where_conditions[] = 'w.identifier LIKE %s';
            $where_values[] = '%' . $user_filter . '%';
        }
        
        if (!empty($type_filter)) {
            $where_conditions[] = 't.type = %s';
            $where_values[] = $type_filter;
        }
        
        if (!empty($start_date)) {
            $where_conditions[] = 'DATE(t.created_at) >= %s';
            $where_values[] = $start_date;
        }
        
        if (!empty($end_date)) {
            $where_conditions[] = 'DATE(t.created_at) <= %s';
            $where_values[] = $end_date;
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $query = "
            SELECT 
                t.id,
                t.wallet_id,
                w.identifier,
                w.type as wallet_type,
                t.type,
                t.description,
                t.credit,
                t.debit,
                t.remain as balance_after,
                t.params,
                t.created_at,
                CASE 
                    WHEN t.credit IS NOT NULL THEN t.credit
                    WHEN t.debit IS NOT NULL THEN -t.debit
                    ELSE 0
                END as amount
            FROM $tx_table t
            LEFT JOIN $wallet_table w ON t.wallet_id = w.id
            WHERE $where_clause
            ORDER BY t.created_at DESC 
            LIMIT %d
        ";
        
        $where_values[] = $limit;
        
        if (!empty($where_values)) {
            $results = $wpdb->get_results($wpdb->prepare($query, $where_values));
        } else {
            $results = $wpdb->get_results($query);
        }

        return $results ?: [];
    }
    
    /**
     * Get transaction statistics
     */
    public function getTransactionStats()
    {
        global $wpdb;
        
        if (!$wpdb) {
            return [
                'total_transactions' => 0,
                'today_transactions' => 0,
                'total_volume' => 0,
                'today_volume' => 0
            ];
        }
        
        $table = $wpdb->prefix . 'dnp_user_wallet_transactions';
        $today = date('Y-m-d');
        
        return [
            'total_transactions' => $wpdb->get_var("SELECT COUNT(*) FROM $table") ?: 0,
            'today_transactions' => $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE DATE(created_at) = %s", $today)) ?: 0,
            'total_volume' => $wpdb->get_var("SELECT SUM(COALESCE(credit, 0) + COALESCE(debit, 0)) FROM $table") ?: 0,
            'today_volume' => $wpdb->get_var($wpdb->prepare("SELECT SUM(COALESCE(credit, 0) + COALESCE(debit, 0)) FROM $table WHERE DATE(created_at) = %s", $today)) ?: 0
        ];
    }
    
    /**
     * Get recent activity data
     */
    public function getRecentActivity($limit = 10)
    {
        global $wpdb;
        
        if (!$wpdb) {
            return [];
        }
        
        $tx_table = $wpdb->prefix . 'dnp_user_wallet_transactions';
        $wallet_table = $wpdb->prefix . 'dnp_user_wallets';
        
        $query = "
            SELECT 
                t.*,
                w.identifier,
                w.type as wallet_type,
                CASE 
                    WHEN t.credit IS NOT NULL THEN t.credit
                    WHEN t.debit IS NOT NULL THEN -t.debit
                    ELSE 0
                END as amount
            FROM $tx_table t
            LEFT JOIN $wallet_table w ON t.wallet_id = w.id
            ORDER BY t.created_at DESC 
            LIMIT %d
        ";
        
        $results = $wpdb->get_results($wpdb->prepare($query, $limit));
        return $results ?: [];
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
        
        $table = $wpdb->prefix . 'dnp_user_wallet_transactions';
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
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