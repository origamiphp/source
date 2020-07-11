<?php

declare(strict_types=1);

namespace App\Tests\Middleware\Binary;

use App\Helper\ProcessFactory;
use App\Middleware\Binary\Mkcert;
use Generator;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Process\Process;

/**
 * @internal
 *
 * @covers \App\Middleware\Binary\Mkcert
 */
final class MkcertTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @dataProvider provideCertificateDomains
     */
    public function testItGeneratesCertificate(array $domains): void
    {
        $process = $this->prophesize(Process::class);
        $processFactory = $this->prophesize(ProcessFactory::class);
        $command = array_merge(['mkcert', '-cert-file', './custom.pem', '-key-file', './custom.key'], $domains);

        $process->isSuccessful()->shouldBeCalledOnce()->willReturn(true);
        $processFactory->runBackgroundProcess($command)->shouldBeCalledOnce()->willReturn($process->reveal());

        $mkcert = new Mkcert($processFactory->reveal());
        static::assertTrue($mkcert->generateCertificate('./custom.pem', './custom.key', $domains));
    }

    public function provideCertificateDomains(): Generator
    {
        yield 'Magento domain' => [['magento2.localhost']];
        yield 'Sylius domain' => [['sylius.localhost']];
        yield 'Symfony domain' => [['symfony.localhost']];
    }
}
