<?php

declare(strict_types=1);

namespace App\Command;

use App\Exception\ConfigurationException;
use App\Helper\CommandExitCode;
use App\Manager\ProcessManager;
use App\Traits\CustomCommandsTrait;
use App\Validator\Constraints\LocalDomains;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Exception\IOException;
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
     * @param ProcessManager     $processManager
     */
    public function __construct(
        ?string $name = null,
        array $environments,
        ValidatorInterface $validator,
        ProcessManager $processManager
    ) {
        parent::__construct($name);

        $this->environments = $environments;
        $this->validator = $validator;
        $this->processManager = $processManager;
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

        $type = $this->io->choice('Which type of environment you want to install?', $this->environments, 'symfony');
        $location = realpath(
            $this->io->ask('Where do you want to install the environment?', '.', function ($answer) {
                return $this->installationPathCallback($answer);
            })
        );

        if ($location && $filesystem->exists($location)) {
            try {
                $source = __DIR__."/../Resources/$type";
                $destination = "$location/var/docker";
                $this->copyEnvironmentFiles($filesystem, $source, $destination);

                if ($this->io->confirm('Do you want to generate a locally-trusted development certificate?', false)) {
                    $certificate = "$destination/nginx/certs/custom.pem";
                    $privateKey = "$destination/nginx/certs/custom.key";
                    $domains = $this->io->ask(
                        'Which domains does this certificate belong to?',
                        'symfony.localhost www.symfony.localhost',
                        function ($answer) {
                            return $this->localDomainsCallback($answer);
                        }
                    );

                    $this->processManager->generateCertificate($certificate, $privateKey, explode(' ', $domains));
                }

                $this->io->success("Environment files were successfully copied into \"$destination\".");
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
     * Copies all environment files into the project directory.
     *
     * @param Filesystem $filesystem
     * @param string     $source
     * @param string     $destination
     *
     * @throws FileNotFoundException|IOException
     */
    private function copyEnvironmentFiles(Filesystem $filesystem, string $source, string $destination): void
    {
        // Create the directory where all configuration files will be stored
        $filesystem->mkdir($destination);

        // Copy the environment files into the project directory
        $filesystem->mirror($source, $destination);
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
