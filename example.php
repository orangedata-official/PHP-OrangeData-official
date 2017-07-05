
<?php

include_once 'orangedata_client.php';

$api_url='https://apip.orangedata.ru:2443/api/v2/documents/';
$sign_pkey = getcwd().'/secure_path/private_key.pem';//private key for signing
$ssl_client_key = getcwd().'/secure_path/client.key';//path to client private key for ssl
$ssl_client_crt = getcwd().'/secure_path/client.crt';//path to client certificate for ssl
$ssl_client_crt_pass = 'password';//password for client certificate for ssl
$inn = '0123456789';//ИНН
//
//create new client
$byer = new orangedata\orangedata_client($inn, 
        $api_url,
        $sign_pkey,
        $ssl_client_key,
        $ssl_client_crt,
        $ssl_client_crt_pass); //password for client cert 

//for sandbox
$byer->is_debug()->set_ca_cert(getcwd() . '/secure_path/server.crt'); //path to ca certificate for suport selfsigned certificates
   

// create client new order, add positions , add payment, send request
$result = $byer
        ->create_order(2, 1, 'a@b',1)
        ->add_position_to_order(6,10,1,'matches')
        ->add_payment_to_order(1, 10)
        ->add_payment_to_order(2, 50)
        ->send_order();
//view response
var_dump($result);

//view status of order 
var_dump($byer->get_order_status(2));
?>
