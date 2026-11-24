<?php
global $pattern_test_action,$pattern_test_echoes,$pattern_test_trace;
$pattern_test_action='init';$pattern_test_echoes=0;$pattern_test_trace=array();
function current_action():mixed{global $pattern_test_action;return $pattern_test_action;}
function __(mixed $text,mixed ...$unused):mixed{return $text;}
function _doing_it_wrong(mixed ...$arguments):void{throw new LogicException('Unexpected pattern diagnostic: '.json_encode($arguments));}
function esc_html_e(mixed $text,mixed ...$unused):void{global $pattern_test_echoes;++$pattern_test_echoes;echo htmlspecialchars((string)$text,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function apply_block_hooks_to_content(mixed $content,mixed $pattern,mixed $callback):mixed{
 global $pattern_test_trace;$pattern_test_trace[]=array($pattern['name'],array_keys((array)$pattern),isset($pattern['filePath']),hash('sha256',(string)$content),$callback);return $content;
}
function _x(mixed ...$arguments):string{throw new LogicException('This theme environment helper is outside the registry control.');}
function esc_attr__(mixed ...$arguments):string{throw new LogicException('This theme environment helper is outside the registry control.');}
function esc_attr_e(mixed ...$arguments):string{throw new LogicException('This theme environment helper is outside the registry control.');}
function esc_attr_x(mixed ...$arguments):string{throw new LogicException('This theme environment helper is outside the registry control.');}
function esc_html__(mixed ...$arguments):string{throw new LogicException('This theme environment helper is outside the registry control.');}
function esc_html_x(mixed ...$arguments):string{throw new LogicException('This theme environment helper is outside the registry control.');}
function esc_url(mixed ...$arguments):string{throw new LogicException('This theme environment helper is outside the registry control.');}
function get_template_directory_uri(mixed ...$arguments):string{throw new LogicException('This theme environment helper is outside the registry control.');}
function wp_kses_post(mixed ...$arguments):string{throw new LogicException('This theme environment helper is outside the registry control.');}
