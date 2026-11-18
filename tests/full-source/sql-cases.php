<?php

$database = new PDO('sqlite::memory:');
$database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$database->exec('CREATE TABLE wp_options (option_id INTEGER PRIMARY KEY, option_name TEXT UNIQUE, option_value TEXT, autoload TEXT)');
$insert = $database->prepare('INSERT INTO wp_options (option_name, option_value, autoload) VALUES (?, ?, ?)');
$insert->execute(array('blogname', 'WordPress è vivo', 'on'));
$insert->execute(array('active_plugins', 'a:0:{}', 'on'));
$rows = $database->query("SELECT option_name, option_value FROM wp_options WHERE autoload IN ('yes', 'on', 'auto-on', 'auto') ORDER BY option_id")->fetchAll(PDO::FETCH_OBJ);
$result = array();
foreach ($rows as $row) $result[] = array(get_class($row), $row->option_name, $row->option_value);
$database->exec("INSERT INTO wp_options (option_name, option_value, autoload) VALUES ('blogname', 'Updated', 'on') ON CONFLICT(option_name) DO UPDATE SET option_value = excluded.option_value");
$result[] = $database->query("SELECT option_value FROM wp_options WHERE option_name = 'blogname'")->fetchColumn();
$result[] = $database->query('SELECT count(*) FROM wp_options')->fetchColumn();
echo json_encode($result), "\n";
