#!/usr/bin/env python3
"""Replay captured native calls under Elephc's exact function profiler."""
import argparse,os,subprocess
from pathlib import Path
ROOT=Path(__file__).resolve().parents[2];OUT=ROOT/'elephc/build/php-hybrid'
p=argparse.ArgumentParser(description=__doc__);p.add_argument('label');p.add_argument('--source',default='wp_extended.php');p.add_argument('--inputs',default='profiling/rest-inputs.jsonl');p.add_argument('--assert',dest='assertions',action='append',default=[]);a=p.parse_args()
source=OUT/('profile-'+a.label+'.php')
source.write_text((OUT/a.source).read_text()+'''\nep_request_reset();
$lines=explode("\\n",(string)file_get_contents((string)getenv('HYBRID_REPLAY_INPUTS')));
$count=0; $bytes=0;
foreach($lines as $line) { if($line==='')continue; $result=ep_dispatch((string)$line); ++$count; $bytes+=strlen($result); }
echo $count,' ',$bytes,"\\n";
''')
folder=OUT/'profiling';folder.mkdir(exist_ok=True)
with (folder/(a.label+'-build.log')).open('w') as log:
 subprocess.run(['elephc','--php-version','8.5','--heap-size=33554432','--with-monitoring','--keep-symbols',source.name],cwd=OUT,stdout=log,stderr=subprocess.STDOUT,check=True)
env=dict(os.environ,HYBRID_REPLAY_INPUTS=str(OUT/a.inputs))
with (folder/(a.label+'-monitor.log')).open('w') as log:
 checks=[part for assertion in a.assertions for part in ['--assert',assertion]]
 subprocess.run(['elephc','monitor','./'+source.stem,'--save','profiling/'+a.label+'.json','--html','profiling/'+a.label+'-callgraph.html','--out','profiling/'+a.label+'.speedscope.json',*checks],cwd=OUT,env=env,stdout=log,stderr=subprocess.STDOUT,check=True)
print((folder/(a.label+'-monitor.log')).read_text()[:10000])
