<?php

declare(strict_types=1);

namespace App\Command\Services;

use App\Entity\Environment;
use App\Exception\OrigamiExceptionInterface;
use App\Helper\CommandExitCode;
use App\Manager\EnvironmentManager;
use App\Manager\Process\DockerCompose;
use App\Traits\CustomCommandsTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class AbstractServiceCommand extends Command implements ServiceCommandInterface
{
    use CustomCommandsTrait;

    /** @var DockerCompose */
    private $dockerCompose;

    /**
     * AbstractServiceCommand constructor.
     *
     * @param EnvironmentManager $environmentManager
     * @param ValidatorInterface $validator
     * @param DockerCompose      $dockerCompose
     * @param string|null        $name
     */
    public function __construct(
        EnvironmentManager $environmentManager,
        ValidatorInterface $validator,
        DockerCompose $dockerCompose,
        ?string $name = null
    ) {
        parent::__construct($name);

        $this->environmentManager = $environmentManager;
        $this->validator = $validator;
        $this->dockerCompose = $dockerCompose;
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

        $activeEnvironment = $this->environmentManager->getActiveEnvironment();
        if ($activeEnvironment instanceof Environment) {
            $this->environment = $activeEnvironment;

            try {
                $this->checkEnvironmentConfiguration();

                if ($output->isVerbose()) {
                    $this->printEnvironmentDetails();
                }

                $this->dockerCompose->openTerminal(
                    $this->getServiceName(),
                    $this->getUsername(),
                    $this->getRequiredVariables($this->environment)
                );
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
