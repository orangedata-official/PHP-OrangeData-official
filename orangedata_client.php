<?php

/**
 * OrangeDataClient PHP
 * Version of PHP: 5.6.6+
 * Version of Protocol: 2.22.0
 * @version 3.0.0
 */

namespace orangedata;

use \DateTime;
use \Exception;

class orangedata_client {
  const MAX_ID_LENGTH = 64;
  const MAX_GROUP_LENGTH = 32;
  const MAX_KEY_LENGTH = 32;
  const MAX_POSITION_TEXT_LENGTH = 129;
  const MAX_POSITION_SUPPLIER_NAME_LENGTH = 239;
  const MAX_PAYMENT_AGENT_OPERATION_LENGTH = 24;
  const MAX_PAYMENT_OPERATOR_NAME_LENGTH = 64;
  const MAX_PAYMENT_OPERATOR_ADDRESS_LENGTH = 243;
  const MAX_POSITION_UNIT_OF_MEASUREMENT_LENGTH = 16;
  const MAX_POSITION_ADDITIONAL_ATTRIBUTE_LENGTH = 64;
  const MAX_POSITION_MANUFACTURE_COUNTRY_CODE_LENGTH = 3;
  const MAX_POSITION_CUSTOMS_DECLARATION_NUMBER = 32;
  const MAX_ADDITIONAL_USER_ATTRIBUTE_NAME_LENGTH = 64;
  const MAX_ADDITIONAL_USER_ATTRIBUTE_VALUE_LENGTH = 234;
  const MAX_ADDITIONAL_ATTRIBUTE_LENGTH = 16;
  const MAX_CUSTOMER_LENGTH = 243;
  const MAX_VENDING_AUTOMAT_NUMBER_LENGTH = 20;
  const MAX_VENDING_ADDRESS_LENGTH = 243;
  const MAX_VENDING_PLACE_LENGTH = 243;
  const MAX_CORRECTION_DESCRIPTION_LENGTH = 243;
  const MAX_CORRECTION_CAUSE_DOCUMENT_NUMBER = 32;

  private $order_request;
  private $correction_request;
  private $api_url;
  private $inn;
  private $debug_file;
  private $debug = false;
  private $ca_cert = false;
  private $private_key_pem;
  private $client_pkey;
  private $client_cert;
  private $client_cert_pass;

  /**
   * @param string $inn
   * @param mixed $api_url
   * @param string $sign_pkey - Path to signing private key or his PEM body
   * @param string $client_key - Path to client private key
   * @param string $client_cert - Path to Client 2SSL Certificate
   * @param string $ca_cert - Path to CA Certificate
   * @param string $client_cert_pass - Password for Client 2SSL Certificate
   */
  public function __construct(array $params = []) {
      $this->inn = (string) $params['inn'];
      $this->api_url = $params['api_url'];
      $this->private_key_pem = (string) $params['sign_pkey'];
      $this->client_pkey = (string) $params['ssl_client_key'];
      $this->client_cert = (string) $params['ssl_client_crt'];
      $this->ca_cert = (string) $params['ssl_ca_cert'];
      $this->client_cert_pass = (string) $params['ssl_client_crt_pass'];
      $this->debug_file = getcwd() . '/curl.log';
  }

  /**
   *  Создание чека
   *  @param stdClass $params
   *  @return class $this
   *  @throws Exception
   */
  public function create_order(array $params = []) {
    $id = $params['id'];
    $type = $params['type'];
    $customerContact = $params['customerContact'];
    $taxationSystem = $params['taxationSystem'];
    $group = $params['group'];
    $key = $params['key'];
    $errors = array();

    if (!$id || strlen($id) > self::MAX_ID_LENGTH) array_push($errors, 'id - ' . ($id ? 'maxLength is ' . self::MAX_ID_LENGTH : 'is required'));
    if (!$this->inn || (strlen($this->inn ) !== 10 && strlen($this->inn ) !== 12)) array_push($errors, 'inn - ' . ($this->inn ? 'length need to be 10 or 12' : 'is required'));
    if ($group && strlen($group) > self::MAX_GROUP_LENGTH) array_push($errors, 'group - maxLength is ' . self::MAX_GROUP_LENGTH);
    if (!$key || strlen($key) > self::MAX_KEY_LENGTH) array_push($errors, 'key - ' . ($key ? 'maxLength is ' . self::MAX_KEY_LENGTH : 'is required'));
    if (!is_int($type) && !preg_match('/^[1234]$/', $type)) array_push($errors, 'content.type - invalid value');
    if (!preg_match('/^[012345]$/', $taxationSystem)) array_push($errors, 'checkClose.taxationSystem - invalid value');
    if (!filter_var($customerContact, FILTER_VALIDATE_EMAIL) && !preg_match('/^((8|\+7)[\- ]?)?(\(?\d{3}\)?[\- ]?)?[\d\- ]{7,10}$/', $customerContact)) array_push($errors, 'content.customerContact - invalid value');

    if (count($errors) > 0) throw new Exception(implode(', ', $errors) . PHP_EOL);

    $this->order_request = new \stdClass();
    $this->order_request->id = (string) $id;
    $this->order_request->inn = $this->inn;
    $this->order_request->group = $group ?: 'Main';
    $this->order_request->key = $key;
    $this->order_request->content = new \stdClass();
    $this->order_request->content->type = $type;
    $this->order_request->content->positions = array();
    $this->order_request->content->checkClose = new \stdClass();
    $this->order_request->content->checkClose->payments = array();
    $this->order_request->content->checkClose->taxationSystem = $taxationSystem;
    $this->order_request->content->customerContact = $customerContact;

    return $this;
  }

