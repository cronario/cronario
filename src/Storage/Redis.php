<?php

namespace Cronario\Storage;

use Cronario\AbstractJob;
use Predis\Client;

class Redis implements StorageInterface
{

    const DEFAULT_REDIS_SERVER = '127.0.0.1:6379';
    const DEFAULT_NAMESPACE = 'default';

    /**
     * @var Client
     */
    protected $redis;
    protected $namespace;

    public function __construct($namespace = null, Client $redis = null)
    {
        if (null === $redis) {
            $redis = new Client(self::DEFAULT_REDIS_SERVER);
        }

        if (null === $namespace) {
            $namespace = self::DEFAULT_NAMESPACE;
        }

        $this->redis = $redis;
        $this->namespace = $namespace;
    }

    /**
     * @param AbstractJob $job
     *
     * @return bool
     * @throws \Cronario\Exception\JobException
     */
    public function save(AbstractJob $job)
    {

        $data = $job->getData();
        if (!$job->isStored()) {
            $job->setId(uniqid());
        }

        // use short keys / or just save data like it was
        $data = $this->minimizeKeys($data, $this->minimizeMap);

        $this->redis->set($this->namespace . $job->getId(), json_encode($data));

        return true;
    }

    /**
     * @param $jobId
     *
     * @return AbstractJob
     */
    public function find($jobId)
    {
        $data = $this->redis->get($this->namespace . $jobId);
        $data = json_decode($data, true);

        // use short keys / or just load data like it was
        $data = $this->maximizeKeys($data, $this->minimizeMap);

        $jobClass = $data[AbstractJob::P_JOB_CLASS];

        /** @var AbstractJob $job */
        $job = new $jobClass($data);

        return $job;
    }

    protected $minimizeMap
        = [
            AbstractJob::P_AUTHOR       => 'aut',
            AbstractJob::P_DEBUG        => 'dbg',
            AbstractJob::P_COMMENT      => 'com',
            AbstractJob::P_JOB_CLASS    => 'jbc',
            AbstractJob::P_WORKER_CLASS => 'wrc',
            AbstractJob::P_PARAMS       => 'prm',
            AbstractJob::P_RESULT       => 'res',
            'res'                       => [
                AbstractJob::RESULT_P_GLOBAL_CODE => 'g',
                AbstractJob::RESULT_P_DATA        => 'd',
            ],
            AbstractJob::P_CREATE_ON    => 'cro',
            AbstractJob::P_START_ON     => 'sto',
            AbstractJob::P_EXPIRED_ON   => 'exo',
            AbstractJob::P_DELETE_ON    => 'dlo',
            AbstractJob::P_FINISH_ON    => 'fno',
            AbstractJob::P_PRIORITY     => 'pri',
            AbstractJob::P_IS_SYNC      => 'snc',
            AbstractJob::P_CALLBACKS    => 'clb',
            'clb'                       => [
                AbstractJob::P_CALLBACK_T_SUCCESS => 's',
                AbstractJob::P_CALLBACK_T_FAILURE => 'f',
                AbstractJob::P_CALLBACK_T_DONE    => 'd',
                AbstractJob::P_CALLBACK_T_ERROR   => 'e',
            ]
        ];

    /**
     * @param      $data
     * @param null $map
     *
     * @return mixed
     */
    public function maximizeKeys($data, $map = null)
    {
        if (null === $map) {
            return $data;
        }

        $mapFlip = @array_flip($map);
        foreach ($data as $key => $value) {
            if (isset($mapFlip[$key])) {
                unset($data[$key]);
                $data[$mapFlip[$key]] = (is_array($value) && !isset($value[0]) && is_array($map[$key]))
                    ? $this->maximizeKeys($map[$key], $value)
                    : $value;
            }
        }

        return $data;
    }

    /**
     * @param      $data
     * @param null $map
     *
     * @return mixed
     */
    public function minimizeKeys($data, $map = null)
    {
        if (null === $map) {
            return $data;
        }

        foreach ($data as $key => $value) {
            if (isset($map[$key])) {
                unset($data[$key]);
                $data[$map[$key]] = (is_array($value) && !isset($value[0]) && is_array($map[$map[$key]]))
                    ? $this->minimizeKeys($map[$map[$key]], $value)
                    : $value;
            }
        }

        return $data;
    }


}