#!/usr/bin/env python3
"""Alternate real WordPress page requests and native boundary microbenchmarks."""
import argparse
import hashlib
import http.client
import json
import os
from pathlib import Path
import shutil
import socket
import statistics
import subprocess
import time

ROOT = Path(__file__).resolve().parents[2]
OUT = ROOT / 'elephc/build/php-hybrid'
PHP = shutil.which('php')
BASE = [PHP, '-d', 'opcache.enable_cli=1', '-d', 'opcache.jit=disable', '-d', 'opcache.jit_buffer_size=0', '-d', 'opcache.validate_timestamps=0']
EXT = OUT / 'wp_elephc_extended.so'
MODES = ['php', 'loaded-off', 'hybrid']

def php_command(mode):
    return BASE + (['-d', 'extension='+str(EXT)] if mode != 'php' else [])

def install_mode(mode):
    subprocess.run(['python3',str(ROOT/'experiments/php-hybrid/route-extended.py')]+([] if mode=='hybrid' else ['--original']),cwd=ROOT,check=True)
    source=ROOT/'experiments/php-hybrid'
    shutil.copyfile(source/'host-bridge.php',OUT/'wordpress/wp-native-bridge.php')
    shutil.copyfile(source/'host-objects.php',OUT/'wordpress/host-objects.php')

def request(path, port):
    conn = http.client.HTTPConnection('127.0.0.1', port, timeout=20)
    start = time.perf_counter_ns()
    try:
        conn.request('GET', path)
        response = conn.getresponse()
        body = response.read()
        elapsed = (time.perf_counter_ns()-start)/1e6
        return response.status, dict(response.getheaders()), body, elapsed
    finally:
        conn.close()

def summary(values):
    return {'median':statistics.median(values), 'min':min(values), 'max':max(values), 'p25':statistics.quantiles(values,n=4)[0], 'p75':statistics.quantiles(values,n=4)[2], 'samples':len(values)}

