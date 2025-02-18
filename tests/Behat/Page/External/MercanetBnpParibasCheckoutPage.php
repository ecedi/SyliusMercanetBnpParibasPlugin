<?php

/**
 * This file was created by the developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * another great project.
 * You can find more information about us on https://bitbag.shop and write us
 * an email on kontakt@bitbag.pl.
 */

namespace Tests\Ecedi\MercanetBnpParibasPlugin\Behat\Page\External;

use Behat\Mink\Session;
use Payum\Core\Security\TokenInterface;
use Sylius\Behat\Page\Page;
use Sylius\Component\Resource\Repository\RepositoryInterface;

/**
 * @author Patryk Drapik <patryk.drapik@bitbag.pl>
 */
final class MercanetBnpParibasCheckoutPage extends Page implements MercanetBnpParibasCheckoutPageInterface
{
    /**
     * @var RepositoryInterface
     */
    private $securityTokenRepository;

    /**
     * @param Session $session
     * @param array $parameters
     * @param RepositoryInterface $securityTokenRepository
     */
    public function __construct(Session $session, array $parameters, RepositoryInterface $securityTokenRepository)
    {
        parent::__construct($session, $parameters);

        $this->securityTokenRepository = $securityTokenRepository;
    }

    /**
     * {@inheritDoc}
     */
    public function pay()
    {
        $this->getDriver()->visit($this->findCaptureToken()->getTargetUrl());
    }

    /**
     * {@inheritDoc}
     */
    public function cancel()
    {
        $this->getDriver()->visit($this->findCaptureToken()->getTargetUrl());
    }

    /**
     * @param array $urlParameters
     *
     * @return string
     */
    protected function getUrl(array $urlParameters = [])
    {
        return 'https://payment-webinit-mercanet.test.sips-atos.com/rs-services/v2/paymentInit';
    }

    /**
     * @return TokenInterface
     *
     * @throws \RuntimeException
     */
    private function findCaptureToken()
    {
        $tokens = $this->securityTokenRepository->findAll();

        /** @var TokenInterface $token */
        foreach ($tokens as $token) {
            if (strpos($token->getTargetUrl(), 'capture')) {
                return $token;
            }
        }

        throw new \RuntimeException('Cannot find capture token, check if you are after proper checkout steps');
    }
}