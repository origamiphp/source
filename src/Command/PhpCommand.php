<?php

declare(strict_types=1);

namespace App\Command;

use App\Exception\InvalidEnvironmentException;
use App\Exception\OrigamiExceptionInterface;
use App\Service\CurrentContext;
use App\Service\Middleware\Binary\Docker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class PhpCommand extends AbstractBaseCommand
{
    private const COMMAND_SERVICE_NAME = 'php';
    private const COMMAND_USERNAME = 'www-data:www-data';

    /**
     * {@inheritdoc}
     */
    protected static $defaultName = 'origami:php';

    private CurrentContext $currentContext;
    private Docker $docker;

    public function __construct(CurrentContext $currentContext, Docker $docker, ?string $name = null)
    {
        parent::__construct($name);

        $this->currentContext = $currentContext;
        $this->docker = $docker;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setDescription('Opens a terminal on the "php" service');
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

            if ($output->isVerbose()) {
                $this->printEnvironmentDetails($environment, $io);
            }

            if (!$this->docker->openTerminal(self::COMMAND_SERVICE_NAME, self::COMMAND_USERNAME)) {
                throw new InvalidEnvironmentException('An error occurred while opening a terminal.');
            }
        } catch (OrigamiExceptionInterface $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
