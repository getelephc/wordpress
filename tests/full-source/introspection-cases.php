<?php
interface WordPressProbeInterface {}
trait WordPressProbeTrait {}
class WordPressProbeClass implements WordPressProbeInterface {
    use WordPressProbeTrait;
    public const VALUE = array('nested' => array(null, false, 1));
    public static function handler(): void {}
}
const WORDPRESS_PROBE_VALUE = array('key' => array(null, 2));
function WordPressProbeFunction(): void {}
$result = array();
foreach (array('WordPressProbeClass', 'wordpressprobeclass', '\\WordPressProbeClass', '\\\\WordPressProbeClass', 'WordPressProbeInterface', 'WordPressProbeTrait', 'WordPressMissingClass', 'stdClass') as $name) {
    $result[] = array(probe_class_exists($name, false), probe_class_exists($name), probe_interface_exists($name, false));
}
foreach (array('WordPressProbeFunction', 'wordpressprobefunction', '\\WordPressProbeFunction', 'strlen', 'WordPressMissingFunction', 'WordPressProbeClass::handler') as $name) $result[] = probe_function_exists($name);
foreach (array('WORDPRESS_PROBE_VALUE', 'WORDPRESS_MISSING_VALUE') as $name) {
    $exists = probe_defined($name); $result[] = array($exists, $exists ? probe_constant($name) : null);
}
// Compare only a module whose availability is shared by PHP and this compiler.
$result[] = probe_extension_loaded('Core');
class WordPressImplicitText { public function __toString(): string {return 'implicit';} }
class WordPressInheritedText extends WordPressImplicitText {}
class WordPressInvokableText { public function __invoke(): string {return 'callable';} }
foreach (array('', '12', 42, 1.5, null, true, false, array(), array('x'), new stdClass(), new WordPressImplicitText(), new WordPressInheritedText(), new WordPressInvokableText()) as $stringability_value) {
    $result[] = array(\WpOrg\Requests\Utility\InputValidator::is_string_or_stringable($stringability_value), \WpOrg\Requests\Utility\InputValidator::is_stringable_object($stringability_value));
}
require __DIR__ . '/property-metadata-cases.php';
echo json_encode($result, JSON_THROW_ON_ERROR), chr(10);
