<?php
require_once __DIR__ . '/variadic-forward-library.php';
echo json_encode(array(
    vf_collect(1, 'two', null, false),
    vf_collect_one('prefix', 1, 'two', null, false),
    vf_forward(array(1, 'two', null, false)),
    vf_forward(vf_source()),
    vf_forward(array(array('first' => 1), array(3, 4))),
    vf_forward_arrays(array('first' => 1), array(3, 4)),
    vf_collect_arrays(array('first' => 1), array(3, 4))
), JSON_THROW_ON_ERROR), "\n";