  /**
   *  Add position to order
   *  @param stdClass $params
   *  @return class $this
   *  @throws Exception
   */
  public function add_position_to_order(array $params = []) {
    $quantity = $params['quantity'];
    $price = $params['price'];
    $tax = $params['tax'];
    $text = $params['text'];
    $paymentMethodType = $params['paymentMethodType'];
    $paymentSubjectType = $params['paymentSubjectType'];
    $nomenclatureCode = $params['nomenclatureCode'];
    $supplierInfo = $params['supplierInfo'];
    $supplierINN = $params['supplierINN'];
    $agentType = $params['agentType'];
    $agentInfo = $params['agentInfo'];
    $unitOfMeasurement = $params['unitOfMeasurement'];
    $additionalAttribute = $params['additionalAttribute'];
    $manufacturerCountryCode = $params['manufacturerCountryCode'];
    $customsDeclarationNumber = $params['customsDeclarationNumber'];
    $excise = $params['excise'];

    $errors = array();

    if (!is_numeric($quantity)) array_push($errors, 'position.quantity - ' . ($quantity ? 'invalid value "' . $quantity . '"' : 'is required'));
    if (!is_numeric($price)) array_push($errors, 'position.price - ' . ($price ? 'invalid value "' . $price . '"' : 'is required'));
    if (!preg_match('/^[123456]{1}$/', $tax)) array_push($errors, 'position.tax - ' . ($tax ? 'invalid value "' . $tax . '"' : 'is required'));
    if (!$text or mb_strlen($text) > self::MAX_POSITION_TEXT_LENGTH) array_push($errors, 'position.text - ' . ($text ? 'maxLength is ' . MAX_POSITION_TEXT_LENGTH : 'is required'));
    if (!(preg_match('/^[1-7]$/', $paymentMethodType) or is_null($paymentMethodType))) array_push($errors, 'position.paymentMethodType - invalid value "' . $paymentMethodType . '"');
    if (!(preg_match('/^[1-9]{1}$|^1[0-9]{1}$/', $paymentSubjectType) or is_null($paymentSubjectType))) array_push($errors, 'position.paymentSubjectType - invalid value "' . $paymentSubjectType . '"');

    if ($nomenclatureCode && base64_encode(base64_decode($nomenclatureCode, true)) !== $nomenclatureCode) array_push($errors, 'position.nomenclatureCode - base64 required');
    if ($supplierInfo) {
      if ($supplierInfo['name'] && mb_strlen($supplierInfo['name']) > self::MAX_POSITION_SUPPLIER_NAME_LENGTH) array_push($errors, 'position.supplierInfo.name - maxLength is ' . self::MAX_POSITION_SUPPLIER_NAME_LENGTH);
      if ($supplierInfo['phoneNumbers']) {
        for ($i = 0; $i < count($supplierInfo['phoneNumbers']); $i++) {
            if (!preg_match('/^((8|\+7)[\- ]?)?(\(?\d{3}\)?[\- ]?)?[\d\- ]{7,10}$/', $supplierInfo['phoneNumbers'][$i])) array_push($errors, 'position.supplierInfo.phoneNumbers[' . $i . '] - invalid phone');
        }
      }
    }
    if ($supplierINN && strlen($supplierINN) !== 10 && strlen($supplierINN) !== 12) array_push($errors, 'position.supplierINN - length need to be 10 or 12');
    if ($agentType && (!is_numeric($agentType) or $agentType < 1 or $agentType > 127)) array_push($errors, 'position.agentType - need to be from 1 to 127');

    if ($agentInfo) {
      if ($agentInfo['paymentTransferOperatorPhoneNumbers']) {
        for ($i = 0; $i < count($agentInfo['paymentTransferOperatorPhoneNumbers']); $i++) {
            if (!preg_match('/^((8|\+7)[\- ]?)?(\(?\d{3}\)?[\- ]?)?[\d\- ]{7,10}$/', $agentInfo['paymentTransferOperatorPhoneNumbers'][$i])) array_push($errors, 'position.agentInfo.paymentTransferOperatorPhoneNumbers[' . $i . '] - invalid phone');
        }
      }
      if ($agentInfo['paymentAgentOperation'] && mb_strlen($agentInfo['paymentAgentOperation']) > self::MAX_PAYMENT_AGENT_OPERATION_LENGTH) array_push($errors, 'position.agentInfo.paymentAgentOperation - maxLength is ' . self::MAX_PAYMENT_AGENT_OPERATION_LENGTH);
      if ($agentInfo['paymentAgentPhoneNumbers']) {
        for ($i = 0; $i < count($agentInfo['paymentAgentPhoneNumbers']); $i++) {
            if (!preg_match('/^((8|\+7)[\- ]?)?(\(?\d{3}\)?[\- ]?)?[\d\- ]{7,10}$/', $agentInfo['paymentAgentPhoneNumbers'][$i])) array_push($errors, 'position.agentInfo.paymentAgentPhoneNumbers[' . $i . '] - invalid phone');
        }
      }
      if ($agentInfo['paymentOperatorPhoneNumbers']) {
        for ($i = 0; $i < count($agentInfo['paymentOperatorPhoneNumbers']); $i++) {
            if (!preg_match('/^((8|\+7)[\- ]?)?(\(?\d{3}\)?[\- ]?)?[\d\- ]{7,10}$/', $agentInfo['paymentOperatorPhoneNumbers'][$i])) array_push($errors, 'position.agentInfo.paymentOperatorPhoneNumbers[' . $i . '] - invalid phone');
        }
      }
      if ($agentInfo['paymentOperatorName'] && mb_strlen($agentInfo['paymentOperatorName']) > self::MAX_PAYMENT_OPERATOR_NAME_LENGTH) array_push($errors, 'position.agentInfo.paymentOperatorName - maxLength is ' . self::MAX_PAYMENT_OPERATOR_NAME_LENGTH);
      if ($agentInfo['paymentOperatorAddress'] && mb_strlen($agentInfo['paymentOperatorAddress']) > self::MAX_PAYMENT_OPERATOR_ADDRESS_LENGTH) array_push($errors, 'position.agentInfo.paymentOperatorAddress - maxLength is ' . self::MAX_PAYMENT_OPERATOR_ADDRESS_LENGTH);
      if ($agentInfo['paymentOperatorINN'] && strlen($agentInfo['paymentOperatorINN']) !== 10 && strlen($agentInfo['paymentOperatorINN']) !== 12) array_push($errors, 'position.agentInfo.paymentOperatorINN - length need to be 10 or 12');
    }

    if ($unitOfMeasurement && mb_strlen($unitOfMeasurement) > self::MAX_POSITION_UNIT_OF_MEASUREMENT_LENGTH) array_push($errors, 'position.unitOfMeasurement - maxLength is ' . self::MAX_POSITION_UNIT_OF_MEASUREMENT_LENGTH);
    if ($additionalAttribute && mb_strlen($additionalAttribute) > self::MAX_POSITION_ADDITIONAL_ATTRIBUTE_LENGTH) array_push($errors, 'position.additionalAttribute - maxLength is ' . self::MAX_POSITION_ADDITIONAL_ATTRIBUTE_LENGTH);
    if ($manufacturerCountryCode && strlen($manufacturerCountryCode) > self::MAX_POSITION_MANUFACTURE_COUNTRY_CODE_LENGTH) array_push($errors, 'position.manufacturerCountryCode - maxLength is ' . self::MAX_POSITION_MANUFACTURE_COUNTRY_CODE_LENGTH);
    if ($customsDeclarationNumber && mb_strlen($customsDeclarationNumber) > self::MAX_POSITION_CUSTOMS_DECLARATION_NUMBER) array_push($errors, 'position.additionalAttribute - maxLength is ' . self::MAX_POSITION_CUSTOMS_DECLARATION_NUMBER);
    if (!is_numeric($excise)) array_push($errors, 'position.excise - ' . ($excise ? 'invalid value "' . $excise . '"' : 'is required'));

    if (count($errors) > 0) throw new Exception(implode(', ', $errors) . PHP_EOL);

    $position = new \stdClass();
    $position->quantity = (float) $quantity;
    $position->price = (float) $price;
    $position->tax = $tax;
    $position->text = $text;
    $position->paymentMethodType = $paymentMethodType ?: 4;
    $position->paymentSubjectType = $paymentSubjectType ?: 1;

    if ($nomenclatureCode) $position->nomenclatureCode = $nomenclatureCode;
    if ($supplierInfo) $position->supplierInfo = $supplierInfo;
    if ($supplierINN) $position->supplierINN = $supplierINN;
    if ($agentType) $position->agentType = $agentType;
    if ($agentInfo) $position->agentInfo = $agentInfo;
    if ($unitOfMeasurement) $position->unitOfMeasurement = $unitOfMeasurement;
    if ($additionalAttribute) $position->additionalAttribute = $additionalAttribute;
    if ($manufacturerCountryCode) $position->manufacturerCountryCode = $manufacturerCountryCode;
    if ($customsDeclarationNumber) $position->customsDeclarationNumber = $customsDeclarationNumber;
    if ($excise) $position->excise = $excise;

    $this->order_request->content->positions[] = $position;

    return $this;
  }

