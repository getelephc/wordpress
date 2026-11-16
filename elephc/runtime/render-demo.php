<?php

final class WordPressContentState {
    public static int $calls = 0;
}

function wordpress_content_greet(array $attributes, ?string $content = null, string $tag = ''): string {
    $attributes = shortcode_atts(['name' => 'World'], $attributes, 'greet');
    $name = htmlspecialchars((string) $attributes['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    return '<span class="greeting">Hello ' . $name . '</span>';
}

function wordpress_content_upper(array $attributes, ?string $content = null, string $tag = ''): string {
    return strtoupper(do_shortcode($content ?? '', true));
}

function wordpress_content_box(array $attributes, ?string $content = null, string $tag = ''): string {
    return '<div class="box">' . do_shortcode($content ?? '', true) . '</div>';
}

function wordpress_content_counter(array $attributes, ?string $content = null, string $tag = ''): string {
    ++WordPressContentState::$calls;
    return (string) WordPressContentState::$calls;
}

function wordpress_content_context(array $attributes, ?string $content = null, string $tag = ''): string {
    return (string) apply_filters('wp_get_attachment_image_context', 'outside');
}

function wordpress_content_shortcodes(string $content): string {
    return do_shortcode($content, true);
}

function wordpress_content_html_shortcodes(string $content): string {
    return do_shortcode($content, false);
}

function wordpress_content_raw(array $attributes, ?string $content = null, string $tag = ''): string {
    return wordpress_kses_attribute_input((string) ($attributes['value'] ?? 'value'));
}

function wordpress_content_autop(string $content): string {
    return wpautop($content);
}

function wordpress_content_unautop(string $content): string {
    return shortcode_unautop($content);
}

function wordpress_content_shortcode_context(): string {
    return _filter_do_shortcode_context();
}

function wordpress_content_setup(): void {
    global $wp_filter, $wp_actions, $wp_filters, $wp_current_filter;
    $wp_filter = [];
    $wp_actions = [];
    $wp_filters = [];
    $wp_current_filter = [];
    WordPressContentState::$calls = 0;
    wordpress_kses_setup();
    remove_all_shortcodes();
    WordPressHookCallbacks::register('_filter_do_shortcode_context', wordpress_content_shortcode_context(...));
    WordPressHookCallbacks::register('wpautop', wordpress_content_autop(...));
    WordPressHookCallbacks::register('shortcode_unautop', wordpress_content_unautop(...));
    WordPressHookCallbacks::register('wordpress_content_shortcodes', wordpress_content_shortcodes(...));
    WordPressHookCallbacks::register('wordpress_content_greet', wordpress_content_greet(...));
    add_shortcode('greet', 'wordpress_content_greet');
    add_shortcode('upper', wordpress_content_upper(...));
    add_shortcode('box', wordpress_content_box(...));
    add_shortcode('counter', wordpress_content_counter(...));
    add_shortcode('context', wordpress_content_context(...));
    add_shortcode('raw', wordpress_content_raw(...));
    foreach (['mark+', 'a.b', 'dash-tag', 'x#y', 'x:y', 'x\\y', 'meta(1)'] as $alias) {
        add_shortcode($alias, wordpress_content_greet(...));
    }
    add_filter('the_content', 'wpautop', 10);
    add_filter('the_content', 'shortcode_unautop', 10);
    add_filter('the_content', 'wordpress_content_shortcodes', 11);
}

function wordpress_content_demo_input(): string {
    return "Welcome [greet name=\"Elephc\"]\n\n[box]WordPress core shortcodes, compiled to native code.[/box]";
}

function wordpress_render_text(string $mode, string $input): string {
    wordpress_content_setup();
    switch ($mode) {
        case 'pipeline':
            return (string) apply_filters('the_content', $input);
        case 'shortcodes':
            return do_shortcode($input, true);
        case 'html-shortcodes':
            wordpress_kses_attribute_input($input);
            return do_shortcode($input, false);
        case 'html-pipeline':
            wordpress_kses_attribute_input($input);
            remove_filter('the_content', 'wordpress_content_shortcodes', 11);
            add_filter('the_content', wordpress_content_html_shortcodes(...), 11);
            return (string) apply_filters('the_content', $input);
        case 'autop':
            return wpautop($input);
        case 'autop-no-br':
            return wpautop($input, false);
        case 'strip':
            return strip_shortcodes($input);
        case 'attributes':
            return (string) json_encode(shortcode_parse_atts($input), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        case 'inspect':
            return (string) json_encode([
                'tags' => get_shortcode_tags_in_content($input),
                'has_greet' => has_shortcode($input, 'greet'),
                'exists' => shortcode_exists('greet'),
                'unknown' => shortcode_exists('missing'),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        case 'pre-filter':
            add_filter('pre_do_shortcode_tag', static function (mixed $output, string $tag, array $attributes, array $matches): mixed {
                return $tag === 'greet' ? '<em>overridden</em>' : $output;
            }, 10, 4);
            return do_shortcode($input, true);
        case 'post-filter':
            add_filter('do_shortcode_tag', static function (string $output, string $tag, array $attributes, array $matches): string {
                return '<mark>' . $output . '</mark>';
            }, 10, 4);
            return do_shortcode($input, true);
        case 'attribute-filter':
            add_filter('shortcode_atts_greet', static function (array $output, array $pairs, array $attributes, string $tag): array {
                $output['name'] = 'filtered ' . (string) $output['name'];
                return $output;
            }, 10, 4);
            return do_shortcode($input, true);
        case 'context':
            $html = do_shortcode($input, true);
            return (string) json_encode([
                'html' => $html,
                'outside' => apply_filters('wp_get_attachment_image_context', 'outside'),
                'context_filter' => has_filter('wp_get_attachment_image_context', '_filter_do_shortcode_context'),
                'current' => current_filter(),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        case 'registration':
            $before = shortcode_exists('greet');
            remove_shortcode('greet');
            $removed = shortcode_exists('greet');
            $html = do_shortcode($input, true);
            add_shortcode('greet', static function (array $attributes, ?string $content, string $tag): string { return 'replacement'; });
            $replacement = do_shortcode('[greet]', true);
            remove_all_shortcodes();
            return (string) json_encode([
                'before' => $before, 'after_remove' => $removed, 'html' => $html,
                'replacement' => $replacement, 'after_clear' => shortcode_exists('upper'),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        default:
            throw new InvalidArgumentException('Unknown render mode: ' . $mode);
    }
}
