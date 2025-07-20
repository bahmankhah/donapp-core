<?php
namespace App\Models;

use Kernel\Model;

class UserMeta extends Model {
    protected $table;
    protected $primaryKey = 'umeta_id';

    public function __construct() {
        parent::__construct();
        $this->table = $this->wpdb->prefix . 'usermeta';
    }

    /**
     * Get user meta value by user ID and meta key
     *
     * @param int $user_id
     * @param string $meta_key
     * @return mixed|null
     */
    public function getUserMeta($user_id, $meta_key) {
        $result = $this->newQuery()
            ->select('meta_value')
            ->where('user_id', '=', $user_id)
            ->where('meta_key', '=', $meta_key)
            ->first();
        
        return $result ? $result['meta_value'] : null;
    }

    /**
     * Get all meta for a specific user
     *
     * @param int $user_id
     * @return array
     */
    public function getAllUserMeta($user_id) {
        return $this->newQuery()
            ->where('user_id', '=', $user_id)
            ->get();
    }

    /**
     * Find users by meta key and value
     *
     * @param string $meta_key
     * @param mixed $meta_value
     * @return array
     */
    public function findUsersByMeta($meta_key, $meta_value = null) {
        $query = $this->newQuery()
            ->select('DISTINCT user_id')
            ->where('meta_key', '=', $meta_key);
            
        if ($meta_value !== null) {
            $query->where('meta_value', '=', $meta_value);
        }
        
        return $query->get();
    }
}
