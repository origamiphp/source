<?php

declare(strict_types=1);

namespace App\Command\Services;

use App\Helper\CommandExitCode;
use App\Manager\ApplicationLock;
use App\Manager\EnvironmentVariables;
use App\Traits\CustomCommandsTrait;
use App\Traits\SymfonyProcessTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class AbstractServiceCommand extends Command implements ServiceCommandInterface
{
    use SymfonyProcessTrait;
    use CustomCommandsTrait;

    /**
     * AbstractServiceCommand constructor.
     *
     * @param string|null          $name
     * @param ApplicationLock      $applicationLock
     * @param EnvironmentVariables $environmentVariables
     */
    public function __construct(?string $name = null, ApplicationLock $applicationLock, EnvironmentVariables $environmentVariables, ValidatorInterface $validator)
    {
        parent::__construct($name);

        $this->applicationLock = $applicationLock;
        $this->environmentVariables = $environmentVariables;
        $this->validator = $validator;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $serviceName = $this->getServiceName();

        $this->setName("origami:services:$serviceName");
        $this->setAliases([$serviceName]);

        $this->setDescription("Opens a terminal on the \"$serviceName\" service");
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $this->io = new SymfonyStyle($input, $output);

        if ($this->project = $this->applicationLock->getCurrentLock()) {
            try {
                $this->checkEnvironmentConfiguration();

                $this->io->success('An environment is currently running.');
                $this->io->listing(
                    [
                        "Project location: {$this->project}",
                        'Environment type: '.getenv('DOCKER_ENVIRONMENT'),
                    ]
                );

                $environmentVariables = $this->environmentVariables->getRequiredVariables($this->project);
                $this->openTerminal($environmentVariables);
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

    /**
     * Opens a terminal on the service associated to the command.
     *
     * @param array $environmentVariables
     */
    private function openTerminal(array $environmentVariables): void
    {
        $command = ['docker-compose', 'exec', $this->getServiceName(), 'sh'];
        $process = new Process($command, null, $environmentVariables, null, 3600.00);
        $this->foreground($process);
    }
}
