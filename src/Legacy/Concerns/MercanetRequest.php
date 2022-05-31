<?php

namespace Ecedi\MercanetBnpParibasPlugin\Legacy\Concerns;

use Ecedi\MercanetBnpParibasPlugin\Legacy\Helper;

trait MercanetRequest
{
    private $checkoutUrl;

    protected $options = [];

    private $brandsmap = [
        'ACCEPTGIRO' => 'CREDIT_TRANSFER',
        'AMEX' => 'CARD',
        'BCMC' => 'CARD',
        'BUYSTER' => 'CARD',
        'BANK CARD' => 'CARD',
        'CB' => 'CARD',
        'IDEAL' => 'CREDIT_TRANSFER',
        'INCASSO' => 'DIRECT_DEBIT',
        'MAESTRO' => 'CARD',
        'MASTERCARD' => 'CARD',
        'MASTERPASS' => 'CARD',
        'MINITIX' => 'OTHER',
        'NETBANKING' => 'CREDIT_TRANSFER',
        'PAYPAL' => 'CARD',
        'PAYLIB' => 'CARD',
        'REFUND' => 'OTHER',
        'SDD' => 'DIRECT_DEBIT',
        'SOFORT' => 'CREDIT_TRANSFER',
        'VISA' => 'CARD',
        'VPAY' => 'CARD',
        'VISA ELECTRON' => 'CARD',
        'CBCONLINE' => 'CREDIT_TRANSFER',
        'KBCONLINE' => 'CREDIT_TRANSFER',
    ];

    /**
     * List of available fields
     *
     * @var array|string[]
     */
    private $availableFields = [
        'amount',
        'currencyCode',
        'merchantId',
        'normalReturnUrl',
        'transactionReference',
        'keyVersion',
        'paymentMeanBrand',
        'customerLanguage',
        'billingAddress.city',
        'billingAddress.company',
        'billingAddress.country',
        'billingAddress',
        'billingAddress.postBox',
        'billingAddress.state',
        'billingAddress.street',
        'billingAddress.streetNumber',
        'billingAddress.zipCode',
        'billingContact.email',
        'billingContact.firstname',
        'billingContact.gender',
        'billingContact.lastname',
        'billingContact.mobile',
        'billingContact.phone',
        'customerAddress',
        'customerAddress.city',
        'customerAddress.company',
        'customerAddress.country',
        'customerAddress.postBox',
        'customerAddress.state',
        'customerAddress.street',
        'customerAddress.streetNumber',
        'customerAddress.zipCode',
        'customerContact',
        'customerContact.email',
        'customerContact.firstname',
        'customerContact.gender',
        'customerContact.lastname',
        'customerContact.mobile',
        'customerContact.phone',
        'customerContact.title',
        'expirationDate',
        'automaticResponseUrl',
        'templateName',
        'paymentMeanBrandList',
        'instalmentData.number',
        'instalmentData.datesList',
        'instalmentData.transactionReferencesList',
        'instalmentData.amountsList',
        'paymentPattern',
        'captureDay',
        'fraudData.bypass3DS',
    ];

    /**
     * List of required fields
     *
     * @var array|string[]
     */
    private $requiredFields = [
        'amount',
        'currencyCode',
        'merchantId',
        'normalReturnUrl',
        'transactionReference',
        'keyVersion',
    ];

    /**
     * List of allowed languages
     *
     * @var array|string[]
     */
    private $allowedLanguages = [
        'nl', 'fr', 'de', 'it', 'es', 'cy', 'en',
    ];

    public function setTransactionReference(string $transactionReference): void
    {
        if (preg_match('/[^a-zA-Z0-9_-]/', $transactionReference)) {
            throw new \InvalidArgumentException('TransactionReference cannot contain special characters');
        }

        $this->options['transactionReference'] = $transactionReference;
    }


    public function setAmount(int $amount): void
    {
        if (!is_int($amount)) {
            throw new \InvalidArgumentException('Integer expected. Amount is always in cents');
        }

        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be a positive number');
        }

