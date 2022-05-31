<?php

namespace Ecedi\MercanetBnpParibasPlugin\Action;

use Ecedi\MercanetBnpParibasPlugin\Bridge\MercanetBnpParibasBridgeInterface;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Notify;
use SM\Factory\FactoryInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Payment\PaymentTransitions;
use Webmozart\Assert\Assert;

final class NotifyAction implements ActionInterface, ApiAwareInterface
{
    use GatewayAwareTrait;

    private $mercanetBnpParibasBridge;
    private $stateMachineFactory;

    public function __construct(FactoryInterface $stateMachineFactory)
    {
        $this->stateMachineFactory = $stateMachineFactory;
    }

    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        if ($this->mercanetBnpParibasBridge->paymentVerification()) {
            /** @var PaymentInterface $payment */
            $payment = $request->getFirstModel();

            $details = $payment->getDetails();
            $payment->setDetails(array_merge($details, [
                'authorisationId' => $this->mercanetBnpParibasBridge->getAuthorisationId(),
            ]));

            Assert::isInstanceOf($payment, PaymentInterface::class);

            $this->stateMachineFactory
                ->get($payment, PaymentTransitions::GRAPH)
                ->apply(PaymentTransitions::TRANSITION_COMPLETE)
            ;
        }
    }

    public function setApi($mercanetBnpParibasBridge): void
    {
        if (!$mercanetBnpParibasBridge instanceof MercanetBnpParibasBridgeInterface) {
            throw new UnsupportedApiException('Not supported.');
        }

        $this->mercanetBnpParibasBridge = $mercanetBnpParibasBridge;
    }

    public function supports($request): bool
    {
        return
            $request instanceof Notify &&
            $request->getModel() instanceof \ArrayObject
        ;
    }
}
