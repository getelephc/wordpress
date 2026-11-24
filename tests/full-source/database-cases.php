<?php

$wpdb->set_prefix('wp_');
$rows = array();
$scalar = $wpdb->prepare('SELECT %s', 'wp_attachment_pages_enabled');
$rows[] = array($wpdb->remove_placeholder_escape($scalar));
foreach (array(
    array('SELECT %s', array("O'Reilly")),
    array('SELECT %s', array('percent%value')),
    array('SELECT %d', array(7)),
    array('SELECT %f', array(1.25)),
    array('SELECT %i', array('col`name')),
    array('SELECT %s, %d, %f', array('é汉', 17, 1.5)),
) as $case) {
    $prepared = $wpdb->prepare($case[0], $case[1]);
    $rows[] = array($wpdb->remove_placeholder_escape($prepared));
}
foreach (array('WordPress', "O'Reilly", '100% ready', 'é汉', '', null, false, 0, 7, 1.25) as $value) {
    $query = $wpdb->prepare('SELECT %s AS value', $value);
    $wpdb->query($query);
    $rows[] = array($wpdb->last_query, $wpdb->last_result, $wpdb->get_var(null, 0, 0), $wpdb->get_var(null, 1, 0), $wpdb->get_var(null, 0, 1));
}
$query = "SELECT 'alpha' AS label, '0' AS value UNION ALL SELECT 'beta', NULL UNION ALL SELECT 'alpha', 'two'";
$wpdb->query($query);
foreach (array(OBJECT, ARRAY_A, ARRAY_N, 'object') as $mode) {
    $rows[] = array($mode, $wpdb->get_row(null, $mode, 0), $wpdb->get_row(null, $mode, 1), $wpdb->get_row(null, $mode, 3));
}
foreach (array(OBJECT, OBJECT_K, ARRAY_A, ARRAY_N, 'object') as $mode) {
    $rows[] = array($mode, $wpdb->get_results($query, $mode));
}
$rows[] = array($wpdb->get_col($query, 0), $wpdb->get_col(null, 1), $wpdb->num_rows);
$rows[] = array($wpdb->get_var("SELECT option_value FROM wp_options WHERE option_name = 'blogname'"));
$rows[] = array($wpdb->get_row("SELECT option_name, option_value FROM wp_options WHERE option_name = 'home'", ARRAY_A));
$rows[] = array(is_string($wpdb->db_version()), $wpdb->db_version(), is_string($wpdb->db_server_info()), $wpdb->db_server_info(), has_filter('query', array($wpdb, 'remove_placeholder_escape')));
echo json_encode($rows), "\n";
