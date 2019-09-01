<?php

declare(strict_types=1);

namespace App;

use App\Exception\InvalidConfigurationException;
use Exception;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\RouteCollectionBuilder;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    private const CONFIG_EXTS = '.{php,xml,yaml,yml}';

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public function registerBundles(): iterable
    {
        /** @noinspection PhpIncludeInspection */
        $contents = require $this->getProjectDir().'/config/bundles.php';

        foreach ($contents as $class => $envs) {
            if ($envs[$this->environment] ?? $envs['all'] ?? false) {
                yield new $class();
            }
        }
    }

    /**
     * Retrieves the custom directory located in the HOME directory of the current user.
     *
     * @throws InvalidConfigurationException
     *
     * @return string
     */
    public function getCustomDir(): string
    {
        $home = PHP_OS_FAMILY !== 'Windows' ? getenv('HOME') : $_SERVER['HOMEDRIVE'].$_SERVER['HOMEPATH'];

        if (\is_string($home) && $home !== '') {
            $home = rtrim($home, \DIRECTORY_SEPARATOR);
        } else {
            throw new InvalidConfigurationException('Unable to determine the home directory.');
        }

        return "{$home}/.origami";
    }

    /**
     * {@inheritdoc}
     */
    public function getProjectDir(): string
    {
        return \dirname(__DIR__);
    }

    /**
     * Checks whether the application is currently run as a PHAR.
     *
     * @return bool
     */
    public function isArchiveContext(): bool
    {
        return strpos($this->getProjectDir(), 'phar://') !== false;
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidConfigurationException
     */
    public function getCacheDir()
    {
        return $this->isArchiveContext() ? $this->getCustomDir().'/cache' : parent::getCacheDir();
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidConfigurationException
     */
    public function getLogDir()
    {
        return $this->isArchiveContext() ? $this->getCustomDir().'/log' : parent::getLogDir();
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     *
     * @throws Exception
     */
    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $container->addResource(new FileResource($this->getProjectDir().'/config/bundles.php'));
        $container->setParameter('container.dumper.inline_class_loader', true);
        $confDir = $this->getProjectDir().'/config';

        $loader->load($confDir.'/{packages}/*'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{packages}/'.$this->getEnvironment().'/**/*'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{services}'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{services}_'.$this->getEnvironment().self::CONFIG_EXTS, 'glob');
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     *
     * @throws Exception
     */
    protected function configureRoutes(RouteCollectionBuilder $routes): void
    {
        $confDir = $this->getProjectDir().'/config';

        $routes->import($confDir.'/{routes}/'.$this->getEnvironment().'/**/*'.self::CONFIG_EXTS, '/', 'glob');
        $routes->import($confDir.'/{routes}/*'.self::CONFIG_EXTS, '/', 'glob');
        $routes->import($confDir.'/{routes}'.self::CONFIG_EXTS, '/', 'glob');
    }
}
