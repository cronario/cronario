<?php

/**
 * https://hackage.haskell.org/package/hbeanstalk-0.1/docs/Network-Beanstalk.html#1
 * https://github.com/nrk/predis
 * https://laravel.ru/docs/v4/queues
 * http://laravel.com/docs/4.2/queues
 * https://github.com/laravel/framework/blob/5.0/src/Illuminate/Queue/RedisQueue.php
 */

namespace Cronario;

use Cronario\Exception\QueueException;

class Queue
{

    // region MAIN *************************************************************

    // Namespaces
    const REDIS_NS_JOB = 'cronario@queue-job';
    const REDIS_NS_QUEUE = 'cronario@queue';
    const REDIS_NS_QUEUE_STOP = 'cronario@queueStop';

    // State
    const STATE_READY = 'ready';
    const STATE_DELAYED = 'delayed';
    const STATE_RESERVED = 'reserved';
    const STATE_BURIED = 'buried';

    // Priority
    const PRIORITY_HIGH = 'high';
    const PRIORITY_LOW = 'low';

    // Stats
    const STATS_QUEUES_LIST = 'queues-list';
    const STATS_QUEUES = 'queues';
    const STATS_JOBS_TOTAL = 'jobs-total';
    const STATS_JOBS_READY = 'jobs-ready';
    const STATS_JOBS_RESERVED = 'jobs-reserved';
    const STATS_JOBS_DELAYED = 'jobs-delayed';
    const STATS_JOBS_BURIED = 'jobs-buried';
    const STATS_QUEUE_STOP = 'stop';

    // Job payload
    const JOB_PAYLOAD_QUEUE = 'queue';
    const JOB_PAYLOAD_STATE = 'state';

    // endregion ***************************************************************


    // region STATIC ********************************************************

    /** @var  Producer */
    protected $producer;

    /**
     * @param Producer $producer
     *
     * @return $this
     * @throws QueueException
     */
    public function setProducer(Producer $producer)
    {
        if (null != $this->producer) {
            throw new QueueException('Queue - producer already sets!');
        }

        $this->producer = $producer;

        return $this;
    }

    /**
     * @return Producer
     * @throws QueueException
     */
    public function getProducer()
    {
        if (null === $this->producer) {
            throw new QueueException('Queue - producer is undefined');
        }

        return $this->producer;
    }

    /**
     * @return \Predis\Client
     */
    public function getRedis()
    {
        return $this->producer->getRedis();
    }

    /**
     * @return string
     */
    public function getAppId()
    {
        return $this->producer->getAppId();
    }

    /**
     * @return string
     */
    protected function getRedisQueueNamespace()
    {
        return implode(':', [self::REDIS_NS_QUEUE, $this->getAppId()]);
    }

    /**
     * @return string
     */
    protected function getRedisJobNamespace()
    {
        return implode(':', [self::REDIS_NS_JOB, $this->getAppId()]);
    }

    /**
     * @return string
     */
    protected function getRedisQueueStopNamespace()
    {
        return implode(':', [self::REDIS_NS_QUEUE_STOP, $this->getAppId()]);
    }

    // endregion ***************************************************************

    // region QUEUE Stopping ************************************************************

    /**
     * @param $queueName
     *
     * @return bool
     */
    public function isStop($queueName)
    {
        return (bool)$this->getRedis()->hexists($this->getRedisQueueStopNamespace(), $queueName);
    }

    /**
     * @param $queueName
     *
     * @return bool
     */
    public function stop($queueName)
    {
        return (bool)$this->getRedis()->hset($this->getRedisQueueStopNamespace(), $queueName, true);
    }

    /**
     * @param $queueName
     *
     * @return bool
     */
    public function start($queueName)
    {
        return (bool)$this->getRedis()->hdel($this->getRedisQueueStopNamespace(), $queueName);
    }


    // endregion ***************************************************************

    // region STATS ************************************************************

    /**
     * @return array
     */
    public function getQueueNames()
    {
        $result = [];

        $jobSet = $this->getRedis()->hgetall($this->getRedisJobNamespace());
        $payloadSet = array_unique(array_values($jobSet));

        if (count($payloadSet) == 0) {
            return $result;
        }

        foreach ($payloadSet as $payload) {
            $payload = self::parsePayload($payload);
            $result[$payload[self::JOB_PAYLOAD_QUEUE]] = true;
        }

        return array_keys($result);
    }

    /**
     * @return array
     */
    public function getJobReserved()
    {
        $result = [];
        $queueKeys = $this->getRedis()->keys($this->getKeyReserved('*'));

        if (count($queueKeys) == 0) {
            return $result;
        }

        foreach ($queueKeys as $queueKey) {
            $jobIds = $this->getRedis()->zrangebyscore($queueKey, '-inf', '+inf');
            foreach ($jobIds as $id) {
                $result[$id] = $this->getQueueNameFromKey($queueKey);
            }
        }

        return $result;
    }

