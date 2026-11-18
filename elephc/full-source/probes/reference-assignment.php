<?php

// WordPress legacy compatibility maps alias one array field to another.
$fields = array( 'name' => 'before' );
$fields['legacy_name'] =& $fields['name'];
$fields['legacy_name'] = 'after';
echo $fields['name'], "\n";
