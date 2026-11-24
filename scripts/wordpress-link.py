#!/usr/bin/env python3
"""Run Elephc with an ELF section-placement compatibility linker for WordPress."""

import argparse
import datetime
import hashlib
import json
import os
from pathlib import Path
import re
import shutil
import subprocess
import sys
import tempfile
import time

from wordpress_elf import ElfObject

ROOT = Path(__file__).resolve().parent.parent
OUT = ROOT / "elephc/build/full-source"
CENTRAL_PREFIXES = (".text._eir", ".text.__elephc", ".text._method_source_abi",
                    ".text._fn_wp_u_aot", ".text._fn__u__u_eir", ".text.main")


def file_sha(path):
    with Path(path).open("rb") as source:
        return hashlib.file_digest(source, "sha256").hexdigest()


def find_lld(value):
    """Find LLVM's GNU linker on PATH or in the active Rust toolchain."""
    if value:
        result = shutil.which(value)
        if not result:
            raise SystemExit("LLVM linker not found: " + value)
        return str(Path(result).resolve())
    result = shutil.which("ld.lld")
    if result:
        return str(Path(result).resolve())
    rustc = shutil.which("rustc")
    if rustc:
        sysroot = subprocess.check_output([rustc, "--print", "sysroot"], text=True).strip()
        version = subprocess.check_output([rustc, "-vV"], text=True)
        host = next((line[6:] for line in version.splitlines() if line.startswith("host: ")), "")
        candidate = Path(sysroot) / "lib/rustlib" / host / "bin/rust-lld"
        if candidate.is_file():
            return str(candidate.resolve())
    raise SystemExit("Install LLVM ld.lld on PATH or set WORDPRESS_LLD to its executable.")


def common_symbols(arguments):
    """Read exact COMMON size, alignment and visibility from the actual link inputs."""
    paths = []
    for argument in arguments:
        if argument.startswith("-"):
            continue
        path = Path(argument)
        if not path.is_file():
            continue
        with path.open("rb") as stream:
            magic = stream.read(8)
        if magic.startswith(b"\x7fELF") or magic == b"!<arch>\n":
            paths.append(str(path))
    symbols = {}
    result = subprocess.run(["readelf", "-sW", *paths], capture_output=True, text=True, check=True)
    visibility = {"DEFAULT": 0, "INTERNAL": 1, "HIDDEN": 2, "PROTECTED": 3}
    for line in result.stdout.splitlines():
        fields = line.split()
        if len(fields) != 8 or fields[6] != "COM":
            continue
        _, alignment, size, _, binding, visible, _, name = fields
        if binding != "GLOBAL" or not re.fullmatch(r"[A-Za-z_.$][A-Za-z_0-9.$]*", name):
            raise SystemExit("Unsupported COMMON binding or symbol spelling: " + line)
        item = {"size": int(size, 16) if size.startswith("0x") else int(size),
                "alignment": int(alignment, 16),
                "visibility": visibility[visible]}
        previous = symbols.get(name)
        if previous:
            item["size"] = max(item["size"], previous["size"])
            item["alignment"] = max(item["alignment"], previous["alignment"])
            old = previous["visibility"]
            if old and item["visibility"] and old != item["visibility"]:
                raise SystemExit("Conflicting COMMON visibility: " + name)
            item["visibility"] = item["visibility"] or old
        symbols[name] = item
    return symbols


def emit_common_object(directory, symbols):
    """Retain zero initialization and storage extents while grouping COMMON definitions."""
    lines = []
    directives = {1: ".internal", 2: ".hidden", 3: ".protected"}
    for name, item in sorted(symbols.items()):
        group = "small" if item["size"] < 65536 else "side"
        if name in ("_concat_buf", "_heap_buf"):
            group = "concat" if name == "_concat_buf" else "heap"
        alignment = item["alignment"]
        if alignment < 1 or alignment & (alignment - 1):
            raise SystemExit("COMMON alignment must be a positive power of two: " + name)
        lines += [f'.section .bss.wp_{group},"aw",@nobits', f".balign {alignment}",
                  f".globl {name}", f".type {name},@object", f".size {name},{item['size']}"]
        if item["visibility"]:
            lines.append(directives[item["visibility"]] + " " + name)
        lines += [name + ":", f".zero {item['size']}"]
    lines.append('.section .note.GNU-stack,"",@progbits')
    assembly = directory / "wordpress-common.s"
    assembly.write_text("\n".join(lines) + "\n")
    output = directory / "wordpress-common.o"
    subprocess.run(["as", "-o", str(output), str(assembly)], check=True)
    return output


