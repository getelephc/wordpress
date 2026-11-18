<?php
// Host callbacks execute after native terminal filters. Nested callback sites
// with active host filters are rejected by the PHP routing preflight.
function apply_filters(string $name, mixed $value = null, mixed $arg1 = null, mixed $arg2 = null, mixed $arg3 = null): mixed {
    global $ep_filters,$ep_context;
    $args = [$value, $arg1];
    if(str_starts_with($name,'shortcode_atts_')) { $args=[$value,$arg1,$arg2,$arg3]; }
    if ($name === 'sanitize_html_class' || $name === 'clean_url') { $args = [$value, $arg1, $arg2]; }
    if ($name === 'wp_kses_uri_attributes' || $name === 'rest_response_link_curies') { $args = [$value]; }
    if ($name === 'rest_request_parameter_order') { $args=[$value,null]; }
    $route=(string)($ep_context['route']??'');
    $terminal=$name===$route || ($name==='attribute_escape' && $route==='esc_attr') || ($name==='clean_url' && in_array($route,['esc_url','esc_url_raw','sanitize_url'],true)) || ($name==='rest_response_link_curies' && $route==='WP_REST_Response::get_curies');
    if($route==='shortcode_atts' && str_starts_with($name,'shortcode_atts_'))$terminal=true;
    $ep_filters[] = ['name'=>$name, 'args'=>$args,'terminal'=>$terminal];
    return $value;
}
function wordpress_kses_protocols(): array { global $ep_context; return (array) ($ep_context['protocols'] ?? ['http','https','ftp','mailto']); }
function wordpress_lists_map(): array { return (array) json_decode('{}', true); }
function wordpress_cache_map(): array { return (array) json_decode('{}', true); }
function wordpress_error_map(): array { return (array) json_decode('{}', true); }
function ep_box(mixed $value): mixed { return $value; }
function ep_error_payload(WP_Error $value): array { return ['errors'=>$value->errors,'data'=>$value->error_data,'history'=>$value->additional_data]; }
function wordpress_cache_multisite(): bool { global $ep_context; return (bool) ($ep_context['multisite'] ?? false); }
function wordpress_cache_blog_id(): int { global $ep_context; return (int) ($ep_context['blog_id'] ?? 1); }
function _elephc_block_json_encode(array $attributes, int $flags): string { return (string) json_encode($attributes,$flags); }
function wordpress_response_map(): array { return (array)json_decode('{}',true); }
function wordpress_request_permalinks(): bool { global $ep_context; return (bool)($ep_context['permalinks']??false); }
function do_action(string $name, mixed $arg1=null, mixed $arg2=null, mixed $arg3=null, mixed $arg4=null): void {
    global $ep_actions;
    if($name==='wp_error_added') { $ep_actions[]=['name'=>$name,'args'=>[$arg1,$arg2,$arg3]]; }
    elseif($name==='is_wp_error_instance') { $ep_actions[]=['name'=>$name,'args'=>[]]; }
}
function ep_sprintf(string $format,mixed $a=null,mixed $b=null,mixed $c=null,mixed $d=null): string {
    $values=[$a,$b,$c,$d]; $next=0; $output='';
    for($i=0;$i<strlen($format);++$i) {
        if($format[$i]!=='%') { $output.=$format[$i]; continue; }
        ++$i;
        if($format[$i]==='%') { $output.='%'; continue; }
        $index=$next;
        if($i+1<strlen($format) && $format[$i+1]==='$') { $index=(int)$format[$i]-1; $i+=2; }
        else { ++$next; }
        $kind=$format[$i];
        if($kind==='s') { $output.=(string)($values[$index]??''); }
        elseif($kind==='d') { $output.=(string)(int)($values[$index]??0); }
        else { throw new InvalidArgumentException('Format outside native error message profile.'); }
    }
    return $output;
}
