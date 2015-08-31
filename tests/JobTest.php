<?php


namespace Cronario\Test;

use \Cronario\Example\Job;

class JobTest extends \PHPUnit_Framework_TestCase
{

//    protected function setUp()
//    {
//        \Ik\Lib\Exception\ResultException::setClassIndexMap([
//            'Cronario\\Exception\\ResultException' => 1,
//        ]);
//    }

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
        $this->assertEquals('\\Cronario\\Example\\Job', $job->getJobClass());

    }


    public function testJobDataCallbackCreate()
    {
        $job = new Job([
            Job::P_PARAMS   => [
                Job::P_PARAM_EXPECTED_RESULT => Job::P_PARAM_EXPECTED_RESULT_T_SUCCESS,
                Job::P_PARAM_SLEEP           => 1,
            ],
            Job::P_COMMENT  => 'comment-xxx',
            Job::P_AUTHOR   => 'author-xxx',
            Job::P_IS_SYNC  => true,
            Job::P_DEBUG    => true,
            Job::P_CALLBACK => [
                Job::P_CALLBACK_T_SUCCESS => [
                    new Job([
                        Job::P_PARAMS  => [
                            Job::P_PARAM_EXPECTED_RESULT => Job::P_PARAM_EXPECTED_RESULT_T_SUCCESS,
                            Job::P_PARAM_SLEEP           => 3,
                        ],
                        Job::P_COMMENT => 'comment-xxx / level 2',
                        Job::P_IS_SYNC => true,
                        Job::P_DEBUG   => true,
                    ]),
                ]
            ]
        ]);

        $callbacksAll = $job->getCallback();
        $this->assertArrayHasKey(Job::P_CALLBACK_T_SUCCESS, $callbacksAll);

        $callbacksSuccess = $job->getCallback(Job::P_CALLBACK_T_SUCCESS);
        $this->assertInternalType('array', $callbacksSuccess);
        $this->assertEquals(1, count($callbacksSuccess));
        $this->assertInstanceOf('\\Cronario\\AbstractJob', $callbacksSuccess[0]);
        $this->assertEquals('comment-xxx / level 2', $callbacksSuccess[0]->getComment());

    }

    public function testSerializationJob()
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

        $packed = serialize($job);
        $unserializeJob = unserialize($packed);

        $this->assertInstanceOf('\\Cronario\\AbstractJob', $unserializeJob);
    }

    public function testCloneJob()
    {
        $commentSource = 'comment-xxx';

        $job = new Job([
            Job::P_PARAMS  => [
                Job::P_PARAM_EXPECTED_RESULT => Job::P_PARAM_EXPECTED_RESULT_T_FAILURE,
                Job::P_PARAM_SLEEP           => 1,
            ],
            Job::P_COMMENT => $commentSource,
            Job::P_AUTHOR  => 'author-xxx',
            Job::P_IS_SYNC => true,
            Job::P_DEBUG   => true,
        ]);

        $clonedJob = clone $job;
        $clonedComment = $clonedJob->getComment('comment-xxx');

        $this->assertInstanceOf('\\Cronario\\AbstractJob', $clonedJob);
        $this->assertEquals($commentSource, $clonedComment);
    }

    public function testGetQueueDelay()
    {
        $job = new Job([
            Job::P_PARAMS   => [
                Job::P_PARAM_EXPECTED_RESULT => Job::P_PARAM_EXPECTED_RESULT_T_FAILURE,
                Job::P_PARAM_SLEEP           => 1,
            ],
            Job::P_COMMENT  => 'comment-xxx',
            Job::P_AUTHOR   => 'author-xxx',
            Job::P_IS_SYNC  => false,
            Job::P_DEBUG    => true,
            Job::P_START_ON => time() + 10,
        ]);

        $delay = $job->getQueueDelay();

        $this->assertLessThan(15, $delay);
        $this->assertGreaterThan(5, $delay);
    }


    public function testGetDataFull()
    {
        $job = new Job([
            Job::P_PARAMS   => [
                Job::P_PARAM_EXPECTED_RESULT => Job::P_PARAM_EXPECTED_RESULT_T_FAILURE,
                Job::P_PARAM_SLEEP           => 1,
            ],
            Job::P_COMMENT  => 'comment-xxx',
            Job::P_AUTHOR   => 'author-xxx',
            Job::P_IS_SYNC  => false,
            Job::P_DEBUG    => true,
            Job::P_START_ON => time() + 10,
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
            Job::P_PARAMS  => [
                Job::P_PARAM_EXPECTED_RESULT => Job::P_PARAM_EXPECTED_RESULT_T_FAILURE,
                Job::P_PARAM_SLEEP           => 1,
            ],
            Job::P_COMMENT => $commentSource,
            Job::P_AUTHOR  => 'author-xxx',
            Job::P_IS_SYNC => false,
        ]);

        $comment = $job->getData(Job::P_COMMENT);
        $this->assertEquals($comment, $commentSource);

        $job->unsetData(Job::P_COMMENT);
        $commentAfterDelete = $job->getData(Job::P_COMMENT);
        $this->assertNotEquals($comment, $commentAfterDelete);
    }


    public function testGetParamFull()
    {
        $job = new Job([
            Job::P_PARAMS  => [
                Job::P_PARAM_EXPECTED_RESULT => Job::P_PARAM_EXPECTED_RESULT_T_FAILURE,
                Job::P_PARAM_SLEEP           => 1,
            ],
            Job::P_COMMENT => 'comment-xxx',
            Job::P_AUTHOR  => 'author-xxx',
            Job::P_IS_SYNC => false,
        ]);

        $paramFull = $job->getParam(null);
        $this->assertInternalType('array', $paramFull);

        $paramFull = $job->getParam();
        $this->assertInternalType('array', $paramFull);
    }


    public function testSetParam()
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


        $this->assertEquals(9, $job->getParam(Job::P_PARAM_SLEEP));

        $job->setParam(Job::P_PARAM_SLEEP, 100);
        $this->assertEquals(100, $job->getParam(Job::P_PARAM_SLEEP));
    }


    public function testSetId()
    {
        $id = 'my-id-xxx';

        $job = new Job([
            Job::P_PARAMS  => [
                Job::P_PARAM_EXPECTED_RESULT => Job::P_PARAM_EXPECTED_RESULT_T_FAILURE,
                Job::P_PARAM_SLEEP           => 9,
            ],
            Job::P_COMMENT => 'comment-xxx',
            Job::P_AUTHOR  => 'author-xxx',
            Job::P_IS_SYNC => false,
        ]);


        $this->assertFalse($job->isStored());
        $this->assertEquals(null, $job->getId());

        $job->setId($id);
        $this->assertEquals($id, $job->getId());
        $this->assertTrue($job->isStored());
    }


    public function testGetSetWorkerClass()
    {
        $workerClass = '\\Custom\\Worker\\Class';

        $job = new Job([
            Job::P_PARAMS  => [
                Job::P_PARAM_EXPECTED_RESULT => Job::P_PARAM_EXPECTED_RESULT_T_FAILURE,
                Job::P_PARAM_SLEEP           => 9,
            ],
            Job::P_COMMENT => 'comment-xxx',
            Job::P_AUTHOR  => 'author-xxx',
            Job::P_IS_SYNC => false,
        ]);

        $job->setWorkerClass($workerClass);
        $this->assertEquals($workerClass, $job->getWorkerClass());
    }


    public function testGetAppId()
    {
        $appId = 'my-app-id-xxx';

        $job = new Job([
            Job::P_APP_ID  => $appId,
            Job::P_PARAMS  => [
                Job::P_PARAM_EXPECTED_RESULT => Job::P_PARAM_EXPECTED_RESULT_T_FAILURE,
                Job::P_PARAM_SLEEP           => 9,
            ],
            Job::P_COMMENT => 'comment-xxx',
            Job::P_AUTHOR  => 'author-xxx',
            Job::P_IS_SYNC => false,
        ]);

        $this->assertEquals($appId, $job->getAppId());
    }

}
