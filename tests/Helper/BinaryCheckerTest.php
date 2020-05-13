<?php

declare(strict_types=1);

namespace App\Tests\Helper;

use App\Helper\BinaryChecker;
use App\Helper\ProcessFactory;
use App\Helper\ProcessProxy;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophet;
use Psr\Log\LoggerInterface;

/**
 * @internal
 *
 * @covers \App\Helper\BinaryChecker
 *
 * @uses \App\Helper\ProcessFactory
 */
final class BinaryCheckerTest extends TestCase
{
    /** @var Prophet */
    private $prophet;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->prophet = new Prophet();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->prophet->checkPredictions();
    }

    public function testItChecksInstalledBinary(): void
    {
        $binaryChecker = new BinaryChecker(
            new ProcessFactory(
                $this->prophet->prophesize(ProcessProxy::class)->reveal(),
                $this->prophet->prophesize(LoggerInterface::class)->reveal()
            )
        );

        static::assertTrue($binaryChecker->isInstalled('php'));
        static::assertFalse($binaryChecker->isInstalled('azerty'));
    }
}
