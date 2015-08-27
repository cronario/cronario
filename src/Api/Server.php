<?php

namespace Cronario\Api;

use Cronario\AbstractJob;
use Cronario\Producer;
use Cronario\Exception\ResultException;

class Server
{

    const P_RESPONSE_OK = 'ok';
    const P_RESPONSE_APP_ID = 'appId';
    const P_RESPONSE_MSG = 'msg';
    const P_RESPONSE_RESULT = 'result';
    const P_RESPONSE_INFO = 'info';


    /**
     * @param $jobId
     * @param $appId
     *
     * @return mixed
     */
    public static function actionJobGetResult($jobId, $appId = Producer::DEFAULT_APP_ID)
    {
        // sanitize user inputs
        $jobId = filter_var($jobId, FILTER_SANITIZE_STRING);
        $appId = filter_var($appId, FILTER_SANITIZE_STRING);

        /** @var AbstractJob $job */
        $job = AbstractJob::find($jobId, $appId);

        if (is_object($job)) {
            $result = $job->getResult();
            if ($result instanceof ResultException) {
                $response[self::P_RESPONSE_MSG] = 'Job result ' . $jobId;
                $response[self::P_RESPONSE_RESULT] = $result->toArray(true);
            } else {
                $response[self::P_RESPONSE_MSG] = 'Job result not ready : ' . $jobId;
            }

        } else {
            $response[self::P_RESPONSE_OK] = false;
            $response[self::P_RESPONSE_MSG] = 'Job not exists : ' . $jobId;
        }

        return $response;
    }

    /**
     * @param $data
     *
     * @return mixed
     * @throws
     */
    public static function actionDoJob($data)
    {
        /** @var AbstractJob $job */
        $job = unserialize($data);

        $response[self::P_RESPONSE_MSG] = 'do job';

        if ($job->isSync()) {
            $response[self::P_RESPONSE_RESULT] = $job();
        } else {
            $response[self::P_RESPONSE_INFO] = $job();
        }

        return $response;
    }

    /**
     * @return string
     */
    public static function catchAction()
    {
        $response = null;


        if ($_REQUEST[Client::P_ACTION] === Client::ACTION_GET_JOB_RESULT) {

            $response = self::actionJobGetResult($_REQUEST[Client::P_DATA], $_REQUEST[Client::P_APP_ID]);

        } elseif ($_REQUEST[Client::P_ACTION] === Client::ACTION_DO_JOB) {

            $response = self::actionDoJob($_REQUEST[Client::P_DATA]);

        } else {
            $response = [
                self::P_RESPONSE_OK  => false,
                self::P_RESPONSE_MSG => 'action is undefined',
            ];
        }

        return self::buildResponse($response);
    }

    /**
     * @param $response
     *
     * @return string
     */
    public static function buildResponse($response)
    {
        $responseDefault = [
            self::P_RESPONSE_OK     => true,
            self::P_RESPONSE_MSG    => 'undefined',
            self::P_RESPONSE_RESULT => null,
            self::P_RESPONSE_APP_ID => Producer::DEFAULT_APP_ID,
        ];

        return json_encode(array_merge($responseDefault, $response));
    }

}