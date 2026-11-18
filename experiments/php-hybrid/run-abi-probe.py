#!/usr/bin/env python3
from pathlib import Path
import json, subprocess
root=Path(__file__).resolve().parents[2]
out=root/'elephc/build/php-hybrid'
subprocess.run(['cc','-O2','-I'+str(out),str(root/'experiments/php-hybrid/abi-probe.c'),str(root/'experiments/php-hybrid/abi-probe.S'),'-L'+str(out),'-lwp_extended','-Wl,-rpath,$ORIGIN','-o',str(out/'abi-probe')],check=True)
results=[]
for fixture in json.loads((root/'tests/render-fixtures.json').read_text()):
    if fixture['mode']!='autop': continue
    result=json.loads(subprocess.check_output([str(out/'abi-probe'),fixture['input']],text=True))
    results.append({'fixture':fixture['name'],**result})
for value in [[], [{'blockName':'core/paragraph','attrs':{},'innerContent':['<p>Hello</p>'],'innerBlocks':[],'innerHTML':'<p>Hello</p>'}], [{'blockName':'core/test','attrs':{'x':'test'},'innerContent':[],'innerBlocks':[],'innerHTML':''}]]:
    result=json.loads(subprocess.check_output([str(out/'abi-probe'),'serialize',json.dumps(value)],text=True))
    results.append({'fixture':'serializer-'+str(len(value)),**result})
(out/'abi-probe.json').write_text(json.dumps(results,indent=2)+'\n')
print(json.dumps([r for r in results if r['expected_r14']!=r['observed_r14']],indent=2))
