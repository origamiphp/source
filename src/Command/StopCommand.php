<?php

declare(strict_types=1);

namespace App\Command;

use App\Helper\CommandExitCode;
use App\Manager\ApplicationLock;
use App\Manager\EnvironmentVariables;
use App\Manager\ProcessManager;
use App\Traits\CustomCommandsTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class StopCommand extends Command
{
    use CustomCommandsTrait;

    /**
     * StopCommand constructor.
     *
     * @param string|null          $name
     * @param ApplicationLock      $applicationLock
     * @param EnvironmentVariables $environmentVariables
     * @param ValidatorInterface   $validator
     * @param ProcessManager       $processManager
     */
    public function __construct(
        ?string $name = null,
        ApplicationLock $applicationLock,
        EnvironmentVariables $environmentVariables,
        ValidatorInterface $validator,
        ProcessManager $processManager
    ) {
        parent::__construct($name);

        $this->applicationLock = $applicationLock;
        $this->environmentVariables = $environmentVariables;
        $this->validator = $validator;
        $this->processManager = $processManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('origami:stop');
        $this->setAliases(['stop']);

        $this->setDescription('Stops an environment previously started');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $this->io = new SymfonyStyle($input, $output);

        if ($this->project = $this->applicationLock->getCurrentLock()) {
            try {
                $this->checkEnvironmentConfiguration(true);
                $environmentVariables = $this->environmentVariables->getRequiredVariables($this->project);

                if ($this->processManager->stopDockerServices($environmentVariables)) {
                    $this->io->success('Docker services successfully stopped.');
                } else {
                    $this->io->error('An error occurred while stoppping the Docker services.');
                }

                if ($this->processManager->stopDockerSynchronization($environmentVariables)) {
                    $this->io->success('Docker synchronization successfully stopped.');
                } else {
                    $this->io->error('An error occurred while stopping the Docker synchronization.');
                }

                $this->applicationLock->removeLock();
            } catch (\Exception $e) {
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
