<?php


namespace Cronario\Test;

use Cronario\Facade;
use Cronario\Producer;
use Cronario\Queue;

class QueueTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Producer
     */
    protected $producer;
    /**
     * @var Queue
     */
    protected $queue;

    public function setUp()
    {
        // adds default producer to facade
        Facade::addProducer(new Producer());

        $this->producer = Facade::getProducer();
        $this->queue = Facade::getProducer()->getQueue();
    }

    public function tearDown()
    {
        Facade::cleanProducers();
    }


    const TEST_QUEUE = '\Test\My\Queue';
    const TEST_JOB_ID = 'xxx';


    public function testGetProducerInstance()
    {
        $this->assertEquals(
            $this->producer->getAppId(),
            $this->queue->getProducer()->getAppId()
        );

        $this->assertInstanceOf('\Cronario\Producer', $this->queue->getProducer());
    }


    public function testStopStartQueue()
    {
        $this->assertFalse($this->queue->isStop(self::TEST_QUEUE));

        $this->queue->stop(self::TEST_QUEUE);

        $this->assertTrue($this->queue->isStop(self::TEST_QUEUE));

        $this->queue->start(self::TEST_QUEUE);

        $this->assertFalse($this->queue->isStop(self::TEST_QUEUE));
    }


    public function testPutReserveDeleteJob()
    {

        $this->assertFalse($this->queue->existsJob(self::TEST_JOB_ID));

        $jobCount = $this->queue->getJobCount(self::TEST_QUEUE, Queue::STATE_READY);
        $this->assertEquals(0, $jobCount);

        $this->queue->putJob(self::TEST_QUEUE, self::TEST_JOB_ID);

        $jobCount = $this->queue->getJobCount(self::TEST_QUEUE, Queue::STATE_READY);
        $this->assertEquals(1, $jobCount);

        $jobid = $this->queue->reserveJob(self::TEST_QUEUE);

        $this->queue->deleteJob($jobid);

        $jobCount = $this->queue->getJobCount(self::TEST_QUEUE, Queue::STATE_READY);
        $this->assertEquals(0, $jobCount);

        $queueInfo = $this->queue->getQueueInfo(self::TEST_QUEUE);
        $this->assertEquals(0, $queueInfo[Queue::STATS_JOBS_TOTAL]);

    }

    public function testPutBuryKickDeleteJob()
    {
        $this->queue->putJob(self::TEST_QUEUE, self::TEST_JOB_ID);

        $jobid = $this->queue->reserveJob(self::TEST_QUEUE);
        $this->assertEquals(self::TEST_JOB_ID, $jobid);

        $this->queue->buryJob(self::TEST_JOB_ID);
        $this->assertEquals(1, $this->queue->getJobCount(self::TEST_QUEUE, Queue::STATE_BURIED));

        $this->queue->kickJob(self::TEST_JOB_ID);
        $this->assertEquals(1, $this->queue->getJobCount(self::TEST_QUEUE, Queue::STATE_READY));

        $jobid = $this->queue->reserveJob(self::TEST_QUEUE);
        $this->assertEquals(self::TEST_JOB_ID, $jobid);

        $this->queue->deleteJob(self::TEST_JOB_ID);

        $queueInfo = $this->queue->getQueueInfo(self::TEST_QUEUE);
        $this->assertEquals(0, $queueInfo[Queue::STATS_JOBS_TOTAL]);

    }


    public function testPutReleaseDeleteJob()
    {
        $this->queue->putJob(self::TEST_QUEUE, self::TEST_JOB_ID);

        $jobid = $this->queue->reserveJob(self::TEST_QUEUE);
        $this->assertEquals(self::TEST_JOB_ID, $jobid);

        $this->queue->releaseJob(self::TEST_JOB_ID);
        $this->assertEquals(1, $this->queue->getJobCount(self::TEST_QUEUE, Queue::STATE_READY));

        $jobid = $this->queue->reserveJob(self::TEST_QUEUE);
        $this->assertEquals(self::TEST_JOB_ID, $jobid);

        $this->queue->deleteJob(self::TEST_JOB_ID);

        $queueInfo = $this->queue->getQueueInfo(self::TEST_QUEUE);
        $this->assertEquals(0, $queueInfo[Queue::STATS_JOBS_TOTAL]);


    }


    public function testMigrate()
    {
        $this->queue->putJob(self::TEST_QUEUE, self::TEST_JOB_ID , 1);

        sleep(2);

        $this->assertEquals(0, $this->queue->getJobCount(self::TEST_QUEUE, Queue::STATE_READY));

        $this->queue->migrate();

        $this->assertEquals(1, $this->queue->getJobCount(self::TEST_QUEUE, Queue::STATE_READY));

        $jobid = $this->queue->reserveJob(self::TEST_QUEUE);
        $this->assertEquals(self::TEST_JOB_ID, $jobid);
        $this->queue->deleteJob(self::TEST_JOB_ID);

        $queueInfo = $this->queue->getQueueInfo(self::TEST_QUEUE);
        $this->assertEquals(0, $queueInfo[Queue::STATS_JOBS_TOTAL]);
    }


//    public function testCleanAll()
//    {
//        $queue = $this->getQueueTest();
//        $queue->putJob(self::TEST_QUEUE, self::TEST_JOB_ID);
//
//        $statsQueue = $queue->getStats(self::TEST_QUEUE);
//        $this->assertEquals(1, $statsQueue[Queue::STATS_JOBS_TOTAL]);
//
//        // $queue->clean();
//
//        $statsQueue = $queue->getStats(self::TEST_QUEUE);
//        $this->assertEquals(0, $statsQueue[Queue::STATS_JOBS_TOTAL]);
//
//    }

}
