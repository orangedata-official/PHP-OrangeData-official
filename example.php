
<?php

include_once 'orangedata_client.php';
$api_url='https://apip.orangedata.ru:2443/api/v2/documents/';

$sign_pkey = getcwd().'/secure_path/private_key.pem';

//create new client
$byer = new orangedata\orangedata_client('0123456789', //ИНН
        $api_url,
        $sign_pkey,//private key for signing
        getcwd() . '/secure_path/client_ca.crt', //path to CA CERTIFICATE
        getcwd() . '/secure_path/client.pem',//path to Client cert in PEM
        'Str0ngP@$$w0rD'); //password for client cert 

// create client new order, add positions , add payment, send request
$result = $byer
        ->is_debug()
        ->set_ca_cert(getcwd() . '/secure_path/client_ca.crt')//path to CA CERTIFICATE
        ->create_order(2, 1, 'a@b',1)
        ->add_position_to_order(6,10,1,'mathes')
        ->add_payment_to_order(1, 10)
        ->add_payment_to_order(2, 50)
        ->send_order();
//view response
var_dump($result);

//view status of order 
var_dump($byer->get_order_status(2));
?>
