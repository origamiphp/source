<?php

declare(strict_types=1);

namespace App\Command;

use App\Exception\EnvironmentException;
use App\Manager\ApplicationLock;
use App\Manager\EnvironmentVariables;
use App\Traits\CustomCommandsTrait;
use App\Traits\SymfonyProcessTrait;
use App\Validator\Constraints\DotEnvExists;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RootCommand extends Command
{
    use SymfonyProcessTrait;
    use CustomCommandsTrait;

    /**
     * RootCommand constructor.
     *
     * @param string|null          $name
     * @param ApplicationLock      $applicationLock
     * @param EnvironmentVariables $environmentVariables
     */
    public function __construct(?string $name = null, ApplicationLock $applicationLock, EnvironmentVariables $environmentVariables, ValidatorInterface $validator)
    {
        parent::__construct($name);

        $this->applicationLock = $applicationLock;
        $this->environmentVariables = $environmentVariables;
        $this->validator = $validator;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('origami:root');
        $this->setAliases(['root']);

        $this->setDescription('Display instructions to set up the environment variables');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);

        if ($this->project = $this->applicationLock->getCurrentLock()) {
            try {
                $this->checkEnvironmentConfiguration();
                $this->writeInstructions();
            } catch (\Exception $e) {
                $this->io->error($e->getMessage());
            }
        } else {
            $this->io->error('There is no running environment.');
        }
    }

    /**
     * Checks whether the environment has been installed and correctly configured.
     *
     * @throws EnvironmentException
     */
    private function checkEnvironmentConfiguration(): void
    {
        $dotEnvConstraint = new DotEnvExists();
        $errors = $this->validator->validate($this->project, $dotEnvConstraint);
        if ($errors->has(0) !== true) {
            $this->environmentVariables->loadFromDotEnv("{$this->project}/var/docker/.env");
        } else {
            throw new EnvironmentException($errors[0]->getMessage());
        }
    }

    /**
     * Writes instructions to the console output.
     */
    private function writeInstructions(): void
    {
        $result = '';
        foreach ($this->environmentVariables->getRequiredVariables($this->project) as $key => $value) {
            $result .= "export $key=\"$value\"\n";
        }

        $this->io->writeln($result);
        $this->io->writeln('# Run this command to configure your shell:');
        $this->io->writeln('# eval $(origami root)');
    }
}
