<?php
function sidebar_reference_cases(): array {
    global $_wp_sidebars_widgets;
    $cases=array();
    $initials=array(null,false,0,'',array(),array('sidebar'=>array()),
        array('sidebar'=>array(3=>'native',8=>'other','last'=>'native'),'second'=>array('native')),'scalar-cache');
    $fallbacks=array(array(),array('sidebar'=>array(4=>'native',9=>'native'),'meta'=>false),array('sidebar'=>array('other')));
    foreach($initials as $initial) foreach($fallbacks as $fallback) {
        foreach(array(false,true) as $admin) foreach(array('native','missing') as $base) {
            $_wp_sidebars_widgets=sidebar_reference_seed($initial);
            $before=$_wp_sidebars_widgets;
            $converted=sidebar_reference_call($admin,$fallback,$base);
            $cases[]=array($admin,$base,$initial,$fallback,$converted,$before,$_wp_sidebars_widgets);
        }
    }
    return $cases;
}
