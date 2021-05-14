<?php

declare(strict_types=1);

namespace App\Command;

use App\Event\EnvironmentUninstalledEvent;
use App\Exception\InvalidEnvironmentException;
use App\Exception\OrigamiExceptionInterface;
use App\Helper\CurrentContext;
use App\Middleware\Binary\Docker;
use App\Service\ConfigurationFiles;
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

    private CurrentContext $currentContext;
    private Docker $docker;
    private ConfigurationFiles $uninstaller;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        CurrentContext $currentContext,
        Docker $docker,
        ConfigurationFiles $uninstaller,
        EventDispatcherInterface $eventDispatcher,
        ?string $name = null
    ) {
        parent::__construct($name);

        $this->currentContext = $currentContext;
        $this->docker = $docker;
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
            $this->currentContext->loadEnvironment($input);
            $environment = $this->currentContext->getActiveEnvironment();

            $question = sprintf(
                'Are you sure you want to uninstall the "%s" environment?',
                $environment->getName()
            );

            if ($io->confirm($question, false)) {
                try {
                    if (!$this->docker->removeServices()) {
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

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
