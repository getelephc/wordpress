<?php

/** Isolated, in-memory SQL fixture. Never opens a local or remote server. */
class ElephcFixtureDatabase extends wpdb {
    private PDO $fixture;

    public function __construct() {
        $this->fixture = new PDO( 'sqlite::memory:' );
        $this->fixture->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
        $this->ready = true;
        $this->is_mysql = false;
        $this->charset = 'utf8mb4';
        $this->collate = '';
        $this->fixture->exec( 'CREATE TABLE wp_options (option_id INTEGER PRIMARY KEY, option_name TEXT UNIQUE, option_value TEXT, autoload TEXT)' );
        $options = array(
            'siteurl' => 'http://127.0.0.1:9000',
            'home' => 'http://127.0.0.1:9000',
            'blogname' => 'WordPress on Elephc',
            'blogdescription' => 'Original WordPress core with a finite local fixture',
            'blog_charset' => 'UTF-8',
            'db_version' => '61833',
            'template' => 'twentytwentyfive',
            'stylesheet' => 'twentytwentyfive',
            'active_plugins' => 'a:0:{}',
            'WPLANG' => '',
            'permalink_structure' => '',
            'timezone_string' => 'UTC',
            'gmt_offset' => '0',
            'default_role' => 'subscriber',
            'users_can_register' => '0',
            'posts_per_page' => '10',
        );
        $statement = $this->fixture->prepare( 'INSERT INTO wp_options (option_name, option_value, autoload) VALUES (?, ?, ?)' );
        foreach ( $options as $name => $value ) {
            $statement->execute( array( $name, $value, 'on' ) );
        }
    }

    public function query( $query ) {
        $this->last_query = $query;
        $this->last_result = array();
        $this->last_error = '';
        ++$this->num_queries;
        $operation = strtoupper( strtok( ltrim( $query ), " \n\t" ) );
        if ( $operation === 'SELECT' ) {
            $statement = $this->fixture->query( $query );
            $this->last_result = $statement->fetchAll( PDO::FETCH_OBJ );
            $this->num_rows = count( $this->last_result );
            return $this->num_rows;
        }
        if ( $operation !== 'INSERT' && $operation !== 'UPDATE' && $operation !== 'DELETE' ) {
            throw new RuntimeException( 'SQL operation outside the SQLite fixture: ' . $query );
        }
        $query = str_replace( 'INSERT IGNORE INTO', 'INSERT OR IGNORE INTO', $query );
        $query = str_replace( ' ON DUPLICATE KEY UPDATE ', ' ON CONFLICT(option_name) DO UPDATE SET ', $query );
        $query = preg_replace( '/VALUES\(`([a-z_]+)`\)/', 'excluded.`$1`', $query );
        $this->rows_affected = $this->fixture->exec( $query );
        $this->insert_id = (int) $this->fixture->lastInsertId();
        return $this->rows_affected;
    }

    public function _real_escape( $data ) {
        return str_replace( "'", "''", (string) $data );
    }

    public function db_version() {
        return '8.0.0';
    }

    public function db_server_info() {
        return '8.0.0-elephc-sqlite-fixture';
    }
}

$wpdb = new ElephcFixtureDatabase();
