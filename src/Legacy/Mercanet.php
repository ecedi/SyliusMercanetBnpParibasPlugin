<?php

namespace Ecedi\MercanetBnpParibasPlugin\Legacy;

use Ecedi\MercanetBnpParibasPlugin\Legacy\Concerns\MercanetRequest;
use Ecedi\MercanetBnpParibasPlugin\Legacy\Concerns\MercanetResponse;

/**
 * @method string getAuthorisationId()
 * @method void setMerchantId(string $merchantId)
 * @method void setKeyVersion(string $keyVersion)
 */
class Mercanet
{
    use MercanetRequest;
    use MercanetResponse;

    public const TEST = 'https://payment-webinit-mercanet.test.sips-services.com/paymentInit';
    public const PRODUCTION = 'https://payment-webinit.mercanet.bnpparibas.net/paymentInit';
    public const INTERFACE_VERSION = 'HP_2.20';

    protected $secretKey;
    protected $pspUrl = self::TEST;

    public function __construct(string $secret)
    {
        $this->secretKey = $secret;
    }

    public function getUrl(): string
    {
        return $this->pspUrl;
    }

    public function setUrl($pspUrl): void
    {
        if (!Helper::isValidatedUri($pspUrl)) {
            throw new \InvalidArgumentException('Uri is not valid');
        }

        $this->pspUrl = $pspUrl;
    }
}
