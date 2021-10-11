<?php
namespace Payum\OmnipayV3Bridge\Tests\Action;

use Payum\Core\Request\GetBinaryStatus;
use Payum\Core\Tests\GenericActionTest;
use Payum\OmnipayV3Bridge\Action\StatusAction;
use Payum\Core\Request\GetHumanStatus;

class StatusActionTest extends GenericActionTest
{
    protected $actionClass = StatusAction::class;

    protected $requestClass = GetHumanStatus::class;

    /**
     * @test
     */
    public function shouldMarkUnknownIfStatusNotSupported(): void
    {
        $action = new StatusAction();

        $status = new GetBinaryStatus([
            '_status' => 'not-supported-status',
        ]);

        //guard
        $status->markNew();

        $action->execute($status);

        self::assertTrue($status->isUnknown());
    }

    /**
     * @test
     */
    public function shouldMarkNewIfDetailsEmpty(): void
    {
        $action = new StatusAction();

        $status = new GetBinaryStatus([]);

        //guard
        $status->markUnknown();

        $action->execute($status);

        self::assertTrue($status->isNew());
    }

    /**
     * @test
     */
    public function shouldMarkNewIfOrderStatusNotSet(): void
    {
        $action = new StatusAction();

        $status = new GetBinaryStatus([]);

        //guard
        $status->markUnknown();

        $action->execute($status);

        self::assertTrue($status->isNew());
    }

    /**
     * @test
     */
    public function shouldMarkCapturedIfStatusCaptured(): void
    {
        $action = new StatusAction();

        $status = new GetBinaryStatus([
            '_status' => 'captured',
        ]);

        //guard
        $status->markUnknown();

        $action->execute($status);

        self::assertTrue($status->isCaptured());
    }

    /**
     * @test
     */
    public function shouldMarkFailedIfStatusFailed(): void
    {
        $action = new StatusAction();

        $status = new GetBinaryStatus([
            '_status' => 'failed',
        ]);

        //guard
        $status->markUnknown();

        $action->execute($status);

        self::assertTrue($status->isFailed());
    }

    /**
     * @test
     */
    public function couldBeConstructedWithoutAnyArguments(): void
    {
        self::assertNotNull(new $this->actionClass());
    }
}
