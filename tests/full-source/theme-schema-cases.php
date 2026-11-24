<?php
class WordPressThemeSchemaProbe extends WP_Theme_JSON {
 public static function filter_schema(mixed $input,mixed $blocks,mixed $elements,mixed $variations):mixed{return parent::sanitize($input,$blocks,$elements,$variations);}
}
if(defined('WP_AOT_NATIVE_RUNTIME')){$schema_xml_capability=new SimpleXMLElement('<schema/>');}
$schema_rows=array();
$schema_inputs=array(null,false,'invalid',array(),array('version'=>3,'unknown'=>7),array('settings'=>false,'styles'=>'invalid'),array('settings'=>array('blockVisibility'=>false,'unknown'=>true,'blocks'=>array('core/group'=>array('viewport'=>array('mobile'=>'400px'),'blockVisibility'=>false,'color'=>array('text'=>true),'unknown'=>7)))),array('styles'=>array('spacing'=>array('blockGap'=>'2em'),'blocks'=>array('core/group'=>array('spacing'=>array('blockGap'=>'1em','padding'=>'2px'),'unknown'=>true)),'elements'=>array('link'=>array('color'=>array('text'=>'red'),':hover'=>array('color'=>array('text'=>'blue'))),'invalid'=>array('color'=>array('text'=>'red'))))));
$schema_inputs[] = json_decode(file_get_contents(WP_SCHEMA_ORIGINAL_ROOT . '/wp-includes/theme.json'),true,512,JSON_THROW_ON_ERROR);
$schema_inputs[] = json_decode(file_get_contents(WP_SCHEMA_ORIGINAL_ROOT . '/wp-content/themes/twentytwentyfive/theme.json'),true,512,JSON_THROW_ON_ERROR);
$schema_inputs[] = json_decode('{"version":3,"styles":{"blocks":{"core/button":{"variations":{"allowed":{"color":{"text":"red"},":hover":{"color":{"text":"blue"}},"blocks":{"core/group":{"spacing":{"blockGap":"8px","padding":"2px"}}}},"denied":{"color":{"text":"black"}}}}}}}',true,512,JSON_THROW_ON_ERROR);
foreach($schema_inputs as $schema_input){
 foreach(array(array(),array('core/group'),array('core/button','core/post-terms','core/navigation-link','fixture/custom')) as $schema_blocks){
  foreach(array(array(),array('core/button'=>array('allowed'))) as $schema_variations){
   $schema_before=$schema_input;
   $schema_result=WordPressThemeSchemaProbe::filter_schema($schema_input,$schema_blocks,array('link','heading','h1','button'),$schema_variations);
   $schema_rows[]=array($schema_before,$schema_input,$schema_blocks,$schema_variations,$schema_result);
  }
 }
}
echo json_encode($schema_rows,JSON_THROW_ON_ERROR),"\n";
