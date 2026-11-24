<?php

final class StaticSnapshotCell {
    public mixed $value = array();
}

function static_snapshot(bool $append): array {
    static $cell = new StaticSnapshotCell();
    $values =& $cell->value;
    if ($append) $values[] = 'tail';
    return array($values);
}

$before = static_snapshot(false);
$after = static_snapshot(true);
echo json_encode(array($before,$after)), "\n";