def main():
    parser=argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--rounds', type=int, default=3)
    parser.add_argument('--requests', type=int, default=12)
    parser.add_argument('--port', type=int, default=9000)
    args=parser.parse_args()
    with socket.socket() as check:
        check.bind(('127.0.0.1',args.port))
    ids=json.loads((OUT/'posts.json').read_text())
    paths={name:'/?p='+str(ident) for name,ident in ids.items()}
    paths['home']='/'
    paths['rest-posts']='/?rest_route=/wp/v2/posts&per_page=4&_fields=id,slug,content,title'
    paths['rest-one']='/?rest_route=/wp/v2/posts/'+str(ids['blocks-large'])+'&_fields=id,slug,content,title'
    raw={mode:{name:[] for name in paths} for mode in MODES}
    expected={}
    proof={}
    readiness={}
    for round_index in range(args.rounds):
        order=MODES[round_index%3:]+MODES[:round_index%3]
        for mode in order:
            install_mode(mode)
            env=dict(os.environ, HYBRID_MODE=mode)
            log_path=OUT/f'http-{round_index}-{mode}.log'
            with log_path.open('wb') as log:
                command=php_command(mode)+['-S',f'127.0.0.1:{args.port}','-t',str(OUT/'wordpress'),str(ROOT/'experiments/php-hybrid/router.php')]
                process=subprocess.Popen(command,cwd=ROOT,env=env,stdout=log,stderr=subprocess.STDOUT)
                try:
                    for _ in range(100):
                        if process.poll() is not None: raise RuntimeError(log_path.read_text())
                        try:
                            status,headers,body,_=request('/__hybrid_ready',args.port)
                            if status==200: break
                        except OSError: time.sleep(.05)
                    else: raise RuntimeError('WordPress server did not start')
                    readiness[mode]=json.loads(body)
                    assert readiness[mode]['extension']==(mode!='php')
                    for _ in range(2):
                        for path in paths.values(): request(path,args.port)
                    for repetition in range(args.requests):
                        for name,path in paths.items():
                            status,headers,body,client_ms=request(path,args.port)
                            assert status==200, (mode,name,status,body[:400])
                            digest=hashlib.sha256(body).hexdigest()
                            if name not in expected:
                                expected[name]=digest
                                folder=OUT/'html'; folder.mkdir(exist_ok=True)
                                (folder/(name+'.html')).write_bytes(body)
                            if digest!=expected[name]:
                                (OUT/f'mismatch-{name}-{mode}.html').write_bytes(body)
                                raise AssertionError(('Full HTML output differs',name,mode,expected[name],digest))
                            stats=json.loads(headers['X-Hybrid-Stats'])
                            assert 'profile' not in stats,'Disable HYBRID_PROFILE for performance measurements'
                            if mode!='hybrid': assert not any(stats.get(key,0) for key in ['parser','autop','serialize','identity'])
                            elif not name.startswith('rest-'):
                                assert stats['serialize'] > 0, (name, stats)
                                if name.startswith('classic') or name=='home': assert stats['autop'] > 0, (name, stats)
                            raw[mode][name].append({'server_ms':float(headers['X-Hybrid-Render-Ms']), 'client_ms':client_ms,'php_peak_bytes':int(headers['X-Hybrid-Peak-Bytes']), 'native':stats,'fallbacks':json.loads(headers.get('X-Hybrid-Fallbacks','{}'))})
                            proof.setdefault(mode,{})[name]=stats
                    print(f'HTTP round {round_index+1}: {mode}, full response hashes match; '+str({name:sum((stats.get('calls') or {}).values()) for name,stats in proof[mode].items()}),flush=True)
                finally:
                    process.terminate()
                    try: process.wait(timeout=5)
                    except subprocess.TimeoutExpired: process.kill(); process.wait()
    subprocess.run(['python3',str(ROOT/'experiments/php-hybrid/route-extended.py')],cwd=ROOT,check=True)
    page_summary={name:{} for name in paths}
    for name in paths:
        for mode in MODES:
            page_summary[name][mode]={metric:summary([sample[metric] for sample in raw[mode][name]]) for metric in ['server_ms','client_ms']}
        a=page_summary[name]['php']['server_ms']['median']
        b=page_summary[name]['hybrid']['server_ms']['median']
        page_summary[name]['php_over_hybrid']=a/b
        page_summary[name]['hybrid_change_percent']=(b/a-1)*100
    micro_raw={}
    for kind,loops in [('autop-small',300),('autop-large',100),('serialize-small',1000),('serialize-large',100),('identity',3000)]:
        micro_raw[kind]={mode:[] for mode in MODES}
        output_hash=None
        for round_index in range(args.rounds):
            order=MODES[round_index%3:]+MODES[:round_index%3]
            for mode in order:
                install_mode(mode)
                command=php_command(mode)+[str(ROOT/'experiments/php-hybrid/micro.php'),mode,kind,str(loops)]
                start=time.perf_counter_ns()
                result=subprocess.run(command,cwd=ROOT,capture_output=True,timeout=60,check=True)
                assert not result.stderr, result.stderr.decode(errors='replace')
                sample=json.loads(result.stdout)
                sample['process_wall_ms']=(time.perf_counter_ns()-start)/1e6
                if output_hash is None: output_hash=sample['sha256']
                assert output_hash==sample['sha256'],(kind,mode,sample)
                micro_raw[kind][mode].append(sample)
        print('Micro:',kind, {mode:round(statistics.median(s['ns_per_call'] for s in micro_raw[kind][mode])/1000,2) for mode in MODES},'us/call',flush=True)
    micro_summary={kind:{mode:summary([s['ns_per_call']/1000 for s in samples]) for mode,samples in modes.items()} for kind,modes in micro_raw.items()}
    report={'schema':2,'generated_at':time.strftime('%Y-%m-%dT%H:%M:%SZ',time.gmtime()),'wordpress':'7.1.4','compiler':subprocess.check_output(['elephc','--version'],text=True).strip(),'php':readiness,'sqlite_plugin':'3.1.3','theme':'twentytwentyfive','rounds':args.rounds,'requests_per_case_per_round':args.requests,'opcache_enabled':True,'jit_enabled':False,'concurrency':1,'native_library_heap_bytes':33554432,'native_components':json.loads((OUT/'extended-inventory.json').read_text())['routes'],'parser_native':True,'reference_core':'pristine upstream files; no injected delegation guards','extension':str(EXT.relative_to(ROOT)),'output_sha256':expected,'native_call_proof':proof,'pages':page_summary,'micro_us_per_call':micro_summary,'raw_pages':raw,'raw_micro':micro_raw,'method':'Real WordPress front controller and SQLite, identical site/data/PHP settings; alternating modes and warmed OPcache. Full HTML, REST JSON and micro result hashes must match. Custom Zend adapter calls Elephc cdylib in process; serializer array transport uses JSON. Single NTS worker only.'}
    install_mode('hybrid')
    output=OUT/'results.json'
    output.write_text(json.dumps(report,indent=2)+'\n')
    print('Saved',output,flush=True)

if __name__=='__main__': main()
