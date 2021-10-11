<?php

namespace Payum\OmnipayV3Bridge\Tests\Action;

use Omnipay\Common\GatewayInterface;
use Payum\Core\Exception\UnsupportedApiException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Payum\OmnipayV3Bridge\Action\BaseApiAwareAction;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use ReflectionClass;
use stdClass;

class BaseApiAwareActionTest extends TestCase
{
    /**
     * @test
     */
    public function shouldImplementActionInterface(): void
    {
        $rc = new ReflectionClass(BaseApiAwareAction::class);

        self::assertTrue($rc->isSubclassOf(ActionInterface::class));
    }

    /**
     * @test
     */
    public function shouldImplementApiAwareInterface(): void
    {
        $rc = new ReflectionClass(BaseApiAwareAction::class);

        self::assertTrue($rc->isSubclassOf(ApiAwareInterface::class));
    }

    /**
     * @test
     */
    public function shouldBeAbstract(): void
    {
        $rc = new ReflectionClass(BaseApiAwareAction::class);

        self::assertTrue($rc->isAbstract());
    }

    /**
     * @test
     */
    public function shouldAllowSetApi(): void
    {
        $expectedApi = $this->createGatewayMock();

        $action = $this->getMockForAbstractClass(BaseApiAwareAction::class);

        $action->setApi($expectedApi);

        $gateway = new \ReflectionProperty($action, 'omnipayGateway');
        $gateway->setAccessible(true);

        self::assertEquals($expectedApi, $gateway->getValue($action));
    }

    /**
     * @test
     *
     * @expectedException \Payum\Core\Exception\UnsupportedApiException
     */
    public function throwIfUnsupportedApiGiven(): void
    {
        $action = $this->getMockForAbstractClass(BaseApiAwareAction::class);

        $action->setApi(new stdClass);
    }

    /**
     * @return MockObject|GatewayInterface
     */
    protected function createGatewayMock()
    {
        return $this->createMock(GatewayInterface::class);
    }
}
