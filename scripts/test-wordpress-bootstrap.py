#!/usr/bin/env python3
"""Compare complete original and patched WordPress bootstrap with the SQL fixture."""

import argparse
from contextlib import ExitStack
import hashlib
from html.parser import HTMLParser
import json
import os
import re
from pathlib import Path
import subprocess
import shutil
import tempfile

ROOT = Path(__file__).resolve().parent.parent
OUT = ROOT / "elephc/build/full-source"


def require_navigation_names(markup):
    class NavigationNames(HTMLParser):
        def __init__(self):
            super().__init__()
            self.names = []

        def handle_starttag(self, tag, attrs):
            attributes = dict(attrs)
            if tag in ("nav", "div") and "wp-block-navigation" in attributes.get("class", "").split():
                self.names.append((tag, attributes.get("aria-label")))

    parser = NavigationNames()
    parser.feed(markup)
    expected = [("nav", "Menu"), ("div", "Menu"), ("nav", "Menu 2"),
                ("nav", "Città & utilità"), ("nav", "Città & utilità 2"),
                ("nav", "Menu 3"), ("nav", "0"), ("nav", "0 2")]
    if parser.names != expected:
        raise SystemExit(f"Unexpected navigation names or overlay counter behavior: {parser.names}")



def require_native_state(actual, expected, case, source_hash):
    if json.dumps(actual, sort_keys=True) == json.dumps(expected, sort_keys=True):
        return
    path = OUT / "bootstrap-native-mismatch.json"
    path.write_text(json.dumps({"case": case, "source_manifest_sha256": source_hash,
                                "expected": expected, "actual": actual}, indent=2) + "\n")
    raise SystemExit(f"Native WordPress differs from original PHP for {case}; see {path.relative_to(ROOT)}")


def require_native_diagnostics(stderr, case):
    unexpected = [line for line in stderr.splitlines()
                  if line.strip() and not re.fullmatch(r"GC: allocs=\d+ frees=\d+", line)]
    if unexpected:
        raise SystemExit(f"Unexpected native WordPress diagnostics for {case}:\n" + stderr)


def require_menu_previews(state):
    expected = {
        "replace": ([1, 2, 3], ["Alpha", "Updated", "Gamma"]),
        "insert": ([1, 2, 9, 3], ["Alpha", "Beta", "Inserted", "Gamma"]),
        "empty": ([7], ["Only"]),
        "previous": ([1, 17, 3], ["Alpha", "Previous", "Gamma"]),
        "delete": ([1, 3], ["Alpha", "Gamma"]),
        "delete-absent": ([1, 2, 3], ["Alpha", "Beta", "Gamma"]),
        "delete-previous": ([1, 3], ["Alpha", "Gamma"]),
        "sort": ([1, 3, 2], ["Alpha", "Gamma", "Zulu"]),
        "other-blog": ([1, 2, 3], ["Alpha", "Beta", "Gamma"]),
    }
    rows = state["menu_previews"]
    if [row["case"] for row in rows] != list(expected):
        raise SystemExit("Unexpected menu preview cases")
    for row in rows:
        ids, names = expected[row["case"]]
        identities = [[value == 1, value == 2 and row["case"] in ("insert", "delete-absent", "other-blog"), value == 3] for value in ids]
        classes = ["WP_Term" if value in (1, 3) else "stdClass" for value in ids]
        if (row["ids"] != ids or row["names"] != names or row["keys"] != list(range(len(ids)))
                or row["classes"] != classes or row["identity"] != identities
                or row["input_before"] != row["input_after"] or row["input_before"] != row["snapshot"]
                or row["reads"] != (0 if row["case"] == "other-blog" else 1)):
            raise SystemExit(f"Unexpected original menu preview state: {row}")


def require_menu_options(state):
    rows = state["menu_option_cases"]
    if len(rows) != 128:
        raise SystemExit("Unexpected menu option case count")
    for row in rows:
        original = json.loads(row["input"])
        values = original.get("auto_add") or []
        entries = list(enumerate(values)) if isinstance(values, list) else [(int(key), value) for key, value in values.items()]
        found = next((key for key, value in entries if type(value) is type(row["menu_id"]) and value == row["menu_id"]), None)
        if row["auto_add"] and found is None:
            next_key = max([0] + [key + 1 for key, _ in entries])
            entries.append((next_key, row["term_id"]))
        elif not row["auto_add"] and found is not None:
            size = len(entries)
            start = max(0, size + found) if found < 0 else min(found, size)
            retained = [value for index, (_, value) in enumerate(entries) if index != start]
            entries = list(enumerate(retained))
        if (row["keys"] != [key for key, _ in entries] or row["values"] != [value for _, value in entries]
                or row["before"] != row["after"] or row["before"] != row["snapshot"] or row["reads"] != 0):
            raise SystemExit(f"Unexpected original menu option state: {row}")


