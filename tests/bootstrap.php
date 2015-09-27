<?php

require __DIR__."/../vendor/autoload.php";

/**
* Bootstrapping application
*/

shell_exec('php startDaemon.php > /dev/null &');

use \Cronario\Facade as Facade;
use \Cronario\Producer as Producer;

Facade::addProducer(new Producer());