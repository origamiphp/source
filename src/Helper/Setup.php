<?php

declare(strict_types=1);

namespace App\Helper;

use App\Exception\InvalidConfigurationException;
use App\Kernel;
use Exception;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpKernel\KernelInterface;

class Setup
{
    /** @var Kernel */
    private $kernel;

    /** @var ApplicationFactory */
    private $applicationFactory;

    /** @var string */
    private $databaseName;

    /**
     * Setup constructor.
     *
     * @param Kernel $kernel
     */
    public function __construct(KernelInterface $kernel, ApplicationFactory $applicationFactory, string $databaseName)
    {
        $this->kernel = $kernel;
        $this->applicationFactory = $applicationFactory;
        $this->databaseName = $databaseName;
    }

    /**
     * Creates the directory where the database, the cache and the logs will be stored.
     *
     * @throws InvalidConfigurationException
     */
    public function createProjectDirectory(): void
    {
        $directory = $this->kernel->getCustomDir();

        if (!is_dir($directory)
            && !mkdir($concurrentDirectory = $directory, 0777, true) && !is_dir($concurrentDirectory)
        ) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory)); // @codeCoverageIgnore
        }
    }

    /**
     * Initializes the project database by creating the schema with Doctrine.
     *
     * @throws Exception
     */
    public function initializeProjectDatabase(): void
    {
        if (!is_file($this->kernel->getCustomDir().'/'.$this->databaseName)) {
            $application = $this->applicationFactory->create($this->kernel);
            $application->setAutoExit(false);

            $input = new ArrayInput(['command' => 'doctrine:schema:create', '--force']);
            $output = new NullOutput();

            $application->run($input, $output);
        }
    }
}
