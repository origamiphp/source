<?php

declare(strict_types=1);

namespace App\Command;

use App\Exception\InvalidEnvironmentException;
use App\Exception\OrigamiExceptionInterface;
use App\Service\ApplicationContext;
use App\Service\Setup\ConfigurationFiles;
use App\Service\Setup\EnvironmentBuilder;
use App\Service\Wrapper\OrigamiStyle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateCommand extends AbstractBaseCommand
{
    /** {@inheritdoc} */
    protected static $defaultName = 'origami:update';
    /** {@inheritdoc} */
    protected static $defaultDescription = 'Updates the configuration of a previously installed environment';

    private ApplicationContext $applicationContext;
    private EnvironmentBuilder $builder;
    private ConfigurationFiles $configuration;

    public function __construct(
        ApplicationContext $applicationContext,
        EnvironmentBuilder $builder,
        ConfigurationFiles $configuration,
        ?string $name = null
    ) {
        parent::__construct($name);

        $this->applicationContext = $applicationContext;
        $this->builder = $builder;
        $this->configuration = $configuration;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->addArgument(
            'environment',
            InputArgument::OPTIONAL,
            'Name of the environment to update'
        );
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

            if ($environment->isActive()) {
                throw new InvalidEnvironmentException('Unable to update a running environment.');
            }

            $question = sprintf(
                'Are you sure you want to update the "%s" environment?',
                $environment->getName()
            );

            if ($io->confirm($question, false)) {
                $userInputs = $this->builder->prepare($io, $environment);
                $this->configuration->install($environment, $userInputs->getSettings());

                $io->success('Environment successfully updated.');
            }
        } catch (OrigamiExceptionInterface $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
