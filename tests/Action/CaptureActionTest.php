<?php

namespace Payum\OmnipayV3Bridge\Tests\Action;

use Omnipay\Common\Message\AbstractResponse as OmnipayAbstractResponse;
use Omnipay\Common\Message\RequestInterface as OmnipayRequestInterface;
use Omnipay\Common\Message\ResponseInterface as OmnipayResponseInterface;
use Payum\Core\Exception\LogicException;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayInterface;
use Payum\Core\Model\CreditCard;
use Payum\Core\Request\Capture;
use Payum\Core\Request\ObtainCreditCard;
use Payum\Core\Security\GenericTokenFactoryAwareInterface;
use Payum\Core\Security\SensitiveValue;
use Payum\Core\Tests\GenericActionTest;
use Payum\OmnipayV3Bridge\Action\BaseApiAwareAction;
use Payum\OmnipayV3Bridge\Action\CaptureAction;
use Payum\OmnipayV3Bridge\Tests\CreditCardGateway;
use Payum\OmnipayV3Bridge\Tests\OffsiteGateway;
use PHPUnit\Framework\MockObject\MockObject;

class CaptureActionTest extends GenericActionTest
{
    protected $actionClass = CaptureAction::class;

    protected $requestClass = Capture::class;

    /**
     * @var  CaptureAction
     */
    protected $action;

    protected function setUp(): void
    {
        $this->action = new $this->actionClass();
        $this->action->setApi(new CreditCardGateway());
    }

    /**
     * @test
     */
    public function shouldBeSubClassOfBaseApiAwareAction(): void
    {
        $rc = new \ReflectionClass(CaptureAction::class);

        self::assertTrue($rc->isSubclassOf(BaseApiAwareAction::class));
    }

    /**
     * @test
     */
    public function shouldImplementInterfaceGatewayAwareAction(): void
    {
        $rc = new \ReflectionClass(CaptureAction::class);

        self::assertTrue($rc->implementsInterface(GatewayAwareInterface::class));
    }

    /**
     * @test
     */
    public function shouldImplementInterfaceGenericTokenFactoryAwareInterface(): void
    {
        $rc = new \ReflectionClass(CaptureAction::class);

        self::assertTrue($rc->implementsInterface(GenericTokenFactoryAwareInterface::class));
    }

    public function shouldNotSupportIfOffsiteOmnipayGatewaySetAsApi(): void
    {
        $this->action->setApi(new OffsiteGateway());

        self::assertFalse($this->action->supports(new Capture([])));
        self::assertFalse($this->action->supports(new Capture(new \ArrayObject())));
    }

    /**
     * @test
     *
     * @dataProvider provideDetails
     *
     * @expectedException \LogicException
     * @expectedExceptionMessage The bridge supports only responses which extends AbstractResponse. Their ResponseInterface is useless.
     */
    public function throwsIfPurchaseMethodReturnResponseNotInstanceOfAbstractResponse($details): void
    {
        $requestMock = $this->createMock(OmnipayRequestInterface::class);
        $requestMock
            ->expects($this->once())
            ->method('send')
            ->willReturn($this->createMock(OmnipayResponseInterface::class));

        $gateway = new CreditCardGateway();
        $gateway->returnOnPurchase = $requestMock;

        $action = new CaptureAction;
        $action->setApi($gateway);
        $action->setGateway($this->createGatewayMock());

        $action->execute(new Capture($details));
    }

    /**
     * @test
     *
     * @dataProvider provideDetails
     */
    public function shouldCallGatewayPurchaseMethodWithExpectedArguments($details): void
    {
        $responseMock = $this->createMock(OmnipayAbstractResponse::class, [], [], '', false);
        $responseMock
            ->expects($this->once())
            ->method('getData')
            ->willReturn([]);

        $requestMock = $this->createMock(OmnipayRequestInterface::class);
        $requestMock
            ->expects($this->once())
            ->method('send')
            ->willReturn($responseMock);

        $gateway = new CreditCardGateway();
        $gateway->returnOnPurchase = $requestMock;

        $action = new CaptureAction;
        $action->setApi($gateway);
        $action->setGateway($this->createGatewayMock());

        $action->execute(new Capture($details));
    }

