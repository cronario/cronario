<?php


namespace Cronario\Test;

use \Cronario\Facade;
use \Cronario\Producer;


class FacadeTest extends \PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        \Cronario\Facade::addProducer(new \Cronario\Producer());
    }

    public function tearDown()
    {
        \Cronario\Facade::cleanProducers();
    }

    public function testAddProducerDefault()
    {
        $this->assertEquals(Producer::DEFAULT_APP_ID, Facade::getProducer()->getAppId());
    }


    public function testAddProducerCustom()
    {
        $appId = 'customAppId';

        Facade::addProducer(new Producer([
            Producer::P_APP_ID => $appId,
        ]));

        $producerCustom = Facade::getProducer($appId);
        $this->assertInstanceOf('\\Cronario\\Producer', $producerCustom);
        $this->assertEquals($appId, $producerCustom->getAppId());
    }


    public function testGetProducerUndefinedException()
    {
        $this->setExpectedException('\\Cronario\\Exception\\FacadeException');

        Facade::getProducer('undefined');
    }

    public function testAddProducerTwiceException()
    {
        $appId = 'customAppId';

        Facade::addProducer(new Producer([
            Producer::P_APP_ID => $appId,
        ]));

        $this->setExpectedException('\\Cronario\\Exception\\FacadeException');

        Facade::addProducer(new Producer([
            Producer::P_APP_ID => $appId,
        ]));

    }

    public function testGetStorage()
    {
        $appId = 'test';

        Facade::addProducer(new Producer([
            Producer::P_APP_ID => $appId,
        ]));

        $this->assertInstanceOf('\\Cronario\\Storage\\StorageInterface', Facade::getStorage($appId));
    }

    public function testGetDefaultProducerWithNullArgument()
    {
        $producer = Facade::getProducer(null);

        $this->assertEquals(Producer::DEFAULT_APP_ID, $producer->getAppId());
    }


    public function testGetProducersStats()
    {
        $producerStats = Facade::getProducersStats();

        $this->assertTrue(is_array($producerStats));
    }

    public function testGetQueuesStats()
    {
        $queuesStats = Facade::getQueuesStats();

        $this->assertTrue(is_array($queuesStats));
    }


    public function testGetJobsReserved()
    {
        $jobsReserved = Facade::getJobsReserved();

        $this->assertTrue(is_array($jobsReserved));
    }


    public function testGetManagersStats()
    {
        $managersStats = Facade::getManagersStats();

        $this->assertTrue(is_array($managersStats));
    }

}