def placement_script(path):
    """Keep code/data reachable and retain the normal TLS, unwind and RELRO sections."""
    path.write_text('''SECTIONS {
 .wp_first_text : { *wordpress-layout.o(.wp.first.*) }
 .wp_central_text : { *wordpress-layout.o(.wp.center.*) }
 .wp_native_text : { *(EXCLUDE_FILE (*wordpress-layout.o) .text .text.*) }
 .init : { KEEP(*(.init)) }
 .fini : { KEEP(*(.fini)) }
 .plt : { *(.plt) }
 .tdata : { *(.tdata .tdata.*) }
 .tbss : { *(.tbss .tbss.*) }
 .fini_array : { *(.fini_array .fini_array.*) }
 .init_array : { *(.init_array .init_array.*) }
 .data.rel.ro : { *(.data.rel.ro .data.rel.ro.*) }
 .dynamic : { *(.dynamic) }
 .got : { *(.got) }
 .got.plt : { *(.got.plt) }
 .relro_padding : { *(.relro_padding) }
 .wp_data : { *(.data .data.*) *(.tm_clone_table) }
 .wp_small_bss (NOLOAD) : {
   *(.bss.wp_small)
   *(EXCLUDE_FILE (*wordpress-common.o) .bss .bss.*)
 }
}
INSERT BEFORE .rodata;
SECTIONS {
 .wp_tail_text : { *wordpress-layout.o(.text .text.*) }
 .wp_side_tables (NOLOAD) : { *(.bss.wp_side) }
 .wp_buffers (NOLOAD) : { *(.bss.wp_concat) *(.bss.wp_heap) }
}
INSERT AFTER .eh_frame;
''')


def inspect_link_errors(path, code_symbols, original_sections, central):
    """Move complete code sections for observed long edges, including data-address callers."""
    extra = set()
    unresolved = []
    errors = 0
    source = None
    for line in path.open():
        if "ld.lld: error:" in line:
            errors += 1
            source = re.search(r"wordpress-layout\.o:\(([^+]+)\+0x[0-9a-f]+\)", line)
            if "out of range" not in line:
                unresolved.append(line.strip())
                continue
        target = re.search(r"references '([^']+)'", line)
        if not target:
            continue
        section = code_symbols.get(target[1])
        if section and section not in central:
            extra.add(section)
            continue
        caller = source[1] if source else ""
        for prefix in (".wp.first.", ".wp.center."):
            if caller.startswith(prefix):
                caller = caller[len(prefix):]
                break
        if caller in original_sections and caller not in central:
            extra.add(caller)
        else:
            unresolved.append(line.strip())
    return errors, extra, unresolved


