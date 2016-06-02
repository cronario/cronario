<?php

namespace Cronario;

use Cronario\Exception\JobException;
use Cronario\Exception\ResultException;


// IMPORTANT !!!
// use here 'static' not 'self' in child class of \Thread
// cause it is BUG - will get 'Segmentation Error' sometimes


class Manager extends \Thread
{

    /**
     * cant use cause
     * pthreads detected an attempt to call private method Cronario\Manager::setOptions from outside the threading context
     *
     * use TraitOptions;
     */


    const P_WORKER_CLASS = 'workerClass';
    const P_ID = 'id';
    const P_APP_ID = 'appId';
    const P_BOOTSTRAP_FILE = 'bootstrapFile';

    const REDIS_NS_LIVE = 'cronario@manager-live';
    const REDIS_NS_STATS = 'cronario@manager-stats';

    protected $appId;
    protected $id;
    protected $bootstrapFile;
    protected $workerClass;
    protected $startOn;

    protected $eventTriggerSet = []; // need for speed enter for this values


    //region INIT *******************************************************

    /**
     * @param $id
     * @param $appId
     * @param $workerClass
     * @param $bootstrapFile
     */
    public function __construct($id, $appId, $workerClass, $bootstrapFile)
    {
        $this->setId($id);
        $this->setAppId($appId);
        $this->setWorkerClass($workerClass);
        $this->setBootstrapFile($bootstrapFile);

        $this->startOn = time();

        return $this->start(PTHREADS_INHERIT_NONE);
    }

    // endregion *******************************************************


    /**
     * @return mixed
     */
    protected function getBootstrapFile()
    {
        return $this->bootstrapFile;
    }

    /**
     * @param $bootstrapFile
     *
     * @return $this
     */
    protected function setBootstrapFile($bootstrapFile)
    {
        $this->bootstrapFile = $bootstrapFile;

        return $this;
    }

    /**
     * @return mixed
     */
    protected function getAppId()
    {
        return $this->appId;
    }

    /**
     * @param $appId
     *
     * @return $this
     */
    protected function setAppId($appId)
    {
        $this->appId = $appId;

        return $this;
    }

    /**
     * @return mixed
     */
    protected function getId()
    {
        return $this->id;
    }


    /**
     * @param $id
     *
     * @return $this
     */
    protected function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return mixed
     */
    protected function getWorkerClass()
    {
        return $this->workerClass;
    }

    /**
     * @param $workerClass
     *
     * @return $this
     */
    protected function setWorkerClass($workerClass)
    {
        $this->workerClass = $workerClass;

        return $this;
    }

    /**
     * @return mixed
     */
    protected function getStartOn()
    {
        return $this->startOn;
    }


    //endregion *******************************************************


    // region PRODUCER GOODS *************************************************

    /**
     * @var Producer
     */
    protected static $producer;

    /**
     * @return Producer
     * @throws Exception\FacadeException
     */
    protected function getProducer()
    {
        if (null === static::$producer) {
            static::$producer = Facade::getProducer($this->getAppId());
        }

        return static::$producer;
    }

    /**
     * @var \Predis\Client
     */
    protected static $redis;

    /**
     * @return \Predis\Client
     * @throws Exception\FacadeException
     */
    protected function getRedis()
    {
        if (null === static::$redis) {
            static::$redis = static::getProducer()->getRedis();
        }

        return static::$redis;
    }

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected static $logger;

    /**
     * @return \Psr\Log\LoggerInterface
     */
    protected function getLogger()
    {
        if (null === static::$logger) {
            static::$logger = static::getProducer()->getLogger();
        }

        return static::$logger;
    }

    /**
     * @var Queue
     */
    protected static $queue;

    /**
     * @return Queue
     */
    protected function getQueue()
    {
        if (null === static::$queue) {
            static::$queue = static::getProducer()->getQueue();
        }

        return static::$queue;
    }

    // endregion *******************************************************


    //region Stats ***********************************************************

    const EVENT_SUCCESS = 'success';
    const EVENT_FAIL = 'fail';
    const EVENT_ERROR = 'error';
    const EVENT_RETRY = 'retry';
    const EVENT_REDIRECT = 'redirect';

