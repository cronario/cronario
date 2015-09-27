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


    /**
     * @expectedException \Cronario\Exception\FacadeException
     */
    public function testAddProducerDefault()
    {
        Facade::addProducer(new Producer());
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

        $this->assertInstanceOf('\\Cronario\\Storage\\StorageInterface',Facade::getStorage($appId));
    }



}
