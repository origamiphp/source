<?php

declare(strict_types=1);

namespace App\Command\Additional;

use App\Command\AbstractBaseCommand;
use App\Environment\EnvironmentEntity;
use App\Event\EnvironmentInstalledEvent;
use App\Exception\OrigamiExceptionInterface;
use App\Helper\CommandExitCode;
use App\Helper\ProcessProxy;
use App\Middleware\Configuration\ConfigurationInstaller;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class RegisterCommand extends AbstractBaseCommand
{
    /** @var ProcessProxy */
    private $processProxy;

    /** @var ConfigurationInstaller */
    private $installer;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    public function __construct(
        ProcessProxy $processProxy,
        ConfigurationInstaller $installer,
        EventDispatcherInterface $eventDispatcher,
        ?string $name = null
    ) {
        parent::__construct($name);

        $this->processProxy = $processProxy;
        $this->installer = $installer;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setDescription('Register an external environment which was not created by Origami.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            if ($io->confirm('Do you want to register the current directory as a custom environment?', false)) {
                $location = $this->processProxy->getWorkingDirectory();
                $name = $this->askEnvironmentName($io, basename($location));

                $environment = $this->installer->install($name, $location, EnvironmentEntity::TYPE_CUSTOM);

                $event = new EnvironmentInstalledEvent($environment, $io);
                $this->eventDispatcher->dispatch($event);

                $io->success('Environment successfully registered.');
            }
        } catch (OrigamiExceptionInterface $exception) {
            $io->error($exception->getMessage());
            $exitCode = CommandExitCode::EXCEPTION;
        }

        return $exitCode ?? CommandExitCode::SUCCESS;
    }

    /**
     * Asks the question about the environment name.
     */
    private function askEnvironmentName(SymfonyStyle $io, string $defaultName): string
    {
        return $io->ask('What is the name of the environment you want to install?', $defaultName);
    }
}
