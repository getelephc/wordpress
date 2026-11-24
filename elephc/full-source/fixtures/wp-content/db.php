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
        // Empty content tables let original theme-style queries run against SQL.
        $this->fixture->exec( 'CREATE TABLE wp_terms (term_id INTEGER PRIMARY KEY, name TEXT, slug TEXT, term_group INTEGER)' );
        $this->fixture->exec( 'CREATE TABLE wp_term_taxonomy (term_taxonomy_id INTEGER PRIMARY KEY, term_id INTEGER, taxonomy TEXT, description TEXT, parent INTEGER, count INTEGER)' );
        $this->fixture->exec( 'CREATE TABLE wp_term_relationships (object_id INTEGER, term_taxonomy_id INTEGER, term_order INTEGER)' );
        $this->fixture->exec( 'CREATE TABLE wp_posts (ID INTEGER PRIMARY KEY, post_author INTEGER, post_date TEXT, post_date_gmt TEXT, post_content TEXT, post_title TEXT, post_excerpt TEXT, post_status TEXT, comment_status TEXT, ping_status TEXT, post_password TEXT, post_name TEXT, to_ping TEXT, pinged TEXT, post_modified TEXT, post_modified_gmt TEXT, post_content_filtered TEXT, post_parent INTEGER, guid TEXT, menu_order INTEGER, post_type TEXT, post_mime_type TEXT, comment_count INTEGER)' );
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

    public function query( mixed $query ): mixed {
        $query = apply_filters( 'query', $query );
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

    public function _real_escape( mixed $data ): mixed {
        return str_replace( "'", "''", (string) $data );
    }

    public function db_version(): mixed {
        return '8.0.0';
    }

    public function db_server_info(): mixed {
        return '8.0.0-elephc-sqlite-fixture';
    }
}

$wpdb = new ElephcFixtureDatabase();
