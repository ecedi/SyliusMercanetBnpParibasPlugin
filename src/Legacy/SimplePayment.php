<?php

namespace Ecedi\MercanetBnpParibasPlugin\Legacy;

use Payum\Core\Reply\HttpResponse;

final class SimplePayment
{
    private $mercanet;
    private $environment;
    private $merchantId;
    private $keyVersion;
    private $amount;
    private $currency;
    private $transactionReference;
    private $automaticResponseUrl;
    private $targetUrl;

    public function __construct(
        Mercanet $mercanet,
        string $merchantId,
        string $keyVersion,
        string $environment,
        int $amount,
        string $targetUrl,
        string $currency,
        string $transactionReference,
        string $automaticResponseUrl
    ) {
        $this->automaticResponseUrl = $automaticResponseUrl;
        $this->transactionReference = $transactionReference;
        $this->mercanet = $mercanet;
        $this->environment = $environment;
        $this->merchantId = $merchantId;
        $this->keyVersion = $keyVersion;
        $this->amount = $amount;
        $this->currency = $currency;
        $this->targetUrl = $targetUrl;
    }

    public function execute(): void
    {
        $this->resolveEnvironment();

        $this->mercanet->setMerchantId($this->merchantId);
        $this->mercanet->setKeyVersion($this->keyVersion);
        $this->mercanet->setAmount($this->amount);
        $this->mercanet->setCurrency($this->currency);
        $this->mercanet->setOrderChannel('INTERNET');
        $this->mercanet->setTransactionReference($this->transactionReference);
        $this->mercanet->setNormalReturnUrl($this->targetUrl);
        $this->mercanet->setAutomaticResponseUrl($this->automaticResponseUrl);

        $this->mercanet->validateRequiredOptions();

        throw new HttpResponse($this->mercanet->generatePayment());
    }

    /**
     * @throws \InvalidArgumentException
     */
    private function resolveEnvironment(): void
    {
        if (Mercanet::TEST === $this->environment) {
            $this->mercanet->setUrl(Mercanet::TEST);

            return;
        }

        if (Mercanet::PRODUCTION === $this->environment) {
            $this->mercanet->setUrl(Mercanet::PRODUCTION);

            return;
        }

        throw new \InvalidArgumentException(
            sprintf(
                'The "%s" environment is invalid. Expected %s or %s',
                $this->environment,
                Mercanet::PRODUCTION,
                Mercanet::TEST
            )
        );
    }
}
