<?php

function ability_property_execute(mixed $input = null): mixed {return $input;}
function ability_property_permission(mixed $input = null): bool {return true;}

function ability_properties_cases(): array {
    $rows = array();
    $annotation_cases = array(
        array('null', '{"readonly":null,"destructive":null,"idempotent":null}'),
        array('{}', '{"readonly":null,"destructive":null,"idempotent":null}'),
        array('{"readonly":true,"extra":"kept"}', '{"readonly":true,"destructive":null,"idempotent":null,"extra":"kept"}'),
        array('{"destructive":false,"idempotent":null}', '{"readonly":null,"destructive":false,"idempotent":null}'),
        array('{"readonly":null,"destructive":true,"idempotent":false}', '{"readonly":null,"destructive":true,"idempotent":false}'),
    );
    $policies = array(
        array(array(), false, false),
        array(array('public' => true), true, true),
        array(array('public' => false), false, false),
        array(array('public' => null, 'show_in_rest' => true), true, false),
        array(array('public' => true, 'show_in_rest' => false), false, true),
        array(array('public' => false, 'show_in_rest' => null), false, false),
    );
    foreach ($annotation_cases as $annotation_case) foreach ($policies as $policy) {
        $annotations = json_decode($annotation_case[0], true, 512, JSON_THROW_ON_ERROR);
        $expected_annotations = json_decode($annotation_case[1], true, 512, JSON_THROW_ON_ERROR);
        $meta_input = $annotations === null ? array('fixture' => array('x' => 1))
            : array('annotations' => $annotations, 'fixture' => array('x' => 1));
        foreach ($policy[0] as $key => $value) $meta_input[$key] = $value;
        $ability = new WP_Ability('native/properties', array(
            'label' => 'Native label', 'description' => 'Native description', 'category' => 'native-category',
            'execute_callback' => ability_property_execute(...), 'permission_callback' => ability_property_permission(...),
            'meta' => $meta_input,
        ));
        $meta = $ability->get_meta();
        $copy = $ability->get_meta();
        $copy['annotations']['readonly'] = 'local copy';
        $after = $ability->get_meta();
        if ($meta['annotations'] !== $expected_annotations || $meta['show_in_rest'] !== $policy[1]
            || $meta['public'] !== $policy[2] || $meta['fixture'] !== array('x' => 1)
            || $after !== $meta || $copy['annotations']['readonly'] !== 'local copy') {
            throw new LogicException('Ability annotation defaults, policy or snapshot independence differ.');
        }
        $rows[] = array($ability->get_name(), $ability->get_label(), $ability->get_description(), $ability->get_category(),
            $meta, $ability->get_meta_item('annotations'), $ability->get_meta_item('missing', 'fallback'), $copy, $after);
    }
    return $rows;
}
