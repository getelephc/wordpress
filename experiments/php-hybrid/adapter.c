#include <php.h>
#include <ext/json/php_json.h>
#include <Zend/zend_smart_str.h>
#include <stdio.h>
#include <stdlib.h>
#include <time.h>
#ifdef ZTS
#error This isolated experiment requires the verified NTS PHP host.
#endif
#include "libwp_extended.h"

#define GUARDED(name) extern __typeof__(name) guarded_##name
GUARDED(ep_autop);
GUARDED(ep_identity);
GUARDED(ep_serialize_json);
GUARDED(elephc_abi_version);
GUARDED(elephc_init);
GUARDED(elephc_shutdown);
GUARDED(elephc_free);
GUARDED(elephc_last_error);
GUARDED(elephc_last_status);
GUARDED(ep_parse_json);
#define ep_parse_json guarded_ep_parse_json
GUARDED(ep_dispatch);
GUARDED(ep_request_reset);
GUARDED(ep_fast_absint);
GUARDED(ep_fast_numeric_keys);
GUARDED(ep_fast_text);
#define ep_dispatch guarded_ep_dispatch
#define ep_request_reset guarded_ep_request_reset
#define ep_fast_absint guarded_ep_fast_absint
#define ep_fast_numeric_keys guarded_ep_fast_numeric_keys
#define ep_fast_text guarded_ep_fast_text
static HashTable native_calls;
static HashTable fast_calls;
static HashTable profile_calls;
static FILE *input_trace;
static bool profile_enabled;
static uint64_t profile_clock(void) {
    struct timespec value;
    clock_gettime(CLOCK_MONOTONIC,&value);
    return (uint64_t)value.tv_sec*UINT64_C(1000000000)+(uint64_t)value.tv_nsec;
}
static void profile_record(zend_string *name,uint64_t encode,uint64_t native,uint64_t decode) {
    zval *row=zend_hash_find(&profile_calls,name);
    if(!row) { zval initial; array_init(&initial); add_assoc_long(&initial,"calls",0);add_assoc_long(&initial,"encode_ns",0);add_assoc_long(&initial,"native_ns",0);add_assoc_long(&initial,"decode_ns",0);row=zend_hash_add_new(&profile_calls,name,&initial); }
    ++Z_LVAL_P(zend_hash_str_find(Z_ARRVAL_P(row),"calls",5));
    Z_LVAL_P(zend_hash_str_find(Z_ARRVAL_P(row),"encode_ns",9))+=encode;
    Z_LVAL_P(zend_hash_str_find(Z_ARRVAL_P(row),"native_ns",9))+=native;
    Z_LVAL_P(zend_hash_str_find(Z_ARRVAL_P(row),"decode_ns",9))+=decode;
}
#define ep_autop guarded_ep_autop
#define ep_identity guarded_ep_identity
#define ep_serialize_json guarded_ep_serialize_json
#define elephc_abi_version guarded_elephc_abi_version
#define elephc_init guarded_elephc_init
#define elephc_shutdown guarded_elephc_shutdown
#define elephc_free guarded_elephc_free
#define elephc_last_error guarded_elephc_last_error
#define elephc_last_status guarded_elephc_last_status

static zend_long parser_calls, autop_calls, serialize_calls, identity_calls, input_bytes, output_bytes;

static int native_error(int32_t status) {
    if (status == ELEPHC_STATUS_OK) return 0;
    const char *message = elephc_last_error();
    zend_throw_error(NULL, "Elephc status %d: %s", status, message ? message : "unknown error");
    return 1;
}

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_autop, 0, 1, IS_STRING, 0)
    ZEND_ARG_TYPE_INFO(0, content, IS_STRING, 0)
    ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, br, _IS_BOOL, 0, "true")
ZEND_END_ARG_INFO()
ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_serialize, 0, 1, IS_STRING, 0)
    ZEND_ARG_TYPE_INFO(0, blocks, IS_ARRAY, 0)
ZEND_END_ARG_INFO()
ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_string, 0, 1, IS_STRING, 0)
    ZEND_ARG_TYPE_INFO(0, content, IS_STRING, 0)
ZEND_END_ARG_INFO()
ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_parse, 0, 1, IS_ARRAY, 0)
    ZEND_ARG_TYPE_INFO(0, content, IS_STRING, 0)
ZEND_END_ARG_INFO()
ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_stats, 0, 0, IS_ARRAY, 0)
ZEND_END_ARG_INFO()
#include "fast-adapter.h"
ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_call, 0, 2, IS_ARRAY, 0)
    ZEND_ARG_TYPE_INFO(0, name, IS_STRING, 0)
    ZEND_ARG_TYPE_INFO(0, arguments, IS_ARRAY, 0)
    ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, context, IS_ARRAY, 0, "[]")
