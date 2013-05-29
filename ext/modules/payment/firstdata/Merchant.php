<?php

class Merchant {

    var $url;

    var $keystore;
    
    var $keystorepassword;

    var $verbose;

    function Merchant($url, $keystore, $keystorepassword, $verbose = 0)
    {
        $this->url = $url;
        $this->keystore = $keystore;
        $this->keystorepassword = $keystorepassword;
        $this->verbose = $verbose;
    }

  function sentPost($params){

if(!file_exists($this->keystore)){
$result = "file " . $this->keystore . " not exists";
error_log($result);
return $result;
}

if(!is_readable($this->keystore)){
$result = "Please check CHMOD for file \"" . $this->keystore . "\"! It must be readable!";
error_log($result);
return $result;
}

$post = "";

foreach ($params as $key => $value){
$post .= "$key=$value&";
}


$curl = curl_init();
if($this->verbose){
curl_setopt($curl, CURLOPT_VERBOSE, TRUE);
}

curl_setopt($curl, CURLOPT_URL, $this->url);
curl_setopt($curl, CURLOPT_HEADER, 0);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);

curl_setopt($curl, CURLOPT_SSLCERT, $this->keystore);
curl_setopt($curl, CURLOPT_CAINFO, $this->keystore);
curl_setopt($curl, CURLOPT_SSLKEYPASSWD, $this->keystorepassword);
curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
$result =curl_exec ($curl);

if(curl_error($curl)){
$result = curl_error($curl);
error_log($result);
}
curl_close ($curl);
return $result;
}

function startSMSTrans($amount, $currency, $ip, $desc, $language){

$params = array(
'command' => 'v',
'amount' => $amount,
'currency'=> $currency,
'client_ip_addr' => $ip,
'description' => $desc,
'language'=> $language  
);
return $this->sentPost($params);
}

function startDMSAuth($amount, $currency, $ip, $desc, $language){

$params = array(
'command' => 'a',
'msg_type'=> 'DMS',
'amount' => $amount,
'currency'=> $currency,
'client_ip_addr' => $ip,
'description' => $desc,
'language' => $language,
);
return $this->sentPost($params);
}

function makeDMSTrans($auth_id, $amount, $currency, $ip, $desc, $language){

$params = array(
'command' => 't',
'msg_type'=> 'DMS',
'trans_id' => $auth_id,
'amount' => $amount,
'currency'=> $currency,
'client_ip_addr' => $ip	
);

$str = $this->sentPost($params);
return $str;
}

function getTransResult($trans_id, $ip){

$params = array(
'command' => 'c',
'trans_id' => $trans_id,
'client_ip_addr' => $ip
);

$str = $this->sentPost($params);
return $str;
}

function reverse($trans_id, $amount){

$params = array(
'command' => 'r',
'trans_id' => $trans_id,
'amount' => $amount
);

$str = $this->sentPost($params);
return $str;
}

function closeDay(){

$params = array(
'command' => 'b',
);

$str = $this->sentPost($params);
return $str;
}
}
?>
