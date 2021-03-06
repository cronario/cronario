<?php

namespace Cronario;

use Cron\CronExpression;
use Cronario\Exception\JobException;
use Cronario\Exception\ResultException;

abstract class AbstractJob implements \Serializable
{
    use \Cronario\TraitOptions;

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->setOptions($options);
        $this->addDefaultData();
        $this->init();
    }

    /**
     * Template Method
     */
    protected function init()
    {

    }

    // region SERIALIZE ******************************************************************

    /**
     * @return string
     */
    public function serialize()
    {
        return serialize($this->data);
    }

    /**
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        $options = unserialize($serialized);
        $this->setOptions($options);
        $this->init();
    }

    // endregion  **************************************************************************


    // region MAGIC *********************************************

    /**
     * @param AbstractJob $parentJob
     *
     * @return ResultException|null
     */
    public function __invoke(AbstractJob $parentJob = null)
    {

        if (null !== $parentJob) {
            $this
                ->setParentId($parentJob->getId())
                ->setAuthor($parentJob->getAuthor());
        }

        $this->save();

        try {
            if ($this->isSync()) {
                $worker = AbstractWorker::factory($this->getWorkerClass());
                $worker($this);
            } else {
                $this->setResult(new ResultException(ResultException::R_QUEUED));
                $this->putIntoQueue();
                $this->save();
            }
        } catch (\Exception $ex) {
            $this->setResult($ex);
            $this->save();
        }

        return $this->getResult();
    }

    /**
     * @throws JobException
     */
    public function __clone()
    {
        $currentTime = time();
        $deleteOn = $this->getDeleteOn() - $this->getCreateOn() + $currentTime;
        $expiredOn = $this->getExpiredOn() - $this->getCreateOn() + $currentTime;

        $this
            ->setCreateOn($currentTime)// already create
            ->setStartOn($currentTime)// current time cause parent job is executed
            ->setDeleteOn($deleteOn)
            ->setExpiredOn($expiredOn)
            ->unsetData(self::P_ID)
            ->unsetData(self::P_RESULT);
    }


    // endregion ************************************************

    // region Storage Queue ***********************************************************

    protected function getProducer()
    {
        return Facade::getProducer($this->getAppId());
    }

    /**
     * @return Storage\StorageInterface
     */
    protected function getStorage()
    {
        return Facade::getStorage($this->getAppId());
    }

    /**
     * @return Queue
     * @throws Exception\FacadeException
     */
    protected function getQueue()
    {
        return $this->getProducer()->getQueue();
    }


    /**
     * @return bool
     */
    public function isStored()
    {
        return $this->hasData(self::P_ID);
    }

    /**
     * @return $this
     */
    public function save()
    {
        $this->getStorage()->save($this);

        return $this;
    }

    /**
     * @return mixed
     */
    public function getQueueDelay()
    {
        $startOn = $this->getStartOn();
        $scheduleDelay = $this->getScheduleDelay();
        if ($scheduleDelay > 0) {
            $startOn = $startOn + $scheduleDelay;
        }
        $delay = $startOn - time();

        return $delay;
    }


    /**
     * @return $this
     */
    public function putIntoQueue()
    {
        $this->getQueue()->putJob(
            $this->getWorkerClass(),
            $this->getId(),
            $this->getQueueDelay(),
            $this->getPriority()
        );

        return $this;
    }

    // endregion ***********************************************************

    // region DATA Default ******************************************************

    const DEFAULT_DELETE_DELAY = '30 day';
    const DEFAULT_EXPIRED_DELAY = '90 day';

    /**
     * @throws JobException
     */
    protected function addDefaultData()
    {
        $currentTime = time();
        if (!$this->hasData(self::P_CREATE_ON)) {
            $this->setCreateOn($currentTime);
        }

        if (!$this->hasData(self::P_START_ON)) {
            $this->setStartOn($currentTime);
        }

        if (!$this->hasData(self::P_DELETE_ON)) {
            $this->setDeleteOn(strtotime(static::DEFAULT_DELETE_DELAY));
        }

        if (!$this->hasData(self::P_EXPIRED_ON)) {
            $this->setExpiredOn(strtotime(static::DEFAULT_EXPIRED_DELAY));
        }

        if (!$this->hasData(self::P_JOB_CLASS)) {
            $this->setJobClass('\\' . ltrim(get_class($this), "\\"));
        }

        if (!$this->hasData(self::P_APP_ID)) {
            $this->setAppId(Producer::DEFAULT_APP_ID);
        }

    }


    // endregion ********************************************************

    // region DATA ******************************************************

    const P_PARAMS = 'params';
    const P_RESULT = 'result';

    const RESULT_P_GLOBAL_CODE = 'global_code';
    const RESULT_P_DATA = 'data';

    /**
     * @param null $key
     * @param null $default
     *
     * @return string|int|null
     */
    public function getData($key = null, $default = null)
    {
        if (null === $key) {
            return $this->data;
        }

        return $this->hasData($key) ? $this->data[$key] : $default;
    }

    /**
     * @param $key
     *
     * @return bool
     */
    public function hasData($key)
    {
        return array_key_exists($key, $this->data);
    }


    /**
     * @param $key
     *
     * @return $this
     */
    public function unsetData($key)
    {
        unset($this->data[$key]);

        return $this;
    }

    /**
     * @param $key
     * @param $value
     *
     * @return $this
     */
    public function setData($key, $value)
    {
        $this->data[$key] = $value;

        return $this;
    }

    /**
     * @param $key
     * @param $value
     *
     * @return $this
     */
    public function addData($key, $value)
    {
        $this->data[$key][] = $value;

        return $this;
    }

    /**
     * @param null $key
     * @param null $default
     *
     * @return string|int|null
     */
    public function getParam($key = null, $default = null)
    {
        if (null === $key) {
            return $this->data[self::P_PARAMS];
        }

        return $this->hasParam($key) ? $this->data[self::P_PARAMS][$key] : $default;
    }

    /**
     * @param $key
     *
     * @return bool
     */
    public function hasParam($key)
    {
        return $this->hasData(self::P_PARAMS) && array_key_exists($key, (array) $this->data[self::P_PARAMS]);
    }


    /**
     * @param array $params
     *
     * @return $this
     */
    public function setParams(array $params = [])
    {
        return $this->setOptions($params);
    }

    /**
     * @param $key
     * @param $value
     *
     * @return $this
     */
    public function setParam($key, $value)
    {
        $this->data[self::P_PARAMS][$key] = $value;

        return $this;
    }

    protected static $resultClass = '\\Cronario\\Exception\\ResultException';

    /**
     * @var ResultException
     */
    protected $result;

    /**
     * @return ResultException|null
     * @throws \Result\RuntimeException
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param      $result
     * @param null $data
     *
     * @return ResultException|mixed
     * @throws \Result\RuntimeException
     */
    public function setResult($result, $data = null)
    {
        if (null === $result) {
            return $this;
        }

        if (is_numeric($result)) {
            if ($result > 1000) {
                $result = ResultException::factory($result, $data);
            } else {
                $result = new static::$resultClass((int) $result, $data);
            }
        } elseif ($result instanceof ResultException) {
            // continue
        } elseif (is_array($result) && isset($result[self::RESULT_P_GLOBAL_CODE])) {
            $result = ResultException::factory($result[self::RESULT_P_GLOBAL_CODE], $result[self::RESULT_P_DATA]);
        } else {
            $result = new ResultException(ResultException::E_INTERNAL, $result);
        }

        if (!empty($data)) {
            $result->addData($data);
        }

        $this->result = $result;
        $this->setData(self::P_RESULT, [
            self::RESULT_P_GLOBAL_CODE => $result->getGlobalCode(),
            self::RESULT_P_DATA        => $result->getData(),
        ]);

        return $this;
    }

    // endregion **************************************************************************

    // region DATA helpers ************************************

    const P_ID = 'id';
    const P_JOB_CLASS = 'jobClass';
    const P_WORKER_CLASS = 'workerClass';

    const P_IS_SYNC = 'sync';
    const P_APP_ID = 'appId';
    const P_PARENT_ID = 'parentId';
    const P_AUTHOR = 'author';
    const P_COMMENT = 'comment';

    const P_SCHEDULE = 'schedule';

    const P_ATTEMPTS = 'attempts';
    const P_ATTEMPT_STRATEGY = 'attemptStrategy';
    const P_ATTEMPT_DELAY = 'attemptDelay';
    const P_ATTEMPT_STRATEGY_T_LINEAR = 'linear';
    const P_ATTEMPT_STRATEGY_T_EXP = 'exponent';
    const P_ATTEMPTS_MAX = 'attemptsMax';
    const DEFAULT_ATTEMPT_DELAY = '5 minute';
    const DEFAULT_ATTEMPT_STRATEGY = self::P_ATTEMPT_STRATEGY_T_LINEAR;
    const DEFAULT_ATTEMPTS_MAX = 5;

    const P_PRIORITY = 'priority';
    const P_PRIORITY_T_LOW = 'low';
    const P_PRIORITY_T_HIGH = 'high';
    const DEFAULT_PRIORITY = self::P_PRIORITY_T_LOW;

    const P_DEBUG = 'debug';
    const P_DEBUG_DATA = 'debugData';

    // ID =====

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->getData(self::P_ID);
    }

    /**
     * @param $id
     *
     * @return AbstractJob
     * @throws JobException
     */
    public function setId($id)
    {
        if ($this->hasData(self::P_ID)) {
            throw new JobException('Cant set id cause it is already sets!');
        }

        return $this->setData(self::P_ID, $id);
    }

    // JobClass =====

    /**
     * @return string
     */
    public function getJobClass()
    {
        return $this->getData(self::P_JOB_CLASS);
    }

    /**
     * @param $class
     *
     * @return AbstractJob
     */
    protected function setJobClass($class)
    {
        return $this->setData(self::P_JOB_CLASS, $class);
    }

    // WorkerClass =====

    /**
     * @var null|string
     */
    protected $workerClass = null;

    /**
     * @return string
     */
    public function getWorkerClass()
    {
        $class = $this->getData(self::P_WORKER_CLASS,
            (null !== $this->workerClass)
                ? $this->workerClass
                : str_replace('\Job', '\Worker', get_class($this))
        );

        return '\\' . ltrim($class, "\\");
    }

    /**
     * @param $class
     *
     * @return AbstractJob
     */
    public function setWorkerClass($class)
    {
        return $this->setData(self::P_WORKER_CLASS, $class);
    }


    // AppId =====

    /**
     * @return array|int|null|string
     */
    public function getAppId()
    {
        return $this->getData(self::P_APP_ID);
    }

    /**
     * @param $appId
     *
     * @return AbstractJob
     */
    public function setAppId($appId)
    {
        return $this->setData(self::P_APP_ID, $appId);
    }



    // ParentId =====

    /**
     * @return int|null|string
     */
    protected function getParentId()
    {
        return $this->getData(self::P_PARENT_ID);
    }

    /**
     * @param $parentId
     *
     * @return AbstractJob
     */
    protected function setParentId($parentId)
    {
        return $this->setData(self::P_PARENT_ID, $parentId);
    }

    // Sync =====

    /**
     * @param $bool
     *
     * @return AbstractJob
     */
    public function setSync($bool)
    {
        return $this->setData(self::P_IS_SYNC, (bool) $bool);
    }

    /**
     * @return bool
     */
    public function isSync()
    {
        return !!$this->getData(self::P_IS_SYNC);
    }

    // Author =====

    /**
     * @return mixed
     */
    public function getAuthor()
    {
        return $this->getData(self::P_AUTHOR);
    }


    /**
     * @param $author
     *
     * @return $this
     */
    public function setAuthor($author)
    {
        return $this->setData(self::P_AUTHOR, $author);
    }

    // Comment =====

    /**
     * @return int|null|string
     */
    public function getComment()
    {
        return $this->getData(self::P_COMMENT);
    }

    /**
     * @param $comment
     *
     * @return AbstractJob
     */
    public function setComment($comment)
    {
        return $this->setData(self::P_COMMENT, $comment);
    }

    // SCHEDULE =====

    /**
     * @return int|null|string
     */
    public function getSchedule()
    {
        return $this->getData(self::P_SCHEDULE);
    }

    /**
     * @return int
     *
     * return -1 : if schedule is not set
     * return x >= 0 : if schedule is set
     */
    public function getScheduleDelay()
    {
        $value = $this->getSchedule();
        if (null === $value) {
            return -1;
        }

        $object = CronExpression::factory($value);

        $format = 'Y-m-d H:i:s';
        $timeFirst = strtotime($object->getNextRunDate()->format($format));
        $timeSecond = strtotime(date($format));

        return (int) $timeFirst - $timeSecond;
    }

    /**
     * @param $schedule
     *
     * @return AbstractJob
     */
    public function setSchedule($schedule)
    {
        return $this->setData(self::P_SCHEDULE, trim($schedule));
    }

    // Attempts =====

    /**
     * @return int
     */
    public function getAttemptDelay()
    {
        return $this->getData(self::P_ATTEMPT_DELAY, self::DEFAULT_ATTEMPT_DELAY);
    }

    /**
     * @param $attemptDelay
     *
     * @return AbstractJob
     */
    public function setAttemptDelay($attemptDelay)
    {
        return $this->setData(self::P_ATTEMPT_DELAY, $attemptDelay);
    }


    /**
     * @return int
     */
    public function countAttemptQueueDelay()
    {
        $strategy = $this->getAttemptStrategy();
        $attempts = $this->getAttempts();
        $delay = $this->getAttemptDelay();

        if ($attempts === 0) {
            return 0;
        }

        return (int) (($strategy === self::P_ATTEMPT_STRATEGY_T_EXP)
            ? round($delay * exp($attempts - 1))
            : round($delay));
    }


    /**
     * @return int|null|string
     */
    public function getAttemptsMax()
    {
        return $this->getData(self::P_ATTEMPTS_MAX, self::DEFAULT_ATTEMPTS_MAX);
    }

    /**
     * @param $attemptMax
     *
     * @return AbstractJob
     */
    public function setAttemptsMax($attemptMax)
    {
        return $this->setData(self::P_ATTEMPTS_MAX, (int) $attemptMax);
    }

    /**
     * @return int|null|string
     */
    public function getAttemptStrategy()
    {
        return $this->getData(self::P_ATTEMPT_STRATEGY, self::DEFAULT_ATTEMPT_STRATEGY);
    }


    /**
     * @param $attemptStrategy
     *
     * @return AbstractJob
     */
    public function setAttemptStrategy($attemptStrategy)
    {
        return $this->setData(self::P_ATTEMPT_STRATEGY, $attemptStrategy);
    }


    /**
     * @return bool
     */
    public function hasAttempt()
    {
        return ($this->getAttempts() < $this->getAttemptsMax());
    }

    /**
     * @param int $inc
     *
     * @return AbstractJob
     */
    public function addAttempts($inc = 1)
    {
        return $this->setAttempts($this->getAttempts() + (int) $inc);
    }

    /**
     * @param $value
     *
     * @return AbstractJob
     */
    public function setAttempts($value)
    {
        return $this->setData(self::P_ATTEMPTS, (int) $value);
    }

    /**
     * @return int
     */
    public function getAttempts()
    {
        return (int) $this->getData(self::P_ATTEMPTS, 0);
    }

    /// Priority

    /**
     * @return null
     */
    public function getPriority()
    {
        return $this->getData(self::P_PRIORITY, self::DEFAULT_PRIORITY);
    }

    /**
     * @param $priority
     *
     * @return AbstractJob
     */
    public function setPriority($priority)
    {
        return $this->setData(self::P_PRIORITY, $priority);
    }


    /// Debug

    /**
     * @return int|null|string
     */
    public function isDebug()
    {
        return $this->hasData(self::P_DEBUG);
    }

    /**
     * @param $debug
     *
     * @return AbstractJob
     */
    public function setDebug($debug)
    {
        return $this->setData(self::P_DEBUG, is_array($debug) ? $debug : []);
    }

    /**
     * @return null|array
     */
    public function getDebug()
    {
        return $this->getData(self::P_DEBUG, []);
    }

    /**
     * @param $value
     *
     * @return $this|AbstractJob
     */
    public function addDebug($value)
    {
        if (!$this->isDebug()) {
            return $this;
        }

        return $this->addData(
            self::P_DEBUG,
            is_array($value) ? json_encode($value) : $value
        );
    }

    // endregion **********************************************

    // region DATA timing ************************************


    const P_CREATE_ON = 'createOn';
    const P_EXPIRED_ON = 'expiredOn';
    const P_DELETE_ON = 'deleteOn';
    const P_FINISH_ON = 'finishOn';
    const P_START_ON = 'startOn';

    /**
     * @return int|null|string
     */
    public function getCreateOn()
    {
        return $this->getData(self::P_CREATE_ON);
    }

    /**
     * @param $value
     *
     * @return AbstractJob
     * @throws JobException
     */
    public function setCreateOn($value)
    {
        if (!is_int($value)) {
            throw new JobException('Job setExpiredOn can sets only integer type!');
        }

        return $this->setData(self::P_CREATE_ON, $value);
    }

    /**
     * @return null
     */
    public function getExpiredOn()
    {
        return $this->getData(self::P_EXPIRED_ON);
    }

    /**
     * @param $value
     *
     * @return AbstractJob
     * @throws JobException
     */
    public function setExpiredOn($value)
    {
        if (!is_int($value)) {
            throw new JobException('Job setExpiredOn can sets only integer type!');
        }

        return $this->setData(self::P_EXPIRED_ON, $value);
    }

    /**
     * @return int|null|string
     */
    public function getDeleteOn()
    {
        return $this->getData(self::P_DELETE_ON);
    }

    /**
     * @param $value
     *
     * @return AbstractJob
     * @throws JobException
     */
    public function setDeleteOn($value)
    {
        if (!is_int($value)) {
            throw new JobException('Job setStartOn can sets only integer type!');
        }

        return $this->setData(self::P_DELETE_ON, $value);
    }

    /**
     * @return null
     */
    public function getFinishOn()
    {
        return $this->getData(self::P_FINISH_ON);
    }

    /**
     * @return mixed
     */
    public function getStartOn()
    {
        return $this->getData(self::P_START_ON);
    }

    /**
     * @param $value
     *
     * @return AbstractJob
     * @throws JobException
     */
    public function setStartOn($value)
    {
        if (!is_int($value)) {
            throw new JobException('Job setStartOn can sets only integer type!');
        }

        return $this->setData(self::P_START_ON, $value);
    }

    // endregion *********************************************

    // region Callback ******************************************************

    const P_CALLBACKS = 'callbacks';
    const P_CALLBACK_T_SUCCESS = 'onSuccess';
    const P_CALLBACK_T_FAILURE = 'onFail';
    const P_CALLBACK_T_DONE = 'onDone';
    const P_CALLBACK_T_ERROR = 'onError';

    /**
     * @var array
     */
    protected $callbacks = [];

    /**
     * @return array
     */
    public function getCallbacks()
    {
        return $this->callbacks;
    }

    /**
     * @param $type
     *
     * @return array
     */
    public function getCallbacksByType($type)
    {
        if (array_key_exists($type, $this->callbacks) && count($this->callbacks[$type]) > 0) {
            return $this->callbacks[$type];
        }

        return [];
    }

    /**
     * @param array $callbacks
     *
     * @return $this
     * @throws JobException
     */
    public function setCallbacks(array $callbacks = [])
    {
        foreach ($callbacks as $type => $items) {
            foreach ($items as $item) {
                $this->addCallbackJob($type, $item);
            }
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCallbacksSuccess()
    {
        return $this->getCallbacksByType(self::P_CALLBACK_T_SUCCESS);
    }

    /**
     * @param $job
     *
     * @return AbstractJob
     * @throws JobException
     */
    public function setCallbackSuccess($job)
    {
        return $this->addCallbackJob(self::P_CALLBACK_T_SUCCESS, $job);
    }

    /**
     * @return mixed
     */
    public function getCallbacksFailure()
    {
        return $this->getCallbacksByType(self::P_CALLBACK_T_FAILURE);
    }

    /**
     * @param $job
     *
     * @return AbstractJob
     * @throws JobException
     */
    public function setCallbackFailure($job)
    {
        return $this->addCallbackJob(self::P_CALLBACK_T_FAILURE, $job);
    }

    /**
     * @return mixed
     */
    public function getCallbacksDone()
    {
        return $this->getCallbacksByType(self::P_CALLBACK_T_DONE);
    }

    /**
     * @param $job
     *
     * @return AbstractJob
     * @throws JobException
     */
    public function setCallbackDone($job)
    {
        return $this->addCallbackJob(self::P_CALLBACK_T_DONE, $job);
    }

    /**
     * @return mixed
     */
    public function getCallbacksError()
    {
        return $this->getCallbacksByType(self::P_CALLBACK_T_ERROR);
    }

    /**
     * @param $job
     *
     * @return AbstractJob
     * @throws JobException
     */
    public function setCallbackError($job)
    {
        return $this->addCallbackJob(self::P_CALLBACK_T_ERROR, $job);
    }

    /**
     * @param $type
     * @param $job
     *
     * @return $this
     * @throws JobException
     */
    protected function addCallbackJob($type, $job)
    {
        if (is_object($job) && $job instanceof self) {
            $this->data[self::P_CALLBACKS][$type][] = serialize($job);
            $this->callbacks[$type][] = $job;

            return $this;
        }

        if (is_string($job)) {
            $this->data[self::P_CALLBACKS][$type][] = $job;
            $this->callbacks[$type][] = unserialize($job);

            return $this;
        }

        throw new JobException('Callback job must be a serialized string OR object!');
    }

    // endregion ************************************************************
}
