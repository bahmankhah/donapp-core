<?php

namespace Kernel;

class Model
{
    protected $table;
    protected $primaryKey = 'id';
    protected $queryBuilder = [];
    protected $wpdb;
    protected $postType = null; // Default to null, set in derived classes if needed
    protected $attributes = []; // Stores the current record's data

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->newQuery();
    }

    // Start a new query
    public function newQuery()
    {
        $this->queryBuilder = [
            'select' => '*',
            'joins' => [],
            'where' => [],
            'orderBy' => '',
            'limit' => '',
            'relations' => [
                'hasMany' => [],
            ],
        ];

        if ($this->postType) {
            $this->where('post_type', '=', $this->postType);
        }

        return $this;
    }

    // Set the table name dynamically if needed
    public function setTable($table)
    {
        $this->table = $table;
        return $this;
    }

    // Select specific columns
    public function select($columns)
    {
        $this->queryBuilder['select'] = is_array($columns) ? implode(',', $columns) : $columns;
        return $this;
    }

    // Add a join clause
    public function join($table, $first, $operator, $second, $type = 'INNER')
    {
        $this->queryBuilder['joins'][] = "{$type} JOIN {$table} ON {$first} {$operator} {$second}";
        return $this;
    }

    // Add a where clause
    public function where($column, $operator, $value, $type = '%s')
    {
        $this->queryBuilder['where'][] = $this->wpdb->prepare("{$column} {$operator} {$type}", $value);
        return $this;
    }

    // Add an order by clause
    public function orderBy($column, $direction = 'ASC')
    {
        $this->queryBuilder['orderBy'] = "ORDER BY {$column} {$direction}";
        return $this;
    }

    // Add a limit clause
    public function limit($limit)
    {
        $this->queryBuilder['limit'] = "LIMIT {$limit}";
        return $this;
    }

    // Execute the built query and get all results
    public function get()
    {
        $joins = !empty($this->queryBuilder['joins']) ? implode(' ', $this->queryBuilder['joins']) : '';
        $where = !empty($this->queryBuilder['where']) ? 'WHERE ' . implode(' AND ', $this->queryBuilder['where']) : '';

        $sql = "SELECT {$this->queryBuilder['select']} FROM {$this->table} {$joins} {$where} {$this->queryBuilder['orderBy']} {$this->queryBuilder['limit']}";

        $results = $this->wpdb->get_results($sql, 'ARRAY_A');
        foreach ($results as &$result) {
            $this->attributes = $result;
            foreach ($this->queryBuilder['relations'] as $type => $relations) {
                foreach ($relations as $name => $args) {
                    print_r($args);
                    $result[$name] = call_user_func_array([$this, "{$type}Method"], $args);
                }
            }
        }
        $this->newQuery(); // Reset the builder for a fresh start
        return $results;
    }

    // Store the fetched data in attributes for access by relationships
    public function first()
    {
        $this->limit(1);
        $result = $this->get();
        $this->attributes = !empty($result) ? $result[0] : [];
        return $this->attributes;
    }

    private function getCallingFunctionName() {
        $backtrace = debug_backtrace();
        return $backtrace[2]['function'];
    }

    public function hasMany($relatedTable, $foreignKey, $localKey = null)
    {
        $name = $this->getCallingFunctionName();
        $this->queryBuilder['relations']['hasMany'] = array_merge(
            $this->queryBuilder['relations']['hasMany'] ?? [],
            [$name => [$relatedTable, $foreignKey, $localKey]]
        );
        return $this;
    }

    private function hasManyMethod($relatedTable, $foreignKey, $localKey = null)
    {
        $localKey = $localKey ?: $this->primaryKey;
        $query = new static();
        print($this->attributes[$localKey]. ' ');
        $query->setTable($relatedTable)->where($foreignKey, '=', $this->attributes[$localKey] ?? null, '%d');
        return $query->get();
    }
    
}
