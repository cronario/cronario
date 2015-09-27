<?php

namespace Cronario\Test;

use Cronario\AbstractJob;
use Cronario\AbstractWorker;

class Worker extends AbstractWorker
{

    protected static $config
        = [
            'superKey' => 'superValue',
            'params' => []
        ];

    //endregion ****************************************************************

    /**
     * @param AbstractJob|Job $job
     *
     * @throws ResultException
     */
    protected function doJob(AbstractJob $job)
    {
        $job->addDebugData('event' , 'workerDoJob');

        // generate getter config =====================================================

        $job->addDebugData('getConfig' , static::getConfig());
        $job->addDebugData('getConfigSuperKey' , static::getConfig('superKey'));



        // generate response ==========================================================

        $sleep = $job->getSleep() ?: 0;
        $job->addDebugData('event' , "before sleep {$sleep} / time: " . time());
        sleep($sleep);
        $job->addDebugData('event' , "after sleep {$sleep} / time: " . time());

        $response = [
            'uniqid' => uniqid(),
        ];

        $job->addDebugData('response' , $response);
        $resultData = $response;

        // generate new ResultException ===============================================

        $expectedResult = $job->getExpectedResult();
        $job->addDebugData('expect' , $expectedResult);
        $job->addDebugData('event' , "now will get ResultException ...");

        if ($expectedResult === Job::P_PARAM_EXPECTED_RESULT_T_SUCCESS) {
            $job->addDebugData('throw' , "code " . ResultException::R_SUCCESS);
            throw new ResultException(ResultException::R_SUCCESS, $resultData);
        }

        if ($expectedResult === Job::P_PARAM_EXPECTED_RESULT_T_FAILURE) {
            $job->addDebugData('throw' , "code " . ResultException::FAILURE_XXX);
            throw new ResultException(ResultException::FAILURE_XXX);
        }

        if($expectedResult === Job::P_PARAM_EXPECTED_RESULT_T_ERROR){
            $job->addDebugData('throw' , "code " . ResultException::ERROR_XXX);
            throw new ResultException(ResultException::ERROR_XXX , $resultData);
        }

        if ($expectedResult === Job::P_PARAM_EXPECTED_RESULT_T_RETRY) {
            $newWorkerClass = $job->getWorkerClass();
            $job->setWorkerClass($newWorkerClass)->save();
            $job->addDebugData('setWorkerClass' , $newWorkerClass);
            $job->addDebugData('throw' , "code " . ResultException::RETRY_XXX);
            throw new ResultException(ResultException::RETRY_XXX, $resultData);
        }

        if ($expectedResult === Job::P_PARAM_EXPECTED_RESULT_T_REDIRECT) {
            $newWorkerClass = $job->getWorkerClass();
            $job->setWorkerClass($newWorkerClass)->save();
            $job->addDebugData('setWorkerClass' , $newWorkerClass);
            $job->addDebugData('throw' , "code " . ResultException::REDIRECT_XXX);
            throw new ResultException(ResultException::REDIRECT_XXX,$resultData);

        }

        $job->addDebugData('throw' , "code " . ResultException::ERROR_PARAM_EXPECTED_RESULT);
        throw new ResultException(ResultException::ERROR_PARAM_EXPECTED_RESULT, $resultData);
    }




}