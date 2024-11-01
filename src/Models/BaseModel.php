<?php
namespace Donapp\Models;


class BaseModel {
    protected $table;
    protected $primaryKey = 'id';
    protected $queryBuilder = [];

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->queryBuilder = [
            'select' => '*',
            'where' => [],
            'orderBy' => '',
            'limit' => '',
        ];
    }

    // Start a new query
    public function newQuery() {
        $this->queryBuilder = [
            'select' => '*',
            'where' => [],
            'orderBy' => '',
            'limit' => '',
        ];
        return $this;
    }

    // Select specific columns
    public function select($columns) {
        $this->queryBuilder['select'] = is_array($columns) ? implode(',', $columns) : $columns;
        return $this;
    }

    // Add a where clause
    public function where($column, $operator, $value) {
        $this->queryBuilder['where'][] = $this->wpdb->prepare("{$column} {$operator} %s", $value);
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
        $where = !empty($this->queryBuilder['where']) ? 'WHERE ' . implode(' AND ', $this->queryBuilder['where']) : '';
        $sql = "SELECT {$this->queryBuilder['select']} FROM {$this->table} {$where} {$this->queryBuilder['orderBy']} {$this->queryBuilder['limit']}";
        
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
