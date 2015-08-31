<?php

namespace Cronario;

use Cronario\Exception\ProducerException;
use Cronario\Exception\WorkerException;
use Cronario\Logger\LoggerInterface;
use Cronario\Storage\StorageInterface;

class Producer
{
    use TraitOptions;

    /**
     * @param array $options
     *
     * @throws ProducerException
     */
    public function __construct(array $options = [])
    {
        $this->setOptions($options);

        if (!isset($options[self::P_APP_ID])) {
            $this->appId = self::DEFAULT_APP_ID;
        }

        if (!isset($options[self::P_QUEUE]) || !($options[self::P_QUEUE] instanceof Queue)) {
            $this->queue = new Queue();
            $this->queue->setProducer($this);
        }

        if (!isset($options[self::P_LOGGER]) || !($options[self::P_LOGGER] instanceof LoggerInterface)) {
            $this->logger = new Logger\Journal();
        }

        if (!isset($options[self::P_REDIS]) || !($options[self::P_REDIS] instanceof \Predis\Client)) {
            $this->redis = new \Predis\Client(self::DEFAULT_REDIS_SERVER);
        }

        if (!isset($options[self::P_STORAGE]) || !($options[self::P_STORAGE] instanceof StorageInterface)) {
            $this->storage = new Storage\Redis($this->appId);
        }

    }


    // region OPTIONS and CONFIGURATION ******************************************

    const P_APP_ID = 'appId';
    const DEFAULT_APP_ID = 'default';
    const P_QUEUE = 'queue';
    const P_LOGGER = 'logger';
    const P_REDIS = 'redis';
    const P_STORAGE = 'storage';
    const P_CONFIG = 'config';

    const DEFAULT_REDIS_SERVER = '127.0.0.1:6379';

    protected $appId;
    protected $queue;
    protected $logger;
    protected $redis;

    /**
     * @var Storage\StorageInterface
     */
    protected $storage;

    /**
     * @return string
     */
    public function getAppId()
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
     * @return Queue
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * @param Queue $queue
     *
     * @return $this
     * @throws Exception\QueueException
     */
    protected function setQueue(Queue $queue)
    {
        $this->queue = $queue;
        $this->queue->setProducer($this);

        return $this;
    }


    /**
     * @return Logger\LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return $this
     */
    protected function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @return \Predis\Client
     */
    public function getRedis()
    {
        return $this->redis;
    }

    /**
     * @param \Predis\Client $redis
     *
     * @return $this
     */
    protected function setRedis(\Predis\Client $redis)
    {
        $this->redis = $redis;

        return $this;
    }

    /**
     * @return StorageInterface
     */
    public function getStorage()
    {
        return $this->storage;
    }

    /**
     * @param StorageInterface $storage
     *
     * @return $this
     */
    protected function setStorage(StorageInterface $storage)
    {
        $this->storage = $storage;

        return $this;
    }


    const CONFIG_BOOTSTRAP_FILE = 'bootstrap_file';
    const CONFIG_SLEEP_MAIN_LOOP = 'sleep_main_loop';
    const CONFIG_SLEEP_STOP_LOOP = 'sleep_stop_loop';
    const CONFIG_SLEEP_FINISH_MANAGER_LOOP = 'sleep_finish_manager_loop';

    protected $config
        = [
            self::CONFIG_BOOTSTRAP_FILE            => null,
            self::CONFIG_SLEEP_MAIN_LOOP           => 2,
            self::CONFIG_SLEEP_STOP_LOOP           => 2,
            self::CONFIG_SLEEP_FINISH_MANAGER_LOOP => 2,
        ];

    /**
     * @param null $key
     *
     * @return array|int|string|null
     */
    protected function getConfig($key = null)
    {

        return ($key == null)
            ? $this->config
            : $this->config[$key];
    }

    /**
     * @param array $config
     */
    protected function setConfig(array $config)
    {
        $this->config = array_merge($this->config, $config);
    }


    // endregion *********************************************************


    // region DATA redis ************************************************************


    /**
     * @return string
     */
    public function getRedisNamespace()
    {
        return implode(':', [self::REDIS_NS, $this->getAppId()]);
    }

    /**
     * @param null $key
     *
     * @return array|string
     */
    protected function getData($key = null)
    {
        if (null === $key) {
            return $this->getRedis()->hgetall($this->getRedisNamespace());
        }

        return $this->getRedis()->hget($this->getRedisNamespace(), $key);
    }

