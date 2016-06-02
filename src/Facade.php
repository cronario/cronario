<?php

namespace Cronario;

/**
 * Class Facade
 *
 * @package Cronario
 */
final class Facade
{
    /**
     * Producer instances
     *
     * @var array
     */
    protected static $producers = [];

    /**
     * Producer builders scope
     *
     * @var array
     */
    protected static $builders = [];

    /**
     * Add producer to facade
     *
     * @param Producer $producer
     *
     * @return boolean
     * @throws Exception\FacadeException
     */
    public static function addProducer(Producer $producer)
    {
        $appId = $producer->getAppId();

        if (array_key_exists($appId, static::$producers)) {
            throw new Exception\FacadeException("Producer with {$appId} already exists!");
        }

        static::$producers[$appId] = $producer;

        return true;
    }

    /**
     * Add Builder to facade builder scope
     *
     * @param $appId
     * @param $builderFunction
     *
     * @return boolean
     * @throws Exception\FacadeException
     */
    public static function addBuilder($appId, $builderFunction)
    {
        if (array_key_exists($appId, static::$builders)) {
            throw new Exception\FacadeException("Builder with {$appId} already exists!");
        }

        if (!is_callable($builderFunction)) {
            throw new Exception\FacadeException("Builder function for {$appId} is not callable!");
        }

        static::$builders[$appId] = $builderFunction;

        return true;
    }

    /**
     * Get producer by id
     *
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

            if (array_key_exists($appId, static::$builders)) {
                $func = static::$builders[$appId];
                static::addProducer($func($appId));
                unset(static::$builders[$appId]);

                return static::getProducer($appId);
            }

            throw new Exception\FacadeException("Producer with appId: '{$appId}' not exists yet!");
        }

        return static::$producers[$appId];
    }

    /**
     * Clean producer scope
     *
     * @return boolean
     */
    public static function cleanProducers()
    {
        static::$producers = [];

        return true;
    }

    /**
     * Get storage by application id
     *
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
     * Get producer statistic
     *
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
     * Get queues statistic
     *
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
     * Get all reserved jobs
     *
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
     * Get all managers statistic
     *
     * @return array
     */
    public static function getManagersStats()
    {

        $managersStats = [];
        foreach (static::$producers as $appId => $producer) {

            /** @var $producer Producer */
            $redis = $producer->getRedis();
            $keys = [
                'stat' => $redis->keys(Manager::REDIS_NS_STATS . '*'),
                'live' => $redis->keys(Manager::REDIS_NS_LIVE . '*')
            ];

            foreach ($keys as $type => $statsKeys) {
                foreach ($statsKeys as $statsKey) {
                    $parse = Manager::parseManagerStatKey($statsKey);
                    if ($appId != $parse['appId']) {
                        continue;
                    }

                    $statsItemData = $redis->hgetall($statsKey);
                    $statsItemData['workerClass'] = $parse['workerClass'];
                    $statsItemData['appId'] = $parse['appId'];
                    $managersStats[$parse['appId']][$type][] = $statsItemData;
                }
            }

        }

        return $managersStats;
    }

    // endregion ****************************************************************************
}