    /**
     * @param $queue
     * @param $state
     *
     * @return int|string
     */
    public function getJobCount($queue, $state)
    {
        if ($state === self::STATE_READY) {
            return $this->getRedis()->llen($this->getKeyReady($queue));
        }

        return $this->getRedis()->zcount($this->getKey($queue, $state), '-inf', '+inf');
    }

    /**
     * Return statistical information about the server, across all clients.
     * Keys that can be expected to be returned are the following:
     *
     * @return array
     */
    public function getStats()
    {
        $result = [
            self::STATS_QUEUES_LIST   => [],
            self::STATS_JOBS_TOTAL    => 0,
            self::STATS_JOBS_READY    => 0,
            self::STATS_JOBS_RESERVED => 0,
            self::STATS_JOBS_DELAYED  => 0,
            self::STATS_JOBS_BURIED   => 0,
        ];

        $queueList = $this->getQueueNames();
        if (count($queueList) == 0) {
            return $result;
        }

        $result[self::STATS_QUEUES_LIST] = $queueList;

        foreach ($queueList as $queueName) {
            $itemStats = $this->getQueueInfo($queueName);
            $result[self::STATS_JOBS_READY] += $itemStats[self::STATS_JOBS_READY];
            $result[self::STATS_JOBS_RESERVED] += $itemStats[self::STATS_JOBS_RESERVED];
            $result[self::STATS_JOBS_DELAYED] += $itemStats[self::STATS_JOBS_DELAYED];
            $result[self::STATS_JOBS_BURIED] += $itemStats[self::STATS_JOBS_BURIED];
            $result[self::STATS_JOBS_TOTAL] += $itemStats[self::STATS_JOBS_TOTAL];
            $result[self::STATS_QUEUES][$queueName] = $itemStats;
        }

        return $result;
    }


    /**
     * @param $queueName
     *
     * @return array
     */
    public function getQueueInfo($queueName)
    {
        $result = [
            self::STATS_JOBS_READY    => $this->getJobCount($queueName, self::STATE_READY),
            self::STATS_JOBS_RESERVED => $this->getJobCount($queueName, self::STATE_RESERVED),
            self::STATS_JOBS_DELAYED  => $this->getJobCount($queueName, self::STATE_DELAYED),
            self::STATS_JOBS_BURIED   => $this->getJobCount($queueName, self::STATE_BURIED),
        ];

        $result[self::STATS_JOBS_TOTAL] = array_sum($result);
        $result[self::STATS_QUEUE_STOP] = $this->isStop($queueName);

        return $result;
    }


    // endregion ***************************************************************

    // region JobPayload ************************************************************

    /**
     * @param $id
     *
     * @return mixed
     */
    protected function getPayload($id)
    {
        $rawPayload = $this->getRedis()->hget($this->getRedisJobNamespace(), $id);

        return static::parsePayload($rawPayload);
    }

    /**
     * @param $queueName string
     * @param $jobState  string
     *
     * @return string
     */
    protected static function buildPayload($queueName, $jobState)
    {
        return serialize([
            self::JOB_PAYLOAD_QUEUE => $queueName,
            self::JOB_PAYLOAD_STATE => $jobState,
        ]);
    }

    /**
     * @param $rawPayload
     *
     * @return mixed
     */
    protected static function parsePayload($rawPayload)
    {
        return unserialize($rawPayload);
    }

    // endregion ***************************************************************

    // region HELPER ************************************************************


    /**
     * @return int
     */
    protected function getTime()
    {
        return time();
    }

    /**
     * @param string $key
     *
     * @return string
     */
    protected function getQueueNameFromKey($key)
    {
        return explode(':', $key)[1];
    }

    /**
     * @param string $queue
     * @param string $state
     *
     * @return string
     */
    protected function getKey($queue, $state = self::STATE_READY)
    {
        return implode(':', [$this->getRedisQueueNamespace(), $queue, $state]);
    }

    /**
     * @param $queue
     *
     * @return string
     */
    protected function getKeyReady($queue)
    {
        return $this->getKey($queue, self::STATE_READY);
    }

    /**
     * @param $queue
     *
     * @return string
     */
    protected function getKeyDelayed($queue)
    {
        return $this->getKey($queue, self::STATE_DELAYED);
    }

    /**
     * @param $queue
     *
     * @return string
     */
    protected function getKeyReserved($queue)
    {
        return $this->getKey($queue, self::STATE_RESERVED);
    }

