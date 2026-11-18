/* Typed transport keeps the original WordPress work in the Elephc library. */
static void hybrid_count(HashTable *table,zend_string *name) {
    zval *counter=zend_hash_find(table,name);
    if(counter)++Z_LVAL_P(counter);
    else { zval initial;ZVAL_LONG(&initial,1);zend_hash_add_new(table,name,&initial); }
}
static void hybrid_record(zend_string *name,size_t input_length,size_t output_length,bool fast) {
    hybrid_count(&native_calls,name);
    if(fast)hybrid_count(&fast_calls,name);
    if(zend_string_equals_literal(name,"parse_blocks"))++parser_calls;
    if(zend_string_equals_literal(name,"wpautop"))++autop_calls;
    if(zend_string_equals_literal(name,"serialize_blocks"))++serialize_calls;
    input_bytes+=input_length;output_bytes+=output_length;
}
static bool hybrid_fast_call(zend_string *name,zval *args,zval *result) {
    zval *first=zend_hash_index_find(Z_ARRVAL_P(args),0),*second=zend_hash_index_find(Z_ARRVAL_P(args),1);
    int op=-1;bool flag=true;uint64_t t0=profile_enabled?profile_clock():0,t1,t2;
    char *output=NULL;size_t output_length=0,input_length=0;int32_t status;
    if(zend_string_equals_literal(name,"wp_is_numeric_array")) {
        bool array=first&&Z_TYPE_P(first)==IS_ARRAY;
        size_t count=array?zend_hash_num_elements(Z_ARRVAL_P(first)):0;
        zend_string *types=zend_string_alloc(count,0);size_t i=0;
        if(array) { zend_ulong index;zend_string *key;ZEND_HASH_FOREACH_KEY(Z_ARRVAL_P(first),index,key) { ZSTR_VAL(types)[i++]=key?'s':'i'; } ZEND_HASH_FOREACH_END(); }
        ZSTR_VAL(types)[count]='\0';int64_t value=0;
        t1=profile_enabled?profile_clock():0;
        value=ep_fast_numeric_keys(ZSTR_VAL(types),count,array);status=elephc_last_status();
        t2=profile_enabled?profile_clock():0;zend_string_release(types);
        if(native_error(status))return true;
        array_init(result);add_assoc_string(result,"kind","native");add_assoc_bool(result,"value",value!=0);
        hybrid_record(name,count,sizeof(value),true);
    } else if(zend_string_equals_literal(name,"absint")&&first&&Z_TYPE_P(first)==IS_LONG&&Z_LVAL_P(first)!=ZEND_LONG_MIN) {
        int64_t value=0;t1=profile_enabled?profile_clock():0;
        value=ep_fast_absint(Z_LVAL_P(first));status=elephc_last_status();t2=profile_enabled?profile_clock():0;
        if(native_error(status))return true;
        array_init(result);add_assoc_string(result,"kind","native");add_assoc_long(result,"value",value);
        hybrid_record(name,sizeof(int64_t),sizeof(value),true);
    } else {
        if(zend_string_equals_literal(name,"wpautop")) { op=0;flag=second?zend_is_true(second):true; }
        else if(zend_string_equals_literal(name,"wp_strip_all_tags")) { op=1;flag=second?zend_is_true(second):false; }
        else if(zend_string_equals_literal(name,"wp_kses_no_null")) {
            op=2;
            if(second) {
                if(Z_TYPE_P(second)!=IS_ARRAY)return false;
                zval *mode=zend_hash_str_find(Z_ARRVAL_P(second),"slash_zero",10);
                if(mode) { if(Z_TYPE_P(mode)!=IS_STRING)return false; if(zend_string_equals_literal(Z_STR_P(mode),"keep"))flag=false;else if(!zend_string_equals_literal(Z_STR_P(mode),"remove"))return false; }
            }
        } else if(zend_string_equals_literal(name,"wp_spaces_regexp"))op=3;
        else if(zend_string_equals_literal(name,"wp_make_link_relative"))op=4;
        else if(zend_string_equals_literal(name,"get_html_split_regex"))op=5;
        else if(zend_string_equals_literal(name,"unescape_invalid_shortcodes"))op=6;
        bool parser=zend_string_equals_literal(name,"parse_blocks");
        bool serializer=zend_string_equals_literal(name,"serialize_blocks");
        if(op<0&&!parser&&!serializer)return false;
        if(op!=3&&op!=5&&(!first||(!serializer&&Z_TYPE_P(first)!=IS_STRING)))return false;
        smart_str json={0};const char *input="";
        if(serializer) {
            if(Z_TYPE_P(first)!=IS_ARRAY)return false;
            if(php_json_encode(&json,first,PHP_JSON_PRESERVE_ZERO_FRACTION)!=SUCCESS) { smart_str_free(&json);zend_throw_error(NULL,"Cannot encode native serializer input");return true; }
            smart_str_0(&json);input=ZSTR_VAL(json.s);input_length=ZSTR_LEN(json.s);
        } else if(first&&Z_TYPE_P(first)==IS_STRING) { input=Z_STRVAL_P(first);input_length=Z_STRLEN_P(first); }
        t1=profile_enabled?profile_clock():0;
        if(parser)status=ep_parse_json(input,input_length,&output,&output_length);
        else if(serializer)status=ep_serialize_json(input,input_length,&output,&output_length);
        else status=ep_fast_text(op,input,input_length,flag,&output,&output_length);
        t2=profile_enabled?profile_clock():0;smart_str_free(&json);
        if(native_error(status))return true;
        zval value;
        if(parser) {
            ZVAL_NULL(&value);
            if(php_json_decode_ex(&value,output,output_length,PHP_JSON_OBJECT_AS_ARRAY,512)!=SUCCESS) { elephc_free(output);zval_ptr_dtor(&value);zend_throw_error(NULL,"Invalid typed parser result");return true; }
        } else ZVAL_STRINGL(&value,output,output_length);
        elephc_free(output);
        array_init(result);add_assoc_string(result,"kind","native");add_assoc_zval(result,"value",&value);
        hybrid_record(name,input_length,output_length,true);
    }
    if(profile_enabled)profile_record(name,t1-t0,t2-t1,profile_clock()-t2);
    return true;
}