ZEND_END_ARG_INFO()
PHP_FUNCTION(elephc_wp_call) {
    zend_string *name;
    zval *args, *context = NULL, spec, copy;
    ZEND_PARSE_PARAMETERS_START(2,3)
        Z_PARAM_STR(name)
        Z_PARAM_ARRAY(args)
        Z_PARAM_OPTIONAL
        Z_PARAM_ARRAY(context)
    ZEND_PARSE_PARAMETERS_END();
    if(!input_trace && hybrid_fast_call(name,args,return_value)) { if(EG(exception))RETURN_THROWS();return; }
    uint64_t t0=profile_enabled?profile_clock():0;
    array_init(&spec);
    add_assoc_str(&spec,"name",zend_string_copy(name));
    ZVAL_COPY(&copy,args); add_assoc_zval(&spec,"args",&copy);
    if (context) { ZVAL_COPY(&copy,context); } else { array_init(&copy); }
    add_assoc_zval(&spec,"context",&copy);
    smart_str json={0};
    zend_result encoded=php_json_encode(&json,&spec,PHP_JSON_PRESERVE_ZERO_FRACTION);
    zval_ptr_dtor(&spec);
    if (encoded!=SUCCESS) {
        smart_str_free(&json);
        zend_throw_error(NULL,"Cannot encode native call input");
        RETURN_THROWS();
    }
    smart_str_0(&json);
    size_t input_length=ZSTR_LEN(json.s),length=0;
    if(input_trace) { fwrite(ZSTR_VAL(json.s),1,input_length,input_trace);fputc('\n',input_trace); }
    char *output=NULL;
    uint64_t t1=profile_enabled?profile_clock():0;
    int32_t status=ep_dispatch(ZSTR_VAL(json.s),input_length,&output,&length);
    uint64_t t2=profile_enabled?profile_clock():0;
    smart_str_free(&json);
    if(native_error(status)) RETURN_THROWS();
    zend_result decoded=php_json_decode_ex(return_value,output,length,PHP_JSON_OBJECT_AS_ARRAY,512);
    elephc_free(output);
    if(decoded!=SUCCESS || Z_TYPE_P(return_value)!=IS_ARRAY) {
        zend_throw_error(NULL,"Invalid native call envelope for %s",ZSTR_VAL(name));
        RETURN_THROWS();
    }
    if(profile_enabled)profile_record(name,t1-t0,t2-t1,profile_clock()-t2);
    hybrid_record(name,input_length,length,false);
}

PHP_FUNCTION(elephc_wpautop) {
    zend_string *input;
    bool br = true;
    ZEND_PARSE_PARAMETERS_START(1, 2)
        Z_PARAM_STR(input)
        Z_PARAM_OPTIONAL
        Z_PARAM_BOOL(br)
    ZEND_PARSE_PARAMETERS_END();
    char *output = NULL;
    size_t length = 0;
    if (native_error(ep_autop(ZSTR_VAL(input), ZSTR_LEN(input), br, &output, &length))) RETURN_THROWS();
    zend_string *result = zend_string_init(output, length, 0);
    elephc_free(output);
    ++autop_calls;
    input_bytes += ZSTR_LEN(input);
    output_bytes += length;
    RETURN_STR(result);
}

PHP_FUNCTION(elephc_wp_parse_blocks) {
    zend_string *input;
    ZEND_PARSE_PARAMETERS_START(1, 1)
        Z_PARAM_STR(input)
    ZEND_PARSE_PARAMETERS_END();
    char *output = NULL;
    size_t length = 0;
    if (native_error(ep_parse_json(ZSTR_VAL(input), ZSTR_LEN(input), &output, &length))) RETURN_THROWS();
    zend_result decoded = php_json_decode_ex(return_value, output, length, PHP_JSON_OBJECT_AS_ARRAY, 512);
    elephc_free(output);
    if (decoded != SUCCESS || Z_TYPE_P(return_value) != IS_ARRAY) {
        zend_throw_error(NULL, "Native parser returned invalid block JSON");
        RETURN_THROWS();
    }
    ++parser_calls;
    input_bytes += ZSTR_LEN(input);
    output_bytes += length;
}

PHP_FUNCTION(elephc_wp_identity) {
    zend_string *input;
    ZEND_PARSE_PARAMETERS_START(1, 1)
        Z_PARAM_STR(input)
    ZEND_PARSE_PARAMETERS_END();
    char *output = NULL;
    size_t length = 0;
    if (native_error(ep_identity(ZSTR_VAL(input), ZSTR_LEN(input), &output, &length))) RETURN_THROWS();
    zend_string *result = zend_string_init(output, length, 0);
    elephc_free(output);
    ++identity_calls;
    input_bytes += ZSTR_LEN(input);
    output_bytes += length;
    RETURN_STR(result);
}

