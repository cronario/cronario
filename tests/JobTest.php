<?php


namespace Cronario;

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
            Job::P_PARAMS  => [
                Job::P_PARAM_EXPECTED_RESULT => Job::P_PARAM_EXPECTED_RESULT_T_SUCCESS,
                Job::P_PARAM_SLEEP           => 1,
            ],
            Job::P_COMMENT => 'comment-xxx',
            Job::P_AUTHOR  => 'author-xxx',
            Job::P_IS_SYNC => true,
            Job::P_DEBUG   => true,
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

}
