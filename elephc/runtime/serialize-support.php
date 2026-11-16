<?php

// This profile accepts JSON-encodable UTF-8 attributes, without charset recovery.
function _elephc_block_json_encode(array $attributes, int $flags): string {
    $encoded = json_encode($attributes, $flags);
    if (!is_string($encoded)) {
        throw new InvalidArgumentException('Block attributes cannot be encoded as JSON.');
    }
    return (string) $encoded;
}

function wordpress_serialization_json(string $input): mixed {
    $decoded = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new InvalidArgumentException('Invalid JSON input.');
    }
    return $decoded;
}

function wordpress_serialization_validate(array $blocks, int $depth = 0): array {
    if ($depth > 64) {
        throw new InvalidArgumentException('Block nesting exceeds 64 levels.');
    }
    if (!array_is_list($blocks)) {
        throw new InvalidArgumentException('Blocks must be a JSON list.');
    }
    $result = [];
    foreach ($blocks as $block) {
        if (!is_array($block)) {
            throw new InvalidArgumentException('Each block must be an object.');
        }
        foreach (['blockName', 'attrs', 'innerBlocks', 'innerHTML', 'innerContent'] as $field) {
            if (!array_key_exists($field, $block)) {
                throw new InvalidArgumentException('Missing block field: ' . $field);
            }
        }
        if ($block['blockName'] !== null && !is_string($block['blockName'])) {
            throw new InvalidArgumentException('blockName must be a string or null.');
        }
        if (!is_string($block['innerHTML']) || !is_array($block['innerBlocks']) || !is_array($block['innerContent'])) {
            throw new InvalidArgumentException('Invalid block content fields.');
        }
        $children = wordpress_serialization_validate((array) $block['innerBlocks'], $depth + 1);
        $chunks = (array) $block['innerContent'];
        if (!array_is_list($chunks)) {
            throw new InvalidArgumentException('innerContent must be a JSON list.');
        }
        $markers = 0;
        foreach ($chunks as $chunk) {
            if ($chunk === null) {
                ++$markers;
            } elseif (!is_string($chunk)) {
                throw new InvalidArgumentException('innerContent accepts only strings and null markers.');
            }
        }
        if ($markers !== count($children)) {
            throw new InvalidArgumentException('Child blocks and null markers must match.');
        }
        $result[] = [
            'blockName' => $block['blockName'], 'attrs' => $block['attrs'],
            'innerBlocks' => $children, 'innerHTML' => $block['innerHTML'], 'innerContent' => $chunks,
        ];
    }
    return $result;
}

function wordpress_serialize_text(string $mode, string $input): string {
    if ($mode === 'roundtrip') {
        $parser = new WP_Block_Parser();
        return serialize_blocks(wordpress_serialization_validate($parser->parse($input)));
    }
    if ($mode === 'blocks') {
        $decoded = wordpress_serialization_json($input);
        if (!is_array($decoded) || !str_starts_with(ltrim($input), '[')) {
            throw new InvalidArgumentException('Blocks must be a JSON list.');
        }
        return serialize_blocks(wordpress_serialization_validate((array) $decoded));
    }
    if ($mode === 'attributes') {
        $decoded = wordpress_serialization_json($input);
        if (!is_array($decoded)) {
            throw new InvalidArgumentException('Attributes must be a JSON object or list.');
        }
        return serialize_block_attributes((array) $decoded);
    }
    throw new InvalidArgumentException('Unknown serialization mode.');
}
