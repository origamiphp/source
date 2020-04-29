<?php

declare(strict_types=1);

namespace App\Tests\Middleware\Binary;

use App\Helper\ProcessFactory;
use App\Middleware\Binary\Mkcert;
use Generator;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophet;
use Symfony\Component\Process\Process;

/**
 * @internal
 *
 * @covers \App\Middleware\Binary\Mkcert
 */
final class MkcertTest extends TestCase
{
    /** @var Prophet */
    protected $prophet;

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

    /**
     * @dataProvider provideCertificateDomains
     */
    public function testItGeneratesCertificate(array $domains): void
    {
        $process = $this->prophet->prophesize(Process::class);
        $processFactory = $this->prophet->prophesize(ProcessFactory::class);

        (new MethodProphecy($process, 'isSuccessful', []))
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $command = array_merge(['mkcert', '-cert-file', './custom.pem', '-key-file', './custom.key'], $domains);
        (new MethodProphecy($processFactory, 'runBackgroundProcess', [$command]))
            ->shouldBeCalledOnce()
            ->willReturn($process->reveal())
        ;

        $mkcert = new Mkcert($processFactory->reveal());
        static::assertTrue($mkcert->generateCertificate('./custom.pem', './custom.key', $domains));
    }

    public function provideCertificateDomains(): Generator
    {
        yield [['magento2.localhost']];
        yield [['www.magento2.localhost']];
        yield [['magento2.localhost', 'www.magento2.localhost']];

        yield [['sylius.localhost']];
        yield [['www.sylius.localhost']];
        yield [['sylius.localhost', 'www.sylius.localhost']];

        yield [['symfony.localhost']];
        yield [['www.symfony.localhost']];
        yield [['symfony.localhost', 'www.symfony.localhost']];
    }
}
