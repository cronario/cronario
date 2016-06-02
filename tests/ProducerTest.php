<?php


namespace Cronario\Test;

use Cronario\Facade;
use Cronario\Producer;

class ProducerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Producer
     */
    protected $producer;

    public function setUp()
    {
        // adds default producer to facade
        Facade::addProducer(new Producer());

        $this->producer = Facade::getProducer();
    }

    public function tearDown()
    {
        Facade::cleanProducers();
    }


    public function testResourcesInstance()
    {
        $this->assertInstanceOf('\\Cronario\\Producer', $this->producer);
        $this->assertEquals(Producer::DEFAULT_APP_ID, $this->producer->getAppId());
        $this->assertInstanceOf('\\Predis\\Client', $this->producer->getRedis());
        $this->assertInstanceOf('\\Cronario\\Storage\\StorageInterface', $this->producer->getStorage());
        $this->assertInstanceOf('\\Psr\\Log\\LoggerInterface', $this->producer->getLogger());
        $this->assertInstanceOf('\\Cronario\\Queue', $this->producer->getQueue());
    }
//
//    public function testSetSubItems()
//    {
//        // new clean instance
//        $producer = new Producer([
//            Producer::P_APP_ID => 'custom-app-id'
//        ]);
//
//        $queue = new \Cronario\Queue();
//        $logger = new \Cronario\Logger\Journal();
//        $storage = new \Cronario\Storage\Redis();
//        $redis = new \Predis\Client();
//        $config = [
//            Producer::CONFIG_SLEEP_MAIN_LOOP => 999
//        ];
//
//        Helpers::callMethod($producer, 'setQueue', [$queue]);
//        Helpers::callMethod($producer, 'setLogger', [$logger]);
//        Helpers::callMethod($producer, 'setStorage', [$storage]);
//        Helpers::callMethod($producer, 'setRedis', [$redis]);
//        Helpers::callMethod($producer, 'setConfig', [$config]);
//
//        $this->assertInstanceOf('\\Cronario\\Producer', $producer);
//        $this->assertInstanceOf('\\Predis\\Client', $producer->getRedis());
//        $this->assertInstanceOf('\\Cronario\\Storage\\StorageInterface', $producer->getStorage());
//        $this->assertInstanceOf('\\Cronario\\Logger\\LoggerInterface', $producer->getLogger());
//        $this->assertInstanceOf('\\Cronario\\Queue', $producer->getQueue());
//
//        $cnf = Helpers::callMethod($producer, 'getConfig', [null]);
//        $this->assertTrue(is_array($cnf));
//
//        $cnfMaxLoop = Helpers::callMethod($producer, 'getConfig', [Producer::CONFIG_SLEEP_MAIN_LOOP]);
//        $this->assertEquals(999, $cnfMaxLoop);
//    }
//
//    public function testDataManipulation()
//    {
////        // set / get
////        Helpers::callMethod($this->producer, 'setData', ['test-key', 'test-value']);
////        $testValue = Helpers::callMethod($this->producer, 'getData', ['test-key']);
////        $this->assertEquals('test-value', $testValue);
////
////        $allData = Helpers::callMethod($this->producer, 'getData', [null]);
////        $this->assertTrue(is_array($allData));
////        $this->assertArrayHasKey('test-key', $allData);
////
////        Helpers::callMethod($this->producer, 'deleteData', ['test-key']);
////        $allData = Helpers::callMethod($this->producer, 'getData', [null]);
////        $this->assertArrayNotHasKey('test-key', $allData);
////
////        Helpers::callMethod($this->producer, 'setData', ['test-inc', null]);
////        Helpers::callMethod($this->producer, 'incData', ['test-inc']);
////        $eq_1 = Helpers::callMethod($this->producer, 'getData', ['test-inc']);
////        Helpers::callMethod($this->producer, 'incData', ['test-inc', 9]);
////        $eq_10 = Helpers::callMethod($this->producer, 'getData', ['test-inc']);
////        $this->assertEquals(1, $eq_1);
////        $this->assertEquals(10, $eq_10);
////
////        Helpers::callMethod($this->producer, 'setData', ['test-dec', null]);
////        Helpers::callMethod($this->producer, 'decData', ['test-dec']);
////        $eq_m1 = Helpers::callMethod($this->producer, 'getData', ['test-dec']);
////        Helpers::callMethod($this->producer, 'decData', ['test-dec', 9]);
////        $eq_m10 = Helpers::callMethod($this->producer, 'getData', ['test-dec']);
////        $this->assertEquals(-1, $eq_m1);
////        $this->assertEquals(-10, $eq_m10);
//
//
//        // Helpers::callMethod($this->producer, 'cleanData', []);
//    }
//
//
////    public function testDataProcessId()
////    {
////        $pid = Helpers::callMethod($this->producer, 'getProcessId', []);
////        Helpers::callMethod($this->producer, 'setProcessId', [999]);
////        $mack_pid = Helpers::callMethod($this->producer, 'getProcessId', []);
////        Helpers::callMethod($this->producer, 'setProcessId', [$pid]);
////        $this->assertEquals(999, $mack_pid);
////
////        $state = Helpers::callMethod($this->producer, 'getState', []);
////        Helpers::callMethod($this->producer, 'setState', ['mock_state']);
////        $mack_state = Helpers::callMethod($this->producer, 'getState', []);
////        Helpers::callMethod($this->producer, 'setState', [$state]);
////        $this->assertEquals('mock_state', $mack_state);
////    }
//
//    public function testDataUpdateInfo()
//    {
////        $allData = Helpers::callMethod($this->producer, 'getData', []);
////        // $this->assertNotEquals('xxx', $allData[Producer::P_LAST_CIRCLE]);
//////        $this->assertNotEquals('xxx', $allData[Producer::P_MEMORY_USAGE]);
//////        $this->assertNotEquals('xxx', $allData[Producer::P_MEMORY_PEAK_USAGE]);
////
////        Helpers::callMethod($this->producer, 'setData', [Producer::P_LAST_CIRCLE, 'xxx']);
////        Helpers::callMethod($this->producer, 'setData', [Producer::P_MEMORY_USAGE, 'xxx']);
////        Helpers::callMethod($this->producer, 'setData', [Producer::P_MEMORY_PEAK_USAGE, 'xxx']);
////
////        Helpers::callMethod($this->producer, 'updateInfo', []);
////        $allData = Helpers::callMethod($this->producer, 'getData', []);
////
////        $this->assertNotEquals('xxx', $allData[Producer::P_LAST_CIRCLE]);
////        $this->assertNotEquals('xxx', $allData[Producer::P_MEMORY_USAGE]);
////        $this->assertNotEquals('xxx', $allData[Producer::P_MEMORY_PEAK_USAGE]);
//    }
//
//
//    public function testManagerId()
//    {
//        $workerClass = '\\Class\\Test';
//        $managerId = 999;
//
//        $stringManagerId = Helpers::callMethod($this->producer, 'buildManagerId', [$workerClass, $managerId]);
//        $segments = Helpers::callMethod($this->producer, 'parseManagerId', [$stringManagerId]);
//
//        $this->assertEquals($this->producer->getAppId(), $segments[\Cronario\Manager::P_APP_ID]);
//        $this->assertEquals($workerClass, $segments[\Cronario\Manager::P_WORKER_CLASS]);
//        $this->assertEquals($managerId, $segments[\Cronario\Manager::P_ID]);
//    }
//
//    // Manager Set manipulation
//
//    public function testCountManagerSetEmpty()
//    {
//        $countManagerSet = Helpers::callMethod($this->producer, 'countManagerSet', []);
//
//        $this->assertEquals(0, $countManagerSet);
//    }
//
//    public function testCleanManagerSetEmpty()
//    {
//        Helpers::callMethod($this->producer, 'cleanManagerSet', []);
//        $countManagerSet = Helpers::callMethod($this->producer, 'countManagerSet', []);
//        // updateManagerSet
//        $this->assertEquals(0, $countManagerSet);
//    }
//
////    public function testUpdateManagerSetEmpty()
////    {
////        Helpers::callMethod($this->producer, 'updateManagerSet', []);
////        $countManagerSet = Helpers::callMethod($this->producer, 'countManagerSet', []);
////
////        $this->assertEquals(0, $countManagerSet);
////    }
//
//
    public function testCalcManagerSize()
    {
        // calcManagerSize( $countJobsReady, $managerPoolSize, $managersLimit );

        $size = Helpers::callMethod($this->producer, 'calcManagerSize', [1, 1, 1]);
        $this->assertEquals(1, $size);

        $size = Helpers::callMethod($this->producer, 'calcManagerSize', [100, 1, 1]);
        $this->assertEquals(1, $size);

        $size = Helpers::callMethod($this->producer, 'calcManagerSize', [99, 100, 100]);
        $this->assertEquals(1, $size);

        $size = Helpers::callMethod($this->producer, 'calcManagerSize', [199, 100, 100]);
        $this->assertEquals(2, $size);

        $size = Helpers::callMethod($this->producer, 'calcManagerSize', [999, 1, 99]);
        $this->assertEquals(99, $size);

    }


//    public function testRun()
//    {
//        // NOTE :
//
//        // cant do like this
//        // cause method start will starts main loop (forever loop)
//        // The producer starts on bootstrap testing
//
////         $this->producer->start();
////         $this->assertTrue($this->producer->isStateStart());
////         $this->producer->stop();
//
////        $this->assertTrue($this->producer->isStateStart());
//    }
}
