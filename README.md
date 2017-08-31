# PHPOrangeData
PHP integration for OrangeData service
Для начала работы необходимо подключить файл класса, например так:

include_once 'orangedata_client.php'; //Путь к библиотеке (как правило это файл orangedata_client.php или orangedataclient_Beta.php)

Следует указать исходные данные:

Адрес API или прокси, на который будем отправлять запросы
$api_url='https://apip.orangedata.ru:2443/api/v2/documents/';

Путь к приватному ключу, который используется для подписи "чека"
$sign_pkey = getcwd().'\secure_path\private_key.pem'; //private key for signing

Путь к приватному ключу используемому для 2ssl взаимодействия
$ssl_client_key = getcwd().'\secure_path\client.key'; //path to client private key for ssl

Путь к клиентскому сертификату используемому для 2ssl
$ssl_client_crt = getcwd().'\secure_path\client.crt'; //path to client certificate for ssl

Путь к cacert.pem
$ssl_ca_cert = getcwd().'\secure_path\cacert.pem'; //path to cacert for ssl

Пароль к клиентскому сертификату
$ssl_client_crt_pass = 'password'; //password for client certificate for ssl

Инн!
$inn = '0123456789';//ИНН

На основании исходных данных,
Создаем "клиента"

$byer = new orangedata\orangedata_client($inn, 
        $api_url,
        $sign_pkey,
        $ssl_client_key,
        $ssl_client_crt,
        $ssl_ca_cert,
        $ssl_client_crt_pass);
        
Если хотим включить запись логов в файле 'curl.log', прописываем:
$byer->is_debug();

Методы созданного "клиента" возвращают его самого
Например
$byer->create_order(2, 1, 'a@b',1) создаст новый заказ внутри объекта (и вернет сам объект)

Добавить спички к заказу
$byer->add_position_to_order(6, 10, 1, 'matches', 1, 10)

Добавить оплату заказа
$byer->add_payment_to_order(1, 10)
->add_payment_to_order(2, 50)   ///а можно и несколько разных оплат

и когда все готово - отправить заказ
$order_result = $byer->send_order();
Метод возвращает (bool) true в случае успешного завершения,
json с ошибками валидации, если таковые вернул сервер
либо бросит Exception, в прочих случаях

Проверить его статус
$order_status = $byer->get_order_status(2)
Метод возвращает (bool) true в случае наличия "чека" в незавершенном статусе,
json с деталями заказа, в случае успешного завершения,
бросит Exception в прочих случаях 

Методы send_order() и get_order_status($id) возвращают ответ сервера,
в отличии от прочих методов, возвращающих сам родительский объект