    /**
     * @test
     */
    public function shouldObtainCreditCardAndPopulateCardFieldIfNotSet(): void
    {
        $firstModel = new \stdClass();
        $model = new \ArrayObject([]);

        $responseMock = $this->createMock(OmnipayAbstractResponse::class, [], [], '', false);
        $responseMock
            ->expects($this->once())
            ->method('getData')
            ->willReturn([]);

        $requestMock = $this->createMock(OmnipayRequestInterface::class);
        $requestMock
            ->expects($this->once())
            ->method('send')
            ->willReturn($responseMock);

        $omnipayGateway = $this->createMock(CreditCardGateway::class, [], [], '', false);
        $omnipayGateway
            ->expects($this->once())
            ->method('purchase')
            ->with([
                'card' => [
                    'number' => '4111111111111111',
                    'cvv' => '123',
                    'expiryMonth' => '11',
                    'expiryYear' => '10',
                    'firstName' => 'John Doe',
                    'lastName' => '',
                ],
                'clientIp' => '',
            ])
            ->willReturn($requestMock);

        $gateway = $this->createGatewayMock();
        $gateway
            ->expects($this->at(0))
            ->method('execute')
            ->with($this->isInstanceOf(ObtainCreditCard::class))
            ->willReturnCallback(function (ObtainCreditCard $request) use ($firstModel, $model) {
                self::assertSame($firstModel, $request->getFirstModel());
                self::assertSame($model, $request->getModel());

                $card = new CreditCard();
                $card->setExpireAt(new \DateTime('2010-11-12'));
                $card->setHolder('John Doe');
                $card->setNumber('4111111111111111');
                $card->setSecurityCode('123');

                $request->set($card);
            });

        $action = new CaptureAction;
        $action->setApi($omnipayGateway);
        $action->setGateway($gateway);

        $capture = new Capture($firstModel);
        $capture->setModel($model);

        $action->execute($capture);

        $details = iterator_to_array($model);

        self::assertArrayNotHasKey('cardReference', $details);
        self::assertArrayHasKey('card', $details);
        self::assertInstanceOf(SensitiveValue::class, $details['card']);
        self::assertNull($details['card']->peek(), 'The card must be already erased');
    }

    /**
     * @test
     */
    public function shouldObtainCreditCardAndPopulateCardReferenceFieldIfNotSet(): void
    {
        $firstModel = new \stdClass();
        $model = new \ArrayObject([]);

        $responseMock = $this->createMock(OmnipayAbstractResponse::class, [], [], '', false);
        $responseMock
            ->expects($this->once())
            ->method('getData')
            ->willReturn([]);

        $requestMock = $this->createMock(OmnipayRequestInterface::class);
        $requestMock
            ->expects($this->once())
            ->method('send')
            ->willReturn($responseMock);

        $omnipayGateway = $this->createMock(CreditCardGateway::class, [], [], '', false);
        $omnipayGateway
            ->expects($this->once())
            ->method('purchase')
            ->with([
                'cardReference' => 'theCardToken',
                'clientIp' => '',
            ])
            ->willReturn($requestMock);

        $gateway = $this->createGatewayMock();
        $gateway
            ->expects($this->at(0))
            ->method('execute')
            ->with($this->isInstanceOf(ObtainCreditCard::class))
            ->willReturnCallback(function (ObtainCreditCard $request) use ($firstModel, $model) {
                self::assertSame($firstModel, $request->getFirstModel());
                self::assertSame($model, $request->getModel());

                $card = new CreditCard();
                $card->setToken('theCardToken');

                $request->set($card);
            });

        $action = new CaptureAction;
        $action->setApi($omnipayGateway);
        $action->setGateway($gateway);

        $capture = new Capture($firstModel);
        $capture->setModel($model);

        $action->execute($capture);

        $details = iterator_to_array($model);

        self::assertArrayNotHasKey('card', $details);
        self::assertArrayHasKey('cardReference', $details);
        self::assertEquals('theCardToken', $details['cardReference']);
    }

    /**
     * @test
     *
     * @expectedException \Payum\Core\Exception\LogicException
     * @expectedExceptionMessage Credit card details has to be set explicitly or there has to be an action that supports ObtainCreditCard request.
     */
    public function throwIfObtainCreditCardNotSupported(): void
    {
        $omnipayGateway = $this->createMock(CreditCardGateway::class, [], [], '', false);
        $omnipayGateway
            ->expects($this->never())
            ->method('purchase');

        $gateway = $this->createGatewayMock();
        $gateway
            ->expects($this->at(0))
            ->method('execute')
            ->with($this->isInstanceOf(ObtainCreditCard::class))
            ->willThrowException(new RequestNotSupportedException());

        $action = new CaptureAction;
        $action->setApi($omnipayGateway);
        $action->setGateway($gateway);

        $action->execute(new Capture([]));
    }

    /**
     * @test
     */
    public function shouldDoNothingIfStatusAlreadySet(): void
    {
        $gatewayMock = $this->createMock(CreditCardGateway::class);
        $gatewayMock
            ->expects($this->never())
            ->method('purchase');
        $gatewayMock
            ->expects($this->never())
            ->method('completePurchase');

        $action = new CaptureAction;
        $action->setApi($gatewayMock);
        $action->setGateway($this->createGatewayMock());

        $action->execute(new Capture([
            '_status' => 'foo',
        ]));
    }

    /**
     * @return MockObject|GatewayInterface
     */
    protected function createGatewayMock()
    {
        return $this->createMock(GatewayInterface::class, ['execute']);
    }

    public static function provideDetails(): array
    {
        return [
            [
                [
                    'foo' => 'fooVal',
                    'bar' => 'barVal',
                    'card' => ['cvv' => 123],
                    'clientIp' => '',
                ],
            ],
            [
                [
                    'foo' => 'fooVal',
                    'bar' => 'barVal',
                    'cardReference' => 'abc',
                    'clientIp' => '',
                ],
            ],
        ];
    }

    /**
     * @test
     */
    public function couldBeConstructedWithoutAnyArguments(): void
    {
        self::assertNotNull(new $this->actionClass());
    }
}
