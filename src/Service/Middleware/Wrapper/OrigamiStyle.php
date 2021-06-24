<?php

declare(strict_types=1);

namespace App\Service\Middleware\Wrapper;

use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @codeCoverageIgnore
 */
class OrigamiStyle extends SymfonyStyle
{
    /**
     * Formats an info result bar.
     *
     * @param array|string $message
     */
    public function info($message): void
    {
        $this->block($message, 'INFO', 'bg=blue;fg=white', ' ', true, true);
    }
}
