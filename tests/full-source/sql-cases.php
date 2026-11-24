<?php

$database = new PDO('sqlite::memory:');
$database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$database->exec('CREATE TABLE wp_options (option_id INTEGER PRIMARY KEY, option_name TEXT UNIQUE, option_value TEXT, autoload TEXT)');
$insert = $database->prepare('INSERT INTO wp_options (option_name, option_value, autoload) VALUES (?, ?, ?)');
$insert->execute(array('blogname', 'WordPress is live', 'on'));
$insert->execute(array('active_plugins', 'a:0:{}', 'on'));
$rows = $database->query("SELECT option_name, option_value FROM wp_options WHERE autoload IN ('yes', 'on', 'auto-on', 'auto') ORDER BY option_id")->fetchAll(PDO::FETCH_OBJ);
$result = array();
foreach ($rows as $row) $result[] = array(get_class($row), $row->option_name, $row->option_value);
$database->exec("INSERT INTO wp_options (option_name, option_value, autoload) VALUES ('blogname', 'Updated', 'on') ON CONFLICT(option_name) DO UPDATE SET option_value = excluded.option_value");
$result[] = $database->query("SELECT option_value FROM wp_options WHERE option_name = 'blogname'")->fetchColumn();
$result[] = $database->query('SELECT count(*) FROM wp_options')->fetchColumn();
// A finite stand-in checks inherited dispatch and fresh global receiver reads.
class wpdb {
    public array $trace = array();
    public function query(mixed $sql): mixed {$this->trace[]=array('base-query',$sql); return 'base:'.(string)$sql;}
    public function prepare(mixed $format,mixed $value): mixed {$this->trace[]=array('prepare',$format,$value); return 'prepared:'.(string)$value;}
    public function esc_like(mixed $value): mixed {$this->trace[]=array('escape',$value); return 'escaped:'.(string)$value;}
}
class SqlProbeDerived extends wpdb {
    public function query(mixed $sql): mixed {$this->trace[]=array('derived-query',$sql); return 'derived:'.(string)$sql;}
    public function esc_like(mixed $value): mixed {$this->trace[]=array('derived-escape',$value); return 'derived-escaped:'.(string)$value;}
}
foreach (array(false,true) as $original_derived) foreach (array(false,true) as $replacement_derived) {
    foreach (array(false,true) as $replace) foreach (array('seed','é汉','',null,false,42) as $seed) {
        $base = new wpdb();
        $derived = new SqlProbeDerived();
        $active = $original_derived ? sql_probe_seed($derived) : sql_probe_seed($base);
        $replacement = $replacement_derived ? sql_probe_seed($derived) : sql_probe_seed($base);
        $wpdb = sql_probe_seed($active);
        $returned = sql_probe_nested((bool)$replace,$replacement,$seed);
        $result[] = array($returned,$base->trace,$derived->trace,$wpdb === $active,$wpdb === $replacement);
    }
}
echo json_encode($result), "\n";
