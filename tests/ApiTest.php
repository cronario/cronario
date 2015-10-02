<?php


namespace Cronario\Test;


use \Cronario\Facade;
use \Cronario\Producer;


class ApiTest extends \PHPUnit_Framework_TestCase
{

    const TEST_PRODUCER_API_ID = 'api-app-id';

    public function setUp()
    {
        \Result\ResultException::setClassIndexMap([
            'Cronario\\Exception\\ResultException' => 1,
            'Cronario\\Test\\ResultException'      => 2,
        ]);

        // adds defaul producer to facade
        Facade::addProducer(new Producer([
            Producer::P_APP_ID => self::TEST_PRODUCER_API_ID
        ]));
    }

    public function tearDown()
    {
        Facade::cleanProducers();
    }

    public function testClientInstance()
    {
        $client = new \Cronario\Api\Client('http://localhost');

        $this->assertInstanceOf('\\Cronario\\Api\\Client', $client);
    }

    public function testServerDoJob()
    {
        $job = new Job([
            Job::P_WORKER_CLASS => '\\Cronario\\Test\\Worker',
            Job::P_APP_ID       => self::TEST_PRODUCER_API_ID,
            Job::P_PARAMS       => [
                Job::P_PARAM_EXPECTED_RESULT => Job::P_PARAM_EXPECTED_RESULT_T_SUCCESS,
                Job::P_PARAM_SLEEP           => 0,
            ],
            Job::P_COMMENT      => 'comment-xxx',
            Job::P_AUTHOR       => 'author-xxx',
            Job::P_IS_SYNC      => true,
            Job::P_DEBUG        => true,
        ]);

        $_REQUEST[\Cronario\Api\Client::P_ACTION] = \Cronario\Api\Client::ACTION_DO_JOB;
        $_REQUEST[\Cronario\Api\Client::P_DATA] = serialize($job);

        $response = \Cronario\Api\Server::catchAction();
        $response = json_decode($response, true);

        $this->assertArrayHasKey(\Cronario\Api\Server::P_RESPONSE_OK, $response);
        $this->assertArrayHasKey(\Cronario\Api\Server::P_RESPONSE_MSG, $response);
        $this->assertArrayHasKey(\Cronario\Api\Server::P_RESPONSE_RESULT, $response);
        $this->assertTrue($response[\Cronario\Api\Server::P_RESPONSE_OK]);
    }


    public function testServerGetResult()
    {
        $job = new Job([
            Job::P_WORKER_CLASS => '\\Cronario\\Test\\Worker',
            Job::P_APP_ID       => self::TEST_PRODUCER_API_ID,
            Job::P_PARAMS       => [
                Job::P_PARAM_EXPECTED_RESULT => Job::P_PARAM_EXPECTED_RESULT_T_SUCCESS,
                Job::P_PARAM_SLEEP           => 0,
            ],
            Job::P_COMMENT      => 'comment-xxx',
            Job::P_AUTHOR       => 'author-xxx',
            Job::P_IS_SYNC      => true,
            Job::P_DEBUG        => true,
        ]);
        $job->save();
        $result = $job();
        $jobId = $job->getId();

        $_REQUEST[\Cronario\Api\Client::P_ACTION] = \Cronario\Api\Client::ACTION_GET_JOB_RESULT;
        $_REQUEST[\Cronario\Api\Client::P_DATA] = $jobId;
        $_REQUEST[\Cronario\Api\Client::P_APP_ID] = self::TEST_PRODUCER_API_ID;

        $response = \Cronario\Api\Server::catchAction();
        $response = json_decode($response, true);


        $this->assertArrayHasKey(\Cronario\Api\Server::P_RESPONSE_OK, $response);
        $this->assertArrayHasKey(\Cronario\Api\Server::P_RESPONSE_MSG, $response);
        $this->assertTrue($response[\Cronario\Api\Server::P_RESPONSE_OK]);
        $this->assertSame($result->getGlobalCode(), $response[\Cronario\Api\Server::P_RESPONSE_RESULT]['globalCode']);

    }

}
