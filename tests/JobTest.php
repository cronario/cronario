<?php


namespace Cronario\Test;


use Cronario\AbstractJob;
use Result\ResultException;

class JobTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AbstractJob
     */
    private $job;

    public function setUp()
    {
        $this->job = new Job();

        \Cronario\Facade::addProducer(new \Cronario\Producer());

        ResultException::setClassIndexMap([
            'Cronario\\Exception\\ResultException' => 1,
            'Cronario\\Test\\ResultException'      => 2,
        ]);

    }

    public function tearDown()
    {
        \Cronario\Facade::cleanProducers();
    }


    protected static function callMethod($obj, $name, array $args)
    {
        $class = new \ReflectionClass($obj);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method->invokeArgs($obj, $args);
    }


    public function testJobCreate()
    {
        $job = new Job();

        $this->assertInstanceOf('\\Cronario\\AbstractJob', $job);
    }

    public function testJobDataCreate()
    {
        $job = new Job([
            Job::P_PARAMS  => [
                Job::P_PARAM_EXPECTED_RESULT => Job::P_PARAM_EXPECTED_RESULT_T_FAILURE,
                Job::P_PARAM_SLEEP           => 1,
            ],
            Job::P_COMMENT => 'comment-xxx',
            Job::P_AUTHOR  => 'author-xxx',
            Job::P_IS_SYNC => true,
            Job::P_DEBUG   => true,
        ]);

        $this->assertInstanceOf('\\Cronario\\AbstractJob', $job);

        $this->assertFalse($job->isStored());
        $this->assertEquals('comment-xxx', $job->getComment());
        $this->assertEquals('author-xxx', $job->getAuthor());
        $this->assertEquals(Job::P_PARAM_EXPECTED_RESULT_T_FAILURE, $job->getExpectedResult());
        $this->assertEquals(1, $job->getSleep());
        $this->assertTrue($job->isDebug());
        $this->assertTrue($job->isSync());
        $this->assertInternalType('int', $job->getStartOn());
        $this->assertInternalType('int', $job->getDeleteOn());
        $this->assertInternalType('int', $job->getExpiredOn());
        $this->assertEquals('\\Cronario\\Test\\Job', $job->getJobClass());

    }


    public function testJoStartOnExceptions()
    {
        $this->setExpectedException('\Cronario\Exception\JobException');

        $job = new Job([
            Job::P_COMMENT => 'comment-xxx',
            Job::P_AUTHOR  => 'author-xxx',
        ]);

        $job->setStartOn('xxx');
    }

    public function testJoDeleteOnExceptions()
    {
        $this->setExpectedException('\Cronario\Exception\JobException');

        $job = new Job([
            Job::P_COMMENT => 'comment-xxx',
            Job::P_AUTHOR  => 'author-xxx',
        ]);

        $job->setDeleteOn('xxx');
    }


    public function testJobDataCallbackCreate()
    {
        $job = new Job([
            Job::P_COMMENT  => 'comment-xxx',
            Job::P_AUTHOR   => 'author-xxx',
            Job::P_CALLBACK => [
                Job::P_CALLBACK_T_SUCCESS => [
                    new Job([
                        Job::P_COMMENT => 'comment-xxx / level 2',
                    ]),
                ]
            ]
        ]);

        $callbacksAll = $job->getCallback();
        $this->assertArrayHasKey(Job::P_CALLBACK_T_SUCCESS, $callbacksAll);


        $callbacksSuccess = $job->getCallback(Job::P_CALLBACK_T_SUCCESS);
        $this->assertInternalType('array', $callbacksSuccess);
        $this->assertEquals(1, count($callbacksSuccess));

        /** @var Job $callbackSingleJob */
        $callbackSingleJob = $callbacksSuccess[0];
        $this->assertInstanceOf('\\Cronario\\AbstractJob', $callbackSingleJob);
        $this->assertEquals('comment-xxx / level 2', $callbackSingleJob->getComment());
    }

    public function testSerializationJob()
    {
        $job = new Job([
            Job::P_COMMENT => 'comment-xxx',
            Job::P_AUTHOR  => 'author-xxx',
        ]);

        $packed = serialize($job);
        $unserializeJob = unserialize($packed);

        $this->assertInstanceOf('\\Cronario\\AbstractJob', $unserializeJob);
    }

    public function testCloneJob()
    {
        $commentSource = 'comment-xxx';

        $job = new Job([
            Job::P_COMMENT => $commentSource,
            Job::P_AUTHOR  => 'author-xxx',
        ]);

        $clonedJob = clone $job;
        $clonedComment = $clonedJob->getComment('comment-xxx');

        $this->assertInstanceOf('\\Cronario\\AbstractJob', $clonedJob);
        $this->assertEquals($commentSource, $clonedComment);
    }

    public function testGetQueueDelay()
    {
        $job = new Job([
            Job::P_IS_SYNC  => false,
            Job::P_START_ON => time() + 10,
        ]);

        $delay = $job->getQueueDelay();

        $this->assertLessThan(15, $delay);
        $this->assertGreaterThan(5, $delay);
    }


    public function testGetDataFull()
    {
        $job = new Job([
            Job::P_COMMENT => 'comment-xxx',
            Job::P_AUTHOR  => 'author-xxx',
        ]);

        $dataFull = $job->getData(null);
        $this->assertInternalType('array', $dataFull);

        $dataFull = $job->getData();
        $this->assertInternalType('array', $dataFull);
    }


    public function testUnsetData()
    {
        $commentSource = 'comment-xxx';

        $job = new Job([
            Job::P_COMMENT => $commentSource,
            Job::P_AUTHOR  => 'author-xxx',
        ]);

        $comment = $job->getData(Job::P_COMMENT);
        $this->assertEquals($comment, $commentSource);

        $job->unsetData(Job::P_COMMENT);
        $commentAfterDelete = $job->getData(Job::P_COMMENT);
        $this->assertNotEquals($comment, $commentAfterDelete);
    }


    public function testGetSetParam()
    {
        $job = new Job([
            Job::P_PARAMS  => [
                Job::P_PARAM_EXPECTED_RESULT => Job::P_PARAM_EXPECTED_RESULT_T_FAILURE,
                Job::P_PARAM_SLEEP           => 9,
            ],
            Job::P_COMMENT => 'comment-xxx',
            Job::P_AUTHOR  => 'author-xxx',
            Job::P_IS_SYNC => false,
        ]);

        $paramFull = $job->getParam(null);
        $this->assertInternalType('array', $paramFull);

        $paramFull = $job->getParam();
        $this->assertInternalType('array', $paramFull);

        $this->assertEquals(9, $job->getParam(Job::P_PARAM_SLEEP));

        $job->setParam(Job::P_PARAM_SLEEP, 100);
        $this->assertEquals(100, $job->getParam(Job::P_PARAM_SLEEP));
    }


    public function testSetId()
    {
        $id = 'my-id-xxx';

        $job = new Job([
            Job::P_COMMENT => 'comment-xxx',
            Job::P_AUTHOR  => 'author-xxx',
        ]);


        $this->assertFalse($job->isStored());
        $this->assertEquals(null, $job->getId());

        $job->setId($id);
        $this->assertEquals($id, $job->getId());
        $this->assertTrue($job->isStored());

        $this->setExpectedException('\\Cronario\\Exception\\JobException');
        $job->setId('new');
    }

    public function testSetSync()
    {
        $job = new Job([
            Job::P_COMMENT => 'comment-xxx',
            Job::P_AUTHOR  => 'author-xxx',
        ]);

        // default
        $this->assertFalse($job->isSync());

        $job->setSync(false);
        $this->assertFalse($job->isSync());
        $job->setSync(true);
        $this->assertTrue($job->isSync());
    }


    public function testGetSetWorkerClass()
    {
        $workerClass = '\\Custom\\Worker\\Class';

        $job = new Job([
            Job::P_COMMENT => 'comment-xxx',
            Job::P_AUTHOR  => 'author-xxx',
        ]);

        $job->setWorkerClass($workerClass);
        $this->assertEquals($workerClass, $job->getWorkerClass());
    }


    public function testGetAppId()
    {
        $appId = 'my-app-id-xxx';

        $job = new Job([
            Job::P_APP_ID  => $appId,
            Job::P_COMMENT => 'comment-xxx',
            Job::P_AUTHOR  => 'author-xxx',
        ]);

        $this->assertEquals($appId, $job->getAppId());
    }


    public function testGetSetSchedule()
    {
        $job = new Job([
            Job::P_COMMENT => 'comment-xxx',
            Job::P_AUTHOR  => 'author-xxx',
            Job::P_IS_SYNC => false,
        ]);

        $job->setSchedule('* * * * *');
        $this->assertEquals('* * * * *', $job->getSchedule());
        $this->assertGreaterThanOrEqual(0, $job->getScheduleDelay());
    }


    public function testGetSetAttempt()
    {
        $job = new Job([
            Job::P_COMMENT => 'comment-xxx',
            Job::P_AUTHOR  => 'author-xxx',
        ]);

        $this->assertEquals(0, $job->getAttempts());
        $this->assertEquals(0, $job->countAttemptQueueDelay());

        $job->setAttemptsMax(99);
        $this->assertEquals(99, $job->getAttemptsMax());

        $job->setAttemptStrategy(Job::P_ATTEMPT_STRATEGY_T_EXP);
        $this->assertEquals(Job::P_ATTEMPT_STRATEGY_T_EXP, $job->getAttemptStrategy());

        $job->setAttemptDelay(33);
        $this->assertEquals(33, $job->getAttemptDelay());

        $this->assertTrue($job->hasAttempt());

        $job->addAttempts();
        $this->assertEquals(1, $job->getAttempts());

        $job->addAttempts(10);
        $this->assertEquals(11, $job->getAttempts());

        $this->assertGreaterThan(33 * 11, $job->countAttemptQueueDelay());

        $job->addAttempts(100);
        $this->assertEquals(111, $job->getAttempts());
        $this->assertFalse($job->hasAttempt());

    }


    public function testGetSetPriority()
    {
        $job = new Job([
            Job::P_COMMENT => 'comment-xxx',
            Job::P_AUTHOR  => 'author-xxx',
        ]);

        // defaults
        $this->assertEquals(Job::P_PRIORITY_T_LOW, $job->getPriority());

        // hight
        $job->setPriority(Job::P_PRIORITY_T_HIGH);
        $this->assertEquals(Job::P_PRIORITY_T_HIGH, $job->getPriority());

        // low
        $job->setPriority(Job::P_PRIORITY_T_LOW);
        $this->assertEquals(Job::P_PRIORITY_T_LOW, $job->getPriority());

    }

    public function testJobResult()
    {
        $job = clone $this->job;

        $job->setAuthor('phpunit');
        $job->setComment('test');
        $job->setSchedule('* * * * *');
        $job->setWorkerClass('\\Cronario\\Test\\Worker');

        $this->assertInstanceOf('\\Result\\ResultException', $job());
    }

    /**
     * @expectedException \Cronario\Exception\JobException
     */
    public function testExpireOnException()
    {
        $date = new \DateTime('now');
        $this->job->setExpiredOn($date);
    }

    /**
     * @expectedException \Cronario\Exception\JobException
     */
    public function testCreateOnException()
    {
        $date = new \DateTime('now');
        $this->job->setCreateOn($date);
    }

    public function testJobIsDone()
    {
        $job = new Job([
            Job::P_PARAMS  => [
                Job::P_PARAM_EXPECTED_RESULT => Job::P_PARAM_EXPECTED_RESULT_T_FAILURE,
                Job::P_PARAM_SLEEP           => 1,
            ],
            Job::P_COMMENT => 'comment-xxx',
            Job::P_AUTHOR  => 'author-xxx',
            Job::P_IS_SYNC => true,
            Job::P_DEBUG   => true,
        ]);

        $job->setExpectedResult(Job::P_PARAM_EXPECTED_RESULT_T_SUCCESS);

        $this->assertTrue($job()->isSuccess());
    }


    public function testJobDebug()
    {
        $job = new Job([
            Job::P_COMMENT => 'comment-xxx',
            Job::P_AUTHOR  => 'author-xxx',
            Job::P_IS_SYNC => true,
        ]);

        $job->setDebug(false);
        $job->addDebugData('item1', 'value1');
        $this->assertFalse($job->isDebug());

        $job->setDebug(true);
        $job->addDebugData('item1', 'value1');
        $this->assertTrue($job->isDebug());
        $this->assertEquals(1, count($job->getDebugData()));

        self::callMethod($job, 'setDebugData', [
            [
                'item1' => 'value1',
                'item2' => 'value2',
            ]
        ]);
        $this->assertEquals(2, count($job->getDebugData()));
    }

    public function testJobParentId()
    {
        $job = new Job([
            Job::P_COMMENT => 'comment-xxx',
            Job::P_AUTHOR  => 'author-xxx',
        ]);

        $parentId = self::callMethod($job, 'getParentId', []);
        $this->assertNull($parentId);

        self::callMethod($job, 'setParentId', ['xxx']);
        $parentId = self::callMethod($job, 'getParentId', []);
        $this->assertEquals('xxx', $parentId);
    }

}
