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
        report = {"schema": 1,
                  "bootstrap": json.loads((verified / "bootstrap-verification.json").read_text()),
                  "controls": json.loads((verified / "controls.json").read_text())}
        report_path.write_text(json.dumps(report, indent=2) + "\n")
    print("PASS: source-only bootstrap references and native controls; export removed")


if __name__ == "__main__":
    main()
