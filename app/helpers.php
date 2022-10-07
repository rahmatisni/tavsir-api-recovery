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

?>