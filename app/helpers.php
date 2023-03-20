<?php
  
function sendNotif($to, $title, $message, $payload=array()){
    $headers = array(
        'Content-Type:application/json',
        'Authorization:key='.env("FCM_KEY")
    );
    $param = array();   
    if(is_array($to)){
        $param['registration_ids']=$to;
    }else{
        $param['to']=$to;
    }
    $param['notification']=array('title'=>$title, 'body'=>$message);
    if(count($payload)>0)
    $param['data']=$payload;
    $ch = curl_init();
    curl_setopt( $ch,CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send' );
    curl_setopt( $ch,CURLOPT_POST, true );
    curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
    curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
    curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $param ) );
    $result = curl_exec($ch );
    curl_close( $ch );
    return $result;
}


function  codefikasiNomor($phone)
{
    $code_phone = substr($phone,0 , 4);
    $code = [
            '0853' => 'TELKOMSEL',
            '0811' => 'TELKOMSEL',
            '0812' => 'TELKOMSEL',
            '0813' => 'TELKOMSEL',
            '0821' => 'TELKOMSEL',
            '0822' => 'TELKOMSEL',
            '0851' => 'TELKOMSEL',
            '0856' => 'TELKOMSEL',
            '0895' => 'THREE',
            '0897' => 'THREE',
            '0898' => 'THREE',
            '0899' => 'THREE',
            '0818' => 'XL',
            '0819' => 'XL',
            '0859' => 'XL',
            '0877' => 'XL',
            '0878' => 'XL',
            '0882' => 'AXIS',
            '0883' => 'AXIS',
            '0884' => 'AXIS',
            '0885' => 'AXIS',
            '0886' => 'AXIS',
            '0887' => 'AXIS',
            '0888' => 'AXIS',
            '0889' => 'AXIS',
            '0882' => 'SMARTFREN',
            '0883' => 'SMARTFREN',
            '0884' => 'SMARTFREN',
            '0885' => 'SMARTFREN',
            '0886' => 'SMARTFREN',
            '0887' => 'SMARTFREN',
            '0888' => 'SMARTFREN',
        ];
    return $code[$code_phone] ?? null;
}
