<?php
function vf_collect(mixed ...$values): array { return array($values); }
function vf_collect_one(mixed $prefix, mixed ...$values): array { return array($prefix, $values); }
function vf_collect_arrays(array ...$values): array { return array($values); }
function vf_forward(array $values): array { return array(vf_collect(...$values), vf_collect_one('prefix', ...$values)); }
function vf_forward_arrays(array ...$values): array { return array(vf_collect(...$values), vf_collect_one('prefix', ...$values)); }
function vf_source(): array { return array(1, 'two', null, false); }
