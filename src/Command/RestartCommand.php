<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Environment;
use App\Event\EnvironmentRestartedEvent;
use App\Exception\OrigamiExceptionInterface;
use App\Helper\CommandExitCode;
use App\Manager\EnvironmentManager;
use App\Manager\Process\DockerCompose;
use App\Traits\CustomCommandsTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RestartCommand extends Command
{
    use CustomCommandsTrait;

    /** @var DockerCompose */
    private $dockerCompose;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /**
     * RestartCommand constructor.
     *
     * @param EnvironmentManager       $environmentManager
     * @param ValidatorInterface       $validator
     * @param DockerCompose            $dockerCompose
     * @param EventDispatcherInterface $eventDispatcher
     * @param string|null              $name
     */
    public function __construct(
        EnvironmentManager $environmentManager,
        ValidatorInterface $validator,
        DockerCompose $dockerCompose,
        EventDispatcherInterface $eventDispatcher,
        ?string $name = null
    ) {
        parent::__construct($name);

        $this->environmentManager = $environmentManager;
        $this->validator = $validator;
        $this->dockerCompose = $dockerCompose;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('origami:restart');
        $this->setAliases(['restart']);

        $this->setDescription('Restarts an environment previously started');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $this->io = new SymfonyStyle($input, $output);

        $activeEnvironment = $this->environmentManager->getActiveEnvironment();
        if ($activeEnvironment instanceof Environment) {
            $this->environment = $activeEnvironment;

            try {
                $this->checkEnvironmentConfiguration(true);

                $environmentVariables = $this->getRequiredVariables($this->environment);
                if ($this->dockerCompose->restartDockerServices($environmentVariables)) {
                    $this->io->success('Docker services successfully restarted.');

                    $event = new EnvironmentRestartedEvent($this->environment, $environmentVariables, $this->io);
                    $this->eventDispatcher->dispatch($event);
                } else {
                    $this->io->error('An error occurred while restarting the Docker services.');
                }
            } catch (OrigamiExceptionInterface $e) {
                $this->io->error($e->getMessage());
                $exitCode = CommandExitCode::EXCEPTION;
            }
        } else {
            $this->io->error('There is no running environment.');
            $exitCode = CommandExitCode::INVALID;
        }

        return $exitCode ?? CommandExitCode::SUCCESS;
    }
}
