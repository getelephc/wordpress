<?php
$results=array();$post=json_decode('{"ID":901,"post_type":"post","post_status":"draft","post_author":99}');
foreach(array('before','after')as$phase){if($phase==='after')$capability_type->cap->edit_post='edit_fixture_post';foreach(array('edit_post','read_post','delete_post')as$cap){$capability_trace=array();$result=map_meta_cap($cap,99,$post);$results[]=array($phase,$cap,$result,$capability_trace);}}
foreach(array('promote_user','add_users','edit_users','remove_user','unfiltered_html','edit_posts','custom_capability')as$cap){$capability_trace=array();$result=map_meta_cap($cap,99);$results[]=array('primitive',$cap,$result,$capability_trace);}
echo json_encode($results,JSON_THROW_ON_ERROR),"\n";
