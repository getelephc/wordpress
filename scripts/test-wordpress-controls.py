#!/usr/bin/env python3
"""Compile full-source platform and UTF-8 controls and compare with original PHP."""

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
CONTROL_NAMES = ("crypto", "span", "native-strings", "binary-strings", "binary-pack", "string-replace", "constant-policy", "version", "utf8", "sql", "callable", "pregquote", "array-cursor", "array-calls", "array-values", "array-sort", "array-callback", "array-more", "array-mutation", "query", "headers", "ai", "compact", "xml-append", "shutdown", "array-merge-recursive", "regex-offset", "introspection", "block-parser", "ports", "assertions", "html-entities", "dynamic-primitives", "regex-replace", "array-extra", "stream-read", "property-cursor", "weak-integer", "walker", "glob", "array-assoc", "filesystem-touch", "subdomain-constants", "token-map", "query-pagination", "uniqid", "cron")


def run(command, *, cwd=OUT, timeout=120):
    result = subprocess.run(command, cwd=cwd, capture_output=True, timeout=timeout)
    if result.returncode:
        raise SystemExit(result.stdout.decode(errors="replace") + result.stderr.decode(errors="replace"))
    return result.stdout


def main(filesystem_directory):
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--compiler", default=os.environ.get("ELEPHC_BIN", "elephc"))
    parser.add_argument("--only", choices=CONTROL_NAMES, action="append", help="Run selected controls and save a separate partial report")
    args = parser.parse_args()
    selected = tuple(dict.fromkeys(args.only or CONTROL_NAMES))
    report_path = OUT / ("controls-" + "-".join(selected) + ".json" if args.only else "controls.json")
    report_path.unlink(missing_ok=True)
    progress_path = report_path.with_stem(report_path.stem + "-progress")
    progress_path.unlink(missing_ok=True)
    compiler = shutil.which(args.compiler)
    if not compiler:
        parser.error("Compiler not found. Provide a complete Elephc SDK on PATH or with --compiler.")
    compiler = str(Path(compiler).resolve())
    php = os.environ.get("PHP_BIN", "php")
    run(["python3", "scripts/prepare-wordpress.py", "--check"], cwd=ROOT)
    for name in ("elephc.toml", "elephc.lock"):
        shutil.copyfile(ROOT / "elephc/full-source" / name, OUT / name)
    run([compiler, "native", "install", "--locked"])
    report = {"schema": 1, "complete": not bool(args.only), "compiler_version": run([compiler, "--version"]).decode().strip(),
              "ir_optimization": "off" if os.environ.get("ELEPHC_IR_OPT") == "off" else "on",
              "compiler_sha256": hashlib.sha256(Path(compiler).read_bytes()).hexdigest(),
              "source_manifest_sha256": hashlib.sha256((OUT / "source-manifest.json").read_bytes()).hexdigest(),
              "requested_controls": list(selected), "controls": {}}
    report["harness_sha256"] = hashlib.sha256(Path(__file__).read_bytes()).hexdigest()
    for name in selected:
        native = "<?php\nrequire __DIR__ . '/wordpress/aot-platform.lfc';\n"
        reference = "<?php\nrequire __DIR__ . '/wordpress/aot-php-platform.php';\n"
        if name == "cron":
            support = (ROOT / "tests/full-source/cron-support.php").read_text().removeprefix("<?php")
            boundary = Path(filesystem_directory) / "cron"
            quoted_boundary = "'" + (str(boundary) + "/").replace("\\", "\\\\").replace("'", "\\'") + "'"
            native += "define('ABSPATH', " + quoted_boundary + ");\n" + support
            reference += "require_once __DIR__ . '/wordpress/aot-arrays.php';\n" + support
            boundary.mkdir(exist_ok=True)
            (boundary / "wp-cron.php").write_text("<?php throw new LogicException('Cron process execution is outside this control.');\n")
            for source in ("plugin.php", "class-wp-error.php", "cron.php"):
                native += f"require __DIR__ . '/wordpress/wp-includes/{source}';\n"
                reference += f"require __DIR__ . '/../../../upstream/wordpress/wp-includes/{source}';\n"
        if name == "sql":
            contracts = (ROOT / "elephc/full-source/fixtures/aot-core-contracts.php").read_text()
            start = contracts.index("function wp_aot_require_wpdb(")
            native += contracts[start:contracts.index("\n}", start) + 2] + "\n"
            # This stand-in checks dispatch, separately from WordPress SQL algorithms.
            body = """function sql_probe_seed(mixed $value): mixed {return $value;}
function sql_probe_argument(bool $replace, mixed $replacement, mixed $seed): mixed {
    global $wpdb;
    if ($replace) $wpdb = $replacement;
    return $seed;
}
function sql_probe_nested(bool $replace, mixed $replacement, mixed $seed): mixed {
    global $wpdb;
    return RECEIVER->query(RECEIVER->prepare('format', RECEIVER->esc_like(sql_probe_argument($replace,$replacement,$seed))));
}
"""
            native += body.replace("RECEIVER", "wp_aot_require_wpdb($wpdb)")
            reference += body.replace("RECEIVER", "$wpdb")
        if name == "binary-strings":
            for contract_file, helper in (("aot-contracts.php", "wp_aot_require_string"), ("aot-contracts.php", "wp_aot_require_Throwable"), ("aot-core-contracts.php", "wp_aot_weak_string")):
                contracts = (ROOT / "elephc/full-source/fixtures" / contract_file).read_text()
                begin = contracts.index("function " + helper + "(")
                native += contracts[begin:contracts.index("\n}", begin) + 2] + "\n"
            iri_sources = ("Requests/src/Exception.php", "Requests/src/Exception/InvalidArgument.php", "Requests/src/Utility/InputValidator.php", "Requests/src/Port.php", "Requests/src/Ipv6.php", "Requests/src/Iri.php", "SimplePie/src/Net/IPv6.php", "SimplePie/src/IRI.php")
            for source in iri_sources:
                native += f"require_once __DIR__ . '/wordpress/wp-includes/{source}';\n"
                reference += f"require_once __DIR__ . '/../../../upstream/wordpress/wp-includes/{source}';\n"
            shared = "class WordPressRequestsIriByteProbe extends \\WpOrg\\Requests\\Iri {public function encode(mixed $value): mixed {return parent::to_uri($value);}}\n"
            native += shared
            reference += shared
        if name == "query-pagination":
            support = (ROOT / "tests/full-source/query-pagination-support.php").read_text().removeprefix("<?php")
            native += support + "require_once __DIR__ . '/wordpress/aot-arrays.php';\nrequire_once __DIR__ . '/wordpress/wp-includes/blocks/query.php';\n"
            reference += support + "require_once __DIR__ . '/../../../upstream/wordpress/wp-includes/blocks/query.php';\n"
        if name == "token-map":
            support = "function __(string $text): string {return $text;} function _doing_it_wrong(string $name,string $message,string $version): void {throw new LogicException($message);}\n"
            native += support + "require_once __DIR__ . '/wordpress/wp-includes/class-wp-token-map.php';\n"
            reference += support + "require_once __DIR__ . '/../../../upstream/wordpress/wp-includes/class-wp-token-map.php';\n"
        if name == "subdomain-constants":
            # Other functions remain in the complete original file but are outside this control.
            support = """define('ABSPATH', '/unused-subdomain-control/');
wp_aot_bootstrap_mark('WP_CONTENT_DIR', define('WP_CONTENT_DIR', '/unused-subdomain-control/wp-content'));
function add_filter(mixed ...$args): never {throw new LogicException('Upload filters are outside this control.');}
function get_site_option(mixed ...$args): never {throw new LogicException('Site options are outside this control.');}
function get_current_blog_id(): never {throw new LogicException('Blog lookup is outside this control.');}
function get_network(): mixed {throw new LogicException('Network lookup is outside this control.');}
function get_option(mixed ...$args): never {throw new LogicException('Options are outside this control.');}
function is_subdomain_install(): never {throw new LogicException('Network discovery is outside this control.');}
function __(string $text): string {return $text;}
function wp_trigger_error(string $name,string $message,int $level): void {
    global $subdomain_trace; $subdomain_trace[] = array($name,$message,$level);
}
function _deprecated_argument(string $name,string $version,string $message): void {
    global $subdomain_trace; $subdomain_trace[] = array($name,$version,$message);
}
"""
            native += support + "require_once __DIR__ . '/wordpress/wp-includes/ms-default-constants.php';\n"
            reference += support + "require_once __DIR__ . '/../../../upstream/wordpress/wp-includes/ms-default-constants.php';\n"
        if name == "filesystem-touch":
            literal = "'" + str(Path(filesystem_directory) / "timestamps.txt").replace("\\", "\\\\").replace("'", "\\'") + "'"
            shared = "define('TOUCH_TEST_FILE', " + literal + ");\n"
            native += shared + "function filesystem_touch_call(mixed $f,mixed $m,mixed $a): bool {return wp_aot_touch($f,$m,$a); } function filesystem_touch_cache(string $p): void {}\n"
            reference += shared + "function filesystem_touch_call(mixed $f,mixed $m,mixed $a): bool {return touch($f,$m,$a); } function filesystem_touch_cache(string $p): void {clearstatcache(true,$p); }\n"
        if name == "array-assoc":
            native += "function assoc_difference_call(array $a): array {return wp_aot_array_diff_assoc($a); }\n"
            reference += "function assoc_difference_call(array $a): array {return array_diff_assoc(...$a); }\n"
        if name == "walker":
            support = "function __(mixed $text): mixed {return $text;} function _doing_it_wrong(mixed $name,mixed $message,mixed $version): void {throw new LogicException((string)$message); }\n"
            native += support + "require_once __DIR__ . '/wordpress/wp-includes/class-wp-walker.php';\n"
            reference += support + "define('ABSPATH', __DIR__ . '/../../../upstream/wordpress/'); define('WPINC', 'wp-includes'); require ABSPATH . 'wp-includes/compat.php'; require ABSPATH . 'wp-includes/class-wp-walker.php';\n"
        if name == "glob":
            native += "function glob_call(string $p,int $f): array|false {return wp_aot_glob($p,$f); }\n"
            reference += "function glob_call(string $p,int $f): array|false {return glob($p,$f); }\n"
        if name == "weak-integer":
            float_body = "return array(is_float($value),$value===null?null:(is_nan($value)?'nan':(is_infinite($value)?($value>0?'inf':'-inf'):$value)));"
            native += "function nullable_float_call(mixed $input): array {$value=wp_aot_nullable_float($input);" + float_body + "}\n"
            reference += "function nullable_float_call(?float $value): array {" + float_body + "}\n"
            native += "function calendar_format_call(mixed $p,mixed $t): string {return wp_aot_gmdate($p,$t);}\n"
            reference += "function calendar_format_call(mixed $p,mixed $t): string {return gmdate($p,$t);}\n"
            native += "function calendar_call(bool $utc,array $p): mixed {return $utc ? gmmktime(wp_aot_calendar_component($p[0]),wp_aot_calendar_component($p[1]),wp_aot_calendar_component($p[2]),wp_aot_calendar_component($p[3]),wp_aot_calendar_component($p[4]),wp_aot_calendar_component($p[5])) : mktime(wp_aot_calendar_component($p[0]),wp_aot_calendar_component($p[1]),wp_aot_calendar_component($p[2]),wp_aot_calendar_component($p[3]),wp_aot_calendar_component($p[4]),wp_aot_calendar_component($p[5]));}\n"
            reference += "function calendar_call(bool $utc,array $p): mixed {return $utc ? gmmktime($p[0],$p[1],$p[2],$p[3],$p[4],$p[5]) : mktime($p[0],$p[1],$p[2],$p[3],$p[4],$p[5]);}\n"
            native += "function format_number_call(mixed $n,mixed $d,mixed $p,mixed $t): string {return wp_aot_number_format($n,$d,$p,$t);}\n"
            reference += "function format_number_call(mixed $n,mixed $d,mixed $p,mixed $t): string {return number_format($n,$d,$p,$t);}\n"
            native += "function integer_call(mixed $v): int {return wp_aot_internal_int($v); } function weak_integer_call(mixed $v): int {return wp_aot_weak_int($v); } function nullable_integer_call(mixed $v): ?int {return wp_aot_nullable_int($v); }\n"
            reference += "function integer_call(mixed $v): int {return intdiv($v,1); } function weak_integer_call(int $v): int {return $v; } function nullable_integer_call(?int $v): ?int {return $v; }\n"
        if name == "uniqid":
            native += "function format_uniqid_call(string $p,int $s,int $u,?int $e): string {return wp_aot_uniqid_format($p,$s,$u,$e); }\n"
            reference += "function format_uniqid_call(string $p,int $s,int $u,?int $e): string {$v=sprintf('%s%08x%05x',$p,$s & 0xffffffff,$u);if($e!==null)$v.=sprintf('%.8F',((float)$e/4294967295.0)*10.0);return $v;}\n"
        if name == "callable":
            shared_forward = "function theme_forward_target(mixed $feature,mixed ...$args): mixed {return array($feature,$args);}\n"
            native += shared_forward + "function theme_forward_call(array $arguments): mixed {return call_user_func_array('theme_forward_target',$arguments);}\n"
            reference += shared_forward + "function theme_forward_call(array $arguments): mixed {return theme_forward_target(...$arguments);}\n"
        if name == "property-cursor":
            native += "function lazy_attribute_exists(LazyAttributesControl $block,string $key): bool {return array_key_exists($key,wp_aot_array_input($block->attributes));}\n"
            reference += "function lazy_attribute_exists(LazyAttributesControl $block,string $key): bool {return array_key_exists($key,$block->attributes);}\n"
            shared_widgets = "require_once __DIR__ . '/../../../tests/full-source/widget-factory-cases.php';\n"
            native += shared_widgets + "require_once __DIR__ . '/wordpress/wp-includes/class-wp-widget-factory.php';\n"
            reference += shared_widgets + "require_once __DIR__ . '/../../../upstream/wordpress/wp-includes/class-wp-widget-factory.php';\n"
            admin_bar_boundary = ("_deprecated_argument", "_deprecated_function", "_doing_it_wrong", "_e", "current_theme_supports", "did_action", "do_action", "esc_attr", "esc_attr_e", "esc_js", "esc_url", "get_active_blog_for_user", "get_blogs_of_user", "get_current_blog_id", "get_current_user_id", "get_home_url", "get_theme_support", "home_url", "is_admin", "is_multisite", "is_network_admin", "is_user_admin", "is_user_logged_in", "sanitize_title", "trailingslashit", "user_admin_url", "wp_enqueue_script", "wp_enqueue_style", "wp_is_mobile")
            shared_bar = "".join(f"function {function}(mixed ...$arguments): {'array' if function in ('get_theme_support', 'get_blogs_of_user') else 'never'} {{throw new LogicException('Toolbar environment and rendering are outside this control.');}}\n" for function in admin_bar_boundary)
            shared_bar += "function wp_parse_args(mixed $args,mixed $defaults=array()): mixed {if(!is_array($args)||!is_array($defaults))throw new LogicException('Only array node defaults are supported by this control.');foreach($args as $key=>$value)$defaults[$key]=$value;return $defaults;}\n"
            native += shared_bar + "require_once __DIR__ . '/wordpress/wp-includes/class-wp-admin-bar.php';\n"
            reference += shared_bar + "require_once __DIR__ . '/../../../upstream/wordpress/wp-includes/class-wp-admin-bar.php';\n"
            shared_bar_cases = "require_once __DIR__ . '/../../../tests/full-source/admin-bar-cases.php';\n"
            native += shared_bar_cases
            reference += shared_bar_cases
            ability_support = "function __(mixed $text): mixed {return $text;}\nfunction esc_html(mixed ...$arguments): mixed {throw new LogicException('Invalid ability property diagnostics are outside this control.');}\nfunction apply_filters(mixed ...$arguments): mixed {throw new LogicException('Ability filters are outside this control.');}\nfunction rest_validate_value_from_schema(mixed ...$arguments): mixed {throw new LogicException('Ability schema execution is outside this control.');}\nfunction is_wp_error(mixed $value): bool {return $value instanceof WP_Error;}\n"
            native += ability_support
            reference += ability_support
            contracts = (ROOT / "elephc/full-source/fixtures/aot-contracts.php").read_text()
            for helper in ("wp_aot_require_string", "wp_aot_require_array"):
                begin = contracts.index("function " + helper + "(")
                native += contracts[begin:contracts.index("\n}", begin) + 2] + "\n"
            for source in ("class-wp-filter-sentinel.php", "class-wp-error.php", "abilities-api/class-wp-ability.php"):
                native += f"require_once __DIR__ . '/wordpress/wp-includes/{source}';\n"
                reference += f"require_once __DIR__ . '/../../../upstream/wordpress/wp-includes/{source}';\n"
            ability_cases = "require_once __DIR__ . '/../../../tests/full-source/ability-properties-cases.php';\n"
            native += ability_cases
            reference += ability_cases
            for source in ("SimplePie/src/RegistryAware.php", "SimplePie/src/Registry.php"):
                native += f"require_once __DIR__ . '/wordpress/wp-includes/{source}';\n"
                reference += f"require_once __DIR__ . '/../../../upstream/wordpress/wp-includes/{source}';\n"
            contracts = (ROOT / "elephc/full-source/fixtures/aot-core-contracts.php").read_text()
            begin = contracts.index("function wp_aot_require_SimplePie_Registry(")
            native += contracts[begin:contracts.index("\n}", begin) + 2] + "\n"
            reference += "function wp_aot_require_SimplePie_Registry(mixed $value): \\SimplePie\\Registry {return $value;}\n"
            registry_shared = r"""class WordPressRegistryProbe extends \SimplePie\Registry {
    public string $marker;
    public function __construct(string $marker){$this->marker=$marker;}
    public function &create(mixed $type,array $parameters=[]): mixed {$result=(object)array('kind'=>'create','marker'=>$this->marker,'type'=>$type,'parameters'=>$parameters,'class'=>get_class($this));return $result;}
    public function &call(mixed $type,string $method,array $parameters=[]): mixed {$result=array('kind'=>'call','marker'=>$this->marker,'type'=>$type,'method'=>$method,'parameters'=>$parameters,'class'=>get_class($this));return $result;}
}
class WordPressInheritedRegistryProbe extends WordPressRegistryProbe {}
class WordPressOtherRegistryProbe extends WordPressRegistryProbe {
    public function &create(mixed $type,array $parameters=[]): mixed {$result=(object)array('kind'=>'other-create','marker'=>$this->marker,'type'=>$type,'parameters'=>$parameters,'class'=>get_class($this));return $result;}
}
class WordPressRegistryHolder {public mixed $registry;public function __construct(\SimplePie\Registry $registry){$this->registry=$registry;}}
function registry_probe_argument(WordPressRegistryHolder $holder,\SimplePie\Registry $next,array $parameters): array {$holder->registry=$next;return $parameters;}
function registry_probe_create(WordPressRegistryHolder $holder,\SimplePie\Registry $next,array $parameters): mixed {return wp_aot_require_SimplePie_Registry($holder->registry)->create('probe/type',registry_probe_argument($holder,$next,$parameters));}
function registry_probe_call(WordPressRegistryHolder $holder,\SimplePie\Registry $next,array $parameters): mixed {return wp_aot_require_SimplePie_Registry($holder->registry)->call('probe/type','probe_method',registry_probe_argument($holder,$next,$parameters));}
"""
            native += registry_shared
            reference += registry_shared
            support = "function get_temp_dir(): string {throw new LogicException('Temporary diff engines are outside this control.');}\n"
            native += support + "require_once __DIR__ . '/wordpress/wp-includes/Text/Exception.php';\nrequire_once __DIR__ . '/wordpress/wp-includes/Text/Diff.php';\nrequire_once __DIR__ . '/wordpress/wp-includes/Text/Diff/Renderer.php';\n"
            reference += support + "require_once __DIR__ . '/../../../upstream/wordpress/wp-includes/Text/Exception.php';\nrequire_once __DIR__ . '/../../../upstream/wordpress/wp-includes/Text/Diff.php';\nrequire_once __DIR__ . '/../../../upstream/wordpress/wp-includes/Text/Diff/Renderer.php';\n"
            contracts = (ROOT / "elephc/full-source/fixtures/aot-core-contracts.php").read_text()
            begin = contracts.index("function wp_aot_require_Text_Diff_Op(")
            native += contracts[begin:contracts.index("\n}", begin) + 2] + "\n"
            sidebar_body = """function sidebar_reference_seed(mixed $value): mixed {return $value;}
function sidebar_reference_call(bool $admin,mixed $fallback,string $base): array {
    if ($admin) {$sidebar_values=$fallback;} else {
        BINDGLOBAL
        if(empty(CACHEVALUE)) CACHEVALUE=$fallback;
        $sidebar_values =& CACHEVALUE;
    }
    $sidebar_saved=$sidebar_values;$changed=false;
    foreach((array)$sidebar_values as $index=>$sidebar) {
        if(is_array($sidebar)) foreach((array)$sidebar as $i=>$name) {
            if($base===$name){$sidebar_values[$index][$i]="$name-2";$changed=true;break 2;}
        }
    }
    return array($sidebar_saved,$sidebar_values,$changed,sidebar_reference_value());
}
"""
            native += "function sidebar_reference_value(): mixed {global $_wp_sidebars_widgets;return $_wp_sidebars_widgets;}\n" + sidebar_body.replace("BINDGLOBAL", "global $_wp_sidebars_widgets;").replace("CACHEVALUE", "$_wp_sidebars_widgets")
            reference += "function sidebar_reference_value(): mixed {return $GLOBALS['_wp_sidebars_widgets'];}\n" + sidebar_body.replace("BINDGLOBAL", "").replace("CACHEVALUE", "$GLOBALS['_wp_sidebars_widgets']")
            contracts = (ROOT / "elephc/full-source/fixtures/aot-core-contracts.php").read_text()
            for helper in ("wp_aot_bookmark_link_is_set", "wp_aot_bookmark_link_value"):
                begin = contracts.index("function " + helper + "(")
                shared = contracts[begin:contracts.index("\n}", begin) + 2] + "\n"
                native += shared
                reference += shared
            shared = """function global_reference_rebind(mixed $value): void {global $link; $link=$value;}
function global_reference_call(mixed $replacement): array {
    if (!wp_aot_bookmark_link_is_set()) return array('absent',wp_aot_bookmark_link_is_set());
    BINDGLOBAL
    $before=$alias;
    $alias=$replacement;
    $first=wp_aot_bookmark_link_value();
    global_reference_rebind('rebound');
    return array($before,$first,$alias,wp_aot_bookmark_link_value());
}
"""
            native += shared.replace("BINDGLOBAL", "global $link; $alias =& $link;")
            reference += shared.replace("BINDGLOBAL", "$alias =& $GLOBALS['link'];")
            native += "class CursorQueueState {public mixed $queue=array();}\nfunction queue_prepend_call(CursorQueueState $holder,array $values): int {return wp_aot_array_unshift($holder->queue,$values);}\n"
            reference += "class CursorQueueState {public $queue=array();}\nfunction queue_prepend_call(CursorQueueState $holder,array $values): int {return array_unshift($holder->queue,...$values);}\n"
            native += "#[AllowDynamicProperties] class CursorQueryReferenceView {public mixed $comments = null; public mixed $comments_by_type = null;}\n"
            reference += "#[AllowDynamicProperties] class CursorQueryReferenceView {public $comments;}\n"
            shared = "function query_reference_write_call(mixed &$value, mixed $replacement): void {$value=$replacement;}\n"
            native += shared
            reference += shared
            native += "function query_reference_view_call(mixed $value): CursorQueryReferenceView {if ($value instanceof CursorQueryReferenceView) return $value; throw new TypeError('Expected query object.');}\n"
            reference += "function query_reference_view_call(mixed $value): mixed {return $value;}\n"
            native += "function record_unset_call(mixed $record,string $name): stdClass {$typed=wp_aot_json_record($record); unset($typed->$name); return $typed;}\n"
            reference += "function record_unset_call(mixed $record,string $name): stdClass {unset($record->$name); return $record;}\n"
            late_static_cache = """class LateStaticCacheControl {
    public static mixed $files=array();
    protected static mixed $blocks=array('core'=>array(),'theme'=>array());
    public static function reset(array $files): void {static::$files=$files;static::$blocks=array('core'=>array(),'theme'=>array());}
    public static function write(mixed $key,mixed $value): void {FILEWRITE}
    public static function mark(string $origin,array $names): bool {
        if(!isset(static::$blocks[$origin])) return false;
        BLOCKWRITE
        return true;
    }
    public static function state(): array {return array(static::$files,static::$blocks);}
    public static function read(mixed $key): mixed {return array_key_exists($key,static::$files)?static::$files[$key]:array();}
}
class InheritedLateStaticCacheControl extends LateStaticCacheControl {}
class IsolatedLateStaticCacheControl extends LateStaticCacheControl {
    public static mixed $files=array();
    protected static mixed $blocks=array('core'=>array(),'theme'=>array());
}
function late_static_cache_eval_policy(string $program): mixed {return eval($program);}
"""
            native += late_static_cache.replace("FILEWRITE", "$files=wp_aot_mixed(static::$files);$files[$key]=$value;static::$files=$files;").replace("BLOCKWRITE", "$blocks=wp_aot_mixed(static::$blocks);foreach($names as $name)$blocks[$origin][$name]=true;static::$blocks=$blocks;").replace("return array(static::$files,static::$blocks);", "return array(wp_aot_mixed(static::$files),wp_aot_mixed(static::$blocks));")
            reference += late_static_cache.replace("FILEWRITE", "static::$files[$key]=$value;").replace("BLOCKWRITE", "foreach($names as $name)static::$blocks[$origin][$name]=true;")
            native += "function scalar_cache_call(mixed $v,bool $write): array {static $cell = new WordPressStaticStringCell(); $cached =& $cell->value; $before=$cached; if($write) $cached=$v; return array($before,$cached);}\n"
            reference += "function scalar_cache_call(mixed $v,bool $write): array {static $cached=''; $before=$cached; if($write) $cached=$v; return array($before,$cached);}\n"
            nullable_body = "function nullable_cache_call(mixed $v,bool $write): array {NULLDECL $cached_before=$cached; if($write) $cached=$v; return array(isset($cached),$cached_before,$cached);}\n"
            native += nullable_body.replace("NULLDECL", "static $cell = new WordPressStaticValueCell(); $cached =& $cell->value;")
            reference += nullable_body.replace("NULLDECL", "static $cached;")
            literal_body = """function nullable_literal_cache_call(int $op): array {
    NULLDECL
    if ($op === 1) $cached = array('first','second');
    elseif ($op === 2) $cached = 'literal';
    elseif ($op === 3) $cached = null;
    elseif ($op === 4) $cached = array(array('nested'=>1));
    elseif ($op === 5) $cached = true;
    elseif ($op === 6) $cached = 42;
    elseif ($op === 7) { unset($cached); return array(isset($cached),'unset-marker'); }
    return array(isset($cached),LITERALVALUE);
}
"""
            native += literal_body.replace("NULLDECL", "static $cell = new WordPressStaticValueCell(); $cached =& $cell->value;").replace("LITERALVALUE", "wp_aot_mixed($cached)")
            reference += literal_body.replace("NULLDECL", "static $cached;").replace("LITERALVALUE", "$cached")
            false_body = """function false_literal_cache_call(int $op): array {
    FALSEDECL
    if ($op === 1) $cached = 'random_int';
    elseif ($op === 2) $cached = 'mt_rand';
    elseif ($op === 3) $cached = false;
    elseif ($op === 4) $cached = array('entry'=>'value');
    return array(isset($cached),$cached);
}
"""
            native += false_body.replace("FALSEDECL", "static $cell = new WordPressStaticFalseCell(); $cached =& $cell->value;")
            reference += false_body.replace("FALSEDECL", "static $cached=false;")
            singleton_body = """class SingletonCacheValue {public int $visits=0;}
function singleton_cache_call(bool $reset): SingletonCacheValue {
    NULLDECL
    if ($reset) $cached=null;
    if ($cached===null) $cached=new SingletonCacheValue();
    $instance=$cached;
    if (!($instance instanceof SingletonCacheValue)) throw new TypeError('Expected a singleton cache value.');
    return $instance;
}
"""
            native += singleton_body.replace("NULLDECL", "static $cell = new WordPressStaticValueCell(); $cached =& $cell->value;")
            reference += singleton_body.replace("NULLDECL", "static $cached;")
            cache_body = """function array_cache_call(string $op,mixed $key,mixed $value): array {
    CACHEDECL
    $result = null;
    if ($op === 'clear') $cache = array();
    elseif ($op === 'set') $cache[$key] = $value;
    elseif ($op === 'append') CACHEAPPEND
    elseif ($op === 'unset') unset($cache[$key]);
    elseif ($op === 'pop') $result = CACHEPOP($cache);
    elseif ($op === 'read') $result = array(isset($cache[$key]),array_key_exists($key,(array)$cache),$cache[$key] ?? 'absent');
    return array($result,$cache);
}
"""
            native += cache_body.replace("CACHEDECL", "static $cell = new WordPressStaticArrayCell(); $cache =& $cell->value;").replace("CACHEPOP", "wp_aot_array_pop").replace("CACHEAPPEND", "wp_aot_array_push($cache,array($value));")
            reference += cache_body.replace("CACHEDECL", "static $cache = array();").replace("CACHEPOP", "array_pop").replace("CACHEAPPEND", "$cache[] = $value;")
            body = "function cursor_current_call(mixed $v): mixed {return CALLCURRENT($v);}\nfunction cursor_reset_call(mixed &$v): mixed {return CALLRESET($v);}\nfunction cursor_iterator_call(mixed $v,bool $p): array {return CALLITERATOR($v,$p);}\nfunction cursor_length_call(mixed $v): int {return CALLLENGTH($v);}\n"
            native += body.replace("CALLCURRENT", "wp_aot_array_current_value").replace("CALLRESET", "wp_aot_array_reset_property").replace("CALLITERATOR", "wp_aot_iterator_values").replace("CALLLENGTH", "wp_aot_strlen")
            reference += body.replace("CALLCURRENT", "current").replace("CALLRESET", "reset").replace("CALLITERATOR", "iterator_to_array").replace("CALLLENGTH", "strlen")
        if name == "stream-read":
            literal = "'" + str(Path(filesystem_directory) / "pomo.bin").replace("\\", "\\\\").replace("'", "\\'") + "'"
            shared = "define('POMO_TEST_FILE', " + literal + ");\nfunction _deprecated_constructor(mixed ...$arguments): never {throw new LogicException('Legacy POMO constructor calls are outside this control.');}\n"
            native += shared + "require_once __DIR__ . '/wordpress/wp-includes/pomo/streams.php';\n"
            reference += shared + "require_once __DIR__ . '/../../../upstream/wordpress/wp-includes/pomo/streams.php';\n"
            native += "require_once __DIR__ . '/wordpress/wp-includes/class-avif-info.php';\n"
            reference += "require_once __DIR__ . '/../../../upstream/wordpress/wp-includes/class-avif-info.php';\n"
            shared = "$pclzip_definition_trace = array(wp_aot_pclzip_block_size_defined());\n$pclzip_size = $argv[1] ?? null;\nif ($pclzip_size !== null) wp_aot_pclzip_block_size_define((int)$pclzip_size);\n$pclzip_definition_trace[] = wp_aot_pclzip_block_size_defined();\n"
            native += shared + "require_once __DIR__ . '/wordpress/wp-admin/includes/class-pclzip.php';\n"
            reference += shared + "require_once __DIR__ . '/../../../upstream/wordpress/wp-admin/includes/class-pclzip.php';\n"
            shared = "$pclzip_definition_trace[] = wp_aot_pclzip_block_size_defined();\n$pclzip_definition_trace[] = wp_aot_pclzip_block_size();\n"
            native += shared
            reference += shared
            body = "function read_line_call(mixed $s,?int $l): mixed {return TARGETfgets($s,$l);}\nfunction read_bytes_call(mixed $s,int $l): mixed {return TARGETfread($s,$l);}\nfunction close_call(mixed $s): bool {return TARGETfclose($s);}\n"
            native += body.replace("TARGET", "wp_aot_")
            reference += body.replace("TARGET", "")
        if name == "array-extra":
            native += "function extra_flip(mixed $a): array {return wp_aot_array_flip($a);}\n"
            reference += "function extra_flip(mixed $a): array {return array_flip($a);}\n"
            body = "function extra_column(mixed $a,mixed $c,mixed $i): array {return TARGETarray_column($a,$c,$i);}\nfunction extra_combine(mixed $a,mixed $b): array {return TARGETarray_combine($a,$b);}\nfunction extra_count(mixed $a): int {return TARGETcount($a);}\nfunction extra_shuffle(mixed &$a): bool {return TARGETshuffle($a);}\nfunction extra_sort(string $op,mixed &$a): bool {return $op==='natural'?TARGETnatsort($a):TARGETnatcasesort($a);}\nfunction extra_compare(mixed $a,mixed $b): int {return TARGETphp_compare($a,$b);}\n"
            native += body.replace("TARGET", "wp_aot_")
            reference += "function php_compare(mixed $a,mixed $b): int {return $a<=>$b;}\n" + body.replace("TARGET", "")
            native += "function extra_keys(mixed $a,mixed $n,bool $s,bool $f): array {return wp_aot_array_keys($a,$n,$s,$f);}\n"
            reference += "function extra_keys(mixed $a,mixed $n,bool $s,bool $f): array {return $f?array_keys($a,$n,$s):array_keys($a);}\n"
        if name == "regex-replace":
            body = "function regex_replace_call(mixed $p,mixed $r,mixed $s,int $l,mixed &$c): mixed {return TARGETpreg_replace($p,$r,$s,$l,$c); }\nfunction regex_callback_call(mixed $p,mixed $r,mixed $s,int $l,mixed &$c,int $f): mixed {return TARGETpreg_replace_callback($p,$r,$s,$l,$c,$f); }\n"
            native += body.replace("TARGET", "wp_aot_")
            reference += body.replace("TARGET", "")
        if name == "dynamic-primitives":
            native += "function aot_test_union(mixed $a, mixed $b): mixed {return wp_aot_value_array_union($a,$b);}\nfunction aot_test_xor(string $a,string $b): string {return wp_aot_string_xor($a,$b);}\nfunction aot_test_translate(mixed $a,mixed $b,mixed $c=null): string {return wp_aot_strtr($a,$b,$c);}\n"
            reference += "function aot_test_union(mixed $a,mixed $b): mixed {return $a+$b;}\nfunction aot_test_xor(string $a,string $b): string {return $a^$b;}\nfunction aot_test_translate(mixed $a,mixed $b,mixed $c=null): string {return is_array($b)?strtr($a,$b):strtr($a,$b,$c);}\n"
        if name == "html-entities":
            shared = "require_once __DIR__ . '/wordpress/aot-entities.php';\n"
            native += shared
            reference += shared
            body = "function entity_decode_call(string $text, int $flags, string $encoding): string { return CALL($text, $flags, $encoding); }\n"
            native += body.replace("CALL", "wp_aot_html_entity_decode")
            reference += body.replace("CALL", "html_entity_decode")
            body = "function entity_escape_call(string $text, int $flags, string $encoding, bool $double): string { return CALL($text, $flags, $encoding, $double); }\n"
            native += body.replace("CALL", "wp_aot_htmlspecialchars")
            reference += body.replace("CALL", "htmlspecialchars")
        if name == "callable":
            native += "function editor_method_call(mixed $editor): bool {return is_callable(array($editor,'make_subsize'));}\n"
            reference += "function editor_method_call(mixed $editor): bool {return method_exists($editor,'make_subsize');}\n"
        if name == "regex-offset":
            native += "function regex_split_options(string $pattern,string $subject,mixed $limit,mixed $flags): mixed {return wp_aot_preg_split($pattern,$subject,$limit,$flags);}\n"
            reference += "function regex_split_options(string $pattern,string $subject,mixed $limit,mixed $flags): mixed {return preg_split($pattern,$subject,$limit,$flags);}\n"
            native += "function regex_subject_call(string $pattern,mixed $subject): int|false {return preg_match($pattern,wp_aot_regex_text($subject));}\n"
            reference += "function regex_subject_call(string $pattern,mixed $subject): int|false {return preg_match($pattern,$subject);}\n"
            body = "function probe_match(string $pattern, string $subject, mixed &$matches, int $flags, int $offset): int|false { return CALL($pattern, $subject, $matches, $flags, $offset); }\n"
            native += body.replace("CALL", "wp_aot_preg_match")
            reference += body.replace("CALL", "preg_match")
        if name == "introspection":
            property_meta = json.loads((ROOT / "elephc/full-source/property-provenance.json").read_text())
            observed_properties = json.loads(run([php, str(ROOT / "tests/full-source/property-metadata-reference.php"), "--inventory"]))
            if observed_properties != property_meta["classes"]:
                raise SystemExit("Property inventory differs from original complete PHP classes")
            property_quote = lambda text: "'" + text.replace("\\", "\\\\").replace("'", "\\'") + "'"
            for property_class, info in property_meta["classes"].items():
                native += "#[AllowDynamicProperties] class " + property_class + " {\n"
                for property_name, flags in info["properties"].items():
                    native += flags["visibility"] + (" static" if flags["static"] else "") + " mixed $" + property_name + "=null;\n"
                native += "public function aot_meta_unset(string $name): void {unset($this->$name); }\n}\n"
            native += "function property_fixture_objects(): array {return array(" + ",".join(property_quote(c) + "=>new " + c + "()" for c in property_meta["classes"]) + ");}\n"
            property_names = sorted({n for info in property_meta["classes"].values() for n in info["properties"]}) + ["absent", "é汉", "", "name.case"]
            native += "function property_fixture_names(): array {return array(" + ",".join(property_quote(n) for n in property_names) + ");}\n"
            property_fields = ",".join(property_quote(c) + "=>array(" + ",".join(property_quote(n) for n, flags in info["properties"].items() if not flags["static"]) + ")" for c, info in property_meta["classes"].items())
            native += "function property_fixture_fields(string $class): array {$fields=array(" + property_fields + ");return (array)$fields[$class];}\n"
            native += "function property_fixture_unset(mixed $object,string $name): void {$object->aot_meta_unset($name); }\n"
            reference += "require_once __DIR__ . '/../../../tests/full-source/property-metadata-reference.php';\n"
            native += "require_once __DIR__ . '/wordpress/wp-includes/Requests/src/Utility/InputValidator.php';\n"
            reference += "require_once __DIR__ . '/../../../upstream/wordpress/wp-includes/Requests/src/Utility/InputValidator.php';\n"
            for operation in ("defined", "constant", "class_exists", "interface_exists", "function_exists", "extension_loaded"):
                signature = "string $name, bool $autoload = true" if operation in ("class_exists", "interface_exists") else "string $name"
                arguments = "$name, $autoload" if operation in ("class_exists", "interface_exists") else "$name"
                target = "wp_aot_metadata_" + operation if operation in ("defined", "constant", "class_exists", "interface_exists") else "wp_aot_" + operation
                native += f"function probe_{operation}({signature}): mixed {{ return {target}({arguments}); }}\n"
                reference += f"function probe_{operation}({signature}): mixed {{ return {operation}({arguments}); }}\n"
        if name in ("block-parser", "ports"):
            sources = ("class-wp-block-parser-block.php", "class-wp-block-parser-frame.php", "class-wp-block-parser.php") if name == "block-parser" else ("Requests/src/Exception.php", "Requests/src/Exception/InvalidArgument.php", "Requests/src/Port.php")
            for source in sources:
                native += f"require_once __DIR__ . '/wordpress/wp-includes/{source}';\n"
                reference += f"require_once __DIR__ . '/../../../upstream/wordpress/wp-includes/{source}';\n"
        if name == "ports":
            constructor_fixture = r"""<?php
namespace WpOrg\Requests\Response {final class Headers {public function __construct(){throw new \LogicException('Response header construction is outside this control.');}}}
namespace WpOrg\Requests\Cookie {final class Jar {public function __construct(){throw new \LogicException('Cookie construction is outside this control.');}}}
"""
            (OUT / "control-response-constructor-boundaries.php").write_text(constructor_fixture)
            shared = "require_once __DIR__ . '/control-response-constructor-boundaries.php';\n"
            native += shared
            reference += shared
            response_sources = ("Requests/src/Exception/Http.php",) + tuple("Requests/src/Exception/Http/" + p.name for p in sorted((ROOT / "upstream/wordpress/wp-includes/Requests/src/Exception/Http").glob("*.php"))) + ("Requests/src/Response.php",)
            for source in response_sources:
                native += f"require_once __DIR__ . '/wordpress/wp-includes/{source}';\n"
                reference += f"require_once __DIR__ . '/../../../upstream/wordpress/wp-includes/{source}';\n"
            contracts = (ROOT / "elephc/full-source/fixtures/aot-contracts.php").read_text()
            begin = contracts.index("function wp_aot_require_Throwable(")
            native += contracts[begin:contracts.index("\n}", begin) + 2] + "\n"
            shared = "function response_probe_mixed(mixed $value): mixed {return $value;}\nfunction response_probe_instance(mixed $value): \\WpOrg\\Requests\\Response {if($value instanceof \\WpOrg\\Requests\\Response)return $value;throw new TypeError('Expected the response instance.');}\n"
            native += shared
            reference += shared
        if name == "array-merge-recursive":
            for operation, builtin in (("reverse", "array_reverse"), ("case", "array_change_key_case"), ("fill", "array_fill_keys")):
                second = "bool $b" if operation == "reverse" else ("int $b" if operation == "case" else "mixed $b")
                native += f"function more_{operation}(mixed $a, {second}): array {{ return wp_aot_{builtin}($a, $b); }}\n"
                reference += f"function more_{operation}(mixed $a, {second}): array {{ return {builtin}($a, $b); }}\n"
            for operation, builtin in (("replace", "array_replace"), ("recursive", "array_replace_recursive"), ("merge", "array_merge_recursive")):
                native += f"function more_{operation}(array $arrays): array {{ return wp_aot_{builtin}($arrays); }}\n"
                reference += f"function more_{operation}(array $arrays): array {{ return {builtin}(...$arrays); }}\n"
        if name == "ai":
            for fixture in ("aot-arrays.php", "aot-contracts.php", "aot-ai.php"):
                native += f"require_once __DIR__ . '/wordpress/{fixture}';\n"
            native += "require_once __DIR__ . '/wordpress/wp-includes/php-ai-client/autoload.php';\n"
            reference += "require __DIR__ . '/wordpress/aot-contracts.php';\n"
            reference += "require __DIR__ . '/../../../upstream/wordpress/wp-includes/php-ai-client/autoload.php';\n"
        if name == "compact":
            scope = """function aot_compact_scope(array $names): array {
    $first = 'alpha'; $second = 42; $nothing = null; $zero = 0; $flag = false; $empty = '';
    $list = array('nested' => array(1, null, false));
    $record = new stdClass(); $record->label = 'object';
    $alias =& $first;
    $result = COLLECT;
    $keys = array_keys($result);
    if (array_key_exists('first', $result)) $result['first'] = 'result-only';
    $record->marker = 'shared';
    return array($result, $keys, $first, $alias);
}
"""
            native += scope.replace("COLLECT", "wp_aot_compact(get_defined_vars(), $names)")
            reference += scope.replace("COLLECT", "compact(...$names)")
        if name in ("array-cursor", "array-calls", "array-values", "array-sort", "array-callback", "array-more", "array-mutation", "query", "compact"):
            native += "require_once __DIR__ . '/wordpress/aot-arrays.php';\n"
        if name == "query":
            native += "function aot_test_token(string $string, ?string $delimiters = null): mixed { return wp_aot_strtok($string, $delimiters); }\n"
            reference += "function aot_test_token(string $string, ?string $delimiters = null): mixed { return $delimiters === null ? strtok($string) : strtok($string, $delimiters); }\n"
            reference += "require __DIR__ . '/wordpress/aot-query.php';\n"
            native += "function aot_test_parse(string $query, mixed &$result): void { parse_str($query, $result); }\n"
            reference += "function aot_test_parse(string $query, mixed &$result): void { $result = wp_aot_parse_query($query); }\n"
        if name == "headers":
            for source in ("Requests/src/Exception.php", "Requests/src/Utility/CaseInsensitiveDictionary.php"):
                native += f"require_once __DIR__ . '/wordpress/wp-includes/{source}';\n"
                reference += f"require __DIR__ . '/../../../upstream/wordpress/wp-includes/{source}';\n"
        if name == "array-mutation":
            native += "function theme_record_remove(mixed $theme): stdClass {$record=wp_aot_json_record($theme);unset($record->slug,$record->screenshot_url,$record->author);return $record;}\n"
            reference += "function theme_record_remove(mixed $theme): stdClass {unset($theme->slug,$theme->screenshot_url,$theme->author);return $theme;}\n"
            splice_call = "function mutation_splice_call(mixed &$values,int $offset,?int $length,stdClass $replacement,int $count): mixed {$items=array();for($i=0;$i<$count;++$i)$items[]=ITEM;if($count===0)return SPLICE($values,$offset,$length);return SPLICE($values,$offset,$length,$items);}\n"
            nested_splice = "function mutation_nested_splice(mixed $outer,int $offset,?int $length,stdClass $replacement,int $count): mixed {$items=array();for($i=0;$i<$count;++$i)$items[]=ITEM;if($count===0)SPLICE($outer['auto_add'],$offset,$length);else SPLICE($outer['auto_add'],$offset,$length,$items);return $outer;}\n"
            for splice_body in (splice_call, nested_splice):
                native += splice_body.replace("ITEM", "wp_aot_mixed($replacement)").replace("SPLICE", "wp_aot_array_splice_values")
                reference += splice_body.replace("ITEM", "$replacement").replace("SPLICE", "array_splice")
            connector_state = "class MutationConnectorRegistry {public bool $active=false;public function hasProvider(string $id): bool {if($id==='throws')throw new Exception('provider failure');return $this->active && $id==='present';}}\n"
            connector_callback = "function mutation_connector_callback(array $args,MutationConnectorRegistry $ai_registry,string $id): array {$args['plugin']['is_active']=VALUE;return $args;}\n"
            callback_value = "static function () use ($ai_registry,$id): bool {try{return $ai_registry->hasProvider($id);}catch(Exception $e){return false;}}"
            native += connector_state + connector_callback.replace("VALUE", "wp_aot_mixed(" + callback_value + ")")
            reference += connector_state + connector_callback.replace("VALUE", callback_value)
            native += "function mutation_unset_key(mixed $values,mixed $key): array {return wp_aot_array_without_key($values,$key);}\n"
            reference += "function mutation_unset_key(mixed $values,mixed $key): array {$original=(array)$values;unset($original[$key]);return $original;}\n"
            fixture = (ROOT / "elephc/full-source/fixtures/aot-probe.php").read_text()
            begin = fixture.index("function wp_aot_post_support_cases(")
            support_cases = fixture[begin:fixture.index("\n}\n", begin) + 3]
            native += support_cases + "function post_support_remove(mixed $post,mixed $feature): mixed {global $_wp_post_type_features;if(isset($_wp_post_type_features[$post]))$_wp_post_type_features[$post]=wp_aot_array_without_key($_wp_post_type_features[$post],$feature);return null;}\n"
            reference += "require_once __DIR__ . '/wordpress/aot-arrays.php';\n" + support_cases + "function post_support_remove(mixed $post,mixed $feature): mixed {global $_wp_post_type_features;unset($_wp_post_type_features[$post][$feature]);return null;}\n"
            native += "function mutation_mixed(mixed $value): mixed {return wp_aot_mixed($value);}\nfunction mutation_record(mixed $value): stdClass {return wp_aot_json_record($value);}\nfunction walk_holder(stdClass $holder,string $property,callable $walk_callable): bool {$walk_values=wp_aot_mixed($holder->$property);try{return array_walk($walk_values,$walk_callable);}finally{$holder->$property=$walk_values;}}\n"
            reference += "function mutation_mixed(mixed $value): mixed {return $value;}\nfunction mutation_record(mixed $value): stdClass {return $value;}\nfunction walk_holder(stdClass $holder,string $property,callable $walk_callable): bool {return array_walk($holder->$property,$walk_callable);}\n"
            nullable_append = """function nullable_append_call(array $items): array {
    NULLAPPENDINIT
    $initial=$list;$alias =& $list;$history=array();
    foreach($items as $item){$before=$list;$list[]=$item;$history[]=array($before,$list,$alias);}
    $parent=$list;$child=$parent;$child[]='child';
    return array($initial,$list,$alias,$parent,$child,$history);
}
"""
            native += nullable_append.replace("NULLAPPENDINIT", "$list=wp_aot_mixed(null);").replace("$list[]=$item;", "wp_aot_nullable_array_append($list,$item);").replace("$child[]='child';", "wp_aot_nullable_array_append($child,'child');")
            reference += nullable_append.replace("NULLAPPENDINIT", "$list=null;")
            native += "function nullable_array_write(bool $write,mixed $key,mixed $value): mixed {$errors=wp_aot_mixed(null); if($write) $errors[$key]=$value; return $errors;}\n"
            reference += "function nullable_array_write(bool $write,mixed $key,mixed $value): mixed {$errors=null; if($write) $errors[$key]=$value; return $errors;}\n"
            body = "function mutation_call(string $operation, mixed &$array, array $values): mixed { switch ($operation) {case 'push': return array_push($array, ...$values);case 'unshift': return array_unshift($array, ...$values);case 'shift': return array_shift($array);case 'pop': return array_pop($array);default: throw new LogicException();}}\n"
            reference += body
            native += body.replace("array_push($array, ...$values)", "wp_aot_array_push($array, $values)").replace("array_unshift($array, ...$values)", "wp_aot_array_unshift($array, $values)").replace("array_shift($array)", "wp_aot_array_shift($array)").replace("array_pop($array)", "wp_aot_array_pop($array)")
            shape = "function upgrade_shape(bool $legacy,mixed $key,string $taxonomy,int $value): mixed {$ids=array(); $ids[$key][$taxonomy]=$value; $snapshot=$ids; $first=READ[$key][$taxonomy]; if($legacy) {$ids=array(); $ids[$key]=$value+1; $second=$ids[$key];} else {$ids[$key][$taxonomy]=$value+2; $second=READ[$key][$taxonomy];} return array($first,$second,$ids,$snapshot); }\n"
            reference += shape.replace("READ", "$ids")
            native += shape.replace("READ", "wp_aot_mixed($ids)")
        if name == "array-callback":
            body = "function callback_sort_call(string $operation, mixed &$array, mixed $callback): bool { switch ($operation) {"
            for operation in ("usort", "uasort", "uksort"):
                body += f"case '{operation}': return CALL{operation}($array, $callback);"
            body += "default: throw new LogicException(); } }\n"
            native += body.replace("CALL", "wp_aot_")
            reference += body.replace("CALL", "")
        if name == "array-more":
            for operation, builtin in (("reverse", "array_reverse"), ("case", "array_change_key_case"), ("fill", "array_fill_keys")):
                second = "bool $b" if operation == "reverse" else ("int $b" if operation == "case" else "mixed $b")
                body = f"function more_{operation}(mixed $a, {second}): array {{ return CALL($a, $b); }}\n"
                native += body.replace("CALL", "wp_aot_" + builtin)
                reference += body.replace("CALL", builtin)
            for operation, builtin in (("replace", "array_replace"), ("recursive", "array_replace_recursive")):
                native += f"function more_{operation}(array $arrays): array {{ return wp_aot_{builtin}($arrays); }}\n"
                reference += f"function more_{operation}(array $arrays): array {{ return {builtin}(...$arrays); }}\n"
        if name == "array-sort":
            body = "function sort_call(string $operation, mixed &$array, int $flags): bool { switch ($operation) {"
            for operation in ("sort", "rsort", "asort", "arsort", "ksort", "krsort"):
                body += f"case '{operation}': return CALL{operation}($array, $flags);"
            body += "default: throw new LogicException(); } }\n"
            native += body.replace("CALL", "wp_aot_")
            reference += body.replace("CALL", "")
        if name == "array-values":
            native += "function av_unique(array $a, int $f): array { return wp_aot_array_unique($a, $f); }\n"
            native += "function av_search(mixed $needle, array $a, bool $strict = false): mixed { return wp_aot_array_search($needle, $a, $strict); }\n"
            native += "function av_slice(array $a, int $offset, ?int $length, bool $preserve): array { return wp_aot_array_slice($a, $offset, $length, $preserve); }\n"
            reference += "function av_unique(array $a, int $f): array { return array_unique($a, $f); }\n"
            reference += "function av_search(mixed $needle, array $a, bool $strict = false): mixed { return array_search($needle, $a, $strict); }\n"
            reference += "function av_slice(array $a, int $offset, ?int $length, bool $preserve): array { return array_slice($a, $offset, $length, $preserve); }\n"
            for operation in ("diff", "intersect", "diff_key", "intersect_key"):
                native += f"function av_{operation}(array $a): array {{ return wp_aot_array_{operation}($a); }}\n"
                reference += f"function av_{operation}(array $a): array {{ return array_{operation}(...$a); }}\n"
        if name == "array-calls":
            native += "function aot_test_merge(array $arrays): array { return wp_aot_array_merge($arrays); }\n"
            native += "function aot_test_map(mixed $callback, array $array, array ...$others): array { return wp_aot_array_map($callback, $array, ...$others); }\n"
            native += "function aot_test_filter(array $array, mixed $callback = null, int $mode = 0): array { return wp_aot_array_filter($array, $callback, $mode); }\n"
            reference += "function aot_test_merge(array $arrays): array { return array_merge(...$arrays); }\n"
            reference += "function aot_test_map(mixed $callback, array $array, array ...$others): array { return array_map($callback, $array, ...$others); }\n"
            reference += "function aot_test_filter(array $array, mixed $callback = null, int $mode = 0): array { return array_filter($array, $callback, $mode); }\n"
        if name == "string-replace":
            body = "function replace_call(mixed $search, mixed $replace, mixed $subject, mixed &$count): mixed { return CALL($search, $replace, $subject, $count); }\n"
            native += body.replace("CALL", "wp_aot_str_replace")
            reference += body.replace("CALL", "@str_replace")
        if name == "binary-pack":
            native += "function aot_test_pack(string $format, array $values): string { return wp_aot_pack($format, $values); }\n"
            native += "function aot_test_unpack(string $format, string $data, int $offset = 0): mixed { return unpack($format, $data, $offset); }\n"
            reference += "function aot_test_pack(string $format, array $values): string { return @pack($format, ...$values); }\n"
            reference += "function aot_test_unpack(string $format, string $data, int $offset = 0): mixed { return @unpack($format, $data, $offset); }\n"
        if name == "constant-policy":
            native += "function aot_test_absent_constant(string $name): mixed { return wp_aot_undefined_constant($name); }\n"
            native += "function aot_test_nonnull_constant_key(string $name): bool {return array_key_exists($name,AotNonNullConstantMap::VALUES);}\n"
            reference += "function aot_test_absent_constant(string $name): mixed { return constant($name); }\n"
            reference += "function aot_test_nonnull_constant_key(string $name): bool {return isset(AotNonNullConstantMap::VALUES[$name]);}\n"
        if name == "crypto":
            reference += "require __DIR__ . '/../../../upstream/wordpress/wp-includes/sodium_compat/autoload.php';\n"
        if name == "utf8":
            native += "require_once __DIR__ . '/wordpress/wp-includes/compat.php';\n"
            native += "function clamp_call(mixed $value,mixed $minimum,mixed $maximum): mixed {return wp_aot_clamp($value,$minimum,$maximum); }\n"
            reference += "require_once __DIR__ . '/../../../upstream/wordpress/wp-includes/sodium_compat/autoload.php';\n"
            reference += "require_once __DIR__ . '/../../../upstream/wordpress/wp-includes/compat.php';\n"
            reference += "function clamp_call(mixed $value,mixed $minimum,mixed $maximum): mixed {return clamp($value,$minimum,$maximum); }\n"
            for source in ("compat-utf8.php", "utf8.php"):
                native += f"require_once __DIR__ . '/wordpress/wp-includes/{source}';\n"
                reference += f"require __DIR__ . '/../../../upstream/wordpress/wp-includes/{source}';\n"
            sink = "function _deprecated_function(string $name, string $version, ?string $replacement = null): void {}\n"
            sink += "function _deprecated_argument(string $name, string $version): void {global $utf8_deprecations; $utf8_deprecations[] = array($name,$version); }\n"
            sink += "function get_option(mixed ...$arguments): never {throw new LogicException('Site charset lookup is outside this UTF-8 control.'); }\n"
            native += sink
            reference += sink
        cases = f"require __DIR__ . '/../../../tests/full-source/{name}-cases.php';\n"
        (OUT / f"control-{name}.php").write_text(native + cases)
        (OUT / f"reference-{name}.php").write_text(reference + cases)
        expected = run([php, "-d", "display_errors=stderr", "-d", "zend.assertions=1", "-d", "assert.exception=1", str(OUT / f"reference-{name}.php")])
        php_version = "8.5" if name == "walker" else "8.4"
        log = run([compiler, "--php-version", php_version, "--with-regex", "--link", "sodium", f"control-{name}.php"])
        (OUT / f"control-{name}.log").write_bytes(log)
        actual = run([str(OUT / f"control-{name}")], timeout=10)
        expected_value = json.loads(expected)
        actual_value = json.loads(actual)
        if name == "stream-read":
            for block_size in ("7", "128"):
                expected_value.extend(json.loads(run([php, str(OUT / f"reference-{name}.php"), block_size])))
                actual_value.extend(json.loads(run([str(OUT / f"control-{name}"), block_size], timeout=10)))
        if name == "subdomain-constants":
            modes = ("subdomain-false", "subdomain-true", "vhost-yes", "vhost-no", "both-match", "both-conflict", "subdomain-int", "vhost-other")
            for mode in modes:
                expected_value.extend(json.loads(run([php, str(OUT / f"reference-{name}.php"), mode])))
                actual_value.extend(json.loads(run([str(OUT / f"control-{name}"), mode], timeout=10)))
        if json.dumps(expected_value, sort_keys=True) != json.dumps(actual_value, sort_keys=True):
            (OUT / f"control-{name}-expected.json").write_text(json.dumps(expected_value, indent=2) + "\n")
            (OUT / f"control-{name}-actual.json").write_text(json.dumps(actual_value, indent=2) + "\n")
            raise SystemExit(f"{name} control differs from original PHP")
        for suffix in ("expected", "actual"):
            (OUT / f"control-{name}-{suffix}.json").unlink(missing_ok=True)
        socket_report = None
        if name == "stream-read":
            run(["python3", "scripts/test-wordpress-sockets.py", "--compiler", compiler], cwd=ROOT)
            socket_report = json.loads((OUT / "socket-verification.json").read_text())
            assert socket_report["matches_original_php"] and socket_report["source_manifest_sha256"] == report["source_manifest_sha256"]
        database_report = None
        if name == "sql":
            run(["python3", "scripts/test-wordpress-database.py", "--compiler", compiler], cwd=ROOT)
            database_report = json.loads((OUT / "database-verification.json").read_text())
            assert database_report["matches_original_php"] and database_report["source_manifest_sha256"] == report["source_manifest_sha256"]
            assert database_report["compiler_sha256"] == report["compiler_sha256"]
            assert database_report["ir_optimization"] == report["ir_optimization"]
        component_reports = {}
        components = {"callable": ("hooks", "capabilities", "block-wrapper"), "property-cursor": ("mailer", "block-context", "theme-schema"),
                      "array-mutation": ("patterns",), "native-strings": ("styles",), "sql": ("query-cache",)}
        for component in components.get(name, ()):
            run(["python3", "scripts/test-wordpress-components.py", "--compiler", compiler,
                 "--component", component], cwd=ROOT)
            item = json.loads((OUT / f"component-{component}-verification.json").read_text())
            assert item["matches_original_php"] and item["native_stderr_clean"]
            assert item["source_manifest_sha256"] == report["source_manifest_sha256"]
            assert item["compiler_sha256"] == report["compiler_sha256"]
            assert item["ir_optimization"] == report["ir_optimization"]
            component_reports[component] = item
        values = actual_value
        count = sum(len(row) if isinstance(row, list) else 1 for row in values)
        report["controls"][name] = {"cases": count, "target_php_version": php_version, "matches_original_php": True,
                                    "cases_sha256": hashlib.sha256((ROOT / f"tests/full-source/{name}-cases.php").read_bytes()).hexdigest()}
        for component, item in component_reports.items():
            report["controls"][name]["complete_" + component] = item
            report["controls"][name]["cases"] += item["comparisons"]
            count += item["comparisons"]
        if database_report is not None:
            report["controls"][name]["complete_database"] = database_report
            report["controls"][name]["database_harness_sha256"] = hashlib.sha256((ROOT / "scripts/test-wordpress-database.py").read_bytes()).hexdigest()
            report["controls"][name]["cases"] += database_report["comparisons"]
            count += database_report["comparisons"]
        if socket_report is not None:
            report["controls"][name]["socket_loopback"] = socket_report
            report["controls"][name]["socket_cases_sha256"] = hashlib.sha256((ROOT / "tests/full-source/socket-cases.php").read_bytes()).hexdigest()
            report["controls"][name]["socket_harness_sha256"] = hashlib.sha256((ROOT / "scripts/test-wordpress-sockets.py").read_bytes()).hexdigest()
            report["controls"][name]["cases"] += socket_report["comparisons"]
            count += socket_report["comparisons"]
        if name == "property-cursor":
            report["controls"][name]["mapped_diff_cases_sha256"] = hashlib.sha256((ROOT / "tests/full-source/mapped-diff-cases.php").read_bytes()).hexdigest()
            report["controls"][name]["sidebar_reference_cases_sha256"] = hashlib.sha256((ROOT / "tests/full-source/sidebar-reference-cases.php").read_bytes()).hexdigest()
            report["controls"][name]["nullable_static_property_cases_sha256"] = hashlib.sha256((ROOT / "tests/full-source/nullable-static-property-cases.php").read_bytes()).hexdigest()
            report["controls"][name]["widget_factory_cases_sha256"] = hashlib.sha256((ROOT / "tests/full-source/widget-factory-cases.php").read_bytes()).hexdigest()
            report["controls"][name]["admin_bar_cases_sha256"] = hashlib.sha256((ROOT / "tests/full-source/admin-bar-cases.php").read_bytes()).hexdigest()
            report["controls"][name]["ability_properties_cases_sha256"] = hashlib.sha256((ROOT / "tests/full-source/ability-properties-cases.php").read_bytes()).hexdigest()
        if name == "callable":
            report["controls"][name]["theme_support_forward_cases_sha256"] = hashlib.sha256((ROOT / "tests/full-source/theme-support-forward-cases.php").read_bytes()).hexdigest()
        if name == "array-mutation":
            report["controls"][name]["connector_callback_cases_sha256"] = hashlib.sha256((ROOT / "tests/full-source/connector-callback-cases.php").read_bytes()).hexdigest()
            report["controls"][name]["nav_menu_splice_cases_sha256"] = hashlib.sha256((ROOT / "tests/full-source/nav-menu-splice-cases.php").read_bytes()).hexdigest()
        if name == "utf8":
            report["controls"][name]["clamp_cases_sha256"] = hashlib.sha256((ROOT / "tests/full-source/clamp-cases.php").read_bytes()).hexdigest()
        if name == "introspection":
            for support in ("property-metadata-cases.php", "property-metadata-reference.php"):
                report["controls"][name][support.replace("-", "_").replace(".php", "_sha256")] = hashlib.sha256((ROOT / "tests/full-source" / support).read_bytes()).hexdigest()
            report["controls"][name]["property_provenance_sha256"] = hashlib.sha256((ROOT / "elephc/full-source/property-provenance.json").read_bytes()).hexdigest()

        if name == "stream-read":
            report["controls"][name]["pclzip_configurations"] = ["default", "7", "128"]
            report["controls"][name]["avif_cases_sha256"] = hashlib.sha256(
                (ROOT / "tests/full-source/avif-io-cases.php").read_bytes()).hexdigest()
            report["controls"][name]["pomo_cases_sha256"] = hashlib.sha256(
                (ROOT / "tests/full-source/pomo-stream-cases.php").read_bytes()).hexdigest()
            report["controls"][name]["pclzip_cases_sha256"] = hashlib.sha256(
                (ROOT / "tests/full-source/pclzip-copy-cases.php").read_bytes()).hexdigest()
        if name == "cron":
            report["controls"][name]["support_sha256"] = hashlib.sha256((ROOT / "tests/full-source/cron-support.php").read_bytes()).hexdigest()
        if name == "query-pagination":
            report["controls"][name]["boundary_fixture_sha256"] = hashlib.sha256(
                (ROOT / "tests/full-source/query-pagination-support.php").read_bytes()).hexdigest()
        if name == "weak-integer":
            report["controls"][name]["number_format_cases_sha256"] = hashlib.sha256(
                (ROOT / "tests/full-source/number-format-cases.php").read_bytes()).hexdigest()
            report["controls"][name]["calendar_cases_sha256"] = hashlib.sha256(
                (ROOT / "tests/full-source/calendar-cases.php").read_bytes()).hexdigest()
        if name in ("ai", "array-calls", "array-sort", "array-callback", "array-mutation", "string-replace"):
            report["controls"][name]["groups"] = len(values)
        if name in ("array-values", "array-more", "array-merge-recursive"):
            report["controls"][name]["groups"] = len(values[0])
        progress_path.write_text(json.dumps({**report, "complete": False}, indent=2) + "\n")
        print(f"PASS: {name}, {count} comparisons with original PHP", flush=True)
    report_path.write_text(json.dumps(report, indent=2) + "\n")
    progress_path.unlink(missing_ok=True)


if __name__ == "__main__":
    OUT.mkdir(parents=True, exist_ok=True)
    with tempfile.TemporaryDirectory(prefix=".control-files-", dir=OUT) as directory:
        main(directory)
