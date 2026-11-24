<?php
$socket_results = array();
foreach (array(SOCKET_TEST_PORT, (string)SOCKET_TEST_PORT, (float)SOCKET_TEST_PORT) as $socket_port) foreach (array(null, '1.25') as $socket_timeout) {
    $socket_code = 199;
    $socket_message = 'before';
    $socket = socket_open_call('127.0.0.1', $socket_port, $socket_code, $socket_message, $socket_timeout);
    if (!is_resource($socket)) throw new RuntimeException('Loopback socket connection failed.');
    $socket_written = socket_write_call($socket, "probe\n");
    $socket_reply = socket_read_call($socket);
    fclose($socket);
    $socket_results[] = array($socket_code, $socket_message, $socket_written, $socket_reply);
}
echo json_encode($socket_results, JSON_THROW_ON_ERROR), "\n";
