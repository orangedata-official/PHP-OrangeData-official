<?php

/**
 * OrangeDataClient PHP Beta
 * Version of PHP: 7+
 * @version 2.0.0
 */

namespace orangedata;


class orangedata_client {

    private $order_request;
    private $url;
    private $inn;
    private $debug_file;
    private $debug = false;
    private $ca_cert = false;
    
    private $private_key_pem;
    private $client_pkey;
    private $client_cert;
    private $client_cert_pass;

    /**
     * @param mixed $inn
     * @param mixed $url
     * @param string $sign_pkey - Path to signing private key or his PEM body
     * @param string $client_key - Path to client private key
     * @param string $client_cert - Path to Client 2SSL Certificate
	 * @param string $ca_cert - Path to cacert
     * @param string $client_cert_pass - Password for Client 2SSL Certificate
     */
    public function __construct($inn, $url, $sign_pkey, $client_key, $client_cert, $ca_cert, $client_cert_pass) {
        $this->inn = (string) $inn;
        $this->url = (string) $url;
        $this->private_key_pem = (string) $sign_pkey;
        $this->client_pkey = (string) $client_key;
        $this->client_cert = (string) $client_cert;
		$this->ca_cert = (string) $ca_cert;
        $this->client_cert_pass = (string) $client_cert_pass;
        $this->debug_file = getcwd() . '/curl.log';
    }

    /**
     * create_order(a, b, c, d, e*) - Создание чека
	 *  @param $id (a) - Идентификатор документа (Строка от 1 до 32 символов)
	 *  @param $type (b) - Признак расчета (Число от 1 до 4):
	 *      1 - Приход
     *      2 - Возврат прихода
     *      3 - Расход
     *      4 - Возврат расхода
	 *  @param $customerContact (c) - Телефон или электронный адрес покупателя (Строка от 1 до 64 символов)
	 *  @param $taxationSystem (d) - Система налогообложения (Число от 0 до 5):
     *      0 – Общая, ОСН
     *      1 – Упрощенная доход, УСН доход
     *      2 – Упрощенная доход минус расход, УСН доход - расход
     *      3 – Единый налог на вмененный доход, ЕНВД
     *      4 – Единый сельскохозяйственный налог, ЕСН
     *      5 – Патентная система налогообложения, Патент
	 *  @param $group (e*) - Группа устройств, с помощью которых будет пробит чек (не всегда является обязательным полем)
	 *  @return $this
     *  @throws Exception
     */
    public function create_order($id, $type, $customerContact, $taxationSystem, $group = null) {
        $this->order_request = new \stdClass();
        $this->order_request->id = $id;
        $this->order_request->inn = $this->inn;
		(is_null($group)) ?
		    $this->order_request->group = 'Main' : $this->order_request->group = $group;
			
        $this->order_request->content = new \stdClass();
		if (is_int($type)) {
            $this->order_request->content->type = $type;
        } else {
            throw new \Exception('Incorrect order Type');
        }
        $this->order_request->content->positions = array();
        $this->order_request->content->checkClose = new \stdClass();
        $this->order_request->content->checkClose->payments = array();
        if (preg_match('/^[012345]$/', $taxationSystem)) {
            $this->order_request->content->checkClose->taxationSystem = $taxationSystem;
        } else {
            throw new \Exception('Incorrect taxationSystem');
        }
        if (filter_var($customerContact, FILTER_VALIDATE_EMAIL) or preg_match('/^((8|\+7)[\- ]?)?(\(?\d{3}\)?[\- ]?)?[\d\- ]{7,10}$/', $customerContact)) {
            $this->order_request->content->customerContact = $customerContact;
        } else {
            throw new \Exception('Incorrect customer Contact');
        }
        return $this;
    }

    /**
     * add_position_to_order(a, b, c, d, e*, f*) - Добавление позиций
	 *  @param float $quantity (a) - Количество предмета расчета
	 *  @param int $price (b) - Цена за единицу предмета расчета с учетом скидок и наценок
	 *  @param int $tax (c) - Система налогообложения (Число от 1 до 6):
     *      1 – ставка НДС 18%
     *      2 – ставка НДС 10%
     *      3 – ставка НДС расч. 18/118
     *      4 – ставка НДС расч. 10/110
     *      5 – ставка НДС 0%
     *      6 – НДС не облагается
	 *  @param string $text - Наименование предмета расчета (Строка до 128 символов)
	 *  @param $paymentMethodType (e*) - Признак способа расчета (Число от 1 до 7 или null. Если передано null, то будет отправлено значение 4):
     *      1 – Предоплата 100%
     *      2 – Частичная предоплата
     *      3 – Аванс
     *      4 – Полный расчет
     *      5 – Частичный расчет и кредит
     *      6 – Передача в кредит
     *      7 – оплата кредита
	 *  @param $paymentSubjectType (f*) - Признак предмета расчета (Число от 1 до 13 или null. Если передано null, то будет отправлено значение 1):
     *      1 – Товар
     *      2 – Подакцизный товар
     *      3 – Работа
     *      4 – Услуга
     *      5 – Ставка азартной игры
     *      6 – Выигрыш азартной игры
     *      7 – Лотерейный билет
     *      8 – Выигрыш лотереи
     *      9 – Предоставление РИД
     *      10 – Платеж
     *      11 – Агентское вознаграждение
     *      12 – Составной предмет расчета
     *      13 – Иной предмет расчета
	 *  @return $this
     *  @throws Exception
   	 */
    public function add_position_to_order(float $quantity, int $price, int $tax, string $text, $paymentMethodType = null, $paymentSubjectType = null) {
        if (is_float($quantity) and is_int($price) and preg_match('/^[123456]{1}$/', $tax) and strlen($text) < 129) {
            $position = new \stdClass();
            $position->quantity = $quantity;
            $position->price = $price / 100;
            $position->tax = $tax;
            $position->text = $text;
            $this->order_request->content->positions[] = $position;
        } else {
            throw new \Exception('Invalid Position Quantity, Price, Tax or Text');
        }
		if ((preg_match('/^[1234567]$/', $paymentMethodType) or is_null($paymentMethodType)) and ((preg_match('/^[123456789]{1}$|^1[0123]{1}$/', $paymentSubjectType)) or is_null($paymentSubjectType))) {
			(is_null($paymentMethodType)) ?
			    $position->paymentMethodType = 4 : $position->paymentMethodType = $paymentMethodType;
			
			(is_null($paymentSubjectType)) ?
			    $position->paymentSubjectType = 1 : $position->paymentSubjectType = $paymentSubjectType;
		} else {
			throw new \Exception('Invalid Position paymentMethodType or paymentSubjectType');
		}
        return $this;
    }
	
