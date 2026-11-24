<?php

// User wrapper metadata is unavailable in the native SDK; strings use its memory stream.
const WP_AOT_USER_STREAM_METADATA = false;

function wp_aot_intersect_keys(array $left, array $right): array {
    $result = array();
    foreach ($left as $key => $value) {
        if (array_key_exists($key, $right)) $result[$key] = $value;
    }
    return $result;
}

function wp_aot_diff_keys(array $left, array $right): array {
    $result = array();
    foreach ($left as $key => $value) {
        if (!array_key_exists($key, $right)) $result[$key] = $value;
    }
    return $result;
}

function wp_aot_array_union(array $left, array $right): array {
    $result = $left;
    foreach ($right as $key => $value) {
        if (!array_key_exists($key, $result)) $result[$key] = $value;
    }
    return $result;
}

function wp_aot_unique_regular(array $values): array {
    $result = array();
    foreach ($values as $key => $value) {
        $seen = false;
        foreach ($result as $previous) {
            if ($value == $previous) { $seen = true; break; }
        }
        if (!$seen) $result[$key] = $value;
    }
    return $result;
}

// The native profile discovers only declarations in the compiled source graph.
function wp_aot_class_exists(string $name): bool {
    $needle = strtolower(ltrim($name, '\\'));
    foreach (get_declared_classes() as $class) {
        if (strtolower($class) === $needle) return true;
    }
    return false;
}

function wp_aot_interface_exists(string $name): bool {
    $needle = strtolower(ltrim($name, '\\'));
    foreach (get_declared_interfaces() as $class) {
        if (strtolower($class) === $needle) return true;
    }
    return false;
}

function wp_aot_defined(string $name): bool {
    return array_key_exists($name, get_defined_constants());
}

function wp_aot_constant(string $name): mixed {
    $constants = get_defined_constants();
    if (!array_key_exists($name, $constants)) throw new Error('Undefined constant "' . $name . '"');
    return $constants[$name];
}


function wp_aot_enum_reflection(string $name): ReflectionClass {
    switch ($name) {
        case \WordPress\AiClient\Common\AbstractEnum::class: return new ReflectionClass(\WordPress\AiClient\Common\AbstractEnum::class);
        case \WordPress\AiClient\Files\Enums\FileTypeEnum::class: return new ReflectionClass(\WordPress\AiClient\Files\Enums\FileTypeEnum::class);
        case \WordPress\AiClient\Files\Enums\MediaOrientationEnum::class: return new ReflectionClass(\WordPress\AiClient\Files\Enums\MediaOrientationEnum::class);
        case \WordPress\AiClient\Messages\Enums\MessagePartChannelEnum::class: return new ReflectionClass(\WordPress\AiClient\Messages\Enums\MessagePartChannelEnum::class);
        case \WordPress\AiClient\Messages\Enums\MessagePartTypeEnum::class: return new ReflectionClass(\WordPress\AiClient\Messages\Enums\MessagePartTypeEnum::class);
        case \WordPress\AiClient\Messages\Enums\MessageRoleEnum::class: return new ReflectionClass(\WordPress\AiClient\Messages\Enums\MessageRoleEnum::class);
        case \WordPress\AiClient\Messages\Enums\ModalityEnum::class: return new ReflectionClass(\WordPress\AiClient\Messages\Enums\ModalityEnum::class);
        case \WordPress\AiClient\Operations\Enums\OperationStateEnum::class: return new ReflectionClass(\WordPress\AiClient\Operations\Enums\OperationStateEnum::class);
        case \WordPress\AiClient\Providers\Enums\ProviderTypeEnum::class: return new ReflectionClass(\WordPress\AiClient\Providers\Enums\ProviderTypeEnum::class);
        case \WordPress\AiClient\Providers\Enums\ToolTypeEnum::class: return new ReflectionClass(\WordPress\AiClient\Providers\Enums\ToolTypeEnum::class);
        case \WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum::class: return new ReflectionClass(\WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum::class);
        case \WordPress\AiClient\Providers\Http\Enums\RequestAuthenticationMethod::class: return new ReflectionClass(\WordPress\AiClient\Providers\Http\Enums\RequestAuthenticationMethod::class);
        case \WordPress\AiClient\Providers\Models\Enums\CapabilityEnum::class: return new ReflectionClass(\WordPress\AiClient\Providers\Models\Enums\CapabilityEnum::class);
        case \WordPress\AiClient\Providers\Models\Enums\OptionEnum::class: return new ReflectionClass(\WordPress\AiClient\Providers\Models\Enums\OptionEnum::class);
        case \WordPress\AiClient\Results\Enums\FinishReasonEnum::class: return new ReflectionClass(\WordPress\AiClient\Results\Enums\FinishReasonEnum::class);
    }
    throw new LogicException('The enum class is outside the compiled source manifest: ' . $name);
}
