#!/usr/bin/env python3
"""Record one edited complete build-tree file as a checksummed source patch."""

import argparse
import difflib
import hashlib
import json
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("path", help="Path relative to the WordPress root")
    parser.add_argument("--reason", required=True)
    parser.add_argument("--kind", choices=["compatibility", "finite-profile"], required=True)
    args = parser.parse_args()
    relative = Path(args.path)
    if relative.is_absolute() or ".." in relative.parts:
        parser.error("Expected a path inside WordPress")
    original = ROOT / "upstream/wordpress" / relative
    prepared = ROOT / "elephc/build/full-source/wordpress" / relative
    before, after = original.read_bytes(), prepared.read_bytes()
    lines = difflib.unified_diff(before.decode().splitlines(keepends=True),
                                after.decode().splitlines(keepends=True),
                                fromfile=f"a/{relative}", tofile=f"b/{relative}")
    patch = "".join(line if line.endswith("\n") else line + "\n\\ No newline at end of file\n"
                    for line in lines)
    if not patch:
        parser.error("The prepared file is identical to the original")
    target = ROOT / "patches/full-source" / (str(relative) + ".patch")
    target.parent.mkdir(parents=True, exist_ok=True)
    target.write_text(patch)
    digest = lambda data: hashlib.sha256(data).hexdigest()
    entry = {"path": str(relative), "kind": args.kind, "reason": args.reason,
             "patch": str(target.relative_to(ROOT)), "original_sha256": digest(before),
             "patch_sha256": digest(target.read_bytes()), "patched_sha256": digest(after)}
    manifest_path = ROOT / "patches/full-source/manifest.json"
    manifest = json.loads(manifest_path.read_text())
    manifest["files"] = sorted([e for e in manifest["files"] if e["path"] != str(relative)] + [entry],
                               key=lambda e: e["path"])
    manifest_path.write_text(json.dumps(manifest, indent=2) + "\n")
    print(f"Recorded full-file patch: {relative}")


if __name__ == "__main__":
    main()
