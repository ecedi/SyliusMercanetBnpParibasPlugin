<?php

namespace Ecedi\MercanetBnpParibasPlugin\Legacy;

class Helper
{
    public static function isValidatedUri(string $uri): bool
    {
        if (!filter_var($uri, \FILTER_VALIDATE_URL) || strlen($uri) > 200) {
            return false;
        }

        return true;
    }

    public static function convertCurrencyToCode(string $currency = ''): ?string
    {
        $currencies = [
            'EUR' => '978', 'USD' => '840', 'CHF' => '756', 'GBP' => '826',
            'CAD' => '124', 'JPY' => '392', 'MXP' => '484', 'TRY' => '949',
            'AUD' => '036', 'NZD' => '554', 'NOK' => '578', 'BRC' => '986',
            'ARP' => '032', 'KHR' => '116', 'TWD' => '901', 'SEK' => '752',
            'DKK' => '208', 'KRW' => '410', 'SGD' => '702', 'XPF' => '953',
            'XOF' => '952',
        ];

        return $currencies[$currency] ?? null;
    }

    public static function generateSHASign(array $options, string $secretKey): ?string
    {
        $shaString = '';
        foreach ($options as $key => $value) {
            $shaString .= $key . '=' . $value;
            $shaString .= (array_search($key, array_keys($options), true) !== (count($options) - 1))
                ? '|'
                : $secretKey;
        }

        return hash('sha256', $shaString);
    }
}
