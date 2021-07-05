<?php

namespace App\Repositories;

use App\Api\CbrXML;

class CurrencyRepository
{
    private $api;

    public function __construct()
    {
        $this->api = new CbrXML();
    }

    /**
     * @param string|null $date d/m/Y
     * @return array|false
     */
    public function getCurrenciesValues(string $date = null)
    {
        $xmlObject = $this->api->getCurrenciesValues($date)->send();
        $result = $this->parseCurrenciesValues($xmlObject);

        return $result;
    }

    /**
     * @param $xmlObject
     * @return array|false
     */
    public function parseCurrenciesValues($xmlObject)
    {
        if (empty($xmlObject)) {
            return false;
        }

        $responseArray = simpleXmlToArray($xmlObject);

        if (!array_key_exists('Valute', $responseArray)) {
            return false;
        } else {
            $currencyItems = $responseArray['Valute'];
        }

        $result = [];

        foreach ($currencyItems as $currencyItem) {
            $result[$currencyItem['CharCode']] = floatval(str_replace(',', '.', $currencyItem['Value']));
        }

        return $result;
    }

    /**
     * @param array $params
     *      ['date_req1']   string  required    date from d/m/Y
     *      ['date_req2']   string  required    date to d/m/Y
     *      ['VAL_NM_RQ']   string  required    currency code
     * @return array|false
     */
    public function getCurrencyDynamics(array $params)
    {
        $xmlObject = $this->api->getCurrencyDynamics($params)->send();
        $result = $this->parseCurrencyDynamics($xmlObject);

        return $result;
    }

    /**
     * @param $xmlObject
     * @return array|false
     */
    public function parseCurrencyDynamics($xmlObject)
    {
        if (empty($xmlObject)) {
            return false;
        }

        $responseArray = simpleXmlToArray($xmlObject);

        if (!array_key_exists('Record', $responseArray)) {
            return false;
        } else {
            $currencyItems = $responseArray['Record'];
        }

        $result = [];

        foreach ($currencyItems as $currencyItem) {
            $date = $currencyItem['attributes']['Date'];
            $result[$date] = floatval(str_replace(',', '.', $currencyItem['Value']));
        }

        return $result;
    }

    /**
     * @return array|false
     */
    public function getCurrenciesList()
    {
        $xmlObject = $this->api->getCurrenciesList()->send();
        $result = $this->parseCurrenciesList($xmlObject);

        return $result;
    }

    /**
     * @param $xmlObject
     * @return array|false
     */
    public function parseCurrenciesList($xmlObject)
    {
        if (empty($xmlObject)) {
            return false;
        }

        $responseArray = simpleXmlToArray($xmlObject);

        if (!array_key_exists('Item', $responseArray)) {
            return false;
        } else {
            $currencyItems = $responseArray['Item'];
        }

        $result = [];

        foreach ($currencyItems as $currencyItem) {
            $currencyId = $currencyItem['attributes']['ID'];
            unset($currencyItem['attributes']);
            $result[$currencyId] = $currencyItem;
        }

        return $result;
    }
}