PHP_FUNCTION(elephc_wp_serialize_blocks) {
    zval *blocks;
    ZEND_PARSE_PARAMETERS_START(1, 1)
        Z_PARAM_ARRAY(blocks)
    ZEND_PARSE_PARAMETERS_END();
    smart_str json = {0};
    if (php_json_encode(&json, blocks, PHP_JSON_PRESERVE_ZERO_FRACTION) != SUCCESS) {
        smart_str_free(&json);
        zend_throw_error(NULL, "Cannot encode native serializer input");
        RETURN_THROWS();
    }
    smart_str_0(&json);
    size_t input_length = ZSTR_LEN(json.s);
    char *output = NULL;
    size_t length = 0;
    int32_t status = ep_serialize_json(ZSTR_VAL(json.s), input_length, &output, &length);
    smart_str_free(&json);
    if (native_error(status)) RETURN_THROWS();
    zend_string *result = zend_string_init(output, length, 0);
    elephc_free(output);
    ++serialize_calls;
    input_bytes += input_length;
    output_bytes += length;
    RETURN_STR(result);
}

PHP_FUNCTION(elephc_wp_stats) {
    ZEND_PARSE_PARAMETERS_NONE();
    array_init(return_value);
    add_assoc_long(return_value, "parser", parser_calls);
    add_assoc_long(return_value, "autop", autop_calls);
    add_assoc_long(return_value, "serialize", serialize_calls);
    add_assoc_long(return_value, "identity", identity_calls);
    add_assoc_long(return_value, "input_bytes", input_bytes);
    add_assoc_long(return_value, "output_bytes", output_bytes);
    zval calls;
    ZVAL_ARR(&calls,zend_array_dup(&native_calls));
    add_assoc_zval(return_value,"calls",&calls);
    zval fast;ZVAL_ARR(&fast,zend_array_dup(&fast_calls));add_assoc_zval(return_value,"fast_calls",&fast);
    if(profile_enabled) { zval times;ZVAL_ARR(&times,zend_array_dup(&profile_calls));add_assoc_zval(return_value,"profile",&times); }
}

static const zend_function_entry hybrid_functions[] = {
    PHP_FE(elephc_wpautop, arginfo_autop)
    PHP_FE(elephc_wp_serialize_blocks, arginfo_serialize)
    PHP_FE(elephc_wp_parse_blocks, arginfo_parse)
    PHP_FE(elephc_wp_identity, arginfo_string)
    PHP_FE(elephc_wp_stats, arginfo_stats)
    PHP_FE(elephc_wp_call, arginfo_call)
    PHP_FE_END
};

PHP_MINIT_FUNCTION(wp_elephc_hybrid) {
    if (elephc_abi_version() != ELEPHC_ABI_VERSION || elephc_init() != ELEPHC_STATUS_OK) return FAILURE;
    return SUCCESS;
}
PHP_MSHUTDOWN_FUNCTION(wp_elephc_hybrid) {
    elephc_shutdown();
    return SUCCESS;
}
PHP_RINIT_FUNCTION(wp_elephc_hybrid) {
    parser_calls = autop_calls = serialize_calls = identity_calls = input_bytes = output_bytes = 0;
    zend_hash_init(&native_calls,64,NULL,ZVAL_PTR_DTOR,0);
    zend_hash_init(&fast_calls,32,NULL,ZVAL_PTR_DTOR,0);
    zend_hash_init(&profile_calls,64,NULL,ZVAL_PTR_DTOR,0);
    profile_enabled=getenv("HYBRID_PROFILE")!=NULL;
    const char *trace_path=getenv("HYBRID_TRACE_INPUTS");
    input_trace=trace_path?fopen(trace_path,"w"):NULL;
    ep_request_reset();
    return SUCCESS;
}
PHP_RSHUTDOWN_FUNCTION(wp_elephc_hybrid) {
    zend_hash_destroy(&native_calls);
    zend_hash_destroy(&fast_calls);
    zend_hash_destroy(&profile_calls);
    if(input_trace) { fclose(input_trace);input_trace=NULL; }
    return SUCCESS;
}
zend_module_entry wp_elephc_hybrid_module_entry = {
    STANDARD_MODULE_HEADER,
    "wp_elephc_hybrid", hybrid_functions,
    PHP_MINIT(wp_elephc_hybrid), PHP_MSHUTDOWN(wp_elephc_hybrid),
    PHP_RINIT(wp_elephc_hybrid), PHP_RSHUTDOWN(wp_elephc_hybrid), NULL,
    "0.0.1-experiment", STANDARD_MODULE_PROPERTIES
};
ZEND_GET_MODULE(wp_elephc_hybrid)
