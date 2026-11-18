#!/usr/bin/env python3
"""Download, patch, compile and test from a source-only temporary export."""

from pathlib import Path
import shutil
import subprocess
import sys
import tempfile


ROOT = Path(__file__).resolve().parent.parent


def main():
    build = ROOT / "elephc/build"
    build.mkdir(parents=True, exist_ok=True)
    with tempfile.TemporaryDirectory(prefix=".clean-room-", dir=build) as directory:
        stage = Path(directory)
        for name in ("scripts", "tests", "patches"):
            shutil.copytree(ROOT / name, stage / name, ignore=shutil.ignore_patterns("__pycache__"))
        (stage / "elephc/runtime").mkdir(parents=True)
        shutil.copyfile(ROOT / ".elephc-version", stage / ".elephc-version")
        shutil.copyfile(ROOT / "elephc/upstream.json", stage / "elephc/upstream.json")
        for path in (ROOT / "elephc/runtime").iterdir():
            if path.suffix in (".php", ".toml", ".lock"):
                shutil.copyfile(path, stage / "elephc/runtime" / path.name)
        subprocess.run(["bash", "scripts/build-elephc.sh"], cwd=stage, check=True)
        subprocess.run([sys.executable, "scripts/prepare-elephc.py", "--check"], cwd=stage, check=True)
        subprocess.run([sys.executable, "scripts/test-elephc.py"], cwd=stage, check=True)
    print("PASS: source-only reproduction; temporary export removed.")


if __name__ == "__main__":
    main()