    /**
     * add_payment_to_order(a, b) - Добавление оплаты
     *  @param int $type (a) - Тип оплаты (Число от 1 до 16):
     *      1 – сумма по чеку наличными, 1031
     *      2 – сумма по чеку электронными, 1081
     *      14 – сумма по чеку предоплатой (зачетом аванса и (или) предыдущих платежей), 1215
     *      15 – сумма по чеку постоплатой (в кредит), 1216
     *      16 – сумма по чеку (БСО) встречным предоставлением, 1217
     *  @param int $amount (b) - Сумма оплаты (Десятичное число с точностью до 2 символов после точки*)
     *  @return $this
     *  @throws Exception
     */
    public function add_payment_to_order(int $type, int $amount) {
        if (preg_match('/^[123456789]{1}$|^1[0123456]{1}$/', $type) and is_int($amount)) {
            $payment = new \stdClass();
            $payment->type = $type;
            $payment->amount = $amount / 100;
            $this->order_request->content->checkClose->payments[] = $payment;
        } else {
            throw new \Exception('Invalid Payment Type or Amount');
        }
        return $this;
    }

    /**
     * send_order() - Отправка чека
     *  @return mixed
     *  @throws \Exception
     */
    public function send_order() {
        $jsonstring = json_encode($this->order_request, JSON_PRESERVE_ZERO_FRACTION);
        $sign = $this->sign_order_request($jsonstring);
        $curl = $this->prepare_curl($this->url);
        $headers = array(
            "Content-Length: " . strlen($jsonstring),
            "Content-Type: application/json; charset=utf-8",
			"X-Signature: " . $sign
        );
        curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonstring);
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
                throw new \Exception('Unauthorized. Client certificate check is failed');
                break;
            case '409':
                throw new \Exception('Conflict. Order with same id is already exists in the system.');
                break;
			case '503':
			    $return = $answer;
				break;
            default:
                $return = false;
                break;
        }
        if (FALSE === $return) {
            ob_start();
            var_dump($info);
            $last_curl = ob_get_flush();
            throw new \Exception('Curl error ' . PHP_EOL . $last_curl);
        }
        return $return;
    }

    /**
     * get_order_status(a) - Проверка состояния чека
     *  @param $id (a) - Идентификатор документа (Строка от 1 до 32 символов)
     *  @return mixed
     *  @throws \Exception
     */
    public function get_order_status($id) {
        $curl = $this->prepare_curl($this->url . $this->inn . '/status/' . $id);
        curl_setopt($curl, CURLOPT_POST, false);
        $answer = curl_exec($curl);
        $info = curl_getinfo($curl);
        switch ($info['http_code']) {
            case '200':
                $return =  $answer;
                break;
            case '202':
                $return = TRUE;
                break;
            case '400':
                throw new \Exception('Not Found. Order was not found in the system.');
                break;
            case '401':
                throw new \Exception('Unauthorized. Client certificate check is failed');
                break;
            default:
                $return = false;
                break;
        }
        if (FALSE === $return) {
            ob_start();
            var_dump($info);
            $last_curl = ob_get_flush();
            throw new \Exception('Curl error ' . PHP_EOL . $last_curl);
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

    private function prepare_curl($url) {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_SSLKEY, $this->client_pkey);
        curl_setopt($curl, CURLOPT_SSLCERT, $this->client_cert);
        curl_setopt($curl, CURLOPT_SSLCERTPASSWD, $this->client_cert_pass);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($curl, CURLOPT_TIMEOUT, 15);
		curl_setopt($curl, CURLOPT_CAINFO, $this->ca_cert);
        if ($this->debug) {
            curl_setopt($curl, CURLOPT_VERBOSE, 1);
            curl_setopt($curl, CURLOPT_STDERR, fopen($this->debug_file, 'a'));
        }
        return $curl;
    }

    public function is_debug(bool $is_debug = true) {
        $this->debug = (bool) $is_debug;
        return $this;
    }
}
