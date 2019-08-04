<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Environment;
use App\Exception\OrigamiExceptionInterface;
use App\Helper\CommandExitCode;
use App\Manager\EnvironmentManager;
use App\Traits\CustomCommandsTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RootCommand extends Command
{
    use CustomCommandsTrait;

    /**
     * RootCommand constructor.
     *
     * @param EnvironmentManager $environmentManager
     * @param ValidatorInterface $validator
     * @param string|null        $name
     */
    public function __construct(
        EnvironmentManager $environmentManager,
        ValidatorInterface $validator,
        ?string $name = null
    ) {
        parent::__construct($name);

        $this->environmentManager = $environmentManager;
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
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $this->io = new SymfonyStyle($input, $output);

        $activeEnvironment = $this->environmentManager->getActiveEnvironment();
        if ($activeEnvironment instanceof Environment) {
            $this->environment = $activeEnvironment;

            try {
                $this->checkEnvironmentConfiguration();
                $this->writeInstructions();
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

    /**
     * Writes instructions to the console output.
     */
    private function writeInstructions(): void
    {
        $result = '';
        foreach ($this->getRequiredVariables($this->environment) as $key => $value) {
            $result .= "export $key=\"$value\"\n";
        }

        $this->io->writeln($result);
        $this->io->writeln('# Run this command to configure your shell:');
        $this->io->writeln('# eval $(origami root)');
    }
}
