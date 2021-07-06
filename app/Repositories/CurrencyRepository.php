<?php

namespace App\Repositories;

use App\Api\CbrXML;
use App\Models\CurrencyValue;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class CurrencyRepository
{
    private $api;

    public function __construct()
    {
        $this->api = new CbrXML();
    }

    /**
     * @param string|null $dateString
     * @param bool $recursiveFlag
     * @return array|false
     */
    public function getCurrenciesValues(string $dateString = null, bool $recursiveFlag = false)
    {
        if (!$dateString) {
            $dateString = Carbon::now()->toDateString();
        }

        if (!(CurrencyValue::byDate($dateString)
            ->exists())
        ) {
            $currenciesValues = new Collection();
            $date = Carbon::parse($dateString)->format('d/m/Y');
            $xmlObject = $this->api->getCurrenciesValues($date)->send();

            if (empty($xmlObject)) {
                return false;
            }

            $parsedCurrenciesValues = $this->parseCurrenciesValues($xmlObject);

            if (empty($parsedCurrenciesValues)) {
                return false;
            }

            foreach ($parsedCurrenciesValues as $cbrId => $value) {
                try {
                    $currencyValue = CurrencyValue::updateOrCreate([
                        'cbr_id' => $cbrId,
                        'value' => $value,
                        'date' => $dateString,
                    ]);
                } catch (\Exception $e) {
                    Log::error(__CLASS__ . ':' . __FUNCTION__ . ':' . $e->getMessage());

                    return false;
                }

                $currenciesValues->push($currencyValue);
            }

            unset($parsedCurrencyValue);
        }

        if (!$recursiveFlag) {
            $previousDateString = getPreviousDateStringNotWeekend($dateString);
            $this->getCurrenciesValues($previousDateString, true);
        }

        return true;
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
        $result = [];

        if (!array_key_exists('Valute', $responseArray)) {
            return false;
        } else {
            $currencyItems = $responseArray['Valute'];
        }

        foreach ($currencyItems as $currencyItem) {
            $currencyId = $currencyItem['attributes']['ID'];
            $result[$currencyId] = floatval(str_replace(',', '.', $currencyItem['Value']));
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
