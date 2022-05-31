<?php

namespace Ecedi\MercanetBnpParibasPlugin\Legacy\Concerns;

use Ecedi\MercanetBnpParibasPlugin\Legacy\Helper;
use InvalidArgumentException;

trait MercanetResponse
{
    private $responseParameters;

    private $shaSign;

    /**
     * Extract http parameters from given http response
     *
     *
     * @return $this
     */
    public function fromResponse(array $httpResponse): self
    {
        $httpResponse = array_change_key_case($httpResponse, \CASE_UPPER);
        $this->shaSign = $this->extractShaSign($httpResponse);
        $this->responseParameters = $this->filterRequestParameters($httpResponse);

        return $this;
    }

    /**
     * Filter http request parameters
     */
    private function filterRequestParameters(array $httpResponse): array
    {
        if (
            !array_key_exists('DATA', $httpResponse)
            ||
            '' === $httpResponse['DATA']
        ) {
            throw new InvalidArgumentException('Data parameter not present in parameters.');
        }

        $responseParameters = explode('|', $httpResponse['DATA']);
        $parameters = [];

        foreach ($responseParameters as $parameter) {
            $dataKeyValue = explode('=', $parameter, 2);
            $parameters[$dataKeyValue[0]] = $dataKeyValue[1];
        }

        return $parameters;
    }

    private function extractShaSign(array $responseParameters): ?string
    {
        if (
            !array_key_exists('SEAL', $responseParameters)
            ||
            '' === $responseParameters['SEAL']
        ) {
            throw new InvalidArgumentException('SHASIGN parameter not present in parameters.');
        }

        return $responseParameters['SEAL'];
    }

    /**
     * Retrieves a response parameter
     *
     *
     * @return mixed
     */
    public function getParameter(string $key)
    {
        if (method_exists($this, 'get' . $key)) {
            return $this->{'get' . $key}(); /** @phpstan-ignore-line */
        }

        $key = strtoupper($key);
        $parameters = array_change_key_case($this->responseParameters, \CASE_UPPER);
        if (!array_key_exists($key, $parameters)) {
            throw new InvalidArgumentException('Parameter ' . $key . ' does not exist.');
        }

        return $parameters[$key];
    }

    /**
     * Checks if the response is valid
     */
    public function isValid(): bool
    {
        return Helper::generateSHASign($this->responseParameters, $this->secretKey) === $this->shaSign;
    }

    /**
     * Check if the response is passed successful
     */
    public function isSuccessful(): bool
    {
        return in_array(
            $this->getParameter('RESPONSECODE'),
            ['00', '60'],
            true
        );
    }

    /**
     * Check if payment is successfully passed
     */
    public function isSuccessfullyPassed(): bool
    {
        return $this->isValid() && $this->isSuccessful();
    }

    public function getResponseParameters(): array
    {
        return $this->responseParameters;
    }

    public function getTransactionReference(): string
    {
        return $this->responseParameters['transactionReference'];
    }
}
