<?php

declare(strict_types=1);

namespace App\Command;

use App\Exception\FilesystemException;
use App\Exception\InvalidEnvironmentException;
use App\Exception\OrigamiExceptionInterface;
use App\Service\ApplicationContext;
use App\Service\Middleware\Binary\Docker;
use App\Service\Middleware\Binary\Mkcert;
use App\Service\Middleware\Binary\Mutagen;
use App\Service\Wrapper\OrigamiStyle;
use App\ValueObject\EnvironmentEntity;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DebugCommand extends AbstractBaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected static $defaultName = 'origami:debug';

    private Docker $docker;
    private Mutagen $mutagen;
    private Mkcert $mkcert;
    private ApplicationContext $applicationContext;
    private string $installDir;

    public function __construct(
        Docker $docker,
        Mutagen $mutagen,
        Mkcert $mkcert,
        ApplicationContext $applicationContext,
        string $installDir,
        string $name = null
    ) {
        parent::__construct($name);

        $this->docker = $docker;
        $this->mutagen = $mutagen;
        $this->mkcert = $mkcert;
        $this->applicationContext = $applicationContext;
        $this->installDir = $installDir;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setDescription('Shows some data to help in debugging');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new OrigamiStyle($input, $output);

        try {
            $this->displayBinaryVersions($io);

            try {
                $this->applicationContext->loadEnvironment($input);
                $environment = $this->applicationContext->getActiveEnvironment();

                $this->displayEnvironmentDetails($io, $environment);
            } catch (InvalidEnvironmentException $exception) {
                // A non-blocking exception can be thrown when trying to load the active environment.
            }
        } catch (OrigamiExceptionInterface $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function displayBinaryVersions(OrigamiStyle $io): void
    {
        $io->section('System Information');
        $io->table(
            ['Binary', 'Version'],
            [
                ['docker', $this->docker->getVersion()],
                ['mutagen', $this->mutagen->getVersion()],
                ['mkcert', $this->mkcert->getVersion()],
            ]
        );
    }

    /**
     * @throws FilesystemException
     */
    private function displayEnvironmentDetails(OrigamiStyle $io, EnvironmentEntity $environment): void
    {
        $io->section($this->installDir.'/docker-compose.yml');

        $configurationPath = $environment->getLocation().$this->installDir.'/docker-compose.yml';
        if (!$configuration = file_get_contents($configurationPath)) {
            // @codeCoverageIgnoreStart
            throw new FilesystemException(sprintf('Unable to load the content of the file "%s".', $configurationPath));
            // @codeCoverageIgnoreEnd
        }

        $io->writeln($configuration);
    }
}
