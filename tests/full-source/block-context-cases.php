<?php
$rows=array();
foreach(array('{}','{"showSubmenuIcon":false}','{"showSubmenuIcon":true,"style":{"spacing":{"blockGap":"1rem"}}}','{"showSubmenuIcon":0,"submenuVisibility":"","style":null}','{"showSubmenuIcon":"é汉","maxNestingLevel":4}')as$json){
 $registry=new WP_Block_Type_Registry();$parent_type=new WP_Block_Type();$child_type=new WP_Block_Type();
 $parent_type->provides_context=json_decode('{"showSubmenuIcon":"showSubmenuIcon","submenuVisibility":"submenuVisibility","style":"style","maxNestingLevel":"maxNestingLevel"}',true);
 $parent_type->defaults=json_decode('{"showSubmenuIcon":true,"submenuVisibility":"hover","maxNestingLevel":5}',true);
 $child_type->uses_context=array('showSubmenuIcon','submenuVisibility','style','maxNestingLevel','existing');$registry->types['fixture/parent']=$parent_type;$registry->types['fixture/child']=$child_type;
 $data=json_decode('{"blockName":"fixture/parent","attrs":'.$json.',"innerBlocks":[{"blockName":"fixture/child","attrs":{},"innerBlocks":[],"innerHTML":"","innerContent":[]}],"innerHTML":"<div></div>","innerContent":["<div>",null,"</div>"]}',true);
 $before=$data;$context=json_decode('{"existing":"parent","showSubmenuIcon":"inherited"}',true);$block=new WP_Block($data,$context,$registry);$child=$block->inner_blocks[0];
 $rows[]=array($json,$block->attributes,$child->context,$parent_type->prepares,$data,$before,$context,$block->inner_html,$block->inner_content);
 $block->refresh_parsed_block_dependents();$again=$block->inner_blocks[0];$rows[]=array('refresh',$again->context,$child->context,$parent_type->prepares);
}
echo json_encode($rows,JSON_THROW_ON_ERROR),"\n";
