#!/usr/bin/env python3
"""Reproduce the complete-source controls without generated WordPress sources."""

import argparse
import json
import os
from pathlib import Path
import shutil
import subprocess
import tempfile

ROOT = Path(__file__).resolve().parent.parent


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--compiler", default=os.environ.get("ELEPHC_BIN", "elephc"))
    parser.add_argument("--native", action="store_true", help="Also build and verify the complete native bootstrap")
    args = parser.parse_args()
    compiler = shutil.which(args.compiler)
    if not compiler:
        parser.error("A complete Elephc SDK is required")
    compiler = str(Path(compiler).resolve())
    files = subprocess.check_output(
        ["git", "ls-files", "--cached", "--others", "--exclude-standard", "-z"], cwd=ROOT
    ).decode().split("\0")
    output = ROOT / "elephc/build/full-source"
    output.mkdir(parents=True, exist_ok=True)
    report_path = output / "clean-room-verification.json"
    report_path.unlink(missing_ok=True)
    with tempfile.TemporaryDirectory(prefix=".clean-room-", dir=output) as directory:
        clone = Path(directory) / "source"
        clone.mkdir()
        for name in files:
            if not name or not (ROOT / name).is_file():
                continue
            target = clone / name
            target.parent.mkdir(parents=True, exist_ok=True)
            shutil.copy2(ROOT / name, target)
        # The archive is a verified source dependency, never a prepared build tree.
        archive = ROOT / "upstream/wordpress-7.1.4.tar.gz"
        if archive.is_file():
            (clone / "upstream").mkdir()
            shutil.copyfile(archive, clone / "upstream" / archive.name)
        subprocess.run(["python3", "scripts/prepare-wordpress.py"], cwd=clone, check=True)
        subprocess.run(["python3", "scripts/prepare-wordpress.py", "--check"], cwd=clone, check=True)
        subprocess.run(["python3", "scripts/test-wordpress-bootstrap.py"], cwd=clone, check=True)
        subprocess.run(["python3", "scripts/test-wordpress-controls.py", "--compiler", compiler], cwd=clone, check=True)
        verified = clone / "elephc/build/full-source"
        if args.native:
            subprocess.run(["python3", "scripts/test-wordpress-linker.py"], cwd=clone, check=True)
            environment = os.environ.copy()
            environment["ELEPHC_BIN"] = compiler
            subprocess.run(["bash", "scripts/build-wordpress.sh"], cwd=clone, env=environment, check=True)
            subprocess.run(["python3", "scripts/test-wordpress-bootstrap.py", "--native", str(verified / "bootstrap")],
                           cwd=clone, check=True)
        report = {"schema": 1,
                  "bootstrap": json.loads((verified / "bootstrap-verification.json").read_text()),
                  "controls": json.loads((verified / "controls.json").read_text())}
        if args.native:
            link = json.loads((verified / "bootstrap-link.json").read_text())
            if (link["linked"] is not True or link["function_folding"] is not False
                    or link["allocated_contents_sha256"] != link["placed_allocated_contents_sha256"]
                    or link["source_manifest_sha256"] != report["bootstrap"]["source_manifest_sha256"]
                    or link["compiler_sha256"] != report["controls"]["compiler_sha256"]):
                raise SystemExit("Source-only native linker provenance does not match the verified build")
            report["linker"] = {key: value for key, value in link.items()
                                if key not in ("section_renames", "common_symbols")}
        report_path.write_text(json.dumps(report, indent=2) + "\n")
    scope = "complete native bootstrap and controls" if args.native else "bootstrap references and native controls"
    print(f"PASS: source-only {scope}; export removed")


if __name__ == "__main__":
    main()