  /**
   *  Добавление оплаты
   *  @param stdClass $params
   *  @return class $this
   *  @throws Exception
   */
  public function add_payment_to_order(array $params = []) {
    $type = $params['type'];
    $amount = $params['amount'];
    $errors = array();

    if (!preg_match('/^[1-9]{1}$|^1[0-6]{1}$/', $type)) array_push($errors, 'payments.type - ' . ($type ? 'invalid value "' . $type . '"' : 'is required'));
    if (!is_numeric($amount)) array_push($errors, 'payments.amount - ' . ($amount ? 'invalid value "' . $amount . '"' : 'is required'));

    if (count($errors) > 0) throw new Exception(implode(', ', $errors) . PHP_EOL);

    $payment = new \stdClass();
    $payment->type = (int) $type;
    $payment->amount = (float) $amount;
    $this->order_request->content->checkClose->payments[] = $payment;

    return $this;
  }

  /**
   *  Добавление агента
   *  @param stdClass $params
   *  @return class $this
   *  @throws Exception
   */
  public function add_agent_to_order(array $params = []) {
    $agentType = $params['agentType'];
    $paymentTransferOperatorPhoneNumbers = $params['paymentTransferOperatorPhoneNumbers'];
    $paymentAgentOperation = $params['paymentAgentOperation'];
    $paymentAgentPhoneNumbers = $params['paymentAgentPhoneNumbers'];
    $paymentOperatorPhoneNumbers = $params['paymentOperatorPhoneNumbers'];
    $paymentOperatorName = $params['paymentOperatorName'];
    $paymentOperatorAddress = $params['paymentOperatorAddress'];
    $paymentOperatorINN = $params['paymentOperatorINN'];
    $supplierPhoneNumbers = $params['supplierPhoneNumbers'];

    $errors = array();

    if ($agentType < 1 || $agentType > 127) array_push($errors, 'agentType - invalid value');
    for ($i = 0; $i < count($paymentTransferOperatorPhoneNumbers); $i++) {
        if (!preg_match('/^((8|\+7)[\- ]?)?(\(?\d{3}\)?[\- ]?)?[\d\- ]{7,10}$/', $paymentTransferOperatorPhoneNumbers[$i]))
            array_push($errors, 'paymentTransferOperatorPhoneNumbers[' . $i . '] - invalid phone');
    }
    if (mb_strlen($paymentAgentOperation) > self::MAX_PAYMENT_AGENT_OPERATION_LENGTH) array_push($errors, 'paymentAgentOperation - maxLength is ' . self::MAX_PAYMENT_AGENT_OPERATION_LENGTH);
    for ($i = 0; $i < count($paymentAgentPhoneNumbers); $i++) {
        if (!preg_match('/^((8|\+7)[\- ]?)?(\(?\d{3}\)?[\- ]?)?[\d\- ]{7,10}$/', $paymentAgentPhoneNumbers[$i]))
            array_push($errors, 'paymentAgentPhoneNumbers[' . $i . '] - invalid phone');
    }
    for ($i = 0; $i < count($paymentOperatorPhoneNumbers); $i++) {
        if (!preg_match('/^((8|\+7)[\- ]?)?(\(?\d{3}\)?[\- ]?)?[\d\- ]{7,10}$/', $paymentOperatorPhoneNumbers[$i]))
            array_push($errors, 'paymentOperatorPhoneNumbers[' . $i . '] - invalid phone');
    }
    if (mb_strlen($paymentOperatorName) > self::MAX_PAYMENT_OPERATOR_NAME_LENGTH) array_push($errors, 'paymentOperatorName - maxLength is ' . self::MAX_PAYMENT_OPERATOR_NAME_LENGTH);
    if (mb_strlen($paymentOperatorAddress) > self::MAX_PAYMENT_OPERATOR_ADDRESS_LENGTH) array_push($errors, 'paymentOperatorAddress - maxLength is ' . self::MAX_PAYMENT_OPERATOR_ADDRESS_LENGTH);
    if ($paymentOperatorINN && strlen($paymentOperatorINN) !== 10 && strlen($paymentOperatorINN) !== 12) array_push($errors, 'paymentOperatorINN - length need to be 10 or 12');

    for ($i = 0; $i < count($supplierPhoneNumbers); $i++) {
        if (!preg_match('/^((8|\+7)[\- ]?)?(\(?\d{3}\)?[\- ]?)?[\d\- ]{7,10}$/', $supplierPhoneNumbers[$i]))
            array_push($errors, 'supplierPhoneNumbers[' . $i . '] - invalid phone');
    }

    if (count($errors) > 0) throw new Exception(implode(', ', $errors) . PHP_EOL);


    if ($agentType) $this->order_request->content->agentType = $agentType;
    if ($paymentTransferOperatorPhoneNumbers) $this->order_request->content->paymentTransferOperatorPhoneNumbers = $paymentTransferOperatorPhoneNumbers;
    if ($paymentAgentOperation) $this->order_request->content->paymentAgentOperation = $paymentAgentOperation;
    if ($paymentAgentPhoneNumbers) $this->order_request->content->paymentAgentPhoneNumbers = $paymentAgentPhoneNumbers;
    if ($paymentOperatorPhoneNumbers) $this->order_request->content->paymentOperatorPhoneNumbers = $paymentOperatorPhoneNumbers;
    if ($paymentOperatorName) $this->order_request->content->paymentOperatorName = $paymentOperatorName;
    if ($paymentOperatorAddress) $this->order_request->content->paymentOperatorAddress = $paymentOperatorAddress;
    if ($paymentOperatorINN) $this->order_request->content->paymentOperatorINN = $paymentOperatorINN;
    if ($supplierPhoneNumbers) $this->order_request->content->supplierPhoneNumbers = $supplierPhoneNumbers;

    return $this;
  }

