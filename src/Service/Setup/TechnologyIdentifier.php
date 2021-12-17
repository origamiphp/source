<?php

declare(strict_types=1);

namespace App\Service\Setup;

use App\Exception\FilesystemException;
use App\ValueObject\EnvironmentEntity;
use App\ValueObject\TechnologyDetails;
use JsonException;

class TechnologyIdentifier
{
    private const REQUIRED_TECHNOLOGY_PACKAGE = [
        EnvironmentEntity::TYPE_DRUPAL => ['drupal/core', 'drupal/core-recommended', 'drupal/recommended-project'],
        EnvironmentEntity::TYPE_MAGENTO2 => ['magento/product-community-edition', 'magento/product-enterprise-edition'],
        EnvironmentEntity::TYPE_OROCOMMERCE => ['oro/commerce', 'oro/commerce-enterprise'],
        EnvironmentEntity::TYPE_SYLIUS => ['sylius/sylius'],
        EnvironmentEntity::TYPE_SYMFONY => ['symfony/framework-bundle'],
    ];

    /**
     * Executes the technology identification process on the given location.
     */
    public function identify(string $location): ?TechnologyDetails
    {
        $file = "{$location}/composer.json";

        if (!is_file($file)) {
            return null;
        }

        try {
            $configuration = $this->loadConfiguration($file);

            foreach (self::REQUIRED_TECHNOLOGY_PACKAGE as $technology => $packages) {
                foreach ($packages as $package) {
                    if (isset($configuration['require'][$package])) {
                        return new TechnologyDetails($technology, $configuration['require'][$package]);
                    }
                }
            }
        } catch (FilesystemException|JsonException $exception) {
            // Ignore the thrown exception and return nothing.
        }

        return null;
    }

    /**
     * Loads the Composer configuration from the filesystem as an associative array.
     *
     * @throws JsonException
     * @throws FilesystemException
     *
     * @return array[]
     */
    private function loadConfiguration(string $filename): array
    {
        if (!$content = file_get_contents($filename)) {
            throw new FilesystemException('Unable to load the Composer configuration from the filesystem.');
        }

        $result = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        if (!\is_array($result)) {
            throw new JsonException('The Composer configuration is not a valid JSON.');
        }

        return $result;
    }
}