    /**
     * @param $queue
     *
     * @return string
     */
    protected function getKeyBuried($queue)
    {
        return $this->getKey($queue, self::STATE_BURIED);
    }


    // endregion ***************************************************************

    // region JOB ************************************************************

    /**
     * @param $id
     *
     * @return bool
     */
    public function existsJob($id)
    {
        return !empty($this->getPayload($id));
    }

    /**
     * @param        $queue
     * @param        $id
     * @param int    $delay
     * @param string $priority
     *
     * @return bool
     */
    public function putJob($queue, $id, $delay = 0, $priority = self::PRIORITY_LOW)
    {
        $delay = intval($delay);
        $keyReady = $this->getKeyReady($queue);
        $keyDelayed = $this->getKeyDelayed($queue);
        $redisJobNamespace = $this->getRedisJobNamespace();

        $result = $this->getRedis()->transaction(
            function ($tx) use ($queue, $id, $delay, $keyDelayed, $priority, $keyReady, $redisJobNamespace) {
                /** @var $tx \Predis\Client */
                $payload = self::buildPayload($queue, (($delay) ? self::STATE_DELAYED : self::STATE_READY));
                $tx->hset($redisJobNamespace, $id, $payload);

                if ($delay <= 0) {
                    if (self::PRIORITY_HIGH === $priority) {
                        $tx->lpush($keyReady, $id);
                    } else {
                        $tx->rpush($keyReady, $id);
                    }
                } else {
                    $tx->zadd($keyDelayed, $this->getTime() + $delay, $id);
                }
            }
        );

        return !in_array(false, $result);
    }

    /**
     * @param      $queue
     * @param null $timeout
     *
     * @return null|string
     */
    public function reserveJob($queue, $timeout = null)
    {
        $this->migrate($queue);
        $keyReady = $this->getKeyReady($queue);
        $keyReserved = $this->getKeyReserved($queue);

        if ($this->isStop($queue)) {
            return null;
        }

        if ($timeout > 0) {
            $id = $this->getRedis()->blpop($keyReady, $timeout);
            /**
             *  return [
             *      '0' => queueName ,
             *      '1' => jobId
             *  ]
             */
            $id = (is_array($id)) ? $id[1] : null;
        } else {
            $id = $this->getRedis()->lpop($keyReady);
        }

        if (!is_null($id)) {
            $payload = self::buildPayload($queue, self::STATE_RESERVED);
            $this->getRedis()->hset($this->getRedisJobNamespace(), $id, $payload);
            $this->getRedis()->zadd($keyReserved, $this->getTime(), $id);

            return $id;
        }

        return null;
    }

    /**
     * @param $id
     *
     * @return bool
     */
    public function deleteJob($id)
    {
        $payload = $this->getPayload($id);

        if (!in_array($payload[self::JOB_PAYLOAD_STATE], [self::STATE_RESERVED, self::STATE_BURIED])) {
            return true;
        }

        $keyBuried = $this->getKeyBuried($payload[self::JOB_PAYLOAD_QUEUE]);
        $keyReserved = $this->getKeyReserved($payload[self::JOB_PAYLOAD_QUEUE]);
        $redisJobNamespace = $this->getRedisJobNamespace();

        $result = $this->getRedis()->transaction(
            function ($tx) use ($id, $keyBuried, $keyReserved, $redisJobNamespace) {
                /** @var $tx \Predis\Client */
                $tx->zrem($keyReserved, $id);
                $tx->zrem($keyBuried, $id);
                $tx->hdel($redisJobNamespace, $id);
            }
        );

        return array_count_values($result) >= 1;
    }

    /**
     * @param $id
     *
     * @return bool
     * @throws QueueException
     */
    public function buryJob($id)
    {
        $payload = $this->getPayload($id);

        if ($payload[self::JOB_PAYLOAD_STATE] !== self::STATE_RESERVED) {
            throw new QueueException(' Cannot bury state unsupported!' . $payload[self::JOB_PAYLOAD_STATE]);
        }

        $redisJobNamespace = $this->getRedisJobNamespace();
        $keyBuried = $this->getKeyBuried($payload[self::JOB_PAYLOAD_QUEUE]);
        $keyReserved = $this->getKeyReserved($payload[self::JOB_PAYLOAD_QUEUE]);

        $result = $this->getRedis()->transaction(
            function ($tx) use ($id, $payload, $keyBuried, $keyReserved, $redisJobNamespace) {
                /** @var $tx \Predis\Client */
                $tx->zrem($keyReserved, $id);
                $payload = self::buildPayload($payload[self::JOB_PAYLOAD_QUEUE], self::STATE_BURIED);
                $tx->hset($redisJobNamespace, $id, $payload);
                $tx->zadd($keyBuried, $this->getTime(), $id);
            }
        );

        return !in_array(false, $result);
    }

