<?php
$property_names=property_fixture_names();
foreach(property_fixture_objects() as $property_class=>$property_object){
    foreach($property_names as $property_name)$result[]=array($property_class,$property_name,wp_aot_property_exists($property_object,(string)$property_name));
    foreach(property_fixture_fields((string)$property_class) as $property_name){
        $property_before=wp_aot_property_exists($property_object,(string)$property_name);
        property_fixture_unset($property_object,(string)$property_name);
        $result[]=array($property_class,$property_name,$property_before,wp_aot_property_exists($property_object,(string)$property_name));
    }
}
$property_record=json_decode('{"present":null,"gone":false}');
unset($property_record->gone);
foreach(array('present','gone','absent') as $property_name)$result[]=array('stdClass',$property_name,wp_aot_property_exists($property_record,$property_name));
