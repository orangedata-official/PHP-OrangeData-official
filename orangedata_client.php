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
  const MAX_POSITION_QUANTITY_MEASUREMENT_UNIT_LENGTH = 255;
  const MAX_POSITION_ITEM_CODE_LENGTH = 223;
  const MAX_POSITION_INDUSTRY_ATTRIBUTE_FOIVID_LENGTH = 3;
  const MAX_POSITION_INDUSTRY_ATTRIBUTE_CAUSE_DOCUMENT_NUMBER_LENGTH = 32;
  const MAX_POSITION_INDUSTRY_ATTRIBUTE_VALUE_LENGTH = 239;
  const MAX_POSITION_BARCODES_EAN8_LENGTH = 8;
  const MAX_POSITION_BARCODES_EAN13_LENGTH = 13;
  const MAX_POSITION_BARCODES_ITF14_LENGTH = 14;
  const MAX_POSITION_BARCODES_GS1_LENGTH = 38;
  const MAX_POSITION_BARCODES_MI_LENGTH = 20;
  const MAX_POSITION_BARCODES_EGAIS20_LENGTH = 23;
  const MAX_POSITION_BARCODES_EGAIS30_LENGTH = 14;
  const MAX_POSITION_BARCODES_F1_LENGTH = 32;
  const MAX_POSITION_BARCODES_F2_LENGTH = 32;
  const MAX_POSITION_BARCODES_F3_LENGTH = 32;
  const MAX_POSITION_BARCODES_F4_LENGTH = 32;
  const MAX_POSITION_BARCODES_F5_LENGTH = 32;
  const MAX_POSITION_BARCODES_F6_LENGTH = 32;
  const MAX_SENDER_EMAIL_LENGTH = 64;
  const MAX_CUSTOMER_INFO_NAME_LENGTH = 239;
  const MAX_CUSTOMER_INFO_CITIZENSHIP_LENGTH = 3;
  const MAX_CUSTOMER_INFO_IDENTITY_DOCUMENT_DATA_LENGTH = 64;
  const MAX_CUSTOMER_INFO_ADDRESS_LENGTH = 239;
  const MAX_OPERATIONAL_ATTRIBUTE_VALUE_LENGTH = 64;
  const MAX_OPERATIONAL_ATTRIBUTE_ID_LENGTH = 255;
  const MAX_CASHIER_LENGTH = 64;


  const MAX_POSITION_FRACTIONAL_QUANTITY_NUMERATOR_BYTE = 8;
  const MAX_POSITION_FRACTIONAL_QUANTITY_DENOMINATOR_BYTE = 8;

  const FFD_VERSION_105 = 2;
  const FFD_VERSION_12 = 4;

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
   *  @param array $params
   *  @return orangedata_client $this
   *  @throws Exception
   */
  public function create_order(array $params = []) {
    $id = $params['id'];
    $ffdVersion = isset($params['ffdVersion']) ? $params['ffdVersion'] : self::FFD_VERSION_105;
    $type = $params['type'];
    $customerContact = $params['customerContact'];
    $taxationSystem = $params['taxationSystem'];
    $group = $params['group'];
    $key = $params['key'];
    $ignoreItemCodeCheck = $params['ignoreItemCodeCheck'];
    $errors = [];

    if (!$id || strlen($id) > self::MAX_ID_LENGTH) $errors[] = 'id - ' . ($id ? 'maxLength is ' . self::MAX_ID_LENGTH : 'is required');
    if (!$this->inn || (strlen($this->inn ) !== 10 && strlen($this->inn ) !== 12)) $errors[] = 'inn - ' . ($this->inn ? 'length need to be 10 or 12' : 'is required');
    if ($group && strlen($group) > self::MAX_GROUP_LENGTH) $errors[] = 'group - maxLength is ' . self::MAX_GROUP_LENGTH;
    if (!$key || strlen($key) > self::MAX_KEY_LENGTH) $errors[] = 'key - ' . ($key ? 'maxLength is ' . self::MAX_KEY_LENGTH : 'is required');
    if (!is_int($type) && !preg_match('/^[1234]$/', $type)) $errors[] = 'content.type - invalid value';
    if (!preg_match('/^[012345]$/', $taxationSystem)) $errors[] = 'checkClose.taxationSystem - invalid value';
    if (!filter_var($customerContact, FILTER_VALIDATE_EMAIL) && !preg_match('/^((8|\+7)[\- ]?)?(\(?\d{3}\)?[\- ]?)?[\d\- ]{7,10}$/', $customerContact)) $errors[] = 'content.customerContact - invalid value';
    if (!in_array($ffdVersion, [self::FFD_VERSION_105, self::FFD_VERSION_12])) $errors[] = 'content.ffdVersion - invalid value';

    if (count($errors) > 0) throw new Exception(implode(', ', $errors) . PHP_EOL);

    $this->order_request = new \stdClass();
    $this->order_request->id = (string) $id;
    $this->order_request->inn = $this->inn;
    $this->order_request->group = $group ?: 'Main';
    $this->order_request->key = $key;
    $this->order_request->ignoreItemCodeCheck = $ignoreItemCodeCheck;
    $this->order_request->content = new \stdClass();
    $this->order_request->content->ffdVersion = $ffdVersion;
    $this->order_request->content->type = $type;
    $this->order_request->content->positions = [];
    $this->order_request->content->checkClose = new \stdClass();
    $this->order_request->content->checkClose->payments = [];
    $this->order_request->content->checkClose->taxationSystem = $taxationSystem;
    $this->order_request->content->customerContact = $customerContact;

    return $this;
  }

  /**
   *  Add position to order
   *  @param array $params
   *  @return orangedata_client $this
   *  @throws Exception
   */
  public function add_position_to_order(array $params = []) {
    $errors = [];
    $quantity = $params['quantity'];
    $price = $params['price'];
    $tax = $params['tax'];
    $taxSum = $params['taxSum'];
    $text = $params['text'];
    $paymentMethodType = $params['paymentMethodType'];
    $paymentSubjectType = $params['paymentSubjectType'];
    $supplierInfo = $params['supplierInfo'];
    $supplierINN = $params['supplierINN'];
    $agentType = $params['agentType'];
    $agentInfo = $params['agentInfo'];
    $additionalAttribute = $params['additionalAttribute'];
    $manufacturerCountryCode = $params['manufacturerCountryCode'];
    $customsDeclarationNumber = $params['customsDeclarationNumber'];
    $excise = $params['excise'];

    if ($this->order_request->content->ffdVersion === self::FFD_VERSION_105)
    {
        $unitOfMeasurement = $params['unitOfMeasurement']; //поле для ФФД 1.05
        if ($unitOfMeasurement && mb_strlen($unitOfMeasurement) > self::MAX_POSITION_UNIT_OF_MEASUREMENT_LENGTH) $errors[] = 'position.unitOfMeasurement - maxLength is ' . self::MAX_POSITION_UNIT_OF_MEASUREMENT_LENGTH;
        $nomenclatureCode = $params['nomenclatureCode'];
        if ($nomenclatureCode && base64_encode(base64_decode($nomenclatureCode, true)) !== $nomenclatureCode) $errors[] = 'position.nomenclatureCode - base64 required';
    }
    else if ($this->order_request->content->ffdVersion === self::FFD_VERSION_12)
    {
        $quantityMeasurementUnit = $params['quantityMeasurementUnit']; //поле для ФФД 1.2
        if ($quantityMeasurementUnit && mb_strlen($quantityMeasurementUnit) > self::MAX_POSITION_QUANTITY_MEASUREMENT_UNIT_LENGTH) $errors[] = 'position.quantityMeasurementUnit - maxLength is ' . self::MAX_POSITION_QUANTITY_MEASUREMENT_UNIT_LENGTH;
        $itemCode = $params['itemCode'];
        if ($itemCode && mb_strlen($itemCode) > self::MAX_POSITION_ITEM_CODE_LENGTH) $errors[] = 'position.itemCode - maxLength is ' . self::MAX_POSITION_ITEM_CODE_LENGTH;

        $plannedStatus = $params['plannedStatus'];
        if ($plannedStatus && (!is_numeric($plannedStatus) || ($plannedStatus < 0 || $plannedStatus > 256))) $errors[] = 'position.plannedStatus - invalid value is ' . $plannedStatus;

        $fractionalQuantity = $params['fractionalQuantity'];
        if ($fractionalQuantity)
        {
            if ($fractionalQuantity['Numerator'] && strlen($fractionalQuantity['Numerator']) > self::MAX_POSITION_FRACTIONAL_QUANTITY_NUMERATOR_BYTE) $errors[] = 'position.fractionalQuantity.Numerator - maxByteLength is ' . self::MAX_POSITION_FRACTIONAL_QUANTITY_NUMERATOR_BYTE;
            if ($fractionalQuantity['Numerator'] && strlen($fractionalQuantity['Denominator']) > self::MAX_POSITION_FRACTIONAL_QUANTITY_DENOMINATOR_BYTE) $errors[] = 'position.fractionalQuantity.Denominator - maxByteLength is ' . self::MAX_POSITION_FRACTIONAL_QUANTITY_DENOMINATOR_BYTE;
        }

        $industryAttribute = $params['industryAttribute'];
        if ($industryAttribute)
        {
            if ($industryAttribute['foivId'] && mb_strlen($industryAttribute['foivId']) > self::MAX_POSITION_INDUSTRY_ATTRIBUTE_FOIVID_LENGTH) $errors[] = 'position.industryAttribute.foivId - maxLength is ' . self::MAX_POSITION_INDUSTRY_ATTRIBUTE_FOIVID_LENGTH;
            if ($industryAttribute['causeDocumentNumber'] && mb_strlen($industryAttribute['causeDocumentNumber']) > self::MAX_POSITION_INDUSTRY_ATTRIBUTE_CAUSE_DOCUMENT_NUMBER_LENGTH) $errors[] = 'position.industryAttribute.causeDocumentNumber - maxLength is ' . self::MAX_POSITION_INDUSTRY_ATTRIBUTE_CAUSE_DOCUMENT_NUMBER_LENGTH;
            if ($industryAttribute['value'] && mb_strlen($industryAttribute['value']) > self::MAX_POSITION_INDUSTRY_ATTRIBUTE_VALUE_LENGTH) $errors[] = 'position.industryAttribute.value - maxLength is ' . self::MAX_POSITION_INDUSTRY_ATTRIBUTE_VALUE_LENGTH;
            if ($industryAttribute['causeDocumentDate'] && !preg_match('/^(0?[1-9]|[12][0-9]|3[01]).(0?[1-9]|1[012]).((19|20)\d\d)$/', $industryAttribute['causeDocumentDate'])) $errors[] = 'position.industryAttribute.causeDocumentDate - invalid value ' . $industryAttribute['causeDocumentDate'];
        }
        $barcodes = $params['barcodes'];
        if ($barcodes)
        {
            if ($barcodes['ean8'] && mb_strlen($barcodes['ean8']) > self::MAX_POSITION_BARCODES_EAN8_LENGTH) $errors[] = 'position.barcodes.ean8 - maxLength is ' . self::MAX_POSITION_BARCODES_EAN8_LENGTH;
            if ($barcodes['ean13'] && mb_strlen($barcodes['ean13']) > self::MAX_POSITION_BARCODES_EAN13_LENGTH) $errors[] = 'position.barcodes.ean13 - maxLength is ' . self::MAX_POSITION_BARCODES_EAN13_LENGTH;
            if ($barcodes['itf14'] && mb_strlen($barcodes['itf14']) > self::MAX_POSITION_BARCODES_ITF14_LENGTH) $errors[] = 'position.barcodes.itf14 - maxLength is ' . self::MAX_POSITION_BARCODES_ITF14_LENGTH;
            if ($barcodes['gs1'] && mb_strlen($barcodes['itf14']) > self::MAX_POSITION_BARCODES_GS1_LENGTH) $errors[] = 'position.barcodes.gs1 - maxLength is ' . self::MAX_POSITION_BARCODES_GS1_LENGTH;
            if ($barcodes['mi'] && mb_strlen($barcodes['itf14']) > self::MAX_POSITION_BARCODES_MI_LENGTH) $errors[] = 'position.barcodes.mi - maxLength is ' . self::MAX_POSITION_BARCODES_MI_LENGTH;
            if ($barcodes['egais20'] && mb_strlen($barcodes['egais20']) > self::MAX_POSITION_BARCODES_EGAIS20_LENGTH) $errors[] = 'position.barcodes.egais20 - maxLength is ' . self::MAX_POSITION_BARCODES_EGAIS20_LENGTH;
            if ($barcodes['egais30'] && mb_strlen($barcodes['egais30']) > self::MAX_POSITION_BARCODES_EGAIS30_LENGTH) $errors[] = 'position.barcodes.egais30 - maxLength is ' . self::MAX_POSITION_BARCODES_EGAIS30_LENGTH;

            if ($barcodes['f1'] && mb_strlen($barcodes['f1']) > self::MAX_POSITION_BARCODES_F1_LENGTH) $errors[] = 'position.barcodes.f1 - maxLength is ' . self::MAX_POSITION_BARCODES_F1_LENGTH;
            if ($barcodes['f2'] && mb_strlen($barcodes['f2']) > self::MAX_POSITION_BARCODES_F2_LENGTH) $errors[] = 'position.barcodes.f2 - maxLength is ' . self::MAX_POSITION_BARCODES_F2_LENGTH;
            if ($barcodes['f3'] && mb_strlen($barcodes['f3']) > self::MAX_POSITION_BARCODES_F3_LENGTH) $errors[] = 'position.barcodes.f3 - maxLength is ' . self::MAX_POSITION_BARCODES_F3_LENGTH;
            if ($barcodes['f4'] && mb_strlen($barcodes['f4']) > self::MAX_POSITION_BARCODES_F4_LENGTH) $errors[] = 'position.barcodes.f4 - maxLength is ' . self::MAX_POSITION_BARCODES_F4_LENGTH;
            if ($barcodes['f5'] && mb_strlen($barcodes['f5']) > self::MAX_POSITION_BARCODES_F5_LENGTH) $errors[] = 'position.barcodes.f5 - maxLength is ' . self::MAX_POSITION_BARCODES_F5_LENGTH;
            if ($barcodes['f6'] && mb_strlen($barcodes['f6']) > self::MAX_POSITION_BARCODES_F6_LENGTH) $errors[] = 'position.barcodes.f6 - maxLength is ' . self::MAX_POSITION_BARCODES_F6_LENGTH;
        }
    }
    $unitTaxSum = $params['unitTaxSum'];

    if (!is_numeric($quantity)) $errors[] = 'position.quantity - ' . ($quantity ? 'invalid value "' . $quantity . '"' : 'is required');
    if (!is_numeric($price)) $errors[] = 'position.price - ' . ($price ? 'invalid value "' . $price . '"' : 'is required');
    if (!preg_match('/^[123456]{1}$/', $tax)) $errors[] = 'position.tax - ' . ($tax ? 'invalid value "' . $tax . '"' : 'is required');
    if (!$text or mb_strlen($text) > self::MAX_POSITION_TEXT_LENGTH) $errors[] = 'position.text - ' . ($text ? 'maxLength is ' . self::MAX_POSITION_TEXT_LENGTH : 'is required');
    if (!(preg_match('/^[1-7]$/', $paymentMethodType) or is_null($paymentMethodType))) $errors[] = 'position.paymentMethodType - invalid value "' . $paymentMethodType . '"';
    if (!(preg_match('/^[1-9]{1}$|^1[0-9]{1}$/', $paymentSubjectType) or is_null($paymentSubjectType))) $errors[] = 'position.paymentSubjectType - invalid value "' . $paymentSubjectType . '"';

    if ($supplierInfo)
    {
      if ($supplierInfo['name'] && mb_strlen($supplierInfo['name']) > self::MAX_POSITION_SUPPLIER_NAME_LENGTH) $errors[] = 'position.supplierInfo.name - maxLength is ' . self::MAX_POSITION_SUPPLIER_NAME_LENGTH;
      if ($supplierInfo['phoneNumbers']) {
        for ($i = 0; $i < count($supplierInfo['phoneNumbers']); $i++) {
            if (!preg_match('/^((8|\+7)[\- ]?)?(\(?\d{3}\)?[\- ]?)?[\d\- ]{7,10}$/', $supplierInfo['phoneNumbers'][$i])) $errors[] = 'position.supplierInfo.phoneNumbers[' . $i . '] - invalid phone';
        }
      }
    }
    if ($supplierINN && strlen($supplierINN) !== 10 && strlen($supplierINN) !== 12) $errors[] = 'position.supplierINN - length need to be 10 or 12';
    if ($agentType && (!is_numeric($agentType) or $agentType < 1 or $agentType > 127)) $errors[] = 'position.agentType - need to be from 1 to 127';

    if ($agentInfo) {
      if ($agentInfo['paymentTransferOperatorPhoneNumbers']) {
        for ($i = 0; $i < count($agentInfo['paymentTransferOperatorPhoneNumbers']); $i++) {
            if (!preg_match('/^((8|\+7)[\- ]?)?(\(?\d{3}\)?[\- ]?)?[\d\- ]{7,10}$/', $agentInfo['paymentTransferOperatorPhoneNumbers'][$i])) $errors[] = 'position.agentInfo.paymentTransferOperatorPhoneNumbers[' . $i . '] - invalid phone';
        }
      }
      if ($agentInfo['paymentAgentOperation'] && mb_strlen($agentInfo['paymentAgentOperation']) > self::MAX_PAYMENT_AGENT_OPERATION_LENGTH) $errors[] = 'position.agentInfo.paymentAgentOperation - maxLength is ' . self::MAX_PAYMENT_AGENT_OPERATION_LENGTH;
      if ($agentInfo['paymentAgentPhoneNumbers']) {
        for ($i = 0; $i < count($agentInfo['paymentAgentPhoneNumbers']); $i++) {
            if (!preg_match('/^((8|\+7)[\- ]?)?(\(?\d{3}\)?[\- ]?)?[\d\- ]{7,10}$/', $agentInfo['paymentAgentPhoneNumbers'][$i])) $errors[] = 'position.agentInfo.paymentAgentPhoneNumbers[' . $i . '] - invalid phone';
        }
      }
      if ($agentInfo['paymentOperatorPhoneNumbers']) {
        for ($i = 0; $i < count($agentInfo['paymentOperatorPhoneNumbers']); $i++) {
            if (!preg_match('/^((8|\+7)[\- ]?)?(\(?\d{3}\)?[\- ]?)?[\d\- ]{7,10}$/', $agentInfo['paymentOperatorPhoneNumbers'][$i])) $errors[] = 'position.agentInfo.paymentOperatorPhoneNumbers[' . $i . '] - invalid phone';
        }
      }
      if ($agentInfo['paymentOperatorName'] && mb_strlen($agentInfo['paymentOperatorName']) > self::MAX_PAYMENT_OPERATOR_NAME_LENGTH) $errors[] = 'position.agentInfo.paymentOperatorName - maxLength is ' . self::MAX_PAYMENT_OPERATOR_NAME_LENGTH;
      if ($agentInfo['paymentOperatorAddress'] && mb_strlen($agentInfo['paymentOperatorAddress']) > self::MAX_PAYMENT_OPERATOR_ADDRESS_LENGTH) $errors[] = 'position.agentInfo.paymentOperatorAddress - maxLength is ' . self::MAX_PAYMENT_OPERATOR_ADDRESS_LENGTH;
      if ($agentInfo['paymentOperatorINN'] && strlen($agentInfo['paymentOperatorINN']) !== 10 && strlen($agentInfo['paymentOperatorINN']) !== 12) $errors[] = 'position.agentInfo.paymentOperatorINN - length need to be 10 or 12';
    }

    if ($additionalAttribute && mb_strlen($additionalAttribute) > self::MAX_POSITION_ADDITIONAL_ATTRIBUTE_LENGTH) $errors[] = 'position.additionalAttribute - maxLength is ' . self::MAX_POSITION_ADDITIONAL_ATTRIBUTE_LENGTH;
    if ($manufacturerCountryCode && strlen($manufacturerCountryCode) > self::MAX_POSITION_MANUFACTURE_COUNTRY_CODE_LENGTH) $errors[] = 'position.manufacturerCountryCode - maxLength is ' . self::MAX_POSITION_MANUFACTURE_COUNTRY_CODE_LENGTH;
    if ($customsDeclarationNumber && mb_strlen($customsDeclarationNumber) > self::MAX_POSITION_CUSTOMS_DECLARATION_NUMBER) $errors[] = 'position.additionalAttribute - maxLength is ' . self::MAX_POSITION_CUSTOMS_DECLARATION_NUMBER;
    if (!is_numeric($excise)) $errors[] = 'position.excise - ' . ($excise ? 'invalid value "' . $excise . '"' : 'is required');

    if (isset($taxSum) && !is_numeric($taxSum)) $errors[] = 'position.taxSum - invalid value ' . $taxSum;
    if (isset($unitTaxSum) && !is_numeric($unitTaxSum)) $errors[] = 'position.taxSum - invalid value ' . $unitTaxSum;

    if (count($errors) > 0) throw new Exception(implode(', ', $errors) . PHP_EOL);

    $position = new \stdClass();
    $position->quantity = (float) $quantity;
    $position->price = (float) $price;
    $position->tax = $tax;
    $position->text = $text;
    $position->paymentMethodType = $paymentMethodType ?: 4;
    $position->paymentSubjectType = $paymentSubjectType ?: 1;

    if (isset($nomenclatureCode)) $position->nomenclatureCode = $nomenclatureCode;
    if (isset($itemCode)) $position->itemCode = $itemCode;
    if ($supplierInfo) $position->supplierInfo = $supplierInfo;
    if ($supplierINN) $position->supplierINN = $supplierINN;
    if ($agentType) $position->agentType = $agentType;
    if ($agentInfo) $position->agentInfo = $agentInfo;
    if (isset($unitOfMeasurement)) $position->unitOfMeasurement = $unitOfMeasurement;
    if (isset($quantityMeasurementUnit)) $position->quantityMeasurementUnit = $quantityMeasurementUnit;
    if ($additionalAttribute) $position->additionalAttribute = $additionalAttribute;
    if ($manufacturerCountryCode) $position->manufacturerCountryCode = $manufacturerCountryCode;
    if ($customsDeclarationNumber) $position->customsDeclarationNumber = $customsDeclarationNumber;
    if ($excise) $position->excise = $excise;
    if ($taxSum) $position->taxSum = $taxSum;
    if (isset($fractionalQuantity)) $position->fractionalQuantity = $fractionalQuantity;
    if (isset($industryAttribute)) $position->industryAttribute = $industryAttribute;
    if (isset($barcodes)) $position->barcodes = $barcodes;
    if (isset($plannedStatus)) $position->plannedStatus = $plannedStatus;

      $this->order_request->content->positions[] = $position;

    return $this;
  }

  /**
   *  Добавление оплаты
   *  @param array $params
   *  @return orangedata_client $this
   *  @throws Exception
   */
  public function add_payment_to_order(array $params = []) {
    $type = $params['type'];
    $amount = $params['amount'];
    $errors = [];

    if (!preg_match('/^[1-9]{1}$|^1[0-6]{1}$/', $type)) $errors[] = 'payments.type - ' . ($type ? 'invalid value "' . $type . '"' : 'is required');
    if (!is_numeric($amount)) $errors[] = 'payments.amount - ' . ($amount ? 'invalid value "' . $amount . '"' : 'is required');

    if (count($errors) > 0) throw new Exception(implode(', ', $errors) . PHP_EOL);

    $payment = new \stdClass();
    $payment->type = (int) $type;
    $payment->amount = (float) $amount;
    $this->order_request->content->checkClose->payments[] = $payment;

    return $this;
  }

  /**
   *  Добавление агента (поддерживается только ФФД 1.05)
   *  @param array $params
   *  @return orangedata_client $this
   *  @throws Exception
   */
    public function add_agent_to_order(array $params = [])
    {
        $errors = [];
        if ($this->order_request->content->ffdVersion === self::FFD_VERSION_12)
            $errors[] = 'add_agent_to_order not supported by FFD1.2';

        $agentType = $params['agentType'];
        $paymentTransferOperatorPhoneNumbers = $params['paymentTransferOperatorPhoneNumbers'];
        $paymentAgentOperation = $params['paymentAgentOperation'];
        $paymentAgentPhoneNumbers = $params['paymentAgentPhoneNumbers'];
        $paymentOperatorPhoneNumbers = $params['paymentOperatorPhoneNumbers'];
        $paymentOperatorName = $params['paymentOperatorName'];
        $paymentOperatorAddress = $params['paymentOperatorAddress'];
        $paymentOperatorINN = $params['paymentOperatorINN'];
        $supplierPhoneNumbers = $params['supplierPhoneNumbers'];


        if ($agentType < 1 || $agentType > 127) $errors[] = 'agentType - invalid value';
        for ($i = 0; $i < count($paymentTransferOperatorPhoneNumbers); $i++) {
            if (!preg_match('/^((8|\+7)[\- ]?)?(\(?\d{3}\)?[\- ]?)?[\d\- ]{7,10}$/', $paymentTransferOperatorPhoneNumbers[$i]))
                $errors[] = 'paymentTransferOperatorPhoneNumbers[' . $i . '] - invalid phone';
        }
        if (mb_strlen($paymentAgentOperation) > self::MAX_PAYMENT_AGENT_OPERATION_LENGTH) $errors[] = 'paymentAgentOperation - maxLength is ' . self::MAX_PAYMENT_AGENT_OPERATION_LENGTH;
        for ($i = 0; $i < count($paymentAgentPhoneNumbers); $i++) {
            if (!preg_match('/^((8|\+7)[\- ]?)?(\(?\d{3}\)?[\- ]?)?[\d\- ]{7,10}$/', $paymentAgentPhoneNumbers[$i]))
                $errors[] = 'paymentAgentPhoneNumbers[' . $i . '] - invalid phone';
        }
        for ($i = 0; $i < count($paymentOperatorPhoneNumbers); $i++) {
            if (!preg_match('/^((8|\+7)[\- ]?)?(\(?\d{3}\)?[\- ]?)?[\d\- ]{7,10}$/', $paymentOperatorPhoneNumbers[$i]))
                $errors[] = 'paymentOperatorPhoneNumbers[' . $i . '] - invalid phone';
        }
        if (mb_strlen($paymentOperatorName) > self::MAX_PAYMENT_OPERATOR_NAME_LENGTH) $errors[] = 'paymentOperatorName - maxLength is ' . self::MAX_PAYMENT_OPERATOR_NAME_LENGTH;
        if (mb_strlen($paymentOperatorAddress) > self::MAX_PAYMENT_OPERATOR_ADDRESS_LENGTH) $errors[] = 'paymentOperatorAddress - maxLength is ' . self::MAX_PAYMENT_OPERATOR_ADDRESS_LENGTH;
        if ($paymentOperatorINN && strlen($paymentOperatorINN) !== 10 && strlen($paymentOperatorINN) !== 12) $errors[] = 'paymentOperatorINN - length need to be 10 or 12';

        for ($i = 0; $i < count($supplierPhoneNumbers); $i++) {
            if (!preg_match('/^((8|\+7)[\- ]?)?(\(?\d{3}\)?[\- ]?)?[\d\- ]{7,10}$/', $supplierPhoneNumbers[$i]))
                $errors[] = 'supplierPhoneNumbers[' . $i . '] - invalid phone';
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
   *  @param array $params
   *  @return orangedata_client $this
   *  @throws Exception
   */
  public function add_user_attribute(array $params = []) {
    $name = $params['name'];
    $value = $params['value'];
    $errors = [];

    if (!$name or mb_strlen($name) > self::MAX_ADDITIONAL_USER_ATTRIBUTE_NAME_LENGTH) $errors[] = 'additionalUserAttribute.name - ' . ($name ? 'maxLength is ' . self::MAX_ADDITIONAL_USER_ATTRIBUTE_NAME_LENGTH : 'is required');
    if (!$value or mb_strlen($value) > self::MAX_ADDITIONAL_USER_ATTRIBUTE_VALUE_LENGTH) $errors[] = 'additionalUserAttribute.value - ' . ($value ? 'maxLength is ' . self::MAX_ADDITIONAL_USER_ATTRIBUTE_VALUE_LENGTH : 'is required');

    if (count($errors) > 0) throw new Exception(implode(', ', $errors) . PHP_EOL);

    $this->order_request->content->additionalUserAttribute = new \stdClass();
    $this->order_request->content->additionalUserAttribute->name = $name;
    $this->order_request->content->additionalUserAttribute->value = $value;

    return $this;
  }

  /**
   *  Добавление дополнительных аттрибутов
   *  @param array $params
   *  @return orangedata_client $this
   *  @throws Exception
   */
    public function add_additional_attributes(array $params = [])
    {
        $errors = [];
        $additionalAttribute = $params['additionalAttribute'];

        if ($this->order_request->content->ffdVersion === self::FFD_VERSION_105)
        {
            $customer = $params['customer'];
            $customerINN = $params['customerINN'];
            if (mb_strlen($customer) > self::MAX_CUSTOMER_LENGTH) $errors[] = 'customer - maxLength is ' . self::MAX_CUSTOMER_LENGTH;
            if ($customerINN && strlen($customerINN) !== 10 && strlen($customerINN) !== 12) $errors[] = 'customerINN - length need to be 10 or 12';
        }

        if ($this->order_request->content->ffdVersion === self::FFD_VERSION_12)
        {
            $customerInfo = $params['customerInfo'];
            if ($customerInfo)
            {
                if ($customerInfo['name'] && mb_strlen($customerInfo['name']) > self::MAX_CUSTOMER_INFO_NAME_LENGTH) $errors[] = 'customerInfo.name - maxLength is ' . self::MAX_CUSTOMER_INFO_NAME_LENGTH;
                if ($customerInfo['inn'] && strlen($customerInfo['inn']) !== 10 && strlen($customerInfo['inn']) !== 12) $errors[] = 'customerInfo.inn - length need to be 10 or 12';
                if ($customerInfo['birthDate'] && !preg_match('/^(0?[1-9]|[12][0-9]|3[01]).(0?[1-9]|1[012]).((19|20)\d\d)$/', $customerInfo['birthDate'])) $errors[] = 'customerInfo.birthDate - invalid value ' . $customerInfo['birthDate'];
                if ($customerInfo['citizenship'] && mb_strlen($customerInfo['citizenship']) > self::MAX_CUSTOMER_INFO_CITIZENSHIP_LENGTH) $errors[] = 'customerInfo.citizenship - maxLength is ' . self::MAX_CUSTOMER_INFO_CITIZENSHIP_LENGTH;
                if ($customerInfo['identityDocumentCode'] && mb_strlen($customerInfo['identityDocumentCode']) > 2) $errors[] = 'customerInfo.identityDocumentCode - maxLength is 2';
                if ($customerInfo['identityDocumentData'] && mb_strlen($customerInfo['identityDocumentData']) > self::MAX_CUSTOMER_INFO_IDENTITY_DOCUMENT_DATA_LENGTH) $errors[] = 'customerInfo.identityDocumentDate - maxLength is ' . self::MAX_CUSTOMER_INFO_IDENTITY_DOCUMENT_DATA_LENGTH;
                if ($customerInfo['address'] && mb_strlen($customerInfo['address']) > self::MAX_CUSTOMER_INFO_ADDRESS_LENGTH) $errors[] = 'customerInfo.address - maxLength is ' . self::MAX_CUSTOMER_INFO_ADDRESS_LENGTH;
            }

            $operationalAttribute = $params['operationalAttribute'];
            if ($operationalAttribute)
            {
                if ($operationalAttribute['date'])
                    $operationalAttribute['date'] = $operationalAttribute['date']->setTime(0, 0)->format(DateTime::ISO8601);
                if ($operationalAttribute['id'] && mb_strlen($operationalAttribute['id']) > self::MAX_OPERATIONAL_ATTRIBUTE_ID_LENGTH) $errors[] = 'operationalAttribute.id - maxLength is ' . self::MAX_OPERATIONAL_ATTRIBUTE_ID_LENGTH;
                if ($operationalAttribute['value'] && mb_strlen($operationalAttribute['value']) > self::MAX_OPERATIONAL_ATTRIBUTE_VALUE_LENGTH) $errors[] = 'operationalAttribute.value - maxLength is ' . self::MAX_OPERATIONAL_ATTRIBUTE_VALUE_LENGTH;
            }
        }

        $senderEmail = $params['senderEmail'];
        $totalSum = $params['totalSum'];
        $vat1Sum = $params['vat1Sum'];
        $vat2Sum = $params['vat2Sum'];
        $vat3Sum = $params['vat3Sum'];
        $vat4Sum = $params['vat4Sum'];
        $vat5Sum = $params['vat5Sum'];
        $vat6Sum = $params['vat6Sum'];

        if (mb_strlen($additionalAttribute) > self::MAX_ADDITIONAL_ATTRIBUTE_LENGTH) $errors[] = 'additionalAttribute - maxLength is ' . self::MAX_ADDITIONAL_ATTRIBUTE_LENGTH;
        if ($senderEmail && mb_strlen($senderEmail) > self::MAX_SENDER_EMAIL_LENGTH) $errors[] = 'senderEmail - maxLength is ' . self::MAX_SENDER_EMAIL_LENGTH;
        if ($totalSum && !is_numeric($totalSum)) $errors[] = 'totalSum - invalid value';
        if ($vat1Sum && !is_numeric($vat1Sum)) $errors[] = '$vat1Sum - invalid value';
        if ($vat2Sum && !is_numeric($vat2Sum)) $errors[] = '$vat2Sum - invalid value';
        if ($vat3Sum && !is_numeric($vat3Sum)) $errors[] = '$vat3Sum - invalid value';
        if ($vat4Sum && !is_numeric($vat4Sum)) $errors[] = '$vat4Sum - invalid value';
        if ($vat5Sum && !is_numeric($vat5Sum)) $errors[] = '$vat5Sum - invalid value';
        if ($vat6Sum && !is_numeric($vat6Sum)) $errors[] = '$vat6Sum - invalid value';

        if (count($errors) > 0) throw new Exception(implode(', ', $errors) . PHP_EOL);

        if ($additionalAttribute) $this->order_request->content->additionalAttribute = $additionalAttribute;
        if (isset($customer)) $this->order_request->content->customer = $customer;
        if (isset($customerINN)) $this->order_request->content->customerINN = $customerINN;
        if (isset($customerInfo)) $this->order_request->content->customerInfo = $customerInfo;
        if (isset($operationalAttribute)) $this->order_request->content->operationalAttribute = $operationalAttribute;
        if ($senderEmail) $this->order_request->content->senderEmail = $senderEmail;
        if ($totalSum) $this->order_request->content->totalSum = $totalSum;
        if ($vat1Sum) $this->order_request->content->vat1Sum = $vat1Sum;
        if ($vat2Sum) $this->order_request->content->vat2Sum = $vat2Sum;
        if ($vat3Sum) $this->order_request->content->vat3Sum = $vat3Sum;
        if ($vat4Sum) $this->order_request->content->vat4Sum = $vat4Sum;
        if ($vat5Sum) $this->order_request->content->vat5Sum = $vat5Sum;
        if ($vat6Sum) $this->order_request->content->vat6Sum = $vat6Sum;

        return $this;
    }

  /**
   *  Добавление вендинга
   *  @param array $params
   *  @return orangedata_client $this
   *  @throws Exception
   */
  public function add_vending_to_order(array $params = []) {
    $automatNumber = $params['automatNumber'];
    $settlementAddress = $params['settlementAddress'];
    $settlementPlace = $params['settlementPlace'];
    $errors = [];

    if (!$automatNumber || strlen($automatNumber) > self::MAX_VENDING_AUTOMAT_NUMBER_LENGTH) $errors[] = 'automatNumber - ' . ($automatNumber ? 'maxLength is ' . self::MAX_VENDING_AUTOMAT_NUMBER_LENGTH : 'is required');
    if (!$settlementAddress || mb_strlen($settlementAddress) > self::MAX_VENDING_ADDRESS_LENGTH) $errors[] = 'settlementAddress - ' . ($settlementAddress ? 'maxLength is ' . self::MAX_VENDING_ADDRESS_LENGTH : 'is required');
    if (!$settlementPlace || mb_strlen($settlementPlace) > self::MAX_VENDING_PLACE_LENGTH) $errors[] = 'settlementPlace - ' . ($settlementPlace ? 'maxLength is ' . self::MAX_VENDING_PLACE_LENGTH : 'is required');

    if (count($errors) > 0) throw new Exception(implode(', ', $errors) . PHP_EOL);

    $this->order_request->content->automatNumber = $automatNumber;
    $this->order_request->content->settlementAddress = $settlementAddress;
    $this->order_request->content->settlementPlace = $settlementPlace;

    return $this->order_request;
  }


    /**
     *  Добавление курьера
     *  @param array $params
     *  @return orangedata_client $this
     *  @throws Exception
     */
    public function add_courier_to_order(array $params = []) {
        $settlementAddress = $params['settlementAddress'];
        $settlementPlace = $params['settlementPlace'];
        $cashier = $params['cashier'];
        $cashierINN = $params['cashierINN'];
        $errors = [];

        if (!$settlementAddress || mb_strlen($settlementAddress) > self::MAX_VENDING_ADDRESS_LENGTH) $errors[] = 'settlementAddress - ' . ($settlementAddress ? 'maxLength is ' . self::MAX_VENDING_ADDRESS_LENGTH : 'is required');
        if (!$settlementPlace || mb_strlen($settlementPlace) > self::MAX_VENDING_PLACE_LENGTH) $errors[] = 'settlementPlace - ' . ($settlementPlace ? 'maxLength is ' . self::MAX_VENDING_PLACE_LENGTH : 'is required');
        if (!$cashier || mb_strlen($cashier) > self::MAX_CASHIER_LENGTH) $errors[] = 'cashier - ' . ($cashier ? 'maxLength is ' . self::MAX_CASHIER_LENGTH : 'is required');
        if ($cashierINN && (strlen($cashierINN) !== 12)) $errors[] = 'cashierINN - ' . $cashierINN . ' length need to be 12';

        if (count($errors) > 0) throw new Exception(implode(', ', $errors) . PHP_EOL);

        $this->order_request->content->settlementAddress = $settlementAddress;
        $this->order_request->content->settlementPlace = $settlementPlace;
        $this->order_request->content->cashier = $cashier;
        $this->order_request->content->cashierINN = $cashierINN;

        return $this->order_request;
    }

  /**
   *  Отправка чека
   *  @return bool
   *  @throws Exception
   */
  public function send_order() {
    $jsonstring = json_encode($this->order_request, JSON_PRESERVE_ZERO_FRACTION);
    $sign = $this->sign_order_request($jsonstring);
    $curl = is_numeric($this->api_url) ? $this->prepare_curl($this->edit_url($this->api_url, true)) : $this->prepare_curl($this->api_url . '/api/v2/documents/');
    $headers = [
      "Content-Length: " . strlen($jsonstring),
      "Content-Type: application/json; charset=utf-8",
      "X-Signature: " . $sign
    ];

    curl_setopt_array($curl, [
      CURLOPT_POST => true,
      CURLOPT_HTTPHEADER => $headers,
      CURLOPT_POSTFIELDS => $jsonstring
    ]);
    $answer = curl_exec($curl);
    $info = curl_getinfo($curl);
    switch ($info['http_code']) {
      case '201':
        $return = true;
        break;
      case '400':
      case '503':
        $return = $answer;
        break;
      case '401':
        throw new Exception('Unauthorized. Client certificate check is failed');
      case '404':
        throw new Exception('Endpoint not found');
      case '409':
        throw new Exception('Conflict. Order with same id is already exists in the system.');
      default:
        $return = false;
        break;
    }
    if (false === $return) {
      throw new Exception('Curl error: ' . curl_error($curl));
    }
    return $return;
  }

  /**
   * get_order_status(a) - Проверка состояния чека
   *  @param string $id (a) - Идентификатор документа (Строка от 1 до 32 символов)
   *  @return bool
   *  @throws Exception
   */
  public function get_order_status($id) {
    if (strlen($id) > 32 OR strlen($id) == 0) {
      throw new Exception('Invalid order identifier');
    }
    $curl = is_numeric($this->api_url) ? $this->prepare_curl($this->edit_url($this->api_url, true) . $this->inn . '/status/' . $id) : $this->prepare_curl($this->api_url . '/api/v2/documents/' . $this->inn . '/status/' . $id);
    curl_setopt($curl, CURLOPT_POST, false);
    $answer = curl_exec($curl);
    $info = curl_getinfo($curl);
    switch ($info['http_code']) {
      case '200':
        $return = $answer;
        break;
      case '202':
        $return = true;
        break;
      case '400':
        throw new Exception('Not Found. Order was not found in the system.');
      case '401':
        throw new Exception('Unauthorized. Client certificate check is failed');
      case '404':
        throw new Exception('Endpoint not found');
      default:
        $return = false;
        break;
    }
    if (false === $return) {
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
    curl_setopt_array($curl, [
      CURLOPT_SSLKEY => $this->client_pkey,
      CURLOPT_SSLCERT => $this->client_cert,
      CURLOPT_SSLCERTPASSWD => $this->client_cert_pass,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_CONNECTTIMEOUT => 3,
      CURLOPT_TIMEOUT => 15,
      CURLOPT_CAINFO => $this->ca_cert,
    ]);
    if ($this->debug) {
      curl_setopt_array($curl, [
        CURLOPT_VERBOSE => 1,
        CURLOPT_STDERR => fopen($this->debug_file, 'a'),
      ]);
    }
    return $curl;
  }

  public function is_debug($is_debug = true) {
    $this->debug = (bool) $is_debug;
    return $this;
  }

  /**
   *  Создание чека-коррекции
   *  @param array $params
   *  @return orangedata_client $this
   *  @throws Exception
   */
  public function create_correction(array $params = []) {
    $id = $params['id'];
    $key = $params['key'];
    $group = $params['group'];
    $ignoreItemCodeCheck = $params['ignoreItemCodeCheck'];
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
    $errors = [];

    if (!$id || strlen($id) > self::MAX_ID_LENGTH) $errors[] = 'id - ' . ($id ? 'maxLength is ' . self::MAX_ID_LENGTH : 'is required');
    if (!$this->inn || (strlen($this->inn ) !== 10 && strlen($this->inn ) !== 12)) $errors[] = 'inn - ' . ($this->inn ? 'length need to be 10 or 12' : 'is required');
    if (!$key || strlen($key) > self::MAX_KEY_LENGTH) $errors[] = 'key - ' . ($key ? 'maxLength is ' . self::MAX_KEY_LENGTH : 'is required');
    if (!is_numeric($correctionType) || !preg_match('/^[01]$/', $correctionType)) $errors[] = 'correctionType - ' . ($correctionType ? 'need to be 0 or 1' : 'is required');
    if (!is_numeric($type) || !preg_match('/^[13]$/', $type)) $errors[] = 'type - ' . ($type ? 'need to be 1 or 3' : 'is required');
    if (!$description || mb_strlen($description) > self::MAX_CORRECTION_DESCRIPTION_LENGTH) $errors[] = 'description - ' . ($description ? 'maxLength is ' . self::MAX_CORRECTION_DESCRIPTION_LENGTH : 'is required');
    if (!$causeDocumentDate) $errors[] = 'causeDocumentDate - is required';
    if (!$causeDocumentNumber || mb_strlen($causeDocumentNumber) > self::MAX_CORRECTION_CAUSE_DOCUMENT_NUMBER) $errors[] = 'causeDocumentNumber - ' . ($causeDocumentNumber ? 'maxLength is ' . self::MAX_CORRECTION_CAUSE_DOCUMENT_NUMBER : 'is required');
    if (!$totalSum || !is_numeric($totalSum)) $errors[] = 'totalSum - ' . ($totalSum ? 'invalid value' : 'is required');
    if ($cashSum && !is_numeric($cashSum)) $errors[] = 'cashSum - invalid value';
    if ($eCashSum && !is_numeric($eCashSum)) $errors[] = 'eCashSum - invalid value';
    if ($prepaymentSum && !is_numeric($prepaymentSum)) $errors[] = 'prepaymentSum - invalid value';
    if ($postpaymentSum && !is_numeric($postpaymentSum)) $errors[] = 'postpaymentSum - invalid value';
    if ($otherPaymentTypeSum && !is_numeric($otherPaymentTypeSum)) $errors[] = 'otherPaymentTypeSum - invalid value';
    if ($tax1Sum && !is_numeric($tax1Sum)) $errors[] = 'tax1Sum - invalid value';
    if ($tax2Sum && !is_numeric($tax2Sum)) $errors[] = 'tax2Sum - invalid value';
    if ($tax3Sum && !is_numeric($tax3Sum)) $errors[] = 'tax3Sum - invalid value';
    if ($tax4Sum && !is_numeric($tax4Sum)) $errors[] = 'tax4Sum - invalid value';
    if ($tax5Sum && !is_numeric($tax5Sum)) $errors[] = 'tax5Sum - invalid value';
    if ($tax6Sum && !is_numeric($tax6Sum)) $errors[] = 'tax6Sum - invalid value';
    if (is_numeric($taxationSystem) && !preg_match('/^[012345]$/', $taxationSystem)) $errors[] = 'taxationSystem - invalid value';

    if (count($errors) > 0) throw new Exception(implode(', ', $errors) . PHP_EOL);


    $this->correction_request = new \stdClass();
    $this->correction_request->id = (string) $id;
    $this->correction_request->inn = $this->inn;
    $this->correction_request->group = $group ?: 'Main';
    $this->correction_request->key = $key;
    $this->correction_request->ignoreItemCodeCheck = $ignoreItemCodeCheck;
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
   *  @param array $params
   *  @return orangedata_client $this
   *  @throws Exception
   */
  public function add_vending_to_correction(array $params = []) {
    $automatNumber = $params['automatNumber'];
    $settlementAddress = $params['settlementAddress'];
    $settlementPlace = $params['settlementPlace'];
    $errors = [];

    if (!$automatNumber || strlen($automatNumber) > self::MAX_VENDING_AUTOMAT_NUMBER_LENGTH) $errors[] = 'automatNumber - ' . ($automatNumber ? 'maxLength is ' . self::MAX_VENDING_AUTOMAT_NUMBER_LENGTH : 'is required');
    if (!$settlementAddress || strlen($settlementAddress) > self::MAX_VENDING_ADDRESS_LENGTH) $errors[] = 'settlementAddress - ' . ($settlementAddress ? 'maxLength is ' . self::MAX_VENDING_ADDRESS_LENGTH : 'is required');
    if (!$settlementPlace || strlen($settlementPlace) > self::MAX_VENDING_PLACE_LENGTH) $errors[] = 'settlementPlace - ' . ($settlementPlace ? 'maxLength is ' . self::MAX_VENDING_PLACE_LENGTH : 'is required');

    if (count($errors) > 0) throw new Exception(implode(', ', $errors) . PHP_EOL);

    $this->correction_request->content->automatNumber = $automatNumber;
    $this->correction_request->content->settlementAddress = $settlementAddress;
    $this->correction_request->content->settlementPlace = $settlementPlace;

    return $this;
  }


    /**
     *  Добавление курьера в коррекцию
     *  @param array $params
     *  @return orangedata_client $this
     *  @throws Exception
     */
    public function add_courier_to_correction(array $params = []) {
        $settlementAddress = $params['settlementAddress'];
        $settlementPlace = $params['settlementPlace'];
        $cashier = $params['cashier'];
        $cashierINN = $params['cashierINN'];
        $errors = [];

        if (!$settlementAddress || mb_strlen($settlementAddress) > self::MAX_VENDING_ADDRESS_LENGTH) $errors[] = 'settlementAddress - ' . ($settlementAddress ? 'maxLength is ' . self::MAX_VENDING_ADDRESS_LENGTH : 'is required');
        if (!$settlementPlace || mb_strlen($settlementPlace) > self::MAX_VENDING_PLACE_LENGTH) $errors[] = 'settlementPlace - ' . ($settlementPlace ? 'maxLength is ' . self::MAX_VENDING_PLACE_LENGTH : 'is required');
        if (!$cashier || mb_strlen($cashier) > self::MAX_CASHIER_LENGTH) $errors[] = 'cashier - ' . ($cashier ? 'maxLength is ' . self::MAX_CASHIER_LENGTH : 'is required');
        if ($cashierINN && (strlen($cashierINN) !== 12)) $errors[] = 'cashierINN - ' . $cashierINN . ' length need to be 12';

        if (count($errors) > 0) throw new Exception(implode(', ', $errors) . PHP_EOL);

        $this->correction_request->content->settlementAddress = $settlementAddress;
        $this->correction_request->content->settlementPlace = $settlementPlace;
        $this->correction_request->content->cashier = $cashier;
        $this->correction_request->content->cashierINN = $cashierINN;

        return $this->correction_request;
    }

  /**
   * post_correction() - Отправка чека-коррекции на обработку
   *  @return bool
   *  @throws Exception
   */
  public function post_correction() {
    $jsonstring = json_encode($this->correction_request, JSON_PRESERVE_ZERO_FRACTION);
    if(!$jsonstring){
      throw  new Exception('JSON encode error:' . json_last_error_msg());
    }
    $sign = $this->sign_order_request($jsonstring);
    $curl = is_numeric($this->api_url) ? $this->prepare_curl($this->edit_url($this->api_url, false)) : $this->prepare_curl($this->api_url . '/api/v2/corrections/');
    $headers = [
      "Content-Length: " . strlen($jsonstring),
      "Content-Type: application/json; charset=utf-8",
      "X-Signature: " . $sign
    ];
    curl_setopt_array($curl, [
      CURLOPT_POST => true,
      CURLOPT_HTTPHEADER => $headers,
      CURLOPT_POSTFIELDS => $jsonstring
    ]);
    $answer = curl_exec($curl);
    $info = curl_getinfo($curl);
    switch ($info['http_code']) {
      case '201':
        $return = true;
        break;
        case '503':
        case '400':
        $return = $answer;
        break;
      case '401':
        throw new Exception('Unauthorized. Client certificate check is failed');
      case '404':
        throw new Exception('Endpoint not found');
      case '409':
        throw new Exception('Conflict. Bill with same id is already exists in the system.');
        default:
        $return = false;
        break;
    }
    if (false === $return) {
      throw new Exception('Curl error: ' . curl_error($curl));
    }
    return $return;
  }

  /**
   * get_correction_status(a) - Проверка состояния чека-коррекции
   *  @param $id (a) - Идентификатор документа (Строка от 1 до 32 символов)
   *  @return bool
   *  @throws Exception
   */
  public function get_correction_status($id) {
    if (strlen($id) > 32 OR strlen($id) == 0) {
        throw new Exception('Invalid order identifier');
    }
    $curl = is_numeric($this->api_url) ? $this->prepare_curl($this->edit_url($this->api_url,false) . $this->inn . '/status/' . $id) : $this->prepare_curl($this->api_url . '/api/v2/corrections/' . $this->inn . '/status/' . $id);
    curl_setopt($curl, CURLOPT_POST, false);
    $answer = curl_exec($curl);
    $info = curl_getinfo($curl);
    switch ($info['http_code']) {
      case '200':
        $return = $answer;
        break;
      case '202':
        $return = true;
        break;
      case '400':
        $return = $answer;
        //throw new Exception('Not Found. Order was not found in the system. Company not found.');
        break;
      case '401':
        throw new Exception('Unauthorized. Client certificate check is failed');
      case '404':
        throw new Exception('Endpoint not found');
      default:
        $return = false;
        break;
    }
    if (false === $return) {
      throw new Exception('Curl error: ' . curl_error($curl));
    }
    return $return;
  }


    /**
     *  Создание чека-коррекции для ФФД1.2
     *  @param array $params
     *  @return orangedata_client $this
     *  @throws Exception
     */
    public function create_correction12(array $params = []) {
        $errors = [];
        if (!isset($params['ffdVersion']))
            $errors[] = 'create_correction12 supports only FFD1.2';

        $id = $params['id'];
        $group = $params['group'];
        $key = $params['key'];
        $ignoreItemCodeCheck = $params['ignoreItemCodeCheck'];
        $correctionType = $params['correctionType'];
        $type = $params['type'];
        $customerContact = $params['customerContact'];
        $causeDocumentDate = $params['causeDocumentDate'];
        $causeDocumentNumber = $params['causeDocumentNumber'];
        $totalSum = $params['totalSum'];
        $vat1Sum = $params['vat1Sum'];
        $vat2Sum = $params['vat2Sum'];
        $vat3Sum = $params['vat3Sum'];
        $vat4Sum = $params['vat4Sum'];
        $vat5Sum = $params['vat5Sum'];
        $vat6Sum = $params['vat6Sum'];

        if (!$id || strlen($id) > self::MAX_ID_LENGTH) $errors[] = 'id - ' . ($id ? 'maxLength is ' . self::MAX_ID_LENGTH : 'is required');
        if (!$this->inn || (strlen($this->inn ) !== 10 && strlen($this->inn ) !== 12)) $errors[] = 'inn - ' . ($this->inn ? 'length need to be 10 or 12' : 'is required');
        if (!$key || strlen($key) > self::MAX_KEY_LENGTH) $errors[] = 'key - ' . ($key ? 'maxLength is ' . self::MAX_KEY_LENGTH : 'is required');
        if (!is_numeric($correctionType) || !preg_match('/^[01]$/', $correctionType)) $errors[] = 'correctionType - ' . ($correctionType ? 'need to be 0 or 1' : 'is required');
        if (!is_numeric($type) || !preg_match('/^[13]$/', $type)) $errors[] = 'type - ' . ($type ? 'need to be 1 or 3' : 'is required');
        if (!filter_var($customerContact, FILTER_VALIDATE_EMAIL) && !preg_match('/^((8|\+7)[\- ]?)?(\(?\d{3}\)?[\- ]?)?[\d\- ]{7,10}$/', $customerContact)) $errors[] = 'content.customerContact - invalid value';
        if (!$causeDocumentDate) $errors[] = 'causeDocumentDate - is required';
        if (!$causeDocumentNumber || mb_strlen($causeDocumentNumber) > self::MAX_CORRECTION_CAUSE_DOCUMENT_NUMBER) $errors[] = 'causeDocumentNumber - ' . ($causeDocumentNumber ? 'maxLength is ' . self::MAX_CORRECTION_CAUSE_DOCUMENT_NUMBER : 'is required');
        if ($totalSum && !is_numeric($totalSum)) $errors[] = 'totalSum - invalid value';
        if ($vat1Sum && !is_numeric($vat1Sum)) $errors[] = 'vat1Sum - invalid value';
        if ($vat2Sum && !is_numeric($vat2Sum)) $errors[] = 'vat2Sum - invalid value';
        if ($vat3Sum && !is_numeric($vat3Sum)) $errors[] = 'vat3Sum - invalid value';
        if ($vat4Sum && !is_numeric($vat4Sum)) $errors[] = 'vat4Sum - invalid value';
        if ($vat5Sum && !is_numeric($vat5Sum)) $errors[] = 'vat5Sum - invalid value';
        if ($vat6Sum && !is_numeric($vat6Sum)) $errors[] = 'vat6Sum - invalid value';

        if (count($errors) > 0) throw new Exception(implode(', ', $errors) . PHP_EOL);

        $this->correction_request = new \stdClass();
        $this->correction_request->id = (string) $id;
        $this->correction_request->inn = $this->inn;
        $this->correction_request->key = $key;
        $this->correction_request->ignoreItemCodeCheck = $ignoreItemCodeCheck;
        $this->correction_request->group = $group ?: 'Main_2';
        $this->correction_request->content = new \stdClass();
        $this->correction_request->content->ffdVersion = self::FFD_VERSION_12;
        $this->correction_request->content->correctionType = (int) $correctionType;
        $this->correction_request->content->type = (int) $type;
        $this->correction_request->content->causeDocumentDate = $causeDocumentDate->setTime(0, 0)->format(DateTime::ISO8601);
        $this->correction_request->content->causeDocumentNumber = $causeDocumentNumber;
        if ($totalSum) $this->correction_request->content->totalSum = (float) $totalSum;
        if ($vat1Sum) $this->correction_request->content->vat1Sum = (float) $vat1Sum;
        if ($vat2Sum) $this->correction_request->content->vat2Sum = (float) $vat2Sum;
        if ($vat3Sum) $this->correction_request->content->vat3Sum = (float) $vat3Sum;
        if ($vat4Sum) $this->correction_request->content->vat4Sum = (float) $vat4Sum;
        if ($vat5Sum) $this->correction_request->content->vat5Sum = (float) $vat5Sum;
        if ($vat6Sum) $this->correction_request->content->vat6Sum = (float) $vat6Sum;

        return $this;
    }

    /**
     *  Add position to correction ФФД1.2
     *  @param array $params
     *  @return orangedata_client $this
     *  @throws Exception
     */
    public function add_position_to_correction(array $params = []) {
        $errors = [];
        if (!isset( $this->correction_request->content->ffdVersion))
            $errors[] = 'add_position_to_correction supports only FFD1.2';
        $quantity = $params['quantity'];
        $price = $params['price'];
        $tax = $params['tax'];
        $taxSum = $params['taxSum'];
        $text = $params['text'];
        $paymentMethodType = $params['paymentMethodType'];
        $paymentSubjectType = $params['paymentSubjectType'];
        $supplierInfo = $params['supplierInfo'];
        $supplierINN = $params['supplierINN'];
        $agentType = $params['agentType'];
        $agentInfo = $params['agentInfo'];
        $additionalAttribute = $params['additionalAttribute'];
        $manufacturerCountryCode = $params['manufacturerCountryCode'];
        $customsDeclarationNumber = $params['customsDeclarationNumber'];
        $excise = $params['excise'];

        $quantityMeasurementUnit = $params['quantityMeasurementUnit'];
        if ($quantityMeasurementUnit && mb_strlen($quantityMeasurementUnit) > self::MAX_POSITION_QUANTITY_MEASUREMENT_UNIT_LENGTH) $errors[] = 'position.quantityMeasurementUnit - maxLength is ' . self::MAX_POSITION_QUANTITY_MEASUREMENT_UNIT_LENGTH;
        $itemCode = $params['itemCode'];
        if ($itemCode && mb_strlen($itemCode) > self::MAX_POSITION_ITEM_CODE_LENGTH) $errors[] = 'position.itemCode - maxLength is ' . self::MAX_POSITION_ITEM_CODE_LENGTH;

        $plannedStatus = $params['plannedStatus'];
        if ($plannedStatus && (!is_numeric($plannedStatus) || ($plannedStatus < 0 || $plannedStatus > 256))) $errors[] = 'position.plannedStatus - invalid value is ' . $plannedStatus;

        $fractionalQuantity = $params['fractionalQuantity'];
        if ($fractionalQuantity) {
            if ($fractionalQuantity['Numerator'] && strlen($fractionalQuantity['Numerator']) > self::MAX_POSITION_FRACTIONAL_QUANTITY_NUMERATOR_BYTE) $errors[] = 'position.fractionalQuantity.Numerator - maxByteLength is ' . self::MAX_POSITION_FRACTIONAL_QUANTITY_NUMERATOR_BYTE;
            if ($fractionalQuantity['Numerator'] && strlen($fractionalQuantity['Denominator']) > self::MAX_POSITION_FRACTIONAL_QUANTITY_DENOMINATOR_BYTE) $errors[] = 'position.fractionalQuantity.Denominator - maxByteLength is ' . self::MAX_POSITION_FRACTIONAL_QUANTITY_DENOMINATOR_BYTE;
        }

        $industryAttribute = $params['industryAttribute'];
        if ($industryAttribute) {
            if ($industryAttribute['foivId'] && mb_strlen($industryAttribute['foivId']) > self::MAX_POSITION_INDUSTRY_ATTRIBUTE_FOIVID_LENGTH) $errors[] = 'position.industryAttribute.foivId - maxLength is ' . self::MAX_POSITION_INDUSTRY_ATTRIBUTE_FOIVID_LENGTH;
            if ($industryAttribute['causeDocumentNumber'] && mb_strlen($industryAttribute['causeDocumentNumber']) > self::MAX_POSITION_INDUSTRY_ATTRIBUTE_CAUSE_DOCUMENT_NUMBER_LENGTH) $errors[] = 'position.industryAttribute.causeDocumentNumber - maxLength is ' . self::MAX_POSITION_INDUSTRY_ATTRIBUTE_CAUSE_DOCUMENT_NUMBER_LENGTH;
            if ($industryAttribute['value'] && mb_strlen($industryAttribute['value']) > self::MAX_POSITION_INDUSTRY_ATTRIBUTE_VALUE_LENGTH) $errors[] = 'position.industryAttribute.value - maxLength is ' . self::MAX_POSITION_INDUSTRY_ATTRIBUTE_VALUE_LENGTH;
            if ($industryAttribute['causeDocumentDate'] && !preg_match('/^(0?[1-9]|[12][0-9]|3[01]).(0?[1-9]|1[012]).((19|20)\d\d)$/', $industryAttribute['causeDocumentDate'])) $errors[] = 'position.industryAttribute.causeDocumentDate - invalid value ' . $industryAttribute['causeDocumentDate'];
        }
        $barcodes = $params['barcodes'];
        if ($barcodes) {
            if ($barcodes['ean8'] && mb_strlen($barcodes['ean8']) > self::MAX_POSITION_BARCODES_EAN8_LENGTH) $errors[] = 'position.barcodes.ean8 - maxLength is ' . self::MAX_POSITION_BARCODES_EAN8_LENGTH;
            if ($barcodes['ean13'] && mb_strlen($barcodes['ean13']) > self::MAX_POSITION_BARCODES_EAN13_LENGTH) $errors[] = 'position.barcodes.ean13 - maxLength is ' . self::MAX_POSITION_BARCODES_EAN13_LENGTH;
            if ($barcodes['itf14'] && mb_strlen($barcodes['itf14']) > self::MAX_POSITION_BARCODES_ITF14_LENGTH) $errors[] = 'position.barcodes.itf14 - maxLength is ' . self::MAX_POSITION_BARCODES_ITF14_LENGTH;
            if ($barcodes['gs1'] && mb_strlen($barcodes['itf14']) > self::MAX_POSITION_BARCODES_GS1_LENGTH) $errors[] = 'position.barcodes.gs1 - maxLength is ' . self::MAX_POSITION_BARCODES_GS1_LENGTH;
            if ($barcodes['mi'] && mb_strlen($barcodes['itf14']) > self::MAX_POSITION_BARCODES_MI_LENGTH) $errors[] = 'position.barcodes.mi - maxLength is ' . self::MAX_POSITION_BARCODES_MI_LENGTH;
            if ($barcodes['egais20'] && mb_strlen($barcodes['egais20']) > self::MAX_POSITION_BARCODES_EGAIS20_LENGTH) $errors[] = 'position.barcodes.egais20 - maxLength is ' . self::MAX_POSITION_BARCODES_EGAIS20_LENGTH;
            if ($barcodes['egais30'] && mb_strlen($barcodes['egais30']) > self::MAX_POSITION_BARCODES_EGAIS30_LENGTH) $errors[] = 'position.barcodes.egais30 - maxLength is ' . self::MAX_POSITION_BARCODES_EGAIS30_LENGTH;

            if ($barcodes['f1'] && mb_strlen($barcodes['f1']) > self::MAX_POSITION_BARCODES_F1_LENGTH) $errors[] = 'position.barcodes.f1 - maxLength is ' . self::MAX_POSITION_BARCODES_F1_LENGTH;
            if ($barcodes['f2'] && mb_strlen($barcodes['f2']) > self::MAX_POSITION_BARCODES_F2_LENGTH) $errors[] = 'position.barcodes.f2 - maxLength is ' . self::MAX_POSITION_BARCODES_F2_LENGTH;
            if ($barcodes['f3'] && mb_strlen($barcodes['f3']) > self::MAX_POSITION_BARCODES_F3_LENGTH) $errors[] = 'position.barcodes.f3 - maxLength is ' . self::MAX_POSITION_BARCODES_F3_LENGTH;
            if ($barcodes['f4'] && mb_strlen($barcodes['f4']) > self::MAX_POSITION_BARCODES_F4_LENGTH) $errors[] = 'position.barcodes.f4 - maxLength is ' . self::MAX_POSITION_BARCODES_F4_LENGTH;
            if ($barcodes['f5'] && mb_strlen($barcodes['f5']) > self::MAX_POSITION_BARCODES_F5_LENGTH) $errors[] = 'position.barcodes.f5 - maxLength is ' . self::MAX_POSITION_BARCODES_F5_LENGTH;
            if ($barcodes['f6'] && mb_strlen($barcodes['f6']) > self::MAX_POSITION_BARCODES_F6_LENGTH) $errors[] = 'position.barcodes.f6 - maxLength is ' . self::MAX_POSITION_BARCODES_F6_LENGTH;
        }

        $unitTaxSum = $params['unitTaxSum'];

        if (!is_numeric($quantity)) $errors[] = 'position.quantity - ' . ($quantity ? 'invalid value "' . $quantity . '"' : 'is required');
        if (!is_numeric($price)) $errors[] = 'position.price - ' . ($price ? 'invalid value "' . $price . '"' : 'is required');
        if (!preg_match('/^[123456]{1}$/', $tax)) $errors[] = 'position.tax - ' . ($tax ? 'invalid value "' . $tax . '"' : 'is required');
        if (!$text or mb_strlen($text) > self::MAX_POSITION_TEXT_LENGTH) $errors[] = 'position.text - ' . ($text ? 'maxLength is ' . self::MAX_POSITION_TEXT_LENGTH : 'is required');
        if (!(preg_match('/^[1-7]$/', $paymentMethodType) or is_null($paymentMethodType))) $errors[] = 'position.paymentMethodType - invalid value "' . $paymentMethodType . '"';
        if (!(preg_match('/^[1-9]{1}$|^1[0-9]{1}$/', $paymentSubjectType) or is_null($paymentSubjectType))) $errors[] = 'position.paymentSubjectType - invalid value "' . $paymentSubjectType . '"';

        if ($supplierInfo) {
            if ($supplierInfo['name'] && mb_strlen($supplierInfo['name']) > self::MAX_POSITION_SUPPLIER_NAME_LENGTH) $errors[] = 'position.supplierInfo.name - maxLength is ' . self::MAX_POSITION_SUPPLIER_NAME_LENGTH;
            if ($supplierInfo['phoneNumbers']) {
                for ($i = 0; $i < count($supplierInfo['phoneNumbers']); $i++) {
                    if (!preg_match('/^((8|\+7)[\- ]?)?(\(?\d{3}\)?[\- ]?)?[\d\- ]{7,10}$/', $supplierInfo['phoneNumbers'][$i])) $errors[] = 'position.supplierInfo.phoneNumbers[' . $i . '] - invalid phone';
                }
            }
        }
        if ($supplierINN && strlen($supplierINN) !== 10 && strlen($supplierINN) !== 12) $errors[] = 'position.supplierINN - length need to be 10 or 12';
        if ($agentType && (!is_numeric($agentType) or $agentType < 1 or $agentType > 127)) $errors[] = 'position.agentType - need to be from 1 to 127';

        if ($agentInfo) {
            if ($agentInfo['paymentTransferOperatorPhoneNumbers']) {
                for ($i = 0; $i < count($agentInfo['paymentTransferOperatorPhoneNumbers']); $i++) {
                    if (!preg_match('/^((8|\+7)[\- ]?)?(\(?\d{3}\)?[\- ]?)?[\d\- ]{7,10}$/', $agentInfo['paymentTransferOperatorPhoneNumbers'][$i])) $errors[] = 'position.agentInfo.paymentTransferOperatorPhoneNumbers[' . $i . '] - invalid phone';
                }
            }
            if ($agentInfo['paymentAgentOperation'] && mb_strlen($agentInfo['paymentAgentOperation']) > self::MAX_PAYMENT_AGENT_OPERATION_LENGTH) $errors[] = 'position.agentInfo.paymentAgentOperation - maxLength is ' . self::MAX_PAYMENT_AGENT_OPERATION_LENGTH;
            if ($agentInfo['paymentAgentPhoneNumbers']) {
                for ($i = 0; $i < count($agentInfo['paymentAgentPhoneNumbers']); $i++) {
                    if (!preg_match('/^((8|\+7)[\- ]?)?(\(?\d{3}\)?[\- ]?)?[\d\- ]{7,10}$/', $agentInfo['paymentAgentPhoneNumbers'][$i])) $errors[] = 'position.agentInfo.paymentAgentPhoneNumbers[' . $i . '] - invalid phone';
                }
            }
            if ($agentInfo['paymentOperatorPhoneNumbers']) {
                for ($i = 0; $i < count($agentInfo['paymentOperatorPhoneNumbers']); $i++) {
                    if (!preg_match('/^((8|\+7)[\- ]?)?(\(?\d{3}\)?[\- ]?)?[\d\- ]{7,10}$/', $agentInfo['paymentOperatorPhoneNumbers'][$i])) $errors[] = 'position.agentInfo.paymentOperatorPhoneNumbers[' . $i . '] - invalid phone';
                }
            }
            if ($agentInfo['paymentOperatorName'] && mb_strlen($agentInfo['paymentOperatorName']) > self::MAX_PAYMENT_OPERATOR_NAME_LENGTH) $errors[] = 'position.agentInfo.paymentOperatorName - maxLength is ' . self::MAX_PAYMENT_OPERATOR_NAME_LENGTH;
            if ($agentInfo['paymentOperatorAddress'] && mb_strlen($agentInfo['paymentOperatorAddress']) > self::MAX_PAYMENT_OPERATOR_ADDRESS_LENGTH) $errors[] = 'position.agentInfo.paymentOperatorAddress - maxLength is ' . self::MAX_PAYMENT_OPERATOR_ADDRESS_LENGTH;
            if ($agentInfo['paymentOperatorINN'] && strlen($agentInfo['paymentOperatorINN']) !== 10 && strlen($agentInfo['paymentOperatorINN']) !== 12) $errors[] = 'position.agentInfo.paymentOperatorINN - length need to be 10 or 12';
        }

        if ($additionalAttribute && mb_strlen($additionalAttribute) > self::MAX_POSITION_ADDITIONAL_ATTRIBUTE_LENGTH) $errors[] = 'position.additionalAttribute - maxLength is ' . self::MAX_POSITION_ADDITIONAL_ATTRIBUTE_LENGTH;
        if ($manufacturerCountryCode && strlen($manufacturerCountryCode) > self::MAX_POSITION_MANUFACTURE_COUNTRY_CODE_LENGTH) $errors[] = 'position.manufacturerCountryCode - maxLength is ' . self::MAX_POSITION_MANUFACTURE_COUNTRY_CODE_LENGTH;
        if ($customsDeclarationNumber && mb_strlen($customsDeclarationNumber) > self::MAX_POSITION_CUSTOMS_DECLARATION_NUMBER) $errors[] = 'position.additionalAttribute - maxLength is ' . self::MAX_POSITION_CUSTOMS_DECLARATION_NUMBER;
        if (!is_numeric($excise)) $errors[] = 'position.excise - ' . ($excise ? 'invalid value "' . $excise . '"' : 'is required');

        if (isset($taxSum) && !is_numeric($taxSum)) $errors[] = 'position.taxSum - invalid value ' . $taxSum;
        if (isset($unitTaxSum) && !is_numeric($unitTaxSum)) $errors[] = 'position.taxSum - invalid value ' . $unitTaxSum;

        if (count($errors) > 0) throw new Exception(implode(', ', $errors) . PHP_EOL);

        $position = new \stdClass();
        $position->quantity = (float)$quantity;
        $position->price = (float)$price;
        $position->tax = $tax;
        $position->text = $text;
        $position->paymentMethodType = $paymentMethodType ?: 4;
        $position->paymentSubjectType = $paymentSubjectType ?: 1;

        if (isset($itemCode)) $position->itemCode = $itemCode;
        if ($supplierInfo) $position->supplierInfo = $supplierInfo;
        if ($supplierINN) $position->supplierINN = $supplierINN;
        if ($agentType) $position->agentType = $agentType;
        if ($agentInfo) $position->agentInfo = $agentInfo;
        if (isset($quantityMeasurementUnit)) $position->quantityMeasurementUnit = $quantityMeasurementUnit;
        if ($additionalAttribute) $position->additionalAttribute = $additionalAttribute;
        if ($manufacturerCountryCode) $position->manufacturerCountryCode = $manufacturerCountryCode;
        if ($customsDeclarationNumber) $position->customsDeclarationNumber = $customsDeclarationNumber;
        if ($excise) $position->excise = $excise;
        if ($taxSum) $position->taxSum = $taxSum;
        if (isset($fractionalQuantity)) $position->fractionalQuantity = $fractionalQuantity;
        if (isset($industryAttribute)) $position->industryAttribute = $industryAttribute;
        if (isset($barcodes)) $position->barcodes = $barcodes;
        if (isset($plannedStatus)) $position->plannedStatus = $plannedStatus;

        $this->correction_request->content->positions[] = $position;

        return $this;
    }

    /**
     *  Добавление оплаты в коррекцию ФФД1.2
     *  @param array $params
     *  @return orangedata_client $this
     *  @throws Exception
     */
    public function add_payment_to_correction(array $params = []) {
        $type = $params['type'];
        $amount = $params['amount'];
        $errors = [];

        if (!isset( $this->correction_request->content->ffdVersion))
            $errors[] = 'add_position_to_correction supports only FFD1.2';

        if (!preg_match('/^[1-9]{1}$|^1[0-6]{1}$/', $type)) $errors[] = 'payments.type - ' . ($type ? 'invalid value "' . $type . '"' : 'is required');
        if (!is_numeric($amount)) $errors[] = 'payments.amount - ' . ($amount ? 'invalid value "' . $amount . '"' : 'is required');

        if (count($errors) > 0) throw new Exception(implode(', ', $errors) . PHP_EOL);

        $payment = new \stdClass();
        $payment->type = (int) $type;
        $payment->amount = (float) $amount;
        $this->correction_request->content->checkClose->payments[] = $payment;

        return $this;
    }

    /**
     *  Добавление дополнительных аттрибутов в коррекцию ФФД1.2
     *  @param array $params
     *  @return orangedata_client $this
     *  @throws Exception
     */
    public function add_additional_attributes_to_correction(array $params = []) {
        $errors = [];
        if (!isset( $this->correction_request->content->ffdVersion))
            $errors[] = 'add_position_to_correction supports only FFD1.2';

        $additionalAttribute = $params['additionalAttribute'];

        $customerInfo = $params['customerInfo'];
        if ($customerInfo)
        {
            if ($customerInfo['name'] && mb_strlen($customerInfo['name']) > self::MAX_CUSTOMER_INFO_NAME_LENGTH) $errors[] = 'customerInfo.name - maxLength is ' . self::MAX_CUSTOMER_INFO_NAME_LENGTH;
            if ($customerInfo['inn'] && strlen($customerInfo['inn']) !== 10 && strlen($customerInfo['inn']) !== 12) $errors[] = 'customerInfo.inn - length need to be 10 or 12';
            if ($customerInfo['birthDate'] && !preg_match('/^(0?[1-9]|[12][0-9]|3[01]).(0?[1-9]|1[012]).((19|20)\d\d)$/', $customerInfo['birthDate'])) $errors[] = 'customerInfo.birthDate - invalid value ' . $customerInfo['birthDate'];
            if ($customerInfo['citizenship'] && mb_strlen($customerInfo['citizenship']) > self::MAX_CUSTOMER_INFO_CITIZENSHIP_LENGTH) $errors[] = 'customerInfo.citizenship - maxLength is ' . self::MAX_CUSTOMER_INFO_CITIZENSHIP_LENGTH;
            if ($customerInfo['identityDocumentCode'] && mb_strlen($customerInfo['identityDocumentCode']) > 2) $errors[] = 'customerInfo.identityDocumentCode - maxLength is 2';
            if ($customerInfo['identityDocumentData'] && mb_strlen($customerInfo['identityDocumentData']) > self::MAX_CUSTOMER_INFO_IDENTITY_DOCUMENT_DATA_LENGTH) $errors[] = 'customerInfo.identityDocumentDate - maxLength is ' . self::MAX_CUSTOMER_INFO_IDENTITY_DOCUMENT_DATA_LENGTH;
            if ($customerInfo['address'] && mb_strlen($customerInfo['address']) > self::MAX_CUSTOMER_INFO_ADDRESS_LENGTH) $errors[] = 'customerInfo.address - maxLength is ' . self::MAX_CUSTOMER_INFO_ADDRESS_LENGTH;
        }

        $operationalAttribute = $params['operationalAttribute'];
        if ($operationalAttribute)
        {
            if ($operationalAttribute['date'])
                $operationalAttribute['date'] = $operationalAttribute['date']->setTime(0, 0)->format(DateTime::ISO8601);
            if ($operationalAttribute['id'] && mb_strlen($operationalAttribute['id']) > self::MAX_OPERATIONAL_ATTRIBUTE_ID_LENGTH) $errors[] = 'operationalAttribute.id - maxLength is ' . self::MAX_OPERATIONAL_ATTRIBUTE_ID_LENGTH;
            if ($operationalAttribute['value'] && mb_strlen($operationalAttribute['value']) > self::MAX_OPERATIONAL_ATTRIBUTE_VALUE_LENGTH) $errors[] = 'operationalAttribute.value - maxLength is ' . self::MAX_OPERATIONAL_ATTRIBUTE_VALUE_LENGTH;
        }


        $senderEmail = $params['senderEmail'];
        $totalSum = $params['totalSum'];
        $vat1Sum = $params['vat1Sum'];
        $vat2Sum = $params['vat2Sum'];
        $vat3Sum = $params['vat3Sum'];
        $vat4Sum = $params['vat4Sum'];
        $vat5Sum = $params['vat5Sum'];
        $vat6Sum = $params['vat6Sum'];

        if (mb_strlen($additionalAttribute) > self::MAX_ADDITIONAL_ATTRIBUTE_LENGTH) $errors[] = 'additionalAttribute - maxLength is ' . self::MAX_ADDITIONAL_ATTRIBUTE_LENGTH;
        if ($senderEmail && mb_strlen($senderEmail) > self::MAX_SENDER_EMAIL_LENGTH) $errors[] = 'senderEmail - maxLength is ' . self::MAX_SENDER_EMAIL_LENGTH;
        if ($totalSum && !is_numeric($totalSum)) $errors[] = 'totalSum - invalid value';
        if ($vat1Sum && !is_numeric($vat1Sum)) $errors[] = '$vat1Sum - invalid value';
        if ($vat2Sum && !is_numeric($vat2Sum)) $errors[] = '$vat2Sum - invalid value';
        if ($vat3Sum && !is_numeric($vat3Sum)) $errors[] = '$vat3Sum - invalid value';
        if ($vat4Sum && !is_numeric($vat4Sum)) $errors[] = '$vat4Sum - invalid value';
        if ($vat5Sum && !is_numeric($vat5Sum)) $errors[] = '$vat5Sum - invalid value';
        if ($vat6Sum && !is_numeric($vat6Sum)) $errors[] = '$vat6Sum - invalid value';

        if (count($errors) > 0) throw new Exception(implode(', ', $errors) . PHP_EOL);

        if ($additionalAttribute) $this->correction_request->content->additionalAttribute = $additionalAttribute;
        if ($customerInfo) $this->correction_request->content->customerInfo = $customerInfo;
        if ($operationalAttribute) $this->correction_request->content->operationalAttribute = $operationalAttribute;
        if ($senderEmail) $this->correction_request->content->senderEmail = $senderEmail;
        if ($totalSum) $this->correction_request->content->totalSum = $totalSum;
        if ($vat1Sum) $this->correction_request->content->vat1Sum = $vat1Sum;
        if ($vat2Sum) $this->correction_request->content->vat2Sum = $vat2Sum;
        if ($vat3Sum) $this->correction_request->content->vat3Sum = $vat3Sum;
        if ($vat4Sum) $this->correction_request->content->vat4Sum = $vat4Sum;
        if ($vat5Sum) $this->correction_request->content->vat5Sum = $vat5Sum;
        if ($vat6Sum) $this->correction_request->content->vat6Sum = $vat6Sum;

        return $this;
    }

    /**
     * post_correction12() - Отправка чека-коррекции на обработку ФФД1.2
     *  @return bool
     *  @throws Exception
     */
    public function post_correction12() {
        $jsonstring = json_encode($this->correction_request, JSON_PRESERVE_ZERO_FRACTION);
        if(!$jsonstring) {
            throw  new Exception('JSON encode error:' . json_last_error_msg());
        }
        $sign = $this->sign_order_request($jsonstring);
        $curl = is_numeric($this->api_url) ? $this->prepare_curl($this->edit_url($this->api_url, false)) : $this->prepare_curl($this->api_url . '/api/v2/correction12/');
        $headers = [
            "Content-Length: " . strlen($jsonstring),
            "Content-Type: application/json; charset=utf-8",
            "X-Signature: " . $sign
        ];
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $jsonstring
        ]);
        $answer = curl_exec($curl);
        $info = curl_getinfo($curl);
        switch ($info['http_code']) {
            case '201':
                $return = true;
                break;
            case '503':
            case '400':
                $return = $answer;
                break;
            case '401':
                throw new Exception('Unauthorized. Client certificate check is failed');
            case '404':
                throw new Exception('Endpoint not found');
            case '409':
                throw new Exception('Conflict. Bill with same id is already exists in the system.');
            default:
                $return = false;
                break;
        }
        if (false === $return) {
            throw new Exception('Curl error: ' . curl_error($curl));
        }
        return $return;
    }

    /**
     * get_correction_status12(a) - Проверка состояния чека-коррекции
     *  @param $id (a) - Идентификатор документа (Строка от 1 до 32 символов)
     *  @return bool
     *  @throws Exception
     */
    public function get_correction_status12($id) {
        if (strlen($id) > 32 OR strlen($id) == 0) {
            throw new Exception('Invalid order identifier');
        }
        $curl = is_numeric($this->api_url) ? $this->prepare_curl($this->edit_url($this->api_url,false) . $this->inn . '/status/' . $id) : $this->prepare_curl($this->api_url . '/api/v2/correction12/' . $this->inn . '/status/' . $id);
        curl_setopt($curl, CURLOPT_POST, false);
        $answer = curl_exec($curl);
        $info = curl_getinfo($curl);
        switch ($info['http_code']) {
            case '200':
                $return = $answer;
                break;
            case '202':
                $return = true;
                break;
            case '400':
                throw new Exception('Not Found. Order was not found in the system. Company not found.');
            case '401':
                throw new Exception('Unauthorized. Client certificate check is failed');
            case '404':
                throw new Exception('Endpoint not found');
            default:
                $return = false;
                break;
        }
        if (false === $return) {
            throw new Exception('Curl error: ' . curl_error($curl));
        }
        return $return;
    }


}
