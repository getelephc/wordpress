<?php

$rows=array();
$mailer=new \PHPMailer\PHPMailer\PHPMailer();
foreach(array('en','not-a-code','zz','dk','pt_BR','sr_Latn_RS','zh_CN','fr')as$code){
    $found=\PHPMailer\PHPMailer\PHPMailer::setLanguage($code);
    $language=$mailer->getTranslations();
    $copy=$language;$copy['authenticate']='local copy';
    $rows[]=array($code,$found,$language,$copy['authenticate'],$mailer->getTranslations(),array_keys($language));
}
class WordPressSmtpErrorProbe extends \PHPMailer\PHPMailer\SMTP {
    public function store(mixed $message,mixed $detail,mixed $code,mixed $extended): mixed {
        $this->setError($message,$detail,$code,$extended);
        return $this->getError();
    }
}
$smtp=new WordPressSmtpErrorProbe();
foreach(array(array('first','details','550','5.1.1'),array("A\0é",'',451,'4.0.0'))as$input){
    $before=$smtp->getError();$saved=json_encode($before);
    $changed=(array)$smtp->store($input[0],$input[1],$input[2],$input[3]);
    $changed['error']='local copy';
    $rows[]=array('smtp-error',$before,$saved,json_encode($before),$changed,$smtp->getError());
}
echo json_encode($rows,JSON_THROW_ON_ERROR),"\n";
