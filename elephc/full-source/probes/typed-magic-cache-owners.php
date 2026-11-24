<?php
#[AllowDynamicProperties]
class LazyCache {
    public int $reads = 0;
    public mixed $seed;
    public function __construct(mixed $seed) { $this->seed = $seed; }
    public function __get(string $name): mixed {
        if ($name !== 'cached') { return null; }
        if (++$this->reads > 3) { throw new LogicException('recursive getter'); }
        $this->cached = $this->seed;
        return $this->cached;
    }
}
class ChildCache extends LazyCache { public string $padding = 'extra'; }
function known(LazyCache $value): mixed { return $value->cached; }
function literal(LazyCache $value): mixed { return $value->{'cached'}; }
function boxed(mixed $value): mixed { return $value->cached; }
function run(): void {
    foreach ([null, false, ['x' => null, 'flag' => false]] as $seed) {
        $value = new ChildCache($seed);
        $first = known($value);
        $value->seed = 'changed';
        $second = literal($value);
        $third = boxed($value);
        echo json_encode([$first, $second, $third, $value->reads]), "\n";
        unset($first, $second, $third, $value);
    }
}
run();

class LiteralGetOnlyProbe {
    public function __get(string $name): int { echo 'get:', $name, "\n"; return 1; }
}
class LiteralIssetProbe {
    public function __get(string $name): int { echo 'get:', $name, "\n"; return 0; }
    public function __isset(string $name): bool { echo 'isset:', $name, "\n"; return true; }
}
function literal_probe_controls(): void {
$get_only = new LiteralGetOnlyProbe();
var_dump(isset($get_only->{'missing'}));
var_dump($get_only->{'missing'} ?? 'default');
var_dump(empty($get_only->{'missing'}));
$with_isset = new LiteralIssetProbe();
var_dump(isset($with_isset->{'missing'}));
var_dump($with_isset->{'missing'} ?? 'default');
var_dump(empty($with_isset->{'missing'}));
unset($get_only, $with_isset);
}
literal_probe_controls();

#[AllowDynamicProperties]
class CachedFloatProbe {
    public int $reads = 0;
    public function __get(string $name): float {
        if ($name !== 'cached') throw new LogicException('name');
        if (++$this->reads > 2) throw new LogicException('recursive float cache');
        $this->cached = 9.5;
        return $this->cached;
    }
}
#[AllowDynamicProperties]
class CachedBoolProbe {
    public int $reads = 0;
    public function __get(string $name): bool {
        if ($name !== 'cached') throw new LogicException('name');
        if (++$this->reads > 2) throw new LogicException('recursive bool cache');
        $this->cached = false;
        return $this->cached;
    }
}
#[AllowDynamicProperties]
class CachedStringProbe {
    public int $reads = 0;
    public function __get(string $name): string {
        if ($name !== 'cached') throw new LogicException('name');
        if (++$this->reads > 2) throw new LogicException('recursive string cache');
        $this->cached = str_repeat('é', 64) . "\0end";
        return $this->cached;
    }
}
#[AllowDynamicProperties]
class CachedArrayProbe {
    public int $reads = 0;
    public function __get(string $name): array {
        if ($name !== 'cached') throw new LogicException('name');
        if (++$this->reads > 2) throw new LogicException('recursive array cache');
        $this->cached = ['x' => ['y' => 1]];
        return $this->cached;
    }
}
#[AllowDynamicProperties]
class CachedObjectProbe {
    public int $reads = 0;
    public function __get(string $name): stdClass {
        if ($name !== 'cached') throw new LogicException('name');
        if (++$this->reads > 2) throw new LogicException('recursive object cache');
        $this->cached = (object) ['value' => 7];
        return $this->cached;
    }
}
function concrete_cache_controls(): void {
    $float = new CachedFloatProbe();
    $a = $float->cached; $b = $float->{'cached'};
    if ($a !== 9.5 || $b !== 9.5 || $float->reads !== 1) throw new LogicException('float cache');
    echo json_encode([$a, $b, $float->reads]), "\n";
    $bool = new CachedBoolProbe();
    $c = $bool->cached; $d = $bool->{'cached'};
    if ($c !== false || $d !== false || $bool->reads !== 1) throw new LogicException('bool cache');
    echo json_encode([$c, $d, $bool->reads]), "\n";
    $string = new CachedStringProbe();
    $e = $string->cached; $f = $string->{'cached'};
    if ($e !== str_repeat('é', 64) . "\0end" || $e !== $f || $string->reads !== 1) throw new LogicException('string cache');
    echo json_encode([$e, $f, $string->reads]), "\n";
    $array = new CachedArrayProbe();
    $g = $array->cached; $g['x']['y'] = 2; $h = $array->{'cached'};
    if ($g['x']['y'] !== 2 || $h['x']['y'] !== 1 || $array->reads !== 1) throw new LogicException('array cache');
    echo json_encode([$g, $h, $array->reads]), "\n";
    $object = new CachedObjectProbe();
    $i = $object->cached; $j = $object->{'cached'};
    $same = $i === $j;
    $i->value = 9;
    if (!$same || $j->value !== 9 || $object->reads !== 1) throw new LogicException('object cache');
    echo json_encode([$same, $i, $j, $object->reads]), "\n";
    unset($a, $b, $c, $d, $e, $f, $g, $h, $i, $j, $float, $bool, $string, $array, $object);
}
concrete_cache_controls();
