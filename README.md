# Domain Drivers PHP

Prerequisites:

- PHP 8.3
- `decimal` extension (https://github.com/php-decimal/ext-decimal)
- Composer 

### Setup

The code below will install all dependencies and run the build script (code style check, static analysis and all tests):

```shell
composer install
composer ci
```


Alternatively you can use a docker image, then you have two options:

#### Build and run image locally

```shell
docker build -t domain-drivers .
docker run -it --rm -v "$PWD":/app -w /app domain-drivers bash
```

#### Use public image

```shell
docker run -it --rm -v "$PWD":/app -w /app akondas/domain-drivers bash
```

