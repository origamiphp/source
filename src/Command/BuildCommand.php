<?php

declare(strict_types=1);

namespace App\Command;

use App\Exception\EnvironmentException;
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

class BuildCommand extends Command
{
    use CustomCommandsTrait;

    /**
     * BuildCommand constructor.
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
        $this->setName('origami:build');
        $this->setAliases(['build']);

        $this->setDescription('Builds or rebuilds an environment previously installed in the current directory');
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

            $this->checkEnvironmentConfiguration();
            $environmentVariables = $this->environmentVariables->getRequiredVariables($this->project);

            $this->processManager->buildServices($environmentVariables);
        } catch (\Exception $e) {
            $this->io->error($e->getMessage());
            $exitCode = CommandExitCode::EXCEPTION;
        }

        return $exitCode ?? CommandExitCode::SUCCESS;
    }
}
