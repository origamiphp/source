<?php

declare(strict_types=1);

namespace App\DependencyInjection\Compiler;

use App\Command\AbstractBaseCommand;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @codeCoverageIgnore
 */
class CommandPass implements CompilerPassInterface
{
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