def require_theme_capabilities(state):
    names = ["empty-string", "zero-string", "null", "false", "empty-array", "missing", "plain", "html5-present",
             "html5-missing", "thumbnails-present", "thumbnails-missing", "logo-present", "logo-false", "context-full",
             "context-named", "context-mixed", "context-long", "deny", "named-prefix", "support-array"]
    denied = {"missing", "html5-missing", "thumbnails-missing", "logo-false", "deny"}
    traces = {"plain": [[True, [], True]], "support-array": [[True, [], True]], "deny": [[True, [], False]],
              "context-full": [[True, [None, 1, True, {"x": 2}], ["context-data"]]],
              "context-named": [[True, {"extra": 7, "flag": False}, ["context-data"]]],
              "context-mixed": [[True, {"0": 2, "named": None}, ["context-data"]]],
              "context-long": [[True, list(range(1, 12)), ["context-data"]]],
              "named-prefix": [[True, [], ["context-data"]]]}
    rows = state["theme_capability_cases"]
    if [(row["case"], row["kind"]) for row in rows] != [(name, kind) for name in names for kind in ("setting", "panel", "section")]:
        raise SystemExit("Unexpected theme capability cases")
    for row in rows:
        if row["result"] is not (row["case"] not in denied) or row["trace"] != traces.get(row["case"], []):
            raise SystemExit(f"Unexpected original theme capability result: {row}")


def require_template_constructors(state):
    rows = state["template_constructor_cases"]
    kinds = {"panel": ("WordPressTemplatePanelProbe", "default", ["construct", "panel-content", "panel-container"]),
             "section": ("WordPressTemplateSectionProbe", "default", ["construct", "section-container"]),
             "control": ("WordPressTemplateControlProbe", "text", ["construct", "control-content"])}
    if len(rows) != 12:
        raise SystemExit("Unexpected template constructor case count")
    for row in rows:
        cls, type_name, events = kinds[row["kind"]]
        count = row["registered"].count(row["kind"])
        expected = []
        for _ in range(count):
            for event in events:
                args = ({"settings": []} if row["kind"] == "control" else []) if event == "construct" else None
                expected.append([event, cls, "temp", True, args, type_name, True])
        if row["return"] is not None or row["trace"] != expected:
            raise SystemExit(f"Unexpected original template construction: {row}")
        if row["kind"] == "control":
            if 'id="tmpl-customize-control-default-content"' not in row["html"] or row["html"].count("probe-control-content") != count:
                raise SystemExit("Unexpected original control template output")
        elif count == 0 and row["html"] != "":
            raise SystemExit("Empty template registry produced output")
        elif count and row["html"].count("probe-" + row["kind"]) != count * (2 if row["kind"] == "panel" else 1):
            raise SystemExit("Template registry lost or duplicated output")


def require_theme_json_caches(state):
    rows = state["theme_json_cache_cases"]
    if len(rows) != 46:
        raise SystemExit("Unexpected theme JSON cache case count")
    for row in rows:
        if row["kind"] == "hit":
            expected = [] if row["path"] == "0" else row["input"]
            if row["first"] != expected or row["second"] != expected:
                raise SystemExit(f"Unexpected cached theme JSON value: {row}")
            if len(row["state"][0]) != 1:
                raise SystemExit("Theme JSON cache hit changed storage")
        elif row["kind"] == "file":
            if (row["first_sha256"] != row["second_sha256"] or row["first_sha256"] != row["cached_sha256"]
                    or row["cache_size"] != 1 or "version" not in row["keys"]):
                raise SystemExit(f"Theme JSON file was not retained in its selected cache: {row}")
        else:
            valid = row["origin"] != "invalid"
            if row["first"] is not (valid and row["mode"] != "empty") or row["second"] is not valid:
                raise SystemExit(f"Unexpected registered block cache result: {row}")
            for origin, values in row["cache"].items():
                expected = list(row["registered"]) if row["mode"] != "empty" or (valid and origin == row["origin"]) else []
                if row["mode"] == "extra":
                    expected.append("fixture/extra")
                keys = list(values) if isinstance(values, dict) else []
                if keys != expected or (isinstance(values, dict) and any(value is not True for value in values.values())):
                    raise SystemExit(f"Unexpected registered block cache storage: {row}")


