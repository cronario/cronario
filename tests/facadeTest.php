<?php


namespace Cronario\Test;

use \Cronario\Facade;
use \Cronario\Producer;


class FacadeTest extends \PHPUnit_Framework_TestCase
{

    protected function tearDown()
    {
        Facade::cleanProducers();
    }


    public function testAddProducerDefault()
    {
        Facade::addProducer(new Producer());

        $producer = Facade::getProducer();
        $this->assertInstanceOf('\\Cronario\\Producer', $producer);
        $this->assertEquals(Producer::DEFAULT_APP_ID, $producer->getAppId());
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


}
