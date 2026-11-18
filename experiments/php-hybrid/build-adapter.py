#!/usr/bin/env python3
"""Build the small real-Zend extension against the generated Elephc ABI."""
from pathlib import Path
import argparse
import hashlib
import json
import platform
import shlex
import shutil
import subprocess
ROOT = Path(__file__).resolve().parents[2]
OUT = ROOT / 'elephc/build/php-hybrid'
parser=argparse.ArgumentParser(description=__doc__)
parser.add_argument('--manifest-only',action='store_true')
args=parser.parse_args()
library = 'wp_extended'
flags = shlex.split(subprocess.check_output(['php-config', '--includes'], text=True))
command = ['cc', '-O2', '-shared', '-fPIC', '-Wall', '-Wextra', '-Wno-unused-parameter', *flags, '-I'+str(OUT)]
command += [str(ROOT/'experiments/php-hybrid/adapter.c'), str(ROOT/'experiments/php-hybrid/abi-guard.S'), '-L'+str(OUT), '-l'+library, '-Wl,-rpath,$ORIGIN', '-o', str(OUT/'wp_elephc_extended.so')]
if not args.manifest_only:
    subprocess.run(command, check=True)
    print('Zend adapter links', library)
compiler_path=shutil.which('elephc')
if compiler_path is None:raise SystemExit('Elephc not found. Install the complete SDK and add elephc to PATH.')
compiler=Path(compiler_path).resolve()
files=[compiler,OUT/(library+'.php'),OUT/('lib'+library+'.so'),Path(command[-1]),ROOT/'experiments/php-hybrid/adapter.c',ROOT/'experiments/php-hybrid/abi-guard.S']
files += [ROOT/'experiments/php-hybrid/fast-adapter.h',ROOT/'experiments/php-hybrid/fast-exports.php']
manifest={'compiler':subprocess.check_output(['elephc','--version'],text=True).strip(),'compiler_path':str(compiler),'host':platform.platform(),'php':subprocess.check_output(['php','-v'],text=True).splitlines()[0],'php_config_version':subprocess.check_output(['php-config','--version'],text=True).strip(),'elephc_flags':['--php-version','8.5','--emit','cdylib','--heap-size='+'33554432'],'c_command':command,'files':{str(path.relative_to(ROOT) if path.is_relative_to(ROOT) else path):{'sha256':hashlib.sha256(path.read_bytes()).hexdigest(),'bytes':path.stat().st_size} for path in files}}
(OUT/'build-manifest.json').write_text(json.dumps(manifest,indent=2)+'\n')
