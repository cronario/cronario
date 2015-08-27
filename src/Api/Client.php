<?php

namespace Cronario\Api;

use Cronario\AbstractJob;
use Cronario\Producer;

class Client
{

    const P_APP_ID = 'appId';
    const P_ACTION = 'action';
    const P_DATA = 'data';

    const ACTION_DO_JOB = 'do_job';
    const ACTION_GET_JOB_RESULT = 'get_job_result';
    const ACTION_GET_DAEMON_INFO = 'get_daemon_info';

    // region CLIENT SIDE *****************************************************

    protected $_url;
    protected $_lastRequest;
    protected $_lastResponse;

    /**
     * @param $url string
     */
    public function __construct($url)
    {
        $this->_url = $url;
    }

    /**
     * @param AbstractJob $job
     *
     * @return mixed
     */
    public function doJob(AbstractJob $job)
    {
        return $this->request([
            self::P_ACTION => self::ACTION_DO_JOB,
            self::P_DATA => serialize($job),
        ]);
    }

    /**
     * @param        $jobId
     * @param string $appId
     *
     * @return mixed
     */
    public function getJobResult($jobId, $appId = Producer::DEFAULT_APP_ID)
    {
        return $this->request([
            self::P_ACTION => self::ACTION_GET_JOB_RESULT,
            self::P_DATA => $jobId,
            self::P_APP_ID => $appId,
        ]);
    }

    /**
     * @param $params
     *
     * @return mixed
     */
    protected function request($params)
    {
        $options = [
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL            => $this->_url,
            CURLOPT_POST           => 1,
            CURLOPT_POSTFIELDS     => http_build_query($params),
        ];

        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $this->_lastRequest = $options;

        $response = curl_exec($ch);
        $this->_lastResponse = $response;
        curl_close($ch);

        $response = json_decode($response, true);

        return $response;
    }

    // endregion **************************************************************

}