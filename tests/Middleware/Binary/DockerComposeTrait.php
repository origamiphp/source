<?php

declare(strict_types=1);

namespace App\Tests\Middleware\Binary;

use App\Entity\Environment;
use App\Helper\ProcessFactory;
use App\Validator\Constraints\ConfigurationFiles;
use App\Validator\Constraints\DotEnvExists;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

trait DockerComposeTrait
{
    /** @var ObjectProphecy|ValidatorInterface */
    private ObjectProphecy $validator;
    /** @var ObjectProphecy|ProcessFactory */
    private ObjectProphecy $processFactory;

    private Environment $environment;

    /**
     * Defines successful validations to use within tests related to Docker Compose.
     */
    public function prophesizeSuccessfulValidations(): void
    {
        $this->validator->validate(Argument::any(), new DotEnvExists())
            ->shouldBeCalledOnce()
            ->willReturn(new ConstraintViolationList())
        ;
        $this->validator->validate(Argument::any(), new ConfigurationFiles())
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
            'DOCKER_PHP_IMAGE' => 'default',
            'PROJECT_LOCATION' => $this->location,
        ];
    }
}
