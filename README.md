# origami

<p>
  <img src="https://img.shields.io/packagist/php-v/ajardin/origami" alt="PHP version support"/>

  <a href="https://shepherd.dev/github/origamiphp/source" target="_blank">
    <img src="https://shepherd.dev/github/origamiphp/source/coverage.svg" alt="Shepherd results"/>
  </a>

  <a href="https://codecov.io/gh/origamiphp/source" target="_blank">
    <img src="https://img.shields.io/codecov/c/github/origamiphp/source?label=code-coverage" alt="Codecov results"/>
  </a>

  <a href="https://packagist.org/packages/ajardin/origami" target="_blank">
    <img src="https://img.shields.io/packagist/dt/ajardin/origami" alt="Packagist downloads"/>
  </a>
</p>

This toolbox helps you manage local Docker environments for different type of PHP projects. There is a built-in skeleton
for [Drupal][drupal], [Magento 2][magento], [OroCommerce][orocommerce], [Sylius][sylius], and [Symfony][symfony].

It allows you to create a new environment from scratch without impacting the source code of your project. It also
offers the ability to have a global overview of all installed environments and perform actions without having to be in
the project directory. Native commands are still available since this is a wrapper of [Docker Compose][docker-compose].

## 📦 Prerequisites
* [Docker][docker-engine] and [Docker Compose][docker-compose].
* [Mutagen][mutagen] to improve performance because Docker can be [painfully slow on macOS][issue] with some projects.
* [mkcert][mkcert] (optional) to make locally trusted development certificates because the HTTPS has become the norm.

**Last but not least, this package currently only supports macOS**.

## 🛠 Installing
You can install `origami` with Composer like any PHP dependency.
```shell script
composer global require ajardin/origami
```

Once you have installed the binary, you can check the status of the application requirements.
```shell script
origami --verbose
```

## 🚀 Getting Started
1. Open a terminal in the directory of your project
2. Run `origami install`
3. Configure the environment of your choice
4. Run `origami start`
5. Open your favorite browser on your custom domain or https://127.0.0.1/

The `var/docker/` directory of your project contains the environment configuration. Feel free to edit it at your
convenience; it is your configuration now. 😉

## 🪄 Available Commands

### `origami data`
Shows real-time usage statistics of the running environment.

### `origami database:dump`
Generates a database dump of the running environment.

### `origami database:restore`
Restores a database dump of the running environment.

### `origami debug`
Shows system information and the configuration of the current environment.

### `origami install`
Installs an environment for the project in the current directory.

### `origami logs [--tail=XX] [service]`
Shows the logs generated in real-time by the running environment.

By default, this command only shows new entries. You can use the `--tail=XX` option to view previous entries, and filter
the output by specifying the name of a service (`php` for example).

### `origami php`
Opens a terminal on the `php` service to interact with it.

### `origami prepare`
Prepares Docker images (i.e. pull and build) of a previously installed environment.

### `origami ps`
Shows the status of the running environment services.

### `origami registry`
Shows the list and status of all previously installed environments.

### `origami restart`
Restarts an environment previously started.

### `origami root`
Shows instructions for configuring your terminal to manually use Docker commands.

### `origami start`
Starts an environment previously installed.

### `origami stop`
Stops an environment previously started.

### `origami uninstall`
Uninstalls an environment by deleting all Docker data and associated configuration.

You can either run this command from the directory where the project has been installed, or pass its name as an
argument to the command.

### `origami update`
Updates the configuration of a previously installed environment.

This command must be run after each `origami` update to ensure that you benefit from all the latest improvements.  
**Your manual changes are overwritten by this action.**

## 🔍 Architecture
`origami` relies on [Symfony][symfony], a popular PHP framework.

Unlike common Symfony projects, we use [Box][box] to package the tool into a single binary file so that it's possible to
share it without installing all its dependencies. Otherwise, it could potentially bring conflicts if other tools with 
outdated dependencies are present on the local machine.

**Why PHP?** The main reason is that `origami` will focus on environments dedicated to PHP applications, and we would
like to facilitate the contribution process by using something well-known by our end-users.

## ✅ Testing
There is a `Makefile` with the most useful commands (e.g. fixing the coding style or running the tests).

```shell script
make
```

## 🤝 Contributing
Contributions, issues and feature requests are welcome! Feel free to check [issues page][contributions].

## 📝 License
Copyright © [Alexandre Jardin][me]. `origami` is an open-sourced software licensed under the [MIT](/LICENSE) license.

<!-- Resources -->
[box]: https://github.com/humbug/box/
[contributions]: https://github.com/origamiphp/source/issues
[docker-compose]: https://docs.docker.com/compose/
[docker-engine]: https://docs.docker.com/engine/
[drupal]: https://drupal.org/
[issue]: https://github.com/docker/for-mac/issues/1592
[magento]: https://magento.com/
[me]: https://github.com/ajardin
[mkcert]: https://github.com/FiloSottile/mkcert
[mutagen]: https://mutagen.io/
[orocommerce]: https://oroinc.com/
[sylius]: https://sylius.com/
[symfony]: https://symfony.com/
