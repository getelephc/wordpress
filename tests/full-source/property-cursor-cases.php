<?php
class CursorValueHolder {
    public mixed $value = array();
    public function __construct(mixed $value) {$this->value=$value;}
}
#[AllowDynamicProperties]
class LazyAttributesControl {
    public mixed $seed;
    public int $reads = 0;
    public function __construct(mixed $seed) {$this->seed = $seed;}
    public function __get(string $name): mixed {
        if ($name !== 'attributes') return null;
        ++$this->reads;
        $this->attributes = $this->seed;
        return $this->attributes;
    }
}
function cursor_seed(mixed $value): mixed {return $value;}
class UnrelatedRecordReference {
    public mixed $headers = array('kept'=>1);
    public function &getHeaders(): mixed {return $this->headers;}
}
$unrelated_record = new UnrelatedRecordReference();
$unrelated_headers =& $unrelated_record->getHeaders();
$unrelated_headers['live'] = 2;
$results=array();
foreach (array(
    array('{}', array(false, false, false, false)),
    array('{"nullable":null}', array(true, false, false, false)),
    array('{"nullable":null,"flag":false}', array(true, true, false, false)),
    array('{"nullable":null,"flag":false,"value":{"x":1}}', array(true, true, true, false)),
) as $lazy_case) {
    $lazy_values = json_decode($lazy_case[0], true);
    $lazy_block = new LazyAttributesControl($lazy_values);
    $lazy_before = $lazy_block->reads;
    $lazy_bits = array();
    for ($lazy_repeat = 0; $lazy_repeat < 2; ++$lazy_repeat) {
        foreach (array('nullable', 'flag', 'value', 'missing') as $lazy_key) {
            $lazy_bits[] = lazy_attribute_exists($lazy_block, (string) $lazy_key);
        }
    }
    $lazy_snapshot = $lazy_block->attributes;
    $lazy_snapshot['copy-only'] = true;
    $lazy_block->seed = array('changed' => true);
    $lazy_cached = lazy_attribute_exists($lazy_block, 'nullable');
    $lazy_expected = array((bool) $lazy_case[1][0], (bool) $lazy_case[1][1], (bool) $lazy_case[1][2], (bool) $lazy_case[1][3],
        (bool) $lazy_case[1][0], (bool) $lazy_case[1][1], (bool) $lazy_case[1][2], (bool) $lazy_case[1][3]);
    if ($lazy_bits !== $lazy_expected || $lazy_before !== 0
        || $lazy_block->reads !== 1 || $lazy_cached !== (bool) $lazy_case[1][0]
        || json_encode($lazy_block->attributes) !== json_encode($lazy_values)) {
        throw new LogicException('Lazy attribute lookup changed presence, cache or snapshots.');
    }
    $results[] = array($lazy_bits, $lazy_before, $lazy_block->reads, $lazy_snapshot,
        $lazy_block->attributes, $lazy_block->seed, $lazy_cached);
}
foreach (array(null, false, 1, 'text') as $lazy_invalid) {
    $lazy_block = new LazyAttributesControl($lazy_invalid);
    try {
        lazy_attribute_exists($lazy_block, 'key');
        throw new LogicException('Invalid lazy attributes were accepted.');
    } catch (TypeError $lazy_error) {
        $results[] = array('TypeError', $lazy_block->reads);
    }
}
foreach (array(array(),array(null,false,1),array('a'=>'one',4=>'two'),array('x'=>array('nested'=>1))) as $source) {
    $holder=new CursorValueHolder($source);
    $first=cursor_current_call($holder->value);
    $reset=cursor_reset_call($holder->value);
    $results[]=array($first,$reset,$holder->value,array_keys((array)$holder->value));
    $copy=cursor_seed($source);
    if ($source) next($copy);
    $advanced=new CursorValueHolder($copy);
    $reset=cursor_reset_call($advanced->value);
    $results[]=array($reset,cursor_current_call($advanced->value),$advanced->value);
}
foreach (array(array(),array('a'=>1,4=>2),new ArrayIterator(array('a'=>1,4=>2))) as $value) foreach (array(false,true) as $preserve) {
    $copy=cursor_iterator_call($value,$preserve);$results[]=array($copy,array_keys($copy));
}
foreach (array('', 'é汉', "a\0b", false,true,123) as $value) $results[]=cursor_length_call($value);
foreach (array('nonce', 'é汉', "a\0b", '', null, false, true, 0, 42, array(), array('key'=>'value')) as $value) {
    $before = scalar_cache_call(null, false);
    $written = scalar_cache_call($value, true);
    $snapshot = scalar_cache_call(null, false);
    if (is_array($snapshot[1])) $snapshot[1]['copy-only'] = true;
    $results[] = array($before, $written, $snapshot, scalar_cache_call(null, false));
}
$results[] = array_cache_call('clear', null, null);
foreach (array('null'=>null,'false'=>false,4=>'four',-2=>'negative','utf8'=>'é汉','nested'=>array('key'=>'value')) as $key=>$value) {
    $results[] = array_cache_call('set',$key,$value);
    $results[] = array_cache_call('read',$key,null);
}
$snapshot = array_cache_call('snapshot',null,null);
$snapshot[1]['copy-only'] = true;
$snapshot[1]['nested']['key'] = 'changed copy';
$results[] = array($snapshot,array_cache_call('snapshot',null,null));
$results[] = array_cache_call('read','absent',null);
$results[] = array_cache_call('append',null,'tail');
$results[] = array_cache_call('pop',null,null);
$results[] = array_cache_call('unset',4,null);
$results[] = array_cache_call('read',4,null);
$results[] = array_cache_call('set','nested',array());
$results[] = array_cache_call('clear',null,null);
$results[] = array_cache_call('pop',null,null);
$results[] = array_cache_call('append',null,'fresh');
$results[] = array_cache_call('snapshot',null,null);
$results[] = nullable_cache_call(null,false);
foreach (array(null,false,true,0,42,'','é汉',array(),array('key'=>'value')) as $value) {
    $results[] = nullable_cache_call($value,true);
    $snapshot = nullable_cache_call(null,false);
    if (is_array($snapshot[2])) $snapshot[2]['copy-only'] = true;
    $results[] = array($snapshot,nullable_cache_call(null,false));
}
foreach (array(0,1,0,2,0,3,0,4,0,5,0,6,0,7,0) as $op) $results[] = nullable_literal_cache_call($op);
foreach (array(0,1,0,2,0,3,0,4,0) as $op) $results[] = false_literal_cache_call($op);
$results[] = late_static_cache_eval_policy('return 19;');
$late_base_alias =& LateStaticCacheControl::$files;
$late_isolated_alias =& IsolatedLateStaticCacheControl::$files;
foreach(array('LateStaticCacheControl','InheritedLateStaticCacheControl','IsolatedLateStaticCacheControl') as $late_owner) {
    foreach(array(null,false,array(),array('nested'=>array('x'=>1))) as $late_value) {
        LateStaticCacheControl::reset(array('base'=>'seed'));
        IsolatedLateStaticCacheControl::reset(array('isolated'=>'seed'));
        $late_before = $late_owner::state();
        $late_owner::write('file',$late_value);
        $late_after = $late_owner::state();
        $late_owner::write('08',array('next'=>true));
        $late_marked = $late_owner::mark('core',array('one','two','one'));
        $late_rejected = $late_owner::mark('invalid',array('ignored'));
        $late_snapshot = $late_owner::state();
        $late_snapshot[0]['local-only']=true;
        $late_snapshot[1]['core']['one']=false;
        $results[]=array($late_owner,$late_value,$late_before,$late_after,$late_marked,$late_rejected,
            $late_snapshot,$late_owner::state(),LateStaticCacheControl::state(),InheritedLateStaticCacheControl::state(),
            IsolatedLateStaticCacheControl::state(),$late_base_alias,$late_isolated_alias,$late_owner::read('file'),$late_owner::read('08'));
    }
}
unset($late_base_alias,$late_isolated_alias);
$registry_base = new \SimplePie\Registry();
$results[]=array(wp_aot_require_SimplePie_Registry($registry_base)===$registry_base);
$registry_variants=array(new WordPressRegistryProbe('first'),new WordPressInheritedRegistryProbe('inherited'),new WordPressOtherRegistryProbe('other'));
foreach($registry_variants as $registry_origin)foreach($registry_variants as $registry_next)foreach(array(array(),array(null,false,3),array('nested'=>array('x'=>1))) as $registry_parameters) {
    $registry_holder=new WordPressRegistryHolder($registry_origin);
    $registry_created=registry_probe_create($registry_holder,$registry_next,(array)$registry_parameters);
    $registry_after_create=$registry_holder->registry===$registry_next;
    $registry_holder->registry=$registry_origin;
    $registry_called=registry_probe_call($registry_holder,$registry_next,(array)$registry_parameters);
    $results[]=array(get_class($registry_origin),$registry_origin->marker,$registry_parameters,
        $registry_created,$registry_called,$registry_after_create,$registry_holder->registry===$registry_next,
        wp_aot_require_SimplePie_Registry($registry_origin)===$registry_origin);
}
$first=singleton_cache_call(false);$first->visits=7;
$second=singleton_cache_call(false);
$results[]=array($first===$second,$second->visits);
$third=singleton_cache_call(true);
$results[]=array($third===$first,$third->visits,$first->visits);
foreach (array('{"r":90,"label":"é汉"}', '{"r":null,"f":2,"c":{"x":1}}', '{"c":{"x":0,"w":2},"extra":false}', '{}') as $record_json) {
    foreach (array('r','f','c','absent') as $record_key) {
        $record = json_decode($record_json);
        $returned = record_unset_call($record,$record_key);
        $results[] = array($record === $returned,$record);
    }
}
foreach (array(
    array('{}', '{}'),
    array('{"ID":null}', '{}'),
    array('{"ID":false}', '{}'),
    array('{"ID":0}', '{}'),
    array('{"ID":701}', '{}'),
    array('{"ID":"701","post_title":"Kept","meta":{"nested":[null,false,0]}}', '{"post_title":"Kept","meta":{"nested":[null,false,0]}}'),
    array('{"before":1,"ID":701,"after":null}', '{"before":1,"after":null}'),
    array('{"0":"zero","08":"zero-eight","id":9,"ID":701}', '{"0":"zero","08":"zero-eight","id":9}'),
) as $attachment_record_case) {
    $attachment_record = json_decode($attachment_record_case[0]);
    $attachment_alias = $attachment_record;
    $attachment_snapshot = clone $attachment_record;
    $attachment_returned = record_unset_call($attachment_record, 'ID');
    if (array_key_exists('ID', (array) $attachment_returned)
        || json_encode($attachment_returned) !== json_encode(json_decode($attachment_record_case[1]))) {
        throw new LogicException('Prepared attachment ID deletion changed other fields.');
    }
    $results[] = array($attachment_returned === $attachment_record, $attachment_alias === $attachment_record,
        (array) $attachment_returned, (array) $attachment_alias, (array) $attachment_snapshot);
}
$results[] = array($unrelated_record->headers,$unrelated_headers);

