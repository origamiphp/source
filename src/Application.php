<?php

declare(strict_types=1);

namespace App;

use App\Command\DefaultCommand;
use App\ValueObject\ApplicationVersion;
use Symfony\Bundle\FrameworkBundle\Console\Application as SymfonyApplication;
use Symfony\Component\HttpKernel\KernelInterface;

class Application extends SymfonyApplication
{
    public const CONSOLE_LOGO = <<<'ASCII'
  ___       _                       _
 / _ \ _ __(_) __ _  __ _ _ __ ___ (_)
| | | | '__| |/ _` |/ _` | '_ ` _ \| |
| |_| | |  | | (_| | (_| | | | | | | |
 \___/|_|  |_|\__, |\__,_|_| |_| |_|_|
              |___/


ASCII;

    public const CONSOLE_NAME = 'Origami';

    /**
     * {@inheritdoc}
     *
     * @param Kernel $kernel
     */
    public function __construct(
        KernelInterface $kernel,
        private ApplicationVersion $version
    ) {
        parent::__construct($kernel);

        if ($defaultName = DefaultCommand::getDefaultName()) {
            $this->setDefaultCommand($defaultName);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getHelp(): string
    {
        return self::CONSOLE_LOGO.parent::getHelp();
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return self::CONSOLE_NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getVersion(): string
    {
        return $this->version->getValue();
    }
}
