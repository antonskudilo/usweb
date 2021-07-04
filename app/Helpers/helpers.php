<?php

if (!function_exists('simpleXmlToArray')) {
    /**
     * @param $xmlObject
     * @param array $out
     * @return array|mixed
     */
    function simpleXmlToArray($xmlObject, array $out = []) : array
    {
        foreach ($xmlObject as $index => $node) {
            if (count($node) === 0) {
                $out[$node->getName()] = $node->__toString ();
            } else {
                $out[$node->getName()][] = simpleXmlToArray($node);
            }
        }

        return $out;
    }
}
