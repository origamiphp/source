<?php

declare(strict_types=1);

namespace App\Command;

use App\Environment\EnvironmentFactory;
use App\Event\EnvironmentInstalledEvent;
use App\Exception\OrigamiExceptionInterface;
use App\Helper\OrigamiStyle;
use App\Service\ConfigurationFiles;
use App\Service\EnvironmentBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class InstallCommand extends AbstractBaseCommand
{
    /** {@inheritdoc} */
    protected static $defaultName = 'origami:install';

    private EnvironmentBuilder $builder;
    private ConfigurationFiles $configuration;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        EnvironmentBuilder $builder,
        ConfigurationFiles $configuration,
        EventDispatcherInterface $eventDispatcher,
        ?string $name = null
    ) {
        parent::__construct($name);

        $this->builder = $builder;
        $this->configuration = $configuration;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setDescription('Installs a Docker environment in the desired directory');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new OrigamiStyle($input, $output);

        try {
            $userInputs = $this->builder->prepare($io);
            $environment = EnvironmentFactory::createEntityFromUserInputs($userInputs);

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
