"""Generate fixed class method dispatch with explicit state transport."""
import re

def arguments(params, container='$args', error_import=False):
    fields=re.split(r',\s*(?![^()]*\))',params.strip()) if params.strip() else []
    result=[]
    for i,field in enumerate(fields):
        match=re.match(r'\s*(\??\w+)?\s*&?\s*\$(\w+)(?:\s*=\s*(.*))?',field,re.S)
        if not match:raise ValueError(field)
        typ,name,default=match.groups();value=container+'['+str(i)+']'
        if default is not None:value='(array_key_exists('+str(i)+','+container+') ? '+value+' : '+default+')'
        if typ in ['string','int','float','bool','array']:value='('+typ+')'+value
        elif typ and typ.startswith('?'):value='('+value+' === null ? null : ('+typ[1:]+')'+value+')'
        elif typ=='WP_Error':value='ep_import_error((array)'+value+')'
        result.append(value)
    return result

def generate(ROOT,CORE,tools,add):
    specs=[('error','WP_Error','class-wp-error.php','ep_error_',None),('http','WP_HTTP_Response','class-wp-http-response.php','ep_http_',None),('response','WP_REST_Response','rest-api/class-wp-rest-response.php','ep_response_','http'),('request','WP_REST_Request','rest-api/class-wp-rest-request.php','ep_request_',None)]
    protocol=[]; routes=[]; records={}; methods={}
    readers={
        'error':['get_error_codes','get_error_code','get_error_messages','get_error_message','get_error_data','has_errors','get_all_error_data'],
        'http':['get_headers','get_status','get_data','jsonSerialize'],
        'response':['get_links','get_matched_route','get_matched_handler','is_error','as_error','get_curies'],
        'request':['get_method','is_method','get_headers','get_header','get_header_as_array','get_content_type','get_url_params','get_query_params','get_body_params','get_file_params','get_default_params','get_body','is_json_content_type','get_route','get_attributes'],
    }
    for kind,cls,rel,prefix,parent in specs:
        path=CORE/rel;raw=path.read_bytes();entries=tools.functions(path)
        class_code, funcs=tools.flatten(path,cls,prefix)
        if kind=='request':
            class_code=class_code.replace(' implements ArrayAccess','')
            funcs=[f for f in funcs if not any(f.startswith('function '+prefix+name+'(') for name in ['sanitize_params','has_valid_params','from_url'])]
        if parent:
            for name in methods[parent]:
                if name in entries:continue
                pattern=r'(\$\w+)->'+re.escape(name)+r'\(\s*'
                for i,body in enumerate(funcs):
                    funcs[i]=re.sub(pattern,lambda m:'ep_http_'+name+'('+m[1]+('' if body[m.end():].startswith(')') else ', '),body)
        if kind=='response':
            for i,body in enumerate(funcs):
                body=re.sub(r'(\$\w+)->add\(\s*',lambda m:'ep_error_add('+m[1]+', ',body)
                funcs[i]=body
        # The error class was already added to the scalar-validator graph.
        if kind!='error':add(path,class_code+'\n\n'+'\n\n'.join(funcs))
        properties={m[2]:m[1] for m in re.findall(r'(?m)^\s*(public|protected|private)\s+(\??\w+)\s+\$(\w+)',path.read_text())}
        properties={m.group(2):m.group(1) for m in re.finditer(r'(?m)^\s*(?:public|protected|private)\s+(\??\w+)\s+\$(\w+)',path.read_text())}
        if parent:properties={**records[parent],**properties}
        records[kind]=properties;methods[kind]=entries
        factory='new '+cls+'()'
        imports=['function ep_import_'+kind+'(array $state): '+cls+' { $object='+factory+';']
        for name,typ in properties.items():
            val='$state[\''+name+'\']'
            if typ in ['array','bool','string','int','float']:val='('+typ+')'+val
            elif typ.startswith('?'):val='('+val+' === null ? null : ('+typ[1:]+')'+val+')'
            imports.append("if(array_key_exists('"+name+"',$state)) { $object->"+name+'='+val+'; }')
        imports.append('return $object; }')
        protocol.append('\n'.join(imports))
        protocol.append('function ep_export_'+kind+'('+cls+' $object): array { return ['+','.join("'"+n+"'=>$object->"+n for n in properties)+']; }')
        protocol.append('function ep_retained_'+kind+'(int $id,array $state): '+cls+' { global $ep_handles_'+kind+'; $handles=(array)$ep_handles_'+kind+'; $candidate=$handles[$id]??null; if($candidate instanceof '+cls+' && !$state)return $candidate; $object=ep_import_'+kind+'($state); $handles[$id]=$object; $ep_handles_'+kind+'=$handles; return $object; }')
        readonly='['+','.join("'"+name+"'" for name in readers[kind])+']'
        operation=['function ep_operation_'+kind+'(string $method,array $payload): mixed {','global $ep_object_state,$ep_second_state,$ep_context;','$handle=(int)($ep_context["handle"]??0); $object=$handle>0?ep_retained_'+kind+'($handle,(array)$payload[0]):ep_import_'+kind+'((array)$payload[0]); $args=(array)$payload[1]; $value=ep_box(null);', '$readonly=in_array($method,'+readonly+',true); $before=wordpress_lists_map(); if($handle>0 && !$readonly)$before=ep_export_'+kind+'($object);','switch($method) {']
        selected=[]
        for name,entry in entries.items():
            declaration=raw[entry['line_start']:entry['start']].decode()
            if name=='__construct' or 'public' not in declaration or 'static' in declaration:continue
            if kind=='request' and name in ['sanitize_params','has_valid_params','from_url']:continue
            if name in ['merge_from','export_to']:
                statement='global $ep_context; $other=($ep_context["self_transfer"]??false)?$object:ep_import_error((array)$args[0]); '+prefix+name+'($object,$other); $ep_second_state=ep_export_error($other);'
            else:
                call=prefix+name+'($object'+(' ,'+','.join(arguments(entry['params'])) if entry['params'].strip() else '')+')'
                statement=call+';' if entry['return_type']==': void' else '$value='+call+';'
            operation.append("case '"+name+"': "+statement+' break;')
            routes.append(cls+'::'+name);selected.append(name)
        operation.extend(["default: throw new InvalidArgumentException('Unsupported native object method.');",'}', '$ep_object_state=[]; if($handle===0)$ep_object_state=ep_export_'+kind+'($object); elseif(!$readonly) { $after=ep_export_'+kind+'($object); foreach($after as $key=>$item) { if(!array_key_exists($key,$before)||$before[$key]!==$item)$ep_object_state[$key]=$item; } } return $value; }'])
        protocol.append('\n'.join(operation))
    branches=[]
    protocol.append('function ep_objects_reset(): void { '+''.join('global $ep_handles_'+kind+'; $ep_handles_'+kind+'=[]; ' for kind,cls,rel,prefix,parent in specs)+'}')
    for kind,cls,rel,prefix,parent in specs:
        branches.append("if(str_starts_with($name,'"+cls+"::')) { return ep_operation_"+kind+"(substr($name,"+str(len(cls)+2)+"),$a); }")
    return '\n\n'.join(protocol),branches,routes,records