    /**
     * @param $event
     */
    protected function eventTrigger($event)
    {
        $this->eventTriggerSet[$event]++;

        $key1 = $this->buildManagerStatKey();
        $this->getRedis()->hincrby($key1, $event, 1);

        $key2 = $this->buildManagerLiveKey($this->getId());
        $this->getRedis()->hincrby($key2, $event, 1);
    }


    const P_STATS_KEY_NAMESPACE = 'namespace';
    const P_STATS_KEY_APP_ID = 'appId';
    const P_STATS_KEY_WORKER_CLASS = 'workerClass';

    /**
     * @param $key
     *
     * @return array
     */
    public static function parseManagerStatKey($key)
    {
        list($namespace, $appId, $workerClass) = explode(':', $key);

        return [
            static::P_STATS_KEY_NAMESPACE    => $namespace,
            static::P_STATS_KEY_APP_ID       => $appId,
            static::P_STATS_KEY_WORKER_CLASS => $workerClass,
        ];
    }

    /**
     * @return string
     */
    protected function buildManagerStatKey()
    {
        return implode(':', [static::REDIS_NS_STATS, $this->getAppId(), $this->getWorkerClass(),]);
    }

    /**
     * @param $id
     *
     * @return string
     */
    protected function buildManagerLiveKey($id)
    {
        return implode(':', [static::REDIS_NS_LIVE, $this->getAppId(), $id]);
    }


    const P_LIVE_KEY_NAMESPACE = 'namespace';
    const P_LIVE_KEY_APP_ID = 'appId';
    const P_LIVE_KEY_WORKER_CLASS = 'workerClass';
    const P_LIVE_KEY_MANAGER_ID = 'managerId';
    const P_LIVE_KEY_STARTED_TIME = 'started';

    /**
     * @return $this
     */
    protected function startManagerLive()
    {
        $key = $this->buildManagerLiveKey($this->getId());
        $this->getRedis()->hmset($key, [
            static::P_LIVE_KEY_APP_ID       => $this->getAppId(),
            static::P_LIVE_KEY_WORKER_CLASS => $this->getWorkerClass(),
            static::P_LIVE_KEY_MANAGER_ID   => $this->getId(),
            static::P_LIVE_KEY_STARTED_TIME => $this->getStartOn(),
        ]);

        return $this;
    }

    /**
     * @return $this
     */
    protected function finishManagerLive()
    {
        $key = $this->buildManagerLiveKey($this->getId());
        $this->getRedis()->del($key);

        return $this;
    }

    //endregion ***********************************************************


    //region MainLoop ********************************************************

