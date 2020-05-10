<?php

declare(strict_types=1);

namespace App\Helper;

class BinaryChecker
{
    /** @var ProcessFactory */
    private $processFactory;

    public function __construct(ProcessFactory $processFactory)
    {
        $this->processFactory = $processFactory;
    }

    /**
     * Checks whether the given binary is available.
     */
    public function isInstalled(string $binary): bool
    {
        return $this->processFactory->runBackgroundProcess(['which', $binary])->isSuccessful();
    }
}
