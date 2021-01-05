<?php

declare(strict_types=1);

namespace App\Tests\Middleware\Binary;

use App\Exception\MkcertException;
use App\Helper\ProcessFactory;
use App\Middleware\Binary\Mkcert;
use App\Tests\CustomProphecyTrait;
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
    use CustomProphecyTrait;

    /**
     * @dataProvider provideCertificateDomains
     */
    public function testItGeneratesCertificate(array $domains): void
    {
        [$processFactory] = $this->prophesizeObjectArguments();

        $installProcess = $this->prophesize(Process::class);
        $installCommand = ['mkcert', '-install'];

        $processFactory->runBackgroundProcess($installCommand)->shouldBeCalledOnce()->willReturn($installProcess->reveal());
        $installProcess->isSuccessful()->shouldBeCalledOnce()->willReturn(true);

        $generateProcess = $this->prophesize(Process::class);
        $generateCommand = array_merge(
            ['mkcert', '-cert-file', './custom.pem', '-key-file', './custom.key', 'localhost', '127.0.0.1', '::1'],
            $domains
        );

        $processFactory->runBackgroundProcess($generateCommand)->shouldBeCalledOnce()->willReturn($generateProcess->reveal());
        $generateProcess->isSuccessful()->shouldBeCalledOnce()->willReturn(true);

        $mkcert = new Mkcert($processFactory->reveal());
        static::assertTrue($mkcert->generateCertificate('./custom.pem', './custom.key', $domains));
    }

    /**
     * @dataProvider provideCertificateDomains
     */
    public function testItThrowsAnExceptionWhenInstallFails(array $domains): void
    {
        [$processFactory] = $this->prophesizeObjectArguments();

        $installProcess = $this->prophesize(Process::class);
        $installCommand = ['mkcert', '-install'];

        $processFactory->runBackgroundProcess($installCommand)->shouldBeCalledOnce()->willReturn($installProcess->reveal());
        $installProcess->isSuccessful()->shouldBeCalledOnce()->willReturn(false);
        $installProcess->getOutput()->shouldBeCalledOnce();

        $this->expectException(MkcertException::class);

        $mkcert = new Mkcert($processFactory->reveal());
        static::assertTrue($mkcert->generateCertificate('./custom.pem', './custom.key', $domains));
    }

    public function provideCertificateDomains(): Generator
    {
        yield 'Single domain' => [['symfony.localhost']];
        yield 'Multiple domains' => [['symfony.localhost', '*.symfony.localhost']];
    }

    /**
     * {@inheritdoc}
     */
    protected function prophesizeObjectArguments(): array
    {
        return [
            $this->prophesize(ProcessFactory::class),
        ];
    }
}
