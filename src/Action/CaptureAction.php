<?php

namespace Ecedi\MercanetBnpParibasPlugin\Action;

use Ecedi\MercanetBnpParibasPlugin\Bridge\MercanetBnpParibasBridgeInterface;
use Ecedi\MercanetBnpParibasPlugin\Legacy\SimplePayment;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Payum;
use Payum\Core\Request\Capture;
use Payum\Core\Security\TokenInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Webmozart\Assert\Assert;

final class CaptureAction implements ActionInterface, ApiAwareInterface
{
    use GatewayAwareTrait;

    private $payum;
    private $mercanetBnpParibasBridge;

    public function __construct(Payum $payum)
    {
        $this->payum = $payum;
    }

    public function setApi($mercanetBnpParibasBridge): void
    {
        if (!$mercanetBnpParibasBridge instanceof MercanetBnpParibasBridgeInterface) {
            throw new UnsupportedApiException('Not supported.');
        }

        $this->mercanetBnpParibasBridge = $mercanetBnpParibasBridge;
    }

    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        /** @var PaymentInterface $payment */
        $payment = $request->getFirstModel();
        Assert::isInstanceOf($payment, PaymentInterface::class);

        /** @var TokenInterface $token */
        $token = $request->getToken();

        $transactionReference = $model['transactionReference'] ?? null;

        if ($transactionReference !== null) {
            if ($this->mercanetBnpParibasBridge->isPostMethod()) {
                $model['status'] = $this->mercanetBnpParibasBridge->paymentVerification() ?
                    PaymentInterface::STATE_COMPLETED : PaymentInterface::STATE_CANCELLED
                ;

                if ($model['status'] == PaymentInterface::STATE_COMPLETED) {
                    $model['authorisationId'] = $this->mercanetBnpParibasBridge->getAuthorisationId();
                }

                $request->setModel($model);

                return;
            }

            if ($model['status'] === PaymentInterface::STATE_COMPLETED) {
                return;
            }
        }

        $notifyToken = $this->createNotifyToken($token->getGatewayName(), $token->getDetails());

        $secretKey = $this->mercanetBnpParibasBridge->getSecretKey();

        $mercanet = $this->mercanetBnpParibasBridge->createMercanet($secretKey);

        $environment = $this->mercanetBnpParibasBridge->getEnvironment();
        $merchantId = $this->mercanetBnpParibasBridge->getMerchantId();
        $keyVersion = $this->mercanetBnpParibasBridge->getKeyVersion();

        $automaticResponseUrl = $notifyToken->getTargetUrl();
        $currencyCode = $payment->getCurrencyCode();
        $targetUrl = $request->getToken()->getTargetUrl();
        $amount = $payment->getAmount();

        $transactionReference = 'MercanetWS' . uniqid() . 'OR' . $payment->getOrder()->getNumber();

        $model['transactionReference'] = $transactionReference;

        $simplePayment = new SimplePayment(
            $mercanet,
            $merchantId,
            $keyVersion,
            $environment,
            $amount,
            $targetUrl,
            $currencyCode,
            $transactionReference,
            $automaticResponseUrl
        );

        $request->setModel($model);
        $simplePayment->execute();
    }

    private function createNotifyToken(string $gatewayName, object $model): TokenInterface
    {
        return $this->payum->getTokenFactory()->createNotifyToken(
            $gatewayName,
            $model
        );
    }

    public function supports($request): bool
    {
        return
            $request instanceof Capture &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}
