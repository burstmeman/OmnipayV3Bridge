<?php
namespace Payum\OmnipayV3Bridge\Tests\Functional;

use Payum\Core\Request\GetHumanStatus;
use Payum\Core\Request\Capture;
use Payum\OmnipayV3Bridge\OmnipayGatewayFactory;
use PHPUnit\Framework\TestCase;

class PaymentTest extends TestCase
{
    /**
     * @test
     */
    public function shouldFinishSuccessfully(): void
    {
        $factory = new OmnipayGatewayFactory();

        $gateway = $factory->create(['type' => 'Dummy']);

        $date = new \DateTime('now + 2 year');

        $capture = new Capture([
            'amount' => '1000.00',
            'card' => [
                'number' => '4242424242424242', // must be authorized
                'cvv' => 123,
                'expiryMonth' => 6,
                'expiryYear' => $date->format('y'),
                'firstName' => 'foo',
                'lastName' => 'bar',
            ]
        ]);

        $gateway->execute($capture);

        $statusRequest = new GetHumanStatus($capture->getModel());
        $gateway->execute($statusRequest);

        self::assertTrue($statusRequest->isCaptured());
    }

    /**
     * @test
     */
    public function shouldFinishWithFailed(): void
    {
        $factory = new OmnipayGatewayFactory();

        $gateway = $factory->create(['type' => 'Dummy']);

        $date = new \DateTime('now + 2 year');

        $capture = new Capture([
            'amount' => '1000.00',
            'card' => [
                'number' => '4111111111111111', //must be declined,
                'cvv' => 123,
                'expiryMonth' => 6,
                'expiryYear' => $date->format('y'),
                'firstName' => 'foo',
                'lastName' => 'bar',
            ]
        ]);

        $gateway->execute($capture);

        $statusRequest = new GetHumanStatus($capture->getModel());
        $gateway->execute($statusRequest);

        self::assertTrue($statusRequest->isFailed());
    }
}