    /**
     * @param $key
     * @param $value
     */
    protected function setData($key, $value)
    {
        $this->getRedis()->hset($this->getRedisNamespace(), $key, $value);
    }

    /**
     * @param $key
     */
    protected function deleteData($key)
    {
        $this->getRedis()->hdel($this->getRedisNamespace(), $key);
    }

    /**
     * @param     $key
     * @param int $by
     */
    protected function incData($key, $by = 1)
    {
        $this->getRedis()->hincrby($this->getRedisNamespace(), $key, $by);
    }

    /**
     * @param     $key
     * @param int $by
     */
    protected function decData($key, $by = 1)
    {
        $this->getRedis()->hincrby($this->getRedisNamespace(), $key, (-1) * $by);
    }

    /**
     *
     */
    protected function cleanData()
    {
        $this->getRedis()->del($this->getRedisNamespace());
        $this->getLogger()->trace("Daemon clean state");
    }


    // endregion ***************************************************************

    // region ProcessId ************************************************************

    /**
     * @return array|string
     */
    protected function getProcessId()
    {
        return (int) $this->getData(self::P_PID);
    }

    /**
     * @param $id
     */
    protected function setProcessId($id)
    {
        $this->setData(self::P_PID, $id);
        $this->getLogger()->trace("Daemon set process id  : {$id}");
    }

    // endregion ***************************************************************

    // region STATE redis ************************************************************

    /**
     * @return bool
     */
    public function isStateStart()
    {
        return $this->isState(self::STATE_T_START);
    }

    /**
     * @param $state
     *
     * @return bool
     */
    protected function isState($state)
    {
        $current = $this->getState();

        return (is_array($state))
            ? in_array($current, $state)
            : $current == $state;
    }

    /**
     * @return array|string
     */
    protected function getState()
    {
        return $this->getData(self::P_STATE);
    }

    /**
     * @param $state
     */
    protected function setState($state)
    {
        $this->setData(self::P_STATE, $state);
        $this->getLogger()->trace("Daemon set state : {$state}");
    }

    // endregion ***************************************************************

    // region DAEMON ************************************************************

    const REDIS_NS = 'cronario:producer';

    const P_STATE = 'state';  // redis : daemon:state = 'start'
    const P_LAST_CIRCLE = 'circle'; // last time of circle
    const P_PID = 'pid';    // redis : daemon:pid = 123
    const P_PID_EXISTS = 'pid_exists';
    const P_MEMORY_USAGE = 'm_usage';
    const P_MEMORY_PEAK_USAGE = 'mp_usage';
    const P_CREATED_TIME = 'created';

    const STATE_T_START = 'start';
    const STATE_T_STOP = 'stop';
    const STATE_T_KILL = 'kill';

    /**
     * @return $this
     */
    protected function updateInfo()
    {
        $this->setData(self::P_LAST_CIRCLE, time());
        $this->setData(self::P_MEMORY_USAGE, memory_get_usage());
        $this->setData(self::P_MEMORY_PEAK_USAGE, memory_get_peak_usage());

        return $this;
    }

    // endregion ***************************************************************

    // region MANAGER ************************************************************

    protected $managerSet = [];
    protected $managerIgnoreSet = [];

    /**
     * @param $workerClass
     * @param $managerId
     *
     * @return string
     */
    protected function buildManagerId($workerClass, $managerId)
    {
        return implode('@', [$this->getAppId(), $workerClass, $managerId]);
    }

    /**
     * @param $string
     *
     * @return array
     */
    protected function parseManagerId($string)
    {
        list($appId, $workerClass, $managerId) = explode('@', $string);

        return [
            Manager::P_APP_ID       => $appId,
            Manager::P_WORKER_CLASS => $workerClass,
            Manager::P_ID           => $managerId,
        ];
    }

    /**
     * @return int
     */
    protected function countManagerSet()
    {
        return count($this->managerSet);
    }

    /**
     * Clean Manager SET
     *
     * Checking each manager in ManagerSet and delete manager that done theirs work
     *
     * @return $this
     */
    protected function cleanManagerSet()
    {
        foreach ($this->managerSet as $managerKey => $manager) {

            /** @var $manager \Thread */
            if (!$manager->isRunning()) {
                // $manager->join();
                $this->getLogger()->trace("Daemon clean old manager : {$managerKey}");
                unset($this->managerSet[$managerKey]);
            }
        }

        return $this;
    }


