<?php

declare(strict_types=1);

namespace App\Command\Additional;

use App\Command\AbstractBaseCommand;
use App\Helper\CommandExitCode;
use App\Helper\ProcessProxy;
use App\Middleware\Binary\DockerCompose;
use App\Middleware\SystemManager;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CheckCommand extends AbstractBaseCommand
{
    /** @var array */
    private $requirements;

    public function __construct(
        SystemManager $systemManager,
        ValidatorInterface $validator,
        DockerCompose $dockerCompose,
        EventDispatcherInterface $eventDispatcher,
        ProcessProxy $processProxy,
        array $requirements,
        ?string $name = null
    ) {
        parent::__construct($systemManager, $validator, $dockerCompose, $eventDispatcher, $processProxy, $name);

        $this->requirements = $requirements;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setDescription('Checks whether all required softwares are installed');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->section('Docker Requirements Checker');

        $table = new Table($output);
        $table->setHeaders(['Binary', 'Description', 'Status']);

        $ready = true;
        foreach ($this->requirements as $name => $description) {
            $status = $this->systemManager->isBinaryInstalled($name) ? 'Installed' : 'Missing';
            if ($status !== 'Installed') {
                $ready = false;
            }
            $table->addRow([$name, $description, $status]);
        }

        $table->render();

        if ($ready) {
            $this->io->success('Your system is ready.');
        } else {
            $this->io->error('At least one system requirement is missing.');
            $exitCode = CommandExitCode::INVALID;
        }

        return $exitCode ?? CommandExitCode::SUCCESS;
    }
}
