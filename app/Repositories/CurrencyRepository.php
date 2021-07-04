<?php


namespace App\Repositories;

use App\Api\CbrRequests;


class CurrencyRepository
{
    private $api;

    public function __construct()
    {
        $this->api = new CbrRequests();
    }

    public function getCurrencyValuesThrottle()
    {
        return $this->api->getCurrenciesValues()->waitThrottle();
    }

    public function getCurrenciesValues(string $date = null)
    {
        $xmlObject = $this->api->getCurrenciesValues($date)->send();
        $result = $this->parseCurrenciesValues($xmlObject);

        return $result;
    }

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
}
