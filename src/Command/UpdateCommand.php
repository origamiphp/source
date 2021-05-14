<?php

declare(strict_types=1);

namespace App\Command;

use App\Exception\InvalidEnvironmentException;
use App\Exception\OrigamiExceptionInterface;
use App\Helper\CurrentContext;
use App\Helper\OrigamiStyle;
use App\Service\ConfigurationFiles;
use App\Service\EnvironmentBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateCommand extends AbstractBaseCommand
{
    /** {@inheritdoc} */
    protected static $defaultName = 'origami:update';

    private CurrentContext $currentContext;
    private EnvironmentBuilder $builder;
    private ConfigurationFiles $configuration;

    public function __construct(
        CurrentContext $currentContext,
        EnvironmentBuilder $builder,
        ConfigurationFiles $configuration,
        ?string $name = null
    ) {
        parent::__construct($name);

        $this->currentContext = $currentContext;
        $this->builder = $builder;
        $this->configuration = $configuration;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setDescription('Updates a specific environment');

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
            $this->currentContext->loadEnvironment($input);
            $environment = $this->currentContext->getActiveEnvironment();

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
