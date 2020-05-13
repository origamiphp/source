<?php

declare(strict_types=1);

namespace App\Tests\Command\Contextual;

use App\Helper\CurrentContext;
use App\Middleware\Binary\DockerCompose;
use App\Tests\Command\AbstractCommandWebTestCase;
use App\Tests\TestFakeEnvironmentTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

abstract class AbstractContextualCommandWebTestCase extends AbstractCommandWebTestCase
{
    use TestFakeEnvironmentTrait;

    /** @var ObjectProphecy */
    protected $currentContext;

    /** @var ObjectProphecy */
    protected $dockerCompose;

    /** @var ObjectProphecy */
    protected $eventDispatcher;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->currentContext = $this->prophet->prophesize(CurrentContext::class);
        $this->dockerCompose = $this->prophet->prophesize(DockerCompose::class);
        $this->eventDispatcher = $this->prophet->prophesize(EventDispatcherInterface::class);
    }
}
