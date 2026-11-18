#!/usr/bin/env python3
"""Compare complete original and patched WordPress bootstrap with the SQL fixture."""

import argparse
from contextlib import ExitStack
import hashlib
import json
import os
from pathlib import Path
import subprocess
import shutil
import tempfile

ROOT = Path(__file__).resolve().parent.parent
OUT = ROOT / "elephc/build/full-source"


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--native", type=Path, help="Also require the compiled bootstrap executable to match")
    args = parser.parse_args()
    (OUT / "bootstrap-verification.json").unlink(missing_ok=True)
    subprocess.run(["python3", "scripts/prepare-wordpress.py", "--check"], cwd=ROOT, check=True)
    template = (ROOT / "elephc/full-source/fixtures/aot-probe.php").read_text()
    report = {"schema": 1, "wordpress_version": "7.1.4", "source_manifest_sha256": hashlib.sha256((OUT / "source-manifest.json").read_bytes()).hexdigest(),
              "php": {}, "native_verified": False}
    expected = None
    expected_tree = None
    expected_templates = None
    expected_visitors = None
    with ExitStack() as stack:
        directory = stack.enter_context(tempfile.TemporaryDirectory(prefix=".php-reference-", dir=OUT))
        reference = Path(directory) / "wordpress"
        shutil.copytree(ROOT / "upstream/wordpress", reference)
        # Original core stays byte-identical; only local configuration and drop-ins are added.
        for name in ("wp-config.php", "wp-content/db.php", "wp-content/aot-data.php"):
            shutil.copyfile(ROOT / "elephc/full-source/fixtures" / name, reference / name)
        for label, base in (("original", f"/{reference.parent.name}/wordpress/"), ("patched", "/wordpress/")):
            source = template.replace("require __DIR__ . '/aot-platform.lfc';", "")
            if label == "patched":
                source = source.replace("<?php", "<?php\nrequire __DIR__ . '/../../../upstream/wordpress/wp-includes/sodium_compat/autoload.php';", 1)
            source = source.replace("define( 'ABSPATH', __DIR__ . '/' );", f"define( 'ABSPATH', __DIR__ . '{base}' );")
            source = source.replace("require __DIR__ . '/wp-load.php';", "require ABSPATH . 'wp-load.php';")
            source += "\necho json_encode(require __DIR__ . '/../../../tests/full-source/setting-tree-cases.php'), \"\\n\";\n"
            source += "\necho json_encode(require __DIR__ . '/../../../tests/full-source/template-theme-cases.php'), \"\\n\";\n"
            source += "\necho json_encode(require __DIR__ . '/../../../tests/full-source/block-visitor-cases.php'), \"\\n\";\n"
            driver = OUT / f"bootstrap-{label}.php"
            driver.write_text(source)
            stack.callback(driver.unlink, missing_ok=True)
            result = subprocess.run([os.environ.get("PHP_BIN", "php"), str(driver)], cwd=ROOT,
                                    capture_output=True, text=True, timeout=30, check=True)
            lines = result.stdout.splitlines()
            state = json.loads(lines[0])
            tree = json.loads(lines[1])
            templates = json.loads(lines[2])
            visitors = json.loads(lines[3])
            if state["booted"] is not True or type(state["init"]) is not int or state["init"] != 1 or state["registered_blocks"] < 1:
                raise SystemExit(f"Incomplete WordPress initialization: {state}")
            if expected is not None and json.dumps(expected, sort_keys=True) != json.dumps(state, sort_keys=True):
                raise SystemExit("The patched WordPress bootstrap differs from the complete original PHP bootstrap")
            expected = state
            if expected_tree is not None and json.dumps(expected_tree, sort_keys=True) != json.dumps(tree, sort_keys=True):
                raise SystemExit("Patched setting tree operations differ from original WordPress PHP")
            expected_tree = tree
            if expected_templates is not None and json.dumps(expected_templates, sort_keys=True) != json.dumps(templates, sort_keys=True):
                raise SystemExit("Patched deprecated template transformations differ from original WordPress PHP")
            expected_templates = templates
            if expected_visitors is not None and json.dumps(expected_visitors, sort_keys=True) != json.dumps(visitors, sort_keys=True):
                raise SystemExit("Patched block visitor serialization differs from original WordPress PHP")
            expected_visitors = visitors
            report["php"][label] = state
            print(f"PASS: complete {label} WordPress bootstrap, {state['registered_blocks']} registered blocks", flush=True)
    report["setting_tree_php"] = {"cases": len(expected_tree), "matches_original": True}
    print(f"PASS: {len(expected_tree)} setting tree results match original PHP", flush=True)
    report["template_theme_php"] = {"cases": len(expected_templates), "matches_original": True}
    print(f"PASS: {len(expected_templates)} template transformations and callback traces match original PHP", flush=True)
    report["block_visitors_php"] = {"cases": len(expected_visitors), "matches_original": True}
    print(f"PASS: {len(expected_visitors)} block visitor results and mutation traces match original PHP", flush=True)
    if args.native:
        result = subprocess.run([str(args.native.resolve())], cwd=ROOT, capture_output=True,
                                text=True, timeout=30, check=True)
        state = json.loads(result.stdout)
        if json.dumps(state, sort_keys=True) != json.dumps(expected, sort_keys=True):
            raise SystemExit("The native WordPress bootstrap differs from original PHP")
        report["native_verified"] = True
        report["native"] = state
        report["native_sha256"] = hashlib.sha256(args.native.read_bytes()).hexdigest()
        print("PASS: native WordPress bootstrap matches original PHP", flush=True)
    (OUT / "bootstrap-verification.json").write_text(json.dumps(report, indent=2) + "\n")


if __name__ == "__main__":
    main()