def link_wordpress(arguments, driver, tools_directory, *, direct_ld=False, source=None):
    """Link the complete object with distinct addresses and verify unchanged allocated bytes."""
    source = Path(source) if source is not None else Path(
        os.environ.get("WORDPRESS_LINK_OBJECT", OUT / "bootstrap.o"))
    index = next((i for i, value in enumerate(arguments)
                  if not value.startswith("-") and Path(value).resolve() == source), None)
    if index is None or "-c" in arguments or "-r" in arguments:
        return subprocess.run([driver, *arguments]).returncode
    report_path = source.with_name(source.stem + "-link.json")
    report_path.unlink(missing_ok=True)
    with tempfile.TemporaryDirectory(prefix=".wordpress-layout-", dir=OUT) as temporary:
        directory = Path(temporary)
        common = common_symbols(arguments)
        common_object = emit_common_object(directory, common)
        layout_object = directory / "wordpress-layout.o"
        script = directory / "wordpress-layout.ld"
        log = directory / "link.log"
        placement_script(script)
        with ElfObject(source) as original:
            sections = original.executable_sections()
            total_code = sum(sections.values())
            central = {name for name in sections if name == ".text" or name.startswith(CENTRAL_PREFIXES)}
            size = 0
            first = set()
            for name, length in sections.items():
                if name not in central and size < total_code // 2:
                    first.add(name)
                    size += length
            code_symbols = {item["name"]: original.names[item["section"]]
                            for item in original.symbols()
                            if item["binding"] and not item["common"]
                            and 0 < item["section"] < len(original.headers)
                            and original.headers[item["section"]][2] & 4}
            before = original.allocated_digest()
            attempts = []
            for attempt in range(1, 33):
                changes = original.placement_copy(layout_object, first, central)
                if direct_ld:
                    command = [str(tools_directory / "ld.lld"), *arguments]
                    command[index + 1] = str(layout_object)
                else:
                    command = [driver, "-B" + str(tools_directory) + "/", "-fuse-ld=lld", *arguments]
                    command[index + 3] = str(layout_object)
                command += ([str(common_object), "-T", str(script), "--icf=none", "--error-limit=0"]
                            if direct_ld else [str(common_object), "-Wl,-T," + str(script),
                                               "-Wl,--icf=none", "-Wl,--error-limit=0"])
                print(f"WordPress section placement: link attempt {attempt}", flush=True)
                with log.open("w") as stream:
                    result = subprocess.run(command, stdout=stream, stderr=subprocess.STDOUT)
                row = {"attempt": attempt, "exit": result.returncode}
                attempts.append(row)
                if result.returncode == 0:
                    with ElfObject(layout_object) as placed:
                        after = placed.allocated_digest()
                    if before != after:
                        raise SystemExit("Section placement changed allocated object contents")
                    report = {"schema": 1, "linked": True, "function_folding": False,
                              "source_manifest_sha256": os.environ.get("WORDPRESS_OBJECT_SOURCE_SHA256",
                                  hashlib.sha256((OUT / "source-manifest.json").read_bytes()).hexdigest()),
                              "prepared_source_manifest_sha256": hashlib.sha256((OUT / "source-manifest.json").read_bytes()).hexdigest(),
                              "compiler_sha256": os.environ.get("WORDPRESS_COMPILER_SHA256"),
                              "entry_sha256": os.environ.get("WORDPRESS_ENTRY_SHA256"),
                              "allocated_contents_sha256": before,
                              "placed_allocated_contents_sha256": after,
                              "original_object_bytes": source.stat().st_size,
                              "object_code_bytes": total_code,
                              "section_renames": changes, "common_symbols": common,
                              "attempts": attempts}
                    if os.environ.get("WORDPRESS_KEEP_DEBUG") == "1":
                        output_index = arguments.index("-o") + 1
                        executable = Path(arguments[output_index])
                        debug = executable.with_name(executable.name + ".debug")
                        shutil.copy2(executable, debug)
                        report["debug_binary"] = {"file": debug.name,
                            "sha256": hashlib.sha256(debug.read_bytes()).hexdigest()}
                    report_path.write_text(json.dumps(report, indent=2) + "\n")
                    print("WordPress linked with unchanged allocated bytes and distinct function addresses", flush=True)
                    return 0
                errors, extra, unresolved = inspect_link_errors(log, code_symbols, sections, central)
                row.update(relocation_errors=errors, moved_sections=len(extra))
                if unresolved or not extra:
                    source.with_name(source.stem + "-link-failure.log").write_text(log.read_text())
                    source.with_name(source.stem + "-link-failure.json").write_text(json.dumps(
                        {"attempts": attempts, "unresolved": unresolved}, indent=2) + "\n")
                    print("Unresolved placement diagnostics: " + repr(unresolved[:3]), file=sys.stderr)
                    print(log.read_text()[-12000:], file=sys.stderr)
                    return result.returncode
                central.update(extra)
            raise SystemExit("WordPress linker placement exceeded 32 attempts")


