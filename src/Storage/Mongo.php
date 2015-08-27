<?php

namespace Cronario\Storage;

use Cronario\AbstractJob;

class Mongo implements StorageInterface
{


    // region CONNECTION *****************************************************

    protected static $client;
    protected static $db;
    protected static $collection;

    protected function getCollection()
    {
        if (null === static::$collection) {
            // connect
            static::$client = new \MongoClient();

            // select a database
            static::$db = static::$client->ik2;

            // select a collection (analogous to a relational database's table)
            static::$collection = static::$db->cronario_jobsss2;
        }

        return static::$collection;
    }

    // endregion *****************************************************


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

        $result = static::getCollection()->update(
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
        $data = static::getCollection()->findOne(array('_id' => new \MongoId($jobId)));

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