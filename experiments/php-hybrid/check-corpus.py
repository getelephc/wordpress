#!/usr/bin/env python3
import json,subprocess
from pathlib import Path
ROOT=Path(__file__).resolve().parents[2];OUT=ROOT/'elephc/build/php-hybrid'
for mode in ['php','hybrid']:
    subprocess.run(['python3',str(ROOT/'experiments/php-hybrid/route-extended.py')]+(['--original'] if mode=='php' else []),cwd=ROOT,check=True)
    cmd=['php']+(['-d','extension='+str(OUT/'wp_elephc_extended.so')] if mode=='hybrid' else [])+[str(ROOT/'experiments/php-hybrid/corpus.php'),mode]
    subprocess.run(cmd,cwd=ROOT,check=True)
a=json.loads((OUT/'corpus-php.json').read_text())['results'];native=json.loads((OUT/'corpus-hybrid.json').read_text());b=native['results']
failures=[]
for group,cases in a.items():
    for name,value in cases.items():
        if value!=b[group][name]:failures.append((group,name))
if failures:raise SystemExit('Mismatches: '+str(failures[:25]))
print('Equivalent corpus cases:',sum(map(len,a.values())))
print('Native routes actually called:',len(native['native']['calls']))
