<?php

namespace Cronario\Storage;

use Cronario\AbstractJob;

class Mongo implements StorageInterface
{

    // region CONNECTION *****************************************************
    /**
     * @var \MongoCollection
     */
    protected $cacheCollection;

    /**
     * @return \MongoCollection
     */
    protected function getCollection()
    {
        if (null === $this->cacheCollection) {
            // connect
            $client = new \MongoClient($this->config['server']);

            // select a database
            $db = $client->{$this->config['database']};

            // select a collection (analogous to a relational database's table)
            $this->cacheCollection = $db->{$this->config['collection']};
        }

        return $this->cacheCollection;
    }

    // endregion *****************************************************

    const CONFIG_SERVER_DEFAULT = 'mongodb://localhost:27017';

    /**
     * @var array
     */
    protected $config
        = [
            'server'     => self::CONFIG_SERVER_DEFAULT,
            'database'   => null,
            'collection' => null,
        ];

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * @param AbstractJob $job
     *
     * @return mixed
     * @throws \Cronario\Exception\JobException
     */
    public function save(AbstractJob $job)
    {
        $data = $job->getData();

        if ($job->isStored()) {
            $data['_id'] = new \MongoId($data[AbstractJob::P_ID]);
            unset($data[AbstractJob::P_ID]);
        } else {
            $data['_id'] = new \MongoId();
            $job->setId((string) $data['_id']);
        }

        $result = $this->getCollection()->update(
            array('_id' => $data['_id']),
            array('$set' => $data),
            array('upsert' => true)
        );

        return $result;
    }

    /**
     * @param $jobId
     *
     * @return AbstractJob|null
     */
    public function find($jobId)
    {
        $data = $this->getCollection()->findOne(array('_id' => new \MongoId($jobId)));

        unset($data['_id']);
        $data[AbstractJob::P_ID] = $jobId;
        $class = $data[AbstractJob::P_JOB_CLASS];

        if (!is_array($data) || empty($class)) {
            return null;
        }

        /** @var AbstractJob $job */
        $job = new $class($data);

        return $job;
    }

}