<?php


namespace Cronario\Test;

use \Cronario\Facade;
use \Cronario\Producer;


class FacadeTest extends \PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        // adds defaul producer to facade
        Facade::addProducer(new Producer());
    }

    public function tearDown()
    {
        Facade::cleanProducers();
    }


    public function testAddProducerDefault()
    {
        $this->assertEquals(Producer::DEFAULT_APP_ID, Facade::getProducer()->getAppId());
    }


    public function testAddProducerCustom()
    {
        $appId = 'app-id-custom';

        Facade::addProducer(new Producer([
            Producer::P_APP_ID => $appId,
        ]));

        $producerCustom = Facade::getProducer($appId);
        $this->assertInstanceOf('\\Cronario\\Producer', $producerCustom);
        $this->assertEquals($appId, $producerCustom->getAppId());
    }


    public function testAddBuilderCustom()
    {
        $appId = 'app-id-builder-simple';

        Facade::addBuilder($appId, function ($appIdInsideCallback) {
            return new Producer([
                Producer::P_APP_ID => $appIdInsideCallback,
            ]);
        });

        $producer = Facade::getProducer($appId);
        $this->assertInstanceOf('\\Cronario\\Producer', $producer);
        $this->assertEquals($appId, $producer->getAppId());
    }

    public function testAddBuilderTwiceException()
    {
        $appId = 'app-exception-twice';

        Facade::addBuilder($appId, function ($appId) {
            return new Producer([
                Producer::P_APP_ID => $appId,
            ]);
        });

        $this->setExpectedException('\\Cronario\\Exception\\FacadeException');

        Facade::addBuilder($appId, function ($appId) {
            return new Producer([
                Producer::P_APP_ID => $appId,
            ]);
        });
    }

    public function testAddBuilderFunctionException()
    {
        $appId = 'app-exception-not-obj';

        Facade::addBuilder($appId, function ($appId) {
            return 'must return Producer object but we will return string';
        });

        $hasException = false;
        try {
            $producer = Facade::getProducer($appId);
        } catch (\Exception $ex) {
            $hasException = true;
        }

        $this->assertTrue($hasException);
    }

    public function testAddBuilderNotFunctionException()
    {
        $appId = 'app-exception-not-fn';

        $this->setExpectedException('\\Cronario\\Exception\\FacadeException');

        Facade::addBuilder($appId, 'string function !?!');
    }

    public function testGetProducerUndefinedException()
    {
        $this->setExpectedException('\\Cronario\\Exception\\FacadeException');

        Facade::getProducer('app-id-undefined');
    }

    public function testAddProducerTwiceException()
    {
        $appId = 'app-id-custom';

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
        $appId = 'app-id-custom';

        Facade::addProducer(new Producer([
            Producer::P_APP_ID => $appId,
        ]));

        $this->assertInstanceOf('\\Cronario\\Storage\\StorageInterface', Facade::getStorage($appId));
    }

    public function testGetDefaultProducerWithNullArgument()
    {
        $appId = 'app-id-custom';

        Facade::addProducer(new Producer([
            Producer::P_APP_ID => $appId,
        ]));

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
