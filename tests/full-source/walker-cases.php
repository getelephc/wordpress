<?php
class SourceWalkerProbe extends Walker {
    public function __construct() {$this->db_fields=array('parent'=>'parent','id'=>'id');}
    public function start_lvl(mixed &$output,mixed $depth=0,mixed $args=array()): mixed {$output.='['.(string)$depth;return null;}
    public function end_lvl(mixed &$output,mixed $depth=0,mixed $args=array()): mixed {$output.=']'.(string)$depth;return null;}
    public function start_el(mixed &$output,mixed $node,mixed $depth=0,mixed $args=array(),mixed $current=0): mixed {$output.='S'.(string)$node->id.':'.(string)$depth.':'.(string)$current.':'.(string)($args['label']??'').';';return null;}
    public function end_el(mixed &$output,mixed $node,mixed $depth=0,mixed $args=array()): mixed {$output.='E'.(string)$node->id.';';return null;}
}
function walker_nodes(mixed $records): array {
    $result=array();
    foreach ($records as $record) {$node=new stdClass();$node->id=$record[0];$node->parent=$record[1];$result[]=$node;}
    return $result;
}
$results=array();
$forests=array(array(),array(array(1,0)),array(array(1,0),array(2,1),array(3,1),array(4,2),array(5,0)),array(array(7,2),array(8,7),array(9,0)));
foreach ($forests as $forest) foreach (array(-1,0,1,2,3) as $depth) {
    $walker=new SourceWalkerProbe();$nodes=walker_nodes($forest);
    $results[]=array($walker->walk($nodes,$depth,array('label'=>'x'),99),$walker->has_children);
    $results[]=array($walker->get_number_of_root_elements($nodes),$walker->max_pages);
}
echo json_encode($results),"\n";
