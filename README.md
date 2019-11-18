<h1 align="center">Welcome to Origami 👋</h1>
<p align="center">
  <img alt="Status: Beta" src="https://img.shields.io/badge/status-beta-yellow" target="_blank" />
  
  <a href="https://codecov.io/gh/ajardin/origami">
    <img src="https://codecov.io/gh/ajardin/origami/branch/master/graph/badge.svg?token=eYBykVI0QK" />
  </a>

  <a href="https://github.com/ajardin/origami/blob/master/LICENSE">
    <img alt="License: MIT" src="https://img.shields.io/badge/license-MIT-blue.svg" target="_blank" />
  </a>
</p>

`origami` is designed to help you manage local Docker environments for PHP applications.

It allows among other things to: install a complete environment in a project, start/stop/restart it, visualize the
services logs, display the services status, go inside a service, etc. It also offers the ability to have a global
overview of all installed environments and to perform the actions mentioned above without having to be in the project
directory.

Basically, `origami` is an abstraction of [Docker](https://docs.docker.com/)
and [Docker Compose](https://docs.docker.com/compose/). But because Docker can be
[painfully slow on macOS](https://github.com/docker/for-mac/issues/1592) with projects which contain a large number of
files, there is an abstraction of [Mutagen](https://mutagen.io/) to improve I/O performance. And because the HTTPS has
become the norm, there is also an abstraction of [mkcert](https://github.com/FiloSottile/mkcert) to make locally trusted
development certificates.

### Disclaimer

As `origami` is still in its earlier stages, **the compatibility with other operating systems than macOS is not
guaranteed**, for the moment. The support of Linux and Windows could come in the near future. 

## 🔍 Architecture

`origami` is built on top of [Symfony](https://symfony.com/), a popular PHP framework. 

Unlike common Symfony projects, we use [Box](https://github.com/humbug/box/) to package the tool into a single binary
file. So that it's possible to easily share it without using Composer, as it could potentially bring conflicts if other
projects with outdated dependencies are already globally installed on the local machine.

**Why PHP?** The main reason is that `origami` will focus on environments for PHP applications (Magento & Symfony at
first), and we would like to facilitate the contributions process by using something well-known by our users.

## ✨ Demo

<p align="center">
  <img src="https://gist.githubusercontent.com/ajardin/ec3d9487fc86bdc25a7dac74bf8a1d34/raw/515b67168d87340612fd7cd51a4a13b8fc760dc8/origami.gif"
    width="700" alt="demo"/>
</p>

## 📦 Install

```sh
# Homebrew (recommended)
brew tap ajardin/formulae
brew install origami

# Manual
curl https://github.com/ajardin/origami/releases/latest/download/origami.phar --output origami
```

## 🚀 Usage

```sh
# List commands used to manage environments
origami 

# Execute a specific command
origami xxxxx

# Display the help message of a specific command
origami xxxxx --help

# List all available commands (i.e. Symfony included)
origami list
```

## ✅ Run tests

```sh
./bin/console doctrine:database:create --env=test
./bin/console doctrine:schema:create --env=test
./bin/console doctrine:fixtures:load --env=test

./bin/phpunit
```

## 🤝 Contributing

Contributions, issues and feature requests are welcome!
Feel free to check [issues page](https://github.com/ajardin/origami/issues).

By the way, don't forget you can give a ⭐️ if this project helped you!

## 📝 License

Copyright © 2019 [Alexandre Jardin](https://github.com/ajardin).
This project is licensed under the [MIT](https://github.com/ajardin/origami/blob/master/LICENSE) license.