  /**
   *  Добавление дополнительного реквизита пользователя, 1084
   *  @param stdClass $params
   *  @return class $this
   *  @throws Exception
   */
  public function add_user_attribute(array $params = []) {
    $name = $params['name'];
    $value = $params['value'];
    $errors = array();

    if (!$name or mb_strlen($name) > self::MAX_ADDITIONAL_USER_ATTRIBUTE_NAME_LENGTH) array_push($errors, 'additionalUserAttribute.name - ' . ($name ? 'maxLength is ' . self::MAX_ADDITIONAL_USER_ATTRIBUTE_NAME_LENGTH : 'is required'));
    if (!$value or mb_strlen($value) > self::MAX_ADDITIONAL_USER_ATTRIBUTE_VALUE_LENGTH) array_push($errors, 'additionalUserAttribute.value - ' . ($value ? 'maxLength is ' . self::MAX_ADDITIONAL_USER_ATTRIBUTE_VALUE_LENGTH : 'is required'));

    if (count($errors) > 0) throw new Exception(implode(', ', $errors) . PHP_EOL);

    $this->order_request->content->additionalUserAttribute = new \stdClass();
    $this->order_request->content->additionalUserAttribute->name = $name;
    $this->order_request->content->additionalUserAttribute->value = $value;

    return $this;
  }

