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

  $order = [
    'id' => '23423423434',
    'type' => 1,
    'customerContact' => 'example@example.com',
    'taxationSystem' => 1,
    'key' => '1234567',
    'group' => 'Main',
  ];

  $position = [
    'quantity' => '10',
    'price' => 100,
    'tax' => 1,
    'text' => 'some text',
    'paymentMethodType' => 3,
    'paymentSubjectType' => 1,
    'nomenclatureCode' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/',
    'supplierInfo' => [
      'phoneNumbers' => ['+79266660011', '+79293456723'],
      'name' => 'PAO Example',
    ],
    'supplierINN' => 1234567890,
    'agentType' => 127,
    'agentInfo' => [
      'paymentTransferOperatorPhoneNumbers' => ['+79266660011', '+79293456723'],
      'paymentAgentOperation' => 'some operartion',
      'paymentAgentPhoneNumbers' => ['+79266660011', '+79293456723'],
      'paymentOperatorPhoneNumbers' => ['+79266660011'],
      'paymentOperatorName' => 'OAO ATLANT',
      'paymentOperatorAddress' => 'Address',
      'paymentOperatorINN' => 1234567890,
    ],
    'unitOfMeasurement' => 'kg',
    'additionalAttribute' => 'attribute',
    'manufacturerCountryCode' => '534',
    'customsDeclarationNumber' => 'AD 11/77 from 01.08.2018',
    'excise' => '12.43',
  ];

  $payment = [
    'type' => 16,
    'amount' => 131.23,
  ];

  $agent = [
    'agentType' => 127,
    'paymentTransferOperatorPhoneNumbers' => ['+79998887766', '+76667778899'],
    'paymentAgentOperation' => 'Operation',
    'paymentAgentPhoneNumbers' => ['+79998887766'],
    'paymentOperatorPhoneNumbers' => ['+79998887766'],
    'paymentOperatorName' => 'Name',
    'paymentOperatorAddress' => 'ulitsa Adress, dom 7',
    'paymentOperatorINN' => '3123011520',
    'supplierPhoneNumbers' => ['+79998887766', '+76667778899'],
  ];

  $userAttribute = [
    'name' => 'Like',
    'value' => 'Example',
  ];

  $additional = [
    'additionalAttribute' => 'Attribute',
    'customer' => 'Ivanov Ivan',
    'customerINN' => '0987654321',
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
        ->add_agent_to_order($agent)
        ->add_user_attribute($userAttribute)
        ->add_additional_attributes($additional)
        ->add_vending_to_order($vending);

  $result = $buyer->send_order(); // Send order
  var_dump($result); // View response
} catch (Exception $ex) {
  echo 'Errors:' . PHP_EOL . $ex->getMessage();
}

/** View status of order **/
try {
  $order_status = $buyer->get_order_status(23423423434);
  var_dump($order_status);
} catch (Exception $ex) {
  echo 'Ошибка:' . PHP_EOL . $ex->getMessage();
}

?>
