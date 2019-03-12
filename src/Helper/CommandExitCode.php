<?php

declare(strict_types=1);

namespace App\Helper;

class CommandExitCode
{
    /**
     * Exit code returned when a command ran successfully.
     *
     * @var int
     */
    public const SUCCESS = 0;

    /**
     * Exit code returned when a command ended because of an invalid input.
     *
     * @var int
     */
    public const INVALID = 1;

    /**
     * Exit code returned when a command ended because of an exception.
     *
     * @var int
     */
    public const EXCEPTION = 2;
}
