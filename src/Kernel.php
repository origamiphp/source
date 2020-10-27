<?php

declare(strict_types=1);

namespace App;

use App\DependencyInjection\Compiler\CommandPass;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
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
    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->import('../config/{packages}/*.php');
        $container->import('../config/{packages}/'.$this->environment.'/*.php');
        $container->import('../config/services.php');
        $container->import('../config/{services}_'.$this->environment.'.php');
    }

    /**
     * {@inheritdoc}
     */
    protected function configureRoutes(): void
    {
    }
}