    /**
     * Update Manager SET
     *
     * REMEMBER queueName === workerClass (cause we have relation one queue name for one type of worker)
     *
     * 1) get Queue stats for all queues on server
     * 2) filter queues (stopping flag / existing ready job in it)
     * 3) get worker configuration (to know what manager balance should do later)
     *    - if worker have problems with config we will add him to ignore manager set
     * 4) then we try to create manager depend on exists managerSet and current balance
     *
     * @return $this
     */
    protected function updateManagerSet()
    {
        $queueStatsServer = $this->getQueue()->getStats();

        if (!isset($queueStatsServer[Queue::STATS_QUEUES]) || count($queueStatsServer[Queue::STATS_QUEUES]) == 0) {
            return $this;
        }

        /**
         * filter if queue not stopped
         * filter if queue has jobs
         */
        $managerOptionsSet = [];
        foreach ($queueStatsServer[Queue::STATS_QUEUES] as $workerClass => $queueStats) {

            if (in_array($workerClass, $this->managerIgnoreSet)) {
                continue;
            }

            if (!class_exists($workerClass)) {
                $this->managerIgnoreSet[] = $workerClass;
                continue;
            }

            if ($queueStats[Queue::STATS_QUEUE_STOP]) {
                continue;
            }

            if ($queueStats[Queue::STATS_JOBS_READY] == 0) {
                continue;
            }

            try {

                /** @var AbstractWorker $workerClass */
                $workerConfig = $workerClass::getConfig();

                // we need threads ..
                $managerCount = $this->calcManagerSize(
                    $queueStats[Queue::STATS_JOBS_READY],
                    $workerConfig[AbstractWorker::CONFIG_P_MANAGER_POOL_SIZE],
                    $workerConfig[AbstractWorker::CONFIG_P_MANAGERS_LIMIT]
                );

                /** @var string $workerClass */
                $managerOptionsSet[$workerClass] = $managerCount;

            } catch (WorkerException $ex) {
                $this->managerIgnoreSet[] = $workerClass;
                $this->getLogger()->exception($ex);
                $this->getLogger()->warning("Daemon {$this->getAppId()} will ignore worker class {$workerClass}");
                continue;
            } catch (\Exception $ex) {
                $this->getLogger()->exception($ex);
                continue;
            }

        }

        if (count($managerOptionsSet) == 0) {
            return $this;
        }

        $appId = $this->getAppId();
        $bootstrapFile = $this->getConfig(self::CONFIG_BOOTSTRAP_FILE);

        foreach ($managerOptionsSet as $workerClass => $managerCount) {

            while ($managerCount--) {
                $managerId = $this->buildManagerId($workerClass, $managerCount);
                if (array_key_exists($managerId, $this->managerSet)) {
                    continue;
                }

                $this->getLogger()->trace("Daemon create manager : {$managerId}");
                $this->managerSet[$managerId] = new Manager($managerId, $appId, $workerClass, $bootstrapFile);
            }

        }

        return $this;
    }

    /**
     * @param $countJobsReady
     * @param $managerPoolSize
     * @param $managersLimit
     *
     * @return float
     */
    protected function calcManagerSize($countJobsReady, $managerPoolSize, $managersLimit)
    {
        $managerCount = floor($countJobsReady / $managerPoolSize) + 1;

        return ($managerCount > $managersLimit) ? $managersLimit : $managerCount;
    }

    // endregion ***************************************************************

    // region LOOP ************************************************************


    /**
     * Daemon Main Loop
     * 1) checking if Daemon should finish work (if redis flag is finish then finish loop)
     * 2) updating info about loop and current process
     * 3) Migrate all 'delayed job' to 'ready queue' if time is become
     * 4) Update Manager set with theirs worker balance (create new Managers if needed)
     * 5) Clean Manager if they are finished theirs works
     * 6) loop sleep cause and run again until redis flag is 'start'
     *
     * @return $this
     */
    protected function mainLoop()
    {
        $sleep = $this->getConfig(self::CONFIG_SLEEP_MAIN_LOOP);

        while ($this->isStateStart()) {
            $this->updateInfo();
            $this->getQueue()->migrate();
            $this->updateManagerSet();
            $this->cleanManagerSet();

            sleep($sleep);
        }

        $this->getLogger()->trace("Daemon loop catch \"STOP\" flag (X)");

        return $this;
    }

