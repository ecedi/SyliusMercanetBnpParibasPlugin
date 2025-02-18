<?php

/**
 * This file was created by the developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * another great project.
 * You can find more information about us on https://bitbag.shop and write us
 * an email on kontakt@bitbag.pl.
 */

namespace Tests\Ecedi\MercanetBnpParibasPlugin\Behat\Page\External;

use Behat\Mink\Exception\DriverException;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use Sylius\Behat\Page\PageInterface;

/**
 * @author Patryk Drapik <patryk.drapik@bitbag.pl>
 */
interface MercanetBnpParibasCheckoutPageInterface extends PageInterface
{
    /**
     * @throws UnsupportedDriverActionException
     * @throws DriverException
     */
    public function pay();

    /**
     * @throws UnsupportedDriverActionException
     * @throws DriverException
     */
    public function cancel();
}