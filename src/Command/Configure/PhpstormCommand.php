<?php

declare(strict_types=1);

namespace App\Command\Configure;

use App\Command\AbstractBaseCommand;
use App\Exception\FilesystemException;
use App\Exception\InvalidConfigurationException;
use App\Exception\OrigamiExceptionInterface;
use App\Service\ApplicationContext;
use App\Service\Middleware\Database;
use App\Service\Wrapper\OrigamiStyle;
use App\ValueObject\EnvironmentEntity;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
    name: 'origami:configure:phpstorm',
    description: 'Copies the PhpStorm configuration associated to the current environment'
)]
class PhpstormCommand extends AbstractBaseCommand
{
    public function __construct(
        private ApplicationContext $applicationContext,
        private Database $database,
        private string $projectDir,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new OrigamiStyle($input, $output);

        try {
            $this->applicationContext->loadEnvironment($input);
            $environment = $this->applicationContext->getActiveEnvironment();

            if (!is_dir($environment->getLocation().'/.idea')) {
                throw new FilesystemException('You must run this command with a project that uses PhpStorm.');
            }

            if ($output->isVerbose()) {
                $this->printEnvironmentDetails($environment, $io);
            }

            $this->copyDatabaseConfiguration($environment);

            $this->copyPhpServerConfiguration($environment);
            $this->copyPhpRemoteDebugConfiguration($environment);

            $io->success('PhpStorm configuration has been successfully updated.');
        } catch (OrigamiExceptionInterface $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * Installs new XML files to manage the database connection.
     *
     * @throws InvalidConfigurationException
     * @throws FilesystemException
     */
    private function copyDatabaseConfiguration(EnvironmentEntity $environment): void
    {
        $databaseType = $this->database->getDatabaseType();
        $source = $this->projectDir.'/src/Resources/phpstorm/'.$databaseType;

        if (!$databaseType || !is_dir($source)) {
            throw new FilesystemException('Unable to find the database configuration for PhpStorm.');
        }

        $destination = $environment->getLocation().'/.idea';

        $filesystem = new Filesystem();
        $filesystem->copy($source.'/dataSources.local.xml', $destination.'/dataSources.local.xml', true);
        $filesystem->copy($source.'/dataSources.xml', $destination.'/dataSources.xml', true);
    }

    /**
     * Updates an existing XML file to manage the PHP servers.
     *
     * @throws FilesystemException
     */
    private function copyPhpServerConfiguration(EnvironmentEntity $environment): void
    {
        $configurationFile = $environment->getLocation().'/.idea/workspace.xml';

        $document = new DOMDocument();
        $document->load($configurationFile);

        if (!$document->documentElement instanceof DOMElement) {
            throw new FilesystemException('The configuration file of PhpStorm seems to be misformed.');
        }

        $entries = (new DOMXPath($document))->query('/project/component[@name="PhpServers"]');
        if ($entries && $entries->count() !== 0 && ($existingNode = $entries->item(0))) {
            $document->documentElement->removeChild($existingNode);
        }

        if (!$contents = file_get_contents($this->projectDir.'/src/Resources/phpstorm/php_servers.xml')) {
            throw new FilesystemException('Unable to load the PHP servers configuration file.');
        }

        $fragment = $document->createDocumentFragment();
        $fragment->appendXML($contents);

        $document->documentElement->append($fragment);
        $document->save($configurationFile);
    }

    /**
     * Installs new XML files to manage the remote debug process.
     */
    private function copyPhpRemoteDebugConfiguration(EnvironmentEntity $environment): void
    {
        $filesystem = new Filesystem();
        $filesystem->copy(
            $this->projectDir.'/src/Resources/phpstorm/remote_debug.xml',
            $environment->getLocation().'/.idea/runConfigurations/origami.xml',
            true
        );
    }
}
