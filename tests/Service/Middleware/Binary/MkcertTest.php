<?php

declare(strict_types=1);

namespace App\Tests\Service\Middleware\Binary;

use App\Exception\MkcertException;
use App\Service\Middleware\Binary\Mkcert;
use App\Service\Wrapper\ProcessFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Process\Process;

/**
 * @internal
 *
 * @covers \App\Service\Middleware\Binary\Mkcert
 */
final class MkcertTest extends TestCase
{
    use ProphecyTrait;

    public function testItRetrievesBinaryVersion(): void
    {
        $processFactory = $this->prophesize(ProcessFactory::class);

        $process = $this->prophesize(Process::class);
        $process->getOutput()
            ->shouldBeCalledOnce()
            ->willReturn('mkcert version')
        ;

        $processFactory->runBackgroundProcess(Argument::type('array'))
            ->shouldBeCalledOnce()
            ->willReturn($process)
        ;

        $mkcert = new Mkcert($processFactory->reveal());
        static::assertSame('mkcert version', $mkcert->getVersion());
    }

    public function testItGeneratesCertificate(): void
    {
        $installProcess = $this->prophesize(Process::class);

        $processFactory = $this->prophesize(ProcessFactory::class);
        $processFactory
            ->runBackgroundProcess(['mkcert', '-install'])
            ->shouldBeCalledOnce()
            ->willReturn($installProcess->reveal())
        ;

        $installProcess
            ->isSuccessful()
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $generateProcess = $this->prophesize(Process::class);
        $generateCommand = ['mkcert', '-cert-file', './nginx/certs/custom.pem', '-key-file', './nginx/certs/custom.key', 'localhost', '127.0.0.1', '::1'];

        $processFactory
            ->runBackgroundProcess($generateCommand)
            ->shouldBeCalledOnce()
            ->willReturn($generateProcess->reveal())
        ;

        $generateProcess
            ->isSuccessful()
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $mkcert = new Mkcert($processFactory->reveal());
        static::assertTrue($mkcert->generateCertificate('.'));
    }

    public function testItThrowsAnExceptionWhenInstallFails(): void
    {
        $installProcess = $this->prophesize(Process::class);

        $processFactory = $this->prophesize(ProcessFactory::class);
        $processFactory
            ->runBackgroundProcess(['mkcert', '-install'])
            ->shouldBeCalledOnce()
            ->willReturn($installProcess->reveal())
        ;

        $installProcess
            ->isSuccessful()
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;

        $installProcess
            ->getOutput()
            ->shouldBeCalledOnce()
        ;

        $this->expectException(MkcertException::class);

        $mkcert = new Mkcert($processFactory->reveal());
        static::assertTrue($mkcert->generateCertificate('.'));
    }
}
