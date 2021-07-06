<?php

use Carbon\Carbon;

if (! function_exists('simpleXmlToArray')) {
    /**
     * @param $xmlObject
     * @param array $out
     * @return array|mixed
     */
    function simpleXmlToArray($xmlObject, array $out = []) : array
    {
        foreach ($xmlObject as $index => $node) {
            if (!empty($xmlObject->attributes())) {
                foreach ($xmlObject->attributes() as $attributeName => $attributeValue) {
                    $out['attributes'][$attributeName] = $attributeValue->__toString ();
                }
            }

            if (count($node) === 0) {
                $out[$node->getName()] = $node->__toString ();
            } else {
                $out[$node->getName()][] = simpleXmlToArray($node);
            }
        }

        return $out;
    }
}


if (! function_exists('getPreviousDateStringNotWeekend')) {
    /**
     * @param string $dateString
     * @return string
     */
    function getPreviousDateStringNotWeekend(string $dateString): string
    {
        $previousDate = Carbon::createFromDate($dateString)->subDay();

        if ($previousDate->isWeekend()) {
            $previousDate = $previousDate->previous('Friday');
        }

        return $previousDate->toDateString();
    }
}
