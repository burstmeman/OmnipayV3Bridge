<?php
namespace Payum\OmnipayV3Bridge\Tests;

use Omnipay\Common\AbstractGateway;
use Omnipay\Common\Message\RequestInterface;

class CreditCardGateway extends AbstractGateway
{
    public $returnOnPurchase;

    public function purchase()
    {
        return $this->returnOnPurchase;
    }

    // this type of gateways do not have this method.
    //public function completePurchase() {}

    public function getName() {}
}
