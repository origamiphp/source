<?php

declare(strict_types=1);

namespace App\Helper;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @codeCoverageIgnore
 */
class ApplicationFactory
{
    /**
     * Creates a new console application based on the given kernel.
     *
     * @param KernelInterface $kernel
     *
     * @return Application
     */
    public function create(KernelInterface $kernel): Application
    {
        return new Application($kernel);
    }
}