  /**
   *  Добавление дополнительных аттрибутов
   *  @param stdClass $params
   *  @return class $this
   *  @throws Exception
   */
  public function add_additional_attributes(array $params = []) {
    $additionalAttribute = $params['additionalAttribute'];
    $customer = $params['customer'];
    $customerINN = $params['customerINN'];
    $errors = array();

    if (mb_strlen($additionalAttribute) > self::MAX_ADDITIONAL_ATTRIBUTE_LENGTH) array_push($errors, 'additionalAttribute - maxLength is ' . self::MAX_ADDITIONAL_ATTRIBUTE_LENGTH);
    if (mb_strlen($customer) > self::MAX_CUSTOMER_LENGTH) array_push($errors, 'customer - maxLength is ' . self::MAX_CUSTOMER_LENGTH);
    if ($customerINN && strlen($customerINN) !== 10 && strlen($customerINN) !== 12) array_push($errors, 'customerINN - length need to be 10 or 12');

    if (count($errors) > 0) throw new Exception(implode(', ', $errors) . PHP_EOL);

    if ($additionalAttribute) $this->order_request->content->additionalAttribute = $additionalAttribute;
    if ($customer) $this->order_request->content->customer = $customer;
    if ($customerINN) $this->order_request->content->customerINN = $customerINN;

    return $this;
  }

  /**
   *  Добавление вендинга
   *  @param stdClass $params
   *  @return class $this
   *  @throws Exception
   */
  public function add_vending_to_order(array $params = []) {
    $automatNumber = $params['automatNumber'];
    $settlementAddress = $params['settlementAddress'];
    $settlementPlace = $params['settlementPlace'];
    $errors = array();

    if (!$automatNumber || strlen($automatNumber) > self::MAX_VENDING_AUTOMAT_NUMBER_LENGTH) array_push($errors, 'automatNumber - ' . ($automatNumber ? 'maxLength is ' . self::MAX_VENDING_AUTOMAT_NUMBER_LENGTH : 'is required'));
    if (!$settlementAddress || mb_strlen($settlementAddress) > self::MAX_VENDING_ADDRESS_LENGTH) array_push($errors, 'settlementAddress - ' . ($settlementAddress ? 'maxLength is ' . self::MAX_VENDING_ADDRESS_LENGTH : 'is required'));
    if (!$settlementPlace || mb_strlen($settlementPlace) > self::MAX_VENDING_PLACE_LENGTH) array_push($errors, 'settlementPlace - ' . ($settlementPlace ? 'maxLength is ' . self::MAX_VENDING_PLACE_LENGTH : 'is required'));

    if (count($errors) > 0) throw new Exception(implode(', ', $errors) . PHP_EOL);

    $this->order_request->content->automatNumber = $automatNumber;
    $this->order_request->content->settlementAddress = $settlementAddress;
    $this->order_request->content->settlementPlace = $settlementPlace;

    return $this->order_request;
  }

