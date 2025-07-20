<?php
namespace App\Models;

use Kernel\Model;

class Transaction extends Model {
    protected $table;
    protected $primaryKey = 'id';

    public function __construct() {
        parent::__construct();
        $this->table = $this->wpdb->prefix . 'dnp_user_wallet_transactions';
    }

    /**
     * Get the wallet that owns the transaction
     */
    public function wallet() {
        return $this->belongsTo($this->wpdb->prefix . 'dnp_user_wallets', 'wallet_id', 'id');
    }

    /**
     * Get transactions for a specific wallet
     */
    public function getWalletTransactions($wallet_id, $limit = null) {
        $query = $this->newQuery()
            ->where('wallet_id', '=', $wallet_id)
            ->orderBy('created_at', 'DESC');
            
        if ($limit) {
            $query->limit($limit);
        }
        
        return $query->get();
    }

    /**
     * Get recent transactions across all wallets
     */
    public function getRecentTransactions($limit = 10) {
        return $this->newQuery()
            ->select('t.*, w.identifier')
            ->setTableAlias('t')
            ->join($this->wpdb->prefix . 'dnp_user_wallets w', 't.wallet_id', '=', 'w.id')
            ->orderBy('t.created_at', 'DESC')
            ->limit($limit)
            ->get();
    }

    /**
     * Get transactions with filters and pagination
     */
    public function getTransactionsWithFilters($filters = [], $page = 1, $per_page = 20) {
        $offset = ($page - 1) * $per_page;
        
        $query = $this->newQuery()
            ->select('t.*, w.identifier, w.type as wallet_type')
            ->setTableAlias('t')
            ->join($this->wpdb->prefix . 'dnp_user_wallets w', 't.wallet_id', '=', 'w.id')
            ->orderBy('t.created_at', 'DESC');

        // Apply filters
        if (!empty($filters['user_filter'])) {
            // Use exact match for SSO IDs, fallback to LIKE for partial searches
            if (strlen($filters['user_filter']) > 10) { // Assuming SSO IDs are longer than 10 chars
                $query->where('w.identifier', '=', $filters['user_filter']);
            } else {
                $query->where('w.identifier', 'LIKE', '%' . $filters['user_filter'] . '%');
            }
        }

        if (!empty($filters['type_filter'])) {
            $query->where('t.type', '=', $filters['type_filter']);
        }

        if (!empty($filters['start_date'])) {
            $query->where('t.created_at', '>=', $filters['start_date'] . ' 00:00:00');
        }

        if (!empty($filters['end_date'])) {
            $query->where('t.created_at', '<=', $filters['end_date'] . ' 23:59:59');
        }

        // Get total count for pagination
        $count_query = clone $query;
        $count_query->select('COUNT(*)');
        $total_items = $this->wpdb->get_var($count_query->sql()) ?: 0;

        // Get paginated results
        $query->limit($per_page);
        $sql = $query->sql() . " OFFSET $offset";
        $results = $this->wpdb->get_results($sql);

        $total_pages = ceil($total_items / $per_page);

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
     * Get transaction statistics
     */
    public function getTransactionStats() {
        $total_transactions = $this->wpdb->get_var("
            SELECT COUNT(*) FROM {$this->table}
        ") ?: 0;

        $total_credit = $this->wpdb->get_var("
            SELECT SUM(credit) FROM {$this->table} WHERE credit IS NOT NULL
        ") ?: 0;

        $total_debit = $this->wpdb->get_var("
            SELECT SUM(debit) FROM {$this->table} WHERE debit IS NOT NULL
        ") ?: 0;

        $today_transactions = $this->wpdb->get_var("
            SELECT COUNT(*) FROM {$this->table} 
            WHERE DATE(created_at) = CURDATE()
        ") ?: 0;

        return [
            'total_transactions' => $total_transactions,
            'total_credit' => $total_credit,
            'total_debit' => $total_debit,
            'today_transactions' => $today_transactions,
            'net_amount' => $total_credit - $total_debit
        ];
    }
}
