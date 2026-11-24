<?php
$results = array();
foreach (array('acap', 'ACAP', 'dict', 'DICT', 'http', 'HTTP', 'https', 'HTTPS', '', 'ftp', ' http', null, false, 80, array()) as $type) {
    try { $results[] = array('port', \WpOrg\Requests\Port::get($type)); }
    catch (Throwable $error) { $results[] = array('error', get_class($error), $error->getMessage()); }
}
$response_reflection = new ReflectionClass(\WpOrg\Requests\Response::class);
foreach(array(false,0,null,200,201,299,300,301,302,303,304,305,306,307,308,399,400,401,403,404,418,429,500,503,511,599,'300','307','308','404') as $response_status) {
    foreach(array(false,true) as $response_success) foreach(array(true,false,null,0,1,'yes') as $response_allow) {
        $response=response_probe_instance($response_reflection->newInstanceWithoutConstructor());
        $response->status_code=response_probe_mixed($response_status);
        $response->success=$response_success;
        $response->body='response-data';
        $response_redirect=$response->is_redirect();
        try {
            $response_return=$response->throw_for_status($response_allow);
            $response_outcome=array('ok',$response_return);
        } catch(\WpOrg\Requests\Exception $response_error) {
            $response_reason=$response_error instanceof \WpOrg\Requests\Exception\Http?$response_error->getReason():null;
            $response_outcome=array('error',get_class($response_error),$response_error->getMessage(),$response_error->getType(),
                $response_error->getCode(),$response_reason,$response_error->getData()===$response,
                response_probe_instance($response_error->getData())->body);
        }
        $results[]=array($response_status,$response_success,$response_allow,$response_redirect,$response_outcome);
    }
}
echo json_encode($results, JSON_THROW_ON_ERROR), "\n";
