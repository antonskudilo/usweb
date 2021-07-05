<?php

namespace App\Api;

use Monolog\Logger;
use Illuminate\Support\Facades\Log;
use Monolog\Handler\StreamHandler;
use Illuminate\Support\Facades\Redis as PHPRedis;

class CbrRequests
{
    const THROTTLE_EXPECTATION = 1000; // 1 секунда

    private $controller;
    private $requestParams;
    private $baseUri;
    private $syncLog;

    public function __construct()
    {
        $this->syncLog = new Logger('api/cbr');

        try {
            $this->syncLog->pushHandler(new StreamHandler(storage_path('logs/api/cbr.log')));
        } catch (\Exception $e) {
            Log::error(__CLASS__ . ':' . __FUNCTION__ . ':' . $e->getMessage());
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
     * Проверим, вышло ли время между запросами. Отправим запрос
     *
     * @return mixed|null
     */
    public function send()
    {
        if (!$this->controller()) {
            return null;
        }

        $waitThrottle = $this->waitThrottle();

        if ($waitThrottle) {
            usleep($waitThrottle * 1000);
        }

        $this->setThrottle();
        $response = $this->getXml();

        if (!isset($response)
            || empty($response)
        ) {
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
        $controller = $this->controller();

        if (empty($controller)) {
            return null;
        }

        return implode(':', [
            'cbr',
            'throttle',
            $controller,
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
            Log::error(__CLASS__ . ':' . __FUNCTION__ . ':' . $e->getMessage());

            return null;
        }
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
            PHPRedis::pexpire($throttleKey, self::THROTTLE_EXPECTATION);
        } catch (\Exception $e) {
            Log::error(__CLASS__ . ':' . __FUNCTION__ . ':' . $e->getMessage());

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
        if (!empty($this->requestParams)
            && is_array($this->requestParams)
        ) {
            return urldecode(http_build_query($this->requestParams));
        } else {
            return [];
        }
    }

    /**
     * Сформировать адрес для запроса
     *
     * @return string
     */
    private function getFullPath()
    {
        $requestOptions = $this->getRequestOptions();

        if (!empty($requestOptions)) {
            $requestOptions = '?' . $requestOptions;
        }

        return join([
            $this->baseUri,
            $this->controller(),
            $requestOptions
        ]);
    }

    /**
     * Подготавливает запрос и делает его
     *
     * @return mixed|null
     */
    private function getXml()
    {
        $this->syncLog->withName("Cbr request")->info($this->getFullPath());

        try {
            return simplexml_load_file($this->getFullPath());
        } catch (\Exception $e) {
            $this->syncLog->withName($this->requestType)->error($e->getMessage());

            return null;
        } catch (\Throwable $e) {
            $this->syncLog->withName($this->requestType)->error($e->getMessage());

            return null;
        }
    }

    /**
     * Получим курс валют на указанную дату
     *
     * @param string|null $date
     * @return $this
     */
    public function getCurrenciesValues(string $date = null): CbrRequests
    {
        $params = [];

        if (isset($date)
            && !empty($date)
        ) {
            $params['date_req'] = $date;
        }

        $this->setDefault([
            'controller' => 'XML_daily.asp',
        ]);

        $this->requestParams = $params;

        return $this;
    }

    /**
     * Получим динамику котировки курса валюты за промежуток между двумя датами
     *
     * @param array $params
     * @return $this
     */
    public function getCurrencyDynamics(array $params = []): CbrRequests
    {
        $this->setDefault([
            'controller' => 'XML_dynamic.asp',
        ]);

        $this->requestParams = $params;

        return $this;
    }
}
