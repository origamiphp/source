<?php

declare(strict_types=1);

namespace App\Tests\Command;

use Liip\FunctionalTestBundle\Test\WebTestCase;

/**
 * @internal
 * @covers \App\Command\DefaultCommand
 */
final class DefaultCommandTest extends WebTestCase
{
    public function testItNotPrintsDefaultCommandInList(): void
    {
        $output = $this->runCommand('list')->getDisplay();
        static::assertStringNotContainsString('origami:default', $output);
    }

    public function testItPrintsOnlyOrigamiCommands(): void
    {
        static::assertSame(
            $this->runCommand('list', ['namespace' => 'origami'])->getDisplay(),
            $this->runCommand('origami:default')->getDisplay()
        );
    }
}
