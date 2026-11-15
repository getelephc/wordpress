<?php

final class WordPressHookTrace {
    public static array $events = [];
    public static int $depth = 0;
    public static bool $changed = false;
}

final class WordPressHookReceiver {
    public function append(string $value): string {
        return $value . ':instance';
    }

    public static function decorate(string $value): string {
        return '[' . $value . ']';
    }

    public function __invoke(string $value): string {
        return $value . ':invoked';
    }
}

function wordpress_hook_early(string $value): string {
    return $value . ':early';
}

function wordpress_hook_late(string $value): string {
    return $value . ':late';
}

function wordpress_hook_zero(): string {
    return 'zero';
}

function wordpress_hook_pair(string $value, string $suffix): string {
    return $value . ':' . $suffix;
}

function wordpress_hook_record(mixed $value = ''): void {
    $event = current_filter() . ':' . (string) $value;
    WordPressHookTrace::$events[] = $event;
}

function wordpress_hook_nested(string $value): string {
    $before = current_filter();
    $inner = apply_filters('inner', $value);
    WordPressHookTrace::$events[] = [$before, current_filter(), doing_filter('outer')];
    return (string) $inner;
}

function wordpress_hook_recursive(string $value): string {
    ++WordPressHookTrace::$depth;
    if (WordPressHookTrace::$depth < 3) {
        $value = (string) apply_filters('recursive', $value . ':nested');
    }
    --WordPressHookTrace::$depth;
    return $value . ':return';
}

function wordpress_hook_add_later(string $value): string {
    if (!WordPressHookTrace::$changed) {
        WordPressHookTrace::$changed = true;
        add_filter('live', 'wordpress_hook_late', 20);
    }
    return $value . ':add';
}

function wordpress_hook_add_earlier(string $value): string {
    if (!WordPressHookTrace::$changed) {
        WordPressHookTrace::$changed = true;
        add_filter('live', 'wordpress_hook_early', 5);
    }
    return $value . ':add';
}

function wordpress_hook_remove_later(string $value): string {
    remove_filter('live', 'wordpress_hook_late', 20);
    return $value . ':remove';
}

function wordpress_hook_remove_same(string $value): string {
    remove_filter('live', 'wordpress_hook_late', 10);
    return $value . ':remove';
}

function wordpress_hook_remove_everything(string $value): string {
    remove_all_filters('live');
    return $value . ':clear';
}

function wordpress_hook_all(string $hook, mixed $value = ''): void {
    WordPressHookTrace::$events[] = [$hook, $value, current_filter()];
}

function wordpress_hooks_reset(): void {
    global $wp_filter, $wp_actions, $wp_filters, $wp_current_filter;
    $wp_filter = [];
    $wp_actions = [];
    $wp_filters = [];
    $wp_current_filter = [];
    WordPressHookTrace::$events = [];
    WordPressHookTrace::$depth = 0;
    WordPressHookTrace::$changed = false;
}

