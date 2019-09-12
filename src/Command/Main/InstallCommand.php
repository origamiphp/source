<?php

declare(strict_types=1);

namespace App\Command\Main;

use App\Command\AbstractBaseCommand;
use App\Exception\InvalidConfigurationException;
use App\Exception\OrigamiExceptionInterface;
use App\Helper\CommandExitCode;
use App\Middleware\Binary\DockerCompose;
use App\Middleware\SystemManager;
use App\Validator\Constraints\LocalDomains;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class InstallCommand extends AbstractBaseCommand
{
    /** @var array */
    private $environments;

    /**
     * InstallCommand constructor.
     *
     * @param SystemManager            $systemManager
     * @param ValidatorInterface       $validator
     * @param DockerCompose            $dockerCompose
     * @param EventDispatcherInterface $eventDispatcher
     * @param array                    $environments
     * @param null|string              $name
     */
    public function __construct(
        SystemManager $systemManager,
        ValidatorInterface $validator,
        DockerCompose $dockerCompose,
        EventDispatcherInterface $eventDispatcher,
        array $environments,
        ?string $name = null
    ) {
        parent::__construct($systemManager, $validator, $dockerCompose, $eventDispatcher, $name);

        $this->environments = $environments;
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
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
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

                $this->systemManager->install($location, $type, $domains);
                $this->io->success('Environment successfully installed.');
            } catch (OrigamiExceptionInterface $e) {
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
     * @throws InvalidConfigurationException
     *
     * @return string
     */
    private function installationPathCallback(string $answer): string
    {
        if (!is_dir($answer)) {
            throw new InvalidConfigurationException('An existing directory must be provided.');
        }

        return $answer;
    }

    /**
     * Validates the response provided by the user to the local domains question.
     *
     * @param string $answer
     *
     * @throws InvalidConfigurationException
     *
     * @return string
     */
    private function localDomainsCallback(string $answer): string
    {
        $constraint = new LocalDomains();
        $errors = $this->validator->validate($answer, $constraint);
        if ($errors->has(0)) {
            throw new InvalidConfigurationException($errors->get(0)->getMessage());
        }

        return $answer;
    }
}
