<?php

namespace Cronario\Test;

use Cronario\AbstractWorker;

class WorkerTest extends \PHPUnit_Framework_TestCase
{

    public function setUp()
    {

    }

    public function tearDown()
    {

    }

    public function testFactoryExceptionClassNotExists()
    {
        $this->setExpectedException('\Cronario\Exception\WorkerException');

        AbstractWorker::factory('xxx');
    }


    public function testFactoryExceptionClassInstanceFail()
    {
        $this->setExpectedException('\Cronario\Exception\WorkerException');

        AbstractWorker::factory('\Cronario\Producer');
    }


    public function testFactory()
    {
        $testWorker = AbstractWorker::factory('\Cronario\Test\Worker');

        $this->assertInstanceOf('\Cronario\AbstractWorker', $testWorker);
    }


    public function testWorkerGetConfig()
    {
        $testWorker = AbstractWorker::factory('\Cronario\Test\Worker');

        $full = $testWorker->getConfig();
        $this->assertInternalType('array', $full);

        $item = $testWorker->getConfig(AbstractWorker::CONFIG_P_MANAGER_POOL_SIZE);
        $this->assertNotNull($item);
    }

}