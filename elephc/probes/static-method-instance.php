<?php

// Compile-only diagnostic, outside the verified native profiles.
class WordPressStaticMethodProbe {
    public static function normalize(string $value): string { return strtolower($value); }
    public function via_instance(string $value): string { return $this->normalize($value); }
}

echo (new WordPressStaticMethodProbe())->via_instance('HEADER');
