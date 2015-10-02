<?php


namespace Cronario\Test;


class JobTest extends \PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        \Cronario\Facade::addProducer(new \Cronario\Producer());
    }

    public function tearDown()
    {
        \Cronario\Facade::cleanProducers();
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
        $this->assertNull($job->getFinishOn());
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

    public function testJobDataUseSetters()
    {
        $job = new Job();

        $job->setComment('comment-xxx');
        $job->setAuthor('author-xxx');

        $this->assertEquals('comment-xxx', $job->getComment());
        $this->assertEquals('author-xxx', $job->getAuthor());
    }

    public function testJobCreateOnExceptions()
    {
        $job = new Job();

        $this->setExpectedException('\Cronario\Exception\JobException');

        $job->setCreateOn('xxx');
    }

    public function testJobStartOnExceptions()
    {
        $job = new Job();

        $this->setExpectedException('\Cronario\Exception\JobException');

        $job->setStartOn('xxx');
    }

    public function testJobDeleteOnExceptions()
    {
        $job = new Job();

        $this->setExpectedException('\Cronario\Exception\JobException');

        $job->setDeleteOn('xxx');
    }

    public function testJobExpireOnException()
    {
        $job = new Job();

        $this->setExpectedException('\Cronario\Exception\JobException');

        $job->setExpiredOn('xxx');
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
            Job::P_COMMENT => $commentSource
        ]);

        $clonedJob = clone $job;

        $this->assertInstanceOf('\\Cronario\\AbstractJob', $clonedJob);
        $this->assertEquals($commentSource, $clonedJob->getComment());
    }

    public function testGetQueueDelay()
    {
        $job = new Job([
            Job::P_IS_SYNC  => false,
            Job::P_START_ON => time() + 10,
        ]);

        $delay = $job->getQueueDelay();

        $this->assertLessThan(12, $delay);
        $this->assertGreaterThan(8, $delay);

        $job2 = new Job([
            Job::P_IS_SYNC  => false,
            Job::P_START_ON => time() + 10,
            Job::P_SCHEDULE => '* * * * *',
        ]);

        $delay = $job2->getQueueDelay();
        $this->assertGreaterThan(8, $delay);

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

        $this->assertEquals($commentSource, $job->getData(Job::P_COMMENT));

        $job->unsetData(Job::P_COMMENT);
        $this->assertNotEquals($commentSource, $job->getData(Job::P_COMMENT));
        $this->assertNull($job->getData(Job::P_COMMENT));
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


        // param full set
        $this->assertInternalType('array', $job->getParam(null));

        // param full set
        $this->assertInternalType('array', $job->getParam());

        $this->assertEquals(9, $job->getParam(Job::P_PARAM_SLEEP));

        $job->setParam(Job::P_PARAM_SLEEP, 100);
        $this->assertEquals(100, $job->getParam(Job::P_PARAM_SLEEP));
    }

    public function testGetSetParams()
    {
        $job = new Job([

            Job::P_COMMENT => 'comment-xxx',
            Job::P_AUTHOR  => 'author-xxx',
            Job::P_IS_SYNC => false,
        ]);

        $job->setParam([
            Job::P_PARAM_EXPECTED_RESULT => Job::P_PARAM_EXPECTED_RESULT_T_FAILURE,
            Job::P_PARAM_SLEEP           => 9,
        ]);

        // param full set
        $this->assertInternalType('array', $job->getParam(null));

        // param full set
        $this->assertInternalType('array', $job->getParam());
    }


    const TEST_JOB_ID = 'job-id-xxx';

    public function testSetId()
    {
        $job = new Job();

        $this->assertFalse($job->isStored());
        $this->assertEquals(null, $job->getId());

        $job->setId(self::TEST_JOB_ID);

        $this->assertEquals(self::TEST_JOB_ID, $job->getId());
        $this->assertTrue($job->isStored());

    }


    public function testSetIdException()
    {

        $job = new Job();
        $job->setId(self::TEST_JOB_ID);

        $this->setExpectedException('\\Cronario\\Exception\\JobException');

        $job->setId('new-id-yyy');
    }


    public function testSetSync()
    {
        $job = new Job();

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

        $job = new Job();

        $job->setWorkerClass($workerClass);
        $this->assertEquals($workerClass, $job->getWorkerClass());
    }


    public function testGetAppId()
    {
        $appId = 'my-app-id-xxx';

        $job = new Job([
            Job::P_APP_ID => $appId
        ]);

        $this->assertEquals($appId, $job->getAppId());
    }


    public function testGetSetSchedule()
    {
        $job = new Job([
            Job::P_IS_SYNC => false
        ]);

        $job->setSchedule('* * * * *');

        $this->assertEquals('* * * * *', $job->getSchedule());
        $this->assertGreaterThanOrEqual(0, $job->getScheduleDelay());
    }


    public function testGetSetAttempt()
    {
        $job = new Job();

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
        $job = new Job();

        // defaults
        $this->assertEquals(Job::P_PRIORITY_T_LOW, $job->getPriority());

        // hight
        $job->setPriority(Job::P_PRIORITY_T_HIGH);
        $this->assertEquals(Job::P_PRIORITY_T_HIGH, $job->getPriority());

        // low
        $job->setPriority(Job::P_PRIORITY_T_LOW);
        $this->assertEquals(Job::P_PRIORITY_T_LOW, $job->getPriority());

    }

    public function testResult()
    {

        \Result\ResultException::setClassIndexMap([
            'Cronario\\Exception\\ResultException' => 1,
            'Cronario\\Test\\ResultException'      => 2,
        ]);

        $job = new Job();
        $result = new \Cronario\Test\ResultException(\Cronario\Test\ResultException::FAILURE_XXX);
        $job->setResult($result);

        // defaults
        $this->assertInstanceOf('\\Cronario\\Test\\ResultException', $job->getResult());

        // hight
        $job->setPriority(Job::P_PRIORITY_T_HIGH);
        $this->assertEquals(Job::P_PRIORITY_T_HIGH, $job->getPriority());

        // low
        $job->setPriority(Job::P_PRIORITY_T_LOW);
        $this->assertEquals(Job::P_PRIORITY_T_LOW, $job->getPriority());

    }



    public function testJobResult()
    {
        $job = new Job();

        $job->setWorkerClass('\\Cronario\\Test\\Worker');

        $result = $job();

        $this->assertInstanceOf('\\Result\\ResultException', $result);
    }

