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

        $jobClass = $data[AbstractJob::P_JOB_CLASS];

        /** @var AbstractJob $job */
        $job = new $jobClass($data);

        return $job;
    }

}