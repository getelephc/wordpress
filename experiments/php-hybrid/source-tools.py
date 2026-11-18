"""Mechanical experiment-only lowering of private native method calls."""
import json,re,subprocess
from pathlib import Path
INDEX=Path(__file__).with_name('source-index.php')
def functions(path):
    return json.loads(subprocess.check_output(['php',str(INDEX),str(path)]))
def flatten(path, class_name, prefix, returns=None):
    raw=Path(path).read_bytes()
    entries=functions(path)
    returns=returns or {}
    fragments=[]
    class_source=raw
    for name,entry in entries.items():
        if name=='__construct':continue
        body=raw[entry['body_start']:entry['body_end']].decode()
        if prefix=='ep_list_': body=body.replace('$this->sort_callback(...)','$state')
        for other in entries:
            if other=='__construct':continue
            pattern=r'(\$\w+)->'+re.escape(other)+r'\(\s*'
            body=re.sub(pattern,lambda m:prefix+other+'('+('$state' if m[1]=='$this' else m[1])+('' if body[m.end():].startswith(')') else ', '),body)
        body=body.replace('$this','$state')
        for other in entries:
            body=re.sub(r'static::'+re.escape(other)+r'\(\s*',lambda m:prefix+other+'($state'+('' if body[m.end():].startswith(')') else ', '),body)
        params=entry['params'].strip()
        ret=entry['return_type'] or ': '+returns.get(name,'mixed')
        fragments.append('function '+prefix+name+'('+class_name+' $state'+(', '+params if params else '')+')'+ret+' {'+body+'}')
    for name,entry in reversed(list(entries.items())):
        if name=='__construct':continue
        class_source=class_source[:entry['line_start']]+class_source[entry['end']:]
    class_text=class_source.decode().removeprefix('<?php')
    class_text=re.sub(r'(?m)^(\s*)(private|protected)\s+',r'\1public ',class_text)
    # Constructors may call another method. Internal callers stay direct.
    for name in entries:
        if name=='__construct':continue
        pattern=r'\$this->'+re.escape(name)+r'\(\s*'
        class_text=re.sub(pattern,lambda m:prefix+name+'($this'+('' if class_text[m.end():].startswith(')') else ', '),class_text)
    return class_text,fragments
