<?php

declare(strict_types=1);

namespace App\Command;

use App\Helper\CommandExitCode;
use App\Manager\Process\System;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CheckCommand extends Command
{
    /** @var array */
    private $requirements;

    /** @var System */
    private $system;

    /**
     * CheckCommand constructor.
     *
     * @param array       $requirements
     * @param System      $system
     * @param string|null $name
     */
    public function __construct(array $requirements, System $system, ?string $name = null)
    {
        parent::__construct($name);

        $this->requirements = $requirements;
        $this->system = $system;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('origami:check');
        $this->setAliases(['check']);

        $this->setDescription('Checks whether all required softwares are installed');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = new SymfonyStyle($input, $output);
        $io->section('Docker Requirements Checker');

        $table = new Table($output);
        $table->setHeaders(['Binary', 'Description', 'Status']);

        $ready = true;
        foreach ($this->requirements as $name => $description) {
            $status = $this->system->isBinaryInstalled($name) ? 'Installed' : 'Missing';
            if ($status !== 'Installed') {
                $ready = false;
            }
            $table->addRow([$name, $description, $status]);
        }

        $table->render();

        if ($ready === true) {
            $io->success('Your system is ready.');
        } else {
            $io->error('At least one system requirement is missing.');
            $exitCode = CommandExitCode::INVALID;
        }

        return $exitCode ?? CommandExitCode::SUCCESS;
    }
}
