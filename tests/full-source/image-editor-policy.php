<?php

$wordpress = dirname(__DIR__, 2) . '/upstream/wordpress/';
require $wordpress . 'wp-includes/class-wp-image-editor.php';
require $wordpress . 'wp-includes/class-wp-image-editor-gd.php';
require $wordpress . 'wp-includes/class-wp-image-editor-imagick.php';
$rows = array();
foreach (array(WP_Image_Editor_GD::class, WP_Image_Editor_Imagick::class) as $name) {
    $class = new ReflectionClass($name);
    $method = $class->getMethod('make_subsize');
    $editor = $class->newInstanceWithoutConstructor();
    $row = array('public' => $method->isPublic(), 'static' => $method->isStatic(), 'magic_fallback' => $class->hasMethod('__call'),
        'method_exists' => method_exists($editor, 'make_subsize'),
        'callable' => is_callable(array($editor, 'make_subsize')),
        'original_sha256' => hash_file('sha256', $class->getFileName()));
    if (!$row['public'] || $row['static'] || $row['magic_fallback'] || !$row['method_exists'] || !$row['callable']) {
        throw new LogicException('The original core editor violates the native public-capability policy: ' . $name);
    }
    $rows[$name] = $row;
}
echo json_encode($rows, JSON_THROW_ON_ERROR), "\n";