    /**
     * @return bool
     */
    public function run()
    {

        $file = $this->getBootstrapFile();

        require_once($file);

        $this->startManagerLive();

        $workerClass = $this->getWorkerClass();
        $managerId = $this->getId();
        $logger = $this->getLogger();
        $queue = $this->getQueue();
        $logger->info("Manager {$managerId} start work {$workerClass}", [__NAMESPACE__]);


        try {
            $worker = AbstractWorker::factory($workerClass);
        } catch (Exception\WorkerException $ex) {

            $queue->stop($workerClass);
            $logger->warning($ex->getMessage(), [__NAMESPACE__]);
            $logger->info("Manager {$managerId} Queue stop {$workerClass}", [__NAMESPACE__]);
            $logger->info("Manager {$managerId} finish work {$workerClass}", [__NAMESPACE__]);

            return false;
        }

        try {

            $waitForJob
                = $this->getWorkerConfig(AbstractWorker::CONFIG_P_MANAGER_IDLE_DIE_DELAY);

            while ($jobId = $queue->reserveJob($workerClass, $waitForJob)) {

                if ($jobId === false) {
                    $logger->debug("Manager {$managerId} queue is empty so {$workerClass}", [__NAMESPACE__]);
                    break;
                }

                $logger->debug("Manager {$managerId} reserve job {$jobId}", [__NAMESPACE__]);

                $job = null;
                try {
                    /** @var AbstractJob $job */
                    $job = Facade::getStorage($this->getAppId())->find($jobId);
                } catch (JobException $ex) {
                    $logger->warning($ex->getMessage(), [__NAMESPACE__]);
                    $queue->deleteJob($jobId);
                    continue;
                }

                if ($job === null) {
                    $logger->error("Manager {$managerId} job is not instanceof of AbstractJob {$jobId}",
                        [__NAMESPACE__]);
                    $queue->deleteJob($jobId);
                    continue;
                }

                $logger->debug("Manager {$managerId} Worker start working at {$jobId}...", [__NAMESPACE__]);

                // DO JOB!

                /** @var ResultException $result */
                $result = $worker($job);

                $logger->debug("Manager {$managerId}  Worker finish working at {$jobId} : {$result->getGlobalCode()}",
                    [__NAMESPACE__]);

                if ($result instanceof ResultException) {

                    if ($result->isSuccess()) {

                        $queue->deleteJob($jobId);
                        $logger->debug("Manager {$managerId} Job ResultException isSuccess {$jobId}", [__NAMESPACE__]);
                        $this->eventTrigger(self::EVENT_SUCCESS);

                    } elseif ($result->isFailure()) {

                        $queue->deleteJob($jobId);
                        $logger->debug("Manager {$managerId} Job ResultException isFail {$jobId}", [__NAMESPACE__]);
                        $this->eventTrigger(self::EVENT_FAIL);

                    } elseif ($result->isError()) {

                        $queue->buryJob($jobId);
                        $logger->debug("Manager {$managerId} Job ResultException isError {$jobId}", [__NAMESPACE__]);
                        $this->eventTrigger(self::EVENT_ERROR);

                    } elseif ($result->isRetry()) {

                        $logger->debug("Manager {$managerId} Job ResultException isRetry {$jobId}", [__NAMESPACE__]);
                        $job->addAttempts();
                        $job->save(); // important this will saved result to job !!!

                        $attemptCount = $job->getAttempts();
                        $attemptDelay = $job->countAttemptQueueDelay();

                        // can be new worker class "like redirect in sms/alpha to sms"
                        $gatewayClass = $job->getWorkerClass();
                        $queue->deleteJob($jobId);

                        if ($job->hasAttempt()) {
                            $logger->debug("job {$jobId} has {$attemptCount} attempts (max:{$job->getAttemptsMax()}) and will be delayed {$attemptDelay}",
                                [__NAMESPACE__]);

                            $queue->putJob($gatewayClass, $jobId, $attemptDelay);
                            $this->eventTrigger(self::EVENT_RETRY);
                        } else {
                            $logger->debug("job {$jobId} has {$attemptCount} attempts (max:{$job->getAttemptsMax()})  and will have bad result",
                                [__NAMESPACE__]);

                            $job->setResult(new ResultException(ResultException::FAILURE_MAX_ATTEMPTS));
                            $job->save();
                            $this->eventTrigger(self::EVENT_FAIL);
                        }

                    } elseif ($result->isRedirect()) {

                        $queue->deleteJob($jobId);
                        $queue->putJob($job->getWorkerClass(), $jobId);

                        $logger->debug("Job ResultException isRedirect {$jobId} to {$job->getWorkerClass()}",
                            [__NAMESPACE__]);

                        $this->eventTrigger(self::EVENT_REDIRECT);

                    } else {
                        $queue->deleteJob($jobId);
                        $logger->error("Undefined result job id : {$jobId}", [__NAMESPACE__]);
                    }
                } else {
                    $logger->error('job result is not type of AbstractResultException', [__NAMESPACE__]);
                }


                if ($job->getSchedule()) {
                    $newJob = clone $job;
                    $newJob();

                    $logger->debug("Manager {$managerId} catch daemon  shutdown, finish listening queue",
                        [__NAMESPACE__]);
                }

                if ($this->isLimitsExceeded() || $this->isProducerShutDown()) {
                    break;
                }

                $this->waitDelay();

            }
        } catch (\Exception $ex) {
            $logger->warning($ex->getMessage());
        }

        $logger->info("Manager {$managerId} finish work {$workerClass}", [__NAMESPACE__]);
        $this->finishManagerLive();

        return true;
    }

    //endregion ********************************************************

    //region Worker ***********************************************************

