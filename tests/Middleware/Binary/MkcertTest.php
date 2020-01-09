<?php

declare(strict_types=1);

namespace App\Tests\Middleware\Binary;

use App\Helper\ProcessFactory;
use App\Middleware\Binary\Mkcert;
use Generator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

/**
 * @internal
 *
 * @covers \App\Middleware\Binary\Mkcert
 */
final class MkcertTest extends TestCase
{
    /**
     * @dataProvider provideCertificateDomains
     */
    public function testItGeneratesCertificate(array $domains): void
    {
        $process = $this->prophesize(Process::class);
        $process->isSuccessful()->shouldBeCalledOnce()->willReturn(true);

        $processFactory = $this->prophesize(ProcessFactory::class);
        $processFactory->runBackgroundProcess([...['mkcert', '-cert-file', './custom.pem', '-key-file', './custom.key'], ...$domains])
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

        yield [['symfony.localhost']];
        yield [['www.symfony.localhost']];
        yield [['symfony.localhost', 'www.symfony.localhost']];
    }
}
