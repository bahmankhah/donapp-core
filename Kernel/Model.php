<?php

namespace Kernel;

use ReflectionMethod;

class Model {
    protected $table;
    protected $primaryKey = 'id';
    protected $queryBuilder = [];
    protected $wpdb;
    protected $postType = null; // Default to null, set in derived classes if needed

    public function __call($name, $arguments)
    {
        if (method_exists($this, $name)) {
            // Use Reflection to invoke the actual method in the child class
            echo 'intercepted';
            call_user_func_array([$this, $name], $arguments);
            echo 'after';
            die();
            // $reflector = new ReflectionMethod($this, $name);
            // return $reflector->invokeArgs($this, $arguments);
        } else {
            // Handle as a non-existent method call
            echo "Method '$name' does not exist in the child class. Handling as needed.\n";
        }
    }

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->newQuery();
    }

    // Start a new query
    public function newQuery() {
        $this->queryBuilder = [
            'select' => '*',
            'joins' => [],
            'where' => [],
            'orderBy' => '',
            'limit' => '',
        ];

        // If a post type is defined, add it to the query
        if ($this->postType) {
            $this->where('post_type', '=', $this->postType);
        }

        return $this;
    }

    // Set the table name dynamically if needed
    public function setTable($table) {
        $this->table = $table;
        return $this;
    }

    // Select specific columns
    public function select($columns) {
        $this->queryBuilder['select'] = is_array($columns) ? implode(',', $columns) : $columns;
        return $this;
    }

    // Add a join clause
    public function join($table, $first, $operator, $second, $type = 'INNER') {
        $this->queryBuilder['joins'][] = "{$type} JOIN {$table} ON {$first} {$operator} {$second}";
        return $this;
    }

    // Add a where clause
    public function where($column, $operator, $value, $type = '%s') {
        $this->queryBuilder['where'][] = $this->wpdb->prepare("{$column} {$operator} {$type}", $value);
        return $this;
    }

    // Add a where clause for meta values (for tables with meta data like posts and users)
    public function whereMeta($metaKey, $operator, $value, $type = '%s') {
        $this->queryBuilder['where'][] = $this->wpdb->prepare("ID IN (SELECT post_id FROM {$this->wpdb->prefix}postmeta WHERE meta_key = %s AND meta_value {$operator} {$type})", $metaKey, $value);
        return $this;
    }

    // Add an order by clause
    public function orderBy($column, $direction = 'ASC') {
        $this->queryBuilder['orderBy'] = "ORDER BY {$column} {$direction}";
        return $this;
    }

    // Add a limit clause
    public function limit($limit) {
        $this->queryBuilder['limit'] = "LIMIT {$limit}";
        return $this;
    }

    // Execute the built query and get all results
    public function get() {
        $joins = !empty($this->queryBuilder['joins']) ? implode(' ', $this->queryBuilder['joins']) : '';
        $where = !empty($this->queryBuilder['where']) ? 'WHERE ' . implode(' AND ', $this->queryBuilder['where']) : '';
        
        $sql = "SELECT {$this->queryBuilder['select']} FROM {$this->table} {$joins} {$where} {$this->queryBuilder['orderBy']} {$this->queryBuilder['limit']}";

        $this->newQuery(); // Reset the builder for a fresh start
        return $this->wpdb->get_results($sql);
    }

    // Execute the built query and get the first result
    public function first() {
        $this->limit(1);
        $result = $this->get();
        return !empty($result) ? $result[0] : null;
    }

    // Find a record by primary key
    public function find($id) {
        return $this->newQuery()->where($this->primaryKey, '=', $id)->first();
    }

    // Insert a new record
    public function create(array $data) {
        $inserted = $this->wpdb->insert($this->table, $data);
        if ($inserted) {
            return $this->find($this->wpdb->insert_id);
        }
        return false;
    }

    // Update a record by primary key
    public function update($id, array $data) {
        $updated = $this->wpdb->update($this->table, $data, [$this->primaryKey => $id]);
        if ($updated) {
            return $this->find($id);
        }
        return false;
    }

    // Delete a record by primary key
    public function delete($id) {
        return $this->wpdb->delete($this->table, [$this->primaryKey => $id]);
    }
}
