<?php
$property_sources=array(
    'WP_Ability'=>'wp-includes/abilities-api/class-wp-ability.php',
    'WP_Ability_Category'=>'wp-includes/abilities-api/class-wp-ability-category.php',
    'WP_Block_Type'=>'wp-includes/class-wp-block-type.php',
    'WP_Post'=>'wp-includes/class-wp-post.php',
    'WP_Term'=>'wp-includes/class-wp-term.php',
);
$property_inventory=array();
foreach($property_sources as $class=>$relative){
    $source=dirname(__DIR__,2).'/upstream/wordpress/'.$relative;
    require_once $source;
    $properties=array();
    foreach((new ReflectionClass($class))->getProperties() as $property){
        $properties[$property->getName()]=array('static'=>$property->isStatic(),'visibility'=>$property->isPrivate()?'private':($property->isProtected()?'protected':'public'));
    }
    ksort($properties);
    $property_inventory[$class]=array('path'=>$relative,'original_sha256'=>hash_file('sha256',$source),'properties'=>$properties);
}
function property_fixture_objects(): array {
    global $property_inventory;
    $objects=array();
    foreach($property_inventory as $class=>$info)$objects[$class]=(new ReflectionClass($class))->newInstanceWithoutConstructor();
    return $objects;
}
function property_fixture_names(): array {
    global $property_inventory;
    $names=array();
    foreach($property_inventory as $info)foreach($info['properties'] as $name=>$flags)$names[$name]=true;
    $names=array_keys($names);sort($names,SORT_STRING);
    return array_merge($names,array('absent','é汉','','name.case'));
}
function property_fixture_fields(string $class): array {
    global $property_inventory;
    return array_keys(array_filter($property_inventory[$class]['properties'],static fn(array $flags): bool => !$flags['static']));
}
function property_fixture_unset(mixed $object,string $name): void {
    $unsetter=Closure::bind(static function(mixed $value,string $key): void {unset($value->$key);},null,get_class($object));
    $unsetter($object,$name);
}
if(($argv[1]??'')==='--inventory')echo json_encode($property_inventory,JSON_THROW_ON_ERROR),"\n";
