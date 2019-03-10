<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class InstallCommand extends Command
{
    /** @var SymfonyStyle */
    private $io;

    /** @var array */
    private $environments;

    /**
     * InstallCommand constructor.
     *
     * @param string|null $name
     * @param array $environments
     */
    public function __construct(?string $name = null, array $environments)
    {
        parent::__construct($name);
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
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
        $filesystem = new Filesystem();

        $type = $this->io->choice('Which type of environment you want to install?', $this->environments, 'symfony');
        $location = realpath($this->io->askQuestion(new Question('Where do you want to install the environment?', '.')));

        if ($filesystem->exists($location)) {
            try {
                $source = __DIR__ . "/../Resources/$type";
                $destination = "$location/var/docker/";
                $this->copyEnvironmentFiles($filesystem, $source, $destination);

                $this->io->success("Environment files were successfully copied into \"$destination\".");
            } catch (\Exception $e) {
                $this->io->error($e->getMessage());
            }
        } else {
            $this->io->error('An existing directory must be provided.');
        }
    }

    /**
     * Copies all environment files into the project directory.
     *
     * @param Filesystem $filesystem
     * @param string $source
     * @param string $destination
     *
     * @throws FileNotFoundException|IOException
     */
    private function copyEnvironmentFiles(Filesystem $filesystem, string $source, string $destination): void
    {
        // Create the directory where all configuration files will be stored
        $filesystem->mkdir($destination);

        // Copy the docker-sync configuration into the project directory
        $filesystem->copy("$source/../docker-sync.yml", "$destination/docker-sync.yml");

        // Copy the docker configuration into the project directory
        $filesystem->mirror($source, $destination);
    }
}
