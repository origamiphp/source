<?php

declare(strict_types=1);

namespace App\Command;

use Exception;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'origami:default',
    description: 'Wrapper of the default "list" command',
    hidden: true
)]
class DefaultCommand extends Command
{
    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var Application $application */
        $application = $this->getApplication();

        $command = $application->find('list');
        $arguments = ['namespace' => 'origami'];

        $listInput = new ArrayInput($arguments);

        return $command->run($listInput, $output);
    }
}
