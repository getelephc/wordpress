#!/usr/bin/env python3
"""Compare complete WordPress component files with their original PHP behavior."""

import argparse
import hashlib
import json
import os
from pathlib import Path
import shutil
import subprocess
import tempfile

ROOT = Path(__file__).resolve().parent.parent
OUT = ROOT / "elephc/build/full-source"
COMPONENTS = {
    "block-wrapper": ("class-wp-block-supports.php",),
    "query-cache": ("class-wp-query.php",),
    "theme-schema": ("class-wp-theme-json.php",),
    "block-context": ("class-wp-block-list.php", "class-wp-block.php"),
    "capabilities": ("capabilities.php",),
    "styles": ("style-engine/class-wp-style-engine.php",),
    "patterns": ("class-wp-block-patterns-registry.php",),
    "hooks": ("plugin.php", "class-wp-hook.php", "class-wp-filter-sentinel.php"),
    "mailer": ("PHPMailer/OAuthTokenProvider.php", "PHPMailer/Exception.php",
               "PHPMailer/SMTP.php", "PHPMailer/PHPMailer.php"),
}


def sha(path):
    return hashlib.sha256(path.read_bytes()).hexdigest()


def run(command, *, timeout=120):
    result = subprocess.run(command, cwd=OUT, capture_output=True, text=True, timeout=timeout)
    if result.returncode:
        raise SystemExit(result.stdout + result.stderr)
    return result


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--compiler", default=os.environ.get("ELEPHC_BIN", "elephc"))
    parser.add_argument("--component", choices=COMPONENTS, required=True)
    args = parser.parse_args()
    compiler = shutil.which(args.compiler)
    if not compiler:
        parser.error("A complete Elephc SDK is required")
    compiler = str(Path(compiler).resolve())
    subprocess.run(["python3", "scripts/prepare-wordpress.py", "--check"], cwd=ROOT, check=True)
    report_path = OUT / f"component-{args.component}-verification.json"
    report_path.unlink(missing_ok=True)
    for name in ("elephc.toml", "elephc.lock"):
        shutil.copyfile(ROOT / "elephc/full-source" / name, OUT / name)
    run([compiler, "native", "install", "--locked"])
    cases = ROOT / f"tests/full-source/{args.component}-cases.php"
    support = ROOT / f"tests/full-source/{args.component}-support.php"
    helper = ""
    if args.component in ("mailer", "query-cache"):
        # Only fixture views are selected; every WordPress class stays complete.
        contract_file = "aot-contracts.php" if args.component == "mailer" else "aot-core-contracts.php"
        contract_name = "wp_aot_require_Throwable" if args.component == "mailer" else "wp_aot_require_wpdb"
        contracts = (ROOT / "elephc/full-source/fixtures" / contract_file).read_text()
        begin = contracts.index("function " + contract_name + "(")
        helper = contracts[begin:contracts.index("\n}\n", begin) + 3]
    with tempfile.TemporaryDirectory(prefix=f".{args.component}-control-", dir=OUT) as directory:
        work = Path(directory)
        for native in (False, True):
            label = "native" if native else "reference"
            platform = "aot-platform.lfc" if native else "aot-php-platform.php"
            source = f"<?php\nrequire __DIR__ . '/../wordpress/{platform}';\n"
            source += "require_once __DIR__ . '/../wordpress/aot-arrays.php';\n"
            if native:
                source += helper
            tree = "../wordpress" if native else "../../../../upstream/wordpress"
            if args.component == "theme-schema":
                source += "define('WP_SCHEMA_ORIGINAL_ROOT', __DIR__ . '/../../../../upstream/wordpress');\n"
            if args.component == "patterns":
                source += f"define('WP_CONTENT_DIR', __DIR__ . '/{tree}/wp-content');\n"
            if support.exists():
                source += support.read_text().removeprefix("<?php")
            # plugin.php loads the other complete hook files through its own includes.
            files = COMPONENTS[args.component][:1] if args.component == "hooks" else COMPONENTS[args.component]
            for name in files:
                source += f"require __DIR__ . '/{tree}/wp-includes/{name}';\n"
            source += f"require __DIR__ . '/../../../../tests/full-source/{args.component}-cases.php';\n"
            (work / f"{label}.php").write_text(source)
        expected = run([os.environ.get("PHP_BIN", "php"), str(work / "reference.php")])
        command = [compiler, "--php-version", "8.4", "--with-regex", "--link", "sodium", str(work / "native.php")]
        if os.environ.get("ELEPHC_IR_OPT") == "off":
            command.insert(1, "--ir-opt=off")
        run(command)
        actual = run([str(work / "native")], timeout=20)
        if expected.stderr or actual.stderr:
            raise SystemExit("Unexpected component diagnostics:\n" + expected.stderr + actual.stderr)
        expected_value, actual_value = json.loads(expected.stdout), json.loads(actual.stdout)
        if json.dumps(actual_value, sort_keys=True) != json.dumps(expected_value, sort_keys=True):
            raise SystemExit(json.dumps({"php": expected_value, "native": actual_value}, indent=2))
        comparisons = sum(len(row) if isinstance(row, list) else 1 for row in actual_value)
        report = {
            "schema": 1, "component": args.component, "groups": len(actual_value),
            "comparisons": comparisons, "matches_original_php": True, "native_stderr_clean": True,
            "complete_wordpress_files": ["wp-includes/" + name for name in COMPONENTS[args.component]],
            "ir_optimization": "off" if os.environ.get("ELEPHC_IR_OPT") == "off" else "on",
            "compiler_sha256": sha(Path(compiler)), "source_manifest_sha256": sha(OUT / "source-manifest.json"),
            "cases_sha256": sha(cases), "support_sha256": sha(support) if support.exists() else None,
            "contract_helper_sha256": hashlib.sha256(helper.encode()).hexdigest() if helper else None,
            "harness_sha256": sha(Path(__file__)),
        }
        if args.component == "hooks":
            report["cursor_cases_sha256"] = sha(ROOT / "tests/full-source/hook-cursor-cases.php")
        if args.component in ("hooks", "theme-schema", "query-cache", "block-wrapper"):
            report["native_xml_capability_enabled"] = True
        if args.component == "theme-schema":
            report["schema_inputs_sha256"] = {
                name: sha(ROOT / "upstream/wordpress" / name)
                for name in ("wp-includes/theme.json", "wp-content/themes/twentytwentyfive/theme.json")
            }
        report_path.write_text(json.dumps(report, indent=2) + "\n")
    print(f"PASS: complete WordPress {args.component} files, {comparisons} comparisons with original PHP", flush=True)


if __name__ == "__main__":
    main()
