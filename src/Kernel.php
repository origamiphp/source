<?php

declare(strict_types=1);

namespace App;

use App\DependencyInjection\Compiler\CommandPass;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

/**
 * @codeCoverageIgnore
 */
class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    /**
     * {@inheritdoc}
     */
    public function getProjectDir(): string
    {
        return \dirname(__DIR__);
    }

    /**
     * {@inheritdoc}
     */
    protected function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new CommandPass());
    }

    /**
     * {@inheritdoc}
     */
    protected function configureRoutes(): void
    {
    }
}
