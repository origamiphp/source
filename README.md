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

`origami` is designed to help you manage local Docker environments for PHP applications.

It allows among other things to: install and manage a complete environment in a project, visualize status and logs of
the services, go inside a service, etc. It also offers the ability to have a global overview of all installed
environments and to perform the actions mentioned above without having to be in the project directory.

Basically, `origami` is an abstraction written in PHP of [Docker Compose][1] and [mkcert][2].

✨ Live Demo
------------
Because a picture is worth a thousand words...

<p align="center">
  <img src="https://gist.githubusercontent.com/ajardin/ec3d9487fc86bdc25a7dac74bf8a1d34/raw/c6c3b5792472fa4edd05a49d9bc9338d590ecf3a/origami.gif" alt="demo"/>
</p>

📦 Installing
-------------
You can install `origami` with Composer like any PHP dependency.
```
composer global require ajardin/origami
```

Once you have installed the binary, you can check the status of the application requirements.
```
origami --verbose
```

🚀 Getting Started
------------------
1. Open a terminal in the directory of your project
2. Run `origami install`
3. Configure the environment of your choice
4. Run `origami start`
5. Open your favorite browser on your custom domain, or https://127.0.0.1/

**Note:** The configuration of your new environment can be found in the `var/docker/` directory of your project. This
is your own configuration. Feel free to edit it at your convenience. 😉

✅ Testing
----------
There is a Makefile that provides all the continuous integration processes.

```
$ make

 ----------------------------------------------------------------------------
   ORIGAMI
 ----------------------------------------------------------------------------

box                            Compiles the project into a PHAR archive
phpcsfixer-audit               Fixes code style in all PHP files
phpcsfixer-fix                 Fixes code style in all PHP files
phpcpd                         Executes a copy/paste analysis
psalm                          Executes a static analysis on all PHP files
security                       Executes a security audit on all PHP dependencies
tests                          Executes the unit tests and functional tests
update                         Executes a Composer update within a PHP 7.3 environment
```

🔍 Architecture
---------------
`origami` is built on top of [Symfony][3], a popular PHP framework.

Unlike common Symfony projects, we use [Box][4] to package the tool into a single binary file. So that it's possible
to easily share it without installing all its dependencies, as it could potentially bring conflicts if other projects
with outdated dependencies are already globally installed on the local machine.

**Why PHP?** The main reason is that `origami` will focus on environments dedicated to PHP applications, and we would
like to facilitate the contribution process by using something well-known by our end-users.

🤝 Contributing
---------------
Contributions, issues and feature requests are welcome! Feel free to check [issues page][5].  
By the way, don't forget you can give a ⭐️ if this project helped you!

📝 License
----------
Copyright © [Alexandre Jardin][6]. This project is licensed under the [MIT][7] license.

<!-- Resources -->
[1]: https://docs.docker.com/compose/
[2]: https://github.com/FiloSottile/mkcert
[3]: https://symfony.com/
[4]: https://github.com/humbug/box/
[5]: https://github.com/ajardin/origami-source/issues
[6]: https://github.com/ajardin
[7]: https://github.com/ajardin/origami-source/blob/master/LICENSE
