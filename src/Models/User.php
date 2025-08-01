<?php
namespace App\Models;

use Kernel\Model;

class User extends Model {
    protected $table;
    protected $primaryKey = 'ID';

    public function __construct() {
        parent::__construct();
        $this->table = $this->wpdb->prefix . 'users';
    }

    /**
     * Retrieve the current logged-in user as a model instance.
     *
     * @return User|null
     */
    public static function currentUser() {
        $current_user_id = get_current_user_id();
        if ($current_user_id) {
            $user = new self();
            return $user->where('ID','=',$current_user_id)->first();
        }
        return null;
    }

    /**
     * Find a user by their email address.
     *
     * @param string $email
     * @return User|null
     */
    public function findByEmail($email) {
        return $this->newQuery()->where('user_email', '=', $email)->first();
    }

    /**
     * Find a user by their login username.
     *
     * @param string $username
     * @return User|null
     */
    public function findByUsername($username) {
        return $this->newQuery()->where('user_login', '=', $username)->first();
    }

    /**
     * Example custom query: Get all users created after a specific date.
     *
     * @param string $date
     * @return array
     */
    public function createdAfter($date) {
        return $this->newQuery()->where('user_registered', '>', $date)->get();
    }
}
