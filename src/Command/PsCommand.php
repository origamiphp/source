<?php

declare(strict_types=1);

namespace App\Command;

use App\Exception\InvalidEnvironmentException;
use App\Exception\OrigamiExceptionInterface;
use App\Service\ApplicationContext;
use App\Service\Middleware\Binary\Docker;
use App\Service\Wrapper\OrigamiStyle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PsCommand extends AbstractBaseCommand
{
    /** {@inheritdoc} */
    protected static $defaultName = 'origami:ps';
    /** {@inheritdoc} */
    protected static $defaultDescription = 'Shows the status of an environment previously started';

    private ApplicationContext $applicationContext;
    private Docker $docker;

    public function __construct(ApplicationContext $applicationContext, Docker $docker, ?string $name = null)
    {
        parent::__construct($name);

        $this->applicationContext = $applicationContext;
        $this->docker = $docker;
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

            if ($output->isVerbose()) {
                $this->printEnvironmentDetails($environment, $io);
            }

            if (!$this->docker->showServicesStatus()) {
                throw new InvalidEnvironmentException('An error occurred while checking the services status.');
            }
        } catch (OrigamiExceptionInterface $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