    /**
     * @return $this
     */
    protected function waitManagersDone()
    {
        $sleep = $this->getConfig(self::CONFIG_SLEEP_FINISH_MANAGER_LOOP);

        while ($this->countManagerSet()) {
            $this->cleanManagerSet();
            $this->getLogger()->trace("Daemon finish threads (wait for 0): {$this->countManagerSet()}");
            sleep($sleep);
        }

        return $this;
    }

    // endregion ***************************************************************

    // region DAEMON ************************************************************

    /**
     * START daemon
     *
     * 1) get
     *
     * @return bool
     */
    public function start()
    {

        $state = $this->getState();

        if (in_array($state, [self::STATE_T_START, self::STATE_T_STOP, self::STATE_T_KILL])) {

            $processId = $this->getProcessId();

            if (!$this->processExists()) {

                $this->getLogger()
                    ->warning("Daemon is ill cant find process {$processId}, try clean data and continue starting");
                $this->cleanData();

                // continue start

            } else {
                $this->getLogger()->trace("Daemon cant START, cause state : {$state}, and process exists {$processId}");

                return false;
            }
        }

        $this->setProcessId($pid = intval(getmypid()));
        $this->setData(self::P_CREATED_TIME, time());
        $this->setState(self::STATE_T_START);

        try {
            $this->mainLoop();
            $this->waitManagersDone();
        } catch (\Exception $ex) {
            $this->getLogger()->exception($ex);
        }

        $this->cleanData();

        return true;
    }

    /**
     * @param bool $async
     *
     * @return bool
     */
    public function stop($async = false)
    {
        $state = $this->getState();

        if (in_array($state, [self::STATE_T_START, self::STATE_T_STOP, self::STATE_T_KILL])
            && !$this->processExists()
        ) {
            $this->getLogger()->warning("Daemon is ill cant find process {$this->getProcessId()}, try clean data");

            $this->cleanData();

            return true;
        }


        if (in_array($state, [self::STATE_T_STOP, self::STATE_T_KILL])) {
            $this->getLogger()->trace("Daemon cant STOP, cause state : {$state}");

            return true;
        }

        if (!in_array($state, [self::STATE_T_START])) {
            $this->getLogger()->trace("Daemon cant STOP, cause daemon not START");

            return true;
        }

        $this->setState(self::STATE_T_STOP);

        // sync mode (wait for stopping loop)

        if ($async) {
            return true;
        }

        $this->getLogger()->trace("Daemon sync STOP, wait loop ...");

        $sleep = $this->getConfig(self::CONFIG_SLEEP_STOP_LOOP);
        while ($this->isState(
            [self::STATE_T_STOP, self::STATE_T_KILL, self::STATE_T_START]
        )) {
            $this->getLogger()->trace("Daemon sync STOP, wait loop .......");
            sleep($sleep);
        }

        return true;
    }

    /**
     * @return bool
     */
    public function kill()
    {

        $state = $this->getState();

        if (in_array($state, [self::STATE_T_KILL])) {
            $this->getLogger()->trace("Daemon cant KILL, cause state : {$state}");

            return true;
        }

        if (!in_array($state, [self::STATE_T_START, self::STATE_T_STOP])) {
            $this->getLogger()->trace("Daemon cant KILL, cause daemon not START or STOP");

            return true;
        }

        $this->setState(self::STATE_T_KILL);

        // ==========================

        $this->getLogger()->trace("Daemon kill process : {$this->getProcessId()}");
        if ($this->processExists()) {
            $this->processKill();
        }
        // ==========================

        $this->cleanData();

        return true;
    }

    /**
     * @return bool
     */
    protected function processExists()
    {
        $processId = $this->getProcessId();
        if ($processId) {
            return shell_exec("ps aux | grep {$processId} | wc -l") > 2;
        }

        return false;
    }

    /**
     * @return string
     */
    protected function processKill()
    {
        $processId = $this->getProcessId();
        $out = shell_exec("kill -9 {$processId}");
        $this->getLogger()->trace("Daemon kill process : {$processId} , out : {$out}");

        return $out;
    }

    /**
     * @return $this
     */
    public function restart()
    {
        $this->getLogger()->trace('Daemon restart');
        $this->stop();
        $this->start();

        return $this;
    }

    /**
     * @return $this
     */
    public function reset()
    {
        $this->getLogger()->trace('Daemon reset');
        $this->kill();
        $this->start();

        return $this;
    }

    /**
     * @return array|string
     */
    public function getStats()
    {
        $stats = $this->getData();
        $stats[self::P_PID_EXISTS] = $this->processExists();

        return $stats;
    }


    // endregion ***************************************************************

}