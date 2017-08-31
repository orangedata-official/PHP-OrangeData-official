<?php

/**
 *Пример для библиотеки OrangeDataClient PHP Beta version 2.0.0
 *Библиотека корректно работает с версией PHP: 7+
 */

/**
 *create_order(a, b, c, d, e*) - Создание нового чека
 *  Параметры:
 *    a ($id) - Идентификатор документа (Строка от 1 до 32 символов)
 *    b ($type) - Признак расчета (Число от 1 до 4):
 *          1 - Приход
 *          2 - Возврат прихода
 *          3 - Расход
 *          4 - Возврат расхода
 *    с ($customerContact) - Телефон или электронный адрес покупателя (Строка от 1 до 64 символов)
 *    d ($taxationSystem) - Система налогообложения (Число от 0 до 5):
 *          0 – Общая, ОСН
 *          1 – Упрощенная доход, УСН доход
 *          2 – Упрощенная доход минус расход, УСН доход - расход
 *          3 – Единый налог на вмененный доход, ЕНВД
 *          4 – Единый сельскохозяйственный налог, ЕСН
 *          5 – Патентная система налогообложения, Патент
 *    e* ($group) - Группа устройств, с помощью которых будет пробит чек (не всегда является обязательным полем)
 *  
 *    Пример запроса:
 *        create_order('2234', 1, 'ex@example.ex', 5);
 */
 
/**
 *add_position_to_order(a, b, c, d, e*, f*) - Добавление позиций в чек
 *  Параметры:
 *    a ($quantity) - Количество предмета расчета (Десятичное число с точностью до 6 символов после точки*)
 *    b ($price) - Цена за единицу предмета расчета с учетом скидок и наценок (Десятичное число с точностью до 2 символов после точки*)
 *    c ($tax) - Система налогообложения (Число от 1 до 6):
 *          1 – ставка НДС 18%
 *          2 – ставка НДС 10%
 *          3 – ставка НДС расч. 18/118
 *          4 – ставка НДС расч. 10/110
 *          5 – ставка НДС 0%
 *          6 – НДС не облагается
 *    d ($text) - Наименование предмета расчета (Строка до 128 символов)
 *    e* ($paymentMethodType) - Признак способа расчета (Число от 1 до 7 или null. Если передано null, то будет отправлено значение 4):
 *          1 – Предоплата 100%
 *          2 – Частичная предоплата
 *          3 – Аванс
 *          4 – Полный расчет
 *          5 – Частичный расчет и кредит
 *          6 – Передача в кредит
 *          7 – оплата кредита
 *    f* ($paymentSubjectType) - Признак предмета расчета (Число от 1 до 13 или null. Если передано null, то будет отправлено значение 1):
 *          1 – Товар
 *          2 – Подакцизный товар
 *          3 – Работа
 *          4 – Услуга
 *          5 – Ставка азартной игры
 *          6 – Выигрыш азартной игры
 *          7 – Лотерейный билет
 *          8 – Выигрыш лотереи
 *          9 – Предоставление РИД
 *          10 – Платеж
 *          11 – Агентское вознаграждение
 *          12 – Составной предмет расчета
 *          13 – Иной предмет расчета
 *  
 *    Примеры запроса:
 *        Полный запрос (рекомендуется):
 *          add_position_to_order(6, 200, 'ex@example.ex', 2, 2, 10);
 *
 *        Запрос с пропуском поля 'e*':
 *          add_postion_to order(6, 200, 'ex@example.ex', 2, null, 10); (Поле e* = 4)
 *
 *        Запрос с пропуском полей 'e*' и 'f*':
 *          add_position_to_order(6, 200, 'ex@example.ex', 2); (Поле e* = 4, поле f* = 1)
 */
 
/**
 *add_payment_to_order(a, b) - Добавление позиций в чек
 *  Параметры:
 *    a ($type) - Тип оплаты (Число от 1 до 16):
 *          1 – сумма по чеку наличными, 1031
 *          2 – сумма по чеку электронными, 1081
 *          14 – сумма по чеку предоплатой (зачетом аванса и (или) предыдущих платежей), 1215
 *          15 – сумма по чеку постоплатой (в кредит), 1216
 *          16 – сумма по чеку (БСО) встречным предоставлением, 1217
 *    b ($amount) - Сумма оплаты (Десятичное число с точностью до 2 символов после точки*)
 *    !ВАЖНО переменная b ($amount) указывается в копейках
 *
 *    Примеры запроса:
 *        add_payment_to_order(1, 270); (Поле b ($amount) будет равняться 270/100=2,70)
 */
 
/**
 *send_order() - Отправка чека на обработку
 */
 
/**
 *get_order_status(a) - Проверка состояния чека
 *  Параметры:
 *    a ($id) - Идентификатор документа (Строка от 1 до 32 символов)
 *
 *    Пример запроса:
 *        get_order_status(435621);
 */
 
/**
 *is_debug() - Данная функция служит для активации записей в файле 'curl.log'
 */ 

include_once 'orangedata_client_Beta.php'; //Путь к библиотеке (как правило это файл orangedata_client.php или orangedataclient_Beta.php)

$api_url='https://apip.orangedata.ru:2443/api/v2/documents/';
$sign_pkey = getcwd().'\secure_path\private_key.pem'; //path to private key for signing
$ssl_client_key = getcwd().'\secure_path\client.key'; //path to client private key for ssl
$ssl_client_crt = getcwd().'\secure_path\client.crt'; //path to client certificate for ssl
$ssl_ca_cert = getcwd().'\secure_path\cacert.pem'; //path to cacert for ssl
$ssl_client_crt_pass = 1234; //password for client certificate for ssl
$inn = '0123456789'; //ИНН

//create new client
$byer = new orangedata\orangedata_client($inn, 
        $api_url,
        $sign_pkey,
        $ssl_client_key,
        $ssl_client_crt,
		$ssl_ca_cert,
        $ssl_client_crt_pass);

//for write curl.log file
//$byer->is_debug();

// create client new order, add positions , add payment, send request
$result = $byer
        ->create_order('3268483278', 1, 'example@example.com', 1)
        ->add_position_to_order(6, 10, 1, 'matches', 1, 10)
		->add_payment_to_order(1, 10)
        ->add_payment_to_order(2, 50)
        ->send_order();

//view response
var_dump($result);

//view status of order 
var_dump($byer->get_order_status(3268483278));

?>
