<?php


namespace Cronario;

use \Cronario\Exception\ResultException;

class ResultTest extends \PHPUnit_Framework_TestCase
{

    protected function setUp()
    {
        \Result\ResultException::setClassIndexMap([
            'Cronario\\Exception\\ResultException' => 1,
        ]);
    }

    public function testResultsStatus()
    {
        $results = [];

        $results['success'] = new ResultException(ResultException::R_SUCCESS);
        $this->assertTrue($results['success']->isSuccess());

        $results['failure'] = new ResultException(ResultException::R_FAILURE);
        $this->assertTrue($results['failure']->isFailure());

        $results['redirect'] = new ResultException(ResultException::R_REDIRECT);
        $this->assertTrue($results['redirect']->isRedirect());

        $results['retry'] = new ResultException(ResultException::R_RETRY);
        $this->assertTrue($results['retry']->isRetry());

        $results['queued'] = new ResultException(ResultException::R_QUEUED);
        $this->assertTrue($results['queued']->isQueued());

        $results['error'] = new ResultException(ResultException::E_INTERNAL);
        $this->assertTrue($results['error']->isError());
    }

    public function testResultsData()
    {
        $key = 'super-key';
        $value = 'super-value';

        $result = new ResultException(ResultException::R_SUCCESS);
        $result->setData($key, $value);

        $this->assertEquals($value, $result->getData($key));
    }

    public function testResultToArray()
    {
        $key = 'super-key';
        $value = 'super-value';

        $result = new ResultException(ResultException::R_REDIRECT, [$key => $value]);
        $array = $result->toArray();

        $this->assertArrayHasKey('globalCode', $array);
        $this->assertArrayHasKey('data', $array);
    }


}