//
//    /**
//     * @expectedException \Cronario\Exception\JobException
//     */
//    public function testCreateOnException()
//    {
//        $date = new \DateTime('now');
//        $this->job->setCreateOn($date);
//    }
//
//    public function testJobIsDone()
//    {
//        $job = new Job([
//            Job::P_PARAMS  => [
//                Job::P_PARAM_EXPECTED_RESULT => Job::P_PARAM_EXPECTED_RESULT_T_FAILURE,
//                Job::P_PARAM_SLEEP           => 1,
//            ],
//            Job::P_COMMENT => 'comment-xxx',
//            Job::P_AUTHOR  => 'author-xxx',
//            Job::P_IS_SYNC => true,
//            Job::P_DEBUG   => true,
//        ]);
//
//        $job->setExpectedResult(Job::P_PARAM_EXPECTED_RESULT_T_SUCCESS);
//
//        $this->assertTrue($job()->isSuccess());
//    }
//
//
    public function testJobDebug()
    {
        $job = new Job([
            Job::P_IS_SYNC    => true,
            Job::P_DEBUG_DATA => [
                'item1' => 'value1',
                'item2' => 'value2',
            ]
        ]);

        $job->setDebug(false);
        $job->addDebugData('item3', 'value3');
        $this->assertFalse($job->isDebug());

        $job->setDebug(true);
        $job->addDebugData('item3', 'value3');
        $this->assertTrue($job->isDebug());


        $this->assertEquals(3, count($job->getDebugData()));
    }

    public function testJobParentId()
    {

        $job = new Job();
        $parentId = Helpers::callMethod($job, 'getParentId', []);
        $this->assertNull($parentId);


        $pid = 'parentId-xxx';
        $job = new Job([
            Job::P_PARENT_ID => $pid
        ]);
        $resultParentId = Helpers::callMethod($job, 'getParentId', []);
        $this->assertEquals($pid, $resultParentId);
    }

//    public function testSaveJob()
//    {
//        $job = new Job([
//            Job::P_COMMENT => 'comment-xxx',
//            Job::P_AUTHOR  => 'author-xxx',
//        ]);
//
//        $this->assertFalse($job->isStored());
//        $job->save();
//        $this->assertTrue($job->isStored());
//
//        $id = $job->getId();
//        $loadedJob = \Cronario\Facade::getProducer()->getStorage()->find($id);;
//        $this->assertInstanceOf('\\Cronario\\AbstractJob', $loadedJob);
//
//    }
//
////
////    public function testSaveJobMongo()
////    {
////
////        $appId = 'app-mongo-storage';
////
////        \Cronario\Facade::addProducer(new \Cronario\Producer([
////            \Cronario\Producer::P_APP_ID  => $appId,
////            \Cronario\Producer::P_STORAGE => new \Cronario\Storage\Mongo([
////                [
////                    'server'     => \Cronario\Storage\Mongo::CONFIG_SERVER_DEFAULT,
////                    'database'   => 'cronario',
////                    'collection' => 'jobs',
////                ]
////            ]),
////        ]));
////
////        $job = new Job([
////            Job::P_COMMENT => 'comment-xxx',
////            Job::P_AUTHOR  => 'author-xxx',
////            Job::P_APP_ID  => $appId,
////        ]);
////
////        $this->assertFalse($job->isStored());
////        $job->save();
////        $this->assertTrue($job->isStored());
////
////        $id = $job->getId();
////        $loadedJob = \Cronario\Facade::getProducer($appId)->getStorage()->find($id);;
////        $this->assertInstanceOf('\\Cronario\\AbstractJob', $loadedJob);
////
////    }


}
