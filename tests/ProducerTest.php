<?php


namespace Cronario\Test;

use Cronario\Producer;

class ProducerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Producer
     */
    protected $producer = null;


    protected function setUp()
    {
        $this->producer = new Producer();
    }

    protected function tearDown()
    {
        $this->producer = null;
    }

    public function testResourcesInstance()
    {
        $this->assertInstanceOf('\\Cronario\\Producer', $this->producer);
        $this->assertEquals(Producer::DEFAULT_APP_ID, $this->producer->getAppId());
        $this->assertInstanceOf('\\Predis\\Client', $this->producer->getRedis());
        $this->assertInstanceOf('\\Cronario\\Storage\\StorageInterface', $this->producer->getStorage());
        $this->assertInstanceOf('\\Cronario\\Logger\\LoggerInterface', $this->producer->getLogger());
        $this->assertInstanceOf('\\Cronario\\Queue', $this->producer->getQueue());
    }


    public function testRun()
    {
        // NOTE :

        // cant do like this
        // cause method start will starts main loop (forever loop)

        // $this->producer->start();
        // $this->assertTrue($this->producer->isStateStart());
        // $this->producer->stop();

        $this->assertFalse($this->producer->isStateStart());
    }
}