    protected static $workerConfig;

    /**
     * @param null $key
     *
     * @return mixed
     */
    protected function getWorkerConfig($key = null)
    {
        /** @var AbstractWorker $class */
        if (null === static::$workerConfig) {
            $class = $this->workerClass;
            static::$workerConfig = $class::getConfig();
        }

        return (null === $key)
            ? static::$workerConfig
            : static::$workerConfig[$key];
    }

    //endregion ***********************************************************

    /**
     *
     */
    protected function waitDelay()
    {
        $sleep = $this->getWorkerConfig(AbstractWorker::CONFIG_P_MANAGER_JOB_DONE_DELAY);
        if ($sleep > 0) {
            $logger = $this->getLogger();
            $managerId = $this->getId();
            $logger->debug("Manager {$managerId} job-done-after-sleep : {$sleep} ...", [__NAMESPACE__]);
            sleep($sleep);
        }
    }

    /**
     * @return bool
     */
    protected function isProducerShutDown()
    {
        if (!$this->getProducer()->isStateStart()) {
            $logger = $this->getLogger();
            $managerId = $this->getId();

            $logger->info("Manager {$managerId} catch daemon  shutdown, finish listening queue", [__NAMESPACE__]);

            return true;
        }

        return false;
    }


    /**
     * @return bool
     */
    protected function isLimitsExceeded()
    {

        $events = $this->eventTriggerSet;
        $wc = $this->getWorkerConfig();
        $managerId = $this->getId();
        $logger = $this->getLogger();

        $jobsDoneCount
            = $events[self::EVENT_SUCCESS] + $events[self::EVENT_FAIL] + $events[self::EVENT_ERROR];

        $lim = $wc[AbstractWorker::CONFIG_P_JOBS_DONE_LIMIT];
        if ($lim > 0 && $lim === $jobsDoneCount) {
            $logger->debug("Manager {$managerId} done limit is equal {$jobsDoneCount}, so finish this manager ...",
                [__NAMESPACE__]);

            return true;
        }

        $lim = $wc[AbstractWorker::CONFIG_P_JOBS_SUCCESS_LIMIT];
        if ($lim > 0 && $lim === $events[self::EVENT_SUCCESS]) {
            $logger->debug("Manager {$managerId} success limit is equal {$events['isSuccess']}, so finish this manager ...",
                [__NAMESPACE__]);

            return true;
        }

        $lim = $wc[AbstractWorker::CONFIG_P_JOBS_FAIL_LIMIT];
        if ($lim > 0 && $lim === $events[self::EVENT_FAIL]) {
            $logger->debug("Manager {$managerId} fail limit is equal {$events['isFail']}, so finish this manager ...",
                [__NAMESPACE__]);

            return true;
        }

        $lim = $wc[AbstractWorker::CONFIG_P_JOBS_RETRY_LIMIT];
        if ($lim > 0 && $lim === $events[self::EVENT_RETRY]) {
            $logger->debug("Manager {$managerId} retry limit is equal {$events['isRetry']}, so finish this manager ...",
                [__NAMESPACE__]);

            return true;
        }

        $lim = $wc[AbstractWorker::CONFIG_P_JOBS_ERROR_LIMIT];
        if ($lim > 0 && $lim === $events[self::EVENT_ERROR]) {
            $logger->debug("Manager {$managerId} error limit is equal {$events['isError']}, so finish this manager ...",
                [__NAMESPACE__]);

            return true;
        }

        $lim = $wc[AbstractWorker::CONFIG_P_JOBS_REDIRECT_LIMIT];
        if ($lim > 0 && $lim === $events[self::EVENT_REDIRECT]) {
            $logger->debug("Manager {$managerId} redirect limit is equal {$events['isRedirect']}, so finish this manager ...",
                [__NAMESPACE__]);

            return true;
        }

        $lim = $wc[AbstractWorker::CONFIG_P_MANAGER_LIFETIME];
        if ($lim > 0 && $this->getStartOn() + $lim <= time()) {
            $logger->debug("Manager {$managerId} lifetime limit {$lim}, so finish this manager ...", [__NAMESPACE__]);

            return true;
        }

        return false;
    }


}