<?php

declare(strict_types=1);

namespace App\Command;

use App\Event\EnvironmentInstalledEvent;
use App\Exception\OrigamiExceptionInterface;
use App\Service\Setup\ConfigurationFiles;
use App\Service\Setup\EnvironmentBuilder;
use App\Service\Wrapper\OrigamiStyle;
use App\ValueObject\EnvironmentEntity;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[AsCommand(
    name: 'origami:install',
    description: 'Installs a local Docker environment for the project in the current directory'
)]
class InstallCommand extends AbstractBaseCommand
{
    public function __construct(
        private EnvironmentBuilder $builder,
        private ConfigurationFiles $configuration,
        private EventDispatcherInterface $eventDispatcher,
        string $name = null
    ) {
        parent::__construct($name);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new OrigamiStyle($input, $output);

        try {
            $userInputs = $this->builder->prepare($io);
            $environment = new EnvironmentEntity(
                $userInputs->getName(),
                $userInputs->getLocation(),
                $userInputs->getType(),
                $userInputs->getDomains()
            );

            $this->configuration->install($environment, $userInputs->getSettings());

            $event = new EnvironmentInstalledEvent($environment, $io);
            $this->eventDispatcher->dispatch($event);

            $io->success('Environment successfully installed.');
            $io->info(
                "You can now use the following commands to start the environment.\n"
                .'  * "origami start '.$environment->getName()."\" from any location.\n"
                .'  * "origami start" from this location ('.$environment->getLocation().').'
            );
        } catch (OrigamiExceptionInterface $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