  /**
   *  Отправка чека
   *  @return mixed
   *  @throws Exception
   */
  public function send_order() {
    $jsonstring = json_encode($this->order_request, JSON_PRESERVE_ZERO_FRACTION);
    $sign = $this->sign_order_request($jsonstring);
    $curl = is_numeric($this->api_url) ? $this->prepare_curl($this->edit_url($this->api_url, TRUE)) : $this->prepare_curl($this->api_url . '/api/v2/documents/');
    $headers = array(
      "Content-Length: " . strlen($jsonstring),
      "Content-Type: application/json; charset=utf-8",
      "X-Signature: " . $sign
    );
    curl_setopt_array($curl, array(
      CURLOPT_POST => true,
      CURLOPT_HTTPHEADER => $headers,
      CURLOPT_POSTFIELDS => $jsonstring
    ));
    $answer = curl_exec($curl);
    $info = curl_getinfo($curl);
    switch ($info['http_code']) {
      case '201':
        $return = true;
        break;
      case '400':
        $return = $answer;
        break;
      case '401':
        throw new Exception('Unauthorized. Client certificate check is failed');
        break;
      case '409':
        throw new Exception('Conflict. Order with same id is already exists in the system.');
        break;
      case '503':
        $return = $answer;
        break;
      default:
        $return = false;
        break;
    }
    if (FALSE === $return) {
      throw new Exception('Curl error: ' . curl_error($curl));
    }
    return $return;
  }

  /**
   * get_order_status(a) - Проверка состояния чека
   *  @param string $id (a) - Идентификатор документа (Строка от 1 до 64 символов)
   *  @return mixed
   *  @throws Exception
   */
  public function get_order_status($id) {
    if (strlen($id) > 64 OR strlen($id) == 0) {
      throw new Exception('Invalid order identifier');
    }
    $curl = is_numeric($this->api_url) ? $this->prepare_curl($this->edit_url($this->api_url, TRUE) . $this->inn . '/status/' . $id) : $this->prepare_curl($this->api_url . '/api/v2/documents/' . $this->inn . '/status/' . $id);
    curl_setopt($curl, CURLOPT_POST, false);
    $answer = curl_exec($curl);
    $info = curl_getinfo($curl);
    switch ($info['http_code']) {
      case '200':
        $return = $answer;
        break;
      case '202':
        $return = TRUE;
        break;
      case '400':
        throw new Exception('Not Found. Order was not found in the system.');
        break;
      case '401':
        throw new Exception('Unauthorized. Client certificate check is failed');
        break;
      default:
        $return = false;
        break;
    }
    if (FALSE === $return) {
      throw new Exception('Curl error: ' . curl_error($curl));
    }
    return $return;
  }

  private function sign_order_request($jsonstring) {
    $binary_signature = "";
    $r = openssl_sign($jsonstring, $binary_signature, file_get_contents($this->private_key_pem), OPENSSL_ALGO_SHA256);
    if ($r) {
      return base64_encode($binary_signature);
    } else {
      return false;
    }
  }

  /**
   * edit_url(a, b) - автоматическая генерация ссылки
   *  @param $port (a) - номер порта, на который шлются запросы
   *  @param $tag (b) - идентификатор отправки/проверки чека
   *  @return string
   *  @throws Exception
   */
  private function edit_url($port, $tag) {
    if ($port == 2443 or $port == 12001){
      $tag ? $url = 'https://apip.orangedata.ru:' . $port . '/api/v2/documents/' : $url = 'https://apip.orangedata.ru:' . $port . '/api/v2/corrections/';
    } elseif ($port == 12003) {
      $tag ? $url = 'https://api.orangedata.ru:' . $port . '/api/v2/documents/' : $url = 'https://api.orangedata.ru:' . $port . '/api/v2/corrections/';
    } else {
      throw new Exception('Port error: invalid value' . PHP_EOL);
    }
    return $url;
  }

  private function prepare_curl($url) {
    $curl = curl_init($url);
    curl_setopt_array($curl, array(
      CURLOPT_SSLKEY => $this->client_pkey,
      CURLOPT_SSLCERT => $this->client_cert,
      CURLOPT_SSLCERTPASSWD => $this->client_cert_pass,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_CONNECTTIMEOUT => 3,
      CURLOPT_TIMEOUT => 15,
      CURLOPT_CAINFO => $this->ca_cert,
    ));
    if ($this->debug) {
      curl_setopt_array($curl, array(
        CURLOPT_VERBOSE => 1,
        CURLOPT_STDERR => fopen($this->debug_file, 'a'),
      ));
    }
    return $curl;
  }

  public function is_debug($is_debug = true) {
    $this->debug = (bool) $is_debug;
    return $this;
  }

