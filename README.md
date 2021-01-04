<h1 align="center">Welcome to Origami! 👋</h1>
<p align="center">
  <a href="https://codecov.io/gh/ajardin/origami-source" target="_blank">
    <img src="https://img.shields.io/codecov/c/github/ajardin/origami-source?style=for-the-badge" alt="Codecov"/>
  </a>

  <a href="https://github.com/ajardin/origami-source/blob/master/LICENSE" target="_blank">
    <img src="https://img.shields.io/github/license/ajardin/origami?color=blue&style=for-the-badge" alt="MIT license">
  </a>

  <a href="https://packagist.org/packages/ajardin/origami" target="_blank">
    <img src="https://img.shields.io/packagist/dt/ajardin/origami?style=for-the-badge" alt="Packagist downloads"/>
  </a>
</p>

`origami` manages local Docker environments for different PHP solutions.
* [Drupal][drupal]
* [Magento 2][magento]
* [OroCommerce][orocommerce]
* [Sylius][sylius]
* [Symfony][symfony]

It allows you to create a new environment from scratch without impacting the source code of your project. It also
offers the ability to have a global overview of all installed environments and perform actions without having to be in
the project directory. Native commands are still available since this is a wrapper of [Docker Compose][docker].

In addition to this abstraction, there are two others.
* [Mutagen][mutagen] to improve performance because Docker can be [painfully slow on macOS][issue] with some projects.
* [mkcert][mkcert] to make locally trusted development certificates because the HTTPS has become the norm.

✨ Live Demo
------------
Because a picture is worth a thousand words...

![Demo](/docs/origami.gif)

📦 Installing
-------------
> Before going any further, you need to install [Docker Compose][docker] and [Mutagen][mutagen] on your computer.  
> You should also install [mkcert][mkcert] if you want to benefit from all `origami` features.

You can install `origami` with Composer like any PHP dependency.
```shell script
composer global require ajardin/origami
```

Once you have installed the binary, you can check the status of the application requirements.
```shell script
origami --verbose
```

🚀 Getting Started
------------------
1. Open a terminal in the directory of your project
2. Run `origami install`
3. Configure the environment of your choice
4. Run `origami start`
5. Open your favorite browser on your custom domain or https://127.0.0.1/

**Note:** The `var/docker/` directory of your project contains the environment configuration. Feel free to edit it at
your convenience; it is your configuration now. 😉

✅ Testing
----------
There is a `Makefile` with the most common commands (e.g. fixing the coding style or running the tests).

```shell script
make
```

🔍 Architecture
---------------
`origami` relies on [Symfony][symfony], a popular PHP framework.

Unlike common Symfony projects, we use [Box][box] to package the tool into a single binary file so that it's possible to
share it without installing all its dependencies. Otherwise, it could potentially bring conflicts if other tools with 
outdated dependencies are present on the local machine.

**Why PHP?** The main reason is that `origami` will focus on environments dedicated to PHP applications, and we would
like to facilitate the contribution process by using something well-known by our end-users.

🤝 Contributing
---------------
Contributions, issues and feature requests are welcome! Feel free to check [issues page][contributions].

📝 License
----------
Copyright © [Alexandre Jardin][me]. `origami` is an open-sourced software licensed under the [MIT](/LICENSE) license.

<!-- Resources -->
[box]: https://github.com/humbug/box/
[contributions]: https://github.com/ajardin/origami-source/issues
[docker]: https://docs.docker.com/compose/
[drupal]: https://drupal.org/
[issue]: https://github.com/docker/for-mac/issues/1592
[magento]: https://magento.com/
[me]: https://github.com/ajardin
[mkcert]: https://github.com/FiloSottile/mkcert
[mutagen]: https://mutagen.io/
[orocommerce]: https://oroinc.com/
[sylius]: https://sylius.com/
[symfony]: https://symfony.com/
