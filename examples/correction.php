<?php

include_once '../orangedata_client.php'; // path to orangedata_client.php

try {
  $client = [
    'inn' => '0123456789',
    'api_url' => '2443',
    // 'api_url' => 'https://apip.orangedata.ru:2443', // link access
    'sign_pkey' => dirname(__DIR__) . '/secure_path/private_key.pem',
    'ssl_client_key' => dirname(__DIR__) . '/secure_path/client.key',
    'ssl_client_crt' => dirname(__DIR__) . '/secure_path/client.crt',
    'ssl_ca_cert' => dirname(__DIR__) . '/secure_path/cacert.pem',
    'ssl_client_crt_pass' => 1234,
  ];

  $buyer = new orangedata\orangedata_client($client); // create new client

  // $buyer->is_debug(); // for write curl.log file

  $correction = [
    'id' => '23423423',
    'key' => '1234567',
    'correctionType' => 0,
    'type' => 1,
    'description' => 'cashier error',
    'causeDocumentDate' => new \DateTime(),
    'causeDocumentNumber' => '56ce',
    'totalSum' => 567.9,
    'cashSum' => 567,
    'eCashSum' => 0.9,
    'prepaymentSum' => 0,
    'postpaymentSum' => 0,
    'otherPaymentTypeSum' => 0,
    'tax1Sum' => 0,
    'tax2Sum' => 0,
    'tax3Sum' => 0,
    'tax4Sum' => 0,
    'tax5Sum' => 0,
    'tax6Sum' => 0,
    'taxationSystem' => 2,
  ];

  $correctionVending = [
    'automatNumber' => '21321321123',
    'settlementAddress' => 'Address',
    'settlementPlace' => 'Place',
  ];

  $buyer->create_correction($correction)->add_vending_to_correction($correctionVending); // Create correction

  $result = $buyer->post_correction(); // Send correction
  var_dump($result); // View response
} catch (Exception $ex) {
  echo 'Errors:' . PHP_EOL . $ex->getMessage();
}

/** View status of correction **/
try {
  $cor_status = $buyer->get_correction_status('23423423');
  var_dump($cor_status);
} catch (Exception $ex) {
  echo 'Ошибка:' . PHP_EOL . $ex->getMessage();
}

?>
