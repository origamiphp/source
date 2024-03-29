<?php

declare(strict_types=1);

namespace App\Command;

use App\Exception\OrigamiExceptionInterface;
use App\Service\ApplicationContext;
use App\Service\Middleware\Binary\Docker;
use App\Service\Wrapper\OrigamiStyle;
use App\ValueObject\EnvironmentEntity;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\Request;

#[AsCommand(
    name: 'origami:root',
    description: 'Shows instructions for configuring your terminal to manually use Docker commands'
)]
class RootCommand extends AbstractBaseCommand
{
    public function __construct(
        private ApplicationContext $applicationContext,
        private Docker $docker,
        string $name = null
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

            if ($output->isVerbose()) {
                $this->printEnvironmentDetails($environment, $io);
            }

            $this->writeInstructions($environment, $io);
        } catch (OrigamiExceptionInterface $exception) {
            $io->error($exception->getMessage());
            $exitCode = Command::FAILURE;
        }

        return $exitCode ?? Command::SUCCESS;
    }

    /**
     * Writes instructions to the console output.
     */
    private function writeInstructions(EnvironmentEntity $environment, SymfonyStyle $io): void
    {
        $result = '';

        foreach ($this->docker->getEnvironmentVariables($environment) as $key => $value) {
            $result .= "export {$key}=\"{$value}\"\n";
        }

        $request = Request::createFromGlobals();
        $path = $request->server->get('SCRIPT_NAME');

        $io->writeln($result);
        $io->writeln('# Run this command to configure your shell:');
        $io->writeln("# eval $({$path} root)");
    }
}
