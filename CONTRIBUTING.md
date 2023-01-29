# Contributing

Everyone is welcome to contribute code.

### 1. Development environment.

#### Requirements

- PHP >= 7.2
- Symfony application.

### 2. Creating Symfony Applications or use existing 

See [symfony docs](https://symfony.com/doc/current/setup.html)

```
git clone git@github.com:YOUR_GITHUB_NAME/packeton.git
git checkout -b fix/patch-1
```

### 3. Make a fork, install the dependencies.

Make a fork on GitHub, and then create a pull request to provide your changes.

Run composer install and make symlink with repo.

```
cd app
composer require okvpn/cron-bundle

git clone https://github.com/vtsykun/cron-bundle.git

rm -rf vendor/okvpn/cron-bundle
ln -s $PWD/cron-bundle vendor/okvpn/cron-bundle

```