    /**
     * @param $id
     *
     * @return bool
     * @throws QueueException
     */
    public function kickJob($id)
    {
        $payload = $this->getPayload($id);

        if ($payload[self::JOB_PAYLOAD_STATE] !== self::STATE_BURIED) {
            throw new QueueException(' Cannot kick state unsupported!' . $payload[self::JOB_PAYLOAD_STATE]);
        }

        $keyReady = $this->getKeyReady($payload[self::JOB_PAYLOAD_QUEUE]);
        $keyBuried = $this->getKeyBuried($payload[self::JOB_PAYLOAD_QUEUE]);
        $redisJobNamespace = $this->getRedisJobNamespace();

        $result = $this->getRedis()->transaction(
            function ($tx) use ($id, $payload, $keyBuried, $keyReady, $redisJobNamespace) {
                /** @var $tx \Predis\Client */
                $tx->zrem($keyBuried, $id);
                $payload = self::buildPayload($payload[self::JOB_PAYLOAD_QUEUE], self::STATE_READY);
                $tx->hset($redisJobNamespace, $id, $payload);
                $tx->rpush($keyReady, $id);
            }
        );

        return !in_array(false, $result);
    }

    /**
     * @param     $id
     * @param int $delay
     *
     * @return bool
     * @throws QueueException
     */
    public function releaseJob($id, $delay = 0)
    {
        $payload = $this->getPayload($id);

        if ($payload[self::JOB_PAYLOAD_STATE] !== self::STATE_RESERVED) {
            throw new QueueException(' Cannot release state unsupported! ' . $payload[self::JOB_PAYLOAD_STATE]);
        }

        $keyReady = $this->getKeyReady($payload[self::JOB_PAYLOAD_QUEUE]);
        $keyDelayed = $this->getKeyDelayed($payload[self::JOB_PAYLOAD_QUEUE]);
        $keyReserved = $this->getKeyReserved($payload[self::JOB_PAYLOAD_QUEUE]);
        $redisJobNamespace = $this->getRedisJobNamespace();

        $result = $this->getRedis()->transaction(
            function ($tx) use ($id, $payload, $delay, $keyReady, $keyDelayed, $keyReserved, $redisJobNamespace) {
                /** @var $tx \Predis\Client */
                $tx->zrem($keyReserved, $id);

                $payload = self::buildPayload($payload[self::JOB_PAYLOAD_QUEUE], (($delay)
                    ? self::STATE_DELAYED
                    : self::STATE_READY
                ));
                $tx->hset($redisJobNamespace, $id, $payload);

                if ($delay == 0) {
                    $tx->rpush($keyReady, $id);
                } else {
                    $tx->zadd($keyDelayed, $this->getTime() + $delay, $id);
                }
            }
        );

        return !in_array(false, $result);
    }

    // endregion ***************************************************************

    // region MIGRATIONS *************************************************************

    /**
     * @param null $queue
     *
     * @return bool
     */
    public function migrate($queue = null)
    {
        if (null === $queue) {
            $list = $this->getQueueNames();
            foreach ($list as $item) {
                $this->migrate($item);
            }

            return true;
        }

        $keyReady = $this->getKeyReady($queue);
        $keyDelayed = $this->getKeyDelayed($queue);
        $redisJobNamespace = $this->getRedisJobNamespace();

        $this->getRedis()->transaction(['cas' => true, 'watch' => [$keyReady, $keyDelayed], 'retry' => 10],
            function ($tx) use ($queue, $keyReady, $keyDelayed, $redisJobNamespace) {

                /** @var $tx \Predis\Client */
                $time = $this->getTime();

                // get expired jobs from "delayed queue"
                $jobIds = $tx->zrangebyscore($keyDelayed, '-inf', $time);

                if (count($jobIds) > 0) {
                    // remove jobs from "delayed queue"
                    $tx->multi();
                    $tx->zremrangebyscore($keyDelayed, '-inf', $time);
                    foreach ($jobIds as $id) {
                        $tx->hset($redisJobNamespace, $id, self::buildPayload($queue, self::STATE_READY));
                        $tx->rpush($keyReady, $id);
                    }
                }
            }
        );

        return true;
    }

    // endregion ***************************************************************

    /**
     * @return $this
     */
    protected function clean()
    {
        $this->getRedis()->del($this->getRedisQueueNamespace());
        $this->getRedis()->del($this->getRedisJobNamespace());
        $this->getRedis()->del($this->getRedisQueueStopNamespace());

        return $this;
    }


}