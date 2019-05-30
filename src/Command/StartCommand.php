<?php

declare(strict_types=1);

namespace App\Command;

use App\Event\EnvironmentStartedEvent;
use App\Exception\EnvironmentException;
use App\Helper\CommandExitCode;
use App\Manager\ApplicationLock;
use App\Manager\EnvironmentVariables;
use App\Manager\ProcessManager;
use App\Traits\CustomCommandsTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class StartCommand extends Command
{
    use CustomCommandsTrait;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /**
     * StartCommand constructor.
     *
     * @param string|null              $name
     * @param ApplicationLock          $applicationLock
     * @param EnvironmentVariables     $environmentVariables
     * @param ValidatorInterface       $validator
     * @param ProcessManager           $processManager
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        ?string $name = null,
        ApplicationLock $applicationLock,
        EnvironmentVariables $environmentVariables,
        ValidatorInterface $validator,
        ProcessManager $processManager,
        EventDispatcherInterface $eventDispatcher
    ) {
        parent::__construct($name);

        $this->applicationLock = $applicationLock;
        $this->environmentVariables = $environmentVariables;
        $this->validator = $validator;
        $this->processManager = $processManager;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('origami:start');
        $this->setAliases(['start']);

        $this->addOption(
            'force',
            'f',
            InputOption::VALUE_NONE,
            'Forces the startup of the environment'
        );

        $this->setDescription('Starts an environment previously installed in the current directory');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $this->io = new SymfonyStyle($input, $output);

        try {
            if ($cwd = getcwd()) {
                $this->project = $cwd;
            } else {
                throw new EnvironmentException('Unable to retrieve the current working directory.');
            }

            $lock = $this->applicationLock->getCurrentLock();
            if (!$lock || $input->getOption('force')) {
                $this->checkEnvironmentConfiguration(true);
                $environmentVariables = $this->environmentVariables->getRequiredVariables($this->project);

                if ($this->processManager->startDockerServices($environmentVariables)) {
                    $this->io->success('Docker services successfully started.');

                    $event = new EnvironmentStartedEvent($environmentVariables, $this->io);
                    $this->eventDispatcher->dispatch($event);
                } else {
                    $this->io->error('An error occurred while starting the Docker services.');
                }

                $this->applicationLock->generateLock($this->project);
            } else {
                $this->io->error(
                    $lock === $this->project
                        ? 'The environment is already running.'
                        : 'Unable to start an environment when another is still running.'
                );
                $exitCode = CommandExitCode::INVALID;
            }
        } catch (\Exception $e) {
            $this->io->error($e->getMessage());
            $exitCode = CommandExitCode::EXCEPTION;
        }

        return $exitCode ?? CommandExitCode::SUCCESS;
    }
}
