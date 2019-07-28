<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Project;
use App\Helper\CommandExitCode;
use App\Manager\EnvironmentVariables;
use App\Manager\ProjectManager;
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
     * @param ProjectManager       $projectManager
     * @param EnvironmentVariables $environmentVariables
     * @param ValidatorInterface   $validator
     * @param string|null          $name
     */
    public function __construct(
        ProjectManager $projectManager,
        EnvironmentVariables $environmentVariables,
        ValidatorInterface $validator,
        ?string $name = null
    ) {
        parent::__construct($name);

        $this->projectManager = $projectManager;
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
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $this->io = new SymfonyStyle($input, $output);

        $activeProject = $this->projectManager->getActiveProject();
        if ($activeProject instanceof Project) {
            $this->project = $activeProject;

            try {
                $this->checkEnvironmentConfiguration();
                $this->writeInstructions();
            } catch (\Exception $e) {
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
        foreach ($this->environmentVariables->getRequiredVariables($this->project) as $key => $value) {
            $result .= "export $key=\"$value\"\n";
        }

        $this->io->writeln($result);
        $this->io->writeln('# Run this command to configure your shell:');
        $this->io->writeln('# eval $(origami root)');
    }
}
