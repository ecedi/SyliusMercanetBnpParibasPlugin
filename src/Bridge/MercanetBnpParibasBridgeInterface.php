<?php

namespace Ecedi\MercanetBnpParibasPlugin\Bridge;

use Ecedi\MercanetBnpParibasPlugin\Legacy\Mercanet;

interface MercanetBnpParibasBridgeInterface
{
    public function createMercanet(string $secretKey): Mercanet;
    public function paymentVerification(): bool;
    public function getAuthorisationId(): string;
    public function isPostMethod(): bool;
    public function getSecretKey(): string;
    public function setSecretKey(string $secretKey);
    public function getMerchantId(): string;
    public function setMerchantId(string $merchantId);
    public function getKeyVersion(): string;
    public function setKeyVersion(string $keyVersion);
    public function getEnvironment(): string;
    public function setEnvironment(string $environment);
}
