<?php
namespace Payum\OmnipayV3Bridge\Tests;

use Omnipay\Common\GatewayInterface as OmnipayGatewayInterface;
use Payum\Core\Exception\LogicException;
use Payum\Core\Gateway;
use Payum\Core\GatewayFactoryInterface;
use Payum\OmnipayV3Bridge\OmnipayGatewayFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OmnipayGatewayFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function shouldImplementGatewayFactoryInterface(): void
    {
        $rc = new \ReflectionClass(OmnipayGatewayFactory::class);

        $this->assertTrue($rc->implementsInterface(GatewayFactoryInterface::class));
    }

    /**
     * @test
     */
    public function couldBeConstructedWithoutAnyArguments(): void
    {
        self::assertNotNull(new OmnipayGatewayFactory());
    }

    /**
     * @test
     *
     * @throws \ReflectionException
     */
    public function shouldAllowCreateGatewayWithTypeGivenInConfig(): void
    {
        $factory = new OmnipayGatewayFactory();

        $gateway = $factory->create(['type' => 'Dummy']);

        self::assertInstanceOf(Gateway::class, $gateway);


        $apis = new \ReflectionProperty($gateway, 'apis');
        $apis->setAccessible(true);
        self::assertNotEmpty($apis->getValue($gateway));

        $actions = new \ReflectionProperty($gateway, 'actions');
        $actions->setAccessible(true);
        self::assertNotEmpty($actions->getValue($gateway));

        $extCollection = new \ReflectionProperty($gateway, 'extensions');
        $extCollection->setAccessible(true);
        $extCollectionValue = $extCollection->getValue($gateway);
        $gatewayExtensions = new \ReflectionProperty($extCollectionValue, 'extensions');
        $gatewayExtensions->setAccessible(true);
        self::assertNotEmpty($gatewayExtensions->getValue($extCollectionValue));
    }

    /**
     * @test
     */
    public function shouldAllowCreateGatewayWithCustomGateway(): void
    {
        $factory = new OmnipayGatewayFactory();

        $gateway = $factory->create([
            'payum.api' => $this->createGatewayMock(),
        ]);

        self::assertInstanceOf(Gateway::class, $gateway);


        $apis = new \ReflectionProperty($gateway, 'apis');
        $apis->setAccessible(true);
        self::assertNotEmpty($apis->getValue($gateway));

        $actions = new \ReflectionProperty($gateway, 'actions');
        $actions->setAccessible(true);
        self::assertNotEmpty($actions->getValue($gateway));

        $extCollection = new \ReflectionProperty($gateway, 'extensions');
        $extCollection->setAccessible(true);
        $extCollectionValue = $extCollection->getValue($gateway);
        $gatewayExtensions = new \ReflectionProperty($extCollectionValue, 'extensions');
        $gatewayExtensions->setAccessible(true);
        self::assertNotEmpty($gatewayExtensions->getValue($extCollectionValue));
    }

    /**
     * @test
     *
     * @expectedException \Payum\Core\Exception\LogicException
     * @expectedExceptionMessage The type fields are required.
     */
    public function shouldThrowIfRequiredOptionsNotPassed(): void
    {
        $factory = new OmnipayGatewayFactory();

        $factory->create();
    }

    /**
     * @test
     */
    public function shouldAllowCreateGatewayConfig(): void
    {
        $factory = new OmnipayGatewayFactory();

        $config = $factory->createConfig();

        self::assertIsArray($config);
        self::assertNotEmpty($config);
    }

    /**
     * @test
     *
     * @expectedException \Payum\Core\Exception\LogicException
     * @expectedExceptionMessage Given omnipay gateway type Invalid or class is not supported.
     */
    public function shouldThrowIfTypeNotValid(): void
    {
        $factory = new OmnipayGatewayFactory();

        $factory->create(['type' => 'Invalid']);
    }

    /**
     * @return MockObject|OmnipayGatewayInterface
     */
    protected function createGatewayMock()
    {
        return $this->createMock(OmnipayGatewayInterface::class);
    }
}
