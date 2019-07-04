<?php

declare(strict_types=1);

namespace App\Command;

use App\Exception\ConfigurationException;
use App\Helper\CommandExitCode;
use App\Manager\ProjectManager;
use App\Traits\CustomCommandsTrait;
use App\Validator\Constraints\LocalDomains;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class InstallCommand extends Command
{
    use CustomCommandsTrait;

    /** @var array */
    private $environments;

    /**
     * InstallCommand constructor.
     *
     * @param string|null        $name
     * @param array              $environments
     * @param ValidatorInterface $validator
     * @param ProjectManager     $projectManager
     */
    public function __construct(
        ?string $name = null,
        array $environments,
        ProjectManager $projectManager,
        ValidatorInterface $validator
    ) {
        parent::__construct($name);

        $this->environments = $environments;
        $this->projectManager = $projectManager;
        $this->validator = $validator;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('origami:install');
        $this->setAliases(['install']);

        $this->setDescription('Installs a Docker environment in the desired directory');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $this->io = new SymfonyStyle($input, $output);
        $filesystem = new Filesystem();

        $type = $this->io->choice('Which type of environment you want to install?', $this->environments, 'magento2');
        $location = realpath(
            $this->io->ask('Where do you want to install the environment?', '.', function ($answer) {
                return $this->installationPathCallback($answer);
            })
        );

        if ($location && $filesystem->exists($location)) {
            try {
                if ($this->io->confirm('Do you want to generate a locally-trusted development certificate?', false)) {
                    $domains = $this->io->ask(
                        'Which domains does this certificate belong to?',
                        'magento.localhost www.magento.localhost',
                        function ($answer) {
                            return $this->localDomainsCallback($answer);
                        }
                    );
                } else {
                    $domains = null;
                }

                $this->projectManager->install($location, $type, $domains);
                $this->io->success('Environment files were successfully copied into the project.');
            } catch (\Exception $e) {
                $this->io->error($e->getMessage());
                $exitCode = CommandExitCode::EXCEPTION;
            }
        } else {
            $this->io->error('An existing directory must be provided.');
            $exitCode = CommandExitCode::INVALID;
        }

        return $exitCode ?? CommandExitCode::SUCCESS;
    }

    /**
     * Validates the response provided by the user to the installation path question.
     *
     * @param string $answer
     *
     * @throws \Exception
     *
     * @return string
     */
    private function installationPathCallback(string $answer): string
    {
        if (!is_dir($answer)) {
            throw new ConfigurationException('An existing directory must be provided.');
        }

        return $answer;
    }

    /**
     * Validates the response provided by the user to the local domains question.
     *
     * @param string $answer
     *
     * @throws ConfigurationException
     *
     * @return string
     */
    private function localDomainsCallback(string $answer): string
    {
        $constraint = new LocalDomains();
        $errors = $this->validator->validate($answer, $constraint);
        if ($errors->has(0)) {
            throw new ConfigurationException($errors->get(0)->getMessage());
        }

        return $answer;
    }
}