  /**
   *  Создание чека-коррекции
   *  @param stdClass $params
   *  @return class $this
   *  @throws Exception
   */
  public function create_correction(array $params = []) {
    $id = $params['id'];
    $key = $params['key'];
    $group = $params['group'];
    $correctionType = $params['correctionType'];
    $type = $params['type'];
    $description = $params['description'];
    $causeDocumentDate = $params['causeDocumentDate'];
    $causeDocumentNumber = $params['causeDocumentNumber'];
    $totalSum = $params['totalSum'];
    $cashSum = $params['cashSum'];
    $eCashSum = $params['eCashSum'];
    $prepaymentSum = $params['prepaymentSum'];
    $postpaymentSum = $params['postpaymentSum'];
    $otherPaymentTypeSum = $params['otherPaymentTypeSum'];
    $tax1Sum = $params['tax1Sum'];
    $tax2Sum = $params['tax2Sum'];
    $tax3Sum = $params['tax3Sum'];
    $tax4Sum = $params['tax4Sum'];
    $tax5Sum = $params['tax5Sum'];
    $tax6Sum = $params['tax6Sum'];
    $taxationSystem = $params['taxationSystem'];
    $errors = array();

    if (!$id || strlen($id) > self::MAX_ID_LENGTH) array_push($errors, 'id - ' . ($id ? 'maxLength is ' . self::MAX_ID_LENGTH : 'is required'));
    if (!$this->inn || (strlen($this->inn ) !== 10 && strlen($this->inn ) !== 12)) array_push($errors, 'inn - ' . ($this->inn ? 'length need to be 10 or 12' : 'is required'));
    if (!$key || strlen($key) > self::MAX_KEY_LENGTH) array_push($errors, 'key - ' . ($key ? 'maxLength is ' . MAX_KEY_LENGTH : 'is required'));
    if (!is_numeric($correctionType) || !preg_match('/^[01]$/', $correctionType)) array_push($errors, 'correctionType - ' . ($correctionType ? 'need to be 0 or 1' : 'is required'));
    if (!is_numeric($type) || !preg_match('/^[13]$/', $type)) array_push($errors, 'type - ' . ($type ? 'need to be 1 or 3' : 'is required'));
    if (!$description || mb_strlen($description) > self::MAX_CORRECTION_DESCRIPTION_LENGTH) array_push($errors, 'description - ' . ($description ? 'maxLength is ' . self::MAX_CORRECTION_DESCRIPTION_LENGTH : 'is required'));
    if (!$causeDocumentDate) array_push($errors, 'causeDocumentDate - is required');
    if (!$causeDocumentNumber || mb_strlen($causeDocumentNumber) > self::MAX_CORRECTION_CAUSE_DOCUMENT_NUMBER) array_push($errors, 'causeDocumentNumber - ' . ($causeDocumentNumber ? 'maxLength is ' . self::MAX_CORRECTION_CAUSE_DOCUMENT_NUMBER : 'is required'));
    if (!$totalSum || !is_numeric($totalSum)) array_push($errors, 'totalSum - ' . ($totalSum ? 'invalid value' : 'is required'));
    if ($cashSum && !is_numeric($cashSum)) array_push($errors, 'cashSum - invalid value');
    if ($eCashSum && !is_numeric($eCashSum)) array_push($errors, 'eCashSum - invalid value');
    if ($prepaymentSum && !is_numeric($prepaymentSum)) array_push($errors, 'prepaymentSum - invalid value');
    if ($postpaymentSum && !is_numeric($postpaymentSum)) array_push($errors, 'postpaymentSum - invalid value');
    if ($otherPaymentTypeSum && !is_numeric($otherPaymentTypeSum)) array_push($errors, 'otherPaymentTypeSum - invalid value');
    if ($tax1Sum && !is_numeric($tax1Sum)) array_push($errors, 'tax1Sum - invalid value');
    if ($tax2Sum && !is_numeric($tax2Sum)) array_push($errors, 'tax2Sum - invalid value');
    if ($tax3Sum && !is_numeric($tax3Sum)) array_push($errors, 'tax3Sum - invalid value');
    if ($tax4Sum && !is_numeric($tax4Sum)) array_push($errors, 'tax4Sum - invalid value');
    if ($tax5Sum && !is_numeric($tax5Sum)) array_push($errors, 'tax5Sum - invalid value');
    if ($tax6Sum && !is_numeric($tax6Sum)) array_push($errors, 'tax6Sum - invalid value');
    if (is_numeric($taxationSystem) && !preg_match('/^[012345]$/', $taxationSystem)) array_push($errors, 'taxationSystem - invalid value');

    if (count($errors) > 0) throw new Exception(implode(', ', $errors) . PHP_EOL);


    $this->correction_request = new \stdClass();
    $this->correction_request->id = (string) $id;
    $this->correction_request->inn = $this->inn;
    $this->correction_request->group = $group ?: 'Main';
    $this->correction_request->key = $key;
    $this->correction_request->content = new \stdClass();
    $this->correction_request->content->correctionType = (int) $correctionType;
    $this->correction_request->content->type = (int) $type;
    $this->correction_request->content->description = $description;
    $this->correction_request->content->causeDocumentDate = $causeDocumentDate->setTime(0, 0)->format(DateTime::ISO8601);
    $this->correction_request->content->causeDocumentNumber = $causeDocumentNumber;
    $this->correction_request->content->totalSum = (float) $totalSum;
    if ($cashSum) $this->correction_request->content->cashSum = (float) $cashSum;
    if ($eCashSum) $this->correction_request->content->eCashSum = (float) $eCashSum;
    if ($prepaymentSum) $this->correction_request->content->prepaymentSum = (float) $prepaymentSum;
    if ($postpaymentSum) $this->correction_request->content->postpaymentSum = (float) $postpaymentSum;
    if ($otherPaymentTypeSum) $this->correction_request->content->otherPaymentTypeSum = (float) $otherPaymentTypeSum;
    if ($tax1Sum) $this->correction_request->content->tax1Sum = (float) $tax1Sum;
    if ($tax2Sum) $this->correction_request->content->tax2Sum = (float) $tax2Sum;
    if ($tax3Sum) $this->correction_request->content->tax3Sum = (float) $tax3Sum;
    if ($tax4Sum) $this->correction_request->content->tax4Sum = (float) $tax4Sum;
    if ($tax5Sum) $this->correction_request->content->tax5Sum = (float) $tax5Sum;
    if ($tax6Sum) $this->correction_request->content->tax6Sum = (float) $tax6Sum;
    if (is_numeric($taxationSystem)) $this->correction_request->content->taxationSystem = $taxationSystem;

    return $this;
  }

