<?php

declare(strict_types=1);

namespace App\Command;

use App\Environment\Configuration\ConfigurationInstaller;
use App\Environment\EnvironmentMaker;
use App\Event\EnvironmentInstalledEvent;
use App\Exception\OrigamiExceptionInterface;
use App\Helper\OrigamiStyle;
use App\Helper\ProcessProxy;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class InstallCommand extends AbstractBaseCommand
{
    /** {@inheritdoc} */
    protected static $defaultName = 'origami:install';

    /** @var ProcessProxy */
    private $processProxy;

    /** @var EnvironmentMaker */
    private $configurator;

    /** @var ConfigurationInstaller */
    private $installer;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    public function __construct(
        ProcessProxy $processProxy,
        EnvironmentMaker $configurator,
        ConfigurationInstaller $installer,
        EventDispatcherInterface $eventDispatcher,
        ?string $name = null
    ) {
        parent::__construct($name);

        $this->processProxy = $processProxy;
        $this->configurator = $configurator;
        $this->installer = $installer;
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
        $io->note('The environment will be installed in the current directory.');

        try {
            $environment = $this->installer->install(
                $location = $this->processProxy->getWorkingDirectory(),
                $name = $this->configurator->askEnvironmentName($io, basename($location)),
                $type = $this->configurator->askEnvironmentType($io, $location),
                $this->configurator->askPhpVersion($io, $type),
                $this->configurator->askDatabaseVersion($io),
                $this->configurator->askDomains($io, $name)
            );

            $event = new EnvironmentInstalledEvent($environment, $io);
            $this->eventDispatcher->dispatch($event);

            $io->success('Environment successfully installed.');
            $io->info(
                "You can now use the following commands to start the environment.\n"
                ."  * \"origami start {$name}\" from any location.\n"
                ."  * \"origami start\" from this location ({$location})."
            );
        } catch (OrigamiExceptionInterface $exception) {
            $io->error($exception->getMessage());
            $exitCode = Command::FAILURE;
        }

        return $exitCode ?? Command::SUCCESS;
    }
}
