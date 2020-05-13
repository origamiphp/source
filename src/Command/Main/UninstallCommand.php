<?php

declare(strict_types=1);

namespace App\Command\Main;

use App\Command\AbstractBaseCommand;
use App\Event\EnvironmentUninstalledEvent;
use App\Exception\InvalidEnvironmentException;
use App\Exception\OrigamiExceptionInterface;
use App\Helper\CommandExitCode;
use App\Helper\CurrentContext;
use App\Middleware\Binary\DockerCompose;
use App\Middleware\Configuration\ConfigurationUninstaller;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class UninstallCommand extends AbstractBaseCommand
{
    /** @var CurrentContext */
    private $currentContext;

    /** @var DockerCompose */
    private $dockerCompose;

    /** @var ConfigurationUninstaller */
    private $uninstaller;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    public function __construct(
        CurrentContext $currentContext,
        DockerCompose $dockerCompose,
        ConfigurationUninstaller $uninstaller,
        EventDispatcherInterface $eventDispatcher,
        ?string $name = null
    ) {
        parent::__construct($name);

        $this->currentContext = $currentContext;
        $this->dockerCompose = $dockerCompose;
        $this->uninstaller = $uninstaller;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setDescription('Uninstalls a specific environment');

        $this->addArgument(
            'environment',
            InputArgument::OPTIONAL,
            'Name of the environment to uninstall'
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $environment = $this->currentContext->getEnvironment($input);

            $question = sprintf(
                'Are you sure you want to uninstall the "%s" environment?',
                $environment->getName()
            );

            if ($io->confirm($question, false)) {
                if ($environment->isActive()) {
                    throw new InvalidEnvironmentException('Unable to uninstall a running environment.');
                }

                if (!$this->dockerCompose->removeServices()) {
                    throw new InvalidEnvironmentException('An error occurred while removing the Docker services.');
                }

                $event = new EnvironmentUninstalledEvent($environment, $io);
                $this->eventDispatcher->dispatch($event);

                $this->uninstaller->uninstall($environment);

                $io->success('Environment successfully uninstalled.');
            }
        } catch (OrigamiExceptionInterface $exception) {
            $io->error($exception->getMessage());
            $exitCode = CommandExitCode::EXCEPTION;
        }

        return $exitCode ?? CommandExitCode::SUCCESS;
    }
}
