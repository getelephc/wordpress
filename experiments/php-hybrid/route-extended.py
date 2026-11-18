#!/usr/bin/env python3
"""Inject real native delegation into an isolated copy of original WordPress."""
from pathlib import Path
import json,hashlib,importlib.util,shutil,sys
ROOT=Path(__file__).resolve().parents[2];OUT=ROOT/'elephc/build/php-hybrid';SITE=OUT/'wordpress'
spec=importlib.util.spec_from_file_location('source_tools',Path(__file__).with_name('source-tools.py'))
tools=importlib.util.module_from_spec(spec);spec.loader.exec_module(tools)
routes=json.loads((OUT/'extended-inventory.json').read_text())['routes']
files=['formatting.php','blocks.php','http.php','kses.php','functions.php','rest-api.php','cache.php','load.php','shortcodes.php','class-wp-error.php','class-wp-http-response.php','rest-api/class-wp-rest-response.php','rest-api/class-wp-rest-request.php','class-wp-list-util.php','class-wp-object-cache.php']
if '--original' in sys.argv:
    for filename in files:
        relative='wp-includes/'+filename
        shutil.copyfile(ROOT/'upstream/wordpress'/relative,SITE/relative)
        assert (SITE/relative).read_bytes()==(ROOT/'upstream/wordpress'/relative).read_bytes()
    print('Restored pristine original core files for PHP control')
    raise SystemExit(0)
manifest=[]
for filename in files:
    relative='wp-includes/'+filename
    path=ROOT/'upstream/wordpress'/relative
    raw=path.read_bytes();entries=tools.functions(path);selected=[]
    cls={'class-wp-error.php':'WP_Error','class-wp-http-response.php':'WP_HTTP_Response','rest-api/class-wp-rest-response.php':'WP_REST_Response','rest-api/class-wp-rest-request.php':'WP_REST_Request'}.get(filename)
    for name,entry in entries.items():
        route=cls+'::'+name if cls else name
        list_method=filename=='class-wp-list-util.php' and name in ['filter','pluck','sort']
        cache_method=filename=='class-wp-object-cache.php' and 'wp_cache_'+name in routes
        if route not in routes and not list_method and not cache_method:continue
        params=entry['params']
        import re
        names=re.findall(r'\$(\w+)',params)
        call='['+','.join('$'+n for n in names)+']'
        if cls:
            result='return;' if entry['return_type']==': void' else "return $ep['value'];"
            body="$ep=hybrid_object_try('"+cls+"','"+name+"',$this,"+call+"); if ($ep['handled']) { "+result+" }"
        elif list_method:
            call='[$this->output'+(','+','.join('$'+n for n in names) if names else '')+']'
            body="$ep=hybrid_list_call('wp_list_"+name+"',"+call+"); if ($ep['handled']) { $this->output=$ep['value']; return $this->output; }"
        elif name.startswith('wp_cache_') or cache_method:
            native_name='wp_cache_'+name if cache_method else name
            if native_name=='wp_cache_get':
                call='[$key,$group,$force]'
                body="$ep=hybrid_cache_call('wp_cache_get',"+call+"); $found=$ep['found']; return $ep['data'];"
            elif native_name in ['wp_cache_init','wp_cache_add_global_groups','wp_cache_add_non_persistent_groups','wp_cache_switch_to_blog']:
                body="hybrid_cache_call('"+native_name+"',"+call+"); return;"
            else:body="return hybrid_cache_call('"+native_name+"',"+call+");"
        else:body="$ep=hybrid_try_call('"+name+"',"+call+"); if ($ep['handled']) { return $ep['value']; }"
        condition="defined('HYBRID_NATIVE') && HYBRID_NATIVE"+(' && $this === ($GLOBALS[\'wp_object_cache\']??null)' if cache_method else '')
        injection="\n\tif ("+condition+") { "+body+" }\n"
        selected.append((entry['body_start'],injection.encode(),'WP_Object_Cache::'+name if cache_method else route if not list_method else 'WP_List_Util::'+name))
    for offset,code,name in sorted(selected,reverse=True):raw=raw[:offset]+code+raw[offset:]
    (SITE/relative).write_bytes(raw)
    manifest.append({'path':relative,'functions':[n for _,_,n in selected],'original_sha256':hashlib.sha256(path.read_bytes()).hexdigest(),'hybrid_sha256':hashlib.sha256(raw).hexdigest()})
shutil.copyfile(ROOT/'experiments/php-hybrid/host-bridge.php',SITE/'wp-native-bridge.php')
shutil.copyfile(ROOT/'experiments/php-hybrid/host-objects.php',SITE/'host-objects.php')
config=SITE/'wp-config.php';text=config.read_text()
line="require_once __DIR__ . '/wp-native-bridge.php';\n"
if line not in text:text=text.replace("require_once ABSPATH . 'wp-settings.php';",line+"require_once ABSPATH . 'wp-settings.php';")
config.write_text(text)
(OUT/'extended-routing.json').write_text(json.dumps(manifest,indent=2)+'\n')
print('Routed',sum(len(e['functions']) for e in manifest),'core functions')
