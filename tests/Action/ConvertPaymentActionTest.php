<?php
namespace Payum\OmnipayV3Bridge\Tests\Action;

use Payum\Core\Model\CreditCard;
use Payum\Core\Request\GetCurrency;
use Payum\Core\Security\SensitiveValue;
use Payum\OmnipayV3Bridge\Action\ConvertPaymentAction;
use Payum\Core\Model\Payment;
use Payum\Core\Request\Convert;
use Payum\Core\Tests\GenericActionTest;
use Payum\Core\Model\PaymentInterface;
use Payum\Core\Security\TokenInterface;
use Payum\Core\Request\Generic;
use Payum\Core\GatewayInterface;

class ConvertPaymentActionTest extends GenericActionTest
{
    protected $actionClass = ConvertPaymentAction::class;

    protected $requestClass = Convert::class;

    public function provideSupportedRequests(): \Iterator
    {
        return yield from [
            [ new $this->requestClass(new Payment, 'array') ],
            [ new $this->requestClass($this->createMock(PaymentInterface::class), 'array') ],
            [ new $this->requestClass(new Payment, 'array', $this->createMock(TokenInterface::class)) ],
        ];
    }

    public function provideNotSupportedRequests(): \Iterator
    {
        return yield from [
            ['foo'],
            [['foo']],
            [new \stdClass()],
            [$this->getMockForAbstractClass(Generic::class, [[]])],
            [new $this->requestClass($this->createMock(PaymentInterface::class), 'notArray')],
        ];
    }

    /**
     * @test
     */
    public function shouldCorrectlyConvertPaymentToDetailsArray(): void
    {
        $gatewayMock = $this->createMock(GatewayInterface::class);
        $gatewayMock
            ->expects($this->once())
            ->method('execute')
            ->with($this->isInstanceOf(GetCurrency::class))
            ->willReturnCallback(function(GetCurrency $request) {
                $request->name = 'US Dollar';
                $request->alpha3 = 'USD';
                $request->numeric = 123;
                $request->exp = 2;
                $request->country = 'US';
            })
        ;

        $payment = new Payment;
        $payment->setNumber('theNumber');
        $payment->setCurrencyCode('USD');
        $payment->setTotalAmount(123);
        $payment->setDescription('the description');
        $payment->setClientId('theClientId');
        $payment->setClientEmail('theClientEmail');

        $action = new ConvertPaymentAction;
        $action->setGateway($gatewayMock);

        $action->execute($convert = new Convert($payment, 'array'));
        $details = $convert->getResult();

        self::assertNotEmpty($details);

        self::assertArrayHasKey('amount', $details);
        self::assertEquals(1.23, $details['amount']);

        self::assertArrayHasKey('currency', $details);
        self::assertEquals('USD', $details['currency']);

        self::assertArrayHasKey('description', $details);
        self::assertEquals('the description', $details['description']);
    }

    /**
     * @test
     */
    public function shouldCorrectlyConvertPaymentWithCreditCardToDetailsArray(): void
    {
        $gatewayMock = $this->createMock(GatewayInterface::class);
        $gatewayMock
            ->expects($this->once())
            ->method('execute')
            ->with($this->isInstanceOf(GetCurrency::class))
            ->willReturnCallback(function(GetCurrency $request) {
                $request->name = 'US Dollar';
                $request->alpha3 = 'USD';
                $request->numeric = 123;
                $request->exp = 2;
                $request->country = 'US';
            })
        ;

        $creditCard = new CreditCard();
        $creditCard->setNumber('4444333322221111');
        $creditCard->setHolder('John Doe');
        $creditCard->setSecurityCode('322');
        $creditCard->setExpireAt(new \DateTime('2015-11-12'));

        $payment = new Payment;
        $payment->setNumber('theNumber');
        $payment->setCurrencyCode('USD');
        $payment->setTotalAmount(123);
        $payment->setDescription('the description');
        $payment->setClientId('theClientId');
        $payment->setClientEmail('theClientEmail');
        $payment->setCreditCard($creditCard);

        $action = new ConvertPaymentAction;
        $action->setGateway($gatewayMock);

        $action->execute($convert = new Convert($payment, 'array'));
        $details = $convert->getResult();

        self::assertNotEmpty($details);

        self::assertArrayHasKey('card', $details);
        self::assertInstanceOf(SensitiveValue::class, $details['card']);
        self::assertEquals([
            'number' => '4444333322221111',
            'cvv' => '322',
            'expiryMonth' => '11',
            'expiryYear' => '15',
            'firstName' => 'John Doe',
            'lastName' => '',
        ], $details['card']->peek());
    }

    /**
     * @test
     */
    public function shouldNotOverwriteAlreadySetExtraDetails(): void
    {
        $gatewayMock = $this->createMock(GatewayInterface::class);
        $gatewayMock
            ->expects($this->once())
            ->method('execute')
            ->with($this->isInstanceOf(GetCurrency::class))
            ->willReturnCallback(function(GetCurrency $request) {
                $request->name = 'US Dollar';
                $request->alpha3 = 'USD';
                $request->numeric = 123;
                $request->exp = 2;
                $request->country = 'US';
            })
        ;

        $payment = new Payment;
        $payment->setCurrencyCode('USD');
        $payment->setTotalAmount(123);
        $payment->setDescription('the description');
        $payment->setDetails([
            'foo' => 'fooVal',
        ]);

        $action = new ConvertPaymentAction;
        $action->setGateway($gatewayMock);

        $action->execute($convert = new Convert($payment, 'array'));
        $details = $convert->getResult();

        self::assertNotEmpty($details);

        self::assertArrayHasKey('foo', $details);
        self::assertEquals('fooVal', $details['foo']);
    }

    /**
     * @test
     */
    public function couldBeConstructedWithoutAnyArguments(): void
    {
        self::assertNotNull(new $this->actionClass());
    }
}
