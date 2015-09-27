<?php

date_default_timezone_set('Europe/Kiev');

require __DIR__ . "/../vendor/autoload.php";

/**
 * Bootstrapping application
 */

if (getenv('TRAVIS') != true) {
    shell_exec('php startDaemon.php > /dev/null &');
}
