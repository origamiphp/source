<?php

declare(strict_types=1);

namespace App\Tests;

use Prophecy\PhpUnit\ProphecyTrait;

trait CustomProphecyTrait
{
    use ProphecyTrait;

    /**
     * Prophesizes arguments needed by the class which will be tested.
     */
    abstract protected function prophesizeObjectArguments(): array;
}
