<?php

include_once '../orangedata_client.php'; // path to orangedata_client.php

try {
  $client = [
    'inn' => '7725327863',
//    'api_url' => '2443',
     'api_url' => 'https://apip.orangedata.ru:2443', // link access
    'sign_pkey' => dirname(__DIR__) . '/secure_path/private_key.pem',
    'ssl_client_key' => dirname(__DIR__) . '/secure_path/client.key',
    'ssl_client_crt' => dirname(__DIR__) . '/secure_path/client.crt',
    'ssl_ca_cert' => dirname(__DIR__) . '/secure_path/cacert.pem',
    'ssl_client_crt_pass' => 1234,
  ];

  $buyer = new orangedata\orangedata_client($client); // create new client

  // $buyer->is_debug(); // for write curl.log file

  $correction = [
      'ffdVersion' => 4, //required for FFD1.2
      'id' => '23423423',
      'inn' => '7725327863',
      'key' => '1234567',
      'correctionType' => 0,
      'type' => 1,
      'group' => 'main_2',
      'causeDocumentDate' => new \DateTime(),
      'causeDocumentNumber' => '56',
      'totalSum' => 1,
      'customerContact' => 'liza@ya.ru',
      'vat1Sum' => 0,
      'vat2Sum' => 0,
      'vat3Sum' => 0,
      'vat4Sum' => 0,
      'vat5Sum' => 0,
      'vat6Sum' => 0,
  ];

  $correctionPos = [
      "quantity" => 1.000,
      "price" => 1,
      "tax" => 6,
      "text" => "Булка",
      "excise" => 23.45,
      "paymentMethodType" => 4, "paymentSubjectType" => 1,
      "agentType" => 127,
      "agentInfo" => ["paymentTransferOperatorPhoneNumbers" => ["+79200000001", "+74997870001"], "paymentAgentOperation" => "Какая-то операция 1", "paymentAgentPhoneNumbers" => ["+79200000003"], "paymentOperatorPhoneNumbers" => ["+79200000002", "+74997870002"], "paymentOperatorName" => "ООО \"Атлант\"", "paymentOperatorAddress" => "Воронеж, ул. Недогонная, д. 84", "paymentOperatorINN" => "7727257386"]
  ];

  $correctionPayment =
      [
          'type' => 1,
          'amount' => 1,
      ];

  $correctionVending = [
      'automatNumber' => '21321321123',
      'settlementAddress' => 'Address',
      'settlementPlace' => 'Place',
  ];

  $additional = [
      'additionalAttribute' => 'Attribute',
      "customerInfo" =>[
            "name"=> "Кузнецов Иван Петрович",
            "inn"=> "7725327863",
            "birthDate"=> "15.09.1988",
            "citizenship"=> "643",
            "identityDocumentCode"=> "01",
            "identityDocumentData"=> "multipassport",
            "address"=> "Басеенная 36"
        ],
  ];

  $buyer->create_correction12($correction) // Create correction
      ->add_position_to_correction($correctionPos)
      ->add_payment_to_correction($correctionPayment)
      ->add_additional_attributes_to_correction($additional)
//      ->add_vending_to_correction($correctionVending)
  ;


  $result = $buyer->post_correction12(); // Send correction
  var_dump($result); // View response
} catch (Exception $ex) {
  echo 'Errors:' . PHP_EOL . $ex->getMessage();
}

/** View status of correction **/
try {
  $cor_status = $buyer->get_correction_status12('23423423');
  var_dump($cor_status);
} catch (Exception $ex) {
  echo 'Ошибка:' . PHP_EOL . $ex->getMessage();
}

?>
