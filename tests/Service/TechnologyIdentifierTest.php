<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\TechnologyIdentifier;
use App\Tests\TestEnvironmentTrait;
use App\ValueObject\TechnologyDetails;
use Generator;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \App\Service\TechnologyIdentifier
 *
 * @uses \App\ValueObject\TechnologyDetails
 */
final class TechnologyIdentifierTest extends TestCase
{
    use TestEnvironmentTrait;

    /**
     * @dataProvider provideSupportedTechnologies
     */
    public function testItIdentifiesSupportedTechnology(string $technologyName, string $configuration): void
    {
        file_put_contents("{$this->location}/composer.json", $configuration);
        $technologyIdentifier = new TechnologyIdentifier();
        $technology = $technologyIdentifier->identify($this->location);

        static::assertInstanceOf(TechnologyDetails::class, $technology);
        static::assertSame($technologyName, $technology->getName());
        static::assertSame('dev-master', $technology->getVersion());
    }

    public function testItReturnsNullWithUnknownTechnology(): void
    {
        file_put_contents("{$this->location}/composer.json", '{"require":{"foo/bar": "dev-master"}}');
        $technologyIdentifier = new TechnologyIdentifier();

        static::assertNull($technologyIdentifier->identify($this->location));
    }

    public function testItReturnsNullWithInvalidComposerConfiguration(): void
    {
        file_put_contents("{$this->location}/composer.json", '');
        $technologyIdentifier = new TechnologyIdentifier();

        static::assertNull($technologyIdentifier->identify($this->location));
    }

    public function testItReturnsNullWithoutComposerConfiguration(): void
    {
        $technologyIdentifier = new TechnologyIdentifier();

        static::assertNull($technologyIdentifier->identify($this->location));
    }

    public function provideSupportedTechnologies(): Generator
    {
        yield 'drupal/core' => ['drupal', '{"require":{"drupal/core": "dev-master"}}'];
        yield 'drupal/core-recommended' => ['drupal', '{"require":{"drupal/core-recommended": "dev-master"}}'];
        yield 'drupal/recommended-project' => ['drupal', '{"require":{"drupal/recommended-project": "dev-master"}}'];
        yield 'magento/product-community-edition' => ['magento2', '{"require":{"magento/product-community-edition": "dev-master"}}'];
        yield 'magento/product-enterprise-edition' => ['magento2', '{"require":{"magento/product-enterprise-edition": "dev-master"}}'];
        yield 'oro/commerce' => ['orocommerce', '{"require":{"oro/commerce": "dev-master"}}'];
        yield 'sylius/sylius' => ['sylius', '{"require":{"sylius/sylius": "dev-master"}}'];
        yield 'symfony/framework-bundle' => ['symfony', '{"require":{"symfony/framework-bundle": "dev-master"}}'];
    }
}