def main():
    if Path(sys.argv[0]).name == "ld":
        return link_wordpress(sys.argv[1:], os.environ["WORDPRESS_REAL_LD"],
                              Path(os.environ["WORDPRESS_LINK_TOOLS"]), direct_ld=True)
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--compiler", required=True)
    parser.add_argument("--entry", default=str(OUT / "bootstrap.php"))
    parser.add_argument("--lld", default=os.environ.get("WORDPRESS_LLD"))
    parser.add_argument("arguments", nargs=argparse.REMAINDER)
    args = parser.parse_args()
    compiler = shutil.which(args.compiler)
    if not compiler:
        raise SystemExit("Elephc compiler not found: " + args.compiler)
    compiler = str(Path(compiler).resolve())
    arguments = args.arguments[1:] if args.arguments[:1] == ["--"] else args.arguments
    driver = shutil.which("ld")
    if not driver:
        raise SystemExit("GNU ld is required on PATH")
    lld = find_lld(args.lld)
    OUT.mkdir(parents=True, exist_ok=True)
    entry = Path(args.entry).resolve()
    source_hash = file_sha(OUT / "source-manifest.json")
    entry_hash = file_sha(entry)
    build_path = entry.with_name(entry.stem + "-build.json")
    build = {"schema": 1, "entry_sha256": entry_hash, "source_manifest_sha256": source_hash,
             "compiler_sha256": file_sha(compiler), "driver_sha256": file_sha(__file__),
             "compiler_arguments": arguments, "web": "--web" in arguments,
             "in_progress": True, "started_at": datetime.datetime.now(datetime.UTC).isoformat()}
    build_path.write_text(json.dumps(build, indent=2) + "\n")
    if any(not value.startswith("-") and Path(value).resolve() == entry
           for value in arguments):
        entry.with_name(entry.stem + "-link.json").unlink(missing_ok=True)
    with tempfile.TemporaryDirectory(prefix=".wordpress-link-tools-", dir=OUT) as temporary:
        directory = Path(temporary)
        (directory / "ld").symlink_to(Path(__file__).resolve())
        (directory / "ld.lld").symlink_to(lld)
        environment = {**os.environ, "WORDPRESS_REAL_LD": str(Path(driver).resolve()),
                       "WORDPRESS_LINK_TOOLS": str(directory),
                       "WORDPRESS_OBJECT_SOURCE_SHA256": hashlib.sha256((OUT / "source-manifest.json").read_bytes()).hexdigest(),
                       "WORDPRESS_COMPILER_SHA256": hashlib.sha256(Path(compiler).read_bytes()).hexdigest(),
                       "WORDPRESS_LINK_OBJECT": str(entry.with_suffix(".o")),
                       "WORDPRESS_ENTRY_SHA256": entry_hash,
                       "COMPILER_PATH": str(directory) + (os.pathsep + os.environ["COMPILER_PATH"]
                                                          if os.environ.get("COMPILER_PATH") else "")}
        started = time.monotonic()
        result = subprocess.run([compiler, *arguments], env=environment)
        unchanged = (file_sha(entry) == entry_hash
                     and file_sha(OUT / "source-manifest.json") == source_hash
                     and file_sha(__file__) == build["driver_sha256"])
        code = result.returncode if unchanged else 1
        build.update(in_progress=False, exit_code=code, compiler_exit_code=result.returncode,
                     inputs_unchanged=unchanged, elapsed_seconds=time.monotonic() - started,
                     finished_at=datetime.datetime.now(datetime.UTC).isoformat())
        if code == 0:
            build["native_sha256"] = file_sha(entry.with_suffix(""))
        build_path.write_text(json.dumps(build, indent=2) + "\n")
        return code


if __name__ == "__main__":
    raise SystemExit(main())