        $this->options['amount'] = $amount;
    }

    public function setCurrency(string $currency): void
    {
        $currencyCode = Helper::convertCurrencyToCode($currency);

        if ($currencyCode === null) {
            throw new \InvalidArgumentException('Unknown currency');
        }

        $this->options['currencyCode'] = $currencyCode;
    }

    public function setLanguage(string $language): void
    {
        if (!in_array($language, $this->allowedLanguages, true)) {
            throw new \InvalidArgumentException('Invalid language locale');
        }

        $this->options['customerLanguage'] = $language;
    }

    public function setOrderChannel($value): void
    {
        if (strlen($value) > 20) {
            throw new \InvalidArgumentException('orderChannel is too long');
        }

        $this->options['orderChannel'] = $value;
    }

    public function setNormalReturnUrl(string $url): void
    {
        if (!Helper::isValidatedUri($url)) {
            throw new \InvalidArgumentException('Uri is not valid');
        }

        $this->options['normalReturnUrl'] = $url;
    }

    public function setAutomaticResponseUrl($url): void
    {
        if (!Helper::isValidatedUri($url)) {
            throw new \InvalidArgumentException('Uri is not valid');
        }

        $this->options['automaticResponseUrl'] = $url;
    }

    public function setCustomerContactEmail(string $email): void
    {
        if (strlen($email) > 50) {
            throw new \InvalidArgumentException('Email is too long');
        }

        if (!filter_var($email, \FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Email is invalid');
        }

        $this->options['customerContact.email'] = $email;
    }

    public function setBillingContactEmail(string $email): void
    {
        if (strlen($email) > 50) {
            throw new \InvalidArgumentException('Email is too long');
        }

        if (!filter_var($email, \FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Email is invalid');
        }

        $this->options['billingContact.email'] = $email;
    }

    public function setBillingAddressStreet(string $street): void
    {
        if (strlen($street) > 35) {
            throw new \InvalidArgumentException('street is too long');
        }

        $this->options['billingAddress.street'] = \Normalizer::normalize($street);
    }

    public function setBillingAddressStreetNumber(string $streetNumber): void
    {
        if (strlen($streetNumber) > 10) {
            throw new \InvalidArgumentException('streetNumber is too long');
        }

        $this->options['billingAddress.streetNumber'] = \Normalizer::normalize($streetNumber);
    }

    public function setBillingAddressZipCode(string $zipCode): void
    {
        if (strlen($zipCode) > 10) {
            throw new \InvalidArgumentException('zipCode is too long');
        }

        $this->options['billingAddress.zipCode'] = \Normalizer::normalize($zipCode);
    }

    public function setBillingAddressCity(string $city): void
    {
        if (strlen($city) > 25) {
            throw new \InvalidArgumentException('city is too long');
        }

        $this->options['billingAddress.city'] = \Normalizer::normalize($city);
    }

    public function setBillingContactPhone(string $phone): void
    {
        if (strlen($phone) > 30) {
            throw new \InvalidArgumentException('phone is too long');
        }

        $this->options['billingContact.phone'] = $phone;
    }

    public function setBillingContactFirstname(string $firstname): void
    {
        $this->options['billingContact.firstname'] = str_replace(["'", '"'], '', \Normalizer::normalize($firstname));
    }


    public function setBillingContactLastname(string $lastname): void
    {
        $this->options['billingContact.lastname'] = str_replace(["'", '"'], '', \Normalizer::normalize($lastname));
    }

    public function setPaymentBrand($brand): void
    {
        $this->options['paymentMeanBrandList'] = '';

        if (!array_key_exists(strtoupper($brand), $this->brandsmap)) {
            throw new \InvalidArgumentException("Unknown Brand [$brand].");
        }

        $this->options['paymentMeanBrandList'] = strtoupper($brand);
    }

    public function __call($method, $args)
    {
        if (0 === strpos($method, 'set')) {
            $field = lcfirst(substr($method, 3));

            if (in_array($field, $this->availableFields, true)) {
                $this->options[$field] = $args[0];

                return $this;
            }
        }

        if (0 === strpos($method, 'get')) {
            $field = lcfirst(substr($method, 3));

            if (array_key_exists($field, $this->options)) {
                return $this->options[$field];
            }
        }

        throw new \BadMethodCallException("Unknown method $method");
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    private function optionsToString(): string
    {
        $optionString = '';
        foreach ($this->options as $key => $value) {
            $optionString .= $key . '=' . $value;
            $optionString .= (array_search($key, array_keys($this->options), true) !== (count($this->options) - 1))
                ? '|'
                : '';
        }

        return utf8_decode($optionString);
    }

    public function validateRequiredOptions(): void
    {
        foreach ($this->requiredFields as $field) {
            if ($this->options[$field] === null) {
                throw new \RuntimeException($field . ' can not be empty');
            }
        }
    }

    public function generatePayment(): ?string
    {
        $parameters = [
            'Data' => $this->optionsToString(),
            'InterfaceVersion' => self::INTERFACE_VERSION,
            'Seal' => Helper::generateSHASign($this->options, $this->secretKey),
        ];

        $html = <<<HTML
                    <!doctype html>
                    <html lang="en">
                    <head>
                    </head>
                    <body>
                        <form id="redirect_form" method="post" action="{$this->getUrl()}" >'
                    HTML;

        foreach ($parameters as $name => $value) {
            $html .= "<input type='hidden' name='$name' id='$name' value='$value' />";
        }

        $html .= <<<HTML
                    </form>
                    <script>
                        const form = document.getElementById("redirect_form");
                        form.submit();
                    </script>
                </body>
                </html>';
                HTML;

        return $html;
    }
}
