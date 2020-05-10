<?php

declare(strict_types=1);

namespace App\Command\Additional;

use App\Command\AbstractBaseCommand;
use App\Environment\EnvironmentEntity;
use App\Exception\OrigamiExceptionInterface;
use App\Helper\CommandExitCode;
use App\Helper\ProcessProxy;
use App\Middleware\Configuration\ConfigurationInstaller;
use App\Middleware\Database;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class RegisterCommand extends AbstractBaseCommand
{
    /** @var Database */
    private $database;

    /** @var ConfigurationInstaller */
    private $installer;

    /** @var ProcessProxy */
    private $processProxy;

    public function __construct(
        Database $database,
        ConfigurationInstaller $installer,
        ProcessProxy $processProxy,
        ?string $name = null
    ) {
        parent::__construct($name);

        $this->database = $database;
        $this->installer = $installer;
        $this->processProxy = $processProxy;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setDescription('Register an external environment which was not created by Origami.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            if ($io->confirm('Do you want to register the current directory as a custom environment?', false)) {
                $location = $this->processProxy->getWorkingDirectory();

                $environment = $this->installer->install($location, EnvironmentEntity::TYPE_CUSTOM);
                $this->database->add($environment);
                $this->database->save();

                $io->success('Environment successfully registered.');
            }
        } catch (OrigamiExceptionInterface $exception) {
            $io->error($exception->getMessage());
            $exitCode = CommandExitCode::EXCEPTION;
        }

        return $exitCode ?? CommandExitCode::SUCCESS;
    }
}
