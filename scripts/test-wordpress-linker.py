#!/usr/bin/env python3
"""Check native behavior, function identity and ELF protection under section placement."""

import importlib.util
import hashlib
import json
import os
from pathlib import Path
import shutil
import subprocess
import tempfile

ROOT = Path(__file__).resolve().parent.parent


def main():
    spec = importlib.util.spec_from_file_location("wordpress_link", ROOT / "scripts/wordpress-link.py")
    linker = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(linker)
    driver = shutil.which("gcc")
    if not driver:
        raise SystemExit("gcc is required on PATH")
    output = ROOT / "elephc/build/full-source"
    output.mkdir(parents=True, exist_ok=True)
    with tempfile.TemporaryDirectory(prefix=".linker-control-", dir=output) as temporary:
        directory = Path(temporary)
        linker.OUT = directory
        (directory / "source-manifest.json").write_text('{"control":"native linker placement"}\n')
        (directory / "ld.lld").symlink_to(linker.find_lld(None))
        source = directory / "control.c"
        source.write_text('''#include <stdio.h>
long shared_counter __attribute__((aligned(16)));
__attribute__((noinline)) int first(void) { return 7; }
__attribute__((noinline)) int second(void) { return 7; }
int main(void) {
    shared_counter += 9;
    printf("%d %d %d %ld\\n", first(), second(), first == second, shared_counter);
    return 0;
}
''')
        original = directory / "bootstrap.o"
        binary = directory / "control"
        subprocess.run([driver, "-fcommon", "-ffunction-sections", "-fdata-sections", "-O0",
                        "-c", str(source), "-o", str(original)], check=True)
        arguments = [str(original), "-o", str(binary), "-Wl,--gc-sections",
                     "-Wl,-z,relro", "-Wl,-z,now", "-Wl,-z,noexecstack"]
        subprocess.run([driver, *arguments], check=True)
        expected = subprocess.check_output([str(binary)], text=True)
        if expected != "7 7 0 9\n":
            raise SystemExit("Ordinary toolchain control did not preserve function identity")
        previous_debug = os.environ.get("WORDPRESS_KEEP_DEBUG")
        try:
            os.environ["WORDPRESS_KEEP_DEBUG"] = "1"
            code = linker.link_wordpress(arguments, driver, directory)
        finally:
            if previous_debug is None:
                os.environ.pop("WORDPRESS_KEEP_DEBUG", None)
            else:
                os.environ["WORDPRESS_KEEP_DEBUG"] = previous_debug
        if code:
            raise SystemExit("Section placement did not link the native control")
        if subprocess.check_output([str(binary)], text=True) != expected:
            raise SystemExit("Section placement changed native behavior or function identity")
        report = json.loads((directory / "bootstrap-link.json").read_text())
        debug = directory / report["debug_binary"]["file"]
        if (debug.read_bytes() != binary.read_bytes()
                or report["debug_binary"]["sha256"] != hashlib.sha256(debug.read_bytes()).hexdigest()):
            raise SystemExit("The retained debug copy differs from the linked executable")
        if report["function_folding"] or report["allocated_contents_sha256"] != report["placed_allocated_contents_sha256"]:
            raise SystemExit("Section placement changed allocated contents")
        if report["common_symbols"]["shared_counter"] != {"size": 8, "alignment": 16, "visibility": 0}:
            raise SystemExit("COMMON storage size or alignment changed")
        headers = subprocess.check_output(["readelf", "-lW", str(binary)], text=True)
        dynamic = subprocess.check_output(["readelf", "-dW", str(binary)], text=True)
        stack = next((line for line in headers.splitlines() if "GNU_STACK" in line), "")
        if "GNU_RELRO" not in headers or not stack or " E " in stack or "TEXTREL" in dynamic:
            raise SystemExit("Section placement lost RELRO or executable-memory protections")
        if list(directory.glob(".wordpress-layout-*")):
            raise SystemExit("Linker temporary files were not removed")
        # A second entry must have its own object and provenance report.
        cli_report = (directory / "bootstrap-link.json").read_bytes()
        web_object = directory / "bootstrap-web.o"
        web_binary = directory / "control-web"
        shutil.copyfile(original, web_object)
        web_arguments = [str(web_object), "-o", str(web_binary), "-Wl,--gc-sections",
                         "-Wl,-z,relro", "-Wl,-z,now", "-Wl,-z,noexecstack"]
        if linker.link_wordpress(web_arguments, driver, directory, source=web_object):
            raise SystemExit("Named web entry did not link")
        if subprocess.check_output([str(web_binary)], text=True) != expected:
            raise SystemExit("Named entry changed native behavior")
        web_report = json.loads((directory / "bootstrap-web-link.json").read_text())
        if (web_report["allocated_contents_sha256"] != report["allocated_contents_sha256"]
                or (directory / "bootstrap-link.json").read_bytes() != cli_report):
            raise SystemExit("Named entry changed contents or overwrote CLI provenance")
    print("PASS: section placement preserves native values, function identity, COMMON alignment, allocated bytes and ELF protections")


if __name__ == "__main__":
    main()
