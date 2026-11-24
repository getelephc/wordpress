<?php
if(defined('WP_AOT_NATIVE_RUNTIME')){$wrapper_control_xml=new SimpleXMLElement('<wrapper/>');}
WP_Block_Supports::get_instance()->register('fixture',array('apply'=>'wrapper_control_apply'));
$wrapper_control_inputs=array(
 array(array(),array()),
 array(array(),array('aria-label'=>'Menu')),
 array(array(),array('aria-label'=>'Menu 2')),
 array(array(),array('aria-label'=>'Cafés & amenities')),
 array(array(),array('aria-label'=>'0')),
 array(array(),array('aria-label'=>'0 2')),
 array(array('aria-label'=>'Support'),array()),
 array(array('aria-label'=>'Support'),array('aria-label'=>'')),
 array(array('aria-label'=>'Support'),array('aria-label'=>'Extra')),
 array(array('aria-label'=>'Support'),array('aria-label'=>0)),
 array(array('aria-label'=>0),array('aria-label'=>null)),
 array(array('aria-label'=>true),array('aria-label'=>false)),
 array(array('aria-label'=>array('invalid')),array('aria-label'=>array())),
 array(array('id'=>'old'),array('id'=>'new','aria-label'=>'Menu')),
 array(array('id'=>'old'),array('id'=>'')),
 array(array('id'=>99),array('id'=>0)),
 array(array('style'=>' color:red; '),array('style'=>' padding:0; ')),
 array(array('style'=>'padding:0'),array('style'=>'color:blue')),
 array(array('style'=>'color:red'),array('style'=>'color:blue')),
 array(array('class'=>' old shared '),array('class'=>'new shared new')),
 array(array('class'=>0),array('class'=>'zero')),
 array(array('class'=>'🙂 café'),array('class'=>'café Menu')),
 array(array(),array('data-count'=>0,'data-float'=>1.5,'data-empty'=>'','data-text'=>'A & B < C "Q"')),
 array(array(),array('data-bool'=>true,'data-null'=>null,'data-array'=>array('x'))),
 array(array('aria-label'=>'"A" & B'),array('aria-label'=>'')),
 array(array('aria-label'=>1.5),array('aria-label'=>false)),
 array(array('class'=>'a','id'=>'a','aria-label'=>'A'),array('class'=>'b','id'=>'b','aria-label'=>'B','data-extra'=>'C'))
);
$wrapper_control_rows=array();
for($wrapper_control_cycle=0;$wrapper_control_cycle<3;++$wrapper_control_cycle){foreach($wrapper_control_inputs as $wrapper_control_input){
 WP_Block_Supports::$block_to_render=array('blockName'=>'fixture/wrapper','attrs'=>array('support'=>$wrapper_control_input[0]));
 $wrapper_control_before=WP_Block_Supports::$block_to_render;$wrapper_control_extra=$wrapper_control_input[1];$wrapper_control_snapshot=$wrapper_control_extra;
 $wrapper_control_support=WP_Block_Supports::get_instance()->apply_block_supports();
 $wrapper_control_html=get_block_wrapper_attributes($wrapper_control_extra);
 $wrapper_control_rows[]=array($wrapper_control_cycle,$wrapper_control_support,$wrapper_control_html,WP_Block_Supports::$block_to_render===$wrapper_control_before,$wrapper_control_extra===$wrapper_control_snapshot);
}}
WP_Block_Supports::$block_to_render=null;$wrapper_control_rows[]=array('null',get_block_wrapper_attributes(array('aria-label'=>'Menu')));
WP_Block_Supports::$block_to_render=array('blockName'=>'unknown','attrs'=>array());$wrapper_control_rows[]=array('unknown',get_block_wrapper_attributes(array('aria-label'=>'Menu 2')));
echo json_encode($wrapper_control_rows,JSON_THROW_ON_ERROR),"\n";
