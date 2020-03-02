<?php

/**
 * This file was created by the developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * another great project.
 * You can find more information about us on https://bitbag.shop and write us
 * an email on kontakt@bitbag.pl.
 */

namespace BitBag\MercanetBnpParibasPlugin\Action;

use BitBag\MercanetBnpParibasPlugin\Legacy\SimplePayment;
use BitBag\MercanetBnpParibasPlugin\Legacy\MixPayment;
use BitBag\MercanetBnpParibasPlugin\Bridge\MercanetBnpParibasBridgeInterface;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Capture;
use Payum\Core\Security\TokenInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Webmozart\Assert\Assert;
use Payum\Core\Payum;

/**
 * @author Mikołaj Król <mikolaj.krol@bitbag.pl>
 * @author Patryk Drapik <patryk.drapik@bitbag.pl>
 */
final class CaptureAction implements ActionInterface, ApiAwareInterface
{
    use GatewayAwareTrait;

    /**
     * @var Payum
     */
    private $payum;

    /**
     * @var MercanetBnpParibasBridgeInterface
     */
    private $mercanetBnpParibasBridge;

    /**
     * @param Payum $payum
     */
    public function __construct(Payum $payum)
    {
        $this->payum = $payum;
    }

    /**
     * {@inheritDoc}
     */
    public function setApi($mercanetBnpParibasBridge)
    {
        if (!$mercanetBnpParibasBridge instanceof MercanetBnpParibasBridgeInterface) {
            throw new UnsupportedApiException('Not supported.');
        }

        $this->mercanetBnpParibasBridge = $mercanetBnpParibasBridge;
    }

    /**
     * {@inheritDoc}
     *
     * @param Capture $request
     */
    public function execute($request)
    {

        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        /** @var PaymentInterface $payment */
        $payment = $request->getFirstModel();
        Assert::isInstanceOf($payment, PaymentInterface::class);

        /** @var TokenInterface $token */
        $token = $request->getToken();

        $transactionReference = isset($model['transactionReference']) ? $model['transactionReference'] : null;

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

        $trips = [];
        $events = [];
        $books = [];
        $items = $payment->getOrder()->getItems()->getValues();

        foreach ($items as $item) {
            if ($item->getProduct()->isEvent()) {
                $events[] = $item;
            }
            if ($item->getProduct()->isBook()) {
                $books[] = $item;
            }
            if ($item->getProduct()->isTrip()) {
                $trips[] = $item;
            }
        }

        $transactionReference = "MercanetWS".uniqid()."n".$payment->getOrder()->getNumber();

        $model['transactionReference'] = $transactionReference;

        if ((count($trips) > 0 && count($events) > 0 )
            || (count($trips) > 0 && count($books) > 0 )
            || (count($events) > 0 && count($books) > 0 )
            || (count($trips) > 0 && count($books) > 0 && count($events) > 0 )) {
            $transactionReferencesList = [];
            $transactionDatesList = [];
            $transactionAmountsList = [];
            if (count($events) > 0) {
                $transactionReferencesList[] = $transactionReference."E";
                $transactionDatesList[] = date("Ymd");
                $amountEvents = 0;
                foreach ($events as $event) {
                    $amountEvents = $amountEvents + $event->getTotal();
                }
                $transactionAmountsList[] = $amountEvents;
            }
            if (count($books) > 0) {
                $transactionReferencesList[] = $transactionReference."B";
                $dateBooks = new \DateTime('+1day');
                $transactionDatesList[] = $dateBooks->format('Ymd');
                $amountBooks = 0;
                foreach ($books as $book) {
                    $amountBooks = $amountBooks + $book->getTotal();
                }
                $transactionAmountsList[] = $amountBooks;
            }
            if (count($trips) > 0) {
                $transactionReferencesList[] = $transactionReference."T";
                $dateTrips = new \DateTime('+2days');
                $transactionDatesList[] = $dateTrips->format('Ymd');
                $amountTrips = 0;
                foreach ($trips as $trip) {
                    $amountTrips = $amountTrips + $trip->getTotal();
                }
                $transactionAmountsList[] = $amountTrips;
            }
            $transactionReference = $transactionReferencesList[0];

            $payment = new MixPayment(
                $mercanet,
                $merchantId,
                $keyVersion,
                $environment,
                $amount,
                $targetUrl,
                $currencyCode,
                $transactionReference,
                $transactionReferencesList,
                $transactionDatesList,
                $transactionAmountsList,
                $automaticResponseUrl
            );
        } else {
            $payment = new SimplePayment(
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
        }

        $request->setModel($model);
        $payment->execute();
    }

    /**
     * @param string $gatewayName
     * @param object $model
     *
     * @return TokenInterface
     */
    private function createNotifyToken($gatewayName, $model)
    {
        return $this->payum->getTokenFactory()->createNotifyToken(
            $gatewayName,
            $model
        );
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Capture &&
            $request->getModel() instanceof \ArrayAccess;
    }
}