function wordpress_hooks_demo(string $scenario, string $input): array {
    wordpress_hooks_reset();
    WordPressHookCallbacks::register('wordpress_hook_early', wordpress_hook_early(...));
    WordPressHookCallbacks::register('wordpress_hook_late', wordpress_hook_late(...));
    WordPressHookCallbacks::register('wordpress_hook_zero', wordpress_hook_zero(...));
    WordPressHookCallbacks::register('wordpress_hook_pair', wordpress_hook_pair(...));
    WordPressHookCallbacks::register('wordpress_hook_record', wordpress_hook_record(...));
    WordPressHookCallbacks::register('wordpress_hook_nested', wordpress_hook_nested(...));
    WordPressHookCallbacks::register('wordpress_hook_recursive', wordpress_hook_recursive(...));
    WordPressHookCallbacks::register('wordpress_hook_add_later', wordpress_hook_add_later(...));
    WordPressHookCallbacks::register('wordpress_hook_add_earlier', wordpress_hook_add_earlier(...));
    WordPressHookCallbacks::register('wordpress_hook_remove_later', wordpress_hook_remove_later(...));
    WordPressHookCallbacks::register('wordpress_hook_remove_same', wordpress_hook_remove_same(...));
    WordPressHookCallbacks::register('wordpress_hook_remove_everything', wordpress_hook_remove_everything(...));
    WordPressHookCallbacks::register('wordpress_hook_all', wordpress_hook_all(...));
    $result = [];
    switch ($scenario) {
        case 'priority':
            add_filter('title', 'wordpress_hook_late', 20);
            add_filter('title', 'wordpress_hook_early', 5);
            add_filter('title', static function (string $value): string { return $value . ':middle'; }, 10);
            $result = ['value' => apply_filters('title', $input), 'count' => did_filter('title')];
            break;
        case 'same-priority':
            add_filter('title', 'wordpress_hook_early');
            add_filter('title', 'wordpress_hook_late');
            $result = ['value' => apply_filters('title', $input)];
            break;
        case 'duplicate':
            add_filter('title', 'wordpress_hook_early');
            add_filter('title', 'wordpress_hook_early');
            $result = ['value' => apply_filters('title', $input)];
            break;
        case 'zero-args':
            add_filter('title', 'wordpress_hook_zero', 10, 0);
            $result = ['value' => apply_filters('title', $input)];
            break;
        case 'extra-args':
            add_filter('title', 'wordpress_hook_pair', 10, 2);
            $result = ['value' => apply_filters('title', $input, 'suffix', 'ignored')];
            break;
        case 'remove':
            add_filter('title', 'wordpress_hook_early', 0);
            $result = ['priority' => has_filter('title', 'wordpress_hook_early'),
                'wrong_priority' => remove_filter('title', 'wordpress_hook_early', 10),
                'removed' => remove_filter('title', 'wordpress_hook_early', 0),
                'missing' => has_filter('title'), 'value' => apply_filters('title', $input)];
            break;
        case 'remove-priority':
            add_filter('title', 'wordpress_hook_early', 5);
            add_filter('title', 'wordpress_hook_late', 20);
            remove_all_filters('title', 5);
            $result = ['value' => apply_filters('title', $input), 'has_late' => has_filter('title', 'wordpress_hook_late', 20)];
            remove_all_filters('title');
            $result['empty'] = has_filter('title');
            break;
        case 'empty':
            $result = ['value' => apply_filters('missing', $input), 'count' => did_filter('missing')];
            do_action('missing_action');
            $result['actions'] = did_action('missing_action');
            break;
        case 'actions':
            add_action('event', 'wordpress_hook_record');
            do_action('event', $input);
            do_action('event');
            $result = ['events' => WordPressHookTrace::$events, 'count' => did_action('event'), 'has' => has_action('event')];
            remove_action('event', 'wordpress_hook_record');
            $result['removed'] = !has_action('event');
            break;
        case 'nested':
            add_filter('outer', 'wordpress_hook_nested');
            add_filter('inner', static function (string $value): string {
                WordPressHookTrace::$events[] = [current_filter(), doing_filter('outer'), doing_filter('inner')];
                return $value . ':inner';
            });
            $result = ['value' => apply_filters('outer', $input), 'events' => WordPressHookTrace::$events];
            break;
        case 'recursive':
            add_filter('recursive', 'wordpress_hook_recursive');
            $result = ['value' => apply_filters('recursive', $input), 'count' => did_filter('recursive')];
            break;
        case 'add-later':
            add_filter('live', 'wordpress_hook_add_later');
            $result = ['first' => apply_filters('live', $input), 'second' => apply_filters('live', $input)];
            break;
        case 'add-earlier':
            add_filter('live', 'wordpress_hook_add_earlier');
            $result = ['first' => apply_filters('live', $input), 'second' => apply_filters('live', $input)];
            break;
        case 'remove-later':
            add_filter('live', 'wordpress_hook_remove_later');
            add_filter('live', 'wordpress_hook_late', 20);
            $result = ['value' => apply_filters('live', $input)];
            break;
        case 'remove-same':
            add_filter('live', 'wordpress_hook_remove_same');
            add_filter('live', 'wordpress_hook_late');
            $result = ['first' => apply_filters('live', $input), 'second' => apply_filters('live', $input)];
            break;
        case 'remove-all-active':
            add_filter('live', 'wordpress_hook_remove_everything');
            add_filter('live', 'wordpress_hook_late', 20);
            $result = ['first' => apply_filters('live', $input), 'second' => apply_filters('live', $input)];
            break;
        case 'all-hook':
            add_action('all', 'wordpress_hook_all', 10, 2);
            add_filter('title', 'wordpress_hook_early');
            $value = apply_filters('title', $input);
            do_action('event', $input);
            $result = ['value' => $value, 'events' => WordPressHookTrace::$events];
            break;
        case 'array-args':
            add_filter('title', 'wordpress_hook_pair', 10, 2);
            add_action('event', 'wordpress_hook_record');
            $value = apply_filters_ref_array('title', [$input, 'array']);
            do_action_ref_array('event', [$input]);
            $result = ['value' => $value, 'events' => WordPressHookTrace::$events];
            break;
        case 'closure-capture':
            $prefix = 'captured:';
            $callback = static function (string $value) use ($prefix): string { return $prefix . $value; };
            add_filter('title', $callback);
            $value = apply_filters('title', $input);
            $result = ['value' => $value, 'removed' => remove_filter('title', $callback), 'empty' => has_filter('title')];
            break;
        case 'static-string':
            add_filter('title', 'WordPressHookReceiver::decorate');
            $result = ['value' => apply_filters('title', $input)];
            break;
        case 'static-array':
            add_filter('title', [WordPressHookReceiver::class, 'decorate']);
            $result = ['value' => apply_filters('title', $input)];
            break;
        case 'instance-array':
            $receiver = new WordPressHookReceiver();
            $callback = [$receiver, 'append'];
            add_filter('title', $callback);
            $value = apply_filters('title', $input);
            $result = ['value' => $value, 'removed' => remove_filter('title', $callback)];
            break;
        case 'invokable':
            $receiver = new WordPressHookReceiver();
            add_filter('title', $receiver);
            $value = apply_filters('title', $input);
            $result = ['value' => $value, 'removed' => remove_filter('title', $receiver)];
            break;
        case 'first-class':
            add_filter('title', wordpress_hook_early(...));
            $result = ['value' => apply_filters('title', $input)];
            break;
        case 'sentinel':
            $sentinel = new WP_Filter_Sentinel();
            add_filter('sentinel', static function (mixed $value): mixed { return $value; });
            $value = apply_filters('sentinel', $sentinel);
            $result = ['same' => $value === $sentinel];
            break;
        case 'mixed-values':
            add_filter('mixed', static function (mixed $value, mixed $extra): array {
                return ['value' => $value, 'extra' => $extra];
            }, 10, 2);
            $result = ['false' => apply_filters('mixed', false, null),
                'array' => apply_filters('mixed', ['text' => $input, 'count' => 7], true)];
            break;
        case 'action-context':
            add_action('event', static function (): void {
                WordPressHookTrace::$events[] = [current_action(), doing_action('event'), doing_action(), did_action('event')];
            }, 10, 0);
            do_action('event');
            $result = ['events' => WordPressHookTrace::$events, 'current_action' => current_action(), 'doing_action' => doing_action()];
            remove_all_actions('event');
            $result['removed'] = !has_action('event');
            break;
        case 'builtin':
            add_filter('title', 'strtoupper');
            $result = ['value' => apply_filters('title', $input)];
            break;
        case 'zero-negative-priority':
            add_filter('title', 'wordpress_hook_late', 20);
            add_filter('title', 'wordpress_hook_early', -10);
            add_filter('title', static function (string $value): string { return $value . ':zero'; }, 0);
            $result = ['value' => apply_filters('title', $input)];
            break;
        case 'iterator':
            $hook = new WP_Hook();
            $hook->add_filter('direct', 'wordpress_hook_late', 20, 1);
            $hook->add_filter('direct', 'wordpress_hook_early', 5, 1);
            $priorities = [];
            foreach ($hook as $priority => $callbacks) {
                $priorities[] = [$priority, count((array) $callbacks)];
            }
            $result = ['priorities' => $priorities, 'offset' => isset($hook[5]), 'value' => $hook->apply_filters($input, [$input])];
            break;
        case 'reference-capture':
            $calls = 0;
            $callback = static function (string $value) use (&$calls): string {
                ++$calls;
                return $value;
            };
            add_filter('title', $callback);
            apply_filters('title', $input);
            apply_filters('title', $input);
            $result = ['calls' => $calls, 'removed' => remove_filter('title', $callback)];
            break;
        default:
            throw new InvalidArgumentException('Unknown hook scenario: ' . $scenario);
    }
    $result['current'] = current_filter();
    $result['doing'] = doing_filter();
    return $result;
}