  /**
   *  Добавление вендинга в чек-коррекцию
   *  @param stdClass $params
   *  @return class $this
   *  @throws Exception
   */
  public function add_vending_to_correction(array $params = []) {
    $automatNumber = $params['automatNumber'];
    $settlementAddress = $params['settlementAddress'];
    $settlementPlace = $params['settlementPlace'];
    $errors = array();

    if (!$automatNumber || strlen($automatNumber) > self::MAX_VENDING_AUTOMAT_NUMBER_LENGTH) array_push($errors, 'automatNumber - ' . ($automatNumber ? 'maxLength is ' . self::MAX_VENDING_AUTOMAT_NUMBER_LENGTH : 'is required'));
    if (!$settlementAddress || strlen($settlementAddress) > self::MAX_VENDING_ADDRESS_LENGTH) array_push($errors, 'settlementAddress - ' . ($settlementAddress ? 'maxLength is ' . self::MAX_VENDING_ADDRESS_LENGTH : 'is required'));
    if (!$settlementPlace || strlen($settlementPlace) > self::MAX_VENDING_PLACE_LENGTH) array_push($errors, 'settlementPlace - ' . ($settlementPlace ? 'maxLength is ' . self::MAX_VENDING_PLACE_LENGTH : 'is required'));

    if (count($errors) > 0) throw new Exception(implode(', ', $errors) . PHP_EOL);

    $this->correction_request->content->automatNumber = $automatNumber;
    $this->correction_request->content->settlementAddress = $settlementAddress;
    $this->correction_request->content->settlementPlace = $settlementPlace;

    return $this;
  }

  /**
   * post_correction() - Отправка чека-коррекции на обработку
   *  @return bool|mixed
   *  @throws Exception
   */
  public function post_correction() {
    $jsonstring = json_encode($this->correction_request, JSON_PRESERVE_ZERO_FRACTION);
    if(!$jsonstring){
      throw  new Exception('JSON encode error:' . json_last_error_msg());
    }
    $sign = $this->sign_order_request($jsonstring);
    $curl = is_numeric($this->api_url) ? $this->prepare_curl($this->edit_url($this->api_url, FALSE)) : $this->prepare_curl($this->api_url . '/api/v2/corrections/');
    $headers = array(
      "Content-Length: " . strlen($jsonstring),
      "Content-Type: application/json; charset=utf-8",
      "X-Signature: " . $sign
    );
    curl_setopt_array($curl, array(
      CURLOPT_POST => true,
      CURLOPT_HTTPHEADER => $headers,
      CURLOPT_POSTFIELDS => $jsonstring
    ));
    $answer = curl_exec($curl);
    $info = curl_getinfo($curl);
    switch ($info['http_code']) {
      case '201':
        $return = true;
        break;
      case '400':
        $return = $answer;
        break;
      case '401':
        throw new Exception('Unauthorized. Client certificate check is failed');
        break;
      case '409':
        throw new Exception('Conflict. Bill with same id is already exists in the system.');
        break;
      case '503':
        $return = $answer;
        break;
      default:
        $return = false;
        break;
    }
    if (FALSE === $return) {
      throw new Exception('Curl error: ' . curl_error($curl));
    }
    return $return;
  }

  /**
   * get_correction_status(a) - Проверка состояния чека-коррекции
   *  @param $id (a) - Идентификатор документа (Строка от 1 до 64 символов)
   *  @return bool|mixed
   *  @throws Exception
   */
  public function get_correction_status($id) {
    if (strlen($id) > 64 OR strlen($id) == 0) {
        throw new Exception('Invalid order identifier');
    }
    $curl = is_numeric($this->api_url) ? $this->prepare_curl($this->edit_url($this->api_url,FALSE) . $this->inn . '/status/' . $id) : $this->prepare_curl($this->api_url . '/api/v2/corrections/' . $this->inn . '/status/' . $id);
    curl_setopt($curl, CURLOPT_POST, false);
    $answer = curl_exec($curl);
    $info = curl_getinfo($curl);
    switch ($info['http_code']) {
      case '200':
        $return = $answer;
        break;
      case '202':
        $return = TRUE;
        break;
      case '400':
        throw new Exception('Not Found. Order was not found in the system. Company not found.');
        break;
      case '401':
        throw new Exception('Unauthorized. Client certificate check is failed');
        break;
      default:
        $return = false;
        break;
    }
    if (FALSE === $return) {
      throw new Exception('Curl error: ' . curl_error($curl));
    }
    return $return;
  }

}
