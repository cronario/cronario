<?php

require_once __DIR__ . "/../vendor/autoload.php";


use \Cronario\Facade as Facade;
use \Cronario\Producer as Producer;
use \Cronario\Logger\Journal as LoggerJournal;
use \Cronario\Queue as Queue;

Facade::addProducer(
    new Producer([
        Producer::P_CONFIG => [
            Producer::CONFIG_SLEEP_MAIN_LOOP           => 2,
            Producer::CONFIG_SLEEP_STOP_LOOP           => 2,
            Producer::CONFIG_SLEEP_FINISH_MANAGER_LOOP => 2,
        ],
        Producer::P_LOGGER => new LoggerJournal([
            LoggerJournal::P_CONSOLE_LEVEL => LoggerJournal::LEVEL_DEBUG,
            LoggerJournal::P_JOURNAL_LEVEL => LoggerJournal::LEVEL_DEBUG,
        ]),
        Producer::P_QUEUE  => new Queue(),
        Producer::P_REDIS  => new \Predis\Client()
    ])
);

$producer = \Cronario\Facade::getProducer();
echo $producer->getAppId() . PHP_EOL;
$producer->start();
