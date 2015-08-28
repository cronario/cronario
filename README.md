# Cronario 


[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-travis]][link-travis]
[![Coverage Status][ico-scrutinizer]][link-scrutinizer]
[![Quality Score][ico-code-quality]][link-code-quality]
[![Total Downloads][ico-downloads]][link-downloads]

**Cronario** -  do jobs like a boss
- Management tasks (asinhronnoo and synchronous)
- Tasks with exotic schedule

## Install

Via Composer

``` bash
$ composer require cronario/cronario
```

## Usage



bootstrap.php
```php
// main file where presents all producers

$producer = new Producer(); // by defaults appId = 'default'

Facade::addProducer($producer);
```


simpleDaemon.php
```php
// this file you should execute from cli 
// $ php /.../simpleDaemon.php

include('bootstrap.php');

$producer = Facade::getProducer();
$producer->start();
```


applicationPage-1.php
```php
include('bootstrap.php');

$job = new Job([
    /* ... */
]);

$result = $job();

/*
    $result = [ ... ];
*/

```

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.


## Credits

- [Vlad Groznov][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/cronario/cronario.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/cronario/cronario/develop.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/cronario/cronario.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/cronario/cronario.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/cronario/cronario.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/cronario/cronario
[link-travis]: https://travis-ci.org/cronario/cronario
[link-scrutinizer]: https://scrutinizer-ci.com/g/cronario/cronario/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/cronario/cronario
[link-downloads]: https://packagist.org/packages/cronario/cronario
[link-author]: https://github.com/vlad-groznov
[link-contributors]: ../../contributors



