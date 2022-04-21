<?php

include_once '/../orangedata_client.php'; // path to orangedata_client.php

$buyer = null;

try {
  $client = [
    'inn' => '7725327863',
    'api_url' => '2443',
    // 'api_url' => 'https://apip.orangedata.ru:2443', // link access
    'sign_pkey' => dirname(__DIR__) . '/examples/secure_path/private_key.pem',
    'ssl_client_key' => dirname(__DIR__) . '/examples/secure_path/client.key',
    'ssl_client_crt' => dirname(__DIR__) . '/examples/secure_path/client.crt',
    'ssl_ca_cert' => dirname(__DIR__) . '/examples/secure_path/cacert.pem',
    'ssl_client_crt_pass' => 1234,
  ];

  $buyer = new orangedata\orangedata_client($client); // create new client

  // $buyer->is_debug(); // for write curl.log file

  $order = [
      'ffdVersion' => 4,
      'id' => '1238',
      'type' => 1,
      'customerContact' => 'example@example.com',
      'taxationSystem' => 1,
      'key' => '7725327863',
      'group' => 'main_2'
  ];

  $position = [
      'quantity' => '2',
      'price' => 1.44,
      'tax' => 1,
      'text' => 'some text',
      'paymentMethodType' => 4,
      'paymentSubjectType' => 1,
      'nomenclatureCode' => null,
      'supplierInfo' => [
          'phoneNumbers' => ['+79266660011', '+79293456723'],
          'name' => 'PAO Example',
      ],
      'agentType' => 127,
      'agentInfo' => [
          'paymentTransferOperatorPhoneNumbers' => ['+79266660011', '+79293456723'],
          'paymentAgentOperation' => 'some operartion',
          'paymentAgentPhoneNumbers' => ['+79266660011', '+79293456723'],
          'paymentOperatorPhoneNumbers' => ['+79266660011'],
          'paymentOperatorName' => 'OAO ATLANT',
          'paymentOperatorAddress' => 'Address',
          'paymentOperatorInn' => 1234567890,
      ],
      'additionalAttribute' => 'attribute',
      'manufacturerCountryCode' => '534',
      'customsDeclarationNumber' => 'AD 11/77 from 01.08.2018',
      'excise' => '12.43',
      "unitTaxSum" => 0.23,
      "plannedStatus" => 2,
      "industryAttribute" => [
          "foivId" => "012",
          "causeDocumentDate" => "10.08.2021",
          "value" => "position industry"
      ],
      "barcodes" => [
          "ean8" => "46198532",
          "ean13" => "4006670128002",
          "itf14" => "14601234567890",
          "mi" => "RU-401301-AAA0277031",
          "egais20" => "NU5DBKYDOT17ID980726019",
          "egais30" => "13622200005881",
          "f1" => '898989',
          "f2" => null,
          "f3" => null,
          "f4" => null,
          "f5" => null,
          "f6" => null,
      ]
  ];

  $payment = [
    'type' => 16,
    'amount' => 2.88,
  ];

  $userAttribute = [
    'name' => 'Like',
    'value' => 'Example',
  ];

  $additional = [
    'additionalAttribute' => 'Attribute',
      "customerInfo" =>[
          "name"=> "Кузнецов Иван Петрович",
          "inn"=> "828021964975",
          "birthDate"=> "15.09.1988",
          "citizenship"=> "643",
          "identityDocumentCode"=> "01",
          "identityDocumentData"=> "multipassport",
          "address"=> "Басеенная 36"
      ],
  ];

  $vending = [
    'automatNumber' => '21321321123',
    'settlementAddress' => 'Address',
    'settlementPlace' => 'Place',
  ];

  /** Create client new order **/
  $buyer->create_order($order)
        ->add_position_to_order($position)
        ->add_payment_to_order($payment)
        ->add_user_attribute($userAttribute)
        ->add_additional_attributes($additional)
        //->add_vending_to_order($vending)
  ;

  $result = $buyer->send_order(); // Send order
  var_dump($result); // View response

  /** View status of order **/
  echo "Order status:" . PHP_EOL;
  $order_status = $buyer->get_order_status(1238);
  var_dump($order_status);

} catch (Exception $ex) {
  echo 'Errors:' . PHP_EOL . $ex->getMessage();
}

