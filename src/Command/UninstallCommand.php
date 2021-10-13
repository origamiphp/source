<?php

declare(strict_types=1);

namespace App\Command;

use App\Event\EnvironmentUninstalledEvent;
use App\Exception\InvalidEnvironmentException;
use App\Exception\OrigamiExceptionInterface;
use App\Service\ApplicationContext;
use App\Service\Middleware\Binary\Docker;
use App\Service\Setup\ConfigurationFiles;
use App\Service\Wrapper\OrigamiStyle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class UninstallCommand extends AbstractBaseCommand
{
    /** {@inheritdoc} */
    protected static $defaultName = 'origami:uninstall';
    /** {@inheritdoc} */
    protected static $defaultDescription = 'Uninstalls a specific environment';

    private ApplicationContext $applicationContext;
    private Docker $docker;
    private ConfigurationFiles $uninstaller;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        ApplicationContext $applicationContext,
        Docker $docker,
        ConfigurationFiles $uninstaller,
        EventDispatcherInterface $eventDispatcher,
        ?string $name = null
    ) {
        parent::__construct($name);

        $this->applicationContext = $applicationContext;
        $this->docker = $docker;
        $this->uninstaller = $uninstaller;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
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
        $io = new OrigamiStyle($input, $output);

        try {
            $this->applicationContext->loadEnvironment($input);
            $environment = $this->applicationContext->getActiveEnvironment();

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
