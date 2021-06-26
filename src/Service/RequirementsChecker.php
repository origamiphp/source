<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\MissingRequirementException;
use App\Service\Wrapper\OrigamiStyle;
use Symfony\Component\Process\ExecutableFinder;

class RequirementsChecker
{
    private ExecutableFinder $executableFinder;

    public function __construct(ExecutableFinder $executableFinder)
    {
        $this->executableFinder = $executableFinder;
    }

    /**
     * Checks whether all required binaries are available.
     *
     * @throws MissingRequirementException
     */
    public function validate(OrigamiStyle $io, bool $isVerbose): void
    {
        $mandatoryRequirements = $this->checkMandatoryRequirements();
        $nonMandatoryRequirements = $this->checkNonMandatoryRequirements();

        if ($isVerbose) {
            $io->title('Origami Requirements Checker');

            $io->listing(
                array_map(static function ($item) {
                    $icon = $item['status'] ? '✅' : '❌';

                    return "{$icon} {$item['name']} - {$item['description']}";
                }, [...$mandatoryRequirements, ...$nonMandatoryRequirements])
            );
        }

        if (\count($mandatoryRequirements) !== \count(array_filter(array_column($mandatoryRequirements, 'status')))) {
            throw new MissingRequirementException('At least one mandatory binary uses an unsupported version or is missing from your system.');
        }
    }

    /**
     * Checks whether the application mandatory requirements are installed.
     *
     * @return array<int, array<string, string|bool>>
     */
    private function checkMandatoryRequirements(): array
    {
        return [
            [
                'name' => 'docker',
                'description' => 'A self-sufficient runtime for containers.',
                'status' => $this->executableFinder->find('docker') !== null,
            ],
            [
                'name' => 'mutagen',
                'description' => 'Fast and efficient way to synchronize code to Docker containers.',
                'status' => $this->executableFinder->find('mutagen') !== null,
            ],
        ];
    }

    /**
     * Checks whether the application non-mandatory requirements are installed.
     *
     * @return array<int, array<string, string|bool>>
     */
    private function checkNonMandatoryRequirements(): array
    {
        return [
            [
                'name' => 'mkcert',
                'description' => 'A simple zero-config tool to make locally trusted development certificates.',
                'status' => $this->canMakeLocallyTrustedCertificates(),
            ],
        ];
    }

    /**
     * Checks whether Mkcert is available in the system.
     */
    public function canMakeLocallyTrustedCertificates(): bool
    {
        return $this->executableFinder->find('mkcert') !== null;
    }
}