// Duplicate keys and key-method effects distinguish indexed collection from keyed collection.
class CursorTraceIterator implements Iterator {
    public array $trace = array();
    private array $entries;
    private int $position = 0;
    private bool $throw_key;
    public function __construct(array $entries, bool $throw_key = false) {$this->entries=$entries; $this->throw_key=$throw_key;}
    public function rewind(): void {$this->trace[]='rewind'; $this->position=0;}
    public function valid(): bool {$this->trace[]='valid:'.$this->position; return $this->position<count($this->entries);}
    public function current(): mixed {$this->trace[]='current:'.$this->position; return $this->entries[$this->position][1];}
    public function key(): mixed {
        $this->trace[]='key:'.$this->position;
        if ($this->throw_key) throw new RuntimeException('key method called');
        return $this->entries[$this->position][0];
    }
    public function next(): void {$this->trace[]='next:'.$this->position; ++$this->position;}
    public function position(): int {return $this->position;}
}
class CursorTraceAggregate implements IteratorAggregate {
    public CursorTraceIterator $iterator;
    public int $calls = 0;
    public function __construct(CursorTraceIterator $iterator) {$this->iterator=$iterator;}
    public function getIterator(): Traversable {++$this->calls; return $this->iterator;}
}
$iterator_sequences = array(
    array(),
    array(array('same',1),array('same',2),array('tail',3)),
    array(array(3,'integer'),array('3','numeric string'),array('03','leading zero')),
    array(array(null,'null'),array(false,'false'),array(true,'true')),
    array(array('nested',array('x'=>1)),array('nested',array('x'=>2))),
    array(array(array(),'invalid key'),array('tail','after invalid key')),
    array(array(new stdClass(),'object key'),array('tail','after object key'))
);
foreach ($iterator_sequences as $entries) foreach (array(false,true) as $preserve) {
    foreach (array(false,true) as $aggregate) foreach (array(false,true) as $throw_key) {
        $iterator = new CursorTraceIterator((array)$entries,$throw_key);
        $aggregate_input = new CursorTraceAggregate($iterator);
        $input = $aggregate ? cursor_seed($aggregate_input) : cursor_seed($iterator);
        try {
            $collected = cursor_iterator_call($input,$preserve);
            $results[] = array('ok',$collected,array_keys($collected),$iterator->trace,$iterator->position(),$aggregate_input->calls);
        } catch (Throwable $error) {
            $results[] = array('error',get_class($error),$iterator->trace,$iterator->position(),$aggregate_input->calls);
        }
    }
}
foreach (array(null,false,true,0,1,'text',new stdClass()) as $invalid) {
    try {$results[]=array('ok',cursor_iterator_call($invalid,true));}
    catch (TypeError $error) {$results[]=array('error','TypeError');}
}
// Checked object views preserve live property aliases, source snapshots and fresh receivers.
foreach (array(null,array(),array(null,false,1),array('nested'=>array('x'=>1))) as $query_source) {
    foreach (array(null,false,42,'é汉',array(),array('replacement'=>array('x'=>2))) as $query_replacement) {
        $query = new CursorQueryReferenceView();
        $query->comments = cursor_seed($query_source);
        $query->comments_by_type = array('all'=>$query_source);
        $comments_alias =& query_reference_view_call(cursor_seed($query))->comments;
        $types_alias =& query_reference_view_call(cursor_seed($query))->comments_by_type;
        $comments_snapshot = $comments_alias;
        $types_snapshot = $types_alias;
        query_reference_write_call($comments_alias, cursor_seed($query_replacement));
        $types_alias['changed'] = $query_replacement;
        $results[] = array(query_reference_view_call($query) === $query,$query->comments,$comments_alias,
            $query->comments_by_type,$types_alias,$comments_snapshot,$types_snapshot);
        $query->comments = cursor_seed($query_source);
        $query->comments_by_type = array('replaced'=>$query_replacement);
        $results[] = array($comments_alias,$types_alias,$query->comments,$query->comments_by_type,
            $comments_snapshot,$types_snapshot);
        unset($comments_alias,$types_alias);
    }
}
// Named-global aliases retain whole-value rebinding and source snapshots.
foreach (array(null,false,0,'text',array(),array('nested'=>array('x'=>1))) as $initial_global) {
    foreach (array(null,false,42,'é汉',array(),array('changed'=>2)) as $global_replacement) {
        $link = cursor_seed($initial_global);
        $results[] = global_reference_call($global_replacement);
        $results[] = array(wp_aot_bookmark_link_value(),wp_aot_bookmark_link_is_set());
        unset($link);
        $results[] = global_reference_call($global_replacement);
    }
}
// Direct queue-property references preserve prepend order, live aliases and snapshots.
foreach (array(array(),array('base'),array(7=>'a',2=>'b'),array('head'=>'first','tail'=>'last'),array('nested'=>array('x'=>null),4=>false)) as $queue_source) {
    foreach (array(array(),array('first'),array('x','y'),array(null,array('z'=>1))) as $queue_prefix) {
        $queue_holder=new CursorQueueState();
        $queue_holder->queue=cursor_seed($queue_source);
        $queue_snapshot=$queue_holder->queue;
        $queue_alias =& $queue_holder->queue;
        $queue_count=queue_prepend_call($queue_holder,(array)$queue_prefix);
        $queue_after=$queue_holder->queue;
        $queue_alias[]='live';
        $results[]=array($queue_count,$queue_after,array_keys($queue_after),$queue_holder->queue,$queue_alias,$queue_snapshot,$queue_source);
        unset($queue_alias);
    }
}
require __DIR__ . '/sidebar-reference-cases.php';
foreach (sidebar_reference_cases() as $row) $results[] = $row;
require __DIR__ . '/mapped-diff-cases.php';
foreach (mapped_diff_cases() as $row) $results[] = $row;
foreach (widget_factory_cases() as $row) $results[] = $row;
foreach (admin_bar_cases() as $row) $results[] = $row;
foreach (ability_properties_cases() as $row) $results[] = $row;
require __DIR__ . '/nullable-static-property-cases.php';
foreach (nullable_static_property_cases() as $row) $results[] = $row;
// Public object value views avoid mixed property reads without replacing objects.
function aot_public_record(mixed $value): mixed { return $value; }
foreach (array('SQL term','Core term','','é汉') as $name) {
    $record = json_decode(json_encode(array('name'=>$name,'nested'=>array('keep'=>1)),JSON_THROW_ON_ERROR));
    $returned = aot_public_record($record);
    $view = get_object_vars($returned);
    $view['name'] = 'copy-only';
    $actual = get_object_vars(aot_public_record($record));
    $results[] = array((string)$actual['name'],array_keys($actual),$view,$returned === $record);
}
echo json_encode($results),"\n";
