#!/usr/bin/env python3
"""Compare a candidate SDK with the pinned compiler without booting WordPress."""

import argparse
import hashlib
import json
import os
from pathlib import Path
import re
import shutil
import subprocess
import sys
import tempfile

ROOT = Path(__file__).resolve().parent.parent


def digest(path):
    h = hashlib.sha256()
    with path.open('rb') as stream:
        for chunk in iter(lambda: stream.read(1048576), b''):
            h.update(chunk)
    return h.hexdigest()


def diagnostics(output):
    result = []
    for line in output.splitlines():
        match = re.match(r'^error\[(.*?):(\d+):(\d+)\]: (.*)$', line)
        if match:
            path, row, column, message = match.groups()
            try:
                path = str(Path(path).resolve().relative_to(ROOT))
            except ValueError:
                path = Path(path).name
            result.append({'path': path, 'line': int(row), 'column': int(column), 'message': message})
        elif line.startswith('error:'):
            result.append({'message': line[6:].strip()})
    return result


def run(command, log, timeout=120, cwd=ROOT):
    with log.open('wb') as stream:
        try:
            process = subprocess.run(command, cwd=cwd, stdout=stream, stderr=subprocess.STDOUT, timeout=timeout)
            code = process.returncode
        except subprocess.TimeoutExpired:
            code = 124
    output = log.read_text(errors='replace')
    errors = diagnostics(output)
    return {'exit_code': code, 'error_count': len(errors), 'errors': errors[:12], 'log': log.name}


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--compiler', required=True, help='Candidate compiler executable')
    parser.add_argument('--baseline', default='elephc', help='Pinned compiler command')
    parser.add_argument('--revision', required=True, help='Candidate compiler source revision')
    parser.add_argument('--sdk-sha256', help='Verified SDK archive checksum')
    parser.add_argument('--output', required=True, type=Path, help='Generated report/log directory')
    args = parser.parse_args()
    candidate = shutil.which(args.compiler)
    baseline = shutil.which(args.baseline)
    if not candidate or not baseline:
        parser.error('Both compiler executables must be available.')
    candidate = str(Path(candidate).resolve())
    output = args.output.resolve()
    output.mkdir(parents=True, exist_ok=True)
    subprocess.run([sys.executable, 'scripts/prepare-elephc.py', '--check'], cwd=ROOT, check=True)
    report = {'schema': 1, 'wordpress_version': '7.1.4', 'php_profile': '8.4', 'wordpress_executed': False, 'candidate_revision': args.revision, 'sdk_archive_sha256': args.sdk_sha256, 'compiler_sha256': digest(Path(candidate)), 'compilers': {}}
    probes = ['goto-control', 'utf8-original', 'shortinit', 'full-wordpress', 'string-span', 'versioned-include']
    for label, compiler in [('pinned', baseline), ('candidate', candidate)]:
        version = subprocess.check_output([compiler, '--version'], cwd=ROOT, text=True).strip()
        checks = {}
        for name in probes:
            checks[name] = run([compiler, '--php-version', '8.4', '--check', f'elephc/probes/{name}.php'], output / f'{label}-{name}.log')
            print(f"{label} {name}: exit {checks[name]['exit_code']}, {checks[name]['error_count']} errors", flush=True)
        report['compilers'][label] = {'version': version, 'checks': checks}
    php = os.environ.get('PHP_BIN', 'php')
    expected = subprocess.check_output([php, 'elephc/probes/goto-control.php'], cwd=ROOT)
    with tempfile.TemporaryDirectory(prefix='.goto-control-', dir=output) as directory:
        stage = Path(directory)
        source = stage / 'goto-control.php'
        shutil.copyfile(ROOT / 'elephc/probes/goto-control.php', source)
        compiled = run([candidate, '--php-version', '8.4', str(source)], output / 'candidate-native-control.log', cwd=stage)
        report['native_control'] = compiled
        if compiled['exit_code'] == 0:
            actual = subprocess.check_output([str(stage / 'goto-control')], cwd=stage, timeout=20)
            report['native_control']['matches_php'] = actual == expected
            report['native_control']['output'] = json.loads(actual)
        else:
            report['native_control']['matches_php'] = False
    (output / 'report.json').write_text(json.dumps(report, indent=2) + '\n')
    print('Report:', output / 'report.json')
    if not report['native_control']['matches_php']:
        raise SystemExit('Candidate native goto control did not match PHP.')
    print('PASS: native goto control matches PHP; original WordPress probes were compile-only.')


if __name__ == '__main__':
    main()
