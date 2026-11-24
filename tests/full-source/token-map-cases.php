<?php

function token_map_read_case(WP_Token_Map $map, string $text, int $offset, string $mode): array {
    $matched = -11;
    $value = $map->read_token($text, $offset, $matched, $mode);
    return array($value, $matched);
}

// The complete original class consumes the same valid precomputed wire tables.
$states = array(
    array('storage_version' => WP_Token_Map::STORAGE_VERSION, 'key_length' => 2,
        'groups' => '', 'large_words' => array(),
        'small_words' => "a\0\0Ab\0:)\0Z\0\0", 'small_mappings' => array('one', 'two', 'happy', 'zed')),
    array('storage_version' => WP_Token_Map::STORAGE_VERSION, 'key_length' => 2,
        'groups' => "ab\0", 'large_words' => array("\x03cde\x04long\x01c\x05short"),
        'small_words' => "A\0\0", 'small_mappings' => array('small')),
);
$results = array();
foreach ($states as $state) {
    $map = WP_Token_Map::from_precomputed_table($state);
    if (!$map instanceof WP_Token_Map) throw new LogicException('A valid token map is required.');
    $results[] = $map->to_array();
    foreach (array('a', 'A', 'Ab', 'AB', 'ab', ':)', ':(', 'Z', 'z', '?', 'abc', 'abcde', 'ABCDE', 'abx') as $query) {
        foreach (array('case-sensitive', 'ascii-case-insensitive') as $mode) {
            $results[] = array($map->contains($query, $mode), token_map_read_case($map, $query, 0, $mode),
                token_map_read_case($map, '!' . $query, 1, $mode));
        }
    }
}

class WordPressTokenMapChildProbe extends WP_Token_Map {
    public static function longest_first_then_alphabetical(string $a, string $b): int {
        throw new LogicException('The original lexical comparator must stay in WP_Token_Map.');
    }
}

$construction_cases = array(
    array(),
    array('ab' => 'prefix', 'abc' => 'short', 'abcde' => 'long', 'abcf' => 'equal-f',
        'abcd' => 'equal-d', 'a' => 'one', 'Z' => 'zed', ':)' => '🙂', ':(' => '🙁',
        'xyzz' => 'other', 'xyza' => 'other-a'),
    array('é' => 'accent', 'école' => 'éducation', 'élan' => '🙂', 'été' => 'saison',
        'zz' => 'last', 'zzzz' => 'long-last'),
);
foreach ($construction_cases as $mappings) {
    $snapshot = $mappings;
    $map = WP_Token_Map::from_array((array) $mappings);
    $child_map = WordPressTokenMapChildProbe::from_array((array) $mappings);
    if (!$map instanceof WP_Token_Map || !$child_map instanceof WP_Token_Map) {
        throw new LogicException('Construction requires valid token maps.');
    }
    $results[] = array($map->to_array(), $child_map->to_array(),
        get_class($map), get_class($child_map), $mappings === $snapshot);
    foreach (array('a', 'ab', 'abc', 'abcde', 'abcd', 'abcf', 'abcdef', ':)', ':(',
                   'xyzz', 'xyza', 'é', 'école', 'élan', 'été', 'zz', 'zzzz', '?') as $query) {
        foreach (array('case-sensitive', 'ascii-case-insensitive') as $mode) {
            $results[] = array($map->contains($query, $mode), token_map_read_case($map, $query, 0, $mode),
                token_map_read_case($map, '!' . $query, 1, $mode),
                token_map_read_case($child_map, $query, 0, $mode));
        }
    }
}
echo json_encode($results, JSON_THROW_ON_ERROR), "\n";
