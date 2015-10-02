<?php


namespace Cronario\Test;

use \Cronario\Facade;
use \Cronario\Producer;


class FlowTest extends \PHPUnit_Framework_TestCase
{

    const TEST_PRODUCER_FLOW_ID = 'flow-app-id';

    public function setUp()
    {
        \Result\ResultException::setClassIndexMap([
            'Cronario\\Exception\\ResultException' => 1,
            'Cronario\\Test\\ResultException'      => 2,
        ]);

        // adds defaul producer to facade
        Facade::addProducer(new Producer([
            Producer::P_APP_ID => self::TEST_PRODUCER_FLOW_ID
        ]));
    }

    public function tearDown()
    {
        Facade::cleanProducers();
    }

    public function testDoSimpleJob()
    {
        $job = new Job([
            Job::P_APP_ID   => self::TEST_PRODUCER_FLOW_ID,
            Job::P_PARAMS   => [
                Job::P_PARAM_EXPECTED_RESULT => Job::P_PARAM_EXPECTED_RESULT_T_SUCCESS,
                Job::P_PARAM_SLEEP           => 0,
            ],
            Job::P_COMMENT  => 'comment-xxx',
            Job::P_AUTHOR   => 'author-xxx',
            Job::P_IS_SYNC  => true,
            Job::P_CALLBACK => [
                Job::P_CALLBACK_T_SUCCESS => [
                    new Job([
                        Job::P_APP_ID  => self::TEST_PRODUCER_FLOW_ID,
                        Job::P_PARAMS  => [
                            Job::P_PARAM_EXPECTED_RESULT => Job::P_PARAM_EXPECTED_RESULT_T_SUCCESS,
                            Job::P_PARAM_SLEEP           => 0,
                        ],
                        Job::P_COMMENT => 'comment-xxx / level 2',
                        Job::P_IS_SYNC => true,
                    ]),
                ]
            ]
        ]);

        $result = $job();
        $this->assertInstanceOf('\\Cronario\\Exception\\ResultException', $result);

        $serializedJob = serialize($job);
        $unserializedJob = unserialize($serializedJob);

        $this->assertEquals($job->getId(), $unserializedJob->getId());
        $this->assertEquals($job->getResult()->getGlobalCode(), $unserializedJob->getResult()->getGlobalCode());
    }

    public function testDoCallbackJob()
    {
        $job = new Job([
            Job::P_APP_ID   => self::TEST_PRODUCER_FLOW_ID,
            Job::P_PARAMS   => [
                Job::P_PARAM_EXPECTED_RESULT => Job::P_PARAM_EXPECTED_RESULT_T_SUCCESS,
                Job::P_PARAM_SLEEP           => 0,
            ],
            Job::P_COMMENT  => 'comment-xxx',
            Job::P_AUTHOR   => 'author-xxx',
            Job::P_IS_SYNC  => true,
            Job::P_CALLBACK => [
                Job::P_CALLBACK_T_SUCCESS => [
                    new Job([
                        Job::P_APP_ID  => self::TEST_PRODUCER_FLOW_ID,
                        Job::P_PARAMS  => [
                            Job::P_PARAM_EXPECTED_RESULT => Job::P_PARAM_EXPECTED_RESULT_T_SUCCESS,
                            Job::P_PARAM_SLEEP           => 0,
                        ],
                        Job::P_COMMENT => 'comment-xxx / level 2',
                        Job::P_IS_SYNC => true,
                    ]),
                ]
            ]
        ]);

        $job->save();
        $jobId = $job->getId();

        // other part of programm
        $storage = Facade::getStorage(self::TEST_PRODUCER_FLOW_ID);
        $job2 = $storage->find($jobId);

        $result = $job2();
        $this->assertInstanceOf('\\Cronario\\Exception\\ResultException', $result);

    }


    public function testManagerCreate(){

        $manager = new \Cronario\Manager(999 , self::TEST_PRODUCER_FLOW_ID , '\\Cronario\\Test\\Worker','/var/www/tests/daemon.php');

        $this->assertInstanceOf('\\Thread', $manager);
        $this->assertInstanceOf('\\Cronario\\Manager', $manager);

        $manager->join();
    }


    public function testMegaFlowTestAsync()
    {
        // start daemon in background
        shell_exec('php /var/www/tests/daemon.php start > /dev/null &');
        sleep(1);





        // kill daemon in background
        shell_exec('php /var/www/tests/daemon.php kill > /dev/null &');

        $this->assertEquals(1, 1);
    }

}
