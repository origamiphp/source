<?php

declare(strict_types=1);

namespace App\Command;

use App\Helper\CommandExitCode;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

class CheckCommand extends Command
{
    /** @var array */
    private $requirements;

    /**
     * CheckCommand constructor.
     *
     * @param string|null $name
     * @param array       $requirements
     */
    public function __construct(?string $name = null, array $requirements)
    {
        parent::__construct($name);
        $this->requirements = $requirements;
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
            $status = $this->isBinaryInstalled($name) ? 'Installed' : 'Missing';
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

    /**
     * Checks whether the given binary is available.
     *
     * @param string $binary
     *
     * @return bool
     */
    private function isBinaryInstalled(string $binary): bool
    {
        if (strpos($binary, '/') === false) {
            $process = new Process(['which', $binary]);
            $process->run();

            $result = $process->isSuccessful();
        } else {
            $process = new Process(['brew', 'list']);
            $process->run();

            $result = strpos($process->getOutput(), substr($binary, strrpos($binary, '/') + 1)) !== false;
        }

        return $result;
    }
}
