<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserMeta;
use Exception;

class UserService
{
    protected $userModel;
    protected $userMetaModel;

    public function __construct()
    {
        $this->userModel = new User();
        $this->userMetaModel = new UserMeta();
    }

    /**
     * Get all SSO users with pagination
     */
    public function getAllSSOUsers($page = 1, $per_page = 20, $search = '')
    {
        $wpdb = $this->userModel->getWpdb();
        
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
        
        // Build the query using models
        $userQuery = $this->userModel->newQuery()
            ->select('DISTINCT u.ID, u.user_login, u.display_name, u.user_email, u.user_registered, um.meta_value as sso_global_id')
            ->setTableAlias('u')
            ->join($wpdb->prefix . 'usermeta um', 'u.ID', '=', 'um.user_id')
            ->where('um.meta_key', '=', 'sso_global_id')
            ->orderBy('u.user_registered', 'DESC')
            ->limit($per_page);

        // Apply search filter if provided
        if (!empty($search)) {
            $search_term = '%' . $search . '%';
            // We need to build a custom WHERE clause for OR conditions
            $where_sql = $wpdb->prepare(
                "(u.user_login LIKE %s OR u.display_name LIKE %s OR u.user_email LIKE %s OR um.meta_value LIKE %s)",
                $search_term, $search_term, $search_term, $search_term
            );
            $userQuery->where('1', '=', '1', 'none'); // Dummy condition
            // Manually modify the last where condition to be an OR group
            $userQuery->queryBuilder['where'][count($userQuery->queryBuilder['where']) - 1] = $where_sql;
        }

        // Get total count for pagination
        $countQuery = $this->userModel->newQuery()
            ->select('COUNT(DISTINCT u.ID)')
            ->setTableAlias('u')
            ->join($wpdb->prefix . 'usermeta um', 'u.ID', '=', 'um.user_id')
            ->where('um.meta_key', '=', 'sso_global_id');

        if (!empty($search)) {
            $search_term = '%' . $search . '%';
            $where_sql = $wpdb->prepare(
                "(u.user_login LIKE %s OR u.display_name LIKE %s OR u.user_email LIKE %s OR um.meta_value LIKE %s)",
                $search_term, $search_term, $search_term, $search_term
            );
            $countQuery->where('1', '=', '1', 'none');
            $countQuery->queryBuilder['where'][count($countQuery->queryBuilder['where']) - 1] = $where_sql;
        }

        $total_items = $wpdb->get_var($countQuery->sql()) ?: 0;

        // Add OFFSET to the main query
        $sql = $userQuery->sql() . " OFFSET $offset";
        $results = $wpdb->get_results($sql);

        $total_pages = ceil($total_items / $per_page);
        $page = max(1, min($page, $total_pages));

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
     * Get total SSO users count
     */
    public function getTotalSSOUsersCount()
    {
        $wpdb = $this->userModel->getWpdb();
        
        if (!$wpdb) {
            return 0;
        }
        
        $countQuery = $this->userModel->newQuery()
            ->select('COUNT(DISTINCT u.ID)')
            ->setTableAlias('u')
            ->join($wpdb->prefix . 'usermeta um', 'u.ID', '=', 'um.user_id')
            ->where('um.meta_key', '=', 'sso_global_id');

        $count = $wpdb->get_var($countQuery->sql());
        return $count ?: 0;
    }

    /**
     * Get user details by ID for wallet creation
     */
    public function getUserForWalletCreation($user_id)
    {
        $wpdb = $this->userModel->getWpdb();
        
        if (!$user_id || !$wpdb) {
            return null;
        }

        $userQuery = $this->userModel->newQuery()
            ->select('u.ID, u.user_login, u.display_name, u.user_email, um.meta_value as sso_global_id')
            ->setTableAlias('u')
            ->join($wpdb->prefix . 'usermeta um', 'u.ID', '=', 'um.user_id')
            ->where('u.ID', '=', $user_id)
            ->where('um.meta_key', '=', 'sso_global_id');

        $result = $wpdb->get_row($userQuery->sql());

        if ($result) {
            // Check if user already has wallets using WalletService
            $walletService = \Kernel\Container::resolve('WalletService');
            $existing_wallets = $walletService->findUserWallets($result->sso_global_id);
            $result->has_wallet = !empty($existing_wallets);
            $result->wallets = $existing_wallets;
        }

        return $result;
    }

    /**
     * Get user by SSO global ID
     */
    public function getUserBySSOId($sso_global_id)
    {
        $wpdb = $this->userModel->getWpdb();
        
        if (!$sso_global_id || !$wpdb) {
            return null;
        }

        $userQuery = $this->userModel->newQuery()
            ->select('u.ID, u.user_login, u.display_name, u.user_email, um.meta_value as sso_global_id')
            ->setTableAlias('u')
            ->join($wpdb->prefix . 'usermeta um', 'u.ID', '=', 'um.user_id')
            ->where('um.meta_value', '=', $sso_global_id)
            ->where('um.meta_key', '=', 'sso_global_id');

        return $wpdb->get_row($userQuery->sql());
    }

    /**
     * Check if user has SSO global ID
     */
    public function userHasSSOId($user_id)
    {
        $sso_id = $this->userMetaModel->getUserMeta($user_id, 'sso_global_id');
        return !empty($sso_id);
    }

    /**
     * Get user's SSO global ID
     */
    public function getUserSSOId($user_id)
    {
        return $this->userMetaModel->getUserMeta($user_id, 'sso_global_id');
    }

    /**
     * Search SSO users for dropdown/autocomplete
     */
    public function searchSSOUsers($search_term, $limit = 50)
    {
        $wpdb = $this->userModel->getWpdb();
        
        if (empty($search_term) || !$wpdb) {
            return [];
        }

        $search_term = '%' . $search_term . '%';
        
        $userQuery = $this->userModel->newQuery()
            ->select('DISTINCT u.ID, u.user_login, u.display_name, u.user_email, um.meta_value as sso_global_id')
            ->setTableAlias('u')
            ->join($wpdb->prefix . 'usermeta um', 'u.ID', '=', 'um.user_id')
            ->where('um.meta_key', '=', 'sso_global_id')
            ->orderBy('u.user_login', 'ASC')
            ->limit($limit);

        // Add search condition
        $where_sql = $wpdb->prepare(
            "(u.user_login LIKE %s OR u.display_name LIKE %s OR u.user_email LIKE %s OR um.meta_value LIKE %s)",
            $search_term, $search_term, $search_term, $search_term
        );
        $userQuery->where('1', '=', '1', 'none');
        $userQuery->queryBuilder['where'][count($userQuery->queryBuilder['where']) - 1] = $where_sql;

        return $wpdb->get_results($userQuery->sql());
    }

    /**
     * Get users with SSO IDs for dropdown (paginated)
     */
    public function getSSOUsersForDropdown($page = 1, $per_page = 100)
    {
        $wpdb = $this->userModel->getWpdb();
        
        if (!$wpdb) {
            return [];
        }
        
        $offset = ($page - 1) * $per_page;
        
        $userQuery = $this->userModel->newQuery()
            ->select('DISTINCT u.ID, u.user_login, u.display_name, u.user_email, um.meta_value as sso_global_id')
            ->setTableAlias('u')
            ->join($wpdb->prefix . 'usermeta um', 'u.ID', '=', 'um.user_id')
            ->where('um.meta_key', '=', 'sso_global_id')
            ->orderBy('u.user_login', 'ASC')
            ->limit($per_page);

        $sql = $userQuery->sql() . " OFFSET $offset";
        return $wpdb->get_results($sql);
    }

    /**
     * Validate if user exists and has SSO ID
     */
    public function validateSSOUser($user_id)
    {
        $wpdb = $this->userModel->getWpdb();
        
        if (!$wpdb) {
            return false;
        }
        
        $user = $this->userModel->newQuery()
            ->select('u.ID')
            ->setTableAlias('u')
            ->join($wpdb->prefix . 'usermeta um', 'u.ID', '=', 'um.user_id')
            ->where('u.ID', '=', $user_id)
            ->where('um.meta_key', '=', 'sso_global_id')
            ->first();

        return !empty($user);
    }
}
