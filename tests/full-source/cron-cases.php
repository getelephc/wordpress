<?php

$rows = array(array(_get_cron_array(), wp_next_scheduled('absent'), wp_get_scheduled_event('absent'), wp_get_schedule('absent')));
foreach (array(2000000000, 2000000017, 2147483647) as $timestamp) {
    $args = array($timestamp, 'é', null, false);
    $rows[] = array(cron_result(wp_schedule_event($timestamp, 'daily', 'fixture_recurring', $args)), wp_get_scheduled_event('fixture_recurring', $args, $timestamp), wp_next_scheduled('fixture_recurring', $args), wp_get_schedule('fixture_recurring', $args));
}
foreach (array(2000000100, 2147483600) as $timestamp) {
    $args = array($timestamp);
    $rows[] = array(cron_result(wp_schedule_single_event($timestamp, 'fixture_single', $args)), cron_result(wp_schedule_single_event($timestamp, 'fixture_single', $args)), cron_result(wp_schedule_single_event($timestamp, 'fixture_single', $args, true)), wp_get_scheduled_event('fixture_single', $args), wp_get_schedule('fixture_single', $args));
}
foreach (array(0, -1, 'invalid') as $timestamp) {
    $rows[] = array(cron_result(wp_schedule_event($timestamp, 'daily', 'invalid')), cron_result(wp_schedule_event($timestamp, 'daily', 'invalid', array(), true)), cron_result(wp_schedule_single_event($timestamp, 'invalid', array(), true)));
}
$rows[] = array(cron_result(wp_schedule_event(2000000200, 'missing-schedule', 'invalid')), cron_result(wp_schedule_event(2000000200, 'missing-schedule', 'invalid', array(), true)));
$crons = _get_cron_array();
$rows[] = array($crons, array_keys($crons), count($crons), $cron_control_options['cron'], wp_get_ready_cron_jobs());
$snapshot = $crons;
$rows[] = array(cron_result(wp_schedule_event(2000000300, 'hourly', 'later')), $snapshot, _get_cron_array());
echo json_encode($rows), "\n";
