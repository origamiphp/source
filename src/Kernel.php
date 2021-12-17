<?php

declare(strict_types=1);

namespace App;

use App\Command\AbstractBaseCommand;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

/**
 * @codeCoverageIgnore
 */
class Kernel extends BaseKernel implements CompilerPassInterface
{
    use MicroKernelTrait;

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $commands = $container->findTaggedServiceIds('console.command');

        foreach (array_keys($commands) as $id) {
            if (!is_subclass_of($id, AbstractBaseCommand::class)) {
                continue;
            }

            if (!$defaultName = $id::getDefaultName()) {
                continue;
            }

            $alias = str_replace('origami:', '', $defaultName);
            $container->findDefinition($id)
                ->addTag('console.command', ['command' => $alias])
            ;
        }
    }
}
