<?php

declare(strict_types=1);

namespace App\Command;

use App\Environment\Configuration\ConfigurationUninstaller;
use App\Event\EnvironmentUninstalledEvent;
use App\Exception\InvalidEnvironmentException;
use App\Exception\OrigamiExceptionInterface;
use App\Helper\CurrentContext;
use App\Middleware\Binary\DockerCompose;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class UninstallCommand extends AbstractBaseCommand
{
    /** {@inheritdoc} */
    protected static $defaultName = 'origami:uninstall';

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

                try {
                    $this->currentContext->setActiveEnvironment($environment);
                    if (!$this->dockerCompose->removeServices()) {
                        throw new InvalidEnvironmentException('An error occurred while removing the Docker services.');
                    }
                } catch (OrigamiExceptionInterface $exception) {
                    $io->warning($exception->getMessage());
                }

                $this->uninstaller->uninstall($environment);

                $event = new EnvironmentUninstalledEvent($environment, $io);
                $this->eventDispatcher->dispatch($event);

                $io->success('Environment successfully uninstalled.');
            }
        } catch (OrigamiExceptionInterface $exception) {
            $io->error($exception->getMessage());
            $exitCode = Command::FAILURE;
        }

        return $exitCode ?? Command::SUCCESS;
    }
}
