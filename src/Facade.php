<?php

namespace Cronario;

final class Facade
{
    /**
     * @var array
     */
    protected static $producers = [];

    /**
     * @param Producer $producer
     *
     * @throws Exception\FacadeException
     */
    public static function addProducer(Producer $producer)
    {
        $appId = $producer->getAppId();

        if (array_key_exists($appId, static::$producers)) {
            throw new Exception\FacadeException("Producer with {$appId} already exists!");
        }

        static::$producers[$appId] = $producer;
    }

    /**
     * @param string $appId
     *
     * @return Producer|null
     * @throws Exception\FacadeException
     */
    public static function getProducer($appId = Producer::DEFAULT_APP_ID)
    {
        if (null === $appId) {
            $appId = Producer::DEFAULT_APP_ID;
        }

        if (!array_key_exists($appId, static::$producers)) {
            throw new Exception\FacadeException("Producer with appId: '{$appId}' not exists yet!");
        }

        return static::$producers[$appId];
    }

    /**
     * @return bool
     */
    public static function cleanProducers()
    {
        static::$producers = [];

        return true;
    }

    /**
     * @param $appId
     *
     * @return Storage\StorageInterface
     * @throws Exception\FacadeException
     */
    public static function getStorage($appId)
    {
        return static::getProducer($appId)->getStorage();
    }


    // region PRODUCERS STATS *******************************************************************

    /**
     * @return array
     */
    public static function getProducersStats()
    {
        $producersStats = [];
        foreach (static::$producers as $appId => $producer) {
            /** @var $producer Producer */
            $producersStats[$appId] = $producer->getStats();
        }

        return $producersStats;
    }

    /**
     * @return array
     */
    public static function getQueuesStats()
    {
        $queuesStats = [];
        foreach (static::$producers as $appId => $producer) {
            /** @var $producer Producer */
            $queuesStats[$appId] = $producer->getQueue()->getStats();
        }

        return $queuesStats;
    }

    /**
     * @return array
     */
    public static function getJobsReserved()
    {
        $jobsReserved = [];
        foreach (static::$producers as $appId => $producer) {
            /** @var $producer Producer */
            $jobsReserved[$appId] = $producer->getQueue()->getJobReserved();
        }

        return $jobsReserved;
    }


    /**
     * @return array
     */
    public static function getManagersStats()
    {

        $managersStats = [];
        foreach (static::$producers as $appId => $producer) {

            /** @var $producer Producer */
            $redis = $producer->getRedis();

            /** @var $redis \Predis\Client */
            $keys = $redis->keys(Manager::REDIS_NS_STATS . '*');


            foreach ($keys as $key) {
                $parse = Manager::parseManagerStatKey($key);

                if ($appId != $parse['appId']) {
                    continue;
                }

                $data = $redis->hgetall($key);
                $data['workerClass'] = $parse['workerClass'];
                $data['appId'] = $parse['appId'];

                $managersStats[$parse['appId']]['stat'][] = $data;

            }

            $keys = $redis->keys(Manager::REDIS_NS_LIVE . '*');
            foreach ($keys as $key) {

                $parse = Manager::parseManagerStatKey($key);
                if ($appId != $parse['appId']) {
                    continue;
                }

                $data = $redis->hgetall($key);
                $data['workerClass'] = $parse['workerClass'];
                $data['appId'] = $parse['appId'];

                $managersStats[$parse['appId']]['live'][] = $data;
            }
        }

        return $managersStats;
    }

    // endregion ****************************************************************************
}