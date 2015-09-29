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


    protected static function callMethod($obj, $name, array $args)
    {
        $class = new \ReflectionClass($obj);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method->invokeArgs($obj, $args);
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

    public function testSetSubItems()
    {
        // new clean instance
        $producer = new Producer();

        $queue = new \Cronario\Queue();
        $logger = new \Cronario\Logger\Journal();
        $storage = new \Cronario\Storage\Redis();
        $redis = new \Predis\Client();
        $config = [
            Producer::CONFIG_SLEEP_MAIN_LOOP => 999
        ];

        self::callMethod($producer, 'setQueue', [$queue]);
        self::callMethod($producer, 'setLogger', [$logger]);
        self::callMethod($producer, 'setStorage', [$storage]);
        self::callMethod($producer, 'setRedis', [$redis]);
        self::callMethod($producer, 'setConfig', [$config]);

        $this->assertInstanceOf('\\Cronario\\Producer', $producer);
        $this->assertInstanceOf('\\Predis\\Client', $producer->getRedis());
        $this->assertInstanceOf('\\Cronario\\Storage\\StorageInterface', $producer->getStorage());
        $this->assertInstanceOf('\\Cronario\\Logger\\LoggerInterface', $producer->getLogger());
        $this->assertInstanceOf('\\Cronario\\Queue', $producer->getQueue());

        $cnf = self::callMethod($producer, 'getConfig', [null]);
        $this->assertTrue(is_array($cnf));

        $cnfMaxLoop = self::callMethod($producer, 'getConfig', [Producer::CONFIG_SLEEP_MAIN_LOOP]);
        $this->assertEquals(999, $cnfMaxLoop);
    }

    public function testDataManipulation()
    {
        // set / get
        self::callMethod($this->producer, 'setData', ['test-key', 'test-value']);
        $testValue = self::callMethod($this->producer, 'getData', ['test-key']);
        $this->assertEquals('test-value', $testValue);

        $allData = self::callMethod($this->producer, 'getData', [null]);
        $this->assertTrue(is_array($allData));
        $this->assertArrayHasKey('test-key', $allData);

        self::callMethod($this->producer, 'deleteData', ['test-key']);
        $allData = self::callMethod($this->producer, 'getData', [null]);
        $this->assertArrayNotHasKey('test-key', $allData);

        self::callMethod($this->producer, 'setData', ['test-inc', null]);
        self::callMethod($this->producer, 'incData', ['test-inc']);
        $eq_1 = self::callMethod($this->producer, 'getData', ['test-inc']);
        self::callMethod($this->producer, 'incData', ['test-inc', 9]);
        $eq_10 = self::callMethod($this->producer, 'getData', ['test-inc']);
        $this->assertEquals(1, $eq_1);
        $this->assertEquals(10, $eq_10);

        self::callMethod($this->producer, 'setData', ['test-dec', null]);
        self::callMethod($this->producer, 'decData', ['test-dec']);
        $eq_m1 = self::callMethod($this->producer, 'getData', ['test-dec']);
        self::callMethod($this->producer, 'decData', ['test-dec', 9]);
        $eq_m10 = self::callMethod($this->producer, 'getData', ['test-dec']);
        $this->assertEquals(-1, $eq_m1);
        $this->assertEquals(-10, $eq_m10);


        // self::callMethod($this->producer, 'cleanData', []);
    }


    public function testDataProcessId()
    {
        $pid = self::callMethod($this->producer, 'getProcessId', []);
        self::callMethod($this->producer, 'setProcessId', [999]);
        $mack_pid = self::callMethod($this->producer, 'getProcessId', []);
        self::callMethod($this->producer, 'setProcessId', [$pid]);
        $this->assertEquals(999, $mack_pid);

        $state = self::callMethod($this->producer, 'getState', []);
        self::callMethod($this->producer, 'setState', ['mock_state']);
        $mack_state = self::callMethod($this->producer, 'getState', []);
        self::callMethod($this->producer, 'setState', [$state]);
        $this->assertEquals('mock_state', $mack_state);
    }

    public function testDataUpdateInfo()
    {
        $allData = self::callMethod($this->producer, 'getData', []);
        $this->assertNotEquals('xxx', $allData[Producer::P_LAST_CIRCLE]);
        $this->assertNotEquals('xxx', $allData[Producer::P_MEMORY_USAGE]);
        $this->assertNotEquals('xxx', $allData[Producer::P_MEMORY_PEAK_USAGE]);

        self::callMethod($this->producer, 'setData', [Producer::P_LAST_CIRCLE, 'xxx']);
        self::callMethod($this->producer, 'setData', [Producer::P_MEMORY_USAGE, 'xxx']);
        self::callMethod($this->producer, 'setData', [Producer::P_MEMORY_PEAK_USAGE, 'xxx']);

        self::callMethod($this->producer, 'updateInfo', []);
        $allData = self::callMethod($this->producer, 'getData', []);

        $this->assertNotEquals('xxx', $allData[Producer::P_LAST_CIRCLE]);
        $this->assertNotEquals('xxx', $allData[Producer::P_MEMORY_USAGE]);
        $this->assertNotEquals('xxx', $allData[Producer::P_MEMORY_PEAK_USAGE]);
    }


    public function testManagerId()
    {
        $workerClass = '\\Class\\Test';
        $managerId = 999;

        $stringManagerId = self::callMethod($this->producer, 'buildManagerId', [$workerClass, $managerId]);
        $segments = self::callMethod($this->producer, 'parseManagerId', [$stringManagerId]);

        $this->assertEquals($this->producer->getAppId(), $segments[\Cronario\Manager::P_APP_ID]);
        $this->assertEquals($workerClass, $segments[\Cronario\Manager::P_WORKER_CLASS]);
        $this->assertEquals($managerId, $segments[\Cronario\Manager::P_ID]);
    }

    // Manager Set manipulation

    public function testCountManagerSetEmpty()
    {
        $countManagerSet = self::callMethod($this->producer, 'countManagerSet', []);

        $this->assertEquals(0, $countManagerSet);
    }

    public function testCleanManagerSetEmpty()
    {
        self::callMethod($this->producer, 'cleanManagerSet', []);
        $countManagerSet = self::callMethod($this->producer, 'countManagerSet', []);
        // updateManagerSet
        $this->assertEquals(0, $countManagerSet);
    }

    public function testUpdateManagerSetEmpty()
    {
        self::callMethod($this->producer, 'updateManagerSet', []);
        $countManagerSet = self::callMethod($this->producer, 'countManagerSet', []);

        $this->assertEquals(0, $countManagerSet);
    }


    public function testCalcManagerSize()
    {
        // calcManagerSize( $countJobsReady, $managerPoolSize, $managersLimit );

        $size = self::callMethod($this->producer, 'calcManagerSize', [1, 1, 1]);
        $this->assertEquals(1, $size);

        $size = self::callMethod($this->producer, 'calcManagerSize', [100, 1, 1]);
        $this->assertEquals(1, $size);

        $size = self::callMethod($this->producer, 'calcManagerSize', [99, 100, 100]);
        $this->assertEquals(1, $size);

        $size = self::callMethod($this->producer, 'calcManagerSize', [199, 100, 100]);
        $this->assertEquals(2, $size);

        $size = self::callMethod($this->producer, 'calcManagerSize', [999, 1, 99]);
        $this->assertEquals(99, $size);

    }


    public function testRun()
    {
        // NOTE :

        // cant do like this
        // cause method start will starts main loop (forever loop)
        // The producer starts on bootstrap testing

//         $this->producer->start();
//         $this->assertTrue($this->producer->isStateStart());
//         $this->producer->stop();

        $this->assertTrue($this->producer->isStateStart());
    }
}
