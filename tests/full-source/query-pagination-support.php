<?php

// Only the registry lookup and hook boundary are fixtures; query.php stays complete.
function pagination_initial_callback(): mixed {return null;}

function pagination_empty_trace(): array {return array();}

function pagination_current_callback(): mixed {
    global $pagination_callback;
    return $pagination_callback;
}

final class WordPressQueryControlType {
    public mixed $supports;

    public function __construct(mixed $supports) {
        $this->supports = $supports;
    }
}

final class WP_Block_Type_Registry {
    public static function get_instance(): WP_Block_Type_Registry {
        static $registry = new WP_Block_Type_Registry();
        return $registry;
    }

    public function get_registered(mixed $name): WordPressQueryControlType {
        $support = $name === 'core/query' || $name === 'plugin/compatible'
            ? array('interactivity' => array('clientNavigation' => true))
            : ($name === 'plugin/interactive' ? array('interactivity' => true) : array());
        return new WordPressQueryControlType($support);
    }
}

function add_action(mixed ...$arguments): void {}

function add_filter(string $name, mixed $callback, int $priority = 10, int $accepted = 1): void {
    global $pagination_callback, $pagination_trace;
    if ($name !== 'render_block_core/query') return;
    $pagination_callback = $callback;
    $pagination_trace[] = array('add', $name, $priority, $accepted);
}

function remove_filter(string $name, mixed $callback): bool {
    global $pagination_callback, $pagination_trace;
    $same = $callback === $pagination_callback;
    $pagination_trace[] = array('remove', $name, $same);
    $pagination_callback = null;
    return $same;
}

function wp_interactivity_config(string $store, array $config): void {
    global $pagination_trace;
    $pagination_trace[] = array('config', $store, $config);
}

function register_block_type_from_metadata(mixed ...$arguments): never {
    throw new LogicException('Block registration is outside this isolated control.');
}

function wp_enqueue_script_module(mixed ...$arguments): never {
    throw new LogicException('Script enqueueing is outside this isolated control.');
}

function wp_style_is(mixed ...$arguments): bool {
    throw new LogicException('Style enqueueing is outside this isolated control.');
}

final class WP_HTML_Tag_Processor {
    public function __construct(mixed $html) {
        throw new LogicException('HTML rewriting is outside this isolated control.');
    }

    public function next_tag(): bool {
        throw new LogicException('HTML rewriting is outside this isolated control.');
    }

    public function set_attribute(mixed $name, mixed $value): void {
        throw new LogicException('HTML rewriting is outside this isolated control.');
    }

    public function get_updated_html(): string {
        throw new LogicException('HTML rewriting is outside this isolated control.');
    }
}
