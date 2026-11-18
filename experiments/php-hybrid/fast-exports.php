<?php
// Typed entry points retain the selected WordPress implementations.
#[Export]
function ep_fast_absint(int $value): int { return absint($value); }
#[Export]
function ep_fast_numeric_keys(string $types,bool $is_array): bool {
    if(!$is_array)return wp_is_numeric_array(null);
    $keys=wordpress_lists_map();
    for($i=0;$i<strlen($types);++$i) {
        if($types[$i]==='s') { $keys['key'.$i]=null; }
        else { $keys[$i]=null; }
    }
    return wp_is_numeric_array($keys);
}
#[Export]
function ep_fast_text(int $operation,string $content,bool $flag): string {
    switch($operation) {
        case 0:return wpautop($content,$flag);
        case 1:return wp_strip_all_tags($content,$flag);
        case 2:return wp_kses_no_null($content,['slash_zero'=>$flag?'remove':'keep']);
        case 3:return wp_spaces_regexp();
        case 4:return wp_make_link_relative($content);
        case 5:return get_html_split_regex();
        case 6:return unescape_invalid_shortcodes($content);
    }
    throw new InvalidArgumentException('Unknown typed text operation.');
}
