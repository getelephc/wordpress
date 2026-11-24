#!/usr/bin/env python3
"""Materialize the complete pinned WordPress tree and full-file AOT patches."""

import argparse
import hashlib
import json
from pathlib import Path
import shutil
import subprocess
import tarfile
import tempfile
import urllib.request

ROOT = Path(__file__).resolve().parent.parent
OUT = ROOT / "elephc/build/full-source"


def sha(data):
    return hashlib.sha256(data).hexdigest()


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--check", action="store_true")
    args = parser.parse_args()
    upstream = json.loads((ROOT / "elephc/upstream.json").read_text())
    manifest = json.loads((ROOT / "patches/full-source/manifest.json").read_text())
    archive = ROOT / "upstream" / f"wordpress-{upstream['version']}.tar.gz"
    if not archive.exists() and not args.check:
        archive.parent.mkdir(parents=True, exist_ok=True)
        with tempfile.TemporaryDirectory(prefix=".wordpress-download-", dir=archive.parent) as directory:
            staged = Path(directory) / archive.name
            with urllib.request.urlopen(upstream["url"], timeout=60) as response, staged.open("wb") as target:
                shutil.copyfileobj(response, target)
            if sha(staged.read_bytes()) != upstream["archive_sha256"]:
                raise SystemExit("WordPress archive checksum mismatch")
            staged.rename(archive)
    if not archive.is_file() or sha(archive.read_bytes()) != upstream["archive_sha256"]:
        raise SystemExit("Missing original WordPress archive or checksum mismatch")

    OUT.mkdir(parents=True, exist_ok=True)
    original = ROOT / "upstream/wordpress"
    if original.is_symlink():
        raise SystemExit("Refusing a symbolic upstream tree")
    if not original.exists() and not args.check:
        with tempfile.TemporaryDirectory(prefix=".wordpress-extract-", dir=original.parent) as directory:
            with tarfile.open(archive) as package:
                package.extractall(directory, filter="data")
            (Path(directory) / "wordpress").rename(original)

    expected = {}
    with tarfile.open(archive) as package:
        for member in package:
            if not member.isfile():
                continue
            path = str(Path(member.name).relative_to("wordpress"))
            data = package.extractfile(member).read()
            expected[path] = sha(data)
            source = original / path
            if not source.is_file() or source.is_symlink() or sha(source.read_bytes()) != expected[path]:
                raise SystemExit(f"Original dependency differs from archive: {path}")
    if {str(p.relative_to(original)) for p in original.rglob("*") if p.is_file()} != set(expected):
        raise SystemExit("Original dependency file inventory differs from archive")

    entity_fixture = ROOT / "elephc/full-source/fixtures/aot-entities.php"
    if entity_fixture.exists():
        entity_meta = json.loads((ROOT / "elephc/full-source/entities-provenance.json").read_text())
        if entity_meta["fixture"] != "elephc/full-source/fixtures/aot-entities.php":
            raise SystemExit("Unexpected entity fixture path")
        if sha(entity_fixture.read_bytes()) != entity_meta["fixture_sha256"]:
            raise SystemExit("Entity fixture checksum mismatch")
        for path, checksum in entity_meta["sources"].items():
            if expected.get(path) != checksum:
                raise SystemExit(f"Entity fixture source checksum mismatch: {path}")

    property_meta_path = ROOT / "elephc/full-source/property-provenance.json"
    if property_meta_path.exists():
        property_meta = json.loads(property_meta_path.read_text())
        if property_meta["fixture"] != "elephc/full-source/fixtures/aot-platform.lfc" or property_meta["function"] != "wp_aot_property_exists":
            raise SystemExit("Unexpected property metadata fixture")
        native_platform = (ROOT / property_meta["fixture"]).read_text()
        begin = native_platform.index("function wp_aot_property_exists(")
        end = native_platform.index("\n}\n", begin) + 3
        if sha(native_platform[begin:end].encode()) != property_meta["function_sha256"]:
            raise SystemExit("Property metadata fixture checksum mismatch")
        for info in property_meta["classes"].values():
            if expected.get(info["path"]) != info["original_sha256"]:
                raise SystemExit("Property metadata original source checksum mismatch: " + info["path"])

    constant_meta_path = ROOT / "elephc/full-source/constant-presence-provenance.json"
    if constant_meta_path.exists():
        constant_meta = json.loads(constant_meta_path.read_text())
        for path, checksum in constant_meta["sources"].items():
            if expected.get(path) != checksum:
                raise SystemExit("Bootstrap constant original source checksum mismatch: " + path)
        for fixture, functions in constant_meta["functions"].items():
            if not fixture.startswith("elephc/full-source/fixtures/") or ".." in Path(fixture).parts:
                raise SystemExit("Unexpected bootstrap constant fixture")
            source = (ROOT / fixture).read_text()
            for name, checksum in functions.items():
                start = source.index("function " + name + "(")
                end = source.index("\n}\n", start) + 3
                if sha(source[start:end].encode()) != checksum:
                    raise SystemExit("Bootstrap constant helper checksum mismatch: " + name)

    tree = OUT / "wordpress"
    patched = dict(expected)
    for entry in manifest["files"]:
        if Path(entry["path"]).is_absolute() or ".." in Path(entry["path"]).parts:
            raise SystemExit("Patch target must be inside WordPress")
        if expected[entry["path"]] != entry["original_sha256"]:
            raise SystemExit(f"Wrong original checksum: {entry['path']}")
        patch = ROOT / entry["patch"]
        if sha(patch.read_bytes()) != entry["patch_sha256"]:
            raise SystemExit(f"Patch checksum mismatch: {patch}")
        patched[entry["path"]] = entry["patched_sha256"]

    if not args.check:
        with tempfile.TemporaryDirectory(prefix=".wordpress-stage-", dir=OUT) as directory:
            stage = Path(directory) / "wordpress"
            shutil.copytree(original, stage)
            for entry in manifest["files"]:
                applied = subprocess.run(["patch", "--batch", "--fuzz=0", "-p1", "-d", str(stage)],
                                         input=(ROOT / entry["patch"]).read_bytes(), capture_output=True)
                if applied.returncode:
                    raise SystemExit(f"Cannot apply {entry['patch']}:\n" +
                                     applied.stdout.decode(errors="replace") + applied.stderr.decode(errors="replace"))
                if sha((stage / entry["path"]).read_bytes()) != entry["patched_sha256"]:
                    raise SystemExit(f"Patched source checksum mismatch: {entry['path']}")
            for fixture in sorted((ROOT / "elephc/full-source/fixtures").rglob("*")):
                if fixture.is_file():
                    relative = str(fixture.relative_to(ROOT / "elephc/full-source/fixtures"))
                    if relative in expected:
                        raise SystemExit(f"Fixture would overwrite original source: {relative}")
                    target = stage / relative
                    target.parent.mkdir(parents=True, exist_ok=True)
                    shutil.copyfile(fixture, target)
            if tree.is_symlink():
                raise SystemExit("Refusing a symbolic build tree")
            if tree.exists():
                shutil.rmtree(tree)
            stage.rename(tree)

    for fixture in sorted((ROOT / "elephc/full-source/fixtures").rglob("*")):
        if fixture.is_file():
            patched[str(fixture.relative_to(ROOT / "elephc/full-source/fixtures"))] = sha(fixture.read_bytes())
    for path, checksum in patched.items():
        target = tree / path
        if not target.is_file() or target.is_symlink() or sha(target.read_bytes()) != checksum:
            raise SystemExit(f"Prepared source mismatch: {path}")
    actual = {str(p.relative_to(tree)) for p in tree.rglob("*") if p.is_file()}
    if actual != set(patched):
        raise SystemExit("Prepared source inventory differs")
    report = {"schema": 1, "wordpress_version": upstream["version"],
              "archive_sha256": upstream["archive_sha256"], "original_files": len(expected),
              "patches": len(manifest["files"]), "original": expected, "prepared": patched}
    if property_meta_path.exists():
        report["property_provenance_sha256"] = sha(property_meta_path.read_bytes())
    if constant_meta_path.exists():
        report["constant_presence_provenance_sha256"] = sha(constant_meta_path.read_bytes())
    if not args.check:
        (OUT / "source-manifest.json").write_text(json.dumps(report, indent=2) + "\n")
    elif json.loads((OUT / "source-manifest.json").read_text()) != report:
        raise SystemExit("Prepared provenance report differs")
    print(f"Verified complete WordPress {upstream['version']}: {len(expected)} original files, "
          f"{len(manifest['files'])} full-file patches, {len(patched) - len(expected)} fixtures.")


if __name__ == "__main__":
    main()
