<?php

declare(strict_types=1);

namespace App\Command\Contextual\Services;

use App\Command\AbstractBaseCommand;
use App\Exception\InvalidEnvironmentException;
use App\Exception\OrigamiExceptionInterface;
use App\Helper\CommandExitCode;
use App\Helper\CurrentContext;
use App\Middleware\Binary\DockerCompose;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AbstractServiceCommand extends AbstractBaseCommand implements ServiceCommandInterface
{
    /** @var CurrentContext */
    protected $currentContext;

    /** @var DockerCompose */
    protected $dockerCompose;

    /** @var string */
    protected $serviceName;

    /** @var string */
    protected $username;

    public function __construct(CurrentContext $currentContext, DockerCompose $dockerCompose, ?string $name = null)
    {
        parent::__construct($name);

        $this->currentContext = $currentContext;
        $this->dockerCompose = $dockerCompose;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->serviceName = $this->getServiceName();
        $this->username = $this->getUsername();

        $this->setDescription(sprintf('Opens a terminal on the "%s" service', $this->serviceName));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $environment = $this->currentContext->getEnvironment($input);

            if ($output->isVerbose()) {
                $this->printEnvironmentDetails($environment, $io);
            }

            if (!$this->dockerCompose->openTerminal($this->serviceName, $this->username)) {
                throw new InvalidEnvironmentException('An error occurred while opening a terminal.');
            }
        } catch (OrigamiExceptionInterface $exception) {
            $io->error($exception->getMessage());
            $exitCode = CommandExitCode::EXCEPTION;
        }

        return $exitCode ?? CommandExitCode::SUCCESS;
    }
}
