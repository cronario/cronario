<?php

namespace Cronario\Test;

use Cronario\AbstractJob;

class Job extends AbstractJob
{


    const P_PARAM_SLEEP = 'sleep';
    const P_PARAM_EXPECTED_RESULT = 'expectedResult';
    const P_PARAM_EXPECTED_RESULT_T_SUCCESS = 'will_success';
    const P_PARAM_EXPECTED_RESULT_T_FAILURE = 'will_failure';
    const P_PARAM_EXPECTED_RESULT_T_ERROR = 'will_error';
    const P_PARAM_EXPECTED_RESULT_T_RETRY = 'will_retry';
    const P_PARAM_EXPECTED_RESULT_T_REDIRECT = 'will_redirect';
    const P_PARAM_EXPECTED_RESULT_T_REDIRECT_RETRY = 'will_redirect+retry';

    /**
     * @return null
     */
    public function getExpectedResult()
    {
        return $this->getParam(self::P_PARAM_EXPECTED_RESULT);
    }

    /**
     * @param $value
     *
     * @return $this
     */
    public function setExpectedResult($value)
    {
        return $this->setParam(self::P_PARAM_EXPECTED_RESULT, $value);
    }

    /**
     * @return null
     */
    public function getSleep()
    {
        return $this->getParam(self::P_PARAM_SLEEP);
    }

    /**
     * @param $value
     *
     * @return $this
     */
    public function setSleep($value)
    {
        return $this->setParam(self::P_PARAM_SLEEP, $value);
    }


}