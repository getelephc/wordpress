<?php

// WordPress do_action_ref_array needs an array element to alias this variable.
$value = 'before';
$arguments = array( &$value );
$arguments[0] = 'after';
echo $value, "\n";
