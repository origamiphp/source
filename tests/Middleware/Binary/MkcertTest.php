<?php

declare(strict_types=1);

namespace App\Tests\Middleware\Binary;

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

        $process = $this->prophesize(Process::class);
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
