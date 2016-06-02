<?php

require_once "bootstrap.php";

use \Cronario\Facade as Facade;
use \Cronario\Producer as Producer;
use \Cronario\Logger\Journal as LoggerJournal;
use \Cronario\Queue as Queue;

\Result\ResultException::setClassIndexMap([
    'Cronario\\Exception\\ResultException' => 1,
    'Cronario\\Test\\ResultException'      => 2,
]);

Facade::addProducer(
    new Producer([
        Producer::P_APP_ID => \Cronario\Test\FlowTest::TEST_PRODUCER_FLOW_ID,
        Producer::P_CONFIG => [
            Producer::CONFIG_SLEEP_MAIN_LOOP           => 1,
            Producer::CONFIG_SLEEP_STOP_LOOP           => 1,
            Producer::CONFIG_SLEEP_FINISH_MANAGER_LOOP => 1,
            Producer::CONFIG_BOOTSTRAP_FILE            => __FILE__,
        ],
        Producer::P_LOGGER => new LoggerJournal(),
        Producer::P_QUEUE  => new Queue(),
        Producer::P_REDIS  => new \Predis\Client()
    ])
);


if (isset($argv) && count($argv) > 1) {
    $action = $argv[1];
    $producer = \Cronario\Facade::getProducer(\Cronario\Test\FlowTest::TEST_PRODUCER_FLOW_ID);

    if ($action == 'start') {
        echo 'START ...' . PHP_EOL;
        $producer->start();
    } elseif ($action == 'kill') {
        echo 'KILL ...' . PHP_EOL;
        $producer->kill();
    } elseif ($action == 'kill') {
        echo 'KILL ...' . PHP_EOL;
        $producer->kill();
    } elseif ($action == 'stats') {
        echo 'STATS ...' . PHP_EOL;
        print_r($producer->getStats());
    }
}