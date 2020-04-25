<?php

declare(strict_types=1);

namespace App\Tests\Middleware\Binary;

use App\Environment\EnvironmentEntity;
use App\Helper\ProcessFactory;
use App\Tests\TestLocationTrait;
use App\Validator\Constraints\ConfigurationFiles;
use App\Validator\Constraints\DotEnvExists;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\Prophet;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 *
 * @coversNothing
 */
abstract class AbstractDockerComposeTestCase extends TestCase
{
    use TestLocationTrait;

    /** @var Prophet */
    protected $prophet;

    /** @var ObjectProphecy */
    protected $validator;

    /** @var ObjectProphecy */
    protected $processFactory;

    /** @var EnvironmentEntity */
    protected $environment;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->prophet = new Prophet();
        $this->validator = $this->prophet->prophesize(ValidatorInterface::class);
        $this->processFactory = $this->prophet->prophesize(ProcessFactory::class);

        $this->createLocation();
        mkdir($this->location.'/var/docker', 0777, true);
        $this->environment = new EnvironmentEntity('foo', $this->location, EnvironmentEntity::TYPE_SYMFONY, null, true);

        $filesystem = new Filesystem();
        $filesystem->mirror(__DIR__.'/../../../src/Resources/symfony/', $this->location.'/var/docker');
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->prophet->checkPredictions();
        $this->removeLocation();
    }

    /**
     * Defines successful validations to use within tests related to Docker Compose.
     */
    public function prophesizeSuccessfulValidations(): void
    {
        (new MethodProphecy($this->validator, 'validate', [Argument::any(), new DotEnvExists()]))
            ->shouldBeCalledOnce()
            ->willReturn(new ConstraintViolationList())
        ;

        (new MethodProphecy($this->validator, 'validate', [Argument::any(), new ConfigurationFiles()]))
            ->shouldBeCalledOnce()
            ->willReturn(new ConstraintViolationList())
        ;
    }

    /**
     * Retrieves fake environment variables to use within tests related to Docker Compose.
     */
    public function getFakeEnvironmentVariables(): array
    {
        return [
            'COMPOSE_FILE' => $this->location.'/var/docker/docker-compose.yml',
            'COMPOSE_PROJECT_NAME' => $this->environment->getType().'_'.$this->environment->getName(),
            'DOCKER_PHP_IMAGE' => '',
            'PROJECT_LOCATION' => $this->location,
        ];
    }
}
