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

class DataCommand extends Command
{
    use CustomCommandsTrait;

    /**
     * DataCommand constructor.
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
        $this->setName('origami:data');
        $this->setAliases(['data']);

        $this->setDescription('Shows the statistics usage of running environment');
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

                if ($output->isVerbose()) {
                    $this->printEnvironmentDetails();
                }

                $environmentVariables = $this->environmentVariables->getRequiredVariables($this->project);
                $this->processManager->showResourcesUsage($environmentVariables);
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
