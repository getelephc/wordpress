<?php
class WordPressStaticCallableProbe { public static function handle(): void {} }
$target = new WordPressStaticCallableProbe();
echo json_encode(array(is_callable(array($target, 'handle')), is_callable(array(WordPressStaticCallableProbe::class, 'handle')))), "\n";
