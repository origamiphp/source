<?php

declare(strict_types=1);

namespace App\Command\Main;

use App\Command\AbstractBaseCommand;
use App\Environment\Configuration\ConfigurationUpdater;
use App\Exception\OrigamiExceptionInterface;
use App\Helper\CurrentContext;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateCommand extends AbstractBaseCommand
{
    /** {@inheritdoc} */
    protected static $defaultName = 'origami:update';

    /** @var CurrentContext */
    private $currentContext;

    /** @var ConfigurationUpdater */
    private $updater;

    public function __construct(
        CurrentContext $currentContext,
        ConfigurationUpdater $updater,
        ?string $name = null
    ) {
        parent::__construct($name);

        $this->currentContext = $currentContext;
        $this->updater = $updater;
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
        $io = new SymfonyStyle($input, $output);

        try {
            $environment = $this->currentContext->getEnvironment($input);
            $this->currentContext->setActiveEnvironment($environment);

            $question = sprintf(
                'Are you sure you want to update the "%s" environment?',
                $environment->getName()
            );

            if ($io->confirm($question, false)) {
                $this->updater->update($environment);
                $io->success('Environment successfully updated.');
            }
        } catch (OrigamiExceptionInterface $exception) {
            $io->error($exception->getMessage());
            $exitCode = Command::FAILURE;
        }

        return $exitCode ?? Command::SUCCESS;
    }
}
