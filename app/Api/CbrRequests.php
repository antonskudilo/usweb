<?php


namespace App\Api;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PHPRedis;

class CbrRequests
{
    const THROTTLE_EXPECTATION = 1000; // 1 секунда
    public $controller;
    private $requestParams;
    private $requestType = 'GET';
    private $baseUri;
    private $syncLog;

    public function __construct()
    {
        $this->syncLog = new Logger('api/cbr');

        try {
            $this->syncLog->pushHandler(new StreamHandler(storage_path('logs/api/cbr.log')));
        } catch (\Exception $e) {
            Log::getMonolog()->withName(__CLASS__ . ':' . __FUNCTION__)->error($e->getMessage());
        }

        $this->setDefault();
    }

    /**
     * Зададим дефолтные параметры настроек
     *
     * @param array $params
     */
    private function setDefault(array $params = [])
    {
        $this->baseUri = 'http://www.cbr.ru/scripts/';
        $this->requestParams = null;
        $this->controller = null;

        if (isset($params)
            && !empty($params)
            && is_array($params)
        ) {
            foreach ($params as $key => $value) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Вернуть имя контроллера в запросе
     *
     * @param string|null $val
     * @return string|null
     */
    private function controller(string $val = null)
    {
        if (isset($val) && !empty($val) && is_string($val)) {
            $this->controller = $val;
        }

        return $this->controller ?? null;
    }

    /**
     * Вернуть объект Curl клиента
     *
     * @return null
     */
    private function clientObj()
    {
        return new Client([
            'base_uri' => $this->baseUri . $this->controller(),
            'timeout'  => 2.0,
        ]);
    }

    /**
     * Отправить запрос и сформировать ответ
     *
     * @return mixed|null
     */
    public function send()
    {
        if (!$this->controller()) {
            return null;
        }

        $this->setThrottle();

        $response = $this->sendRequest();

        if (!isset($response)) {
            return null;
        }

        $this->setThrottle();

        return $response;
    }

    /**
     * Сформируем имя ключа, используемого для ограничения частоты запросов
     *
     * @return string|null
     */
    private function getThrottleRedisKey()
    {
        $userIp = Request::ip();

        if (empty($userIp)
            || !$this->controller()
        ) {
            return null;
        }

        return implode(':', [
            'tk',
            'throttle',
            $this->controller(),
            Auth::user()->id ?? 'sys',
            $userIp,
        ]);
    }

    /**
     * Проверим вышло ли время ожидания между запросами
     *
     * @return integer|null
     */
    public function waitThrottle()
    {
        $throttleKey = $this->getThrottleRedisKey();

        if (!isset($throttleKey) || empty($throttleKey)) {
            return null;
        }

        try {
            if (!PHPRedis::ping()) {
                throw new \Exception();
            }

            if (PHPRedis::exists($throttleKey)) {
                return PHPRedis::pttl($throttleKey);
            }
        } catch (\Exception $e) {
            Log::getMonolog()->withName(__CLASS__ . ':' . __FUNCTION__)->error($e->getMessage());

            return null;
        }
    }

    /**
     * Получим время ожидания до следующего запроса
     *
     * @return int
     */
    private function getThrottleExpectation() : int
    {
        return self::THROTTLE_EXPECTATION;
    }

    /**
     * Установим ключ ожидания выполнения запроса
     *
     * @return bool
     */
    private function setThrottle()
    {
        $throttleKey = $this->getThrottleRedisKey();

        if (!isset($throttleKey) || empty($throttleKey)) {
            return false;
        }

        try {
            if (!PHPRedis::ping()) {
                throw new \Exception();
            }

            PHPRedis::set($throttleKey, 1);
            PHPRedis::pexpire($throttleKey, $this->getThrottleExpectation());
        } catch (\Exception $e) {
            Log::getMonolog()->withName(__CLASS__ . ':' . __FUNCTION__)->error($e->getMessage());

            return false;
        }

        return true;
    }

    /**
     * Сформировать массив опций для запроса
     *
     * @return mixed
     */
    private function getRequestOptions()
    {
        return [
            'query' => $this->requestParams,
        ];
    }

    /**
     * Подготавливает запрос и делает его
     *
     * @return mixed|null
     */
    private function sendRequest()
    {
        $this->syncLog->withName("Cbr request")->info(http_build_query($this->getRequestOptions()));

        try {
            $response = $this->clientObj()->request($this->requestType, '', $this->getRequestOptions());
        } catch (\Exception $e) {
            $this->syncLog->withName($this->requestType)->error($e->getMessage());

            return null;
        } catch (\Throwable $e) {
            $this->syncLog->withName($this->requestType)->error($e->getMessage());

            return null;
        }

        $result = json_decode($response->getBody(), true);
        $this->syncLog->withName("Cbr response")->info(json_encode($result));

        return $result;
    }

    /**
     * Получим курс валют на указанную дату
     *
     * @param array $data
     * @return $this
     */
    public function getCurrencyValues(array $data = [])
    {
        $params = [];

        if (!empty($data)) {
            $params['date_req'] = $data['date_req'];
        }

        $this->setDefault([
            'controller' => 'XML_daily.asp',
        ]);

        $this->requestParams = $params;

        return $this;
    }
}