def require_user_query_compatibility(state):
    defaults, rows, invalid, alias_state = state["user_query_compatibility"]
    seed = {"preserved": {"x": 7}}
    if defaults != [None, 0] or len(rows) != 12 or invalid != [None, None, False, None]:
        raise SystemExit("Unexpected user query compatibility guards or defaults")
    for row in rows:
        field, value, before, removed, missing, replacement, present, query, alias, snapshot = row
        if (field not in ("results", "total_users") or before != [value, value is not None]
                or removed is not None or missing is not False or replacement != {"field": field}
                or present is not True or query != seed or alias != seed or snapshot != seed):
            raise SystemExit(f"Unexpected user query compatibility lifecycle: {row}")
    changed = {**seed, "live": 9}
    if alias_state != [changed, changed, seed]:
        raise SystemExit("User query compatibility removal changed query_vars alias or snapshot")


def require_nominal_views(state):
    expected_tags = [
        ["TEMPLATE", "CAPTION", "CAPTION", "TEMPLATE"],
        ["TEMPLATE", "COLGROUP", "COL", "COLGROUP", "TEMPLATE"],
        ["TEMPLATE", "TBODY", "TR", "TD", "TD", "TR", "TBODY", "TEMPLATE"],
        ["TEMPLATE", "TR", "TH", "TH", "TR", "TEMPLATE"],
        ["TEMPLATE", "STYLE", "P", "P", "TEMPLATE"],
        ["TEMPLATE", "TEMPLATE", "DIV", "DIV", "TEMPLATE", "TEMPLATE"],
    ]
    rows = state["html_state_cases"]
    if len(rows) != len(expected_tags):
        raise SystemExit("Unexpected HTML parser state case count")
    for (_, tokens, error), tags in zip(rows, expected_tags):
        if (error is not None or [row[1] for row in tokens if row[0] == "#tag"] != tags
                or tokens[0] != ["#tag", "TEMPLATE", "open", "html", 3]
                or tokens[-1] != ["#tag", "TEMPLATE", "close", "html", 2]
                or any(row[3] != "html" or type(row[4]) is not int or row[4] < 2 for row in tokens)):
            raise SystemExit(f"Unexpected original HTML template state transitions: {tokens}, {error}")
    expected_caps = [[phase, cap, ["edit_fixture_post" if phase == "after" and cap == "edit_post" else cap]]
                     for phase in ("before", "after") for cap in ("edit_post", "read_post", "delete_post")]
    if state["capability_view_cases"] != expected_caps:
        raise SystemExit(f"Unexpected live capability map: {state['capability_view_cases']}")


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--native", type=Path, help="Also require the compiled bootstrap executable to match")
    args = parser.parse_args()
    (OUT / "bootstrap-verification.json").unlink(missing_ok=True)
    (OUT / "bootstrap-native-mismatch.json").unlink(missing_ok=True)
    subprocess.run(["python3", "scripts/prepare-wordpress.py", "--check"], cwd=ROOT, check=True)
    template = (ROOT / "elephc/full-source/fixtures/aot-probe.php").read_text()
    render_cases = json.loads((ROOT / "tests/full-source/bootstrap-render-cases.json").read_text())
    expected_renders = {}
    report = {"schema": 1, "wordpress_version": "7.1.4", "source_manifest_sha256": hashlib.sha256((OUT / "source-manifest.json").read_bytes()).hexdigest(),
              "php": {}, "native_verified": False,
              "harness_sha256": hashlib.sha256(Path(__file__).read_bytes()).hexdigest()}
    editor_policy = subprocess.run([os.environ.get("PHP_BIN", "php"), str(ROOT / "tests/full-source/image-editor-policy.php")],
                                   cwd=ROOT, capture_output=True, text=True, check=True)
    report["image_editor_policy_php"] = json.loads(editor_policy.stdout)
    print("PASS: original GD/Imagick public subsize capability policy", flush=True)
    expected = None
    expected_tree = None
    expected_templates = None
    expected_visitors = None
    expected_hooks = None
    expected_block_lists = None
    expected_ai = None
    expected_lexical_closures = None
    with ExitStack() as stack:
        directory = stack.enter_context(tempfile.TemporaryDirectory(prefix=".php-reference-", dir=OUT))
        reference = Path(directory) / "wordpress"
        shutil.copytree(ROOT / "upstream/wordpress", reference)
        # Original core stays byte-identical; only local configuration and drop-ins are added.
        for name in ("wp-config.php", "wp-content/db.php", "wp-content/aot-data.php", "aot-arrays.php", "aot-binary.php", "aot-html.php", "aot-regex.php", "aot-contracts.php", "aot-core-contracts.php", "aot-ai.php", "aot-php-platform.php"):
            shutil.copyfile(ROOT / "elephc/full-source/fixtures" / name, reference / name)
        for label, base in (("original", f"/{reference.parent.name}/wordpress/"), ("patched", "/wordpress/")):
            source = template.replace("require __DIR__ . '/aot-platform.lfc';", "require ABSPATH . 'aot-php-platform.php';")
            if label == "patched":
                source = source.replace("<?php", "<?php\nrequire __DIR__ . '/../../../upstream/wordpress/wp-includes/sodium_compat/autoload.php';", 1)
            source = source.replace("define( 'ABSPATH', __DIR__ . '/' );", f"define( 'ABSPATH', __DIR__ . '{base}' );")
            source = source.replace("require __DIR__ . '/wp-load.php';", "require ABSPATH . 'wp-load.php';")
            source += "\necho json_encode(require __DIR__ . '/../../../tests/full-source/setting-tree-cases.php'), \"\\n\";\n"
            source += "\necho json_encode(require __DIR__ . '/../../../tests/full-source/template-theme-cases.php'), \"\\n\";\n"
            source += "\necho json_encode(require __DIR__ . '/../../../tests/full-source/block-visitor-cases.php'), \"\\n\";\n"
            source += "\necho json_encode(require __DIR__ . '/../../../tests/full-source/hook-cursor-cases.php'), \"\\n\";\n"
            source += "\necho json_encode(require __DIR__ . '/../../../tests/full-source/block-list-cursor-cases.php'), \"\\n\";\n"
            source += "\nrequire __DIR__ . '/../../../tests/full-source/ai-cases.php';\n"
            source += "\necho json_encode(require __DIR__ . '/../../../tests/full-source/lexical-closure-cases.php'), \"\\n\";\n"
            driver = OUT / f"bootstrap-{label}.php"
            driver.write_text(source)
            stack.callback(driver.unlink, missing_ok=True)
            result = subprocess.run([os.environ.get("PHP_BIN", "php"), "-d", "zend.assertions=1", "-d", "assert.exception=1", str(driver)], cwd=ROOT,
                                    capture_output=True, text=True, timeout=30)
            if result.returncode:
                raise SystemExit(f"{label} PHP bootstrap failed:\n" + result.stdout + result.stderr)
            lines = result.stdout.splitlines()
            state = json.loads(lines[0])
            require_menu_previews(state)
            require_menu_options(state)
            require_theme_capabilities(state)
            require_template_constructors(state)
            require_theme_json_caches(state)
            require_user_query_compatibility(state)
            require_nominal_views(state)
            tree = json.loads(lines[1])
            templates = json.loads(lines[2])
            visitors = json.loads(lines[3])
            hooks = json.loads(lines[4])
            block_lists = json.loads(lines[5])
            ai = json.loads(lines[6])
            lexical_closures = json.loads(lines[7])
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
            if expected_hooks is not None and expected_hooks != hooks:
                raise SystemExit("Patched hook mutation cursors differ from original WordPress PHP")
            expected_hooks = hooks
            if expected_block_lists is not None and expected_block_lists != block_lists:
                raise SystemExit("Patched block list mutation cursors differ from original WordPress PHP")
            expected_block_lists = block_lists
            if expected_ai is not None and expected_ai != ai:
                raise SystemExit("Patched AI DTO, enum and PSR-7 results differ from original WordPress PHP")
            expected_ai = ai
            if expected_lexical_closures is not None and expected_lexical_closures != lexical_closures:
                raise SystemExit("Patched lexical callback behavior differs from original WordPress PHP")
            expected_lexical_closures = lexical_closures
            report["php"][label] = state
            print(f"PASS: complete {label} WordPress bootstrap, {state['registered_blocks']} registered blocks", flush=True)
            for name, document in render_cases.items():
                rendered = subprocess.run(
                    [os.environ.get("PHP_BIN", "php"), "-d", "zend.assertions=1", "-d", "assert.exception=1", str(driver), document],
                    cwd=ROOT, capture_output=True, text=True, timeout=30)
                if rendered.returncode:
                    raise SystemExit(f"{label} PHP document {name} failed:\n" + rendered.stdout + rendered.stderr)
                try:
                    rendered_state = json.loads(rendered.stdout.splitlines()[0])
                except (ValueError, IndexError) as error:
                    raise SystemExit(f"{label} PHP document {name} returned invalid JSON:\n" + rendered.stdout + rendered.stderr) from error
                if name == "navigation-names":
                    require_navigation_names(rendered_state["rendered"])
                if label == "original":
                    expected_renders[name] = rendered_state
                elif json.dumps(rendered_state, sort_keys=True) != json.dumps(expected_renders[name], sort_keys=True):
                    raise SystemExit(f"Patched WordPress rendering differs from original PHP: {name}")
    report["render_cases"] = {
        name: {"document_sha256": hashlib.sha256(document.encode()).hexdigest(),
               "rendered": expected_renders[name]["rendered"], "php_matches_original": True,
               "native_matches_original": False}
        for name, document in render_cases.items()
    }
    print(f"PASS: {len(render_cases)} runtime document renderings match original PHP", flush=True)
    report["setting_tree_php"] = {"cases": len(expected_tree), "matches_original": True}
    print(f"PASS: {len(expected_tree)} setting tree results match original PHP", flush=True)
    report["template_theme_php"] = {"cases": len(expected_templates), "matches_original": True}
    print(f"PASS: {len(expected_templates)} template transformations and callback traces match original PHP", flush=True)
    report["block_visitors_php"] = {"cases": len(expected_visitors), "matches_original": True}
    print(f"PASS: {len(expected_visitors)} block visitor results and mutation traces match original PHP", flush=True)
    report["hook_cursors_php"] = {"cases": len(expected_hooks), "matches_original": True}
    print(f"PASS: {len(expected_hooks)} hook mutation and iterator traces match original PHP", flush=True)
    report["block_list_cursors_php"] = {"cases": len(expected_block_lists), "matches_original": True}
    print(f"PASS: {len(expected_block_lists)} lazy block list cursor traces match original PHP", flush=True)
    report["ai_php"] = {"groups": len(expected_ai), "matches_original": True}
    print(f"PASS: {len(expected_ai)} AI DTO, enum and PSR-7 groups match original PHP", flush=True)
    report["lexical_closures_php"] = {"cases": len(expected_lexical_closures), "matches_original": True}
    print(f"PASS: {len(expected_lexical_closures)} lexical callback results match original PHP", flush=True)
    report["html_state_php"] = {"cases": len(expected["html_state_cases"]), "matches_original": True}
    report["capability_view_php"] = {"cases": len(expected["capability_view_cases"]), "matches_original": True}
    print("PASS: 6 HTML template state transitions and 6 live capability map results match original PHP", flush=True)
    if args.native:
        result = subprocess.run([str(args.native.resolve())], cwd=ROOT, capture_output=True,
                                text=True, timeout=30)
        if result.returncode:
            raise SystemExit("Native WordPress bootstrap failed:\n" + result.stdout + result.stderr)
        require_native_diagnostics(result.stderr, "default")
        state = json.loads(result.stdout)
        require_native_state(state, expected, "default", report["source_manifest_sha256"])
        for name, document in render_cases.items():
            rendered = subprocess.run([str(args.native.resolve()), document], cwd=ROOT, capture_output=True,
                                      text=True, timeout=30)
            if rendered.returncode:
                raise SystemExit(f"Native document {name} failed:\n" + rendered.stdout + rendered.stderr)
            require_native_diagnostics(rendered.stderr, name)
            require_native_state(json.loads(rendered.stdout), expected_renders[name], name, report["source_manifest_sha256"])
            report["render_cases"][name]["native_matches_original"] = True
            report["render_cases"][name]["native_stderr_clean"] = True
        report["native_verified"] = True
        report["native_stderr_clean"] = True
        report["native"] = state
        report["native_sha256"] = hashlib.sha256(args.native.read_bytes()).hexdigest()
        print("PASS: native WordPress bootstrap matches original PHP", flush=True)
        print(f"PASS: {len(render_cases)} native runtime document renderings match original PHP", flush=True)
    (OUT / "bootstrap-verification.json").write_text(json.dumps(report, indent=2) + "\n")


if __name__ == "__main__":
    main()
