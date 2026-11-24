<?php
// Keep the same XML-aware array lowering capability as the complete WordPress graph.
if(defined('WP_AOT_NATIVE_RUNTIME')){$hook_xml_capability=new SimpleXMLElement('<hooks/>');}
function hook_builtin_suffix(mixed $text): mixed {return (string)$text.'|suffix';}
$rows=array();
foreach(array('strip_tags','STRIP_TAGS','\\strip_tags')as$callback){
    foreach(array(1,2)as$arguments){
        $hook='fixture-strip-'.$callback.'-'.$arguments;
        add_filter($hook,$callback,10,1);
        add_filter($hook,'hook_builtin_suffix',11,1);
        $has=has_filter($hook,$callback);
        $value=$arguments===1?apply_filters($hook,'<b>one</b> &amp; <i>two</i>'):apply_filters($hook,'<b>one</b> &amp; <i>two</i>','ignored');
        $removed=remove_filter($hook,$callback,10);
        $rows[]=array($callback,$arguments,$has,$value,$removed,has_filter($hook,$callback),apply_filters($hook,'<b>kept</b>'));
        remove_all_filters($hook);
    }
}
$zero_calls=0;
$zero_callback=static function()use(&$zero_calls):mixed{++$zero_calls;return 'zero';};
add_filter('fixture-zero',$zero_callback,10,0);
$zero_value=apply_filters('fixture-zero','ignored','extra');
$zero_present=has_filter('fixture-zero',$zero_callback);
$zero_removed=remove_filter('fixture-zero',$zero_callback,10);
$rows[]=array('zero',$zero_value,$zero_calls,$zero_present,$zero_removed,has_filter('fixture-zero',$zero_callback));

class WordPressHookMethodProbe {
    public int $calls=0;
    public function append(mixed $value,mixed $suffix):mixed{++$this->calls;return (string)$value.(string)$suffix;}
}
$method_object=new WordPressHookMethodProbe();
$method_callback=array($method_object,'append');
add_filter('fixture-method',$method_callback,10,2);
$method_value=apply_filters('fixture-method','value','|method');
$method_present=has_filter('fixture-method',$method_callback);
$method_removed=remove_filter('fixture-method',$method_callback,10);
$rows[]=array('method',$method_value,$method_object->calls,$method_present,$method_removed,has_filter('fixture-method',$method_callback));

class WordPressHookRecursionProbe {
    public int $depth = 0;
    public int $maximum;
    public array $trace = array();
    public function __construct(int $maximum) { $this->maximum = $maximum; }
    public function call(mixed $value): mixed {
        $level = $this->depth++;
        $this->trace[] = array('enter',$level,current_filter(),doing_filter('fixture-nested'),$value);
        $inner = $level < $this->maximum ? apply_filters('fixture-nested',(string)$value.'>'.$level) : (string)$value;
        $this->trace[] = array('leave',$level,current_filter(),doing_filter('fixture-nested'),$inner);
        --$this->depth;
        return $inner.'<'.$level;
    }
}
foreach (array(0,1,2,3) as $maximum) {
    $nested_probe = new WordPressHookRecursionProbe($maximum);
    add_filter('fixture-nested',array($nested_probe,'call'),10,1);
    add_filter('fixture-nested','hook_builtin_suffix',11,1);
    $nested_result = apply_filters('fixture-nested','start');
    $rows[] = array('recursive-filter',$maximum,$nested_result,$nested_probe->trace,$nested_probe->depth,current_filter(),doing_filter('fixture-nested'));
    remove_all_filters('fixture-nested');
}
$hook_all_depth = 0;
$hook_all_maximum = 0;
$hook_all_trace = array();
function hook_all_recursive(mixed $name,mixed $value): mixed {
    global $hook_all_depth,$hook_all_maximum,$hook_all_trace;
    $level = $hook_all_depth++;
    $hook_all_trace[] = array('enter',$level,$name,$value,current_filter());
    if ($level < $hook_all_maximum) apply_filters('fixture-all-child','nested-'.$level);
    $hook_all_trace[] = array('leave',$level,$name,current_filter());
    --$hook_all_depth;
    return null;
}
foreach (array(0,1,2) as $maximum) {
    $hook_all_depth = 0; $hook_all_maximum = $maximum; $hook_all_trace = array();
    add_filter('all','hook_all_recursive',10,2);
    add_filter('fixture-all-main','hook_builtin_suffix',10,1);
    add_filter('fixture-all-child','hook_builtin_suffix',10,1);
    $all_value = apply_filters('fixture-all-main','main');
    $rows[] = array('recursive-all',$maximum,$all_value,$hook_all_trace,$hook_all_depth,current_filter());
    remove_all_filters('all'); remove_all_filters('fixture-all-main'); remove_all_filters('fixture-all-child');
}
foreach (array(-2,0,7,1000) as $priority) {
    $direct_hook = new WP_Hook();
    $direct_hook->add_filter('direct','hook_builtin_suffix',$priority,1);
    $bucket_alias = $direct_hook->callbacks[$priority];
    $bucket_count = count($bucket_alias);
    $removed = $direct_hook->remove_filter('direct','hook_builtin_suffix',$priority);
    $bucket_alias['detached'] = array();
    $rows[] = array('bucket-snapshot',$priority,$bucket_count,$removed,array_keys($bucket_alias),array_keys($direct_hook->callbacks),$direct_hook->has_filters());
    unset($bucket_alias);
}

class WordPressHookStructuredRecursionProbe {
    public array $trace = array();
    public int $calls = 0;
    public function call(mixed $value, int $depth): mixed {
        ++$this->calls;
        $this->trace[] = array('enter',$depth,current_filter(),$value);
        $result = $depth > 0 ? apply_filters('fixture-structured',$value,$depth-1) : $value;
        $this->trace[] = array('leave',$depth,current_filter(),$result);
        return $result;
    }
}
$structured_object = new stdClass();
$structured_object->label = 'Cafés';
$structured_object->values = array(true,null,array('nested'=>7));
foreach (array(array(),array(3=>false,'nested'=>array('label'=>'é','value'=>null)),$structured_object) as $structured_value) {
    foreach (array(1,3,5) as $structured_depth) {
        $structured_probe = new WordPressHookStructuredRecursionProbe();
        add_filter('fixture-structured',array($structured_probe,'call'),10,2);
        $structured_before = $structured_value;
        $structured_result = apply_filters('fixture-structured',$structured_value,$structured_depth);
        $rows[] = array('structured-recursion',$structured_depth,$structured_before,$structured_value,$structured_result,$structured_probe->trace,$structured_probe->calls,current_filter(),doing_filter('fixture-structured'));
        remove_all_filters('fixture-structured');
    }
}

function hook_cursor_component_cases(): array { $cases = require __DIR__ . '/hook-cursor-cases.php'; return $cases; }
foreach (hook_cursor_component_cases() as $cursor_case) $rows[] = $cursor_case;

echo json_encode($rows,JSON_THROW_ON_ERROR),"\n";
