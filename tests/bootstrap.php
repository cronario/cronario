<?php

date_default_timezone_set('Europe/Kiev');

require __DIR__."/../vendor/autoload.php";

/**
* Bootstrapping application
*/

shell_exec('php startDaemon.php > /dev/null &');

\Cronario\Facade::addProducer(new \Cronario\Producer());